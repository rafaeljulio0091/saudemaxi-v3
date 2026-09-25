# Papel

Atue como um arquiteto de software sênior e engenheiro de segurança especializado em:

- Laravel 12
- PHP 8+
- Vue 3
- Inertia.js
- Tailwind CSS
- APIs REST
- MySQL/MariaDB
- Clean Architecture
- SOLID
- aplicações SaaS multi-tenant
- segurança de aplicações web
- proteção de dados pessoais
- LGPD
- integrações de telemedicina

# Objetivo

Criar uma estrutura de **Engineering Harness** para o projeto `saudemaxi-v3`, com o objetivo de aumentar a assertividade, segurança, rastreabilidade e previsibilidade das alterações realizadas por agentes de IA.

O Harness deve funcionar como uma camada de governança para agentes como o Codex.

Antes de criar qualquer arquivo, analise cuidadosamente o repositório existente.

Não assuma que uma tecnologia, classe, tabela ou padrão existe apenas porque foi mencionado neste prompt.

O código atual é a fonte de verdade.

---

# REGRA CENTRAL

Nesta tarefa:

- NÃO alterar funcionalidades de produção;
- NÃO refatorar código existente;
- NÃO alterar regras de negócio;
- NÃO alterar schema;
- NÃO criar migrations de domínio;
- NÃO substituir autenticação existente;
- NÃO alterar fluxos de login, cadastro ou recuperação de senha;
- NÃO alterar integração LSX existente;
- NÃO alterar frontend funcional;
- NÃO trocar arquitetura;
- NÃO instalar novas dependências apenas para criar o Harness.

O objetivo é exclusivamente:

1. analisar;
2. documentar;
3. criar regras para agentes;
4. criar mecanismos seguros de validação.

---

# ETAPA 1 — INSPEÇÃO DO PROJETO

Antes de criar o Harness, identifique no código:

## Backend

- Models;
- Controllers;
- Services;
- Actions;
- Repositories;
- DTOs;
- Form Requests;
- Policies;
- Gates;
- Middleware;
- Jobs;
- Events;
- Listeners;
- Commands;
- migrations;
- observers;
- APIs;
- integrações externas.

## Frontend

Identifique como o frontend realmente está estruturado:

- Vue 3;
- Inertia.js;
- Tailwind;
- layouts;
- componentes;
- páginas;
- composables;
- stores;
- Axios/fetch;
- tratamento de sessão;
- validações.

Não introduza Vue Router, Pinia, Axios ou qualquer outra camada se o projeto já possuir abordagem diferente.

Evite duplicação de responsabilidades.

---

# AUTENTICAÇÃO

Analise o mecanismo atual de autenticação antes de documentar regras.

Login, cadastro e recuperação de senha existentes devem ser tratados como fluxos críticos.

Não criar mecanismo paralelo de autenticação.

Não misturar:

- sessão Laravel;
- tokens personalizados;
- autenticação externa;

sem existir decisão arquitetural explícita no código.

Documentar:

- fluxo atual;
- regeneração de sessão;
- CSRF;
- cookies;
- middleware;
- autorização;
- logout;
- recuperação de senha.

---

# INTEGRAÇÃO DE TELEMEDICINA

Mapear a integração existente com a plataforma externa LSX Medical.

A arquitetura esperada deve ser validada no código:

```text
Browser
   ↓
Laravel SaúdeMaxi
   ↓
Camada dedicada de integração
   ↓
LSX Medical API
```

Nunca expor token da LSX no frontend.

Variáveis como:

```text
TELEMEDICINE_API_URL
TELEMEDICINE_API_TOKEN
```

devem permanecer exclusivamente no backend/configuração segura.

Nunca enviar o token da clínica para:

- Vue;
- JavaScript;
- localStorage;
- sessionStorage;
- HTML;
- logs públicos;
- respostas de API.

---

# CREDENCIAIS DO PACIENTE

Senhas recebidas para autenticação em sistemas externos:

- não devem ser persistidas;
- não devem ser logadas;
- não devem ser adicionadas a exceptions;
- não devem aparecer em traces;
- não devem ser armazenadas em banco;
- não devem aparecer em telemetry.

Utilizar somente durante a operação necessária.

---

# DADOS DE SAÚDE

Dados clínicos e informações relacionadas à saúde devem ser classificados como altamente sensíveis.

Nunca registrar indiscriminadamente em logs:

- prontuário;
- diagnóstico;
- prescrição;
- exames;
- documentos médicos;
- payload clínico completo;
- tokens;
- senhas;
- CPF completo quando desnecessário.

