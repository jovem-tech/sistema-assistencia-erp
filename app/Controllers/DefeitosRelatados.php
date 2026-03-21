<?php

namespace App\Controllers;

use App\Models\DefeitoRelatadoModel;
use App\Models\LogModel;

class DefeitosRelatados extends BaseController
{
    private DefeitoRelatadoModel $model;

    public function __construct()
    {
        $this->model = new DefeitoRelatadoModel();
        requirePermission('defeitos');
    }

    public function index()
    {
        $categoria = trim((string)$this->request->getGet('categoria'));

        return view('defeitos_relatados/index', [
            'title' => 'Defeitos Relatados',
            'categoriaSelecionada' => $categoria,
            'categorias' => $this->model->getDistinctCategories(),
            'relatos' => $this->model->getAllOrdered($categoria ?: null),
        ]);
    }

    public function create()
    {
        return view('defeitos_relatados/form', [
            'title' => 'Nãovo Defeito Relatado',
            'relato' => null,
            'categoriasExistentes' => $this->model->getDistinctCategories(),
        ]);
    }

    public function store()
    {
        $data = $this->collectPayload();
        if (!$this->validate($this->model->getValidationRules())) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' | ', $this->validator->getErrors()));
        }

        $this->model->insert($data);
        LogModel::registrar('defeito_relatado_criado', 'Defeito relatado criado: ' . $data['texto_relato']);
        return redirect()->to('/defeitosrelatados')->with('success', 'Defeito relatado cadastrado com sucessão.');
    }

    public function edit(int $id)
    {
        $relato = $this->model->find($id);
        if (!$relato) {
            return redirect()->to('/defeitosrelatados')->with('error', 'Registro não encontrado.');
        }

        return view('defeitos_relatados/form', [
            'title' => 'Editar Defeito Relatado',
            'relato' => $relato,
            'categoriasExistentes' => $this->model->getDistinctCategories(),
        ]);
    }

    public function update(int $id)
    {
        $relato = $this->model->find($id);
        if (!$relato) {
            return redirect()->to('/defeitosrelatados')->with('error', 'Registro não encontrado.');
        }

        $data = $this->collectPayload();
        if (!$this->validate($this->model->getValidationRules())) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' | ', $this->validator->getErrors()));
        }

        $this->model->update($id, $data);
        LogModel::registrar('defeito_relatado_atualizado', 'Defeito relatado atualizado ID: ' . $id);
        return redirect()->to('/defeitosrelatados')->with('success', 'Defeito relatado atualizado com sucessão.');
    }

    public function toggleStatus(int $id)
    {
        $relato = $this->model->find($id);
        if (!$relato) {
            return redirect()->to('/defeitosrelatados')->with('error', 'Registro não encontrado.');
        }

        $nãovoStatus = (int)!((int)($relato['ativo'] ?? 0));
        $this->model->update($id, ['ativo' => $nãovoStatus]);
        LogModel::registrar('defeito_relatado_status', "Defeito relatado {$id} alterado para " . ($nãovoStatus ? 'ativo' : 'inativo'));
        return redirect()->to('/defeitosrelatados')->with('success', 'Status atualizado.');
    }

    public function delete(int $id)
    {
        $relato = $this->model->find($id);
        if (!$relato) {
            return redirect()->to('/defeitosrelatados')->with('error', 'Registro não encontrado.');
        }

        $this->model->delete($id);
        LogModel::registrar('defeito_relatado_excluido', 'Defeito relatado excluído ID: ' . $id);
        return redirect()->to('/defeitosrelatados')->with('success', 'Defeito relatado excluído.');
    }

    private function collectPayload(): array
    {
        $categoria = trim((string)$this->request->getPost('categoria'));
        $textoRelato = trim((string)$this->request->getPost('texto_relato'));
        $icone = trim((string)$this->request->getPost('icone'));
        $ordem = trim((string)$this->request->getPost('ordem_exibicao'));
        $ativo = $this->request->getPost('ativo');
        $observacoes = trim((string)$this->request->getPost('observacoes'));
        return [
            'categoria' => $categoria,
            'texto_relato' => $textoRelato,
            'icone' => $icone,
            'ordem_exibicao' => ($ordem === '') ? 0 : (int)$ordem,
            'ativo' => $ativo ? 1 : 0,
            'slug' => null,
            'observacoes' => $observacoes ?: null,
        ];
    }
}
