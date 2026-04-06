# Assistencia Mobile (PWA)

Frontend mobile paralelo do ERP, construido em Next.js (App Router), consumindo a API interna `api/v1` do CodeIgniter 4.

## Versao atual do app

- App Mobile/PWA: `0.4.2`
- ERP minimo compativel: `2.11.5`
- Politica oficial: `../documentacao/12-app-mobile-pwa/09-versionamento-e-releases/politica-de-versoes.md`
- Hub documental exclusivo: `../documentacao/12-app-mobile-pwa/README.md`
- Versao visivel no produto: login e navegacao autenticada
- Selecao rica de equipamento na OS: foto de perfil + tipo/marca + modelo/cor + serie/IMEI

## Requisitos

- Node.js 20+
- ERP CI4 ativo (local ou VPS)

## Setup rapido

1. Copie `.env.example` para `.env.local`.
2. Ajuste:
   - `NEXT_PUBLIC_ERP_API_BASE_URL` (padrao recomendado: `/api/v1`)
   - `NEXT_PUBLIC_ERP_WEB_BASE_URL` (origem web do ERP usada nos rewrites server-side)
   - `NEXT_PUBLIC_APP_BASE_PATH` (ex.: `/atendimento-mobile-app` na VPS)
   - `NEXT_PUBLIC_VAPID_PUBLIC_KEY` (quando push real estiver ativo)
3. Instale dependencias:
   - `npm install`
4. Rode em desenvolvimento:
   - `npm run dev`

## Integracao de API sem CORS (padrao recomendado)

- O projeto usa rewrite server-side em `next.config.mjs` para proxiar:
  - `/api/v1/*` -> `${NEXT_PUBLIC_ERP_WEB_BASE_URL}/api/v1/*`
- Com isso, o app mobile pode rodar em porta separada (ex.: `3100`) sem precisar liberar CORS no ERP para o MVP.
- Em VPS com subdominio dedicado do app (ex.: `app.jovemtech.eco.br`), o recomendado e publicar na raiz do dominio com `NEXT_PUBLIC_APP_BASE_PATH=` vazio.
- Nesse cenario, `NEXT_PUBLIC_ERP_WEB_BASE_URL` deve apontar para o dominio canonico HTTPS do ERP (ex.: `https://sistema.jovemtech.eco.br`) para que os rewrites `/api/v1/*` nao caiam no vhost errado do Nginx.
- Em VPS sem subdominio dedicado, a alternativa e publicar em subrota (ex.: `/atendimento-mobile-app`) usando `NEXT_PUBLIC_APP_BASE_PATH=/atendimento-mobile-app`.
- O `sw.js` do app deve pre-cachear apenas assets estaveis e respostas `200 OK`, sem tentar armazenar a raiz do app quando ela responder com redirect.

## PM2 em producao

- Arquivo pronto: `ecosystem.config.cjs`
- Processo: `assistencia-mobile-pwa`
- Porta padrao: `3100`

## Requisitos para aparecer o botao de instalar

- Origem segura obrigatoria:
  - `https://seu-dominio` (ou `http://localhost` somente para dev local).
- Em IP publico com `http` (ex.: `http://161.97.93.120`) o navegador nao exibe instalacao de PWA.
- iOS Safari nao mostra banner automatico; instalacao e manual em:
  - Compartilhar -> Adicionar a Tela de Inicio.
- O login exibe um card de instalacao assistida (`PwaInstallCard`) para:
  - abrir `beforeinstallprompt` via botao quando disponivel;
  - orientar instalacao manual quando o banner nao aparecer.

## Push no iPhone (iOS)

- O pedido de permissao de notificacao so aparece no iPhone quando:
  - iOS 16.4 ou superior;
  - o app foi instalado na Tela de Inicio;
  - o app foi aberto pela Tela de Inicio (modo standalone/PWA);
  - o dominio esta em HTTPS valido.
- O modulo de notificacoes agora mostra diagnostico de bloqueio (HTTPS, suporte, iOS sem standalone e chave VAPID ausente).
- O modulo de notificacoes tambem disponibiliza teste local para validar permissao e exibicao da notificacao no proprio iPhone.
- `NEXT_PUBLIC_VAPID_PUBLIC_KEY` deve estar preenchida para registrar subscription de push.

## Telas MVP

- `/login`
- `/conversas`
- `/conversas/[id]`
- `/os`
- `/os/[id]`
- `/notificacoes`
