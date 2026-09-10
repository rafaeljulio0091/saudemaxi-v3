import { ref, onBeforeUnmount } from 'vue';
export function useAsyncState(loader) {
    const data = ref(null),
        status = ref('idle'),
        error = ref('');
    let controller,
        sequence = 0;
    async function run(...args) {
        controller?.abort();
        controller = new AbortController();
        const ticket = ++sequence;
        status.value = 'loading';
        error.value = '';
        try {
            const result = await loader(controller.signal, ...args);
            if (ticket !== sequence) return;
            data.value = result;
            status.value =
                Array.isArray(result) && !result.length ? 'empty' : 'success';
            return result;
        } catch (failure) {
            if (
                ticket !== sequence ||
                failure.code === 'ERR_CANCELED' ||
                failure.name === 'AbortError'
            )
                return;
            error.value =
                failure.userMessage ||
                failure.message ||
                'Não foi possível carregar.';
            status.value = 'error';
        }
    }
    onBeforeUnmount(() => {
        sequence++;
        controller?.abort();
    });
    return { data, status, error, run };
}
