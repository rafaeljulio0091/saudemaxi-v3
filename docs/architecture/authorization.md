# Autorização

## Implementação atual

- Rotas protegidas usam `auth`, `verified` e, nas áreas de saúde, o middleware
  `EnsureUserHasRole` com `patient` ou `manager`.
- `TriageSessionPolicy` autoriza visualização e mensagens somente quando o
  usuário é paciente, pertence ao mesmo `tenant_id` e é dono da sessão.
- `TriageSessionController@show` chama `Gate::authorize('view', ...)`.
- Form Requests controlam autorização e proíbem `tenant_id`/identificadores de
  contexto recebidos do cliente em fluxos de triagem e demonstração.
- O route model binding fornece `TriageSession` a partir do UUID, mas a
  autorização é responsabilidade da policy, não do UUID.

## Invariantes

- Esconder uma ação no Vue não autoriza a operação.
- Toda leitura ou escrita sensível deve validar usuário, tenant e relação com
  o recurso no backend.
- Não aceitar `tenant_id`, `patient_id` ou `clinic_id` do browser como prova de
  acesso.

## Riscos e lacunas

- As rotas de gestor são protegidas por papel, mas os clientes LSX recebem
  filtros e usam um token de clínica. Não há uma policy local ou escopo de
  tenant visível nesses controllers; a separação efetiva dentro da LSX é
  `NEEDS_VERIFICATION`.
- Não foi encontrado um mecanismo global que aplique tenant a todas as queries.
- A rota `/gestor/pacientes/{id}` existe como página não pronta, e não como
  fluxo de ficha implementado; qualquer implementação futura exige teste IDOR.

## Arquivos principais

`routes/healthcare.php`, `app/Http/Middleware/EnsureUserHasRole.php`,
`app/Policies/TriageSessionPolicy.php`, `app/Http/Requests/**`,
`app/Http/Controllers/Triage/**` e testes `TriageTest`,
`HealthcarePatientAreaTest`, `PatientsTest` e `ConsultationsTest`.

## Área real do gestor

- `/gestor/painel`, `/gestor/planos`, `/gestor/identidade` e `/gestor/integracao`
  usam `HealthcareContext::forManager` (gestor sem tenant → 403; no painel, a
  tela explicativa anterior é mantida).
- `/gestor/dados/{resource}` e `/gestor/dados/{operation}` exigem papel
  `manager`, derivam o tenant da sessão e validam cada operação; plano de outro
  tenant retorna 404 e o módulo `nr1` não pode ser alterado.
- Os módulos do plano são aplicados no servidor às páginas do paciente, ao
  início da triagem (`orientacao`) e às receitas (`farmacia`).
