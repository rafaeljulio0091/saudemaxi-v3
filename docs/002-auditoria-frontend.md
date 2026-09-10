# Migração do frontend Saúde Maxi

## Auditoria e decisão de arquitetura

A base inspecionada contém Laravel 12, Breeze, Vue 3, Inertia 2, Ziggy, Axios, Vite 7 e Tailwind 3 via PostCSS. O plugin de Tailwind 4 está declarado, mas não integra o build. Não existe Vue Router, contexto de tenant, perfil de paciente/gestor, cadastro clínico ou serviço de telemedicina. A autenticação por e-mail e sessão, `/dashboard`, `/profile` e as rotas de senha devem ser preservadas.

Em 09/09/2026 o usuário confirmou manter Inertia e adaptar o documento 001. As páginas usam as rotas Laravel como única navegação. Contexto autorizado do servidor não deve ser duplicado em Pinia; Pinia fica reservado à conversa transitória do MAX e à interface.

Baseline executado: 25 testes PHP passaram (61 assertions); build Vite passou. `git status` falhou porque o Git não reconhece os metadados disponíveis neste ambiente.

## Limite entre demonstração e produto

A referência é inteiramente simulada. A demonstração migrada fica em `/demonstracao`, exclusivamente em ambiente local/teste, com dados fictícios e contexto mantido pelo servidor. Ela não autentica uma pessoa no produto, não concede acesso aos registros reais e não chama o fornecedor. Trocar cenário nessa área só seleciona fixtures conhecidas. Não há persistência de saúde no localStorage.

As rotas de produto permanecem protegidas pela autenticação Laravel e apresentam indisponibilidade até existir o vínculo confiável entre usuário, tenant, perfil e contrato. Não é seguro inferir esse vínculo de um usuário Breeze ou permitir que o usuário escolha seu papel no produto.

## Mapeamento

As URLs abaixo recebem o prefixo `/demonstracao` no ambiente de revisão.

| Referência | URL | Página Vue | Estado/serviço |
| --- | --- | --- | --- |
| A1-A4, entrada | `/login`, `/register`, `/forgot-password` | Auth existente | Formulários Inertia e sessão Laravel |
| B1-B2, início | `/inicio` | Patient/Home | Contexto servidor, patient service |
| D1-D6, orientação | `/orientacao` | Patient/Guidance | Conversa em memória, max service |
| E1-E3, atendimento | `/atendimento` | Patient/Immediate | appointment service, repasse ao fornecedor pendente |
| F1-F3, agenda | `/agendamento` | Patient/Scheduling | Estado local sequencial, appointment service |
| C1-C3, receitas/foto | `/farmacia` | Patient/Pharmacy | pharmacy service, seleção de imagem local |
| C4, resultado | `/receita/{id}` | Patient/Prescription | Confirmação da leitura separada da cobertura |
| C5-C6, farmácias | `/farmacias` | Patient/Pharmacies | pharmacy service, reserva bloqueada |
| F4, histórico | `/consultas` | Shared/Consultations | Busca e estado, appointment service |
| H1-H2, conta | `/conta` | Patient/Account | patient service, idiomas ainda sem tradução contratada |
| G1-G5, saúde mental | `/nr1` | Patient/MentalHealth | Trilha bloqueada, sem coleta individual |
| X1, ajuda | `/ajuda` | Patient/Help | Encaminhamento 192 preservado |
| J1, operação | `/gestor/painel` | Manager/Dashboard | Indicadores fictícios e agregados |
| Lista de pacientes | `/gestor/pacientes` | Manager/Patients | Busca, filtros, paginação, patient service |
| Ficha | `/gestor/pacientes/{id}` | Manager/Patient | Escopo de cenário no servidor |
| Consultas | `/gestor/consultas` | Shared/Consultations | Marcação simulada, sem cobrança |
| Contratos | `/gestor/planos` | Manager/Plans | plan service, capacidades centralizadas |
| White label | `/gestor/identidade` | Manager/Branding | Preview local, confirmação no servidor |
| Integrações | `/gestor/integracao` | Manager/Integrations | Catálogo documentado, sem conexão ativa |
| I1, estação | Layout responsivo | HealthcareLayout | Fonte legível e alvos de 44 px |