---

# LGPD

Criar:

`docs/agent/privacy-lgpd-checklist.md`

Toda alteração envolvendo dados pessoais deve avaliar:

## Minimização

Coletamos somente o dado necessário?

## Finalidade

Existe finalidade clara para o tratamento?

## Exposição

Esse dado pode aparecer em:

- URL;
- query string;
- logs;
- frontend;
- analytics;
- exception;
- cache?

## Retenção

É necessário manter o dado?

## Exclusão

Existe impacto na estratégia de exclusão/anonimização?

## Tenant Isolation

Outro cliente pode acessar esse dado?

## Segurança

O dado está protegido durante trânsito e armazenamento?

---

# CPF E IDENTIFICADORES

Evitar exposição desnecessária de CPF completo.

CPF nunca deve ser utilizado indiscriminadamente em:

- URLs;
- logs;
- nomes de eventos;
- analytics;
- exception messages.

Quando necessário para integração externa, limitar sua utilização ao fluxo estritamente necessário.

---

# MULTI-TENANCY

Mapear como o tenant atual é resolvido.

Documentar a implementação real.

Nenhuma operação deve confiar exclusivamente em valores como:

```text
tenant_id
clinic_id
company_id
user_id
patient_id
```

vindos do frontend.

A autorização e resolução de tenant devem ocorrer no backend.

Todas as queries contendo dados pertencentes a clientes devem ser analisadas quanto a acesso cross-tenant.

---

# IDOR

Avaliar explicitamente risco de Insecure Direct Object Reference.

Rotas como:

```text
/patients/{id}
/appointments/{id}
/consultations/{id}
```

não podem permitir acesso apenas porque o usuário conhece o ID.

Validar:

- usuário;
- tenant;
- autorização;
- relacionamento com o recurso.

---

# INTEGRAÇÕES EXTERNAS

Criar regras para chamadas externas.

Toda integração deve considerar:

- timeout;
- connection timeout;
- erros 4xx;
- erros 5xx;
- JSON inválido;
- indisponibilidade;
- retries;
- circuitos de falha quando aplicável;
- logs seguros.

Retries nunca devem ser adicionados indiscriminadamente.

Operações não idempotentes devem ser analisadas antes de retry.

---

# LOGGING

Nunca utilizar logs como:

```php
Log::info($request->all());
```

em fluxos contendo dados sensíveis.

Evitar logging indiscriminado de:

- Request;
- Response;
- Authorization headers;
- Bearer tokens;
- CPF;
- senha;
- dados médicos.

Preferir logs estruturados e sanitizados.

Exemplo conceitual:

```text
telemedicine_login_failed
provider=lsx
status=401
request_id=...
```

e não:

```text
cpf=...
password=...
token=...
```

---

# FRONTEND

O frontend não deve ser considerado uma barreira de segurança.

Esconder botão não equivale a autorização.

Toda operação sensível deve ser validada no backend.

Evitar armazenar dados sensíveis em:

- localStorage;
- sessionStorage;
- URL;
- query strings.

---

# GOLDEN RULES

Criar:

`docs/agent/golden-rules.md`

Incluindo pelo menos:

## Existing Code First

Antes de criar:

- Model;
- Service;
- Action;
- Repository;
- DTO;
- Middleware;
- Policy;
- Composable;
- Store;
- Client;

pesquisar implementação equivalente.

---

## Minimal Change

Alterar somente o necessário para cumprir o requisito.

---

## No Guessing

Não inventar:

- endpoints;
- tabelas;
- colunas;
- services;
- contratos;
- eventos;
- webhooks;
- funcionalidades externas.

---

## Preserve Authentication

Nunca substituir ou duplicar autenticação existente sem requisito explícito.

---

## Backend Security

Autorização deve existir no backend.

---

## Tenant Isolation

Nenhuma alteração pode permitir acesso entre tenants.

---

## Sensitive Data

Dados de saúde e dados pessoais devem receber tratamento restritivo.

---

## External API Isolation

Integrações externas devem permanecer encapsuladas no backend.

---

## Secrets

Nunca versionar:

- tokens;
- senhas;
- API keys;
- client secrets;
- credenciais.

---

# FORBIDDEN ACTIONS

Criar:

`docs/agent/forbidden-actions.md`

Incluir proibições como:

