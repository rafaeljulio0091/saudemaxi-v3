# Exec Plan: corrigir parâmetro de disponibilidade LSX

- **Status:** completed
- **Objetivo:** Corrigir o mapeamento do identificador de especialidade nas consultas de dias, horários e profissionais disponíveis.
- **Contexto:** A interface envia `specialty_id` ao Laravel, mas os três endpoints de disponibilidade da LSX homologada exigem `specialty` no payload server-to-server.
- **Escopo:** Adaptador LSX, teste de regressão do contrato e documentação técnica.
- **Fora de escopo:** Alterar autenticação, tenant, criação de consulta, schema, persistência, interface ou credenciais.
- **Risco:** HIGH
- **Dados sensíveis envolvidos:** Nenhum dado pessoal é necessário nas consultas de disponibilidade. O token da clínica permanece somente no backend.
- **Impacto LGPD:** Sem nova coleta, persistência, exposição ou retenção de dados pessoais.
- **Impacto multi-tenant:** Nenhum. A autorização e o tenant derivados do usuário autenticado permanecem inalterados.
- **Integrações afetadas:** LSX Medical, endpoints `business-days`, `available-times` e `doctors`.
- **Arquivos envolvidos:** `LsxMedicalSchedulingClient.php`, `ConsultationSchedulingTest.php` e documentação de telemedicina.

## Plano de implementação

1. Inspeção: comparar o payload atual com o contrato observado na homologação sem enviar dados pessoais.
2. Implementação: mapear o campo interno `specialty_id` para `specialty` apenas nos três endpoints de disponibilidade.
3. Validação: executar teste focado, suíte PHP, análise de estilo, revisão de segurança/LGPD e revisão do diff.

## Testes

- `ConsultationSchedulingTest`: 10 testes e 61 asserções aprovados.
- Suíte PHP: 147 testes e 1082 asserções aprovados.
- Laravel Pint nos arquivos PHP alterados: aprovado.
- Homologação LSX, somente leitura: cliente corrigido retornou dias, horários e profissionais para a especialidade 165.
- `git diff --check`: aprovado.

## Security Review

- Token continua restrito ao cliente HTTP no backend.
- Entrada do browser continua validada por Form Request e não fornece tenant, paciente, CPF ou pagamento.
- Rotas continuam protegidas por autenticação, perfil, verificação, rate limit e CSRF.
- Nenhum payload sensível ou resposta do provedor é adicionado aos logs.

## Decisões

- Manter `specialty_id` no contrato interno Vue/Laravel para preservar compatibilidade.
- Manter `specialty_id` no endpoint `create-consultation`, conforme seu contrato específico.
- Não adicionar fallback com duas chamadas, para evitar requisições duplicadas e ocultar divergências de contrato.

## Pendências

- A separação interna por clínica no token LSX e a política de retenção dos agendamentos locais permanecem `NEEDS_VERIFICATION`, sem alteração nesta tarefa.
