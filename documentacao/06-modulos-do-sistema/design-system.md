# Modulo: Design System

## Finalidade

Centralizar padroes visuais, comportamentais e estruturais do frontend para todos os modulos do ERP.

## Rota interna

- `GET /design-system`

Pagina protegida por permissao de configuracoes (`configuracoes:visualizar`).

## Estrutura de arquivos

Todos os arquivos do Design System ficam em:

`public/assets/css/design-system/`

### Camadas

1. `tokens/`
- `colors.css`
- `spacing.css`
- `typography.css`
- `shadows.css`
- `radius.css`

2. `components/base/`
- `button.css`
- `input.css`
- `select.css`
- `textarea.css`
- `checkbox.css`
- `badge.css`

### Padrao global para Select2 single-line

O arquivo `components/base/select.css` centraliza o comportamento visual dos Select2 de linha unica.

Padroes aplicados:
- `width: 100%` e `max-width: 100%` no container
- texto truncado com `ellipsis` em selecoes longas
- `overflow: hidden` e `white-space: nowrap` no valor renderizado
- espaco reservado para seta e botao de limpar
- compatibilidade com grid, modal, drawer e formularios responsivos

Objetivo:
- evitar quebra de layout com nomes longos
- manter o alinhamento interno do componente
- permitir reaproveitamento da mesma solucao em outros modulos

3. `components/composite/`
- `card.css`
- `modal.css`
- `tabs.css`
- `dropdown.css`
- `table.css`
- `alert.css`
- `gallery.css`

4. `layouts/`
- `dashboard-layout.css`
- `os-list-layout.css`
- `os-form-layout.css`
- `form-layout.css`
- `detail-layout.css`
- `responsive-layout.css`

5. `patterns/`
- `forms.css`
- `lists.css`
- `galleries.css`
- `design-system-page.css`

## Arquivo agregador

`public/assets/css/design-system/index.css`

Esse arquivo importa todas as camadas e e carregado globalmente no layout principal:

- `app/Views/layouts/main.php`

## Exibicao no sistema

A pagina `/design-system` demonstra:

- Paleta de cores e tokens
- Tipografia
- Variantes de botoes
- Campos de formulario e estados
- Tabs padronizadas
- Tabela padronizada
- Galeria de miniaturas
- Alertas e badges

Tambem existe a pagina estatica publica:

- `public/design-system.html`

Uso recomendado:

- referencia rapida de padrao visual fora do fluxo logado
- homologacao de UI sem depender de rota protegida
- checklist de conformidade (SweetAlert2, fluxo de fotos, responsividade)

## Navegacao

O acesso foi incluido no sidebar em:

- `Configuracoes > Design System`

Arquivo:

- `app/Views/layouts/sidebar.php`

## Ajuda contextual

Mapeamento de ajuda adicionado em:

- `public/assets/js/scripts.js`

Slug:

- `openDocPage('design-system')`

Destino:

- `06-modulos-do-sistema/design-system.md`

## Diretrizes de uso

1. Novas telas devem reutilizar classes dos componentes base/composite antes de criar CSS local.
2. Tokens devem ser usados via variaveis CSS (`--color-*`, `--space-*`, `--radius-*`).
3. Evitar valores hardcoded quando houver token equivalente.
4. Ajustes visuais globais devem priorizar os arquivos do Design System para reduzir divergencia entre modulos.
5. Responsividade estrutural deve ser centralizada em `layouts/responsive-layout.css` (shell, sidebar, tabs, modais, tabelas adaptativas, split layout).
6. A pagina `public/design-system.html` deve ser atualizada sempre que um novo padrao global de UI/UX for adotado.
7. Avisos globais de sessao expirada em telas protegidas devem usar SweetAlert2, com bloqueio de interacao e redirecionamento claro para o login.

## Padrao responsivo global

A camada `layouts/responsive-layout.css` consolida:

- `page-content` com largura util em telas grandes (sem espaco morto excessivo)
- ajuste de sidebar e navbar para notebook, tablet e mobile
- `main-content` com largura calculada a partir da sidebar fixa ou recolhida, evitando shell mais largo que a viewport
- padrao de colunas `ds-split-layout`, `ds-split-sidebar`, `ds-split-main`
- abas com rolagem horizontal via `ds-tabs-scroll`
- tabela em cartao no mobile com `ds-table-responsive-card`
- modal com largura e altura responsiva por breakpoint

Classes utilitarias adicionadas para telas de OS e formularios:

- `os-form-page`
- `os-show-page`
- `os-status-update-form`
- `os-doc-form`
- `os-form-actions`
- `equip-form-page`

Padrao dedicado do modal Nova OS:
- `public/assets/css/design-system/layouts/os-form-layout.css`
- centraliza os blocos internos da abertura e edicao de OS
- define hierarquia visual das abas `Cliente`, `Equipamento` e `Dados Operacionais`
- padroniza o card de contexto do cliente e os botoes inline `Novo` e `Editar`
- inclui o botao contextual `ds-inline-add-btn`, usado ao lado das labels de `Marca` e `Modelo`
- inclui o componente `ds-password-field`, com alternancia entre senha `DESENHO` e `TEXTO`
- o subcomponente de desenho usa grade 3x3 (`ds-pattern-password__grid` + `ds-pattern-node`) e serializa o valor no formato `desenho_1-4-7-8-9`
- a grade de desenho foi reduzida para um bloco compacto, evitando ocupar largura excessiva em modal e formulario
- aplica o conceito de `superficie editavel`, com destaque suave no bloco completo e estado ativo por `:focus-within`
- usa base branca nas superficies da Nova OS, com borda azul/cinza suave e estado ativo por foco para evidenciar areas editaveis com contraste limpo
- cobre tambem sidebar, shell principal, painel de fotos e titulos auxiliares com classes reutilizaveis do modulo
- em layouts protegidos, meta tags globais alimentam o monitor de sessao do frontend para expiracao explicita e heartbeat controlado por atividade

Padroes globais adicionais para DataTables:

- `dataTables_length`, `dataTables_filter`, `dataTables_info` e `dataTables_paginate` com empilhamento responsivo em mobile
- campo de busca expandido para largura total no mobile
- child rows responsivas com botao `+` e `-` para revelar colunas ocultas em larguras menores
- colunas semanticas ricas para tabelas densas:
  - celula de equipamento com bloco `label + valor`
  - celula de datas com indicadores visuais de prazo
  - badge clicavel de status abrindo modal contextual sem sair da listagem
- wrappers de tabela (`card-body`, `table-responsive`, cards de listagem) devem usar `min-width: 0` para impedir overflow estrutural em notebook
- barras de filtro desktop devem quebrar o grupo de acoes para uma nova linha em notebook antes de gerar scroll horizontal
- paginacao com quebra de linha e melhor area de toque
- em listagens densas, o padrao responsivo recomendado passa a ser:
  - desktop, notebook e tablet: tabela com ocultacao progressiva + child row
  - mobile: cards reais, sem depender apenas de `overflow-x`
- a decisao de trocar entre tabela e cards deve seguir os breakpoints reais da viewport, e nao apenas a largura de um bloco interno isolado
