<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/HealthcareGuestLayout.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
defineProps({ status: String, canResetPassword: Boolean });
const form = useForm({ email: '', password: '', remember: false });
function submit() {
    form.post(route('login'), { onFinish: () => form.reset('password') });
}
</script>
<template>
    <GuestLayout>
        <Head title="Entrar" />
        <div class="sm-stack">
            <div>
                <p class="sm-kicker sm-muted">Saúde Maxi</p>
                <h1>Entre no seu ambiente.</h1>
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
                <AppField
                    id="password"
                    label="Senha"
                    type="password"
                    autocomplete="current-password"
                    v-model="form.password"
                    :error="form.errors.password"
                    required
                />
                <label class="sm-row">
                    <input type="checkbox" v-model="form.remember" />
                    <span>Manter conectado</span>
                </label>
                <div class="sm-row">
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="sm-link"
                    >
                        Esqueci minha senha
                    </Link>
                    <Link :href="route('register')" class="sm-link">
                        Primeiro acesso
                    </Link>
                </div>
                <AppButton type="submit" :busy="form.processing">
                    Entrar
                </AppButton>
            </form>
        </div>
    </GuestLayout>
</template>
