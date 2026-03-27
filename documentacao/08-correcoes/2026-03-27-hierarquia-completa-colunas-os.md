# Listagem de OS - Hierarquia completa para recolhimento de colunas

## Contexto
Mesmo apos os ajustes anteriores, ainda restavam cenarios em que `Datas` ou `Equipamento` podiam permanecer na grade principal quando a largura real ja nao comportava toda a tabela com conforto visual.

## Correcao aplicada
- a estrategia de overflow real foi estendida para duas colunas adicionais:
  - `Datas`
  - `Equipamento`
- a grade passou a seguir uma ordem unica de recolhimento:
  - `Acoes`
  - `Relato`
  - `Status`
  - `Datas`
  - `Equipamento`
- cada coluna e movida integralmente para o expansor `+` antes que qualquer corte parcial aconteca

## Impacto
- a grade fica previsivel em larguras intermediarias
- elimina cortes residuais em colunas operacionais
- reforca a estrategia de usar o child row como area de detalhes, em vez de tolerar overflow lateral
