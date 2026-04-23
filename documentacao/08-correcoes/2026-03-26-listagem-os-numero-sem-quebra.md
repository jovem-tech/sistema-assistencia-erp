# Correcao: coluna N OS sem quebra de linha

Data: 2026-03-26
Modulo: Ordens de Servico
Tela: `/os`

## Problema

Em determinados tamanhos de tela, a coluna `N OS` quebrava o numero da ordem em duas linhas, prejudicando a leitura rapida da listagem.

## Ajuste aplicado

- a renderizacao do numero da OS passou a usar uma classe dedicada;
- a coluna `N OS` recebeu largura minima fixa;
- o conteudo passou a usar `white-space: nowrap` e `word-break: keep-all`;
- o ajuste vale tambem em breakpoints menores da listagem responsiva.

## Arquivos alterados

- `app/Controllers/Os.php`
- `public/assets/css/design-system/layouts/os-list-layout.css`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
