# Sistema de Permissões e Controle de Acesso (RBAC)

> **Versão:** 2.0 ? implementado em março/2026  
> **Arquitetura:** Role-Based Access Control (RBAC) ? CodeIgniter 4

---

## 1. Visão Geral

O sistema utiliza um modelo de **controle de acesso baseado em grupos (RBAC)**, onde:

- **Usuários** pertencem a um **Grupo**
- **Grupos** possuem **Permissões**
- **Permissões** são compostas por **Módulo + Ação**
- Toda proteção é aplicada em **duas camadas**: backend (filtro de rota) e frontend (views)

```
Usuário ? pertence ? Grupo ? possui ? Permissão (Módulo + Ação)
```

---

## 2. Estrutura do Banco de Dados

### 2.1 Tabela `grupos`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT PK | Identificador |
| `nome` | VARCHAR(80) | Nome do grupo (ex: Administrador) |
| `descricao` | VARCHAR(200) | Descrição opcional |
| `sistema` | TINYINT(1) | `1` = protegido, não pode ser excluído |
| `created_at` | DATETIME | Data de criação |

**Grupos padrão do sistema:**

| ID | Nome | Sistema | Descrição |
|----|------|---------|-----------|
| 1 | Administrador | ? | Acesso total. Protegido |
| 2 | Técnico | ? | OS, Equipamentos, Estoque. Sem Financeiro |
| 3 | Atendente | ? | Clientes, OS, Equipamentos. Sem Administração |

---

### 2.2 Tabela `modulos`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT PK | Identificador |
| `nome` | VARCHAR(80) | Nome exibido |
| `slug` | VARCHAR(80) UNIQUE | Chave usada no código |
| `icone` | VARCHAR(60) | Classe Bootstrap Icons |
| `ordem_menu` | INT | Ordem no sidebar |
| `ativo` | TINYINT(1) | Habilita/desabilita |

**Módulos cadastrados:**

| Slug | Nome | Ordem |
|------|------|-------|
| `dashboard` | Dashboard | 1 |
| `clientes` | Clientes | 10 |
| `fornecedores` | Fornecedores | 11 |
| `funcionarios` | Funcionários | 12 |
| `usuarios` | Usuários | 13 |
| `grupos` | Grupos de Acesso | 14 |
| `equipamentos` | Equipamentos | 20 |
| `os` | Ordens de Serviço | 30 |
| `estoque` | Estoque | 40 |
| `financeiro` | Financeiro | 50 |
| `relatorios` | Relatórios | 60 |
| `configuracoes` | Configurações | 70 |

> **Nota:** Os submenus de Equipamentos (Tipos, Marcas, Modelos, Defeitos Comuns) **herdam** do módulo `equipamentos`. Não são módulos separados.

---

### 2.3 Tabela `permissoes`

| ID | Nome | Slug |
|----|------|------|
| 1 | Visualizar | `visualizar` |
| 2 | Criar | `criar` |
| 3 | Editar | `editar` |
| 4 | Excluir | `excluir` |
| 5 | Exportar | `exportar` |
| 6 | Importar | `importar` |
| 7 | Encerrar | `encerrar` |

---

### 2.4 Tabela `grupo_permissoes`

Tabela central que associa Grupo + Módulo + Permissão.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT PK | Identificador |
| `grupo_id` | INT FK | Referência a `grupos` |
| `modulo_id` | INT FK | Referência a `modulos` |
| `permissao_id` | INT FK | Referência a `permissoes` |

**Chave única:** `(grupo_id, modulo_id, permissao_id)` ? sem permissões duplicadas.

---

### 2.5 Campo `grupo_id` em `usuarios`

```sql
ALTER TABLE usuarios ADD COLUMN grupo_id INT NULL;
ALTER TABLE usuarios ADD FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL;
```

---

## 3. Matriz de Permissões por Grupo

