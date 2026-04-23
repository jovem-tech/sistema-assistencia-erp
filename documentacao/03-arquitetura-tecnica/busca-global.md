# Arquitetura Tecnica: Busca Global Inteligente

A busca global e um componente transversal da navbar que unifica consultas em modulos, OS, clientes, equipamentos, mensagens, servicos e pecas.

## Componentes principais

### 1. GlobalSearchService (`app/Libraries/GlobalSearchService.php`)

Responsavel por:
- interpretar o parametro `filter` (multi-selecao, incluindo `all`);
- validar permissao por categoria via `can(modulo, visualizar)`;
- consultar os modelos de cada dominio;
- agrupar os resultados por secao para renderizacao no frontend.

Regra tecnica obrigatoria para catalogo operacional:
- busca de `servicos` deve retornar apenas registros disponiveis (`status = 'ativo'` e `encerrado_em IS NULL`);
- busca de `pecas` deve retornar apenas registros ativos (`ativo = 1`).

### 2. GlobalSearch Controller (`app/Controllers/GlobalSearch.php`)

- expõe `GET /api/busca-global` para AJAX;
- valida termo minimo antes de consultar o service;
- retorna payload JSON agrupado.

### 3. Frontend (`public/assets/js/global-search.js`)

- debounce de 300 ms para reduzir chamadas;
- gestao de filtros com selecao multipla;
- navegacao por teclado (`ArrowUp`, `ArrowDown`, `Enter`, `Escape`);
- renderizacao dinamica por grupo.

## Integracao com modelos

- `ServicoModel::searchAtivos()` centraliza a busca de servicos operacionais.
- `PecaModel::search()` centraliza a busca de pecas ativas.

Observacao importante de query:
- quando houver combinacao de `like` e `orLike`, as condicoes textuais devem ser agrupadas antes do filtro de ativo para evitar retorno indevido de registros inativos/encerrados.

## Limites e comportamento

- minimo de 2 caracteres para disparo da busca;
- limite por grupo definido no backend (ex.: 5 para servicos, 10 para pecas);
- resposta agrupada por categoria para consumo direto do dropdown.

## Pontos de manutencao

Em evolucoes da busca global, revisar em conjunto:
- `app/Libraries/GlobalSearchService.php`
- `app/Models/ServicoModel.php`
- `app/Models/PecaModel.php`
- `app/Views/layouts/navbar.php`
- `public/assets/js/global-search.js`
