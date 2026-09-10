# Tarefas comuns, passo a passo

Receita pronta para o que mais aparece. Toda tarefa termina em
`node teste/fumaca.mjs`.

---

## Adicionar uma tela nova

**1. Escreva a função da tela** em `js/telas-paciente.js` ou
`js/telas-gestor.js`:

```js
/* ==========================================================================
   Meus exames. ORIGEM: Reunião.
   ========================================================================== */

export function exames(ir) {
  return frag([
    cabecalho('Meus exames', 'Pedidos de exame feitos no atendimento.', ['reuniao']),
    el('div', { class: 'cartao' }, [
      el('p', { class: 'cartao-titulo', texto: 'Em construção' })
    ])
  ]);
}
```

**2. Registre a rota** em `js/app.js`, na constante `ROTAS`:

```js
'#/exames': { perfil: 'paciente', titulo: 'Meus exames', tela: (ir) => P.exames(ir), modulo: 'exames' },
```

O campo `modulo` é opcional. Se existir, a rota só abre quando o módulo
estiver ligado no plano. Se o módulo não existir em `dados.js`, a rota fica
inacessível para sempre. É o erro mais comum.

**3. Coloque no menu**, na constante `MENU_PACIENTE` ou `MENU_GESTOR`:

```js
{ rota: '#/exames', icone: 'documento', rotulo: 'Meus exames', modulo: 'exames' }
```

**4. Dê contexto ao assistente**, em `js/max.js`, na constante `CONTEXTOS`:

```js
'#/exames': {
  fala: 'Posso ajudar você a achar um pedido de exame.',
  atalhos: ['Meu último exame', 'Como funciona']
},
```

Sem isso a tela funciona, mas o MAX abre com a fala genérica.

**5. Registre no teste**, em `teste/fumaca.mjs`, junto das outras:

```js
checar(`paciente/exames [${cli}]`, () => P.exames(nada));
```

**6. Rode o teste e abra no navegador.**

---

## Adicionar um módulo contratável

Módulo é o que liga e desliga por tipo de plano. Foi decisão de reunião, é o
coração do modelo comercial.

**1.** Em `js/dados.js`, acrescente na lista `MODULOS`:

```js
{ chave: 'exames', nome: 'Meus exames', padrao: false, nota: 'Contratado à parte' }
```

**2.** Acrescente a chave em **todos** os planos de `PLANOS` e em **todos** os
clientes de `CLIENTES`, no objeto `modulos`. Se faltar em um, aquele plano
trata como desligado, sem avisar.

**3.** Use `moduloLiberado('exames')` antes de mostrar qualquer coisa do
módulo.

**4.** Confira na tela **Planos e módulos** do gestor. A chave nova aparece
sozinha, porque a tela é montada a partir de `MODULOS`.

---

## Adicionar um cliente novo, com white label

**1.** Em `js/dados.js`, em `CLIENTES`:

```js
prefeitura_x: {
  id: 'prefeitura_x',
  nome: 'Prefeitura de Exemplo',
  tipo: 'Município',
  sigla: 'PX',
  subdominio: 'exemplo.saudemaxi.com.br',
  saudacao: 'Bem-vindo à telemedicina da Prefeitura de Exemplo',
  cor: '#1D4E89',                 // A ÚNICA cor. O resto deriva dela
  modulos: { orientacao: true, atendimento: true, farmacia: true, agendamento: false, nr1: false },
  regulacao: true,                // município: marcação passa pelo núcleo de regulação
  populacao: '40.000 habitantes'
}
```

**2.** Crie pelo menos um plano para ele em `PLANOS`, com `cliente:
'prefeitura_x'`. Sem plano, o cliente entra sem módulo nenhum.

**3.** Acrescente a opção no seletor de cliente, em `index.html`, dentro de
`#sel-cliente`.

**4.** Acrescente o cliente no `base()` de `js/estado.js`, no objeto
`identidade`.

**5.** No teste, acrescente o id nas listas de clientes.

**Não crie uma paleta.** Uma cor só. Confira na tela Identidade visual do
gestor, que repinta o sistema inteiro ao vivo.

---

## Ensinar uma resposta nova ao assistente

Em `js/max.js`, dentro de `respostaPaciente()` ou `respostaGestor()`. A ordem
importa: a primeira condição que casar ganha.

```js
if (tem(t, ['exame', 'resultado', 'laudo'])) {
  return {
    texto: 'Seus pedidos de exame ficam em Meus exames.',
    acoes: [{ rotulo: 'Abrir meus exames', rota: '#/exames' }]
  };
}
```

O objeto de resposta aceita:

| Campo | Para quê |
| --- | --- |
| `texto` | O que ele fala. Obrigatório. Curto |
| `acoes` | Botões que levam para uma rota. Opcional |
| `tom` | `'alerta'` pinta a bolha de vermelho. Use só para segurança |

**A verificação de gravidade tem que continuar sendo a primeira condição da
função.** Ela vale mais que qualquer outra intenção. Se alguém escreve "dor no
peito" enquanto fala de exame, o 192 ganha.

**Nunca escreva resposta que conclua sobre doença.** O teste barra as palavras
"diagnóstico" e "pré-diagnóstico" e quebra o build.

---

## Ligar o assistente numa IA de verdade

Em `js/max.js`, no topo:

```js
export const configuracao = {
  endpoint: 'https://sua-api/assistente',
  token: 'o token',
  modelo: 'claude-opus-5'
};
```

Pronto. A função `responder()` passa a chamar `responderPorServico()`, que já
está escrita e já manda o contexto:

```js
{
  mensagem: 'o que a pessoa escreveu',
  modelo: '...',
  contexto: {
    perfil: 'paciente',
    rota: '#/farmacia',
    cliente: 'queimados',
    plano: 'Municipal Básico',
    modulos: { orientacao: true, ... }
  }
}
```

O serviço precisa responder com `{ texto, acoes, tom }`, o mesmo formato das
respostas simuladas. Nenhuma tela muda.

**O token não pode viver no código do navegador.** Quem guarda o token é o seu
serviço no meio do caminho. O navegador fala com o seu serviço, o seu serviço
fala com o modelo. Nunca o contrário.

**As regras 3, 4 e 6 valem para a IA também.** Elas precisam estar no prompt
do sistema do seu serviço, não só no teste daqui.

---

## Mexer em CSS sem quebrar nada

1. Ache o bloco pelo cabeçalho comentado. O arquivo é dividido por assunto
2. Use as variáveis de `:root`. Não escreva `#5E5212` na mão em lugar nenhum
3. Cor de marca é `--marca` e derivadas. Cor semântica é `--ok`, `--aviso`,
   `--erro`. Não misture: um botão de perigo não é da cor do cliente
4. Espaço é `--e1` a `--e7`. Raio é `--r-sm` a `--r-xl`
5. Se criar elemento fixo, confira a tabela de camadas em
   [ARQUITETURA.md](ARQUITETURA.md)
6. **Abra no navegador.** O teste não pega CSS

---

## Trocar simulação por API real

Ver [LIGAR-A-API-REAL.md](LIGAR-A-API-REAL.md). É um arquivo só, `js/api.js`,
e nenhuma tela muda.

---

## Antes de todo commit

```
node teste/fumaca.mjs
```

E, se mexeu em CSS, abra no navegador nos dois perfis e em 375 pixels de
largura.

Confira também que não entrou travessão. O caractere entra por código, para
este próprio arquivo não ter um:

```
grep -rn "$(printf '\xe2\x80\x94')" css js index.html
```

Não pode devolver nada.
