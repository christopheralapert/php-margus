// Match ChatRequest's six history items and per-item character limit.
export const trimHistory = (history, maxChars) => history.slice(-6).map(({ role, content }) => ({
    role,
    content: Array.from(content).slice(0, maxChars).join(''),
}));

export const buildPayload = (procedures, message, history, maxChars) => ({
    procedures: [...new Set(procedures)],
    message: message.trim(),
    history: trimHistory(history, maxChars),
});

export default (options) => ({
    message: '',
    messages: [],
    history: [],
    loading: false,
    error: '',

    get characterCount() {
        return Array.from(this.message).length;
    },

    async send() {
        if (this.loading || !this.message.trim()) return;
        if (this.characterCount > options.maxChars) {
            this.error = options.validation;
            this.$refs.input.focus();
            return;
        }

        const payload = buildPayload(options.procedures, this.message, this.history, options.maxChars);
        this.loading = true;
        this.error = '';
        this.$refs.input.focus();

        try {
            const response = await fetch(options.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (!response.ok) {
                this.error = [422, 429].includes(response.status) && typeof data.message === 'string'
                    ? data.message : options.networkError;
                return;
            }
            if (typeof data.reply !== 'string') throw new Error('Invalid response');

            this.messages.push(
                { role: 'user', content: payload.message },
                { role: 'assistant', content: data.reply, redFlag: data.red_flag === true, fallback: data.fallback === true },
            );
            this.history = trimHistory([...this.history,
                { role: 'user', content: payload.message },
                { role: 'assistant', content: data.reply },
            ], options.maxChars);
            this.message = '';
        } catch {
            this.error = options.networkError;
        } finally {
            this.loading = false;
            this.$refs.input.focus();
        }
    },
});
