# Atualizacao da VPS - Release v2.11.2

Data da execucao: 03/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Publicar no ambiente produtivo a evolucao da Central Mobile PWA com abertura completa de OS:

- endpoint de metadados `GET /api/v1/orders/meta`;
- criacao completa de OS em `POST /api/v1/orders`;
- nova tela mobile `/os/nova`;
- botao direto `Nova OS` na listagem mobile;
- atualizacao de documentacao e versao oficial `2.11.2`.

## Estrategia aplicada

- deploy por pacote seletivo (`tar.gz`) com manifesto explicito;
- backup pre-deploy apenas dos arquivos existentes no alvo;
- sem `rsync --delete`, preservando `.env`, `public/uploads/`, `writable/` e `whatsapp-api/`;
- migracao incremental (`php8.3 spark migrate --all --no-header`);
- rebuild do app mobile (`npm run build`) e restart do PM2 (`assistencia-mobile-pwa`).

## Arquivos publicados

- `app/Config/Routes.php`
- `app/Config/SystemRelease.php`
- `app/Controllers/Api/V1/OrdersController.php`
- `mobile-app/src/lib/api.ts`
- `mobile-app/src/app/os/page.tsx`
- `mobile-app/src/app/os/nova/page.tsx`
- `mobile-app/src/app/globals.css`
- `documentacao/05-api/rotas.md`
- `documentacao/06-modulos-do-sistema/central-mobile-pwa.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-03-release-v2.11.2-mobile-pwa-nova-os-completa.md`
- `documentacao/README.md`

## Artefatos operacionais remotos

Diretorio remoto:

```text
/root/deploy_patch_20260403_v2.11.2/
```

Arquivos gerados:

- `sistema_hml_patch_20260403_v2.11.2.tar.gz`
- `sync_manifest_20260403_v2.11.2.txt`
- `existing_files_20260403_v2.11.2.txt`
- `predeploy_files_20260403_v2.11.2.tar.gz`
- `remote_apply_v2.11.2.sh`
- `remote_validate_v2.11.2.sh`

## Validacao executada

- `php8.3 -l app/Config/Routes.php` -> `OK`
- `php8.3 -l app/Config/SystemRelease.php` -> `OK`
- `php8.3 -l app/Controllers/Api/V1/OrdersController.php` -> `OK`
- `php8.3 spark migrate --all --no-header` -> `Migrations complete`
- `npm run build` em `mobile-app` -> `Compiled successfully`
- `pm2 startOrRestart ecosystem.config.cjs --only assistencia-mobile-pwa` -> `online`
- `GET https://sistema.jovemtech.eco.br/login` -> `200`
- `GET https://app.jovemtech.eco.br/login` -> `200`
- `GET https://app.jovemtech.eco.br/os/nova` -> `200`
- `GET https://sistema.jovemtech.eco.br/api/v1/orders/meta` sem token -> `401` (esperado)
- versao remota confirmada: `2.11.2`

## Resultado final

- release `2.11.2` publicada na VPS;
- modulo mobile com abertura completa de OS disponivel em producao;
- API de metadados e rota de criacao completa de OS ativas;
- PM2 do app mobile reiniciado com build atualizado.
