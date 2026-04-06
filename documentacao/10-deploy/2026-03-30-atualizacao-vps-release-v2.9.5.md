# Atualizacao da VPS - Release v2.9.5

Data: `30/03/2026`

## Objetivo

Alinhar a VPS com a release `v2.9.5`, corrigindo:

- configuracao do gateway WhatsApp Linux
- dependencia Node ausente no servico `whatsapp-api`
- labels da busca global com caracteres seguros
- filtro explicito para `OS Legado (numero antigo)` na navbar

## Acoes executadas

### 1. Gateway WhatsApp Linux

- Validada a configuracao do ERP na base `sistema_hml`:
  - `whatsapp_direct_provider = api_whats_linux`
  - `whatsapp_linux_node_url = http://127.0.0.1:3001`
  - `whatsapp_linux_node_origin = http://161.97.93.120`
  - `whatsapp_linux_node_token = mesmo valor do API_TOKEN do Node`

### 2. Restauracao do servico Node

Foi identificado o erro:

```text
Error: Cannot find module 'dotenv'
```

Correcao aplicada:

```bash
cd /var/www/sistema-hml/whatsapp-api
npm install --omit=dev
pm2 restart whatsapp-gateway
```

Resultado:

- `pm2 status whatsapp-gateway` -> `online`
- `GET http://127.0.0.1:3001/status` -> `200`
- gateway em estado `awaiting_qr`
- `GET http://127.0.0.1:3001/qr` -> `200`

Observacao:

- o gateway nao esta mais em erro interno
- no momento da validacao, ele ficou aguardando autenticacao por QR Code
- isso significa que a infraestrutura foi restaurada corretamente e o proximo passo operacional e apenas escanear o QR no painel

### 3. ERP / Busca global

Arquivos sincronizados para a VPS:

- `app/Views/layouts/navbar.php`
- `app/Libraries/GlobalSearchService.php`
- `app/Config/SystemRelease.php`

Efeitos publicados:

- `OS Legado (numero antigo)` aparece como filtro explicito na busca global
- labels do dropdown foram normalizadas para evitar caracteres quebrados:
  - `Servicos`
  - `Pecas`
  - `Configuracoes`

## Validacoes realizadas

- `php -l app/Views/layouts/navbar.php`
- `php -l app/Libraries/GlobalSearchService.php`
- `php -l app/Config/SystemRelease.php`
- `pm2 status whatsapp-gateway`
- `pm2 logs whatsapp-gateway --lines 25 --nostream`
- `GET /status` interno no gateway
- `GET /qr` interno no gateway

## Estado final

- ERP publicado na VPS com release `v2.9.5`
- busca global com filtro explicito de OS legado
- gateway WhatsApp Linux restaurado e operando
- sessao WhatsApp pendente apenas de leitura do QR Code
