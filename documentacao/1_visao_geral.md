# Visão Geral do Sistema: AssistTech

## 📌 Introdução
O **AssistTech** é um sistema projetado para o gerenciamento de assistências técnicas, abrangendo o controle financeiro, equipamentos, estoque e o controle de fluxo das **Ordens de Serviço (OS)**.

## 🛠 Tecnologias Utilizadas
O projeto baseia-se num pacote moderno e flexível com a arquitetura MVC (Model-View-Controller).

- **Backend:** PHP 8.2 utilizando o Framework **CodeIgniter 4** (CI4).
- **Frontend / Interface:** HTML5 semântico, folha de estilo customizada atrelada ao framework CSS **Bootstrap 5**, JavaScript (jQuery + SP Mask para formulários dinâmicos) com design estilo *Glassmorphism* (efeitos translúcidos nas Views).
- **Banco de Dados:** MySQL/MariaDB. Integração direta com os Models do CI4 para uso com queries em *Query Builder*.
- **Iconografia:** Bootstrap Icons (v1.11.3).
- **Acessórios de Dados:** Chart.js para dashboards e gráficos; DataTables para listagens paginadas responsivas de grande volume.

## 📁 Estrutura de Diretórios e Escopo

A arquitetura do sistema reside originalmente em `c:\xampp\htdocs\sistema-assistencia`. Apenas os arquivos contidos em `public` são servidos diretamente, adicionando uma camada de segurança.
A principal lógica reside no diretório `/app`:

- `/app/Config`: Configurações de Banco de Dados (`Database.php`), Roteador Automático e Variáveis Ambientais de Rotas (`Routes.php`).
- `/app/Controllers`: A camada lógica que governa cada uma das Views e interage com os dados de base em MySQL (`Admin`, `Auth`, `Clientes`, `Configuracoes`, `Equipamentos`, `EquipamentosDefeitos`, `Estoque`, `Financeiro`, `Os`, `Relatorios`, `Usuarios`).
- `/app/Models`: Elementos que refletem a ligação de dados MySQL (`ConfiguracaoModel`, `LogModel`, `UsuarioModel`, `OsModel`, etc).
- `/app/Views`: Modelos de apresentação utilizando as camadas de `layouts/main.php`, separadas por cada contexto (como pastas exclusivas `auth`, `clientes`, `configuracoes` e `usuarios`).

## 🔀 Sistema Base de Rotas
**Todas** as requisições estão focadas sem sufixos através dos diretivos MVC do app `spark serve` via index: `http://localhost:8081/dashboard`, `/login`, `/equipamentos` que apontam dinamicamente para cada grupo protetor no CI4 com o uso de **Filters** como:
- `AuthFilter.php` (Proteção restrita de Autenticação via Sessão).
- `PermissionFilter.php` (Proteção de Rotas com base em Controle de Acesso e Grupos RBAC).
