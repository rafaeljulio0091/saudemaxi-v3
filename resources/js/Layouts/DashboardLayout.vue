<script setup>
import '@/../css/healthcare/tokens.css';
import '@/../css/healthcare/components.css';
import '@/../css/healthcare/utilities.css';
import { computed, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useHealthcareUi } from '@/stores/healthcareUi';
import { brandTokens } from '@/utils/brandColor';
import { initials } from '@/utils/healthcareFormat';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppModal from '@/Components/Healthcare/AppModal.vue';
import DashboardNavigation from '@/Components/DashboardNavigation.vue';
import MaxAssistant from '@/Components/Healthcare/MaxAssistant.vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    navItems: {
        type: Array,
        required: true,
    },
    roleLabel: {
        type: String,
        required: true,
    },
    tenant: {
        type: Object,
        default: null,
    },
});

const ui = useHealthcareUi();
const page = usePage();

// Same endpoints as HealthcareContext (patient: /triagem, manager:
// /gestor/dados). MAX needs a tenant, so it is hidden without one.
const maxContext = computed(() => {
    const user = page.props.auth.user;
    const profile = user.role === 'manager' ? 'manager' : 'patient';
    return {
        demo: false,
        profile,
        key: `${props.tenant?.name}:${profile}:${user.id}`,
        basePath: '',
        apiBase: profile === 'manager' ? '/gestor/dados' : '/triagem',
    };
});

watch(
    () => page.url,
    () => {
        ui.menuOpen = false;
    },
);
</script>

<template>
    <div class="sm-app" :style="brandTokens(tenant?.brand_color)">
        <Head :title="title" />
        <a href="#dashboard-content" class="sm-skip">Pular para o conteúdo</a>
        <div class="sm-shell">
            <aside class="sm-sidebar">
                <div class="sm-brand">
                    <span class="sm-brand-symbol">
                        {{ initials(tenant?.name || 'SaudeMaxi') }}
                    </span>
                    <div>
                        <strong>{{ tenant?.name || 'SaudeMaxi' }}</strong>
                        <small>{{ roleLabel }}</small>
                    </div>
                </div>
                <DashboardNavigation :items="navItems" />
                <div class="sm-brand">
                    <Link :href="route('profile.edit')" class="sm-link">
                        Meu perfil
                    </Link>
                </div>
            </aside>
            <div class="sm-main">
                <header class="sm-topbar">
                    <AppButton
                        variant="secondary"
                        class="sm-mobile-menu"
                        aria-label="Abrir menu"
                        :aria-expanded="ui.menuOpen"
                        @click="ui.menuOpen = true"
                    >
                        ☰
                    </AppButton>
                    <strong class="sm-grow">{{ title }}</strong>
                    <span class="sm-badge">{{ roleLabel }}</span>
                    <span
                        class="sm-avatar"
                        :aria-label="page.props.auth.user.name"
                    >
                        {{ initials(page.props.auth.user.name) }}
                    </span>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="sm-link"
                    >
                        Sair
                    </Link>
                </header>
                <main id="dashboard-content" class="sm-content" tabindex="-1">
                    <slot />
                </main>
            </div>
        </div>
        <MaxAssistant v-if="tenant" :context="maxContext" />
        <AppModal :open="ui.menuOpen" title="Menu" @close="ui.menuOpen = false">
            <DashboardNavigation
                :items="navItems"
                @navigate="ui.menuOpen = false"
            />
        </AppModal>
    </div>
</template>
