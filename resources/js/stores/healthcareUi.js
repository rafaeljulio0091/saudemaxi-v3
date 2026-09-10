import { defineStore } from 'pinia';
export const useHealthcareUi = defineStore('healthcare-ui', {
    state: () => ({ menuOpen: false, maxOpen: false }),
});
