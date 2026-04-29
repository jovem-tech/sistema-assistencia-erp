# Correcao das abas com scroll na visualizacao do orcamento

Data: 29/04/2026
Modulo: Orcamentos

## Problema

As abas da tela `Visualizar Orcamento` dependiam de rolagem horizontal em resolucoes menores, o que prejudicava usabilidade e leitura em telas compactas.

## Correcao aplicada

- removido o `nowrap` com `overflow-x: auto` da navegacao das abas;
- adicionada distribuicao por grade responsiva com quebra em linhas;
- ajustados tamanho minimo, padding e alinhamento dos botoes para `<= 430px`, `<= 360px` e `<= 320px`.

## Resultado esperado

- o operador navega pelas abas sem arrastar lateralmente a interface;
- a tela fica mais previsivel em mobile, com botoes legiveis e area de toque consistente.
