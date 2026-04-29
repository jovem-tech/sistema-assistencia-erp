# Correcao - OS Cancelada nao Saia do Cancelado apos Nova Aprovacao

Data: 29/04/2026

## Problema

Depois do hotfix da `2.16.25`, a OS cancelada ja voltava para `aguardando_autorizacao` durante o reenvido do orcamento.

Mas ainda havia um segundo gap:

- quando o cliente aprovava a nova versao e o orcamento passava a exibir `2ª aprovacao` ou equivalente;
- a OS podia continuar em `cancelado`, em vez de seguir para `aguardando_reparo`.

## Correcao aplicada

- a reabertura de OS cancelada passou a considerar tambem destinos operacionais `aguardando_reparo`;
- o reset agora e forcado quando o orcamento vinculado chega a `aprovado` ou `convertido`;
- a correcao foi replicada no fluxo interno do modulo e no controller complementar de aprovacao publica.

## Resultado esperado

- uma OS cancelada vinculada a orcamento revisado volta para `aguardando_autorizacao` durante a nova rodada comercial;
- se o cliente aprovar essa nova rodada, a OS deixa `cancelado` e passa para `aguardando_reparo`;
- a coluna `Status` da listagem `/os` deixa de mostrar combinacao incoerente entre `Cancelado` e `Orcamento: 2ª aprovacao`.
