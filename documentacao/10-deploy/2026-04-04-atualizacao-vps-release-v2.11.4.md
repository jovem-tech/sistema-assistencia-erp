# Atualizacao da VPS - Release v2.11.4

Data da execucao: 04/04/2026  
Ambiente: VPS (`161.97.93.120`)  
Aplicacao: `/var/www/sistema-hml`

## Objetivo

Publicar a consolidacao oficial do app mobile/PWA da Assistencia com:

- versao do ERP em `2.11.4`;
- versao oficial do app em `0.4.0`;
- documentacao exclusiva do app em `documentacao/12-app-mobile-pwa/`;
- fluxo mobile de OS, equipamento, acessorios, fotos e busca refinado;
- visibilidade explicita da versao do app no produto.

## Regra critica aplicada nesta atualizacao

Nao houve sincronizacao de dados nem fotos de desenvolvimento para producao.

Itens explicitamente preservados:

- banco de dados operacional;
- registros de teste criados em ambiente local;
- `public/uploads/`;
- `.env`;
- `writable/`.

## Estrategia aplicada

- deploy por pacote seletivo (`tar.gz`) com manifesto explicito de arquivos;
- backup pre-deploy dos arquivos existentes antes da sobrescrita;
- sem migracoes e sem escrita de dados em banco;
- sem envio de fotos locais de teste;
- rebuild do app mobile na VPS e restart apenas do processo PM2 do PWA.

## Artefatos utilizados

Diretorio remoto:

```text
/root/deploy_patch_20260404_v2.11.4/
```

Arquivos operacionais:

- `sistema_hml_patch_20260404_v2.11.4.tar.gz`
- `sync_manifest_20260404_v2.11.4.txt`
- `existing_files_20260404_v2.11.4.txt`
- `predeploy_files_20260404_v2.11.4.tar.gz`
- `remote_apply_v2.11.4.sh`
- `remote_validate_v2.11.4.sh`
- `remote_rollback_v2.11.4.sh`

## Ajuste importante de publicacao

O app possui subdominio dedicado em `app.jovemtech.eco.br`. Por isso, o build de producao foi alinhado para publicar o Next na raiz do subdominio com:

```text
NEXT_PUBLIC_APP_BASE_PATH=

e o rewrite server-side da API foi alinhado para usar o dominio HTTPS canonico do ERP:

```text
NEXT_PUBLIC_ERP_WEB_BASE_URL=https://sistema.jovemtech.eco.br
```
```

Com isso:

- `https://app.jovemtech.eco.br/login` responde diretamente o app;
- `https://sistema.jovemtech.eco.br/atendimento-mobile-app/login` redireciona para o subdominio dedicado do app.
- `https://app.jovemtech.eco.br/api/v1/*` deixa de cair no vhost errado do Nginx e passa a reescrever corretamente para a API do ERP.

## Validacao executada

- `php8.3 -l` em:
  - `app/Config/*` relevantes
  - `app/Controllers/Api/V1/*`
  - `app/Services/Mobile/*`
  - `app/Models/Mobile*`
- `npm install`
- `npm run build`
- `pm2 startOrRestart ecosystem.config.cjs --only assistencia-mobile-pwa`
- `pm2 save`
- `nginx -t`
- validacao publica:
  - `https://app.jovemtech.eco.br/login` -> `200`
  - `https://sistema.jovemtech.eco.br/atendimento-mobile-app/login` -> `302` seguido de `200`

## Estado final

- `assistencia-mobile-pwa` online no PM2;
- `whatsapp-gateway` preservado online;
- release `2.11.4` publicada na VPS;
- app `0.4.0` publicado com dominio dedicado funcional;
- nenhuma carga de dados ou fotos de teste enviada para producao.

## Observacoes

O build de producao concluiu com warnings de ESLint/Next sobre dependencias de `useEffect` e uso de `<img>`, mas sem bloquear a publicacao.
