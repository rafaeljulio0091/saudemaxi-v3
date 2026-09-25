# Ações proibidas

- Alterar `.env` com credenciais reais ou inserir secrets no código.
- Registrar senha, token, Authorization header, CPF desnecessário ou payload
  clínico completo.
- Retornar token de integração ao browser ou persistir credenciais externas.
- Colocar dados de saúde em URL/query string ou browser storage.
- Criar autenticação paralela, remover autorização ou desabilitar CSRF.
- Usar `->withoutMiddleware()` como correção permanente.
- Usar `verify=false` para ignorar validação TLS.
- Editar migration antiga de produção para alterar schema.
- Criar tabela, coluna ou cliente sem pesquisar a estrutura existente.
- Adicionar retry indiscriminadamente, especialmente em operações não
  idempotentes.
- Remover validação para fazer uma integração funcionar.
- Inventar webhook, endpoint LSX, prescrição sincronizada ou regra clínica.
- Fazer alterações de produção para criar ou validar este Harness.
