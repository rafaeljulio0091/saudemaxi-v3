# PAPEL

Atue como um **Engenheiro de Software Sênior / Tech Lead**, especialista em:

* Laravel 12
* PHP 8.2+
* Vue 3
* Composition API
* JavaScript ES6+
* Vue Router
* Pinia
* Axios
* Vite
* Tailwind CSS 4
* HTML5 semântico
* CSS responsivo
* UI/UX
* SOLID
* Clean Architecture
* Clean Code
* REST APIs
* Segurança de aplicações web
* Acessibilidade
* Arquitetura SPA integrada ao Laravel

Você será responsável por migrar e implementar o frontend do projeto **Saúde Maxi**, utilizando como referência os arquivos existentes no repositório de esboço, mas criando uma arquitetura definitiva e sustentável dentro do projeto Laravel `saudemaxi-v3`.

---

# OBJETIVO PRINCIPAL

Implementar no projeto:

`saudemaxi-v3`

o frontend baseado no projeto/esboço:

`esbo-o-saude-maxi-main`

O resultado NÃO deve ser simplesmente copiar HTML, CSS e JavaScript do protótipo.

O objetivo é **reimplementar corretamente o frontend utilizando Vue 3**, preservando:

* identidade visual;
* experiência do usuário;
* fluxos;
* regras funcionais;
* responsividade;
* estados das telas;
* comportamento das funcionalidades;
* regras de módulos;
* white label;
* regras específicas dos perfis;
* preparação para integração com API.

O projeto final deverá utilizar Laravel como aplicação principal e Vue 3 como camada de frontend.

---

# FONTES DE VERDADE

Antes de implementar qualquer código, analise completamente os dois projetos.

## 1. Projeto de destino

```text
saudemaxi-v3/
```

Este é o projeto definitivo.

Preserve sua arquitetura Laravel existente e implemente o frontend nele.

Atualmente o projeto possui aproximadamente:

```text
Laravel 12
PHP >= 8.2
Vite 7
Tailwind CSS 4
Axios
```

O Vue ainda deverá ser configurado.

---

## 2. Referência funcional principal

Analise prioritariamente:

```text
esbo-o-saude-maxi-main/
└── sistema-saude-maxi/
```

Principalmente:

```text
docs/ARQUITETURA.md
docs/LIGAR-A-API-REAL.md
docs/TAREFAS-COMUNS.md

js/app.js
js/api.js
js/estado.js
js/dados.js
js/ui.js
js/telas-paciente.js
js/telas-gestor.js
js/max.js

css/app.css
index.html
```

Esta implementação deve ser considerada a principal referência para:

* regras funcionais;
* comportamento;
* navegação;
* módulos;
* perfis;
* estados;
* simulação de APIs;
* assistente MAX;
* multi-cliente;
* white label.

---

## 3. Referência visual e de produto

Também analise:

```text
esbo-o-saude-maxi-main/
└── esboco-saude-maxi/
```

Especialmente:

```text
fonte/index.html
README.md
```

Esse protótipo contém aproximadamente **37 telas validadas visualmente**.

Utilize-o para complementar:

* layout;
* identidade visual;
* fluxos detalhados;
* jornadas;
* estados intermediários;
* conteúdo;
* UI/UX.

Não copie sua arquitetura monolítica.

---

# PRIORIDADE EM CASO DE CONFLITO

Caso haja diferenças entre os arquivos, utilize esta ordem:

1. regras documentadas em `sistema-saude-maxi/docs`;
2. comportamento do `sistema-saude-maxi`;
3. protótipo visual `esboco-saude-maxi`;
4. decisões arquiteturais necessárias para Vue 3;
5. arquitetura atual do Laravel.

Não invente regra de negócio para resolver conflito.

Documente a divergência encontrada.

---

# ETAPA 1: AUDITORIA ANTES DA IMPLEMENTAÇÃO

Antes de modificar arquivos, faça um levantamento completo.

Identifique:

