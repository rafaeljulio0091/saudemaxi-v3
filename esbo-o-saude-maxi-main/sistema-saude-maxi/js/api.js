/* ==========================================================================
   Camada de serviço.

   Cada função abaixo espelha um endpoint real da plataforma existente,
   levantado em 2 de setembro de 2026 e transcrito em
   docs/sistema-oficial/api-clinic.md.

   URL base real: https://saudemaxi.com.br/api/clinic/
   Autenticação real: Authorization: Bearer <token de serviço da clínica>

   TROCAR SIMULAÇÃO POR API REAL: substituir o corpo de cada função por uma
   chamada a `requisicao()`, que já está escrita no fim deste arquivo. A
   assinatura de entrada e o formato de saída de cada função foram desenhados
   para bater com o contrato real, então o resto do sistema não muda.

   Situação de cada endpoint:
     VERDE    já existe na plataforma e está documentado
     AZUL     simulado aqui, não existe endpoint correspondente
     AMARELO  existe, mas depende de definição com o fornecedor
     VERMELHO bloqueado por decisão pendente do cliente
   ========================================================================== */

import * as D from './dados.js';
import { estado, salvar } from './estado.js';

export const BASE_REAL = 'https://saudemaxi.com.br/api/clinic/';

/* Catálogo de integração, usado pela tela de Integração do gestor */
export const CATALOGO = [
  { fn: 'loginPaciente',        metodo: 'POST',  rota: 'login-patient/',                     sit: 'verde',    nota: 'Magic link por CPF. Validade não documentada, pergunta Q30' },
  { fn: 'abrirProntoAtendimento', metodo: 'POST', rota: 'create-emergency-consultation/',    sit: 'verde',    nota: 'Magic link sem expiração por tempo, com external_url de retorno' },
  { fn: 'listarEspecialidades', metodo: 'GET',   rota: 'scheduling/specialties/',            sit: 'verde',    nota: 'Respeita modo CLINIC ou PLATFORM' },
  { fn: 'listarDias',           metodo: 'POST',  rota: 'scheduling/business-days/',          sit: 'verde',    nota: '' },
  { fn: 'listarHorarios',       metodo: 'POST',  rota: 'scheduling/available-times/',        sit: 'verde',    nota: '' },
  { fn: 'listarMedicos',        metodo: 'POST',  rota: 'scheduling/doctors/',                sit: 'verde',    nota: 'is_real separa médico real de genérico' },
  { fn: 'criarConsulta',        metodo: 'POST',  rota: 'scheduling/create-consultation/',    sit: 'verde',    nota: 'Aceita is_paid na criação' },
  { fn: 'marcarPagamento',      metodo: 'POST',  rota: 'scheduling/update-payment-status/',  sit: 'verde',    nota: 'Permite cobrar por fora e só marcar aqui. Resolve a decisão do recebimento' },
  { fn: 'historicoConsultas',   metodo: 'GET',   rota: 'consultation-history/',              sit: 'verde',    nota: 'Única forma de reconciliar, porque não existe webhook' },
  { fn: 'filtrarPacientes',     metodo: 'GET',   rota: 'filter-patients/',                   sit: 'verde',    nota: 'Paginado, máximo 50 por página' },
  { fn: 'atualizarPaciente',    metodo: 'PATCH', rota: 'update-patient/',                    sit: 'verde',    nota: 'Identificador é o CPF' },
  { fn: 'criarPaciente',        metodo: 'POST',  rota: 'create-patient/',                    sit: 'verde',    nota: 'Suporta paciente estrangeiro, o Projeto Angola' },
  { fn: 'alternarStatusPaciente', metodo: 'POST', rota: 'toggle-patient-status/',            sit: 'amarelo',  nota: 'Exige a chave "Permitir que a clínica inative pacientes", que fica em nível de administração' },
  { fn: 'listarTags',           metodo: 'GET',   rota: 'patient-tags/',                      sit: 'verde',    nota: '' },
  { fn: 'buscarReceitas',       metodo: '',      rota: 'não existe',                         sit: 'vermelho', nota: 'NÃO EXISTE endpoint de prescrição. A receita sai pela Mevo dentro do atendimento. Pergunta Q35' },
  { fn: 'lerReceitaPorFoto',    metodo: '',      rota: 'camada nova',                        sit: 'azul',     nota: 'Leitura de imagem com medida de confiança. É da camada nova, não do fornecedor' },
  { fn: 'consultarCobertura',   metodo: '',      rota: 'camada nova',                        sit: 'vermelho', nota: 'Depende da lista oficial de medicamentos, que não tem fonte definida. Pergunta Q03' },
  { fn: 'listarFarmacias',      metodo: '',      rota: 'camada nova',                        sit: 'azul',     nota: 'Cadastro entregue pelo município na adesão' },
  { fn: 'modulosDoPlano',       metodo: '',      rota: 'camada nova',                        sit: 'azul',     nota: 'O plano do sistema atual tem só quatro campos e nenhum módulo. O de para é nosso' },
  { fn: 'orientar',             metodo: '',      rota: 'camada nova',                        sit: 'amarelo',  nota: 'Depende do script de fundamentação clínica, prometido pelo cliente' },
  { fn: 'nr1',                  metodo: '',      rota: 'camada nova',                        sit: 'vermelho', nota: 'BLOQUEADO. Decisão sobre relatório individual ao gestor, pergunta Q01' }
];

