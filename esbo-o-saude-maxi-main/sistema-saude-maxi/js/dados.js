/* ==========================================================================
   Dados simulados.
   Nenhum dado real de paciente. Todos os nomes, documentos e endereços foram
   inventados para demonstração. Os CPFs abaixo são sequências inválidas de
   propósito, para nunca coincidirem com pessoa real.
   ========================================================================== */

export const CLIENTES = {
  queimados: {
    id: 'queimados',
    nome: 'Prefeitura de Queimados',
    tipo: 'Município',
    sigla: 'PQ',
    subdominio: 'queimados.saudemaxi.com.br',
    saudacao: 'Bem-vindo à telemedicina da Prefeitura de Queimados',
    cor: '#5E5212',
    modulos: { orientacao: true, atendimento: true, farmacia: true, agendamento: false, nr1: false },
    // ORIGEM REUNIAO: em prefeitura, agendamento passa pelo núcleo de regulação
    regulacao: true,
    populacao: '22.400 habitantes'
  },
  cetid: {
    id: 'cetid',
    nome: 'Cetid',
    tipo: 'Rede de clínicas',
    sigla: 'CE',
    subdominio: 'cetid.saudemaxi.com.br',
    saudacao: 'Bem-vinda à telemedicina do Cetid de Queimados',
    cor: '#B3261E',
    modulos: { orientacao: true, atendimento: true, farmacia: true, agendamento: true, nr1: false },
    regulacao: false,
    populacao: '4 unidades'
  },
  metalurgica: {
    id: 'metalurgica',
    nome: 'Metalúrgica Bandeirantes',
    tipo: 'Empresa',
    sigla: 'MB',
    subdominio: 'bandeirantes.saudemaxi.com.br',
    saudacao: 'Bem-vindo ao programa de saúde da Metalúrgica Bandeirantes',
    cor: '#1D4E89',
    modulos: { orientacao: true, atendimento: true, farmacia: true, agendamento: true, nr1: true },
    regulacao: false,
    populacao: '247 colaboradores'
  }
};

/* ORIGEM OFICIAL: um plano tem quatro campos e só. ID, nome, clínica e máximo
   de dependentes. Não existe campo de módulo no sistema atual.
   ORIGEM REUNIAO: o de para entre plano e módulo é da camada nova. */
export const PLANOS = [
  { id: 1, nome: 'Municipal Básico',   cliente: 'queimados',   maxDependentes: 4, modulos: { orientacao: true, atendimento: true, farmacia: true, agendamento: false, nr1: false } },
  { id: 2, nome: 'Municipal Ampliado', cliente: 'queimados',   maxDependentes: 4, modulos: { orientacao: true, atendimento: true, farmacia: true, agendamento: false, nr1: true  } },
  { id: 3, nome: 'Cetid Familiar',     cliente: 'cetid',       maxDependentes: 3, modulos: { orientacao: true, atendimento: true, farmacia: true, agendamento: true,  nr1: false } },
  { id: 4, nome: 'Cetid Individual',   cliente: 'cetid',       maxDependentes: 0, modulos: { orientacao: true, atendimento: true, farmacia: true, agendamento: true,  nr1: false } },
  { id: 5, nome: 'Corporativo NR-1',   cliente: 'metalurgica', maxDependentes: 2, modulos: { orientacao: true, atendimento: true, farmacia: true, agendamento: true,  nr1: true  } }
];

export const MODULOS = [
  { chave: 'orientacao',  nome: 'Orientação em saúde',        padrao: true,  nota: 'Em todos os planos, decisão da reunião' },
  { chave: 'atendimento', nome: 'Falar com o médico agora',   padrao: true,  nota: 'Em todos os planos, decisão da reunião' },
  { chave: 'farmacia',    nome: 'Farmácia popular',           padrao: true,  nota: 'Em todos os planos, decisão da reunião' },
  { chave: 'agendamento', nome: 'Agendar consulta',           padrao: false, nota: 'Contratado à parte. Em prefeitura, só pelo núcleo de regulação' },
  { chave: 'nr1',         nome: 'Saúde mental e NR-1',        padrao: false, nota: 'Contratado à parte. Trilha bloqueada até decisão sobre a LGPD' }
];

