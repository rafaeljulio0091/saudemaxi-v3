<script setup>
import { reactive, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { brandTokens } from '@/utils/brandColor';
import { initials } from '@/utils/healthcareFormat';
import Layout from '@/Layouts/HealthcareLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
import PageHeader from '@/Components/Healthcare/PageHeader.vue';
import AsyncState from '@/Components/Healthcare/AsyncState.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import { useHealthcare } from '@/composables/useHealthcare';
import { useHealthcareServices } from '@/composables/useHealthcareServices';
import { useAsyncState } from '@/composables/useAsyncState';
defineOptions({ layout: Layout });

const { context } = useHealthcare();
const { plan } = useHealthcareServices();
const draft = reactive({
    nome: context.value.tenant.nome,
    cor: context.value.tenant.cor,
    saudacao: context.value.tenant.saudacao,
});
const valid = computed(() => /^#[0-9a-f]{6}$/i.test(draft.cor));
const busy = ref(false),
    error = ref(''),
    saved = ref(false);
function reset() {
    Object.assign(draft, {
        nome: context.value.tenant.nome,
        cor: context.value.tenant.cor,
        saudacao: context.value.tenant.saudacao,
    });
    saved.value = false;
    error.value = '';
}
async function save() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    saved.value = false;
    try {
        await plan.branding(draft);
        saved.value = true;
        router.reload({ only: ['healthcare'] });
    } catch (e) {
        error.value = e.userMessage;
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <PageHeader
        title="Identidade visual"
        description="Uma cor de marca. Um ambiente com a identidade do seu cliente."
    />
    <div class="sm-grid">
        <AppCard>
            <form class="sm-stack-sm" @submit.prevent="save">
                <AppField
                    id="brand-name"
                    label="Nome exibido"
                    v-model="draft.nome"
                    required
                />
                <div class="sm-field">
                    <label for="brand-color">Cor principal</label>
                    <input id="brand-color" type="color" v-model="draft.cor" />
                </div>
                <AppField
                    id="brand-hex"
                    label="Cor em hexadecimal"
                    v-model="draft.cor"
                    :error="valid ? '' : 'Informe uma cor como #5E5212.'"
                />
                <AppField
                    id="brand-greeting"
                    label="Saudação"
                    v-model="draft.saudacao"
                    required
                />
                <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>
                <AppAlert v-if="saved" tone="success">
                    Identidade atualizada na demonstração.
                </AppAlert>
                <div class="sm-row">
                    <AppButton type="submit" :busy="busy" :disabled="!valid">
                        Salvar identidade
                    </AppButton>
                    <AppButton variant="secondary" @click="reset">
                        Desfazer
                    </AppButton>
                </div>
            </form>
        </AppCard>
        <div class="sm-stack">
            <section
                class="sm-app sm-card sm-brand-preview"
                :style="brandTokens(valid ? draft.cor : context.tenant.cor)"
            >
                <p class="sm-kicker sm-muted sm-mb">Prévia ao vivo</p>
                <div class="sm-hero sm-stack-sm">
                    <span class="sm-brand-symbol">
                        {{ initials(draft.nome) }}
                    </span>
                    <h2>{{ draft.nome }}</h2>
                    <p>{{ draft.saudacao }}</p>
                </div>
                <button class="sm-button primary sm-mt" type="button">
                    Exemplo de botão
                </button>
                <p class="sm-small sm-muted sm-mt">
                    Fundos, bordas e gradientes derivam da cor principal.
                </p>
            </section>
            <AppAlert>
                Envio de logotipo, imagens de atendimento e configuração de
                domínio aguardam integração. A prévia não altera esses serviços.
            </AppAlert>
        </div>
    </div>
</template>
