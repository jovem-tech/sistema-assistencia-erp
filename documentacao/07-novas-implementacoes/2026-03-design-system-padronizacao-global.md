# 2026-03 - Design System modular global

## Objetivo

Padronizar a interface em todos os modulos com um Design System reutilizavel e com estrutura em camadas.

## Entregas aplicadas

1. Estrutura modular criada em `public/assets/css/design-system/`:
- `tokens/` (cores, espacamento, tipografia, sombras, radius)
- `components/base/` (button, input, select, textarea, checkbox, badge)
- `components/composite/` (card, modal, tabs, dropdown, table, alert, gallery)
- `layouts/` (dashboard, form, detail)
- `patterns/` (forms, lists, galleries, design-system-page)

2. Agregador global:
- `public/assets/css/design-system/index.css`

3. Integracao no layout principal:
- `app/Views/layouts/main.php` passa a carregar `assets/css/design-system/index.css`.

4. Nova pagina interna de referencia visual:
- Rota: `GET /design-system`
- Controller: `app/Controllers/DesignSystem.php`
- View: `app/Views/design_system/index.php`

5. Navegacao:
- Novo item no sidebar: `Configuracoes > Design System`.
- Arquivo: `app/Views/layouts/sidebar.php`.

6. Ajuda contextual:
- `openDocPage('design-system')` mapeado para:
  - `documentacao/06-modulos-do-sistema/design-system.md`
- Arquivo: `public/assets/js/scripts.js`.

7. Pagina estatica de referencia completa atualizada:
- Arquivo: `public/design-system.html`
- Padroes consolidados:
  - tokens
  - botoes e estados
  - formulario padrao
  - tabs e tabela responsiva
  - galeria e feedback com SweetAlert2
  - split layout responsivo
  - checklist oficial de conformidade UI

8. Dependencias front-end da pagina estatica padronizadas para ambiente offline/local:
- `assets/vendor/bootstrap/*`
- `assets/vendor/bootstrap-icons/*`
- `assets/vendor/sweetalert2/*`
- `assets/css/estilo.css`
- `assets/css/design-system/index.css`

## Impacto funcional

- Uniformizacao visual de botoes, formularios, cards, tabs, tabelas, alerts e galerias.
- Base pronta para novos modulos seguirem o mesmo padrao.
- Referencia interna unica para validacao de UI/UX antes de publicar novas telas.

## Observacoes de manutencao

- Ajustes globais devem priorizar os arquivos de `design-system/`.
- `estilo.css` segue ativo como base legada e compatibilidade.
- Novos componentes devem usar tokens CSS em vez de valores hardcoded.
