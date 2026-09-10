/* ==========================================================================
   Peças de interface reutilizáveis.
   Tudo que aparece mais de uma vez no sistema mora aqui.
   ========================================================================== */

/* ---------- construção ---------- */

export function el(tag, props = {}, filhos = []) {
  const n = document.createElement(tag);
  for (const [k, v] of Object.entries(props)) {
    if (v === null || v === undefined || v === false) continue;
    if (k === 'class') n.className = v;
    else if (k === 'html') n.innerHTML = v;
    else if (k === 'texto') n.textContent = v;
    else if (k.startsWith('on') && typeof v === 'function') n.addEventListener(k.slice(2), v);
    else if (k === 'dataset') Object.assign(n.dataset, v);
    else if (v === true) n.setAttribute(k, '');
    else n.setAttribute(k, v);
  }
  (Array.isArray(filhos) ? filhos : [filhos]).forEach(f => {
    if (f === null || f === undefined || f === false) return;
    n.appendChild(typeof f === 'string' ? document.createTextNode(f) : f);
  });
  return n;
}

export const frag = (filhos) => {
  const f = document.createDocumentFragment();
  filhos.filter(Boolean).forEach(c => f.appendChild(c));
  return f;
};

export function limpar(no) { while (no.firstChild) no.removeChild(no.firstChild); return no; }

/* ---------- ícones, traçado único, sem dependência externa ---------- */

const CAMINHOS = {
  casa: 'M3.6 10.4 12 3.6l8.4 6.8V20a1 1 0 0 1-1 1h-4.6v-6h-5.6v6H4.6a1 1 0 0 1-1-1z',
  conversa: 'M20.5 11.8a7.7 7.7 0 0 1-7.7 7.7H8.4L4 22.2v-5.6a7.7 7.7 0 0 1 6.6-11.1h2.2a7.7 7.7 0 0 1 7.7 6.3z',
  video: 'M2.8 6.2h12.6v11.6H2.8zM15.4 10.6l5.8-3v8.8l-5.8-3',
  calendario: 'M3.2 5.2h17.6v15.6H3.2zM3.2 10h17.6M8.2 3.2v4M15.8 3.2v4',
  remedio: 'M2.6 8.6h18.8v6.8H2.6zM9.1 6.9l6 6',
  mente: 'M15.6 20.8v-2.4a5.9 5.9 0 0 0 3.6-5.4c0-.7.3-1.2.8-1.7.4-.4.5-1 .2-1.5l-1.4-2.4A7.6 7.6 0 0 0 4.6 9.6a7.4 7.4 0 0 0 2.6 5.6v5.6',
  pessoa: 'M12 4.8a3.6 3.6 0 1 1 0 7.2 3.6 3.6 0 0 1 0-7.2M5 20.4c0-3.4 3.1-5.6 7-5.6s7 2.2 7 5.6',
  grupo: 'M9 4.8a3.2 3.2 0 1 1 0 6.4 3.2 3.2 0 0 1 0-6.4M3 20c0-3.1 2.7-5 6-5s6 1.9 6 5M16.4 11.4a2.8 2.8 0 1 0 0-5.6M18 20c0-2.2-.7-3.6-1.9-4.4',
  painel: 'M3.4 3.6h7v7h-7zM13.6 3.6h7v4.2h-7zM13.6 10.6h7v9.8h-7zM3.4 13.6h7v6.8h-7z',
  engrenagem: 'M12 9.2a2.8 2.8 0 1 1 0 5.6 2.8 2.8 0 0 1 0-5.6M19.5 12c0-.5 0-1-.1-1.4l2-1.5-2-3.4-2.3 1a7.6 7.6 0 0 0-2.4-1.4L14.4 3H9.6l-.3 2.3a7.6 7.6 0 0 0-2.4 1.4l-2.3-1-2 3.4 2 1.5a8 8 0 0 0 0 2.8l-2 1.5 2 3.4 2.3-1c.7.6 1.5 1.1 2.4 1.4l.3 2.3h4.8l.3-2.3c.9-.3 1.7-.8 2.4-1.4l2.3 1 2-3.4-2-1.5c.1-.4.1-.9.1-1.4z',
  plugue: 'M8.4 3.2v5.2M15.6 3.2v5.2M6 8.4h12v3.2a6 6 0 0 1-6 6 6 6 0 0 1-6-6zM12 17.6v3.2',
  lista: 'M8 6.4h12M8 12h12M8 17.6h12M4 6.4h.01M4 12h.01M4 17.6h.01',
  alerta: 'M12 3.4 21.4 20H2.6zM12 9.6v4.4M12 16.8h.01',
  ok: 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18M8.2 12.2l2.6 2.6 5-5.2',
  x: 'M6 6l12 12M18 6 6 18',
  volta: 'M19.2 12H4.8M10.4 5.6 4.8 12l5.6 6.4',
  avanca: 'M4.8 12h14.4M13.6 6.4l5.6 5.6-5.6 5.6',
  local: 'M12 21.2s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11zM12 10.2a2.6 2.6 0 1 1 0-5.2 2.6 2.6 0 0 1 0 5.2',
  relogio: 'M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18M12 6.8V12l3.4 2',
  documento: 'M6 2.8h8.4L19 7.4v13.8H6zM14.2 2.8v4.8H19M9 12.4h7M9 16h4.6',
  camera: 'M3.4 8.8h2.9l1.5-2.2h8.4l1.5 2.2h2.9v11H3.4zM12 17.4a3.4 3.4 0 1 1 0-6.8 3.4 3.4 0 0 1 0 6.8',
  busca: 'M11 4.4a6.6 6.6 0 1 1 0 13.2 6.6 6.6 0 0 1 0-13.2M20 20l-4.3-4.3',
  filtro: 'M3.6 5.2h16.8l-6.6 7.6v6.4l-3.6-2v-4.4z',
  escudo: 'M12 2.8l7.6 3v5.2c0 4.6-3.1 8.6-7.6 10.2C7.5 20.6 4.4 16.6 4.4 12V5.8z',
  cardapio: 'M4 7h16M4 12h16M4 17h16',
  vazio: 'M4.6 7.4h14.8v12.2H4.6zM4.6 11.6h14.8M9 4.4v3M15 4.4v3'
};

