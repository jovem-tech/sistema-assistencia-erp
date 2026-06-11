# Manual do Administrador â€” UsuÃ¡rios e PermissÃµes

## ðŸ‘¤ UsuÃ¡rios

**Caminho:** ConfiguraÃ§Ãµes â†’ UsuÃ¡rios

### Criar Novo UsuÃ¡rio

| Campo | ObrigatÃ³rio | DescriÃ§Ã£o |
|-------|-------------|-----------|
| **Nome** | Sim | Nome de exibiÃ§Ã£o |
| **Email** | Sim | Login do usuÃ¡rio |
| **Senha** | Sim | MÃ­nimo 8 caracteres |
| NÃ­vel de Acesso | Sim | Grupo de permissÃµes |
| Ativo | Sim | Ativa ou bloqueia o usuÃ¡rio |

> âš ï¸ O email deve ser Ãºnico no sistema.

---

## ðŸ” NÃ­veis de Acesso (RBAC)

**Caminho:** ConfiguraÃ§Ãµes â†’ NÃ­veis de Acesso

O sistema usa **RBAC (Role-Based Access Control)**. Cada usuÃ¡rio pertence a um **grupo** que tem um conjunto de **permissÃµes por mÃ³dulo**.

### Grupos PadrÃ£o

| Grupo | DescriÃ§Ã£o |
|-------|-----------|
| **Administrador** | Acesso total a todos os mÃ³dulos e aÃ§Ãµes |
| **TÃ©cnico** | Acesso operacional (OS, Equipamentos, Estoque) |
| **Atendente** | Acesso a clientes, CRM, WhatsApp e abertura de OS |
| **Financeiro** | Acesso ao mÃ³dulo financeiro e relatÃ³rios |

### PermissÃµes por MÃ³dulo

Cada mÃ³dulo pode ter as seguintes permissÃµes independentes:

| PermissÃ£o | DescriÃ§Ã£o |
|-----------|-----------|
| `visualizar` | Ver listagens e detalhes |
| `criar` | Cadastrar novos registros |
| `editar` | Alterar registros existentes |
| `excluir` | Deletar registros |
| `importar` | Importar dados via CSV |
| `exportar` | Exportar dados para CSV |
| `encerrar` | Encerrar/arquivar registros |

### MÃ³dulos com Controle de Acesso

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

- **Clientes**: NÃ£o suporta permissÃ£o `encerrar` (clientes tÃªm histÃ³rico permanente)
- **Equipamentos**: a permissao `encerrar` fecha o ciclo de vida operacional do cadastro e tambem libera a acao `Reativar`; o equipamento continua no historico, mas deixa de aceitar novas OS e novos vinculos operacionais ate voltar para `ativo`
- **Equipamentos com OS abertas**: a interface exibe o aviso `OS em andamento`, desabilita o botao `Encerrar` e o backend mantem o bloqueio ate concluir ou cancelar as ordens em andamento
- **Dashboard**: Apenas permissÃ£o `visualizar` disponÃ­vel
- **OS com baixa tÃ©cnica concluÃ­da**: alteraÃ§Ãµes em `forma_pagamento`, `valor_mao_obra`, `valor_pecas` e `desconto` exigem perfil administrativo ou autenticaÃ§Ã£o de um administrador no momento do salvamento

---

## ðŸ”§ VerificaÃ§Ã£o em CÃ³digo

No Controller, use sempre:
```php
// Bloqueia todo acesso ao mÃ³dulo
requirePermission('crm');

// Verifica aÃ§Ã£o especÃ­fica (retorna true/false)
if (can('os', 'criar')) {
    // mostra botÃ£o de nova OS
}
```

Na View:
```php
<?php if (can('clientes', 'excluir')): ?>
    <a href="..." class="btn btn-danger btn-delete">Excluir</a>
<?php endif; ?>
```
