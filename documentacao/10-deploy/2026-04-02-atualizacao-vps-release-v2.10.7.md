# Atualizacao da VPS - Release v2.10.7

Data da execucao: 02/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Publicar na VPS o hotfix da Central de Mensagens que torna a sincronizacao inbound automatica silenciosa, removendo a faixa azul intrusiva do chat durante o uso normal.

## Estrategia aplicada

- publicacao por pacote seletivo (`tar.gz`) com manifesto explicito de `9` arquivos;
- sem `rsync --delete` e sem tocar em:
  - `.env`
  - `public/uploads/`
  - `writable/`
  - `whatsapp-api/`
- reexecucao segura de migrations para confirmar que nao havia pendencias estruturais;
- validacao HTTP, servicos web e gateway WhatsApp na propria VPS.

## Arquivos publicados

- `app/Config/SystemRelease.php`
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-02-release-v2.10.7-central-mensagens-sync-inbound-silencioso.md`
- `documentacao/08-correcoes/2026-04-02-central-mensagens-sync-inbound-silencioso.md`
- `documentacao/README.md`

## Artefatos operacionais

Diretorio remoto:

```text
/root/deploy_patch_20260402_v2.10.7/
```

Arquivos gerados:

- `sistema_hml_patch_20260402_v2.10.7.tar.gz`
- `sync_manifest_20260402_v2.10.7.txt`
- `predeploy_files_20260402_v2.10.7.tar.gz`
- `whatsapp_status.json`

## Validacao executada

### Sintaxe e integridade

- lint PHP remoto sobre os arquivos `.php` do manifesto: `OK`
- `node --check` remoto em `public/assets/js/central-mensagens.js`: `OK`

### Migrations

- `php spark migrate --all --no-header`: executado com sucesso
- status final mantido para as migrations:
  - `AddLegacyMigrationInfrastructure`
  - `AddLegacyImportAliases`
  - `AddLegacyTrackingToOsDetailTables`
  - `CreateOsNotasLegadasTable`

### Aplicacao / HTTP

- `GET /login` -> `200`
- `GET /` -> `200`
- `GET /atendimento-whatsapp` -> `302` (autenticacao esperada)
- `GET /assets/css/design-system/layouts/central-mensagens.css` -> `200`
- `GET /assets/js/central-mensagens.js` -> `200`

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

### Release confirmada

- versao remota em `app/Config/SystemRelease.php`: `2.10.7`

## Observacoes operacionais

- o checkout Git remoto permaneceu fora do caminho critico; a publicacao foi feita diretamente por manifesto seletivo para evitar arrastar alteracoes historicas da VPS;
- como esta release nao introduziu migration nova nem alteracao de dados, nao houve dump SQL dedicado nesta rodada;
- o backup remoto da rodada ficou concentrado em `predeploy_files_20260402_v2.10.7.tar.gz`, suficiente para rollback dos arquivos implantados.

## Resultado final

- ERP remoto alinhado a `v2.10.7`
- sincronizacao inbound automatica da Central publicada em modo silencioso
- servicos web e gateway WhatsApp validados com sucesso apos a publicacao
