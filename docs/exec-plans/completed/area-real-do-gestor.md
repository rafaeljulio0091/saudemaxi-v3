# Exec Plan: Área real do gestor (painel, planos, identidade, integrações)

- **Status:** completed
- **Objetivo:** tornar oficiais `/gestor/painel`, `/gestor/planos`,
  `/gestor/identidade` e `/gestor/integracao`, com o layout e as
  funcionalidades das telas de demonstração, para o papel `manager`.
- **Contexto:** as páginas `Healthcare/Manager/*` existiam apenas na demo; as
  rotas reais eram 503 (planos, identidade, integração) ou o painel simples do
  `DashboardLayout`.
- **Escopo:** `HealthcareContext::forManager`, `ManagerAreaController`,
  `ManagerDataController`/`ManagerDataService`, `TenantPlanService`, tabela
  `plans`, coluna `tenants.greeting`, aplicação server-side dos módulos no
  lado do paciente e textos de demo condicionados a `context.demo`.
- **Fora de escopo:** integração LSX, atribuição de plano por paciente,
  indicadores de consultas/pagamentos, upload de logotipo, `/gestor/pacientes/{id}`.
- **Risco:** HIGH (autorização, tenant, configuração que afeta pacientes).
- **Dados sensíveis envolvidos:** contagem e faixa etária agregada de pacientes
  do tenant (derivada de `birthdate`); nenhum dado individual exposto.
- **Impacto LGPD:** somente agregados; nenhum dado novo coletado.
- **Impacto multi-tenant:** tudo derivado do tenant do gestor autenticado.
- **Integrações afetadas:** nenhuma.

## Decisões

- `plans` é local porque o próprio catálogo de integrações classifica
  "Módulos do plano" como "Camada Saúde Maxi".
- Plano padrão criado na primeira listagem pelo gestor; sem plano gravado, o
  paciente mantém o comportamento anterior (todos os módulos).
- Painel real não exibe números fictícios: consultas, agendamentos, pagamentos
  e indicadores ficam como "—" ou ocultos; faixas etárias vêm do banco local.
- Card do MAX só na demo (o assistente não está configurado fora dela).
- Gestor sem tenant mantém o painel explicativo anterior.

## Testes

- `tests/Feature/HealthcareManagerAreaTest.php` e `DashboardTest` atualizados:
  acesso por papel/tenant, isolamento de dashboard/planos/identidade,
  bloqueio de `nr1`, validações, efeito dos módulos no paciente, padrão
  preservado.

## Security Review

- Autorização server-side por papel e tenant; IDs de plano verificados dentro
  do tenant; `tenant_id` do browser ignorado; rate limit nas rotas novas.
- Mass assignment explícito (`name`, `brand_color`, `greeting`; campos de plano).

## Pendências

- Atribuição de plano por paciente e mapeamento com `plan_id` da LSX.
- Catálogo de integrações (`integrationCatalog.js`) marca histórico, listagem e
  criação de pacientes como "não conectado", embora já existam clientes reais.
- Corrida na criação do plano padrão (duas requisições simultâneas) pode gerar
  duplicata; a leitura usa o mais antigo.
