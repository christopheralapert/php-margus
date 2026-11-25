export const normalizeSearch = (value) => String(value)
    .normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase();

export default (groups, selected = []) => ({
    query: '',
    groups,
    selected: selected.map(String),

    matches(text) {
        return normalizeSearch(text).includes(normalizeSearch(this.query).trim());
    },

    categoryVisible(id) {
        return this.groups[id].some((text) => this.matches(text));
    },

    get resultCount() {
        return Object.values(this.groups).flat().filter((text) => this.matches(text)).length;
    },
});
