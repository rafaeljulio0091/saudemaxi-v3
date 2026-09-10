import { http } from './http';
export function createHealthcareClient(context) {
    async function request(method, path, data, signal) {
        const current = context.value || context;
        if (!current.apiBase)
            throw new Error('A integração ainda não está disponível.');
        if (current.demo)
            await new Promise((resolve) =>
                setTimeout(resolve, current.network === 'slow' ? 1400 : 180),
            );
        if (signal?.aborted)
            throw new DOMException('Solicitação cancelada', 'AbortError');
        return (
            await http.request({
                method,
                url: current.apiBase + '/' + path,
                data,
                signal,
            })
        ).data;
    }
    return {
        get: (path, signal) => request('get', path, undefined, signal),
        post: (path, data, signal) => request('post', path, data, signal),
    };
}
