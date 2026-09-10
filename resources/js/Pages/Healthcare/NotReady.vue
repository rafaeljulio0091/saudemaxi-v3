<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/HealthcareGuestLayout.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
</script>
<template>
    <GuestLayout>
        <Head title="Serviço ainda indisponível" />
        <div class="sm-stack">
            <h1>Seu ambiente de saúde ainda não está disponível.</h1>
            <p>
                O acesso aos serviços depende da configuração do seu vínculo com
                o contratante. Entre em contato com o responsável pelo seu
                acesso.
            </p>
            <AppButton href="/dashboard">Voltar à minha conta</AppButton>
        </div>
    </GuestLayout>
</template>
