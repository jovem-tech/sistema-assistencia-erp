# Instalacao Local (Windows + XAMPP)

Atualizado em 17/03/2026.

## 1. Requisitos

- XAMPP (PHP 8.2+)
- MySQL/MariaDB
- Node.js 20+

## 2. ERP local

1. Importar banco (`database.sql`) se aplicavel.
2. Configurar `.env` local (`app.baseURL`, banco, etc.).
3. Rodar migrations:

```bash
C:\xampp\php\php.exe spark migrate
```

4. Iniciar Apache + MySQL e abrir o host definido em `app.baseURL` (ex.: `http://localhost:8084`).

## 3. Gateway local WhatsApp

```bash
cd C:\xampp\htdocs\sistema-assistencia\whatsapp-api
copy .env.example .env
npm install
node server.js
```

Sugestao minima no `.env` do gateway (ajuste para o mesmo host/porta do ERP):

```env
NODE_ENV=development
PORT=3001
API_TOKEN=TOKEN_FORTE_LOCAL
ERP_ORIGIN=http://localhost:8084
ERP_WEBHOOK_URL=http://localhost:8084/webhooks/whatsapp
ERP_WEBHOOK_TOKEN=SEU_TOKEN_WEBHOOK
WHATSAPP_SESSION_PATH=./.wwebjs_auth
SESSION_PATH=./.wwebjs_auth
LOGS_DIR=./logs
```

## 4. Configuracao no ERP

Em `Configuracoes -> Integracoes WhatsApp`:

1. Definir `Canal Direto` como `api_whats_local`.
2. Preencher:
   - `whatsapp_local_node_url` (ex.: `http://127.0.0.1:3001`)
   - `whatsapp_local_node_token` (igual ao `API_TOKEN`)
   - `whatsapp_local_node_origin` (`http://localhost:8084`)
   - `whatsapp_local_node_timeout`
3. Salvar.
4. Executar:
   - `Testar conexao`
   - `Enviar mensagem de teste`
   - `Gerenciar` para QR/status/restart

## 5. Erros comuns

- `gateway_unreachable`: Node parado, porta ou URL incorreta.
- `unauthorized`: token salvo no ERP diferente de `API_TOKEN`.
- `forbidden_origin`: `ERP_ORIGIN` nao inclui o host/porta ativo do ERP (ex.: `http://localhost:8084`).
- inbound nao chega no CRM/central: `ERP_WEBHOOK_URL` do gateway aponta para host/porta diferente do ERP.
- `not_ready`: QR ainda nao autenticado.