## Divergências identificadas

- O documento 001 pressupunha Vue ausente; a aplicação já usa Inertia. Não substituir o roteador nem criar stores para copiar todas as props.
- Buscas de receita, ficha e histórico da referência nem sempre respeitam o cliente. Na migração, a filtragem e autorização devem ocorrer no servidor, inclusive nos IDs de detalhe e nas mutações.
- A referência grava dados e conversa em localStorage. A migração não mantém dados de saúde nesse armazenamento.
- Confirmar uma leitura na referência muda cobertura para gratuita. Confirmação de texto não comprova cobertura. Manter esses estados separados.
- O protótipo mostra fila e sala, mas a documentação funcional atribui ambas ao fornecedor. A camada nova só representa o repasse e o histórico.
- Há mensagens de pagamento concluído sem chamada correspondente. A demonstração deve identificar cada resultado como simulado, sem coletar cartão ou anunciar cobrança real.
- Receita com origem plataforma é fixture, não integração. Não anunciar sincronização Mevo.
- Reserva de medicamento, cancelamento remoto, voz, upload de identidade, consentimento de menores, trilha NR-1 e roteiro clínico não possuem contrato suficiente para execução real.
- A referência inclui tags clínicas e NR-1 no cadastro. Não reproduzir informação individual de saúde mental na gestão.

## Contratos para integração real

O servidor deverá resolver tenant, perfil, paciente vinculado, plano e módulos a partir da autenticação confiável antes de habilitar os serviços. O frontend recebe apenas contexto permitido e endpoints locais publicados pelo Laravel. Tokens ficam em configuração privada do servidor.

Operações documentadas do fornecedor: login-patient; create-emergency-consultation; scheduling/specialties; business-days; available-times; doctors; create-consultation; update-payment-status; consultation-history; filter-patients; update-patient; create-patient; patient-tags. Inativação depende de habilitação administrativa. Validar o contrato completo em homologação antes de implementar chamadas.

A sequência de agenda deve validar cada escolha contra a anterior, invalidar seleções posteriores ao voltar e evitar repetição de criação/pagamento. Reconciliação usa histórico do fornecedor. Não há webhook geral nem endpoint de prescrição. OCR, catálogo de farmácias, cobertura oficial, vínculo plano/módulos e auditoria clínica persistente requerem backend próprio e definições de produto.

## Auditoria da implementação e continuidade

A retomada foi feita sobre os arquivos presentes, sem reverter alterações. Como os metadados Git não são reconhecidos pelo ambiente, o inventário abaixo registra os arquivos trabalhados nesta execução; não equivale a uma comparação certificada com um commit anterior.

| Achado | Correção / resultado |
| --- | --- |
| Texto do convite do MAX comprimido em 375 px | Largura mínima do conteúdo e quebra do botão no card, mantendo os alvos de toque. |
| Branco sobre marcas claras | Cor do texto do botão calculada pela luminância; links usam uma variante escura da mesma marca. |
| Busca e paginação limitadas à lista do navegador | Busca no serviço Laravel após aplicar o escopo, limite de 50 registros por página, debounce e cancelamento de requisições obsoletas. Na integração real, aplicar os mesmos filtros na consulta paginada do fornecedor ou banco. |
| Resposta do MAX podendo chegar após trocar perfil | Store descarta respostas de um contexto anterior e impede envio duplicado. Conversa limitada a 40 mensagens em memória. |
| Dois campos do MAX com o mesmo ID | IDs gerados por instância com `useId`. |
| Nome antigo após salvar cadastro | Atualização do registro da ficha e recarga das props autorizadas da conta. |
| Atalho e descrição de atendimento mesmo sem módulo | Exibição condicionada à capacidade enviada pelo servidor. |
| IDs numéricos enviados como texto aceitos pela validação, mas sem correspondência na atualização | Normalização de IDs e booleanos após validação, com teste incluindo rejeição de plano de outro cenário. |
| Limites de requisições compartilhavam o contador e bloqueavam a troca de cenário após navegação normal | Prefixos independentes para seleção, leitura e operação, mantendo 30/120/60 requisições por minuto e teste de regressão do bloqueio. |
| Prévia white label herdava a altura da aplicação; gráfico por hora quebrava em duas linhas no celular | Altura da prévia limitada ao conteúdo e grade com as 24 horas na mesma linha. |
| Mutações simultâneas sobre fixtures da sessão | Bloqueio de sessão nas operações POST para serializar as gravações. |

