<script setup>
import { Link } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';
defineProps({
    title: String,
    description: String,
    icon: String,
    href: String,
    enabled: { type: Boolean, default: true },
});
</script>
<template>
    <Link :href="href" class="sm-service" :aria-disabled="!enabled">
        <span class="sm-service-icon"><AppIcon :name="icon" /></span>
        <span class="sm-grow">
            <strong>{{ title }}</strong>
            <span class="sm-muted sm-small">{{ description }}</span>
            <span v-if="!enabled" class="sm-badge sm-mt">
                Não incluído no seu plano
            </span>
        </span>
        <span aria-hidden="true">{{ enabled ? '↗' : '○' }}</span>
    </Link>
</template>
