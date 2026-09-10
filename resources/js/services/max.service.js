export const maxService = (client) => ({
    respond: (message, page, signal) =>
        client.post('max', { message, page }, signal),
});
