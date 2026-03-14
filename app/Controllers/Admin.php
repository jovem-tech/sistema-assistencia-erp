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

        // OS by status for chart
        $statusCount = $db->table('os')
            ->select('status, COUNT(*) as total')
            ->whereNotIn('status', ['entregue', 'cancelado'])
            ->groupBy('status')
            ->get()->getResultArray();

        // Monthly revenue for chart (last 6 months)
        $faturamento = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = date('m', strtotime("-$i months"));
            $ano = date('Y', strtotime("-$i months"));
            $label = date('M/Y', strtotime("-$i months"));

            $total = $db->table('os')
                ->selectSum('valor_final')
                ->where('status', 'entregue')
                ->where('MONTH(data_entrega)', $mes)
                ->where('YEAR(data_entrega)', $ano)
                ->get()->getRow()->valor_final ?? 0;

            $faturamento[] = [
                'label' => $label,
                'valor' => (float)$total
            ];
        }

        return $this->response->setJSON([
            'status_count' => $statusCount,
            'faturamento'  => $faturamento,
        ]);
    }
}
