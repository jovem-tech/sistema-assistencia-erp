# Sistema de PermissÃµes e Controle de Acesso (RBAC)

> **VersÃ£o:** 2.0 ? implementado em marÃ§o/2026
> **Arquitetura:** Role-Based Access Control (RBAC) ? CodeIgniter 4

> **Atualizacao relevante em 05/06/2026:** `crm`, `atendimento_whatsapp` e `precificacao` passaram a ser modulos RBAC independentes.

---

## 1. VisÃ£o Geral

O sistema utiliza um modelo de **controle de acesso baseado em grupos (RBAC)**, onde:

- **UsuÃ¡rios** pertencem a um **Grupo**
- **Grupos** possuem **PermissÃµes**
- **PermissÃµes** sÃ£o compostas por **MÃ³dulo + AÃ§Ã£o**
- Toda proteÃ§Ã£o Ã© aplicada em **duas camadas**: backend (filtro de rota) e frontend (views)

```
UsuÃ¡rio ? pertence ? Grupo ? possui ? PermissÃ£o (MÃ³dulo + AÃ§Ã£o)
```

---

## 2. Estrutura do Banco de Dados

### 2.1 Tabela `grupos`

| Coluna | Tipo | DescriÃ§Ã£o |
|--------|------|-----------|
| `id` | INT PK | Identificador |
| `nome` | VARCHAR(80) | Nome do grupo (ex: Administrador) |
| `descricao` | VARCHAR(200) | DescriÃ§Ã£o opcional |
| `sistema` | TINYINT(1) | `1` = protegido, nÃ£o pode ser excluÃ­do |
| `created_at` | DATETIME | Data de criaÃ§Ã£o |

**Grupos padrÃ£o do sistema:**

| ID | Nome | Sistema | DescriÃ§Ã£o |
|----|------|---------|-----------|
| 1 | Administrador | ? | Acesso total. Protegido |
| 2 | TÃ©cnico | ? | OS, Equipamentos, Estoque. Sem Financeiro |
| 3 | Atendente | ? | Clientes, OS, Equipamentos. Sem AdministraÃ§Ã£o |

---

### 2.2 Tabela `modulos`

| Coluna | Tipo | DescriÃ§Ã£o |
|--------|------|-----------|
| `id` | INT PK | Identificador |
| `nome` | VARCHAR(80) | Nome exibido |
| `slug` | VARCHAR(80) UNIQUE | Chave usada no cÃ³digo |
| `icone` | VARCHAR(60) | Classe Bootstrap Icons |
| `ordem_menu` | INT | Ordem no sidebar |
| `ativo` | TINYINT(1) | Habilita/desabilita |

**MÃ³dulos cadastrados:**

| Slug | Nome | Ordem |
|------|------|-------|
| `dashboard` | Dashboard | 1 |
| `clientes` | Clientes | 10 |
| `fornecedores` | Fornecedores | 11 |
| `funcionarios` | FuncionÃ¡rios | 12 |
| `usuarios` | UsuÃ¡rios | 13 |
| `grupos` | Grupos de Acesso | 14 |
| `equipamentos` | Equipamentos | 20 |
| `os` | Ordens de ServiÃ§o | 30 |
| `estoque` | Estoque | 40 |
| `financeiro` | Financeiro | 50 |
| `relatorios` | RelatÃ³rios | 60 |
| `configuracoes` | ConfiguraÃ§Ãµes | 70 |

> **Nota:** Os submenus de Equipamentos (Tipos, Marcas, Modelos, Defeitos Comuns) **herdam** do mÃ³dulo `equipamentos`. NÃ£o sÃ£o mÃ³dulos separados.

### 2.2.1 Modulos evoluidos da linha atual

AlÃ©m da base original, a linha atual do ERP trabalha com os seguintes slugs independentes no RBAC:

- `servicos`
- `defeitos`
- `orcamentos`
- `crm`
- `atendimento_whatsapp`
- `precificacao`
- `vendas`

Compatibilidade aplicada pela migration `2026-06-05-160000_SyncEvolvedRbacModules`:

