<script setup>
import { Head } from '@inertiajs/vue3';
import Layout from '@/Layouts/HealthcareGuestLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
defineOptions({ layout: Layout });
defineProps({ reason: String });
</script>
<template>
    <Head title="Conta ainda não configurada" />
    <AppCard class="sm-state">
        <h1>Conta ainda não configurada</h1>
        <AppAlert>{{ reason }}</AppAlert>
        <AppButton href="/dashboard" variant="secondary">
            Voltar ao painel
        </AppButton>
    </AppCard>
</template>
