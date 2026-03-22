# Deploy VPS Linux (ERP + Gateway WhatsApp)

Atualizado em 17/03/2026.

Este guia prepara a aplicacao para producao em VPS Linux (Ubuntu/Debian), mantendo compatibilidade com desenvolvimento local (Windows/XAMPP).

## Guia oficial completo

Para procedimento integral de provisionamento e operacao do ERP CI4 em Ubuntu 24.04, consulte:

- `manual-tecnico-oficial-vps-ubuntu-24-ci4.md`
- `atualizacao-vps-sem-downtime.md`
- `scripts/install_erp.sh`

## 1. Arquitetura alvo

Fluxo de mensageria:

`ERP/CRM -> MensageriaService -> Provider -> Gateway/Fornecedor`

Providers diretos suportados no ERP:
- `menuia`
- `api_whats_local` (Windows/local)
- `api_whats_linux` (VPS Linux/producao)
- `webhook` (integracao generica)

Provider de massa (futuro):
- `meta_oficial`

## 2. Estrutura recomendada em producao

```text
/sistema/
  app/
  public/
  writable/
  whatsapp-api/
    server.js
    package.json
    .env
    .wwebjs_auth/
    logs/
    install-whatsapp-api.sh
```

## 3. Requisitos de servidor

- Ubuntu 22.04+ ou Debian 12+
- PHP 8.2+ (extensoes: curl, intl, mbstring, xml, zip, gd, mysql)
- Apache 2.4+ ou Nginx
- MySQL/MariaDB
- Node.js 20+
- PM2

## 4. Instalacao automatica do gateway

O projeto inclui o script:

`whatsapp-api/install-whatsapp-api.sh`

Executar:

```bash
cd /sistema/whatsapp-api
chmod +x install-whatsapp-api.sh
./install-whatsapp-api.sh /sistema/whatsapp-api whatsapp-gateway
```

O script:
1. instala dependencias Linux do Puppeteer/Chromium
2. instala Node.js (se nao existir)
3. instala PM2
4. executa `npm install --omit=dev`
5. sobe `server.js` no PM2
6. salva processo e prepara boot automatico

## 4.1 Instalador VPS do ERP (com opcao de gateway)

Tambem existe o instalador unificado:

`scripts/install-vps.sh`

Execucao:

```bash
cd /sistema
chmod +x scripts/install-vps.sh
./scripts/install-vps.sh /sistema /sistema/whatsapp-api whatsapp-gateway
```

Esse instalador:
1. roda migrations do ERP
2. limpa cache
3. ajusta permissoes basicas de escrita
4. pergunta: **"Instalar WhatsApp Gateway agora?"**
5. se confirmado, executa `whatsapp-api/install-whatsapp-api.sh`

## 5. Configuracao do `.env` do gateway

Arquivo: `/sistema/whatsapp-api/.env`

Exemplo:

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

RECONNECT_DELAY_MS=5000
RATE_LIMIT_WINDOW_MS=60000
RATE_LIMIT_MAX=120
REQUEST_TIMEOUT_MS=30000
```

Gerar token forte:

```bash
openssl rand -hex 32
```

## 6. Regras de seguranca do gateway

- Endpoints sensiveis (`/status`, `/qr`, `/restart`, `/create-message`) exigem token:
  - `Authorization: Bearer <API_TOKEN>` ou `X-Api-Token`
- CORS restrito por `ERP_ORIGIN`
- Rate limit ativo para endpoints sensiveis
- Porta 3001 nao deve ficar publica; use apenas acesso interno/reverse-proxy

## 7. PM2 operacao

Comandos uteis:

```bash
pm2 status whatsapp-gateway
pm2 logs whatsapp-gateway
pm2 restart whatsapp-gateway
pm2 save
```

## 8. Configuracao no painel ERP

Tela: `Configuracoes -> Integracoes WhatsApp`

1. Defina `Canal Direto` como `API Linux (VPS)` (`api_whats_linux`).
2. Preencha:
   - `whatsapp_linux_node_url` (ex.: `http://127.0.0.1:3001`)
   - `whatsapp_linux_node_token` (igual ao `API_TOKEN` do `.env`)
   - `whatsapp_linux_node_origin` (dominio ERP)
   - `whatsapp_linux_node_timeout`
3. Salve.
4. Use `Testar conexao`, `Enviar mensagem de teste` e `Gerenciar`.
5. No modal, escaneie QR para criar sessao em `.wwebjs_auth`.

## 9. Troubleshooting rapido

`unauthorized`:
- token salvo no ERP diferente do `API_TOKEN`.

`forbidden_origin`:
- origem ERP nao incluida em `ERP_ORIGIN`.

`gateway_unreachable`:
- processo Node parado ou URL errada.
- validar `pm2 status` e `whatsapp_linux_node_url`.

QR nao aparece:
- verificar logs: `pm2 logs whatsapp-gateway`
- reiniciar inicializacao pelo modal (botao reiniciar)
- validar escrita em `.wwebjs_auth` e `logs/`

`Erro nao tratado no gateway. {"detail":"Origin not allowed by ERP_ORIGIN."}`
- Ocorre quando a origem configurada no painel (**Origem ERP**) Nao bate com o arquivo `.env`.
- Verifique se ha barras sobressalentes. `http://meu-vps.com` (Certo) vs `http://meu-vps.com/` (Errado).

`Error while loading shared libraries: libatk-1.0.so.0... no such file or directory` (ou `libasound.so.2`)
- Faltam dependencias graficas do Puppeteer no seu Linux (geralmente Ubuntu 24.04+).
- Em paineis limpos, instale o conjunto compelto:
  `apt-get install -y libgbm1 libatk1.0-0 libatk-bridge2.0-0 libnss3 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 libpango-1.0-0 libpangocairo-1.0-0 libgtk-3-0 libcairo2 libcups2 fonts-liberation`
- Para pacotes de som problematicos como ALSA no Ubuntu 24.04+, verifique o novo nome da dependencia:
  `apt-get install -y libasound2t64 || apt-get install -y libasound2`

`Falha ao executar PM2` (Alertas na interface Web)
- O botao de **Iniciar Servidor** no painel Administrativo (Web) chama processos via terminal. No Linux VPS, ele sera bloquedo por causa de permissoes restritas (usuario `www-data`). Esteja ciente de que **sempre** precisara iniciar ou reiniciar o servico como ROOT pelo terminal: `pm2 restart whatsapp-gateway`.

## 10. Compatibilidade local x producao

Desenvolvimento local:
- provider `api_whats_local`
- URL local (`http://127.0.0.1:3001`)

Producao VPS:
- provider `api_whats_linux`
- URL interna da VPS

O ERP usa a mesma camada `MensageriaService`, sem acoplamento hardcoded ao provider.
