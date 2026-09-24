<script setup>
import { ref, watch } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
import AppModal from '@/Components/Healthcare/AppModal.vue';
import AppPagination from '@/Components/Healthcare/AppPagination.vue';
import { navItemsForRole } from '@/utils/dashboardNavigation';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    roleLabel: {
        type: String,
        required: true,
    },
    tenant: {
        type: Object,
        default: null,
    },
    patients: {
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
    status: {
        type: String,
        default: null,
    },
});

const navItems = navItemsForRole('manager');

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const holder = ref(props.filters.holder);
const page = ref(props.filters.page);

function reload(overrides = {}) {
    router.get(
        route('healthcare.manager.patients'),
        {
            search: search.value,
            status: status.value,
            holder: holder.value,
            page: page.value,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

let debounce;
watch([search, status, holder], () => {
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
    search.value = '';
    status.value = '';
    holder.value = '';
    page.value = 1;
    reload({ search: '', status: '', holder: '', page: 1 });
}

const open = ref(false);
const form = useForm({
    name: '',
    cpf: '',
    email: '',
    birth_date: '',
    phone: '',
    holder_cpf: '',
    insurance_card_number: '',
    insurance_plan_code: '',
    plan_adherence_date: '',
    plan_expiry_date: '',
    no_email: false,
    address: {
        street: '',
        number: '',
        complement: '',
        neighborhood: '',
        city: '',
        state: '',
        zip_code: '',
    },
});

function create() {
    form.post(route('healthcare.manager.patients.store'), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}

function closeModal() {
    if (!form.processing) {
        open.value = false;
    }
}
</script>

<template>
    <DashboardLayout
        title="Pacientes"
        :nav-items="navItems"
        :role-label="roleLabel"
        :tenant="tenant"
    >
        <div class="sm-stack">
            <div class="sm-row">
                <div class="sm-grow">
                    <h1>Pacientes</h1>
                    <p class="sm-muted sm-mt">
                        Encontre um cadastro e acompanhe as informações
                        permitidas.
                    </p>
                </div>
                <AppButton @click="open = true">+ Novo paciente</AppButton>
            </div>

            <AppAlert v-if="status" tone="success">{{ status }}</AppAlert>
            <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>

            <AppCard>
                <div class="sm-grid">
                    <AppField
                        id="patient-search"
                        v-model="search"
                        type="search"
                        label="Nome, CPF ou e-mail"
                    />
                    <div class="sm-field">
                        <label for="patient-status">Situação</label>
                        <select id="patient-status" v-model="status">
                            <option value="">Todas</option>
                            <option value="ACTIVE">Ativos</option>
                            <option value="INACTIVE">Inativos</option>
                        </select>
                    </div>
                    <div class="sm-field">
                        <label for="patient-holder">Titularidade</label>
                        <select id="patient-holder" v-model="holder">
                            <option value="">Titulares e dependentes</option>
                            <option value="titular">Titulares</option>
                            <option value="dependente">Dependentes</option>
                        </select>
                    </div>
                </div>
                <button class="sm-button secondary sm-mt" @click="clearFilters">
                    Limpar filtros
                </button>
            </AppCard>

            <AppCard>
                <div class="sm-table-wrap">
                    <table class="sm-table">
                        <caption>Pacientes cadastrados na clínica</caption>
                        <thead>
                            <tr>
                                <th scope="col">Nome</th>
                                <th scope="col">CPF</th>
                                <th scope="col">Titularidade</th>
                                <th scope="col">Plano de saúde</th>
                                <th scope="col">Contato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="record in patients.results"
                                :key="record.cpf"
                            >
                                <td>{{ record.name }}</td>
                                <td>{{ record.cpf }}</td>
                                <td>
                                    {{
                                        record.holder_cpf
                                            ? 'Dependente'
                                            : 'Titular'
                                    }}
                                </td>
                                <td>{{ record.insurance_plan_code || '-' }}</td>
                                <td>
                                    {{ record.email || record.phone || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p
                    v-if="!patients.results.length"
                    class="sm-state"
                    role="status"
                >
                    Nenhum paciente encontrado. Ajuste ou limpe os filtros.
                </p>
                <AppPagination
                    :page="page"
                    :total="patients.count"
                    @update:page="goToPage"
                />
            </AppCard>
        </div>

        <AppModal :open="open" title="Novo paciente" @close="closeModal">
            <form class="sm-stack-sm" @submit.prevent="create">
                <AppField
                    id="new-name"
                    label="Nome completo"
                    v-model="form.name"
                    required
                    :error="form.errors.name"
                />
                <AppField
                    id="new-cpf"
                    label="CPF"
                    v-model="form.cpf"
                    required
                    placeholder="000.000.000-00"
                    :error="form.errors.cpf"
                />
                <AppField
                    id="new-email"
                    label="E-mail"
                    type="email"
                    v-model="form.email"
                    :error="form.errors.email"
                />
                <AppField
                    id="new-birth"
                    label="Nascimento"
                    type="date"
                    v-model="form.birth_date"
                    :error="form.errors.birth_date"
                />
                <AppField
                    id="new-phone"
                    label="Telefone"
                    type="tel"
                    v-model="form.phone"
                    :error="form.errors.phone"
                />
                <AppField
                    id="new-holder-cpf"
                    label="CPF do titular (se dependente)"
                    v-model="form.holder_cpf"
                    :error="form.errors.holder_cpf"
                />

                <h2 class="sm-mt">Plano de saúde</h2>
                <AppField
                    id="new-insurance-card"
                    label="Número da carteirinha"
                    v-model="form.insurance_card_number"
                    :error="form.errors.insurance_card_number"
                />
                <AppField
                    id="new-insurance-plan"
                    label="Código do plano"
                    v-model="form.insurance_plan_code"
                    :error="form.errors.insurance_plan_code"
                />
                <AppField
                    id="new-plan-adherence"
                    label="Adesão ao plano"
                    type="date"
                    v-model="form.plan_adherence_date"
                    :error="form.errors.plan_adherence_date"
                />
                <AppField
                    id="new-plan-expiry"
                    label="Validade do plano"
                    type="date"
                    v-model="form.plan_expiry_date"
                    :error="form.errors.plan_expiry_date"
                />

                <h2 class="sm-mt">Endereço</h2>
                <AppField
                    id="new-address-street"
                    label="Rua"
                    v-model="form.address.street"
                    :error="form.errors['address.street']"
                />
                <div class="sm-grid">
                    <AppField
                        id="new-address-number"
                        label="Número"
                        v-model="form.address.number"
                        :error="form.errors['address.number']"
                    />
                    <AppField
                        id="new-address-complement"
                        label="Complemento"
                        v-model="form.address.complement"
                        :error="form.errors['address.complement']"
                    />
                </div>
                <AppField
                    id="new-address-neighborhood"
                    label="Bairro"
                    v-model="form.address.neighborhood"
                    :error="form.errors['address.neighborhood']"
                />
                <div class="sm-grid">
                    <AppField
                        id="new-address-city"
                        label="Cidade"
                        v-model="form.address.city"
                        :error="form.errors['address.city']"
                    />
                    <AppField
                        id="new-address-state"
                        label="UF"
                        v-model="form.address.state"
                        :error="form.errors['address.state']"
                    />
                    <AppField
                        id="new-address-zip"
                        label="CEP"
                        v-model="form.address.zip_code"
                        :error="form.errors['address.zip_code']"
                    />
                </div>

                <label class="sm-row">
                    <input type="checkbox" v-model="form.no_email" />
                    Não enviar e-mail de boas-vindas
                </label>

                <AppButton type="submit" :busy="form.processing">
                    Cadastrar paciente
                </AppButton>
            </form>
        </AppModal>
    </DashboardLayout>
</template>
