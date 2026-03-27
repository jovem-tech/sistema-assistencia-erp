# Listagem de OS - Status e Relato sem corte parcial

## Contexto
Depois da correção da coluna `Acoes`, ainda havia cenarios em que `Status` ou `Relato` permaneciam na grade principal mesmo sem largura suficiente, causando corte visual ou leitura comprometida na borda direita da tabela.

## Correcao aplicada
- a protecao por overflow real do wrapper foi ampliada para tratar tambem `Relato` e `Status`
- a tabela agora recolhe as colunas em ordem de prioridade:
  - `Acoes`
  - `Relato`
  - `Status`
- cada coluna e removida integralmente da grade principal antes que o corte parcial aconteca

## Impacto
- elimina corte visual de badge de status e conteudo de relato
- mantem a tabela limpa e operacional em larguras intermediarias
- preserva acesso ao conteudo completo pelo expansor `+`
