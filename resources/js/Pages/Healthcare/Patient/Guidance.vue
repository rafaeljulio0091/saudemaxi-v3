<script setup>
import MaxConversation from '@/Components/Healthcare/MaxConversation.vue';
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
        title="Vamos conversar?"
        description="Orientação para encontrar o próximo cuidado."
    />
    <div class="sm-stack">
        <AppAlert tone="warning">
            O roteiro clínico aguarda validação. Esta conversa demonstra somente
            encaminhamentos, sem avaliação médica.
        </AppAlert>
        <AppCard><MaxConversation /></AppCard>
        <AppAlert>
            Entrada por voz ainda indisponível. Você pode escrever sua mensagem
            ou procurar atendimento.
        </AppAlert>
        <div class="sm-row">
            <AppButton :href="href('/atendimento')">
                Falar com um médico
            </AppButton>
            <AppButton :href="href('/ajuda')" variant="danger">
                Ajuda imediata
            </AppButton>
        </div>
    </div>
</template>
