<script setup>
import { integrationCatalog } from '@/constants/integrationCatalog';
import Layout from '@/Layouts/HealthcareLayout.vue';
import AppCard from '@/Components/Healthcare/AppCard.vue';
import AppButton from '@/Components/Healthcare/AppButton.vue';
import AppAlert from '@/Components/Healthcare/AppAlert.vue';
import PageHeader from '@/Components/Healthcare/PageHeader.vue';
import AsyncState from '@/Components/Healthcare/AsyncState.vue';
import AppField from '@/Components/Healthcare/AppField.vue';
import { useHealthcare } from '@/composables/useHealthcare';
import { useHealthcareServices } from '@/composables/useHealthcareServices';
import { useAsyncState } from '@/composables/useAsyncState';
defineOptions({ layout: Layout });

const labels = {
    documented: 'Documentado, não conectado',
    pending: 'Aguarda definição',
    local: 'Camada Saúde Maxi',
    blocked: 'Bloqueado',
};
</script>
<template>
    <PageHeader
        title="Integrações"
        description="O que está documentado e o que ainda precisa ser conectado."
    />
    <div class="sm-stack">
        <AppAlert tone="warning">
            Nenhuma conexão real está ativa nesta demonstração. A homologação
            depende de credenciais e ambiente do fornecedor.
        </AppAlert>
        <div class="sm-grid sm-grid-4">
            <AppCard v-for="(label, key) in labels" :key="key">
                <p class="sm-muted sm-small">{{ label }}</p>
                <strong class="sm-stat">
                    {{
                        integrationCatalog.filter((item) => item.status === key)
                            .length
                    }}
                </strong>
            </AppCard>
        </div>
        <AppCard>
            <h2>Limites conhecidos</h2>
            <p class="sm-muted">
                O fornecedor não disponibiliza webhook geral de consultas nem
                endpoint de prescrição. O histórico permite reconciliar
                atendimentos; receitas não são recebidas automaticamente da
                Mevo.
            </p>
        </AppCard>
        <AppCard>
            <div class="sm-table-wrap">
                <table class="sm-table">
                    <caption>Catálogo documentado na referência</caption>
                    <thead>
                        <tr>
                            <th scope="col">Serviço</th>
                            <th scope="col">Situação</th>
                            <th scope="col">Dependência</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in integrationCatalog" :key="item.name">
                            <th scope="row">{{ item.name }}</th>
                            <td>
                                <span
                                    class="sm-badge"
                                    :class="
                                        item.status === 'blocked'
                                            ? 'danger'
                                            : item.status === 'pending'
                                              ? 'warning'
                                              : 'info'
                                    "
                                >
                                    {{ labels[item.status] }}
                                </span>
                            </td>
                            <td>{{ item.note }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </AppCard>
    </div>
</template>
