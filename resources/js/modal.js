export default (visible = false) => ({
    show: visible,
    previousFocus: null,
    inertElements: [],

    init() {
        this.$watch('show', (value) => value ? this.activate() : this.deactivate());
        if (this.show) this.activate();
    },

    focusables() {
        return [...this.$el.querySelectorAll('a[href], button, input, textarea, select, [tabindex]')]
            .filter((element) => !element.disabled && element.tabIndex >= 0 && element.getClientRects().length);
    },

    activate() {
        this.previousFocus = document.activeElement;
        document.body.classList.add('overflow-y-hidden');
        // Keep the dialog's ancestor path available, making other content inert.
        let branch = this.$el;
        while (branch.parentElement && branch !== document.body) {
            for (const sibling of branch.parentElement.children) {
                if (sibling !== branch && !sibling.inert) {
                    sibling.inert = true;
                    this.inertElements.push(sibling);
                }
            }
            branch = branch.parentElement;
        }
        this.$nextTick(() => (this.focusables()[0] || this.$refs.dialog).focus());
    },

    deactivate() {
        document.body.classList.remove('overflow-y-hidden');
        this.inertElements.forEach((element) => { element.inert = false; });
        this.inertElements = [];
        this.previousFocus?.focus();
    },

    trap(event) {
        const elements = this.focusables();
        const index = elements.indexOf(document.activeElement);
        const next = event.shiftKey ? index - 1 : index + 1;
        (elements[(next + elements.length) % elements.length] || this.$refs.dialog).focus();
    },

    destroy() {
        if (this.show) this.deactivate();
    },
});
