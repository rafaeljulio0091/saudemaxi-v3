# Exec Plan: Cadastro nativo de saúde

- **Status:** completed
- **Objetivo:** tornar o banco SaúdeMaxi a fonte principal para pacientes e
  diretório operacional, mantendo a LSX como integração complementar.
- **Risco:** HIGH, por envolver autenticação, CPF, CNS, CNPJ, pacientes,
  tenant e telemedicina.
- **Escopo:** migrations incrementais, modelos, relações, Policies, Form
  Requests, Services transacionais, endpoints locais, tela de pacientes,
  auditoria, testes e documentação.
- **Fora de escopo:** sincronização automática LSX, importação de legado,
  exclusão física, regra jurídica de retenção e provisionamento automático de
  tenant.

## Plano executado

1. Auditoria de models, migrations, rotas, autenticação, telemedicina, tela e
   testes existentes.
2. Security e Privacy/LGPD Gates antes da implementação.
3. Schema nativo com UUID, tenant, chaves estrangeiras, índices compostos,
   SoftDeletes, criptografia e índices HMAC.
4. Services transacionais com autorização, relações escopadas e auditoria.
5. Migração da tela de pacientes para busca POST e persistência local.
6. Testes de sucesso, validação, duplicidade, rollback, mass assignment,
   indisponibilidade LSX e isolamento entre tenants.
7. Revisão final de segurança, LGPD, diff, rotas, migrations, PHP e frontend.

## Security Review

- Guard web, sessão, CSRF, logout e recuperação de senha preservados.
- Tenant nunca vem do browser como autoridade.
- UUID externo não substitui Policy nem query escopada.
- Token LSX permanece server-side e não participa do cadastro local.
- Log de conexão do login LSX não inclui mais mensagem de exceção.
- Campos autoritativos são proibidos e os Models têm fillable explícito.

## Privacy/LGPD Review

- Busca sensível foi retirada de query string e movida para POST autenticado.
- O mesmo controle foi aplicado à consulta LSX do gestor, que antes colocava
  CPF nos parâmetros da página.
- Identificadores e contatos são criptografados; listagens são mascaradas.
- Auditoria não persiste payload pessoal ou clínico.
- SoftDelete não foi apresentado como anonimização ou exclusão LGPD.
- Retenção, base legal e sincronização externa permanecem
  `NEEDS_VERIFICATION`.

## Validação

- `php artisan test`: PASS, 131 testes e 974 asserções.
- `npm run test:frontend`: PASS.
- `npm run format:check`: PASS.
- `npm run build`: PASS.
- `vendor/bin/pint --dirty --test`: PASS.
- migration completa em SQLite descartável: PASS.
- `php artisan route:list --path=gestor --except-vendor`: inspecionado.
- `git diff --check`: PASS.
- inspeção visual automatizada: não executada, pois o script opcional depende
  de Playwright, que não está instalado e não deve ser adicionado apenas para
  o Harness.

## Gates finais

### Security

- Autenticação, regeneração de sessão, logout e CSRF preservados.
- Form Requests, Policies, rate limit, mass assignment e IDOR cobertos.
- Queries de recurso e relações são escopadas ao tenant autenticado.
- Props do Inertia foram minimizadas e não serializam CPF ou tenant_id.
- Nenhum segredo ou payload clínico foi incluído em código, resposta ou log.

### Privacy/LGPD

- Coleta limitada aos campos operacionais do cadastro.
- Busca sensível usa corpo POST e não URL.
- Identificadores e contatos persistidos são criptografados e mascarados na
  apresentação.
- Auditoria registra metadados, sem conteúdo clínico ou identificador em claro.
- Retenção, base legal, anonimização e rotação de chaves continuam
  `NEEDS_VERIFICATION` e não receberam regra presumida.
