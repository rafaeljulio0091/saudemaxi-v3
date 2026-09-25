# Multi-tenancy

## Implementação atual

- `Tenant` é um modelo Eloquent com `name`, `slug`, `brand_color` e
  `regulacao`.
- `User` possui `tenant_id` nullable e relação `belongsTo(Tenant::class)`.
- `TriageSession`, mensagens, avaliações e eventos de IA têm `tenant_id` e
  índices compostos voltados para consultas por tenant.
- `HealthcareContext::forPatient()` resolve o tenant pela relação do usuário
  autenticado e expõe somente dados de apresentação e uma chave de contexto.
- `SharesDashboardContext` compartilha dados do tenant relacionado ao usuário
  para dashboards.
- `TriageSessionPolicy` e `StartTriageSession` comparam o tenant da sessão
  com o tenant do paciente. Os Form Requests impedem que o browser escolha o
  tenant.
- O contexto da demonstração é isolado em sessão e em dados de demonstração,
  mas não é o mesmo mecanismo dos dados persistidos.

## Invariantes

- O tenant confiável é derivado da sessão/usuário no backend.
- Queries e policies de dados persistidos devem manter escopo por tenant e por
  usuário quando aplicável.
- IDs conhecidos não substituem autorização.

## Riscos e lacunas

- Não existe global scope ou serviço único de resolução de tenant comprovado.
  Cada novo acesso precisa ser revisado individualmente.
- Registro local e provisionamento via LSX deixam `tenant_id` nulo por padrão;
  a regra de associação automática é `NEEDS_VERIFICATION`.
- Os fluxos de gestor consultam a API LSX com token de clínica e CPF/filtros;
  o isolamento entre tenants dentro do provedor não é demonstrado pelo código.
  Exigir confirmação do contrato antes de ampliar esses fluxos.

## Arquivos principais

`app/Models/Tenant.php`, `app/Models/User.php`,
`app/Services/Healthcare/HealthcareContext.php`,
`app/Http/Controllers/Concerns/SharesDashboardContext.php`,
`app/Triage/Actions/StartTriageSession.php`,
`app/Policies/TriageSessionPolicy.php` e migrações de tenant/triagem.
