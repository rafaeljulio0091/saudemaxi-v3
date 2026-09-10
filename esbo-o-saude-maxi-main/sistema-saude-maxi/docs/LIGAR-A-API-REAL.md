# Ligar a API real

Tudo hoje é simulado, dentro de `js/api.js`. Nenhuma tela precisa mudar para
ligar a API de verdade.

---

## Contexto que você precisa saber antes

A plataforma de telemedicina **já existe e já está em produção**, com outros
clientes, e é de outro fornecedor. Nós construímos uma camada por cima. Não
tocamos nela.

- URL base: `https://saudemaxi.com.br/api/clinic/`
- Autenticação: `Authorization: Bearer <token de serviço da clínica>`
- Pilha do lado deles: Django com Bootstrap 5
- **Não existe webhook.** A plataforma não avisa quando algo acontece
- **Não existe endpoint de prescrição.** A receita sai pela Mevo dentro do
  atendimento e não volta

Essas duas ausências mudam decisão de produto, não são detalhe técnico. Leia
a seção "O que não dá para fazer" no fim.

---

## O que falta para ligar

| O que | De quem |
| --- | --- |
| Token de serviço da clínica | Fornecedor da plataforma |
| Ambiente de homologação | Fornecedor da plataforma |

Sem os dois, não dá para ligar nada. Não é limitação do código.

---

## Como trocar uma função

Todas as funções de `js/api.js` hoje chamam `simular()`. A função de chamada
real, `requisicao()`, já está escrita no fim do arquivo, com tratamento de
401, 404 e erro genérico.

**Antes:**

```js
/* VERDE  GET /api/clinic/filter-patients/ */
export function filtrarPacientes(filtros = {}) {
  return simular(() => {
    /* ... monta o resultado a partir de dados.js ... */
  }, { podeFicarVazio: true });
}
```

**Depois:**

```js
export function filtrarPacientes(filtros = {}) {
  const busca = new URLSearchParams(filtros).toString();
  return requisicao('filter-patients/?' + busca, { token: TOKEN });
}
```

Só isso. A assinatura de entrada e o formato de saída de cada função foram
desenhados para bater com o contrato real, então nenhuma tela muda.

### Onde guardar o token

**Não no navegador.** O token é de serviço, vale para a clínica inteira. Se
ele estiver no JavaScript, qualquer pessoa que abrir o sistema tem acesso à
base completa de pacientes.

O caminho certo é um serviço seu no meio:

```
navegador  ->  seu serviço (guarda o token)  ->  API da plataforma
```

Nesse caso, mude `BASE_REAL` para a URL do seu serviço e mantenha o resto.

---

## Os 21 pontos de integração

A tela **Integração**, no perfil de gestor, mostra esta mesma tabela dentro do
sistema, sempre atualizada a partir da constante `CATALOGO` de `js/api.js`.

### Verde: já existe e está documentado. 13 pontos

| Função | Método e rota | Nota |
| --- | --- | --- |
| `loginPaciente` | `POST login-patient/` | Magic link por CPF. Validade não documentada |
| `abrirProntoAtendimento` | `POST create-emergency-consultation/` | Magic link sem expiração por tempo, com `external_url` de retorno |
| `listarEspecialidades` | `GET scheduling/specialties/` | Respeita modo CLINIC ou PLATFORM |
| `listarDias` | `POST scheduling/business-days/` | |
| `listarHorarios` | `POST scheduling/available-times/` | |
| `listarMedicos` | `POST scheduling/doctors/` | `is_real` separa médico real de genérico |
| `criarConsulta` | `POST scheduling/create-consultation/` | Aceita `is_paid` na criação |
| `marcarPagamento` | `POST scheduling/update-payment-status/` | Permite cobrar por fora e só marcar aqui |
| `historicoConsultas` | `GET consultation-history/` | Única forma de reconciliar, porque não existe webhook |
| `filtrarPacientes` | `GET filter-patients/` | Paginado, máximo 50 por página |
| `atualizarPaciente` | `PATCH update-patient/` | O identificador é o CPF |
| `criarPaciente` | `POST create-patient/` | Suporta paciente estrangeiro |
| `listarTags` | `GET patient-tags/` | |

### Amarelo: existe, mas depende de definição. 2 pontos

| Função | Trava |
| --- | --- |
| `alternarStatusPaciente` | `POST toggle-patient-status/` exige a chave "Permitir que a clínica inative pacientes", que fica em nível de administração do fornecedor |
| `orientar` | Depende do script de fundamentação clínica, que o cliente prometeu escrever |

### Azul: é da camada nova, não existe do lado do fornecedor. 3 pontos

