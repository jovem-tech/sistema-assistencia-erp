<?php

namespace App\Controllers;

use App\Models\FinanceiroModel;
use App\Models\OsModel;
use App\Models\PecaModel;

class Relatorios extends BaseController
{
    public function __construct()
    {
        requirePermission('relatorios');
    }

    public function index()
    {
        return view('relatorios/index', [
            'title' => 'Relatorios Gerenciais',
        ]);
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
            et.nome as equip_tipo,
            em.nome as equip_marca,
            emod.nome as equip_modelo,
            equipamentos.resumo_tecnico as equip_resumo_tecnico,
            equipamentos.desktop_modalidade as equip_desktop_modalidade'
        )
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left');

        if ($dataInicial !== '') {
            $builder->where('os.created_at >=', $dataInicial . ' 00:00:00');
        }

        if ($dataFinal !== '') {
            $builder->where('os.created_at <=', $dataFinal . ' 23:59:59');
        }

        if (! empty($status) && $status !== 'todos') {
            $builder->where('os.status', $status);
        }

        $ordens = $builder->orderBy('os.id', 'DESC')->findAll();

        $printData = [
            'ordens' => $ordens,
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal,
            'status' => $status,
        ];

        if ($this->request->getGet('print')) {
            return view('relatorios/print_os', $printData);
        }

        return view('relatorios/view_os', [
            'title' => 'Relatorio de OS por periodo',
            'ordens' => $ordens,
            'filtro_data_inicial' => $dataInicial,
            'filtro_data_final' => $dataFinal,
            'filtro_status' => $status ?? 'todos',
        ]);
    }

    public function financial()
    {
        $mes = $this->resolveMonthFilter();
        $finModel = new FinanceiroModel();
        $operacional = $finModel->getOperationalReport($mes);
        $lancamentos = $operacional['lancamentos'] ?? [];

        $fluxo = $finModel->getCashFlowReport($mes);
        $resumo = [
            'receitas' => (float) ($fluxo['entradas_realizadas'] ?? 0),
            'despesas' => (float) ($fluxo['saidas_realizadas'] ?? 0),
            'lucro' => (float) (($fluxo['entradas_realizadas'] ?? 0) - ($fluxo['saidas_realizadas'] ?? 0)),
            'resultado_caixa' => (float) (($fluxo['entradas_realizadas'] ?? 0) - ($fluxo['saidas_realizadas'] ?? 0)),
            'saldo_inicial' => (float) ($fluxo['saldo_inicial'] ?? 0),
            'saldo_final' => (float) ($fluxo['saldo_final'] ?? 0),
        ];

        $data = [
            'title' => 'Movimentacoes Financeiras',
            'lancamentos' => $lancamentos,
            'periodo_label' => $operacional['periodo_label'] ?? '',
            'resumo_por_categoria' => $operacional['resumo_por_categoria'] ?? [],
            'resumo' => $resumo,
            'filtro_mes' => $mes,
        ];

        if ($this->request->getGet('print')) {
            return view('relatorios/print_financeiro', $data);
        }

        return view('relatorios/view_financeiro', $data);
    }

    public function dre()
    {
        $mes = $this->resolveMonthFilter();
        $finModel = new FinanceiroModel();

        return view('relatorios/view_dre', [
            'title' => 'DRE Gerencial',
            'filtro_mes' => $mes,
            'dre' => $finModel->getDreReport($mes),
        ]);
    }

    public function cashFlow()
    {
        $mes = $this->resolveMonthFilter();
        $finModel = new FinanceiroModel();

        return view('relatorios/view_fluxo_caixa', [
            'title' => 'Fluxo de Caixa',
            'filtro_mes' => $mes,
            'fluxo' => $finModel->getCashFlowReport($mes),
        ]);
    }

    public function stock()
    {
        $pecaModel = new PecaModel();
        $tipo = $this->request->getGet('tipo') ?? 'todos';

        if ($tipo === 'baixo') {
            $pecas = $pecaModel->getLowStock();
        } else {
            $pecas = $pecaModel->orderBy('nome', 'ASC')->findAll();
        }

        $data = [
            'title' => 'Relatorio de Estoque',
            'pecas' => $pecas,
            'filtro_tipo' => $tipo,
        ];

        if ($this->request->getGet('print')) {
            return view('relatorios/print_estoque', $data);
        }

        return view('relatorios/view_estoque', $data);
    }

    private function resolveMonthFilter(): string
    {
        $mes = trim((string) ($this->request->getGet('mes') ?? ''));

        return preg_match('/^\d{4}-\d{2}$/', $mes) === 1 ? $mes : date('Y-m');
    }
}
