export const pharmacyService = (client) => ({
    list: (signal) => client.get('prescriptions', signal),
    find: (id, signal) =>
        client.get('prescription/' + encodeURIComponent(id), signal),
    pharmacies: (signal) => client.get('pharmacies', signal),
    readDemoPhoto: (file) => {
        const form = new FormData();
        if (file) form.append('file', file);

        return client.post('photo', form);
    },
    confirm: (data) => client.post('confirm-item', data),
});
