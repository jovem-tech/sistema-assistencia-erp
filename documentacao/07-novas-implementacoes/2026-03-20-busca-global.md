# Implementação: Busca Global Inteligente

**Data:** 20 de Março de 2026  
**Autor:** Antigravity (IA Sénior)  
**Status:** ✅ Concluído  

## 🚀 Visão Geral
A funcionalidade de **Busca Global** foi implementada para permitir que usuários localizem qualquer informação relevante no ERP de forma centralizada e instantânea, diretamente pela navbar. A busca é inteligente, agrupada por categorias e respeita integralmente as permissões de acesso (RBAC) do sistema.

## 🛠️ Detalhes Técnicos

### 1. Arquitetura
A solução segue o padrão MVC do CodeIgniter 4, utilizando uma Service Layer para centralizar a lógica de busca em múltiplos modelos.

- **Controller:** `app/Controllers/GlobalSearch.php` - Lida com as requisições AJAX e renderização de resultados.
- **Service:** `app/Libraries/GlobalSearchService.php` - Centraliza a lógica de busca, filtragem e verificação de permissões.
- **Frontend:** 
  - `public/assets/js/global-search.js`: Lógica de debounce (300ms), navegação por teclado e renderização dinâmica.
  - `public/assets/css/global-search.css`: Estilização moderna com suporte a temas e responsividade.
  - `app/Views/layouts/navbar.php`: Integração visual do campo de busca.

### 2. Abrangência da Busca
A busca varre as seguintes entidades:
- **Módulos do Sistema:** Atalhos rápidos para páginas e funcionalidades.
- **Ordens de Serviço (OS):** Número da OS, nome do cliente, relato, serial do equipamento, modelo.
- **Clientes:** Nome/Razão social, CPF/CNPJ, telefone, e-mail.
- **Equipamentos:** Marca, modelo, serial, IMEI, nome do cliente vinculado.
- **WhatsApp:** Conteúdo de mensagens e telefones de contato.
- **Serviços:** Nome e descrição.
- **Estoque / Peças:** Nome, código, modelos compatíveis.

### 3. Segurança e Permissões
- Todas as consultas são realizadas via **Query Builder**, prevenindo SQL Injection.
- A função `can()` é utilizada antes de buscar em cada módulo, garantindo que o usuário só veja resultados aos quais tem permissão de "visualizar".
- Sanitização de entrada (trim e escaping) aplicada em todos os termos de busca.

## 📱 UX e UI
- **Debounce:** Evita sobrecarga no servidor disparando a busca apenas após 300ms de inatividade no teclado.
- **Navegação:** Suporte a teclas `ArrowUp`, `ArrowDown`, `Enter` e `Esc`.
- **Feedback Visual:** Estados de "Buscando...", "Nenhum resultado" e agrupamento por categorias com ícones e badges.
- **Responsividade:** Em dispositivos móveis, a busca se adapta para não quebrar o layout da navbar.

## 📂 Arquivos Alterados/Criados
1. `app/Controllers/GlobalSearch.php` (Novo)
2. `app/Libraries/GlobalSearchService.php` (Novo)
3. `app/Config/Routes.php` (Alterado: adicionadas rotas `/api/busca-global` e `/busca/resultados`)
4. `app/Views/layouts/navbar.php` (Alterado: inserção do HTML do campo de busca)
5. `app/Views/layouts/main.php` (Alterado: inclusão de CSS e JS globais)
6. `public/assets/js/global-search.js` (Novo)
7. `public/assets/css/global-search.css` (Novo)

## 🔗 Links de Ajuda
- [Manual do Usuário - Busca Global](../01-manual-do-usuario/busca-global.md)
- [Documentação Técnica - GlobalSearchService](../03-arquitetura-tecnica/global-search-service.md)
