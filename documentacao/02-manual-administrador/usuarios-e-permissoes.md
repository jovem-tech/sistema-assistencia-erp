# Manual do Administrador — Usuários e Permissões

## 👤 Usuários

**Caminho:** Configurações → Usuários

### Criar Novo Usuário

| Campo | Obrigatório | Descrição |
|-------|-------------|-----------|
| **Nome** | Sim | Nome de exibição |
| **Email** | Sim | Login do usuário |
| **Senha** | Sim | Mínimo 8 caracteres |
| Nível de Acesso | Sim | Grupo de permissões |
| Ativo | Sim | Ativa ou bloqueia o usuário |

> ⚠️ O email deve ser único no sistema.

---

## 🔐 Níveis de Acesso (RBAC)

**Caminho:** Configurações → Níveis de Acesso

O sistema usa **RBAC (Role-Based Access Control)**. Cada usuário pertence a um **grupo** que tem um conjunto de **permissões por módulo**.

### Grupos Padrão

| Grupo | Descrição |
|-------|-----------|
| **Administrador** | Acesso total a todos os módulos e ações |
| **Técnico** | Acesso operacional (OS, Equipamentos, Estoque) |
| **Atendente** | Acesso a clientes e abertura de OS |
| **Financeiro** | Acesso ao módulo financeiro e relatórios |

### Permissões por Módulo

Cada módulo pode ter as seguintes permissões independentes:

| Permissão | Descrição |
|-----------|-----------|
| `visualizar` | Ver listagens e detalhes |
| `criar` | Cadastrar novos registros |
| `editar` | Alterar registros existentes |
| `excluir` | Deletar registros |
| `importar` | Importar dados via CSV |
| `exportar` | Exportar dados para CSV |
| `encerrar` | Encerrar/arquivar registros |

### Módulos com Controle de Acesso

`dashboard`, `clientes`, `equipamentos`, `os`, `servicos`, `estoque`, `financeiro`, `relatorios`, `usuarios`, `grupos`, `configuracoes`, `fornecedores`, `funcionarios`, `vendas`

---

## 🚫 Bloqueios Especiais

- **Clientes**: Não suporta permissão `encerrar` (clientes têm histórico permanente)
- **Dashboard**: Apenas permissão `visualizar` disponível

---

## 🔧 Verificação em Código

No Controller, use sempre:
```php
// Bloqueia todo acesso ao módulo
requirePermission('clientes');

// Verifica ação específica (retorna true/false)
if (can('os', 'criar')) {
    // mostra botão de nova OS
}
```

Na View:
```php
<?php if (can('clientes', 'excluir')): ?>
    <a href="..." class="btn btn-danger btn-delete">Excluir</a>
<?php endif; ?>
```