* dependências atuais;
* estrutura Laravel;
* Vite;
* Tailwind;
* arquivos Vue existentes;
* rotas Laravel;
* assets;
* autenticação existente;
* endpoints existentes;
* controllers;
* services;
* models;
* middlewares.

Depois analise todo o projeto de referência.

Produza internamente um mapeamento:

```text
Tela original
    ↓
Rota
    ↓
Vue View
    ↓
Componentes
    ↓
Store
    ↓
Service
    ↓
API necessária
```

Não comece copiando arquivos antes de concluir esse levantamento.

---

# ETAPA 2: CONFIGURAR VUE 3

Configure Vue 3 corretamente no projeto Laravel.

Adicionar somente as dependências necessárias, preferencialmente:

```text
vue
@vitejs/plugin-vue
vue-router
pinia
```

Utilizar o Axios já existente.

Atualizar corretamente:

```text
vite.config.js
resources/js/app.js
```

Criar um ponto único de inicialização.

Exemplo conceitual:

```text
Laravel
   ↓
Blade principal
   ↓
#app
   ↓
Vue App
   ↓
Vue Router
   ↓
Layouts / Views
```

Não transformar Blade em uma segunda camada de componentes paralela ao Vue.

Blade deverá atuar principalmente como bootstrap/container da SPA.

---

# ETAPA 3: ARQUITETURA DO FRONTEND

Criar uma estrutura organizada aproximadamente como:

```text
resources/
├── css/
│   ├── app.css
│   ├── tokens.css
│   ├── components.css
│   └── utilities.css
│
├── js/
│   ├── app.js
│   │
│   ├── components/
│   │   ├── common/
│   │   ├── layout/
│   │   ├── navigation/
│   │   ├── feedback/
│   │   ├── forms/
│   │   ├── healthcare/
│   │   └── max/
│   │
│   ├── layouts/
│   │   ├── PatientLayout.vue
│   │   ├── ManagerLayout.vue
│   │   └── AuthLayout.vue
│   │
│   ├── views/
│   │   ├── auth/
│   │   ├── patient/
│   │   ├── manager/
│   │   └── errors/
│   │
│   ├── router/
│   │   ├── index.js
│   │   └── routes.js
│   │
│   ├── stores/
│   │   ├── auth.js
│   │   ├── tenant.js
│   │   ├── patient.js
│   │   ├── modules.js
│   │   ├── appointments.js
│   │   └── ui.js
│   │
│   ├── services/
│   │   ├── http.js
│   │   ├── auth.service.js
│   │   ├── patient.service.js
│   │   ├── appointment.service.js
│   │   ├── pharmacy.service.js
│   │   ├── plan.service.js
│   │   └── max.service.js
│   │
│   ├── composables/
│   │   ├── useAuth.js
│   │   ├── useTenant.js
│   │   ├── useModules.js
│   │   └── useAsyncState.js
│   │
│   ├── constants/
│   │
│   └── utils/
│
└── views/
    └── app.blade.php
```

Essa estrutura é uma referência.

Adapte caso a arquitetura existente indique solução melhor.

Evite:

```text
components gigantes
views com milhares de linhas
JavaScript global
window.*
estado global manual
DOM manipulation
document.querySelector para controlar componentes Vue
innerHTML desnecessário
lógica de API dentro das Views
```

---

# ETAPA 4: MIGRAR O DESIGN SYSTEM

Analise cuidadosamente:

```text
sistema-saude-maxi/css/app.css
```

Preserve o design visual existente.

Entretanto, converta a estrutura em um pequeno **Design System reutilizável**.

Preserve conceitos existentes como:

```text
--marca
--marca-hover
--marca-2
--marca-escura
--marca-noite
--marca-suave
--marca-linha
--marca-grad
```

A cor da marca deve continuar sendo dinâmica.

O sistema possui conceito de **white label por cliente**.

A arquitetura deverá permitir:

```javascript
tenant.brandColor
```

alimentar variáveis CSS dinamicamente.

Exemplo conceitual:

```javascript
document.documentElement.style.setProperty(
    '--marca',
    tenant.brandColor
);
```

