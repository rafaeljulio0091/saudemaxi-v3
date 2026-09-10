# Saúde Maxi

Camada nova sobre uma plataforma de telemedicina que **já está em produção**,
é de outro fornecedor e é vendida em white label para municípios, clínicas e
empresas.

Três funcionalidades novas: assistente de triagem, farmácia popular e risco
psicossocial NR-1.

---

## As duas pastas

| Pasta | O que é | No ar |
| --- | --- | --- |
| [`sistema-saude-maxi/`](sistema-saude-maxi/) | **A base de produto.** É aqui que se programa | `https://saude-maxi-app.vercel.app` |
| [`esboco-saude-maxi/`](esboco-saude-maxi/) | Protótipo de venda, 37 telas num arquivo só. Ferramenta de alinhamento com o cliente | `https://esboco-saude-maxi.vercel.app` |

As duas convivem de propósito. O protótipo mostra o produto inteiro em cinco
minutos para quem vai comprar. O sistema é onde o produto é construído, com
arquitetura própria, camada de serviço espelhando a API real e teste
automatizado.

**Programador: comece por
[`sistema-saude-maxi/README.md`](sistema-saude-maxi/README.md).**

---

## Começar em três comandos

```
cd sistema-saude-maxi
python -m http.server 8000     # abra http://localhost:8000
node teste/fumaca.mjs          # 134 verificações
```

Sem `npm install`. Sem passo de compilação. Sem dependência externa.

---

## As dez regras invioláveis

Não são preferência de estilo. Vieram do contrato e das reuniões com o
cliente.

1. **Não alterar a plataforma existente.** A camada nova só a consome.
2. Na dúvida entre implementar e já existir, a resposta padrão é: **já
   existe**.
3. O produto **orienta e encaminha, nunca conclui sobre doença.** As palavras
   "diagnóstico" e "pré-diagnóstico" são proibidas na interface.
4. **Nunca sugerir troca de medicamento.** Substituição é ato médico.
5. Registro auditável de toda decisão do assistente.
6. **NR-1: o gestor vê o time, nunca a pessoa.**
7. Isolamento por cliente em camada única, com o cliente vindo do contexto
   autenticado.
8. **Sem travessão** em texto, código ou comentário.
9. Nenhum vocabulário de disputa eleitoral. A palavra "campanha" é vetada.
10. Português do Brasil, fonte e botão grandes, por causa da estação física.

Cada pasta traz o detalhe no seu próprio README.

---

## O que está travado

Três decisões do cliente seguram trilhas inteiras. Não tente construir o que
está abaixo.

| O que trava | Consequência |
| --- | --- |
| **NR-1:** relatório individual para o RH, ou indicador agregado do time? O cliente disse as duas coisas na mesma reunião | A trilha de saúde mental inteira. Envolve LGPD |
| **Receita:** a plataforma não tem endpoint de prescrição nem webhook. Como a receita chega até nós? | A farmácia popular, que é a funcionalidade mais pedida |
| **Agendamento:** volta ao escopo? A API entrega tudo, e é o único jeito de aplicar a regra de município e a cobrança | A trilha de agendamento |

Também falta, do fornecedor: **token de serviço** e **ambiente de
homologação**. Sem os dois, nenhuma integração real acontece.

---

## Sobre os dados

Tudo nas duas pastas é inventado. Nenhum dado real de pessoa. Os documentos
usados na demonstração são sequências inválidas por construção e nunca
coincidem com documento real.

Nenhuma das duas faz chamada de rede hoje. Nada sai do navegador de quem abre.
