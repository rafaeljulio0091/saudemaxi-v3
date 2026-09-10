<script setup>
import { ref } from 'vue';
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

const { appointment } = useHealthcareServices();
const { href } = useHealthcare();
const busy = ref(false),
    result = ref(null),
    error = ref('');
async function open() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        result.value = await appointment.emergency();
    } catch (e) {
        error.value = e.userMessage;
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <PageHeader
        title="Falar com um médico agora"
        description="Um atendente recebe você e direciona para o profissional."
    />
    <div class="sm-stack">
        <AppCard>
            <h2>Atendimento por vídeo</h2>
            <p class="sm-muted">
                Clínica médica, pediatria, geriatria e medicina de família, 24
                horas por dia.
            </p>
            <AppButton v-if="!result" class="sm-mt" :busy="busy" @click="open">
                Demonstrar encaminhamento
            </AppButton>
            <AppAlert v-if="result" class="sm-mt">
                {{ result.message }}
            </AppAlert>
            <AppAlert v-if="error" tone="danger" class="sm-mt">
                {{ error }}
                <button class="sm-link" @click="open">Tentar novamente</button>
            </AppAlert>
        </AppCard>
        <AppCard v-if="result">
            <h2>Ao retornar, acompanhe suas consultas</h2>
            <p class="sm-muted">
                O histórico mostra a situação informada pela plataforma.
                Receitas não chegam automaticamente; o envio por foto é um fluxo
                separado.
            </p>
            <AppButton class="sm-mt" :href="href('/consultas')">
                Minhas consultas
            </AppButton>
        </AppCard>
        <AppAlert tone="danger">
            Em caso de emergência, ligue
            <a href="tel:192" class="sm-link">192, SAMU</a>
            . Não espere atendimento por vídeo.
        </AppAlert>
    </div>
</template>
