<script setup>
import { onMounted, computed } from 'vue';
import ServiceCard from '@/Components/Healthcare/ServiceCard.vue';
import { modules } from '@/constants/healthcareNavigation';
import { activeStatuses } from '@/constants/consultationStatus';
import { greeting, date } from '@/utils/healthcareFormat';
import { useHealthcareUi } from '@/stores/healthcareUi';
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

const { context, href, moduleEnabled } = useHealthcare();
const { appointment } = useHealthcareServices();
const state = useAsyncState((signal) => appointment.history(signal));
const ui = useHealthcareUi();
const next = computed(
    () =>
        (state.data.value || []).find((c) =>
            activeStatuses.includes(c.status),
        ) ||
        (state.data.value || [])
            .filter((c) => c.status === 'SCHEDULED')
            .sort((a, b) => a.agendadaPara.localeCompare(b.agendadaPara))[0],
);
onMounted(state.run);
</script>
<template>
    <div class="sm-stack">
        <section class="sm-hero sm-stack-sm">
            <p class="sm-kicker">Seu ambiente de cuidado</p>
            <h1>{{ greeting() }}, {{ context.patient.nome.split(' ')[0] }}.</h1>
            <p>{{ context.tenant.saudacao }}</p>
            <div class="sm-row">
                <span class="sm-badge">{{ context.plan.nome }}</span>
                <span class="sm-badge">
                    {{ (state.data.value || []).length }}
                    {{
                        state.data.value?.length === 1
                            ? 'consulta'
                            : 'consultas'
                    }}
                    no histórico
                </span>
                <span v-if="moduleEnabled('atendimento')" class="sm-badge">
                    Atendimento 24h
                </span>
            </div>
        </section>
        <AsyncState
            :status="state.status.value"
            :error="state.error.value"
            @retry="state.run"
        >
            <AppCard class="sm-next">
                <p class="sm-kicker sm-muted">Sua próxima ação</p>
                <h2 class="sm-mt">
                    {{
                        next
                            ? 'Acompanhe sua consulta'
                            : 'Tudo certo por enquanto'
                    }}
                </h2>
                <p class="sm-muted">
                    {{
                        next
                            ? next.especialidade +
                              ', ' +
                              date(next.agendadaPara) +
                              '. Confira os detalhes do atendimento.'
                            : 'Se precisar, seus serviços de cuidado estão logo abaixo.'
                    }}
                </p>
                <AppButton
                    class="sm-mt"
                    :href="
                        href(
                            next || !moduleEnabled('atendimento')
                                ? '/consultas'
                                : '/atendimento',
                        )
                    "
                >
                    {{
                        next
                            ? 'Ver minha consulta'
                            : moduleEnabled('atendimento')
                              ? 'Falar com um médico'
                              : 'Ver minhas consultas'
                    }}
                </AppButton>
            </AppCard>
            <template #empty>
                <AppButton
                    v-if="moduleEnabled('atendimento')"
                    :href="href('/atendimento')"
                >
                    Conhecer atendimento
                </AppButton>
            </template>
        </AsyncState>
        <AppCard class="sm-max-invite">
            <div class="sm-row">
                <span class="sm-service-icon">Ⓜ</span>
                <div class="sm-grow">
                    <h2>MAX está por aqui.</h2>
                    <p class="sm-muted">
                        Uma ajuda para encontrar suas informações e o próximo
                        cuidado.
                    </p>
                </div>
                <AppButton variant="secondary" @click="ui.maxOpen = true">
                    Conversar com MAX
                </AppButton>
            </div>
        </AppCard>
        <h2 v-if="moduleEnabled('atendimento')">
            Cobertura do seu atendimento
        </h2>
        <div v-if="moduleEnabled('atendimento')" class="sm-grid">
            <AppCard>
                <h3>24 horas por dia, todos os dias</h3>
                <p class="sm-muted">
                    Clínica médica, pediatria, geriatria e medicina de família.
                    Um atendente humano direciona você.
                </p>
            </AppCard>
            <AppCard>
                <h3>Nutrição e psicologia</h3>
                <p class="sm-muted">
                    Acolhimento e orientação, de segunda a sexta, das 9h às 23h.
                    A consulta com especialista tem outro formato.
                </p>
            </AppCard>
        </div>
        <h2>Serviços disponíveis</h2>
        <div class="sm-stack-sm">
            <ServiceCard
                v-for="module in modules"
                :key="module.key"
                :title="module.label"
                :description="
                    module.key === 'agendamento' && context.tenant.regulacao
                        ? 'A marcação com especialista passa pelo núcleo de regulação.'
                        : module.description
                "
                :icon="module.icon"
                :href="href('/' + module.key)"
                :enabled="moduleEnabled(module.key)"
            />
        </div>
        <div class="sm-row">
            <AppButton :href="href('/consultas')" variant="secondary">
                Minhas consultas
            </AppButton>
            <AppButton :href="href('/ajuda')" variant="danger">
                Preciso de ajuda agora
            </AppButton>
        </div>
    </div>
</template>
