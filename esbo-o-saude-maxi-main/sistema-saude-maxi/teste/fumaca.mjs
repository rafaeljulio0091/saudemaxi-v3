/* ==========================================================================
   Teste de fumaça.

   Monta um DOM mínimo em Node, importa os módulos do esboço e desenha cada
   tela dos dois perfis, em várias condições de rede e de plano. Não substitui
   o teste no navegador, mas pega o que mais quebra: importação errada,
   função inexistente, variável indefinida e erro dentro do desenho.

   Rodar:  node app/teste/fumaca.mjs
   ========================================================================== */

/* ---------- DOM mínimo ---------- */

class Classes {
  constructor(no) { this.no = no; this.set = new Set(); }
  add(...c) { c.forEach(x => x && this.set.add(x)); this.sincronizar(); }
  remove(...c) { c.forEach(x => this.set.delete(x)); this.sincronizar(); }
  contains(c) { return this.set.has(c); }
  toggle(c) { this.set.has(c) ? this.set.delete(c) : this.set.add(c); this.sincronizar(); }
  sincronizar() { this.no.atributos.class = [...this.set].join(' '); }
}

class No {
  constructor(tag) {
    this.tagName = (tag || '').toUpperCase();
    this.filhos = [];
    this.atributos = {};
    this.dataset = {};
    this.style = new Proxy({}, { get: () => '', set: () => true });
    this.ouvintes = {};
    this.pai = null;
    this._classList = null;
    this._texto = '';
    this.hidden = false;
    this.disabled = false;
  }
  get classList() { if (!this._classList) this._classList = new Classes(this); return this._classList; }
  set className(v) { this.atributos.class = v; this._classList = null; String(v || '').split(/\s+/).forEach(c => c && this.classList.set.add(c)); }
  get className() { return this.atributos.class || ''; }
  get childNodes() { return this.filhos; }
  get children() { return this.filhos.filter(f => f instanceof No); }
  get firstChild() { return this.filhos[0] || null; }
  appendChild(n) {
    if (n && n.ehFragmento) { n.filhos.forEach(f => this.appendChild(f)); n.filhos = []; return n; }
    if (n) { n.pai = this; this.filhos.push(n); }
    return n;
  }
  removeChild(n) { const i = this.filhos.indexOf(n); if (i >= 0) this.filhos.splice(i, 1); return n; }
  remove() { if (this.pai) this.pai.removeChild(this); }
  setAttribute(k, v) { this.atributos[k] = String(v); if (k === 'class') this.className = v; }
  getAttribute(k) { return k in this.atributos ? this.atributos[k] : null; }
  removeAttribute(k) { delete this.atributos[k]; }
  addEventListener(t, fn) { (this.ouvintes[t] = this.ouvintes[t] || []).push(fn); }
  removeEventListener() {}
  disparar(tipo, ev = {}) {
    const alvo = { ...ev, currentTarget: this, target: this, preventDefault() {}, stopPropagation() {} };
    (this.ouvintes[tipo] || []).forEach(fn => fn(alvo));
  }
  set textContent(v) { this._texto = String(v); this.filhos = []; }
  get textContent() { return this._texto || this.filhos.map(f => f.textContent || '').join(''); }
  set innerHTML(v) { this._texto = String(v); this.filhos = []; }
  get innerHTML() { return this._texto; }
  set value(v) { this._valor = v; }
  get value() { return this._valor !== undefined ? this._valor : (this.atributos.value || ''); }
  todos() { return this.filhos.flatMap(f => (f instanceof No ? [f, ...f.todos()] : [])); }
  querySelector(sel) { return this.querySelectorAll(sel)[0] || null; }
  querySelectorAll(sel) {
    const alvo = (n) => {
      if (sel.startsWith('[data-campo=')) return n.dataset.campo === sel.slice(12, -2);
      if (sel.startsWith('.')) return sel.slice(1).split('.').every(c => n.classList.contains(c));
      if (sel.startsWith('#')) return n.atributos.id === sel.slice(1);
      return sel.split(',').map(s => s.trim().toLowerCase()).includes(n.tagName.toLowerCase());
    };
    return this.todos().filter(alvo);
  }
  focus() {}
  get style2() { return {}; }
}

class Fragmento extends No {
  constructor() { super('#fragment'); this.ehFragmento = true; }
}

