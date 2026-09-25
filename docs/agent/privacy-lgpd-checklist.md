# Checklist de privacidade e LGPD

Use para toda alteração que toque dados pessoais, saúde, CPF, credenciais ou
integrações.

- [ ] Minimização: cada campo coletado é necessário para a finalidade?
- [ ] Finalidade: a finalidade está documentada e limitada ao fluxo?
- [ ] Exposição: o dado pode aparecer em URL, query string, props, browser
      storage, analytics, exceptions ou cache?
- [ ] Retenção: a persistência e o prazo são necessários? Existe exclusão ou
      anonimização compatível com a finalidade?
- [ ] Tenant isolation: outro cliente, usuário ou gestor pode acessar o dado?
- [ ] Segurança: trânsito HTTPS, segredo server-side, autorização e proteção
      de armazenamento foram avaliados?
- [ ] Logs: CPF, prontuário, prescrição, exame, senha, token e payload clínico
      foram removidos ou redigidos?
- [ ] Integração: o provedor realmente exige o campo e o contrato permite o
      envio? Não presumir endpoints ou finalidade.
- [ ] Frontend: não há dado sensível em localStorage, sessionStorage, URL ou
      estado global sem justificativa revisada.
- [ ] Exclusão/anonimização: a mudança afeta estratégia de exclusão ou cópias?
- [ ] Auditoria: ações privilegiadas e decisões automatizadas têm rastreabilidade
      sem registrar conteúdo clínico desnecessário?

Se qualquer resposta não puder ser comprovada, marcar `NEEDS_VERIFICATION` e
não inventar uma regra de retenção ou base legal.
