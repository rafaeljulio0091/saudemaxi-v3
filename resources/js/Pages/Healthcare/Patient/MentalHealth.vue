<script setup>
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

const { href } = useHealthcare();
</script>
<template>
    <PageHeader
        title="Saúde mental"
        description="Privacidade em cada etapa do cuidado."
    />
    <div class="sm-stack">
        <AppCard class="sm-state">
            <h2>Esta trilha ainda não está disponível</h2>
            <p>
                O acolhimento e o acompanhamento periódico aguardam a definição
                das regras de consentimento e proteção de dados.
            </p>
            <AppAlert>
                O gestor vê informações agregadas do grupo. Nenhuma informação
                individual de saúde mental é apresentada nesta área.
            </AppAlert>
            <AppButton :href="href('/ajuda')">Preciso de ajuda agora</AppButton>
        </AppCard>
    </div>
</template>