export function ico(nome, tam = 18) {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('width', tam);
  svg.setAttribute('height', tam);
  svg.setAttribute('fill', 'none');
  svg.setAttribute('stroke', 'currentColor');
  svg.setAttribute('stroke-width', '1.75');
  svg.setAttribute('stroke-linecap', 'round');
  svg.setAttribute('stroke-linejoin', 'round');
  svg.setAttribute('aria-hidden', 'true');
  svg.classList.add('ic');
  const p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  p.setAttribute('d', CAMINHOS[nome] || CAMINHOS.lista);
  svg.appendChild(p);
  return svg;
}

/* ---------- profundidade ----------
   Fundo com movimento quase imperceptível. Fica atrás do conteúdo, não
   recebe clique e não entra em cima de texto. Puro enfeite estrutural, por
   isso sai da árvore de acessibilidade. */

export function aurora() {
  return el('div', { class: 'aurora', 'aria-hidden': 'true' }, [el('i'), el('i'), el('i')]);
}

/* ---------- estados ---------- */

export function carregando(texto = 'Carregando') {
  return el('div', { class: 'cartao' }, [
    el('div', { class: 'esqueleto esq-linha', style: 'width:38%' }),
    el('div', { class: 'esqueleto esq-linha', style: 'width:72%' }),
    el('div', { class: 'esqueleto esq-linha', style: 'width:56%' }),
    el('div', { class: 'esqueleto esq-cartao mt4' }),
    el('p', { class: 'pequeno mt3', texto: texto + '...' })
  ]);
}

export function vazio({ titulo, texto, acao }) {
  return el('div', { class: 'cartao' }, [
    el('div', { class: 'estado' }, [
      el('div', { class: 'icone' }, [ico('vazio', 24)]),
      el('h3', { texto: titulo }),
      el('p', { texto }),
      acao || null
    ])
  ]);
}

