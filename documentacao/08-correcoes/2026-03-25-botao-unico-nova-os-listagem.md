# 2026-03-25 - Botao unico de Nova OS na listagem

## Problema

Na rota `/os`, o usuario via dois CTAs equivalentes para abrir nova ordem de servico:

- botao global `Nova OS` na navbar
- botao `+ Nova OS` da propria pagina

Isso gerava duplicidade visual e reduzia a clareza da interface.

## Correcao aplicada

- `app/Views/layouts/navbar.php` agora identifica o modulo atual pela URI.
- Quando o contexto ativo e `os`, a navbar oculta a acao global `Nova OS`.
- O CTA visivel passa a ser apenas o botao `+ Nova OS` da listagem de ordens de servico.

## Impacto

- reduz ruido visual no topo da pagina `/os`
- mantem um unico ponto de acao para abrir OS na listagem
- preserva o comportamento dos demais modulos, sem alterar a navbar global fora do contexto de OS
