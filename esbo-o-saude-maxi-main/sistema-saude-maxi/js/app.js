/* ==========================================================================
   Casca da aplicação: entrada, navegação, rotas e barra de demonstração.
   ========================================================================== */

import * as D from './dados.js';
import {
  estado, carregar, salvar, zerar, aoMudar, avisar,
  trocarCliente, trocarPlano, trocarPerfil, planosDoCliente, planoAtual, moduloLiberado
} from './estado.js';
import { el, frag, limpar, ico, aviso, campo, marcarErro, limparErros, ocupado, iniciais, aurora } from './ui.js';
import * as P from './telas-paciente.js';
import * as G from './telas-gestor.js';
import {
  montarMax, contextualizarMax, visibilidadeMax, abrirMax, marcaMax,
  MAX_NOME, periodoDoDia, primeiroNome
} from './max.js';

const raiz = document.getElementById('raiz');

/* ==========================================================================
   Mapa de rotas
   ========================================================================== */

const ROTAS = {
  '#/inicio':      { perfil: 'paciente', titulo: 'Início',                 tela: (ir) => P.inicio(ir) },
  '#/orientacao':  { perfil: 'paciente', titulo: 'Orientação em saúde',    tela: (ir) => P.orientacao(ir),  modulo: 'orientacao' },
  '#/atendimento': { perfil: 'paciente', titulo: 'Atendimento',            tela: (ir) => P.atendimento(ir), modulo: 'atendimento' },
  '#/agendamento': { perfil: 'paciente', titulo: 'Agendar consulta',       tela: (ir) => P.agendamento(ir), modulo: 'agendamento' },
  '#/farmacia':    { perfil: 'paciente', titulo: 'Farmácia popular',       tela: (ir) => P.farmacia(ir),    modulo: 'farmacia' },
  '#/farmacias':   { perfil: 'paciente', titulo: 'Farmácias próximas',     tela: (ir) => P.farmacias(ir),   modulo: 'farmacia' },
  '#/consultas':   { perfil: 'paciente', titulo: 'Minhas consultas',       tela: (ir) => P.consultas(ir) },
  '#/conta':       { perfil: 'paciente', titulo: 'Minha conta',            tela: (ir) => P.conta(ir) },
  '#/nr1':         { perfil: 'paciente', titulo: 'Saúde mental',           tela: (ir) => P.nr1(ir) },
  '#/ajuda':       { perfil: 'paciente', titulo: 'Ajuda imediata',         tela: (ir) => P.ajuda(ir) },

  '#/g/painel':    { perfil: 'gestor',   titulo: 'Painel',                 tela: (ir) => G.painel(ir) },
  '#/g/pacientes': { perfil: 'gestor',   titulo: 'Pacientes',              tela: (ir) => G.pacientes(ir) },
  '#/g/consultas': { perfil: 'gestor',   titulo: 'Consultas',              tela: (ir) => G.consultasGestor(ir) },
  '#/g/planos':    { perfil: 'gestor',   titulo: 'Planos e módulos',       tela: (ir) => G.planos(ir) },
  '#/g/identidade':{ perfil: 'gestor',   titulo: 'Identidade visual',      tela: (ir) => G.identidade(ir) },
  '#/g/integracao':{ perfil: 'gestor',   titulo: 'Integração',             tela: (ir) => G.integracao(ir) }
};

const MENU_PACIENTE = [
  { grupo: 'Atendimento', itens: [
    { rota: '#/inicio',      icone: 'casa',       rotulo: 'Início' },
    { rota: '#/orientacao',  icone: 'conversa',   rotulo: 'Orientação em saúde', modulo: 'orientacao' },
    { rota: '#/atendimento', icone: 'video',      rotulo: 'Falar com médico',    modulo: 'atendimento' },
    { rota: '#/agendamento', icone: 'calendario', rotulo: 'Agendar consulta',    modulo: 'agendamento' }
  ]},
  { grupo: 'Meus cuidados', itens: [
    { rota: '#/farmacia',  icone: 'remedio', rotulo: 'Farmácia popular', modulo: 'farmacia' },
    { rota: '#/consultas', icone: 'lista',   rotulo: 'Minhas consultas' },
    { rota: '#/nr1',       icone: 'mente',   rotulo: 'Saúde mental',     modulo: 'nr1' }
  ]},
  { grupo: 'Conta', itens: [
    { rota: '#/conta', icone: 'pessoa', rotulo: 'Minha conta' },
    { rota: '#/ajuda', icone: 'alerta', rotulo: 'Ajuda imediata' }
  ]}
];

