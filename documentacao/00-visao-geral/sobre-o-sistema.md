# Sobre o Sistema

## 🏢 Identificação

| Campo | Valor |
|-------|-------|
| **Nome** | Sistema de Gestão de Assistência Técnica |
| **Nome Comercial** | Jovem Tech ERP |
| **Versão** | 2.0 (Março 2026) |
| **Tipo** | ERP Web para Assistência Técnica |
| **Ambiente** | XAMPP Local / Servidor Apache |

---

## 🎯 Objetivo

Sistema completo de gestão para assistências técnicas de eletrônicos. Centraliza o controle de:

- **Clientes** e seus equipamentos
- **Ordens de Serviço** com fluxo completo
- **Estoque de Peças** com movimentações
- **Serviços** padronizados com valores
- **Financeiro** (contas a pagar/receber)
- **Relatórios** gerenciais
- **Controle de Acesso** por grupos (RBAC)

---

## 👥 Público-Alvo

| Perfil | Uso |
|--------|-----|
| **Atendente** | Clientes, abertura de OS |
| **Técnico** | Diagnóstico, orçamento, reparo |
| **Gerente** | Relatórios, financeiro |
| **Administrador** | Configurações, usuários, permissões |

---

## 📦 Principais Módulos

### Operacional
- **Ordens de Serviço (OS)** — Core do sistema. Gerencia todo ciclo de vida de um reparo.
- **Serviços** — Catálogo de serviços com preços padrão.
- **Estoque de Peças** — Controle de entrada/saída.
- **Base de Defeitos** — Biblioteca de problemas e soluções por tipo de equipamento.

### Comercial
- **Clientes** — Cadastro com endereço, contatos e histórico.
- **Equipamentos** — Vinculados a clientes, com fotos e ficha técnica.
- **Fornecedores** — Gestão de fornecedores de peças.
- **Vendas** — Em desenvolvimento.

### Financeiro & Resultados
- **Financeiro** — Lançamentos de receitas e despesas.
- **Relatórios** — OS por período, financeiro, estoque, clientes.

### Configurações
- **Dados da Empresa** — Logo, nome, informações fiscais.
- **Usuários** — Gestão de contas de acesso.
- **Níveis de Acesso** — Grupos com permissões granulares (RBAC).

---

## 🔗 Acesso ao Sistema

```
URL Local: http://localhost:8081
URL Dev:   http://localhost:8080

Login padrão (admin):
  Usuário: admin@sistema.com
  Senha:   admin123
```

> **Recomendação:** troque a senha imediatamente após o primeiro acesso.