| Função | Nota |
| --- | --- |
| `lerReceitaPorFoto` | Leitura de imagem com medida de confiança. Construção nossa |
| `listarFarmacias` | Cadastro entregue pelo município na adesão |
| `modulosDoPlano` | O plano do sistema atual tem quatro campos e nenhum módulo. O de para é nosso |

### Vermelho: bloqueado por decisão pendente. 3 pontos

| Função | Trava |
| --- | --- |
| `buscarReceitas` | **Não existe** endpoint de prescrição. Falta definir como a receita chega até aqui |
| `consultarCobertura` | Sem fonte oficial da lista de medicamentos custeados |
| `nr1` | Bloqueado pela decisão sobre relatório individual ao gestor. Envolve LGPD |

---

## O fluxo de agendamento, seis chamadas

A plataforma documenta exatamente nesta ordem. A tela `#/agendamento` segue
passo a passo:

```
1. GET  scheduling/specialties/       lista as especialidades
2. POST scheduling/business-days/     dias com agenda aberta na especialidade
3. POST scheduling/available-times/   horários livres no dia escolhido
4. POST scheduling/doctors/           profissionais naquele horário
5. POST scheduling/create-consultation/  cria, aceitando is_paid
6. POST scheduling/update-payment-status/  marca pago depois, se preciso
```

Duas regras de negócio em cima disso:

- **Município:** a marcação com especialista passa pelo **núcleo de
  regulação**. Não é o paciente que escolhe. O campo `regulacao` do cliente,
  em `dados.js`, liga esse comportamento
- **Privado:** exige pagamento, e **o recebimento é da Maximize**, não passa
  pelo gateway do fornecedor. Por isso `update-payment-status` importa tanto:
  cobra por fora e só marca `is_paid` lá

---

## O repasse de sessão: magic link

Quando o paciente pede atendimento agora, nós **não** construímos fila nem
sala de vídeo. Isso já existe na plataforma. O fluxo é:

```
1. POST create-emergency-consultation/ com { cpf, external_url }
2. a resposta traz magic_link e consultation_code
3. abrimos o magic_link, e a plataforma assume o atendimento
4. ao encerrar, a plataforma devolve o paciente para external_url,
   com consultation_code e status na query string
```

**Cuidado com a leitura do passo 4.** Isso é um redirecionamento de navegador,
**não é webhook**. Se a pessoa fechar a aba, o retorno não acontece e nós não
ficamos sabendo de nada.

A única forma confiável de saber o que aconteceu é consultar
`consultation-history/` depois. Planeje a reconciliação por consulta
periódica, não por evento.

---

## A máquina de estados da consulta

Real, lida da documentação do fornecedor. Não invente estados:

| Estado | Significa |
| --- | --- |
| `SCHEDULED` | Agendada |
| `PENDING` | Pendente |
| `WAITING_HELPDESK` | Aguardando triagem |
| `ONGOING_HELPDESK` | Em triagem |
| `WAITING_DOCTOR` | Aguardando médico |
| `ONGOING_DOCTOR` | Em atendimento |
| `FINISHED` | Finalizada |
| `CANCELED` | Cancelada |

Tipo de consulta: `POOL`, que é o pronto atendimento, ou `SCHEDULED`, que é a
agendada.

A tradução para português vive em `STATUS_CONSULTA`, em `js/dados.js`.

---

## Como ler as respostas de rota, ao explorar a API

Ao testar um endereço no ambiente do fornecedor:

| O que acontece | O que significa |
| --- | --- |
| `Not Found` | A rota não existe |
| `403` | Existe, e você está barrado |
| Redireciona em silêncio para o painel | Existe, mas não está habilitada para este perfil ou para esta clínica |

O terceiro caso engana. Não conclua que a funcionalidade não existe: pergunte
se está habilitada no contrato.

---

## O que não dá para fazer, e por quê

**Não dá para saber que uma receita foi emitida.** Não existe endpoint de
prescrição nem webhook. A receita sai pela integração com a Mevo, dentro do
atendimento, e vai direto para o paciente. Por isso a farmácia popular
funciona hoje com foto da receita.

Três saídas possíveis, e a escolha é do cliente, não sua: o fornecedor
desenvolve o endpoint, integramos direto com a Mevo, ou a foto continua sendo
o caminho.

**Não dá para saber em tempo real que um atendimento terminou.** Sem webhook,
só consultando o histórico.

**Não dá para construir a trilha de saúde mental.** Está bloqueada até a
definição sobre o que o gestor pode ver. Regra 6: o gestor vê o time, nunca a
pessoa.
