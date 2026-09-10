<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useHealthcare } from '@/composables/useHealthcare';
import { patientMenu, managerMenu } from '@/constants/healthcareNavigation';
import AppIcon from './AppIcon.vue';
const emit = defineEmits(['navigate']);
const { context, href, moduleEnabled } = useHealthcare();
const page = usePage();
const menu = computed(() =>
    context.value.profile === 'manager' ? managerMenu : patientMenu,
);
</script>
<template>
    <nav class="sm-nav" aria-label="Navegação principal">
        <template v-for="(item, index) in menu" :key="item.path">
            <p
                v-if="index === 0 || item.group !== menu[index - 1].group"
                class="sm-nav-label"
            >
                {{ item.group }}
            </p>
            <Link
                :href="href(item.path)"
                :aria-current="
                    page.url.split('?')[0] === href(item.path)
                        ? 'page'
                        : undefined
                "
                @click="emit('navigate')"
            >
                <AppIcon :name="item.icon" />
                <span class="sm-grow">{{ item.label }}</span>
                <span
                    v-if="item.key && !moduleEnabled(item.key)"
                    aria-label="Não incluído no plano"
                >
                    ○
                </span>
            </Link>
        </template>
    </nav>
</template>
