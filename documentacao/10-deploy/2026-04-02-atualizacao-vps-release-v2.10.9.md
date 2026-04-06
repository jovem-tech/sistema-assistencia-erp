# Atualizacao da VPS - Release v2.10.9

Data da execucao: 02/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Publicar na VPS a correcao da Central de Mensagens para processamento inbound de audio/video/imagem com hidratacao de anexos no fluxo de historico.

## Estrategia aplicada

- deploy por pacote seletivo (`tar.gz`) com manifesto de arquivos;
- backup pre-deploy apenas dos arquivos existentes no alvo (arquivos novos de documentacao entram direto no patch);
- sem `rsync --delete` e sem alteracao em:
  - `.env`
  - `public/uploads/`
  - `writable/`
- aplicacao do pacote diretamente em `/var/www/sistema-hml`;
- reinicio do gateway Node via PM2 para carregar o novo `server.js`.

## Arquivos publicados

- `app/Config/SystemRelease.php`
- `app/Services/CentralMensagensService.php`
- `public/assets/js/central-mensagens.js`
- `whatsapp-api/server.js`
- `documentacao/05-api/rotas.md`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/06-modulos-do-sistema/whatsapp.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-02-release-v2.10.9-central-mensagens-inbound-multimidia-hidratacao.md`
- `documentacao/08-correcoes/2026-04-02-central-mensagens-inbound-multimidia-hidratacao.md`
- `documentacao/README.md`

## Artefatos operacionais

Diretorio remoto:

```text
/root/deploy_patch_20260402_v2.10.9/
```

Arquivos gerados:

- `sistema_hml_patch_20260402_v2.10.9.tar.gz`
- `sync_manifest_20260402_v2.10.9.txt`
- `files_list_20260402_v2.10.9.txt`
- `predeploy_files_20260402_v2.10.9.tar.gz`

## Validacao executada

### Sintaxe e migrations

- `php8.3 -l app/Services/CentralMensagensService.php` -> `OK`
- `php8.3 -l app/Config/SystemRelease.php` -> `OK`
- `node --check public/assets/js/central-mensagens.js` -> `OK`
- `node --check whatsapp-api/server.js` -> `OK`
- `php8.3 spark migrate --all --no-header` -> `Migrations complete`

### Aplicacao / HTTP

- `GET http://127.0.0.1/login` -> `200`
- `GET http://127.0.0.1/assets/js/central-mensagens.js` -> `200`

### Gateway WhatsApp

- `pm2 restart whatsapp-gateway` executado com sucesso
- `pm2 status whatsapp-gateway` -> `online`
- `GET http://127.0.0.1:3001/status` com `X-Api-Token` e `X-ERP-Origin` -> `200`
- status retornado: `connected`
- sessao pronta: `ready = true`
- conta ativa reportada: `5522997196876`

## Release confirmada

- versao remota em `app/Config/SystemRelease.php`: `2.10.9`

## Resultado final

- VPS atualizada para `v2.10.9`;
- gateway reiniciado e operacional;
- fluxo inbound multimidia preparado para sincronizar anexos tambem pelo historico, com classificacao correta de audio `ptt/voice`.
