/* ==========================================================================
   Telas do paciente.

   Origem de cada tela declarada no cabeçalho, com as etiquetas
   Oficial, Reunião, Esboço, Melhoria e Novo.
   ========================================================================== */

import * as api from './api.js';
import * as D from './dados.js';
import { estado, salvar, moduloLiberado, planoAtual } from './estado.js';
import {
  el, frag, limpar, ico, carregando, vazio, erro, sucesso, aviso, modal, confirmar,
  cabecalho, campo, marcarErro, limparErros, ocupado, dinheiro, data, dataLonga,
  idade, iniciais, mapaSimulado, indicador, aurora, origem, ambiente, proximaAcaoCartao
} from './ui.js';
import { blocoMax, proximaAcaoPaciente, periodoDoDia, primeiroNome } from './max.js';


/* Utilitário: monta um container e preenche quando a promessa resolve */
function assincrono(carregar, montar, textoCarga) {
  const caixa = el('div', {}, [carregando(textoCarga)]);
  const rodar = () => {
    limpar(caixa).appendChild(carregando(textoCarga));
    carregar()
      .then(dados => { limpar(caixa).appendChild(montar(dados, rodar)); })
      .catch(e => { limpar(caixa).appendChild(erro({ texto: e.message, aoTentar: rodar })); });
  };
  rodar();
  return caixa;
}

/* ==========================================================================
   Início. ORIGEM: Esboço, mais a ordem e os cartões de cobertura da Reunião.
   ========================================================================== */

export function inicio(ir) {
  const p = estado.pacienteAtual;
  const plano = planoAtual();

  const servico = (chave, icone, titulo, desc, destino, chips) => {
    const liberado = moduloLiberado(chave);
    return el('button', {
      class: 'servico',
      disabled: !liberado,
      onclick: () => liberado && ir(destino)
    }, [
      el('span', { class: 'quadro' }, [ico(icone, 21)]),
      el('span', { class: 'corpo' }, [
        el('span', { class: 'tit', texto: titulo }),
        el('span', { class: 'des', texto: desc }),
        el('span', { class: 'linha mt2' }, [
          ...(chips || []).map(c => el('span', { class: 'chip', texto: c })),
          liberado ? null : el('span', { class: 'etiqueta et-neutra' }, [ico('escudo', 13), 'Não incluído no seu plano'])
        ])
      ]),
      el('span', {}, [ico(liberado ? 'avanca' : 'escudo')])
    ]);
  };

  /* Estado geral, calculado do que já existe. Nenhum número inventado. */
  const minhas = estado.consultas.filter(c => c.pacienteId === p.id);
  const itensCobertos = estado.receitas.flatMap(r => r.itens).filter(i => i.cobertura === 'coberto').length;
  const tiras = [
    { icone: 'escudo', valor: plano ? plano.nome : 'Sem plano', rotulo: '' },
    { icone: 'lista', valor: minhas.filter(c => c.status === 'FINISHED').length, rotulo: 'consultas no histórico' },
    moduloLiberado('farmacia')
      ? { icone: 'remedio', valor: itensCobertos, rotulo: 'itens de graça na farmácia' }
      : null,
    moduloLiberado('atendimento')
      ? { icone: 'relogio', valor: '24h', rotulo: 'médico disponível' }
      : null
  ].filter(Boolean);

  return frag([
    /* CAMADA DE EVOLUÇÃO: a hierarquia mudou. Primeiro quem sou e como estou,
       depois o que preciso fazer, depois quem me ajuda. A vitrine de serviços
       continua inteira, só desceu na página. */
    ambiente(
      `${periodoDoDia()}, ${primeiroNome(p.nome)}.`,
      estado.cliente.saudacao,
      tiras
    ),

    proximaAcaoCartao(proximaAcaoPaciente(), ir),

    blocoMax(ir),

    /* ORIGEM REUNIAO: dois cartões de cobertura em destaque, e o prazo de
       cinco minutos fora da interface do paciente. */
    el('h2', { class: 'mt2', texto: 'Cobertura do seu atendimento' }),
    el('div', { class: 'grade g2 mt3' }, [
      el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: '24 horas por dia, todos os dias' }),
        el('p', { class: 'cartao-apoio', texto: 'Clínica médica, pediatria, geriatria e medicina de família. Um atendente humano recebe você e direciona para a especialidade correta.' })
      ]),
      el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Nutrição e psicologia, de segunda a sexta' }),
        el('p', { class: 'cartao-apoio' }, [
          'Das 9h às 23h, para acolhimento e orientação. ',
          el('span', { class: 'negrito', texto: 'Não é consulta.' }),
          ' A consulta com especialista tem outro formato e outra duração.'
        ])
      ])
    ]),

    el('h2', { class: 'mt5', texto: 'Serviços disponíveis' }),
    el('div', { class: 'pilha mt3' }, [
      servico('orientacao', 'conversa', 'Orientação em saúde',
        'Comece por aqui. Conte o que está sentindo, por texto ou por voz.',
        '#/orientacao', ['Texto ou voz', '24 horas']),
      servico('atendimento', 'video', 'Falar com um médico agora',
        'Atendimento por vídeo em minutos. Clínico, pediatra, geriatra e médico da família.',
        '#/atendimento', ['Atendente humano direciona', '24 horas']),
      servico('agendamento', 'calendario', 'Agendar uma consulta',
        estado.cliente.regulacao
          ? 'Em município, a marcação com especialista passa pelo núcleo de regulação.'
          : 'Escolha especialidade, dia e horário.',
        '#/agendamento', [estado.cliente.regulacao ? 'Via núcleo de regulação' : 'Especialidades do catálogo']),
      servico('farmacia', 'remedio', 'Farmácia popular',
        'Veja o que você retira de graça e em quais farmácias perto de você.',
        '#/farmacia', ['Retirada gratuita', 'Resposta na hora']),
      servico('nr1', 'mente', 'Orientação de saúde mental',
        'Acolhimento, acompanhamento periódico e recursos de apoio. Trilha NR-1.',
        '#/nr1', ['Conformidade NR-1', 'Segunda a sexta'])
    ]),

    el('div', { class: 'faixa faixa-info mt5' }, [
      el('span', { class: 'negrito', texto: 'Como a vitrine se monta. ' }),
      `Seu plano é ${plano ? plano.nome : 'não identificado'}. Os serviços acima aparecem ligados ou apagados conforme o de para entre plano e módulo, que o gestor configura. Troque o plano na barra de demonstração para ver a tela mudar ao vivo.`
    ]),

    el('div', { class: 'linha mt4' }, [
      el('button', { class: 'btn btn-secundario', onclick: () => ir('#/consultas') }, [ico('lista'), 'Minhas consultas']),
      el('button', { class: 'btn btn-secundario', onclick: () => ir('#/conta') }, [ico('pessoa'), 'Minha conta']),
      el('button', { class: 'btn btn-perigo', onclick: () => ir('#/ajuda') }, [ico('alerta'), 'Preciso de ajuda agora'])
    ]),

    /* Rastreabilidade. Saiu do topo para o rodapé, porque a primeira coisa
       que a pessoa vê tem que ser ela mesma, não a etiqueta de origem. */
    el('div', { class: 'linha mt5' }, [origem('esboco'), origem('reuniao'), origem('melhoria')])
  ]);
}

/* ==========================================================================
   Orientação em saúde. ORIGEM: Esboço mais Reunião.
   Regra 2: orienta e encaminha, nunca conclui sobre doença.
   ========================================================================== */

