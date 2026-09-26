<script setup>
import '@/../css/healthcare/tokens.css';
import '@/../css/healthcare/components.css';
import '@/../css/healthcare/utilities.css';
import { watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useHealthcare } from '@/composables/useHealthcare';
import { useHealthcareUi } from '@/stores/healthcareUi';
import { brandTokens } from '@/utils/brandColor';
import { initials } from '@/utils/healthcareFormat';
import AppNavigation from '@/Components/Healthcare/AppNavigation.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppModal from '@/Components/Healthcare/AppModal.vue';
import MaxAssistant from '@/Components/Healthcare/MaxAssistant.vue';
const { context } = useHealthcare();
const ui = useHealthcareUi();
const page = usePage();
watch(
    () => page.url,
    () => {
        ui.menuOpen = false;
        ui.maxOpen = false;
    },
    { immediate: true },
);
</script>
<template>
    <div class="sm-app" :style="brandTokens(context.tenant.cor)">
        <Head :title="page.props.title || 'Saúde Maxi'" />
        <a href="#healthcare-content" class="sm-skip">Pular para o conteúdo</a>
        <div class="sm-shell">
            <aside class="sm-sidebar">
                <div class="sm-brand">
                    <span class="sm-brand-symbol">
                        {{ initials(context.tenant.nome) }}
                    </span>
                    <div>
                        <strong>{{ context.tenant.nome }}</strong>
                        <small>{{ context.tenant.subdominio }}</small>
                    </div>
                </div>
                <AppNavigation />
                <div v-if="context.demo" class="sm-brand">
                    <Link href="/demonstracao" class="sm-link">
                        Trocar cenário de demonstração
                    </Link>
                </div>
            </aside>
            <div class="sm-main">
                <div v-if="context.demo" class="sm-demo-banner">
                    <span>
                        Demonstração · Dados fictícios · Nenhuma operação real
                    </span>
                    <Link href="/demonstracao">Configurar</Link>
                </div>
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
                    <strong class="sm-grow">{{ page.props.title }}</strong>
                    <span class="sm-badge">
                        {{
                            context.profile === 'manager'
                                ? 'Gestor da clínica'
                                : context.plan.nome
                        }}
                    </span>
                    <span
                        class="sm-avatar"
                        :aria-label="
                            context.patient?.nome ||
                            context.manager?.nome ||
                            'Gestor'
                        "
                    >
                        {{
                            initials(
                                context.patient?.nome ||
                                    context.manager?.nome ||
                                    'Gestor Demo',
                            )
                        }}
                    </span>
                    <Link
                        v-if="!context.demo && page.props.auth?.user"
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="sm-link"
                    >
                        Sair
                    </Link>
                </header>
                <main id="healthcare-content" class="sm-content" tabindex="-1">
                    <slot />
                </main>
            </div>
        </div>
        <MaxAssistant :context="context" />
        <AppModal :open="ui.menuOpen" title="Menu" @close="ui.menuOpen = false">
            <AppNavigation @navigate="ui.menuOpen = false" />
        </AppModal>
    </div>
</template>