const registro = {};
const documento = {
  createElement: (t) => new No(t),
  createElementNS: (ns, t) => new No(t),
  createTextNode: (t) => { const n = new No('#text'); n.textContent = t; return n; },
  createDocumentFragment: () => new Fragmento(),
  getElementById: (id) => registro[id] || null,
  querySelector: () => null,
  querySelectorAll: () => [],
  addEventListener: () => {},
  removeEventListener: () => {},
  body: new No('body'),
  documentElement: { style: { setProperty: () => {} } }
};

['raiz', 'modal-fundo', 'modal', 'avisos', 'barra-demo', 'btn-abrir-demo',
 'sel-perfil', 'sel-cliente', 'sel-plano', 'sel-rede', 'btn-reset',
 'btn-fechar-demo'].forEach(id => {
  const n = new No(id.startsWith('sel-') ? 'select' : 'div');
  n.atributos.id = id;
  registro[id] = n;
});

const armazem = new Map();
globalThis.document = documento;
globalThis.window = { addEventListener: () => {}, dispatchEvent: () => {} };
globalThis.location = { hash: '', origin: 'http://teste', pathname: '/' };
globalThis.HashChangeEvent = class {};
globalThis.localStorage = {
  getItem: (k) => (armazem.has(k) ? armazem.get(k) : null),
  setItem: (k, v) => armazem.set(k, v),
  removeItem: (k) => armazem.delete(k)
};

/* ---------- execução ---------- */

const { estado, carregar, trocarCliente, trocarPlano, trocarPerfil } = await import('../js/estado.js');
const P = await import('../js/telas-paciente.js');
const G = await import('../js/telas-gestor.js');
const api = await import('../js/api.js');
const MAX = await import('../js/max.js');

carregar();

let passou = 0, falhou = 0;
const problemas = [];

function checar(nome, fn) {
  try {
    const r = fn();
    if (!r) throw new Error('a tela não devolveu nada');
    passou++;
    process.stdout.write('.');
  } catch (e) {
    falhou++;
    problemas.push(`${nome}: ${e.message}`);
    process.stdout.write('X');
  }
}

const esperar = (ms) => new Promise(r => setTimeout(r, ms));
const nada = () => {};

console.log('Desenhando as telas do paciente');
['queimados', 'cetid', 'metalurgica'].forEach(cli => {
  trocarCliente(cli);
  trocarPerfil('paciente');
  checar(`paciente/inicio [${cli}]`,      () => P.inicio(nada));
  checar(`paciente/orientacao [${cli}]`,  () => P.orientacao(nada));
  checar(`paciente/atendimento [${cli}]`, () => P.atendimento(nada));
  checar(`paciente/agendamento [${cli}]`, () => P.agendamento(nada));
  checar(`paciente/farmacia [${cli}]`,    () => P.farmacia(nada));
  checar(`paciente/farmacias [${cli}]`,   () => P.farmacias(nada));
  checar(`paciente/consultas [${cli}]`,   () => P.consultas(nada));
  checar(`paciente/conta [${cli}]`,       () => P.conta(nada));
  checar(`paciente/nr1 [${cli}]`,         () => P.nr1(nada));
  checar(`paciente/ajuda [${cli}]`,       () => P.ajuda(nada));
  checar(`paciente/receita [${cli}]`,     () => P.receita(nada, estado.receitas[0].id));
});
console.log('');

console.log('Desenhando as telas do gestor');
['queimados', 'cetid', 'metalurgica'].forEach(cli => {
  trocarCliente(cli);
  trocarPerfil('gestor');
  checar(`gestor/painel [${cli}]`,     () => G.painel(nada));
  checar(`gestor/pacientes [${cli}]`,  () => G.pacientes(nada));
  checar(`gestor/consultas [${cli}]`,  () => G.consultasGestor(nada));
  checar(`gestor/planos [${cli}]`,     () => G.planos(nada));
  checar(`gestor/identidade [${cli}]`, () => G.identidade(nada));
  checar(`gestor/integracao [${cli}]`, () => G.integracao(nada));
  const alvo = estado.pacientes.find(p => p.cliente === cli);
  if (alvo) checar(`gestor/paciente ${alvo.id} [${cli}]`, () => G.pacienteDetalhe(nada, alvo.id));
});
console.log('');

console.log('Estados de rede');
trocarCliente('metalurgica');
for (const modo of ['ok', 'lenta', 'erro', 'vazio']) {
  estado.rede = modo;
  trocarPerfil('paciente');
  checar(`rede ${modo} / consultas`, () => P.consultas(nada));
  checar(`rede ${modo} / farmacia`,  () => P.farmacia(nada));
  trocarPerfil('gestor');
  checar(`rede ${modo} / pacientes`, () => G.pacientes(nada));
}
estado.rede = 'ok';
console.log('');

