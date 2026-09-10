import { defineStore } from 'pinia';
export const useMaxStore = defineStore('healthcare-max', {
    state: () => ({
        key: null,
        messages: [],
        pending: false,
        error: '',
        sequence: 0,
    }),
    actions: {
        reset(key) {
            this.sequence++;
            this.key = key;
            this.messages = [];
            this.pending = false;
            this.error = '';
        },
        async send(service, text, page) {
            if (this.pending || !text.trim()) return;
            const sequence = this.sequence;
            this.pending = true;
            this.error = '';
            try {
                const result = await service.respond(text.trim(), page);
                if (sequence !== this.sequence) return;
                this.messages.push(
                    { role: 'user', text: text.trim() },
                    { role: 'assistant', ...result },
                );
                this.messages = this.messages.slice(-40);
            } catch (error) {
                if (sequence === this.sequence)
                    this.error =
                        error.userMessage ||
                        'Não consegui responder. Tente novamente.';
            } finally {
                if (sequence === this.sequence) this.pending = false;
            }
        },
    },
});
