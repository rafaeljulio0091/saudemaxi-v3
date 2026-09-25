import { defineStore } from 'pinia';

export const useTriageStore = defineStore('healthcare-triage', {
    state: () => ({
        key: null,
        sessionId: null,
        status: null,
        messages: [],
        routing: null,
        pending: false,
        error: '',
        retryRequest: null,
    }),
    getters: {
        started: (state) => Boolean(state.sessionId),
        closed: (state) => Boolean(state.status && state.status !== 'active'),
    },
    actions: {
        reset(key) {
            this.key = key;
            this.sessionId = null;
            this.status = null;
            this.messages = [];
            this.routing = null;
            this.pending = false;
            this.error = '';
            this.retryRequest = null;
        },
        async start(service) {
            if (this.pending || this.sessionId) return;
            this.pending = true;
            this.error = '';

            try {
                const session = await service.start();
                this.sessionId = session.id;
                this.status = session.status;
                this.messages = session.messages || [];
                this.routing = session.routing;
            } catch (error) {
                this.error =
                    error.userMessage ||
                    'Não foi possível iniciar a orientação agora.';
            } finally {
                this.pending = false;
            }
        },
        async send(service, text) {
            const cleanText = text.trim();
            if (this.pending || !this.sessionId || this.closed || !cleanText) {
                return;
            }

            this.pending = true;
            this.error = '';
            const request =
                this.retryRequest?.message === cleanText
                    ? this.retryRequest
                    : {
                          id: globalThis.crypto.randomUUID(),
                          message: cleanText,
                      };
            this.retryRequest = request;

            try {
                const result = await service.send(
                    this.sessionId,
                    cleanText,
                    request.id,
                );
                this.messages.push(
                    { role: 'user', text: cleanText },
                    result.message,
                );
                this.status = result.session.status;
                this.routing = result.state;
                this.retryRequest = null;
            } catch (error) {
                this.error =
                    error.userMessage ||
                    'Não foi possível enviar sua mensagem. Tente novamente.';
            } finally {
                this.pending = false;
            }
        },
    },
});
