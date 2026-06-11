# Modulo: Precificacao

## Objetivo

O modulo de `Precificacao` centraliza a configuracao da formula de precificacao de pecas, servicos e categorias, com simulador operacional e overrides por regra.

## Controle de acesso

O modulo possui RBAC proprio:

- `precificacao:visualizar`
- `precificacao:editar`

## Rotas oficiais

- `GET /precificacao`
- `GET /precificacao/configuracao`
- `POST /precificacao/configuracao/salvar`
- `GET /precificacao/simulador`
- `GET /precificacao/categoria-encargos/{id}`
- `POST /precificacao/categoria-encargos/{id}`
- `GET /precificacao/categoria-override`
- `POST /precificacao/salvar`
- `POST /precificacao/simular-peca`
- `POST /precificacao/simular-servico`

## Estrutura tecnica

### Camada principal

- controller: `app/Controllers/Precificacao.php`
- services: `app/Services/PecaPrecificacaoService.php` e `app/Services/ServicoPrecificacaoService.php`
- views: `app/Views/precificacao/configuracao.php` e `app/Views/precificacao/index.php`

### Tabelas relacionadas

- `precificacao_componentes`
- `precificacao_parametros`
- `precificacao_categorias`
- `precificacao_categoria_encargos`
- `precificacao_servico_overrides`

## Operacao atual

A tela de configuracao permite ajustar:

- parametros gerais de pecas;
- componentes e encargos;
- categorias com override;
- ajustes por servico.

O simulador permite validar o resultado esperado para:

- peca;
- servico.

## Relacao com o restante do sistema

- o modulo e independente de `Orcamentos`;
- os itens do orcamento continuam consumindo os metadados de precificacao para exibir valores recomendados;
- o modulo de Estoque usa as configuracoes de precificacao na montagem do custo e do valor de venda de pecas.
