# Ambiente Local

## Requisitos

- Node.js 20+
- ERP local ativo
- `.env.local` configurado

## Fluxo local recomendado

1. instalar dependencias com `npm install`
2. copiar `.env.example` para `.env.local`
3. ajustar URLs do ERP
4. iniciar com `npm run dev`
5. se quiser preservar cache local para investigacao, iniciar com `KEEP_NEXT_CACHE=1 npm run dev`
6. se houver erro de chunks (`/_next/static/* 404`), executar recuperacao rapida:
   - `npm run dev:recover`

## Cuidados

- nao misturar `next build` com `next dev` aberto sem necessidade;
- quando o cache `.next` corromper, limpar e reiniciar o dev server;
- validar as telas principais apos mudancas amplas.
- em ambiente local, o app nao registra Service Worker (e limpa registros antigos) para evitar 404 de chunks estaticos apos hot reload/redirecionamentos.
- o script `mobile-app/scripts/dev-server.cjs` limpa `.next` no boot para evitar erro de chunks orfaos (`Cannot find module './xxx.js'`).
- para recuperar ambiente quebrado sem acao manual longa, usar `mobile-app/scripts/recover-dev.ps1` (encerrar processos node do app, limpar `.next` e subir `npm run dev`).
- o recovery agora tambem encerra processo node preso na porta `3000` e valida readiness por healthcheck em `http://localhost:3000/login`.
