/* ==========================================================================
   MAX, o assistente do Saúde Maxi.

   Camada de evolução da experiência. Não substitui nenhuma tela existente:
   mora por cima delas, num botão flutuante e num painel lateral, e conhece
   a rota em que a pessoa está.

   REGRAS QUE O MAX NÃO PODE QUEBRAR, herdadas do projeto:
   regra 3, orienta e encaminha, nunca conclui sobre doença. As palavras
           diagnóstico e pré-diagnóstico não aparecem.
   regra 4, nunca sugere troca de medicamento.
   regra 6, no perfil de gestor não fala de pessoa em saúde mental.
   regra 8, sem travessão.

   NOME: provisório. O projeto ainda não decidiu. Ver SUGESTOES_DE_NOME e a
   pendência Q40 em ESTADO-ATUAL.md.

   COMO LIGAR NUMA IA DE VERDADE: preencha `configuracao.endpoint` e
   `configuracao.token`. A função `responder` passa a chamar o serviço e o
   resto da interface não muda. O corpo enviado já vai com o contexto da tela.
   ========================================================================== */

import * as D from './dados.js';
import { estado, planoAtual, moduloLiberado } from './estado.js';
import { el, limpar, ico, data } from './ui.js';

export const MAX_NOME = 'MAX';

/* Opções de nome para o cliente escolher. Nada aqui foi aprovado. */
export const SUGESTOES_DE_NOME = [
  { nome: 'MAX',   razao: 'Curto, já vive dentro da marca, funciona falado e escrito.' },
  { nome: 'MAXI',  razao: 'Igual à marca. Reforça, mas confunde produto com assistente.' },
  { nome: 'Íris',  razao: 'Humano e neutro, sem prometer inteligência artificial no nome.' },
  { nome: 'Vita',  razao: 'Ligado a saúde. Genérico demais no mercado brasileiro.' },
  { nome: 'Max Saúde', razao: 'Explica sozinho. Longo para botão e para voz.' }
];

/* Ponto de troca para a IA real. Enquanto for nulo, responde local. */
export const configuracao = { endpoint: null, token: null, modelo: null };

const CHAVE_HISTORICO = 'saude-maxi-max-v1';

/* ==========================================================================
   Contexto por tela
   ========================================================================== */

const CONTEXTOS = {
  '#/inicio': {
    fala: 'Posso mostrar o que merece sua atenção hoje.',
    atalhos: ['Minhas consultas', 'Minha receita', 'Falar com um médico', 'Tirar uma dúvida']
  },
  '#/orientacao': {
    fala: 'Estou junto nesta conversa. Se quiser, resumo o que você já contou.',
    atalhos: ['O que acontece depois', 'Falar com um médico', 'Minhas consultas']
  },
  '#/atendimento': {
    fala: 'Vejo que você quer falar com um médico. Posso explicar como funciona a fila.',
    atalhos: ['Quanto tempo demora', 'Preciso de ajuda agora', 'Minhas consultas']
  },
  '#/agendamento': {
    fala: 'Posso explicar as regras de marcação do seu contrato.',
    atalhos: ['Como funciona a regulação', 'Quanto custa', 'Minhas consultas']
  },
  '#/farmacia': {
    fala: 'Estou vendo que você está na farmácia popular. Quer que eu diga o que sai de graça?',
    atalhos: ['O que eu retiro de graça', 'Farmácias perto de mim', 'Minha receita']
  },
  '#/farmacias': {
    fala: 'Posso dizer qual farmácia tem tudo da sua lista.',
    atalhos: ['O que eu retiro de graça', 'Minha receita']
  },
  '#/consultas': {
    fala: 'Quer ajuda com alguma das suas consultas?',
    atalhos: ['Minha próxima consulta', 'Cancelar uma consulta', 'Minha receita']
  },
  '#/conta': {
    fala: 'Posso localizar um dado do seu cadastro ou explicar o seu plano.',
    atalhos: ['Qual é o meu plano', 'Meus dependentes', 'Trocar o idioma']
  },
  '#/nr1': {
    fala: 'Esta área está em construção. Posso explicar o motivo.',
    atalhos: ['Por que está bloqueado', 'Preciso de ajuda agora']
  },
  '#/ajuda': {
    fala: 'Se for emergência, ligue 192 agora. Aqui eu só ajudo a achar o caminho.',
    atalhos: ['Onde tem pronto atendimento', 'Sinais de gravidade']
  },
  '#/g/painel': {
    fala: 'Posso resumir o dia e apontar o que está parado.',
    atalhos: ['Minhas pendências', 'Pacientes que precisam de atenção', 'Como está a espera']
  },
  '#/g/pacientes': {
    fala: 'Posso filtrar a lista por você.',
    atalhos: ['Pacientes inativos', 'Quem está on-line', 'Minhas pendências']
  },
  '#/g/consultas': {
    fala: 'Posso mostrar o que está aguardando triagem ou sem pagamento.',
    atalhos: ['Consultas sem pagamento', 'Quem está esperando', 'Minhas pendências']
  },
  '#/g/planos': {
    fala: 'Aqui você liga e desliga módulo por plano. Posso explicar o efeito.',
    atalhos: ['O que muda para o paciente', 'Quais planos existem']
  },
  '#/g/identidade': {
    fala: 'Uma cor só define o tema inteiro. Posso explicar como o white label funciona.',
    atalhos: ['Como funciona o white label', 'O que o cliente precisa mandar']
  },
  '#/g/integracao': {
    fala: 'Posso dizer o que já está pronto na API e o que ainda depende de resposta.',
    atalhos: ['O que já está pronto', 'O que está bloqueado']
  }
};

