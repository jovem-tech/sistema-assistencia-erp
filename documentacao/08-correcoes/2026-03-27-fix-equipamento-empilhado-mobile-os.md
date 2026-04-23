# Listagem de OS - Fix de equipamento empilhado no mobile

## Contexto
No detalhe expandido do card mobile (`+`), o campo `Equipamento` estava sendo comprimido de forma extrema e renderizando o valor com uma letra por linha, comprometendo a leitura.

## Correcao aplicada
- ajuste do layout do child row mobile para empilhamento vertical de `label` e `valor`
- reforco de regras de quebra para manter texto horizontal e legivel
- remocao do pseudo-label residual (`Campo 1`) no detalhe expandido

## Impacto
- leitura normal do campo `Equipamento` no mobile
- painel expandido mais limpo e consistente
- reducao de ruído visual em telas pequenas
