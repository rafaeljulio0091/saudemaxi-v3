<script setup>
defineProps({ title: String, description: String });
</script>
<template>
    <header class="sm-row sm-mb">
        <div class="sm-grow">
            <h1>{{ title }}</h1>
            <p v-if="description" class="sm-muted sm-mt">{{ description }}</p>
        </div>
        <slot />
    </header>
</template>
