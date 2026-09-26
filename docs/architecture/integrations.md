# Integrações externas

## Implementação atual

- LSX Medical é acessada pelos clientes em `app/Services/Telemedicine`.
- Provedores de IA são abstraídos por `ConversationalAIProvider` e
  `DecisionAIProvider`, com implementações `OpenAIProvider` e `JevProvider`.
  As URLs/chaves são obtidas de `config/ai.php` e as chamadas são server-side.
- O assistente MAX (`app/Max`) usa contratos próprios, `AssistantIntentProvider`
  (Jev, `JevAssistantProvider`) e `AssistantReplyProvider` (OpenAI,
  `OpenAIAssistantProvider`), com as mesmas credenciais de `config/ai.php`,
  habilitados por `ai.max.enabled`. Regras determinísticas (emergência via
  `SafetyRuleEngine`, privacidade, medicação, sintomas) rodam antes e sem IA;
  falhas de provedor voltam para essas regras. Links só vêm de
  `MaxActionCatalog`; conversas não são persistidas nem registradas em log.
- Testes de integração HTTP usam `Http::fake()` e asserções de requisições;
  não há dependência declarada da API real nos testes.
- Não foram encontrados webhooks, listeners, filas ou jobs customizados.

## Invariantes

- Segredos permanecem em configuração backend e nunca em JavaScript/props.
- Clientes HTTP devem normalizar falhas e redigir dados sensíveis nos logs.
- Integrações não devem ser chamadas diretamente por controllers quando já há
  uma camada dedicada.

## Riscos e lacunas

- A cobertura de timeout, conexão recusada, JSON inválido e 429 varia por
  provedor; manter testes de contrato quando alterar clientes.
- Não há evidência de rotação de segredo, circuit breaker ou observabilidade
  estruturada comum a LSX e IA: `NEEDS_VERIFICATION`.
- Triage persiste mensagens, avaliações e eventos de IA; revisão de retenção e
  minimização LGPD é obrigatória em qualquer alteração.

## Arquivos principais

`app/AI/**`, `config/ai.php`, `app/Services/Telemedicine/**`,
`tests/Feature/AiProvidersTest.php` e testes de telemedicina.
