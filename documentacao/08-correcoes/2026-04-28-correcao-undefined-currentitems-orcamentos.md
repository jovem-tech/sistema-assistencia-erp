# Correcao - Variavel `currentItems` na Edicao de Orcamentos

Data: 28/04/2026

## Problema

Ao editar um orcamento no novo fluxo de reaprovacao, o sistema podia lançar:

- `Undefined variable $currentItems`

Isso acontecia porque o snapshot dos itens atuais foi referenciado em `Orcamentos::update()` antes de ser inicializado naquele metodo.

## Correcao aplicada

- removido o carregamento indevido no fluxo de criacao;
- reposicionado o carregamento de `currentItems` dentro do metodo `update()`;
- preservada a comparacao entre itens antigos e novos para o historico e para a mudanca automatica para `reenviar_orcamento`.

## Resultado esperado

- a edicao de orcamentos volta a salvar normalmente;
- o historico de alteracoes continua sendo calculado no mesmo registro;
- a nova rodada de aprovacao pode ser aberta sem excecao em tempo de execucao.
