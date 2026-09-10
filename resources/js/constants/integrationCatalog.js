export const integrationCatalog = [
    {
        name: 'Acesso à plataforma',
        status: 'documented',
        note: 'Magic link, sem substituir a autenticação Saúde Maxi.',
    },
    {
        name: 'Atendimento imediato',
        status: 'documented',
        note: 'Repasse à plataforma por link de acesso.',
    },
    {
        name: 'Especialidades',
        status: 'documented',
        note: 'Catálogo da clínica.',
    },
    {
        name: 'Dias disponíveis',
        status: 'documented',
        note: 'Dependem da especialidade.',
    },
    {
        name: 'Horários disponíveis',
        status: 'documented',
        note: 'Dependem do dia.',
    },
    {
        name: 'Profissionais',
        status: 'documented',
        note: 'Dependem do horário.',
    },
    {
        name: 'Criar consulta',
        status: 'documented',
        note: 'Homologar criação e proteção contra duplicidade.',
    },
    {
        name: 'Marcar pagamento',
        status: 'documented',
        note: 'Registrar confirmação de recebimento externo.',
    },
    {
        name: 'Histórico de consultas',
        status: 'documented',
        note: 'Reconciliar estados pelo servidor.',
    },
    {
        name: 'Listar pacientes',
        status: 'documented',
        note: 'Paginação, até 50 registros por página.',
    },
    {
        name: 'Atualizar paciente',
        status: 'documented',
        note: 'Validar campos autorizados.',
    },
    {
        name: 'Criar paciente',
        status: 'documented',
        note: 'Inclui suporte a estrangeiros.',
    },
    {
        name: 'Etiquetas',
        status: 'documented',
        note: 'Respeitar privacidade e permissões.',
    },
    {
        name: 'Inativar paciente',
        status: 'pending',
        note: 'Depende de permissão do fornecedor.',
    },
    {
        name: 'Orientação clínica',
        status: 'pending',
        note: 'Aguarda roteiro validado pelo responsável clínico.',
    },
    {
        name: 'Leitura de foto',
        status: 'local',
        note: 'Leitura de imagem e medidas de confiança ainda não conectadas.',
    },
    {
        name: 'Farmácias',
        status: 'local',
        note: 'Cadastro oficial fornecido pelo contratante.',
    },
    {
        name: 'Módulos do plano',
        status: 'local',
        note: 'Vínculo contratual sob responsabilidade da Saúde Maxi.',
    },
    {
        name: 'Sincronizar receitas',
        status: 'blocked',
        note: 'Fornecedor não disponibiliza endpoint de prescrição.',
    },
    {
        name: 'Cobertura de medicamentos',
        status: 'blocked',
        note: 'Falta fonte oficial e vigência da lista.',
    },
    {
        name: 'Trilha NR-1',
        status: 'blocked',
        note: 'Aguarda definição de consentimento e privacidade.',
    },
];