const CONTEXTO_PADRAO = {
  fala: 'Como posso ajudar você hoje?',
  atalhos: ['Minhas consultas', 'Minha receita', 'Tirar uma dúvida']
};

export function contextoDaRota(rota) {
  const limpa = (rota || '').split('?')[0];
  if (CONTEXTOS[limpa]) return CONTEXTOS[limpa];
  if (limpa.startsWith('#/receita/')) return CONTEXTOS['#/farmacia'];
  if (limpa.startsWith('#/g/paciente/')) return CONTEXTOS['#/g/pacientes'];
  return CONTEXTO_PADRAO;
}

/* ==========================================================================
   Saudação por horário
   ========================================================================== */

export function periodoDoDia(agora = new Date()) {
  const h = agora.getHours();
  if (h < 12) return 'Bom dia';
  if (h < 18) return 'Boa tarde';
  return 'Boa noite';
}

export function primeiroNome(nome) {
  return (nome || '').trim().split(' ')[0] || '';
}

/* ==========================================================================
   Próxima ação

   Uma só, a mais urgente. Se não houver nenhuma, diz que está tudo certo.
   Sempre calculada a partir do estado real do esboço, nunca inventada.
   ========================================================================== */

const EM_ANDAMENTO = ['WAITING_HELPDESK', 'ONGOING_HELPDESK', 'WAITING_DOCTOR', 'ONGOING_DOCTOR'];

export function proximaAcaoPaciente() {
  const p = estado.pacienteAtual;
  if (!p) return null;
  const minhas = estado.consultas.filter(c => c.pacienteId === p.id);

  const viva = minhas.find(c => EM_ANDAMENTO.includes(c.status));
  if (viva) {
    return {
      urgencia: 'agora',
      titulo: 'Seu atendimento está acontecendo',
      texto: `${D.STATUS_CONSULTA[viva.status].rot}, em ${viva.especialidade}. Código ${viva.codigo}.`,
      rotulo: 'Voltar para o atendimento',
      rota: '#/atendimento'
    };
  }

  const futuras = minhas
    .filter(c => c.status === 'SCHEDULED')
    .sort((a, b) => a.agendadaPara.localeCompare(b.agendadaPara));
  if (futuras.length) {
    const c = futuras[0];
    const hora = c.agendadaPara.slice(11, 16);
    return {
      urgencia: 'atencao',
      titulo: 'Você tem uma consulta marcada',
      texto: `${c.especialidade} com ${c.medico || 'profissional a definir'}, em ${data(c.agendadaPara)} às ${hora}.` +
             (c.pago ? '' : ' O pagamento ainda não foi confirmado.'),
      rotulo: 'Ver a consulta',
      rota: '#/consultas'
    };
  }

  if (moduloLiberado('farmacia')) {
    const pendente = estado.receitas.find(r => r.itens.some(i => i.cobertura === 'confirmar'));
    if (pendente) {
      const n = pendente.itens.filter(i => i.cobertura === 'confirmar').length;
      return {
        urgencia: 'atencao',
        titulo: `Confirme ${n} ${n === 1 ? 'item' : 'itens'} da sua receita`,
        texto: 'A leitura da foto ficou incerta nestes itens. Confirmar leva alguns segundos e libera a busca em farmácia.',
        rotulo: 'Abrir a receita',
        rota: '#/receita/' + pendente.id
      };
    }
  }

  return {
    urgencia: 'calma',
    titulo: 'Tudo certo por enquanto',
    texto: 'Você não tem nada pendente. Se precisar de um médico, o atendimento funciona 24 horas.',
    rotulo: moduloLiberado('atendimento') ? 'Falar com um médico' : 'Ver meus serviços',
    rota: moduloLiberado('atendimento') ? '#/atendimento' : '#/inicio'
  };
}

