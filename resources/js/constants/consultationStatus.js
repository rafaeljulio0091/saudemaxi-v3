export const consultationStatuses = Object.freeze({
    SCHEDULED: { label: 'Agendada', tone: 'info' },
    PENDING: { label: 'Pendente', tone: '' },
    WAITING_HELPDESK: { label: 'Aguardando triagem', tone: 'warning' },
    ONGOING_HELPDESK: { label: 'Em triagem', tone: 'warning' },
    WAITING_DOCTOR: { label: 'Aguardando médico', tone: 'warning' },
    ONGOING_DOCTOR: { label: 'Em atendimento', tone: 'success' },
    FINISHED: { label: 'Finalizada', tone: 'success' },
    CANCELED: { label: 'Cancelada', tone: 'danger' },
});
export const activeStatuses = [
    'WAITING_HELPDESK',
    'ONGOING_HELPDESK',
    'WAITING_DOCTOR',
    'ONGOING_DOCTOR',
];
