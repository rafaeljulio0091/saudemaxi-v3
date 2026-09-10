<script setup>
import { onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { modules } from '@/constants/healthcareNavigation';
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

const { plan } = useHealthcareServices();
const state = useAsyncState((signal) => plan.list(signal));
const busy = ref(false),
    error = ref('');
async function toggle(item, module) {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        const result = await plan.update({
            id: item.id,
            module,
            enabled: !item.modulos[module],
        });
        Object.assign(item, result);
        router.reload({ only: ['healthcare'] });
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
        title="Planos e módulos"
        description="Defina quais serviços cada plano demonstrativo disponibiliza."
    />
    <div class="sm-stack">
        <AppAlert>
            As alterações valem apenas para a demonstração. O plano define os
            módulos; a regulação municipal continua sendo respeitada.
        </AppAlert>
        <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>
        <AsyncState
            :status="state.status.value"
            :error="state.error.value"
            @retry="state.run"
        >
            <AppCard>
                <div class="sm-table-wrap">
                    <table class="sm-table">
                        <caption>Serviços por plano</caption>
                        <thead>
                            <tr>
                                <th scope="col">Plano</th>
                                <th scope="col">Dependentes</th>
                                <th
                                    v-for="module in modules"
                                    :key="module.key"
                                    scope="col"
                                >
                                    {{ module.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in state.data.value" :key="item.id">
                                <th scope="row">{{ item.nome }}</th>
                                <td>{{ item.maxDependentes }}</td>
                                <td v-for="module in modules" :key="module.key">
                                    <button
                                        class="sm-toggle"
                                        role="switch"
                                        :aria-checked="
                                            !!item.modulos[module.key]
                                        "
                                        :aria-label="
                                            module.label +
                                            ' no plano ' +
                                            item.nome
                                        "
                                        :disabled="busy || module.key === 'nr1'"
                                        @click="toggle(item, module.key)"
                                    >
                                        <span
                                            class="sm-toggle-track"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </AppCard>
        </AsyncState>
        <AppCard>
            <h2>Sobre a trilha NR-1</h2>
            <p class="sm-muted">
                A configuração desse módulo permanece bloqueada até a definição
                de consentimento e privacidade. Nenhum relatório individual é
                disponibilizado.
            </p>
        </AppCard>
    </div>
</template>