export function orientacao(ir) {
  const conversa = el('div', { class: 'conversa' });
  const entrada = el('input', { type: 'text', placeholder: 'Conte o que você está sentindo', 'aria-label': 'Sua mensagem' });
  const botao = el('button', { class: 'btn btn-primario', type: 'submit' }, ['Enviar']);
  const saidas = el('div', { class: 'pilha mt4' });
  let porVoz = false;

  const fala = (texto, minha) => {
    conversa.appendChild(el('div', { class: 'bolha ' + (minha ? 'minha' : 'deles'), texto }));
    conversa.scrollTop = conversa.scrollHeight;
  };

  const digitando = () => {
    const n = el('div', { class: 'bolha deles' }, [
      el('span', { class: 'digitando' }, [el('i'), el('i'), el('i')])
    ]);
    conversa.appendChild(n);
    conversa.scrollTop = conversa.scrollHeight;
    return n;
  };

  const oferecer = (seguir) => {
    limpar(saidas);
    if (seguir === 'urgencia') {
      saidas.appendChild(el('div', { class: 'faixa faixa-erro' }, [
        el('span', { class: 'negrito', texto: 'Sinal de alerta. ' }),
        'Procure atendimento agora. Se piorar, ligue 192.'
      ]));
      saidas.appendChild(el('div', { class: 'linha' }, [
        el('button', { class: 'btn btn-primario', onclick: () => ir('#/ajuda') }, [ico('alerta'), 'Ver o que fazer agora']),
        el('button', { class: 'btn btn-secundario', onclick: () => ir('#/atendimento') }, [ico('video'), 'Falar com a equipe médica'])
      ]));
    } else if (seguir === 'farmacia') {
      saidas.appendChild(el('button', { class: 'btn btn-primario', onclick: () => ir('#/farmacia') }, [ico('remedio'), 'Ver minha receita e onde retirar']));
    } else if (seguir === 'orientacao') {
      saidas.appendChild(el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Cuidados enquanto isso' }),
        el('ul', { class: 'cartao-apoio', style: 'margin:0;padding-left:18px' }, [
          el('li', { texto: 'Beba água ao longo do dia.' }),
          el('li', { texto: 'Descanse em ambiente com pouca luz e pouco barulho.' }),
          el('li', { texto: 'Anote quando começou e o que melhora ou piora.' })
        ]),
        el('p', { class: 'cartao-titulo mt4', texto: 'Procure atendimento se aparecer' }),
        el('ul', { class: 'cartao-apoio', style: 'margin:0;padding-left:18px' },
          D.SINAIS_GRAVIDADE.slice(0, 4).map(s => el('li', { texto: s }))),
        el('div', { class: 'faixa faixa-aviso mt4', texto: 'Estes sinais e a tela de ajuda imediata ainda aguardam validação clínica do responsável médico do projeto.' }),
        el('div', { class: 'linha mt4' }, [
          el('button', { class: 'btn btn-primario', onclick: () => ir('#/atendimento') }, [ico('video'), 'Falar com um médico agora']),
          moduloLiberado('agendamento') ? el('button', { class: 'btn btn-secundario', onclick: () => ir('#/agendamento') }, [ico('calendario'), 'Agendar consulta']) : null
        ])
      ]));
    }
  };

  const enviar = async (texto) => {
    if (!texto.trim()) return;
    fala(texto, true);
    entrada.value = '';
    const pensando = digitando();
    const r = await api.orientar(texto);
    pensando.remove();
    fala(r.resposta, false);
    oferecer(r.seguir);
  };

  fala('Oi. Eu sou o assistente de orientação em saúde. Eu não faço consulta e não digo qual é a sua doença. O que eu faço é te orientar sobre o cuidado e te levar para o lugar certo. Pode escrever ou falar. O que está acontecendo?', false);

  const formulario = el('form', {
    class: 'linha mt4',
    onsubmit: (ev) => { ev.preventDefault(); enviar(entrada.value); }
  }, [
    el('button', {
      class: 'btn btn-secundario', type: 'button',
      'aria-pressed': 'false', title: 'Falar em vez de escrever',
      onclick: (ev) => {
        porVoz = !porVoz;
        ev.currentTarget.setAttribute('aria-pressed', String(porVoz));
        aviso(porVoz ? 'Microfone ligado. A entrada por voz é obrigatória no produto final.' : 'Microfone desligado.');
      }
    }, [ico('conversa'), 'Voz']),
    el('div', { style: 'flex:1;min-width:200px' }, [entrada]),
    botao
  ]);

  return frag([
    cabecalho('Orientação em saúde',
      'O assistente orienta e encaminha. Ele nunca conclui sobre doença, e toda conversa fica registrada para auditoria.',
      ['esboco', 'reuniao']),

    el('div', { class: 'faixa faixa-aviso' }, [
      el('span', { class: 'negrito', texto: 'Aguardando conteúdo clínico. ' }),
      'O modelo será fundamentado nas diretrizes brasileiras: Ministério da Saúde, secretarias estaduais e municipais, e sociedades brasileiras de especialidades. O roteiro abaixo é demonstração até o script do responsável médico chegar.'
    ]),

    el('div', { class: 'cartao mt4' }, [conversa, formulario]),
    saidas,

    el('div', { class: 'cartao mt4' }, [
      el('p', { class: 'cartao-titulo', texto: 'Registro auditável' }),
      el('p', { class: 'cartao-apoio', texto: 'Cada turno grava a pergunta, a resposta, a classificação de saída, o destino do encaminhamento, a versão da base de conhecimento e a versão do modelo. Nada é apagado, e não existe configuração que desligue.' }),
      el('div', { class: 'linha mt3' }, [
        el('span', { class: 'chip', texto: 'base v2026.08' }),
        el('span', { class: 'chip', texto: 'modelo v1.4' }),
        el('span', { class: 'chip', texto: 'turnos: ' + Math.ceil(conversa.children.length / 2) })
      ])
    ])
  ]);
}

/* ==========================================================================
   Atendimento imediato. ORIGEM: Reunião.
   Não existe fila nem sala de espera nesta camada. O botão abre a plataforma
   existente em janela, com o paciente já autenticado, pelo magic link.
   ========================================================================== */

