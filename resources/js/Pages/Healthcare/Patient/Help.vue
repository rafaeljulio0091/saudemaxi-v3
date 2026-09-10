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
const signals = [
    'Dor no peito que aperta ou irradia para o braço, o pescoço ou as costas',
    'Falta de ar que atrapalha falar uma frase inteira',
    'Fraqueza de um lado do corpo, boca torta ou fala embolada',
    'Desmaio, confusão ou dificuldade para acordar',
    'Sangramento que não para',
    'Febre alta com manchas na pele ou pescoço duro',
    'Convulsão',
];
</script>
<template>
    <PageHeader title="Ajuda imediata" />
    <div class="sm-stack">
        <AppAlert tone="danger">
            <h2>Se há risco de vida, ligue agora</h2>
            <p class="sm-mt">Não espere atendimento por vídeo.</p>
            <a href="tel:192" class="sm-button danger sm-mt">Ligar 192, SAMU</a>
        </AppAlert>
        <AppCard>
            <h2>Sinais que pedem socorro imediato</h2>
            <ul
                class="sm-stack-sm"
                style="list-style: disc; padding-left: 24px"
            >
                <li v-for="signal in signals" :key="signal">{{ signal }}</li>
            </ul>
        </AppCard>
        <AppCard>
            <h2>Outros caminhos de cuidado</h2>
            <div class="sm-row">
                <AppButton :href="href('/atendimento')">
                    Falar com a equipe médica
                </AppButton>
                <AppButton :href="href('/orientacao')" variant="secondary">
                    Orientação em saúde
                </AppButton>
                <a href="tel:188" class="sm-button secondary">
                    Ligar 188, apoio emocional
                </a>
            </div>
        </AppCard>
    </div>
</template>
