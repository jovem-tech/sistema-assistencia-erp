# Integracao do ERP com o Setup Vem Fazer

Atualizado em 27/05/2026.

## Objetivo

Este fluxo adiciona um caminho oficial para publicar o ERP `Sistema de Assistencia Tecnica` dentro do ecossistema do `Setup Vem Fazer`, reaproveitando a VPS ja preparada com:

- Docker Engine;
- Docker Swarm ativo;
- Traefik publicado;
- rede interna/externa ja conhecida pelo instalador principal.

O foco aqui nao e provisionar a VPS do zero, e sim encaixar o ERP como mais uma stack dentro do ambiente que o `setup-vemfazer.sh` ja administra.

## Arquivos envolvidos

- `scripts/docker/install-vemfazer-stack.sh`
- `docker/swarm/setup-vemfazer-stack.yml`
- `docker/swarm/setup-vemfazer.env.example`

## Como a integracao funciona

O script `install-vemfazer-stack.sh` faz cinco etapas:

1. valida Docker, Swarm e a rede usada pelo Traefik;
2. clona ou atualiza o repositorio do ERP;
3. grava o arquivo `docker/swarm/setup-vemfazer.env` com as credenciais e parametros do stack;
4. executa `docker build` da imagem do ERP localmente na VPS;
5. publica a stack com `docker stack deploy`.

Isso evita depender de imagem publica pronta e aproveita o `Dockerfile` oficial do projeto.

## Execucao direta na VPS

Se a VPS ja passou pela etapa `Traefik & Portainer` do Setup Vem Fazer, voce pode rodar direto:

```bash
bash <(curl -fsSL https://raw.githubusercontent.com/jovem-tech/sistema-assistencia-erp/main/scripts/docker/install-vemfazer-stack.sh | tr -d '\r')
```

O script vai solicitar:

- URL do repositorio do ERP;
- branch;
- diretorio local do checkout;
- nome da stack;
- tag da imagem Docker;
- dominio publico do ERP;
- nome da rede do Traefik no Swarm;
- nome do `certresolver`;
- nome/usuario/senha do banco;
- `APP_ENCRYPTION_KEY`.

## Integrando no menu do `setup-vemfazer.sh`

Se voce quiser colocar o ERP como uma opcao oficial dentro do menu do Setup Vem Fazer, use um atalho simples para chamar o script acima.

### Entrada visual de menu

Adicione uma linha no bloco do menu principal, por exemplo:

```bash
echo -e "${amarelo}[ 90 ]${reset} - ${branco}ERP Jovem Tech${reset}"
```

### Handler no `case`

Adicione um bloco semelhante a este:

```bash
90|sistema-hml|erpjovemtech|jovemtech|ERPJOVEMTECH)
    bash <(curl -fsSL https://raw.githubusercontent.com/jovem-tech/sistema-assistencia-erp/main/scripts/docker/install-vemfazer-stack.sh | tr -d '\r')
    ;;
```

Observacoes:

- troque o numero `90` se ele conflitar com outra opcao do menu;
- se preferir mais seguranca operacional, troque `main` por uma branch de release ou por um `commit` fixo do repositorio;
- o script ja cuida de clonar/atualizar o ERP e publicar a stack do zero.

## Arquivos gerados no servidor

Depois da execucao, o fluxo deixa registrado:

- checkout do ERP em `ERP_SOURCE_DIR` (padrao: `/opt/sistema-hml`);
- arquivo de ambiente do stack em `/opt/sistema-hml/docker/swarm/setup-vemfazer.env`;
- metadado operacional em `/root/dados_vps/dados_sistema_hml`, quando a pasta `dados_vps` existir.

O arquivo `setup-vemfazer.env` fica com permissao `600` e concentra os segredos do stack.

## Parametros importantes

### `TRAEFIK_NETWORK`

Use exatamente o mesmo nome da rede usada pelo Traefik do Setup Vem Fazer.

Na pratica, costuma ser a mesma `Rede interna` informada na instalacao inicial do ambiente.

### `TRAEFIK_CERTRESOLVER`

No fork analisado do Setup Vem Fazer, o Traefik sobe com:

```text
letsencryptresolver
```

Se o seu ambiente usa outro nome, ajuste na pergunta do instalador ou no arquivo `.env`.

### `APP_IMAGE`

O default e:

```text
sistema-hml:latest
```

Como a stack do ERP roda fixada no `manager`, a imagem local gerada pelo `docker build` ja atende o deploy padrao desse fluxo.

## Validacao depois do deploy

Use:

```bash
docker stack services sistema-hml
docker service ps sistema-hml_app
docker service logs -f sistema-hml_app
docker service logs -f sistema-hml_db
```

E teste o dominio:

```bash
curl -k -I https://SEU_DOMINIO/login
```

## Troubleshooting rapido

### A rede do Traefik nao existe

- confira o nome usado na instalacao original do Setup Vem Fazer;
- valide com `docker network ls`;
- ajuste `TRAEFIK_NETWORK` para o nome real da rede.

### O app sobe, mas o dominio nao responde

- valide DNS;
- valide o nome do `certresolver`;
- confirme as labels do Traefik com `docker service inspect sistema-hml_app`.

### O build da imagem falha

- confira se o checkout do ERP foi concluido;
- veja se o `Dockerfile` esta presente em `ERP_SOURCE_DIR`;
- revise os logs do `docker build`.

### Preciso refazer o deploy depois de atualizar a branch

Basta executar o mesmo comando novamente. O script atualiza o checkout, refaz a imagem e republica o stack.
