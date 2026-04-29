# Correcao - OS Cancelada nao Reabria com Orcamento Reenviado

Data: 29/04/2026

## Problema

Na teoria, a regra comercial previa que uma OS cancelada voltasse para `aguardando_autorizacao` quando o orcamento fosse alterado e reenviado ao cliente.

Na pratica, isso nao acontecia em todos os cenarios porque a sincronizacao so forçava a reabertura para alguns status, como `reenviar_orcamento`, `rejeitado` e `cancelado`.

Quando o orcamento chegava a `aguardando_resposta` apos o envio, a OS ainda podia permanecer em `cancelado`.

## Correcao aplicada

- a sincronizacao passou a detectar OS com `status` ou `estado_fluxo` em `cancelado`;
- quando o orcamento volta para um estado comercial ativo cujo destino operacional e `aguardando_autorizacao`, o reset da OS agora e forçado;
- a regra cobre `pendente_envio`, `enviado`, `aguardando_resposta`, `reenviar_orcamento`, `aguardando_pacote` e `pendente`.

## Resultado esperado

- ao reenviar um orcamento alterado para o cliente, a OS vinculada deixa `cancelado`;
- a OS volta para `aguardando_autorizacao`;
- a coluna de status da listagem `/os` passa a refletir corretamente a reabertura operacional.
