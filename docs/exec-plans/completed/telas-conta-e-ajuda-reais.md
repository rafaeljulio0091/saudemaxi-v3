# Exec Plan: Telas reais `/conta` e `/ajuda`

- **Status:** completed
- **Objetivo:** tornar `/conta` e `/ajuda` rotas reais do paciente, com o mesmo
  layout de `/demonstracao/conta` e `/demonstracao/ajuda`.
- **Contexto:** as páginas `Patient/Account.vue` e `Patient/Help.vue` já existiam
  (usadas pela demo); as rotas reais renderizavam `Healthcare/NotReady` (503).
- **Escopo:** registrar as rotas pelo `PatientAreaController`; implementar
  `GET /triagem/account` e `POST /triagem/patient` no `HealthcareDataService`
  para a própria conta logada; textos de demonstração condicionados a `context.demo`.
- **Fora de escopo:** integração LSX, dependentes, CPF, idioma, rotas da demo.
- **Risco:** HIGH (dados pessoais do paciente, autorização, tenant).
- **Dados sensíveis envolvidos:** nome, e-mail, telefone, data de nascimento.
- **Impacto LGPD:** nenhum dado novo coletado; CPF não é exposto na resposta.
- **Impacto multi-tenant:** paciente sem tenant e gestor recebem 403.
- **Integrações afetadas:** nenhuma (LSX inalterada).
- **Arquivos envolvidos:** `routes/healthcare.php`, `HealthcareDataController`,
  `HealthcareDataService`, `Patient/Account.vue`, `HealthcarePatientAreaTest`.

## Plano de implementação

1. Inspeção: páginas da demo, `DemoHealthcareService` (`account`/`patient`),
   `ProfileController`/`ProfileUpdateRequest`, rotas e testes.
2. Implementação: reusar as páginas da demo; `account` devolve só os campos da
   tela; `patient` atualiza nome/e-mail/telefone da conta logada com as mesmas
   regras do perfil (e-mail único, reset de verificação ao trocar e-mail).
3. Validação: testes PHP, frontend, format, pint, build e verificação visual
   demo x real em navegador.

## Testes

- `/conta` e `/ajuda` acessíveis ao paciente com tenant; guest → login;
  gestor e paciente sem tenant → 403.
- `account` devolve somente os dados do usuário logado (sem CPF).
- `patient` ignora o `id` enviado pelo browser (sem IDOR), valida campos,
  impede e-mail duplicado e reseta verificação ao trocar e-mail.

## Security Review

- Autorização server-side: middleware `auth`, `verified`, role `patient` + tenant.
- CSRF preservado (cliente Axios existente); rate limit existente (60/min).
- Mass assignment explícito: apenas `name`, `email`, `phone`.

## Decisões

- Validação por operação no `HealthcareDataController`, padrão já usado por
  `photo` e `consultations-search`, sem novas rotas de API.
- `dependentes` = `null`: não há fonte local; nada inventado.
- `maxDependentes` só aparece quando o plano o informa (demo).

## Pendências

- `NEEDS_VERIFICATION`: `User` não implementa `MustVerifyEmail`, então trocar o
  e-mail zera `email_verified_at` sem exigir nova verificação (igual ao perfil).
- Dependentes e plano reais dependem de fonte oficial (ex.: LSX filter-patients).
