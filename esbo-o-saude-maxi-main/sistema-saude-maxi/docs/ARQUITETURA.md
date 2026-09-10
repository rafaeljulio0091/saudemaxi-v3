# Arquitetura

Leia antes de mexer. São vinte minutos que economizam um dia.

---

## A ideia em uma frase

Cada tela é uma **função que devolve um nó do DOM**. O roteador troca o
conteúdo da casca. Não existe estado de componente, não existe ciclo de vida,
não existe reconciliação. Quando algo muda, a tela é redesenhada inteira.

Isso é simples de propósito. O sistema tem 18 telas e dois perfis. Não precisa
de framework, e não ter framework significa nenhum `npm install`, nenhuma
versão para atualizar e nenhuma dependência que some.

---

## Por que módulos nativos e não um empacotador

O produto vai rodar embutido, em máquina de posto de saúde, e vai ser
publicado por gente que não é do time. Toda etapa de build é uma etapa que
pode falhar longe de você.

O preço é que o sistema **precisa de servidor HTTP** para rodar. Abrir com
duplo clique não funciona. É o único preço.

---

## O fluxo de um clique, do começo ao fim

```
usuário clica num item do menu
        |
        v
ir('#/farmacia')  altera location.hash
        |
        v
evento hashchange dispara
        |
        v
desenhar()  em js/app.js
        |
        +--> não autenticado?  desenha a entrada e para aqui
        |
        +--> rota tem parâmetro?  trata receita/:id e paciente/:id
        |
        +--> rota existe no mapa ROTAS?  não, tela de não encontrado
        |
        +--> perfil bate?  não, redireciona para o início do perfil
        |
        +--> módulo liberado?  não, tela de "não incluído no seu plano"
        |
        v
casca(rota, alvo.tela(ir), titulo)
        |
        +--> monta o menu lateral, o topo e a área de conteúdo
        |
        v
P.farmacia(ir)  a função da tela devolve um DocumentFragment
        |
        v
o fragmento entra em .conteudo, e o CSS anima a entrada escalonada
```

Todo o roteamento vive em `js/app.js`, na função `desenhar()`. Não tem mágica
em outro lugar.

---

## Arquivo por arquivo

### `js/dados.js`

Dados simulados. Clientes, planos, módulos, especialidades, médicos,
pacientes, consultas, receitas, farmácias, unidades, indicadores.

Não tem lógica. É só o conteúdo. Quando a API real entrar, este arquivo vira a
base de teste e some da execução.

**Ponto importante:** `STATUS_CONSULTA` é a máquina de estados **real**, lida
da documentação da API oficial. Não invente estados novos:

```
SCHEDULED  PENDING  WAITING_HELPDESK  ONGOING_HELPDESK
WAITING_DOCTOR  ONGOING_DOCTOR  FINISHED  CANCELED
```

### `js/estado.js`

O estado da aplicação e a persistência em `localStorage`, na chave
`saude-maxi-esboco-v1`.

Funções que você vai usar:

| Função | O que faz |
| --- | --- |
| `estado` | O objeto. Leia direto dele |
| `salvar()` | Grava no `localStorage`. Chame depois de mudar algo |
| `carregar()` | Lê. Roda uma vez, na partida |
| `zerar()` | Apaga e reconstrói de `dados.js` |
| `planoAtual()` | O plano do paciente atual |
| `moduloLiberado(chave)` | **Use sempre antes de mostrar um serviço** |
| `trocarCliente(id)` | Troca o contrato e reaplica o tema |
| `aoMudar(fn)` | Assina mudanças de estado |

### `js/api.js`

A camada de serviço. Cada função espelha um endpoint real, com o contrato de
entrada e saída documentado no comentário acima dela.

Nenhuma tela chama `fetch` direto. **Nunca chame.** Toda chamada passa por
aqui. É isso que faz a troca por API real ser um trabalho de um arquivo só.

Leia [LIGAR-A-API-REAL.md](LIGAR-A-API-REAL.md).

### `js/ui.js`

As peças que aparecem mais de uma vez.

O construtor de elementos é `el(tag, props, filhos)`:

```js
el('button', { class: 'btn btn-primario', onclick: () => ir('#/conta') }, ['Abrir'])
```

Regras do `el`:

- `class` vira `className`
- `texto` vira `textContent`, e é o jeito seguro, sem risco de injeção
- `html` vira `innerHTML`, use só com texto que você mesmo escreveu
- qualquer chave começando com `on` e valor de função vira `addEventListener`
- valor `null`, `undefined` ou `false` é **ignorado**, o que permite escrever
  `condicao ? el(...) : null` dentro de uma lista de filhos

Os ícones são de traçado único, desenhados no próprio arquivo, sem biblioteca:
`ico('remedio', 20)`. A lista de nomes está na constante `CAMINHOS`.

Os estados prontos são `carregando()`, `vazio()`, `erro()` e `sucesso()`.
Use eles, não invente outros.

### `js/telas-paciente.js` e `js/telas-gestor.js`

