# Correcao da scrollbar visivel nas abas da visualizacao do orcamento

Data: 29/04/2026
Modulo: Orcamentos

## Problema

As abas da tela `Visualizar Orcamento` estavam em linha unica, mas ainda exibiam a barra de rolagem horizontal do componente, criando ruido visual e fugindo do acabamento esperado para a tela.

## Correcao aplicada

- aumentado o nivel de especificidade do CSS local em `app/Views/orcamentos/show.php`;
- sobrescrita a configuracao global de `scrollbar-width` do `ds-tabs-scroll` apenas nessa view;
- removido o espacamento inferior associado ao trilho de scroll.

## Resultado esperado

- as abas continuam em linha unica e dentro do design system;
- a barra de rolagem horizontal deixa de ficar aparente na visualizacao do orcamento.
