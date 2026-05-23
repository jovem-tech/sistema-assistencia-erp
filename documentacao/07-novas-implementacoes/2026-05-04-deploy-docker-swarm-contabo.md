# 2026-05-04 - Base de deploy Docker Swarm para Contabo

## Objetivo

Preparar o ERP da assistencia tecnica para rodar em container Docker no servidor Contabo que ja utiliza Swarm + Traefik.

## Entrega

- criado `Dockerfile` com PHP `8.2`, Apache, extensoes do CodeIgniter 4 e healthcheck em `/login`;
- criado `.dockerignore` para evitar empacotar artefatos locais, uploads, `vendor/` e documentacao no build da imagem;
- criado `docker/runtime/entrypoint.sh` para:
  - gerar o `.env` interno da aplicacao a partir das variaveis do container;
  - ajustar permissoes de `writable/` e `public/uploads/`;
  - aguardar o banco;
  - rodar `php spark migrate --all`;
  - limpar cache no boot;
- criado `docker/runtime/vhost.conf` para publicar o CI4 diretamente por `public/`;
- criado `docker/runtime/php.ini` com limites seguros para upload, execucao e opcache;
- criado `docker/swarm/contabo-stack.yml` com:
  - servico `app`;
  - servico `db` (`mysql:8.0`);
  - labels de Traefik para HTTP -> HTTPS e `websecure`;
  - volumes persistentes para banco, uploads e `writable/`;
- criado `docker/swarm/contabo.env.example` para parametrizacao do stack;
- criado `scripts/docker/deploy-contabo-swarm.sh` para padronizar `build + stack deploy`;
- criada documentacao dedicada em `documentacao/10-deploy/docker-swarm-contabo.md`.

## Impacto operacional

- o ERP passa a ter trilha oficial de implantacao em Docker/Swarm, sem depender apenas do manual antigo com Nginx + PHP-FPM fora de container;
- o ambiente fica alinhado com o host atual do usuario, que ja opera Traefik e stacks Docker;
- uploads, sessoes, logs e banco ficam preservados em volumes nomeados.

## Observacao importante

Esta entrega nao containeriza o gateway `whatsapp-api/`. O servico Node.js continua tratado como componente independente para preservar compatibilidade com os endpoints e regras de seguranca ja existentes.

