# Correcao - tipografia da listagem de Ordens de Servico

Data: 09/06/2026
Modulo: Ordens de Servico

## Problema

A listagem `/os` continuava funcional, mas a leitura operacional da tabela havia perdido hierarquia visual:

- varios blocos importantes usavam fontes pequenas demais, principalmente em `Equipamento`, `Datas`, `Status / Orcamento` e `Valor`;
- labels secundarias em caixa alta estavam com peso visual desproporcional para o tamanho exibido;
- a mistura de regras antigas e novas deixou a grade com tamanhos muito diferentes entre colunas equivalentes;
- badges, datas e resumo financeiro ficaram compactados demais em resolucoes intermediarias e em mobile.

## Causa raiz

- a tipografia da listagem foi sendo ajustada em etapas diferentes de responsividade;
- parte das regras ficou duplicada ou distribuida entre blocos de largura, mobile cards e refinamentos finais;
- com isso, surgiram tamanhos entre `0.62rem` e `0.76rem` em pontos que deveriam sustentar leitura operacional rapida.

## Ajuste realizado

- criada uma escala tipografica final para a listagem diretamente em `public/assets/css/design-system/layouts/os-list-layout.css`;
- `numero da OS`, `nome do cliente`, `tipo do equipamento`, `datas principais`, `status principal` e `total da OS` ganharam mais destaque;
- labels secundarias foram rebaixadas por contraste e proporcao, sem perder legibilidade;
- `line-height`, `font-size`, `font-weight` e `letter-spacing` foram rebalanceados para desktop, notebook, tablet e mobile;
- badges e resumo financeiro passaram a manter leitura confortavel sem parecer apertados ou miniaturizados.

## Arquivos alterados

- `public/assets/css/design-system/layouts/os-list-layout.css`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/08-correcoes/2026-06-09-tipografia-listagem-os.md`

## Validacao

- revisao estrutural do CSS com checagem de consistencia entre desktop e breakpoints mobile obrigatorios;
- validacao estatica de diff sem conflitos de sintaxe;
- a validacao visual completa da area autenticada ainda depende da sessao logada no navegador do operador.

## Migracoes

- nenhuma migracao de banco foi necessaria.
