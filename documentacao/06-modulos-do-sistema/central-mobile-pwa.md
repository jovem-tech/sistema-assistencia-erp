# Modulo: Central Mobile PWA (paralelo ao ERP web)

Atualizado em 04/04/2026 (v2.11.5).

## Documentacao exclusiva do app

O modulo mobile/PWA agora possui hub documental proprio em:

- `documentacao/12-app-mobile-pwa/README.md`
- `documentacao/12-app-mobile-pwa/09-versionamento-e-releases/politica-de-versoes.md`
- `documentacao/12-app-mobile-pwa/09-versionamento-e-releases/historico-de-versoes.md`

Regra oficial:

- o ERP mantem sua linha de versao propria;
- o app mobile/PWA mantem linha de versao propria;
- toda release do app deve declarar ERP minimo compativel.

## Objetivo

Disponibilizar uma extensao mobile/PWA oficial para tecnicos e atendentes, sem substituir nem alterar a Central web existente.

Regras aplicadas:

- mesmo banco de dados do ERP;
- backend principal em CodeIgniter 4;
- frontend mobile separado em `mobile-app/` (Next.js);
- API interna versionada em `/api/v1`.

## Arquitetura aplicada

Fluxo principal:

`PWA mobile -> API /api/v1 (CI4) -> Services/Models existentes -> mesmo MySQL do ERP`

Base nova complementar (sem duplicar tabelas operacionais):

- `mobile_api_tokens`
- `mobile_push_subscriptions`
- `mobile_notifications`
- `mobile_notification_targets`
- `mobile_event_outbox`

## Entradas do modulo

- URL de acesso controlada no ERP: `/atendimento-mobile`
- destino configuravel via `configuracoes.mobile_pwa_url` (fallback `/atendimento-mobile-app/login`)
- bloqueio de desktop por User-Agent com override de preview:
  - `/atendimento-mobile?preview=1`
- publicacao recomendada na VPS:
  - app Next em PM2 (`assistencia-mobile-pwa`, porta `3100`);
  - Nginx com proxy interno em `/atendimento-mobile-app/*` para `127.0.0.1:3100`;
  - URL salva no ERP: `/atendimento-mobile-app/login`.

## API v1 criada

Auth:

- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`

Operacao:

- `GET /api/v1/users`
- `GET /api/v1/clients`
- `GET /api/v1/clients/{id}`
- `GET /api/v1/orders`
- `GET /api/v1/orders/meta`
- `GET /api/v1/orders/{id}`
- `POST /api/v1/orders`
- `PUT/PATCH /api/v1/orders/{id}`
- `GET /api/v1/conversations`
- `GET /api/v1/conversations/{id}`
- `GET /api/v1/messages`
- `POST /api/v1/messages`
- `GET /api/v1/notifications`
- `POST /api/v1/notifications`
- `PUT/PATCH /api/v1/notifications/{id}/read`
- `PUT/PATCH /api/v1/notifications/read-all`
- `GET /api/v1/notifications/subscriptions`
- `POST /api/v1/notifications/subscriptions`
- `DELETE /api/v1/notifications/subscriptions/{id}`
- `GET /api/v1/realtime/stream` (SSE + token)

Padrao de resposta:

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

## Frontend PWA separado (`mobile-app/`)

Estrutura MVP:

- `login`
- `conversas`
- `conversa detalhada + envio`
- `os`
- `os/nova` (abertura completa)
- `os detalhada + update de status/prioridade`
- `notificacoes`

Fluxo mobile de abertura de OS (paridade com ERP web):
- cliente + equipamento
- seletor rico de equipamento com foto de perfil, `tipo - marca`, `modelo - cor` e `numero de serie/IMEI`
- relato do cliente
- tecnico, prioridade e status
- datas operacionais (`entrada`, `previsao`, `conclusao`, `entrega`)
- observacoes, diagnostico e solucao aplicada
- defeitos comuns por tipo do equipamento
- acessorios e estado fisico em colecoes estruturadas
- fotos de entrada via `multipart/form-data`
- valores e garantia

PWA:

- `public/manifest.webmanifest`
- `public/sw.js`
- registro de Service Worker em runtime (`PwaBoot`)
- card de instalacao assistida no login (`PwaInstallCard`) com:
  - botao "Instalar aplicativo" quando `beforeinstallprompt` estiver disponivel;
  - fallback de orientacao manual quando o navegador nao exibir banner automatico;
  - aviso tecnico claro quando a origem estiver sem HTTPS valido;
- fluxo de push subscription pronto para integrar VAPID/Firebase
- suporte a subrota por `NEXT_PUBLIC_APP_BASE_PATH` (padrao VPS: `/atendimento-mobile-app`)
- rewrite server-side para API (`/api/v1/*`) sem dependencia de CORS no MVP
- em producao com subdominios separados (`app.*` e `sistema.*`), `NEXT_PUBLIC_ERP_WEB_BASE_URL` deve apontar para o dominio HTTPS do ERP para evitar `404` nos endpoints proxiados;
- icones PNG (`192x192`, `512x512` e `maskable`) para compatibilidade de instalacao em Android/Chrome
- cache de install do SW com tolerancia a falha pontual de asset (nao interrompe instalacao do service worker).
- hardening do fetch no SW com fallback explicito de `Response` (`503 offline`) quando rede e cache falharem, evitando erro de runtime no navegador.

Despacho push em producao:

- `MobileNotificationService` agora dispara Web Push real no momento da criacao da notificacao.
- `WebPushService` envia payload para subscriptions ativas em `mobile_push_subscriptions` usando VAPID.
- Subscriptions expiradas/invalidas sao desativadas automaticamente (`ativo = 0`) para evitar retries inuteis.
- Mensagens inbound da Central (`CentralMensagensService`) agora geram notificacoes mobile com rota direta para a conversa (`/conversas/{id}`).

## Tempo real

Estrategia atual:

- primario: SSE (`/api/v1/realtime/stream`)
- fallback: polling incremental de mensagens no frontend mobile

## Instalacao PWA (criterios reais)

- A instalacao automatica no navegador so aparece em origem segura:
  - `https://dominio-publico` (producao) ou `http://localhost` (dev).
- Em `http` por IP publico, o navegador bloqueia instalacao PWA por politica de seguranca.
- Em iOS/Safari, nao existe banner automatico; a instalacao e manual via:
  - Compartilhar -> Adicionar a Tela de Inicio.

## Push notifications no iOS (regras praticas)

- iOS 16.4+ e requisito minimo para Web Push em PWA.
- O prompt de notificacao no iPhone so pode ser solicitado quando o usuario abre o PWA pela Tela de Inicio (standalone).
- Em aba comum do Safari, o sistema nao deve tentar forcar prompt; deve orientar instalacao primeiro.
- O modulo `Notificacoes` exibe diagnostico de prontidao de push:
  - permissao atual (`default/granted/denied`);
  - bloqueio por falta de HTTPS;
  - bloqueio por versao do iOS sem suporte;
  - bloqueio por iOS fora de standalone;
  - bloqueio por chave VAPID ausente;
  - status de `Service Worker`, `PushManager` e `Notification API`.
- A variavel `NEXT_PUBLIC_VAPID_PUBLIC_KEY` e obrigatoria para criar `PushSubscription` de forma compativel com Safari iOS.
- O modulo oferece botao de `Teste local` para disparar notificacao imediata e validar exibicao no dispositivo.
- No backend ERP, as variaveis obrigatorias para envio real sao:
  - `MOBILE_VAPID_PUBLIC_KEY`
  - `MOBILE_VAPID_PRIVATE_KEY`
  - `MOBILE_VAPID_SUBJECT` (recomendado `mailto:suporte@seu-dominio`)

## Integracoes com modulos existentes

- Conversas e mensagens reutilizam `conversas_whatsapp` e `mensagens_whatsapp`.
- Envio de mensagem reutiliza `WhatsAppService::sendRaw`.
- OS utiliza `OsModel` e `generateNumeroOs`.
- Permissoes seguem RBAC existente (`grupo_permissoes`, `modulos`, `permissoes`).

## Proximos passos apos MVP

- worker de processamento de `mobile_event_outbox` para escala e retries avancados (opcional na proxima fase);
- expandir endpoints para CRM, financeiro, agenda e dashboard mobile;
- hardening de observabilidade e politicas de retry por evento.
