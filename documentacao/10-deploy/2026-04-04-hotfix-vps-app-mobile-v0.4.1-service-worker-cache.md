# Hotfix VPS - App Mobile/PWA v0.4.1 (service worker e cache)

Data: 04/04/2026

## Objetivo

Aplicar na VPS um hotfix de PWA para corrigir falha de cache do `sw.js` em `app.jovemtech.eco.br`.

## Escopo permitido

- codigo do `mobile-app`
- novo build do app
- documentacao de release e deploy

## Escopo preservado

- banco de dados
- `public/uploads/`
- fotos de desenvolvimento
- `.env` e artefatos operacionais

## Resultado esperado

- `sw.js` publicado sem tentar pre-cachear a raiz com redirect;
- navegacoes do app sem falso `503 Service Unavailable` causado pelo service worker;
- versao visivel do app atualizada para `0.4.1`.
