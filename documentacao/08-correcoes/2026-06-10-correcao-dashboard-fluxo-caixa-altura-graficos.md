# Correcao - dashboard do Fluxo de Caixa com altura estavel nos graficos

Data: 10/06/2026
Migracao: nao houve alteracao de schema

## Problema

Na aba `Dashboard` de `Relatorios -> Fluxo de Caixa`, os graficos podiam inflar a altura total da pagina quando a aba era aberta ou recalculada. O efeito mais visivel era um canvas exageradamente alto, com a tela ficando quase toda em branco e os traços do grafico aparecendo esticados ao longo de uma rolagem vertical muito grande.

## Causa raiz

- os canvases do dashboard estavam presos a `height: 100%` via CSS;
- o container dos graficos usava apenas `min-height`, sem uma altura efetivamente controlada;
- como os charts sao inicializados de forma lazy dentro de uma aba Bootstrap, o `resize` podia acontecer num momento em que o layout ainda estava se estabilizando;
- essa combinacao fazia o Chart.js recalcular sobre uma base ambigua e permitia crescimento vertical indevido do canvas.

## Ajuste aplicado

- os containers `.cash-chart-shell` e `.cash-chart-shell-sm` passaram a usar alturas controladas no desktop;
- os breakpoints `<= 430px`, `<= 390px`, `<= 360px` e `<= 320px` receberam alturas especificas para manter leitura e evitar sobra vertical desnecessaria;
- o CSS deixou de forcar `height: 100%` no canvas;
- a inicializacao do dashboard agora agenda `resize()` e `update('none')` apos a aba ficar realmente visivel;
- o dashboard tambem reage ao `resize` da janela, preservando o comportamento em mobile e ao redimensionar o navegador.

## Validacao executada

- validacao desktop em viewport padrao `1280x720`;
- validacao mobile com viewport em `430px`, `390px`, `360px` e `320px`;
- em todos os cenarios, os graficos mantiveram altura previsivel e nao houve overflow horizontal no dashboard;
- `php -l app/Views/relatorios/view_fluxo_caixa.php`;
- `php spark migrate:status`;
- `php spark migrate`.

## Arquivos impactados

- `app/Views/relatorios/view_fluxo_caixa.php`
- `documentacao/01-manual-do-usuario/relatorios.md`
- `documentacao/01-manual-do-usuario/financeiro.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/08-correcoes/2026-06-10-correcao-dashboard-fluxo-caixa-altura-graficos.md`

## Observacoes

- nao houve alteracao em rotas, endpoints, permissao ou banco de dados;
- nao foi necessario ajustar `openDocPage`, porque a ajuda continua apontando para a documentacao do modulo financeiro ja existente.
