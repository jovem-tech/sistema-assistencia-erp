# Correções — Março 2026

## 📅 13/03/2026 — Campos tipo/marca/modelo não exibiam na tela do cliente

**Arquivo:** `app/Views/clientes/show.php`

### Problema
Na visualização dos detalhes do cliente, a lista de equipamentos exibia os valores em branco para Tipo, Marca e Modelo.

### Causa
O código tentava acessar `$eq['tipo']`, `$eq['marca']`, `$eq['modelo']`.
Porém, o `EquipamentoModel::getByCliente()` retorna os campos com aliases:
- `tipos.nome as tipo_nome`
- `equipamentos_marcas.nome as marca_nome`
- `equipamentos_modelos.nome as modelo_nome`

### Solução
Corrigido para usar as chaves corretas: `$eq['tipo_nome']`, `$eq['marca_nome']`, `$eq['modelo_nome']`.

---

## 📅 13/03/2026 — Erro "CPF já existe" ao cadastrar cliente sem CPF

**Arquivo:** `app/Models/ClienteModel.php`

### Problema
Modal de cadastro rápido de cliente retornava "Ocorreu um erro ao salvar (Verifique se o CPF/CNPJ já existe)" ao tentar cadastrar um cliente sem CPF, mesmo sendo o primeiro.

### Causa
A coluna `cpf_cnpj` tem constraint `UNIQUE`. Ao enviar o formulário com CPF vazio, o valor `""` (string vazia) estava sendo salvo. O segundo cliente com CPF vazio conflitava com o primeiro.

### Solução
Implementado o hook `nullifyEmptyFields` no `ClienteModel`:
```php
protected $beforeInsert = ['nullifyEmptyFields'];
protected $beforeUpdate = ['nullifyEmptyFields'];

protected function nullifyEmptyFields(array $data)
{
    // Converte strings vazias para NULL em campos opcionais únicos
    $fieldsToNullify = ['cpf_cnpj', 'email', ...];
    foreach ($fieldsToNullify as $field) {
        if (isset($data['data'][$field]) && trim($data['data'][$field]) === '') {
            $data['data'][$field] = null;
        }
    }
    return $data;
}
```

Bancos de dados permitem múltiplos `NULL` em colunas UNIQUE, resolvendo o conflito.

---

## 📅 Março 2026 — Script de busca de CEP local (duplicidade)

**Arquivo:** `app/Views/fornecedores/form.php`

### Problema
O formulário de fornecedores tinha seu próprio script local de busca de CEP, separado do script global em `scripts.js`.

### Solução
Removido o script local e adicionadas as classes utilitárias (`.js-logradouro`, `.js-bairro`, `.js-cidade`, `.js-uf`) nos campos correspondentes para que o sistema global seja usado.
