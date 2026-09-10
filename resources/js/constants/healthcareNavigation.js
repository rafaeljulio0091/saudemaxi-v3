export const modules = [
    {
        key: 'orientacao',
        label: 'Orientação em saúde',
        icon: 'chat',
        description: 'Conte o que está sentindo e encontre o próximo cuidado.',
    },
    {
        key: 'atendimento',
        label: 'Falar com um médico agora',
        icon: 'video',
        description: 'Clínico, pediatra, geriatra e médico da família.',
    },
    {
        key: 'agendamento',
        label: 'Agendar uma consulta',
        icon: 'calendar',
        description: 'Escolha especialidade, dia, horário e profissional.',
    },
    {
        key: 'farmacia',
        label: 'Farmácia popular',
        icon: 'pill',
        description: 'Confira sua receita e encontre farmácias próximas.',
    },
    {
        key: 'nr1',
        label: 'Saúde mental',
        icon: 'heart',
        description:
            'Acolhimento e recursos de apoio. Trilha ainda indisponível.',
    },
];
export const patientMenu = [
    { path: '/inicio', label: 'Início', icon: 'home', group: 'Atendimento' },
    ...modules
        .slice(0, 3)
        .map((m) => ({ path: '/' + m.key, ...m, group: 'Atendimento' })),
    { ...modules[3], path: '/farmacia', group: 'Meus cuidados' },
    {
        path: '/consultas',
        label: 'Minhas consultas',
        icon: 'list',
        group: 'Meus cuidados',
    },
    { ...modules[4], path: '/nr1', group: 'Meus cuidados' },
    { path: '/conta', label: 'Minha conta', icon: 'user', group: 'Conta' },
    { path: '/ajuda', label: 'Ajuda imediata', icon: 'heart', group: 'Conta' },
];
export const managerMenu = [
    {
        path: '/gestor/painel',
        label: 'Painel',
        icon: 'home',
        group: 'Operação',
    },
    {
        path: '/gestor/pacientes',
        label: 'Pacientes',
        icon: 'user',
        group: 'Operação',
    },
    {
        path: '/gestor/consultas',
        label: 'Consultas',
        icon: 'list',
        group: 'Operação',
    },
    {
        path: '/gestor/planos',
        label: 'Planos e módulos',
        icon: 'shield',
        group: 'Configuração',
    },
    {
        path: '/gestor/identidade',
        label: 'Identidade visual',
        icon: 'palette',
        group: 'Configuração',
    },
    {
        path: '/gestor/integracao',
        label: 'Integrações',
        icon: 'link',
        group: 'Configuração',
    },
];
