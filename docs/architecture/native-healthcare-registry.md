# Cadastro nativo de saúde

## Objetivo e fonte de verdade

Pacientes e o diretório operacional básico são persistidos no banco do
SaúdeMaxi. A LSX Medical continua disponível como provedor externo, mas a
listagem e o cadastro local não dependem de disponibilidade, token ou contrato
da LSX.

```text
Browser / Inertia
  -> Laravel web, sessão e CSRF
  -> Form Request + Policy
  -> NativePatientService ou NativeDirectoryService
  -> transação Eloquent
  -> banco SaúdeMaxi
  -> auditoria sem payload sensível

LSX
  -> integração complementar futura
  -> mapper e DTO ainda NEEDS_VERIFICATION
  -> external_identities
  -> entidade local permanece com ID e UUID próprios
```

Não existe sincronização automática nesta entrega. O contrato para localizar,
atualizar e reconciliar pacientes na LSX ainda precisa ser validado em
homologação. Nenhum endpoint, webhook ou retry foi presumido.

## Modelo de dados

```text
Tenant
  |-- Municipality
  |-- Organization --< HealthUnit
  |                      |--< HealthProfessional (N:N)
  |                      `--< Patient (N:N)
  |-- Patient -- User (0..1)
  |            `-- Patient titular (0..1)
  |-- HealthProfessional -- User (0..1)
  `-- Pharmacy

Patient / Organization / HealthUnit / HealthProfessional / Pharmacy
  |--< Address (polimórfico)
  `--< ExternalIdentity (polimórfico)
```

Clínica não ganhou uma segunda tabela: é uma `Organization` com tipo `clinic`.
Contatos primários permanecem nos cadastros que realmente os usam. Uma tabela
polimórfica de contatos não foi criada sem uma necessidade de múltiplos
contatos, evitando duplicação prematura.

Farmácias são sempre vinculadas a um tenant nesta fase. Um catálogo global
exigiria regras próprias de governança e autorização e permanece fora do
escopo.

## Identificadores e dados sensíveis

- Chaves internas usam `BIGINT`.
- Recursos novos recebem UUID para exposição externa.
- CPF, CNS e CNPJ são criptografados pelo cast `encrypted`.
- E-mail, telefone e partes privadas do endereço também são criptografados.
- Busca e unicidade de CPF, CNS, CNPJ e e-mail usam HMAC SHA-256 estável.
- `PRIVACY_IDENTIFIER_HASH_KEY` deve ser definido antes do primeiro dado de
  produção e não deve ser rotacionado sem uma migração planejada dos índices.
- Listagens e detalhes retornam CPF e contatos mascarados.
- Props globais do Inertia e o contexto de navegação não incluem CPF nem IDs
  internos de tenant.
- CPF é único por `tenant_id + cpf_hash`. A mesma pessoa pode estar em
  contratos distintos sem criar vínculo ou compartilhamento implícito entre
  tenants.

O campo legado `users.cpf` não foi migrado nem utilizado como fonte do novo
cadastro, pois seu ciclo de vida e a associação automática a tenant ainda não
estão definidos.

## Segurança e autorização

- O tenant vem exclusivamente do gestor autenticado.
- IDs de município, organização, unidade, usuário e paciente são novamente
  resolvidos dentro do tenant no Service, mesmo quando já foram validados.
- UUID de paciente de outro tenant retorna 404, reduzindo enumeração e IDOR.
- Policies cobrem `viewAny`, `view`, `create`, `update`, `delete` e `restore`.
- Campos autoritativos como `tenant_id`, `user_id`, `role` e `is_admin` são
  proibidos nos Form Requests aplicáveis.
- Operações sensíveis têm rate limit por usuário.
- Exclusão de paciente usa SoftDelete. Não há exclusão física automática.

## Fluxos implementados

### Paciente

```text
POST /gestor/dados/create-patient
  -> StorePatientRequest
  -> PatientPolicy
  -> NativePatientService
  -> patient + address + vínculos + audit_log em uma transação
```

A busca usa `POST /gestor/dados/patients-search`. CPF, e-mail e nome não são
colocados em query string. O endpoint legado de formulário
`POST /gestor/pacientes` foi preservado para compatibilidade, mas também grava
somente no banco local.

Leitura, alteração, SoftDelete e restauração usam UUID e busca escopada ao
tenant. A leitura sensível gera `patient.viewed_sensitive_data`.

A revisão do mesmo gate também moveu a consulta de histórico do gestor para
POST autenticado. CPF do paciente e do profissional não ficam na URL do
browser; o contrato server-to-server documentado da LSX permanece inalterado.

### Diretório

Os endpoints autenticados em `/gestor/cadastros/*` criam municípios,
organizações, unidades, farmácias e profissionais. Criações compostas usam
transação, validam relações dentro do tenant e registram auditoria.

### Usuário

`users` continua sendo a única fonte de autenticação. Foram adicionados UUID,
status e `last_login_at`; o login local ou LSX atualiza `last_login_at` após a
regeneração da sessão. Um paciente pode existir sem usuário. Vincular um
cadastro nativo a uma conta não é automático.

## LGPD e retenção

Os cadastros coletam somente dados operacionais previstos pelos formulários.
Audit logs guardam ação, ator, tenant, recurso, IP e user agent, nunca o payload
do paciente. Não há dado clínico em URL, browser storage ou log da aplicação.

`NEEDS_VERIFICATION`:

- base legal e finalidade formal por campo;
- prazo de retenção de pacientes e audit logs;
- fluxo jurídico de anonimização, bloqueio, exportação e exclusão;
- política para rotação das chaves de criptografia e HMAC;
- regra de vínculo entre usuário, paciente e tenant;
- contrato de sincronização e reconciliação LSX.