Não criar uma paleta manual independente para cada cliente.

As cores derivadas devem continuar sendo calculadas a partir da cor principal sempre que possível.

---

# COMPONENTES REUTILIZÁVEIS

Transforme padrões repetidos do protótipo em componentes Vue.

Por exemplo:

```text
AppButton
AppCard
AppModal
AppAlert
AppBadge
AppInput
AppSelect
AppTextarea
AppToggle
AppTable
AppPagination

LoadingState
EmptyState
ErrorState
SuccessState

PageHeader
Sidebar
Topbar
MobileNavigation

ServiceCard
AppointmentCard
PatientCard
ModuleCard
PlanCard

MaxAssistant
MaxMessage
MaxSuggestions
```

Evite duplicação entre as telas.

---

# ETAPA 5: ROTEAMENTO

Substituir o roteamento manual existente no `js/app.js` por **Vue Router**.

Implementar inicialmente as áreas equivalentes.

## Paciente

```text
/inicio
/orientacao
/atendimento
/agendamento
/farmacia
/receita/:id
/farmacias
/consultas
/conta
/nr1
/ajuda
```

## Gestor

```text
/gestor/painel
/gestor/pacientes
/gestor/pacientes/:id
/gestor/consultas
/gestor/planos
/gestor/identidade
/gestor/integracao
```

Utilizar `meta` nas rotas quando adequado.

Exemplo:

```javascript
meta: {
    profile: 'patient',
    module: 'pharmacy',
    requiresAuth: true,
}
```

Criar navigation guards para:

* autenticação;
* perfil;
* módulo habilitado;
* autorização.

Não espalhar essas verificações pelas Views.

---

# FLUXOS INTERMEDIÁRIOS DO PROTÓTIPO

O protótipo visual possui aproximadamente 37 estados/telas.

Nem todos precisam necessariamente virar rotas independentes.

Fluxos como:

```text
envio de receita
leitura da receita
resultado

seleção de especialidade
seleção de data
seleção de horário
confirmação

fila de atendimento
atendimento iniciado
atendimento encerrado
```

podem ser implementados como:

```text
Views
+
subcomponentes
+
estado do fluxo
```

Não criar dezenas de rotas artificiais apenas para reproduzir cada frame do protótipo.

Preserve a jornada do usuário.

---

# ETAPA 6: GERENCIAMENTO DE ESTADO

Migrar o comportamento atualmente existente em:

```text
js/estado.js
```

para Pinia e composables.

Separar responsabilidades.

Evitar criar uma única Store gigantesca.

Exemplo:

```text
useAuthStore
useTenantStore
usePatientStore
useModuleStore
useAppointmentStore
useUiStore
```

O estado deve possuir responsabilidades claras.

---

# MÓDULOS E PLANOS

Preservar o conceito existente de módulos contratados.

Exemplos:

```text
orientacao
atendimento
agendamento
farmacia
nr1
```

Criar uma única fonte para responder:

```javascript
moduleEnabled('farmacia')
```

Não espalhar verificações como:

```javascript
if (plan === ...)
```

pelos componentes.

Plano e módulo são conceitos diferentes.

A View não deve conhecer detalhes de regras contratuais.

---

# ETAPA 7: CAMADA DE SERVICES

O princípio existente em:

```text
js/api.js
```

deve ser preservado.

Nenhuma View Vue deve realizar:

```javascript
fetch(...)
```

ou:

```javascript
axios.get(...)
```

diretamente.

Utilizar:

```text
View
 ↓
Store / Composable
 ↓
Service
 ↓
HTTP Client
 ↓
Laravel API
```

Centralizar Axios em:

```text
services/http.js
```

Configurando:

* base URL;
* headers;
* CSRF;
* interceptors;
* 401;
* 403;
* 404;
* 422;
* 500;
* timeout;
* erros de rede.

---

# REGRA CRÍTICA SOBRE API EXTERNA

O esboço documenta uma API externa de telemedicina.

O token de serviço dessa API **NUNCA poderá estar no Vue ou JavaScript enviado ao navegador**.

