<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/HealthcareGuestLayout.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    demoAvailable: Boolean,
});
</script>
<template>
    <GuestLayout>
        <Head title="Seu cuidado, mais perto" />
        <div class="sm-stack">
            <p class="sm-kicker sm-muted">Bem-vindo à Saúde Maxi</p>
            <h1>Como podemos cuidar de você hoje?</h1>
            <p class="sm-muted">
                Entre na sua conta para acompanhar seu acesso. Os serviços de
                saúde são disponibilizados conforme o contrato da sua
                instituição.
            </p>
            <AppButton v-if="canLogin" :href="route('login')">
                Acessar minha conta →
            </AppButton>
            <AppButton
                v-if="canRegister"
                :href="route('register')"
                variant="secondary"
            >
                Primeiro acesso
            </AppButton>
            <AppButton
                v-if="demoAvailable"
                href="/demonstracao"
                variant="secondary"
            >
                Conhecer a demonstração
            </AppButton>
            <a class="sm-link" href="tel:192">Emergência: ligar 192, SAMU</a>
        </div>
    </GuestLayout>
</template>
