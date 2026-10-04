# Papel

Atue como um arquiteto de software sênior especialista em:

- Laravel 12
- PHP 8+
- Vue 3
- Inertia.js
- Tailwind CSS
- APIs REST
- MySQL/MariaDB
- Clean Architecture
- SOLID
- SaaS multi-tenant
- Segurança da informação
- LGPD
- Sistemas de saúde e telemedicina

Antes de alterar qualquer arquivo, leia integralmente o arquivo `AGENTS.md` e analise a arquitetura já existente do projeto `saudemaxi-v3`.

Não crie estruturas duplicadas se já houver Models, migrations, services, repositories, policies, enums ou tabelas equivalentes.

---

# Contexto

Atualmente o SaúdeMaxi possui integração com a plataforma LSX Medical.

Entretanto, o sistema não deve depender exclusivamente da API da LSX para manter os dados operacionais.

Precisamos criar uma estrutura própria e nativa no SaúdeMaxi para armazenar e administrar informações como:

- pacientes;
- usuários;
- profissionais de saúde;
- médicos;
- clínicas;
- unidades de saúde;
- farmácias;
- endereços;
- contatos;
- vínculos entre usuários e organizações;
- informações básicas necessárias para operação da plataforma.

A API da LSX deve permanecer como uma integração externa complementar.

A base de dados do SaúdeMaxi deverá ser considerada a fonte principal das informações internas da plataforma.

---

# Objetivo principal

Analisar a estrutura atual do projeto e implementar uma arquitetura de persistência nativa para pacientes, usuários, profissionais, clínicas, unidades de saúde e farmácias.

A implementação deve:

1. criar ou adaptar migrations;
2. criar Models e relacionamentos necessários;
3. implementar regras de negócio;
4. criar Services/Actions quando necessário;
5. implementar validações;
6. implementar Policies e autorização;
7. preparar a estrutura para multi-tenancy;
8. permitir integração futura ou sincronização com a LSX;
9. manter independência funcional da API externa;
10. priorizar segurança, integridade e privacidade dos dados.

---

# Regra arquitetural fundamental

A arquitetura deverá seguir:

```text
SaúdeMaxi Database
        │
        │ Fonte principal
        ▼
Application / Domain
        │
        ├───────────────► LSX API
        │                 integração externa
        │
        └───────────────► outras integrações futuras
```

Não implementar:

```text
SaúdeMaxi
   ↓
LSX
   ↓
dados necessários para funcionamento
```

Ou seja:

O SaúdeMaxi precisa continuar funcional mesmo quando:

- a API LSX estiver indisponível;
- houver timeout;
- houver erro HTTP;
- houver alteração na API externa;
- o token expirar;
- a integração estiver temporariamente desativada.

---

# Etapa 1 — Auditoria da estrutura existente

Antes de criar migrations, pesquise no projeto por estruturas relacionadas a:

```text
users
patients
doctors
professionals
clinics
health_units
pharmacies
addresses
contacts
tenants
municipalities
organizations
roles
permissions
```

Também procure:

```text
Patient
User
Doctor
Professional
Clinic
HealthUnit
Pharmacy
Address
Tenant
Municipality
```

Identifique:

- tabelas existentes;
- migrations;
- Models;
- relações Eloquent;
- controllers;
- repositories;
- services;
- DTOs;
- enums;
- requests;
- policies;
- gates;
- middleware;
- traits.

Nunca recrie uma estrutura equivalente sem justificar tecnicamente.

---

# Etapa 2 — Estrutura multi-tenant

O SaúdeMaxi será utilizado por diferentes municípios e organizações.

Garanta isolamento lógico entre tenants.

Quando aplicável, utilizar:

```text
tenant_id
municipality_id
health_unit_id
```

Nunca confiar em um `tenant_id` recebido diretamente pelo frontend.

O tenant deve ser determinado preferencialmente por:

```text
usuário autenticado
→ tenant
→ organização/unidade autorizada
```

Uma consulta nunca deve permitir:

```text
Tenant A
```

acessar dados de:

```text
Tenant B
```

mesmo que um ID válido seja manualmente informado na requisição.

---

# Etapa 3 — Pacientes

Criar ou adequar uma estrutura `patients`.

Avalie campos como:

```text
id
uuid
tenant_id
municipality_id

user_id nullable

name
social_name nullable
birth_date
sex nullable

cpf
cns nullable

email nullable
phone nullable

status

created_at
updated_at
deleted_at
```

Não utilize CPF como chave primária.

Utilize identificador interno e preferencialmente UUID público.

### Regras

