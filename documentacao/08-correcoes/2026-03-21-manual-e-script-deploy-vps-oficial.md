# Correcao/Entrega - Manual oficial e script de automacao de deploy VPS

Data: 21/03/2026  
Tipo: documentacao operacional + automacao de infraestrutura

## Objetivo

Consolidar em documentacao oficial:

1. Manual tecnico completo de deploy do ERP (CI4) em Ubuntu 24.04.
2. Script bash de automacao de provisionamento/deploy (`install_erp.sh`).
3. Links e mapeamentos de ajuda para acesso rapido ao novo material.

## Entregas realizadas

- Novo manual completo:
  - `10-deploy/manual-tecnico-oficial-vps-ubuntu-24-ci4.md`
- Novo script de automacao:
  - `10-deploy/scripts/install_erp.sh`
- Atualizacao de indice principal:
  - `README.md` (dentro de `documentacao/`)
- Atualizacao de guias de deploy existentes com referencia ao material oficial:
  - `10-deploy/deploy-producao.md`
  - `10-deploy/linux-vps-deployment.md`
- Atualizacao de mapeamento de ajuda contextual (`openDocPage`):
  - `public/assets/js/scripts.js`

## Escopo tecnico do manual oficial

- Provisionamento inicial de VPS.
- Stack Nginx + PHP-FPM + MySQL + phpMyAdmin.
- Deploy de codigo CI4.
- Configuracao de `.env`.
- Permissoes e validacoes operacionais.
- Troubleshooting dos erros reais observados em campo:
  - `.env` comentado;
  - placeholders literais no vhost;
  - conflito da porta 80 com Apache;
  - servico `php-fpm.service` incorreto;
  - divergencia de base local x VPS;
  - `404` no `/phpmyadmin`;
  - conflito de `location`/`fastcgi` no Nginx;
  - erros MySQL `1396` e `1410`;
  - erro `413 Request Entity Too Large`.

## Resultado esperado

- Equipe com procedimento reproduzivel de ponta a ponta.
- Menor dependencia de execucao manual passo a passo.
- Padronizacao de diagnostico e recuperacao de falhas no deploy.