export function erro({ titulo = 'Não foi possível carregar', texto, aoTentar }) {
  return el('div', { class: 'cartao' }, [
    el('div', { class: 'estado' }, [
      el('div', { class: 'icone', style: 'background:var(--erro-fraco);color:var(--erro)' }, [ico('alerta', 24)]),
      el('h3', { texto: titulo }),
      el('p', { texto: texto || 'A plataforma de atendimento não respondeu. Nenhum dado foi perdido.' }),
      aoTentar ? el('button', { class: 'btn btn-secundario', onclick: aoTentar }, ['Tentar de novo']) : null
    ])
  ]);
}

export function sucesso(titulo, texto, acoes) {
  return el('div', { class: 'cartao' }, [
    el('div', { class: 'estado' }, [
      el('div', { class: 'icone', style: 'background:var(--ok-fraco);color:var(--ok)' }, [ico('ok', 24)]),
      el('h3', { texto: titulo }),
      el('p', { texto }),
      acoes ? el('div', { class: 'linha', style: 'justify-content:center' }, acoes) : null
    ])
  ]);
}

/* ---------- avisos e modais ---------- */

export function aviso(texto, tipo = '') {
  const caixa = document.getElementById('avisos');
  const n = el('div', { class: 'aviso ' + tipo, texto });
  caixa.appendChild(n);
  setTimeout(() => {
    n.style.transition = 'opacity .25s, transform .25s';
    n.style.opacity = '0';
    n.style.transform = 'translateY(6px)';
    setTimeout(() => n.remove(), 250);
  }, 3600);
}

export function modal({ titulo, corpo, acoes, aoFechar }) {
  const fundo = document.getElementById('modal-fundo');
  const caixa = document.getElementById('modal');
  limpar(caixa);

  const fechar = () => {
    fundo.hidden = true;
    limpar(caixa);
    document.removeEventListener('keydown', porEsc);
    if (aoFechar) aoFechar();
  };
  const porEsc = (ev) => { if (ev.key === 'Escape') fechar(); };

  caixa.appendChild(el('div', { class: 'modal-cabecalho' }, [
    el('h2', { id: 'modal-titulo', texto: titulo }),
    el('button', { class: 'btn btn-fantasma', 'aria-label': 'Fechar', onclick: fechar }, [ico('x')])
  ]));
  caixa.appendChild(el('div', {}, [corpo]));
  if (acoes) caixa.appendChild(el('div', { class: 'modal-rodape' }, acoes(fechar)));

  fundo.hidden = false;
  fundo.onclick = (ev) => { if (ev.target === fundo) fechar(); };
  document.addEventListener('keydown', porEsc);
  const focavel = caixa.querySelector('input, select, textarea, button');
  if (focavel) focavel.focus();
  return fechar;
}

export function confirmar({ titulo, texto, rotuloOk = 'Confirmar', perigoso = false }) {
  return new Promise((resolver) => {
    let decidido = false;
    modal({
      titulo,
      corpo: el('p', { class: 'cartao-apoio', texto }),
      acoes: (fechar) => [
        el('button', { class: 'btn btn-secundario', onclick: () => { decidido = true; fechar(); resolver(false); } }, ['Cancelar']),
        el('button', { class: 'btn ' + (perigoso ? 'btn-perigo' : 'btn-primario'), onclick: () => { decidido = true; fechar(); resolver(true); } }, [rotuloOk])
      ],
      aoFechar: () => { if (!decidido) resolver(false); }
    });
  });
}

/* ---------- peças ---------- */

export function origem(tipo) {
  const mapa = {
    oficial:  ['o-oficial',  'Oficial'],
    reuniao:  ['o-reuniao',  'Reunião'],
    esboco:   ['o-esboco',   'Esboço'],
    melhoria: ['o-melhoria', 'Melhoria'],
    novo:     ['o-novo',     'Novo']
  };
  const [cls, rot] = mapa[tipo] || mapa.novo;
  return el('span', { class: 'origem ' + cls, title: 'Origem deste bloco', texto: rot });
}

