<script setup>
import { onMounted, reactive, ref } from 'vue';
import { date } from '@/utils/healthcareFormat';
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

const props = defineProps({ recordId: String });
const { patient, plan } = useHealthcareServices();
const { href } = useHealthcare();
const form = reactive({}),
    busy = ref(false),
    error = ref(''),
    saved = ref(false);
const state = useAsyncState(async (signal) => {
    const record = await patient.find(props.recordId, signal);
    Object.assign(form, {
        id: record.id,
        nome: record.nome,
        email: record.email,
        telefone: record.telefone,
        planoId: record.planoId,
    });
    return { record, plans: await plan.list(signal) };
});
async function save() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    saved.value = false;
    try {
        state.data.value.record = await patient.update(form);
        saved.value = true;
    } catch (e) {
        error.value = e.userMessage;
    } finally {
        busy.value = false;
    }
}
onMounted(state.run);
</script>
<template>
    <PageHeader title="Ficha do paciente">
        <AppButton :href="href('/gestor/pacientes')" variant="secondary">
            ← Voltar à lista
        </AppButton>
    </PageHeader>
    <AsyncState
        :status="state.status.value"
        :error="state.error.value"
        @retry="state.run"
    >
        <div class="sm-grid">
            <AppCard>
                <h2>{{ state.data.value.record.nome }}</h2>
                <p class="sm-muted">
                    {{ state.data.value.record.cpf }} ·
                    {{
                        state.data.value.record.titular
                            ? 'Titular'
                            : 'Dependente'
                    }}
                </p>
                <form class="sm-stack-sm sm-mt" @submit.prevent="save">
                    <AppField
                        id="edit-name"
                        label="Nome completo"
                        v-model="form.nome"
                        required
                    />
                    <AppField
                        id="edit-email"
                        label="E-mail"
                        v-model="form.email"
                        type="email"
                    />
                    <AppField
                        id="edit-phone"
                        label="Telefone"
                        v-model="form.telefone"
                    />
                    <div class="sm-field">
                        <label for="edit-plan">Plano</label>
                        <select id="edit-plan" v-model="form.planoId">
                            <option
                                v-for="item in state.data.value.plans"
                                :key="item.id"
                                :value="item.id"
                            >
                                {{ item.nome }}
                            </option>
                        </select>
                    </div>
                    <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>
                    <AppAlert v-if="saved" tone="success">
                        Cadastro demonstrativo atualizado.
                    </AppAlert>
                    <AppButton type="submit" :busy="busy">
                        Salvar dados demonstrativos
                    </AppButton>
                </form>
            </AppCard>
            <div class="sm-stack">
                <AppCard>
                    <h2>Cadastro</h2>
                    <p>
                        Nascimento:
                        {{ date(state.data.value.record.nascimento) }}
                    </p>
                    <p>Adesão: {{ date(state.data.value.record.adesao) }}</p>
                    <p>
                        Situação:
                        {{
                            state.data.value.record.status === 'ACTIVE'
                                ? 'Ativo'
                                : 'Inativo'
                        }}
                    </p>
                    <div class="sm-row sm-mt">
                        <span
                            v-for="tag in state.data.value.record.tags"
                            :key="tag"
                            class="sm-badge"
                        >
                            {{ tag }}
                        </span>
                    </div>
                </AppCard>
                <AppAlert>
                    A inativação depende de uma permissão específica da
                    plataforma de atendimento. Esta ação ainda não está
                    disponível.
                </AppAlert>
                <AppAlert>
                    Informações individuais de saúde mental não são apresentadas
                    ao gestor.
                </AppAlert>
            </div>
        </div>
    </AsyncState>
</template>
