# 2026-03-24 - Normalizacao de acentuacao nas views

## Objetivo

Remover caracteres estranhos e mojibake que estavam aparecendo em diferentes telas do sistema, principalmente em labels, alertas, titulos de abas e acoes de modais.

## Arquivos corrigidos

- `app/Views/os/form.php`
- `app/Views/equipamentos/form.php`
- `app/Views/grupos/form.php`
- `app/Views/grupos/index.php`
- `app/Views/relatorios/view_financeiro.php`
- `app/Views/servicos/form.php`
- `app/Views/servicos/index.php`

## Ajustes aplicados

- Normalizacao de textos como `Atenção`, `Observações`, `Solução Aplicada`, `Descrição`, `Ações` e `Importação`.
- Correcao de tabs e textos auxiliares do cadastro de equipamento.
- Correcao de labels e botoes da listagem/importacao de servicos.
- Correcao de descricoes padrao em grupos e relatorio financeiro.
- Ajuste dos cabecalhos de sugestoes do modal de equipamento/OS para remover simbolos `?` no lugar de marcadores.

## Validacao

- Revisao por busca textual nas views afetadas.
- Validacao de sintaxe PHP nas views alteradas.
