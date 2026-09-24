<script setup>
import '@/../css/healthcare/tokens.css';
import '@/../css/healthcare/components.css';
import '@/../css/healthcare/utilities.css';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import ServiceCard from '@/Components/Healthcare/ServiceCard.vue';
import { modules } from '@/constants/healthcareNavigation';
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
</script>

<template>
    <Head title="Painel do paciente" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Painel do paciente
            </h2>
        </template>

        <div class="sm-app" :style="brandTokens(tenant?.brand_color)">
            <div class="sm-content">
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
                            Ainda não há histórico de consultas ou atendimentos
                            neste ambiente.
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