/* ORIGEM OFICIAL: catálogo lido da tela de preços de especialidades */
export const ESPECIALIDADES = [
  { id: 1,  nome: 'Alergologia',            preco: 0,   precoRede: 0   },
  { id: 2,  nome: 'Alergologia Infantil',   preco: 0,   precoRede: 0   },
  { id: 3,  nome: 'Angiologia',             preco: 0,   precoRede: 120 },
  { id: 4,  nome: 'Cardiologia',            preco: 0,   precoRede: 140 },
  { id: 5,  nome: 'Cirurgião Geral',        preco: 0,   precoRede: 150 },
  { id: 6,  nome: 'Clínica Médica',         preco: 0,   precoRede: 90  },
  { id: 7,  nome: 'Dermatologia',           preco: 0,   precoRede: 160 },
  { id: 8,  nome: 'Endocrinologia',         preco: 0,   precoRede: 150 },
  { id: 9,  nome: 'Geriatria',              preco: 0,   precoRede: 130 },
  { id: 10, nome: 'Medicina de Família',    preco: 0,   precoRede: 90  },
  { id: 11, nome: 'Neurologia',             preco: 0,   precoRede: 180 },
  { id: 12, nome: 'Nutrição',               preco: 0,   precoRede: 80  },
  { id: 13, nome: 'Pediatria',              preco: 0,   precoRede: 110 },
  { id: 14, nome: 'Psicologia',             preco: 0,   precoRede: 100 },
  { id: 15, nome: 'Psicologia NR-1',        preco: 0,   precoRede: 120 }
];

export const MEDICOS = [
  { id: 11, nome: 'Dra. Helena Vasconcelos', especialidade: 'Clínica Médica',      real: true },
  { id: 12, nome: 'Dr. Márcio Tenório',      especialidade: 'Medicina de Família', real: true },
  { id: 13, nome: 'Dra. Bianca Rossi',       especialidade: 'Cardiologia',         real: true },
  { id: 14, nome: 'Dr. Otávio Lins',         especialidade: 'Pediatria',           real: true },
  { id: 0,  nome: 'Primeiro disponível',     especialidade: 'Qualquer',            real: false }
];