- alterar `.env` com credenciais reais;
- inserir secrets no código;
- registrar senha em log;
- registrar token em log;
- retornar token de integração ao browser;
- expor CPF sem necessidade;
- colocar dados clínicos em URL;
- criar autenticação paralela;
- remover autorização existente;
- desabilitar CSRF para resolver erros;
- utilizar `->withoutMiddleware()` como correção permanente;
- utilizar `verify=false` para ignorar SSL;
- alterar migrations antigas de produção;
- criar tabelas sem pesquisar estrutura existente;
- adicionar retry indiscriminadamente;
- remover validação para fazer integração funcionar.

---

# SECURITY CHECKLIST

Criar:

`docs/agent/security-checklist.md`

Validar:

- authentication;
- authorization;
- tenant isolation;
- IDOR;
- CSRF;
- XSS;
- SQL Injection;
- mass assignment;
- validation;
- rate limiting;
- secrets;
- sessions;
- cookies;
- HTTPS;
- external APIs;
- logging;
- exception handling;
- uploads;
- jobs;
- queues.

---

# RATE LIMITING

Fluxos críticos como:

- login;
- recuperação de senha;
- autenticação externa;
- endpoints sensíveis;

devem possuir análise de rate limiting.

Não aplicar limites arbitrários sem analisar comportamento atual.

---

# ERROR HANDLING

Erros apresentados ao usuário não devem revelar:

- estrutura interna;
- stack trace;
- token;
- payload da API externa;
- credenciais;
- detalhes que facilitem enumeração de usuários.

Mensagens de autenticação devem evitar indicar desnecessariamente se:

- CPF existe;
- paciente existe;
- senha está incorreta;

quando isso aumentar risco de enumeração.

---

# CRITICAL REGRESSIONS

Criar:

`docs/agent/critical-regressions.md`

Identificar no projeto real os fluxos que não podem quebrar.

Considere, quando existirem:

## Authentication

- login;
- cadastro;
- recuperação de senha;
- logout;
- sessão.

## Patients

- criação;
- consulta;
- edição;
- autorização.

## Telemedicine

- autenticação LSX;
- identificação do paciente;
- consultas;
- agendamento;
- especialidades;
- médicos;
- horários.

## Tenant

- resolução;
- isolamento;
- autorização.

## Frontend

- login;
- dashboard;
- rotas Inertia;
- build Vite;
- responsividade.

Não adicionar funcionalidades que não existam no código.

---

# RISK GATE

Classificar alterações.

## LOW

Exemplos:

- textos;
- CSS;
- ajustes visuais isolados.

Processo:

```text
INSPECT
→ IMPLEMENT
→ VALIDATE
→ DIFF REVIEW
```

## MEDIUM

Exemplos:

- Controller;
- Form Request;
- Service;
- Policy;
- query;
- componente com lógica.

Processo:

```text
INSPECT
→ PLAN
→ IMPLEMENT
→ TEST
→ DIFF REVIEW
```

## HIGH

Exemplos:

- autenticação;
- autorização;
- pacientes;
- dados de saúde;
- LGPD;
- tenant;
- LSX;
- credenciais;
- migrations;
- integração externa.

Processo obrigatório:

```text
INSPECT
↓
PLAN
↓
SECURITY REVIEW
↓
PRIVACY / LGPD REVIEW
↓
IMPLEMENT
↓
TEST
↓
SECURITY REVIEW
↓
REGRESSION REVIEW
↓
DIFF REVIEW
↓
REPORT
```

---

# ARCHITECTURE.md

Criar um mapa curto da arquitetura encontrada.

Não transformar o arquivo em uma documentação gigantesca.

Detalhes devem apontar para:

```text
docs/architecture/
```

Documentar somente fatos confirmados no código.

Quando não houver evidência suficiente utilizar:

```text
NEEDS_VERIFICATION
```

---

# DOCUMENTAÇÃO DE ARQUITETURA

Criar conforme realmente encontrado:

```text
docs/architecture/authentication.md
docs/architecture/authorization.md
docs/architecture/multi-tenancy.md
docs/architecture/frontend.md
docs/architecture/database.md
docs/architecture/telemedicine.md
docs/architecture/integrations.md
```

Cada documento deve incluir:

- implementação atual;
- componentes envolvidos;
- fluxo;
- invariantes;
- riscos;
- arquivos principais;
- pontos que agentes não devem quebrar.

---

# TESTING STRATEGY

Criar:

`docs/agent/testing-strategy.md`

Mapear os testes existentes.

Identificar:

- Feature tests;
- Unit tests;
- integração;
- frontend;
- build.

Não assumir ferramentas não instaladas.

Priorizar testes dos fluxos críticos.

Alterações de autenticação devem testar pelo menos:

