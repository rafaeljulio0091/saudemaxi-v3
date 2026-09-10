export const patientService = (client) => ({
    list: (filters, signal) => client.post('patients-search', filters, signal),
    find: (id, signal) =>
        client.get('patient/' + encodeURIComponent(id), signal),
    account: (signal) => client.get('account', signal),
    update: (data) => client.post('patient', data),
    create: (data) => client.post('create-patient', data),
    dashboard: (signal) => client.get('dashboard', signal),
});
