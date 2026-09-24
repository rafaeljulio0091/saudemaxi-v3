<script setup>
import AppField from '@/Components/Healthcare/AppField.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone,
    birthdate: user.birthdate,
});
</script>

<template>
    <h2>Informações pessoais</h2>

    <form
        @submit.prevent="form.patch(route('profile.update'))"
        class="sm-stack-sm"
    >
        <AppField
            id="profile-name"
            label="Nome completo"
            v-model="form.name"
            required
            autofocus
            autocomplete="name"
            :error="form.errors.name"
        />

        <AppField
            id="profile-email"
            label="E-mail"
            type="email"
            v-model="form.email"
            required
            autocomplete="username"
            :error="form.errors.email"
        />

        <AppField
            id="profile-phone"
            label="Telefone"
            type="tel"
            v-model="form.phone"
            autocomplete="tel"
            :error="form.errors.phone"
        />

        <AppField
            id="profile-birthdate"
            label="Nascimento"
            type="date"
            v-model="form.birthdate"
            :error="form.errors.birthdate"
        />

        <AppAlert v-if="mustVerifyEmail && user.email_verified_at === null">
            Seu e-mail ainda não foi verificado.
            <Link
                :href="route('verification.send')"
                method="post"
                as="button"
                class="sm-link"
            >
                Clique aqui para reenviar o e-mail de verificação.
            </Link>
            <p v-if="status === 'verification-link-sent'" class="sm-mt">
                Um novo link de verificação foi enviado para o seu e-mail.
            </p>
        </AppAlert>

        <div class="sm-row">
            <AppButton type="submit" :busy="form.processing">Salvar</AppButton>
            <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
            >
                <span v-if="form.recentlySuccessful" class="sm-muted">
                    Salvo.
                </span>
            </Transition>
        </div>
    </form>
</template>
