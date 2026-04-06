# Atualizacao da VPS - Release v2.11.0

Data da execucao: 03/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Publicar a base da Central Mobile PWA paralela ao ERP web, com:

- API interna `/api/v1` no CodeIgniter 4;
- infraestrutura complementar de banco para mobile/push;
- app `mobile-app` em Next.js rodando por PM2 na porta interna `3100`;
- proxy interno Nginx em `/atendimento-mobile-app/*`;
- redirecionamento do ERP mobile via chave `mobile_pwa_url`.

## Estrategia aplicada

- deploy por pacote seletivo (`tar.gz`) com manifesto explicito de arquivos;
- backup pre-deploy dos arquivos existentes antes de sobrescrever;
- sem `delete`, preservando `.env`, `public/uploads/`, `writable/` e `whatsapp-api/`;
- migracao incremental (`php8.3 spark migrate --all --no-header`);
- build e start do app mobile com PM2 (`assistencia-mobile-pwa`);
- publicacao do app na mesma origem do ERP via Nginx (`/atendimento-mobile-app`), sem expor porta externa adicional.

## Arquivos publicados

- `app/Config/Filters.php`
- `app/Config/Routes.php`
- `app/Config/SystemRelease.php`
- `app/Controllers/AtendimentoMobile.php`
- `app/Controllers/Api/V1/*`
- `app/Database/Migrations/2026-04-03-010000_CreateMobilePwaInfrastructure.php`
- `app/Filters/ApiTokenAuthFilter.php`
- `app/Models/Mobile*`
- `app/Services/Mobile/*`
- `mobile-app/*`
- `public/assets/js/scripts.js`
- `public/mobile-app/index.html`
- documentacao da release `2.11.0`

## Artefatos operacionais remotos

Diretorio remoto:

```text
/root/deploy_patch_20260403_v2.11.0/
```

Arquivos esperados:

- `sistema_hml_patch_20260403_v2.11.0.tar.gz`
- `sync_manifest_20260403_v2.11.0.txt`
- `existing_files_20260403_v2.11.0.txt`
- `predeploy_files_20260403_v2.11.0.tar.gz`
- `mobile_pwa_url.txt`

## Validacao executada

- `php8.3 -l` nos arquivos core da API mobile;
- `php8.3 spark migrate --all --no-header`;
- `npm install` + `npm run build` em `mobile-app/`;
- `pm2 startOrRestart ecosystem.config.cjs --only assistencia-mobile-pwa`;
- `nginx -t` + `systemctl reload nginx` com bloco `assistencia-mobile-pwa-start`;
- `curl http://127.0.0.1/login` (ERP);
- `curl -X POST http://127.0.0.1/api/v1/auth/login` (contrato de validacao);
- `curl http://127.0.0.1/atendimento-mobile-app/login` (app mobile via Nginx);
- `curl -X POST http://161.97.93.120/atendimento-mobile-app/api/v1/auth/login` (proxy + rewrite Next -> API, retorno `422` esperado);
- `curl http://161.97.93.120/atendimento-mobile-app/login` (acesso externo, retorno `200`);
- confirmacao da versao em `app/Config/SystemRelease.php`.

## Resultado final

- release `2.11.0` publicada;
- migracao mobile aplicada no banco;
- app mobile iniciado no PM2;
- app mobile publicado na rota `/atendimento-mobile-app/login`;
- redirecionamento `/atendimento-mobile` apontando para `mobile_pwa_url = /atendimento-mobile-app/login`.

## Observacao critica (PWA e notificacoes push)

Para instalacao PWA completa e push notifications confiavel no celular, o app precisa rodar em contexto seguro (`HTTPS`) com certificado valido no dominio publico.
