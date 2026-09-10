<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/HealthcareGuestLayout.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
const props = defineProps({ email: String, token: String });
const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
    password_confirmation: '',
});
function submit() {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>
<template>
    <GuestLayout>
        <Head title="Salvar nova senha" />
        <div class="sm-stack">
            <div>
                <p class="sm-kicker sm-muted">Saúde Maxi</p>
                <h1>Escolha uma nova senha.</h1>
            </div>
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
                <AppButton type="submit" :busy="form.processing">
                    Salvar nova senha
                </AppButton>
            </form>
        </div>
    </GuestLayout>
</template>