Nunca implementar:

```javascript
Authorization: Bearer SERVICE_TOKEN
```

diretamente no frontend.

Arquitetura obrigatória:

```text
Vue
 ↓
Laravel
 ↓
Service Laravel
 ↓
API externa Saúde Maxi
```

As credenciais devem permanecer somente no backend e `.env`.

---

# ETAPA 8: ESTADOS ASSÍNCRONOS

O sistema atual possui conceitos equivalentes a:

```text
carregando
vazio
erro
sucesso
```

Padronizar isso.

Criar componentes compartilhados como:

```text
LoadingState.vue
EmptyState.vue
ErrorState.vue
SuccessState.vue
```

Toda chamada remota deve considerar:

```text
idle
loading
success
empty
error
```

Fornecer opção de retry nos erros recuperáveis.

---

# ETAPA 9: TELAS DO PACIENTE

Migrar as funcionalidades existentes em:

```text
telas-paciente.js
```

para Views e componentes Vue.

Cobrir pelo menos:

### Dashboard

* início;
* serviços disponíveis;
* serviços indisponíveis pelo plano;
* próxima ação;
* contexto do usuário.

### Orientação em saúde

* entrada;
* conversa;
* encaminhamentos;
* estados;
* histórico quando aplicável.

### Atendimento imediato

* solicitação;
* fila;
* redirecionamento quando necessário;
* estado da consulta.

### Agendamento

Implementar o fluxo equivalente a:

```text
especialidade
↓
dia
↓
horário
↓
profissional
↓
confirmação
```

### Farmácia

* receitas;
* envio de foto;
* processamento;
* resultado;
* farmácias próximas.

### Consultas

* histórico;
* status;
* consulta futura;
* consulta encerrada.

### Conta

* informações pessoais;
* configurações permitidas.

### Ajuda imediata

Preservar todas as regras de segurança existentes.

---

# ETAPA 10: TELAS DO GESTOR

Migrar as funcionalidades existentes em:

```text
telas-gestor.js
```

Cobrir:

### Painel

* indicadores;
* cards;
* gráficos;
* dados agregados.

### Pacientes

* listagem;
* pesquisa;
* filtros;
* paginação;
* estados;
* abertura de ficha.

### Ficha do paciente

* informações permitidas;
* status;
* tags;
* ações disponíveis.

### Consultas

* listagem;
* filtros;
* situação;
* pagamento;
* ações.

### Planos e módulos

Permitir representação clara da relação:

```text
Plano
    ↓
Módulos liberados
```

### Identidade visual

Preservar a capacidade de preview do white label.

### Integrações

Exibir status das integrações existentes e pendentes sem inventar funcionalidades.

---

# ETAPA 11: ASSISTENTE MAX

Migrar a arquitetura existente de:

```text
js/max.js
```

para componentes e Services Vue.

Separar:

```text
UI
contexto
regras
service
respostas
```

Estrutura sugerida:

```text
components/max/
    MaxAssistant.vue
    MaxPanel.vue
    MaxMessage.vue
    MaxActions.vue

services/
    max.service.js

stores/
    max.js
```

Não colocar regras críticas dentro do componente visual.

---

# REGRAS INVIOLÁVEIS DO PRODUTO

Preserve as regras existentes do projeto.

## 1.

Não alterar nem reimplementar funcionalidades pertencentes à plataforma de telemedicina externa.

A nova aplicação somente deverá consumi-las através da integração definida.

## 2.

Na dúvida sobre construir algo ou consumir algo já existente, primeiro considere a possibilidade da funcionalidade pertencer à plataforma externa.

## 3.

O produto orienta e encaminha.

Não deve concluir doenças.

Não utilizar textos de interface que afirmem diagnóstico médico.

## 4.

Nunca sugerir substituição de medicamento.

## 5.

Decisões do assistente que necessitem auditoria deverão possuir arquitetura preparada para rastreabilidade.

## 6.