export function atendimento(ir) {
  const palco = el('div');

  const passoInicial = () => {
    limpar(palco).appendChild(frag([
      el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Atendimento por vídeo em minutos' }),
        el('p', { class: 'cartao-apoio', texto: 'Clínica médica, pediatria, geriatria e medicina de família, 24 horas por dia, todos os dias. Um atendente humano recebe você na entrada e direciona para a especialidade correta.' }),
        el('div', { class: 'linha mt4' }, [
          el('button', { class: 'btn btn-primario', onclick: abrir }, [ico('video'), 'Entrar no atendimento'])
        ])
      ]),
      el('div', { class: 'faixa faixa-info mt4' }, [
        el('span', { class: 'negrito', texto: 'Como isto funciona por dentro. ' }),
        'A camada nova chama create-emergency-consultation, recebe um magic link e a URL de retorno, e abre a plataforma existente já autenticada. Nada de fila é reconstruído aqui.'
      ])
    ]));
  };

  const abrir = async (ev) => {
    const b = ev.currentTarget;
    ocupado(b, true, 'Abrindo atendimento');
    try {
      const r = await api.abrirProntoAtendimento(estado.pacienteAtual.cpf, location.origin + location.pathname + '#/atendimento/volta');
      emAtendimento(r);
    } catch (e) {
      limpar(palco).appendChild(erro({ texto: e.message, aoTentar: passoInicial }));
    }
  };

  const emAtendimento = (r) => {
    limpar(palco).appendChild(frag([
      el('div', { class: 'moldura-externa' }, [
        el('div', { class: 'tarja-externa' }, [
          ico('plugue', 15),
          'Plataforma existente, aberta em janela sobre a Saúde Maxi',
          el('span', { class: 'espaco' }),
          el('span', { texto: 'Consulta ' + r.consultation_code })
        ]),
        el('div', { class: 'palco-video' }, [
          el('div', {}, [
            el('p', { class: 'negrito', style: 'color:#DCE6F0', texto: 'Área de videochamada da plataforma existente' }),
            el('p', { class: 'mt2', texto: 'Câmera, microfone, prontuário, emissão de receita e encerramento pertencem a ela.' })
          ])
        ])
      ]),
      el('div', { class: 'cartao mt4' }, [
        el('p', { class: 'cartao-titulo', texto: 'O que a plataforma devolveu' }),
        el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'magic_link' }), el('p', { class: 'tit', style: 'word-break:break-all', texto: r.magic_link })])]),
        el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'consultation_code' }), el('p', { class: 'tit', texto: r.consultation_code })])]),
        el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'expires_at' }), el('p', { class: 'tit', texto: 'null, não expira por tempo. Vale enquanto a consulta estiver aberta.' })])]),
        el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'external_url' }), el('p', { class: 'tit', style: 'word-break:break-all', texto: r.external_url })])])
      ]),
      el('div', { class: 'linha mt4' }, [
        el('button', { class: 'btn btn-primario', onclick: () => encerrar(r) }, [ico('volta'), 'Encerrar e voltar para a Saúde Maxi'])
      ]),
      el('div', { class: 'faixa faixa-aviso mt4' }, [
        el('span', { class: 'negrito', texto: 'Limite conhecido. ' }),
        'O retorno acontece pelo botão de saída, no navegador do paciente. Não é webhook. Se ele fechar a aba, a camada nova não fica sabendo, e a reconciliação depende de consultar o histórico de consultas.'
      ])
    ]));
  };

  const encerrar = (r) => {
    const registro = {
      codigo: r.consultation_code, pacienteId: estado.pacienteAtual.id, tipo: 'POOL',
      especialidade: 'Clínica Médica', medico: 'Dra. Helena Vasconcelos', status: 'FINISHED',
      agendadaPara: new Date().toISOString(), duracao: '9 min', pago: true, avaliacao: null, receita: true
    };
    if (!estado.consultas.find(c => c.codigo === registro.codigo)) estado.consultas.unshift(registro);
    salvar();

    limpar(palco).appendChild(frag([
      sucesso('Atendimento encerrado',
        `A plataforma devolveu você para a Saúde Maxi com o código ${r.consultation_code} e a situação FINISHED na volta.`,
        [
          el('button', { class: 'btn btn-primario', onclick: () => ir('#/farmacia') }, [ico('remedio'), 'Ver minha receita']),
          el('button', { class: 'btn btn-secundario', onclick: () => ir('#/inicio') }, ['Voltar ao início'])
        ]),
      el('div', { class: 'faixa faixa-erro mt4' }, [
        el('span', { class: 'negrito', texto: 'Ponto sem solução hoje. ' }),
        'A receita emitida nesta consulta sai pela integração com o Mevo dentro da plataforma existente. A API não tem endpoint de prescrição, então ela não chega aqui sozinha. Enquanto isso não se resolver, o envio de foto continua sendo o caminho que funciona.'
      ])
    ]));
  };

  passoInicial();

  return frag([
    cabecalho('Falar com um médico agora',
      'O clique leva direto ao atendimento. Fila e sala de espera acontecem dentro da plataforma existente e não são reconstruídas aqui.',
      ['reuniao', 'oficial']),
    palco
  ]);
}

/* ==========================================================================
   Agendamento. ORIGEM: Oficial, o fluxo de seis chamadas da API, mais as
   regras de prefeitura e de pagamento vindas da Reunião.
   ========================================================================== */

