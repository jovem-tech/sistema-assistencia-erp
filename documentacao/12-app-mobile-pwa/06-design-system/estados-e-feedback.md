# Estados e Feedback

Atualizado em 05/04/2026.

## Estados obrigatorios

- loading
- erro
- vazio
- sucesso
- disabled
- ativo
- selecionado

## Regras

- erros devem aparecer no proprio contexto da tela
- acoes nao devem sumir sem feedback
- miniaturas e cards clicaveis devem responder ao toque/hover
- estados de polling e stream devem ser legiveis, mas discretos
- itens selecionados em listas/combobox (ex.: cliente selecionado) devem manter contraste alto no texto:
  - linha principal em tom escuro
  - linha secundaria em tom medio, nunca com opacidade que comprometa leitura

## Modais

- cabecalho claro
- corpo rolavel
- rodape fixo para acoes finais
- botao de cancelar/fechar sempre visivel
- para criacao de OS:
  - etapa 1: revisao completa dos dados com destaque de pendencias obrigatorias/opcionais
  - etapa 2: decisao explicita sobre notificacao ao cliente
  - obrigatorios pendentes bloqueiam continuidade e devem oferecer redirecionamento direto ao campo

## Midias

- miniatura pequena
- preview ampliado por clique
- remocao isolada por `x`
- crop antes da persistencia no draft ou envio
