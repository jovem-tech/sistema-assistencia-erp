# Correcao - Dashboard: alinhamento do "Ver ..." no rodape dos 4 cards

Data: 23/03/2026

## Problema observado

No bloco superior do Dashboard, os links de acao (`Ver detalhes`, `Ver financeiro`, `Ver OS entregues`, `Ver operacao`) nao permaneciam alinhados no fundo do card quando as alturas dos cards eram equalizadas no grid.

## Causa tecnica

Os cards estavam com `height: 100%` (via grid + `h-100`), mas sem estrutura flex para distribuir o espaco interno.  
Com isso, o footer era renderizado logo apos o conteudo, sobrando area vazia abaixo em cards com menos conteudo.

## Ajuste aplicado

Arquivo alterado:
- `public/assets/css/design-system/layouts/dashboard-layout.css`

Regras adicionadas no escopo `.ds-dashboard-layout`:
- `display: flex; flex-direction: column;` no `.stat-card`;
- `flex: 1 1 auto;` no `.stat-card-body`;
- `margin-top: auto;` no `.stat-card-footer`.

## Resultado esperado

- Os 4 cards mantem o elemento `Ver ...` alinhado na parte inferior.
- Comportamento consistente em desktop, tablet e mobile.
- Sem impacto nos demais cards fora do dashboard (escopo isolado em `.ds-dashboard-layout`).
