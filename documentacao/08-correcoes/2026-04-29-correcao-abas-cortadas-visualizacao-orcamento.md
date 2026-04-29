# Correcao das abas cortadas na visualizacao do orcamento

Data: 29/04/2026
Modulo: Orcamentos

## Problema

Mesmo sem exibir a barra de rolagem horizontal, a navegacao por abas da tela `Visualizar Orcamento` ainda deixava os ultimos destinos parcialmente fora da area visivel em larguras menores.

## Correcao aplicada

- cada aba passou a dividir a largura disponivel do container em vez de depender apenas do tamanho natural do texto;
- os rotulos ganharam versoes abreviadas para breakpoints menores;
- o padding e a tipografia foram compactados progressivamente para preservar legibilidade em `<= 430px`, `<= 390px`, `<= 360px` e `<= 320px`.

## Resultado esperado

- todas as abas permanecem visiveis dentro da largura da tela;
- a navegacao continua em linha unica e alinhada ao design system do ERP;
- a tela deixa de cortar o acesso visual aos ultimos destinos da navegacao.