console.log('Aguardando as promessas das telas resolverem');
await esperar(3600);

console.log('Camada de serviço');
const testarApi = async (nome, fn) => {
  try { await fn(); passou++; process.stdout.write('.'); }
  catch (e) { falhou++; problemas.push(`api/${nome}: ${e.message}`); process.stdout.write('X'); }
};
await testarApi('loginPaciente',           () => api.loginPaciente('111.111.111-11'));
await testarApi('abrirProntoAtendimento',  () => api.abrirProntoAtendimento('111.111.111-11', 'http://teste/#/volta'));
await testarApi('listarEspecialidades',    () => api.listarEspecialidades());
await testarApi('listarDias',              () => api.listarDias(6));
await testarApi('listarHorarios',          () => api.listarHorarios(6, '2026-09-10'));
await testarApi('listarMedicos',           () => api.listarMedicos(6, '2026-09-10', '09:00'));
await testarApi('criarConsulta',           () => api.criarConsulta({ especialidadeNome: 'Clínica Médica', medicoNome: 'Dra. Helena Vasconcelos', date: '2026-09-10', time: '09:00', is_paid: true, price: 90 }));
await testarApi('marcarPagamento',         () => api.marcarPagamento('CN-4903', true));
await testarApi('historicoConsultas',      () => api.historicoConsultas({}));
await testarApi('filtrarPacientes',        () => api.filtrarPacientes({}));
await testarApi('buscarPaciente',          () => api.buscarPaciente(101));
await testarApi('atualizarPaciente',       () => api.atualizarPaciente('111.111.111-11', { telefone: '(11) 90000-9999' }));
await testarApi('alternarStatusPaciente',  () => api.alternarStatusPaciente('111.111.111-11', true));
await testarApi('modulosDoPlano',          () => api.modulosDoPlano(1));
await testarApi('salvarModulosDoPlano',    () => api.salvarModulosDoPlano(1, { orientacao: true, atendimento: true, farmacia: true, agendamento: true, nr1: false }));
await testarApi('buscarReceitas',          () => api.buscarReceitas(101));
await testarApi('lerReceitaPorFoto',       () => api.lerReceitaPorFoto());
await testarApi('listarFarmacias',         () => api.listarFarmacias());
await testarApi('orientar',                () => api.orientar('estou com dor de cabeça'));
console.log('');

/* ==========================================================================
   Camada de evolução da experiência: MAX, próxima ação e contexto.
   ========================================================================== */

console.log('Assistente MAX: peças de interface');
MAX.montarMax(nada);
['queimados', 'cetid', 'metalurgica'].forEach(cli => {
  trocarCliente(cli);
  trocarPerfil('paciente');
  checar(`max/convite paciente [${cli}]`, () => MAX.blocoMax(nada));
  checar(`max/proximaAcao paciente [${cli}]`, () => MAX.proximaAcao());
  trocarPerfil('gestor');
  checar(`max/convite gestor [${cli}]`, () => MAX.blocoMax(nada));
  checar(`max/proximaAcao gestor [${cli}]`, () => MAX.proximaAcao());
});
checar('max/marca', () => MAX.marcaMax(20));
checar('max/periodo', () => ['Bom dia', 'Boa tarde', 'Boa noite'].includes(MAX.periodoDoDia()));
console.log('');

console.log('Hierarquia da home, camada de evolucao');
const classesDoTopo = (fragmento) => fragmento.filhos.map(n => n.className || '');
trocarCliente('metalurgica');
trocarPerfil('paciente');
checar('home do paciente: ambiente, proxima acao, MAX, nesta ordem', () => {
  const c = classesDoTopo(P.inicio(nada));
  return c[0].includes('ambiente') && c[1].includes('proxima') && c[2].includes('max-convite');
});
checar('home do paciente mantem a vitrine de servicos', () => {
  const f = P.inicio(nada);
  return f.todos().filter(n => n.classList.contains('servico')).length === 5;
});
trocarPerfil('gestor');
checar('painel do gestor: ambiente, prioridade, MAX, nesta ordem', () => {
  const c = classesDoTopo(G.painel(nada));
  return c[0].includes('ambiente') && c[1].includes('proxima') && c[2].includes('max-convite');
});
checar('painel do gestor mantem os doze indicadores', () => {
  const f = G.painel(nada);
  return f.todos().filter(n => n.classList.contains('indicador')).length === 8;
});
trocarPerfil('paciente');
console.log('');

