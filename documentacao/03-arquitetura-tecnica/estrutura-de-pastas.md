# Estrutura de Pastas

## Raiz do Projeto

```
sistema-assistencia/
├── app/                    ← Código do aplicativo
│   ├── Config/             ← Configurações CI4
│   │   ├── Routes.php      ← TODAS as rotas do sistema
│   │   ├── Database.php    ← Conexão MySQL
│   │   └── Filters.php     ← Registro de AuthFilter e PermissionFilter
│   │
│   ├── Controllers/        ← Lógica de negócio
│   │   ├── Admin.php
│   │   ├── Auth.php
│   │   ├── Clientes.php
│   │   ├── Equipamentos.php
│   │   ├── EquipamentosMarcas.php
│   │   ├── EquipamentosModelos.php
│   │   ├── EquipamentosDefeitos.php
│   │   ├── EquipamentosTipos.php
│   │   ├── Estoque.php
│   │   ├── Financeiro.php
│   │   ├── Fornecedores.php
│   │   ├── Funcionarios.php
│   │   ├── Grupos.php
│   │   ├── Os.php
│   │   ├── Orcamento.php
│   │   ├── Perfil.php
│   │   ├── Relatorios.php
│   │   ├── Servicos.php
│   │   ├── Usuarios.php
│   │   └── Vendas.php
│   │
│   ├── Filters/            ← Middlewares de autenticação
│   │   ├── AuthFilter.php       ← Verifica sessão ativa
│   │   └── PermissionFilter.php ← Verifica permissão RBAC
│   │
│   ├── Helpers/
│   │   └── sistema_helper.php  ← Funções globais (formatDate, can, getStatusBadge...)
│   │
│   ├── Models/             ← Acesso ao banco de dados
│   │   ├── ClienteModel.php
│   │   ├── ConfiguracaoModel.php
│   │   ├── EquipamentoModel.php
│   │   ├── EstoqueModel.php
│   │   ├── FinanceiroModel.php
│   │   ├── GrupoModel.php
│   │   ├── LogModel.php
│   │   ├── OsModel.php
│   │   ├── ServicoModel.php
│   │   └── UsuarioModel.php
│   │
│   └── Views/              ← Templates HTML/PHP
│       ├── layouts/
│       │   ├── main.php    ← Layout base (head, scripts, navbar, sidebar)
│       │   ├── sidebar.php ← Menu lateral
│       │   └── navbar.php  ← Barra superior
│       ├── auth/           ← Login, recuperação de senha
│       ├── clientes/       ← index.php, form.php, show.php
│       ├── equipamentos/   ← index.php, form.php, show.php
│       ├── os/             ← index.php, form.php, show.php, print.php
│       ├── servicos/
│       ├── estoque/
│       ├── financeiro/
│       ├── relatorios/
│       ├── usuarios/
│       ├── grupos/
│       └── configuracoes/
│
├── documentacao/           ← Esta documentação
│
├── public/                 ← Arquivos servidos pelo Apache (raiz web)
│   ├── index.php           ← Ponto de entrada CI4
│   ├── favicon.ico
│   ├── assets/
│   │   ├── css/estilo.css  ← Design system Glassmorphism
│   │   ├── js/scripts.js   ← Scripts globais
│   │   └── json/pt-BR.json ← Tradução DataTables
│   └── uploads/
│       ├── equipamentos/   ← Fotos dos equipamentos
│       └── sistema/        ← Logo e ícone da empresa
│
├── writable/               ← Logs, cache, sessões (CI4)
│   ├── logs/
│   ├── cache/
│   └── session/
│
├── .env                    ← Variáveis de ambiente (NÃO versionar)
├── .htaccess               ← Rewrite rules Apache
└── spark                   ← CLI do CodeIgniter 4
```

---

## Arquivos Críticos

| Arquivo | Modificar com cuidado |
|---------|----------------------|
| `app/Config/Routes.php` | Qualquer rota nova exige entrada aqui |
| `app/Helpers/sistema_helper.php` | Funções globais usadas em Views e Controllers |
| `public/assets/css/estilo.css` | Design system — mudanças afetam todo o sistema |
| `public/assets/js/scripts.js` | Scripts globais (máscaras, CEP, DataTables) |
| `.env` | Credenciais do banco — nunca subir para repositório |