const MENU_GESTOR = [
  { grupo: 'Operação', itens: [
    { rota: '#/g/painel',    icone: 'painel', rotulo: 'Painel' },
    { rota: '#/g/pacientes', icone: 'grupo',  rotulo: 'Pacientes' },
    { rota: '#/g/consultas', icone: 'lista',  rotulo: 'Consultas' }
  ]},
  { grupo: 'Configuração', itens: [
    { rota: '#/g/planos',     icone: 'escudo',     rotulo: 'Planos e módulos' },
    { rota: '#/g/identidade', icone: 'engrenagem', rotulo: 'Identidade visual' },
    { rota: '#/g/integracao', icone: 'plugue',     rotulo: 'Integração' }
  ]}
];

const inicioDoPerfil = () => (estado.perfil === 'gestor' ? '#/g/painel' : '#/inicio');
const ir = (rota) => { location.hash = rota; };

/* ==========================================================================
   Tela de entrada

   CAMADA DE EVOLUÇÃO: a entrada deixou de ser formulário e virou ambiente.
   Fundo com movimento quase imperceptível, elementos surgindo em ordem, e
   um convite curto no lugar do título burocrático. O campo continua sendo
   um só para e-mail ou CPF, igual ao sistema oficial. Nada foi removido:
   esqueci a senha, primeiro acesso e a nota de privacidade seguem aqui.
   ========================================================================== */

function entrada() {
  const dados = { login: estado.pacienteAtual ? estado.pacienteAtual.cpf : '', senha: 'demonstracao' };
  let caixa;

  const acessar = async (ev) => {
    ev.preventDefault();
    limparErros(caixa);
    if (!dados.login.trim()) return marcarErro(caixa, 'ac-login', 'Informe seu e-mail ou CPF.');
    if (!dados.senha.trim()) return marcarErro(caixa, 'ac-senha', 'Informe sua senha.');
    const b = caixa.querySelector('button[type="submit"]');
    ocupado(b, true, 'Entrando');
    setTimeout(() => {
      estado.autenticado = true;
      salvar();
      ir(inicioDoPerfil());
      /* O ambiente é montado primeiro, atrás da camada. Quando ela sai, a
         tela já está pronta: ninguém espera a animação para poder usar. */
      desenhar();
      boasVindas();
    }, 700);
  };

  caixa = el('form', { class: 'entrada-caixa', onsubmit: acessar }, [
    el('p', { class: 'sobretitulo', texto: 'Saúde Maxi' }),
    el('div', {}, [
      el('h1', { class: 'entrada-titulo', texto: 'Olá. Vamos começar?' }),
      el('p', { class: 'entrada-convite', texto: 'Uma credencial só. Entre e seu ambiente monta sozinho.' })
    ]),
    el('div', { class: 'mt5' }, [
      campo({ id: 'ac-login', rotulo: 'E-mail ou CPF', valor: dados.login, placeholder: 'seu e-mail ou CPF', aoMudar: v => dados.login = v }),
      campo({ id: 'ac-senha', rotulo: 'Senha ou CPF', tipo: 'password', valor: dados.senha, aoMudar: v => dados.senha = v })
    ]),
    el('button', { class: 'btn btn-primario btn-cheio', type: 'submit' }, ['Entrar']),
    el('div', { class: 'linha mt4' }, [
      el('button', { class: 'btn btn-fantasma', type: 'button', onclick: () => aviso('Recuperação de senha acontece na plataforma existente.') }, ['Esqueci a senha']),
      el('button', { class: 'btn btn-fantasma', type: 'button', onclick: () => aviso('Primeiro acesso pede dados, endereço, idioma e consentimento.') }, ['Primeiro acesso'])
    ]),
    el('p', { class: 'pequeno mt4', texto: 'O sistema oficial aceita e-mail ou CPF no mesmo campo, e este esboço reproduz isso. A mesma credencial vale para a camada nova e para a plataforma de atendimento que já está em produção.' })
  ]);

  return el('div', { class: 'palco-entrada' }, [
    el('div', { class: 'entrada-arte' }, [
      aurora(),
      el('div', { class: 'linha', style: 'gap:12px' }, [
        el('span', { class: 'medalhao', style: 'background:rgba(255,255,255,.2)', texto: iniciais(estado.cliente.nome) }),
        el('span', {}, [
          el('p', { class: 'negrito', texto: estado.cliente.nome }),
          el('p', { style: 'font-size:12px;opacity:.85', texto: estado.cliente.subdominio })
        ])
      ]),
      el('h1', { texto: estado.cliente.saudacao }),
      el('div', { class: 'entrada-destaque' }, [
        el('div', { class: 'linha', style: 'gap:12px;flex-wrap:nowrap' }, [
          el('span', { style: 'width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.18);display:grid;place-items:center;flex:none' }, [ico('video', 20)]),
          el('span', {}, [
            el('p', { class: 'negrito', texto: 'Falar com um médico agora' }),
            el('p', { style: 'font-size:13px;opacity:.9', texto: 'Atendimento por vídeo em minutos, 24 horas por dia.' })
          ])
        ])
      ]),
      el('span', { class: 'entrada-selo' }, [marcaMax(16), `${MAX_NOME} acompanha você lá dentro`]),
      el('p', { style: 'font-size:12px;opacity:.8', texto: 'Esboço funcional. Nenhum dado real de paciente. Tudo que você salvar fica apenas no seu navegador.' })
    ]),
    el('div', { class: 'entrada-form' }, [aurora(), caixa])
  ]);
}

