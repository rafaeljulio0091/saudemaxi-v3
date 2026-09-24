<script setup>
import '@/../css/healthcare/tokens.css';
import '@/../css/healthcare/components.css';
import '@/../css/healthcare/utilities.css';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import ServiceCard from '@/Components/Healthcare/ServiceCard.vue';
import { managerMenu } from '@/constants/healthcareNavigation';
import { greeting } from '@/utils/healthcareFormat';
import { brandTokens } from '@/utils/brandColor';
import { Head, usePage } from '@inertiajs/vue3';

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

// The panel itself is the current page, so it is not listed as a service link.
const services = managerMenu.filter((item) => item.path !== '/gestor/painel');
</script>

<template>
    <Head title="Painel do gestor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Painel do gestor
            </h2>
        </template>

        <div class="sm-app" :style="brandTokens(tenant?.brand_color)">
            <div class="sm-content">
                <div class="sm-stack">
                    <section class="sm-hero sm-stack-sm">
                        <p class="sm-kicker">Gestão do cuidado</p>
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

                    <AppCard v-if="!tenant">
                        <p class="sm-muted">
                            Este usuário ainda não está associado a um cliente
                            (tenant).
                        </p>
                    </AppCard>

                    <AppCard>
                        <h2>Operação</h2>
                        <p class="sm-muted sm-mt">
                            Os indicadores de pacientes, consultas e pagamentos
                            ainda não estão disponíveis neste ambiente.
                        </p>
                    </AppCard>

                    <h2>Áreas de gestão</h2>
                    <div class="sm-stack-sm">
                        <ServiceCard
                            v-for="item in services"
                            :key="item.path"
                            :title="item.label"
                            :icon="item.icon"
                            :href="item.path"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
