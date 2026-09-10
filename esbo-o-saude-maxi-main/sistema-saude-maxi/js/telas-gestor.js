/* ==========================================================================
   Telas do gestor.
   ORIGEM predominante: Oficial, o painel real do sistema em produção,
   mais o de para de plano por módulo, que veio da Reunião.
   ========================================================================== */

import * as api from './api.js';
import * as D from './dados.js';
import { estado, salvar, planosDoCliente, aplicarTema, avisar } from './estado.js';
import {
  el, frag, limpar, ico, carregando, vazio, erro, aviso, modal, confirmar,
  cabecalho, campo, marcarErro, limparErros, ocupado, dinheiro, data, idade,
  iniciais, indicador, origem, ambiente, proximaAcaoCartao
} from './ui.js';
import { blocoMax, proximaAcaoGestor, pendenciasGestor, periodoDoDia } from './max.js';

function assincrono(carregar, montar, textoCarga) {
  const caixa = el('div', {}, [carregando(textoCarga)]);
  const rodar = () => {
    limpar(caixa).appendChild(carregando(textoCarga));
    carregar()
      .then(d => { limpar(caixa).appendChild(montar(d, rodar)); })
      .catch(e => { limpar(caixa).appendChild(erro({ texto: e.message, aoTentar: rodar })); });
  };
  rodar();
  return caixa;
}

/* ==========================================================================
   Painel principal
   ========================================================================== */

export function painel(ir) {
  const I = D.INDICADORES;
  const maxHora = Math.max(...D.CONSULTAS_POR_HORA);
  const maxDia = Math.max(...D.CONSULTAS_POR_DIA.map(d => d.v));

  const n = pendenciasGestor();

  return frag([
    /* CAMADA DE EVOLUÇÃO: o gestor não recebe a mesma tela do paciente. Ele
       recebe um centro de operação: o que está acontecendo agora, o que está
       parado, e o caminho mais curto para resolver. Os doze indicadores e os
       gráficos continuam inteiros, logo abaixo. */
    ambiente(
      `${periodoDoDia()}, Danilo.`,
      `${estado.cliente.nome}. ${estado.cliente.tipo}, ${estado.cliente.populacao}. Período: setembro de 2026.`,
      [
        { icone: 'grupo',   valor: n.online,        rotulo: 'on-line agora' },
        { icone: 'video',   valor: n.esperando,     rotulo: 'em atendimento' },
        { icone: 'lista',   valor: n.semPagamento,  rotulo: 'sem pagamento marcado' },
        { icone: 'relogio', valor: D.INDICADORES.esperaProntoAtendimento, rotulo: 'de espera média' }
      ]
    ),

    proximaAcaoCartao(proximaAcaoGestor(), ir, 'Prioridade do dia'),

    blocoMax(ir),

    el('div', { class: 'linha' }, [
      el('h2', { texto: 'Indicadores do período' }),
      el('span', { class: 'espaco' }),
      el('button', { class: 'btn btn-secundario', onclick: () => aviso('Exportação em PDF é ação da plataforma existente.') }, [ico('documento'), 'Exportar PDF'])
    ]),

    el('div', { class: 'grade g4' }, [
      indicador('Pacientes on-line', I.pacientesOnline, 'Agora'),
      indicador('Consultas no período', I.consultas, 'Pronto atendimento e agendadas'),
      indicador('Agendamentos', I.agendamentos, `${I.taxaConfirmacao} por cento confirmados`),
      indicador('Satisfação', I.satisfacao + '%', 'Sobre ' + I.consultas + ' avaliações')
    ]),

    el('div', { class: 'grade g4 mt4' }, [
      indicador('Receitas emitidas', I.receitas, 'Pela integração com o Mevo'),
      indicador('Atestados', I.atestados, null),
      indicador('Exames solicitados', I.exames, null),
      indicador('Encaminhamentos', I.encaminhamentos, null)
    ]),

    el('div', { class: 'grade g3 mt4' }, [
      el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Espera no pronto atendimento' }),
        el('p', { class: 'val', style: 'font-size:28px;font-weight:800', texto: I.esperaProntoAtendimento }),
        el('p', { class: 'pequeno', texto: 'Compromisso contratual de 5 minutos cumprido. Este número vive aqui, no painel do gestor, e não na tela do paciente.' })
      ]),
      el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Espera no consultório' }),
        el('p', { class: 'val', style: 'font-size:28px;font-weight:800', texto: I.esperaConsultorio }),
        el('p', { class: 'pequeno', texto: 'Consultas agendadas, contadas do horário marcado até o início.' })
      ]),
      el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Duração média' }),
        el('p', { class: 'val', style: 'font-size:28px;font-weight:800', texto: I.duracaoMedia }),
        el('p', { class: 'pequeno', texto: 'Do início ao encerramento do atendimento.' })
      ])
    ]),

    el('div', { class: 'grade g2 mt4' }, [
      el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Consultas por hora do dia' }),
        el('div', { class: 'grafico-barras mt4' }, D.CONSULTAS_POR_HORA.map((v, h) =>
          el('div', {
            class: 'b' + (v === maxHora ? ' forte' : ''),
            style: `height:${Math.max(4, (v / maxHora) * 100)}%`,
            title: `${h}h, ${v} consultas`
          }, h % 4 === 0 ? [el('span', { texto: h + 'h' })] : [])
        )),
        el('p', { class: 'eixo-legenda', texto: 'Pico às ' + D.CONSULTAS_POR_HORA.indexOf(maxHora) + ' horas, com ' + maxHora + ' consultas.' })
      ]),
      el('div', { class: 'cartao' }, [
        el('p', { class: 'cartao-titulo', texto: 'Consultas por dia da semana' }),
        el('div', { class: 'grafico-barras mt4' }, D.CONSULTAS_POR_DIA.map(d =>
          el('div', {
            class: 'b' + (d.v === maxDia ? ' forte' : ''),
            style: `height:${(d.v / maxDia) * 100}%`,
            title: `${d.dia}, ${d.v} consultas`
          }, [el('span', { texto: d.dia })])
        )),
        el('p', { class: 'eixo-legenda', texto: 'A demanda cai no fim de semana, e é onde a escala custa mais caro.' })
      ])
    ]),

    el('div', { class: 'cartao mt4' }, [
      el('p', { class: 'cartao-titulo', texto: 'Faixa etária atendida' }),
      el('div', { class: 'mt3' }, D.FAIXA_ETARIA.map(f => el('div', { class: 'lista-item' }, [
        el('span', { class: 'corpo' }, [
          el('p', { class: 'tit', texto: f.faixa + ' anos' }),
          el('div', { class: 'barra-progresso mt2' }, [el('i', { style: `width:${(f.v / 118) * 100}%` })])
        ]),
        el('span', { class: 'negrito nowrap', texto: String(f.v) })
      ])))
    ]),

    el('div', { class: 'faixa faixa-info mt4' }, [
      el('span', { class: 'negrito', texto: 'Sobre o ranking de doenças. ' }),
      'A plataforma existente mostra um ranking de códigos de doença neste painel. Ele foi deixado de fora aqui de propósito: quando o período tem poucos registros, a linha identifica a pessoa por eliminação. Qualquer indicador agregado precisa de um corte mínimo de registros antes de aparecer.'
    ]),

    /* Rastreabilidade, no rodapé. Os números vieram do painel real. */
    el('div', { class: 'linha mt5' }, [origem('oficial'), origem('melhoria')])
  ]);
}

