# Saúde Maxi: sistema funcional

Publicado em `https://saude-maxi-app.vercel.app`

**Esta é a base de produto.** A pasta `esboco-saude-maxi/` deste mesmo
repositório é o protótipo de venda, com 37 telas num arquivo só. As duas
convivem e cada uma tem um propósito. Você trabalha aqui.

---

## 1. Rodar, em trinta segundos

Precisa de um servidor HTTP. Não funciona abrindo o arquivo com duplo clique,
porque o sistema usa módulos nativos do navegador e o protocolo `file://` os
bloqueia.

```
cd sistema-saude-maxi
python -m http.server 8000
```

Abra `http://localhost:8000`.

Qualquer servidor estático serve. `npx serve`, `php -S`, o Live Server do
VS Code. Não existe passo de compilação.

### Entrar

Os campos já vêm preenchidos. Clique em **Entrar**.

No topo da tela existe uma **barra de demonstração**. Ela não faz parte do
produto, é ferramenta de trabalho. Com ela você troca:

| Controle | O que faz |
| --- | --- |
| Perfil | Alterna entre paciente e gestor da clínica |
| Cliente | Troca o contrato. Muda cor, logo, textos e regras |
| Plano | Troca o plano do paciente. Liga e desliga módulos na hora |
| Rede | Força os quatro estados: normal, lenta, com falha, sem dados |
| Zerar dados | Apaga o `localStorage` e volta tudo ao início |

**Comece por aqui:** entre como gestor, abra **Planos e módulos**, desligue o
módulo Farmácia popular, volte para o perfil de paciente. O cartão da farmácia
aparece apagado. São dois cliques e é a demonstração mais importante da
arquitetura: módulo é contrato, não código.

---

## 2. Testar

```
node teste/fumaca.mjs
```

Precisa de Node 18 ou mais novo. Nenhuma dependência, nenhum `npm install`.

O teste monta um DOM mínimo em Node, importa os módulos de verdade e desenha
todas as telas dos dois perfis, para os três clientes, nos quatro modos de
rede. Depois exercita os pontos da camada de serviço e as regras do
assistente.

**Hoje: 134 verificações, 134 passando.**

Rode antes de todo commit. Se quebrar, quebrou de verdade.

### O que o teste NÃO pega

Ele verifica execução de JavaScript, **não renderização**. Defeito de CSS
passa batido. Já aconteceu: uma regra de `display` do autor venceu o atributo
`hidden` e um modal ficou visível para sempre. O teste passou.

Depois de mexer em CSS, abra no navegador. Sempre.

---

## 3. Mapa dos arquivos

```
index.html                casca, barra de demonstração, camadas de sobreposição
css/app.css               tudo de estilo. Um arquivo, sem pré-processador
js/
  dados.js                dados simulados. Todos inventados
  estado.js               estado da aplicação, persistência, tema por cliente
  api.js                  CAMADA DE SERVIÇO. Espelha a API real
  ui.js                   peças reutilizáveis, ícones, estados, modais
  telas-paciente.js       11 telas do paciente
  telas-gestor.js         7 telas do gestor
  max.js                  o assistente MAX
  app.js                  rotas, menu, entrada, casca autenticada
teste/fumaca.mjs          teste automatizado
docs/                     leia antes de mexer
```

Sem etapa de compilação, sem dependência externa, sem CDN. Módulos nativos do
navegador. Publica como estático.

**Leia nesta ordem quando for mexer:**

1. [docs/ARQUITETURA.md](docs/ARQUITETURA.md), como o sistema se desenha e por quê
2. [docs/TAREFAS-COMUNS.md](docs/TAREFAS-COMUNS.md), receita passo a passo para as tarefas do dia a dia
3. [docs/LIGAR-A-API-REAL.md](docs/LIGAR-A-API-REAL.md), como trocar simulação por API de verdade

---

## 4. Rotas

Roteador por hash, sem biblioteca. O mapa vive em `js/app.js`, na constante
`ROTAS`.

### Paciente

