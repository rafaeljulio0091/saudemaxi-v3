# Frontend

## Implementação atual

- Vue 3 é inicializado por `resources/js/app.js` através do Inertia Vue 3.
- Inertia é a navegação de páginas; não há Vue Router.
- `createPinia()` é registrado globalmente. O store `max` reseta quando muda a
  chave de contexto Inertia; há também stores de triagem e UI de saúde.
- Axios é centralizado em `resources/js/services/http.js`, com timeout,
  cabeçalho AJAX e mapeamento de respostas 401, 403, 404, 419, 409, 422,
  429, 500 e 503. Serviços de saúde usam `healthcareClient.js`.
- Páginas Inertia ficam em `resources/js/Pages`; layouts e componentes são
  reutilizados pela área de saúde. CSS de tokens/componentes fica em
  `resources/css/healthcare` e há configuração Tailwind/Vite.
- O backend compartilha `auth.user` via `HandleInertiaRequests` e os
  controllers compartilham contexto de tenant conforme o perfil.

## Invariantes

- Não introduzir Vue Router ou uma segunda camada HTTP.
- Frontend não é barreira de autorização; erros do backend devem continuar
  sendo tratados sem expor payloads sensíveis.
- Não guardar credenciais, tokens LSX ou dados clínicos em localStorage,
  sessionStorage, URL ou props desnecessárias.

## Riscos e lacunas

- A aplicação usa Pinia, mas não há evidência de uma store global de usuário ou
  tenant; não duplicar props de servidor sem necessidade.
- O estado de demonstração utiliza sessão e dados fictícios; não confundir com
  autorização de produção.
- Responsividade e acessibilidade devem ser verificadas nas telas alteradas;
  o Harness não substitui inspeção visual.

## Arquivos principais

`resources/js/app.js`, `resources/js/services/http.js`,
`resources/js/services/healthcareClient.js`, `resources/js/stores/*`,
`resources/js/Pages/**`, `resources/css/healthcare/**`, `vite.config.js` e
`package.json`.
