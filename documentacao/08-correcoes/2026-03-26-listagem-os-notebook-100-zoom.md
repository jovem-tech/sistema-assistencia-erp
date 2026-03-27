# Correcao de responsividade agressiva da tela de Ordens de Servico

Data: 2026-03-26

## Problema

Na rota `/os`, a tela ainda apresentava gargalos relevantes de responsividade:

- conteudo principal ficando mais largo que a viewport em notebook por causa da combinacao entre sidebar fixa e `main-content`;
- card de filtros com campos cortados para a direita em larguras como `1024px` e `820px`;
- regras de tabela x cards sendo acionadas por largura interna do bloco, o que fazia notebooks entrarem em comportamento de mobile;
- necessidade de reorganizacao real da listagem para celular, sem depender apenas de scroll horizontal.

## Causa raiz

O shell principal ainda permitia que `main-content` crescesse alem da largura util da viewport. Em paralelo, a listagem de OS estava misturando duas leituras diferentes de responsividade:

- a tabela escondia colunas com base no contexto da tela;
- o modo `card` podia ser acionado por largura interna do container, e nao pelo breakpoint real da viewport.

Isso gerava inconsistencias visuais entre notebook, tablet e mobile.

## Correcao aplicada

Arquivos alterados:

- `app/Views/os/index.php`
- `public/assets/js/os-list-filters.js`
- `public/assets/js/scripts.js`
- `public/assets/css/design-system/layouts/os-list-layout.css`
- `public/assets/css/design-system/layouts/responsive-layout.css`
- `public/assets/css/global-search.css`

### Shell e sidebar

- `main-content` passou a calcular largura e `max-width` com base no espaco restante apos a sidebar fixa ou recolhida.
- Em `<= 991.98px`, a sidebar passa a trabalhar em overlay, sem reservar largura lateral do conteudo.
- O toggle mobile fecha ao clicar fora e tambem responde a `Escape`.

### Header e filtros

- Navbar e busca global ganharam quebra controlada em notebook e tablet.
- O card de filtros da pagina `/os` foi recalibrado por breakpoint:
  - `1200px a 1399px`: campos em duas colunas e acoes em linha separada
  - `992px a 1199px`: duas colunas + grade de acoes em 3 blocos
  - `768px a 991px`: duas colunas + grade de acoes em 2 blocos
  - `<= 767px`: drawer de filtros

### Listagem da OS

- `>= 768px`: mantem formato de tabela com ocultacao progressiva e child row
- `< 768px`: converte as linhas em cards reais
- A decisao de entrar em modo `card` passou a seguir o breakpoint real da viewport.
- A coluna `N OS` continua fixa em uma unica linha.
- `Cliente` ganhou mais prioridade horizontal e pode ocupar ate duas linhas.
- `Equipamento` e `Datas` usam estrutura semantica em bloco.
- `Relato` quebra em varias linhas e tenta caber em ate 4 linhas com ajuste de fonte.

## Validacao executada

Testes reais em navegador local com autenticacao:

- `1366px`
- `1024px`
- `820px`
- `700px`
- `390px`
- `360px`
- `320px`

Criticos validados:

- sem scroll horizontal global indevido;
- filtros sem corte lateral;
- tabela mantida em desktop, notebook e tablet;
- cards ativos no mobile;
- sidebar sobreposta funcionando em telas pequenas.

## Resultado esperado

- Notebook em zoom de `100%` sem corte lateral no shell da pagina
- filtros organizados e clicaveis por faixa de tela
- tabela operacional legivel em desktop e tablet
- cards claros e usaveis no celular
- sidebar mobile sem travamento visual
