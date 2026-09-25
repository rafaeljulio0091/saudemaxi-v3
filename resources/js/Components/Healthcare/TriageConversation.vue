<script setup>
import { computed, nextTick, ref, useId, watch } from 'vue';
import { useTriageStore } from '@/stores/triage';
import { useHealthcare } from '@/composables/useHealthcare';
import { useHealthcareServices } from '@/composables/useHealthcareServices';
import AppAlert from './AppAlert.vue';
import AppButton from './AppButton.vue';

const store = useTriageStore();
const { context, href } = useHealthcare();
const { triage } = useHealthcareServices();
const messageId = useId();
const consentId = useId();
const consent = ref(false);
const input = ref('');
const log = ref(null);

const statusMessage = computed(() => {
    if (store.status === 'emergency') {
        return 'A conversa foi interrompida para priorizar seu encaminhamento.';
    }

    if (store.status === 'human_review') {
        return 'As informações foram encaminhadas para avaliação humana.';
    }

    if (store.status === 'completed') {
        return 'A coleta inicial foi concluída e ficou registrada.';
    }

    return '';
});

watch(
    () => context.value.key,
    (key) => {
        if (store.key !== key) store.reset(key);
    },
    { immediate: true },
);

async function start() {
    if (!consent.value) return;
    await store.start(triage);
}

async function send() {
    const text = input.value;
    await store.send(triage, text);

    if (!store.error) input.value = '';

    await nextTick();
    log.value?.scrollTo({ top: log.value.scrollHeight });
}
</script>

<template>
    <section class="sm-triage" aria-labelledby="triage-title">
        <div class="sm-triage-head">
            <div>
                <p class="sm-kicker">Assistente automatizado</p>
                <h2 id="triage-title">Orientação em saúde</h2>
                <p class="sm-muted">
                    Conte o que está acontecendo. Faremos uma coleta inicial
                    para ajudar no encaminhamento.
                </p>
            </div>
            <span v-if="store.started" class="sm-badge info">
                Sessão protegida
            </span>
        </div>

        <div v-if="!store.started" class="sm-consent sm-stack-sm">
            <AppAlert tone="warning">
                Este assistente auxilia na coleta inicial e não substitui um
                profissional de saúde. Casos podem ser encaminhados para
                atendimento humano, e as informações fornecidas farão parte do
                atendimento.
            </AppAlert>
            <label class="sm-consent-check" :for="consentId">
                <input :id="consentId" v-model="consent" type="checkbox" />
                <span>Li e quero iniciar a orientação automatizada.</span>
            </label>
            <AppButton
                type="button"
                :disabled="!consent"
                :busy="store.pending"
                @click="start"
            >
                Iniciar orientação
            </AppButton>
        </div>

        <template v-else>
            <div
                ref="log"
                class="sm-chat sm-triage-chat"
                role="log"
                aria-label="Conversa de orientação em saúde"
                aria-live="polite"
            >
                <div v-if="!store.messages.length" class="sm-message assistant">
                    <p>
                        Olá. Explique brevemente o principal motivo pelo qual
                        você procura orientação hoje.
                    </p>
                </div>
                <div
                    v-for="message in store.messages"
                    :key="message.id || message.created_at || message.text"
                    class="sm-message"
                    :class="[message.role, message.tone]"
                >
                    <p>{{ message.text }}</p>
                    <div v-if="message.actions?.length" class="sm-row sm-mt">
                        <template
                            v-for="action in message.actions"
                            :key="action.path"
                        >
                            <a
                                v-if="action.path.startsWith('tel:')"
                                :href="action.path"
                                class="sm-button danger"
                            >
                                {{ action.label }}
                            </a>
                            <AppButton
                                v-else
                                :href="href(action.path)"
                                variant="secondary"
                            >
                                {{ action.label }}
                            </AppButton>
                        </template>
                    </div>
                </div>
            </div>

            <p v-if="store.pending" class="sm-muted" role="status">
                Analisando sua mensagem com segurança...
            </p>
            <AppAlert v-if="store.error" tone="danger" role="alert">
                {{ store.error }}
            </AppAlert>
            <AppAlert
                v-if="statusMessage"
                :tone="store.status === 'emergency' ? 'danger' : 'info'"
            >
                {{ statusMessage }}
            </AppAlert>

            <form
                v-if="!store.closed"
                class="sm-triage-form"
                @submit.prevent="send"
            >
                <label :for="messageId">Sua mensagem</label>
                <div class="sm-triage-input">
                    <textarea
                        :id="messageId"
                        v-model="input"
                        rows="3"
                        maxlength="2000"
                        required
                        :disabled="store.pending"
                        placeholder="Descreva o que está sentindo ou de que ajuda precisa"
                    />
                    <AppButton type="submit" :busy="store.pending">
                        Enviar
                    </AppButton>
                </div>
            </form>
        </template>
    </section>
</template>