export function pendenciasGestor() {
  const doCliente = estado.pacientes.filter(p => p.cliente === estado.cliente.id);
  const ids = doCliente.map(p => p.id);
  const minhas = estado.consultas.filter(c => ids.includes(c.pacienteId));

  return {
    esperando: minhas.filter(c => EM_ANDAMENTO.includes(c.status)).length,
    semPagamento: minhas.filter(c => c.status === 'SCHEDULED' && !c.pago).length,
    inativos: doCliente.filter(p => p.status !== 'ACTIVE').length,
    online: doCliente.filter(p => p.online).length,
    semContato: doCliente.filter(p => !p.email).length
  };
}

export function proximaAcaoGestor() {
  const n = pendenciasGestor();

  if (n.esperando) {
    return {
      urgencia: 'agora',
      titulo: `${n.esperando} ${n.esperando === 1 ? 'paciente está' : 'pacientes estão'} na fila agora`,
      texto: 'Aguardando triagem ou já com o médico. É o número que o contrato de 5 minutos mede.',
      rotulo: 'Abrir consultas',
      rota: '#/g/consultas'
    };
  }
  if (n.semPagamento) {
    return {
      urgencia: 'atencao',
      titulo: `${n.semPagamento} ${n.semPagamento === 1 ? 'consulta agendada sem pagamento' : 'consultas agendadas sem pagamento'}`,
      texto: 'A cobrança é feita por fora e depois marcada aqui. Enquanto não for marcada, o repasse não fecha.',
      rotulo: 'Ver consultas',
      rota: '#/g/consultas'
    };
  }
  if (n.inativos) {
    return {
      urgencia: 'atencao',
      titulo: `${n.inativos} ${n.inativos === 1 ? 'paciente inativo' : 'pacientes inativos'}`,
      texto: 'Cadastro fora de vigência não consegue abrir atendimento.',
      rotulo: 'Ver pacientes',
      rota: '#/g/pacientes'
    };
  }
  return {
    urgencia: 'calma',
    titulo: 'Operação sem pendência',
    texto: 'Nenhuma fila, nenhuma cobrança em aberto, nenhum cadastro vencido.',
    rotulo: 'Abrir o painel',
    rota: '#/g/painel'
  };
}

export const proximaAcao = () =>
  (estado.perfil === 'gestor' ? proximaAcaoGestor() : proximaAcaoPaciente());

/* ==========================================================================
   O cérebro simulado

   Cada resposta sai de um dado que já existe no esboço. O MAX não inventa
   informação clínica, não conclui sobre doença e não fala de medicamento
   fora do que a receita já diz.
   ========================================================================== */

const tem = (texto, palavras) => palavras.some(p => texto.includes(p));

const GRAVIDADE = ['dor no peito', 'peito apertado', 'falta de ar', 'nao consigo respirar',
  'não consigo respirar', 'desmaiei', 'desmaio', 'convulsao', 'convulsão', 'sangrando',
  'boca torta', 'avc', 'infarto', 'me matar', 'suicid'];

const SINTOMA = ['dor', 'febre', 'tosse', 'enjoo', 'nausea', 'náusea', 'tontura', 'mancha',
  'cocei', 'coceira', 'vomit', 'diarreia', 'sinto', 'estou mal', 'to mal', 'doendo'];

