# Autenticação

## Implementação atual

- O guard `web` usa driver `session` e provider Eloquent `App\Models\User`
  (`config/auth.php`).
- Login, cadastro, logout, confirmação, verificação de e-mail e recuperação
  de senha usam as rotas de `routes/auth.php` e controllers em
  `app/Http/Controllers/Auth`.
- O login é validado por `LoginRequest`. Por padrão usa `Auth::attempt`; quando
  `config('lsxmedical.login_enabled')` é verdadeiro, usa
  `LsxMedicalAuthenticator`.
- Depois do login a sessão é regenerada. No logout ela é invalidada e o token
  CSRF é regenerado (`AuthenticatedSessionController`).
- A sessão usa o driver `database` por padrão (`config/session.php`), com
  cookie HTTP-only e SameSite `lax` por padrão. `secure`, domínio, duração e
  criptografia dependem do ambiente.
- CSRF é fornecido pelo middleware web padrão do Laravel. A confirmação de
  senha guarda `auth.password_confirmed_at` na sessão.
- O login local limita cinco tentativas por combinação de e-mail
  transliterado e IP. Verificação de e-mail e envio de notificação usam
  `throttle:6,1`; o broker de reset tem throttle de 60 segundos.

## Fluxo LSX

`LsxMedicalAuthClient` envia e-mail e senha somente durante a chamada HTTPS
server-side para o endpoint configurado. O token da clínica vem de
`TELEMEDICINE_API_TOKEN` via `config/lsxmedical.php`. Em sucesso,
`LsxMedicalAuthenticator` encontra/cria o usuário local e chama
`Auth::login`.

## Invariantes

- Não criar outro guard, token de navegador ou mecanismo de sessão.
- Credenciais do paciente não podem ser persistidas ou registradas.
- O token LSX deve permanecer em configuração server-side.
- Alterações de login devem preservar regeneração, invalidação e CSRF.

## Riscos e lacunas

- Usuários novos criados no registro local e pelo login LSX não recebem
  `tenant_id` ou uma regra de associação explícita no código. A consequência
  de produto e provisionamento é `NEEDS_VERIFICATION`.
- `LsxMedicalAuthClient` registra a mensagem de `ConnectionException`; revisar
  redaction antes de ampliar telemetria.
- Não há evidência no código de MFA, rotação adicional de sessão ou política
  de expiração além da configuração Laravel.

## Arquivos principais

`config/auth.php`, `config/session.php`, `config/lsxmedical.php`,
`routes/auth.php`, `app/Http/Requests/Auth/LoginRequest.php`,
`app/Http/Controllers/Auth/*`, `app/Services/Telemedicine/LsxMedical*` e
`tests/Feature/Auth/*`.
