export const triageService = (client) => ({
    start: (signal) => client.post('sessoes', { consent: true }, signal),
    find: (sessionId, signal) =>
        client.get('sessoes/' + encodeURIComponent(sessionId), signal),
    send: (sessionId, message, requestId, signal) =>
        client.post(
            'sessoes/' + encodeURIComponent(sessionId) + '/mensagens',
            { message, request_id: requestId },
            signal,
        ),
});