function normalizar(t) {
  /* Tira acento para o gatilho casar com quem digita sem acento. */
  return (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
}

function respostaPaciente(bruto) {
  const t = normalizar(bruto);
  const p = estado.pacienteAtual;
  const plano = planoAtual();
  const minhas = p ? estado.consultas.filter(c => c.pacienteId === p.id) : [];

  /* 1. Segurança primeiro. Vale mais que qualquer outra intenção. */
  if (tem(t, GRAVIDADE.map(normalizar))) {
    return {
      texto: 'Isso precisa de gente agora, não de mim. Ligue 192, ou vá ao pronto atendimento mais perto. Eu não avalio sinal de gravidade.',
      tom: 'alerta',
      acoes: [{ rotulo: 'Ajuda imediata', rota: '#/ajuda' }]
    };
  }

  /* 2. Sintoma. O MAX não conclui nada, encaminha para a trilha certa. */
  if (tem(t, SINTOMA.map(normalizar))) {
    return {
      texto: 'Eu não avalio sintoma e não digo o que você tem. Quem faz isso é o profissional de saúde. O que eu faço é te levar pelo caminho mais curto até ele.',
      acoes: [
        moduloLiberado('orientacao') ? { rotulo: 'Começar pela orientação', rota: '#/orientacao' } : null,
        moduloLiberado('atendimento') ? { rotulo: 'Falar com um médico', rota: '#/atendimento' } : null
      ].filter(Boolean)
    };
  }

  /* 3. Consultas */
  if (tem(t, ['consulta', 'agenda', 'marcad', 'horario', 'proxima', 'atendimento'])) {
    const futura = minhas.filter(c => c.status === 'SCHEDULED')
      .sort((a, b) => a.agendadaPara.localeCompare(b.agendadaPara))[0];
    const viva = minhas.find(c => EM_ANDAMENTO.includes(c.status));
    if (viva) {
      return {
        texto: `Você tem um atendimento em andamento: ${viva.especialidade}, ${D.STATUS_CONSULTA[viva.status].rot.toLowerCase()}. Código ${viva.codigo}.`,
        acoes: [{ rotulo: 'Voltar ao atendimento', rota: '#/atendimento' }]
      };
    }
    if (futura) {
      return {
        texto: `Encontrei. ${futura.especialidade} com ${futura.medico || 'profissional a definir'}, em ${data(futura.agendadaPara)} às ${futura.agendadaPara.slice(11, 16)}.`,
        acoes: [{ rotulo: 'Ver os detalhes', rota: '#/consultas' }]
      };
    }
    const feitas = minhas.filter(c => c.status === 'FINISHED').length;
    return {
      texto: feitas
        ? `Você não tem consulta marcada. No seu histórico existem ${feitas} ${feitas === 1 ? 'consulta finalizada' : 'consultas finalizadas'}.`
        : 'Você ainda não tem consulta no histórico.',
      acoes: [
        { rotulo: 'Ver histórico', rota: '#/consultas' },
        moduloLiberado('atendimento') ? { rotulo: 'Falar com um médico agora', rota: '#/atendimento' } : null
      ].filter(Boolean)
    };
  }

  /* 4. Receita e farmácia. Nunca sugere troca de medicamento. */
  if (tem(t, ['receita', 'remedio', 'medicament', 'farmacia', 'gratis', 'gratuit', 'retir'])) {
    if (!moduloLiberado('farmacia')) {
      return { texto: 'A farmácia popular não está no seu plano hoje. Quem libera é o gestor do seu contrato.' };
    }
    const todos = estado.receitas.flatMap(r => r.itens);
    const cobertos = todos.filter(i => i.cobertura === 'coberto');
    const confirmar = todos.filter(i => i.cobertura === 'confirmar');
    return {
      texto: `Nas suas receitas existem ${todos.length} itens. ${cobertos.length} você retira de graça na farmácia popular.` +
        (confirmar.length ? ` ${confirmar.length} ainda dependem de você confirmar a leitura da foto.` : '') +
        ' Eu não sugiro troca de medicamento, isso é decisão do seu médico.',
      acoes: [
        { rotulo: 'Abrir farmácia popular', rota: '#/farmacia' },
        { rotulo: 'Farmácias perto de mim', rota: '#/farmacias' }
      ]
    };
  }

  /* 5. Plano e cobertura */
  if (tem(t, ['plano', 'cobertura', 'incluido', 'modulo', 'quanto custa', 'preco', 'valor'])) {
    const ligados = D.MODULOS.filter(m => moduloLiberado(m.chave)).map(m => m.nome);
    return {
      texto: `Seu plano é ${plano ? plano.nome : 'não identificado'}. Ele libera: ${ligados.join(', ')}.` +
        (estado.cliente.regulacao ? ' Como o contrato é de município, a marcação com especialista passa pelo núcleo de regulação.' : ''),
      acoes: [{ rotulo: 'Ver minha conta', rota: '#/conta' }]
    };
  }

  /* 6. Exames. Honestidade sobre o que o sistema não tem. */
  if (tem(t, ['exame', 'resultado', 'laudo'])) {
    return {
      texto: 'Resultado de exame não passa por esta camada. O que existe hoje é o pedido de exame, que o médico emite dentro do atendimento, e o histórico da consulta.',
      acoes: [{ rotulo: 'Ver minhas consultas', rota: '#/consultas' }]
    };
  }

  /* 7. Saúde mental */
  if (tem(t, ['mental', 'ansiedade', 'depress', 'nr-1', 'nr1', 'psicolog', 'terapia'])) {
    return {
      texto: moduloLiberado('nr1')
        ? 'A trilha de saúde mental está desenhada, mas ainda bloqueada até a definição sobre o que o gestor pode ver. Nada individual seu é enviado a ninguém.'
        : 'A trilha de saúde mental não está no seu plano. Se você precisa de acolhimento agora, a ajuda imediata funciona sempre.',
      acoes: [{ rotulo: 'Ajuda imediata', rota: '#/ajuda' }]
    };
  }

  /* 8. Dados e conta */
  if (tem(t, ['meus dados', 'cadastro', 'telefone', 'email', 'endereco', 'dependente', 'idioma', 'conta'])) {
    return {
      texto: `Seu cadastro está em Minha conta. Hoje consta ${p ? p.telefone : 'sem telefone'}${p && p.email ? ' e ' + p.email : ', sem e-mail'}. Você pode editar por lá.`,
      acoes: [{ rotulo: 'Abrir minha conta', rota: '#/conta' }]
    };
  }

  /* 9. Quem é o MAX */
  if (tem(t, ['quem e voce', 'quem é você', 'o que voce faz', 'voce e um medico', 'voce e ia', 'robo'])) {
    return {
      texto: `Sou o ${MAX_NOME}, o assistente do Saúde Maxi. Organizo informação, encontro seus dados e explico como cada parte funciona. Não sou médico e não avalio sintoma.`,
      acoes: [{ rotulo: 'Ver meus serviços', rota: '#/inicio' }]
    };
  }

  /* 10. Fallback honesto */
  return {
    texto: 'Ainda não sei responder isso. Consigo ajudar com consultas, receita e farmácia popular, seu plano, seu cadastro, e o caminho até um médico.',
    acoes: [
      { rotulo: 'Minhas consultas', rota: '#/consultas' },
      moduloLiberado('farmacia') ? { rotulo: 'Farmácia popular', rota: '#/farmacia' } : null
    ].filter(Boolean)
  };
}

function respostaGestor(bruto) {
  const t = normalizar(bruto);
  const n = pendenciasGestor();
  const doCliente = estado.pacientes.filter(p => p.cliente === estado.cliente.id);

  if (tem(t, ['pendencia', 'hoje', 'resumo', 'o que falta', 'atencao'])) {
    const partes = [];
    if (n.esperando) partes.push(`${n.esperando} na fila agora`);
    if (n.semPagamento) partes.push(`${n.semPagamento} sem pagamento marcado`);
    if (n.inativos) partes.push(`${n.inativos} com cadastro inativo`);
    if (n.semContato) partes.push(`${n.semContato} sem e-mail no cadastro`);
    return {
      texto: partes.length
        ? `Hoje você tem ${partes.length} ${partes.length === 1 ? 'ponto' : 'pontos'} de atenção: ${partes.join(', ')}.`
        : 'Nenhuma pendência aberta em ' + estado.cliente.nome + '.',
      acoes: [
        { rotulo: 'Abrir consultas', rota: '#/g/consultas' },
        { rotulo: 'Abrir pacientes', rota: '#/g/pacientes' }
      ]
    };
  }

  if (tem(t, ['paciente', 'inativo', 'online', 'cadastro'])) {
    return {
      texto: `${estado.cliente.nome} tem ${doCliente.length} cadastros nesta base de demonstração. ${n.online} on-line agora, ${n.inativos} inativos.`,
      acoes: [{ rotulo: 'Ver a lista', rota: '#/g/pacientes' }]
    };
  }

  if (tem(t, ['consulta', 'fila', 'espera', 'pagamento', 'pago'])) {
    return {
      texto: `Espera média no pronto atendimento: ${D.INDICADORES.esperaProntoAtendimento}, dentro do compromisso de 5 minutos. ${n.esperando} em atendimento agora, ${n.semPagamento} agendadas sem pagamento marcado.`,
      acoes: [{ rotulo: 'Abrir consultas', rota: '#/g/consultas' }]
    };
  }

  if (tem(t, ['plano', 'modulo', 'liberar', 'contrat'])) {
    return {
      texto: 'Módulo liga e desliga por tipo de plano, na tela de Planos e módulos. O efeito é imediato na vitrine do paciente, sem nova publicação.',
      acoes: [{ rotulo: 'Abrir planos e módulos', rota: '#/g/planos' }]
    };
  }

  if (tem(t, ['identidade', 'cor', 'logo', 'marca', 'white label'])) {
    return {
      texto: 'O white label é uma cor só, mais os arquivos de imagem. Trocar a cor repinta o sistema inteiro na hora, igual ao sistema oficial.',
      acoes: [{ rotulo: 'Abrir identidade visual', rota: '#/g/identidade' }]
    };
  }

  if (tem(t, ['api', 'integra', 'pronto', 'bloqueado', 'endpoint'])) {
    return {
      texto: 'São 21 pontos catalogados: 13 já disponíveis na API oficial, 5 simulados, 2 de integração futura e 3 bloqueados por decisão pendente.',
      acoes: [{ rotulo: 'Abrir integração', rota: '#/g/integracao' }]
    };
  }

  if (tem(t, ['mental', 'nr-1', 'nr1', 'psicossocial'])) {
    return {
      texto: 'Sobre saúde mental eu mostro indicador do grupo, nunca pessoa. Enquanto a decisão sobre o que o gestor pode ver não vier, a trilha fica bloqueada.',
      tom: 'alerta'
    };
  }

  return {
    texto: 'Ainda não sei responder isso. Consigo ajudar com pendências do dia, pacientes, consultas, planos e módulos, identidade visual e integração.',
    acoes: [{ rotulo: 'Minhas pendências', rota: '#/g/painel' }]
  };
}

/* Ponto único de resposta. Troque o corpo por uma chamada de API e nada
   mais na interface precisa mudar. */
export async function responder(texto) {
  if (configuracao.endpoint) return responderPorServico(texto);
  const atraso = estado.rede === 'lenta' ? 1400 : 420;
  await new Promise(r => setTimeout(r, atraso));
  return estado.perfil === 'gestor' ? respostaGestor(texto) : respostaPaciente(texto);
}

/* Não é chamada enquanto configuracao.endpoint for nulo. Fica escrita para o
   dia em que o serviço existir. O contexto vai junto, para a IA saber em que
   tela a pessoa está e com que perfil fala. */
async function responderPorServico(texto) {
  const corpo = {
    mensagem: texto,
    modelo: configuracao.modelo,
    contexto: {
      perfil: estado.perfil,
      rota: (typeof location !== 'undefined' ? location.hash : ''),
      cliente: estado.cliente.id,
      plano: planoAtual() ? planoAtual().nome : null,
      modulos: estado.modulos
    }
  };
  const resposta = await fetch(configuracao.endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${configuracao.token}` },
    body: JSON.stringify(corpo)
  });
  if (!resposta.ok) throw new Error('O assistente não respondeu.');
  return resposta.json();
}

/* ==========================================================================
   Histórico, guardado só no navegador de quem usa
   ========================================================================== */

let historico = [];

function lerHistorico() {
  try {
    const bruto = JSON.parse(localStorage.getItem(CHAVE_HISTORICO));
    historico = Array.isArray(bruto) ? bruto.slice(-40) : [];
  } catch (e) { historico = []; }
}

function gravarHistorico() {
  try { localStorage.setItem(CHAVE_HISTORICO, JSON.stringify(historico.slice(-40))); }
  catch (e) { /* janela anônima. O painel continua funcionando na sessão. */ }
}

export function limparHistorico() {
  historico = [];
  gravarHistorico();
  if (corpoConversa) {
    limpar(corpoConversa);
    saudar();
  }
}

/* ==========================================================================
   Interface do painel
   ========================================================================== */

let painel = null;
let botao = null;
let corpoConversa = null;
let faixaAtalhos = null;
let campoTexto = null;
let rotaAtual = '';
let aberto = false;
let navegar = (rota) => { location.hash = rota; };

export function marcaMax(tam = 22) {
  const ns = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(ns, 'svg');
  svg.setAttribute('viewBox', '0 0 32 32');
  svg.setAttribute('width', tam);
  svg.setAttribute('height', tam);
  svg.setAttribute('aria-hidden', 'true');
  svg.setAttribute('class', 'max-marca');
  const p = (d, attrs = {}) => {
    const n = document.createElementNS(ns, 'path');
    n.setAttribute('d', d);
    n.setAttribute('fill', 'none');
    n.setAttribute('stroke', 'currentColor');
    n.setAttribute('stroke-width', attrs.w || '2.4');
    n.setAttribute('stroke-linecap', 'round');
    n.setAttribute('stroke-linejoin', 'round');
    if (attrs.opacity) n.setAttribute('opacity', attrs.opacity);
    svg.appendChild(n);
  };
  /* Um M estilizado dentro de uma órbita. Traçado único, sem arquivo externo. */
  p('M16 3.4a12.6 12.6 0 1 1 0 25.2 12.6 12.6 0 0 1 0-25.2', { w: '1.5', opacity: '.45' });
  p('M10.6 21V12l5.4 5.2L21.4 12v9');
  return svg;
}

function bolha(texto, minha, tom) {
  return el('div', {
    class: 'max-bolha ' + (minha ? 'minha' : 'dele') + (tom === 'alerta' ? ' alerta' : '')
  }, [el('span', { texto })]);
}

function digitando() {
  const n = el('div', { class: 'max-bolha dele' }, [
    el('span', { class: 'digitando' }, [el('i'), el('i'), el('i')])
  ]);
  corpoConversa.appendChild(n);
  rolar();
  return n;
}

function rolar() {
  if (corpoConversa) corpoConversa.scrollTop = corpoConversa.scrollHeight;
}

function desenharAcoes(acoes) {
  if (!acoes || !acoes.length) return null;
  return el('div', { class: 'max-acoes' }, acoes.map(a =>
    el('button', {
      class: 'max-acao',
      type: 'button',
      onclick: () => { navegar(a.rota); fecharMax(); }
    }, [a.rotulo, ico('avanca', 14)])
  ));
}

function saudar() {
  const ctx = contextoDaRota(rotaAtual);
  const nome = estado.perfil === 'gestor' ? 'Danilo' : primeiroNome(estado.pacienteAtual ? estado.pacienteAtual.nome : '');
  corpoConversa.appendChild(bolha(`${periodoDoDia()}${nome ? ', ' + nome : ''}. ${ctx.fala}`, false));
  rolar();
}

async function enviar(texto) {
  const limpo = (texto || '').trim();
  if (!limpo) return;
  corpoConversa.appendChild(bolha(limpo, true));
  historico.push({ eu: true, texto: limpo });
  rolar();
  campoTexto.value = '';

  const pensando = digitando();
  try {
    const r = await responder(limpo);
    pensando.remove();
    corpoConversa.appendChild(bolha(r.texto, false, r.tom));
    const acoes = desenharAcoes(r.acoes);
    if (acoes) corpoConversa.appendChild(acoes);
    historico.push({ eu: false, texto: r.texto, tom: r.tom, acoes: r.acoes || [] });
    gravarHistorico();
  } catch (e) {
    pensando.remove();
    corpoConversa.appendChild(bolha('Não consegui responder agora. Tente de novo em instantes.', false, 'alerta'));
  }
  rolar();
}

function restaurar() {
  limpar(corpoConversa);
  if (!historico.length) { saudar(); return; }
  historico.forEach(m => {
    corpoConversa.appendChild(bolha(m.texto, m.eu, m.tom));
    if (!m.eu && m.acoes && m.acoes.length) {
      const a = desenharAcoes(m.acoes);
      if (a) corpoConversa.appendChild(a);
    }
  });
  rolar();
}

function atualizarAtalhos() {
  if (!faixaAtalhos) return;
  limpar(faixaAtalhos);
  contextoDaRota(rotaAtual).atalhos.forEach(s => {
    faixaAtalhos.appendChild(el('button', {
      class: 'max-sugestao', type: 'button', onclick: () => enviar(s)
    }, [s]));
  });
}

export function abrirMax(perguntaInicial) {
  if (!painel) return;
  aberto = true;
  painel.hidden = false;
  /* Deixa o navegador aplicar o display antes de animar. */
  setTimeout(() => painel.classList.add('aberto'), 10);
  botao.classList.add('ativo');
  atualizarAtalhos();
  if (!corpoConversa.firstChild) restaurar();
  if (perguntaInicial) enviar(perguntaInicial);
  else if (campoTexto && campoTexto.focus) campoTexto.focus();
}

export function fecharMax() {
  if (!painel) return;
  aberto = false;
  painel.classList.remove('aberto');
  botao.classList.remove('ativo');
  setTimeout(() => { if (!aberto) painel.hidden = true; }, 220);
}

export function alternarMax() { aberto ? fecharMax() : abrirMax(); }

/* O MAX só existe depois da autenticação. Na tela de entrada ele some, para
   a entrada continuar sendo uma coisa só. */
export function visibilidadeMax(mostrar) {
  if (botao) botao.hidden = !mostrar;
  if (!mostrar) fecharMax();
}

/* Chamada a cada troca de rota. É o que faz o assistente parecer contextual. */
export function contextualizarMax(rota) {
  rotaAtual = rota || '';
  atualizarAtalhos();
  if (botao) {
    const ctx = contextoDaRota(rotaAtual);
    botao.setAttribute('title', ctx.fala);
    botao.setAttribute('aria-label', `Abrir ${MAX_NOME}. ${ctx.fala}`);
  }
}

/* Bloco do MAX na home. Não é o painel: é o convite dentro da página. */
export function blocoMax(ir, atalhosExtras) {
  const ctx = contextoDaRota(estado.perfil === 'gestor' ? '#/g/painel' : '#/inicio');
  const atalhos = atalhosExtras || ctx.atalhos;
  return el('div', { class: 'max-convite' }, [
    el('div', { class: 'max-convite-topo' }, [
      el('span', { class: 'max-avatar' }, [marcaMax(24)]),
      el('span', {}, [
        el('p', { class: 'max-nome' }, [MAX_NOME, el('i', { class: 'max-pulso', title: 'Disponível' })]),
        el('p', { class: 'max-fala', texto: ctx.fala })
      ])
    ]),
    el('div', { class: 'max-sugestoes' }, atalhos.map(s =>
      el('button', {
        class: 'max-sugestao', type: 'button',
        onclick: () => abrirMax(s)
      }, [s])
    )),
    el('p', { class: 'max-rodape', texto: `${MAX_NOME} organiza informação e encontra seus dados. Não é médico e não avalia sintoma.` })
  ]);
}

/* Monta o botão flutuante e o painel. Chamado uma vez, pelo app.js. */
export function montarMax(irPara) {
  if (painel) return;
  if (irPara) navegar = irPara;
  lerHistorico();

  corpoConversa = el('div', { class: 'max-conversa', id: 'max-conversa' });
  faixaAtalhos = el('div', { class: 'max-sugestoes max-sugestoes-painel' });
  campoTexto = el('input', {
    class: 'max-campo', type: 'text', 'aria-label': 'Escreva para o ' + MAX_NOME,
    placeholder: 'Escreva sua pergunta'
  });

  const formulario = el('form', {
    class: 'max-envio',
    onsubmit: (ev) => { ev.preventDefault(); enviar(campoTexto.value); }
  }, [
    campoTexto,
    el('button', { class: 'max-enviar', type: 'submit', 'aria-label': 'Enviar' }, [ico('avanca', 18)])
  ]);

  painel = el('aside', {
    class: 'max-painel', id: 'max-painel', role: 'dialog',
    'aria-label': 'Assistente ' + MAX_NOME, hidden: true
  }, [
    el('header', { class: 'max-cabecalho' }, [
      el('span', { class: 'max-avatar' }, [marcaMax(22)]),
      el('span', { class: 'max-cabecalho-texto' }, [
        el('p', { class: 'max-nome' }, [MAX_NOME, el('i', { class: 'max-pulso' })]),
        el('p', { class: 'max-estado', texto: 'Assistente do Saúde Maxi' })
      ]),
      el('button', {
        class: 'max-icone', type: 'button', 'aria-label': 'Limpar conversa',
        onclick: limparHistorico
      }, [ico('volta', 16)]),
      el('button', {
        class: 'max-icone', type: 'button', 'aria-label': 'Fechar',
        onclick: fecharMax
      }, [ico('x', 16)])
    ]),
    corpoConversa,
    faixaAtalhos,
    formulario,
    el('p', { class: 'max-aviso-legal', texto: 'Respostas simuladas neste esboço. Nenhuma delas conclui sobre doença.' })
  ]);

  botao = el('button', {
    class: 'max-botao', id: 'max-botao', type: 'button',
    'aria-label': 'Abrir ' + MAX_NOME,
    onclick: alternarMax
  }, [
    el('span', { class: 'max-botao-marca' }, [marcaMax(24)]),
    el('span', { class: 'max-botao-rotulo', texto: MAX_NOME }),
    el('i', { class: 'max-pulso' })
  ]);

  document.body.appendChild(painel);
  document.body.appendChild(botao);
  restaurar();

  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape' && aberto) fecharMax();
    /* Atalho de teclado. Não usa combinação que o navegador já reserva. */
    if (ev.key === 'm' && ev.altKey) { ev.preventDefault(); alternarMax(); }
  });
}
