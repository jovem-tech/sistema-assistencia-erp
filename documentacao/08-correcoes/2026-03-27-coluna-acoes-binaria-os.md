# Listagem de OS - Coluna Acoes totalmente visivel ou no expansor

## Contexto
Em alguns cenarios de largura intermediaria, a coluna `Acoes` ainda permanecia visivel na grade principal mesmo sem caber por completo, o que deixava o ultimo conjunto de botoes cortado na borda direita da tabela.

## Correcao aplicada
- a rotina responsiva passou a medir o overflow real do wrapper da tabela apos aplicar a visibilidade inicial das colunas
- quando esse overflow residual acontece com `Acoes` ainda visivel, a coluna e ocultada imediatamente
- os botoes passam a ser renderizados apenas no child row do expansor `+`

## Impacto
- elimina a meia-exibicao da coluna `Acoes`
- preserva a usabilidade da tabela sem scroll lateral
- mantem o acesso completo aos botoes operacionais, agora pelo painel expansivel quando necessario
