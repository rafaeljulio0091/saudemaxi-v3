<script setup>
import { nextTick, ref, reactive } from 'vue';
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
const started = ref(false),
    manualHeading = ref(null),
    step = ref(0),
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
async function startScheduling() {
    started.value = true;
    error.value = '';
    state.run();
    await nextTick();
    manualHeading.value?.focus();
}
function stopScheduling() {
    if (busy.value) return;
    started.value = false;
    step.value = 0;
    choice.specialty = null;
    choice.date = null;
    choice.time = null;
    choice.doctor = null;
    result.value = null;
    error.value = '';
    requestId.value = crypto.randomUUID();
}
function pick(item) {
    requestId.value = crypto.randomUUID();
    if (step.value === 0) choice.specialty = item;
    if (step.value === 1) choice.date = item;
    if (step.value === 2) choice.time = item;
    if (step.value === 3) choice.doctor = item;
    step.value++;
    if (step.value < 4) state.run();
}
function back() {
    if (busy.value || result.value) return;
    requestId.value = crypto.randomUUID();
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
                is_real_doctor: Boolean(choice.doctor.is_real),
                request_id: requestId.value,
            });
    } catch (e) {
        error.value = e.userMessage;
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <PageHeader
        title="Agendar uma consulta"
        description="Um passo de cada vez, no horário que funciona para você."
    />
    <AppCard v-if="!started" class="sm-stack-sm">
        <h2>Como você deseja continuar?</h2>
        <AppAlert v-if="context.tenant.regulacao">
            O município também organiza consultas pelo núcleo de regulação. Você
            pode falar com um médico agora ou solicitar um agendamento.
        </AppAlert>
        <p v-else>
            Escolha entre atendimento imediato ou agendamento para uma data e um
            horário disponíveis.
        </p>
        <div class="sm-row">
            <AppButton :href="href('/atendimento')">
                Falar com um médico
            </AppButton>
            <AppButton
                v-if="!context.demo || !context.tenant.regulacao"
                variant="secondary"
                @click="startScheduling"
            >
                Realizar um agendamento
            </AppButton>
        </div>
    </AppCard>
    <form v-else class="sm-stack" @submit.prevent="confirm">
        <div>
            <h2 ref="manualHeading" tabindex="-1">Agendamento manual</h2>
            <p class="sm-muted">
                Selecione a especialidade, o dia, o horário e o profissional.
            </p>
        </div>
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
                            type="button"
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
                                    option.price !== null
                                        ? money(option.price)
                                        : 'Valor não informado'
                                }}
                            </p>
                        </button>
                    </div>
                    <p v-if="step === 3" class="sm-small sm-muted sm-mt">
                        Profissionais filtrados por
                        {{ choice.specialty.name }}, {{ date(choice.date) }} às
                        {{ choice.time }}.
                    </p>
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
                    <dt class="sm-muted">
                        {{
                            context.demo
                                ? 'Valor demonstrativo'
                                : 'Valor informado'
                        }}
                    </dt>
                    <dd>
                        {{
                            choice.doctor.price !== null
                                ? money(choice.doctor.price)
                                : 'Valor não informado'
                        }}
                    </dd>
                </div>
            </dl>
            <AppAlert v-if="context.demo" class="sm-mt">
                Nenhuma cobrança será realizada. O agendamento será salvo apenas
                na demonstração.
            </AppAlert>
            <AppAlert v-else class="sm-mt">
                A consulta será enviada à plataforma de atendimento. Nenhuma
                cobrança será realizada por esta confirmação.
            </AppAlert>
            <AppButton class="sm-mt" type="submit" :busy="busy">
                {{
                    context.demo
                        ? 'Confirmar agendamento demonstrativo'
                        : 'Confirmar agendamento'
                }}
            </AppButton>
        </AppCard>
        <AppCard v-if="result" class="sm-stack-sm">
            <h2>
                {{
                    context.demo
                        ? 'Agendamento demonstrativo registrado'
                        : 'Agendamento confirmado'
                }}
            </h2>
            <p>
                {{ result.especialidade }} · {{ date(result.agendadaPara) }} ·
                {{ result.codigo }}
            </p>
            <AppAlert v-if="context.demo" tone="warning">
                Nenhuma consulta foi criada na plataforma de atendimento. O
                pagamento permanece em aberto na demonstração e pode ser marcado
                pelo perfil gestor.
            </AppAlert>
            <AppAlert v-else>
                A consulta foi registrada na plataforma de atendimento e na
                Saúde Maxi.
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
        <AppButton
            v-if="!result"
            variant="secondary"
            :disabled="busy"
            @click="stopScheduling"
        >
            Cancelar e voltar
        </AppButton>
    </form>
</template>
