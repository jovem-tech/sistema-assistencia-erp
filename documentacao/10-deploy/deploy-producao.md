# Deploy em Producao (VPS Linux)

Atualizado em 27/03/2026.

Este guia resume a entrada em producao do ERP com gateway WhatsApp Node em VPS Linux.

## Ultima implantacao registrada

- Data: `27/03/2026`
- Release: `v2.5.9`
- Commit: `c2f1fb9`
- Evidencia detalhada: `documentacao/10-deploy/2026-03-27-atualizacao-vps-release-v2.5.9.md`

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

## 5. Compatibilidade com desenvolvimento local

No Windows/XAMPP, manter:
- provider direto `api_whats_local`
- chaves `whatsapp_local_node_*`

No Linux/producao, usar:
- provider direto `api_whats_linux`
- chaves `whatsapp_linux_node_*`

O ERP continua usando a mesma camada (`MensageriaService`) sem alterar regra de negocio.

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
