# Banco de dados

## Implementação atual

- O acesso é Eloquent; não foram encontrados repositories customizados.
- As migrações criam usuários/sessões/cache/jobs, tenants, campos de usuário,
  tabelas de triagem e prescrições.
- As entidades de triagem usam UUID na sessão, chaves estrangeiras e índices
  compostos por tenant/status/paciente/data. Mensagens têm unicidade por
  sessão e sequência/request id.
- `User`, `Tenant`, `TriageSession`, `TriageMessage`, `TriageAssessment`,
  `TriageAiEvent` e `Prescription` definem relações explícitas.
- O driver de sessão padrão é database e há tabelas de jobs, mas nenhum Job
  customizado foi encontrado.

## Invariantes

- Alterações de schema usam nova migration reversível; migrations existentes
  não devem ser editadas para corrigir produção.
- Dados de saúde e tenant devem manter foreign keys e escopo de autorização.
- Consultas de coleções grandes devem considerar paginação e eager loading.

## Riscos e lacunas

- `prescriptions` tem `patient_id`, mas não `tenant_id`; a proteção depende da
  relação com o usuário. Qualquer nova consulta deve manter esse vínculo.
- `users.cpf` é nullable e não há índice/normalização adicional demonstrada;
  o impacto para consultas LSX deve ser validado antes de mudanças.
- Não foi verificada uma execução de migrations em banco descartável neste
  Harness: `NEEDS_VERIFICATION` até a validação local adequada.

## Arquivos principais

`database/migrations/*.php` e os modelos em `app/Models`.

## Planos e identidade do tenant

- `plans` (tenant_id, name, max_dependents, modules JSON, is_default) é a
  camada local "Módulos do plano" da Saúde Maxi, não uma entidade LSX.
  Todo paciente do tenant usa o plano padrão; sem plano gravado valem os
  módulos padrão de `TenantPlanService` (todos habilitados).
- `tenants.greeting` guarda a saudação editada em `/gestor/identidade`.
- Atribuição de plano por paciente ainda não existe (`NEEDS_VERIFICATION`).
