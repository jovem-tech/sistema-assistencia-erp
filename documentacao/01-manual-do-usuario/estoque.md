# Manual do Usuário - Estoque

## Visão geral
O módulo `Estoque de Peças` controla cadastro, saldo e movimentação de peças utilizadas em OS e orçamentos.

Caminho: `Operacional > Estoque de Peças`

## Cadastro e edição de peça
Campos principais:
- `Nome`
- `Código` / `Cód. Fabricante`
- `Categoria`
- `Tipo de Equipamento`
- `Modelos Compatíveis`
- `Preço de Custo`
- `Preço de Venda`
- `Quantidade Atual`
- `Estoque Mínimo`

Regra operacional:
- peças inativas (`ativo = 0`) não aparecem na busca Select2 da OS.

## Cadastro rapido pelo Orcamento

Quando o operador estiver montando um orcamento e a `Peca` ainda nao existir no catalogo:

- escolha `Peca` na linha do item;
- clique em `Cadastrar`;
- informe pelo menos `Nome`, `Preco de Custo` e `Preco de Venda`;
- o sistema salva a peca por AJAX e ja seleciona esse item na mesma linha do orcamento, sem sair da tela.

Campos usados no modal rapido:
- `Nome`
- `Categoria`
- `Tipo de Equipamento`
- `Preco de Custo`
- `Preco de Venda`
- `Quantidade Inicial`

Regra operacional complementar:
- o cadastro rapido cria a peca com `ativo = 1`, para que ela fique imediatamente disponivel na busca do proprio orcamento.

## Listagem
A grade de estoque mostra:
- `Código`
- `Nome`
- `Categoria`
- `Tipo de Equipamento`
- `Custo`
- `Venda`
- `Qtd`
- `Mín.`
- `Ações`

Uso recomendado:
- padronize o tipo (ex.: `Smartphone`, `Notebook`, `Desktop`);
- use `Diverso` para peças genéricas;
- isso melhora a filtragem no Select2 da OS, junto com `Categoria`.

## Importação e exportação CSV
- `Exportar CSV`: inclui `tipo_equipamento`.
- `Baixar Modelo de Estoque (CSV)`: inclui `tipo_equipamento`.
- `Importar Estoque (CSV)`: aceita `tipo_equipamento` e `tipo equipamento`.

## Movimentações
Cada peça possui tela dedicada de `Movimentações`, com:
- saldo atual;
- preço de custo;
- preço de venda;
- histórico com `Data`, `Tipo`, `Quantidade`, `Motivo`, `OS` e `Responsável`.

## Fluxo com OS (peça sem estoque)
Quando uma peça com estoque `0` é adicionada na OS:
- o item é permitido;
- o sistema não faz baixa de estoque nesse momento;
- o item fica com status de pendência (`sem_estoque`, `necessaria_aquisicao` ou `aguardando_compra`);
- a linha do item exibe a ação `Resolver pendência`.

Ao resolver a pendência:
- pode registrar entrada de peça;
- pode reservar automaticamente para a OS;
- pode registrar despesa de compra no financeiro;
- o status do item é atualizado para o estado operacional correspondente.
