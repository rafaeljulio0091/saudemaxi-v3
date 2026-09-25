# Risk gate

Classifique a mudança antes de editar.

## LOW

Textos, CSS ou ajuste visual isolado.

```text
INSPECT -> IMPLEMENT -> VALIDATE -> DIFF REVIEW
```

## MEDIUM

Controller, Form Request, Service, Policy, query ou componente com lógica.

```text
INSPECT -> PLAN -> IMPLEMENT -> TEST -> DIFF REVIEW
```

## HIGH

Autenticação, autorização, pacientes, dados de saúde, LGPD, tenant, LSX,
credenciais, migrations ou qualquer integração externa.

```text
INSPECT -> PLAN -> SECURITY REVIEW -> PRIVACY / LGPD REVIEW -> IMPLEMENT
       -> TEST -> SECURITY REVIEW -> REGRESSION REVIEW -> DIFF REVIEW -> REPORT
```

Tarefas HIGH devem usar um Exec Plan baseado em
`docs/exec-plans/template.md`.
