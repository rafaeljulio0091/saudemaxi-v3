export const pharmacyService = (client) => ({
    list: (signal) => client.get('prescriptions', signal),
    find: (id, signal) =>
        client.get('prescription/' + encodeURIComponent(id), signal),
    pharmacies: (signal) => client.get('pharmacies', signal),
    nearby: (location, signal) =>
        client.post('pharmacies-nearby', location, signal),
    uploadPhoto: (file) => {
        const form = new FormData();
        form.append('file', file, file.name);

        return client.post('photo', form);
    },
    confirm: (data) => client.post('confirm-item', data),
});
