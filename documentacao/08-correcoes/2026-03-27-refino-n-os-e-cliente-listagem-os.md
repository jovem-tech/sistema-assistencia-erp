# Listagem de OS - Refino das colunas N OS e Cliente

## Contexto
Depois do ajuste anterior, a grade ainda podia ceder um pouco mais de largura para outras colunas sem prejudicar a leitura de `N OS` e `Cliente`.

## Correcao aplicada
- reduzida novamente a largura-base da coluna `N OS`
- reduzida levemente a largura-base da coluna `Cliente`
- mantida a regra de nao quebrar o numero da OS
- mantida a quebra semantica do nome em duas linhas para clientes com quatro palavras ou mais

## Impacto
- melhor distribuicao horizontal da tabela
- menos desperdicio de largura em colunas estruturais
- preservacao da legibilidade dos identificadores principais
