<script setup>
import { ref, onMounted, computed, watch, onBeforeUnmount } from 'vue';
import AppPagination from '@/Components/Healthcare/AppPagination.vue';
import StatusBadge from '@/Components/Healthcare/StatusBadge.vue';
import AppModal from '@/Components/Healthcare/AppModal.vue';
import { consultationStatuses } from '@/constants/consultationStatus';
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

const { context } = useHealthcare();
const { appointment } = useHealthcareServices();
const search = ref(''),
    status = ref(''),
    page = ref(1),
    selected = ref(null),
    busy = ref(false),
    error = ref('');
const state = useAsyncState((signal) =>
    appointment.search(
        {
            search: search.value,
            status: status.value,
            page: page.value,
            per_page: 10,
        },
        signal,
    ),
);
const rows = computed(() => state.data.value?.results || []);
let debounce;
watch([search, status], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        if (page.value !== 1) page.value = 1;
        else state.run();
    }, 300);
});
watch(page, () => state.run());
onBeforeUnmount(() => clearTimeout(debounce));
async function pay() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        await appointment.payment({
            code: selected.value.codigo,
            paid: !selected.value.pago,
        });
        selected.value = null;
        await state.run();
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
        :title="
            context.profile === 'manager' ? 'Consultas' : 'Minhas consultas'
        "
        description="Acompanhe agendamentos e atendimentos anteriores."
    />
    <div class="sm-stack">
        <AppCard>
            <div class="sm-grid">
                <AppField
                    id="consultation-search"
                    v-model="search"
                    type="search"
                    label="Buscar por código, especialidade ou profissional"
                />
                <div class="sm-field">
                    <label for="consultation-status">Situação</label>
                    <select id="consultation-status" v-model="status">
                        <option value="">Todas as situações</option>
                        <option
                            v-for="(value, key) in consultationStatuses"
                            :key="key"
                            :value="key"
                        >
                            {{ value.label }}
                        </option>
                    </select>
                </div>
            </div>
        </AppCard>
        <AsyncState
            :status="state.status.value"
            :error="state.error.value"
            empty="Nenhuma consulta registrada neste cenário."
            @retry="state.run"
        >
            <AppCard>
                <div class="sm-table-wrap">
                    <table class="sm-table">
                        <caption>Histórico de consultas</caption>
                        <thead>
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Quando</th>
                                <th scope="col">Especialidade</th>
                                <th scope="col">Profissional</th>
                                <th scope="col">Situação</th>
                                <th v-if="context.demo" scope="col">
                                    Pagamento
                                </th>
                                <th scope="col">Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="consultation in rows"
                                :key="consultation.codigo"
                            >
                                <td>{{ consultation.codigo }}</td>
                                <td>
                                    {{
                                        consultation.agendadaPara
                                            ? date(consultation.agendadaPara) +
                                              ' ' +
                                              consultation.agendadaPara.slice(
                                                  11,
                                                  16,
                                              )
                                            : '-'
                                    }}
                                </td>
                                <td>{{ consultation.especialidade }}</td>
                                <td>
                                    {{ consultation.medico || 'A definir' }}
                                </td>
                                <td>
                                    <StatusBadge
                                        :status="consultation.status"
                                    />
                                </td>
                                <td v-if="context.demo">
                                    <span
                                        class="sm-badge"
                                        :class="
                                            consultation.pago
                                                ? 'success'
                                                : 'warning'
                                        "
                                    >
                                        {{
                                            consultation.pago
                                                ? 'Pago no exemplo'
                                                : 'Em aberto'
                                        }}
                                    </span>
                                </td>
                                <td>
                                    <AppButton
                                        variant="secondary"
                                        @click="
                                            selected = consultation;
                                            error = '';
                                        "
                                    >
                                        Abrir
                                    </AppButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="!rows.length" class="sm-state" role="status">
                    {{
                        search || status
                            ? 'Nenhum resultado para os filtros informados.'
                            : 'Nenhuma consulta registrada neste cenário.'
                    }}
                </p>
                <AppPagination
                    v-model:page="page"
                    :total="state.data.value?.count || 0"
                />
            </AppCard>
        </AsyncState>
    </div>
    <AppModal
        :open="!!selected"
        title="Detalhes da consulta"
        @close="!busy && (selected = null)"
    >
        <div v-if="selected" class="sm-stack-sm">
            <StatusBadge :status="selected.status" />
            <h3>{{ selected.especialidade }}</h3>
            <p>{{ selected.medico || 'Profissional a definir' }}</p>
            <p>{{ date(selected.agendadaPara) }} · {{ selected.codigo }}</p>
            <p v-if="selected.duracao">Duração: {{ selected.duracao }}</p>
            <AppAlert v-if="context.demo">
                O histórico demonstrativo não confirma um atendimento real.
                Cancelamento e documentos dependem da plataforma de atendimento.
            </AppAlert>
            <template v-if="context.demo && context.profile === 'manager'">
                <AppAlert tone="warning">
                    A marcação abaixo altera somente o exemplo. Não realiza
                    cobrança ou estorno.
                </AppAlert>
                <AppButton :busy="busy" @click="pay">
                    {{
                        selected.pago
                            ? 'Marcar em aberto no exemplo'
                            : 'Marcar pagamento no exemplo'
                    }}
                </AppButton>
            </template>
            <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>
        </div>
    </AppModal>
</template>
