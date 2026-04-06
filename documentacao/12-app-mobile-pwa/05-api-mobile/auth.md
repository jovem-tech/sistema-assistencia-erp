# API Mobile - Auth

Atualizado em 04/04/2026.

## `POST /api/v1/auth/login`

Objetivo:

- autenticar usuario do ERP no app

Payload:

- `email`
- `password`
- `device_name`

Resposta principal:

- `access_token`
- `token_type`
- `expires_at`
- `user`

Erros comuns:

- credenciais invalidas
- usuario sem permissao

## `GET /api/v1/auth/me`

Objetivo:

- retornar usuario autenticado no app

## `POST /api/v1/auth/refresh`

Objetivo:

- renovar sessao/token mobile

## `POST /api/v1/auth/logout`

Objetivo:

- revogar sessao mobile atual
