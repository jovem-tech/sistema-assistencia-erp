# Correcao - busca global mobile com resultados espremidos no menu lateral

Data: 09/06/2026
Modulo: Busca global / Ordens de Servico

## Problema

No mobile, especialmente em larguras como `390px`, o painel de resultados da busca global dentro do menu hamburger podia aparecer espremido na lateral do campo, em vez de abrir abaixo dele.

Causa raiz:

- o componente `.search-results-container` foi reaproveitado do dropdown desktop;
- ao virar `position: static` no contexto `.sidebar-search-wrapper`, ele passou a participar da mesma linha flex do `.search-input-group`;
- como o container nao ocupava `100%` da linha nem forÃ§ava quebra, o painel disputava largura com o seletor `Tudo`, o icone e o input;
- titulos, subtitulos e badges dos resultados ainda tinham pouca tolerancia a wrap em telas muito compactas.

## Ajuste realizado

- o bloco `.sidebar-search-wrapper .search-input-group` passou a usar `flex-wrap: wrap`;
- o painel `.sidebar-search-wrapper .search-results-container` agora ocupa largura total, com `flex: 0 0 100%`, `width: 100%` e `max-width: none`;
- os itens de resultado no mobile passaram a alinhar o conteudo pelo topo e a aceitar quebra controlada de texto;
- badges, subtitulos e titulos ganharam `overflow-wrap`, reduzindo risco de sobreposicao ou corte lateral em `<= 430px`, `<= 390px`, `<= 360px` e `<= 320px`.

## Arquivos alterados

- `public/assets/css/global-search.css`
- `documentacao/01-manual-do-usuario/busca-global.md`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/03-arquitetura-tecnica/busca-global.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/08-correcoes/2026-06-09-busca-global-mobile-sidebar-resultados.md`

## Validacao

- revisao estrutural do DOM/CSS do componente mobile a partir do estado real do navegador em `390px`;
- conferida a ausencia de alteracoes em backend, rotas ou banco de dados;
- a validacao visual automatizada completa da area autenticada continua dependente de reutilizar uma sessao logada no navegador do operador.

## Migracoes

- nenhuma migracao de banco foi necessaria.
