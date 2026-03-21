# Registro de Correcao: Alinhamento do Webhook Inbound com Host/Porta do ERP

**Data:** 17/03/2026  
**Modulo:** WhatsApp Gateway Local + Webhook ERP

## Problema
O gateway Node estava ativo, mas com configuracao de origem/webhook em host diferente do ERP ativo, causando falha no inbound.

## Correcao aplicada
- Ajustado `whatsapp-api/.env` para usar:
  - `ERP_WEBHOOK_URL=http://localhost:8084/webhooks/whatsapp`
  - `ERP_ORIGIN` incluindo `http://localhost:8084`
- Mantido `ERP_WEBHOOK_TOKEN` alinhado ao token salvo em `configuracoes.whatsapp_webhook_token`.
- Reiniciado o gateway Node para recarregar variaveis.
- Ajustado `CHROME_EXECUTABLE_PATH` para um binario valido no host local:
  - `C:\Program Files\Google\Chrome\Application\chrome.exe`

## Validacao executada
1. `GET /status` do gateway com `X-ERP-Origin: http://localhost:8084` retornando sucesso.  
2. POST de inbound simulado em `http://localhost:8084/webhooks/whatsapp` com `X-Webhook-Token` valido.  
3. Confirmada persistencia em:
   - `whatsapp_inbound` (`processado=1`)
   - `mensagens_whatsapp` (`direcao=inbound`)
   - atualizacao de `conversas_whatsapp` (`nao_lidas` incrementado)

## Resultado
O fluxo inbound passou a ficar consistente com o ERP rodando em `:8084`, pronto para exibicao na Central de Mensagens.

