# Atualizacao da VPS - Release v2.10.8

Data da execucao: 02/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Publicar na VPS os ajustes da Central de Mensagens para:

- reduzir flicker no sync inbound automatico;
- habilitar status clicavel por modal;
- habilitar atribuicao por modal;
- encerrar com decisao `Concluir` ou `Arquivar`;
- incluir filtro rapido `Arquivadas`;
- adicionar botoes de prioridade, bot ativo e aguardando humano;
- reabrir conversa `resolvida` para `aberta` ao receber novo inbound.

## Estrategia aplicada

- deploy por pacote seletivo (`tar.gz`) com manifesto de 10 arquivos;
- sem `rsync --delete` e sem alteracao em:
  - `.env`
  - `public/uploads/`
  - `writable/`
  - `whatsapp-api/`
- backup pre-deploy dos arquivos existentes no manifesto;
- copia direta dos arquivos do patch para o app remoto;
- validacao de sintaxe e migrations apos publicacao.

## Arquivos publicados

- `app/Config/SystemRelease.php`
- `app/Services/CentralMensagensService.php`
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-02-release-v2.10.8-central-mensagens-controle-operacional-sem-flicker.md`
- `documentacao/08-correcoes/2026-04-02-central-mensagens-controles-operacionais-sem-flicker.md`
- `documentacao/README.md`

## Artefatos operacionais

Diretorio remoto:

```text
/root/deploy_patch_20260402_v2.10.8/
```

Arquivos gerados:

- `sistema_hml_patch_20260402_v2.10.8.tar.gz`
- `sync_manifest_20260402_v2.10.8.txt`
- `predeploy_files_20260402_v2.10.8.tar.gz`
- `files_list_20260402_v2.10.8.txt`

## Validacao executada

### Sintaxe e migrations

- `php8.3 -l app/Services/CentralMensagensService.php` -> `OK`
- `php8.3 -l app/Views/central_mensagens/index.php` -> `OK`
- `php8.3 -l app/Config/SystemRelease.php` -> `OK`
- `node --check public/assets/js/central-mensagens.js` -> `OK`
- `php8.3 spark migrate --all --no-header` -> `Migrations complete`

### Aplicacao / HTTP

- `GET http://127.0.0.1/login` -> `200`
- `GET http://127.0.0.1/` -> `200`
- `GET http://127.0.0.1/assets/css/design-system/layouts/central-mensagens.css` -> `200`
- `GET http://127.0.0.1/assets/js/central-mensagens.js` -> `200`

### Servicos

- `nginx` -> `active`
- `php8.3-fpm` -> `active`
- `pm2 status whatsapp-gateway` -> `online`

### Gateway WhatsApp

- `GET http://127.0.0.1:3001/status` com `X-Api-Token` e `X-ERP-Origin` -> `200`
- status retornado: `connected`
- sessao pronta: `ready = true`
- conta ativa reportada: `5522997196876`

## Release confirmada

- versao remota em `app/Config/SystemRelease.php`: `2.10.8`

## Resultado final

- ERP remoto alinhado com `v2.10.8`;
- Central de Mensagens com os novos controles operacionais no cabecalho;
- sync inbound automatico com menor interferencia visual na thread;
- servicos web e gateway em execucao apos o deploy.