- CPF deve ser validado;
- CNS, caso utilizado, deve possuir validação adequada;
- evitar registros duplicados;
- dados sensíveis não devem aparecer em logs;
- pacientes podem existir sem uma conta de acesso;
- posteriormente um paciente poderá ser associado a um `user`;
- implementar SoftDeletes quando compatível com as regras existentes.

Avaliar cuidadosamente se CPF deverá ser:

```text
unique
```

globalmente ou dentro de:

```text
tenant_id + cpf
```

Documentar a decisão.

---

# Etapa 4 — Usuários

A tabela `users` já existente deve continuar responsável por autenticação.

Não criar uma segunda estrutura de autenticação.

Avaliar extensão segura da tabela para suportar:

```text
uuid
tenant_id
name
email
password
status
last_login_at
email_verified_at
```

Não armazenar:

```text
senha em texto puro
```

Utilizar exclusivamente:

```text
Hash::make()
```

ou mecanismo padrão do Laravel.

---

# Etapa 5 — Profissionais de saúde

Criar estrutura específica para profissionais, evitando colocar atributos clínicos diretamente em `users`.

Exemplo:

```text
health_professionals

id
uuid
tenant_id
user_id

professional_type
registration_number
registration_state
registration_authority

specialty_id nullable

status

created_at
updated_at
deleted_at
```

Exemplos:

```text
CRM
COREN
CRP
CREFITO
CRO
```

Modelar de forma que novas categorias profissionais possam ser incorporadas sem alterar a arquitetura principal.

---

# Etapa 6 — Clínicas e unidades de saúde

Avaliar separação conceitual entre:

```text
organizations
```

e:

```text
health_units
```

Uma organização poderá possuir várias unidades.

Sugestão:

```text
organizations

id
uuid
tenant_id
type
legal_name
trade_name
cnpj
status
```

Tipos possíveis:

```text
clinic
hospital
municipal_secretariat
laboratory
pharmacy
other
```

E:

```text
health_units

id
uuid
tenant_id
organization_id

municipality_id

name
code nullable
type

phone nullable
email nullable

status

created_at
updated_at
deleted_at
```

Não force essa arquitetura se o projeto já possuir estrutura equivalente.

---

# Etapa 7 — Farmácias

Criar estrutura própria para farmácias.

Exemplo:

```text
pharmacies

id
uuid
tenant_id nullable
municipality_id

name
corporate_name nullable
cnpj nullable

phone nullable
email nullable

is_public
is_active

created_at
updated_at
deleted_at
```

Preparar a estrutura para suportar futuramente dados provenientes de:

- cadastros manuais;
- importações;
- integrações governamentais;
- APIs externas;
- programas como Farmácia Popular.

Adicionar campos de origem quando necessário:

```text
data_source
external_id
external_provider
last_synced_at
```

---

# Etapa 8 — Endereços

Evitar duplicação desnecessária de campos de endereço.

Avaliar implementação polimórfica:

```text
addresses

id
addressable_type
addressable_id

zip_code
street
number
complement
district
city
state
country

latitude nullable
longitude nullable
```

Relacionamento:

```php
morphTo()
```

Permitindo utilizar endereço para:

```text
pacientes
clínicas
farmácias
unidades
organizações
```

Caso o projeto já possua outra solução consistente, mantê-la.

---

# Etapa 9 — Vínculos

Preparar relações entre:

```text
Professional
        ↓
Health Unit

Patient
        ↓
Health Unit

User
        ↓
Tenant

Health Unit
        ↓
Organization
```

Para relacionamentos N:N, utilizar pivot tables adequadas.

Exemplo:

```text
health_professional_health_unit

health_professional_id
health_unit_id
status
started_at
ended_at
```

Não utilizar campos JSON para relacionamentos que necessitam:

- consultas;
- filtros;
- integridade referencial;
- auditoria.

---

# Etapa 10 — Integração LSX

A integração LSX deverá ser desacoplada da camada de domínio.

Criar ou adequar uma camada semelhante a:

```text
Infrastructure/
    Integrations/
        Lsx/
            LsxClient
            LsxPatientMapper
            LsxUserMapper
            LsxSyncService
```

Não permitir chamadas diretas à LSX dentro de:

```text
Models
Controllers
Views
```

Utilizar interfaces quando fizer sentido.

Exemplo:

```php
interface ExternalPatientProvider
{
    public function find(...);
}
```

Implementação:

```text
LsxPatientProvider
```

---

# Identificação externa

Não utilizar o ID da LSX como ID principal do SaúdeMaxi.

Utilizar campos semelhantes a:

```text
external_provider
external_id
```

Exemplo:

```text
provider = lsx
external_id = 837239
```

O registro continuará tendo:

```text
patients.id
```

e:

```text
patients.uuid
```

próprios.

---