/* ==========================================================================
   Simulação de rede
   ========================================================================== */

export class ErroApi extends Error {
  constructor(mensagem, status) { super(mensagem); this.status = status; }
}

function atraso() {
  const m = estado.rede;
  if (m === 'lenta') return 1400 + Math.random() * 900;
  return 260 + Math.random() * 240;
}

function simular(carga, opcoes = {}) {
  return new Promise((ok, falha) => {
    setTimeout(() => {
      if (estado.rede === 'erro' && !opcoes.imuneAFalha) {
        return falha(new ErroApi('Não foi possível falar com a plataforma de atendimento.', 503));
      }
      if (estado.rede === 'vazio' && opcoes.podeFicarVazio) {
        return ok(Array.isArray(carga) ? [] : { ...carga, results: [], count: 0 });
      }
      ok(typeof carga === 'function' ? carga() : carga);
    }, atraso());
  });
}

const copia = (v) => JSON.parse(JSON.stringify(v));

/* ==========================================================================
   Identidade e acesso
   ========================================================================== */

/* VERDE  POST /api/clinic/login-patient/
   Entrada real: { cpf }
   Saída real:   { magic_link, expires_at, message } */
export function loginPaciente(cpf) {
  return simular(() => ({
    magic_link: `https://${estado.cliente.subdominio}/patient-login/tk_${Math.random().toString(36).slice(2, 12)}/`,
    expires_at: new Date(Date.now() + 15 * 60000).toISOString(),
    message: 'Magic link gerado com sucesso'
  }));
}

/* VERDE  POST /api/clinic/create-emergency-consultation/
   Entrada real: { cpf, external_url }
   Saída real:   { magic_link, consultation_code, created, expires_at, external_url, message }
   O magic link NÃO expira por tempo. Vale enquanto a consulta estiver aberta.
   Ao encerrar, a plataforma devolve o paciente para external_url com
   consultation_code e status na query string. */
export function abrirProntoAtendimento(cpf, urlRetorno) {
  return simular(() => {
    const codigo = 'CN-' + (5000 + Math.floor(Math.random() * 900));
    return {
      magic_link: `https://${estado.cliente.subdominio}/patient-login/tk_${Math.random().toString(36).slice(2, 12)}/`,
      consultation_code: codigo,
      created: true,
      expires_at: null,
      external_url: urlRetorno,
      message: 'Consulta de pronto atendimento criada'
    };
  });
}

