# Modulo: Atendimento WhatsApp (Unificado)

Atualizado em 20/03/2026.

## Objetivo
Concentrar todo o atendimento WhatsApp dentro do ERP, sem iframe e sem modulo externo dedicado.

## Arquitetura oficial
Fluxo:
`Controller -> WhatsAppService -> MensageriaService -> Provider`

Providers diretos suportados:
- `menuia`
- `api_whats_local` (gateway Node local Windows)
- `api_whats_linux` (gateway Node em VPS Linux)
- `webhook` (integracao custom)

Provider de massa (futuro):
- `meta_oficial`

## Modulo unico no ERP
- Inbox principal: `/atendimento-whatsapp` (alias de `/central-mensagens`)
- Sem embed externo, sem iframe e sem rota `/whaticket`
- Operacao e contexto CRM/OS no mesmo modulo interno

## Funcionalidades principais
- conversa em tempo real com polling/SSE e fallback seguro
- envio de texto, imagem, audio, video e PDF
- vinculacao de conversa com OS
- exibicao de contexto de cliente e OS na thread
- respostas rapidas
- chatbot/automacao (fluxos, FAQ, regras ERP)
- metricas operacionais diarias
- fila e atribuicao de responsavel

## Configuracao
Caminho:
- `Configuracoes -> Integracoes`

Configuracoes usadas:
- `whatsapp_enabled`
- `whatsapp_direct_provider`
- `whatsapp_bulk_provider`
- `whatsapp_test_phone`
- `whatsapp_webhook_token`
- `whatsapp_local_node_url`
- `whatsapp_local_node_token`
- `whatsapp_local_node_origin`
- `whatsapp_local_node_timeout`
- `whatsapp_linux_node_url`
- `whatsapp_linux_node_token`
- `whatsapp_linux_node_origin`
- `whatsapp_linux_node_timeout`
- `whatsapp_webhook_url`
- `whatsapp_webhook_method`
- `whatsapp_webhook_headers`
- `whatsapp_webhook_payload`

## Gateway APIs reaproveitadas
Mantemos o gateway Node como servico de transporte, com integracao ao ERP:
- `GET /status`
- `GET /qr`
- `POST /restart`
- `POST /logout`
- `POST /create-message`
- `POST /self-check-inbound`

## Inbound e seguranca
- webhook ERP: `POST /webhooks/whatsapp`
- token inbound via `X-Webhook-Token` (ou `?token=`)
- validacao de origem ERP + token no gateway
- logs de envio/erro no ERP e no gateway

## Integracao com CRM e OS
- inbound/outbound geram eventos e interacoes CRM
- conversa pode ser vinculada a OS principal e multiplas OS relacionadas
- atualizacao de status da conversa, prioridade e responsavel
- rastreabilidade completa em:
  - `conversas_whatsapp`
  - `mensagens_whatsapp`
  - `crm_mensagens`
  - `crm_eventos`
  - `crm_interacoes`

## Observacao de descontinuidade
O legado WhaTicket/Whaticket foi removido do ERP:
- rotas removidas: `/whaticket`, `/whaticket/status`, `/configuracoes/whatsapp/whaticket-local-start`
- configuracoes legadas removidas por migration
- provider legado normalizado para `api_whats_local`

## Referencias
- [Central de Mensagens](central-de-mensagens.md)
- [Rotas da API](../05-api/rotas.md)
- [Configuracao do Sistema](../02-manual-administrador/configuracao-do-sistema.md)
- [Tabelas principais](../04-banco-de-dados/tabelas-principais.md)

