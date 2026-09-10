<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({
    href: String,
    variant: { type: String, default: 'primary' },
    busy: Boolean,
    disabled: Boolean,
    type: { type: String, default: 'button' },
});
</script>
<template>
    <Link v-if="href" :href="href" class="sm-button" :class="variant">
        <slot />
    </Link>
    <button
        v-else
        :type="type"
        class="sm-button"
        :class="variant"
        :disabled="disabled || busy"
        :aria-busy="busy"
    >
        <span v-if="busy" class="sm-spinner" aria-hidden="true" />
        <slot />
    </button>
</template>