/* ==========================================================================
   Boas-vindas

   Ponte de 1,3 segundo entre autenticar e chegar. Não é tela obrigatória:
   sai sozinha, e um clique em qualquer ponto pula na hora. Se o navegador
   pedir menos movimento, o CSS já zera a animação e ela só pisca.
   ========================================================================== */

function boasVindas(aoTerminar) {
  const nome = estado.perfil === 'gestor'
    ? 'Danilo'
    : primeiroNome(estado.pacienteAtual ? estado.pacienteAtual.nome : '');

  const ns = 'http://www.w3.org/2000/svg';
  const risco = document.createElementNS(ns, 'svg');
  risco.setAttribute('viewBox', '0 0 24 24');
  risco.setAttribute('class', 'bv-check');
  risco.setAttribute('fill', 'none');
  risco.setAttribute('aria-hidden', 'true');
  const traco = document.createElementNS(ns, 'path');
  traco.setAttribute('d', 'M4.6 12.6l4.8 4.8L19.4 7.2');
  traco.setAttribute('stroke', 'currentColor');
  traco.setAttribute('stroke-width', '2.4');
  traco.setAttribute('stroke-linecap', 'round');
  traco.setAttribute('stroke-linejoin', 'round');
  risco.appendChild(traco);

  const camada = el('div', {
    class: 'boas-vindas', id: 'boas-vindas', role: 'status', 'aria-live': 'polite'
  }, [
    aurora(),
    el('div', { class: 'bv-interno' }, [
      risco,
      el('p', { class: 'bv-marca', texto: 'Saúde Maxi' }),
      el('h1', { class: 'bv-ola', texto: `${periodoDoDia()}${nome ? ', ' + nome : ''}.` }),
      el('p', { class: 'bv-nota', texto: 'Seu ambiente está pronto.' })
    ])
  ]);

  let saiu = false;
  const sair = () => {
    if (saiu) return;
    saiu = true;
    camada.classList.add('saindo');
    setTimeout(() => camada.remove(), 260);
    if (aoTerminar) aoTerminar();
  };

  camada.addEventListener('click', sair);
  document.body.appendChild(camada);
  setTimeout(sair, 1300);
}

/* ==========================================================================
   Casca autenticada
   ========================================================================== */

