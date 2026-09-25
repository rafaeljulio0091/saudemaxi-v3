# Testing strategy

## Cobertura existente

- Feature: autenticação (`tests/Feature/Auth/*`), dashboard, perfil,
  pacientes, consultas, triagem, área de saúde, demonstração e provedores de
  IA.
- Unit: apenas o teste de exemplo está presente.
- Frontend: `tests/Frontend/healthcare.test.mjs` executado pelo script
  `npm run test:frontend`.
- Build: `npm run build` usa Vite.
- Formatação: `vendor/bin/pint --test` quando o binário estiver disponível.
- HTTP externo: os testes usam `Http::fake()`; não depender da API LSX, OpenAI
  ou JEV real.

## Prioridades por mudança

- Auth: sucesso, credencial inválida, usuário inexistente quando aplicável,
  rate limit, sessão, logout e ausência de chamada externa no modo local.
- Tenant/IDOR: usuário sem tenant, tenant diferente, perfil incorreto e acesso
  a UUID/ID de outro paciente.
- LSX: sucesso, 401/403, 404, 422, 409, 429, 500, timeout, conexão recusada e
  JSON inválido, com token ausente nos outputs do browser/logs.
- Dados sensíveis: payload mínimo, não persistência de senha e logs
  sanitizados.
- Frontend: erro HTTP, cancelamento, troca de contexto, build e telas nos
  breakpoints relevantes.

## Comandos do repositório

```text
php artisan test
vendor/bin/pint --test
npm run test:frontend
npm run build
git diff --check
```

Executar somente comandos disponíveis e registrar qualquer limitação.
