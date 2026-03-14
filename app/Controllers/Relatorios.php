<?php

namespace App\Controllers;

use App\Models\OsModel;
use App\Models\FinanceiroModel;
use App\Models\PecaModel;
use App\Models\ClienteModel;

class Relatorios extends BaseController
{
    public function __construct()
    {
        requirePermission('relatorios');
    }

    public function index()
    {
        $data = [
            'title' => 'Relatórios Gerenciais'
        ];
        return view('relatorios/index', $data);
    }

    public function osByPeriod()
    {
        $dataInicial = $this->request->getGet('data_inicial') ?? date('Y-m-01');
        $dataFinal = $this->request->getGet('data_final') ?? date('Y-m-t');
        $status = $this->request->getGet('status');

        $osModel = new OsModel();
        
        $builder = $osModel->select(
                'os.*,
                clientes.nome_razao as cliente_nome,
                em.nome as equip_marca,
                emod.nome as equip_modelo'
            )
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left');

        if (!empty($dataInicial)) {
            $builder->where('os.created_at >=', $dataInicial . ' 00:00:00');
        }
        if (!empty($dataFinal)) {
            $builder->where('os.created_at <=', $dataFinal . ' 23:59:59');
        }
        if (!empty($status) && $status !== 'todos') {
            $builder->where('os.status', $status);
        }

        $ordens = $builder->orderBy('os.id', 'DESC')->findAll();

        $html = view('relatorios/print_os', [
            'ordens' => $ordens,
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal,
            'status' => $status
        ]);
        
        if ($this->request->getGet('print')) {
            return $html;
        }

        $data = [
            'title' => 'Relatório de OS por Período',
            'ordens' => $ordens,
            'filtro_data_inicial' => $dataInicial,
            'filtro_data_final' => $dataFinal,
            'filtro_status' => $status ?? 'todos'
        ];

        return view('relatorios/view_os', $data);
    }

    public function financial()
    {
        $mes = $this->request->getGet('mes') ?? date('Y-m');
        
        $finModel = new FinanceiroModel();
        $builder = $finModel;
        
        if (!empty($mes)) {
            $builder->like('data_vencimento', $mes, 'after');
        }

        $lancamentos = $builder->orderBy('data_vencimento', 'ASC')->findAll();

        $resumo = [
            'receitas' => 0,
            'despesas' => 0,
            'lucro' => 0
        ];

        foreach($lancamentos as $l) {
            if ($l['status'] === 'pago') {
                if ($l['tipo'] === 'receber') {
                    $resumo['receitas'] += $l['valor'];
                } else {
                    $resumo['despesas'] += $l['valor'];
                }
            }
        }
        $resumo['lucro'] = $resumo['receitas'] - $resumo['despesas'];

        $data = [
            'title' => 'Relatório Financeiro',
            'lancamentos' => $lancamentos,
            'resumo' => $resumo,
            'filtro_mes' => $mes
        ];

        if ($this->request->getGet('print')) {
            return view('relatorios/print_financeiro', $data);
        }

        return view('relatorios/view_financeiro', $data);
    }

    public function stock()
    {
        $pecaModel = new PecaModel();
        $tipo = $this->request->getGet('tipo') ?? 'todos';

        if ($tipo === 'baixo') {
            // Get low stock items array
            $pecas = $pecaModel->getLowStock();
        } else {
            $pecas = $pecaModel->orderBy('nome', 'ASC')->findAll();
        }

        $data = [
            'title' => 'Relatório de Estoque',
            'pecas' => $pecas,
            'filtro_tipo' => $tipo
        ];

        if ($this->request->getGet('print')) {
            return view('relatorios/print_estoque', $data);
        }

        return view('relatorios/view_estoque', $data);
    }
}
