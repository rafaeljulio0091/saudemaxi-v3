# Telemedicina / LSX Medical

## Implementação atual

- A configuração está em `config/lsxmedical.php`; a URL, endpoints, timeout e
  token vêm de variáveis de ambiente. O token é `TELEMEDICINE_API_TOKEN`.
- `LsxMedicalAuthClient` chama `/api/clinic/patients/authenticate` com Bearer
  server-side, timeout de 10 segundos por padrão e credenciais apenas no corpo
  da requisição.
- `LsxMedicalPatientClient` continua disponível como cliente complementar,
  mas a tela e o cadastro de pacientes usam o banco local e não o chamam.
- `LsxMedicalConsultationClient` implementa apenas histórico de consultas,
  exige CPF e o envia somente com dígitos. Na área do paciente
  (`POST /triagem/consultations-search`) o CPF vem sempre da conta logada,
  os filtros são validados e paciente sem tenant recebe 403.
- Na área do gestor, CPF e filtros são enviados ao Laravel por
  `POST /gestor/dados/consultations-search`, nunca em URL do browser. O gestor
  precisa ter tenant. O Laravel usa query string apenas na chamada
  server-to-server porque esse é o contrato documentado da LSX.
- Falhas de conexão não registram a mensagem da exceção, pois ela contém a URL
  com CPF na query string.
- `MakesTelemedicineRequests` centraliza URL, token, timeout e conversão de
  falhas para `TelemedicineApiException` (indisponível, não autorizado,
  inválido, não encontrado e rejeição de negócio).
- Controllers capturam essa exceção, reportam e exibem erro resumido.
- `LsxMedicalSchedulingClient` implementa a sequência de especialidades, dias
  úteis, horários, profissionais e criação de consulta. Cada seleção é
  revalidada no backend antes da criação.
- O contrato interno do browser usa `specialty_id`. O cliente Laravel traduz
  esse campo para `specialty` nos endpoints de dias, horários e profissionais,
  conforme validado na homologação LSX. A criação da consulta preserva
  `specialty_id`, exigido pelo contrato específico de `create-consultation`.
- `ConsultationSchedulingService` deriva CPF e tenant do usuário autenticado,
  força `is_paid=false` e registra o agendamento local antes da escrita remota.
- A página `/agendamento` mantém o acesso ao atendimento imediato e oferece um
  formulário manual. A especialidade selecionada participa da consulta de dias,
  horários e profissionais disponíveis. Tenants com regulação também podem
  iniciar esse formulário por requisito explícito de produto.
- A criação usa `request_id` único por paciente e tenant. Falha ambígua de
  conexão, indisponibilidade ou resposta inválida muda o registro para
  `reconciliation_required` e bloqueia reenvio automático.
- Na criação, `doctor_id` é enviado somente quando `is_real_doctor` é verdadeiro,
  conforme o contrato LSX. Opções sem profissional específico preservam o ID
  local retornado na disponibilidade, mas não o enviam no payload de criação.
- Falha ao concluir a persistência depois de uma resposta LSX válida também
  exige reconciliação e não dispara uma segunda criação remota.
- O registro local não guarda CPF nem `patient_link`. Nomes de especialidade e
  profissional são criptografados em aplicação.
- Não há cliente de webhook, prescrição retornada ou vídeo no código atual;
  `HealthcareDataService` retorna 503 honesto para esses recursos pendentes.
- Não há sincronização automática entre o cadastro nativo e a LSX. O mapeamento
  e a reconciliação exigem contrato homologado e permanecem
  `NEEDS_VERIFICATION`.

## Invariantes

- Browser chama Laravel; somente Laravel chama LSX.
- Nunca enviar token da clínica ou senha do paciente ao frontend, logs ou
  exceptions.
- Não inventar endpoints ou retry para operações não idempotentes.
- Toda nova operação deve definir timeout, tratamento de status, JSON inválido,
  indisponibilidade e política de retry antes de implementação.

## Riscos e lacunas

- Não há retries/circuit breaker explícitos. Adicioná-los exige análise de
  idempotência e contrato LSX.
- A reconciliação automática de registros `reconciliation_required` ainda não
  existe. Uma nova tentativa com o mesmo `request_id` é bloqueada para evitar
  consulta duplicada.
- Mensagens de erro do provedor podem ser retornadas em contexto de validação;
  revisar redaction antes de ampliar os payloads exibidos.
- A separação de dados por clínica/tenant dentro do token LSX é
  `NEEDS_VERIFICATION`.
- A base legal e o prazo de retenção do espelho local de agendamentos são
  `NEEDS_VERIFICATION`.
- O contrato foi implementado conforme a referência funcional disponível. A
  homologação real, inclusive formatos de resposta, permanece
  `NEEDS_VERIFICATION` porque nenhuma consulta real foi criada durante os
  testes automatizados.

## Arquivos principais

`config/lsxmedical.php`, `.env.example`, `app/Services/Telemedicine/*`,
`app/Http/Controllers/Healthcare/PatientController.php`,
`app/Http/Controllers/Healthcare/ConsultationController.php`,
`app/Http/Controllers/Healthcare/ConsultationSchedulingController.php`,
`app/Services/Healthcare/ConsultationSchedulingService.php`,
`app/Services/Healthcare/HealthcareDataService.php` e testes de autenticação,
pacientes e consultas.
