# Módulo: Clientes

## Finalidade

Gerenciar o cadastro de clientes (pessoas físicas e jurídicas), seus dados de contato, endereço e contatos alternativos.

## Tabelas Utilizadas

| Tabela | Papel |
|--------|-------|
| `clientes` | Principal — armazena todos os dados do cliente |

## Relacionamentos

```
clientes (1) ──► (N) equipamentos
clientes (1) ──► (N) os
```

## Controller

`app/Controllers/Clientes.php`

| Método | Rota | Descrição |
|--------|------|-----------|
| `index()` | GET /clientes | Listagem |
| `create()` | GET /clientes/novo | Formulário novo |
| `store()` | POST /clientes/salvar | Salva novo |
| `edit($id)` | GET /clientes/editar/{id} | Form edição |
| `update($id)` | POST /clientes/atualizar/{id} | Atualiza |
| `delete($id)` | GET /clientes/excluir/{id} | Exclui |
| `show($id)` | GET /clientes/visualizar/{id} | Detalhes |
| `search()` | GET /clientes/buscar | Busca AJAX |
| `salvar_ajax()` | POST /clientes/salvar_ajax | Cadastro rápido |
| `importCsv()` | POST /clientes/importar | Importação CSV |

## Permissões Requeridas

`visualizar`, `criar`, `editar`, `excluir`, `importar`

## Fluxo de Operação Normal

```
1. Atendente abre o formulário de Nova OS
2. Busca cliente pelo nome/telefone (Select2 AJAX)
3. Se não encontrar → clica "+ Novo" → modal de cadastro rápido
4. Preenche nome e telefone (obrigatórios)
5. Sistema salva e já seleciona o cliente na OS
```

## Regras de Negócio

- Apenas **Nome** e **Telefone 1** são obrigatórios
- `cpf_cnpj` e `email` são únicos mas podem ser `NULL` (múltiplos clientes sem CPF)
- Campos vazios de CPF, email, etc. são convertidos para `NULL` pelo hook `nullifyEmptyFields`
- Clientes **não têm** permissão `encerrar` — registros são permanentes por integridade histórica