/* ==========================================================================
   Pacientes
   ========================================================================== */

export function pacientes(ir) {
  const filtros = { busca: '', status: 'TODOS', planoId: '', titularidade: '' };
  const lista = el('div');

  const desenhar = () => {
    limpar(lista).appendChild(assincrono(
      () => api.filtrarPacientes(filtros),
      (r) => r.results.length ? el('div', { class: 'cartao' }, [
        el('div', { class: 'linha', style: 'margin-bottom:12px' }, [
          el('p', { class: 'pequeno', texto: `${r.count} ${r.count === 1 ? 'paciente encontrado' : 'pacientes encontrados'}` }),
          el('span', { class: 'espaco' }),
          el('span', { class: 'pequeno', texto: 'Página 1, até 50 por página' })
        ]),
        el('div', { class: 'rolagem-x' }, [
          el('table', { class: 'tabela' }, [
            el('thead', {}, [el('tr', {}, ['Nome', 'CPF', 'Titularidade', 'Plano', 'Adesão', 'Etiquetas', 'Situação'].map(h => el('th', { texto: h })))]),
            el('tbody', {}, r.results.map(p => {
              const plano = estado.planos.find(x => x.id === p.planoId);
              return el('tr', { onclick: () => ir('#/g/paciente/' + p.id) }, [
                el('td', {}, [
                  el('div', { class: 'linha', style: 'gap:10px;flex-wrap:nowrap' }, [
                    el('span', { style: 'width:30px;height:30px;border-radius:50%;background:var(--marca-suave);color:var(--marca);display:grid;place-items:center;font-weight:700;font-size:11px;flex:none', texto: iniciais(p.nome) }),
                    el('span', {}, [
                      el('p', { class: 'celula-forte', texto: p.nome }),
                      el('p', { class: 'pequeno', texto: idade(p.nascimento) + (p.online ? ', on-line agora' : '') })
                    ])
                  ])
                ]),
                el('td', { class: 'nowrap', texto: p.cpf }),
                el('td', { texto: p.titular ? 'Titular' : 'Dependente' }),
                el('td', { texto: plano ? plano.nome : '' }),
                el('td', { class: 'nowrap', texto: data(p.adesao) }),
                el('td', {}, [el('div', { class: 'linha', style: 'gap:4px' }, p.tags.length ? p.tags.map(t => el('span', { class: 'chip', texto: t })) : [el('span', { class: 'pequeno', texto: 'sem etiqueta' })])]),
                el('td', {}, [el('span', { class: 'etiqueta ' + (p.status === 'ACTIVE' ? 'et-ok' : 'et-neutra'), texto: p.status === 'ACTIVE' ? 'Ativo' : 'Inativo' })])
              ]);
            }))
          ])
        ])
      ]) : vazio({
        titulo: 'Nenhum paciente encontrado',
        texto: 'Nenhum resultado para os filtros aplicados. Limpe os filtros para ver a base inteira.',
        acao: el('button', { class: 'btn btn-secundario', onclick: () => { filtros.busca = ''; filtros.status = 'TODOS'; filtros.planoId = ''; filtros.titularidade = ''; ir('#/g/pacientes'); } }, ['Limpar filtros'])
      }),
      'Consultando a base de pacientes'
    ));
  };

  desenhar();

  return frag([
    cabecalho('Pacientes',
      'Base lida da plataforma existente por filter-patients. A camada nova não mantém cadastro próprio.',
      ['oficial'],
      [el('button', { class: 'btn btn-primario', onclick: () => novoPaciente(desenhar) }, [ico('pessoa'), 'Novo paciente'])]),

    el('div', { class: 'cartao' }, [
      el('div', { class: 'linha' }, [
        el('div', { style: 'flex:1;min-width:200px' }, [
          el('input', { type: 'search', placeholder: 'Buscar por nome, CPF ou e-mail', 'aria-label': 'Buscar paciente', oninput: (e) => { filtros.busca = e.target.value; clearTimeout(desenhar._t); desenhar._t = setTimeout(desenhar, 320); } })
        ]),
        el('select', { 'aria-label': 'Situação', onchange: (e) => { filtros.status = e.target.value; desenhar(); } }, [
          el('option', { value: 'TODOS' }, ['Todos']),
          el('option', { value: 'ACTIVE' }, ['Apenas ativos']),
          el('option', { value: 'INACTIVE' }, ['Apenas inativos'])
        ]),
        el('select', { 'aria-label': 'Titularidade', onchange: (e) => { filtros.titularidade = e.target.value; desenhar(); } }, [
          el('option', { value: '' }, ['Titulares e dependentes']),
          el('option', { value: 'titular' }, ['Apenas titulares']),
          el('option', { value: 'dependente' }, ['Apenas dependentes'])
        ]),
        el('select', { 'aria-label': 'Plano', onchange: (e) => { filtros.planoId = e.target.value; desenhar(); } }, [
          el('option', { value: '' }, ['Todos os planos']),
          ...planosDoCliente().map(p => el('option', { value: p.id }, [p.nome]))
        ])
      ])
    ]),
    el('div', { class: 'mt4' }, [lista])
  ]);
}

