export const pharmacyService = (client) => ({
    list: (signal) => client.get('prescriptions', signal),
    find: (id, signal) =>
        client.get('prescription/' + encodeURIComponent(id), signal),
    pharmacies: (signal) => client.get('pharmacies', signal),
    readDemoPhoto: () => client.post('photo', {}),
    confirm: (data) => client.post('confirm-item', data),
});
