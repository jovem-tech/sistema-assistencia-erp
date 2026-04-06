# Atualizacao da VPS - Release v2.10.10

Data da execucao: 02/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Publicar a estabilizacao do polling incremental da Central de Mensagens para eliminar timeout recorrente de 20s em cascata.

## Estrategia aplicada

- deploy por pacote seletivo (`tar.gz`) com manifesto de arquivos;
- backup pre-deploy apenas dos arquivos ja existentes no alvo;
- sem alteracao de `.env`, `public/uploads/` e `writable/`;
- reinicio do gateway via PM2 apos aplicacao dos arquivos.

## Arquivos publicados

- `app/Config/SystemRelease.php`
- `app/Controllers/CentralMensagens.php`
- `app/Services/CentralMensagensService.php`
- `public/assets/js/central-mensagens.js`
- `documentacao/05-api/rotas.md`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/06-modulos-do-sistema/whatsapp.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-02-release-v2.10.10-central-mensagens-polling-resiliente.md`
- `documentacao/08-correcoes/2026-04-02-central-mensagens-timeout-polling-incremental.md`
- `documentacao/README.md`

## Artefatos operacionais

Diretorio remoto:

```text
/root/deploy_patch_20260402_v2.10.10/
```

Arquivos gerados:

- `sistema_hml_patch_20260402_v2.10.10.tar.gz`
- `sync_manifest_20260402_v2.10.10.txt`
- `files_list_20260402_v2.10.10.txt`
- `predeploy_files_20260402_v2.10.10.tar.gz`

## Validacao executada

### Sintaxe e migrations

- `php8.3 -l app/Controllers/CentralMensagens.php` -> `OK`
- `php8.3 -l app/Services/CentralMensagensService.php` -> `OK`
- `php8.3 -l app/Config/SystemRelease.php` -> `OK`
- `node --check public/assets/js/central-mensagens.js` -> `OK`
- `php8.3 spark migrate --all --no-header` -> `Migrations complete`

### Aplicacao / HTTP

- `GET http://127.0.0.1/login` -> `200`
- `GET http://127.0.0.1/assets/js/central-mensagens.js` -> `200`

### Gateway WhatsApp

- `pm2 restart whatsapp-gateway` executado com sucesso
- `pm2 status whatsapp-gateway` -> `online`
- `GET http://127.0.0.1:3001/status` com token e origem -> `connected`

## Release confirmada

- versao remota em `app/Config/SystemRelease.php`: `2.10.10`

## Resultado final

- Central com polling incremental desacoplado de sync pesado;
- menor risco de timeout em cascata durante uso continuo do chat;
- gateway mantido online apos publicacao.