- `crm` herda permissoes equivalentes de `clientes` no primeiro sync;
- `atendimento_whatsapp` herda permissoes equivalentes de `clientes` no primeiro sync;
- `precificacao` herda permissoes equivalentes de `orcamentos` no primeiro sync;
- o grupo `Administrador` recebe acesso completo a esses novos modulos automaticamente.

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

Tabela central que associa Grupo + MÃ³dulo + PermissÃ£o.

| Coluna | Tipo | DescriÃ§Ã£o |
|--------|------|-----------|
| `id` | INT PK | Identificador |
| `grupo_id` | INT FK | ReferÃªncia a `grupos` |
| `modulo_id` | INT FK | ReferÃªncia a `modulos` |
| `permissao_id` | INT FK | ReferÃªncia a `permissoes` |

**Chave Ãºnica:** `(grupo_id, modulo_id, permissao_id)` ? sem permissÃµes duplicadas.

---

### 2.5 Campo `grupo_id` em `usuarios`

```sql
ALTER TABLE usuarios ADD COLUMN grupo_id INT NULL;
ALTER TABLE usuarios ADD FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL;
```

---

## 3. Matriz de PermissÃµes por Grupo

| MÃ³dulo | Admin | TÃ©cnico | Atendente |
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

## 4. Arquitetura de CÃ³digo

### 4.1 Fluxo completo de uma requisiÃ§Ã£o

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
?    ??? Verifica mapa de permissÃµes      ?
?                                         ?
?  NÃƒO TEM PERMISSÃƒO?                     ?
?  ??? AJAX ? HTTP 403 JSON               ?
?  ??? Browser ? redirect /dashboard     ?
?               + flashdata 'error'       ?
?               + LogModel::registrar()   ?
???????????????????????????????????????????
               ? autorizado ?
               ?
???????????????????????????????????????????
?  Controller::action()                    ?
?  Executa a lÃ³gica de negÃ³cio             ?
???????????????????????????????????????????
               ?
               ?
???????????????????????????????????????????
?  View ? botÃµes protegidos               ?
?  <?php if (can('financeiro','criar')): ?> ?
?    <a href="...">Novo LanÃ§amento</a>    ?
?  <?php endif; ?>                        ?
???????????????????????????????????????????
```

---

### 4.2 FunÃ§Ãµes RBAC ? `app/Helpers/sistema_helper.php`

```php
// ??? Verifica se o usuÃ¡rio pode executar uma aÃ§Ã£o num mÃ³dulo
can(string $modulo, string $acao): bool

// Exemplos:
can('financeiro', 'visualizar')  // true/false
can('crm',        'editar')      // true/false
can('os',         'criar')       // true/false

// ??? Atalho: verifica apenas 'visualizar' (sidebar)
canModule(string $modulo): bool

// Exemplos:
canModule('financeiro')  // equivale a can('financeiro', 'visualizar')
canModule('atendimento_whatsapp')

// ??? ForÃ§a recarga do cache de permissÃµes
refreshPermissions(): void
// Chamar apÃ³s alterar permissÃµes de um grupo enquanto o usuÃ¡rio estÃ¡ logado

// ??? Aborta com redirect 403 (uso legado em controllers)
requirePermission(string $modulo, string $acao = 'visualizar'): void
```

#### Como `loadUserPermissions()` funciona:

```php
// 1. Verifica cache na sessÃ£o
session()->get('user_permissions')
// Estrutura: ['clientes' => ['visualizar', 'criar', 'editar'], 'financeiro' => ['visualizar'], ...]

// 2. Se nÃ£o hÃ¡ cache: consulta o banco
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

**Comportamento por tipo de requisiÃ§Ã£o:**

| Tipo | Sem permissÃ£o | Com permissÃ£o |
|------|--------------|--------------|
| Browser (GET) | Redirect `/dashboard` + flash error | Passa para o controller |
| AJAX (XHR) | HTTP 403 + JSON `{"error": "..."}` | Passa para o controller |

---

### 4.4 ProteÃ§Ã£o nas Views ? padrÃ£o `can()`

