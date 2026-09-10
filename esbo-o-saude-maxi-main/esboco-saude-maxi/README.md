# Saúde Maxi: protótipo de demonstração, 37 telas

Publicado em `https://esboco-saude-maxi.vercel.app`

Este é o protótipo validado com o cliente. Ele mostra as 37 telas da camada
nova do Saúde Maxi em um arquivo só, sem etapa de compilação, sem dependência
externa e sem nenhuma chamada de rede.

**Ele é ferramenta de venda e de alinhamento, não é a base de produto.** Serve
para o cliente ver o produto inteiro em cinco minutos e apontar o que muda.

---

## Como abrir

Abra `index.html` no navegador. Só isso. Não tem `npm install`, não tem
servidor, não tem build.

Se preferir servir por HTTP:

```
python -m http.server 8000
```

E abra `http://localhost:8000`.

---

## Estrutura da pasta

```
index.html                     o que está publicado. GERADO, não editar à mão
vercel.json                    cabeçalhos que bloqueiam indexação
robots.txt                     idem
fonte/
  index.html                   A FONTE DA VERDADE. É aqui que se edita
  gerar-versoes.py             gera o publicado a partir da fonte e verifica
```

### A regra mais importante deste repositório

`index.html` na raiz da pasta é **gerado**. Toda alteração acontece em
`fonte/index.html`. Depois:

```
python fonte/gerar-versoes.py
```

O script regenera o publicado e roda a verificação. Se a verificação falhar,
nada é publicado. Editar o `index.html` da raiz direto funciona até a próxima
geração, quando a alteração é apagada sem aviso.

---

## Publicar

```
vercel deploy --prod --yes
```

A partir desta pasta. O projeto na Vercel já existe e já está apontado para
`esboco-saude-maxi.vercel.app`.

---

## O que o script verifica

Roda em toda geração e trava a publicação se qualquer item falhar:

| Verificação | Por quê |
| --- | --- |
| 37 telas presentes, todos os ids esperados | Nenhuma tela sumiu numa edição |
| Nenhuma ligação quebrada entre telas | Todo `data-ir-para` aponta para um id que existe |
| Zero ocorrência de `diagn` | O produto orienta e encaminha, nunca conclui sobre doença |
| Zero ocorrência de `campanha` | Vocabulário de disputa eleitoral é vetado no contrato com município |
| Zero travessão | Regra de escrita do projeto |
| Zero referência externa | O arquivo tem que funcionar offline e sem CDN |
| Logo oficial presente | A marca vai embutida como data URI |

Hoje: **37 telas, 119 ligações, tudo passando.**

---

## Mapa das 37 telas

O identificador de cada tela é o `id` da `<section class="tela">` no HTML.
Buscar por `id="C4"` leva direto ao ponto.

### A. Entrada e cadastro

| Id | Tela |
| --- | --- |
| A1 | Acesse sua área do paciente |
| A2 | Recuperar senha |
| A3 | Complete seu cadastro |
| A4 | Saúde Maxi do seu município |

### B. Início

| Id | Tela |
| --- | --- |
| B1 | Início do paciente, vitrine de serviços |
| B2 | Início, variação por plano contratado |

### C. Farmácia popular

| Id | Tela |
| --- | --- |
| C1 | Minhas receitas |
| C2 | Enviar foto da receita |
| C3 | Lendo sua receita |
| C4 | Resultado da sua receita, três blocos de cobertura |
| C5 | Farmácias próximas de você |
| C6 | Reserva de retirada |

### D. Orientação em saúde

| Id | Tela |
| --- | --- |
| D1 | Orientação em saúde |
| D2 | Assistente de orientação, a conversa |
| D3 | Cuidados que você pode fazer em casa |
| D4 | Procure a UPA hoje |
| D5 | Como você prefere seguir |
| D6 | Registro das conversas do assistente |

### E. Falar com um médico

| Id | Tela |
| --- | --- |
| E1 | Falar com um médico agora |
| E2 | Você está na fila |
| E3 | Atendimento em andamento |

### F. Agendamento

| Id | Tela |
| --- | --- |
| F1 | Agendar uma consulta |
| F2 | Escolha de especialidade e horário |
| F3 | Consulta agendada |
| F4 | Minhas consultas |

### G. Saúde mental e NR-1

| Id | Tela |
| --- | --- |
| G1 | Antes de começar, consentimento |
| G2 | Assistente de saúde mental |
| G3 | Seu check-in da quinzena |
| G4 | Recursos de apoio |
| G5 | Falar com uma psicóloga |

**Atenção:** esta trilha está desenhada, mas **bloqueada** no produto. Falta a
decisão sobre o que o gestor pode ver: relatório individual ou indicador
agregado do time. Enquanto não houver essa definição, nada individual é
construído. Envolve LGPD.

### H. Conta

| Id | Tela |
| --- | --- |
| H1 | Perfil |
| H2 | Idioma |

### I. Estação física

| Id | Tela |
| --- | --- |
| I1 | O que você precisa hoje, escala ampliada para totem |

### J. Gestão e demonstração

| Id | Tela |
| --- | --- |
| J1 | Ambiente de demonstração |
| J2 | Visão de empresa contratante |
| J3 | Clientes e contratos |

### X. Ajuda imediata

| Id | Tela |
| --- | --- |
| X1 | O que você está sentindo agora |

---

## Regras que o código precisa respeitar

Valem para qualquer alteração aqui e para o produto inteiro.

1. **Não alterar a plataforma de telemedicina existente.** A camada nova só a
   consome. Ela já está em produção com outros clientes.
2. Na dúvida entre implementar e já existir, a resposta padrão é: **já
   existe**.
3. O produto **orienta e encaminha, nunca conclui sobre doença**. As palavras
   "diagnóstico" e "pré-diagnóstico" são proibidas na interface.
4. **Nunca sugerir troca de medicamento.** Substituição é ato médico.
5. Registro auditável de toda decisão do assistente.
6. **NR-1: o gestor vê o time, nunca a pessoa.** Nada de alerta individual.
7. Isolamento por cliente em camada única, com o cliente vindo do contexto
   autenticado.
8. **Sem travessão** em texto, código ou comentário.
9. Nenhum vocabulário de disputa eleitoral. A palavra "campanha" é vetada.
10. Português do Brasil, fonte e botão grandes, por causa da estação física.

---

## Sobre os dados

Tudo aqui é inventado. Nenhum dado real de paciente. Os documentos usados na
demonstração são sequências inválidas por construção e nunca coincidem com
documento real.

O arquivo não faz nenhuma chamada de rede. Nada sai do navegador de quem abre.

---

## Onde está a base de produto

Este protótipo demonstra. Quem vira produto é o sistema funcional, que tem
arquitetura própria, camada de serviço espelhando a API real, dois perfis e
teste automatizado:

`https://saude-maxi-app.vercel.app`

Os dois convivem e cada um tem seu propósito. Este aqui mostra as 37 telas ao
comprador. O outro é onde o produto é construído.
