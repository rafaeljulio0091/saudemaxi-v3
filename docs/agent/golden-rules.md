# Golden rules do Engineering Harness

1. **Existing Code First:** pesquise modelos, rotas, services, actions,
   policies, requests, clients, composables e stores antes de criar algo.
2. **Minimal Change:** altere somente o necessário; não refatore produção como
   efeito colateral.
3. **No Guessing:** não invente endpoints, tabelas, colunas, contratos,
   eventos, webhooks ou capacidades de provedor.
4. **Preserve Authentication:** mantenha guard, sessão, CSRF, regeneração e
   recuperação de senha existentes.
5. **Backend Security:** autorização, validação e rate limiting pertencem ao
   servidor; UI não é controle de acesso.
6. **Tenant Isolation:** derive o tenant do contexto confiável e valide cada
   relação; nunca confie em IDs enviados pelo browser.
7. **Sensitive Data:** trate saúde, CPF, credenciais e tokens como restritos e
   aplique `privacy-lgpd-checklist.md`.
8. **External API Isolation:** mantenha clientes LSX/IA no backend, com timeout,
   erros e logs sanitizados.
9. **Secrets:** nunca versione ou exponha tokens, senhas, API keys ou client
   secrets.
10. **Validation:** escolha o risk gate, rode as validações compatíveis,
    revise o diff e entregue relatório com riscos e pendências.
