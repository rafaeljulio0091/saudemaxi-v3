# Security checklist

- [ ] Autenticação e sessão preservadas; logout invalida sessão e regenera CSRF.
- [ ] Autorização server-side cobre perfil, tenant, recurso e IDOR.
- [ ] Tenant não é aceito como autoridade em input do browser.
- [ ] CSRF, cookies, HTTPS e `http_only` foram avaliados.
- [ ] Entrada validada por Form Request quando o fluxo é não trivial.
- [ ] Queries usam Eloquent/escopo correto; não há SQL ou XSS introduzido.
- [ ] Mass assignment permanece explícito.
- [ ] Rate limiting existente foi preservado e novos limites são justificados.
- [ ] Segredos, senhas, tokens, CPF e dados clínicos não aparecem em logs,
      traces, exceptions, HTML, props ou storage do browser.
- [ ] Integração externa trata timeout, conexão, 4xx, 5xx, JSON inválido e
      idempotência/retry.
- [ ] Uploads, jobs e filas, quando tocados, têm autorização, tipo/tamanho,
      armazenamento e serialização avaliados.
- [ ] Erros públicos não revelam stack trace, payload externo ou enumeração.
- [ ] Testes cobrem sucesso, falhas relevantes, isolamento e repetição segura.
