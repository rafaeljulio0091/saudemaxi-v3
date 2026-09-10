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

const { patient, plan } = useHealthcareServices();
const { href } = useHealthcare();
const search = ref(''),
    status = ref(''),
    holder = ref(''),
    planId = ref(''),
    page = ref(1),
    open = ref(false),
    busy = ref(false),
    error = ref('');
const form = reactive({
    nome: '',
    email: '',
    telefone: '',
    nascimento: '',
    planoId: null,
});
const plans = ref([]);
const state = useAsyncState((signal) =>
    patient.list(
        {
            search: search.value,
            status: status.value,
            holder: holder.value,
            plan_id: planId.value || null,
            page: page.value,
            per_page: 10,
        },
        signal,
    ),
);
const planState = useAsyncState(async (signal) => {
    plans.value = await plan.list(signal);
    return plans.value;
});
const rows = computed(() => state.data.value?.results || []);
let debounce;
watch([search, status, holder, planId], () => {
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
    try {
        await patient.create({ ...form, planoId: Number(form.planoId) });
        open.value = false;
        Object.assign(form, {
            nome: '',
            email: '',
            telefone: '',
            nascimento: '',
            planoId: null,
        });
        await state.run();
    } catch (e) {
        error.value = e.userMessage;
    } finally {
        busy.value = false;
    }
}
onMounted(() => {
    state.run();
    planState.run();
});
</script>
<template>
    <PageHeader
        title="Pacientes"
        description="Encontre um cadastro e acompanhe as informações permitidas."
    >
        <AppButton @click="open = true">
            ＋ Novo paciente demonstrativo
        </AppButton>
    </PageHeader>
    <div class="sm-stack">
        <AppAlert v-if="planState.error.value" tone="danger">
            {{ planState.error.value }}
            <button class="sm-link" @click="planState.run()">
                Recarregar planos
            </button>
        </AppAlert>
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
                <div class="sm-field">
                    <label for="patient-plan">Plano</label>
                    <select id="patient-plan" v-model="planId">
                        <option value="">Todos os planos</option>
                        <option
                            v-for="item in plans"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.nome }}
                        </option>
                    </select>
                </div>
            </div>
            <button
                class="sm-button secondary sm-mt"
                @click="
                    search = '';
                    status = '';
                    holder = '';
                    planId = '';
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
                        <caption>Pacientes do cenário selecionado</caption>
                        <thead>
                            <tr>
                                <th scope="col">Nome</th>
                                <th scope="col">CPF fictício</th>
                                <th scope="col">Titularidade</th>
                                <th scope="col">Plano</th>
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
                                    {{
                                        plans.find(
                                            (p) => p.id === record.planoId,
                                        )?.nome
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
        title="Novo paciente demonstrativo"
        @close="!busy && (open = false)"
    >
        <form class="sm-stack-sm" @submit.prevent="create">
            <AppAlert>
                Informe somente dados fictícios. O documento será preenchido com
                uma sequência inválida de demonstração.
            </AppAlert>
            <AppField
                id="new-name"
                label="Nome fictício"
                v-model="form.nome"
                required
            />
            <AppField
                id="new-birth"
                label="Nascimento fictício"
                type="date"
                v-model="form.nascimento"
                required
            />
            <AppField
                id="new-email"
                label="E-mail de exemplo"
                type="email"
                v-model="form.email"
            />
            <AppField
                id="new-phone"
                label="Telefone de exemplo"
                v-model="form.telefone"
            />
            <div class="sm-field">
                <label for="new-plan">Plano</label>
                <select id="new-plan" v-model="form.planoId" required>
                    <option :value="null" disabled>Escolha um plano</option>
                    <option
                        v-for="item in plans"
                        :key="item.id"
                        :value="item.id"
                    >
                        {{ item.nome }}
                    </option>
                </select>
            </div>
            <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>
            <AppButton type="submit" :busy="busy">Criar no cenário</AppButton>
        </form>
    </AppModal>
</template>
