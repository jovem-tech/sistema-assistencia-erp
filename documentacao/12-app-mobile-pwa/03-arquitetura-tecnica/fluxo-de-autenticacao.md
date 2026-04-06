# Fluxo de Autenticacao

Atualizado em 04/04/2026.

## Sequencia

1. Usuario abre `/login`.
2. App envia `POST /api/v1/auth/login`.
3. Backend valida permissao e gera token em `mobile_api_tokens`.
4. App salva sessao local.
5. Usuario e redirecionado para `/conversas`.

## Token

- tipo: bearer token hashado no backend
- armazenamento: sessao local do app
- uso: header `Authorization: Bearer ...`
- SSE: fallback por query string `access_token`

## Endpoints

- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`

## Regras

- o app nao cria usuarios paralelos
- o usuario mobile e o mesmo usuario do ERP
- a permissao continua sendo aplicada pelo backend CodeIgniter
