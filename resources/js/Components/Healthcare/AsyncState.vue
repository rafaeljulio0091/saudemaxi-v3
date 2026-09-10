<script setup>
import AppButton from './AppButton.vue';
defineProps({
    status: String,
    error: String,
    empty: { type: String, default: 'Nenhum resultado encontrado.' },
});
defineEmits(['retry']);
</script>
<template>
    <div
        v-if="status === 'loading'"
        class="sm-card sm-state"
        role="status"
        aria-live="polite"
    >
        <span class="sm-spinner" aria-hidden="true" />
        <p>Carregando informações...</p>
    </div>
    <div v-else-if="status === 'error'" class="sm-card sm-state" role="alert">
        <h2>Não foi possível carregar</h2>
        <p>{{ error }}</p>
        <AppButton @click="$emit('retry')">Tentar novamente</AppButton>
    </div>
    <div v-else-if="status === 'empty'" class="sm-card sm-state" role="status">
        <h2>Nenhum resultado</h2>
        <p>{{ empty }}</p>
        <slot name="empty" />
    </div>
    <slot v-else-if="status === 'success'" />
</template>
