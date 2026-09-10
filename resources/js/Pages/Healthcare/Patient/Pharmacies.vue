<script setup>
import { onMounted } from 'vue';
import Layout from '@/Layouts/HealthcareLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
import PageHeader from '@/Components/Healthcare/PageHeader.vue';
import AsyncState from '@/Components/Healthcare/AsyncState.vue';
import { useHealthcare } from '@/composables/useHealthcare';
import { useHealthcareServices } from '@/composables/useHealthcareServices';
import { useAsyncState } from '@/composables/useAsyncState';
defineOptions({ layout: Layout });

const { pharmacy } = useHealthcareServices();
const state = useAsyncState((signal) => pharmacy.pharmacies(signal));
onMounted(state.run);
</script>
<template>
    <PageHeader
        title="Farmácias perto de você"
        description="Encontre um ponto de atendimento e confira as informações antes de sair."
    />
    <div class="sm-stack">
        <AppAlert>
            Endereços e distâncias fictícios. Busca por localização real e
            reserva aguardam cadastro oficial e integração.
        </AppAlert>
        <AsyncState
            :status="state.status.value"
            :error="state.error.value"
            empty="Nenhuma farmácia cadastrada neste cenário. Procure sua unidade de referência."
            @retry="state.run"
        >
            <div class="sm-grid">
                <AppCard v-for="place in state.data.value" :key="place.id">
                    <span class="sm-badge">
                        {{ place.distancia }} no exemplo
                    </span>
                    <h2 class="sm-mt">{{ place.nome }}</h2>
                    <p class="sm-muted">{{ place.endereco }}</p>
                    <p class="sm-mt">{{ place.horario }}</p>
                    <AppButton class="sm-mt" disabled variant="secondary">
                        Reserva indisponível
                    </AppButton>
                </AppCard>
            </div>
        </AsyncState>
    </div>
</template>
