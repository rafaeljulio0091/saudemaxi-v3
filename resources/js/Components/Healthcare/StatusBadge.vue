<script setup>
import { consultationStatuses } from '@/constants/consultationStatus';
defineProps({ status: String });
</script>
<template>
    <span class="sm-badge" :class="consultationStatuses[status]?.tone">
        {{ consultationStatuses[status]?.label || 'Situação não informada' }}
    </span>
</template>