export function agendamento(ir) {
  if (estado.cliente.regulacao) {
    return frag([
      cabecalho('Agendar uma consulta', null, ['reuniao']),
      el('div', { class: 'cartao' }, [
        el('div', { class: 'estado' }, [
          el('div', { class: 'icone' }, [ico('escudo', 24)]),
          el('h3', { texto: 'A marcação passa pelo núcleo de regulação' }),
          el('p', { texto: `Em ${estado.cliente.nome}, a consulta com especialista é organizada pelo núcleo de regulação do município, e não pelo paciente. Isso evita o desvio de agenda que quebraria o orçamento da rede.` }),
          el('div', { class: 'linha', style: 'justify-content:center' }, [
            el('button', { class: 'btn btn-primario', onclick: () => ir('#/atendimento') }, [ico('video'), 'Falar com um médico agora']),
            el('button', { class: 'btn btn-secundario', onclick: () => ir('#/inicio') }, ['Voltar ao início'])
          ])
        ])
      ]),
      el('div', { class: 'faixa faixa-info mt4' }, [
        el('span', { class: 'negrito', texto: 'Por que esta regra existe aqui e não lá. ' }),
        'Este bloqueio só é possível porque a camada nova monta o agendamento pela API. Dentro de uma janela embutida da plataforma existente, não haveria como aplicar a regra por tipo de cliente.'
      ])
    ]);
  }

  const ctx = { esp: null, dia: null, hora: null, medico: null, preco: 0 };
  const trilho = el('div', { class: 'passos' });
  const palco = el('div');

  const desenharTrilho = (atual) => {
    const nomes = ['Especialidade', 'Dia', 'Horário', 'Profissional', 'Pagamento', 'Pronto'];
    limpar(trilho);
    nomes.forEach((n, i) => {
      const cls = i + 1 === atual ? 'passo ativo' : (i + 1 < atual ? 'passo feito' : 'passo');
      trilho.appendChild(el('div', { class: cls }, [
        el('span', { class: 'bola', texto: i + 1 < atual ? '✓' : String(i + 1) }),
        el('span', { texto: n })
      ]));
      if (i < nomes.length - 1) trilho.appendChild(el('span', { class: 'traco' }));
    });
  };

  const grade = (itens, aoEscolher, vazioTexto) => {
    if (!itens.length) return vazio({ titulo: 'Nada disponível', texto: vazioTexto });
    return el('div', { class: 'opcoes' }, itens.map(i =>
      el('button', { class: 'opcao', onclick: () => aoEscolher(i.valor) }, [
        el('span', { texto: i.rotulo }),
        i.sub ? el('span', { class: 'sub', texto: i.sub }) : null
      ])
    ));
  };

  const passo1 = () => {
    desenharTrilho(1);
    limpar(palco).appendChild(assincrono(
      () => api.listarEspecialidades(),
      (lista) => lista.length ? el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Escolha a especialidade' }),
        el('div', { class: 'opcoes mt3' }, lista.map(e =>
          el('button', { class: 'opcao', onclick: () => { ctx.esp = e; ctx.preco = e.price; passo2(); } }, [
            el('span', { texto: e.name }),
            el('span', { class: 'sub', texto: e.price ? dinheiro(e.price) : 'Sem custo no seu plano' })
          ])
        ))
      ]) : vazio({ titulo: 'Nenhuma especialidade disponível', texto: 'O catálogo da clínica não devolveu nenhuma especialidade para o seu plano.' }),
      'Consultando especialidades'
    ));
  };

  const passo2 = () => {
    desenharTrilho(2);
    limpar(palco).appendChild(assincrono(
      () => api.listarDias(ctx.esp.id),
      (dias) => el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Escolha o dia' }),
        el('p', { class: 'cartao-apoio', texto: ctx.esp.name }),
        el('div', { class: 'mt3' }, [grade(
          dias.map(d => ({ valor: d, rotulo: dataLonga(d), sub: data(d) })),
          (d) => { ctx.dia = d; passo3(); },
          'Não há dia útil disponível para esta especialidade no período.'
        )]),
        el('button', { class: 'btn btn-fantasma mt3', onclick: passo1 }, [ico('volta'), 'Trocar especialidade'])
      ]),
      'Consultando dias disponíveis'
    ));
  };

  const passo3 = () => {
    desenharTrilho(3);
    limpar(palco).appendChild(assincrono(
      () => api.listarHorarios(ctx.esp.id, ctx.dia),
      (horas) => el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Escolha o horário' }),
        el('p', { class: 'cartao-apoio', texto: `${ctx.esp.name}, ${data(ctx.dia)}` }),
        el('div', { class: 'mt3' }, [grade(
          horas.map(h => ({ valor: h, rotulo: h })),
          (h) => { ctx.hora = h; passo4(); },
          'Todos os horários deste dia já foram ocupados.'
        )]),
        el('button', { class: 'btn btn-fantasma mt3', onclick: passo2 }, [ico('volta'), 'Trocar o dia'])
      ]),
      'Consultando horários'
    ));
  };

  const passo4 = () => {
    desenharTrilho(4);
    limpar(palco).appendChild(assincrono(
      () => api.listarMedicos(ctx.esp.id, ctx.dia, ctx.hora),
      (medicos) => el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Escolha o profissional' }),
        el('p', { class: 'cartao-apoio', texto: `${ctx.esp.name}, ${data(ctx.dia)}, ${ctx.hora}` }),
        el('div', { class: 'pilha mt3' }, medicos.map(m =>
          el('button', { class: 'servico', onclick: () => { ctx.medico = m; ctx.preco = m.price; passo5(); } }, [
            el('span', { class: 'quadro' }, [ico('pessoa', 20)]),
            el('span', { class: 'corpo' }, [
              el('span', { class: 'tit', texto: m.name }),
              el('span', { class: 'des', texto: m.is_real ? 'Profissional específico, com disponibilidade conferida no turno' : 'Quem estiver livre no horário escolhido' })
            ]),
            el('span', {}, [ico('avanca')])
          ])
        )),
        el('button', { class: 'btn btn-fantasma mt3', onclick: passo3 }, [ico('volta'), 'Trocar o horário'])
      ]),
      'Consultando profissionais'
    ));
  };

  const passo5 = () => {
    desenharTrilho(5);
    const gratuito = !ctx.preco;
    const resumo = el('div', { class: 'cartao' }, [
      el('p', { class: 'cartao-titulo', texto: 'Confirme os dados' }),
      el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Especialidade' }), el('p', { class: 'tit', texto: ctx.esp.name })])]),
      el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Quando' }), el('p', { class: 'tit', texto: `${data(ctx.dia)}, às ${ctx.hora}` })])]),
      el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Profissional' }), el('p', { class: 'tit', texto: ctx.medico.name })])]),
      el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Valor' }), el('p', { class: 'tit', texto: gratuito ? 'Sem custo no seu plano' : dinheiro(ctx.preco) })])])
    ]);

    const pagar = el('div', { class: 'cartao' }, [
      el('p', { class: 'cartao-titulo', texto: 'Pagamento' }),
      el('p', { class: 'cartao-apoio', texto: 'O recebimento é da Maximize, e não do provedor da plataforma de atendimento. A plataforma recebe apenas a marcação de que a consulta foi paga.' }),
      el('div', { class: 'campo-linha mt4' }, [
        campo({ id: 'pg-nome', rotulo: 'Nome impresso no cartão', valor: estado.pacienteAtual.nome }),
        campo({ id: 'pg-num', rotulo: 'Número do cartão', valor: '4111 1111 1111 1111' })
      ]),
      el('div', { class: 'campo-linha' }, [
        campo({ id: 'pg-val', rotulo: 'Validade', valor: '12/2030' }),
        campo({ id: 'pg-cvv', rotulo: 'Código de segurança', valor: '123' })
      ]),
      el('p', { class: 'pequeno', texto: 'Dados de exemplo. Nenhuma cobrança acontece neste esboço.' })
    ]);

    const botao = el('button', { class: 'btn btn-primario btn-cheio', onclick: (ev) => concluir(ev.currentTarget, !gratuito) },
      [gratuito ? 'Confirmar agendamento' : 'Pagar e confirmar']);

    limpar(palco).appendChild(frag([
      resumo,
      gratuito ? null : pagar,
      el('div', { class: 'mt4' }, [botao]),
      el('button', { class: 'btn btn-fantasma mt3', onclick: passo4 }, [ico('volta'), 'Trocar o profissional'])
    ]));
  };

  const concluir = async (botao, cobrar) => {
    ocupado(botao, true, cobrar ? 'Processando pagamento' : 'Agendando');
    try {
      const r = await api.criarConsulta({
        patient_cpf: estado.pacienteAtual.cpf,
        specialty_id: ctx.esp.id,
        especialidadeNome: ctx.esp.name,
        medicoNome: ctx.medico.name,
        date: ctx.dia,
        time: ctx.hora,
        doctor_id: ctx.medico.id,
        is_real_doctor: ctx.medico.is_real,
        is_paid: cobrar,
        price: ctx.preco
      });
      desenharTrilho(6);
      limpar(palco).appendChild(frag([
        sucesso('Consulta agendada',
          `Código ${r.consultation_code}, ${data(r.scheduled_for, true)}, com ${ctx.medico.name}.`,
          [
            el('button', { class: 'btn btn-primario', onclick: () => ir('#/consultas') }, [ico('lista'), 'Ver minhas consultas']),
            el('button', { class: 'btn btn-secundario', onclick: () => ir('#/inicio') }, ['Voltar ao início'])
          ]),
        cobrar ? el('div', { class: 'faixa faixa-ok mt4' }, [
          el('span', { class: 'negrito', texto: 'Pagamento registrado. ' }),
          'A camada nova cobrou por fora e chamou update-payment-status para marcar is_paid na plataforma. O dinheiro não passou pelo gateway do fornecedor.'
        ]) : null
      ]));
    } catch (e) {
      ocupado(botao, false, cobrar ? 'Pagar e confirmar' : 'Confirmar agendamento');
      aviso(e.message, 'erro');
    }
  };

  passo1();

  return frag([
    cabecalho('Agendar uma consulta',
      'Fluxo montado pela camada nova, sobre os seis endpoints de agendamento da plataforma existente.',
      ['oficial', 'reuniao']),
    el('div', { class: 'faixa faixa-aviso' }, [
      el('span', { class: 'negrito', texto: 'Aguardando validação. ' }),
      'A reunião decidiu não construir agendamento. Só que as regras de núcleo de regulação e de recebimento pela Maximize não cabem dentro de uma janela embutida. Esta tela mostra o caminho pela API, que atende as duas regras, e aguarda a decisão do responsável pelo projeto.'
    ]),
    trilho,
    palco
  ]);
}

/* ==========================================================================
   Farmácia popular. ORIGEM: Esboço, Reunião e Melhoria.
   ========================================================================== */

