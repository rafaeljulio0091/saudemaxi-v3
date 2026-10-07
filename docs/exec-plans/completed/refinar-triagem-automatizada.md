# Exec Plan: Refinar coleta da triagem automatizada

- **Status:** completed
- **Objetivo:** permitir que o agente de triagem colete progressivamente informações relevantes antes de encaminhar relatos clínicos incompletos para avaliação humana.
- **Contexto:** o fluxo atual encerra a sessão quando o Jev aponta baixa confiança ou informações insuficientes, mesmo que a conversa automatizada ainda tenha uma pergunta útil a fazer. Um relato curto, como "dor de cabeça", pode ser encaminhado cedo demais.
- **Escopo:** instruções do agente conversacional, critérios do Jev, regra de decisão da triagem e testes de regressão dos caminhos ativo, humano e emergência.
- **Fora de escopo:** integração LSX, rotas, banco de dados, telas Vue/Inertia, regras clínicas determinísticas e novos critérios médicos.
- **Risco:** HIGH
- **Dados sensíveis envolvidos:** mensagens e estado estruturado da pré-triagem, já persistidos de forma criptografada.
- **Impacto LGPD:** sem novos dados, logs ou exposições. O processamento permanece no backend e os eventos continuam sem registrar o relato clínico bruto.
- **Impacto multi-tenant:** nenhum novo acesso a dados. Sessões, mensagens, avaliações e eventos continuam vinculados ao `tenant_id` resolvido no servidor.
- **Integrações afetadas:** OpenAI e Jev apenas nos prompts e na interpretação do resultado. LSX não será alterada.
- **Arquivos envolvidos:** `resources/prompts/triage-assistant.txt`, `app/AI/Providers/JevProvider.php`, `app/Triage/Services/TriageDecisionEngine.php`, `tests/Feature/TriageTest.php`, `tests/Feature/AiProvidersTest.php`.

## Plano de implementação

1. Inspeção: confirmar contratos dos provedores, estados da sessão, persistência, isolamento por tenant, UI consumidora e cobertura atual.
2. Implementação: orientar coleta progressiva e separar falta de contexto coletável de uma necessidade efetiva de revisão humana, mantendo escalonamentos de segurança imediatos.
3. Validação: executar testes focados e completos, Pint, testes frontend, build, verificação de diff e scripts obrigatórios do Harness.

## Testes

- Relato clínico inicial incompleto continua com sessão ativa mesmo com baixa confiança do Jev.
- Conversa concluída com baixa confiança continua sendo encaminhada para revisão humana.
- Emergência e prioridade continuam interrompendo a coleta.
- Pedido explícito ou limitação declarada pelo agente continua encaminhando para revisão humana.
- Falhas de provedores continuam usando o fallback humano seguro.
- Prompt e perguntas enviadas aos provedores preservam conteúdo do paciente separado das instruções.
- `php artisan test`: 155 testes e 1.145 asserções aprovados.
- `npm run test:frontend`: aprovado.
- `npm run build`: aprovado.
- `npm run format:check`: aprovado.
- Pint focado nos arquivos alterados: aprovado.
- `git diff --check`: aprovado.

## Security Review

- Não confiar em `tenant_id` ou identidade enviados pelo navegador.
- Não incluir relato clínico em logs, metadados de evento ou mensagens de exceção.
- Preservar proteção contra prompt injection e saída insegura.
- Preservar escalonamento determinístico e probabilístico de emergência para o SAMU 192.

## Decisões

- `conversation_complete=false` representa coleta ainda possível e impede que apenas baixa confiança ou insuficiência apontada pelo Jev encerre a conversa.
- `requires_human_review=true` emitido pelo agente conversacional continua encerrando a conversa, pois representa uma limitação que não deve ser mascarada.
- Classificações de emergência e prioridade continuam imediatas, independentemente da completude.
- Uma classificação humana provisória do Jev será registrada como classificação convencional enquanto a coleta permanece ativa, evitando expor um encaminhamento que ainda não ocorreu.

## Pendências

- As regras determinísticas de segurança continuam com a versão pendente de aprovação clínica já configurada no projeto.
- O Pint global continua apontando formatação preexistente em `bootstrap/app.php`, arquivo não alterado por esta implementação.
