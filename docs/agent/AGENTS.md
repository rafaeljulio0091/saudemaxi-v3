# Mapa rápido do Harness

Antes de criar ou alterar algo:

1. Pesquise o código existente e leia somente os documentos relevantes em
   `docs/ARCHITECTURE.md` e `docs/architecture/`.
2. Classifique LOW, MEDIUM ou HIGH. Planeje MEDIUM/HIGH; para HIGH faça
   security review e privacy/LGPD review antes e depois.
3. Preserve autenticação, CSRF, sessão, autorização e isolamento de tenant.
4. Trate dados médicos, CPF, credenciais e tokens como sensíveis; mantenha
   integrações externas no backend.
5. Execute `scripts/agent/preflight.sh`, validações compatíveis e
   `scripts/agent/changed-files.sh`.
6. Revise o diff e produza relatório com arquivos, testes, riscos e pendências.

Regras detalhadas: `golden-rules.md`, `forbidden-actions.md`,
`security-checklist.md`, `privacy-lgpd-checklist.md` e
`definition-of-done.md`.
