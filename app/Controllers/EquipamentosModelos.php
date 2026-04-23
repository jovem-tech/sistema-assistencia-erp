<?php

namespace App\Controllers;

use App\Models\EquipamentoMarcaModel;
use App\Models\EquipamentoModeloModel;
use App\Models\LogModel;

class EquipamentosModelos extends BaseController
{
    private const RELATION_TABLE = 'equipamentos_catalogo_relacoes';

    protected EquipamentoModeloModel $model;

    public function __construct()
    {
        $this->model = new EquipamentoModeloModel();
        requirePermission('equipamentos');
    }

    public function index()
    {
        $marcaModel = new EquipamentoMarcaModel();

        $data = [
            'title' => 'Modelos de Equipamentos',
            'modelos' => $this->model->getWithMarca(),
            'marcas' => $marcaModel->orderBy('nome', 'ASC')->findAll(),
        ];

        return view('equipamentos_modelos/index', $data);
    }

    public function store()
    {
        $rules = [
            'marca_id' => 'required|integer',
            'nome' => 'required|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'O nome ou marca sao invalidos.');
        }

        $dados = (array) $this->request->getPost();
        $tipoId = $this->readTipoId();
        $marcaId = (int) ($dados['marca_id'] ?? 0);
        $nome = trim((string) ($dados['nome'] ?? ''));

        $existing = $this->model
            ->where('marca_id', $marcaId)
            ->where('nome', $nome)
            ->first();

        if ($existing) {
            $this->syncCatalogoRelacao($tipoId, $marcaId, (int) ($existing['id'] ?? 0));
            return redirect()->back()->with('error', 'Este modelo ja existe para a marca selecionada.');
        }

        $this->model->insert([
            'marca_id' => $marcaId,
            'nome' => $nome,
            'ativo' => 1,
        ]);
        $modeloId = (int) $this->model->getInsertID();
        $this->syncCatalogoRelacao($tipoId, $marcaId, $modeloId);

        LogModel::registrar('equipamento_modelo_criado', 'Modelo adicionado: ' . $nome);

        return redirect()->to('/equipamentosmodelos')->with('success', 'Modelo adicionado com sucesso!');
    }