| Módulo | Admin | Técnico | Atendente |
|--------|:-----:|:-------:|:---------:|
| dashboard ? visualizar | ? | ? | ? |
| clientes ? visualizar | ? | ? | ? |
| clientes ? criar | ? | ? | ? |
| clientes ? editar | ? | ? | ? |
| clientes ? excluir | ? | ? | ? |
| clientes ? importar | ? | ? | ? |
| fornecedores ? todas | ? | ? | ? (vis/cri/edi) |
| funcionarios ? todas | ? | ? | ? |
| usuarios ? todas | ? | ? | ? |
| grupos ? todas | ? | ? | ? |
| equipamentos ? visualizar | ? | ? | ? |
| equipamentos ? criar | ? | ? | ? |
| equipamentos ? editar | ? | ? | ? |
| equipamentos ? excluir | ? | ? | ? |
| equipamentos ? importar | ? | ? | ? |
| os ? visualizar | ? | ? | ? |
| os ? criar | ? | ? | ? |
| os ? editar | ? | ? | ? |
| estoque ? visualizar | ? | ? | ? |
| estoque ? criar | ? | ? | ? |
| estoque ? editar | ? | ? | ? |
| estoque ? excluir | ? | ? | ? |
| financeiro ? todas | ? | ? | ? |
| relatorios ? visualizar | ? | ? | ? |
| configuracoes ? todas | ? | ? | ? |

---

## 4. Arquitetura de Código

### 4.1 Fluxo completo de uma requisição

```
Browser/Cliente
      ?
      ? GET /financeiro
      ?
???????????????????????????????????????????
?  CI4 Router ? Routes.php                ?
?  ['filter' => 'auth']                   ???? Grupo externo: apenas logado?
?  ['filter' => 'permission:financeiro:visualizar'] ?
???????????????????????????????????????????
               ?
               ?
???????????????????????????????????????????
?  AuthFilter (Filters/AuthFilter.php)    ?
?  ? session->get('logged_in') ?          ?
?  ? Verifica timeout de 30 min           ?
?  ? Atualiza last_activity               ?
???????????????????????????????????????????
               ? autenticado ?
               ?
???????????????????????????????????????????
?  PermissionFilter (Filters/PermissionFilter.php) ?
?  ? Extrai "financeiro:visualizar"       ?
?  ? Chama can('financeiro','visualizar') ?
?    ??? loadUserPermissions()            ?
?    ?   ??? Cache session? retorna       ?
?    ?   ??? Query grupo_permissoes       ?
?    ??? Verifica mapa de permissões      ?
?                                         ?
?  NÃO TEM PERMISSÃO?                     ?
?  ??? AJAX ? HTTP 403 JSON               ?
?  ??? Browser ? redirect /dashboard     ?
?               + flashdata 'error'       ?
?               + LogModel::registrar()   ?
???????????????????????????????????????????
               ? autorizado ?
               ?
???????????????????????????????????????????
?  Controller::action()                    ?
?  Executa a lógica de negócio             ?
???????????????????????????????????????????
               ?
               ?
???????????????????????????????????????????
?  View ? botões protegidos               ?
?  <?php if (can('financeiro','criar')): ?> ?
?    <a href="...">Novo Lançamento</a>    ?
?  <?php endif; ?>                        ?
???????????????????????????????????????????
```

---

### 4.2 Funções RBAC ? `app/Helpers/sistema_helper.php`

```php
// ??? Verifica se o usuário pode executar uma ação num módulo
can(string $modulo, string $acao): bool

// Exemplos:
can('financeiro', 'visualizar')  // true/false
can('clientes',   'excluir')     // true/false
can('os',         'criar')       // true/false

// ??? Atalho: verifica apenas 'visualizar' (sidebar)
canModule(string $modulo): bool

// Exemplos:
canModule('financeiro')  // equivale a can('financeiro', 'visualizar')

// ??? Força recarga do cache de permissões
refreshPermissions(): void
// Chamar após alterar permissões de um grupo enquanto o usuário está logado

// ??? Aborta com redirect 403 (uso legado em controllers)
requirePermission(string $modulo, string $acao = 'visualizar'): void
```