# Sincronização

Quando houver sincronização:

```text
LSX
 ↓
mapper
 ↓
DTO
 ↓
validation
 ↓
application service
 ↓
database
```

Nunca:

```text
LSX response
 ↓
Model::create($response)
```

Não realizar mass assignment indiscriminado.

---

# Segurança da informação

Essa etapa é obrigatória.

Implementar ou revisar:

## Autenticação

Utilizar mecanismos nativos do Laravel.

Aplicar:

```text
Auth
Sessions/Tokens
CSRF
rate limiting
```

conforme o tipo de endpoint.

---

# Autorização

Criar Policies e/ou Gates.

Exemplo:

```text
PatientPolicy
PharmacyPolicy
ClinicPolicy
HealthUnitPolicy
HealthProfessionalPolicy
```

Validar pelo menos:

```text
view
create
update
delete
restore
```

Nunca confiar apenas em esconder botões no frontend.

---

# IDOR / Broken Object Level Authorization

Impedir vulnerabilidades como:

```text
GET /patients/123
```

onde o usuário altera para:

```text
GET /patients/124
```

e acessa outro paciente.

Toda operação deve verificar:

```text
tenant
role
permission
resource ownership/scope
```

antes de retornar dados.

---

# Dados sensíveis

Considere como sensíveis:

```text
CPF
CNS
dados clínicos
telefones
endereços
documentos
prontuários
resultados
dados pessoais
```

Nunca registrar esses dados integralmente em:

```text
laravel.log
exceptions
debug
APM
monitoramento
```

Criar sanitização quando necessário.

---

# Criptografia

Avaliar utilização de encrypted casts do Laravel para campos que realmente necessitem criptografia em nível de aplicação.

Exemplo:

```php
protected function casts(): array
{
    return [
        'sensitive_field' => 'encrypted',
    ];
}
```

Não aplicar criptografia indiscriminadamente em campos utilizados para:

```text
busca
index
unique
joins
```

sem analisar os impactos.

---

# Logs de auditoria

Criar ou utilizar infraestrutura existente para registrar operações sensíveis.

Exemplo:

```text
audit_logs

id
user_id
tenant_id

action
resource_type
resource_id

ip_address
user_agent

created_at
```

Registrar eventos como:

```text
patient.created
patient.updated
patient.viewed_sensitive_data
patient.deleted

professional.updated

clinic.created

pharmacy.updated
```

Não salvar payload completo contendo informações sensíveis.

---

# LGPD

Adotar princípios de:

```text
minimização
finalidade
necessidade
controle de acesso
rastreabilidade
retenção
segurança
```

Não cadastrar informações sem necessidade operacional.

Preparar a arquitetura para:

```text
anonimização
exportação de dados
retificação
bloqueio
exclusão quando juridicamente aplicável
```

Não implementar exclusão física de informações médicas sem avaliar obrigações legais de retenção.

---

# Banco de dados

Utilizar:

```text
foreign keys
unique indexes
indexes compostos
not null
enums controlados
constraints
```

quando apropriado.

Avaliar índices principalmente para:

```text
tenant_id
municipality_id
user_id
patient_id
cpf
cns
cnpj
status
external_id
external_provider
```

Evitar indexes redundantes.

---

# UUID

Quando possível, utilizar:

```text
BIGINT
```

internamente e:

```text
UUID
```

para exposição externa.

Exemplo:

```text
GET /patients/{uuid}
```

em vez de expor IDs sequenciais.

---

# Validação

Utilizar:

```text
Form Requests
```

Exemplos:

```text
StorePatientRequest
UpdatePatientRequest

StorePharmacyRequest
UpdatePharmacyRequest

StoreHealthUnitRequest
```

Nunca realizar validações complexas diretamente no Controller.

---

# Mass Assignment

Definir corretamente:

```text
$fillable
```

ou:

```text
$guarded
```

Não utilizar:

```php
Model::create($request->all());
```

Preferir:

```php
$data = $request->validated();
```

seguido de DTO/Action/Service quando necessário.

---

# Controllers

Controllers devem ser finos.

Evitar:

```text
Controller
→ validação
→ regra de negócio
→ integração LSX
→ persistência
→ auditoria
→ transformação
```

Preferir:

```text
Controller
      ↓
FormRequest
      ↓
Application Service / Action
      ↓
Domain
      ↓
Repositories / Integrations
```

---

# Transações

Utilizar:

```php
DB::transaction()
```

em operações que modificam várias tabelas relacionadas.

Exemplo:

```text
criar usuário
+
criar paciente
+
criar endereço
+
criar vínculo
```

Todas devem ser concluídas ou revertidas juntas.

---

# Concorrência

