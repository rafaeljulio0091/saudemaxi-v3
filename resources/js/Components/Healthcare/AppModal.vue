<script setup>
import { ref, watch, onBeforeUnmount, useId } from 'vue';
import AppButton from './AppButton.vue';
const props = defineProps({ open: Boolean, title: String });
const emit = defineEmits(['close']);
const dialog = ref(null);
const titleId = useId();
let previousFocus;
watch(
    () => props.open,
    (open) => {
        if (!dialog.value) return;
        if (open) {
            previousFocus = document.activeElement;
            dialog.value.showModal();
        } else {
            dialog.value.close();
            previousFocus?.focus?.();
        }
    },
    { flush: 'post', immediate: true },
);
function close() {
    emit('close');
}
onBeforeUnmount(() => {
    dialog.value?.close();
    previousFocus?.focus?.();
});
</script>
<template>
    <dialog
        ref="dialog"
        class="sm-dialog"
        :aria-labelledby="titleId"
        @cancel.prevent="close"
        @click="
            (e) => {
                if (e.target === dialog) close();
            }
        "
    >
        <header class="sm-dialog-head">
            <h2 :id="titleId">{{ title }}</h2>
            <AppButton
                variant="secondary"
                aria-label="Fechar janela"
                @click="close"
            >
                ✕
            </AppButton>
        </header>
        <div class="sm-dialog-body"><slot /></div>
    </dialog>
</template>
