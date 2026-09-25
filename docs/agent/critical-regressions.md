# Critical regressions

Estes fluxos não podem quebrar sem uma revisão explícita.

## Autenticação

- login local e login LSX quando habilitado;
- credencial inválida e indisponibilidade do provedor;
- regeneração/invalidação de sessão, logout, CSRF;
- cadastro, verificação, confirmação e recuperação de senha.

## Pacientes e tenant

- associação usuário/tenant e contexto de dashboard;
- criação e consulta de pacientes pela camada LSX;
- bloqueio de paciente em rotas de gestor;
- isolamento de sessão de triagem entre tenants e pacientes.

## Telemedicina

- autenticação server-side LSX;
- busca/criação de paciente;
- histórico de consultas por CPF;
- mapeamento de 401/403/404/422/409/5xx e indisponibilidade.

Não listar como existente o que o código marca como integração pendente:
especialidades, disponibilidade, médicos, agendamento, vídeo e MAX remoto.

## Frontend

- páginas Inertia de login, dashboard, paciente e gestor;
- cliente Axios e tratamento de 401/419/422/429/5xx;
- build Vite, navegação Inertia, responsividade e estado de contexto.

Qualquer fluxo que envolva saúde, CPF, credenciais, tenant ou LSX é HIGH.