function novoPaciente(aoSalvar) {
  const r = { name: '', cpf: '', email: '', phone: '', birth_date: '', plan_id: planosDoCliente()[0] ? planosDoCliente()[0].id : 1, is_foreigner: false };
  let corpo;
  modal({
    titulo: 'Novo paciente',
    corpo: (corpo = el('div', {}, [
      campo({ id: 'np-nome', rotulo: 'Nome completo', obrigatorio: true, aoMudar: v => r.name = v }),
      el('div', { class: 'campo-linha' }, [
        campo({ id: 'np-cpf', rotulo: 'CPF', obrigatorio: true, placeholder: '000.000.000-00', aoMudar: v => r.cpf = v }),
        campo({ id: 'np-nasc', rotulo: 'Nascimento', tipo: 'date', aoMudar: v => r.birth_date = v })
      ]),
      el('div', { class: 'campo-linha' }, [
        campo({ id: 'np-email', rotulo: 'E-mail', tipo: 'email', aoMudar: v => r.email = v }),
        campo({ id: 'np-tel', rotulo: 'Telefone', aoMudar: v => r.phone = v })
      ]),
      campo({ id: 'np-plano', rotulo: 'Plano', valor: r.plan_id, opcoes: planosDoCliente().map(p => ({ valor: p.id, rotulo: p.nome })), aoMudar: v => r.plan_id = Number(v) }),
      el('div', { class: 'faixa faixa-info', texto: 'A plataforma aceita paciente estrangeiro sem CPF, com documento e país de emissão próprios, e envia esses campos à Mevo na receita digital.' })
    ])),
    acoes: (fechar) => [
      el('button', { class: 'btn btn-secundario', onclick: fechar }, ['Cancelar']),
      el('button', {
        class: 'btn btn-primario',
        onclick: async (ev) => {
          limparErros(corpo);
          let ok = true;
          if (!r.name.trim()) { marcarErro(corpo, 'np-nome', 'O nome é obrigatório.'); ok = false; }
          if (!r.cpf.trim()) { marcarErro(corpo, 'np-cpf', 'O CPF é obrigatório, salvo em paciente estrangeiro.'); ok = false; }
          if (!ok) return;
          const b = ev.currentTarget;
          ocupado(b, true, 'Criando');
          setTimeout(() => {
            estado.pacientes.push({
              id: Date.now() % 100000, nome: r.name, cpf: r.cpf, nascimento: r.birth_date || '1990-01-01',
              telefone: r.phone, email: r.email, titular: true, planoId: r.plan_id, cliente: estado.cliente.id,
              status: 'ACTIVE', online: false, adesao: new Date().toISOString().slice(0, 10),
              expiracao: '2027-12-31', tags: [], cidade: '', estado: '', dependentes: 0, consultas: 0
            });
            salvar();
            fechar();
            aviso('Paciente criado.', 'ok');
            aoSalvar();
          }, 700);
        }
      }, ['Criar paciente'])
    ]
  });
}

