# Deploy em Producao (VPS Linux)

Atualizado em 03/04/2026.

Este guia resume a entrada em producao do ERP com gateway WhatsApp Node em VPS Linux.

## Ultima implantacao registrada

- Data: `03/04/2026`
- Release: `v2.11.1`
- Metodo: patch seletivo via `tar.gz` + manifesto explicito de arquivos + backup pre-deploy dos alvos + validacao HTTP/PM2/gateway na propria VPS
- Evidencia detalhada: `documentacao/10-deploy/2026-04-03-atualizacao-vps-release-v2.11.0.md`

## Resumo da implantacao atual

- O checkout Git remoto continuou fora do caminho critico: a publicacao de `03/04/2026` usou pacote seletivo com manifesto explicito, sem `delete`, preservando `.env`, `public/uploads/`, `writable/` e `whatsapp-api/`.
- A VPS foi alinhada ao patch `v2.11.1`, adicionando envio real de Web Push para o app mobile/PWA.
- A migration `CreateMobilePwaInfrastructure` foi aplicada com sucesso (batch `23`).
- O app mobile `mobile-app` foi compilado em producao e iniciado via PM2 como `assistencia-mobile-pwa`.
- O Nginx passou a publicar o app mobile em `/atendimento-mobile-app/*` com proxy interno para `127.0.0.1:3100`.
- Validacoes operacionais apos a publicacao:
  - `GET /login` -> `200`
  - `POST /api/v1/auth/login` (payload vazio) -> `422` esperado de validacao
  - `GET /atendimento-mobile-app/login` -> `200`
  - `php8.3-fpm` -> `active`
  - `nginx` -> `active`
  - `pm2 status assistencia-mobile-pwa` -> `online`
  - `pm2 status whatsapp-gateway` -> `online`
  - `GET /status` do gateway com `X-Api-Token` e `X-ERP-Origin` validos -> `200`
- Artefatos operacionais gerados em `/root/deploy_patch_20260403_v2.11.0/`:
  - `sistema_hml_patch_20260403_v2.11.0.tar.gz`
  - `sync_manifest_20260403_v2.11.0.txt`
  - `predeploy_files_20260403_v2.11.0.tar.gz`
  - `mobile_pwa_url.txt`

## Observacao operacional da release v2.10.11

- Esta publicacao nao exigiu dump SQL dedicado, porque o pacote alterou controller, frontend, configuracao de release e documentacao.
- O backup remoto da rodada ficou concentrado em `predeploy_files_20260402_v2.10.11.tar.gz`, suficiente para rollback dos arquivos sobrepostos pela release.

## Observacao critica sobre assets estaticos

Em deploy por `rsync`, use exclusao apenas para o vendor PHP da raiz:

```bash
--exclude='/vendor/'
```

Nao use:

```bash
--exclude='vendor/'
```

porque isso tambem remove `public/assets/vendor`, onde ficam Bootstrap, jQuery, DataTables, Select2, SweetAlert2, Chart.js e demais bibliotecas do frontend.

## Guia oficial completo

Para procedimento completo (do zero, com troubleshooting real e validacoes operacionais), use:

- `manual-tecnico-oficial-vps-ubuntu-24-ci4.md`
- `atualizacao-vps-sem-downtime.md`
- `scripts/install_erp.sh`
- `agente-autonomo-devops-engenharia-fullstack.md`

## 1. Requisitos

- Ubuntu 22.04+ ou Debian 12+
- PHP 8.2+
- MySQL/MariaDB
- Apache ou Nginx com HTTPS
- Node.js 20+
- PM2

## 2. ERP (CodeIgniter)

1. Publicar codigo do ERP.
2. Configurar `.env` de producao (URL, banco, seguranca).
3. Executar migrations:

```bash
php spark migrate
```

4. Garantir escrita em:
- `writable/`
- `public/uploads/`

## 3. Gateway WhatsApp (Node)

Pasta recomendada:

`/sistema/whatsapp-api`

### 3.1 Instalacao automatica

```bash
cd /sistema/whatsapp-api
chmod +x install-whatsapp-api.sh
./install-whatsapp-api.sh /sistema/whatsapp-api whatsapp-gateway
```

Opcao recomendada (instalador unificado ERP + opcao de gateway):

