# Atualizacao da VPS - Release v2.10.11

Data da execucao: 02/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Publicar o hotfix da Central de Mensagens para reduzir timeout concorrente entre polling incremental e envio de mensagens.

## Estrategia aplicada

- deploy por pacote seletivo (`tar.gz`) com manifesto explicito;
- backup pre-deploy apenas dos arquivos existentes no alvo;
- preservacao de `.env`, `public/uploads/`, `writable/` e `whatsapp-api/`;
- restart do gateway via PM2 apos publicacao.

## Arquivos publicados

- `app/Config/SystemRelease.php`
- `app/Controllers/CentralMensagens.php`
- `public/assets/js/central-mensagens.js`
- `documentacao/05-api/rotas.md`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/06-modulos-do-sistema/whatsapp.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-02-release-v2.10.11-central-mensagens-timeout-polling-envio.md`
- `documentacao/08-correcoes/2026-04-02-central-mensagens-timeout-polling-envio-concorrente.md`
- `documentacao/10-deploy/deploy-producao.md`
- `documentacao/README.md`

## Artefatos operacionais

Diretorio remoto:

```text
/root/deploy_patch_20260402_v2.10.11/
```

Arquivos gerados:

- `sistema_hml_patch_20260402_v2.10.11.tar.gz`
- `sync_manifest_20260402_v2.10.11.txt`
- `existing_files_20260402_v2.10.11.txt`
- `predeploy_files_20260402_v2.10.11.tar.gz`
- `whatsapp_status.json`

## Validacao executada

### Sintaxe e migrations

- `php8.3 -l app/Controllers/CentralMensagens.php` -> `OK`
- `php8.3 -l app/Config/SystemRelease.php` -> `OK`
- `node --check public/assets/js/central-mensagens.js` -> `OK`
- `php8.3 spark migrate --all --no-header` -> `Migrations complete`

### Aplicacao / HTTP

- `GET http://127.0.0.1/login` -> `200`
- `GET http://127.0.0.1/assets/js/central-mensagens.js` -> `200`
- `defaultRequestTimeoutMs` no asset remoto -> `cfg.requestTimeoutMs || 30000`

### Gateway WhatsApp

- `pm2 restart whatsapp-gateway` executado com sucesso
- `pm2 status whatsapp-gateway` -> `online`
- `GET http://127.0.0.1:3001/status` com token e origem -> `connected`

## Release confirmada

- versao remota em `app/Config/SystemRelease.php`: `2.10.11`

## Resultado final

- polling incremental sem processamento auxiliar no caminho critico;
- envio desacoplado do lock de sessao, reduzindo contenção concorrente;
- timeout de request/envio mais resiliente para operacao em VPS.
