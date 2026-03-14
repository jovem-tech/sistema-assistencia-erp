# Arquitetura Geral

## Padrão MVC (Model-View-Controller)

```
Requisição HTTP
      │
      ▼
┌──────────────────────────────────────────┐
│              Apache / Routes.php          │
│  (Filtros: AuthFilter + PermissionFilter) │
└─────────────────┬────────────────────────┘
                  │
                  ▼
         ┌────────────────┐
         │   Controller   │  ← Lógica de negócio
         └───────┬────────┘
                 │
        ┌────────┴────────┐
        ▼                 ▼
   ┌─────────┐      ┌──────────┐
   │  Model  │      │   View   │
   │ (MySQL) │      │ (PHP/HTML│
   └─────────┘      └──────────┘
```

---

## Fluxo de uma Requisição

```
1. Usuário acessa URL (ex: /os/nova)
2. Routes.php verifica se a rota existe
3. AuthFilter verifica se tem sessão ativa → redireciona para /login se não
4. PermissionFilter verifica permissão (ex: os:criar) → 403 se não autorizado
5. Controller::create() é chamado
6. Controller carrega dados via Models
7. Controller passa dados para a View
8. View renderiza HTML com layout main.php
9. Resposta enviada ao browser
```

---

## Camadas do Sistema

### `app/Config/`
| Arquivo | Função |
|---------|--------|
| `Routes.php` | Mapeamento de todas as URLs |
| `Database.php` | Conexão com MySQL |
| `Filters.php` | Registro dos filtros globais |

### `app/Controllers/`
Um Controller por módulo. Padrão CRUD + extras:

```
Admin.php          → Dashboard e estatísticas
Auth.php           → Login, logout, recuperação de senha
Clientes.php       → CRUD + importação CSV + busca AJAX
Equipamentos.php   → CRUD + upload de fotos + vincular cliente
Os.php             → CRUD + itens + status + impressão
Servicos.php       → CRUD + exportar/importar CSV
Estoque.php        → CRUD + movimentações + importar/exportar
Financeiro.php     → CRUD + baixar lançamento
Relatorios.php     → Relatórios por período/filtro
Configuracoes.php  → Dados da empresa
Usuarios.php       → CRUD de usuários
Grupos.php         → CRUD + gestão de permissões RBAC
```

### `app/Models/`
```
ClienteModel.php         → Tabela clientes
EquipamentoModel.php     → Tabela equipamentos + joins
OsModel.php              → Tabela os + joins
ServicoModel.php         → Tabela servicos
EstoqueModel.php         → Tabela pecas + movimentações
FinanceiroModel.php      → Tabela financeiro
UsuarioModel.php         → Tabela usuarios
GrupoModel.php           → Tabela grupos + permissoes
LogModel.php             → Registro de auditoria (static)
ConfiguracaoModel.php    → Configurações do sistema
```

### `app/Views/`
```
layouts/
  main.php          → Layout principal (sidebar + navbar + scripts)
  sidebar.php       → Menu lateral dinâmico
  navbar.php        → Barra superior

clientes/           → index, form, show
equipamentos/       → index, form, show
os/                 → index, form, show, print
servicos/           → index, form
estoque/            → index, form
financeiro/         → index, form
relatorios/         → index, os, financeiro, estoque
configuracoes/      → index
usuarios/           → index, form
grupos/             → index, form, permissoes
auth/               → login, forgot-password
```

### `public/`
```
assets/
  css/estilo.css    → Design system personalizado (Glassmorphism)
  js/scripts.js     → Scripts globais (máscaras, CEP, datatables)
  json/pt-BR.json   → Tradução dataTables

uploads/
  equipamentos/     → Fotos de equipamentos
  sistema/          → Logo e ícone da empresa
```

---

## Sistema de Permissões (RBAC)

```
Usuário → pertence a → Grupo
Grupo   → possui     → Permissões

Permissões = módulo:ação
Ex: clientes:criar, os:editar, relatorios:visualizar

Ações disponíveis:
  visualizar, criar, editar, excluir, importar, exportar, encerrar
```

Verificação no Controller:
```php
requirePermission('clientes');          // Bloqueia acesso total
can('clientes', 'criar')               // Verifica ação específica
```
