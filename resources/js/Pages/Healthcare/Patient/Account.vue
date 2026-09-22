<script setup>
import { onMounted, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppField from '@/Components/Healthcare/AppField.vue';
import { date } from '@/utils/healthcareFormat';
import { modules } from '@/constants/healthcareNavigation';
import Layout from '@/Layouts/HealthcareLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
import PageHeader from '@/Components/Healthcare/PageHeader.vue';
import AsyncState from '@/Components/Healthcare/AsyncState.vue';
import { useHealthcare } from '@/composables/useHealthcare';
import { useHealthcareServices } from '@/composables/useHealthcareServices';
import { useAsyncState } from '@/composables/useAsyncState';
defineOptions({ layout: Layout });

const { context, moduleEnabled } = useHealthcare();
const { patient } = useHealthcareServices();
const form = reactive({
    id: context.value.patient.id,
    nome: '',
    email: '',
    telefone: '',
});
const busy = ref(false),
    error = ref(''),
    saved = ref(false);
const state = useAsyncState(async (signal) => {
    const value = await patient.account(signal);
    Object.assign(form, {
        nome: value.nome,
        email: value.email,
        telefone: value.telefone,
    });
    return value;
});
async function save() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    saved.value = false;
    try {
        await patient.update(form);
        saved.value = true;
        router.reload({ only: ['healthcare'] });
    } catch (e) {
        error.value = e.userMessage;
    } finally {
        busy.value = false;
    }
}
onMounted(state.run);
</script>
<template>
    <PageHeader
        title="Minha conta"
        description="Seus dados, seu plano e seus cuidados."
    />
    <AsyncState
        :status="state.status.value"
        :error="state.error.value"
        @retry="state.run"
    >
        <div class="sm-grid">
            <AppCard>
                <h2>Informações pessoais</h2>
                <form class="sm-stack-sm" @submit.prevent="save">
                    <AppField
                        id="account-name"
                        label="Nome completo"
                        v-model="form.nome"
                        required
                    />
                    <AppField
                        id="account-email"
                        label="E-mail"
                        type="email"
                        v-model="form.email"
                    />
                    <AppField
                        id="account-phone"
                        label="Telefone"
                        type="tel"
                        v-model="form.telefone"
                    />
                    <p class="sm-small sm-muted">
                        Nascimento: {{ date(state.data.value.nascimento) }}
                    </p>
                    <AppAlert v-if="saved" tone="success">
                        {{
                            context.demo
                                ? 'Dados atualizados na demonstração.'
                                : 'Dados atualizados.'
                        }}
                    </AppAlert>
                    <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>
                    <AppButton type="submit" :busy="busy">
                        {{
                            context.demo
                                ? 'Salvar dados demonstrativos'
                                : 'Salvar dados'
                        }}
                    </AppButton>
                </form>
            </AppCard>
            <div class="sm-stack">
                <AppCard>
                    <h2>{{ context.plan.nome }}</h2>
                    <p>{{ context.tenant.nome }}</p>
                    <p class="sm-muted">
                        Até {{ context.plan.maxDependentes }} dependentes
                    </p>
                    <div class="sm-row sm-mt">
                        <template v-for="module in modules" :key="module.key">
                            <span
                                v-if="moduleEnabled(module.key)"
                                class="sm-badge"
                            >
                                {{ module.label }}
                            </span>
                        </template>
                    </div>
                </AppCard>
                <AppCard>
                    <h2>Idioma</h2>
                    <p>Português do Brasil</p>
                    <p class="sm-muted sm-small sm-mt">
                        Outros idiomas e documentos dependem das opções
                        disponibilizadas pelo atendimento.
                    </p>
                </AppCard>
                <AppCard>
                    <h2>Dependentes</h2>
                    <p>
                        {{ state.data.value.dependentes }} dependentes no
                        cadastro demonstrativo.
                    </p>
                    <p class="sm-muted sm-small sm-mt">
                        O acesso ao histórico de menores aguarda definição de
                        consentimento e permissões.
                    </p>
                </AppCard>
            </div>
        </div>
    </AsyncState>
</template>