Uma função exportada por tela. Toda função recebe `ir`, a função de navegação,
e devolve um nó ou um fragmento.

Telas que carregam dado usam o utilitário `assincrono(carregar, montar,
texto)`, que já trata os quatro estados:

```js
assincrono(
  () => api.historicoConsultas({}),        // 1. busca
  (dados, tentarDeNovo) => montarLista(dados),  // 2. monta com o resultado
  'Buscando suas consultas'                // 3. texto do carregamento
)
```

Se a promessa falhar, ele mostra a tela de erro com botão de tentar de novo,
sozinho. Você não escreve `try` nem `catch`.

### `js/max.js`

O assistente. Botão flutuante, painel lateral, contexto por rota, respostas
simuladas e o ponto de troca para uma IA de verdade.

A parte que interessa:

| Peça | Onde |
| --- | --- |
| Nome do assistente | `MAX_NOME`, no topo. **Provisório** |
| Fala e atalhos por rota | `CONTEXTOS` |
| As respostas simuladas | `respostaPaciente()` e `respostaGestor()` |
| Cálculo da próxima ação | `proximaAcaoPaciente()` e `proximaAcaoGestor()` |
| Ponto de troca para IA real | `configuracao` e `responderPorServico()` |

**As respostas do MAX são fiscalizadas pelo teste.** Sinal de gravidade tem
que mandar para o 192. Sintoma tem que receber encaminhamento e não avaliação.
Receita não pode sugerir troca. No perfil de gestor, saúde mental é grupo e
nunca pessoa. As palavras "diagnóstico" e "pré-diagnóstico" e o travessão são
barrados em qualquer resposta.

### `js/app.js`

Rotas, menus, tela de entrada, casca autenticada e a barra de demonstração.

A barra de demonstração **não faz parte do produto**. Quando o sistema for
para o cliente, some daqui e de `index.html`.

### `css/app.css`

Um arquivo, sem pré-processador. Dividido em blocos, com cabeçalho comentado
em cada um.

---

## O tema: uma cor por cliente

Isto veio do sistema oficial, que faz exatamente assim. Existe **uma variável
por cliente**, e todo o resto deriva dela por `color-mix`:

```css
--marca:        #5E5212;              /* a única cor do cliente */
--marca-2:      color-mix(in srgb, var(--marca) 62%, #22E0B0);
--marca-noite:  color-mix(in srgb, var(--marca) 16%, #0A1030);
--marca-suave:  color-mix(in srgb, var(--marca) 8%, #fff);
```

Trocar o tema inteiro é uma linha, em `estado.js`:

```js
document.documentElement.style.setProperty('--marca', cor);
```

**Não crie uma paleta por cliente.** Não é assim que o sistema oficial
funciona, e o cliente entrega uma cor só, mais os arquivos de imagem. A tela
de Identidade visual do gestor demonstra isso ao vivo.

---

## A armadilha de CSS que já mordeu este projeto

Um elemento com `display` definido pelo autor **vence** o `display: none` que
o navegador aplica ao atributo `hidden`. Foi assim que um modal ficou visível
para sempre por cima de todas as telas.

A defesa está no topo do CSS e **não pode ser removida**:

```css
[hidden] { display: none !important; }
```

Se você criar qualquer elemento que nasce com `hidden` e tem `display`
próprio, é esta regra que faz ele sumir de verdade.

Parente próximo: uma animação com `animation-fill-mode: both` fixa o valor
final da propriedade, e esse valor **vence** qualquer declaração que venha
depois. Se um elemento entra com animação, ele precisa **sair com animação**
também. Transição não funciona nesse caso.

---

## Camadas, de baixo para cima

Quando criar algo fixo na tela, respeite:

| `z-index` | O quê |
| --- | --- |
| 20 | Topo da página |
| 60 | Barra de demonstração |
| 70 | Menu lateral no celular |
| 95 | Botão do assistente |
| 100 | Painel do assistente |
| 110 | Modal |
| 115 | Avisos |
| 120 | Camada de boas-vindas |

Os avisos ficam no canto inferior direito, o mesmo canto do botão do
assistente. Eles já sobem para não colidir. Se mexer, confira os dois juntos.

---

## Responsividade

Três pontos de quebra: 1080, 900, 780 e 620 pixels.

- Em 780 o menu lateral vira gaveta, com véu por trás
- Tabela larga nunca estoura a página, ela rola dentro do próprio bloco, com
  a classe `rolagem-x`
- Em 780 o painel do assistente vira tela cheia
- A grade de quatro colunas vira duas em 1080 e uma em 620

Teste sempre em 375 pixels de largura. É onde quebra primeiro.

---

## Acessibilidade e movimento

- O bloco `prefers-reduced-motion` é o **último** do CSS e zera toda animação
  com `!important`. Não coloque nada depois dele
- Alvo de toque mínimo de 44 pixels, na variável `--alvo`
- Todo campo tem `label` ligado por `for`
- Os erros de formulário aparecem embaixo do campo, com texto, não só cor
- Enfeite visual leva `aria-hidden="true"`, como o fundo animado