Considere cenários de criação simultânea.

Exemplo:

Duas requisições tentando cadastrar o mesmo CPF.

Não confiar exclusivamente em:

```php
if (!Patient::where(...)->exists())
```

Utilizar também constraints no banco.

---

# API

Caso sejam criados endpoints, padronizar respostas.

Exemplo:

```json
{
    "data": {},
    "meta": {}
}
```

Erros:

```json
{
    "message": "Dados inválidos.",
    "errors": {}
}
```

Nunca retornar:

```text
stack trace
SQL
tokens
credentials
.env
```

---

# Rate limiting

Aplicar rate limiting especialmente em:

```text
login
cadastro
busca de CPF
busca de CNS
recuperação de senha
consulta externa
sincronização LSX
```

---

# Busca de pacientes

Não criar endpoints que permitam enumeração irrestrita.

Evitar permitir:

```text
GET /patients?cpf=...
```

sem:

```text
autorização
rate limiting
auditoria
escopo de tenant
```

---

# Soft Delete

Avaliar utilização de:

```php
SoftDeletes
```

para:

```text
patients
health_professionals
clinics
health_units
pharmacies
```

Não confundir SoftDelete com anonimização LGPD.

---

# Status

Utilizar Enums ou estrutura equivalente.

Exemplo:

```php
enum RecordStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';
}
```

Evitar strings espalhadas pela aplicação.

---

# Testes obrigatórios

Criar testes para:

### Tenant isolation

```text
Tenant A não consegue visualizar paciente de Tenant B.
```

### Autorização

```text
usuário sem permissão recebe HTTP 403.
```

### Validação

```text
CPF inválido → rejeitado.
```

### Duplicidade

```text
paciente duplicado → bloqueado conforme regra definida.
```

### LSX indisponível

Simular:

```text
timeout
HTTP 500
HTTP 401
```

e validar que o cadastro local continua funcionando.

### Transação

Simular erro durante cadastro composto e garantir rollback.

### Mass assignment

Garantir que campos como:

```text
tenant_id
role
is_admin
```

não possam ser alterados pelo payload do usuário.

---

# Compatibilidade

A implementação não pode quebrar:

```text
login
cadastro
recuperação de senha
integração LSX existente
Vue 3
Inertia
rotas existentes
APIs existentes
```

Sempre priorize compatibilidade retroativa.

---

# Migrations

Antes de executar qualquer migration destrutiva:

1. analisar dados existentes;
2. identificar possibilidade de perda;
3. evitar drop de coluna/tabela sem necessidade;
4. preferir migrations incrementais;
5. garantir rollback;
6. documentar alterações.

Não modificar migrations antigas que já possam ter sido executadas em produção.

Criar novas migrations.

---

# Resultado esperado

Ao final, o SaúdeMaxi deverá conseguir cadastrar nativamente:

```text
Usuários
Pacientes
Profissionais
Clínicas
Unidades
Farmácias
Endereços
Vínculos
```

sem depender da LSX.

A LSX deverá permanecer como:

```text
External Integration Provider
```

e nunca como banco principal da plataforma.

---

# Documentação

Criar documentação técnica contendo:

## Modelo de dados

Relacionamentos entre:

```text
Tenant
Municipality
User
Patient
HealthProfessional
Organization
HealthUnit
Pharmacy
Address
```

## Fluxo

Documentar:

```text
Cadastro local

Sincronização LSX

Consulta

Atualização

Auditoria
```

---

# Entrega

Ao final da implementação, informe obrigatoriamente:

1. diagnóstico da estrutura anterior;
2. tabelas existentes reutilizadas;
3. migrations criadas;
4. tabelas criadas;
5. Models criados ou alterados;
6. relacionamentos implementados;
7. Services/Actions criados;
8. Policies implementadas;
9. endpoints criados ou alterados;
10. testes executados;
11. resultado dos testes;
12. riscos encontrados;
13. impactos na integração LSX;
14. decisões de arquitetura;
15. pendências;
16. possíveis melhorias futuras.

---

# Restrições finais

Não invente tabelas quando houver estrutura equivalente.

Não remova funcionalidades existentes.

Não altere contratos existentes sem necessidade.

Não armazene credenciais em código.

Não exponha informações pessoais em logs.

Não confiar no frontend para autorização.

Não utilizar IDs externos como identificadores principais.

Não acoplar o domínio à LSX.

Não executar alterações destrutivas sem justificar.

Priorize nesta ordem:

```text
1. Segurança dos dados
2. Isolamento multi-tenant
3. Integridade dos dados
4. LGPD
5. Compatibilidade
6. Estabilidade
7. Manutenibilidade
8. Performance
9. Evolução futura
```