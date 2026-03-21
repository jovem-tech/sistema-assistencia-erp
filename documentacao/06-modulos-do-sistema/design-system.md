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

## Padrao responsivo global

A camada `layouts/responsive-layout.css` consolida:

- `page-content` com largura util em telas grandes (sem espaco morto excessivo)
- ajuste de sidebar e navbar para notebook/tablet/mobile
- padrao de colunas `ds-split-layout`, `ds-split-sidebar`, `ds-split-main`
- abas com rolagem horizontal via `ds-tabs-scroll`
- tabela em cartao no mobile com `ds-table-responsive-card`
- modal com largura/altura responsiva por breakpoint

Classes utilitarias adicionadas para telas de OS e formulários:

- `os-form-page`
- `os-show-page`
- `os-status-update-form`
- `os-doc-form`
- `os-form-actions`
- `equip-form-page`

Padroes globais adicionais para DataTables:

- `dataTables_length`, `dataTables_filter`, `dataTables_info` e `dataTables_paginate` com empilhamento responsivo em mobile.
- Campo de busca expandido para largura total no mobile.
- Paginação com quebra de linha e melhor area de toque.
