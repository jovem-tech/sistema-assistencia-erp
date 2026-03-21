?# Modulo: Central de Mensagens (Central de Atendimento Inteligente 24h)

Atualizado em 20/03/2026.

## Objetivo
Transformar o WhatsApp operacional em uma central unica de atendimento integrada ao ERP + CRM, com:
- inbox WhatsApp OS
- contexto de cliente/equipamento/OS
- automacao de resposta por intencao (chatbot)
- fila de atendimento humano
- metricas operacionais de atendimento

Rota principal: `/atendimento-whatsapp`
Alias de compatibilidade: `/central-mensagens`

## Refatoracao visual premium (20/03/2026)

A tela principal da Central foi elevada para padrao SaaS de operacao (WhatsApp Web + Intercom style), mantendo o mesmo backend e rotas.

Principais evolucoes aplicadas em `/atendimento-whatsapp`:
- header da tela com indicador de estado de tempo real (`Tempo real`, `Polling`, `Instavel`)
- cards de conversa com hierarquia forte (avatar, nome, preview, badges operacionais, nao lidas, responsavel)
- ordenacao da inbox com prioridade para nao lidas e, dentro disso, recencia
- barra de filtros com feedback explicito de filtros ativos e botao `Limpar`
- cabecalho do chat com acoes de operador:
  - atualizar conversa
  - assumir conversa (atribui para usuario logado)
  - atribuir (foco no painel de contexto)
  - encerrar (status `resolvida`)
- bolhas de mensagem com:
  - separador por data (`Hoje`, `Ontem`, data completa)
  - status outbound (`Enviada`, `Entregue`, `Lida`, `Falha`)
  - animacao suave para mensagens novas
- composer modernizado:
  - menu de anexo
  - menu rapido de emojis
  - foco em envio rapido por teclado (`Enter` envia, `Shift+Enter` quebra linha)
- contexto lateral reorganizado por secoes:
  - contato (agenda)
  - cliente ERP
  - gestao da conversa
  - vinculo de OS
  - follow-ups
- estados visuais elegantes para:
  - carregando
  - sem dados
  - erro

Arquivos tecnicos impactados:
- `app/Controllers/CentralMensagens.php`
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`

## Estabilizacao de runtime (fase atual)

Padrao aplicado para reduzir erros de sessao/rede e evitar loops:
- runtime unico no frontend: a pagina renderiza somente `window.CM_CFG` + `public/assets/js/central-mensagens.js`
- bloco legado inline mantido apenas como referencia interna e fora da resposta HTML (`if (false)`)
- wrappers de request (`getJson` e `postForm`) com tratamento explicito para:
  - 401/403: encerra polling/stream, informa expiracao de sessao e redireciona para `/login`
  - 502/503/504: mensagem operacional de indisponibilidade de backend/gateway
  - resposta HTML inesperada: bloqueio com mensagem tecnica amigavel
  - falha de rede/CORS e timeout: erro padronizado para diagnostico rapido
- encerramento defensivo de ciclo assinc:
  - `beforeunload` e `pagehide` finalizam polling + stream
  - quando a aba fica oculta, o stream SSE e fechado e reaberto no retorno
- throttle de log no polling incremental para evitar flood no console quando houver indisponibilidade prolongada

## Padronizacao de erro + observabilidade operacional (fase 20/03/2026)

Backend da Central passou a usar contrato unico por status HTTP com `code` tecnico:
- sucesso: `{ ok: true, status, code, ... }`
- erro: `{ ok: false, status, code, message }`

Aplicado em endpoints operacionais:
- `GET /atendimento-whatsapp/conversas`
- `GET /atendimento-whatsapp/conversa/{id}`
- `GET /atendimento-whatsapp/conversa/{id}/novas`
- `GET /atendimento-whatsapp/conversa/{id}/stream`
- `POST /atendimento-whatsapp/enviar`
- `POST /atendimento-whatsapp/vincular-os`
- `POST /atendimento-whatsapp/atualizar-meta`
- `POST /atendimento-whatsapp/sync-inbound`
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`

Observabilidade de falhas por endpoint:
- toda falha relevante gera `code` estavel (ex.: `CM_ENVIO_PROVIDER_FAILED`, `CM_META_UPDATE_FAILED`, `CM_SYNC_INBOUND_ERROR`)
- controller registra contexto tecnico (endpoint, ids, filtros, URI, metodo, IP) em `log_message`
- mesmo contexto e persistido na tabela `logs` para diagnostico operacional rapido sem depender apenas do console do navegador