export function farmacia(ir) {
  return frag([
    cabecalho('Farmácia popular',
      'Mostramos quais medicamentos da sua receita você retira de graça e onde. Nunca alteramos a sua prescrição, e nunca sugerimos troca de medicamento.',
      ['esboco', 'reuniao'],
      [el('button', { class: 'btn btn-secundario', onclick: () => enviarFoto(ir) }, [ico('camera'), 'Enviar foto de receita'])]),

    el('div', { class: 'faixa faixa-erro' }, [
      el('span', { class: 'negrito', texto: 'Fonte de dado em aberto. ' }),
      'A API da plataforma de atendimento não tem endpoint de prescrição. A receita emitida em consulta sai pela integração com o Mevo e não chega aqui sozinha. Enquanto isso não se resolver, o envio de foto é o caminho que funciona.'
    ]),

    assincrono(
      () => api.buscarReceitas(estado.pacienteAtual.id),
      (lista, recarregar) => lista.length
        ? el('div', { class: 'cartao' }, [
            el('p', { class: 'cartao-titulo', texto: 'Minhas receitas' }),
            ...lista.map(r => el('div', { class: 'lista-item' }, [
              el('span', { class: 'quadro', style: 'width:38px;height:38px;border-radius:var(--r-sm);background:var(--marca-suave);color:var(--marca);display:grid;place-items:center;border:1px solid var(--marca-linha);flex:none' },
                [ico(r.origem === 'foto' ? 'camera' : 'documento', 18)]),
              el('span', { class: 'corpo' }, [
                el('p', { class: 'tit', texto: 'Receita de ' + data(r.data) }),
                el('p', { class: 'meta', texto: `${r.medico}. ${r.itens.length} ${r.itens.length === 1 ? 'item' : 'itens'}.` })
              ]),
              el('span', { class: 'etiqueta ' + (r.origem === 'foto' ? 'et-neutra' : 'et-marca'), texto: r.origem === 'foto' ? 'Enviada por você' : 'Plataforma Saúde Maxi' }),
              el('button', { class: 'btn btn-secundario btn-pequeno', onclick: () => ir('#/receita/' + r.id) }, ['Ver'])
            ]))
          ])
        : vazio({
            titulo: 'Nenhuma receita ainda',
            texto: 'Quando você tiver uma receita, ela aparece aqui. Você também pode enviar a foto de uma receita feita fora da plataforma.',
            acao: el('button', { class: 'btn btn-primario', onclick: () => enviarFoto(ir) }, [ico('camera'), 'Enviar foto de receita'])
          }),
      'Buscando suas receitas'
    )
  ]);
}

function enviarFoto(ir) {
  modal({
    titulo: 'Enviar foto da receita',
    corpo: frag([
      el('p', { class: 'cartao-apoio', texto: 'Foto torta, borrada ou amassada é o caso normal, e o sistema trata isso. Quando a leitura não for confiável, ele mostra o que leu e pergunta, em vez de afirmar.' }),
      el('div', { class: 'cartao mt4', style: 'border-style:dashed;text-align:center' }, [
        el('div', { class: 'icone', style: 'width:52px;height:52px;border-radius:var(--r-lg);background:var(--nevoa);color:var(--tinta-3);display:grid;place-items:center;margin:0 auto 12px' }, [ico('camera', 24)]),
        el('p', { class: 'cartao-apoio', texto: 'Toque para tirar a foto ou escolher do aparelho' })
      ]),
      el('ul', { class: 'pequeno mt4', style: 'padding-left:18px;margin:0' }, [
        el('li', { texto: 'Apoie a receita numa superfície plana.' }),
        el('li', { texto: 'Evite sombra sobre o papel.' }),
        el('li', { texto: 'Enquadre a receita inteira, sem cortar as bordas.' })
      ])
    ]),
    acoes: (fechar) => [
      el('button', { class: 'btn btn-secundario', onclick: fechar }, ['Cancelar']),
      el('button', {
        class: 'btn btn-primario',
        onclick: async (ev) => {
          const b = ev.currentTarget;
          ocupado(b, true, 'Lendo a receita');
          const nova = await api.lerReceitaPorFoto();
          fechar();
          aviso('Receita lida. Dois itens precisam da sua confirmação.', 'ok');
          ir('#/receita/' + nova.id);
        }
      }, ['Usar esta foto'])
    ]
  });
}

/* Resultado da receita, com os três blocos de cobertura */
export function receita(ir, id) {
  const r = estado.receitas.find(x => x.id === id);
  if (!r) return vazio({ titulo: 'Receita não encontrada', texto: 'Ela pode ter sido removida ao zerar os dados de demonstração.' });

  const palco = el('div');

  const desenhar = () => {
    const cobertos = r.itens.filter(i => i.cobertura === 'coberto');
    const confirmar_ = r.itens.filter(i => i.cobertura === 'confirmar');
    const naoCobertos = r.itens.filter(i => i.cobertura === 'naoCoberto');

    limpar(palco).appendChild(frag([
      r.origem === 'plataforma'
        ? el('div', { class: 'faixa faixa-info' }, [
            el('span', { class: 'negrito', texto: 'Receita emitida na plataforma. ' }),
            'Ela chega já estruturada, item a item, e não passa por leitura de imagem. Por isso não existe bloco de confirmação nesta receita.'
          ])
        : el('div', { class: 'faixa faixa-aviso' }, [
            el('span', { class: 'negrito', texto: 'Receita enviada por foto. ' }),
            'Onde a leitura ficou abaixo do limiar de confiança, o sistema pergunta em vez de afirmar. Enquanto você não confirmar, nada é afirmado sobre a cobertura daquele item.'
          ]),

      cobertos.length ? el('div', { class: 'cartao mt4' }, [
        el('p', { class: 'cartao-titulo', texto: 'Disponíveis de graça na farmácia popular' }),
        ...cobertos.map(i => el('div', { class: 'lista-item' }, [
          el('span', { class: 'corpo' }, [
            el('p', { class: 'tit', texto: i.nome }),
            el('p', { class: 'meta', texto: `${i.posologia}. ${i.qtd}.` })
          ]),
          el('span', { class: 'etiqueta et-ok' }, [ico('ok', 13), 'Coberto'])
        ]))
      ]) : null,

      confirmar_.length ? el('div', { class: 'cartao mt4', style: 'border-color:#F0E0B8' }, [
        el('p', { class: 'cartao-titulo', texto: 'Precisamos que você confirme' }),
        el('p', { class: 'cartao-apoio', texto: 'A leitura destes itens ficou incerta. Confira o que lemos e confirme, ou corrija.' }),
        ...confirmar_.map(i => el('div', { class: 'lista-item' }, [
          el('span', { class: 'corpo' }, [
            el('p', { class: 'tit', texto: i.nome }),
            el('p', { class: 'meta', texto: `Confiança da leitura: ${Math.round(i.confianca * 100)} por cento. ${i.qtd}.` })
          ]),
          el('div', { class: 'linha' }, [
            el('button', {
              class: 'btn btn-secundario btn-pequeno',
              onclick: () => {
                modal({
                  titulo: 'Corrigir o item',
                  corpo: campo({ id: 'corr', rotulo: 'Como está escrito na receita', valor: i.nome, aoMudar: (v) => { i.nomeNovo = v; } }),
                  acoes: (fechar) => [
                    el('button', { class: 'btn btn-secundario', onclick: fechar }, ['Cancelar']),
                    el('button', { class: 'btn btn-primario', onclick: () => { if (i.nomeNovo) i.nome = i.nomeNovo; i.cobertura = 'coberto'; i.confianca = 1; salvar(); fechar(); aviso('Item corrigido e confirmado.', 'ok'); desenhar(); } }, ['Salvar'])
                  ]
                });
              }
            }, ['Corrigir']),
            el('button', {
              class: 'btn btn-primario btn-pequeno',
              onclick: () => { i.cobertura = 'coberto'; i.confianca = 1; salvar(); aviso('Item confirmado.', 'ok'); desenhar(); }
            }, ['Está correto'])
          ])
        ]))
      ]) : null,

      naoCobertos.length ? el('div', { class: 'cartao mt4' }, [
        el('p', { class: 'cartao-titulo', texto: 'Não estão na lista custeada' }),
        ...naoCobertos.map(i => el('div', { class: 'lista-item' }, [
          el('span', { class: 'corpo' }, [
            el('p', { class: 'tit', texto: i.nome }),
            el('p', { class: 'meta', texto: 'Procure a unidade de saúde para orientação. O sistema não sugere substituição.' })
          ]),
          el('span', { class: 'etiqueta et-neutra', texto: 'Não coberto' })
        ]))
      ]) : null,

      el('div', { class: 'faixa faixa-aviso mt4' }, [
        el('span', { class: 'negrito', texto: 'Cobertura ainda sem fonte oficial. ' }),
        'A lista de medicamentos custeados muda por decreto e precisa de origem confiável, com data de vigência. Ela ainda não foi definida, e por isso os resultados acima são demonstração.'
      ]),

      el('div', { class: 'linha mt4' }, [
        el('button', { class: 'btn btn-primario', onclick: () => ir('#/farmacias') }, [ico('local'), 'Ver farmácias perto de mim']),
        el('button', { class: 'btn btn-secundario', onclick: () => ir('#/farmacia') }, [ico('volta'), 'Voltar às receitas'])
      ])
    ]));
  };

  desenhar();

  return frag([
    cabecalho('Resultado da sua receita',
      `${data(r.data)}, ${r.medico}.`,
      ['esboco', 'reuniao']),
    palco
  ]);
}

