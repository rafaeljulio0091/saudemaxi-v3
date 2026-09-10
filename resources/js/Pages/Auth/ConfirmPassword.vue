<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/HealthcareGuestLayout.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
defineProps({ status: String, canResetPassword: Boolean });
const form = useForm({ password: '' });
function submit() {
    form.post(route('password.confirm'), { onFinish: () => form.reset() });
}
</script>
<template>
    <GuestLayout>
        <Head title="Confirmar senha" />
        <div class="sm-stack">
            <div>
                <p class="sm-kicker sm-muted">Saúde Maxi</p>
                <h1>Confirme sua senha.</h1>
            </div>
            <AppAlert v-if="status" tone="success">{{ status }}</AppAlert>
            <form class="sm-stack-sm" @submit.prevent="submit">
                <AppField
                    id="password"
                    label="Senha"
                    type="password"
                    autocomplete="current-password"
                    v-model="form.password"
                    :error="form.errors.password"
                    required
                />
                <AppButton type="submit" :busy="form.processing">
                    Confirmar senha
                </AppButton>
            </form>
        </div>
    </GuestLayout>
</template>
