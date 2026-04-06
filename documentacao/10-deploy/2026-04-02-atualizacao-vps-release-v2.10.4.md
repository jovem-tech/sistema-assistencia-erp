# Atualizacao da VPS - Release v2.10.4

Data da execucao: 02/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Alinhar a VPS ao estado atual do workspace local `v2.10.4`, sem sobrescrever uploads nem o gateway Node, consolidando:

- backlog funcional/documental entre `v2.9.8` e `v2.10.4`
- refinamentos operacionais da Central de Mensagens
- ajustes de OS/listagem responsiva e endpoints contextuais
- infraestrutura de migracao legada SQL e modelos auxiliares

## Estrategia aplicada

- Publicacao por pacote seletivo (`tar.gz`) com manifesto explicito de `107` arquivos.
- Sem `rsync --delete` e sem tocar em:
  - `.env`
  - `public/uploads/`
  - `writable/`
  - `whatsapp-api/`
- Backup dos arquivos existentes do checkout remoto antes da sobreposicao.
- Migrations reexecutadas na VPS com validacao logo em seguida.

## Artefatos operacionais

Diretorio remoto:

```text
/root/deploy_patch_20260402_v2.10.4/
```

Arquivos gerados:

- `sistema_hml_patch_20260402_v2.10.4.tar.gz`
- `sync_manifest_20260402_v2.10.4.txt`
- `predeploy_files_20260402_v2.10.4.tar.gz`
- `postdeploy_db_20260402_v2.10.4.sql.gz`

## Codigo/documentacao sincronizados

Principais grupos publicados:

- `app/Controllers/`
  - `CentralMensagens.php`
  - `Configuracoes.php`
  - `Os.php`
  - `Sessao.php`
  - `WhatsAppWebhook.php`
- `app/Services/`
  - `CentralMensagensService.php`
  - `CrmService.php`
  - `MensageriaService.php`
  - `WhatsAppService.php`
  - servicos de migracao legada SQL
- `app/Database/Migrations/`
  - migrations `2026-03-28-*` e `2026-03-29-*`
- `app/Views/`
  - Central de Mensagens
  - OS
  - Configuracoes
  - Navbar/layout
- `public/assets/`
  - `css/design-system/layouts/central-mensagens.css`
  - `css/design-system/layouts/os-list-layout.css`
  - `css/design-system/layouts/responsive-layout.css`
  - `js/central-mensagens.js`
  - `js/os-list-filters.js`
  - `js/scripts.js`
- `documentacao/`
  - manuais, arquitetura, banco, API, modulos, historico de versoes e guias de deploy atualizados

## Validacao executada

### Sintaxe e integridade

- lint PHP remoto sobre os arquivos `.php` do manifesto: `OK`
- `node --check` remoto nos JS alterados: `OK`

### Aplicacao / HTTP

- `GET /login` -> `200`
- `GET /` -> `200`
- `GET /atendimento-whatsapp` -> `302` (autenticacao esperada)
- `GET /os` -> `302` (autenticacao esperada)
- `GET /assets/css/design-system/layouts/central-mensagens.css` -> `200`
- `GET /assets/js/central-mensagens.js` -> `200`
- `GET /assets/js/os-list-filters.js` -> `200`
- `GET /assets/js/scripts.js` -> `200`

### Servicos

- `php8.3-fpm` -> `active`
- `nginx` -> `active`
- `pm2 status whatsapp-gateway` -> `online`

### Gateway WhatsApp

Validacao autorizada com `X-Api-Token` e `X-ERP-Origin` da propria VPS:

- `GET http://127.0.0.1:3001/status` -> `200`
- estado retornado: `connected`
- sessao pronta: `ready = true`
- conta ativa reportada pelo gateway: `5522997196876`

### Release e migrations confirmadas

- versao remota em `app/Config/SystemRelease.php`: `2.10.4`
- migrations visiveis no status da VPS:
  - `AddLegacyMigrationInfrastructure`
  - `AddLegacyImportAliases`
  - `AddLegacyTrackingToOsDetailTables`
  - `CreateOsNotasLegadasTable`

## Observacoes operacionais

- O checkout Git remoto continuou sujo e nao foi usado como fonte de verdade para `pull`; a sincronizacao foi feita diretamente por manifesto para evitar conflito com alteracoes historicas locais da VPS.
- O dump SQL predeploy padrao acusou privilegio `PROCESS` ao tentar exportar tablespaces do MySQL.
- Como contorno seguro, foi gerado snapshot compactado do estado publicado com `mysqldump --no-tablespaces`, salvo em `postdeploy_db_20260402_v2.10.4.sql.gz`.
- O gateway `whatsapp-api/` nao precisou de restart especifico e permaneceu `online` durante toda a validacao.

## Resultado final

- ERP remoto alinhado a `v2.10.4`
- Central de Mensagens com fila ordenada por movimentacao real e melhorias premium das releases `v2.9.8` a `v2.10.4`
- infraestrutura legada e documentacao sincronizadas na VPS
- servicos web e gateway WhatsApp validados com sucesso apos a publicacao