/* Farmácias próximas, com endereço editável e localização atual */
export function farmacias(ir) {
  const endereco = { texto: 'Rua Coronel Bento Ferraz, 212, Centro', origem: 'cadastro' };
  const palco = el('div');

  const desenhar = () => {
    limpar(palco).appendChild(assincrono(
      () => api.listarFarmacias(),
      (lista) => lista.length ? frag([
        el('div', { class: 'cartao' }, [
          el('div', { class: 'linha' }, [
            el('span', { class: 'quadro', style: 'width:38px;height:38px;border-radius:var(--r-sm);background:var(--marca-suave);color:var(--marca);display:grid;place-items:center;border:1px solid var(--marca-linha)' }, [ico('local', 18)]),
            el('span', { style: 'flex:1;min-width:160px' }, [
              el('p', { class: 'tit negrito', texto: endereco.texto }),
              el('p', { class: 'pequeno', texto: endereco.origem === 'cadastro' ? 'Endereço do seu cadastro.' : 'Localização atual. Vale só para esta consulta.' })
            ]),
            el('button', { class: 'btn btn-secundario btn-pequeno', onclick: trocarEndereco }, ['Alterar endereço'])
          ])
        ]),
        el('div', { class: 'mt4' }, [mapaSimulado(lista.length)]),
        el('div', { class: 'cartao mt4' }, lista.map((f, i) => el('div', { class: 'lista-item' }, [
          el('span', { style: 'width:30px;height:30px;border-radius:50%;background:var(--ok);color:#fff;display:grid;place-items:center;font-weight:700;font-size:13px;flex:none', texto: String(i + 1) }),
          el('span', { class: 'corpo' }, [
            el('p', { class: 'tit', texto: f.nome }),
            el('p', { class: 'meta', texto: `${f.endereco}. ${f.distancia}. ${f.horario}.` }),
            el('p', { class: 'meta', texto: 'Cobre: ' + f.cobre.join(', ') + '.' })
          ]),
          el('button', {
            class: 'btn btn-secundario btn-pequeno',
            onclick: () => aviso('Reserva de retirada é fase posterior. Depende de integração com o estoque da farmácia e de parecer jurídico.')
          }, ['Reservar'])
        ]))),
        el('div', { class: 'faixa faixa-info mt4' }, [
          el('span', { class: 'negrito', texto: 'Origem do cadastro. ' }),
          'A lista de farmácias credenciadas vem do próprio município no momento da adesão. A distância é calculada sobre as coordenadas cadastradas.'
        ])
      ]) : vazio({
        titulo: 'Nenhuma farmácia credenciada',
        texto: 'O município ainda não entregou o cadastro de farmácias populares. Procure a unidade de saúde para orientação.'
      }),
      'Buscando farmácias perto de você'
    ));
  };

  const trocarEndereco = () => {
    modal({
      titulo: 'Onde você está agora',
      corpo: frag([
        el('button', {
          class: 'btn btn-primario btn-cheio',
          onclick: (ev) => {
            const b = ev.currentTarget;
            ocupado(b, true, 'Localizando');
            setTimeout(() => {
              endereco.texto = 'Avenida Atlântica, 1700, Copacabana';
              endereco.origem = 'atual';
              document.getElementById('modal-fundo').hidden = true;
              aviso('Busca ajustada para a sua localização atual.', 'ok');
              desenhar();
            }, 900);
          }
        }, [ico('local'), 'Usar minha localização atual']),
        el('p', { class: 'pequeno mt2', texto: 'Se você está viajando ou fora de casa, use a sua posição de agora para achar farmácia perto de onde você está.' }),
        el('div', { class: 'mt4' }, [campo({ id: 'cep', rotulo: 'Ou digite outro CEP', valor: '18320-000' })]),
        el('p', { class: 'pequeno', texto: 'O endereço alterado vale só para esta consulta. Na próxima vez que você entrar, a busca volta para o endereço do seu cadastro.' })
      ]),
      acoes: (fechar) => [
        el('button', { class: 'btn btn-secundario', onclick: fechar }, ['Cancelar']),
        el('button', { class: 'btn btn-primario', onclick: () => { endereco.texto = 'Rua Sete de Abril, 90, Centro'; endereco.origem = 'atual'; fechar(); desenhar(); } }, ['Usar este endereço'])
      ]
    });
  };

  desenhar();

  return frag([
    cabecalho('Farmácias perto de você', null, ['esboco', 'melhoria']),
    palco
  ]);
}

/* ==========================================================================
   Minhas consultas. ORIGEM: Oficial, consultation-history.
   ========================================================================== */

export function consultas(ir) {
  const filtros = { busca: '', status: '' };
  const lista = el('div');

  const desenhar = () => {
    limpar(lista).appendChild(assincrono(
      () => api.historicoConsultas({ pacienteId: estado.pacienteAtual.id, ...filtros }),
      (r) => r.results.length ? el('div', { class: 'cartao' }, [
        el('div', { class: 'rolagem-x' }, [
          el('table', { class: 'tabela' }, [
            el('thead', {}, [el('tr', {}, ['Código', 'Quando', 'Tipo', 'Especialidade', 'Profissional', 'Situação', 'Pagamento'].map(h => el('th', { texto: h })))]),
            el('tbody', {}, r.results.map(c => {
              const s = D.STATUS_CONSULTA[c.status] || { rot: c.status, cor: 'et-neutra' };
              return el('tr', { onclick: () => detalheConsulta(c, ir) }, [
                el('td', { class: 'celula-forte nowrap', texto: c.codigo }),
                el('td', { class: 'nowrap', texto: data(c.agendadaPara, true) }),
                el('td', {}, [el('span', { class: 'chip', texto: c.tipo === 'POOL' ? 'Pronto atendimento' : 'Agendada' })]),
                el('td', { texto: c.especialidade }),
                el('td', { texto: c.medico || 'A definir' }),
                el('td', {}, [el('span', { class: 'etiqueta ' + s.cor, texto: s.rot })]),
                el('td', {}, [el('span', { class: 'etiqueta ' + (c.pago ? 'et-ok' : 'et-neutra'), texto: c.pago ? 'Pago' : 'Em aberto' })])
              ]);
            }))
          ])
        ])
      ]) : vazio({
        titulo: 'Nenhuma consulta encontrada',
        texto: filtros.busca || filtros.status ? 'Nenhum resultado para o filtro aplicado. Limpe os filtros para ver tudo.' : 'Quando você tiver uma consulta, ela aparece aqui.',
        acao: el('button', { class: 'btn btn-primario', onclick: () => ir('#/atendimento') }, [ico('video'), 'Falar com um médico agora'])
      }),
      'Consultando o histórico'
    ));
  };

  desenhar();

  return frag([
    cabecalho('Minhas consultas',
      'Histórico vindo de consultation-history. Sem webhook na plataforma, é consultando este histórico que a camada nova se mantém em dia.',
      ['oficial']),
    el('div', { class: 'cartao' }, [
      el('div', { class: 'linha' }, [
        el('div', { style: 'flex:1;min-width:190px' }, [
          el('input', { type: 'search', placeholder: 'Buscar por código ou especialidade', 'aria-label': 'Buscar', oninput: (e) => { filtros.busca = e.target.value; clearTimeout(desenhar._t); desenhar._t = setTimeout(desenhar, 320); } })
        ]),
        el('select', { 'aria-label': 'Situação', onchange: (e) => { filtros.status = e.target.value; desenhar(); } }, [
          el('option', { value: '' }, ['Todas as situações']),
          ...Object.entries(D.STATUS_CONSULTA).map(([k, v]) => el('option', { value: k }, [v.rot]))
        ])
      ])
    ]),
    el('div', { class: 'mt4' }, [lista])
  ]);
}

