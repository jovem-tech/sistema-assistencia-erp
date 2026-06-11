# Correcao - modal de baixa da OS sem scrollbar e com responsividade incompleta

Data: `07/06/2026`

## Sintoma

O modal `Baixa da OS` podia cortar a parte inferior do conteudo em janelas com pouca altura, sem liberar scrollbar visivel. Em telas menores, alguns blocos tambem ficavam apertados demais, especialmente em:

- `Recebimentos desta baixa`
- `Resumo financeiro e lucro`
- rodape com botoes de acao

## Causa

O `#osClosureModal` ficou fora do conjunto de seletores que ja aplicava:

- `display: flex` no `modal-content`
- `max-height` no dialog scrollable
- `overflow-y: auto` no `modal-body`
- ajustes responsivos especificos do modulo `/os`

Ou seja, os modais de `status`, `datas` e `orcamento` estavam endurecidos contra corte vertical, mas o de `baixa` ainda nao.

## Correcao aplicada

Arquivos principais:

- `public/assets/css/design-system/layouts/os-list-layout.css`
- `app/Views/os/index.php`

Ajustes:

- inclusao do `#osClosureModal` no mesmo grupo estrutural de altura e overflow dos outros modais da listagem;
- manutencao do `modal-footer` acessivel com corpo rolavel;
- reforco de empilhamento e espacamento para `767px`, `575px`, `430px`, `390px`, `360px` e `320px`;
- melhoria de quebra de linha em titulos, resumos e botoes.

## Validacao executada

- `php -l app/Views/os/index.php`
- revisao do CSS em `public/assets/css/design-system/layouts/os-list-layout.css`
- conferencia do contrato visual do modal com os demais modais da tela `/os`

## Resultado esperado

O modal de baixa passa a:

- rolar internamente quando faltar altura visivel;
- manter o rodape acessivel;
- evitar corte lateral ou compressao excessiva em telas pequenas;
- preservar leitura e clicabilidade dos botoes em mobile.
