# Atualizacao da VPS - Release v2.11.5

Data da execucao: 04/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Publicar a selecao rica de equipamentos na abertura de OS do ERP web e do app mobile/PWA, sem sincronizar banco, registros de teste ou uploads locais.

## Escopo

- ERP `2.11.5`
- app mobile/PWA `0.4.2`
- ERP minimo compativel do app: `2.11.5`
- patch seletivo apenas de codigo, assets e documentacao

## Itens preservados

- banco de dados operacional
- registros de teste criados localmente
- `public/uploads/`
- `.env`
- `writable/`

## Artefatos utilizados

Diretorio remoto:

```text
/root/deploy_patch_20260404_v2.11.5/
```

Arquivos operacionais:

- `sistema_hml_patch_20260404_v2.11.5.tar.gz`
- `sync_manifest_20260404_v2.11.5.txt`
- `existing_files_20260404_v2.11.5.txt`
- `predeploy_files_20260404_v2.11.5.tar.gz`
- `remote_apply_v2.11.5.sh`
- `remote_validate_v2.11.5.sh`
- `remote_rollback_v2.11.5.sh`

## Validacoes executadas

- PHP lint dos arquivos alterados
- `npx tsc --noEmit` no app mobile
- build de producao do app
- restart do PM2 `assistencia-mobile-pwa`
- `nginx -t`
- smoke publico:
  - `https://app.jovemtech.eco.br/login`
  - `https://app.jovemtech.eco.br/os`
  - `https://app.jovemtech.eco.br/os/nova`
  - `https://app.jovemtech.eco.br/api/v1/auth/login`

Resultado observado:

- `ERP_LOGIN=200`
- `APP_LOGIN=200`
- `APP_OS=200`
- `APP_OS_NOVA=200`
- `API_LOGIN=422` com payload de validacao esperado (`Email e senha sao obrigatorios.`)

## Estado final

- patch seletivo aplicado com backup predeploy dos arquivos existentes
- ERP `2.11.5` publicado
- app mobile/PWA `0.4.2` publicado
- `assistencia-mobile-pwa` online no PM2
- `whatsapp-gateway` preservado online
- nenhum dado de teste, banco local ou foto de desenvolvimento enviado para producao