## Compatibilidade com schema em migracao

Hotfix aplicado para evitar `500` no endpoint de lista quando o banco ainda nao recebeu a migration de contatos:
- `GET /atendimento-whatsapp/conversas` passou a funcionar mesmo sem tabela `contatos`
- join/filtros/campos de `contatos` sao habilitados apenas quando a tabela existe
- em banco ainda nao migrado, o filtro `clientes_novos` considera `conversas_whatsapp.cliente_id IS NULL`

Endpoint de cadastro rapido:
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`
- quando a tabela `contatos` nao existe, retorna `409` com `code = CM_CONTATOS_SCHEMA_MISSING` (ao inves de erro 500 generico)

## Inbox: ordenacao deterministica e filtro de clientes novos

A lista de conversas foi estabilizada para manter sempre as mais novas no topo, sem variacao visual entre recargas:
- ordenacao backend: `ultima_mensagem_em DESC`, `ultima_mensagem_id DESC`, `conversas_whatsapp.id DESC`
- ordenacao frontend (fallback deterministico): mesmo criterio antes do render

Novo filtro lateral:
- checkbox `Clientes novos` (ao lado de `Com OS aberta`)
- envia `clientes_novos=1`
- backend filtra:
  - `conversas_whatsapp.cliente_id IS NULL`
  - `contatos.cliente_id IS NULL`

## Cadastro rapido de contato novo pela conversa

Novo fluxo no card da conversa:
- quando `cliente_id` nao existe, o card recebe badge `Cliente novo`
- atendente pode usar botao `Salvar contato`
- modal (SweetAlert2) abre com:
  - telefone preenchido
  - nome sugerido pelo `contato_nome`/`contato_perfil_nome`/`nome_contato` (quando nao for telefone)

Endpoint novo:
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`
- cria/atualiza contato (`contatos`) e vincula em `conversas_whatsapp.contato_id`
- aplica etapa relacional no contato:
  - `lead_novo` (sem nome completo confiavel)
  - `lead_qualificado` (nome completo capturado)
  - `cliente_convertido` (quando existe vinculo com `clientes`)
- **nao** cria cliente automaticamente
- cliente so e vinculado quando existir contexto operacional (ex.: abertura de OS)

## Nova OS com contexto da conversa

Na coluna de contexto da conversa, o botao `Nova OS` abre `/os/nova` com query params de origem:
- `origem_conversa_id`
- `origem_contato_id`
- `cliente_id` (quando existir)
- `telefone`
- `nome_hint`

No formulario de OS:
- exibe alerta visual de origem da Central WhatsApp
- persiste ids de origem em hidden fields
- pre-seleciona cliente quando ja houver vinculo
- ao salvar, sincroniza conversa/contato com cliente e vinculo em `conversa_os`

## Automacao para contato novo sem nome de perfil

No `ChatbotService`, para conversa sem cliente e sem nome confiavel:
- bot envia:
  - `bom dia|boa tarde|boa noite, *atendimento automatico* ! tudo bem ?!`
  - linha em branco
  - `por favor diga APENAS seu nome e sobre nome para prosseguirmos o atendimento !`
- quando cliente responde com duas ou mais palavras validas:
  - conversa recebe `nome_contato` atualizado
  - bot confirma:
    - `blza {nome sobrenome} ! me digite ou mande um audio do que podemos lhe ajudar que logo encaminho para o atendimento de um jovem humano !`

## Arquitetura por camadas (produto)

Fluxo conceitual:

`WhatsApp OS -> Motor de Atendimento Automatico -> Integracao ERP -> Camada CRM (eventos/follow-up/timeline) -> Camada de Metricas/Gestao`

Fluxo tecnico:

`UI Central -> CentralMensagensController -> CentralMensagensService/ChatbotService/MetricasMensageriaService -> WhatsAppService -> MensageriaService -> Provider ativo`

Providers suportados:
- `api_whats_local`
- `api_whats_linux`
- `menuia`
- `webhook`

## Estrutura funcional por fases

### Fase 1 - WhatsApp OS (Inbox operacional)
- lista de conversas com filtros
- thread completa inbound/outbound
- envio manual de texto, PDF e anexos (`foto`, `video`, `audio`, `arquivo`)
- exibe anexos por mensagem com renderer especifico por tipo (imagem, audio, video, PDF e arquivo)
- vinculo por cliente/telefone
- vinculo e gestao de OS na conversa
- atualizacao incremental da thread via endpoint de novas mensagens

