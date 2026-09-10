<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/HealthcareGuestLayout.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
defineProps({ status: String, canResetPassword: Boolean });
const form = useForm({ email: '' });
function submit() {
    form.post(route('password.email'));
}
</script>
<template>
    <GuestLayout>
        <Head title="Enviar link de recuperação" />
        <div class="sm-stack">
            <div>
                <p class="sm-kicker sm-muted">Saúde Maxi</p>
                <h1>Vamos recuperar seu acesso.</h1>
            </div>
            <AppAlert v-if="status" tone="success">{{ status }}</AppAlert>
            <form class="sm-stack-sm" @submit.prevent="submit">
                <AppField
                    id="email"
                    label="E-mail"
                    type="email"
                    autocomplete="username"
                    v-model="form.email"
                    :error="form.errors.email"
                    required
                />
                <p class="sm-muted">
                    Enviaremos um link para você escolher uma nova senha.
                </p>
                <Link :href="route('login')" class="sm-link">
                    Voltar para entrar
                </Link>
                <AppButton type="submit" :busy="form.processing">
                    Enviar link de recuperação
                </AppButton>
            </form>
        </div>
    </GuestLayout>
</template>
