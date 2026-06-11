# Deploy do ERP em Docker Swarm (Contabo + Traefik)

Atualizado em 27/05/2026.

Este guia instala o ERP da assistencia tecnica em container Docker no seu servidor Contabo, aproveitando o Traefik ja ativo no host e mantendo persistencia de banco, uploads e `writable/`.

## Atalho para o ecossistema Setup Vem Fazer

Se a sua VPS ja foi preparada pelo `setup-vemfazer.sh`, existe agora um fluxo dedicado para encaixar o ERP nesse ambiente sem remontar o Swarm do zero:

- script: `scripts/docker/install-vemfazer-stack.sh`
- stack dedicada: `docker/swarm/setup-vemfazer-stack.yml`
- guia: `documentacao/10-deploy/integracao-setup-vemfazer.md`

Esse caminho faz o `build` local da imagem do ERP e publica a stack aproveitando o Traefik/rede ja existentes no ambiente Vem Fazer.

## 1. O que foi adicionado ao repositorio

- `Dockerfile`
- `.dockerignore`
- `docker/runtime/entrypoint.sh`
- `docker/runtime/php.ini`
- `docker/runtime/vhost.conf`
- `docker/swarm/contabo-stack.yml`
- `docker/swarm/contabo.env.example`
- `scripts/docker/deploy-contabo-swarm.sh`

## 2. Arquitetura alvo

```text
Internet
  -> Traefik (host Docker/Swarm)
  -> service app (PHP 8.2 + Apache + CodeIgniter 4)
  -> service db (MySQL 8)

Volumes persistentes:
  - sistema_hml_mysql
  - sistema_hml_writable
  - sistema_hml_public_uploads
```

## 3. Requisitos no servidor

- Docker Engine ativo
- Docker Swarm inicializado (`docker info | grep Swarm`)
- rede externa do Traefik existente
- dominio apontando para a VPS

Validacoes uteis:

```bash
docker info | grep -i swarm
docker network ls
docker service ls
```

## 4. Preparar o codigo no servidor

Exemplo:

```bash
cd /opt
git clone <URL_DO_REPOSITORIO> sistema-hml
cd /opt/sistema-hml
```

Se o codigo ja estiver no servidor, apenas atualize o checkout da branch desejada.

## 5. Preparar o arquivo de ambiente do stack

Copie o modelo:

```bash
cp docker/swarm/contabo.env.example docker/swarm/contabo.env
```

Edite os campos obrigatorios:

```env
STACK_NAME=sistema-hml
APP_IMAGE=sistema-hml:latest
APP_HOST=sistema.seudominio.com
TRAEFIK_NETWORK=traefik-public
TRAEFIK_CERTRESOLVER=letsencrypt
APP_TIMEZONE=America/Fortaleza
APP_ENCRYPTION_KEY=hex2bin:SEU_HEX_AQUI
MYSQL_DATABASE=sistema_hml
MYSQL_USER=sistema_hml
MYSQL_PASSWORD=SENHA_FORTE
MYSQL_ROOT_PASSWORD=SENHA_ROOT_FORTE
LEGACY_DATABASE_HOST=db
LEGACY_DATABASE_PORT=3306
LEGACY_DATABASE_NAME=sistema_hml
LEGACY_DATABASE_USER=sistema_hml
LEGACY_DATABASE_PASSWORD=SENHA_FORTE
```

Gerar chave forte:

```bash
openssl rand -hex 16
```

Observacoes:

- `APP_HOST` deve ser exatamente o dominio publicado no Traefik.
- `TRAEFIK_NETWORK` precisa bater com a rede externa do seu proxy.
- `TRAEFIK_CERTRESOLVER` precisa usar o nome real do resolver configurado no Traefik.

## 6. Publicar o stack

Use o script incluido:

```bash
chmod +x scripts/docker/deploy-contabo-swarm.sh
./scripts/docker/deploy-contabo-swarm.sh
```

Ou informe um env file especifico:

```bash
./scripts/docker/deploy-contabo-swarm.sh /opt/sistema-hml/docker/swarm/contabo.env
```

O script faz:

