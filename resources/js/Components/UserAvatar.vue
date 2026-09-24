<script setup>
import { computed } from 'vue';
import { initials as computeInitials } from '@/utils/healthcareFormat';

const props = defineProps({
    name: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md',
    },
});

const sizeClasses = {
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-14 w-14 text-lg',
};

const initials = computed(() => computeInitials(props.name));
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700"
        :class="sizeClasses[size] ?? sizeClasses.md"
        aria-hidden="true"
    >
        {{ initials || '?' }}
    </span>
</template>