/* Detalhe do paciente, com edição e salvamento */
export function pacienteDetalhe(ir, id) {
  const palco = el('div');

  const desenhar = () => {
    limpar(palco).appendChild(assincrono(
      () => api.buscarPaciente(id),
      (p) => {
        if (!p) return vazio({ titulo: 'Paciente não encontrado', texto: 'Ele pode ter sido removido ao zerar os dados de demonstração.' });
        const plano = estado.planos.find(x => x.id === p.planoId);
        const dele = estado.consultas.filter(c => c.pacienteId === p.id);

        return frag([
          el('div', { class: 'cartao' }, [
            el('div', { class: 'linha' }, [
              el('span', { style: 'width:52px;height:52px;border-radius:50%;background:var(--marca-suave);color:var(--marca);display:grid;place-items:center;font-weight:800;font-size:16px;flex:none', texto: iniciais(p.nome) }),
              el('div', { style: 'flex:1;min-width:180px' }, [
                el('h2', { texto: p.nome }),
                el('p', { class: 'pequeno', texto: `${p.cpf}. ${idade(p.nascimento)}. ${p.titular ? 'Titular' : 'Dependente'}.` })
              ]),
              el('span', { class: 'etiqueta ' + (p.status === 'ACTIVE' ? 'et-ok' : 'et-neutra'), texto: p.status === 'ACTIVE' ? 'Ativo' : 'Inativo' })
            ]),
            el('div', { class: 'linha mt4' }, [
              el('button', { class: 'btn btn-primario', onclick: () => editar(p) }, ['Editar dados']),
              el('button', { class: 'btn btn-secundario', onclick: () => etiquetas(p) }, ['Etiquetas']),
              el('button', {
                class: 'btn ' + (p.status === 'ACTIVE' ? 'btn-perigo' : 'btn-secundario'),
                onclick: () => alternar(p)
              }, [p.status === 'ACTIVE' ? 'Inativar paciente' : 'Reativar paciente'])
            ])
          ]),

          el('div', { class: 'grade g2 mt4' }, [
            el('div', { class: 'cartao' }, [
              el('p', { class: 'cartao-titulo', texto: 'Contato e cadastro' }),
              linha('E-mail', p.email || 'não informado'),
              linha('Telefone', p.telefone || 'não informado'),
              linha('Cidade', p.cidade ? `${p.cidade}, ${p.estado}` : 'não informada'),
              linha('Dependentes', String(p.dependentes))
            ]),
            el('div', { class: 'cartao' }, [
              el('p', { class: 'cartao-titulo', texto: 'Plano' }),
              linha('Plano', plano ? plano.nome : 'sem plano'),
              linha('Adesão', data(p.adesao)),
              linha('Expiração', data(p.expiracao)),
              linha('Máximo de dependentes', plano ? String(plano.maxDependentes) : '0'),
              el('div', { class: 'linha mt3' }, plano ? Object.entries(plano.modulos).filter(([, v]) => v).map(([k]) => {
                const m = D.MODULOS.find(x => x.chave === k);
                return el('span', { class: 'chip forte', texto: m ? m.nome : k });
              }) : [])
            ])
          ]),

          el('div', { class: 'cartao mt4' }, [
            el('p', { class: 'cartao-titulo', texto: 'Consultas deste paciente' }),
            dele.length ? el('div', { class: 'rolagem-x' }, [
              el('table', { class: 'tabela' }, [
                el('thead', {}, [el('tr', {}, ['Código', 'Quando', 'Especialidade', 'Situação', 'Pagamento'].map(h => el('th', { texto: h })))]),
                el('tbody', {}, dele.map(c => {
                  const s = D.STATUS_CONSULTA[c.status] || { rot: c.status, cor: 'et-neutra' };
                  return el('tr', {}, [
                    el('td', { class: 'celula-forte nowrap', texto: c.codigo }),
                    el('td', { class: 'nowrap', texto: data(c.agendadaPara, true) }),
                    el('td', { texto: c.especialidade }),
                    el('td', {}, [el('span', { class: 'etiqueta ' + s.cor, texto: s.rot })]),
                    el('td', {}, [el('span', { class: 'etiqueta ' + (c.pago ? 'et-ok' : 'et-neutra'), texto: c.pago ? 'Pago' : 'Em aberto' })])
                  ]);
                }))
              ])
            ]) : el('p', { class: 'cartao-apoio', texto: 'Nenhuma consulta registrada para este paciente.' })
          ]),

          el('button', { class: 'btn btn-secundario mt4', onclick: () => ir('#/g/pacientes') }, [ico('volta'), 'Voltar para a lista'])
        ]);
      },
      'Carregando o paciente'
    ));
  };

  const linha = (rot, val) => el('div', { class: 'lista-item' }, [
    el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: rot }), el('p', { class: 'tit', texto: val })])
  ]);

  const editar = (p) => {
    const r = { nome: p.nome, email: p.email, telefone: p.telefone, planoId: p.planoId };
    let corpo;
    modal({
      titulo: 'Editar paciente',
      corpo: (corpo = el('div', {}, [
        campo({ id: 'ep-nome', rotulo: 'Nome completo', valor: r.nome, obrigatorio: true, aoMudar: v => r.nome = v }),
        el('div', { class: 'campo-linha' }, [
          campo({ id: 'ep-email', rotulo: 'E-mail', tipo: 'email', valor: r.email, aoMudar: v => r.email = v }),
          campo({ id: 'ep-tel', rotulo: 'Telefone', valor: r.telefone, aoMudar: v => r.telefone = v })
        ]),
        campo({ id: 'ep-plano', rotulo: 'Plano', valor: r.planoId, opcoes: planosDoCliente().map(x => ({ valor: x.id, rotulo: x.nome })), aoMudar: v => r.planoId = Number(v) }),
        el('p', { class: 'pequeno', texto: 'Na integração real, isto chama PATCH update-patient usando o CPF como identificador. Enviar plano vazio remove o plano do paciente.' })
      ])),
      acoes: (fechar) => [
        el('button', { class: 'btn btn-secundario', onclick: fechar }, ['Cancelar']),
        el('button', {
          class: 'btn btn-primario',
          onclick: async (ev) => {
            limparErros(corpo);
            if (!r.nome.trim()) return marcarErro(corpo, 'ep-nome', 'O nome não pode ficar em branco.');
            if (r.email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(r.email)) return marcarErro(corpo, 'ep-email', 'Digite um e-mail válido.');
            const b = ev.currentTarget;
            ocupado(b, true, 'Salvando');
            try {
              await api.atualizarPaciente(p.cpf, r);
              fechar();
              aviso('Paciente atualizado.', 'ok');
              desenhar();
            } catch (e) { ocupado(b, false, 'Salvar'); aviso(e.message, 'erro'); }
          }
        }, ['Salvar'])
      ]
    });
  };

  const etiquetas = (p) => {
    const atuais = new Set(p.tags);
    const todas = ['Hipertensão', 'Diabetes', 'Asma', 'Idoso', 'Prioridade', 'NR-1', 'Gestante'];
    const corpo = el('div', {}, [
      el('p', { class: 'cartao-apoio', texto: 'Etiquetas ajudam a segmentar a base. A plataforma tem endpoints próprios para criar, alterar, remover e listar etiquetas.' }),
      el('div', { class: 'linha mt4' }, todas.map(t => {
        const b = el('button', {
          class: 'opcao', 'aria-pressed': String(atuais.has(t)),
          onclick: () => {
            if (atuais.has(t)) atuais.delete(t); else atuais.add(t);
            b.setAttribute('aria-pressed', String(atuais.has(t)));
          }
        }, [t]);
        return b;
      }))
    ]);
    modal({
      titulo: 'Etiquetas do paciente',
      corpo,
      acoes: (fechar) => [
        el('button', { class: 'btn btn-secundario', onclick: fechar }, ['Cancelar']),
        el('button', {
          class: 'btn btn-primario',
          onclick: async (ev) => {
            const b = ev.currentTarget;
            ocupado(b, true, 'Salvando');
            try {
              await api.atualizarPaciente(p.cpf, { tags: [...atuais] });
              fechar(); aviso('Etiquetas atualizadas.', 'ok'); desenhar();
            } catch (e) { ocupado(b, false, 'Salvar'); aviso(e.message, 'erro'); }
          }
        }, ['Salvar'])
      ]
    });
  };

  const alternar = async (p) => {
    const ativando = p.status !== 'ACTIVE';
    const ok = await confirmar({
      titulo: ativando ? 'Reativar o paciente?' : 'Inativar o paciente?',
      texto: ativando
        ? `${p.nome} volta a ter acesso ao sistema.`
        : `${p.nome} perde o acesso e é marcado como fora do ar. A plataforma exige a chave "Permitir que a clínica inative pacientes", que fica num nível de administração acima do gestor da clínica.`,
      rotuloOk: ativando ? 'Reativar' : 'Inativar',
      perigoso: !ativando
    });
    if (!ok) return;
    try {
      await api.alternarStatusPaciente(p.cpf, ativando);
      aviso(ativando ? 'Paciente reativado.' : 'Paciente inativado.', 'ok');
      desenhar();
    } catch (e) { aviso(e.message, 'erro'); }
  };

  desenhar();

  return frag([
    cabecalho('Ficha do paciente', null, ['oficial']),
    palco
  ]);
}

