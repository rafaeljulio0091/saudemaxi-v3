<script setup>
import { computed, onMounted } from 'vue';
import { greeting } from '@/utils/healthcareFormat';
import { useHealthcareUi } from '@/stores/healthcareUi';
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

const { context, href } = useHealthcare();
const { patient } = useHealthcareServices();
const ui = useHealthcareUi();
const state = useAsyncState((signal) => patient.dashboard(signal));
const suffix = computed(() => (context.value.demo ? ' no cenário' : ''));
const agesMax = computed(() =>
    Math.max(1, ...(state.data.value?.ages || []).map((age) => age.v)),
);
onMounted(state.run);
</script>
<template>
    <div class="sm-stack">
        <section class="sm-hero sm-stack-sm">
            <p class="sm-kicker">Gestão do cuidado</p>
            <h1>{{ greeting() }}. Vamos olhar o dia?</h1>
            <p>
                {{ context.tenant.nome }}
                <template v-if="context.tenant.tipo">
                    · {{ context.tenant.tipo }}
                </template>
            </p>
            <div class="sm-row">
                <span class="sm-badge">Visão de operação</span>
                <span v-if="context.demo" class="sm-badge">
                    Dados demonstrativos
                </span>
            </div>
        </section>
        <AsyncState
            :status="state.status.value"
            :error="state.error.value"
            @retry="state.run"
        >
            <AppCard class="sm-next">
                <p class="sm-kicker sm-muted">Prioridade do dia</p>
                <h2 class="sm-mt">
                    {{
                        state.data.value.unpaid
                            ? state.data.value.unpaid +
                              ' consultas com pagamento em aberto'
                            : 'Acompanhe sua operação'
                    }}
                </h2>
                <p class="sm-muted">
                    Confira a situação dos atendimentos e as pendências de
                    cadastro.
                </p>
                <AppButton class="sm-mt" :href="href('/gestor/consultas')">
                    Abrir consultas
                </AppButton>
            </AppCard>
            <div class="sm-grid sm-grid-4">
                <AppCard
                    v-for="metric in [
                        { key: 'patients', label: 'Pacientes' + suffix },
                        { key: 'consultations', label: 'Consultas' + suffix },
                        { key: 'scheduled', label: 'Agendamentos' },
                        { key: 'unpaid', label: 'Pagamentos em aberto' },
                    ]"
                    :key="metric.key"
                >
                    <p class="sm-muted sm-small">{{ metric.label }}</p>
                    <p class="sm-stat">
                        {{ state.data.value[metric.key] ?? '—' }}
                    </p>
                </AppCard>
            </div>
            <AppCard v-if="!context.demo">
                <p class="sm-muted">
                    Consultas, agendamentos, pagamentos e indicadores do período
                    ainda não têm fonte oficial neste ambiente e não são
                    exibidos.
                </p>
            </AppCard>
            <AppCard v-if="state.data.value.indicators">
                <h2>Indicadores ilustrativos do período</h2>
                <p class="sm-small sm-muted">
                    Os gráficos abaixo são amostras fictícias do protótipo,
                    independentes dos registros do cenário.
                </p>
                <div class="sm-grid sm-grid-4 sm-mt">
                    <div
                        v-for="metric in [
                            { key: 'receitas', label: 'Receitas emitidas' },
                            { key: 'atestados', label: 'Atestados' },
                            { key: 'exames', label: 'Exames solicitados' },
                            {
                                key: 'encaminhamentos',
                                label: 'Encaminhamentos',
                            },
                            { key: 'satisfacao', label: 'Satisfação (%)' },
                            {
                                key: 'esperaProntoAtendimento',
                                label: 'Espera no atendimento',
                            },
                            {
                                key: 'esperaConsultorio',
                                label: 'Espera no consultório',
                            },
                            { key: 'duracaoMedia', label: 'Duração média' },
                        ]"
                        :key="metric.key"
                    >
                        <p class="sm-small sm-muted">{{ metric.label }}</p>
                        <strong>
                            {{ state.data.value.indicators[metric.key] }}
                        </strong>
                    </div>
                </div>
            </AppCard>
            <div class="sm-grid">
                <AppCard v-if="state.data.value.days">
                    <h2>Consultas por dia da semana</h2>
                    <div class="sm-stack-sm">
                        <div
                            v-for="day in state.data.value.days"
                            :key="day.dia"
                        >
                            <div class="sm-row">
                                <span class="sm-grow">{{ day.dia }}</span>
                                <strong>{{ day.v }}</strong>
                            </div>
                            <div class="sm-bar" aria-hidden="true">
                                <span
                                    :style="{ width: (day.v / 74) * 100 + '%' }"
                                />
                            </div>
                        </div>
                    </div>
                </AppCard>
                <AppCard>
                    <h2>Faixas etárias atendidas</h2>
                    <div class="sm-stack-sm">
                        <div
                            v-for="age in state.data.value.ages"
                            :key="age.faixa"
                        >
                            <div class="sm-row">
                                <span class="sm-grow">
                                    {{ age.faixa }} anos
                                </span>
                                <strong>{{ age.v }}</strong>
                            </div>
                            <div class="sm-bar" aria-hidden="true">
                                <span
                                    :style="{
                                        width: (age.v / agesMax) * 100 + '%',
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </AppCard>
            </div>
            <AppCard v-if="state.data.value.hours">
                <h2>Consultas por hora</h2>
                <div class="sm-hour-chart">
                    <div
                        v-for="(value, hour) in state.data.value.hours"
                        :key="hour"
                        :title="hour + 'h: ' + value + ' consultas'"
                        style="min-width: 0; text-align: center"
                    >
                        <div
                            :style="{
                                height: Math.max(4, value * 5) + 'px',
                                background: 'var(--marca-suave)',
                                borderTop: '3px solid var(--marca)',
                            }"
                        />
                        <span class="sm-small">
                            {{ hour % 4 === 0 ? hour + 'h' : '' }}
                        </span>
                    </div>
                </div>
                <details class="sm-mt">
                    <summary>Ver valores por hora</summary>
                    <p
                        v-for="(value, hour) in state.data.value.hours"
                        :key="hour"
                    >
                        {{ hour }}h: {{ value }} consultas
                    </p>
                </details>
            </AppCard>
        </AsyncState>
        <AppCard class="sm-max-invite">
            <div class="sm-row">
                <div class="sm-grow">
                    <h2>MAX ajuda a encontrar o que precisa</h2>
                    <p class="sm-muted">
                        Consulte caminhos para pacientes, planos e integrações.
                    </p>
                </div>
                <AppButton variant="secondary" @click="ui.maxOpen = true">
                    Abrir MAX
                </AppButton>
            </div>
        </AppCard>
    </div>
</template>