```php
// BotÃ£o criar (cabeÃ§alho)
<?php if (can('clientes', 'criar')): ?>
    <a href="<?= base_url('clientes/novo') ?>">Novo Cliente</a>
<?php endif; ?>

// BotÃ£o editar (por linha)
<?php if (can('clientes', 'editar')): ?>
    <a href="<?= base_url('clientes/editar/' . $c['id']) ?>">??</a>
<?php endif; ?>

// BotÃ£o excluir (por linha)
<?php if (can('clientes', 'excluir')): ?>
    <a href="<?= base_url('clientes/excluir/' . $c['id']) ?>">??</a>
<?php endif; ?>

// Visibilidade de seÃ§Ã£o inteira
<?php if (can('os', 'editar')): ?>
    <form><!-- formulÃ¡rio de adicionar item --></form>
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

### CRM
```
GET  crm/clientes                         ? permission:crm:visualizar
GET  crm/timeline                         ? permission:crm:visualizar
GET  crm/interacoes                       ? permission:crm:visualizar
POST crm/interacoes/salvar                ? permission:crm:criar
GET  crm/followups                        ? permission:crm:visualizar
POST crm/followups/salvar                 ? permission:crm:criar
POST crm/followups/:id/status             ? permission:crm:editar
GET  crm/campanhas                        ? permission:crm:visualizar
GET  crm/metricas-marketing               ? permission:crm:visualizar
POST crm/metricas-marketing/engajamento   ? permission:crm:editar
POST crm/clientes-inativos/followup       ? permission:crm:criar
```

### Central de Mensagens / WhatsApp
```
GET  atendimento-whatsapp                           ? permission:atendimento_whatsapp:visualizar
GET  atendimento-whatsapp/conversas                 ? permission:atendimento_whatsapp:visualizar
GET  atendimento-whatsapp/conversa/:id              ? permission:atendimento_whatsapp:visualizar
POST atendimento-whatsapp/enviar                    ? permission:atendimento_whatsapp:editar
POST atendimento-whatsapp/vincular-os               ? permission:atendimento_whatsapp:editar
POST atendimento-whatsapp/atualizar-meta            ? permission:atendimento_whatsapp:editar
POST atendimento-whatsapp/sync-inbound              ? permission:atendimento_whatsapp:editar
POST atendimento-whatsapp/conversa/:id/cadastrar-contato ? permission:atendimento_whatsapp:editar
```

Observacao importante:
- o fluxo `cadastrar-contato` tambem valida `clientes:criar` ou `clientes:editar`, porque a conversa pode gerar ou atualizar cadastro de contato/cliente no dominio de pessoas.

### Precificacao
```
GET  precificacao                         ? permission:precificacao:visualizar
GET  precificacao/configuracao            ? permission:precificacao:visualizar
POST precificacao/configuracao/salvar     ? permission:precificacao:editar
GET  precificacao/simulador               ? permission:precificacao:visualizar
POST precificacao/simular-peca            ? permission:precificacao:visualizar
POST precificacao/simular-servico         ? permission:precificacao:visualizar
POST precificacao/salvar                  ? permission:precificacao:editar
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

### FuncionÃ¡rios
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

### Ordens de ServiÃ§o
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

### RelatÃ³rios
```
GET  relatorios                   ? permission:relatorios:visualizar
GET  relatorios/os                ? permission:relatorios:visualizar
GET  relatorios/financeiro        ? permission:relatorios:visualizar
GET  relatorios/estoque           ? permission:relatorios:visualizar
GET  relatorios/clientes          ? permission:relatorios:visualizar
```

### ConfiguraÃ§Ãµes
```
GET  configuracoes                ? permission:configuracoes:visualizar
POST configuracoes/salvar         ? permission:configuracoes:editar
```

### UsuÃ¡rios
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

## 6. GestÃ£o de PermissÃµes pela Interface

### 6.1 Tela de Grupos (`/grupos`)
- Lista grupos existentes
- BotÃ£o "Configurar PermissÃµes" leva para a matriz

