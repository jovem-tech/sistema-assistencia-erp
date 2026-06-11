# Correcao - compatibilidade do datatable da listagem de OS

Data: `07/06/2026`

## Sintoma

O endpoint `POST /os/datatable` podia retornar `500 Internal Server Error` em execucoes que ainda referenciavam a rotina de resumo de fluxo da coluna `Status / Orcamento`.

## Causa

A montagem da celula de status foi refatorada para um formato mais compacto, mas a compatibilidade com a chamada antiga do helper de resumo nao ficou garantida em todo o fluxo do controller.

## Correcao aplicada

Arquivo principal:

- `app/Controllers/Os.php`

Ajustes:

- a regra de exibicao do resumo de fluxo foi centralizada em `shouldShowOsFluxoSummary()`;
- a celula de status voltou a usar um helper explicito e seguro para decidir quando renderizar o resumo complementar;
- a listagem `datatable` passou a ficar protegida contra essa divergencia de implementacao.

## Validacao executada

- `php -l app/Controllers/Os.php`

## Resultado esperado

O `datatable` da tela `/os` deixa de falhar por incompatibilidade interna do resumo de status e volta a responder normalmente para o front-end.
