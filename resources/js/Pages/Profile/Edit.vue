<script setup>
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { navItemsForRole } from '@/utils/dashboardNavigation';
import { usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
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

const page = usePage();
const navItems = computed(() => navItemsForRole(page.props.auth.user.role));
</script>

<template>
    <DashboardLayout
        title="Minha conta"
        :nav-items="navItems"
        :role-label="roleLabel"
        :tenant="tenant"
    >
        <div class="sm-stack">
            <p class="sm-muted">Seus dados, seu plano e seus cuidados.</p>

            <div class="sm-grid">
                <AppCard>
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </AppCard>

                <div class="sm-stack">
                    <AppCard>
                        <h2>{{ tenant?.name || 'Sem cliente associado' }}</h2>
                        <p class="sm-muted">{{ roleLabel }}</p>
                        <p class="sm-muted sm-small sm-mt">
                            Plano e módulos contratados ainda não estão
                            configurados neste ambiente.
                        </p>
                    </AppCard>
                    <AppCard>
                        <h2>Idioma</h2>
                        <p>Português do Brasil</p>
                        <p class="sm-muted sm-small sm-mt">
                            Outros idiomas ainda não são suportados pela
                            aplicação.
                        </p>
                    </AppCard>
                </div>
            </div>

            <AppCard>
                <UpdatePasswordForm />
            </AppCard>

            <AppCard>
                <DeleteUserForm />
            </AppCard>
        </div>
    </DashboardLayout>
</template>
