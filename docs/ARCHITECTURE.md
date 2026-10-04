# Arquitetura atual

Este é um mapa curto do código encontrado em 25/09/2026. Ele registra fatos
observados no repositório, não uma arquitetura desejada.

## Fluxo principal

```text
Browser
  -> Laravel web middleware + sessão
  -> rotas web/auth/healthcare
  -> Controllers + Form Requests
  -> Services/Actions/Policies
  -> Eloquent/SQLite ou MySQL configurado
  -> clientes HTTP LSX/AI quando aplicável
  -> Inertia/Vue ou JSON
```

- A navegação usa Inertia.js com Vue 3. Não há Vue Router.
- O frontend usa Axios centralizado em `resources/js/services/http.js` e
  Pinia para estado client-side do MAX.
- A autenticação web usa o guard de sessão Laravel e os controllers padrão do
  Breeze em `app/Http/Controllers/Auth`.
- A área de saúde está registrada em `routes/healthcare.php`, com páginas
  Inertia e endpoints JSON sob `/triagem`.
- A integração de telemedicina fica em `app/Services/Telemedicine` e usa a
  API LSX Medical somente a partir do backend.
- Os dados de triagem persistem em modelos próprios com `tenant_id` e são
  protegidos por `TriageSessionPolicy`.

## Mapa detalhado

- [Autenticação](architecture/authentication.md)
- [Autorização](architecture/authorization.md)
- [Multi-tenancy](architecture/multi-tenancy.md)
- [Frontend](architecture/frontend.md)
- [Banco de dados](architecture/database.md)
- [Telemedicina](architecture/telemedicine.md)
- [Cadastro nativo de saúde](architecture/native-healthcare-registry.md)
- [Outras integrações](architecture/integrations.md)

## Limites confirmados

- Não foram encontrados `app/Jobs`, listeners, observers ou repositories
  customizados no código atual.
- Não há `routes/api.php`; as respostas JSON de saúde estão em rotas web
  autenticadas.
- O fluxo de agendamento usa a API LSX a partir do backend e mantém um espelho
  local tenant-scoped. Vídeo continua explicitamente marcado como integração
  pendente em `HealthcareDataService`.
- A aplicação possui cadastro nativo de pacientes e diretório de saúde. O
  acesso novo usa `TenantContext`, Policies e Services com queries escopadas;
  ainda não existe global scope de tenant para todo o código legado.

Pontos não comprovados no código devem permanecer marcados como
`NEEDS_VERIFICATION` nos documentos específicos.
