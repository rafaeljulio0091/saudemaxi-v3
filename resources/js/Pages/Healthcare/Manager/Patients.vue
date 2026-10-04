<script setup>
import {
    ref,
    onMounted,
    computed,
    watch,
    reactive,
    onBeforeUnmount,
} from 'vue';
import { Link } from '@inertiajs/vue3';
import AppPagination from '@/Components/Healthcare/AppPagination.vue';
import AppModal from '@/Components/Healthcare/AppModal.vue';
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

const { patient } = useHealthcareServices();
const { href } = useHealthcare();
const search = ref(''),
    status = ref(''),
    holder = ref(''),
    page = ref(1),
    open = ref(false),
    busy = ref(false),
    error = ref(''),
    success = ref('');
const form = reactive({
    name: '',
    social_name: '',
    cpf: '',
    cns: '',
    email: '',
    phone: '',
    birth_date: '',
    holder_cpf: '',
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
const state = useAsyncState((signal) =>
    patient.list(
        {
            search: search.value,
            status: status.value,
            holder: holder.value,
            page: page.value,
            per_page: 10,
        },
        signal,
    ),
);
const rows = computed(() => state.data.value?.results || []);
let debounce;
watch([search, status, holder], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        if (page.value !== 1) page.value = 1;
        else state.run();
    }, 300);
});
watch(page, () => state.run());
onBeforeUnmount(() => clearTimeout(debounce));
async function create() {
    busy.value = true;
    error.value = '';
    success.value = '';
    try {
        await patient.create({ ...form });
        open.value = false;
        Object.assign(form, {
            name: '',
            social_name: '',
            cpf: '',
            cns: '',
            email: '',
            phone: '',
            birth_date: '',
            holder_cpf: '',
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
        await state.run();
        success.value = 'Paciente cadastrado com sucesso.';
    } catch (e) {
        error.value =
            Object.values(e.response?.data?.errors || {})[0]?.[0] ||
            e.userMessage;
    } finally {
        busy.value = false;
    }
}
onMounted(() => {
    state.run();
});
</script>
<template>
    <PageHeader
        title="Pacientes"
        description="Encontre um cadastro e acompanhe as informações permitidas."
    >
        <AppButton @click="open = true">+ Novo paciente</AppButton>
    </PageHeader>
    <div class="sm-stack">
        <AppAlert v-if="success" tone="success">{{ success }}</AppAlert>
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
            <button
                class="sm-button secondary sm-mt"
                @click="
                    search = '';
                    status = '';
                    holder = '';
                "
            >
                Limpar filtros
            </button>
        </AppCard>
        <AsyncState
            :status="state.status.value"
            :error="state.error.value"
            @retry="state.run"
        >
            <AppCard>
                <div class="sm-table-wrap">
                    <table class="sm-table">
                        <caption>
                            Pacientes cadastrados no cliente atual
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Nome</th>
                                <th scope="col">CPF protegido</th>
                                <th scope="col">Titularidade</th>
                                <th scope="col">Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="record in rows" :key="record.id">
                                <td>
                                    <Link
                                        :href="
                                            href(
                                                '/gestor/pacientes/' +
                                                    record.id,
                                            )
                                        "
                                    >
                                        {{ record.nome }}
                                    </Link>
                                </td>
                                <td>{{ record.cpf }}</td>
                                <td>
                                    {{
                                        record.titular
                                            ? 'Titular'
                                            : 'Dependente'
                                    }}
                                </td>
                                <td>
                                    <span
                                        class="sm-badge"
                                        :class="
                                            record.status === 'ACTIVE'
                                                ? 'success'
                                                : ''
                                        "
                                    >
                                        {{
                                            record.status === 'ACTIVE'
                                                ? 'Ativo'
                                                : 'Inativo'
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="!rows.length" class="sm-state" role="status">
                    Nenhum paciente encontrado. Ajuste ou limpe os filtros.
                </p>
                <AppPagination
                    v-model:page="page"
                    :total="state.data.value?.count || 0"
                />
            </AppCard>
        </AsyncState>
    </div>
    <AppModal
        :open="open"
        title="Novo paciente"
        @close="!busy && (open = false)"
    >
        <form class="sm-stack-sm" @submit.prevent="create">
            <AppField
                id="new-name"
                label="Nome completo"
                v-model="form.name"
                required
            />
            <AppField
                id="new-social-name"
                label="Nome social"
                v-model="form.social_name"
            />
            <AppField
                id="new-cpf"
                label="CPF"
                v-model="form.cpf"
                required
                placeholder="000.000.000-00"
            />
            <AppField id="new-cns" label="CNS" v-model="form.cns" />
            <AppField
                id="new-birth"
                label="Nascimento"
                type="date"
                v-model="form.birth_date"
            />
            <AppField
                id="new-email"
                label="E-mail"
                type="email"
                v-model="form.email"
            />
            <AppField id="new-phone" label="Telefone" v-model="form.phone" />
            <AppField
                id="new-holder-cpf"
                label="CPF do titular, se dependente"
                v-model="form.holder_cpf"
                placeholder="000.000.000-00"
            />
            <h3>Endereço</h3>
            <AppField
                id="new-address-street"
                label="Rua"
                v-model="form.address.street"
            />
            <div class="sm-grid">
                <AppField
                    id="new-address-number"
                    label="Número"
                    v-model="form.address.number"
                />
                <AppField
                    id="new-address-complement"
                    label="Complemento"
                    v-model="form.address.complement"
                />
            </div>
            <AppField
                id="new-address-neighborhood"
                label="Bairro"
                v-model="form.address.neighborhood"
            />
            <div class="sm-grid">
                <AppField
                    id="new-address-city"
                    label="Cidade"
                    v-model="form.address.city"
                />
                <AppField
                    id="new-address-state"
                    label="UF"
                    v-model="form.address.state"
                />
                <AppField
                    id="new-address-zip-code"
                    label="CEP"
                    v-model="form.address.zip_code"
                />
            </div>
            <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>
            <AppButton type="submit" :busy="busy">Cadastrar paciente</AppButton>
        </form>
    </AppModal>
</template>
