# 2026-03 - Refatoracao Responsiva Global (ERP)

## Objetivo

Profissionalizar a responsividade do ERP para desktop, widescreen, notebook, tablet e telas menores, com base reutilizavel no Design System.

## Escopo implementado

1. Base responsiva global (Design System)
- Novo arquivo: `public/assets/css/design-system/layouts/responsive-layout.css`
- Importado em: `public/assets/css/design-system/index.css`
- Cobre:
  - shell (`page-content`, `page-header`)
  - sidebar/navbar por breakpoint
  - split layout reutilizavel (`ds-split-layout`, `ds-split-sidebar`, `ds-split-main`)
  - tabs com rolagem horizontal (`ds-tabs-scroll`)
  - tabela mobile card (`ds-table-responsive-card`)
  - comportamento responsivo de modais

2. Ordem de Servico - listagem (`/os`)
- Filtros reorganizados por grid responsivo.
- Tabela adaptativa com breakpoints de visibilidade por coluna.
- Removido `scrollX` fixo do DataTable para melhorar ocupacao horizontal no desktop.
- Mantido fallback mobile em formato card.
- Controles do DataTable (busca, tamanho de pagina, info e paginacao) adaptados para mobile com empilhamento e melhor area de toque.

3. Ordem de Servico - abertura (`/os/nova`)
- Estrutura lateral + formulario adaptada com split layout reutilizavel.
- Tabs de abertura com rolagem horizontal em largura reduzida.
- Botoes de acao padronizados para empilhamento no mobile.
- Ajustes de JS para preservar classes responsivas do bloco principal (`formCol`) durante atualizacoes dinamicas.

4. Ordem de Servico - visualizacao (`/os/visualizar/{id}`)
- Layout principal migrado para split responsivo.
- Cards superiores e blocos operacionais reorganizados por breakpoint.
- Formularios de status e geracao de PDF com quebra responsiva.
- Tabs convertidas para padrao de navegacao horizontal em telas menores.

5. Equipamentos - cadastro/edicao
- Tela recebeu wrapper responsivo (`equip-form-page ds-form-layout`).
- Tabs com `ds-tabs-scroll`.
- Bloco de fotos (acoes/grade) adaptado para mobile.
- Rodape de acoes do formulario reorganizado para empilhamento em telas pequenas.

6. Documentacao interna (`/documentacao`)
- Ajustes responsivos adicionais na wiki (altura util, largura lateral mobile e padding do conteudo).

7. Dashboard
- Wrapper de layout padrao `ds-dashboard-layout`.
- Tabelas em cards marcadas para uso da camada responsiva compartilhada.

## Resultado pratico

- Melhor aproveitamento de espaco em widescreen.
- Menos espremimento em notebook.
- Melhor leitura e operacao por toque em telas menores.
- Base reutilizavel para novos modulos sem CSS pontual repetitivo.