### Fase 2 - Contexto ERP + CRM
- painel lateral com cliente/OS/documentos/follow-ups
- atualizacao de status/responsavel/tags da conversa
- registro de eventos e interacoes CRM por mensagem
- badges operacionais por conversa (bot ativo/off, aguardando humano, OS vinculada, SLA estourado, prioridade)

### Fase 3 - Atendimento automatico (chatbot)
- deteccao de intencao por regras e palavras-chave
- FAQ configuravel
- resposta automatica para status OS, orcamento, previsao e horario
- fallback para humano com `aguardando_humano = 1`
- logs de decisao do bot
- threshold de confianca configuravel em `/atendimento-whatsapp/configuracoes`

### Fase 4 - Fluxos e automacoes
- regras ERP -> mensagem/follow-up
- fluxos de atendimento configuraveis
- respostas rapidas reutilizaveis
- regras dinamicas em `chatbot_regras_erp` executadas por `CrmService::applyStatusAutomation()`

### Fase 5 - Gestao e metricas
- dashboard de volume
- tempo medio de resposta
- taxa de automacao
- fila aguardando humano
- produtividade por atendente
- consolidacao diaria em `mensageria_metricas_diarias`

## Telas do modulo

<a id="whatsapp-os"></a>
### 1) WhatsApp OS
Rota: `/atendimento-whatsapp`

Tela em 3 colunas:
1. WhatsApp OS (filtros e nao lidas)
2. Thread (mensagens + envio)
3. Contexto (cliente/OS/follow-up/tags)

Layout estrutural aplicado (index):
- painel principal unico: `.central-mensagens-wrapper`
- altura desktop: `calc(100vh - 140px)`
- proporcao das colunas: `28% / 44% / 28%`
- rolagem individual por coluna
- remocao da barra superior de modulos (WhatsApp OS/Chatbot/Metricas/...) na tela principal para ampliar area util do atendimento
- cabecalho da pagina (`.cm-page-header`) com fundo transparente e sem bordas para maxima integracao visual
- barra de filtros horizontal e compacta no topo da listagem de conversas, com alinhamento flexível e responsivo

#### Responsividade profissional (inspirada em WhatsApp Web/Business)

Breakpoints aplicados:
- `>= 992px`: 3 colunas ativas (`lista + chat + contexto`) com distribuicao fixa `28% / 44% / 28%`.
- `768px a 991px`: colunas empilhadas verticalmente (`width: 100%`) para manter leitura e toque confortavel.
- `< 768px`: pilha vertical com cards em largura total e composer otimizado para toque.

Comportamentos de UX:
- rolagem independente por coluna (lista, thread e contexto)
- composer de mensagem fixo no rodape do chat
- botao flutuante "Ir para o fim" na thread para conversas extensas
- gatilhos `WhatsApp OS` e `Contexto` no header do chat aparecem somente em `< 992px` (offcanvas); em desktop as colunas permanecem fixas
- botoes de acao simplificados em telas menores
- prevencao de overflow horizontal e quebra de layout em anexos/midias
- suporte consistente para tema claro/escuro mantendo tokens do Design System
- altura principal central fixa em desktop/notebook: `calc(100vh - 140px)`, com rolagem independente em cada coluna

#### Atualizacao fluida da thread (SSE + fallback incremental)

Para reduzir refresh bruto e manter chat quase em tempo real:
- stream SSE por conversa: `GET /atendimento-whatsapp/conversa/{id}/stream`
- validacao previa por `probe` e `handshake` antes de abrir `EventSource`
- fallback automatico para polling incremental (`/novas?after_id=...`) quando SSE estiver indisponivel
- envio com UX desacoplada do refresh: confirmacao libera o botao rapidamente e a sincronizacao visual continua em background

Objetivo tecnico da validacao previa:
- evitar tentativas repetidas de SSE quando o endpoint retorna HTML (erro de MIME `text/html` no browser)
- manter atualizacao de mensagens funcional mesmo com indisponibilidade temporaria de stream

Comportamento implementado:
- polling automatico por configuracao (`central_mensagens_auto_sync_interval`)
- abertura de conversa marca inbound como lida e zera contador de nao lidas
- atualizacao de metadados (status, responsavel, prioridade, bot, aguardando humano, tags)
- mensagens recebidas (inbound) e enviadas (outbound) exibidas na mesma thread com classificacao de origem dinamica:
  - `sistema`: enviada pelo ERP por usuario interno
  - `externo`: enviada fora do ERP (app WhatsApp/gateway)
  - `chatbot`: enviada automaticamente pelo bot