#### Como `loadUserPermissions()` funciona:

```php
// 1. Verifica cache na sessão
session()->get('user_permissions')
// Estrutura: ['clientes' => ['visualizar', 'criar', 'editar'], 'financeiro' => ['visualizar'], ...]

// 2. Se não há cache: consulta o banco
SELECT m.slug as modulo, p.slug as permissao
FROM grupo_permissoes gp
JOIN modulos m    ON m.id = gp.modulo_id
JOIN permissoes p ON p.id = gp.permissao_id
WHERE gp.grupo_id = {user_grupo_id}

// 3. Compatibilidade: admin legado (sem grupo_id)
// session->get('user_perfil') === 'admin' ? retorna ['*' => ['*']] (wildcard total)
```

---

### 4.3 PermissionFilter ? `app/Filters/PermissionFilter.php`

```php
// Registro em app/Config/Filters.php:
'permission' => \App\Filters\PermissionFilter::class

// Uso em Routes.php:
$routes->get('financeiro', 'Financeiro::index',
    ['filter' => 'permission:financeiro:visualizar']
);

// Formato do argumento: "modulo:acao"
// Parsing dentro do filtro:
$parts  = explode(':', $arguments[0]);
$modulo = $parts[0];  // ex: "financeiro"
$acao   = $parts[1];  // ex: "visualizar"
```

**Comportamento por tipo de requisição:**

| Tipo | Sem permissão | Com permissão |
|------|--------------|--------------|
| Browser (GET) | Redirect `/dashboard` + flash error | Passa para o controller |
| AJAX (XHR) | HTTP 403 + JSON `{"error": "..."}` | Passa para o controller |

---

### 4.4 Proteção nas Views ? padrão `can()`

```php
// Botão criar (cabeçalho)
<?php if (can('clientes', 'criar')): ?>
    <a href="<?= base_url('clientes/novo') ?>">Novo Cliente</a>
<?php endif; ?>

// Botão editar (por linha)
<?php if (can('clientes', 'editar')): ?>
    <a href="<?= base_url('clientes/editar/' . $c['id']) ?>">??</a>
<?php endif; ?>

// Botão excluir (por linha)
<?php if (can('clientes', 'excluir')): ?>
    <a href="<?= base_url('clientes/excluir/' . $c['id']) ?>">??</a>
<?php endif; ?>

// Visibilidade de seção inteira
<?php if (can('os', 'editar')): ?>
    <form><!-- formulário de adicionar item --></form>
<?php endif; ?>
```

---

## 5. Rotas Protegidas ? Mapa Completo

### Dashboard
```
GET  dashboard                    ? auth apenas (sem permission filter)
GET  admin/stats                  ? auth apenas
```

### Perfil
```
GET  perfil                       ? auth apenas
POST perfil/salvar                ? auth apenas
```

### Grupos de Acesso
```
GET  grupos                       ? permission:grupos:visualizar
GET  grupos/novo                  ? permission:grupos:criar
POST grupos/salvar                ? permission:grupos:criar
GET  grupos/editar/:id            ? permission:grupos:editar
POST grupos/atualizar/:id         ? permission:grupos:editar
GET  grupos/excluir/:id           ? permission:grupos:excluir
GET  grupos/:id/permissoes        ? permission:grupos:editar
POST grupos/:id/permissoes/salvar ? permission:grupos:editar
```

### Clientes
```
GET  clientes                     ? permission:clientes:visualizar
GET  clientes/novo                ? permission:clientes:criar
POST clientes/salvar              ? permission:clientes:criar
GET  clientes/editar/:id          ? permission:clientes:editar
POST clientes/atualizar/:id       ? permission:clientes:editar
GET  clientes/excluir/:id         ? permission:clientes:excluir
GET  clientes/visualizar/:id      ? permission:clientes:visualizar
GET  clientes/buscar              ? permission:clientes:visualizar
GET  clientes/json/:id            ? permission:clientes:visualizar
POST clientes/importar            ? permission:clientes:importar
GET  clientes/modelo-csv          ? permission:clientes:importar
```

