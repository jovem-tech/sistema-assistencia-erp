<?php

namespace App\Controllers;

use App\Models\FinanceiroModel;
use App\Models\LogModel;

class Financeiro extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new FinanceiroModel();
        requirePermission('financeiro');
    }

    public function index()
    {
        $tipo = $this->request->getGet('tipo') ?? 'todos';
        $status = $this->request->getGet('status') ?? 'todos';

        $builder = $this->model->select('financeiro.*, os.numero_os')
                               ->join('os', 'os.id = financeiro.os_id', 'left');

        if ($tipo !== 'todos') {
            $builder->where('financeiro.tipo', $tipo);
        }
        if ($status !== 'todos') {
            $builder->where('financeiro.status', $status);
        }

        $data = [
            'title'     => 'Financeiro',
            'lancamentos' => $builder->orderBy('financeiro.data_vencimento', 'DESC')->findAll(),
            'resumo'    => $this->model->getResumoMensal(),
            'filtro_tipo' => $tipo,
            'filtro_status' => $status,
        ];
        return view('financeiro/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Novo Lançamento',
        ];
        return view('financeiro/form', $data);
    }

    public function store()
    {
        $dados = $this->request->getPost();
        $this->model->insert($dados);
        LogModel::registrar('financeiro_criado', 'Lançamento criado: ' . $dados['descricao']);

        return redirect()->to('/financeiro')
            ->with('success', 'Lançamento criado com sucesso!');
    }

    public function edit($id)
    {
        $lancamento = $this->model->find($id);
        if (!$lancamento) {
            return redirect()->to('/financeiro')
                ->with('error', 'Lançamento não encontrado.');
        }

        $data = [
            'title'      => 'Editar Lançamento',
            'lancamento' => $lancamento,
        ];
        return view('financeiro/form', $data);
    }

    public function update($id)
    {
        $dados = $this->request->getPost();
        $this->model->update($id, $dados);
        LogModel::registrar('financeiro_atualizado', 'Lançamento atualizado ID: ' . $id);

        return redirect()->to('/financeiro')
            ->with('success', 'Lançamento atualizado com sucesso!');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        LogModel::registrar('financeiro_excluido', 'Lançamento excluído ID: ' . $id);

        return redirect()->to('/financeiro')
            ->with('success', 'Lançamento excluído com sucesso!');
    }

    public function pay($id)
    {
        $dados = $this->request->getPost();
        $this->model->update($id, [
            'status'          => 'pago',
            'data_pagamento'  => $dados['data_pagamento'] ?? date('Y-m-d'),
            'forma_pagamento' => $dados['forma_pagamento'] ?? null,
        ]);

        LogModel::registrar('financeiro_baixa', 'Baixa no lançamento ID: ' . $id);

        return redirect()->to('/financeiro')
            ->with('success', 'Pagamento registrado com sucesso!');
    }
}