### 6.2 Tela de PermissÃµes do Grupo (`/grupos/:id/permissoes`)
- Exibe tabela **MÃ³dulo Ã— AÃ§Ã£o** com checkboxes
- Admin marca/desmarca cada combinaÃ§Ã£o
- `POST /grupos/:id/permissoes/salvar` persiste as mudanÃ§as
- Se o usuÃ¡rio logado pertence ao grupo editado ? `refreshPermissions()` Ã© chamado automaticamente

### 6.3 Cache de SessÃ£o
```
Login ? loadUserPermissions() cacheia em session['user_permissions']
     ? Validado em cada can() / canModule()
     ? Invalidado por refreshPermissions() ou logout
```

---

## 7. Como Adicionar um Novo MÃ³dulo

### Passo 1 ? Banco de Dados
```sql
INSERT INTO modulos (nome, slug, icone, ordem_menu)
VALUES ('Contratos', 'contratos', 'bi-file-earmark-text', 55);
```

### Passo 2 ? Configurar PermissÃµes dos Grupos
Acesse `/grupos` ? clique em "Configurar PermissÃµes" no grupo desejado e marque as aÃ§Ãµes permitidas.

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

### Passo 4 ? Proteger botÃµes nas Views
```php
// CabeÃ§alho
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

O sistema mantÃ©m retrocompatibilidade com o campo `perfil` (admin/tecnico/atendente):

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
// Considera tanto grupo 'TÃ©cnico' quanto perfil legado 'tecnico'
WHERE g.nome = 'TÃ©cnico' OR u.perfil = 'tecnico'
```

---

## 9. Arquivos do Sistema RBAC

| Arquivo | Responsabilidade |
|---------|-----------------|
| `app/Helpers/sistema_helper.php` | FunÃ§Ãµes `can()`, `canModule()`, `loadUserPermissions()`, `refreshPermissions()`, `requirePermission()` |
| `app/Filters/PermissionFilter.php` | Filtro CI4 que bloqueia rotas sem permissÃ£o |
| `app/Filters/AuthFilter.php` | Filtro CI4 que bloqueia rotas sem autenticaÃ§Ã£o |
| `app/Config/Filters.php` | Registro dos alias dos filtros (`auth`, `permission`) |
| `app/Config/Routes.php` | ProteÃ§Ã£o declarativa de todas as rotas |
| `app/Controllers/Grupos.php` | CRUD de grupos + gestÃ£o da matriz de permissÃµes |
| `app/Models/GrupoModel.php` | Query da matriz `modulos Ã— permissoes` |
| `app/Views/grupos/permissoes.php` | Interface visual de checkboxes por grupo |
| `app/Views/layouts/sidebar.php` | Sidebar dinÃ¢mico com `canModule()` |
| `setup_rbac.php` | Script de inicializaÃ§Ã£o (rodar uma vez apÃ³s deploy) |

---

## 10. SeguranÃ§a ? Camadas de Defesa

```
??????????????????????????????????????????????????????????????
?  CAMADA 1 ? AuthFilter                                     ?
?  Verifica sessÃ£o + timeout 30min                           ?
?  ? Protege TODAS as rotas do grupo protegido               ?
??????????????????????????????????????????????????????????????
?  CAMADA 2 ? PermissionFilter                               ?
?  Verifica mÃ³dulo:aÃ§Ã£o especÃ­fico por rota                  ?
?  ? Bloqueia acesso por URL direta (OWASP A01:2021)         ?
?  ? Loga tentativas nÃ£o autorizadas                         ?
??????????????????????????????????????????????????????????????
?  CAMADA 3 ? can() nas Views                                ?
?  Oculta botÃµes e seÃ§Ãµes sem permissÃ£o                      ?
?  ? Melhora UX, reduz confusÃ£o                              ?
?  ? NÃƒO substitui as camadas 1 e 2                          ?
??????????????????????????????????????????????????????????????
```

> **PrincÃ­pio:** A seguranÃ§a real estÃ¡ nas camadas 1 e 2 (backend). A camada 3 (frontend) Ã© apenas UX. Um usuÃ¡rio mal-intencionado que desabilite JS ou manipule HTML ainda serÃ¡ bloqueado pelo backend.
