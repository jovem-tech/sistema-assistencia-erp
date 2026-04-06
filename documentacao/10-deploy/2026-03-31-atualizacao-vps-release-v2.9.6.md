# Atualizacao da VPS - Release v2.9.6

Data: 31/03/2026

## Objetivo

Publicar a estabilizacao da integracao Menuia na VPS, com:
- URL canonica
- validacao real das novas credenciais
- badges confiaveis no painel de configuracoes
- labels seguras no filtro da busca global

## Codigo publicado

- `app/Controllers/Configuracoes.php`
- `app/Services/WhatsApp/MenuiaProvider.php`
- `app/Services/MensageriaService.php`
- `app/Views/configuracoes/index.php`
- `app/Views/layouts/navbar.php`
- `app/Config/SystemRelease.php`

## Documentacao sincronizada

- `documentacao/02-manual-administrador/configuracao-do-sistema.md`
- `documentacao/06-modulos-do-sistema/whatsapp.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-03-31-release-v2.9.6-menuia-validacao-e-badges-reais.md`
- `documentacao/README.md`

## Ajustes de configuracao aplicados na VPS

Configuracoes persistidas na tabela `configuracoes`:
- `whatsapp_enabled = 1`
- `whatsapp_direct_provider = menuia`
- `whatsapp_menuia_url = https://chatbot.menuia.com/api`
- `whatsapp_menuia_appkey = credencial ativa da Menuia`
- `whatsapp_menuia_authkey = credencial ativa da Menuia`
- `whatsapp_last_check_provider = menuia`
- `whatsapp_last_check_status = success`
- `whatsapp_last_check_message = Mensagem enviada com sucesso.`
- `whatsapp_last_check_signature = assinatura das credenciais atuais`

## Validacao executada

### 1. Lint na VPS
- `php -l` dos arquivos PHP alterados: sem erros

### 2. Teste real da Menuia na VPS
Requisicao:
- `POST https://chatbot.menuia.com/api/create-message`

Resultado:
- `HTTP 200`
- resposta: `Mensagem enviada com sucesso.`

### 3. Estado esperado no painel
- badge principal: `Menuia conectada`
- badge de configuracao: `Credenciais OK`

## Observacoes operacionais

- se o operador trocar URL, Appkey ou Authkey, o sistema passa a exibir `Menuia nao validada` ate um novo teste de conexao
- se o navegador da VPS ainda mostrar cache antigo, use `Ctrl+F5`