function detalheConsulta(c, ir) {
  const s = D.STATUS_CONSULTA[c.status] || { rot: c.status, cor: 'et-neutra' };
  modal({
    titulo: 'Consulta ' + c.codigo,
    corpo: frag([
      el('div', { class: 'linha' }, [
        el('span', { class: 'etiqueta ' + s.cor, texto: s.rot }),
        el('span', { class: 'chip', texto: c.tipo === 'POOL' ? 'Pronto atendimento' : 'Agendada' }),
        el('span', { class: 'etiqueta ' + (c.pago ? 'et-ok' : 'et-neutra'), texto: c.pago ? 'Pago' : 'Em aberto' })
      ]),
      el('div', { class: 'mt4' }, [
        el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Quando' }), el('p', { class: 'tit', texto: data(c.agendadaPara, true) })])]),
        el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Especialidade' }), el('p', { class: 'tit', texto: c.especialidade })])]),
        el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Profissional' }), el('p', { class: 'tit', texto: c.medico || 'A definir pelo atendente' })])]),
        c.duracao ? el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Duração' }), el('p', { class: 'tit', texto: c.duracao })])]) : null
      ])
    ]),
    acoes: (fechar) => [
      c.receita ? el('button', { class: 'btn btn-secundario', onclick: () => { fechar(); ir('#/farmacia'); } }, [ico('remedio'), 'Ver receita']) : null,
      c.status === 'SCHEDULED' ? el('button', {
        class: 'btn btn-perigo',
        onclick: async () => {
          fechar();
          if (await confirmar({ titulo: 'Cancelar a consulta?', texto: `A consulta ${c.codigo} será cancelada. Você pode agendar outra depois.`, rotuloOk: 'Cancelar consulta', perigoso: true })) {
            c.status = 'CANCELED';
            salvar();
            aviso('Consulta cancelada.', 'ok');
            location.hash = '#/consultas';
            window.dispatchEvent(new HashChangeEvent('hashchange'));
          }
        }
      }, ['Cancelar consulta']) : null,
      el('button', { class: 'btn btn-primario', onclick: fechar }, ['Fechar'])
    ].filter(Boolean)
  });
}

/* ==========================================================================
   Minha conta. ORIGEM: Esboço e Oficial.
   ========================================================================== */

export function conta(ir) {
  const p = estado.pacienteAtual;
  const plano = planoAtual();
  const palco = el('div');

  const desenhar = () => {
    limpar(palco).appendChild(frag([
      el('div', { class: 'grade g2' }, [
        el('div', { class: 'cartao' }, [
          el('p', { class: 'cartao-titulo', texto: 'Identificação' }),
          el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Nome' }), el('p', { class: 'tit', texto: p.nome })])]),
          el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'CPF' }), el('p', { class: 'tit', texto: p.cpf })])]),
          el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Nascimento' }), el('p', { class: 'tit', texto: `${data(p.nascimento)}, ${idade(p.nascimento)}` })])]),
          el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Titularidade' }), el('p', { class: 'tit', texto: p.titular ? 'Titular' : 'Dependente' })])]),
          el('button', { class: 'btn btn-secundario mt4', onclick: editar }, ['Editar meus dados'])
        ]),
        el('div', { class: 'cartao' }, [
          el('p', { class: 'cartao-titulo', texto: 'Plano e contrato' }),
          el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Cliente' }), el('p', { class: 'tit', texto: estado.cliente.nome })])]),
          el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Plano' }), el('p', { class: 'tit', texto: plano ? plano.nome : 'Não identificado' })])]),
          el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Adesão' }), el('p', { class: 'tit', texto: data(p.adesao) })])]),
          el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Máximo de dependentes' }), el('p', { class: 'tit', texto: plano ? String(plano.maxDependentes) : '0' })])]),
          el('div', { class: 'linha mt3' }, Object.entries(estado.modulos).filter(([, v]) => v).map(([k]) => {
            const m = D.MODULOS.find(x => x.chave === k);
            return el('span', { class: 'chip forte', texto: m ? m.nome : k });
          }))
        ])
      ]),

      el('div', { class: 'cartao mt4' }, [
        el('p', { class: 'cartao-titulo', texto: 'Dependentes' }),
        p.dependentes ? frag(D.DEPENDENTES.slice(0, p.dependentes).map(d => el('div', { class: 'lista-item' }, [
          el('span', { class: 'quadro', style: 'width:36px;height:36px;border-radius:50%;background:var(--nevoa);color:var(--tinta-2);display:grid;place-items:center;font-weight:700;font-size:12px;flex:none', texto: iniciais(d.nome) }),
          el('span', { class: 'corpo' }, [
            el('p', { class: 'tit', texto: d.nome }),
            el('p', { class: 'meta', texto: `${d.parentesco}. ${idade(d.nascimento)}.` })
          ])
        ]))) : el('p', { class: 'cartao-apoio', texto: 'Você não tem dependentes cadastrados.' }),
        el('div', { class: 'faixa faixa-aviso mt4' }, [
          el('span', { class: 'negrito', texto: 'Ponto deixado em branco de propósito. ' }),
          'Quem consente pelo menor de idade, e até que idade o responsável enxerga o histórico, é assunto sensível demais para ser decidido pela equipe técnica. Aguarda definição.'
        ])
      ]),

      el('div', { class: 'cartao mt4' }, [
        el('p', { class: 'cartao-titulo', texto: 'Idioma' }),
        el('div', { class: 'campo-linha' }, [
          campo({ id: 'idioma-sistema', rotulo: 'Idioma do sistema', valor: 'pt-BR', opcoes: [
            { valor: 'pt-BR', rotulo: 'Português do Brasil' },
            { valor: 'es', rotulo: 'Espanhol' },
            { valor: 'en', rotulo: 'Inglês' }
          ] }),
          campo({ id: 'idioma-receita', rotulo: 'Idioma do documento da receita', valor: 'pt-BR', opcoes: [
            { valor: 'pt-BR', rotulo: 'Português do Brasil' },
            { valor: 'pt-AO', rotulo: 'Português de Angola' },
            { valor: 'es', rotulo: 'Espanhol' }
          ] })
        ]),
        el('p', { class: 'pequeno', texto: 'A interface segue em português do Brasil. O que muda de idioma é o documento emitido. A plataforma já suporta paciente estrangeiro sem CPF, com documento e país de emissão próprios.' })
      ])
    ]));
  };

  const editar = () => {
    const rascunho = { nome: p.nome, email: p.email, telefone: p.telefone };
    let corpo;
    modal({
      titulo: 'Editar meus dados',
      corpo: (corpo = el('div', {}, [
        campo({ id: 'ed-nome', rotulo: 'Nome completo', valor: rascunho.nome, obrigatorio: true, aoMudar: v => rascunho.nome = v }),
        campo({ id: 'ed-email', rotulo: 'E-mail', tipo: 'email', valor: rascunho.email, aoMudar: v => rascunho.email = v }),
        campo({ id: 'ed-tel', rotulo: 'Telefone', valor: rascunho.telefone, aoMudar: v => rascunho.telefone = v }),
        el('p', { class: 'pequeno', texto: 'Na integração real, isto chama PATCH update-patient usando o CPF como identificador.' })
      ])),
      acoes: (fechar) => [
        el('button', { class: 'btn btn-secundario', onclick: fechar }, ['Cancelar']),
        el('button', {
          class: 'btn btn-primario',
          onclick: async (ev) => {
            limparErros(corpo);
            if (!rascunho.nome.trim()) return marcarErro(corpo, 'ed-nome', 'O nome não pode ficar em branco.');
            if (rascunho.email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(rascunho.email)) return marcarErro(corpo, 'ed-email', 'Digite um e-mail válido.');
            const b = ev.currentTarget;
            ocupado(b, true, 'Salvando');
            try {
              await api.atualizarPaciente(p.cpf, rascunho);
              Object.assign(p, rascunho);
              fechar();
              aviso('Dados salvos.', 'ok');
              desenhar();
            } catch (e) {
              ocupado(b, false, 'Salvar');
              aviso(e.message, 'erro');
            }
          }
        }, ['Salvar'])
      ]
    });
  };

  desenhar();

  return frag([
    cabecalho('Minha conta', null, ['esboco', 'oficial']),
    palco
  ]);
}

