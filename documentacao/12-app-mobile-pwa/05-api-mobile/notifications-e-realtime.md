# API Mobile - Notifications e Realtime

Atualizado em 04/04/2026.

## Notificacoes

### `GET /api/v1/notifications`

- lista avisos do usuario autenticado
- inclui bloco `whatsapp_connection` para diagnostico do canal WhatsApp vigente no ERP:
  - `enabled`
  - `provider`
  - `provider_label`
  - `ok`
  - `status_code`
  - `failure_type`
  - `message`
  - `checked_at`
  - `last_check_status`
  - `last_check_message`
  - `last_check_at`

### `POST /api/v1/notifications`

- cria aviso mobile no backend

### `PUT|PATCH /api/v1/notifications/{id}/read`

- marca um aviso como lido

### `PUT|PATCH /api/v1/notifications/read-all`

- marca toda a fila como lida

## Push subscriptions

### `GET /api/v1/notifications/subscriptions`

- lista subscriptions registradas

### `POST /api/v1/notifications/subscriptions`

- registra subscription web push

### `DELETE /api/v1/notifications/subscriptions/{id}`

- remove subscription

## Tempo real

### `GET /api/v1/realtime/stream`

Objetivo:

- SSE para novas mensagens e deltas operacionais

Parametros:

- `conversa_id`
- `after_message_id`
- `access_token` quando necessario em `EventSource`