### Fornecedores
```
GET  fornecedores                 ? permission:fornecedores:visualizar
GET  fornecedores/novo            ? permission:fornecedores:criar
POST fornecedores/salvar          ? permission:fornecedores:criar
GET  fornecedores/editar/:id      ? permission:fornecedores:editar
POST fornecedores/atualizar/:id   ? permission:fornecedores:editar
GET  fornecedores/excluir/:id     ? permission:fornecedores:excluir
```

### Funcionários
```
GET  funcionarios                 ? permission:funcionarios:visualizar
GET  funcionarios/novo            ? permission:funcionarios:criar
POST funcionarios/salvar          ? permission:funcionarios:criar
GET  funcionarios/editar/:id      ? permission:funcionarios:editar
POST funcionarios/atualizar/:id   ? permission:funcionarios:editar
GET  funcionarios/excluir/:id     ? permission:funcionarios:excluir
```

### Equipamentos + Submenus
```
GET  equipamentos                       ? permission:equipamentos:visualizar
GET  equipamentos/novo                  ? permission:equipamentos:criar
POST equipamentos/salvar                ? permission:equipamentos:criar
GET  equipamentos/editar/:id            ? permission:equipamentos:editar
POST equipamentos/atualizar/:id         ? permission:equipamentos:editar
GET  equipamentos/excluir/:id           ? permission:equipamentos:excluir
GET  equipamentos/por-cliente/:id       ? permission:equipamentos:visualizar

// Tipos
GET  equipamentostipos                  ? permission:equipamentos:visualizar
POST equipamentostipos/salvar           ? permission:equipamentos:criar
GET  equipamentostipos/excluir/:id      ? permission:equipamentos:excluir

// Marcas
GET  equipamentosmarcas                 ? permission:equipamentos:visualizar
POST equipamentosmarcas/salvar          ? permission:equipamentos:criar
GET  equipamentosmarcas/excluir/:id     ? permission:equipamentos:excluir
POST equipamentosmarcas/importar        ? permission:equipamentos:importar

// Modelos
GET  equipamentosmodelos                ? permission:equipamentos:visualizar
POST equipamentosmodelos/salvar         ? permission:equipamentos:criar
GET  equipamentosmodelos/excluir/:id    ? permission:equipamentos:excluir
POST equipamentosmodelos/importar       ? permission:equipamentos:importar
POST equipamentosmodelos/por-marca      ? permission:equipamentos:visualizar

// Defeitos Comuns
GET  equipamentosdefeitos               ? permission:equipamentos:visualizar
POST equipamentosdefeitos/salvar        ? permission:equipamentos:criar
GET  equipamentosdefeitos/editar/:id    ? permission:equipamentos:editar
POST equipamentosdefeitos/atualizar/:id ? permission:equipamentos:editar
GET  equipamentosdefeitos/excluir/:id   ? permission:equipamentos:excluir
POST equipamentosdefeitos/por-tipo      ? permission:equipamentos:visualizar
POST equipamentosdefeitos/importar      ? permission:equipamentos:importar
GET  equipamentosdefeitos/modelo-csv    ? permission:equipamentos:visualizar
```

### Ordens de Serviço
```
GET  os                           ? permission:os:visualizar
POST os/datatable                 ? permission:os:visualizar
GET  os/nova                      ? permission:os:criar
POST os/salvar                    ? permission:os:criar
GET  os/editar/:id                ? permission:os:editar
POST os/atualizar/:id             ? permission:os:editar
GET  os/visualizar/:id            ? permission:os:visualizar
POST os/status/:id                ? permission:os:editar
GET  os/imprimir/:id              ? permission:os:visualizar
POST os/item/salvar               ? permission:os:editar
GET  os/item/excluir/:id          ? permission:os:editar
```

