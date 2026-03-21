# Correcao: Central de Mensagens - codigos de erro, observabilidade e clientes novos

Data: 20/03/2026

## Contexto

Durante a operacao da Central de Mensagens havia dois pontos criticos:
- respostas backend sem padrao unico de `code` por status HTTP, dificultando leitura rapida de falhas
- lista de conversas com variacao visual de ordenacao quando varios registros tinham mesmo timestamp

Tambem faltava um fluxo direto para contatos novos (sem cliente vinculado) e uma triagem automatica inicial de nome/sobrenome.

## Ajustes aplicados

### 1) Padronizacao de erros por endpoint

Controller `CentralMensagens` passou a usar envelope unico:
- sucesso: `{ ok: true, status, code, ... }`
- erro: `{ ok: false, status, code, message }`

Metodos alinhados:
- `conversas`
- `conversa`
- `conversaNovas`
- `conversaStream` (incluindo `probe`, `disabled`, erro incremental SSE)
- `enviar`
- `vincularOs`
- `atualizarMeta`
- `syncInbound`
- `cadastrarContatoConversa` (novo)

### 2) Observabilidade de falhas

Novo helper no controller:
- `observeEndpointFailure(code, status, message, context, exception)`

Comportamento:
- registra falhas em `log_message` com contexto tecnico (endpoint, ids, filtros, URI, metodo, IP)
- persiste o evento na tabela `logs` para diagnostico operacional rapido

### 3) Estabilizacao da ordenacao da inbox

Critico para exibir sempre as conversas mais novas no topo:
- backend: `ultima_mensagem_em DESC`, `ultima_mensagem_id DESC`, `id DESC`
- frontend: sort deterministico com mesmo criterio antes do render

Resultado:
- elimina variacao de ordem em empates de timestamp
- reduz "troca de posicao" inesperada entre polls

### 4) Filtro "Clientes novos"

Filtro novo na barra lateral:
- checkbox `Clientes novos` ao lado de `Com OS aberta`
- envia `clientes_novos=1`
- backend aplica:
  - `conversas_whatsapp.cliente_id IS NULL`
  - `contatos.cliente_id IS NULL`

### 5) Cadastro rapido no card da conversa

Para conversa sem cliente:
- badge `Cliente novo`
- botao `Salvar contato` no card
- modal SweetAlert2 com telefone preenchido e nome sugerido quando disponivel
- endpoint novo `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`
- vinculo automatico em `conversas_whatsapp.contato_id`
- nao cria cliente automaticamente

### 6) Automacao para novo contato sem nome de perfil

No `ChatbotService`:
- se conversa nao tem cliente e nao ha nome confiavel:
  - envia solicitacao de nome/sobrenome
- quando cliente responde com duas ou mais palavras validas:
  - grava `nome_contato`
  - envia confirmacao pedindo descricao/audio do problema

Mensagens implementadas:
- `bom dia|boa tarde|boa noite, *atendimento automatico* ! tudo bem ?!`
- `por favor diga APENAS seu nome e sobre nome para prosseguirmos o atendimento !`
- `blza {nome sobrenome} ! me digite ou mande um audio do que podemos lhe ajudar que logo encaminho para o atendimento de um jovem humano !`

### 7) Funil relacional de contatos e metricas de marketing

Evolucao aplicada para separar com mais rigor:
- contato (agenda operacional)
- cliente (cadastro formal de negocio)

Tabela `contatos` recebeu lifecycle:
- `status_relacionamento`: `lead_novo`, `lead_qualificado`, `cliente_convertido`
- `qualificado_em`
- `convertido_em`

Regras de transicao:
- entrada inicial sem cliente -> `lead_novo`
- nome completo confiavel (manual ou chatbot) -> `lead_qualificado`
- vinculo operacional com cliente (principalmente abertura de OS) -> `cliente_convertido`

Reflexos aplicados:
- lista de contatos com filtro e badge de etapa relacional
- central de mensagens com badge `Lead qualificado` quando aplicavel
- CRM > Metricas Marketing com indicadores de:
  - leads captados
  - leads qualificados
  - leads convertidos
  - taxa de qualificacao
  - taxa de conversao

### 8) Hotfix de resiliência quando migration de contatos ainda nao rodou

Problema observado em producao/local:
- `GET /atendimento-whatsapp/conversas` retornava 500 em loop quando a tabela `contatos` nao existia no banco
- frontend entrava em polling com erro recorrente `Nao foi possivel carregar as conversas no momento`

Correcao aplicada:
- query de conversas ficou condicional ao schema:
  - join em `contatos` apenas se tabela existir
  - campos de contato retornam `NULL` quando schema ainda nao foi migrado
  - filtro `clientes_novos` faz fallback para `conversas_whatsapp.cliente_id IS NULL` sem exigir `contatos`
- endpoint de cadastro rapido de contato passou a retornar erro controlado:
  - HTTP `409`
  - `code: CM_CONTATOS_SCHEMA_MISSING`
  - mensagem orientando execucao das migracoes do modulo contatos

## Arquivos tecnicos alterados

- `app/Controllers/CentralMensagens.php`
- `app/Services/CentralMensagensService.php`
- `app/Services/ChatbotService.php`
- `app/Controllers/Os.php`
- `app/Controllers/Contatos.php`
- `app/Controllers/Crm.php`
- `app/Models/ContatoModel.php`
- `app/Database/Migrations/2026-03-20-091500_AddContatoLifecycleMarketingFields.php`
- `app/Views/central_mensagens/index.php`
- `app/Views/contatos/index.php`
- `app/Views/contatos/form.php`
- `app/Views/os/form.php`
- `public/assets/js/central-mensagens.js`
