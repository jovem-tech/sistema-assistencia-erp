# Rotas da API Interna

> O sistema não possui uma API REST pública. As rotas AJAX internas são utilizadas pelo próprio frontend para carregar dados dinamicamente sem recarregar a página.

---

## Autenticação

Todas as rotas requerem:
1. **Sessão ativa** (cookie de sessão CI4)
2. **Permissão adequada** (verificada pelo `PermissionFilter`)

Requisições AJAX devem incluir o header:
```
X-Requested-With: XMLHttpRequest
```

---

## Rotas AJAX Principais

### Clientes

| Método | URL | Parâmetros | Retorno | Permissão |
|--------|-----|------------|---------|-----------|
| `GET` | `/clientes/buscar?q={termo}` | `q`: string de busca | Array de clientes | `clientes:visualizar` |
| `GET` | `/clientes/json/{id}` | `id`: ID do cliente | Objeto cliente | `clientes:visualizar` |
| `POST` | `/clientes/salvar_ajax` | FormData com dados | `{success, id, nome}` | `clientes:criar` |

#### Exemplo — Busca de Clientes (Select2)
```javascript
fetch(`${BASE_URL}clientes/buscar?q=maria`)
  .then(r => r.json())
  // retorna: [{id: 1, nome_razao: "Maria...", telefone1: "..."}]
```

#### Exemplo — Salvar Cliente via AJAX
```javascript
fetch(`${BASE_URL}clientes/salvar_ajax`, {
    method: 'POST',
    body: formData,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
})
.then(r => r.json())
// retorna: {"success": true, "id": 42, "nome": "Maria do Rosário"}
```

---

### Equipamentos

| Método | URL | Retorno | Permissão |
|--------|-----|---------|-----------|
| `GET` | `/equipamentos/por-cliente/{id}` | Lista de equipamentos do cliente | `equipamentos:visualizar` |
| `GET` | `/equipamentos/fotos/{id}` | Lista de fotos do equipamento | `equipamentos:visualizar` |
| `POST` | `/equipamentos/salvar-ajax` | `{success, id, nome}` | `equipamentos:criar` |
| `POST` | `/equipamentos/vincular-cliente` | `{success}` | `equipamentos:editar` |

#### Exemplo — Carregar Equipamentos de um Cliente
```javascript
fetch(`${BASE_URL}equipamentos/por-cliente/42`)
  .then(r => r.json())
// retorna: [{id, marca_nome, modelo_nome, tipo_nome, numero_serie...}]
```

---

### Marcas e Modelos

| Método | URL | Retorno | Permissão |
|--------|-----|---------|-----------|
| `POST` | `/equipamentosmarcas/salvar_ajax` | `{success, id, nome}` | `equipamentos:criar` |
| `POST` | `/equipamentosmodelos/salvar_ajax` | `{success, id, nome}` | `equipamentos:criar` |
| `POST` | `/equipamentosmodelos/por-marca` | Lista de modelos da marca | `equipamentos:visualizar` |

### Busca Inteligente de Modelos (Proxy)

| Método | URL | Retorno | Permissão |
|--------|-----|---------|-----------|
| `GET` | `/api/modelos/buscar?q={termo}&marca={nome}&marca_id={id}&tipo={tipo}` | Resultados agrupados (local + internet) | `equipamentos:visualizar` |

---

### Defeitos

| Método | URL | Retorno |
|--------|-----|---------|
| `POST` | `/equipamentosdefeitos/por-tipo` | Defeitos do tipo de equipamento |
| `GET` | `/equipamentosdefeitos/procedimentos/{id}` | Procedimentos de solução |

---

### Ordens de Serviço

| Método | URL | Retorno |
|--------|-----|---------|
| `POST` | `/os/datatable` | Dados para DataTables server-side |
| `POST` | `/os/status/{id}` | Atualiza status da OS |
| `POST` | `/os/item/salvar` | Adiciona item à OS |

---

## Formato de Erro Padrão

```json
{
  "success": false,
  "message": "Mensagem de erro amigável para o usuário"
}
```

## Formato de Sucesso Padrão

```json
{
  "success": true,
  "message": "Operação realizada com sucesso",
  "id": 123,
  "nome": "Nome do recurso criado"
}
```
