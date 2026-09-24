<script setup>
import { ref, watch } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
import AppModal from '@/Components/Healthcare/AppModal.vue';
import AppPagination from '@/Components/Healthcare/AppPagination.vue';
import StatusBadge from '@/Components/Healthcare/StatusBadge.vue';
import { consultationStatuses } from '@/constants/consultationStatus';
import { date } from '@/utils/healthcareFormat';
import { navItemsForRole } from '@/utils/dashboardNavigation';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    roleLabel: {
        type: String,
        required: true,
    },
    tenant: {
        type: Object,
        default: null,
    },
    consultations: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    error: {
        type: String,
        default: null,
    },
});

const navItems = navItemsForRole('manager');

const cpf = ref(props.filters.cpf);
const status = ref(props.filters.status);
const doctorCpf = ref(props.filters.doctor_cpf);
const startDateMin = ref(props.filters.start_date_min);
const startDateMax = ref(props.filters.start_date_max);
const page = ref(props.filters.page);
const selected = ref(null);

function reload(overrides = {}) {
    router.get(
        route('healthcare.manager.consultations'),
        {
            cpf: cpf.value,
            status: status.value,
            doctor_cpf: doctorCpf.value,
            start_date_min: startDateMin.value,
            start_date_max: startDateMax.value,
            page: page.value,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

let debounce;
watch([cpf, status, doctorCpf, startDateMin, startDateMax], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        page.value = 1;
        reload({ page: 1 });
    }, 300);
});

function goToPage(nextPage) {
    page.value = nextPage;
    reload({ page: nextPage });
}

function clearFilters() {
    cpf.value = '';
    status.value = '';
    doctorCpf.value = '';
    startDateMin.value = '';
    startDateMax.value = '';
    page.value = 1;
    reload({
        cpf: '',
        status: '',
        doctor_cpf: '',
        start_date_min: '',
        start_date_max: '',
        page: 1,
    });
}
</script>

<template>
    <DashboardLayout
        title="Consultas"
        :nav-items="navItems"
        :role-label="roleLabel"
        :tenant="tenant"
    >
        <div class="sm-stack">
            <div>
                <h1>Consultas</h1>
                <p class="sm-muted sm-mt">
                    Consulte o histórico de atendimentos de um paciente pelo
                    CPF.
                </p>
            </div>

            <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>

            <AppCard>
                <div class="sm-grid">
                    <AppField
                        id="consultation-cpf"
                        v-model="cpf"
                        label="CPF do paciente"
                        placeholder="000.000.000-00"
                        required
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
                    <AppField
                        id="consultation-doctor-cpf"
                        v-model="doctorCpf"
                        label="CPF do médico"
                    />
                    <AppField
                        id="consultation-start-min"
                        v-model="startDateMin"
                        type="date"
                        label="Período: de"
                    />
                    <AppField
                        id="consultation-start-max"
                        v-model="startDateMax"
                        type="date"
                        label="Período: até"
                    />
                </div>
                <button class="sm-button secondary sm-mt" @click="clearFilters">
                    Limpar filtros
                </button>
            </AppCard>

            <AppCard v-if="!cpf">
                <p class="sm-state" role="status">
                    Informe o CPF de um paciente para consultar o histórico de
                    atendimentos.
                </p>
            </AppCard>

            <AppCard v-else>
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
                                <th scope="col">Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(
                                    consultation, index
                                ) in consultations.results"
                                :key="
                                    consultation.code ||
                                    consultation.id ||
                                    index
                                "
                            >
                                <td>
                                    {{
                                        consultation.code ||
                                        consultation.id ||
                                        '-'
                                    }}
                                </td>
                                <td>
                                    {{
                                        consultation.start_date
                                            ? date(consultation.start_date)
                                            : '-'
                                    }}
                                </td>
                                <td>{{ consultation.specialty || '-' }}</td>
                                <td>
                                    {{
                                        consultation.doctor_name ||
                                        consultation.doctor_cpf ||
                                        'A definir'
                                    }}
                                </td>
                                <td>
                                    <StatusBadge
                                        :status="consultation.status"
                                    />
                                </td>
                                <td>
                                    <button
                                        class="sm-button secondary"
                                        @click="selected = consultation"
                                    >
                                        Abrir
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p
                    v-if="!consultations.results.length"
                    class="sm-state"
                    role="status"
                >
                    Nenhuma consulta encontrada para os filtros informados.
                </p>
                <AppPagination
                    :page="page"
                    :total="consultations.count"
                    @update:page="goToPage"
                />
            </AppCard>
        </div>

        <AppModal
            :open="!!selected"
            title="Detalhes da consulta"
            @close="selected = null"
        >
            <div v-if="selected" class="sm-stack-sm">
                <StatusBadge :status="selected.status" />
                <h3>
                    {{ selected.specialty || 'Especialidade não informada' }}
                </h3>
                <p>
                    {{
                        selected.doctor_name ||
                        selected.doctor_cpf ||
                        'Profissional a definir'
                    }}
                </p>
                <p v-if="selected.start_date">
                    {{ date(selected.start_date) }}
                    <template v-if="selected.code">
                        · {{ selected.code }}
                    </template>
                </p>
            </div>
        </AppModal>
    </DashboardLayout>
</template>
