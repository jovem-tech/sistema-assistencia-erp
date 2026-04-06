# API Mobile - Rotas e Contratos

## Base

Todas as rotas mobile oficiais ficam sob `/api/v1`.

## Grupos principais

- `auth`
- `users`
- `clients`
- `orders`
- `conversations`
- `messages`
- `notifications`
- `realtime`

## Padrao de resposta

```json
{
  "status": "success|error",
  "data": {},
  "error": null,
  "meta": {
    "timestamp": "ISO8601",
    "request_id": "req_xxx"
  }
}
```

## Regras obrigatorias

- respostas em JSON consistente;
- erros com mensagem clara e rastreavel;
- contratos retrocompativeis sempre que possivel;
- documentacao atualizada a cada extensao da API.

## Documentos detalhados

- `auth.md`
- `users.md`
- `clients-e-equipamentos.md`
- `orders.md`
- `conversations-e-messages.md`
- `notifications-e-realtime.md`

## Endpoints criticos atuais

- `POST /api/v1/auth/login`
- `GET /api/v1/orders`
- `GET /api/v1/orders/meta`
- `POST /api/v1/orders`
- `GET /api/v1/conversations`
- `GET /api/v1/notifications`
- `POST /api/v1/notifications/subscriptions`
- `GET /api/v1/realtime/stream`

Nota de contrato atual:

- `GET /api/v1/orders/meta` inclui `reported_defects` (itens de Defeitos Relatados do ERP) para alimentar o modal rapido do campo `Relato do cliente` no app.
