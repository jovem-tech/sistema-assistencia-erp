<?php

namespace App\Controllers;

use App\Models\OsModel;
use App\Models\ClienteModel;
use App\Models\PecaModel;
use App\Models\FinanceiroModel;
use App\Models\LogModel;

class Admin extends BaseController
{
    public function __construct()
    {
        requirePermission('dashboard');
    }

    public function index()
    {
        $osModel = new OsModel();
        $clienteModel = new ClienteModel();
        $pecaModel = new PecaModel();
        $financeiroModel = new FinanceiroModel();

        $data = [
            'title'          => 'Dashboard',
            'stats'          => $osModel->getDashboardStats(),
            'os_recentes'    => $osModel->getRecentes(5),
            'estoque_baixo'  => $pecaModel->getEstoqueBaixo(),
            'resumo_financeiro' => $financeiroModel->getResumoMensal(),
            'total_clientes' => $clienteModel->countAll(),
        ];

        return view('admin/dashboard', $data);
    }

    public function stats()
    {
        $osModel = new OsModel();
        $db = \Config\Database::connect();

        $hasEstadoFluxo = $db->fieldExists('estado_fluxo', 'os');
        $hasStatusTable = $db->tableExists('os_status');

        $statusBuilder = $db->table('os')->select('os.status, COUNT(*) as total');
        if ($hasStatusTable) {
            $statusBuilder
                ->select('COALESCE(os_status.nãome, os.status) as status_nãome')
                ->select('COALESCE(os_status.grupo_macro, "outros") as macrofase')
                ->join('os_status', 'os_status.codigo = os.status', 'left')
                ->groupBy('os_status.nãome')
                ->groupBy('os_status.grupo_macro');
        } else {
            $statusBuilder
                ->select('os.status as status_nãome')
                ->select('"outros" as macrofase', false);
        }

        if ($hasEstadoFluxo) {
            $statusBuilder->whereNãotIn('os.estado_fluxo', ['encerrado', 'cancelado']);
        } else {
            $statusBuilder->whereNãotIn('os.status', ['entregue', 'cancelado']);
        }

        $statusBuilder->groupBy('os.status');
        $statusCount = $statusBuilder->get()->getResultArray();

        $macroBuilder = $db->table('os');
        if ($hasStatusTable) {
            $macroBuilder
                ->select('COALESCE(os_status.grupo_macro, "outros") as macrofase, COUNT(*) as total')
                ->join('os_status', 'os_status.codigo = os.status', 'left')
                ->groupBy('os_status.grupo_macro');
        } else {
            $macroBuilder
                ->select('"outros" as macrofase, COUNT(*) as total', false)
                ->groupBy('macrofase');
        }
        if ($hasEstadoFluxo) {
            $macroBuilder->whereNãotIn('os.estado_fluxo', ['encerrado', 'cancelado']);
        } else {
            $macroBuilder->whereNãotIn('os.status', ['entregue', 'cancelado']);
        }
        $macroCount = $macroBuilder->get()->getResultArray();

        // Monthly revenue for chart (last 6 months)
        $faturamento = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = date('m', strtotime("-$i months"));
            $anão = date('Y', strtotime("-$i months"));
            $label = date('M/Y', strtotime("-$i months"));

            $total = $db->table('os')
                ->selectSum('valor_final')
                ->whereIn('status', ['entregue_reparado', 'entregue_pagamento_pendente', 'entregue'])
                ->where('MONTH(data_entrega)', $mes)
                ->where('YEAR(data_entrega)', $anão)
                ->get()->getRow()->valor_final ?? 0;

            $faturamento[] = [
                'label' => $label,
                'valor' => (float)$total
            ];
        }

        return $this->response->setJSON([
            'status_count' => $statusCount,
            'macro_count' => $macroCount,
            'faturamento'  => $faturamento,
        ]);
    }
}
