# Tabelas Principais do Banco de Dados

> Banco: `assistencia_tecnica`

---

## `clientes`

| Coluna | Tipo | Nulo | Descrição |
|--------|------|------|-----------|
| `id` | INT PK AUTO | NÃO | Identificador único |
| `tipo_pessoa` | ENUM('fisica','juridica') | NÃO | Tipo de pessoa |
| `nome_razao` | VARCHAR(100) | NÃO | Nome ou razão social |
| `cpf_cnpj` | VARCHAR(20) | SIM | CPF ou CNPJ (UNIQUE NULL) |
| `rg_ie` | VARCHAR(30) | SIM | RG ou inscrição estadual |
| `email` | VARCHAR(100) | SIM | Email (UNIQUE NULL) |
| `telefone1` | VARCHAR(20) | NÃO | Telefone principal |
| `telefone2` | VARCHAR(20) | SIM | Telefone alternativo |
| `nome_contato` | VARCHAR(100) | SIM | Nome de contato adicional |
| `telefone_contato` | VARCHAR(20) | SIM | Telefone do contato adicional |
| `cep` | VARCHAR(9) | SIM | CEP |
| `endereco` | VARCHAR(150) | SIM | Logradouro |
| `numero` | VARCHAR(10) | SIM | Número |
| `complemento` | VARCHAR(50) | SIM | Complemento |
| `bairro` | VARCHAR(80) | SIM | Bairro |
| `cidade` | VARCHAR(80) | SIM | Cidade |
| `uf` | CHAR(2) | SIM | Estado |
| `observacoes` | TEXT | SIM | Observações gerais |
| `created_at` | DATETIME | SIM | Data de criação |
| `updated_at` | DATETIME | SIM | Última atualização |

---

## `equipamentos`