| Rota | Tela | Módulo que libera |
| --- | --- | --- |
| `#/inicio` | Início, o ambiente | sempre |
| `#/orientacao` | Orientação em saúde | `orientacao` |
| `#/atendimento` | Falar com um médico agora | `atendimento` |
| `#/agendamento` | Agendar consulta | `agendamento` |
| `#/farmacia` | Farmácia popular | `farmacia` |
| `#/receita/:id` | Resultado da receita | `farmacia` |
| `#/farmacias` | Farmácias próximas | `farmacia` |
| `#/consultas` | Minhas consultas | sempre |
| `#/conta` | Minha conta | sempre |
| `#/nr1` | Saúde mental | `nr1`, hoje bloqueado |
| `#/ajuda` | Ajuda imediata | sempre |

### Gestor da clínica

| Rota | Tela |
| --- | --- |
| `#/g/painel` | Painel, indicadores e gráficos |
| `#/g/pacientes` | Lista com busca e quatro filtros |
| `#/g/paciente/:id` | Ficha do paciente |
| `#/g/consultas` | Consultas, com marcação de pagamento |
| `#/g/planos` | O de para de plano por módulo |
| `#/g/identidade` | White label com prévia ao vivo |
| `#/g/integracao` | Os 21 pontos de integração e a situação de cada um |

Rota desconhecida cai numa tela de não encontrado. Rota do perfil errado
redireciona para o início do perfil atual. Rota de módulo não contratado
mostra a explicação, não um erro.

---

## 5. As dez regras invioláveis

Não são preferência de estilo. Vieram do contrato e das reuniões com o
cliente. Quebrar qualquer uma delas é retrabalho garantido.

1. **Não alterar a plataforma de telemedicina existente.** Ela já está em
   produção com outros clientes, é de outro fornecedor. A camada nova só
   consome a API dela.
2. Na dúvida entre implementar e já existir, a resposta padrão é: **já
   existe**. Pergunte antes de construir.
3. O produto **orienta e encaminha, nunca conclui sobre doença.** As palavras
   "diagnóstico" e "pré-diagnóstico" são proibidas na interface.
4. **Nunca sugerir troca de medicamento.** Substituição é ato médico.
5. Registro auditável de toda decisão do assistente.
6. **NR-1: o gestor vê o time, nunca a pessoa.** Nada de alerta individual no
   banco nem na API.
7. Isolamento por cliente em camada única, com o cliente vindo do contexto
   autenticado, nunca de parâmetro na URL.
8. **Sem travessão** em texto, código ou comentário. Use ponto, vírgula ou
   dois pontos.
9. Nenhum vocabulário de disputa eleitoral. A palavra "campanha" é vetada,
   porque parte dos contratos é com prefeitura.
10. Português do Brasil, fonte e botão grandes, por causa da estação física
    que vai ficar em posto de saúde.

O teste de fumaça fiscaliza as regras 3, 4, 6 e 8 nas respostas do assistente.
As outras dependem de você.

---

## 6. O que está bloqueado, e por quê

Não tente construir estes itens. Eles não estão parados por falta de tempo,
estão parados por falta de decisão.

| O que | Trava |
| --- | --- |
| **Trilha de saúde mental, NR-1** | O cliente disse duas coisas na mesma reunião: relatório individual para o RH, e confidencialidade destacada. Envolve LGPD. Enquanto não houver definição, nada individual é construído |
| **Chegada da receita** | A plataforma **não tem** endpoint de prescrição nem webhook. A receita sai pela Mevo dentro do atendimento e não volta para cá. A foto continua sendo o caminho que funciona |
| **Cobertura real de medicamento** | Sem fonte oficial da lista custeada pela farmácia popular |
| **Ligar a API de verdade** | Depende do token de serviço e do ambiente de homologação, que o fornecedor ainda não entregou |

---

## 7. Publicar

```
vercel deploy --prod --yes
```

A partir desta pasta. O projeto na Vercel já existe e já está apontado para
`saude-maxi-app.vercel.app`.

O `vercel.json` manda cabeçalhos que bloqueiam indexação. **Não remova.** O
sistema não deve aparecer em buscador enquanto estiver em construção.

---

## 8. Sobre os dados

Tudo em `js/dados.js` é inventado. Nenhum dado real de pessoa. Os documentos
são sequências de dígito repetido, inválidas por construção, que nunca
coincidem com documento real.

A persistência é `localStorage`, no navegador de quem abre. Nada sai da
máquina. Nenhuma chamada de rede sai do sistema hoje.

**Ao ligar a API real, isso muda.** Leia
[docs/LIGAR-A-API-REAL.md](docs/LIGAR-A-API-REAL.md) antes.