Não foram adicionados modelos de paciente, tenant, contrato, banco clínico ou um segundo mecanismo de autenticação. A seleção de perfil só existe no ambiente fictício. A aplicação real continua exigindo o vínculo autenticado antes de liberar os módulos.

## Estrutura e inventário da entrega

Arquivos criados nesta migração:

```text
.prettierrc.json
config/healthcare.php
routes/healthcare.php
app/Http/Middleware/EnsureHealthcareDemo.php
app/Http/Controllers/Healthcare/DemoController.php
app/Http/Requests/Healthcare/
    DemoScenarioRequest.php
    DemoOperationRequest.php
app/Services/Healthcare/
    DemoContext.php
    DemoHealthcareService.php
    DemoMaxService.php
database/fixtures/healthcare-demo.json
lang/pt_BR/
    auth.php
    passwords.php
    validation.php
resources/css/healthcare/
    tokens.css
    components.css
    utilities.css
resources/js/Layouts/
    HealthcareLayout.vue
    HealthcareGuestLayout.vue
resources/js/Components/Healthcare/
    AppAlert.vue
    AppButton.vue
    AppCard.vue
    AppField.vue
    AppIcon.vue
    AppModal.vue
    AppNavigation.vue
    AppPagination.vue
    AsyncState.vue
    MaxConversation.vue
    PageHeader.vue
    ServiceCard.vue
    StatusBadge.vue
resources/js/Pages/Healthcare/
    Demo.vue
    NotReady.vue
    Unavailable.vue
    Patient/{Home,Guidance,Immediate,Scheduling,Pharmacy,Prescription,
             Pharmacies,Account,MentalHealth,Help}.vue
    Manager/{Dashboard,Patients,Patient,Plans,Branding,Integrations}.vue
    Shared/Consultations.vue
resources/js/composables/
    useAsyncState.js
    useHealthcare.js
    useHealthcareServices.js
resources/js/constants/
    consultationStatus.js
    healthcareNavigation.js
    integrationCatalog.js
resources/js/stores/
    healthcareUi.js
    max.js
resources/js/services/
    http.js
    healthcareClient.js
    patient.service.js
    appointment.service.js
    pharmacy.service.js
    plan.service.js
    max.service.js
resources/js/utils/
    brandColor.js
    healthcareFormat.js
tests/Feature/HealthcareDemoTest.php
tests/Frontend/healthcare.test.mjs
scripts/check-healthcare-ui.mjs
docs/002-auditoria-frontend.md
```

Arquivos existentes alterados:

- `.env.example`: flag segura de demonstração, desligada por padrão, nome Saúde Maxi e locale `pt_BR` para novas instalações.
- `package.json` e `package-lock.json`: Pinia para estado transitório compartilhado, Prettier como ferramenta de desenvolvimento e scripts de verificação. Lockfile atualizado pelo npm.
- `routes/web.php`: inclusão das rotas de saúde e disponibilidade da demonstração na entrada.
- `resources/js/app.js`: instalação do Pinia e descarte da conversa ao mudar de contexto.
- `resources/js/bootstrap.js`: reaproveitamento do cliente HTTP centralizado.
- `resources/js/Pages/Welcome.vue`: entrada visual Saúde Maxi.
- `resources/js/Pages/Auth/{Login,Register,ForgotPassword,ResetPassword,ConfirmPassword,VerifyEmail}.vue`: formulários com o novo layout, mantendo os contratos Inertia/Laravel existentes.

