<script setup>
import { onMounted, ref, reactive } from 'vue';
import { date, money } from '@/utils/healthcareFormat';
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

const { context, href } = useHealthcare();
const { appointment } = useHealthcareServices();
const step = ref(0),
    busy = ref(false),
    error = ref(''),
    result = ref(null);
const choice = reactive({
    specialty: null,
    date: null,
    time: null,
    doctor: null,
});
const requestId = ref(crypto.randomUUID());
const labels = [
    'Especialidade',
    'Dia',
    'Horário',
    'Profissional',
    'Confirmação',
];
const input = () => ({
    specialty_id: choice.specialty?.id,
    date: choice.date,
    time: choice.time,
});
const state = useAsyncState((signal) => {
    if (step.value === 0) return appointment.specialties(signal);
    if (step.value === 1) return appointment.days(input(), signal);
    if (step.value === 2) return appointment.times(input(), signal);
    return appointment.doctors(input(), signal);
});
function pick(item) {
    if (step.value === 0) choice.specialty = item;
    if (step.value === 1) choice.date = item;
    if (step.value === 2) choice.time = item;
    if (step.value === 3) choice.doctor = item;
    step.value++;
    if (step.value < 4) state.run();
}
function back() {
    if (busy.value || result.value) return;
    step.value--;
    if (step.value <= 0) choice.specialty = null;
    if (step.value <= 1) choice.date = null;
    if (step.value <= 2) choice.time = null;
    choice.doctor = null;
    error.value = '';
    state.run();
}
async function confirm() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        if (!result.value)
            result.value = await appointment.create({
                ...input(),
                doctor_id: choice.doctor.id,
                request_id: requestId.value,
            });
    } catch (e) {
        error.value = e.userMessage;
    } finally {
        busy.value = false;
    }
}
onMounted(() => {
    if (!context.value.tenant.regulacao) state.run();
});
</script>
<template>
    <PageHeader
        title="Agendar uma consulta"
        description="Um passo de cada vez, no horário que funciona para você."
    />
    <AppCard v-if="context.tenant.regulacao" class="sm-state">
        <h2>A marcação passa pelo núcleo de regulação</h2>
        <p>
            O município organiza as consultas com especialistas. Procure sua
            unidade de referência.
        </p>
        <AppButton :href="href('/atendimento')">Falar com um médico</AppButton>
    </AppCard>
    <div v-else class="sm-stack">
        <ol class="sm-row" aria-label="Etapas do agendamento">
            <li
                v-for="(label, index) in labels"
                :key="label"
                class="sm-step"
                :aria-current="index === step ? 'step' : undefined"
            >
                {{ index + 1 }}. {{ label }}
            </li>
        </ol>
        <div v-if="step < 4">
            <AsyncState
                :status="state.status.value"
                :error="state.error.value"
                empty="Não há disponibilidade nesta etapa. Tente outro dia ou especialidade."
                @retry="state.run"
            >
                <AppCard>
                    <h2>
                        Escolha
                        {{
                            [
                                'a especialidade',
                                'o dia',
                                'o horário',
                                'o profissional',
                            ][step]
                        }}
                    </h2>
                    <div class="sm-grid sm-mt">
                        <button
                            v-for="(option, index) in state.data.value"
                            :key="index"
                            class="sm-option"
                            @click="pick(option)"
                        >
                            <strong>
                                {{
                                    typeof option === 'object'
                                        ? option.name
                                        : step === 1
                                          ? date(option)
                                          : option
                                }}
                            </strong>
                            <p
                                v-if="typeof option === 'object'"
                                class="sm-small sm-muted"
                            >
                                {{
                                    option.price
                                        ? money(option.price)
                                        : 'Sem custo no plano demonstrativo'
                                }}
                            </p>
                        </button>
                    </div>
                </AppCard>
            </AsyncState>
        </div>
        <AppCard v-else-if="!result">
            <h2>Confira seu agendamento</h2>
            <dl class="sm-stack-sm">
                <div>
                    <dt class="sm-muted">Especialidade</dt>
                    <dd>{{ choice.specialty.name }}</dd>
                </div>
                <div>
                    <dt class="sm-muted">Data e horário</dt>
                    <dd>{{ date(choice.date) }} às {{ choice.time }}</dd>
                </div>
                <div>
                    <dt class="sm-muted">Profissional</dt>
                    <dd>{{ choice.doctor.name }}</dd>
                </div>
                <div>
                    <dt class="sm-muted">Valor demonstrativo</dt>
                    <dd>{{ money(choice.doctor.price) }}</dd>
                </div>
            </dl>
            <AppAlert class="sm-mt">
                Nenhuma cobrança será realizada. O agendamento será salvo apenas
                na demonstração.
            </AppAlert>
            <AppButton class="sm-mt" :busy="busy" @click="confirm">
                Confirmar agendamento demonstrativo
            </AppButton>
        </AppCard>
        <AppCard v-if="result" class="sm-stack-sm">
            <h2>Agendamento demonstrativo registrado</h2>
            <p>
                {{ result.especialidade }} · {{ date(result.agendadaPara) }} ·
                {{ result.codigo }}
            </p>
            <AppAlert tone="warning">
                Nenhuma consulta foi criada na plataforma de atendimento. O
                pagamento permanece em aberto na demonstração e pode ser marcado
                pelo perfil gestor.
            </AppAlert>
            <AppButton :href="href('/consultas')">
                Ver minhas consultas
            </AppButton>
        </AppCard>
        <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>
        <AppButton
            v-if="step > 0 && !result"
            variant="secondary"
            :disabled="busy"
            @click="back"
        >
            ← Voltar uma etapa
        </AppButton>
    </div>
</template>
