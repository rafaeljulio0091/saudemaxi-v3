<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/Healthcare/AppIcon.vue';

defineProps({
    items: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['navigate']);
const page = usePage();
</script>

<template>
    <nav class="sm-nav" aria-label="Navegação principal">
        <template v-for="(item, index) in items" :key="item.path">
            <p
                v-if="index === 0 || item.group !== items[index - 1].group"
                class="sm-nav-label"
            >
                {{ item.group }}
            </p>
            <Link
                :href="item.path"
                :aria-current="
                    page.url.split('?')[0] === item.path ? 'page' : undefined
                "
                @click="emit('navigate')"
            >
                <AppIcon :name="item.icon" />
                <span class="sm-grow">{{ item.label }}</span>
            </Link>
        </template>
    </nav>
</template>