```bash
cd /sistema
chmod +x scripts/install-vps.sh
./scripts/install-vps.sh /sistema /sistema/whatsapp-api whatsapp-gateway
```

### 3.2 Variaveis obrigatorias (`.env`)

```env
NODE_ENV=production
HOST=127.0.0.1
PORT=3001
API_TOKEN=TOKEN_FORTE_AQUI
ERP_ORIGIN=https://erp.seudominio.com,https://crm.seudominio.com
WHATSAPP_SESSION_PATH=./.wwebjs_auth
SESSION_PATH=./.wwebjs_auth
WHATSAPP_CLIENT_ID=erp-gateway-prod
LOGS_DIR=./logs
```

Token forte:

```bash
openssl rand -hex 32
```

### 3.3 Operacao PM2

```bash
pm2 status whatsapp-gateway
pm2 logs whatsapp-gateway
pm2 restart whatsapp-gateway
pm2 save
```

## 4. Configuracao no ERP

Em `Configuracoes -> Integracoes WhatsApp`:

1. Definir `Canal Direto`:
   - `api_whats_linux` para VPS
2. Preencher:
   - `whatsapp_linux_node_url`
   - `whatsapp_linux_node_token`
   - `whatsapp_linux_node_origin`
   - `whatsapp_linux_node_timeout`
3. Salvar.
4. Rodar:
   - `Testar conexao`
   - `Enviar mensagem de teste`
   - `Gerenciar` (QR/status/restart)

### 4.1 Valores obrigatorios na VPS atual

- `whatsapp_direct_provider = api_whats_linux`
- `whatsapp_linux_node_url = http://127.0.0.1:3001`
- `whatsapp_linux_node_origin = http://161.97.93.120`
- `whatsapp_linux_node_token = mesmo valor de API_TOKEN do arquivo /var/www/sistema-hml/whatsapp-api/.env`

Se algum restore de banco sobrescrever essas chaves com valores de desenvolvimento (`api_whats_local` ou `localhost`), o gateway volta a falhar com `Origin not allowed by ERP_ORIGIN`.

## 5. Compatibilidade com desenvolvimento local

No Windows/XAMPP, manter:
- provider direto `api_whats_local`
- chaves `whatsapp_local_node_*`

No Linux/producao, usar:
- provider direto `api_whats_linux`
- chaves `whatsapp_linux_node_*`

O ERP continua usando a mesma camada (`MensageriaService`) sem alterar regra de negocio.

## 5.1 Push mobile (VAPID) - obrigatorio para notificacao no celular

No `.env` do ERP em producao, definir:

```env
MOBILE_VAPID_PUBLIC_KEY=chave_publica_vapid
MOBILE_VAPID_PRIVATE_KEY=chave_privada_vapid
MOBILE_VAPID_SUBJECT=mailto:suporte@jovemtech.eco.br
```

No `.env.production` do `mobile-app`, manter:

```env
NEXT_PUBLIC_VAPID_PUBLIC_KEY=mesma_chave_publica_vapid
```

Observacoes:

- sem `MOBILE_VAPID_PRIVATE_KEY`, a subscription e salva no ERP, mas o push real nao e entregue;
- subscriptions invalidadas pelo provedor sao desativadas automaticamente (`mobile_push_subscriptions.ativo = 0`);
- no iPhone, push so funciona no app instalado na Tela de Inicio (standalone, iOS 16.4+).

## 6. Checklist de seguranca

- `API_TOKEN` forte e nao vazio
- `ERP_ORIGIN` restrito aos dominios do ERP/CRM
- porta do gateway sem exposicao publica direta
- HTTPS ativo no ERP
- logs ativos:
  - ERP: tabelas de mensageria
  - gateway: `whatsapp-api/logs/gateway.log`

## 7. Troubleshooting

- `gateway_unreachable`: Node parado, URL incorreta ou bloqueio de rede
- `unauthorized`: token ERP diferente de `API_TOKEN`
- `forbidden_origin`: origem ERP nao incluida em `ERP_ORIGIN`
- `not_ready`: sessao WhatsApp sem autenticacao (QR pendente)
- `Cannot find module 'dotenv'`: dependencias do gateway nao instaladas. Corrija com:

```bash
cd /var/www/sistema-hml/whatsapp-api
npm install --omit=dev
pm2 restart whatsapp-gateway
pm2 logs whatsapp-gateway
```
