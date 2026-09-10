/* ==========================================================================
   Estado da aplicação, com persistência local.

   Tudo que o usuário salva no esboço fica no navegador dele, em localStorage.
   Nada sai da máquina. Ao trocar de cliente ou zerar, o estado é reconstruído
   a partir de js/dados.js.
   ========================================================================== */

import * as D from './dados.js';

const CHAVE = 'saude-maxi-esboco-v1';

export const estado = {
  perfil: 'paciente',
  rede: 'ok',
  cliente: D.CLIENTES.queimados,
  planoId: 1,
  pacienteAtual: null,
  modulos: {},
  pacientes: [],
  consultas: [],
  receitas: [],
  planos: [],
  identidade: null,
  autenticado: false,
  ouvintes: []
};

function base() {
  return {
    perfil: 'paciente',
    clienteId: 'queimados',
    planoId: 1,
    autenticado: false,
    pacientes: JSON.parse(JSON.stringify(D.PACIENTES)),
    consultas: JSON.parse(JSON.stringify(D.CONSULTAS)),
    receitas: JSON.parse(JSON.stringify(D.RECEITAS)),
    planos: JSON.parse(JSON.stringify(D.PLANOS)),
    identidade: {
      queimados:   { ...D.CLIENTES.queimados },
      cetid:       { ...D.CLIENTES.cetid },
      metalurgica: { ...D.CLIENTES.metalurgica }
    }
  };
}

export function carregar() {
  let bruto = null;
  try { bruto = JSON.parse(localStorage.getItem(CHAVE)); } catch (e) { bruto = null; }
  const dados = bruto && bruto.pacientes ? bruto : base();

  estado.perfil = dados.perfil || 'paciente';
  estado.planoId = dados.planoId || 1;
  estado.autenticado = !!dados.autenticado;
  estado.pacientes = dados.pacientes;
  estado.consultas = dados.consultas;
  estado.receitas = dados.receitas;
  estado.planos = dados.planos;
  estado.identidade = dados.identidade || base().identidade;
  estado.cliente = estado.identidade[dados.clienteId || 'queimados'];

  sincronizarPaciente();
  aplicarTema();
}

export function salvar() {
  try {
    localStorage.setItem(CHAVE, JSON.stringify({
      perfil: estado.perfil,
      clienteId: estado.cliente.id,
      planoId: estado.planoId,
      autenticado: estado.autenticado,
      pacientes: estado.pacientes,
      consultas: estado.consultas,
      receitas: estado.receitas,
      planos: estado.planos,
      identidade: estado.identidade
    }));
  } catch (e) {
    /* Janela anônima ou armazenamento bloqueado. O esboço continua
       funcionando, só não guarda entre sessões. */
  }
}

export function zerar() {
  try { localStorage.removeItem(CHAVE); } catch (e) { /* ignora */ }
  carregar();
  avisar();
}

/* O paciente de demonstração é o primeiro titular ativo do cliente atual */
export function sincronizarPaciente() {
  const doCliente = estado.pacientes.filter(p => p.cliente === estado.cliente.id);
  estado.pacienteAtual = doCliente.find(p => p.titular && p.status === 'ACTIVE') || doCliente[0] || estado.pacientes[0];
  if (estado.pacienteAtual) {
    const plano = estado.planos.find(p => p.id === estado.pacienteAtual.planoId);
    if (plano) estado.planoId = plano.id;
  }
  const plano = estado.planos.find(p => p.id === estado.planoId);
  estado.modulos = plano ? { ...plano.modulos } : { ...estado.cliente.modulos };
}

export function trocarCliente(id) {
  estado.cliente = estado.identidade[id];
  const primeiro = estado.planos.find(p => p.cliente === id);
  if (primeiro) estado.planoId = primeiro.id;
  sincronizarPaciente();
  aplicarTema();
  salvar();
  avisar();
}

export function trocarPlano(id) {
  estado.planoId = Number(id);
  const plano = estado.planos.find(p => p.id === estado.planoId);
  estado.modulos = plano ? { ...plano.modulos } : { ...estado.cliente.modulos };
  if (estado.pacienteAtual) estado.pacienteAtual.planoId = estado.planoId;
  salvar();
  avisar();
}

export function trocarPerfil(p) {
  estado.perfil = p;
  salvar();
  avisar();
}

/* Tema por cliente. Uma variável só, igual ao --main-color do oficial. */
export function aplicarTema() {
  const cor = estado.cliente.cor || '#5E5212';
  document.documentElement.style.setProperty('--marca', cor);
  document.documentElement.style.setProperty(
    '--marca-hover',
    `color-mix(in srgb, ${cor} 82%, #fff)`
  );
}

export function planosDoCliente() {
  return estado.planos.filter(p => p.cliente === estado.cliente.id);
}

export function planoAtual() {
  return estado.planos.find(p => p.id === estado.planoId) || null;
}

export function moduloLiberado(chave) {
  return !!estado.modulos[chave];
}

/* Assinatura simples, para a interface se redesenhar quando o estado muda */
export function aoMudar(fn) { estado.ouvintes.push(fn); }
export function avisar() { estado.ouvintes.forEach(fn => fn()); }
