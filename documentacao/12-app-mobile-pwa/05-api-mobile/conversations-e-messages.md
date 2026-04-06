# API Mobile - Conversations e Messages

Atualizado em 04/04/2026.

## `GET /api/v1/conversations`

Objetivo:

- listar conversas do atendimento mobile

Uso atual:

- tela `/conversas`

## `GET /api/v1/messages`

Objetivo:

- listar mensagens de uma conversa
- servir como fallback incremental por `after_id`

Parametros comuns:

- `conversa_id`
- `after_id`
- `limit`

## `POST /api/v1/messages`

Objetivo:

- enviar nova mensagem outbound para a conversa

Payload minimo:

- `conversa_id`
- `mensagem`

## Regras do app

- a tela de detalhe usa merge por ID
- o stream em tempo real e complementar ao endpoint de mensagens
