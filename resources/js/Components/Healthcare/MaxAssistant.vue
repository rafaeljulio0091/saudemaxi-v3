<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useHealthcareUi } from '@/stores/healthcareUi';
import AppButton from './AppButton.vue';
import AppModal from './AppModal.vue';
import MaxConversation from './MaxConversation.vue';

// Floating MAX launcher + conversation modal, shared by every authenticated
// layout. `context` needs profile, key, basePath, apiBase and demo.
defineProps({ context: { type: Object, required: true } });
const ui = useHealthcareUi();
const page = usePage();
watch(
    () => page.url,
    () => {
        ui.maxOpen = false;
    },
);
</script>
<template>
    <AppButton
        class="sm-max-launch"
        :aria-expanded="ui.maxOpen"
        @click="ui.maxOpen = true"
    >
        Ⓜ MAX
    </AppButton>
    <AppModal
        :open="ui.maxOpen"
        title="MAX, seu assistente"
        @close="ui.maxOpen = false"
    >
        <MaxConversation v-if="ui.maxOpen" :context="context" />
    </AppModal>
</template>