export function cabecalho(titulo, apoio, origens = [], acoes = null) {
  return el('div', { class: 'cabecalho-pagina' }, [
    el('div', { class: 'linha' }, [
      el('h1', { texto: titulo }),
      el('span', { class: 'espaco' }),
      ...(acoes || [])
    ]),
    apoio ? el('p', { texto: apoio }) : null,
    origens.length ? el('div', { class: 'linha mt3' }, origens.map(o => origem(o))) : null
  ]);
}

/* ==========================================================================
   Peças da camada de evolução da experiência, 3 de setembro de 2026.
   Servem aos dois perfis, por isso moram aqui e não numa tela específica.
   ========================================================================== */

/* Ambiente: quem sou e como estou, num bloco só, antes de qualquer serviço. */
export function ambiente(saudacao, linha, tiras) {
  return el('section', { class: 'ambiente' }, [
    aurora(),
    el('h1', { class: 'ambiente-saudacao', texto: saudacao }),
    el('p', { class: 'ambiente-linha', texto: linha }),
    tiras && tiras.length
      ? el('div', { class: 'ambiente-tiras' }, tiras.map(t =>
          el('span', { class: 'tira' }, [ico(t.icone, 15), el('b', { texto: String(t.valor) }), t.rotulo])))
      : null
  ]);
}

/* Próxima ação: uma só, a mais urgente. Nunca vira lista. Quando não há
   nada pendente, o cartão diz isso em vez de sumir, porque sumir deixa a
   pessoa sem saber se o sistema olhou. */
export function proximaAcaoCartao(acao, ir, rotulo = 'Sua próxima ação') {
  if (!acao) return null;
  const marca = { agora: 'alerta', atencao: 'relogio', calma: 'ok' }[acao.urgencia] || 'ok';
  return el('section', { class: 'proxima ' + acao.urgencia }, [
    el('span', { class: 'marcador' }, [ico(marca, 21)]),
    el('span', { class: 'corpo' }, [
      el('p', { class: 'rot', texto: rotulo }),
      el('p', { class: 'tit', texto: acao.titulo }),
      el('p', { class: 'sub', texto: acao.texto })
    ]),
    el('button', {
      class: 'btn ' + (acao.urgencia === 'calma' ? 'btn-secundario' : 'btn-primario'),
      onclick: () => ir(acao.rota)
    }, [acao.rotulo])
  ]);
}

export function indicador(rot, val, nota) {
  return el('div', { class: 'indicador' }, [
    el('p', { class: 'rot', texto: rot }),
    el('p', { class: 'val', texto: String(val) }),
    nota ? el('p', { class: 'nota', texto: nota }) : null
  ]);
}

export function campo({ id, rotulo, tipo = 'text', valor = '', dica, opcoes, obrigatorio, aoMudar, placeholder }) {
  let entrada;
  if (opcoes) {
    entrada = el('select', { id, onchange: (e) => aoMudar && aoMudar(e.target.value) },
      opcoes.map(o => el('option', { value: o.valor, selected: String(o.valor) === String(valor) }, [o.rotulo])));
  } else if (tipo === 'textarea') {
    entrada = el('textarea', { id, placeholder: placeholder || '', oninput: (e) => aoMudar && aoMudar(e.target.value) }, [valor || '']);
  } else {
    entrada = el('input', { id, type: tipo, value: valor || '', placeholder: placeholder || '', oninput: (e) => aoMudar && aoMudar(e.target.value) });
  }
  return el('div', { class: 'campo', dataset: { campo: id } }, [
    el('label', { for: id, texto: rotulo + (obrigatorio ? ' *' : '') }),
    entrada,
    dica ? el('p', { class: 'dica', texto: dica }) : null
  ]);
}

export function marcarErro(raiz, idCampo, mensagem) {
  const bloco = raiz.querySelector(`[data-campo="${idCampo}"]`);
  if (!bloco) return;
  bloco.classList.add('invalido');
  if (!bloco.querySelector('.erro-campo')) bloco.appendChild(el('p', { class: 'erro-campo', texto: mensagem }));
}