Arquivos removidos: nenhum. O build regenerou `public/build`, que é artefato de compilação. Não houve alteração em `composer.json`, `composer.lock`, Vite, migrations, models ou schema. `/dashboard` e `/profile` mantêm as telas administrativas originais do Breeze; o novo cadastro visual de paciente fica em `/demonstracao/conta`.

## Componentes, estado e fronteira HTTP

`AppModal` usa dialog nativo, Escape e retorno de foco. `AsyncState` padroniza carregamento, erro recuperável e vazio. `AppField`, `AppButton`, `AppAlert`, `AppPagination` e `StatusBadge` centralizam controles e feedback. `AppNavigation` atende sidebar e menu mobile. O layout mantém tokens locais em `.sm-app`, sem alterar o CSS global do Breeze.

Pinia tem somente duas stores: `healthcareUi` controla menu/MAX abertos; `max` mantém conversa transitória, envio pendente, erro e identificação do contexto. Tenant, plano, módulos e perfil vêm de props Inertia. `useHealthcare` fornece a leitura centralizada das capacidades; isso não substitui a autorização Laravel.

O cliente Axios centraliza timeout, cookies/CSRF padrão do Laravel e mensagens de erro 401/403/404/419/422/429/5xx. `useAsyncState` cancela leituras anteriores e ignora resultados obsoletos. As páginas recebem serviços por `useHealthcareServices`; o cliente recebe `apiBase` do servidor, permitindo futuramente publicar uma implementação real com o mesmo contrato local, sem chamar o fornecedor pelo navegador.

Todos os endpoints abaixo são locais e demonstrativos, sob `/demonstracao/dados`. Não são endpoints externos inventados.

| Serviço | GET | POST |
| --- | --- | --- |
| patient | `patient/{id}`, `account`, `dashboard` | `patients-search`, `patient`, `create-patient` |
| appointment | `consultations`, `specialties` | `consultations-search`, `days`, `times`, `doctors`, `schedule`, `payment`, `emergency` |
| pharmacy | `prescriptions`, `prescription/{id}`, `pharmacies` | `photo`, `confirm-item` |
| plan | `plans` | `plan`, `branding` |
| max | Nenhum | `max` |

A agenda valida dependências no servidor e usa uma chave de idempotência para criação. Pagamento é apenas marcação fictícia autorizada para gestor. Buscas usam POST para evitar colocar textos potencialmente pessoais na URL. A foto permanece como preview local, sem upload; `photo` devolve uma receita fictícia conhecida e não faz OCR.

## Como revisar localmente

Com as dependências já instaladas:

```bash
npm run build
APP_ENV=local HEALTHCARE_DEMO_ENABLED=true SESSION_DRIVER=file CACHE_STORE=file php artisan serve --host=127.0.0.1 --port=8097
```

Abrir `http://127.0.0.1:8097/demonstracao`. Usar somente informações fictícias. Selecionar cliente, perfil, plano e estado dos serviços: normal, lento, erro ou vazio. O cenário municipal demonstra o bloqueio da agenda por regulação; Cetid permite percorrer todas as escolhas da agenda.

A flag pode ser configurada no ambiente local sem alterar o código. Para as mensagens de validação e autenticação em português, usar `APP_LOCALE=pt_BR` e `APP_FALLBACK_LOCALE=pt_BR`; o `.env` existente não foi alterado. Se houver configuração Laravel previamente em cache, executar `php artisan config:clear` no ambiente de desenvolvimento. Nenhum comando de migration ou seeder é necessário para esta entrega. As alterações demonstrativas duram a sessão; uma nova sessão de navegador começa das fixtures originais. A flag não habilita demonstração em produção.

O teste visual é executável com Playwright/Chromium disponíveis no ambiente de desenvolvimento:

```bash
PLAYWRIGHT_MODULE=/caminho/para/playwright/index.mjs CHROMIUM_PATH=/caminho/para/chromium node scripts/check-healthcare-ui.mjs
```

Também aceita `UI_BASE_URL` e `UI_OUTPUT`. Playwright não foi adicionado como dependência de produção. Capturas ficam por padrão em `/tmp/healthcare-ui`.

## Pendências para produção

