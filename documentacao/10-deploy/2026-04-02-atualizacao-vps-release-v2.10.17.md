# Atualizacao da VPS - Release v2.10.17

Data da execucao: 02/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Publicar o ajuste da Central de Mensagens para:

- mover `Bot ativo/Bot desativado` e `Aguardando atendimento humano` para o menu hamburguer da thread;
- manter `Status` e `Prioridade` visiveis no cabecalho;
- corrigir abertura do menu hamburguer (dropdown) no cabecalho.

## Estrategia aplicada

- deploy por pacote seletivo (`tar.gz`) com manifesto explicito de arquivos;
- backup pre-deploy apenas dos arquivos existentes no alvo;
- sem `rsync --delete`, preservando `.env`, `public/uploads/`, `writable/` e `whatsapp-api/`;
- restart do gateway via PM2 apos publicacao.

## Arquivos publicados

- `app/Config/SystemRelease.php`
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `documentacao/README.md`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-02-release-v2.10.16-central-mensagens-header-status-prioridade-modo-binario.md`
- `documentacao/07-novas-implementacoes/2026-04-02-release-v2.10.17-central-mensagens-modo-no-menu-hamburguer.md`

## Artefatos operacionais remotos

Diretorio remoto:

```text
/root/deploy_patch_20260402_v2.10.17/
```

Arquivos gerados:

- `sistema_hml_patch_20260402_v2.10.17.tar.gz`
- `sync_manifest_20260402_v2.10.17.txt`
- `existing_files_20260402_v2.10.17.txt`
- `predeploy_files_20260402_v2.10.17.tar.gz`
- `whatsapp_status.json`

## Validacao executada

- `php8.3 -l app/Views/central_mensagens/index.php` -> `OK`
- `php8.3 -l app/Config/SystemRelease.php` -> `OK`
- `node --check public/assets/js/central-mensagens.js` -> `OK`
- `php8.3 spark migrate --all --no-header` -> `Migrations complete`
- `pm2 restart whatsapp-gateway` -> `online`
- `GET /login` -> `200`
- `GET /assets/js/central-mensagens.js` -> `200`
- `GET /assets/css/design-system/layouts/central-mensagens.css` -> `200`
- `GET 127.0.0.1:3001/status` com token/origem -> `connected`
- versao remota confirmada: `2.10.17`

## Resultado final

- deploy aplicado com backup;
- gateway operacional apos restart;
- ajustes visuais/funcionais da Central publicados na VPS.
