<script setup>
defineProps({
    page: Number,
    total: Number,
    size: { type: Number, default: 10 },
});
defineEmits(['update:page']);
</script>
<template>
    <nav class="sm-pagination" aria-label="Paginação">
        <button
            class="sm-button secondary"
            :disabled="page <= 1"
            @click="$emit('update:page', page - 1)"
        >
            Anterior
        </button>
        <span class="sm-small" aria-live="polite">
            Página {{ page }} de {{ Math.max(1, Math.ceil(total / size)) }} ·
            {{ total }} registros
        </span>
        <button
            class="sm-button secondary"
            :disabled="page * size >= total"
            @click="$emit('update:page', page + 1)"
        >
            Próxima
        </button>
    </nav>
</template>