- sucesso;
- credencial inválida;
- usuário inexistente quando aplicável;
- rate limit quando existente;
- sessão;
- isolamento.

Integrações externas devem testar:

- sucesso;
- 401/403;
- 404 quando aplicável;
- 422;
- 429;
- 500;
- timeout;
- conexão recusada;
- JSON inválido.

Utilizar mocks/fakes quando apropriado.

Nunca depender da API real de produção para testes automatizados.

---

# SCRIPTS DO HARNESS

Criar quando forem compatíveis com o projeto:

```text
scripts/agent/preflight.sh
scripts/agent/validate.sh
scripts/agent/changed-files.sh
```

O `validate.sh` deve utilizar apenas ferramentas disponíveis no projeto.

Verificar, quando aplicável:

```bash
php artisan test
./vendor/bin/pint --test
npm run build
git diff --check
```

Antes de usar qualquer comando, verificar se a ferramenta existe.

Não instalar dependências apenas para cumprir este Harness.

---

# PREFLIGHT

O `preflight.sh` deve ajudar o agente a identificar:

- branch atual;
- arquivos modificados;
- status Git;
- versões principais;
- dependências relevantes.

Não modificar o ambiente.

---

# CHANGED FILES

O script deve facilitar a revisão do escopo da alteração.

Exemplo conceitual:

```bash
git diff --name-only
```

O agente deve verificar se arquivos fora do escopo foram alterados.

---

# DEFINITION OF DONE

Criar:

`docs/agent/definition-of-done.md`

Uma alteração só pode ser considerada concluída quando:

- requisito atendido;
- código existente foi pesquisado antes da criação de novas estruturas;
- autenticação preservada;
- autorização analisada;
- tenant isolation analisado;
- segurança analisada;
- LGPD analisada quando aplicável;
- dados sensíveis não foram expostos;
- integração externa tratou falhas relevantes;
- testes foram executados;
- build foi validado quando necessário;
- `git diff --check` passou;
- diff foi revisado;
- arquivos alterados foram informados;
- riscos foram informados;
- pendências foram informadas.

---

# EXECUTION PLANS

Criar:

```text
docs/exec-plans/active/
docs/exec-plans/completed/
```

Criar também um template de Exec Plan contendo:

```text
Título
Status
Objetivo
Contexto
Escopo
Fora de escopo
Risco
Dados sensíveis envolvidos
Impacto LGPD
Impacto multi-tenant
Integrações afetadas
Arquivos envolvidos
Plano de implementação
Testes
Security Review
Decisões
Pendências
```

Tarefas HIGH devem utilizar Exec Plan.

---

# AGENTS.md

O arquivo deve ser conciso.

Ele deve funcionar como **mapa do Harness** e não como documentação completa.

Deve dizer ao agente:

1. pesquisar antes de criar;
2. determinar nível de risco;
3. ler somente documentos relevantes;
4. planejar alterações MEDIUM/HIGH;
5. preservar autenticação;
6. validar tenant;
7. tratar dados médicos como sensíveis;
8. manter integrações externas no backend;
9. executar validações;
10. revisar diff;
11. produzir relatório final.

---

# VALIDAÇÃO DO PRÓPRIO HARNESS

Depois de criar os arquivos:

1. releia todos os documentos;
2. confronte cada afirmação arquitetural com o código;
3. remova informações não comprovadas;
4. marque dúvidas como `NEEDS_VERIFICATION`;
5. valide paths citados;
6. valide comandos dos scripts;
7. execute os scripts;
8. execute testes compatíveis;
9. execute build quando possível;
10. execute:

```bash
git diff --check
```

11. revise:

```bash
git diff
```

O Harness não pode criar uma falsa representação do projeto.

---

# RELATÓRIO FINAL

Informe:

## Arquitetura encontrada

Resumo da estrutura real.

## Harness criado

Arquivos e finalidade.

## Authentication

Como funciona atualmente.

## Multi-tenancy

Como o tenant é resolvido e protegido.

## Telemedicine / LSX

Arquitetura encontrada e componentes principais.

## Segurança

Proteções identificadas e lacunas encontradas.

## LGPD

Pontos relevantes identificados.

## Critical Paths

Fluxos classificados como críticos.

## Validações executadas

Comando + resultado.

## NEEDS_VERIFICATION

Itens que não puderam ser confirmados.

## Riscos encontrados

Problemas observados durante a inspeção.

## Arquivos criados

Lista completa.

## Arquivos de produção alterados

O resultado esperado é:

`Nenhum.`

Caso exista algum, explique detalhadamente o motivo.