/* ==========================================================================
   Agendamento. Fluxo de seis chamadas, exatamente como a plataforma documenta.
   ========================================================================== */

/* VERDE  GET /api/clinic/scheduling/specialties/  ->  [{ id, name, price }] */
export function listarEspecialidades() {
  return simular(() => copia(D.ESPECIALIDADES).map(e => ({
    id: e.id, name: e.nome, price: estado.cliente.regulacao ? 0 : e.precoRede
  })), { podeFicarVazio: true });
}

/* VERDE  POST /api/clinic/scheduling/business-days/  ->  ["YYYY-MM-DD"] */
export function listarDias(especialidadeId) {
  return simular(() => {
    const dias = [];
    const hoje = new Date();
    for (let i = 1; i <= 21 && dias.length < 8; i++) {
      const d = new Date(hoje.getTime() + i * 86400000);
      if (d.getDay() === 0 || d.getDay() === 6) continue;
      dias.push(d.toISOString().slice(0, 10));
    }
    return dias;
  }, { podeFicarVazio: true });
}

/* VERDE  POST /api/clinic/scheduling/available-times/  ->  ["HH:MM"] */
export function listarHorarios(especialidadeId, data) {
  return simular(() => {
    const base = ['08:00','08:30','09:00','09:30','10:00','11:00','13:30','14:00','14:30','15:00','16:00','16:30'];
    const semente = (data || '').split('-').pop() | 0;
    return base.filter((_, i) => (i + semente) % 3 !== 0);
  }, { podeFicarVazio: true });
}

/* VERDE  POST /api/clinic/scheduling/doctors/
   ->  [{ id, name, specialty, price, is_real }] */
export function listarMedicos(especialidadeId, data, hora) {
  return simular(() => {
    const esp = D.ESPECIALIDADES.find(e => e.id === especialidadeId);
    const nome = esp ? esp.nome : '';
    const reais = D.MEDICOS.filter(m => m.real && (m.especialidade === nome || nome === ''));
    const lista = reais.length ? reais : [];
    const preco = estado.cliente.regulacao ? 0 : (esp ? esp.precoRede : 0);
    return [
      ...lista.map(m => ({ id: m.id, name: m.nome, specialty: m.especialidade, price: preco, is_real: true })),
      { id: 0, name: 'Primeiro profissional disponível', specialty: nome, price: preco, is_real: false }
    ];
  });
}

/* VERDE  POST /api/clinic/scheduling/create-consultation/
   Entrada real: { patient_cpf, specialty_id, date, time, doctor_id?, is_real_doctor?, is_paid? }
   Saída real 201: { success, message, consultation_code, consultation_id,
                     scheduled_for, patient_link, is_paid, price } */
export function criarConsulta(dados) {
  return simular(() => {
    const codigo = 'CN-' + (5100 + Math.floor(Math.random() * 800));
    const registro = {
      codigo,
      pacienteId: estado.pacienteAtual.id,
      tipo: 'SCHEDULED',
      especialidade: dados.especialidadeNome,
      medico: dados.medicoNome,
      status: 'SCHEDULED',
      agendadaPara: `${dados.date}T${dados.time}:00`,
      duracao: null,
      pago: !!dados.is_paid,
      avaliacao: null,
      receita: false
    };
    estado.consultas.unshift(registro);
    salvar();
    return {
      success: true,
      message: 'Consulta agendada',
      consultation_code: codigo,
      consultation_id: Math.floor(Math.random() * 90000),
      scheduled_for: registro.agendadaPara,
      patient_link: `https://${estado.cliente.subdominio}/consulta/${codigo}/`,
      is_paid: !!dados.is_paid,
      price: dados.price || 0
    };
  });
}

/* VERDE  POST /api/clinic/scheduling/update-payment-status/
   É este endpoint que permite cobrar por fora e apenas marcar aqui. */