/* ==========================================================================
   Consultas agendadas
   ========================================================================== */

export function consultasGestor(ir) {
  const filtros = { busca: '', status: '' };
  const lista = el('div');

  const desenhar = () => {
    limpar(lista).appendChild(assincrono(
      () => api.historicoConsultas(filtros),
      (r) => r.results.length ? el('div', { class: 'cartao' }, [
        el('div', { class: 'rolagem-x' }, [
          el('table', { class: 'tabela' }, [
            el('thead', {}, [el('tr', {}, ['Código', 'Paciente', 'Quando', 'Especialidade', 'Profissional', 'Situação', 'Pagamento', 'Ações'].map(h => el('th', { texto: h })))]),
            el('tbody', {}, r.results.map(c => {
              const s = D.STATUS_CONSULTA[c.status] || { rot: c.status, cor: 'et-neutra' };
              const pac = estado.pacientes.find(p => p.id === c.pacienteId);
              return el('tr', {}, [
                el('td', { class: 'celula-forte nowrap', texto: c.codigo }),
                el('td', { texto: pac ? pac.nome : 'Paciente removido' }),
                el('td', { class: 'nowrap', texto: data(c.agendadaPara, true) }),
                el('td', { texto: c.especialidade }),
                el('td', { texto: c.medico || 'A definir' }),
                el('td', {}, [el('span', { class: 'etiqueta ' + s.cor, texto: s.rot })]),
                el('td', {}, [el('span', { class: 'etiqueta ' + (c.pago ? 'et-ok' : 'et-neutra'), texto: c.pago ? 'Pago' : 'Em aberto' })]),
                el('td', {}, [
                  el('button', {
                    class: 'btn btn-secundario btn-pequeno',
                    onclick: async (ev) => {
                      ev.stopPropagation();
                      const b = ev.currentTarget;
                      ocupado(b, true, 'Marcando');
                      try {
                        await api.marcarPagamento(c.codigo, !c.pago);
                        aviso(`Consulta ${c.codigo} marcada como ${!c.pago ? 'paga' : 'em aberto'}.`, 'ok');
                        desenhar();
                      } catch (e) { ocupado(b, false, 'Marcar'); aviso(e.message, 'erro'); }
                    }
                  }, [c.pago ? 'Marcar em aberto' : 'Marcar paga'])
                ])
              ]);
            }))
          ])
        ])
      ]) : vazio({ titulo: 'Nenhuma consulta no período', texto: 'Ajuste os filtros ou aguarde novos atendimentos.' }),
      'Consultando agenda'
    ));
  };

  desenhar();

  return frag([
    cabecalho('Consultas',
      'Marcar pagamento aqui chama update-payment-status. É este endpoint que permite cobrar por fora e apenas registrar a marcação na plataforma.',
      ['oficial', 'reuniao']),
    el('div', { class: 'cartao' }, [
      el('div', { class: 'linha' }, [
        el('div', { style: 'flex:1;min-width:200px' }, [
          el('input', { type: 'search', placeholder: 'Buscar por código, especialidade ou profissional', 'aria-label': 'Buscar', oninput: (e) => { filtros.busca = e.target.value; clearTimeout(desenhar._t); desenhar._t = setTimeout(desenhar, 320); } })
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

/* ==========================================================================
   Planos e módulos. O de para que a reunião pediu.
   ========================================================================== */

export function planos(ir) {
  const palco = el('div');

  const desenhar = () => {
    const lista = planosDoCliente();
    limpar(palco).appendChild(frag([
      el('div', { class: 'faixa faixa-info' }, [
        el('span', { class: 'negrito', texto: 'Por que esta tela existe. ' }),
        'O plano da plataforma existente tem quatro campos e só: identificador, nome, clínica e máximo de dependentes. Não existe campo de módulo lá. O de para entre plano e módulo é da camada nova, e é ele que decide o que o paciente vê ao entrar.'
      ]),

      el('div', { class: 'cartao mt4' }, [
        el('p', { class: 'cartao-titulo', texto: 'De para entre plano e módulo' }),
        el('p', { class: 'cartao-apoio', texto: 'Marque o que cada plano libera. A mudança vale na próxima entrada do usuário e não exige nova publicação.' }),
        el('div', { class: 'rolagem-x mt4' }, [
          el('table', { class: 'tabela' }, [
            el('thead', {}, [el('tr', {}, [
              el('th', { texto: 'Plano' }),
              el('th', { texto: 'Máx. dependentes' }),
              ...D.MODULOS.map(m => el('th', { texto: m.nome }))
            ])]),
            el('tbody', {}, lista.map(p => el('tr', {}, [
              el('td', { class: 'celula-forte', texto: p.nome }),
              el('td', { texto: String(p.maxDependentes) }),
              ...D.MODULOS.map(m => el('td', {}, [
                el('button', {
                  class: 'chave',
                  'aria-checked': String(!!p.modulos[m.chave]),
                  'aria-label': `${m.nome} no plano ${p.nome}`,
                  disabled: m.chave === 'nr1' ? true : null,
                  title: m.chave === 'nr1' ? 'Bloqueado até a decisão sobre proteção de dados' : m.nota,
                  onclick: async (ev) => {
                    const b = ev.currentTarget;
                    const novo = { ...p.modulos, [m.chave]: !p.modulos[m.chave] };
                    b.setAttribute('aria-checked', String(novo[m.chave]));
                    try {
                      await api.salvarModulosDoPlano(p.id, novo);
                      aviso(`${m.nome} ${novo[m.chave] ? 'liberado' : 'bloqueado'} no plano ${p.nome}.`, 'ok');
                      avisar();
                    } catch (e) {
                      b.setAttribute('aria-checked', String(!novo[m.chave]));
                      aviso(e.message, 'erro');
                    }
                  }
                }, [el('span', { class: 'bolinha' })])
              ]))
            ])))
          ])
        ])
      ]),

      el('div', { class: 'cartao mt4' }, [
        el('p', { class: 'cartao-titulo', texto: 'Regras que valem sobre o de para' }),
        ...D.MODULOS.map(m => el('div', { class: 'lista-item' }, [
          el('span', { class: 'corpo' }, [
            el('p', { class: 'tit', texto: m.nome }),
            el('p', { class: 'meta', texto: m.nota })
          ]),
          el('span', { class: 'etiqueta ' + (m.padrao ? 'et-ok' : 'et-neutra'), texto: m.padrao ? 'Em todos os planos' : 'Contratado à parte' })
        ])),
        el('div', { class: 'faixa faixa-aviso mt4' }, [
          el('span', { class: 'negrito', texto: 'Regra dura de município. ' }),
          'Em cliente do tipo município, o agendamento não é oferecido ao paciente mesmo quando o módulo está ligado. A marcação passa pelo núcleo de regulação, para não desviar a agenda de especialista e quebrar o orçamento da rede.'
        ])
      ]),

      el('div', { class: 'cartao mt4' }, [
        el('p', { class: 'cartao-titulo', texto: 'Planos deste cliente' }),
        el('div', { class: 'rolagem-x' }, [
          el('table', { class: 'tabela' }, [
            el('thead', {}, [el('tr', {}, ['ID', 'Nome do plano', 'Cliente', 'Máx. dependentes', 'Pacientes'].map(h => el('th', { texto: h })))]),
            el('tbody', {}, lista.map(p => el('tr', {}, [
              el('td', { texto: String(p.id) }),
              el('td', { class: 'celula-forte', texto: p.nome }),
              el('td', { texto: estado.cliente.nome }),
              el('td', { texto: String(p.maxDependentes) }),
              el('td', { texto: String(estado.pacientes.filter(x => x.planoId === p.id).length) })
            ])))
          ])
        ])
      ])
    ]));
  };

  desenhar();

  return frag([
    cabecalho('Planos e módulos',
      'Aqui se decide o que cada plano libera na vitrine do paciente.',
      ['reuniao', 'novo']),
    palco
  ]);
}

/* ==========================================================================
   Identidade visual. White label com prévia ao vivo.
   ========================================================================== */

export function identidade(ir) {
  const c = estado.cliente;
  const rascunho = { nome: c.nome, cor: c.cor, subdominio: c.subdominio, saudacao: c.saudacao };
  const palco = el('div');

  const previa = () => el('div', { class: 'cartao', style: 'padding:0;overflow:hidden' }, [
    el('div', { style: `background:linear-gradient(135deg, ${rascunho.cor} 0%, color-mix(in srgb, ${rascunho.cor} 62%, #22E0B0) 100%);color:#fff;padding:28px` }, [
      el('div', { class: 'linha', style: 'gap:12px' }, [
        el('span', { style: 'width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-weight:800', texto: iniciais(rascunho.nome) }),
        el('span', {}, [
          el('p', { class: 'negrito', texto: rascunho.nome }),
          el('p', { style: 'font-size:12px;opacity:.85', texto: rascunho.subdominio })
        ])
      ]),
      el('p', { class: 'mt4', style: 'font-size:19px;font-weight:700', texto: rascunho.saudacao })
    ]),
    el('div', { style: 'padding:20px' }, [
      el('button', { class: 'btn btn-primario', style: `background:${rascunho.cor}` }, ['Entrar']),
      el('p', { class: 'pequeno mt3', texto: 'Prévia da tela de entrada com a identidade deste cliente.' })
    ])
  ]);

  const desenhar = () => {
    limpar(palco).appendChild(frag([
      el('div', { class: 'faixa faixa-info' }, [
        el('span', { class: 'negrito', texto: 'Uma cor, não uma tabela de cores. ' }),
        'No sistema oficial cada cliente define uma única variável, e todo o resto deriva dela por cálculo. A camada nova segue o mesmo modelo, porque ele já está validado em produção e não obriga ninguém a escolher dez tons.'
      ]),

      el('div', { class: 'grade g2 mt4' }, [
        el('div', { class: 'cartao' }, [
          el('p', { class: 'cartao-titulo', texto: 'Identidade do cliente' }),
          campo({ id: 'id-nome', rotulo: 'Nome exibido', valor: rascunho.nome, aoMudar: v => { rascunho.nome = v; atualizarPrevia(); } }),
          el('div', { class: 'campo', dataset: { campo: 'id-cor' } }, [
            el('label', { for: 'id-cor', texto: 'Cor da plataforma' }),
            el('div', { class: 'linha', style: 'flex-wrap:nowrap' }, [
              el('input', { id: 'id-cor', type: 'color', value: rascunho.cor, style: 'width:56px;padding:2px', oninput: (e) => { rascunho.cor = e.target.value; sincronizarTexto(); atualizarPrevia(); } }),
              el('input', { id: 'id-cor-txt', type: 'text', value: rascunho.cor, oninput: (e) => { rascunho.cor = e.target.value; atualizarPrevia(); } })
            ]),
            el('p', { class: 'dica', texto: 'Tudo o mais deriva desta cor: gradiente, superfície escura, linha e fundo suave.' })
          ]),
          campo({ id: 'id-sub', rotulo: 'Endereço do cliente', valor: rascunho.subdominio, dica: 'O domínio próprio precisa estar registrado e gerenciado no Cloudflare antes da integração.', aoMudar: v => { rascunho.subdominio = v; atualizarPrevia(); } }),
          campo({ id: 'id-saud', rotulo: 'Saudação na entrada e no atendimento', valor: rascunho.saudacao, aoMudar: v => { rascunho.saudacao = v; atualizarPrevia(); } }),
          el('div', { class: 'linha mt4' }, [
            el('button', {
              class: 'btn btn-primario',
              onclick: (ev) => {
                const b = ev.currentTarget;
                ocupado(b, true, 'Salvando');
                setTimeout(() => {
                  Object.assign(estado.identidade[c.id], { nome: rascunho.nome, cor: rascunho.cor, subdominio: rascunho.subdominio, saudacao: rascunho.saudacao });
                  estado.cliente = estado.identidade[c.id];
                  aplicarTema();
                  salvar();
                  avisar();
                  aviso('Identidade salva. O tema mudou em todo o sistema.', 'ok');
                  desenhar();
                }, 700);
              }
            }, ['Salvar identidade']),
            el('button', { class: 'btn btn-secundario', onclick: () => { Object.assign(rascunho, { nome: c.nome, cor: c.cor, subdominio: c.subdominio, saudacao: c.saudacao }); desenhar(); } }, ['Desfazer'])
          ])
        ]),

        el('div', {}, [
          el('p', { class: 'cartao-titulo', style: 'margin-bottom:12px', texto: 'Prévia ao vivo' }),
          el('div', { id: 'previa-caixa' }, [previa()])
        ])
      ]),

      el('div', { class: 'cartao mt4' }, [
        el('p', { class: 'cartao-titulo', texto: 'Arquivos de imagem' }),
        ...[
          ['Logotipo', 'Aparece no cabeçalho e na entrada'],
          ['Favicon', 'Ícone da aba do navegador'],
          ['Imagem de fundo da entrada', 'Pode ser ligada ou desligada por cliente'],
          ['Imagem de fundo da videochamada', 'É o plano de fundo que aparece atrás do profissional']
        ].map(([t, s]) => el('div', { class: 'lista-item' }, [
          el('span', { class: 'corpo' }, [el('p', { class: 'tit', texto: t }), el('p', { class: 'meta', texto: s })]),
          el('button', { class: 'btn btn-secundario btn-pequeno', onclick: () => aviso('Envio de arquivo entra na integração com a plataforma existente.') }, ['Enviar arquivo'])
        ])),
        el('div', { class: 'faixa faixa-info mt4', texto: 'A imagem de fundo da videochamada é campo do sistema, e não arte solta. É por ela que entra o layout de fundo de consultório com o brasão do município.' })
      ]),

      el('div', { class: 'cartao mt4' }, [
        el('p', { class: 'cartao-titulo', texto: 'Chaves de exibição' }),
        chave('Habilitar imagem de fundo da entrada', 'Quando desligada, a entrada usa apenas a cor da plataforma.', true),
        chave('Exibir WhatsApp na entrada', 'Três opções no sistema oficial: WhatsApp da clínica, WhatsApp gerido pelo sistema, ou não exibir.', true),
        chave('Servidor de e-mail próprio', 'Cada cliente pode ter o próprio servidor de envio, com criptografia.', false)
      ])
    ]));
  };

  const chave = (rot, sub, ligada) => el('div', { class: 'interruptor' }, [
    el('div', {}, [el('p', { class: 'rot', texto: rot }), el('p', { class: 'sub', texto: sub })]),
    el('button', {
      class: 'chave', 'aria-checked': String(ligada), 'aria-label': rot,
      onclick: (ev) => {
        const b = ev.currentTarget;
        const novo = b.getAttribute('aria-checked') !== 'true';
        b.setAttribute('aria-checked', String(novo));
        aviso(`${rot}: ${novo ? 'ligada' : 'desligada'}.`);
      }
    }, [el('span', { class: 'bolinha' })])
  ]);

  const atualizarPrevia = () => {
    const caixa = document.getElementById('previa-caixa');
    if (caixa) limpar(caixa).appendChild(previa());
  };
  const sincronizarTexto = () => {
    const t = document.getElementById('id-cor-txt');
    if (t) t.value = rascunho.cor;
  };

  desenhar();

  return frag([
    cabecalho('Identidade visual',
      'Um cliente é uma cor, mais os arquivos de imagem, mais o endereço. Nada além disso.',
      ['oficial', 'reuniao']),
    palco
  ]);
}

/* ==========================================================================
   Integração. O catálogo real, com a situação de cada ponto.
   ========================================================================== */

export function integracao(ir) {
  const cor = { verde: 'et-ok', azul: 'et-marca', amarelo: 'et-aviso', vermelho: 'et-erro' };
  const rot = { verde: 'Já disponível', azul: 'Simulado', amarelo: 'Integração futura', vermelho: 'Bloqueada' };
  const conta = (s) => api.CATALOGO.filter(x => x.sit === s).length;

  return frag([
    cabecalho('Integração',
      'Cada ponto onde a camada nova conversa com a plataforma existente, e o que falta em cada um.',
      ['oficial']),

    el('div', { class: 'grade g4' }, [
      indicador('Já disponível', conta('verde'), 'Documentado e pronto para usar'),
      indicador('Simulado', conta('azul'), 'Da camada nova, sem endpoint do fornecedor'),
      indicador('Integração futura', conta('amarelo'), 'Depende de definição'),
      indicador('Bloqueada', conta('vermelho'), 'Depende de decisão do cliente')
    ]),

    el('div', { class: 'cartao mt4' }, [
      el('p', { class: 'cartao-titulo', texto: 'Situação da conexão' }),
      el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Endereço da API' }), el('p', { class: 'tit', style: 'word-break:break-all', texto: api.BASE_REAL })]), el('span', { class: 'etiqueta et-ok', texto: 'Documentada' })]),
      el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Autenticação' }), el('p', { class: 'tit', texto: 'Token de serviço da clínica, no cabeçalho Authorization' })]), el('span', { class: 'etiqueta et-ok', texto: 'Confirmada' })]),
      el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Aviso de eventos' }), el('p', { class: 'tit', texto: 'Não existe. A plataforma nunca chama a camada nova.' })]), el('span', { class: 'etiqueta et-erro', texto: 'Ausente' })]),
      el('div', { class: 'lista-item' }, [el('span', { class: 'corpo' }, [el('p', { class: 'meta', texto: 'Prescrição' }), el('p', { class: 'tit', texto: 'Não existe endpoint. A receita sai pela integração com o Mevo dentro do atendimento.' })]), el('span', { class: 'etiqueta et-erro', texto: 'Ausente' })])
    ]),

    el('div', { class: 'faixa faixa-erro mt4' }, [
      el('span', { class: 'negrito', texto: 'A consequência prática. ' }),
      'Sem aviso de eventos e sem endpoint de prescrição, a camada nova não fica sabendo que uma receita foi emitida. Ela precisa consultar o histórico de consultas de tempos em tempos, e ainda assim não recebe o conteúdo da receita. Enquanto isso não se resolver, a foto enviada pelo paciente é o caminho que funciona na farmácia popular.'
    ]),

    el('div', { class: 'cartao mt4' }, [
      el('p', { class: 'cartao-titulo', texto: 'Pontos de integração' }),
      el('div', { class: 'rolagem-x' }, [
        el('table', { class: 'tabela' }, [
          el('thead', {}, [el('tr', {}, ['Situação', 'Método', 'Rota', 'Função na camada nova', 'Observação'].map(h => el('th', { texto: h })))]),
          el('tbody', {}, api.CATALOGO.map(c => el('tr', {}, [
            el('td', {}, [el('span', { class: 'etiqueta ' + cor[c.sit], texto: rot[c.sit] })]),
            el('td', { class: 'nowrap', texto: c.metodo || '' }),
            el('td', { class: 'nowrap celula-forte', texto: c.rota }),
            el('td', { class: 'nowrap', texto: c.fn }),
            el('td', { texto: c.nota })
          ])))
        ])
      ])
    ]),

    el('div', { class: 'cartao mt4' }, [
      el('p', { class: 'cartao-titulo', texto: 'Como ligar de verdade' }),
      el('p', { class: 'cartao-apoio', texto: 'A camada de serviço fica em app/js/api.js. Cada função tem a rota real no comentário e devolve o mesmo formato que a plataforma devolve. Trocar simulação por chamada real é substituir o corpo da função pela função requisicao, que já está escrita no fim do arquivo. Nenhuma tela precisa mudar.' }),
      el('div', { class: 'linha mt3' }, [
        el('span', { class: 'chip', texto: 'app/js/api.js' }),
        el('span', { class: 'chip', texto: 'requisicao(rota, opcoes)' }),
        el('span', { class: 'chip', texto: 'Authorization: Bearer' })
      ])
    ])
  ]);
}
