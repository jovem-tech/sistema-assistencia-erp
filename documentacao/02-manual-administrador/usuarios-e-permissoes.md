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
| **Atendente** | Acesso a clientes, CRM, WhatsApp e abertura de OS |
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

`dashboard`, `clientes`, `crm`, `atendimento_whatsapp`, `equipamentos`, `os`, `servicos`, `estoque`, `orcamentos`, `precificacao`, `financeiro`, `relatorios`, `usuarios`, `grupos`, `configuracoes`, `fornecedores`, `funcionarios`, `vendas`

### Evolucao do RBAC em 05/06/2026

Os modulos abaixo passaram a ter controle de acesso proprio, sem depender mais de permissao herdada apenas por menu:

- `crm`: controla timeline, interacoes, follow-ups, campanhas, clientes inativos e metricas de marketing
- `atendimento_whatsapp`: controla inbox, chatbot, FAQ, filas, metricas e configuracoes da Central de Mensagens
- `precificacao`: controla configuracao e simulador de precificacao, sem depender mais de `orcamentos`

Compatibilidade operacional:

- a migration `2026-06-05-160000_SyncEvolvedRbacModules` cria esses modulos e replica permissoes equivalentes a partir de `clientes` e `orcamentos` para evitar quebra imediata dos grupos existentes;
- depois da migracao, a recomendacao e revisar cada grupo em `/grupos/{id}/permissoes` para refinar o acesso de CRM, WhatsApp e Precificacao separadamente.

---

## ðŸš« Bloqueios Especiais

- **Clientes**: Não suporta permissão `encerrar` (clientes têm histórico permanente)
- **Equipamentos**: a permissao `encerrar` fecha o ciclo de vida operacional do cadastro e tambem libera a acao `Reativar`; o equipamento continua no historico, mas deixa de aceitar novas OS e novos vinculos operacionais ate voltar para `ativo`
- **Equipamentos com OS abertas**: a interface exibe o aviso `OS em andamento`, desabilita o botao `Encerrar` e o backend mantem o bloqueio ate concluir ou cancelar as ordens em andamento
- **Dashboard**: Apenas permissão `visualizar` disponível
- **OS com baixa técnica concluída**: alterações em `forma_pagamento`, `valor_mao_obra`, `valor_pecas` e `desconto` exigem perfil administrativo ou autenticação de um administrador no momento do salvamento

---

## 🔧 Verificação em Código

No Controller, use sempre:
```php
// Bloqueia todo acesso ao módulo
requirePermission('crm');

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