export function marcarPagamento(codigo, pago) {
  return simular(() => {
    const c = estado.consultas.find(x => x.codigo === codigo);
    if (c) { c.pago = pago; salvar(); }
    return { success: true, consultation_code: codigo, is_paid: pago, paid_at: new Date().toISOString() };
  });
}

/* ==========================================================================
   Consultas e pacientes
   ========================================================================== */

/* VERDE  GET /api/clinic/consultation-history/
   Sem webhook na plataforma, esta é a única forma de reconciliar. */
export function historicoConsultas(filtros = {}) {
  return simular(() => {
    let lista = copia(estado.consultas);
    if (filtros.pacienteId) lista = lista.filter(c => c.pacienteId === filtros.pacienteId);
    if (filtros.status)     lista = lista.filter(c => c.status === filtros.status);
    if (filtros.busca) {
      const t = filtros.busca.toLowerCase();
      lista = lista.filter(c => (c.codigo + ' ' + c.especialidade + ' ' + (c.medico || '')).toLowerCase().includes(t));
    }
    return { count: lista.length, next: null, previous: null, results: lista };
  }, { podeFicarVazio: true });
}

/* VERDE  GET /api/clinic/filter-patients/  paginado, máximo 50 */
export function filtrarPacientes(filtros = {}) {
  return simular(() => {
    let lista = copia(estado.pacientes).filter(p => p.cliente === estado.cliente.id);
    if (filtros.busca) {
      const t = filtros.busca.toLowerCase();
      lista = lista.filter(p => (p.nome + ' ' + p.cpf + ' ' + (p.email || '')).toLowerCase().includes(t));
    }
    if (filtros.status && filtros.status !== 'TODOS') lista = lista.filter(p => p.status === filtros.status);
    if (filtros.planoId) lista = lista.filter(p => String(p.planoId) === String(filtros.planoId));
    if (filtros.titularidade === 'titular')    lista = lista.filter(p => p.titular);
    if (filtros.titularidade === 'dependente') lista = lista.filter(p => !p.titular);
    return { count: lista.length, next: null, previous: null, results: lista };
  }, { podeFicarVazio: true });
}

export function buscarPaciente(id) {
  return simular(() => copia(estado.pacientes.find(p => p.id === Number(id)) || null));
}

/* VERDE  PATCH /api/clinic/update-patient/  identificador é o CPF */
export function atualizarPaciente(cpf, campos) {
  return simular(() => {
    const p = estado.pacientes.find(x => x.cpf === cpf);
    if (!p) throw new ErroApi('Paciente não encontrado', 404);
    Object.assign(p, campos);
    salvar();
    return copia(p);
  });
}

/* AMARELO  POST /api/clinic/toggle-patient-status/
   A plataforma exige a chave "Permitir que a clínica inative pacientes",
   que mora num nível de administração acima do gestor da clínica. */
export function alternarStatusPaciente(cpf, ativo) {
  return simular(() => {
    const p = estado.pacientes.find(x => x.cpf === cpf);
    if (!p) throw new ErroApi('Paciente não encontrado', 404);
    p.status = ativo ? 'ACTIVE' : 'INACTIVE';
    if (!ativo) p.online = false;
    salvar();
    return { success: true, patient: copia(p) };
  });
}

/* ==========================================================================
   Camada nova. Nada disto existe na plataforma do fornecedor.
   ========================================================================== */

/* AZUL  O plano da plataforma tem quatro campos e nenhum módulo.
   O de para entre plano e módulo é da camada nova. */
export function modulosDoPlano(planoId) {
  return simular(() => {
    const plano = estado.planos.find(p => p.id === Number(planoId));
    return plano ? copia(plano.modulos) : copia(estado.cliente.modulos);
  }, { imuneAFalha: true });
}

