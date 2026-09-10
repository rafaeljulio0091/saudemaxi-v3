<script setup>
import { onMounted, ref } from 'vue';
import AppModal from '@/Components/Healthcare/AppModal.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import { date } from '@/utils/healthcareFormat';
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

const props = defineProps({ recordId: String });
const { pharmacy } = useHealthcareServices();
const { href } = useHealthcare();
const state = useAsyncState((signal) => pharmacy.find(props.recordId, signal));
const editing = ref(null),
    name = ref(''),
    busy = ref(false),
    error = ref('');
const groups = [
    { key: 'coberto', title: 'Cobertura no exemplo', tone: 'success' },
    {
        key: 'confirmar',
        title: 'Precisamos conferir a leitura',
        tone: 'warning',
    },
    { key: 'naoCoberto', title: 'Fora da lista do exemplo', tone: '' },
];
function edit(index) {
    editing.value = index;
    name.value = state.data.value.itens[index].nome;
    error.value = '';
}
async function confirm() {
    if (busy.value) return;
    busy.value = true;
    try {
        state.data.value = await pharmacy.confirm({
            id: props.recordId,
            index: editing.value,
            name: name.value,
        });
        editing.value = null;
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
        title="Confira sua receita"
        description="A confirmação da leitura não comprova cobertura do medicamento."
    />
    <div class="sm-stack">
        <AppAlert tone="warning">
            Todos os resultados são demonstrativos. A cobertura real depende de
            uma fonte oficial. Nunca substitua um medicamento por orientação do
            sistema.
        </AppAlert>
        <AsyncState
            :status="state.status.value"
            :error="state.error.value"
            @retry="state.run"
        >
            <p class="sm-muted">
                {{ date(state.data.value.data) }} ·
                {{ state.data.value.medico }}
            </p>
            <AppCard v-for="group in groups" :key="group.key">
                <h2>{{ group.title }}</h2>
                <template
                    v-for="(item, index) in state.data.value.itens"
                    :key="index"
                >
                    <div
                        v-if="item.cobertura === group.key"
                        class="sm-row sm-mt"
                    >
                        <div class="sm-grow">
                            <strong>{{ item.nome }}</strong>
                            <p class="sm-muted sm-small">
                                {{ item.qtd }} · {{ item.posologia }}
                            </p>
                            <p
                                v-if="group.key === 'confirmar'"
                                class="sm-small"
                            >
                                {{
                                    item.confirmed
                                        ? 'Texto confirmado. Cobertura ainda não verificada.'
                                        : 'Leitura incerta: ' +
                                          Math.round(item.confianca * 100) +
                                          '% de confiança no exemplo.'
                                }}
                            </p>
                        </div>
                        <AppButton
                            v-if="group.key === 'confirmar'"
                            variant="secondary"
                            @click="edit(index)"
                        >
                            {{
                                item.confirmed
                                    ? 'Revisar texto'
                                    : 'Conferir ou corrigir'
                            }}
                        </AppButton>
                        <span v-else class="sm-badge" :class="group.tone">
                            Exemplo
                        </span>
                    </div>
                </template>
                <p
                    v-if="
                        !state.data.value.itens.some(
                            (i) => i.cobertura === group.key,
                        )
                    "
                    class="sm-muted"
                >
                    Nenhum item neste grupo.
                </p>
            </AppCard>
        </AsyncState>
        <div class="sm-row">
            <AppButton :href="href('/farmacias')">
                Ver farmácias próximas
            </AppButton>
            <AppButton variant="secondary" :href="href('/farmacia')">
                Voltar às receitas
            </AppButton>
        </div>
    </div>
    <AppModal
        :open="editing !== null"
        title="Confira o texto da receita"
        @close="editing = null"
    >
        <form class="sm-stack-sm" @submit.prevent="confirm">
            <AppField
                id="item-name"
                v-model="name"
                label="Como está escrito na receita"
                required
            />
            <AppAlert>
                Confirmar o texto mantém a cobertura como não verificada.
            </AppAlert>
            <p v-if="error" role="alert" class="sm-error">{{ error }}</p>
            <AppButton type="submit" :busy="busy">Confirmar texto</AppButton>
        </form>
    </AppModal>
</template>
