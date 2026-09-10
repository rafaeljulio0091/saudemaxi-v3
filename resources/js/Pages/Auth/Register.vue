<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/HealthcareGuestLayout.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
defineProps({ status: String, canResetPassword: Boolean });
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
function submit() {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>
<template>
    <GuestLayout>
        <Head title="Criar conta" />
        <div class="sm-stack">
            <div>
                <p class="sm-kicker sm-muted">Saúde Maxi</p>
                <h1>Crie sua conta.</h1>
            </div>
            <AppAlert v-if="status" tone="success">{{ status }}</AppAlert>
            <form class="sm-stack-sm" @submit.prevent="submit">
                <AppField
                    id="name"
                    label="Nome completo"
                    type="text"
                    autocomplete="name"
                    v-model="form.name"
                    :error="form.errors.name"
                    required
                />
                <AppField
                    id="email"
                    label="E-mail"
                    type="email"
                    autocomplete="username"
                    v-model="form.email"
                    :error="form.errors.email"
                    required
                />
                <AppField
                    id="password"
                    label="Senha"
                    type="password"
                    autocomplete="new-password"
                    v-model="form.password"
                    :error="form.errors.password"
                    required
                />
                <AppField
                    id="password_confirmation"
                    label="Confirmar senha"
                    type="password"
                    autocomplete="new-password"
                    v-model="form.password_confirmation"
                    :error="form.errors.password_confirmation"
                    required
                />
                <Link :href="route('login')" class="sm-link">
                    Já tenho uma conta
                </Link>
                <AppButton type="submit" :busy="form.processing">
                    Criar conta
                </AppButton>
            </form>
        </div>
    </GuestLayout>
</template>