A entrega permite revisar os fluxos visuais com fixtures isoladas. A ativação real ainda depende de:

1. Contrato aprovado de vínculo usuário, tenant, perfil, paciente, plano e módulos, com autorização de acesso a dados sensíveis. Enquanto isso, as 18 URLs canônicas de saúde exigem autenticação e respondem 503, sem usar o contexto demonstrativo.
2. Credenciais e homologação do fornecedor, implementação do adaptador Laravel, normalização de erros, reconciliação do histórico e acesso seguro à sala externa. Não há integração real ativa nem token enviado ao navegador.
3. Upload seguro de receitas e documentos, OCR real, catálogo oficial de farmácias, cobertura e eventual reserva. Confirmar texto jamais libera cobertura. Prescrições Mevo não são sincronizadas.
4. Regras de cobrança, idempotência remota e auditoria persistente das alterações de pagamento. A marcação da demonstração não cobra ou estorna.
5. Definições e aprovação do roteiro clínico/MAX e da trilha NR-1. O MAX atual é determinístico e demonstrativo, com regra/versão/ID de rastreio na resposta; isso não constitui auditoria clínica persistente. Não existe avaliação individual de saúde mental para gestores.
6. Contratos para dependentes/menores, consentimento, idiomas adicionais, voz, geolocalização, cancelamento e inativação administrativa. Esses recursos não foram simulados como operações reais.

Não são necessárias migrations nesta migração visual. Os índices, tabelas, retenção de dados e políticas da futura integração deverão ser definidos a partir dos vínculos e consultas reais, sem presumir o modelo clínico do fornecedor.

## Validação executada

| Verificação | Resultado |
| --- | --- |
| `php artisan test` | PASS: 38 testes, 231 assertions. Inclui os testes originais de autenticação/perfil e 13 testes da demonstração. |
| `npm run build` | PASS: build de produção Vite, páginas divididas em chunks. |
| `npm run test:frontend` | PASS. |
| `node tests/Frontend/healthcare.test.mjs` | PASS: quatro testes de estado do MAX, concorrência, contraste e estados oficiais. |
| `npm run format:check` | PASS. |
| Laravel Pint nos arquivos PHP envolvidos | PASS. |
| `php artisan route:list --except-vendor` | Inspecionado: 60 rotas, incluindo autenticação/perfil preservados. |
| `php artisan route:list --path=demonstracao` | Inspecionado após a correção dos limites: 22 rotas. |
| `git status --short` | Indisponível: diretório não reconhecido como repositório Git neste ambiente. Não foi possível certificar o diff por commit nem criar commits incrementais. |

Os testes PHP usam o banco descartável definido em `phpunit.xml`; não houve migração experimental do banco de trabalho. A inspeção visual inclui capturas de início, receita, painel e identidade nas quatro larguras. As primeiras execuções do roteiro visual identificaram o erro 429 corrigido acima; uma falha intermitente de carregamento no servidor Vite não se reproduziu isoladamente. A conferência final do navegador utiliza uma instância Laravel local com o build compilado, sem modificar o servidor Vite existente.

Resultado final de `scripts/check-healthcare-ui.mjs`: **PASS**, executado no Chromium contra o build compilado. Foram 72 verificações de páginas de saúde (18 URLs × 4 larguras) e 16 de entrada/autenticação (4 URLs × 4 larguras), sem overflow horizontal ou erros JavaScript de execução. Também passaram abertura/fechamento do menu, Escape e retorno de foco, encaminhamento 192 do MAX, ausência de persistência da conversa no localStorage, sequência de agendamento, confirmação de leitura, preview/salvamento de marca, bloqueio de módulo, estado de erro com retry e estado vazio. As 24 barras do gráfico mantêm a mesma linha de base no celular e desktop. Capturas adicionais do login foram feitas nas quatro larguras.

A migração visual e os fluxos demonstrativos das fases 1 a 15 estão implementados e verificados com a adaptação Inertia aprovada. A integração de produção permanece condicionada às pendências explicitadas acima; não se deve interpretar os testes da demonstração como homologação do fornecedor ou validação clínica.
