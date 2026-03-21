# Correção de Layout: Filtros Horizontais na Listagem de Conversas

Data: 19/03/2026
Tipo: UI/UX (Refatoração de Layout)

## Alteração Realizada

A listagem de filtros da Central de Mensagens foi reestruturada de um formato vertical (em card) para uma barra horizontal compacta, alinhada com o padrão visual do cabeçalho da thread de conversa.

## Motivação

Melhorar a usabilidade e a economia de espaço vertical no painel de conversas, permitindo uma visualização mais limpa dos filtros e maior consistência visual com os demais cabeçalhos do módulo.

## Mudanças Técnicas

1.  **HTML**:
    *   Substituição do layout baseado em colunas fixas (`col-12`) por um container `d-flex` com `flex-wrap`.
    *   Uso de wrappers semânticos (`cm-filter-q`, `cm-filter-select`, `cm-filter-checks`, `cm-filter-btn`).
2.  **CSS**:
    *   Definição de larguras flexíveis (`flex: 1 1 auto`) com limites de `min-width` para garantir legibilidade.
    *   Alinhamento centralizado (`align-items: center`) e espaçamento padronizado (`gap: .5rem`).
    *   Responsividade: filtros empilhados adequadamente em dispositivos móveis.

## Arquivos Afetados

- `app/Views/central_mensagens/index.php`: Refatoração do bloco HTML e adição de estilos específicos.
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`: Atualização da documentação de layout.

## IDs Preservados (Garantia de Funcionamento JS)

- `filtroConversaQ`
- `filtroConversaStatus`
- `filtroConversaResponsavel`
- `filtroConversaTag`
- `filtroConversaNaoLidas`
- `filtroConversaOsAberta`
- `btnFiltrarConversas`