Na funcionalidade relacionada à NR-1, o gestor deverá trabalhar com **informações agregadas**, nunca expondo informações individuais de saúde mental de um paciente/colaborador.

Não implementar funcionalidades individuais enquanto a regra de negócio estiver bloqueada ou indefinida.

## 7.

O cliente/tenant deve ser obtido do contexto autenticado.

Nunca confiar em algo como:

```text
?tenant_id=10
```

para determinar isolamento de dados.

## 8.

Utilizar português brasileiro em toda a interface.

## 9.

Preservar boa legibilidade, fontes e áreas clicáveis adequadas para utilização inclusive em estação/totem de saúde.

---

# MULTI-TENANT / WHITE LABEL

A arquitetura deve estar preparada para múltiplos clientes.

Exemplo conceitual de contexto:

```javascript
{
    id,
    name,
    logo,
    brandColor,
    greeting,
    modules,
    settings
}
```

Os componentes não devem possuir nomes, cores ou regras de clientes hardcoded.

Evitar:

```javascript
if (cliente === 'queimados')
```

dentro de Views.

Preferir dados fornecidos pelo contexto/configuração.

---

# UI/UX

Preservar a identidade visual existente, porém corrigir problemas estruturais encontrados.

Requisitos:

* mobile first;
* responsivo;
* desktop;
* tablet;
* smartphone;
* preparado para totens;
* touch friendly;
* feedback visual;
* hierarquia clara;
* contraste adequado;
* loaders;
* skeletons quando fizer sentido;
* empty states;
* error states;
* foco de teclado;
* acessibilidade.

Testar no mínimo:

```text
375px
768px
1024px
1440px
```

---

# ACESSIBILIDADE

Utilizar adequadamente:

```text
aria-label
aria-expanded
aria-current
aria-live
role
for
id
```

Garantir navegação por teclado.

Modais devem:

* capturar corretamente o foco;
* permitir Escape;
* devolver foco ao elemento anterior quando fechados.

---

# RESPONSIVIDADE DO MENU

Migrar corretamente o comportamento existente de sidebar.

Desktop:

```text
sidebar fixa
+
topbar
+
conteúdo
```

Mobile:

```text
menu drawer
+
backdrop
+
controle por botão
```

O estado da sidebar deve pertencer à camada de UI, não ao DOM global.

---

# DESIGN TOKENS

Não espalhar valores arbitrários pela aplicação.

Organizar:

```text
cores
spacing
border radius
shadows
typography
transitions
z-index
```

Preservar o conceito já existente no CSS do protótipo.

---

# REGRA PARA `hidden`

O código antigo possui uma proteção importante:

```css
[hidden] {
    display: none !important;
}
```

Caso o atributo `hidden` ainda seja utilizado, preservar comportamento equivalente.

No Vue, preferir quando adequado:

```text
v-if
v-show
```

em vez de manipulação manual do atributo.

---

# SEGURANÇA

Não utilizar dados externos através de:

```javascript
innerHTML
v-html
```

sem sanitização.

Preferir interpolação Vue:

```vue
{{ value }}
```

Nunca colocar:

* tokens;
* secrets;
* credenciais;
* chaves privadas;

no bundle JavaScript.

---

# SOLID

Aplicar SOLID de forma pragmática.

Especialmente:

### Single Responsibility

Views exibem telas.

Services integram APIs.

Stores gerenciam estado compartilhado.

Composables encapsulam comportamentos reutilizáveis.

Components encapsulam UI.

### Dependency Inversion

Views não devem depender diretamente da implementação HTTP.

---

# CLEAN ARCHITECTURE

Manter a separação conceitual:

```text
Presentation
      ↓
Application
      ↓
Services
      ↓
Infrastructure/API
```

Não criar complexidade desnecessária apenas para parecer arquitetural.

---

# NÃO FAZER

Não:

