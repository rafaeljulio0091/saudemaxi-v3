<script setup>
defineProps({ tone: { type: String, default: 'info' } });
</script>
<template>
    <div
        class="sm-alert"
        :class="tone"
        :role="tone === 'danger' ? 'alert' : 'status'"
    >
        <slot />
    </div>
</template>
