# Fluxo de Conversas e Tempo Real

Atualizado em 04/04/2026.

## Objetivo

Descrever a sincronizacao de conversas no app mobile/PWA.

## Lista de conversas

- a tela `/conversas` faz leitura de `GET /api/v1/conversations`
- existe refresh manual
- existe polling de seguranca a cada 12 segundos

## Detalhe da conversa

- a tela `/conversas/{id}` abre primeiro o historico consolidado
- depois tenta SSE em `GET /api/v1/realtime/stream`
- o stream usa `conversa_id` e `after_message_id`

## Fallback

Se o stream falhar:

- o app marca estado `reconectando`
- fecha o `EventSource`
- usa `GET /api/v1/messages?after_id=...`

## Envio

- resposta do operador: `POST /api/v1/messages`
- novas mensagens sao fundidas localmente sem duplicar IDs
