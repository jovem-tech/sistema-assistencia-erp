# Padrões de Código

## PHP / CodeIgniter 4

### Controller ? Estrutura Padrão

```php
<?php
namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\LogModel;

class Clientes extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ClienteModel();
        requirePermission('clientes'); // Bloqueia acesso sem permissão
    }

    // LISTAGEM
    public function index()
    {
        $data = [
            'title'    => 'Clientes',
            'clientes' => $this->model->orderBy('nome_razao')->findAll(),
        ];
        return view('clientes/index', $data);
    }

    // FORMULÁRIO DE CRIAÇÃO
    public function create()
    {
        return view('clientes/form', ['title' => 'Novo Cliente']);
    }

    // SALVAR (POST)
    public function store()
    {
        $rules = [
            'nome_razao' => 'required|min_length[3]',
            'telefone1'  => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        $this->model->insert($dados);
        LogModel::registrar('cliente_criado', 'Cliente: ' . $dados['nome_razao']);

        return redirect()->to('/clientes')->with('success', 'Cadastrado com sucesso!');
    }
}
```

---

### Model ? Estrutura Padrão

```php
<?php
namespace App\Models;

use CodeIgniter\Model;

class ClienteModel extends Model
{
    protected $table      = 'clientes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'tipo_pessoa', 'nome_razao', 'cpf_cnpj', 'telefone1', 'telefone2',
        'email', 'cep', 'endereco', 'numero', 'cidade', 'uf',
        'nome_contato', 'telefone_contato'
    ];

    protected $useTimestamps = true;

    // Regras de validação
    protected $validationRules = [
        'nome_razao' => 'required|min_length[3]|max_length[100]',
        'telefone1'  => 'required|max_length[20]',
    ];

    // Hooks para limpar campos vazios antes de salvar
    protected $beforeInsert = ['nullifyEmptyFields'];
    protected $beforeUpdate = ['nullifyEmptyFields'];

    protected function nullifyEmptyFields(array $data)
    {
        $optional = ['cpf_cnpj', 'email', 'telefone2', ...];
        foreach ($optional as $field) {
            if (isset($data['data'][$field]) && trim($data['data'][$field]) === '') {
                $data['data'][$field] = null;
            }
        }
        return $data;
    }
}
```

---

### View ? Estrutura Padrão

```php
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <h2><i class="bi bi-people me-2"></i><?= $title ?></h2>
</div>

<div class="card glass-card">
    <div class="card-body">
        <!-- conteúdo -->
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Scripts específicos desta página
</script>
<?= $this->endSection() ?>
```

---

## Regras e Convenções

### Nomenclatura
| Tipo | Padrão | Exemplo |
|------|--------|---------|
| Controller | PascalCase | `Clientes.php` |
| Model | PascalCase + Model | `ClienteModel.php` |
| View | snake_case | `ordens_servico/form.php` |
| Variável PHP | camelCase | `$clienteAtivo` |
| Coluna DB | snake_case | `nome_razao` |
| Rota URL | kebab-case | `/os/nova` |
| Classe CSS | kebab-case | `.glass-card` |
| ID JS | camelCase | `#formNovoCliente` |

---

### Segurança

1. **Sempre usar `esc()`** em dados do banco exibidos nas Views
2. **Sempre usar `csrf_field()`** em formulários POST
3. **Nunca confiar em dados do POST** ? validar no Controller
4. **Campos únicos opcionais** devem ser convertidos para `null` quando vazios (ver `nullifyEmptyFields`)
5. **Uploads** devem ter extensão validada e ser armazenados fora da raiz pública navegável

---

### AJAX / Retorno JSON

```php
// Padrão de resposta JSON para requests AJAX
return $this->response->setJSON([
    'success' => true,
    'message' => 'Operação realizada com sucesso',
    'data'    => $resultado
]);

// Em caso de erro
return $this->response->setJSON([
    'success' => false,
    'message' => 'Mensagem de erro para o usuário'
]);
```

---

### Logs de Auditoria

Sempre registrar ações importantes:

```php
LogModel::registrar('acao_realizada', 'Descrição detalhada do que ocorreu');
```