- remetente dinamico no header da bolha outbound:
  - `sistema`: nome do usuario interno (`usuario_nome`)
  - `externo`: numero autenticado no gateway (`#gatewayAccountNumber`)
  - `chatbot`: `Chatbot`
- badge "via" dinamica:
  - `sistema`: `via sistema`
  - `externo`: `via app externo`
  - `chatbot`: `chatbot`
- cores padronizadas por origem outbound:
  - `sistema`: azul
  - `externo`: verde
  - `chatbot`: roxo
- acao rapida `Responder` em mensagens recebidas para montar resposta no campo de envio sem sair da conversa
- envio rapido por teclado (`Enter` envia, `Shift+Enter` quebra linha)
- lista lateral em estilo WhatsApp com preview da ultima mensagem e prefixo de origem (`Cliente`, `Voce`, `Bot`)
- abertura automatica da primeira conversa disponivel para acelerar leitura de mensagens recebidas
- anexos enviados pelo atendente com preview antes do envio (chip de anexo com remover)
- visualizacao de imagem padronizada no mesmo modal de galeria do sistema, com navegacao entre imagens da thread
- audio reproduzido no proprio chat com player embutido (play/pause, progresso e duracao)
- refresh da thread sem recarregamento bruto (incremental por `after_id`)
- carregamento inicial da thread com **janela mais recente** de mensagens (evita abrir conversas longas no inicio historico e garante visibilidade das mensagens externas mais novas)
- área de chat com fundo personalizado estilo "WhatsApp Doodle" com suporte a temas claro e escuro para maior imersão
- abertura de conversa executa sincronizacao forçada do historico no gateway local/linux antes do render, para reduzir defasagem de mensagens externas em casos de atraso de webhook
- **Hub de Composição (Botão "+")**: Centralização de todas as ações de envio em um menu hub inspirado no WhatsApp Web:
  - **Enviar arquivo**: Seleção de mídias e documentos locais (imagem, vídeo, áudio, PDF, docs).
  - **Enviar PDF do sistema**: Painel flutuante para seleção de PDFs gerados pelo ERP (Laudos, Orçamentos).
  - **Tipo da mensagem**: Painel flutuante para classificar a mensagem (Manual, Orçamento, Laudo, Status OS).
  - **Tirar foto agora**: Acionamento direto da câmera do dispositivo (mobile/desktop).
  - **Gravar áudio agora**: Gravador de voz integrado com preview e confirmação (`MediaRecorder API`).
  - **Gravar vídeo agora**: Gravador de vídeo integrado com preview de câmera e confirmação.
- **Preview de Anexo Moderno**: Exibição de anexos selecionados via "Chips" compactos com ícone por tipo, nome do arquivo, tamanho e ação de remover.
- **Resiliência de Captura**: Fallback para seleção de arquivo caso o dispositivo não suporte capturas diretas de mídia.

## Midias e armazenamento (padrao oficial)

Base publica:

`public/uploads/central_mensagens/{telefone}/{tipo}/`

Tipos de subpasta:
- `foto`
- `video`
- `audio`
- `pdf`
- `arquivo`

Regras:
- telefone sanitizado (somente digitos) como identificador do cliente da conversa
- criacao automatica da pasta do telefone e da subpasta de tipo
- normalizacao de nome de arquivo e controle de sobrescrita (`_1`, `_2`, ...)
- a mensagem salva em banco registra `arquivo`, `anexo_path`, `mime_type` e `tipo_conteudo`

<a id="chatbot"></a>
### 2) Chatbot / Automacao
Rota: `/atendimento-whatsapp/chatbot`

Permite:
- cadastrar/ativar intencoes
- cadastrar/ativar regras ERP
- acompanhar logs de deteccao e escalonamento
- **Variaveis Dinamicas**: Suporte a templates nas respostas (`{{cliente_nome}}`, `{{numero_os}}`, `{{status}}`, `{{valor_final}}`, `{{data_previsao}}`, `{{garantia_dias}}`, `{{equipamento}}`, `{{empresa_endereco}}`).

<a id="metricas"></a>
### 3) Metricas
Rota: `/atendimento-whatsapp/metricas`

Exibe:
- recebidas/enviadas/automaticas/humanas
- SLA estourado
- top intencoes
- volume por dia
- produtividade por atendente

