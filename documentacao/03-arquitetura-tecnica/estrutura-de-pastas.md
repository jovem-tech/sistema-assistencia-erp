# Estrutura de Pastas (resumo tecnico)

Atualizado em 22/03/2026.

```text
sistema-assistencia/
|-- app/
|   |-- Config/
|   |   `-- SystemRelease.php
|   |
|   |-- Controllers/
|   |   |-- Os.php
|   |   |-- Configuracoes.php
|   |   |-- WhatsAppWebhook.php
|   |   |-- Crm.php
|   |   |-- Contatos.php
|   |   `-- CentralMensagens.php
|   |
|   |-- Services/
|   |   |-- OsStatusFlowService.php
|   |   |-- OsPdfService.php
|   |   |-- WhatsAppService.php
|   |   |-- MensageriaService.php
|   |   |-- CrmService.php
|   |   |-- CentralMensagensService.php
|   |   |-- ChatbotService.php
|   |   |-- IntencaoService.php
|   |   |-- MetricasMensageriaService.php
|   |   `-- WhatsApp/
|   |       |-- WhatsAppProviderInterface.php
|   |       |-- BulkMessageProviderInterface.php
|   |       |-- MenuiaProvider.php
|   |       |-- LocalGatewayProvider.php
|   |       |-- WebhookProvider.php
|   |       |-- MetaOfficialProvider.php
|   |       |-- NullProvider.php
|   |       `-- NullBulkProvider.php
|   |
|   |-- Models/
|   |   |-- OsStatusModel.php
|   |   |-- OsStatusTransicaoModel.php
|   |   |-- OsStatusHistoricoModel.php
|   |   |-- MensagemWhatsappModel.php
|   |   |-- WhatsappInboundModel.php
|   |   |-- ConversaWhatsappModel.php
|   |   |-- ContatoModel.php
|   |   |-- ConversaOsModel.php
|   |   |-- ConversaTagModel.php
|   |   |-- RespostaRapidaWhatsappModel.php
|   |   |-- ChatbotIntencaoModel.php
|   |   |-- ChatbotFaqModel.php
|   |   |-- ChatbotFluxoModel.php
|   |   |-- ChatbotLogModel.php
|   |   |-- ChatbotRegraErpModel.php
|   |   |-- MensageriaMetricaDiariaModel.php
|   |   |-- CrmEventoModel.php
|   |   |-- CrmInteracaoModel.php
|   |   |-- CrmFollowupModel.php
|   |   |-- CrmPipelineModel.php
|   |   |-- CrmPipelineEtapaModel.php
|   |   `-- CrmTagModel.php
|   |
|   |-- Database/Migrations/
|   |   |-- 2026-03-16-090000_PreCrmFoundation.php
|   |   |-- 2026-03-16-121500_AddMenuiaDirectAndWhatsappEnvios.php
|   |   |-- 2026-03-16-210500_AddLocalGatewayAndMensagensWhatsapp.php
|   |   |-- 2026-03-17-100000_AddLinuxGatewayConfig.php
|   |   |-- 2026-03-17-120000_CreateCrmAndCentralMensagens.php
|   |   |-- 2026-03-17-193000_AddCrmMensagensSeedTagsAutomacoes.php
|   |   |-- 2026-03-17-235500_AddCentralAtendimentoInteligente.php
|   |   |-- 2026-03-20-070500_CreateContatosAndLinkConversas.php
|   |   |-- 2026-03-20-091500_AddContatoLifecycleMarketingFields.php
|   |   `-- 2026-03-20-120500_AddContatoEngajamentoLifecycleWindow.php
|   |
|   `-- Views/
|       |-- crm/
|       |   |-- timeline.php
|       |   |-- interacoes.php
|       |   |-- followups.php
|       |   |-- pipeline.php
|       |   |-- campanhas.php
|       |   |-- metricas_marketing.php
|       |   `-- clientes_inativos.php
|       |-- contatos/
|       |   |-- index.php
|       |   `-- form.php
|       `-- central_mensagens/
|           |-- index.php
|           |-- chatbot.php
|           |-- faq.php
|           |-- respostas_rapidas.php
|           |-- fluxos.php
|           |-- filas.php
|           |-- metricas.php
|           |-- configuracoes.php
|           `-- _menu.php
|
|-- whatsapp-api/
|   |-- server.js
|   |-- package.json
|   |-- ecosystem.config.js
|   |-- install-whatsapp-api.sh
|   |-- .env.example
|   |-- .wwebjs_auth/
|   `-- logs/
|
|-- scripts/
|   `-- install-vps.sh
|
`-- public/uploads/
    |-- os_documentos/
    |-- acessorios/
    |-- estado_fisico/
    `-- equipamentos_perfil/
```

## Camada de mensageria
Fluxo interno:
`Controller -> WhatsAppService -> MensageriaService -> Provider`

## Observacao de layout embed
- Novo layout tecnico: `app/Views/layouts/embed.php`
- Uso principal: abrir telas de OS (`nova` e `visualizar`) em modal no dashboard, sem carregar shell completo.

## Controle de versao visual (rodape)
- A versao de release exibida no rodape vem de `app/Config/SystemRelease.php` (`$version`).
- O helper `get_system_version()` (em `app/Helpers/sistema_helper.php`) aplica fallback:
  - primeiro tenta `configuracoes.sistema_versao` (se existir)
  - se nao existir, usa `SystemRelease::$version`.

Beneficios:
- desacoplamento por provider
- troca de provider sem alterar regra de negocio
- padronizacao de logs operacionais

Providers diretos:
- `menuia`
- `api_whats_local` (ambiente local Windows)
- `api_whats_linux` (producao VPS Linux)
- `webhook` (integracao custom)

Provider de massa (futuro CRM):
- `meta_oficial`

## Camada CRM operacional
Fluxo resumido:
`Eventos da OS + WhatsApp + Chatbot -> CrmService/CentralMensagensService -> timeline, follow-ups, pipeline e metricas`

Objetivo:
- transformar evento operacional em relacionamento rastreavel
- manter cliente/OS/conversa sincronizados na mesma base

## Gateway Node (servico externo interno)
Fluxo:
`ERP (proxy PHP) -> API Node (/status,/qr,/restart,/create-message) -> whatsapp-web.js`

Caracteristicas:
- auth por token (`X-Api-Token`/Bearer)
- CORS/origem restritos por `ERP_ORIGIN`
- rate limit em endpoints sensiveis
- logs em `whatsapp-api/logs/gateway.log`
- sessao persistente em `.wwebjs_auth`
- execucao recomendada com PM2
