# Correcao das abas fora do design system na visualizacao do orcamento

Data: 29/04/2026
Modulo: Orcamentos

## Problema

As abas da tela `Visualizar Orcamento` passaram a quebrar em multiplas linhas, fugindo do padrao visual do design system adotado nas demais telas do ERP.

## Correcao aplicada

- removida a grade responsiva criada localmente para as abas;
- reaplicado o padrao `ds-tabs-scroll` com linha unica;
- convertida a navegacao para `nav-tabs`, alinhando markup e estilo ao restante do sistema.

## Resultado esperado

- as abas voltam a ficar em linha unica;
- a navegacao visual fica coerente com outras telas do ERP que usam o mesmo design system.
