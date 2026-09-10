<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/HealthcareGuestLayout.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
defineProps({ status: String });
const form = useForm({});
</script>
<template>
    <GuestLayout>
        <Head title="Verifique seu e-mail" />
        <div class="sm-stack">
            <h1>Falta confirmar seu e-mail.</h1>
            <p>
                Abra o link enviado ao seu e-mail para confirmar seu endereço.
                Se precisar, podemos enviar outro.
            </p>
            <AppAlert v-if="status === 'verification-link-sent'" tone="success">
                Enviamos um novo link de verificação.
            </AppAlert>
            <form @submit.prevent="form.post(route('verification.send'))">
                <AppButton type="submit" :busy="form.processing">
                    Reenviar e-mail de verificação
                </AppButton>
            </form>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="sm-button secondary"
            >
                Sair
            </Link>
        </div>
    </GuestLayout>
</template>
