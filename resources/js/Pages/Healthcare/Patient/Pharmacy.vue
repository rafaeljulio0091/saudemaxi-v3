<script setup>
import { onMounted, ref, onBeforeUnmount } from 'vue';
import { date } from '@/utils/healthcareFormat';
import AppModal from '@/Components/Healthcare/AppModal.vue';
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
const { href } = useHealthcare();
const state = useAsyncState((signal) => pharmacy.list(signal));
const open = ref(false),
    busy = ref(false),
    error = ref(''),
    preview = ref(''),
    selectedFile = ref(null),
    uploaded = ref(null);
function choose(event) {
    const file = event.target.files[0];
    if (preview.value) URL.revokeObjectURL(preview.value);
    preview.value = '';
    selectedFile.value = null;
    error.value = '';
    if (!file) return;
    if (
        !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) ||
        file.size > 10 * 1024 * 1024
    ) {
        error.value = 'Escolha uma imagem JPG, PNG ou WebP de até 10 MB.';
        return;
    }
    selectedFile.value = file;
    preview.value = URL.createObjectURL(file);
}
async function read() {
    if (busy.value || !preview.value) return;
    busy.value = true;
    error.value = '';
    try {
        uploaded.value = await pharmacy.readDemoPhoto(selectedFile.value);
        open.value = false;
        await state.run();
    } catch (e) {
        error.value = e.userMessage;
    } finally {
        busy.value = false;
    }
}
onMounted(state.run);
onBeforeUnmount(() => {
    if (preview.value) URL.revokeObjectURL(preview.value);
});
</script>
<template>
    <PageHeader
        title="Farmácia popular"
        description="Suas receitas e o caminho até a farmácia."
    >
        <AppButton @click="open = true">＋ Enviar foto</AppButton>
    </PageHeader>
    <div class="sm-stack">
        <AppAlert>
            Receitas não são sincronizadas automaticamente com a plataforma. Os
            resultados abaixo são exemplos fictícios, sem comprovação de
            cobertura.
        </AppAlert>
        <AppAlert v-if="uploaded" tone="success">
            Leitura demonstrativa criada.
            <a :href="href('/receita/' + uploaded.id)" class="sm-link">
                Conferir resultado
            </a>
        </AppAlert>
        <AsyncState
            :status="state.status.value"
            :error="state.error.value"
            empty="Você ainda não tem receitas. Envie uma foto para conhecer o fluxo."
            @retry="state.run"
        >
            <AppCard>
                <h2>Minhas receitas</h2>
                <div
                    v-for="prescription in state.data.value"
                    :key="prescription.id"
                    class="sm-row sm-mt"
                >
                    <span class="sm-service-icon">▤</span>
                    <div class="sm-grow">
                        <strong>
                            Receita de {{ date(prescription.data) }}
                        </strong>
                        <p class="sm-muted sm-small">
                            {{ prescription.medico }} ·
                            {{ prescription.itens.length }} itens
                        </p>
                    </div>
                    <AppButton
                        :href="href('/receita/' + prescription.id)"
                        variant="secondary"
                    >
                        Ver receita
                    </AppButton>
                </div>
            </AppCard>
        </AsyncState>
        <AppButton :href="href('/farmacias')" variant="secondary">
            Encontrar farmácias
        </AppButton>
    </div>
    <AppModal
        :open="open"
        title="Enviar foto da receita"
        @close="!busy && (open = false)"
    >
        <div class="sm-stack-sm">
            <p>
                Apoie o papel numa superfície plana, evite sombras e enquadre a
                receita inteira.
            </p>
            <label for="prescription-photo">Foto de exemplo, até 10 MB</label>
            <input
                id="prescription-photo"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                @change="choose"
            />
            <img
                v-if="preview"
                :src="preview"
                alt="Prévia da foto selecionada"
                style="max-height: 220px; object-fit: contain; width: 100%"
            />
            <AppAlert>
                A imagem fica somente na prévia desta página. A leitura a seguir
                usa um exemplo fixo, sem enviar ou analisar sua foto.
            </AppAlert>
            <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>
            <AppButton :busy="busy" :disabled="!preview" @click="read">
                Demonstrar leitura da receita
            </AppButton>
        </div>
    </AppModal>
</template>