<a id="respostas-rapidas"></a>
### 4) Respostas Rápidas
Rota: `/atendimento-whatsapp/respostas-rapidas`

Interface refatorada para alta produtividade com:
- **Layout Bilateral**: Formulário de cadastro dinâmico à esquerda e catálogo de visualização à direita.
- **Edição Profissional em Modal**: Abertura de formulário dedicado em modal glassmorphism para uma experiência de edição focada, sem perder o contexto da listagem.
- **Variáveis Dinâmicas (Tags)**: Suporte a placeholders inteligentes como `{{cliente_nome}}`, `{{numero_os}}`, `{{equipamento}}`, `{{status}}`, `{{valor_final}}`, `{{data_previsao}}`, `{{garantia_dias}}` e `{{defeito}}`.
- **Seletor de Variáveis**: Interface visual para inserção rápida de tags no texto com um clique, garantindo o uso correto da sintaxe.
- **Preenchimento em Tempo Real**: No chat, ao selecionar uma resposta rápida, o sistema substitui automaticamente as tags pelos dados reais do cliente e da OS vinculada antes de carregar no campo de envio.

<a id="fluxos"></a>
### 5) Fluxos de Atendimento
Rota: `/atendimento-whatsapp/fluxos`

Fluxos padronizados por tipo (operacional, orcamento, pos-atendimento).

<a id="faq"></a>
### 6) FAQ / Base de Conhecimento
Rota: `/atendimento-whatsapp/faq`

Integração de inteligência e interface avançada:
- **Edição via Modal**: Gestão simplificada de perguntas e respostas em ambiente focado.
- **Base de Conhecimento Dinâmica**: FAQ agora suporta as mesmas tags dinâmicas (`{{cliente_nome}}`, `{{numero_os}}`, etc.) das respostas rápidas.
- **Detecção por Gatilhos**: Definição de palavras-chave que disparam a resposta automática via motor de intenções.
- **Preview de Conteúdo**: Listagem otimizada com truncamento inteligente para facilitar a navegação em grandes bases.

<a id="filas"></a>
### 7) Filas e Responsaveis
Rota: `/atendimento-whatsapp/filas`

Gestao de priorizacao, responsavel e status operacional da conversa.

<a id="configuracoes"></a>
### 8) Configuracoes
Rota: `/atendimento-whatsapp/configuracoes`

Parametros da central:
- intervalo de sync
- SLA de resposta
- provider padrao
- threshold de confianca do bot
- horario e dias de operacao
- mensagem de fallback (personalizavel quando o bot nao entende)

## Tabelas envolvidas
- `conversas_whatsapp`
- `contatos`
- `conversa_os`
- `conversa_tags`
- `mensagens_whatsapp`
- `respostas_rapidas_whatsapp`
- `chatbot_intencoes`
- `chatbot_faq`
- `chatbot_fluxos`
- `chatbot_logs`
- `chatbot_regras_erp`
- `mensageria_metricas_diarias`
- `crm_eventos`
- `crm_interacoes`
- `crm_followups`
- `crm_mensagens`
- `whatsapp_inbound` (fila de recebimento bruto)

