# 2026-03 - Evolucao por fases da Central de Atendimento Inteligente

## Escopo desta evolucao
Evoluir o modulo `/central-mensagens` de inbox simples para base de atendimento 24h integrado com ERP + CRM, com rastreabilidade de conversa/OS/cliente e automacao progressiva.

## Analise tecnica consolidada

### Gargalos identificados no estado inicial
1. Envio manual isolado sem contexto completo de OS e CRM.
2. Falta de fila operacional com responsavel, prioridade e indicador de SLA.
3. Chatbot sem base administrativa (intencoes, FAQ, regras ERP, logs).
4. Automacoes de OS acopladas em regras fixas.
5. Falta de visao gerencial de atendimento (metricas de volume e resposta).

### Direcao adotada
- Manter o ERP como origem operacional dos eventos.
- Centralizar mensagens em `conversas_whatsapp` + `mensagens_whatsapp`.
- Converter mensagens relevantes em eventos/interacoes/follow-ups de CRM.
- Ativar automacao por fases, com fallback humano sempre disponivel.

## Fases implementadas nesta etapa

### Fase 1 - Inbox operacional
- Controller estabilizado com thread, listagem, envio, sincronizacao inbound e vinculo com OS.
- UI principal em 3 colunas com filtros, thread e painel de contexto.
- Persistencia de inbound/outbound com espelho CRM.

### Fase 2 - Contexto ERP + CRM
- Contexto da conversa consolidado em `buildConversaContext()`:
  - cliente
  - OS do cliente
  - OS vinculadas
  - documentos da OS
  - follow-ups pendentes
  - metadados da conversa (status, responsavel, tags, automacao)
- Atualizacao de metadados em tempo real via endpoint `/central-mensagens/atualizar-meta`.
- Badges operacionais na lista (bot, aguardando humano, SLA, prioridade, OS vinculada).

### Fase 3 - Chatbot
- Tela administrativa `/central-mensagens/chatbot`.
- CRUD funcional de intencoes e regras ERP.
- Logs de chatbot consolidados para auditoria.
- Threshold de confianca configuravel por parametro (`central_mensagens_bot_confidence_threshold`).

### Fase 4 - Fluxos e base de respostas
- Tela `/central-mensagens/faq` para base de conhecimento.
- Tela `/central-mensagens/respostas-rapidas` para respostas operacionais.
- Tela `/central-mensagens/fluxos` para fluxo de atendimento por tipo.
- Motor de regras ERP dinamico em `CrmService::runErpRules()` com acoes `template`, `followup` e `crm_evento`.

### Fase 5 - Filas e metricas
- Tela `/central-mensagens/filas` para distribuicao e priorizacao.
- Tela `/central-mensagens/metricas` com indicadores de volume/SLA/automacao.
- Acao de consolidacao diaria em `mensageria_metricas_diarias`.
- Tela `/central-mensagens/configuracoes` com parametros da central.

## Modelagem aplicada

### Nucleo de conversa
- `conversas_whatsapp`
- `mensagens_whatsapp`
- `conversa_os`
- `conversa_tags`

### Nucleo de automacao/chatbot
- `chatbot_intencoes`
- `chatbot_faq`
- `chatbot_fluxos`
- `chatbot_logs`
- `chatbot_regras_erp`

### Nucleo CRM integrado
- `crm_eventos`
- `crm_interacoes`
- `crm_followups`
- `crm_mensagens`

### Nucleo gerencial
- `mensageria_metricas_diarias`

## Arquitetura de atendimento (5 camadas)
1. Inbox e conversa (operacao humana).
2. Motor automatico (intencao + FAQ + regras).
3. Integracao ERP (status, OS, documentos).
4. CRM (timeline, follow-up, eventos).
5. Gestao (metricas e fila).

## Avanco adicional desta iteracao
- Inbound com midia no gateway local (`media_base64`, `media_mime_type`, `media_filename`).
- Persistencia de anexos inbound no ERP em `public/uploads/whatsapp/inbound/YYYY/MM`.
- Thread da Central atualizada para renderizar preview de imagem e acesso direto a PDF/anexo.
- Thread atualizada para reforcar atendimento bidirecional: origem visual da mensagem (Cliente/Equipe/Bot), botao de resposta contextual em mensagens inbound e envio rapido via teclado.
- Lista de conversas com preview da ultima mensagem indicando origem (`Cliente`, `Voce`, `Bot`) e abertura automatica da primeira conversa para reduzir cliques no atendimento.
- Endpoint de conversa passou a retornar `unread_before`, permitindo marcador visual de "Mensagens nao lidas" no historico.

## Rotas adicionadas/organizadas
- `GET /central-mensagens/chatbot`
- `GET /central-mensagens/faq`
- `GET /central-mensagens/respostas-rapidas`
- `GET /central-mensagens/fluxos`
- `GET /central-mensagens/filas`
- `GET /central-mensagens/metricas`
- `GET /central-mensagens/configuracoes`
- `POST /central-mensagens/chatbot/intencao/salvar`
- `POST /central-mensagens/chatbot/intencao/toggle/{id}`
- `POST /central-mensagens/chatbot/regra/salvar`
- `POST /central-mensagens/chatbot/regra/toggle/{id}`
- `POST /central-mensagens/faq/salvar`
- `POST /central-mensagens/faq/toggle/{id}`
- `POST /central-mensagens/respostas-rapidas/salvar`
- `POST /central-mensagens/respostas-rapidas/toggle/{id}`
- `POST /central-mensagens/fluxos/salvar`
- `POST /central-mensagens/fluxos/toggle/{id}`
- `POST /central-mensagens/filas/atualizar`
- `POST /central-mensagens/metricas/consolidar-diario`
- `POST /central-mensagens/configuracoes/salvar`
- `POST /webhooks/whatsapp` (inbound para fila e Central)

## OpenDocPage atualizado
Mapeamentos adicionados em `public/assets/js/scripts.js`:
- `central-mensagens-chatbot`
- `central-mensagens-metricas`
- `central-mensagens-filas`
- `central-mensagens-faq`
- `central-mensagens-fluxos`
- `central-mensagens-respostas`
- `central-mensagens-config`

## Arquivos de codigo impactados
- `app/Controllers/CentralMensagens.php`
- `app/Controllers/WhatsAppWebhook.php`
- `app/Config/Routes.php`
- `app/Services/CentralMensagensService.php`
- `app/Services/ChatbotService.php`
- `app/Services/CrmService.php`
- `app/Views/layouts/sidebar.php`
- `app/Views/central_mensagens/index.php`
- `app/Views/central_mensagens/_menu.php`
- `app/Views/central_mensagens/chatbot.php`
- `app/Views/central_mensagens/faq.php`
- `app/Views/central_mensagens/respostas_rapidas.php`
- `app/Views/central_mensagens/fluxos.php`
- `app/Views/central_mensagens/filas.php`
- `app/Views/central_mensagens/metricas.php`
- `app/Views/central_mensagens/configuracoes.php`
- `public/assets/js/scripts.js`
- `whatsapp-api/server.js`
- `whatsapp-api/.env.example`
