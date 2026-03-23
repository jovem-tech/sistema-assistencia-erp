<?php

namespace App\Controllers;

use App\Models\OsModel;
use App\Models\ClienteModel;
use App\Models\EquipamentoModel;
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
        $equipamentoModel = new EquipamentoModel();
        $pecaModel = new PecaModel();
        $financeiroModel = new FinanceiroModel();
        $db = \Config\Database::connect();

        $stats = $osModel->getDashboardStats();
        $statusEquipamentoEntregue = 'entregue_reparado';
        if ($db->tableExists('os_status')) {
            $statusEntregueRow = $db->table('os_status')
                ->select('codigo')
                ->where('nome', 'Equipamento Entregue')
                ->where('ativo', 1)
                ->get()
                ->getRowArray();
            if (!empty($statusEntregueRow['codigo'])) {
                $statusEquipamentoEntregue = (string) $statusEntregueRow['codigo'];
            }
        }
        $stats['equipamento_entregue'] = (int) $db->table('os')
            ->where('status', $statusEquipamentoEntregue)
            ->countAllResults();
        $anoAtual = (int) date('Y');

        $data = [
            'title'          => 'Dashboard',
            'stats'          => $stats,
            'os_recentes'    => $osModel->getRecentes(5),
            'estoque_baixo'  => $pecaModel->getEstoqueBaixo(),
            'resumo_financeiro' => $financeiroModel->getResumoMensal(),
            'total_clientes' => $clienteModel->countAll(),
            'total_equipamentos' => $equipamentoModel->countAll(),
            'total_os' => $osModel->countAll(),
            'ano_dashboard' => $anoAtual,
            'status_entregue_codigo' => $statusEquipamentoEntregue,
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
                ->select('COALESCE(os_status.nome, os.status) as status_nome')
                ->select('COALESCE(os_status.grupo_macro, "outros") as macrofase')
                ->join('os_status', 'os_status.codigo = os.status', 'left')
                ->groupBy('os_status.nome')
                ->groupBy('os_status.grupo_macro');
        } else {
            $statusBuilder
                ->select('os.status as status_nome')
                ->select('"outros" as macrofase', false);
        }

        if ($hasEstadoFluxo) {
            $statusBuilder->whereNotIn('os.estado_fluxo', ['encerrado', 'cancelado']);
        } else {
            $statusBuilder->whereNotIn('os.status', ['entregue', 'cancelado']);
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
            $macroBuilder->whereNotIn('os.estado_fluxo', ['encerrado', 'cancelado']);
        } else {
            $macroBuilder->whereNotIn('os.status', ['entregue', 'cancelado']);
        }
        $macroCount = $macroBuilder->get()->getResultArray();

        // Monthly revenue for chart (last 6 months) - mantido para compatibilidade
        $faturamento = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = date('m', strtotime("-$i months"));
            $ano = date('Y', strtotime("-$i months"));
            $label = date('M/Y', strtotime("-$i months"));

            $total = $db->table('os')
                ->selectSum('valor_final')
                ->whereIn('status', ['entregue_reparado', 'entregue_pagamento_pendente', 'entregue'])
                ->where('MONTH(data_entrega)', $mes)
                ->where('YEAR(data_entrega)', $ano)
                ->get()->getRow()->valor_final ?? 0;

            $faturamento[] = [
                'label' => $label,
                'valor' => (float)$total
            ];
        }

        $anoAtual = (int) ($this->request->getGet('ano') ?: date('Y'));
        $inicioAno = sprintf('%04d-01-01 00:00:00', $anoAtual);
        $inicioProximoAno = sprintf('%04d-01-01 00:00:00', $anoAtual + 1);
        $hasDataAbertura = $db->fieldExists('data_abertura', 'os');
        $hasCreatedAt = $db->fieldExists('created_at', 'os');
        $dateExpr = $hasDataAbertura && $hasCreatedAt
            ? 'COALESCE(data_abertura, created_at)'
            : ($hasDataAbertura ? 'data_abertura' : ($hasCreatedAt ? 'created_at' : 'NULL'));

        $rowsOsAbertas = [];
        if ($dateExpr !== 'NULL') {
            $rowsOsAbertas = $db->query(
                "SELECT MONTH($dateExpr) AS mes, COUNT(*) AS total
                 FROM os
                 WHERE $dateExpr >= ?
                   AND $dateExpr < ?
                 GROUP BY MONTH($dateExpr)",
                [$inicioAno, $inicioProximoAno]
            )->getResultArray();
        }

        $mapaOsAbertas = array_fill(1, 12, 0);
        foreach ($rowsOsAbertas as $row) {
            $mes = (int) ($row['mes'] ?? 0);
            if ($mes >= 1 && $mes <= 12) {
                $mapaOsAbertas[$mes] = (int) ($row['total'] ?? 0);
            }
        }

        $labelsMes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $osAbertasAno = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $osAbertasAno[] = [
                'mes' => $mes,
                'label' => $labelsMes[$mes - 1],
                'total' => $mapaOsAbertas[$mes],
            ];
        }

        $financeiroModel = new FinanceiroModel();
        $resumoFinanceiro = $financeiroModel->getResumoMensal();

        return $this->response->setJSON([
            'status_count' => $statusCount,
            'macro_count' => $macroCount,
            'faturamento'  => $faturamento,
            'ano_referencia' => $anoAtual,
            'os_abertas_ano' => $osAbertasAno,
            'resumo_financeiro' => [
                'receitas' => (float) ($resumoFinanceiro['receitas'] ?? 0),
                'despesas' => (float) ($resumoFinanceiro['despesas'] ?? 0),
                'lucro' => (float) ($resumoFinanceiro['lucro'] ?? 0),
                'pendentes' => (float) ($resumoFinanceiro['pendentes'] ?? 0),
            ],
        ]);
    }
}