### Estoque
```
GET  estoque                      ? permission:estoque:visualizar
GET  estoque/novo                 ? permission:estoque:criar
POST estoque/salvar               ? permission:estoque:criar
GET  estoque/editar/:id           ? permission:estoque:editar
POST estoque/atualizar/:id        ? permission:estoque:editar
GET  estoque/excluir/:id          ? permission:estoque:excluir
POST estoque/movimentacao         ? permission:estoque:editar
GET  estoque/movimentacoes/:id    ? permission:estoque:visualizar
GET  estoque/buscar               ? permission:estoque:visualizar
```

### Financeiro
```
GET  financeiro                   ? permission:financeiro:visualizar
GET  financeiro/novo              ? permission:financeiro:criar
POST financeiro/salvar            ? permission:financeiro:criar
GET  financeiro/editar/:id        ? permission:financeiro:editar
POST financeiro/atualizar/:id     ? permission:financeiro:editar
GET  financeiro/excluir/:id       ? permission:financeiro:excluir
POST financeiro/baixar/:id        ? permission:financeiro:editar
```

### Relatórios
```
GET  relatorios                   ? permission:relatorios:visualizar
GET  relatorios/os                ? permission:relatorios:visualizar
GET  relatorios/financeiro        ? permission:relatorios:visualizar
GET  relatorios/estoque           ? permission:relatorios:visualizar
GET  relatorios/clientes          ? permission:relatorios:visualizar
```

### Configurações
```
GET  configuracoes                ? permission:configuracoes:visualizar
POST configuracoes/salvar         ? permission:configuracoes:editar
```

### Usuários
```
GET  usuarios                     ? permission:usuarios:visualizar
POST usuarios/datatable           ? permission:usuarios:visualizar
GET  usuarios/novo                ? permission:usuarios:criar
POST usuarios/salvar              ? permission:usuarios:criar
GET  usuarios/editar/:id          ? permission:usuarios:editar
POST usuarios/atualizar/:id       ? permission:usuarios:editar
GET  usuarios/excluir/:id         ? permission:usuarios:excluir
```

---

## 6. Gestão de Permissões pela Interface

### 6.1 Tela de Grupos (`/grupos`)
- Lista grupos existentes
- Botão "Configurar Permissões" leva para a matriz

### 6.2 Tela de Permissões do Grupo (`/grupos/:id/permissoes`)
- Exibe tabela **Módulo × Ação** com checkboxes
- Admin marca/desmarca cada combinação
- `POST /grupos/:id/permissoes/salvar` persiste as mudanças
- Se o usuário logado pertence ao grupo editado ? `refreshPermissions()` é chamado automaticamente

### 6.3 Cache de Sessão
```
Login ? loadUserPermissions() cacheia em session['user_permissions']
     ? Validado em cada can() / canModule()
     ? Invalidado por refreshPermissions() ou logout
```

---

## 7. Como Adicionar um Novo Módulo

### Passo 1 ? Banco de Dados
```sql
INSERT INTO modulos (nome, slug, icone, ordem_menu)
VALUES ('Contratos', 'contratos', 'bi-file-earmark-text', 55);
```

### Passo 2 ? Configurar Permissões dos Grupos
Acesse `/grupos` ? clique em "Configurar Permissões" no grupo desejado e marque as ações permitidas.

### Passo 3 ? Proteger as rotas em `Routes.php`
```php
// ?? Contratos ?????????????????????????????????????????????????????????
$routes->get('contratos',                 'Contratos::index',   ['filter' => 'permission:contratos:visualizar']);
$routes->get('contratos/novo',            'Contratos::create',  ['filter' => 'permission:contratos:criar']);
$routes->post('contratos/salvar',         'Contratos::store',   ['filter' => 'permission:contratos:criar']);
$routes->get('contratos/editar/(:num)',   'Contratos::edit/$1', ['filter' => 'permission:contratos:editar']);
$routes->post('contratos/atualizar/(:num)','Contratos::update/$1',['filter' => 'permission:contratos:editar']);
$routes->get('contratos/excluir/(:num)', 'Contratos::delete/$1',['filter' => 'permission:contratos:excluir']);
```