* copiar `index.html` inteiro para Blade;
* copiar os arquivos JavaScript antigos e mantê-los controlando DOM;
* utilizar jQuery;
* misturar Vue com manipulação direta do DOM sem necessidade;
* colocar todas as telas em `App.vue`;
* criar um único store para toda a aplicação;
* fazer Axios diretamente nas Views;
* armazenar token da API externa no navegador;
* reimplementar funcionalidades já existentes na plataforma externa;
* quebrar funcionalidades Laravel existentes;
* alterar models/migrations/backend sem necessidade da tarefa;
* implementar regra de negócio não validada;
* implementar dados individuais de NR-1;
* hardcodar clientes;
* hardcodar planos;
* ignorar mobile;
* remover comportamentos existentes sem justificativa.

---

# COMPATIBILIDADE COM BACKEND

Não altere indiscriminadamente:

```text
Controllers
Models
Migrations
Policies
Middleware
Services
routes/api.php
```

Caso o frontend necessite de um endpoint ainda inexistente:

1. identifique a necessidade;
2. procure se já existe funcionalidade equivalente;
3. documente o contrato esperado;
4. implemente somente se fizer parte clara do escopo;
5. mantenha compatibilidade com o backend.

---

# DESACOPLAMENTO ENTRE MOCK E API

Como várias integrações externas ainda não estão disponíveis, permita executar o frontend independentemente.

Criar arquitetura que suporte:

```text
Mock Service

ou

Real Service
```

sem modificar as Views.

Exemplo conceitual:

```text
PatientService
       ↓
MockPatientRepository

ou

ApiPatientRepository
```

Não precisa aplicar padrões excessivamente complexos se uma abstração simples resolver.

---

# ETAPA 12: ROTAS LARAVEL

Criar um Blade dedicado para montar a SPA.

Exemplo:

```text
resources/views/app.blade.php
```

O Laravel deverá carregar:

```blade
@vite([
    'resources/css/app.css',
    'resources/js/app.js'
])
```

e disponibilizar:

```html
<div id="app"></div>
```

Se utilizar Vue Router em history mode, configurar fallback Laravel adequadamente sem capturar:

```text
/api/*
```

nem sobrescrever rotas de backend existentes.

---

# ETAPA 13: QUALIDADE

Após cada grupo significativo de alterações execute:

```bash
npm run build
```

Também executar os testes Laravel existentes:

```bash
php artisan test
```

Se necessário:

```bash
php artisan route:list
```

Verificar se não houve alteração inesperada de rotas.

---

# TESTES FRONTEND

Criar pelo menos testes ou verificações estruturadas para regras críticas.

Priorizar:

* modules guard;
* profile guard;
* tenant;
* estados das consultas;
* regras do MAX;
* componentes críticos;
* services.

Não buscar cobertura artificial.

Testar principalmente regras de alto impacto.

---

# ESTADOS OFICIAIS DAS CONSULTAS

Não inventar novos estados.

Preservar:

```text
SCHEDULED
PENDING
WAITING_HELPDESK
ONGOING_HELPDESK
WAITING_DOCTOR
ONGOING_DOCTOR
FINISHED
CANCELED
```

Centralizar tradução e apresentação desses estados.

Exemplo:

```text
constants/consultationStatus.js
```

Não duplicar switch/case pelas Views.

---

# API DE AGENDAMENTO

Preservar o fluxo documentado:

```text
1. specialties
2. business-days
3. available-times
4. doctors
5. create-consultation
6. update-payment-status
```

Cada etapa deve possuir:

* loading;
* error;
* retry;
* validação;
* estado vazio.

---

# RECONCILIAÇÃO DE CONSULTAS

A documentação informa que a plataforma externa não possui webhook adequado para todos os eventos.

Não construir arquitetura dependente de webhook inexistente.

O frontend deve refletir dados fornecidos pelo Laravel.

A lógica de polling ou reconciliação, caso necessária, deverá ficar devidamente encapsulada e preferencialmente coordenada pelo backend/service.

---

# ORGANIZAÇÃO DOS COMMITS

Implementar incrementalmente.

Sugestão:

