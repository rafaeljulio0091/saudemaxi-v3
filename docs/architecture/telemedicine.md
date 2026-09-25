# Telemedicina / LSX Medical

## Implementação atual

- A configuração está em `config/lsxmedical.php`; a URL, endpoints, timeout e
  token vêm de variáveis de ambiente. O token é `TELEMEDICINE_API_TOKEN`.
- `LsxMedicalAuthClient` chama `/api/clinic/patients/authenticate` com Bearer
  server-side, timeout de 10 segundos por padrão e credenciais apenas no corpo
  da requisição.
- `LsxMedicalPatientClient` implementa busca e criação de pacientes.
- `LsxMedicalConsultationClient` implementa apenas histórico de consultas e
  exige CPF antes de chamar o provedor.
- `MakesTelemedicineRequests` centraliza URL, token, timeout e conversão de
  falhas para `TelemedicineApiException` (indisponível, não autorizado,
  inválido, não encontrado e rejeição de negócio).
- Controllers capturam essa exceção, reportam e exibem erro resumido.
- Não há cliente de webhook, prescrição retornada, especialidades, horários,
  médicos, agendamento ou vídeo no código atual; `HealthcareDataService`
  retorna 503 honesto para esses recursos pendentes.

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
- Mensagens de erro do provedor podem ser retornadas em contexto de validação;
  revisar redaction antes de ampliar os payloads exibidos.
- A separação de dados por clínica/tenant dentro do token LSX é
  `NEEDS_VERIFICATION`.

## Arquivos principais

`config/lsxmedical.php`, `.env.example`, `app/Services/Telemedicine/*`,
`app/Http/Controllers/Healthcare/PatientController.php`,
`app/Http/Controllers/Healthcare/ConsultationController.php`,
`app/Services/Healthcare/HealthcareDataService.php` e testes de autenticação,
pacientes e consultas.
