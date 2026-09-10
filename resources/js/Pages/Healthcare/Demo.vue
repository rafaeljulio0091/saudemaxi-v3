<script setup>
import { computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/HealthcareGuestLayout.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
const props = defineProps({ scenarios: Array, plans: Array });
const form = useForm({
    scenario: props.scenarios[0].id,
    profile: 'patient',
    plan_id: null,
    network: 'normal',
});
const plans = computed(() =>
    props.plans.filter((p) => p.cliente === form.scenario),
);
watch(
    () => form.scenario,
    () => {
        form.plan_id = plans.value[0]?.id;
    },
    { immediate: true },
);
</script>
<template>
    <GuestLayout>
        <Head title="Demonstração Saúde Maxi" />
        <div class="sm-stack">
            <div>
                <p class="sm-kicker sm-muted">Ambiente de revisão</p>
                <h1>Conheça o seu novo ambiente.</h1>
            </div>
            <AppAlert tone="warning">
                Use apenas dados fictícios. Esta demonstração não abre
                atendimento, não cobra e não altera registros reais.
            </AppAlert>
            <form
                class="sm-stack-sm"
                @submit.prevent="form.post('/demonstracao/cenario')"
            >
                <div class="sm-field">
                    <label for="scenario">Cenário de cliente</label>
                    <select id="scenario" v-model="form.scenario">
                        <option
                            v-for="scenario in scenarios"
                            :key="scenario.id"
                            :value="scenario.id"
                        >
                            {{ scenario.nome }}
                        </option>
                    </select>
                </div>
                <div class="sm-field">
                    <label for="profile">Perfil demonstrativo</label>
                    <select id="profile" v-model="form.profile">
                        <option value="patient">Paciente</option>
                        <option value="manager">Gestor da clínica</option>
                    </select>
                </div>
                <div class="sm-field">
                    <label for="plan">Plano</label>
                    <select id="plan" v-model="form.plan_id">
                        <option
                            v-for="plan in plans"
                            :key="plan.id"
                            :value="plan.id"
                        >
                            {{ plan.nome }}
                        </option>
                    </select>
                </div>
                <div class="sm-field">
                    <label for="network">Estado dos serviços</label>
                    <select id="network" v-model="form.network">
                        <option value="normal">Funcionando</option>
                        <option value="slow">Carregamento lento</option>
                        <option value="error">Falha de conexão</option>
                        <option value="empty">Sem resultados</option>
                    </select>
                </div>
                <p
                    v-for="(error, key) in form.errors"
                    :key="key"
                    class="sm-error"
                    role="alert"
                >
                    {{ error }}
                </p>
                <AppButton type="submit" :busy="form.processing">
                    Explorar demonstração →
                </AppButton>
            </form>
            <AppButton href="/login" variant="secondary">
                Acessar minha conta real
            </AppButton>
        </div>
    </GuestLayout>
</template>
