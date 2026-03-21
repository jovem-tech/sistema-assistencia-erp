# Registro de Implementacao: Botao Self-check Inbound

**Data:** 17/03/2026  
**Modulo:** Configuracoes -> Integracoes WhatsApp

## Objetivo
Adicionar validacao automatica de inbound sem depender de console/terminal.

## O que foi implementado
- Novo botao `Self-check inbound` na tela de configuracoes.
- Botao com tooltip explicando finalidade (validar host/token/webhook/origem inbound).
- Novo endpoint ERP:
  - `POST /configuracoes/whatsapp/self-check-inbound`
- Novo endpoint gateway Node:
  - `POST /self-check-inbound`
- Fluxo de diagnostico em 4 etapas:
  1. consulta `GET /status` no gateway
  2. valida `gateway -> webhook ERP` via `/self-check-inbound`
  3. valida `POST` direto no webhook ERP com token
  4. valida alinhamento de origem (`ERP_ORIGIN` x `app.baseURL`)

## Comportamento tecnico
- O webhook `POST /webhooks/whatsapp` passou a aceitar `self_check=true` (ou `X-Webhook-Self-Check: 1`) sem persistir registros de inbound.
- Resultado do self-check eh exibido em `Swal.fire` com checklist por etapa.
- Tratamento de resiliencia para ambiente local:
  - fallback automatico de URL entre `localhost` e `127.0.0.1` no ERP (self-check direto) e no gateway Node (forward inbound).
  - exibicao de `URL` e `Detalhe` por etapa quando houver falha para facilitar diagnostico.

## Resultado
Diagnostico inbound centralizado no painel administrativo, com feedback visual claro e rastreavel.
