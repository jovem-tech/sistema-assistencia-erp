# Correcao - orcamento convertido editado sem reaprovacao

Data: 29/04/2026

## Problema

Depois da liberacao da edicao em orcamentos `convertidos`, o formulario podia salvar alteracoes mantendo o status final sem abrir nova rodada de aprovacao.

Isso deixava o fluxo comercial inconsistente:

- o conteudo era alterado;
- o cliente nao era novamente chamado para autorizar;
- a trilha operacional ficava diferente do comportamento de orcamentos `aprovados`.

## Ajuste aplicado

- `STATUS_CONVERTIDO` foi incluido na regra `requiresReapprovalAfterEdit()`;
- a transicao `convertido -> reenviar_orcamento` passou a ser aceita no `OrcamentoService`;
- o mesmo registro convertido agora sobe a versao e volta para reenvio quando for alterado.

## Resultado

Orcamentos convertidos editados passam a seguir o mesmo padrao de reaprovacao dos demais estados finais aprovados, sem criar revisao separada.