export function salvarModulosDoPlano(planoId, modulos) {
  return simular(() => {
    const plano = estado.planos.find(p => p.id === Number(planoId));
    if (plano) { plano.modulos = { ...modulos }; salvar(); }
    return copia(plano);
  });
}

/* VERMELHO  Não existe endpoint de prescrição na plataforma.
   A receita sai pela integração com o Mevo dentro do atendimento. */
export function buscarReceitas(pacienteId) {
  return simular(() => copia(estado.receitas), { podeFicarVazio: true });
}

/* AZUL  Leitura de imagem com medida de confiança.
   ORIGEM: regra 8 do projeto. Foto ruim é o caso normal, e confiança baixa
   vira pergunta ao paciente, nunca afirmação. */
export function lerReceitaPorFoto() {
  return new Promise((ok) => {
    setTimeout(() => {
      const nova = {
        id: 'RC-' + (Math.floor(Math.random() * 900) + 100),
        origem: 'foto',
        data: new Date().toISOString().slice(0, 10),
        medico: 'Receita de fora da plataforma',
        especialidade: null, consulta: null,
        itens: [
          { nome: 'Losartana potássica 50 mg', posologia: '1 comprimido pela manhã', qtd: '30 comprimidos', cobertura: 'coberto',    confianca: 0.96 },
          { nome: 'Anlodipino 5 mg',           posologia: 'Leitura incerta',         qtd: '30 comprimidos', cobertura: 'confirmar',  confianca: 0.58 },
          { nome: 'Ácido acetilsalicílico 100 mg', posologia: '1 comprimido após o almoço', qtd: '30 comprimidos', cobertura: 'coberto', confianca: 0.88 }
        ]
      };
      estado.receitas.unshift(nova);
      salvar();
      ok(nova);
    }, 2200);
  });
}

/* AZUL  Cadastro entregue pelo município no momento da adesão. */
export function listarFarmacias(itensCobertos) {
  return simular(() => copia(D.FARMACIAS), { podeFicarVazio: true });
}

/* AMARELO  Depende do script de fundamentação clínica, prometido pelo cliente.
   Enquanto ele não chega, este roteiro é demonstração, e nunca conclui sobre
   doença. ORIGEM: regras 2 e 7 do projeto. */
export function orientar(texto) {
  return new Promise((ok) => {
    setTimeout(() => {
      const t = (texto || '').toLowerCase();
      const achado = D.ROTEIRO_TRIAGEM.find(r => r.gatilhos.some(g => t.includes(g)));
      if (achado) return ok({ resposta: achado.resposta, seguir: achado.seguir });
      ok({
        resposta: 'Certo. Para eu conseguir te orientar, me conta um pouco mais: o que você está sentindo, desde quando, e se piorou nos últimos dias. Se preferir, pode falar em vez de escrever.',
        seguir: null
      });
    }, 700 + Math.random() * 600);
  });
}

/* ==========================================================================
   Chamada real, pronta para uso.
   Quando o token e a URL forem liberados, troque o corpo das funções acima
   por chamadas a esta função. Nada mais no sistema precisa mudar.
   ========================================================================== */

export async function requisicao(rota, { metodo = 'GET', corpo = null, token = null, base = BASE_REAL } = {}) {
  const cabecalhos = { Authorization: `Bearer ${token}` };
  if (corpo) cabecalhos['Content-Type'] = 'application/json';

  const resposta = await fetch(base + rota, {
    method: metodo,
    headers: cabecalhos,
    body: corpo ? JSON.stringify(corpo) : undefined
  });

  if (resposta.status === 401) throw new ErroApi('Token inválido', 401);
  if (resposta.status === 404) throw new ErroApi('Não encontrado', 404);
  if (!resposta.ok) {
    let detalhe = '';
    try { detalhe = (await resposta.json()).error || ''; } catch (e) { /* corpo não JSON */ }
    throw new ErroApi(detalhe || `Falha na plataforma, código ${resposta.status}`, resposta.status);
  }
  return resposta.json();
}
