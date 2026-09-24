<script setup>
import AppField from '@/Components/Healthcare/AppField.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus?.();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus?.();
            }
        },
    });
};
</script>

<template>
    <h2>Alterar senha</h2>
    <p class="sm-muted sm-mt">
        Use uma senha longa e única para manter sua conta segura.
    </p>

    <form @submit.prevent="updatePassword" class="sm-stack-sm sm-mt">
        <AppField
            id="current_password"
            ref="currentPasswordInput"
            label="Senha atual"
            type="password"
            v-model="form.current_password"
            autocomplete="current-password"
            :error="form.errors.current_password"
        />

        <AppField
            id="password"
            ref="passwordInput"
            label="Nova senha"
            type="password"
            v-model="form.password"
            autocomplete="new-password"
            :error="form.errors.password"
        />

        <AppField
            id="password_confirmation"
            label="Confirmar nova senha"
            type="password"
            v-model="form.password_confirmation"
            autocomplete="new-password"
            :error="form.errors.password_confirmation"
        />

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