## Endpoints internos
- `GET /atendimento-whatsapp` (WhatsApp OS)
- `GET /atendimento-whatsapp/conversa/{id}`
- `GET /atendimento-whatsapp/conversa/{id}/novas?after_id={id}`
- `GET /atendimento-whatsapp/conversa/{id}/stream?after_id={id}`
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`
- `POST /atendimento-whatsapp/enviar`
- `POST /atendimento-whatsapp/vincular-os`
- `POST /atendimento-whatsapp/atualizar-meta`
- `POST /atendimento-whatsapp/sync-inbound`
- `POST /atendimento-whatsapp/chatbot/intencao/salvar`
- `POST /atendimento-whatsapp/chatbot/regra/salvar`
- `POST /atendimento-whatsapp/faq/salvar`
- `POST /atendimento-whatsapp/respostas-rapidas/salvar`
- `POST /atendimento-whatsapp/fluxos/salvar`
- `POST /atendimento-whatsapp/filas/atualizar`
- `POST /atendimento-whatsapp/metricas/consolidar-diario`
- `POST /atendimento-whatsapp/configuracoes/salvar`

Webhook inbound:
- `POST /webhooks/whatsapp` (token em `X-Webhook-Token` ou `?token=`)

Parametros de stream SSE:
- `probe=1`: valida disponibilidade basica do endpoint em JSON (pre-check).
- `handshake=1`: valida cabecalhos de stream (`text/event-stream`) antes de abrir `EventSource`.
- `after_id`: envia apenas mensagens novas apos o ultimo id renderizado.

## Recebimento inbound com midia (novo)

### Origem
Gateway Node (`whatsapp-api/server.js`) captura mensagens inbound e encaminha para o ERP.

### Payload inbound suportado
- texto (`message`, `text`, `body`)
- metadados de midia (`has_media`, `media_mime_type`, `media_filename`)
- conteudo base64 (`media_base64`) quando disponivel e dentro do limite

### Persistencia no ERP
`CentralMensagensService::registerInboundFromPayload()`:

## Sincronizacao de respostas fora do ERP (novo)

Para garantir visao completa da conversa, a Central agora registra tambem mensagens enviadas pela propria conta fora da tela do ERP (ex.: tecnico respondeu no app WhatsApp do celular).

### Como funciona
- O gateway Node escuta:
  - `message` para mensagens recebidas do cliente (`fromMe = false`)
  - `message_create` para mensagens enviadas pela propria conta (`fromMe = true`)
- Ambos os eventos sao encaminhados para `POST /webhooks/whatsapp`.
- O ERP persiste:
  - `direcao = inbound` para cliente -> equipe
  - `direcao = outbound` para equipe -> cliente (inclusive resposta feita fora do ERP)

### Regra de deduplicacao
- Quando chega `provider_message_id` ja existente, o ERP atualiza o registro e nao duplica.
- Em corrida de envio (mensagem enviada pelo ERP e evento `message_create` quase simultaneo), o ERP tenta reconciliar com o outbound pendente recente para evitar mensagem duplicada na thread.

### Resultado operacional
- Se o tecnico responder no celular, a mensagem aparece na conversa do ERP.
- Lista de conversas e thread permanecem sincronizadas.
- CRM continua recebendo evento/interacao com origem WhatsApp.
- Mensagens enviadas fora do ERP recebem indicador visual na thread:
  - origem: `Equipe (externo)`
  - badge: `via app externo`
  - meta: `| externa`
- cria/resolve conversa por telefone
- registra mensagem em `mensagens_whatsapp` (`direcao=inbound`)
- salva anexo inbound em disco quando houver base64
- registra espelho CRM (`crm_mensagens`) + interacao + evento
- aciona `ChatbotService` para resposta automatica ou escalonamento humano

### Caminho de anexos inbound
- `public/uploads/central_mensagens/{telefone}/{tipo}/...`

## Atualizacao tecnica 19/03/2026 (estabilizacao de operacao)

### 1) Stream SSE com protecao anti-loop (MIME incorreto)
- Frontend passou a respeitar `CM_CFG.enableSse`.
- Se o endpoint de stream responder com tipo invalido (ex.: `text/html`), o modulo:
  - desativa SSE temporariamente na sessao (`sessionStorage`),
  - mantem polling incremental ativo,
  - evita spam de reconexao e erro repetido no console.

### 2) Reconciliacao incremental quando o webhook atrasa
- Em `GET /atendimento-whatsapp/conversa/{id}/novas`, quando nao ha mensagens novas e existe `after_id`, o controller executa uma sincronizacao forcada curta (`syncInboundQueue(..., true)`) e consulta novamente.
- Isso reduz cenarios de "mensagem enviada fora do ERP nao apareceu ainda" em conversas ativas.

### 3) Blindagem de render na thread
- Renderer da mensagem usa fallback defensivo para payload (`payloadFn`) com escopo local e global.
- Objetivo: eliminar quebra de abertura de conversa por erro `messagePayload is not defined` em cenarios de cache/versao antiga.

### 4) UX mobile/offcanvas
- Gatilhos mobile de WhatsApp OS/Contexto (`cm-mobile-list-trigger` e `cm-mobile-context-trigger`) ganharam fallback JS para `bootstrap.Offcanvas.getOrCreateInstance(...).show()`.
- Mantem funcionamento em viewport reduzida mesmo quando o `data-bs-toggle` nao dispara por re-render dinâmico.

### 5) Envio com timeout defensivo no frontend
- `POST /atendimento-whatsapp/enviar` no cliente passou a usar timeout de 16s.
- Evita spinner preso indefinidamente quando o gateway demora/instabiliza.
- Em timeout, o usuario recebe alerta SweetAlert2 com orientacao de reenvio.

