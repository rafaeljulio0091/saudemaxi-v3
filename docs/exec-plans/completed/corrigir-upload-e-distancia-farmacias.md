# Exec Plan: Corrigir upload de receita e distância de farmácias

- **Status:** completed
- **Objetivo:** enviar corretamente a imagem selecionada da receita e disponibilizar distâncias para farmácias com endereços oficiais, usando a localização consentida do paciente autenticado.
- **Contexto:** o frontend cria apenas a prévia da receita e envia um objeto vazio para o backend. O cálculo Haversine de distância já existe e respeita tenant, mas as farmácias importadas não possuem coordenadas porque as planilhas oficiais fornecem somente endereço textual.
- **Escopo:** envio multipart da receita, feedback da interface, geocodificação administrativa de endereços públicos de farmácias, proveniência das coordenadas, cálculo local existente e testes.
- **Fora de escopo:** leitura automatizada do conteúdo da receita, integração LSX, persistência da localização do paciente, geocodificação de endereços pessoais e cálculo de rota viária.
- **Risco:** HIGH
- **Dados sensíveis envolvidos:** imagem da receita e coordenadas temporárias do navegador. O endereço geocodificado pertence a estabelecimento público.
- **Impacto LGPD:** a receita continua armazenada no disco privado e vinculada ao usuário autenticado. A localização do paciente permanece restrita à requisição e não é enviada ao geocodificador. Nenhuma coordenada pessoal será persistida.
- **Impacto multi-tenant:** upload permanece vinculado ao usuário autenticado. Farmácias e endereços serão selecionados e atualizados exclusivamente pelo tenant informado ao comando administrativo.
- **Integrações afetadas:** nova integração configurável de geocodificação server-side para endereços públicos. LSX não será alterada.
- **Arquivos envolvidos:** serviço e página de farmácia, cliente HTTP, geocodificador, comando Artisan, configuração, migration aditiva, modelos, serviço de distância, testes e documentação de arquitetura.

## Plano de implementação

1. Inspeção: confirmar contrato multipart, armazenamento privado, autorização, origem das coordenadas e limitações do cadastro nativo.
2. Implementação: enviar `FormData`, preservar o arquivo selecionado, criar geocodificação administrativa sequencial e armazenar coordenadas com proveniência para o cálculo local.
3. Validação: cobrir PNG real, arquivo inválido, tenant, geocodificação, falhas externas, idempotência, atribuição, frontend, migration, suíte completa, build, formatação e diff.

## Testes

- O frontend inclui o arquivo selecionado no campo multipart `file`.
- PNG e JPG válidos são armazenados no disco privado e vinculados somente ao usuário autenticado.
- Arquivos inválidos continuam rejeitados.
- O comando atualiza somente endereços de farmácias do tenant selecionado.
- Coordenadas existentes e tentativas já realizadas não geram nova chamada por padrão.
- Resposta vazia, inválida ou falha HTTP não cria coordenadas falsas.
- A busca continua ordenando pelo cálculo local e não envia a localização do paciente ao geocodificador.
- `php artisan test`: 159 testes e 1.174 asserções aprovados.
- Testes focados finais: 33 testes e 289 asserções aprovados.
- `npm run test:frontend`: aprovado.
- `npm run build`: aprovado.
- `npm run format:check`: aprovado.
- Pint focado nos arquivos alterados: aprovado.
- Migration `up` e `down`: aprovadas em SQLite descartável.
- `git diff --check`: aprovado.

## Security Review

- Preservar sessão, CSRF, perfil de paciente, módulo contratado e escopo tenant.
- Não expor receita por URL pública nem registrar seu conteúdo, nome ou caminho.
- Validar MIME e tamanho no servidor e no frontend.
- Não enviar coordenadas do paciente nem dados pessoais ao serviço externo.
- Usar timeout, identificação do cliente, limite sequencial e persistência da tentativa para evitar chamadas repetidas.

## Decisões

- A localização atual do navegador permanece a fonte do paciente porque o vínculo entre `users` e `patients` ainda não é garantido pelo produto.
- O cálculo de distância continuará local, pelo método Haversine já coberto por testes.
- A geocodificação ocorrerá por comando administrativo, nunca durante a requisição do paciente.
- O provedor será configurável e desabilitado por padrão até a implantação definir identificação e política operacional.

## Pendências

- A equipe de implantação deve confirmar o provedor de geocodificação e seus termos antes de habilitar a integração em produção.
- Distância em linha reta não representa rota viária nem tempo de deslocamento.
- O Pint global continua apontando formatação preexistente em `bootstrap/app.php`, arquivo não alterado nesta implementação.
- A inspeção visual automatizada não foi executada porque o script existente depende de `playwright`, pacote ausente no projeto. Testes frontend, formatação e build passaram.
