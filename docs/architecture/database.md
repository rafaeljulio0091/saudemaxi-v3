# Banco de dados

## Implementação atual

- O acesso é Eloquent; não foram encontrados repositories customizados.
- As migrações criam usuários/sessões/cache/jobs, tenants, campos de usuário,
  tabelas de triagem, prescrições e o cadastro nativo de saúde.
- O cadastro nativo inclui municípios, organizações, unidades, pacientes,
  profissionais, farmácias, endereços polimórficos, vínculos, identidades
  externas e audit logs.
- CPF, CNS, CNPJ, contatos e partes privadas de endereço usam criptografia em
  aplicação. HMACs separados suportam unicidade e buscas exatas.
- As entidades de triagem usam UUID na sessão, chaves estrangeiras e índices
  compostos por tenant/status/paciente/data. Mensagens têm unicidade por
  sessão e sequência/request id.
- `User`, `Tenant`, `TriageSession`, `TriageMessage`, `TriageAssessment`,
  `TriageAiEvent` e `Prescription` definem relações explícitas.
- O driver de sessão padrão é database e há tabelas de jobs, mas nenhum Job
  customizado foi encontrado.
- `consultation_appointments` mantém o espelho local mínimo de agendamentos
  LSX, com escopo por `tenant_id` e `user_id`, UUID público, idempotência por
  `request_id`, identificadores do provedor e estado de sincronização. CPF e
  link do paciente não são persistidos nessa tabela. Os nomes de especialidade
  e profissional e o identificador interno do provedor usam cast
  criptografado.
- A tela `/consultas` do paciente pesquisa somente registros locais confirmados,
  com escopo simultâneo por `tenant_id` e `user_id`. Filtros textuais sobre os
  nomes criptografados usam cursor dentro desse escopo; o histórico LSX do
  gestor permanece separado.

## Invariantes

- Alterações de schema usam nova migration reversível; migrations existentes
  não devem ser editadas para corrigir produção.
- Dados de saúde e tenant devem manter foreign keys e escopo de autorização.
- Consultas de coleções grandes devem considerar paginação e eager loading.

## Riscos e lacunas

- `prescriptions` tem `patient_id`, mas não `tenant_id`; a proteção depende da
  relação com o usuário. Qualquer nova consulta deve manter esse vínculo.
- `users.cpf` permanece legado. O novo `patients.cpf` é criptografado e tem
  unicidade por tenant via `cpf_hash`; migração do valor legado depende de uma
  regra de vínculo ainda `NEEDS_VERIFICATION`.
- A migration de agendamentos foi exercitada pelo banco SQLite descartável da
  suíte. A execução específica em MySQL permanece `NEEDS_VERIFICATION`.
- A política de retenção e exclusão dos agendamentos locais depende de decisão
  jurídica e de produto: `NEEDS_VERIFICATION`.

## Arquivos principais

`database/migrations/*.php` e os modelos em `app/Models`.

## Planos e identidade do tenant

- `plans` (tenant_id, name, max_dependents, modules JSON, is_default) é a
  camada local "Módulos do plano" da Saúde Maxi, não uma entidade LSX.
  Todo paciente do tenant usa o plano padrão; sem plano gravado valem os
  módulos padrão de `TenantPlanService` (todos habilitados).
- `tenants.greeting` guarda a saudação editada em `/gestor/identidade`.
- Atribuição de plano por paciente ainda não existe (`NEEDS_VERIFICATION`).
