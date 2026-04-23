# Stack Tecnologica

Atualizado em 03/04/2026 (release 2.11.0).

## Backend

| Tecnologia | Versao | Uso |
|------------|--------|-----|
| PHP | 8.2 | Linguagem principal |
| CodeIgniter 4 | 4.x | Framework MVC |
| MySQL / MariaDB | 10.x+ | Banco de dados unico do ERP |
| Node.js | 18+ | Gateway local/Linux de WhatsApp |
| whatsapp-web.js | 1.23+ | Sessao WhatsApp Web no gateway |
| PM2 | 5+ | Supervisao do processo gateway |

### Padroes CI4 utilizados
- Models com `allowedFields`, `validationRules`, `beforeInsert/beforeUpdate`.
- Controllers com RBAC (`requirePermission`, `can`).
- Filters:
  - `AuthFilter` (sessao web)
  - `PermissionFilter` (RBAC web)
  - `ApiTokenAuthFilter` (API mobile por bearer token)
- Query Builder e Models para consultas operacionais.

## Frontend Web (ERP atual)

| Tecnologia | Versao | Uso |
|------------|--------|-----|
| HTML5 | - | Estrutura semantica |
| Bootstrap | 5.3.3 | Grid e componentes |
| Bootstrap Icons | 1.11.3 | Iconografia |
| jQuery | 3.7.1 | DOM e AJAX |
| Select2 | 4.1.0 | Selects avancados |
| DataTables | 1.13.7 | Tabelas server-side |
| Chart.js | 4.4.0 | Graficos |
| Cropper.js | 1.6.1 | Edicao de imagem |

## Frontend Mobile (novo modulo paralelo)

| Tecnologia | Versao | Uso |
|------------|--------|-----|
| React | 18.x | UI do app mobile |
| Next.js | 14.x | App Router do PWA |
| Service Worker | browser API | Push notifications e cache |

## API Mobile (v2.11.0)

- base versionada: `/api/v1`
- auth por bearer token hashado em `mobile_api_tokens`
- resposta JSON padronizada:
  - `status`
  - `data`
  - `error`
  - `meta.timestamp`
  - `meta.request_id`
- tempo real:
  - SSE (`/api/v1/realtime/stream`)
  - fallback polling incremental no app mobile

## Infraestrutura

| Item | Detalhe |
|------|---------|
| Servidor web ERP | Apache (XAMPP / VPS) |
| Uploads ERP | `/public/uploads/` |
| Assets ERP | `/public/assets/` |
| Fonte do app mobile | `/mobile-app/` |
| Bootstrap estatico de acesso mobile | `/public/mobile-app/index.html` |

## Mensageria WhatsApp (arquitetura atual)

- Menuia API: canal direto 1:1 operacional.
- Gateway local/Linux Node.js: canal direto alternativo.
- Meta Oficial (futuro CRM): canal reservado para massa/campanhas.