/* ==========================================================================
   Saúde mental e NR-1. BLOQUEADA.
   ========================================================================== */

export function nr1(ir) {
  return frag([
    cabecalho('Orientação de saúde mental', null, ['reuniao']),
    el('div', { class: 'cartao' }, [
      el('div', { class: 'estado' }, [
        el('div', { class: 'icone', style: 'background:var(--erro-fraco);color:var(--erro)' }, [ico('escudo', 24)]),
        el('h3', { texto: 'Trilha bloqueada até uma decisão sobre proteção de dados' }),
        el('p', { texto: 'Esta trilha não foi construída, e a decisão não é técnica. Falta definir se o gestor da empresa recebe indicador agregado do time, ou informação sobre a pessoa. A resposta muda a modelagem do banco, o fluxo de consentimento e quem enxerga o quê. Construir antes disso significaria gravar dado sensível de saúde mental sem saber quem pode vê-lo.' })
      ])
    ]),
    el('div', { class: 'cartao mt4' }, [
      el('p', { class: 'cartao-titulo', texto: 'O que está desenhado e espera liberação' }),
      el('ul', { class: 'cartao-apoio', style: 'padding-left:18px;margin:0' }, [
        el('li', { texto: 'Acolhimento com personagem em voz, com perfil apaziguador e orientativo.' }),
        el('li', { texto: 'Quatro saídas: encerrar após o acolhimento, acolhimento imediato com psicólogo de plantão, formulário da norma, e agendamento com psicólogo.' }),
        el('li', { texto: 'Acompanhamento periódico com aviso de confidencialidade em destaque, antes da primeira pergunta.' }),
        el('li', { texto: 'Curva de evolução do próprio usuário, com recorte de um mês e de um ano.' }),
        el('li', { texto: 'Encaminhamento a psicólogo antes de psiquiatra, com a decisão sobre medicação ficando com o profissional.' })
      ]),
      el('div', { class: 'faixa faixa-info mt4', texto: 'Regra que vale enquanto isso: nenhum alerta individual entra no banco nem na API, e o painel da empresa exibe apenas indicador agregado, com corte mínimo de respondentes para ninguém ser identificado por eliminação.' })
    ]),
    el('button', { class: 'btn btn-secundario mt4', onclick: () => ir('#/inicio') }, [ico('volta'), 'Voltar ao início'])
  ]);
}

/* ==========================================================================
   Ajuda imediata. ORIGEM: Esboço. Aguarda validação clínica.
   ========================================================================== */

export function ajuda(ir) {
  return frag([
    cabecalho('Ajuda imediata', null, ['esboco']),
    el('div', { class: 'faixa faixa-aviso' }, [
      el('span', { class: 'negrito', texto: 'Aguardando validação clínica. ' }),
      'Esta tela e a lista de sinais abaixo nasceram de decisão de projeto, não de pedido do cliente. Elas têm implicação clínica direta e precisam de validação do responsável médico antes de qualquer apresentação ao cliente final. Os números de emergência também aguardam aprovação.'
    ]),
    el('div', { class: 'cartao mt4', style: 'border-color:#F3CFCA;background:var(--erro-fraco)' }, [
      el('p', { class: 'cartao-titulo', style: 'color:var(--erro)', texto: 'Se há risco de vida, ligue agora' }),
      el('p', { class: 'cartao-apoio', texto: 'Não espere atendimento por vídeo. O socorro presencial chega mais rápido.' }),
      el('div', { class: 'linha mt4' }, [
        el('a', { class: 'btn btn-primario', href: 'tel:192', style: 'background:var(--erro)' }, [ico('alerta'), 'Ligar 192, SAMU']),
        el('a', { class: 'btn btn-secundario', href: 'tel:188' }, ['Ligar 188, apoio emocional'])
      ])
    ]),
    el('div', { class: 'cartao mt4' }, [
      el('p', { class: 'cartao-titulo', texto: 'Sinais que pedem socorro imediato' }),
      el('ul', { class: 'cartao-apoio', style: 'padding-left:18px;margin:0' },
        D.SINAIS_GRAVIDADE.map(s => el('li', { texto: s })))
    ]),
    el('div', { class: 'cartao mt4' }, [
      el('p', { class: 'cartao-titulo', texto: 'Não é risco de vida' }),
      el('div', { class: 'pilha mt3' }, [
        el('button', { class: 'servico', onclick: () => ir('#/atendimento') }, [
          el('span', { class: 'quadro' }, [ico('video', 20)]),
          el('span', { class: 'corpo' }, [
            el('span', { class: 'tit', texto: 'Falar com a equipe médica agora' }),
            el('span', { class: 'des', texto: 'Atendimento por vídeo em minutos, sem sair de casa.' })
          ]),
          el('span', {}, [ico('avanca')])
        ]),
        el('button', { class: 'servico', onclick: () => ir('#/orientacao') }, [
          el('span', { class: 'quadro' }, [ico('conversa', 20)]),
          el('span', { class: 'corpo' }, [
            el('span', { class: 'tit', texto: 'Quero orientação sobre o que estou sentindo' }),
            el('span', { class: 'des', texto: 'Conte o que está acontecendo, por texto ou por voz.' })
          ]),
          el('span', {}, [ico('avanca')])
        ])
      ])
    ]),
    el('div', { class: 'cartao mt4' }, [
      el('p', { class: 'cartao-titulo', texto: 'Rede de atendimento do município' }),
      ...D.UNIDADES.map(u => el('div', { class: 'lista-item' }, [
        el('span', { class: 'corpo' }, [
          el('p', { class: 'tit', texto: u.nome }),
          el('p', { class: 'meta', texto: `${u.tipo}. ${u.endereco}. ${u.horario}.` })
        ])
      ]))
    ])
  ]);
}