function casca(rotaAtual, conteudo, titulo) {
  const grupos = estado.perfil === 'gestor' ? MENU_GESTOR : MENU_PACIENTE;
  const p = estado.pacienteAtual;

  const lateral = el('aside', { class: 'lateral', id: 'lateral' }, [
    el('div', { class: 'lateral-topo' }, [
      el('div', { class: 'identidade' }, [
        el('span', { class: 'medalhao', texto: iniciais(estado.cliente.nome) }),
        el('span', {}, [
          el('p', { class: 'identidade-nome', texto: estado.cliente.nome }),
          el('p', { class: 'identidade-meta', texto: estado.cliente.subdominio })
        ])
      ])
    ]),
    el('nav', { class: 'menu' }, grupos.map(g => el('div', { class: 'menu-grupo' }, [
      el('p', { class: 'menu-titulo', texto: g.grupo }),
      ...g.itens.map(i => {
        const bloqueado = i.modulo && !moduloLiberado(i.modulo);
        return el('button', {
          class: 'menu-item' + (rotaAtual === i.rota ? ' ativo' : ''),
          disabled: bloqueado && i.modulo !== 'nr1' ? true : null,
          title: bloqueado ? 'Não incluído no plano atual' : '',
          onclick: () => { ir(i.rota); fecharMenu(); }
        }, [
          ico(i.icone),
          el('span', { texto: i.rotulo }),
          bloqueado ? el('span', { class: 'selo-bloqueio' }, [ico('escudo', 13)]) : null
        ]);
      })
    ]))),
    el('div', { class: 'lateral-rodape' }, [
      el('button', {
        class: 'menu-item',
        onclick: () => { estado.autenticado = false; salvar(); desenhar(); }
      }, [ico('volta'), el('span', { texto: 'Sair' })])
    ])
  ]);

  const topo = el('header', { class: 'topo' }, [
    el('button', { class: 'btn-menu', 'aria-label': 'Abrir menu', onclick: abrirMenu }, [ico('cardapio', 20)]),
    el('span', { class: 'topo-titulo', texto: titulo }),
    el('span', { class: 'topo-dir' }, [
      estado.perfil === 'gestor'
        ? el('span', { class: 'selo-papel', texto: 'Gestor da clínica' })
        : el('span', { class: 'selo-papel', texto: planoAtual() ? planoAtual().nome : 'Sem plano' }),
      el('span', { class: 'avatar', texto: iniciais(estado.perfil === 'gestor' ? 'Danilo Melo' : (p ? p.nome : 'Paciente')) })
    ])
  ]);

  return el('div', { class: 'casca' }, [
    lateral,
    el('main', { class: 'principal' }, [topo, el('div', { class: 'conteudo' }, [conteudo])])
  ]);
}

function abrirMenu() {
  const l = document.getElementById('lateral');
  if (!l) return;
  l.classList.add('aberta');
  const veu = el('div', { class: 'veu-menu', id: 'veu-menu', onclick: fecharMenu });
  document.body.appendChild(veu);
}
function fecharMenu() {
  const l = document.getElementById('lateral');
  if (l) l.classList.remove('aberta');
  const v = document.getElementById('veu-menu');
  if (v) v.remove();
}

/* ==========================================================================
   Desenho
   ========================================================================== */

