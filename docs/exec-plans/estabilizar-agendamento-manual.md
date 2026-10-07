# Exec Plan: estabilizar agendamento manual

- **Status:** completed
- **Objetivo:** corrigir a criação de consultas manuais recusada com HTTP 409 sem alterar o fluxo idempotente existente.
- **Contexto:** o contrato LSX torna `doctor_id` opcional quando `is_real_doctor` é falso, mas o payload atual sempre envia o identificador retornado pela lista de profissionais.
- **Escopo:** payload server-side de criação, mensagem de erro segura no cliente HTTP, testes de regressão e documentação da integração.
- **Fora de escopo:** reconciliação automática, novos endpoints LSX, mudanças de schema, autenticação, planos ou regras clínicas.
- **Risco:** HIGH
- **Dados sensíveis envolvidos:** CPF é enviado à LSX pelo backend e não deve aparecer em resposta, log ou estado do browser.
- **Impacto LGPD:** não há nova coleta ou persistência; permanece o payload mínimo já exigido pelo provedor.
- **Impacto multi-tenant:** nenhum novo identificador de tenant será aceito; o registro continuará derivando tenant e paciente do usuário autenticado.
- **Integrações afetadas:** criação de consulta na LSX Medical.
- **Arquivos envolvidos:** serviço de agendamento, contrato do cliente LSX, cliente HTTP frontend, testes de agendamento e documentação de telemedicina.

## Plano de implementação

1. Inspeção: revisar Harness, arquitetura, contrato LSX, rota, request, service, cliente, tela e testes existentes.
2. Implementação: omitir `doctor_id` apenas quando a opção selecionada não representa um profissional real e preservar mensagens seguras do backend para conflitos e validações.
3. Validação: executar testes focados e completos, testes frontend, Pint, build, rotas, scripts do Harness e revisão do diff.

## Testes

- Criação com profissional real preserva `doctor_id`.
- Criação sem profissional real omite `doctor_id` e mantém `is_real_doctor=false`.
- Idempotência, reconciliação e isolamento por tenant continuam cobertos pela suíte existente.
- `php artisan test --filter=ConsultationSchedulingTest`: 11 testes e 67 asserções aprovados.
- `php artisan test`: 148 testes e 1088 asserções aprovados.
- `npm run test:frontend`, `npm run build`, `npm run format:check` e Pint dos arquivos PHP alterados: aprovados.

## Security Review

- Autenticação, CSRF, middleware de perfil, rate limit e bloqueio de sessão permanecem na rota atual.
- Tenant e CPF continuam derivados do usuário autenticado.
- O token LSX continua somente na configuração do backend.
- Nenhum payload do provedor ou dado clínico será incluído em logs ou respostas.
- A revisão posterior confirmou que autorização, CSRF, rate limit, isolamento por tenant, idempotência e bloqueio de repetição ambígua não foram alterados.

## Decisões

- Seguir o contrato documentado em `docs/criar_consulta.json`: `doctor_id` só acompanha uma escolha com `is_real_doctor=true`.
- Preservar a reserva local antes da escrita remota e o bloqueio de repetição para estados ambíguos.
- Usar apenas mensagens já sanitizadas pelo Laravel nas respostas 403, 404, 409, 422 e 429.

## Pendências

- A homologação real do formato de resposta e o isolamento interno da clínica no token LSX permanecem `NEEDS_VERIFICATION`.
- A política de retenção dos espelhos locais permanece `NEEDS_VERIFICATION`.
- O Pint completo encontra uma divergência preexistente em `bootstrap/app.php`; o arquivo não faz parte desta alteração e não foi reformatado. O Pint restrito aos arquivos PHP alterados passou.
