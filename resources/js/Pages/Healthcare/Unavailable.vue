<script setup>
import Layout from '@/Layouts/HealthcareLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import { useHealthcare } from '@/composables/useHealthcare';
defineOptions({ layout: Layout });
defineProps({ reason: String });
const { href } = useHealthcare();
</script>
<template>
    <AppCard class="sm-state">
        <h1>Não incluído no seu plano</h1>
        <p>{{ reason }}</p>
        <AppButton :href="href('/inicio')">Ver meus serviços</AppButton>
    </AppCard>
</template>
