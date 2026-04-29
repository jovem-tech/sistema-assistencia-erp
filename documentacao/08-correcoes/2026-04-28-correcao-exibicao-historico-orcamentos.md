# Correcao - Exibicao do Historico de Status dos Orcamentos

Data: 28/04/2026

## Problema

Na tela `Visualizar Orcamento`, o card `Historico de status` podia mostrar:

- `Sem historico de status`

mesmo quando o controller carregava eventos reais do timeline em `orcamento_status_historico`.

## Correcao aplicada

- alinhado o mapeamento da variavel usada pela view `app/Views/orcamentos/show.php`;
- restaurada a leitura correta do array retornado por `OrcamentoStatusHistoricoModel::timeline()`;
- preservado o restante do fluxo de rastreabilidade sem alterar a model ou a gravacao de historico.

## Resultado esperado

- o usuario volta a enxergar o historico de mudancas de status no proprio orcamento;
- a trilha de reaprovacoes e transicoes comerciais fica visivel na interface;
- a tela deixa de apresentar falso vazio quando os eventos existem no backend.
