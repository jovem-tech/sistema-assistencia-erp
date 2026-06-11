# Manual do UsuÃ¡rio - Estoque

## VisÃ£o geral
O mÃ³dulo `Estoque de PeÃ§as` controla cadastro, saldo e movimentaÃ§Ã£o de peÃ§as utilizadas em OS e orÃ§amentos.

Caminho: `Operacional > Estoque de PeÃ§as`

## Cadastro e ediÃ§Ã£o de peÃ§a
Campos principais:
- `Nome`
- `CÃ³digo` / `CÃ³d. Fabricante`
- `Categoria`
- `Tipo de Equipamento`
- `Modelos CompatÃ­veis`
- `PreÃ§o de Custo`
- `PreÃ§o de Venda`
- `Quantidade Atual`
- `Estoque MÃ­nimo`

Regra operacional:
- peÃ§as inativas (`ativo = 0`) nÃ£o aparecem na busca Select2 da OS.

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
- `CÃ³digo`
- `Nome`
- `Categoria`
- `Tipo de Equipamento`
- `Custo`
- `Venda`
- `Qtd`
- `MÃ­n.`
- `AÃ§Ãµes`

Uso recomendado:
- padronize o tipo (ex.: `Smartphone`, `Notebook`, `Desktop`);
- use `Diverso` para peÃ§as genÃ©ricas;
- isso melhora a filtragem no Select2 da OS, junto com `Categoria`.

## ImportaÃ§Ã£o e exportaÃ§Ã£o CSV
- `Exportar CSV`: inclui `tipo_equipamento`.
- `Baixar Modelo de Estoque (CSV)`: inclui `tipo_equipamento`.
- `Importar Estoque (CSV)`: aceita `tipo_equipamento` e `tipo equipamento`.

## MovimentaÃ§Ãµes
Cada peÃ§a possui tela dedicada de `MovimentaÃ§Ãµes`, com:
- saldo atual;
- preÃ§o de custo;
- preÃ§o de venda;
- histÃ³rico com `Data`, `Tipo`, `Quantidade`, `Motivo`, `OS` e `ResponsÃ¡vel`.

## Fluxo com OS (peÃ§a sem estoque)
Quando uma peÃ§a com estoque `0` Ã© adicionada na OS:
- o item Ã© permitido;
- o sistema nÃ£o faz baixa de estoque nesse momento;
- o item fica com status de pendÃªncia (`sem_estoque`, `necessaria_aquisicao` ou `aguardando_compra`);
- a linha do item exibe a aÃ§Ã£o `Resolver pendÃªncia`.

Ao resolver a pendÃªncia:
- pode registrar entrada de peÃ§a;
- pode reservar automaticamente para a OS;
- pode registrar despesa de compra no financeiro;
- o status do item Ã© atualizado para o estado operacional correspondente.
