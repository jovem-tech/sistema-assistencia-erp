# Sobre o Sistema

## Identificacao

| Campo | Valor |
|---|---|
| **Nome** | Sistema de Gestao de Assistencia Tecnica |
| **Nome Comercial** | Jovem Tech ERP |
| **Versao** | 2.1.0 (Marco 2026) |
| **Tipo** | ERP Web para Assistencia Tecnica |
| **Ambiente** | XAMPP Local / VPS Ubuntu (Nginx + PHP-FPM) |

---

## Objetivo

Sistema completo de gestao para assistencias tecnicas de eletronicos. Centraliza:

- Clientes e seus equipamentos
- Ordens de Servico (OS) com fluxo completo
- Estoque de Pecas com movimentacoes
- Servicos padronizados com valores
- Financeiro (contas a pagar/receber)
- Relatorios gerenciais
- Controle de acesso por grupos (RBAC)

---

## Publico-alvo

| Perfil | Uso |
|---|---|
| **Atendente** | Clientes, abertura de OS |
| **Tecnico** | Diagnostico, orcamento, reparo |
| **Gerente** | Relatorios, financeiro |
| **Administrador** | Configuracoes, usuarios, permissoes |

---

## Principais modulos

### Operacional
- Ordens de Servico (OS): core do sistema, gerencia o ciclo de vida do reparo.
- Servicos: catalogo com precos padrao.
- Estoque de Pecas: controle de entrada e saida.
- Base de Defeitos: biblioteca de problemas e solucoes por tipo de equipamento.

### Comercial
- Clientes: cadastro com contato e historico.
- Equipamentos: vinculacao com clientes e ficha tecnica.
- Fornecedores: gestao de fornecedores de pecas.
- Vendas: em evolucao.

### Financeiro e Resultados
- Financeiro: lancamentos de receitas e despesas.
- Relatorios: OS por periodo, financeiro, estoque e clientes.

### Configuracoes
- Dados da Empresa: logo, nome e dados fiscais.
- Usuarios: gestao de contas de acesso.
- Niveis de Acesso: grupos com permissoes granulares.

---

## Acesso ao sistema

```text
URL Local: http://localhost:8084
URL VPS:   http://SEU_IP_OU_DOMINIO
```

Recomendacao: trocar a senha de administrador no primeiro acesso em cada ambiente.

