# Correcao - responsividade multitelas da listagem de OS

Data: 09/06/2026
Modulo: Ordens de Servico

## Problema

A tela `/os` ainda apresentava conflitos de responsividade entre desktop, notebook, tablet e mobile:

- filtros e acoes disputavam largura em resolucoes intermediarias;
- a DataTable usava combinacoes antigas de largura fixa com `overflow-x: hidden`, comprimindo colunas importantes;
- a grade mobile ainda tinha trechos remanescentes com `nowrap` e `overflow-x:auto` em blocos como `Cliente` e `Equipamento`;
- parte do CSS responsivo da baixa da OS ainda estava embutida em `app/Views/os/index.php`, fora do layout global do modulo.

## Ajuste realizado

- migrados para `public/assets/css/design-system/layouts/os-list-layout.css` os estilos responsivos da baixa da OS que ainda estavam inline na view;
- adicionada uma camada final de normalizacao responsiva para cabecalho, filtros, DataTable, cards mobile e paginacao;
- o wrapper da grade voltou a usar scroll horizontal controlado apenas no container da tabela, sem permitir scroll horizontal da pagina;
- a grade desktop/notebook teve as larguras finais rebalanceadas para reduzir espremimento visual;
- o perfil `tablet-compact` passou a ocultar mais colunas secundarias na DataTable, preservando leitura e usando o detalhe expansivel para completar o contexto;
- os cards mobile passaram a quebrar texto novamente dentro da propria largura, sem faixas estreitas com rolagem lateral.

## Arquivos alterados

- `app/Views/os/index.php`
- `public/assets/css/design-system/layouts/os-list-layout.css`
- `public/assets/js/os-list-filters.js`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/08-correcoes/2026-06-09-correcao-responsividade-listagem-os-multitelas.md`

## Validacao

- `php -l app/Views/os/index.php` executado com sucesso;
- acesso HTTP local confirmado para a rota protegida `/os`, com redirecionamento para login sem sessao autenticada;
- a validacao visual automatizada da tela autenticada ficou limitada pela ausencia de uma sessao de login reutilizavel fora do navegador ja aberto do operador.

## Migracoes

- nenhuma migracao de banco foi necessaria.
