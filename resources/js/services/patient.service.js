export const patientService = (client) => ({
    list: (filters, signal) => client.post('patients-search', filters, signal),
    find: async (id, signal) => {
        const result = await client.get(
            'patient/' + encodeURIComponent(id),
            signal,
        );
        return result.data || result;
    },
    account: (signal) => client.get('account', signal),
    update: async (data) => {
        const result = await client.post('patient', data);
        return result.data || result;
    },
    create: (data) => client.post('create-patient', data),
    dashboard: (signal) => client.get('dashboard', signal),
});
