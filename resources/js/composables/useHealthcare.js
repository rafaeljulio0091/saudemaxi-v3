import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
export function useHealthcare() {
    const page = usePage();
    const context = computed(() => page.props.healthcare);
    return {
        context,
        moduleEnabled: (key) => !!context.value?.modules?.[key],
        href: (path) =>
            path.startsWith('tel:')
                ? path
                : (context.value?.basePath || '') + path,
    };
}
