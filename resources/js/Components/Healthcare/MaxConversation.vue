<script setup>
import { ref, computed, nextTick, watch, useId } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useMaxStore } from '@/stores/max';
import { useHealthcare } from '@/composables/useHealthcare';
import { useHealthcareServices } from '@/composables/useHealthcareServices';
import AppButton from './AppButton.vue';
import AppAlert from './AppAlert.vue';
const store = useMaxStore();
const { context, href } = useHealthcare();
const { max } = useHealthcareServices();
const page = usePage();
const messageId = useId();
const input = ref(''),
    log = ref(null);
const suggestions = computed(() =>
    context.value.profile === 'manager'
        ? ['Minhas pendências', 'Planos e módulos', 'Privacidade na NR-1']
        : page.url.includes('farm') || page.url.includes('receita')
          ? ['Minha receita', 'Farmácias próximas', 'Meu plano']
          : ['Minhas consultas', 'Meu plano', 'Preciso de ajuda agora'],
);
watch(
    () => context.value.key,
    (key) => {
        if (store.key !== key) store.reset(key);
    },
    { immediate: true },
);
async function send(text = input.value) {
    await store.send(max, text, page.url.replace(context.value.basePath, ''));
    if (!store.error) input.value = '';
    await nextTick();
    log.value?.scrollTo({ top: log.value.scrollHeight });
}
</script>
<template>
    <div class="sm-stack-sm">
        <p class="sm-muted">
            Sou o MAX. Ajudo você a encontrar informações e o próximo cuidado.
        </p>
        <AppAlert tone="warning">
            Respostas demonstrativas. O MAX não faz avaliação médica. Não
            informe dados reais de saúde.
        </AppAlert>
        <div
            ref="log"
            class="sm-chat"
            role="log"
            aria-label="Conversa com MAX"
            aria-live="polite"
        >
            <div
                v-for="(message, index) in store.messages"
                :key="index"
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
                <details v-if="message.trace" class="sm-small sm-mt">
                    <summary>Registro desta resposta de demonstração</summary>
                    <p>
                        Regra: {{ message.trace.rule }} · Versão:
                        {{ message.trace.version }}
                    </p>
                    <p class="sm-break">{{ message.trace.id }}</p>
                    <p>
                        Registro transitório, sem auditoria clínica persistente.
                    </p>
                </details>
            </div>
        </div>
        <p v-if="store.pending" role="status">MAX está respondendo...</p>
        <AppAlert v-if="store.error" tone="danger">{{ store.error }}</AppAlert>
        <div class="sm-row">
            <button
                v-for="suggestion in suggestions"
                :key="suggestion"
                class="sm-button secondary sm-small"
                :disabled="store.pending"
                @click="send(suggestion)"
            >
                {{ suggestion }}
            </button>
        </div>
        <form class="sm-stack-sm" @submit.prevent="send()">
            <label :for="messageId">Escreva para o MAX</label>
            <textarea
                :id="messageId"
                v-model="input"
                rows="2"
                maxlength="2000"
                required
                placeholder="Como posso ajudar?"
            />
            <AppButton type="submit" :busy="store.pending">
                Enviar mensagem
            </AppButton>
        </form>
    </div>
</template>