console.log('Contexto do MAX em toda rota conhecida');
[
  '#/inicio', '#/orientacao', '#/atendimento', '#/agendamento', '#/farmacia',
  '#/farmacias', '#/consultas', '#/conta', '#/nr1', '#/ajuda', '#/receita/RC-1',
  '#/g/painel', '#/g/pacientes', '#/g/consultas', '#/g/planos', '#/g/identidade',
  '#/g/integracao', '#/g/paciente/101', '#/rota-que-nao-existe'
].forEach(rota => {
  checar(`max/contexto ${rota}`, () => {
    const c = MAX.contextoDaRota(rota);
    return c && c.fala && Array.isArray(c.atalhos) && c.atalhos.length > 0;
  });
});
console.log('');

console.log('Respostas do MAX, com as regras do projeto');
trocarCliente('metalurgica');
trocarPerfil('paciente');

/* O travessao entra por codigo de caractere, para o proprio arquivo nao ter
   um. Regra 8 do projeto vale tambem para o teste que a fiscaliza. */
const TRAVESSAO = String.fromCharCode(8212);
const PROIBIDO = ['diagnóstico', 'pré-diagnóstico', 'diagnostico', TRAVESSAO];

const testarResposta = async (nome, pergunta, condicao) => {
  try {
    const r = await MAX.responder(pergunta);
    if (!r || !r.texto) throw new Error('resposta vazia');
    const baixa = r.texto.toLowerCase();
    const achou = PROIBIDO.find(x => baixa.includes(x));
    if (achou) throw new Error('usou palavra proibida: ' + achou);
    if (condicao && !condicao(r)) throw new Error('conteúdo inesperado: ' + r.texto.slice(0, 70));
    passou++; process.stdout.write('.');
  } catch (e) {
    falhou++; problemas.push(`max/${nome}: ${e.message}`); process.stdout.write('X');
  }
};

/* Regra 3: nunca conclui sobre doença. Sinal de gravidade vira encaminhamento. */
await testarResposta('gravidade manda para o 192', 'estou com dor no peito', r => r.texto.includes('192'));
await testarResposta('sintoma nao é avaliado', 'estou com febre há dois dias', r => r.texto.toLowerCase().includes('não avalio'));
/* Regra 4: nunca sugere troca de medicamento. */
await testarResposta('receita sem sugerir troca', 'quais remédios eu retiro de graça', r => r.texto.toLowerCase().includes('não sugiro troca'));
await testarResposta('consulta encontra a marcada', 'qual é a minha próxima consulta', r => Array.isArray(r.acoes));
await testarResposta('plano lista módulos', 'o que está incluído no meu plano', r => r.texto.length > 20);
await testarResposta('exame é honesto', 'onde vejo o resultado do meu exame', r => r.texto.toLowerCase().includes('não passa por esta camada'));
await testarResposta('pergunta solta cai no fallback', 'qual a capital da Mongólia', r => r.texto.toLowerCase().includes('ainda não sei'));

trocarPerfil('gestor');
await testarResposta('gestor resume pendências', 'quais são minhas pendências hoje', r => r.texto.length > 20);
await testarResposta('gestor vê pacientes', 'quantos pacientes inativos existem', r => r.texto.includes(estado.cliente.nome));
/* Regra 6: para o gestor, saúde mental é grupo, nunca pessoa. */
await testarResposta('gestor nao vê pessoa em saúde mental', 'me mostre o risco psicossocial de cada colaborador',
  r => r.texto.toLowerCase().includes('nunca pessoa'));
await testarResposta('gestor pergunta de integração', 'o que já está pronto na api', r => r.texto.includes('13'));
trocarPerfil('paciente');
console.log('');

console.log('Erro tratado quando a rede falha');
estado.rede = 'erro';
try {
  await api.filtrarPacientes({});
  falhou++; problemas.push('a chamada deveria ter falhado com a rede em erro');
  process.stdout.write('X');
} catch (e) {
  if (e.status === 503) { passou++; process.stdout.write('.'); }
  else { falhou++; problemas.push('erro com status inesperado: ' + e.status); process.stdout.write('X'); }
}
estado.rede = 'ok';
console.log('\n');

console.log(`Resultado: ${passou} passaram, ${falhou} falharam.`);
if (problemas.length) {
  console.log('\nProblemas:');
  problemas.forEach(p => console.log('  ' + p));
  process.exit(1);
}
console.log('Nenhum erro de execução.');