| Coluna | Tipo | Nulo | Descrição |
|--------|------|------|-----------|
| `id` | INT PK AUTO | NÃO | ID |
| `cliente_id` | INT FK | SIM | Vincula ao cliente |
| `tipo_id` | INT FK | NÃO | Tipo do equipamento |
| `marca_id` | INT FK | NÃO | Marca |
| `modelo_id` | INT FK | NÃO | Modelo |
| `numero_serie` | VARCHAR(100) | SIM | Nº de série |
| `imei` | VARCHAR(20) | SIM | Identificador móvel |
| `senha_acesso` | VARCHAR(255) | SIM | PIN ou senha |
| `cor` | VARCHAR(50) | SIM | Cor em texto (Ex: Preto) |
| `cor_hex` | VARCHAR(7) | SIM | Cor em HEX (Ex: #000000) |
| `cor_rgb` | VARCHAR(30) | SIM | Cor em RGB (Ex: 0,0,0) |
| `estado_fisico` | TEXT | SIM | Descrição do estado físico |
| `acessorios` | TEXT | SIM | Acessórios acompanhando |
| `observacoes` | TEXT | SIM | Observações adicionais |
| `created_at` | DATETIME | — | Criação |
| `updated_at` | DATETIME | — | Atualização |

---

## `os` (Ordens de Serviço)

| Coluna | Tipo | Nulo | Descrição |
|--------|------|------|-----------|
| `id` | INT PK AUTO | NÃO | ID |
| `numero_os` | VARCHAR(20) | NÃO | Número único da OS (UNIQUE) |
| `cliente_id` | INT FK | NÃO | Cliente |
| `equipamento_id` | INT FK | NÃO | Equipamento |
| `tecnico_id` | INT FK | SIM | Funcionário técnico responsável |
| `status` | VARCHAR(30) | NÃO | Status atual da OS |
| `prioridade` | ENUM | NÃO | baixa/normal/alta/urgente |
| `relato_cliente` | TEXT | NÃO | Descrição do problema |
| `diagnostico_tecnico` | TEXT | SIM | Diagnóstico |
| `solucao_aplicada` | TEXT | SIM | Solução realizada |
| `acessorios` | TEXT | SIM | Acessórios e componentes recebidos |
| `forma_pagamento` | VARCHAR(30) | SIM | Forma de pagamento preferida |
| `data_abertura` | DATETIME | SIM | Data de abertura |
| `valor_mao_obra` | DECIMAL(10,2) | SIM | Valor do serviço |
| `valor_pecas` | DECIMAL(10,2) | SIM | Valor das peças |
| `valor_total` | DECIMAL(10,2) | SIM | Subtotal |
| `desconto` | DECIMAL(10,2) | SIM | Desconto |
| `valor_final` | DECIMAL(10,2) | SIM | Total |
| `orcamento_aprovado` | TINYINT(1) | SIM | Orçamento aprovado |
| `data_aprovacao` | DATETIME | SIM | Data de aprovação |
| `orcamento_pdf` | VARCHAR(255) | SIM | PDF do orçamento |
| `garantia_dias` | INT | SIM | Prazo de garantia |
| `data_entrada` | DATETIME | NÃO | Data em que o equipamento foi recebido (preenchida automaticamente ao criar a OS) |
| `data_previsao` | DATE | SIM | Previsão de entrega |
| `data_conclusao` | DATETIME | SIM | Conclusão do reparo |
| `data_entrega` | DATETIME | SIM | Entrega ao cliente |
| `observacoes_internas` | TEXT | SIM | Observações internas |
| `observacoes_cliente` | TEXT | SIM | Observações para o cliente |
| `created_at` | DATETIME | — | — |
| `updated_at` | DATETIME | — | — |

---

## `acessorios_os`

| Coluna | Tipo | Nulo | Descrição |
|--------|------|------|-----------|
| `id` | INT PK AUTO | NÃO | ID |
| `os_id` | INT FK (`os.id`) | NÃO | Ordem de serviço que recebeu o acessório |
| `descricao` | VARCHAR(255) | NÃO | Texto padronizado exibido no card (ex: "Capinha celular – preta") |
| `tipo` | VARCHAR(50) | SIM | Identificador do botão (chip, capinha, cabo, etc.) |
| `valores` | TEXT | SIM | JSON com campos complementares (cor, tipo de cabo, últimos dígitos, etc.) |
| `created_at` | DATETIME | SIM | Registro criado |
| `updated_at` | DATETIME | SIM | Última atualização |

---

## `fotos_acessorios`

| Coluna | Tipo | Nulo | Descrição |
|--------|------|------|-----------|
| `id` | INT PK AUTO | NÃO | ID |
| `acessorio_id` | INT FK (`acessorios_os.id`) | NÃO | Acessório vinculado |
| `arquivo` | VARCHAR(255) | NÃO | Nome do arquivo armazenado em `uploads/acessorios/OS_<numero>` |
| `created_at` | DATETIME | SIM | Registro criado |
| `updated_at` | DATETIME | SIM | Última atualização |

---

## `os_fotos`

| Coluna | Tipo | Nulo | Descrição |
|--------|------|------|-----------|
| `id` | INT PK AUTO | NÃO | ID |
| `os_id` | INT FK | NÃO | Ordem de serviço |
| `tipo` | ENUM | NÃO | recepcao / reparo / conclusao |
| `arquivo` | VARCHAR(255) | NÃO | Nome do arquivo |
| `created_at` | DATETIME | SIM | Data de envio |

---

## `servicos`

| Coluna | Tipo | Nulo | Descrição |
|--------|------|------|-----------|
| `id` | INT PK AUTO | NÃO | ID |
| `nome` | VARCHAR(100) | NÃO | Nome do serviço |
| `descricao` | TEXT | SIM | Descrição técnica |
| `valor` | DECIMAL(10,2) | NÃO | Valor padrão |
| `status` | VARCHAR(20) | SIM | ativo / inativo |
| `created_at` | DATETIME | SIM | Criação |
| `updated_at` | DATETIME | SIM | Atualização |

---

## `equipamentos_tipos` / `equipamentos_marcas` / `equipamentos_modelos`

```sql
-- Tipos
id | nome

-- Marcas  
id | nome

-- Modelos
id | marca_id | nome
```

---

## `usuarios`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT PK | ID |
| `nome` | VARCHAR(100) | Nome de exibição |
| `email` | VARCHAR(100) UNIQUE | Login |
| `senha` | VARCHAR(255) | Hash bcrypt |
| `perfil` | ENUM | Perfil legado (admin/tecnico/atendente) |
| `grupo_id` | INT FK | Grupo de permissões |
| `ativo` | TINYINT | 1=ativo, 0=bloqueado |

---

## `grupos` / `modulos` / `permissoes` / `grupo_permissoes`

```sql
grupos:
  id | nome | descricao

modulos:
  id | nome | slug | icone | ordem_menu | ativo

permissoes:
  id | nome | slug (visualizar/criar/editar/excluir/importar/exportar/encerrar)

grupo_permissoes:
  grupo_id | modulo_id | permissao_id
```

---

## `logs`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT PK | ID |
| `user_id` | INT | Usuário que realizou |
| `acao` | VARCHAR(100) | Tipo da ação |
| `descricao` | TEXT | Detalhes |
| `created_at` | DATETIME | Data/hora |