function desenhar() {
  limpar(raiz);
  fecharMenu();

  if (!estado.autenticado) {
    visibilidadeMax(false);
    raiz.appendChild(entrada());
    return;
  }

  let rota = location.hash || inicioDoPerfil();

  /* CAMADA DE EVOLUÇÃO: o assistente acompanha a navegação. A cada rota ele
     troca a fala de abertura e os atalhos, e é isso que dá a sensação de
     inteligência contextual. */
  visibilidadeMax(true);
  contextualizarMax(rota);

  /* Rotas com parâmetro */
  if (rota.startsWith('#/receita/')) {
    raiz.appendChild(casca('#/farmacia', P.receita(ir, rota.split('/')[2]), 'Resultado da receita'));
    return;
  }
  if (rota.startsWith('#/g/paciente/')) {
    raiz.appendChild(casca('#/g/pacientes', G.pacienteDetalhe(ir, rota.split('/')[3]), 'Ficha do paciente'));
    return;
  }
  if (rota.startsWith('#/atendimento/volta')) {
    rota = '#/atendimento';
    location.hash = rota;
  }

  const alvo = ROTAS[rota];

  /* Rota desconhecida */
  if (!alvo) {
    raiz.appendChild(casca(null, el('div', { class: 'cartao' }, [
      el('div', { class: 'estado' }, [
        el('div', { class: 'icone' }, [ico('busca', 24)]),
        el('h3', { texto: 'Página não encontrada' }),
        el('p', { texto: 'O endereço ' + rota + ' não existe neste esboço.' }),
        el('button', { class: 'btn btn-primario', onclick: () => ir(inicioDoPerfil()) }, ['Voltar ao início'])
      ])
    ]), 'Não encontrado'));
    return;
  }

  /* Perfil errado para a rota */
  if (alvo.perfil !== estado.perfil) {
    ir(inicioDoPerfil());
    return;
  }

  /* Módulo não contratado */
  if (alvo.modulo && !moduloLiberado(alvo.modulo)) {
    const m = D.MODULOS.find(x => x.chave === alvo.modulo);
    raiz.appendChild(casca(rota, el('div', { class: 'cartao' }, [
      el('div', { class: 'estado' }, [
        el('div', { class: 'icone' }, [ico('escudo', 24)]),
        el('h3', { texto: 'Não incluído no seu plano' }),
        el('p', { texto: `O módulo ${m ? m.nome : ''} não está liberado no plano ${planoAtual() ? planoAtual().nome : 'atual'}. O gestor pode liberar em Planos e módulos, e a mudança vale na próxima entrada, sem nova publicação.` }),
        el('button', { class: 'btn btn-primario', onclick: () => ir(inicioDoPerfil()) }, ['Voltar ao início'])
      ])
    ]), alvo.titulo));
    return;
  }

  raiz.appendChild(casca(rota, alvo.tela(ir), alvo.titulo));
}

/* ==========================================================================
   Barra de demonstração
   ========================================================================== */

function preencherPlanos() {
  const sel = document.getElementById('sel-plano');
  limpar(sel);
  planosDoCliente().forEach(p => {
    sel.appendChild(el('option', { value: p.id, selected: p.id === estado.planoId }, [p.nome]));
  });
}

function ligarBarra() {
  const selPerfil = document.getElementById('sel-perfil');
  const selCliente = document.getElementById('sel-cliente');
  const selPlano = document.getElementById('sel-plano');
  const selRede = document.getElementById('sel-rede');

  selPerfil.value = estado.perfil;
  selCliente.value = estado.cliente.id;
  preencherPlanos();

  selPerfil.addEventListener('change', (e) => {
    trocarPerfil(e.target.value);
    ir(inicioDoPerfil());
    desenhar();
  });

  selCliente.addEventListener('change', (e) => {
    trocarCliente(e.target.value);
    preencherPlanos();
    ir(inicioDoPerfil());
    desenhar();
  });

  selPlano.addEventListener('change', (e) => {
    trocarPlano(e.target.value);
    desenhar();
  });

  selRede.addEventListener('change', (e) => {
    estado.rede = e.target.value;
    const rotulos = {
      ok: 'Rede normal.',
      lenta: 'Rede lenta. As telas mostram o estado de carregamento por mais tempo.',
      erro: 'Rede com falha. As chamadas vão falhar, para você ver o tratamento de erro.',
      vazio: 'Sem dados. As listas voltam vazias, para você ver o estado vazio.'
    };
    aviso(rotulos[e.target.value]);
    desenhar();
  });

  document.getElementById('btn-reset').addEventListener('click', async () => {
    zerar();
    preencherPlanos();
    selCliente.value = estado.cliente.id;
    selPerfil.value = estado.perfil;
    aviso('Dados de demonstração zerados.', 'ok');
    ir(inicioDoPerfil());
    desenhar();
  });

  const barra = document.getElementById('barra-demo');
  const reabrir = document.getElementById('btn-abrir-demo');
  document.getElementById('btn-fechar-demo').addEventListener('click', () => {
    barra.hidden = true;
    reabrir.hidden = false;
  });
  reabrir.addEventListener('click', () => {
    barra.hidden = false;
    reabrir.hidden = true;
  });
}

/* ==========================================================================
   Partida
   ========================================================================== */

carregar();
ligarBarra();
montarMax(ir);
aoMudar(() => { preencherPlanos(); });
window.addEventListener('hashchange', desenhar);
if (!location.hash) location.hash = inicioDoPerfil();
desenhar();