### Passo 4 ? Proteger botões nas Views
```php
// Cabeçalho
<?php if (can('contratos', 'criar')): ?>
    <a href="<?= base_url('contratos/novo') ?>">Novo Contrato</a>
<?php endif; ?>

// Na linha da tabela
<?php if (can('contratos', 'editar')): ?>
    <a href="<?= base_url('contratos/editar/' . $c['id']) ?>">??</a>
<?php endif; ?>
<?php if (can('contratos', 'excluir')): ?>
    <a href="<?= base_url('contratos/excluir/' . $c['id']) ?>">??</a>
<?php endif; ?>
```

### Passo 5 ? Adicionar ao Sidebar (`layouts/sidebar.php`)
```php
<?php if (canModule('contratos')): ?>
<li class="nav-item">
    <a class="nav-link <?= str_starts_with(uri_string(), 'contratos') ? 'active' : '' ?>"
       href="<?= base_url('contratos') ?>">
        <i class="bi bi-file-earmark-text"></i>
        <span>Contratos</span>
    </a>
</li>
<?php endif; ?>
```

---

## 8. Compatibilidade com Sistema Legado

O sistema mantém retrocompatibilidade com o campo `perfil` (admin/tecnico/atendente):

```php
// loadUserPermissions() em sistema_helper.php
// Admin legado SEM grupo_id ? acesso wildcard total:
if (session()->get('user_perfil') === 'admin') {
    return ['*' => ['*']];
}

// can() verifica wildcard:
if (isset($permissions['*'])) return true;
```

```php
// UsuarioModel::getTecnicos()
// Considera tanto grupo 'Técnico' quanto perfil legado 'tecnico'
WHERE g.nome = 'Técnico' OR u.perfil = 'tecnico'
```

---

## 9. Arquivos do Sistema RBAC

| Arquivo | Responsabilidade |
|---------|-----------------|
| `app/Helpers/sistema_helper.php` | Funções `can()`, `canModule()`, `loadUserPermissions()`, `refreshPermissions()`, `requirePermission()` |
| `app/Filters/PermissionFilter.php` | Filtro CI4 que bloqueia rotas sem permissão |
| `app/Filters/AuthFilter.php` | Filtro CI4 que bloqueia rotas sem autenticação |
| `app/Config/Filters.php` | Registro dos alias dos filtros (`auth`, `permission`) |
| `app/Config/Routes.php` | Proteção declarativa de todas as rotas |
| `app/Controllers/Grupos.php` | CRUD de grupos + gestão da matriz de permissões |
| `app/Models/GrupoModel.php` | Query da matriz `modulos × permissoes` |
| `app/Views/grupos/permissoes.php` | Interface visual de checkboxes por grupo |
| `app/Views/layouts/sidebar.php` | Sidebar dinâmico com `canModule()` |
| `setup_rbac.php` | Script de inicialização (rodar uma vez após deploy) |

---

## 10. Segurança ? Camadas de Defesa

```
??????????????????????????????????????????????????????????????
?  CAMADA 1 ? AuthFilter                                     ?
?  Verifica sessão + timeout 30min                           ?
?  ? Protege TODAS as rotas do grupo protegido               ?
??????????????????????????????????????????????????????????????
?  CAMADA 2 ? PermissionFilter                               ?
?  Verifica módulo:ação específico por rota                  ?
?  ? Bloqueia acesso por URL direta (OWASP A01:2021)         ?
?  ? Loga tentativas não autorizadas                         ?
??????????????????????????????????????????????????????????????
?  CAMADA 3 ? can() nas Views                                ?
?  Oculta botões e seções sem permissão                      ?
?  ? Melhora UX, reduz confusão                              ?
?  ? NÃO substitui as camadas 1 e 2                          ?
??????????????????????????????????????????????????????????????
```

> **Princípio:** A segurança real está nas camadas 1 e 2 (backend). A camada 3 (frontend) é apenas UX. Um usuário mal-intencionado que desabilite JS ou manipule HTML ainda será bloqueado pelo backend.
