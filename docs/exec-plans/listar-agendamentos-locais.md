# Exec Plan: listar agendamentos locais do paciente

- **Status:** completed
- **Objetivo:** disponibilizar em `/consultas` a pesquisa dos agendamentos confirmados persistidos em `consultation_appointments`.
- **Contexto:** a criação manual já mantém um espelho local por tenant e paciente, mas a tela do paciente consulta somente o histórico LSX.
- **Escopo:** endpoint local autenticado, autorização, filtros, paginação, integração com a tela Vue e testes de isolamento.
- **Fora de escopo:** alterar clientes, contratos ou chamadas LSX; reconciliação; histórico local para gestores; mudanças de schema.
- **Risco:** HIGH
- **Dados sensíveis envolvidos:** especialidade, profissional e data da consulta.
- **Impacto LGPD:** somente o próprio paciente recebe os campos mínimos já exibidos na tela; nenhum dado será registrado em log, URL ou browser storage.
- **Impacto multi-tenant:** a consulta exigirá simultaneamente o tenant e o usuário autenticados.
- **Integrações afetadas:** nenhuma; a integração LSX permanece inalterada.
- **Arquivos envolvidos:** rota de saúde, Form Request, Policy, Controller, Service de consulta local, serviço frontend, página Vue, testes e documentação de banco.

## Plano de implementação

1. Inspeção: revisar Harness, arquitetura, rota, tela compartilhada, modelo local, índices e testes existentes.
2. Implementação: criar busca local paginada e tenant-scoped, conectar somente o perfil paciente ao novo endpoint e preservar a pesquisa LSX do gestor.
3. Validação: testar sucesso, filtros, paginação, tenant/IDOR, perfis indevidos, ausência de chamada LSX, build, formatação, rotas e diff.

## Testes

- O paciente recebe apenas agendamentos confirmados do próprio usuário e tenant.
- Busca por código, especialidade e profissional funciona sem expor filtros em URL.
- Situação e paginação são aplicadas no backend.
- Tenant e usuário fornecidos pelo browser são rejeitados.
- Gestor e paciente sem tenant não acessam o endpoint.
- Nenhuma chamada HTTP externa ocorre na busca local.
- `ConsultationAppointmentSearchTest`: 3 testes e 29 asserções aprovados.
- Testes adjacentes de paciente, gestor, demonstração e agendamento: 57 testes e 514 asserções aprovados.
- `php artisan test`: 151 testes e 1117 asserções aprovados.
- `npm run test:frontend`, `npm run build`, `npm run format:check` e Pint dos arquivos PHP alterados: aprovados.

## Security Review

- Rota preserva sessão, CSRF, e-mail verificado, perfil paciente e rate limit.
- Form Request usa Policy e proíbe contexto autoritativo vindo do browser.
- Query usa Eloquent e combina `tenant_id`, `user_id` e estado confirmado.
- Resposta omite identificadores internos do provedor, CPF, request id e erros de sincronização.
- A revisão posterior confirmou que a tela do gestor e os clientes LSX não foram alterados, e que a nova resposta não contém contexto autoritativo ou dados de outro paciente.

## Decisões

- Usar um endpoint POST distinto para não colocar termos de pesquisa em query string.
- Manter o endpoint LSX e a experiência do gestor sem alterações.
- Pesquisar campos criptografados em cursor tenant/paciente para manter memória constante, pois os casts atuais impedem `LIKE` seguro no banco.
- Excluir estados locais ambíguos para não apresentar uma consulta como confirmada antes da reconciliação.

## Pendências

- A política de retenção dos agendamentos locais permanece `NEEDS_VERIFICATION`.
- O Pint completo continua apontando uma divergência preexistente em `bootstrap/app.php`; o Pint restrito aos arquivos alterados passou.
- `node scripts/check-healthcare-ui.mjs` não iniciou porque o pacote Playwright não está instalado no projeto. O build e as verificações estáticas do frontend passaram; não houve alteração de CSS ou estrutura responsiva.
