# Arquitetura Técnica: Busca Global Inteligente

A busca global é um componente transversal que consome dados de múltiplos modelos e provê uma interface unificada na navbar do ERP.

## 🏗️ Componentes Principais

### 1. GlobalSearchService (`app/Libraries/GlobalSearchService.php`)
O "cérebro" da funcionalidade. Ele centraliza a lógica de:
- **Suporte a Múltiplos Filtros**: O sistema aceita uma lista de categorias separadas por vírgula no parâmetro `filter` (ex: `os,clientes`). Se `all` estiver presente, todas as categorias permitidas são buscadas.
- **Catálogo de Módulos**: Lista de rotas e funcionalidades do sistema pesquisáveis como "Módulos".
- **Integração com Modelos**: Executa `like` queries otimizadas em `OsModel`, `ClienteModel`, `EquipamentoModel`, etc.
- **Filtragem por Permissão**: Antes de executar cada bloco de busca, valida via `can($modulo, 'visualizar')` se o usuário logado tem direito de ver aqueles registros.
- **Agrupamento**: Retorna um array associativo onde as chaves são os nomes das categorias.

### 2. GlobalSearch Controller (`app/Controllers/GlobalSearch.php`)
- **Endpoint API**: Provê a interface AJAX para o frontend.
- **Sanitização**: Limpa os termos de busca antes de passar para o Service.

### 3. Engine de Frontend (`public/assets/js/global-search.js`)
- **Debounce**: Implementado com `setTimeout` (300ms) para reduzir o número de requisições.
- **Keyboard Engine**: Gerencia eventos de teclado (`ArrowUp`, `ArrowDown`, `Enter`, `Esc`).
- **Multi-Filter Engine**: Gerencia o estado de múltiplos checkboxes. Implementa exclusividade lógica: ao marcar "Tudo", desmarca os demais; ao marcar qualquer categoria individual, desmarca "Tudo".
- **Template Engine**: Renderiza dinamicamente os resultados no container flutuante.

## 🎨 Design System & UI
- **CSS Scoped**: Estilos definidos em `public/assets/css/global-search.css`.
- **Variáveis de Tema**: Utiliza tokens do design system (`--color-text`, `--color-surface`).
- **Barra de Rolagem**: Customizada para ser visível e indicar que existem mais resultados.
- **Z-Index Management**: O container de resultados utiliza `z-index: 9999`.

## 🔍 Otimizações e Limites
- **Limite por Grupo**: Cada grupo de resultados é limitado a 10 registros.
- **Mínimo de Caracteres**: A busca só é disparada com 2 ou mais caracteres.
