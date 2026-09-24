<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import ServiceCard from '@/Components/Healthcare/ServiceCard.vue';
import { modules } from '@/constants/healthcareNavigation';
import { navItemsForRole } from '@/utils/dashboardNavigation';
import { greeting } from '@/utils/healthcareFormat';
import { usePage } from '@inertiajs/vue3';

defineProps({
    roleLabel: {
        type: String,
        required: true,
    },
    tenant: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const navItems = navItemsForRole('patient');
</script>

<template>
    <DashboardLayout
        title="Início"
        :nav-items="navItems"
        :role-label="roleLabel"
        :tenant="tenant"
    >
        <div class="sm-stack">
            <section class="sm-hero sm-stack-sm">
                <p class="sm-kicker">Seu ambiente de cuidado</p>
                <h1>
                    {{ greeting() }},
                    {{ page.props.auth.user.name.split(' ')[0] }}.
                </h1>
                <div class="sm-row">
                    <span class="sm-badge">{{ roleLabel }}</span>
                    <span v-if="tenant" class="sm-badge">
                        {{ tenant.name }}
                    </span>
                </div>
            </section>

            <AppCard>
                <h2>Sua próxima ação</h2>
                <p class="sm-muted sm-mt">
                    Ainda não há histórico de consultas ou atendimentos neste
                    ambiente.
                </p>
            </AppCard>

            <h2>Serviços disponíveis</h2>
            <div class="sm-stack-sm">
                <ServiceCard
                    v-for="module in modules"
                    :key="module.key"
                    :title="module.label"
                    :description="module.description"
                    :icon="module.icon"
                    :href="'/' + module.key"
                />
            </div>
        </div>
    </DashboardLayout>
</template>
