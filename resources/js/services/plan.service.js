export const planService = (client) => ({
    list: (signal) => client.get('plans', signal),
    update: (data) => client.post('plan', data),
    branding: (data) => client.post('branding', data),
});
