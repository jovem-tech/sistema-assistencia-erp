# Correcao - Runtime e Foco do Modal de Cliente na OS

Data: 28/04/2026

## Problema

O fluxo de edicao rapida do cliente na OS apresentava dois bloqueios em cascata:

- um erro JavaScript em `buildLegacyEstadoFisicoFromChecklist()` interrompia o restante da inicializacao da tela;
- ao fechar o modal, o Bootstrap ainda podia registrar aviso de `aria-hidden` porque um botao interno mantinha o foco durante o `dismiss`.

## Correcao aplicada

- normalizado para ASCII o identificador local `discrepancias`, removendo a quebra de runtime;
- o modal passou a mover o foco para um alvo externo valido antes do fechamento;
- o fluxo de `hide` e `hidden` agora restaura foco para o botao de origem (`Editar` ou `Novo`) quando possivel.

## Resultado esperado

- o botao `Editar` volta a responder na aba `Cliente`;
- o modal abre, fecha e devolve foco sem warning de acessibilidade ligado a `aria-hidden`;
- o restante do JavaScript da tela deixa de ser abortado por esse trecho do checklist.
