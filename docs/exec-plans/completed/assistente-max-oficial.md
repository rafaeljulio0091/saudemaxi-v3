# Exec Plan: Assistente MAX oficial

- **Status:** completed
- **Objetivo:** botão flutuante do MAX em todo o ambiente autenticado real,
  abrindo o modal de conversa idêntico ao da demonstração, com regras de
  negócio pela estrutura de IA existente (Jev/OpenAI).
- **Contexto:** o MAX só existia na demo (`DemoMaxService`, regras locais); a
  operação real `max` respondia 503.
- **Escopo:** `app/Max` (regras, catálogo de ações, serviço), contratos/DTOs/
  providers de assistente em `app/AI`, prompt, rotas `POST /triagem/max` e
  `POST /gestor/dados/max`, limiter `max-messages`, componente `MaxAssistant`
  nos layouts `HealthcareLayout` e `DashboardLayout`.
- **Fora de escopo:** LSX, persistência/histórico de conversa, MAX da demo.
- **Risco:** HIGH (texto livre possivelmente com dados de saúde enviado a IA).
- **Dados sensíveis envolvidos:** mensagem digitada pelo usuário.
- **Impacto LGPD:** minimização (CPF/e-mail/telefone redigidos; sem nome, CPF,
  tenant ou IDs; página só de lista conhecida); nada persistido; logs sem
  conteúdo; aviso explícito no modal. Base legal/DPA com provedores de IA:
  `NEEDS_VERIFICATION`.
- **Impacto multi-tenant:** exige papel e tenant; links filtrados pelos
  módulos do plano do tenant.
- **Integrações afetadas:** OpenAI e Jev (novos providers; os da triagem
  permanecem inalterados).

## Decisões

- Emergência, privacidade/NR-1, medicação e sintomas têm respostas fixas
  (as mesmas da demo) e nunca chamam IA; o Jev pode escalar para elas.
- OpenAI só redige respostas de navegação, com saída estruturada e ações
  restritas por enum + validação no servidor; `PatientMessageGuard` descarta
  respostas inseguras.
- Com `MAX_ASSISTANT_AI_ENABLED=false` (padrão) o MAX funciona só com regras.
- Providers de triagem não foram refatorados para evitar regressão; a
  duplicação de código HTTP fica como débito técnico.

## Testes

- `tests/Feature/MaxAssistantTest.php`: autenticação/papel/tenant, validação,
  regras sem IA, curto-circuito sem IA para emergência/privacidade/medicação,
  Jev → OpenAI com dados minimizados, escalonamento do Jev, catálogo e
  módulos, fallback por ação inválida, guard, falha de provedor sem conteúdo
  em log, rate limit e demo inalterada.

## Pendências

- Aprovação clínica das regras de emergência (`config/triage.php` segue vazio;
  o MAX também usa a lista de termos da demo).
- `/gestor/pacientes` responde 500 quando a conexão com a LSX falha com
  `RequestException` (ex.: proxy 403 no túnel); não tratado por não alterar LSX.
