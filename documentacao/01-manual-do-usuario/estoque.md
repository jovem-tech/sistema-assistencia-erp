# Manual do Usuário — Estoque

## 📋 Visão Geral

Gerencia o estoque de peças e insumos utilizados nos reparos.

---

## 🧭 Navegação
**Caminho:** OPERACIONAL → Estoque de Peças

## ➕ Cadastrar Peça

**Campos:**

| Campo | Obrigatório | Descrição |
|-------|-------------|-----------|
| **Nome** | Sim | Nome da peça |
| **Código / SKU** | Não | Código interno (gerado auto se omitido) |
| Categoria | Não | Grupo da peça |
| Fornecedor | Não | Fonte da peça |
| Quantidade Inicial | Sim | Estoque de entrada |
| Estoque Mínimo | Não | Alerta de reposição |
| Preço de Custo | Não | Valor pago ao fornecedor |
| Preço de Venda | Não | Valor cobrado nas OS |

---

## 📦 Movimentações

Cada entrada ou saída de peça gera um registro de movimentação com:
- Tipo (Entrada / Saída / Ajuste)
- Quantidade
- Data e hora
- Usuário responsável

### Ver histórico de movimentações
Na listagem do estoque, clique no botão **Movimentações** da peça desejada para abrir o histórico completo.  
Essa tela mostra o saldo atual, valores de custo/venda e o log detalhado de entradas e saídas.

---

## 📥 Importação CSV

1. Baixe o modelo em **Estoque → Baixar Modelo CSV**
2. Preencha o arquivo
3. Importe em **Estoque → Importar CSV**

---

## 📤 Exportação CSV

Clique em **Exportar CSV** para baixar todo o estoque atual com quantidades e valores.
