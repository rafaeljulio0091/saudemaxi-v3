<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/HealthcareGuestLayout.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';

defineProps({ demoAvailable: Boolean });
const page = usePage();
</script>

<template>
    <GuestLayout>
        <Head title="Minha conta" />
        <div class="sm-stack">
            <p class="sm-kicker sm-muted">Saúde Maxi</p>
            <h1>Olá, {{ page.props.auth.user.name.split(' ')[0] }}.</h1>
            <p class="sm-muted">
                Sua conta está ativa. Os serviços de saúde são disponibilizados
                conforme o contrato da sua instituição. Assim que seu vínculo
                for configurado, seus cuidados aparecerão aqui.
            </p>
            <AppButton v-if="demoAvailable" href="/demonstracao">
                Conhecer a demonstração
            </AppButton>
            <AppButton :href="route('profile.edit')" variant="secondary">
                Meu perfil
            </AppButton>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="sm-button secondary"
            >
                Sair
            </Link>
            <a class="sm-link" href="tel:192">Emergência: ligar 192, SAMU</a>
        </div>
    </GuestLayout>
</template>