1. carrega as variaveis do `contabo.env`;
2. executa `docker build` da imagem do ERP;
3. publica o stack com `docker stack deploy`.

## 7. O que o container faz ao iniciar

O `docker/runtime/entrypoint.sh`:

1. gera o `.env` interno do CodeIgniter com base nas variaveis do container;
2. cria/ajusta permissoes de `writable/` e `public/uploads/`;
3. aguarda o MySQL ficar acessivel;
4. executa `php spark migrate --all`;
5. limpa cache do framework;
6. sobe o Apache em `/var/www/html/public`.

## 8. Validacao depois do deploy

```bash
docker stack services sistema-hml
docker service ps sistema-hml_app
docker service logs -f sistema-hml_app
docker service logs -f sistema-hml_db
```

Validacoes HTTP:

```bash
curl -I https://SEU_DOMINIO/login
curl -I https://SEU_DOMINIO/
```

Validacoes internas:

```bash
docker exec -it $(docker ps --filter name=sistema-hml_app -q | head -n 1) php spark migrate:status
docker exec -it $(docker ps --filter name=sistema-hml_app -q | head -n 1) php spark cache:clear
```

## 9. Volumes persistentes

Este stack preserva:

- banco MySQL em `sistema_hml_mysql`;
- uploads publicos em `sistema_hml_public_uploads`;
- sessoes, logs e cache do CI4 em `sistema_hml_writable`.

Por isso, um `docker service update` ou novo `docker stack deploy` nao deve apagar os dados operacionais.

## 10. Integracao com o modulo de WhatsApp

Este deploy containeriza apenas o ERP PHP.

O gateway Node.js em `whatsapp-api/` continua sendo um servico independente e deve manter:

- validacao de `X-Api-Token`;
- validacao de `X-ERP-Origin`;
- execucao separada do ERP.

Se voce quiser containerizar tambem o gateway depois, faca isso em stack proprio ou como servico adicional sem quebrar os endpoints `/status` e `/create-message`.

## 11. Ajuste para banco externo

Se voce ja possui um MySQL/MariaDB fora deste stack:

1. remova o servico `db` de `docker/swarm/contabo-stack.yml`;
2. altere `DATABASE_HOST` e os `LEGACY_DATABASE_*` para o host externo;
3. mantenha os volumes `writable` e `public/uploads`.

## 12. Troubleshooting rapido

### Aplicacao sobe, mas abre erro de conexao com banco

- confirme `MYSQL_PASSWORD`, `MYSQL_USER` e `MYSQL_DATABASE`;
- veja os logs do app e do db;
- valide se o servico `db` foi criado no mesmo stack.

### Traefik nao publica o dominio

- confirme se a rede externa existe (`docker network ls`);
- confirme o valor de `TRAEFIK_NETWORK`;
- confirme se `APP_HOST` bate exatamente com o dominio publico;
- confirme o nome do `certresolver`.

### Uploads somem apos recriar o servico

- valide se o volume `sistema_hml_public_uploads` esta montado;
- nao substitua esse volume por bind efemero sem necessidade.

### Sessao cai ou logs nao persistem

- confirme o volume `sistema_hml_writable`;
- confirme se o `session.savePath` foi gerado como `/var/www/html/writable/session`.

### Migrations falham no boot

- cheque se o banco ja aceitou conexao;
- se precisar subir primeiro e migrar depois manualmente, ajuste no env:

```env
RUN_MIGRATIONS=false
WAIT_FOR_DB=true
```

Depois execute manualmente:

```bash
docker exec -it $(docker ps --filter name=sistema-hml_app -q | head -n 1) php spark migrate --all
```

## 13. Fluxo recomendado de atualizacao

```bash
cd /opt/sistema-hml
git pull
./scripts/docker/deploy-contabo-swarm.sh
docker service logs -f sistema-hml_app
```

## 14. Checklist final

- `APP_HOST` configurado
- `TRAEFIK_NETWORK` correto
- `TRAEFIK_CERTRESOLVER` correto
- senha forte de banco definida
- `APP_ENCRYPTION_KEY` definido
- volumes persistentes criados
- `/login` respondendo `200`
- migrations concluidas sem erro
