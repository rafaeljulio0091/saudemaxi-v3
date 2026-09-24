<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
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
    <Head title="Painel do gestor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Painel do gestor
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex items-center gap-4 p-6">
                        <UserAvatar
                            :name="page.props.auth.user.name"
                            size="lg"
                        />
                        <div>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ page.props.auth.user.name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ page.props.auth.user.email }}
                            </p>
                            <p class="text-sm text-gray-500">{{ roleLabel }}</p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="tenant"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <p class="text-sm text-gray-500">Cliente atendido</p>
                        <p class="text-base font-medium text-gray-900">
                            {{ tenant.name }}
                        </p>
                    </div>
                </div>
                <div
                    v-else
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-600">
                        Este usuário ainda não está associado a um cliente
                        (tenant).
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-600">
                        As áreas de pacientes, consultas, planos, identidade
                        visual e integrações ainda estão em preparação para este
                        ambiente.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
