# Listagem de OS - Fix de classe do child row no mobile

## Contexto
No mobile, a linha expandida da tabela estava sendo renderizada com classe `os-responsive-child-row`, mas parte do CSS de responsividade estava condicionada apenas a `tr.child`.

## Correcao aplicada
- os seletores foram ampliados para cobrir `tr.child` e `tr.os-responsive-child-row`
- a linha expandida agora recebe corretamente as regras de:
  - largura total
  - remocao de pseudo-label tecnico
  - painel de detalhes com layout legivel

## Impacto
- elimina regressao de texto empilhado no detalhe de `Equipamento`
- estabiliza o comportamento do `+` em diferentes renderizacoes do DataTables