/* CPFs propositalmente inválidos, todos com dígitos repetidos */
export const PACIENTES = [
  { id: 101, nome: 'Antônio Ribeiro da Silva', cpf: '111.111.111-11', nascimento: '1958-04-12', telefone: '(11) 90000-0001', email: 'antonio@exemplo.test', titular: true,  planoId: 1, cliente: 'queimados', status: 'ACTIVE',   online: false, adesao: '2026-03-12', expiracao: '2027-03-12', tags: ['Hipertensão'], cidade: 'Queimados', estado: 'RJ', dependentes: 2, consultas: 7 },
  { id: 102, nome: 'Marilene Souza Campos',    cpf: '222.222.222-22', nascimento: '1971-09-30', telefone: '(11) 90000-0002', email: 'marilene@exemplo.test', titular: true,  planoId: 2, cliente: 'queimados', status: 'ACTIVE',   online: true,  adesao: '2026-04-02', expiracao: '2027-04-02', tags: ['Diabetes','Prioridade'], cidade: 'Queimados', estado: 'RJ', dependentes: 1, consultas: 12 },
  { id: 103, nome: 'Joaquim Ferreira Nunes',   cpf: '333.333.333-33', nascimento: '1949-01-08', telefone: '(11) 90000-0003', email: '',                      titular: true,  planoId: 1, cliente: 'queimados', status: 'ACTIVE',   online: false, adesao: '2026-03-20', expiracao: '2027-03-20', tags: ['Idoso'], cidade: 'Queimados', estado: 'RJ', dependentes: 0, consultas: 3 },
  { id: 104, nome: 'Beatriz Almeida Rocha',    cpf: '444.444.444-44', nascimento: '1993-06-21', telefone: '(11) 90000-0004', email: 'beatriz@exemplo.test',  titular: false, planoId: 1, cliente: 'queimados', status: 'ACTIVE',   online: false, adesao: '2026-03-12', expiracao: '2027-03-12', tags: [], cidade: 'Queimados', estado: 'RJ', dependentes: 0, consultas: 1 },
  { id: 105, nome: 'Rogério Pinto Machado',    cpf: '555.555.555-55', nascimento: '1985-11-03', telefone: '(11) 90000-0005', email: 'rogerio@exemplo.test',  titular: true,  planoId: 5, cliente: 'metalurgica', status: 'ACTIVE', online: false, adesao: '2026-06-19', expiracao: '2027-06-19', tags: ['NR-1'], cidade: 'Sorocaba', estado: 'SP', dependentes: 2, consultas: 5 },
  { id: 106, nome: 'Cláudia Menezes Prado',    cpf: '666.666.666-66', nascimento: '1979-02-17', telefone: '(11) 90000-0006', email: 'claudia@exemplo.test',  titular: true,  planoId: 5, cliente: 'metalurgica', status: 'INACTIVE', online: false, adesao: '2026-06-19', expiracao: '2026-08-30', tags: [], cidade: 'Sorocaba', estado: 'SP', dependentes: 0, consultas: 2 },
  { id: 107, nome: 'Wesley Tavares Lopes',     cpf: '777.777.777-77', nascimento: '2001-07-25', telefone: '(11) 90000-0007', email: 'wesley@exemplo.test',   titular: true,  planoId: 3, cliente: 'cetid', status: 'ACTIVE',       online: true,  adesao: '2026-05-04', expiracao: '2027-05-04', tags: ['Asma'], cidade: 'Queimados', estado: 'RJ', dependentes: 0, consultas: 4 },
  { id: 108, nome: 'Neusa Barbosa Figueiredo', cpf: '888.888.888-88', nascimento: '1962-12-01', telefone: '(11) 90000-0008', email: '',                      titular: true,  planoId: 4, cliente: 'cetid', status: 'ACTIVE',       online: false, adesao: '2026-05-11', expiracao: '2027-05-11', tags: ['Hipertensão','Idoso'], cidade: 'Queimados', estado: 'RJ', dependentes: 0, consultas: 9 }
];

/* Máquina de estados real, lida da documentação da API oficial */
export const STATUS_CONSULTA = {
  SCHEDULED:        { rot: 'Agendada',            cor: 'et-marca'  },
  PENDING:          { rot: 'Pendente',            cor: 'et-neutra' },
  WAITING_HELPDESK: { rot: 'Aguardando triagem',  cor: 'et-aviso'  },
  ONGOING_HELPDESK: { rot: 'Em triagem',          cor: 'et-aviso'  },
  WAITING_DOCTOR:   { rot: 'Aguardando médico',   cor: 'et-aviso'  },
  ONGOING_DOCTOR:   { rot: 'Em atendimento',      cor: 'et-ok'     },
  FINISHED:         { rot: 'Finalizada',          cor: 'et-ok'     },
  CANCELED:         { rot: 'Cancelada',           cor: 'et-erro'   }
};

export const CONSULTAS = [
  { codigo: 'CN-4821', pacienteId: 101, tipo: 'POOL',      especialidade: 'Clínica Médica',      medico: 'Dra. Helena Vasconcelos', status: 'FINISHED',       agendadaPara: '2026-08-22T09:41:00', duracao: '11 min', pago: true,  avaliacao: 5, receita: true },
  { codigo: 'CN-4903', pacienteId: 102, tipo: 'SCHEDULED', especialidade: 'Endocrinologia',      medico: 'Dra. Bianca Rossi',       status: 'SCHEDULED',      agendadaPara: '2026-09-08T14:00:00', duracao: null,     pago: false, avaliacao: null, receita: false },
  { codigo: 'CN-4877', pacienteId: 103, tipo: 'POOL',      especialidade: 'Medicina de Família', medico: 'Dr. Márcio Tenório',      status: 'FINISHED',       agendadaPara: '2026-07-09T16:20:00', duracao: '8 min',  pago: true,  avaliacao: 4, receita: true },
  { codigo: 'CN-4950', pacienteId: 107, tipo: 'POOL',      especialidade: 'Clínica Médica',      medico: 'Dra. Helena Vasconcelos', status: 'ONGOING_DOCTOR', agendadaPara: '2026-09-02T10:05:00', duracao: null,     pago: true,  avaliacao: null, receita: false },
  { codigo: 'CN-4952', pacienteId: 102, tipo: 'POOL',      especialidade: 'Clínica Médica',      medico: null,                       status: 'WAITING_HELPDESK', agendadaPara: '2026-09-02T10:12:00', duracao: null,   pago: true,  avaliacao: null, receita: false },
  { codigo: 'CN-4820', pacienteId: 108, tipo: 'SCHEDULED', especialidade: 'Cardiologia',         medico: 'Dra. Bianca Rossi',       status: 'CANCELED',       agendadaPara: '2026-08-15T11:00:00', duracao: null,     pago: false, avaliacao: null, receita: false }
];

