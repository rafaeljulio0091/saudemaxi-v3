# Exec Plan: Histórico real de consultas do paciente (`/consultas`)

- **Status:** completed
- **Objetivo:** listar em "Minhas consultas" o histórico real via
  `GET /api/clinic/consultation-history/?cpf=` (Bearer), usando o CPF da conta logada.
- **Contexto:** o fluxo já existia (`Shared/Consultations.vue` →
  `POST /triagem/consultations-search` → `HealthcareDataService` →
  `LsxMedicalConsultationClient` → `MakesTelemedicineRequests`). Foi reaproveitado.
- **Escopo:** validação de filtros, isolamento de tenant, normalização do CPF,
  redação de CPF em log de falha de conexão e testes.
- **Fora de escopo:** preenchimento de `users.cpf`, busca textual no provedor,
  alteração de layout ou rotas.
- **Risco:** HIGH (CPF, dados de saúde, integração LSX).
- **Dados sensíveis envolvidos:** CPF do paciente e histórico de consultas.
- **Impacto LGPD:** nenhum dado novo coletado ou persistido; CPF deixa de poder
  aparecer em log de erro de conexão.
- **Impacto multi-tenant:** paciente sem tenant recebe 403, como na página.
- **Integrações afetadas:** LSX consultation-history (somente leitura).
- **Arquivos envolvidos:** `HealthcareDataController`, `HealthcareDataService`,
  `ConsultationHistoryRequest`, `LsxMedicalConsultationClient`,
  `MakesTelemedicineRequests`, `tests/Feature/HealthcarePatientAreaTest.php`.

## Plano de implementação

1. Inspeção: rota, serviço, cliente, trait HTTP, testes e checklists do Harness.
2. Implementação: validar `status`/`page` com a lista de `ConsultationHistoryRequest`;
   exigir paciente com tenant; enviar CPF somente com dígitos; não logar a
   mensagem de `ConnectionException` (contém a URL com `?cpf=`).
3. Validação: `php artisan test`, `npm run test:frontend`, `format:check`,
   `build`, `pint --test`, `git diff --check`.

## Testes

- CPF da sessão (normalizado) é enviado com GET e Bearer; CPF do body é ignorado.
- Filtros inválidos → 422 sem chamada externa.
- Paciente sem tenant → 403; gestor → 403; nenhum envio ao provedor.
- Falha de conexão → 503 sem CPF no contexto do log.

## Security Review

- CPF nunca vem do browser; autorização server-side (role + tenant).
- Token permanece no backend; rate limit existente (60/min) preservado.
- Erros públicos continuam genéricos (503) sem payload do provedor.

## Decisões

- Seguido o padrão existente de validação por operação no
  `HealthcareDataController` (como em `photo`) para não alterar rotas.
- Lista de status centralizada em `ConsultationHistoryRequest::STATUSES`.

## Pendências

- `NEEDS_VERIFICATION`: não há fluxo que preencha `users.cpf` (não é fillable,
  o login LSX não retorna CPF documentado). Sem CPF, a lista fica vazia.
  O CPF **não** deve ser autoeditável pelo paciente (risco de IDOR).
- A busca textual da tela não é aplicada: o contrato do endpoint não documenta
  esse filtro.
- Isolamento por clínica dentro do token LSX segue `NEEDS_VERIFICATION`.
