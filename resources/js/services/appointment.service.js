export const appointmentService = (client) => ({
    search: (filters, signal) =>
        client.post('consultations-search', filters, signal),
    history: (signal) => client.get('consultations', signal),
    specialties: (signal) => client.get('specialties', signal),
    days: (data, signal) => client.post('days', data, signal),
    times: (data, signal) => client.post('times', data, signal),
    doctors: (data, signal) => client.post('doctors', data, signal),
    create: (data) => client.post('schedule', data),
    payment: (data) => client.post('payment', data),
    emergency: () => client.post('emergency', {}),
});