    public function salvar_ajax()
    {
        $rules = [
            'marca_id' => 'required|integer',
            'nome' => 'required|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Nome ou marca invalidos.',
            ]);
        }

        $dados = (array) $this->request->getPost();
        $tipoId = $this->readTipoId();
        $marcaId = (int) ($dados['marca_id'] ?? 0);
        $nome = trim((string) ($dados['nome'] ?? ''));

        $existing = $this->model
            ->where('marca_id', $marcaId)
            ->where('nome', $nome)
            ->first();

        if ($existing) {
            $existingId = (int) ($existing['id'] ?? 0);
            $this->syncCatalogoRelacao($tipoId, $marcaId, $existingId);

            return $this->response->setJSON([
                'success' => true,
                'id' => $existingId,
                'nome' => (string) ($existing['nome'] ?? $nome),
                'marca_id' => $marcaId,
                'tipo_id' => $tipoId > 0 ? $tipoId : null,
                'already_exists' => true,
            ]);
        }

        $this->model->insert([
            'marca_id' => $marcaId,
            'nome' => $nome,
            'ativo' => 1,
        ]);
        $id = (int) $this->model->getInsertID();
        $this->syncCatalogoRelacao($tipoId, $marcaId, $id);

        LogModel::registrar('equipamento_modelo_criado_ajax', 'Modelo adicionado via ajax: ' . $nome);

        return $this->response->setJSON([
            'success' => true,
            'id' => $id,
            'nome' => $nome,
            'marca_id' => $marcaId,
            'tipo_id' => $tipoId > 0 ? $tipoId : null,
        ]);
    }

    public function atualizar_ajax($id)
    {
        $modeloId = (int) $id;
        $modelo = $this->model->find($modeloId);
        if (!$modelo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Modelo nao encontrado.',
            ]);
        }

        $marcaId = (int) ($this->request->getPost('marca_id') ?? 0);
        $tipoId = $this->readTipoId();
        $nome = trim((string) $this->request->getPost('nome'));
        $nomeLength = function_exists('mb_strlen') ? mb_strlen($nome, 'UTF-8') : strlen($nome);
        if ($marcaId <= 0 || $nome === '' || $nomeLength > 100) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados invalidos para atualizar o modelo.',
            ]);
        }

        $marcaExiste = (new EquipamentoMarcaModel())->find($marcaId);
        if (!$marcaExiste) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Marca informada nao encontrada.',
            ]);
        }

        $duplicado = $this->model
            ->where('id !=', $modeloId)
            ->where('marca_id', $marcaId)
            ->where('nome', $nome)
            ->first();

        if ($duplicado) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ja existe outro modelo com esse nome para a marca.',
            ]);
        }

        $this->model->update($modeloId, [
            'marca_id' => $marcaId,
            'nome' => $nome,
        ]);
        $this->syncCatalogoRelacao($tipoId, $marcaId, $modeloId);

        LogModel::registrar(
            'equipamento_modelo_atualizado_ajax',
            'Modelo atualizado via ajax: ' . $nome . ' (ID ' . $modeloId . ')'
        );

        return $this->response->setJSON([
            'success' => true,
            'id' => $modeloId,
            'nome' => $nome,
            'marca_id' => $marcaId,
            'tipo_id' => $tipoId > 0 ? $tipoId : null,
        ]);
    }

    public function delete($id)
    {
        $modelo = $this->model->find($id);
        if ($modelo) {
            $this->model->delete($id);
            LogModel::registrar('equipamento_modelo_excluido', 'Modelo excluido ID: ' . $id);
            return redirect()->to('/equipamentosmodelos')->with('success', 'Modelo excluido com sucesso!');
        }

        return redirect()->to('/equipamentosmodelos')->with('error', 'Modelo nao encontrado.');
    }

    public function importCsv()
    {
        $file = $this->request->getFile('arquivo_csv');
        if (!$file || !$file->isValid() || $file->getExtension() !== 'csv') {
            return redirect()->to('/equipamentosmodelos')->with('error', 'Arquivo invalido. Envie um arquivo CSV.');
        }

        $filepath = $file->getTempName();
        $fileStream = fopen($filepath, 'r');
        if (!$fileStream) {
            return redirect()->to('/equipamentosmodelos')->with('error', 'Nao foi possivel ler o arquivo CSV.');
        }

        $bom = fread($fileStream, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fileStream);
        }

        $marcaModel = new EquipamentoMarcaModel();
        $importedCount = 0;

        while (($row = fgetcsv($fileStream, 1000, ';')) !== false) {
            if (empty(trim((string) ($row[0] ?? ''))) || empty(trim((string) ($row[1] ?? '')))) {
                continue;
            }

            $marcaStr = trim((string) ($row[0] ?? ''));
            $modeloStr = trim((string) ($row[1] ?? ''));

            $marca = $marcaModel->where('nome', $marcaStr)->first();
            if (!$marca) {
                $marcaModel->insert(['nome' => $marcaStr, 'ativo' => 1]);
                $marcaId = (int) $marcaModel->getInsertID();
            } else {
                $marcaId = (int) ($marca['id'] ?? 0);
            }

            $existing = $this->model
                ->where('marca_id', $marcaId)
                ->where('nome', $modeloStr)
                ->first();

            if (!$existing) {
                $this->model->insert([
                    'marca_id' => $marcaId,
                    'nome' => $modeloStr,
                    'ativo' => 1,
                ]);
                $importedCount++;
            }
        }
        fclose($fileStream);

        LogModel::registrar('modelos_importacao', "Importados {$importedCount} modelos cruzados.");

        return redirect()->to('/equipamentosmodelos')->with(
            'success',
            "Importacao concluida. {$importedCount} modelo(s) cadastrados automaticamente nas marcas."
        );
    }

    public function porMarca()
    {
        $marcaId = (int) ($this->request->getVar('marca_id') ?? 0);
        $tipoId = max(0, (int) ($this->request->getVar('tipo_id') ?? 0));

        if ($marcaId <= 0) {
            return $this->response->setJSON([]);
        }

        $builder = $this->model
            ->select('equipamentos_modelos.*')
            ->where('equipamentos_modelos.marca_id', $marcaId)
            ->where('equipamentos_modelos.ativo', 1);

        $usingRelationFilter = false;
        if ($tipoId > 0 && $this->hasCatalogoRelacaoTable()) {
            $builder
                ->join(self::RELATION_TABLE . ' rel', 'rel.modelo_id = equipamentos_modelos.id AND rel.marca_id = equipamentos_modelos.marca_id', 'inner')
                ->where('rel.tipo_id', $tipoId)
                ->where('rel.ativo', 1);
            $usingRelationFilter = true;
        }

        $modelos = $builder->orderBy('equipamentos_modelos.nome', 'ASC')->findAll();

        if ($usingRelationFilter && empty($modelos)) {
            $modelos = $this->model
                ->where('marca_id', $marcaId)
                ->where('ativo', 1)
                ->orderBy('nome', 'ASC')
                ->findAll();
        }

        return $this->response->setJSON($modelos);
    }

    private function readTipoId(): int
    {
        return max(0, (int) ($this->request->getPost('tipo_id') ?? 0));
    }

    private function hasCatalogoRelacaoTable(): bool
    {
        try {
            return \Config\Database::connect()->tableExists(self::RELATION_TABLE);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function syncCatalogoRelacao(int $tipoId, int $marcaId, int $modeloId): void
    {
        if ($tipoId <= 0 || $marcaId <= 0 || $modeloId <= 0) {
            return;
        }
        if (!$this->hasCatalogoRelacaoTable()) {
            return;
        }

        try {
            \Config\Database::connect()->query(
                'INSERT IGNORE INTO ' . self::RELATION_TABLE . ' (tipo_id, marca_id, modelo_id, ativo, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())',
                [$tipoId, $marcaId, $modeloId]
            );
        } catch (\Throwable $e) {
            log_message('warning', '[EquipamentosModelos] Falha ao sincronizar relacao de catalogo: ' . $e->getMessage());
        }
    }
}