/* ORIGEM REUNIAO e OFICIAL: a receita sai pela integração com o Mevo dentro do
   atendimento. A API da clínica NÃO expõe prescrição. Por isso a receita por
   foto continua sendo o caminho que funciona hoje. */
export const RECEITAS = [
  {
    id: 'RC-1', origem: 'plataforma', data: '2026-08-22', medico: 'Dra. Helena Vasconcelos',
    especialidade: 'Clínica Médica', consulta: 'CN-4821',
    itens: [
      { nome: 'Losartana potássica 50 mg', posologia: '1 comprimido pela manhã, uso contínuo', qtd: '60 comprimidos', cobertura: 'coberto',   confianca: 1 },
      { nome: 'Salbutamol aerossol 100 mcg', posologia: '2 jatos em caso de falta de ar',      qtd: '1 frasco',       cobertura: 'coberto',   confianca: 1 },
      { nome: 'Metformina 850 mg',          posologia: '1 comprimido após o almoço',           qtd: '60 comprimidos', cobertura: 'coberto',   confianca: 1 },
      { nome: 'Rosuvastatina 10 mg',        posologia: '1 comprimido à noite',                 qtd: '30 comprimidos', cobertura: 'naoCoberto',confianca: 1 }
    ]
  },
  {
    id: 'RC-2', origem: 'foto', data: '2026-06-03', medico: 'Receita de fora da plataforma',
    especialidade: null, consulta: null,
    itens: [
      { nome: 'Hidroclorotiazida 25 mg',  posologia: '1 comprimido pela manhã', qtd: '30 comprimidos', cobertura: 'coberto',   confianca: 0.94 },
      { nome: 'Enalapril 10 mg',          posologia: 'Leitura incerta',         qtd: '30 comprimidos', cobertura: 'confirmar', confianca: 0.61 },
      { nome: 'Sinvastatina 20 mg',       posologia: 'Leitura incerta',         qtd: '30 comprimidos', cobertura: 'confirmar', confianca: 0.55 }
    ]
  }
];

export const FARMACIAS = [
  { id: 1, nome: 'Drogaria São Bento',        endereco: 'Praça da Matriz, 45, Centro',            distancia: '650 m', horario: 'Aberta até as 22h', cobre: ['Losartana potássica 50 mg','Salbutamol aerossol 100 mcg','Metformina 850 mg'] },
  { id: 2, nome: 'Farmácia Vida Nova',        endereco: 'Avenida Sete de Setembro, 1180, Vila Rosa', distancia: '1,8 km', horario: 'Aberta até as 20h', cobre: ['Losartana potássica 50 mg','Metformina 850 mg'] },
  { id: 3, nome: 'Drogaria Popular do Jardim', endereco: 'Rua das Acácias, 77, Jardim União',     distancia: '2,4 km', horario: 'Aberta até as 19h', cobre: ['Losartana potássica 50 mg'] }
];

