# Listagem de OS - Ajuste de colunas e quebra de nome do cliente

## Contexto
A grade da listagem ainda desperdicava largura nas colunas `Status` e `Valor Total`, enquanto nomes longos de clientes ficavam comprimidos de forma pouco legivel.

## Correcao aplicada
- reduzida a largura-base das colunas `Status` e `Valor Total`
- mantida a visibilidade completa de badge de status e valor monetario
- adicionada quebra semantica em `Cliente` quando o nome possui quatro palavras ou mais:
  - a primeira linha leva as duas primeiras palavras
  - a segunda linha recebe o restante do nome

## Impacto
- mais area util para leitura de cliente e equipamento
- visual mais equilibrado em notebook e desktop compacto
- melhor legibilidade de nomes longos sem reintroduzir truncamento agressivo
