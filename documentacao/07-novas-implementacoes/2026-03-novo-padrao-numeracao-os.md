# Implementação do Novo Padrão de Numeração de OS (OSYYMMSSSS)

**Data:** 19 de Março de 2026  
**Status:** Concluído  
**Tipo:** Melhoria de Processo / Organização  

---

## 🎯 Objetivo
Padronizar a identificação das Ordens de Serviço (OS) para um formato mais legível e organizado cronologicamente, facilitando a busca e o controle de volume mensal.

## 🏗️ O Novo Padrão
O sistema passou a utilizar o formato **`OSYYMMSSSS`**:

- **OS**: Prefixo fixo (configurável via banco de dados).
- **YY**: Últimos 2 dígitos do ano atual (Ex: 26).
- **MM**: Mês de abertura com 2 dígitos (Ex: 03).
- **SSSS**: Sequência numérica de 4 dígitos (Ex: 0001).

**Exemplo Prático:**  
A primeira OS de Março de 2026 será gerada como: **`OS26030001`**.

---

## 🛠️ Implementação Técnica

### 1. Alterações no Banco de Dados
Foi adicionada uma nova chave na tabela `configuracoes` para rastrear o mês da última OS gerada e permitir o reset automático da sequência.

```sql
INSERT INTO configuracoes (chave, valor, tipo) VALUES ('os_mes', '03', 'numero');
```

### 2. Lógica no Modelo (`OsModel.php`)
O método `generateNumeroOs()` foi refatorado para realizar as seguintes validações:

- Captura o Ano (YY) e Mês (MM) atuais.
- Compara com os valores salvos em `os_ano` e `os_mes` na tabela de configurações.
- **Se o Ano ou o Mês mudaram**: O sistema reseta a sequência (`os_ultimo_numero`) para **1**.
- **Se permanecem os mesmos**: Iclementa a sequência atual.
- O resultado é formatado com `str_pad` para garantir os 4 dígitos da sequência.

### 3. Impacto no Sistema
- As OS antigas permanecem com seus números originais (preservação histórica).
- Todas as novas OS criadas a partir de agora seguirão o novo padrão.
- Gráficos e painéis que utilizam o `numero_os` continuam funcionando normalmente, pois o tipo de dado permanece `VARCHAR`.

---

## 📄 Documentação Atualizada
- **Banco de Dados**: Atualizado em `documentacao/banco_de_dados.md` com a nova chave `os_mes`.
- **Arquitetura**: A lógica de autonumeração consolidada no `OsModel`.

---
*Documentação gerada automaticamente seguindo as diretrizes de AGENTS.md.*