export const UNIDADES = [
  { nome: 'UBS Central',              tipo: 'Unidade básica de saúde', endereco: 'Rua Coronel Bento Ferraz, 212, Centro', horario: 'Segunda a sexta, 7h às 17h' },
  { nome: 'Pronto Atendimento Norte', tipo: 'Pronto atendimento',      endereco: 'Avenida Brasil, 3400, Vila Nova',       horario: '24 horas' },
  { nome: 'Hospital Municipal',       tipo: 'Hospital',                endereco: 'Rua da Saúde, 90, Centro',              horario: '24 horas' }
];

/* Roteiro do assistente de orientação. Não conclui sobre doença, não nomeia
   doença, e sempre encaminha. ORIGEM: regra 2 do projeto. */
export const ROTEIRO_TRIAGEM = [
  {
    gatilhos: ['cabeça','cabeca','dor de cabeça','enxaqueca'],
    resposta: 'Entendi. Dor de cabeça tem muitas causas possíveis, e eu não vou concluir qual é a sua. Para eu orientar melhor: há quanto tempo começou, e você sentiu junto febre, vômito, alteração na visão ou fraqueza de um lado do corpo?',
    seguir: 'orientacao'
  },
  {
    gatilhos: ['peito','falta de ar','respirar','coração','coracao'],
    resposta: 'Obrigado por contar. Dor no peito e falta de ar são sinais que merecem avaliação rápida, e isso eu não avalio por aqui.',
    seguir: 'urgencia'
  },
  {
    gatilhos: ['febre','garganta','gripe','tosse','resfriado'],
    resposta: 'Certo. Para eu te orientar melhor: há quantos dias está com febre, e você mediu a temperatura? Sentiu falta de ar, manchas na pele, ou dificuldade para engolir líquido?',
    seguir: 'orientacao'
  },
  {
    gatilhos: ['pressão','pressao','remédio','remedio','medicamento','receita'],
    resposta: 'Posso te ajudar com a parte de medicamento. Eu não altero nem sugiro troca de receita, isso é do seu médico. O que eu faço é dizer quais itens da sua receita você retira de graça na farmácia popular e onde tem.',
    seguir: 'farmacia'
  }
];

export const SINAIS_GRAVIDADE = [
  'Dor no peito que aperta ou irradia para o braço, o pescoço ou as costas',
  'Falta de ar que atrapalha falar uma frase inteira',
  'Fraqueza ou dormência de um lado do corpo, boca torta, fala embolada',
  'Desmaio, confusão mental ou dificuldade para acordar a pessoa',
  'Sangramento que não para',
  'Febre alta com manchas na pele ou pescoço duro',
  'Convulsão'
];

/* Indicadores do painel do gestor. ORIGEM OFICIAL, lidos da tela real. */
export const INDICADORES = {
  pacientesOnline: 2,
  agendamentos: 46,
  consultas: 363,
  atestados: 58,
  receitas: 214,
  exames: 91,
  encaminhamentos: 27,
  taxaConfirmacao: 88,
  satisfacao: 95,
  esperaProntoAtendimento: '3m 41s',
  esperaConsultorio: '1m 12s',
  duracaoMedia: '9m 24s'
};

export const CONSULTAS_POR_HORA = [2,1,0,0,0,1,4,9,14,18,21,17,12,15,19,22,16,11,8,6,5,4,3,2];
export const CONSULTAS_POR_DIA  = [
  { dia: 'Seg', v: 74 }, { dia: 'Ter', v: 68 }, { dia: 'Qua', v: 71 },
  { dia: 'Qui', v: 63 }, { dia: 'Sex', v: 58 }, { dia: 'Sáb', v: 21 }, { dia: 'Dom', v: 8 }
];
export const FAIXA_ETARIA = [
  { faixa: '0 a 12',   v: 41 }, { faixa: '13 a 17', v: 18 }, { faixa: '18 a 39', v: 96 },
  { faixa: '40 a 59',  v: 118 }, { faixa: '60 ou mais', v: 90 }
];

export const DEPENDENTES = [
  { nome: 'Beatriz Almeida Rocha', parentesco: 'Filha',   nascimento: '1993-06-21' },
  { nome: 'Tomás Ribeiro da Silva', parentesco: 'Filho',  nascimento: '2014-02-09' }
];
