# 2026-03 - Implantacao do Gateway Local WhatsApp (producao-ready)

## Objetivo
Transformar o gateway Node.js de WhatsApp em infraestrutura oficial de mensageria direta do ERP, mantendo arquitetura desacoplada para CRM futuro.

## Escopo implementado

## 1) Hardening do `whatsapp-api`
- refatoracao completa do `server.js`
- configuracao por `.env`
- suporte a `CHROME_EXECUTABLE_PATH` para ambientes Windows com restricao de spawn
- autenticacao por token (`X-Api-Token` / Bearer)
- validacao de origem (`X-ERP-Origin`, `Origin`, `Referer`)
- rate limit em `/status`, `/qr`, `/restart`, `/create-message`
- respostas padronizadas:
  - sucesso: `success=true`
  - erro: `success=false`
- metadados operacionais no `/status`:
  - ready
  - has_qr
  - conta conectada
  - ultimo ready/erro/desconexao

## 2) Operacao com PM2
- adicionado `whatsapp-api/ecosystem.config.js`
- scripts npm:
  - `pm2:start`
  - `pm2:restart`
  - `pm2:logs`

## 3) Camada desacoplada no ERP
- novo `app/Services/MensageriaService.php`
- novo provider `app/Services/WhatsApp/LocalGatewayProvider.php`
- `WhatsAppService` passou a resolver providers via `MensageriaService`

## 4) Persistencia de mensagens
- nova migration:
  - `2026-03-16-210500_AddLocalGatewayAndMensagensWhatsapp.php`
- nova tabela:
  - `mensagens_whatsapp`
- `WhatsAppService` grava e atualiza status/resposta/erro nessa tabela

## 5) Configuracoes e painel
- tela de configuracoes atualizada com campos do gateway local:
  - URL/token/origem/timeout para `api_whats_local`
  - URL/token/origem/timeout para `api_whats_linux`
- canal direto com selecao de provider:
  - `menuia`
  - `api_whats_local`
  - `api_whats_linux`
  - `webhook`
- credenciais de provider nao ficam hardcoded no repositorio (devem ser salvas no painel/ambiente)
- modal `Gerenciar Gateway` com metadados operacionais
- proxies PHP de status/qr/restart agora enviam token e origem para o Node

## 5.1) Deploy Linux assistido
- novo script `whatsapp-api/install-whatsapp-api.sh`
- instala dependencias de SO + Node + PM2 + npm packages
- sobe e persiste o processo no PM2 para reboot automatico

## 6) Compatibilidade
- logs antigos continuam operando:
  - `whatsapp_envios`
  - `whatsapp_mensagens`
- `MenuiaProvider` preservado para canal direto existente
- arquitetura pronta para provider de massa futuro (`meta_oficial`)

## Resultado
- gateway local preparado para operacao online segura
- ERP com mensageria direta desacoplada por provider
- trilha de auditoria consolidada para base ERP+CRM
