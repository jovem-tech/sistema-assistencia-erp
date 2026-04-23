# Manual do Usuario - Estoque

## Visao geral
O modulo `Estoque de Pecas` controla cadastro, saldo e movimentacao de pecas utilizadas em OS e orcamentos.

Caminho: `Operacional > Estoque de Pecas`

## Cadastro e edicao de peca
Campos principais:
- `Nome`
- `Codigo` / `Codigo fabricante`
- `Categoria`
- `Tipo Equipamento` (novo)
- `Modelos compativeis`
- `Preco de custo`
- `Preco de venda`
- `Quantidade atual`
- `Estoque minimo`

Regra operacional:
- pecas inativas (`ativo = 0`) nao aparecem na busca Select2 da OS.

## Listagem
A grade de estoque agora mostra a coluna:
- `Tipo Equipamento`

Uso recomendado:
- padronize o tipo (ex.: `Smartphone`, `Notebook`, `Desktop`);
- use `Diverso` para pecas genericas;
- isso melhora a filtragem no Select2 da OS, junto com `Categoria`.

## Importacao e exportacao CSV
- `Exportar CSV`: inclui `tipo_equipamento`.
- `Baixar Modelo CSV`: inclui `tipo_equipamento`.
- `Importar CSV`: aceita `tipo_equipamento` e `tipo equipamento`.

## Fluxo com OS (peca sem estoque)
Quando uma peca com estoque `0` e adicionada na OS:
- o item e permitido;
- o sistema nao faz baixa de estoque nesse momento;
- o item fica com status de pendencia (`sem_estoque`, `necessaria_aquisicao` ou `aguardando_compra`);
- a linha do item exibe acao `Resolver pendencia`.

Ao resolver pendencia:
- pode registrar entrada de peca;
- pode reservar automaticamente para a OS;
- pode registrar despesa de compra no financeiro;
- o status do item e atualizado para estado operacional correspondente.
