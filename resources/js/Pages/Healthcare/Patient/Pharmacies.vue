<script setup>
import { computed, onMounted, ref } from 'vue';
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

const { context } = useHealthcare();
const { pharmacy } = useHealthcareServices();
const locationStatus = ref('idle');
const locationError = ref('');
const state = useAsyncState((signal, location) =>
    context.value.demo
        ? pharmacy.pharmacies(signal)
        : pharmacy.nearby(location, signal),
);
const hasCalculatedDistance = computed(() =>
    (state.data.value || []).some((place) => place.distance_km !== null),
);

function rounded(value) {
    return Number(value.toFixed(4));
}

function locationFailure(error) {
    locationStatus.value = 'error';

    if (error.code === error.PERMISSION_DENIED) {
        locationError.value =
            'A localização foi recusada. Autorize o acesso no navegador para consultar as farmácias.';
        return;
    }

    if (error.code === error.TIMEOUT) {
        locationError.value =
            'O navegador demorou para identificar sua localização. Tente novamente.';
        return;
    }

    locationError.value =
        'Não foi possível identificar sua localização neste dispositivo.';
}

function requestLocation() {
    locationError.value = '';

    if (context.value.demo) {
        state.run();
        return;
    }

    if (!navigator.geolocation) {
        locationStatus.value = 'error';
        locationError.value =
            'Seu navegador não oferece identificação de localização.';
        return;
    }

    locationStatus.value = 'requesting';
    navigator.geolocation.getCurrentPosition(
        (position) => {
            locationStatus.value = 'granted';
            state.run({
                latitude: rounded(position.coords.latitude),
                longitude: rounded(position.coords.longitude),
                accuracy: Math.round(position.coords.accuracy),
            });
        },
        locationFailure,
        {
            enableHighAccuracy: false,
            timeout: 12000,
            maximumAge: 300000,
        },
    );
}

function distanceLabel(place) {
    if (place.distancia) return place.distancia;
    if (place.distance_km === null) return 'Distância indisponível';
    if (place.distance_km < 1)
        return `${Math.round(place.distance_km * 1000)} m`;
    return `${place.distance_km.toLocaleString('pt-BR')} km`;
}

onMounted(requestLocation);
</script>
<template>
    <PageHeader
        title="Farmácias perto de você"
        description="Encontre um ponto de atendimento e confira as informações antes de sair."
    />
    <div class="sm-stack">
        <AppAlert v-if="context.demo">
            Endereços e distâncias fictícios. Busca por localização real e
            reserva aguardam cadastro oficial e integração.
        </AppAlert>
        <AppAlert v-else>
            Sua localização será usada somente nesta busca para ordenar as
            farmácias. Ela não será armazenada no navegador nem no banco de
            dados.
        </AppAlert>
        <div
            v-if="locationStatus === 'requesting'"
            class="sm-card sm-state"
            role="status"
            aria-live="polite"
        >
            <span class="sm-spinner" aria-hidden="true" />
            <p>Aguardando autorização de localização do navegador...</p>
        </div>
        <AppAlert v-else-if="locationStatus === 'error'" tone="danger">
            <p>{{ locationError }}</p>
            <AppButton
                class="sm-mt"
                variant="secondary"
                @click="requestLocation"
            >
                Tentar novamente
            </AppButton>
        </AppAlert>
        <AsyncState
            :status="state.status.value"
            :error="state.error.value"
            empty="Nenhuma farmácia cadastrada para o seu cliente. Procure sua unidade de referência."
            @retry="requestLocation"
        >
            <AppAlert
                v-if="!context.demo && !hasCalculatedDistance"
                tone="warning"
            >
                As farmácias oficiais foram encontradas, mas a planilha de
                origem não fornece coordenadas. Os endereços são exibidos sem
                uma distância estimada.
            </AppAlert>
            <div class="sm-grid">
                <AppCard v-for="place in state.data.value" :key="place.id">
                    <span class="sm-badge">
                        {{ distanceLabel(place) }}
                    </span>
                    <h2 class="sm-mt">{{ place.name || place.nome }}</h2>
                    <address class="sm-muted">
                        {{ place.address || place.endereco }}
                    </address>
                    <p v-if="place.horario" class="sm-mt">
                        {{ place.horario }}
                    </p>
                    <p v-if="place.source" class="sm-muted sm-mt">
                        Fonte: {{ place.source }}
                    </p>
                </AppCard>
            </div>
        </AsyncState>
    </div>
</template>
