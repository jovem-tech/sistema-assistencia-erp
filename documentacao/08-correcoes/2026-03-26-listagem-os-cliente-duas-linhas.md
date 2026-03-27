# Correcao: coluna Cliente com prioridade de largura

Data: 2026-03-26
Modulo: Ordens de Servico
Tela: `/os`

## Problema

Os nomes de clientes estavam comprimidos em excesso na listagem, quebrando em muitas linhas e prejudicando a leitura rapida.

## Ajuste aplicado

- a coluna `Cliente` passou a ter largura minima maior;
- o nome do cliente agora pode ocupar ate duas linhas antes de truncar;
- as colunas `Data Abertura`, `Status`, `Valor Total` e `Acoes` receberam larguras mais previsiveis para liberar espaco util para o nome;
- a leitura da grade ficou mais equilibrada sem perder o comportamento responsivo com expansao por linha.

## Arquivos alterados

- `app/Controllers/Os.php`
- `public/assets/css/design-system/layouts/os-list-layout.css`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
