<script setup>
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppModal from '@/Components/Healthcare/AppModal.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus?.());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus?.(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <h2>Excluir conta</h2>
    <p class="sm-muted sm-mt">
        Depois que sua conta for excluída, todos os seus dados serão
        permanentemente apagados.
    </p>

    <AppButton variant="danger" class="sm-mt" @click="confirmUserDeletion">
        Excluir conta
    </AppButton>

    <AppModal
        :open="confirmingUserDeletion"
        title="Tem certeza que deseja excluir sua conta?"
        @close="closeModal"
    >
        <p class="sm-muted">
            Depois que sua conta for excluída, todos os seus dados serão
            permanentemente apagados. Informe sua senha para confirmar que
            deseja excluir sua conta.
        </p>

        <div class="sm-mt">
            <AppField
                id="delete-password"
                ref="passwordInput"
                label="Senha"
                type="password"
                v-model="form.password"
                :error="form.errors.password"
                @keyup.enter="deleteUser"
            />
        </div>

        <div class="sm-row sm-mt">
            <AppButton variant="secondary" @click="closeModal">
                Cancelar
            </AppButton>
            <AppButton
                variant="danger"
                :busy="form.processing"
                @click="deleteUser"
            >
                Excluir conta
            </AppButton>
        </div>
    </AppModal>
</template>