```text
feat(frontend): configure Vue 3 infrastructure

feat(frontend): create application layouts

feat(frontend): implement design system

feat(frontend): implement application routing

feat(frontend): implement tenant and module stores

feat(patient): implement patient dashboard

feat(patient): implement health guidance

feat(patient): implement appointments

feat(patient): implement pharmacy

feat(manager): implement management dashboard

feat(manager): implement patient management

feat(manager): implement plans and modules

feat(manager): implement branding

feat(max): migrate assistant interface
```

Não fazer uma refatoração gigantesca sem pontos verificáveis.

---

# ORDEM OBRIGATÓRIA DE IMPLEMENTAÇÃO

## Fase 1

Auditoria e mapeamento.

## Fase 2

Vue + Vite + Router + Pinia.

## Fase 3

Design System.

## Fase 4

Layouts.

## Fase 5

Stores e contexto de tenant.

## Fase 6

Services.

## Fase 7

Autenticação/entrada.

## Fase 8

Dashboard do paciente.

## Fase 9

Funcionalidades do paciente.

## Fase 10

Dashboard do gestor.

## Fase 11

Funcionalidades do gestor.

## Fase 12

Assistente MAX.

## Fase 13

Responsividade e acessibilidade.

## Fase 14

Testes.

## Fase 15

Build final.

---

# CRITÉRIOS DE ACEITE

A implementação somente será considerada concluída quando:

### Arquitetura

* Vue 3 estiver corretamente integrado ao Laravel 12;
* Vue Router estiver configurado;
* Pinia estiver configurado;
* Services estiverem desacoplados das Views;
* não existir manipulação manual desnecessária do DOM.

### Funcionalidade

* fluxos principais do protótipo estiverem reproduzidos;
* perfil paciente estiver funcional;
* perfil gestor estiver funcional;
* módulos respeitarem contratos;
* white label funcionar;
* navegação funcionar.

### UI

* identidade visual estiver compatível com o esboço;
* desktop funcionar;
* tablet funcionar;
* smartphone funcionar;
* drawer mobile funcionar;
* estados loading/error/empty estarem presentes.

### Segurança

* nenhum token externo no frontend;
* nenhum secret no bundle;
* nenhuma informação de tenant confiada diretamente à URL;
* saída de dados protegida contra HTML arbitrário.

### Build

Obrigatoriamente:

```bash
npm run build
```

sem erros.

E:

```bash
php artisan test
```

sem regressões atribuídas às alterações.

---

# ENTREGA FINAL

Ao concluir, apresente obrigatoriamente:

## 1. Resumo

Descreva objetivamente o que foi implementado.

## 2. Arquivos

Liste:

```text
criados
alterados
removidos
```

## 3. Arquitetura

Apresente a estrutura final relevante.

## 4. Rotas

Liste as rotas Vue implementadas.

## 5. Componentes

Liste os principais componentes reutilizáveis.

## 6. Stores

Liste as Stores Pinia implementadas e suas responsabilidades.

## 7. Services

Liste Services e endpoints utilizados.

## 8. Pendências

Informe integrações que permaneceram simuladas ou bloqueadas.

Não invente APIs para eliminar uma pendência.

## 9. Validações

Informe resultado de:

```bash
npm run build
php artisan test
```

## 10. Divergências

Caso algum comportamento do protótipo tenha sido alterado por motivo arquitetural, de segurança, responsividade ou regra de negócio, explique exatamente:

```text
como era
como ficou
por que mudou
```

---

# REGRA FINAL

Não trate esta tarefa como uma simples conversão:

```text
HTML → Vue
```

Trate-a como uma **migração de um protótipo funcional validado para um frontend de produção integrado ao Laravel 12**.

Preserve o que está validado visual e funcionalmente, mas reconstrua sua implementação seguindo:

```text
Vue 3
Composition API
Componentização
Vue Router
Pinia
Services
SOLID
Clean Architecture
Segurança
Responsividade
Acessibilidade
Manutenibilidade
```

Antes de implementar qualquer funcionalidade nova que não esteja claramente representada nos arquivos fornecidos, procure evidências no projeto e na documentação.

Não invente regras de negócio.

Não quebre o que já funciona.
