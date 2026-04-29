# Correcao da aba unificada de orcamento e financeiro

Data: 29/04/2026
Modulo: Orcamentos

## Problema

Depois da reorganizacao em abas, os blocos de itens e financeiro ficaram separados, exigindo troca extra de aba durante a montagem e revisao do orcamento.

## Correcao aplicada

- a aba `Financeiro do orcamento` foi absorvida pela aba `Orcamento e financeiro`;
- o card `orcSecaoFinanceiro` passou a ficar logo abaixo de `orcSecaoItens` no mesmo `tab-pane`;
- os hooks e IDs usados no recalculo do formulario foram mantidos para evitar regressao.

## Resultado esperado

- o operador consegue montar itens e revisar subtotal, desconto, acrescimo e total final sem sair da mesma aba;
- o fluxo fica mais rapido em desktop e tambem em telas compactas, com menos toques para concluir o orcamento.