export function limparErros(raiz) {
  raiz.querySelectorAll('.campo.invalido').forEach(c => {
    c.classList.remove('invalido');
    const e = c.querySelector('.erro-campo');
    if (e) e.remove();
  });
}

export function ocupado(botao, ocupadoAgora, rotulo) {
  if (ocupadoAgora) {
    botao.dataset.rotulo = botao.textContent;
    botao.disabled = true;
    limpar(botao);
    botao.appendChild(el('span', { class: 'girando' }));
    botao.appendChild(document.createTextNode(' ' + (rotulo || 'Aguarde')));
  } else {
    botao.disabled = false;
    botao.textContent = botao.dataset.rotulo || rotulo || 'Pronto';
  }
}

/* ---------- formatação ---------- */

export const dinheiro = (v) => (v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

export function data(iso, comHora = false) {
  if (!iso) return '';
  const d = new Date(iso.length <= 10 ? iso + 'T12:00:00' : iso);
  const f = d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
  if (!comHora) return f;
  return f + ', ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

export function dataLonga(iso) {
  const d = new Date(iso + 'T12:00:00');
  return d.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: 'short' });
}

export function idade(nascimento) {
  if (!nascimento) return '';
  const n = new Date(nascimento + 'T12:00:00');
  const hoje = new Date();
  let a = hoje.getFullYear() - n.getFullYear();
  const m = hoje.getMonth() - n.getMonth();
  if (m < 0 || (m === 0 && hoje.getDate() < n.getDate())) a--;
  return a + ' anos';
}

export const iniciais = (nome) => (nome || '').split(' ').filter(Boolean).slice(0, 2).map(p => p[0]).join('').toUpperCase();

/* Mapa desenhado no próprio arquivo. Sem serviço externo, por exigência de
   protótipo autocontido. */
export function mapaSimulado(pontos = 3) {
  const ns = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(ns, 'svg');
  svg.setAttribute('viewBox', '0 0 600 220');
  svg.setAttribute('class', 'mapa-sim');
  svg.setAttribute('aria-hidden', 'true');
  const add = (tag, attrs, texto) => {
    const n = document.createElementNS(ns, tag);
    for (const [k, v] of Object.entries(attrs)) n.setAttribute(k, v);
    if (texto) n.textContent = texto;
    svg.appendChild(n);
    return n;
  };
  add('rect', { x: 0, y: 0, width: 600, height: 220, fill: 'var(--nevoa)' });
  add('path', { d: 'M0 84H600M0 152H600M136 0V220M322 0V220M462 0V220', stroke: 'var(--linha)', 'stroke-width': 10 });
  add('rect', { x: 20, y: 96, width: 96, height: 44, rx: 6, fill: 'var(--linha)' });
  add('rect', { x: 356, y: 18, width: 86, height: 52, rx: 6, fill: 'var(--linha)' });
  add('circle', { cx: 200, cy: 120, r: 20, fill: 'var(--marca)', opacity: .16 });
  add('circle', { cx: 200, cy: 120, r: 9, fill: 'var(--marca)' });
  add('text', { x: 200, y: 150, 'text-anchor': 'middle', 'font-size': 10, fill: 'var(--tinta-2)' }, 'Você');
  const locais = [[288, 66], [416, 146], [120, 190]];
  for (let i = 0; i < Math.min(pontos, 3); i++) {
    add('circle', { cx: locais[i][0], cy: locais[i][1], r: 13, fill: 'var(--ok)' });
    add('text', { x: locais[i][0], y: locais[i][1] + 4, 'text-anchor': 'middle', 'font-size': 12, fill: '#fff', 'font-weight': 700 }, String(i + 1));
  }
  add('text', { x: 300, y: 212, 'text-anchor': 'middle', 'font-size': 9, fill: 'var(--tinta-3)' }, 'Mapa desenhado no próprio arquivo, sem serviço externo');
  return svg;
}
