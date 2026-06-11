<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceiroModel extends Model
{
    protected $table = 'financeiro';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'os_id', 'tipo', 'categoria', 'descricao', 'valor',
        'forma_pagamento', 'status', 'data_vencimento', 'data_pagamento', 'observacoes',
        'fornecedor_id',
        'data_competencia', 'origem_tipo', 'origem_id',
        'grupo_dre', 'subgrupo_dre', 'impacta_dre', 'impacta_fluxo_caixa', 'dre_fixo_mensal',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $beforeInsert = ['applyGerencialDefaults'];
    protected $beforeUpdate = ['applyGerencialDefaults'];

    /**
     * @var array<string,bool>
     */
    private array $fieldExistsCache = [];

    /**
     * @var array<string,bool>
     */
    private array $tableExistsCache = [];

    /**
     * @var array<string,array<string,mixed>|null>
     */
    private array $categoriaConfigCache = [];

    private ?FinanceiroMovimentoModel $movimentoModel = null;

    /**
     * @return array<string,string>
     */
    public static function dreGroupOptions(): array
    {
        return [
            'Receita Operacional' => 'Receita Operacional',
            'Outras Receitas' => 'Outras Receitas',
            'Despesas Operacionais' => 'Despesas Operacionais',
            'Custo Direto (OS)' => 'Custo Direto (OS)',
            'Ajustes Gerenciais' => 'Ajustes Gerenciais',
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function originTypeLabels(): array
    {
        return [
            'manual' => 'Lancamento manual',
            'os' => 'Ordem de servico',
            'financeiro_os' => 'Despesa vinculada a OS',
            'estoque' => 'Compra para estoque',
            'os_item_pendencia' => 'Compra para OS',
        ];
    }

    public function getResumoMensal($mes = null, $ano = null)
    {
        $mes = (int) ($mes ?? date('m'));
        $ano = (int) ($ano ?? date('Y'));

        $db = \Config\Database::connect();

        if ($this->hasMovementSupport()) {
            $receitasBuilder = $db->table('financeiro_movimentos fm')
                ->selectSum('fm.valor_movimento', 'valor')
                ->join('financeiro', 'financeiro.id = fm.financeiro_id')
                ->where('financeiro.tipo', 'receber')
                ->where('MONTH(fm.data_movimento)', $mes)
                ->where('YEAR(fm.data_movimento)', $ano);

            $despesasBuilder = $db->table('financeiro_movimentos fm')
                ->selectSum('fm.valor_movimento', 'valor')
                ->join('financeiro', 'financeiro.id = fm.financeiro_id')
                ->where('financeiro.tipo', 'pagar')
                ->where('MONTH(fm.data_movimento)', $mes)
                ->where('YEAR(fm.data_movimento)', $ano);

            if ($this->hasField('impacta_fluxo_caixa')) {
                $receitasBuilder->where('financeiro.impacta_fluxo_caixa', 1);
                $despesasBuilder->where('financeiro.impacta_fluxo_caixa', 1);
            }

            $receitas = (float) ($receitasBuilder->get()->getRow()->valor ?? 0);
            $despesas = (float) ($despesasBuilder->get()->getRow()->valor ?? 0);
        } else {
            $receitasBuilder = $db->table('financeiro')
                ->selectSum('valor')
                ->where('tipo', 'receber')
                ->where('status', 'pago')
                ->where('MONTH(data_pagamento)', $mes)
                ->where('YEAR(data_pagamento)', $ano);

            $despesasBuilder = $db->table('financeiro')
                ->selectSum('valor')
                ->where('tipo', 'pagar')
                ->where('status', 'pago')
                ->where('MONTH(data_pagamento)', $mes)
                ->where('YEAR(data_pagamento)', $ano);

            if ($this->hasField('impacta_fluxo_caixa')) {
                $receitasBuilder->where('impacta_fluxo_caixa', 1);
                $despesasBuilder->where('impacta_fluxo_caixa', 1);
            }

            $receitas = (float) ($receitasBuilder->get()->getRow()->valor ?? 0);
            $despesas = (float) ($despesasBuilder->get()->getRow()->valor ?? 0);
        }

        $pendentesBuilder = $db->table($this->table)
            ->select('financeiro.*')
            ->where('financeiro.tipo', 'receber')
            ->where('financeiro.status !=', 'cancelado');

        if ($this->hasField('impacta_fluxo_caixa')) {
            $pendentesBuilder->where('financeiro.impacta_fluxo_caixa', 1);
        }

        $pendentesRows = $this->enrichReportRows($pendentesBuilder->get()->getResultArray());
        $pendentes = 0.0;

        foreach ($pendentesRows as $row) {
            if (in_array((string) ($row['status_resolvido'] ?? ''), ['pendente', 'parcial'], true)) {
                $pendentes += (float) ($row['valor_aberto'] ?? 0);
            }
        }

        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'lucro' => $receitas - $despesas,
            'resultado_caixa' => $receitas - $despesas,
            'pendentes' => $pendentes,
            'saldo_final' => $receitas - $despesas,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getVencidas()
    {
        $rows = $this->whereIn('status', ['pendente', 'parcial'])
            ->where('data_vencimento <', date('Y-m-d'))
            ->orderBy('data_vencimento', 'ASC')
            ->findAll();

        $rows = $this->enrichReportRows($rows);

        return array_values(array_filter($rows, static function (array $row): bool {
            return in_array((string) ($row['status_resolvido'] ?? ''), ['pendente', 'parcial'], true)
                && (float) ($row['valor_aberto'] ?? 0) > 0;
        }));
    }

    public function hasMovementSupport(): bool
    {
        return $this->hasTable('financeiro_movimentos');
    }

    public function supportsFornecedorLink(): bool
    {
        return $this->hasField('fornecedor_id') && $this->hasTable('fornecedores');
    }

    /**
     * @return array<string,mixed>
     */
    public function getMovementSummaryForTitle(int $financeiroId, ?array $titulo = null): array
    {
        $titulo = $titulo ?? $this->find($financeiroId);
        if (! is_array($titulo)) {
            return [
                'titulo_id' => $financeiroId,
                'valor_titulo' => 0.0,
                'valor_movimentado' => 0.0,
                'valor_aberto' => 0.0,
                'total_movimentos' => 0,
                'ultimo_movimento_em' => null,
                'data_pagamento_resolvida' => null,
                'formas_pagamento_resumo' => null,
                'forma_pagamento_resolvida' => null,
                'status_resolvido' => 'pendente',
                'percentual_quitado' => 0.0,
            ];
        }

        if (! $this->hasMovementSupport()) {
            return $this->buildMovementSummary($titulo, null);
        }

        $aggregate = $this->db->table('financeiro_movimentos')
            ->select('financeiro_id, COUNT(*) as total_movimentos, COALESCE(SUM(valor_movimento), 0) as valor_movimentado, MAX(data_movimento) as ultimo_movimento_em, GROUP_CONCAT(DISTINCT NULLIF(forma_pagamento, "") ORDER BY forma_pagamento SEPARATOR ", ") as formas_pagamento_resumo', false)
            ->where('financeiro_id', $financeiroId)
            ->groupBy('financeiro_id')
            ->get()
            ->getRowArray();

        return $this->buildMovementSummary($titulo, $aggregate ?: null);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listMovements(int $financeiroId): array
    {
        if ($financeiroId <= 0 || ! $this->hasMovementSupport()) {
            return [];
        }

        return $this->movementModel()
            ->where('financeiro_id', $financeiroId)
            ->orderBy('data_movimento', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function registerMovement(int $financeiroId, array $payload): array
    {
        if (! $this->hasMovementSupport()) {
            throw new \RuntimeException('A tabela de movimentos financeiros ainda nao esta disponivel.');
        }

        $titulo = $this->find($financeiroId);
        if (! is_array($titulo)) {
            throw new \RuntimeException('Titulo financeiro nao encontrado.');
        }

        if (($titulo['status'] ?? '') === 'cancelado') {
            throw new \RuntimeException('Nao e possivel registrar baixa em titulo cancelado.');
        }

        $summary = $this->getMovementSummaryForTitle($financeiroId, $titulo);
        $valorAberto = round((float) ($summary['valor_aberto'] ?? 0), 2);

        if ($valorAberto <= 0) {
            throw new \RuntimeException('Este titulo ja esta totalmente liquidado.');
        }

        $valorMovimento = round((float) ($payload['valor_movimento'] ?? $payload['valor'] ?? 0), 2);
        if ($valorMovimento <= 0) {
            throw new \RuntimeException('Informe um valor valido para a baixa.');
        }

        if ($valorMovimento > $valorAberto + 0.001) {
            throw new \RuntimeException('O valor da baixa nao pode ser maior que o saldo em aberto do titulo.');
        }

        $dataMovimento = $this->normalizeDate($payload['data_movimento'] ?? $payload['data_pagamento'] ?? null) ?? date('Y-m-d');
        $formaPagamento = trim((string) ($payload['forma_pagamento'] ?? ''));
        $observacoes = trim((string) ($payload['observacoes'] ?? $payload['observacoes_movimento'] ?? ''));
        $documentoRef = trim((string) ($payload['documento_ref'] ?? ''));
        $impactaFluxoCaixa = array_key_exists('impacta_fluxo_caixa', $payload)
            ? (int) $payload['impacta_fluxo_caixa']
            : null;

        $this->db->transStart();

        $this->movementModel()->insert([
            'financeiro_id' => $financeiroId,
            'tipo_movimento' => (($titulo['tipo'] ?? '') === 'receber') ? 'entrada' : 'saida',
            'data_movimento' => $dataMovimento,
            'valor_movimento' => $valorMovimento,
            'forma_pagamento' => $formaPagamento !== '' ? $formaPagamento : null,
            'documento_ref' => $documentoRef !== '' ? $documentoRef : null,
            'observacoes' => $observacoes !== '' ? $observacoes : null,
        ]);
        $movementId = (int) $this->movementModel()->getInsertID();

        if ($impactaFluxoCaixa !== null && $this->hasField('impacta_fluxo_caixa') && (int) ($titulo['impacta_fluxo_caixa'] ?? 1) !== $impactaFluxoCaixa) {
            $this->update($financeiroId, ['impacta_fluxo_caixa' => $impactaFluxoCaixa]);
        }

        $summary = $this->syncTitleFromMovements($financeiroId);

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Nao foi possivel registrar a baixa financeira.');
        }

        $summary['movement_id'] = $movementId;

        return $summary;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function finalizeTitleAfterSave(int $financeiroId, array $payload = []): void
    {
        if (! $this->hasMovementSupport()) {
            return;
        }

        $titulo = $this->find($financeiroId);
        if (! is_array($titulo)) {
            return;
        }

        $summary = $this->getMovementSummaryForTitle($financeiroId, $titulo);

        if (($titulo['status'] ?? '') === 'cancelado') {
            if ((int) ($summary['total_movimentos'] ?? 0) > 0) {
                throw new \RuntimeException('Nao e possivel cancelar um titulo que ja possui movimentos realizados.');
            }

            return;
        }

        if ((int) ($summary['total_movimentos'] ?? 0) > 0) {
            $this->syncTitleFromMovements($financeiroId);
            return;
        }

        if (($titulo['status'] ?? '') === 'pago') {
            $this->registerMovement($financeiroId, [
                'valor_movimento' => (float) ($titulo['valor'] ?? 0),
                'data_movimento' => $payload['data_pagamento'] ?? $titulo['data_pagamento'] ?? date('Y-m-d'),
                'forma_pagamento' => $payload['forma_pagamento'] ?? $titulo['forma_pagamento'] ?? null,
                'observacoes' => $payload['observacoes'] ?? $titulo['observacoes'] ?? null,
                'impacta_fluxo_caixa' => $payload['impacta_fluxo_caixa'] ?? $titulo['impacta_fluxo_caixa'] ?? 1,
            ]);
            return;
        }

        if (($titulo['status'] ?? '') === 'parcial') {
            $this->update($financeiroId, [
                'status' => 'pendente',
                'data_pagamento' => null,
                'forma_pagamento' => null,
            ]);
            return;
        }

        if (($titulo['status'] ?? '') === 'pendente' && (! empty($titulo['data_pagamento']) || ! empty($titulo['forma_pagamento']))) {
            $this->update($financeiroId, [
                'data_pagamento' => null,
                'forma_pagamento' => null,
            ]);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function syncTitleFromMovements(int $financeiroId): array
    {
        $titulo = $this->find($financeiroId);
        if (! is_array($titulo)) {
            throw new \RuntimeException('Titulo financeiro nao encontrado para sincronizacao.');
        }

        $summary = $this->getMovementSummaryForTitle($financeiroId, $titulo);

        $status = (string) ($summary['status_resolvido'] ?? 'pendente');
        $dataPagamento = (string) ($summary['data_pagamento_resolvida'] ?? '');
        $formaPagamento = (string) ($summary['forma_pagamento_resolvida'] ?? '');

        $this->update($financeiroId, [
            'status' => $status,
            'data_pagamento' => $status === 'cancelado' || $status === 'pendente' ? null : ($dataPagamento !== '' ? $dataPagamento : null),
            'forma_pagamento' => $status === 'cancelado' || $status === 'pendente' ? null : ($formaPagamento !== '' ? $formaPagamento : null),
        ]);

        return $summary;
    }

    /**
     * @return array<string,mixed>
     */
    public function getDreReport(string $mes): array
    {
        [$inicio, $fim, $label] = $this->resolveMonthRange($mes);
        $db = \Config\Database::connect();

        $statusEntregue = ['entregue_reparado', 'entregue_pagamento_pendente', 'entregue'];

        $receitaOs = [
            'receita_bruta' => 0.0,
            'descontos' => 0.0,
            'receita_liquida' => 0.0,
            'total_os' => 0,
        ];

        if ($db->tableExists('os') && $db->fieldExists('data_entrega', 'os')) {
            $row = $db->table('os')
                ->select('COUNT(*) as total_os, COALESCE(SUM(valor_total), 0) as receita_bruta, COALESCE(SUM(desconto), 0) as descontos, COALESCE(SUM(valor_final), 0) as receita_liquida', false)
                ->whereIn('status', $statusEntregue)
                ->where('data_entrega >=', $inicio . ' 00:00:00')
                ->where('data_entrega <=', $fim . ' 23:59:59')
                ->get()
                ->getRowArray();

            if (is_array($row)) {
                $receitaOs = [
                    'receita_bruta' => (float) ($row['receita_bruta'] ?? 0),
                    'descontos' => (float) ($row['descontos'] ?? 0),
                    'receita_liquida' => (float) ($row['receita_liquida'] ?? 0),
                    'total_os' => (int) ($row['total_os'] ?? 0),
                ];
            }
        }

        $custosDiretos = [
            'pecas' => 0.0,
            'servicos' => 0.0,
            'total' => 0.0,
        ];

        if ($db->tableExists('os_itens') && $db->tableExists('os') && $db->fieldExists('preco_custo_referencia', 'os_itens') && $db->fieldExists('data_entrega', 'os')) {
            $rows = $db->table('os_itens')
                ->select('os_itens.tipo, COALESCE(SUM(COALESCE(os_itens.preco_custo_referencia, 0) * COALESCE(os_itens.quantidade, 1)), 0) as total', false)
                ->join('os', 'os.id = os_itens.os_id')
                ->whereIn('os.status', $statusEntregue)
                ->where('os.data_entrega >=', $inicio . ' 00:00:00')
                ->where('os.data_entrega <=', $fim . ' 23:59:59')
                ->groupBy('os_itens.tipo')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $tipo = strtolower(trim((string) ($row['tipo'] ?? '')));
                $total = (float) ($row['total'] ?? 0);
                if ($tipo === 'peca') {
                    $custosDiretos['pecas'] = $total;
                } elseif ($tipo === 'servico') {
                    $custosDiretos['servicos'] = $total;
                } else {
                    $custosDiretos['total'] += $total;
                }
            }
        }

        $custosDiretos['total'] += $custosDiretos['pecas'] + $custosDiretos['servicos'];

        $outrasReceitas = $this->getGerencialBreakdown(
            $inicio,
            $fim,
            'receber',
            true,
            static fn (array $row): bool => (($row['origem_tipo'] ?? '') !== 'os') && ((int) ($row['os_id'] ?? 0) <= 0)
        );

        $despesasOperacionais = $this->getGerencialBreakdown(
            $inicio,
            $fim,
            'pagar',
            false,
            static fn (array $row): bool => ((int) ($row['impacta_dre'] ?? 1)) === 1
        );

        $outrasReceitasTotal = array_sum(array_column($outrasReceitas, 'total'));
        $despesasOperacionaisTotal = array_sum(array_column($despesasOperacionais, 'total'));

        $lucroBruto = $receitaOs['receita_liquida'] - $custosDiretos['total'];
        $resultadoLiquido = $lucroBruto + $outrasReceitasTotal - $despesasOperacionaisTotal;

        return [
            'mes' => $mes,
            'periodo_label' => $label,
            'receita' => $receitaOs,
            'custos_diretos' => $custosDiretos,
            'lucro_bruto' => $lucroBruto,
            'outras_receitas' => $outrasReceitas,
            'outras_receitas_total' => $outrasReceitasTotal,
            'despesas_operacionais' => $despesasOperacionais,
            'despesas_operacionais_total' => $despesasOperacionaisTotal,
            'resultado_liquido' => $resultadoLiquido,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getCashFlowReport(string $mes): array
    {
        [$inicio, $fim, $label] = $this->resolveMonthRange($mes);
        $db = \Config\Database::connect();
        $hasCardMovementMeta = $this->hasTable('financeiro_movimentos_cartao');
        $hasCardOperators = $this->hasTable('financeiro_cartao_operadoras');
        $hasCardFlags = $this->hasTable('financeiro_cartao_bandeiras');

        if ($this->hasMovementSupport()) {
            $saldoInicialEntradasBuilder = $db->table('financeiro_movimentos fm')
                ->selectSum('fm.valor_movimento', 'valor')
                ->join('financeiro', 'financeiro.id = fm.financeiro_id')
                ->where('financeiro.tipo', 'receber')
                ->where('fm.data_movimento <', $inicio);

            $saldoInicialSaidasBuilder = $db->table('financeiro_movimentos fm')
                ->selectSum('fm.valor_movimento', 'valor')
                ->join('financeiro', 'financeiro.id = fm.financeiro_id')
                ->where('financeiro.tipo', 'pagar')
                ->where('fm.data_movimento <', $inicio);

            $realizadosBuilder = $db->table('financeiro_movimentos fm')
                ->select(
                    'fm.id as movimento_id,
                    fm.financeiro_id as titulo_id,
                    fm.valor_movimento as valor,
                    fm.data_movimento as data_pagamento,
                    fm.forma_pagamento,
                    fm.documento_ref,
                    fm.observacoes as observacoes_movimento,
                    financeiro.os_id,
                    financeiro.tipo,
                    financeiro.categoria,
                    financeiro.descricao,
                    financeiro.status,
                    financeiro.data_vencimento,
                    financeiro.data_competencia,
                    financeiro.data_pagamento as data_pagamento_titulo,
                    financeiro.forma_pagamento as forma_pagamento_titulo,
                    financeiro.origem_tipo,
                    financeiro.origem_id,
                    financeiro.grupo_dre,
                    financeiro.subgrupo_dre,
                    financeiro.impacta_dre,
                    financeiro.impacta_fluxo_caixa,
                    financeiro.dre_fixo_mensal,
                    financeiro.valor as valor_titulo,
                    os.numero_os',
                    false
                )
                ->join('financeiro', 'financeiro.id = fm.financeiro_id')
                ->join('os', 'os.id = financeiro.os_id', 'left')
                ->where('fm.data_movimento >=', $inicio)
                ->where('fm.data_movimento <=', $fim);

            if ($hasCardMovementMeta) {
                $realizadosBuilder
                    ->select(
                        'fmc.operadora_id as cartao_operadora_id,
                        fmc.bandeira_id as cartao_bandeira_id,
                        fmc.modalidade as cartao_modalidade,
                        fmc.parcelas as cartao_parcelas,
                        fmc.valor_bruto as cartao_valor_bruto,
                        fmc.taxa_percentual as cartao_taxa_percentual,
                        fmc.taxa_fixa as cartao_taxa_fixa,
                        fmc.valor_taxa as cartao_valor_taxa,
                        fmc.valor_liquido as cartao_valor_liquido,
                        fmc.prazo_recebimento_dias as cartao_prazo_recebimento_dias,
                        fmc.data_prevista_recebimento as cartao_data_prevista_recebimento,
                        fmc.observacoes as cartao_observacoes',
                        false
                    )
                    ->join('financeiro_movimentos_cartao fmc', 'fmc.movimento_id = fm.id', 'left');

                if ($hasCardOperators) {
                    $realizadosBuilder
                        ->select('fco.nome as cartao_operadora_nome', false)
                        ->join('financeiro_cartao_operadoras fco', 'fco.id = fmc.operadora_id', 'left');
                }

                if ($hasCardFlags) {
                    $realizadosBuilder
                        ->select('fcb.nome as cartao_bandeira_nome', false)
                        ->join('financeiro_cartao_bandeiras fcb', 'fcb.id = fmc.bandeira_id', 'left');
                }
            }

            $previstosBuilder = $db->table($this->table)
                ->select('financeiro.*, os.numero_os')
                ->join('os', 'os.id = financeiro.os_id', 'left')
                ->where('financeiro.status !=', 'cancelado')
                ->where('financeiro.data_vencimento >=', $inicio)
                ->where('financeiro.data_vencimento <=', $fim);

            if ($this->hasField('impacta_fluxo_caixa')) {
                $saldoInicialEntradasBuilder->where('financeiro.impacta_fluxo_caixa', 1);
                $saldoInicialSaidasBuilder->where('financeiro.impacta_fluxo_caixa', 1);
                $realizadosBuilder->where('financeiro.impacta_fluxo_caixa', 1);
                $previstosBuilder->where('financeiro.impacta_fluxo_caixa', 1);
            }

            $saldoInicialEntradas = (float) ($saldoInicialEntradasBuilder->get()->getRow()->valor ?? 0);
            $saldoInicialSaidas = (float) ($saldoInicialSaidasBuilder->get()->getRow()->valor ?? 0);

            $realizados = $realizadosBuilder
                ->orderBy('fm.data_movimento', 'ASC')
                ->orderBy('fm.id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($realizados as &$row) {
                $row['origem_registro'] = 'movimento';
                $row['valor_relatorio'] = (float) ($row['valor'] ?? 0);
            }
            unset($row);

            $previstos = $previstosBuilder
                ->orderBy('financeiro.data_vencimento', 'ASC')
                ->orderBy('financeiro.id', 'ASC')
                ->get()
                ->getResultArray();
        } else {
            $saldoInicialEntradasBuilder = $db->table($this->table)
                ->selectSum('valor')
                ->where('tipo', 'receber')
                ->where('status', 'pago')
                ->where('data_pagamento <', $inicio);

            $saldoInicialSaidasBuilder = $db->table($this->table)
                ->selectSum('valor')
                ->where('tipo', 'pagar')
                ->where('status', 'pago')
                ->where('data_pagamento <', $inicio);

            $realizadosBuilder = $db->table($this->table)
                ->select('financeiro.*, os.numero_os')
                ->join('os', 'os.id = financeiro.os_id', 'left')
                ->where('financeiro.status', 'pago')
                ->where('financeiro.data_pagamento >=', $inicio)
                ->where('financeiro.data_pagamento <=', $fim);

            $previstosBuilder = $db->table($this->table)
                ->select('financeiro.*, os.numero_os')
                ->join('os', 'os.id = financeiro.os_id', 'left')
                ->where('financeiro.status', 'pendente')
                ->where('financeiro.data_vencimento >=', $inicio)
                ->where('financeiro.data_vencimento <=', $fim);

            if ($this->hasField('impacta_fluxo_caixa')) {
                $saldoInicialEntradasBuilder->where('impacta_fluxo_caixa', 1);
                $saldoInicialSaidasBuilder->where('impacta_fluxo_caixa', 1);
                $realizadosBuilder->where('financeiro.impacta_fluxo_caixa', 1);
                $previstosBuilder->where('financeiro.impacta_fluxo_caixa', 1);
            }

            $saldoInicialEntradas = (float) ($saldoInicialEntradasBuilder->get()->getRow()->valor ?? 0);
            $saldoInicialSaidas = (float) ($saldoInicialSaidasBuilder->get()->getRow()->valor ?? 0);

            $realizados = $realizadosBuilder
                ->orderBy('financeiro.data_pagamento', 'ASC')
                ->orderBy('financeiro.id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($realizados as &$row) {
                $row['origem_registro'] = 'titulo';
                $row['valor_relatorio'] = (float) ($row['valor'] ?? 0);
            }
            unset($row);

            $previstos = $previstosBuilder
                ->orderBy('financeiro.data_vencimento', 'ASC')
                ->orderBy('financeiro.id', 'ASC')
                ->get()
                ->getResultArray();
        }

        $saldoInicial = $saldoInicialEntradas - $saldoInicialSaidas;

        $realizados = $this->enrichReportRows($realizados);
        $previstos = $this->enrichReportRows($previstos);

        $previstos = array_values(array_filter($previstos, static function (array $row): bool {
            return ((int) ($row['impacta_fluxo_caixa_resolvido'] ?? 1)) === 1
                && in_array((string) ($row['status_resolvido'] ?? ''), ['pendente', 'parcial'], true)
                && (float) ($row['valor_aberto'] ?? 0) > 0;
        }));

        foreach ($previstos as &$row) {
            $row['origem_registro'] = 'titulo';
            $row['valor_relatorio'] = (float) ($row['valor_aberto'] ?? 0);
        }
        unset($row);

        $entradasRealizadas = 0.0;
        $saidasRealizadas = 0.0;
        foreach ($realizados as $row) {
            $valor = (float) ($row['valor_relatorio'] ?? $row['valor'] ?? 0);
            if (($row['tipo'] ?? '') === 'receber') {
                $entradasRealizadas += $valor;
            } else {
                $saidasRealizadas += $valor;
            }
        }

        $entradasPrevistas = 0.0;
        $saidasPrevistas = 0.0;
        foreach ($previstos as $row) {
            $valor = (float) ($row['valor_relatorio'] ?? $row['valor'] ?? 0);
            if (($row['tipo'] ?? '') === 'receber') {
                $entradasPrevistas += $valor;
            } else {
                $saidasPrevistas += $valor;
            }
        }

        $saldoFinal = $saldoInicial + $entradasRealizadas - $saidasRealizadas;
        $saldoProjetado = $saldoFinal + $entradasPrevistas - $saidasPrevistas;

        $linhasDiarias = $this->buildCashFlowDailyRows($inicio, $fim, $saldoInicial, $realizados, $previstos);
        $detalhesDiarios = $this->buildCashFlowDailyDetails($realizados, $previstos);

        foreach ($linhasDiarias as &$linha) {
            $dataLinha = (string) ($linha['data'] ?? '');
            $detalheLinha = $detalhesDiarios[$dataLinha] ?? null;

            $linha['movimentos_realizados_count'] = (int) ($detalheLinha['movimentos_realizados_count'] ?? 0);
            $linha['titulos_previstos_count'] = (int) ($detalheLinha['titulos_previstos_count'] ?? 0);
            $linha['total_operacoes_count'] = (int) ($detalheLinha['total_operacoes_count'] ?? 0);
            $linha['tem_detalhes'] = $detalheLinha !== null;
        }
        unset($linha);

        return [
            'mes' => $mes,
            'periodo_label' => $label,
            'saldo_inicial' => $saldoInicial,
            'entradas_realizadas' => $entradasRealizadas,
            'saidas_realizadas' => $saidasRealizadas,
            'saldo_final' => $saldoFinal,
            'entradas_previstas' => $entradasPrevistas,
            'saidas_previstas' => $saidasPrevistas,
            'saldo_projetado' => $saldoProjetado,
            'realizados' => $realizados,
            'previstos' => $previstos,
            'realizados_por_categoria' => $this->buildCategoryBreakdown($realizados, 'movimento'),
            'previstos_por_categoria' => $this->buildCategoryBreakdown($previstos, 'aberto'),
            'linhas_diarias' => $linhasDiarias,
            'detalhes_diarios' => $detalhesDiarios,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getOperationalReport(string $mes): array
    {
        [$inicio, $fim, $label] = $this->resolveMonthRange($mes);

        $lancamentos = $this->db->table($this->table)
            ->select('financeiro.*, os.numero_os')
            ->join('os', 'os.id = financeiro.os_id', 'left')
            ->where('financeiro.data_vencimento >=', $inicio)
            ->where('financeiro.data_vencimento <=', $fim)
            ->orderBy('financeiro.data_vencimento', 'ASC')
            ->orderBy('financeiro.id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($lancamentos as &$row) {
            $row['origem_registro'] = 'titulo';
            $row['valor_relatorio'] = (float) ($row['valor'] ?? 0);
        }
        unset($row);

        $lancamentos = $this->enrichReportRows($lancamentos);

        return [
            'mes' => $mes,
            'periodo_label' => $label,
            'lancamentos' => $lancamentos,
            'resumo_por_categoria' => $this->buildCategoryBreakdown($lancamentos, 'titulo'),
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    protected function applyGerencialDefaults(array $payload): array
    {
        if (! isset($payload['data']) || ! is_array($payload['data'])) {
            return $payload;
        }

        $existing = [];
        $id = $payload['id'] ?? null;
        $id = is_array($id) ? ($id[0] ?? null) : $id;
        if ($id !== null) {
            $found = $this->find($id);
            if (is_array($found)) {
                $existing = $found;
            }
        }

        $payload['data'] = $this->buildGerencialPayload($payload['data'], $existing);

        return $payload;
    }

    /**
     * @param array<string,mixed> $current
     * @param array<string,mixed> $existing
     * @return array<string,mixed>
     */
    public function buildGerencialPayload(array $current, array $existing = []): array
    {
        $merged = array_merge($existing, $current);
        $tipo = strtolower(trim((string) ($merged['tipo'] ?? '')));
        $categoria = trim((string) ($merged['categoria'] ?? ''));
        $categoriaNormalizada = $this->normalize($categoria);
        $osId = (int) ($merged['os_id'] ?? 0);
        $categoriaConfig = $this->resolveCategoriaConfigurada($categoria, $tipo);
        $origemAutomatica = $this->resolveAutomaticOrigin($merged, $categoriaNormalizada);
        $dreFixoMensal = array_key_exists('dre_fixo_mensal', $current)
            ? (int) $current['dre_fixo_mensal']
            : (array_key_exists('dre_fixo_mensal', $existing)
                ? (int) $existing['dre_fixo_mensal']
                : (int) ($categoriaConfig['dre_fixo_mensal_padrao'] ?? 0));

        $payload = $current;

        if (($payload['status'] ?? $existing['status'] ?? null) === 'pago' && empty($merged['data_pagamento'])) {
            $payload['data_pagamento'] = date('Y-m-d');
            $merged['data_pagamento'] = $payload['data_pagamento'];
        }

        $grupoDre = trim((string) ($merged['grupo_dre'] ?? ''));
        $subgrupoDre = trim((string) ($merged['subgrupo_dre'] ?? ''));

        $classificacao = [
            'origem_tipo' => $origemAutomatica['origem_tipo'] ?? '',
            'origem_id' => $origemAutomatica['origem_id'] ?? null,
            'grupo_dre' => $grupoDre !== '' ? $grupoDre : trim((string) ($categoriaConfig['dre_grupo_nome'] ?? '')),
            'subgrupo_dre' => $subgrupoDre !== '' ? $subgrupoDre : trim((string) ($categoriaConfig['dre_subgrupo_nome'] ?? '')),
            'impacta_dre' => array_key_exists('impacta_dre', $current)
                ? (int) $current['impacta_dre']
                : (array_key_exists('impacta_dre', $existing)
                    ? (int) $existing['impacta_dre']
                    : (int) ($categoriaConfig['impacta_dre_padrao'] ?? 1)),
            'impacta_fluxo_caixa' => array_key_exists('impacta_fluxo_caixa', $current)
                ? (int) $current['impacta_fluxo_caixa']
                : (array_key_exists('impacta_fluxo_caixa', $existing)
                    ? (int) $existing['impacta_fluxo_caixa']
                    : (int) ($categoriaConfig['impacta_fluxo_caixa_padrao'] ?? 1)),
        ];

        if ($tipo === 'receber' && $osId > 0) {
            $classificacao['grupo_dre'] = $classificacao['grupo_dre'] !== '' ? $classificacao['grupo_dre'] : 'Receita Operacional';
            $classificacao['subgrupo_dre'] = $classificacao['subgrupo_dre'] !== '' ? $classificacao['subgrupo_dre'] : 'Servicos e pecas de OS';
            $classificacao['impacta_dre'] = array_key_exists('impacta_dre', $merged) ? (int) $merged['impacta_dre'] : 1;
        } elseif ($tipo === 'pagar' && str_contains($categoriaNormalizada, 'compra') && str_contains($categoriaNormalizada, 'peca')) {
            $classificacao['grupo_dre'] = $classificacao['grupo_dre'] !== '' ? $classificacao['grupo_dre'] : 'Custo Direto (OS)';
            $classificacao['subgrupo_dre'] = $classificacao['subgrupo_dre'] !== '' ? $classificacao['subgrupo_dre'] : 'Compra emergencial de pecas';
            $classificacao['impacta_dre'] = array_key_exists('impacta_dre', $merged) ? (int) $merged['impacta_dre'] : 0;
        } elseif ($tipo === 'receber') {
            $classificacao['grupo_dre'] = $classificacao['grupo_dre'] !== '' ? $classificacao['grupo_dre'] : 'Outras Receitas';
            $classificacao['subgrupo_dre'] = $classificacao['subgrupo_dre'] !== '' ? $classificacao['subgrupo_dre'] : $this->humanizeCategory($categoria, 'Receita avulsa');
        } elseif ($tipo === 'pagar') {
            $classificacao['grupo_dre'] = $classificacao['grupo_dre'] !== '' ? $classificacao['grupo_dre'] : 'Despesas Operacionais';
            $classificacao['subgrupo_dre'] = $classificacao['subgrupo_dre'] !== '' ? $classificacao['subgrupo_dre'] : $this->resolveExpenseSubgroup($categoria);
        }

        $dataCompetencia = trim((string) ($merged['data_competencia'] ?? ''));
        if ($dataCompetencia === '') {
            $dataCompetencia = $this->resolveCompetenceDate($merged, $classificacao);
        }

        $payload['data_competencia'] = $dataCompetencia !== '' ? $dataCompetencia : null;
        $payload['origem_tipo'] = $classificacao['origem_tipo'] !== '' ? $classificacao['origem_tipo'] : null;
        $payload['origem_id'] = ! empty($classificacao['origem_id']) ? (int) $classificacao['origem_id'] : null;
        $payload['grupo_dre'] = $classificacao['grupo_dre'] !== '' ? $classificacao['grupo_dre'] : null;
        $payload['subgrupo_dre'] = $classificacao['subgrupo_dre'] !== '' ? $classificacao['subgrupo_dre'] : null;
        $payload['impacta_dre'] = (int) ($classificacao['impacta_dre'] ?? 1);
        $payload['impacta_fluxo_caixa'] = (int) ($classificacao['impacta_fluxo_caixa'] ?? 1);
        $payload['dre_fixo_mensal'] = $tipo === 'pagar' && $dreFixoMensal === 1 ? 1 : 0;

        if ((int) $payload['dre_fixo_mensal'] === 1) {
            $payload['impacta_dre'] = 1;
        }

        return $payload;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function enrichReportRows(array $rows): array
    {
        $titleMap = [];

        foreach ($rows as $row) {
            $titleId = (int) ($row['titulo_id'] ?? $row['financeiro_id'] ?? $row['id'] ?? 0);
            if ($titleId <= 0) {
                continue;
            }

            $titleMap[$titleId] = array_merge($titleMap[$titleId] ?? [], $row, [
                'id' => $titleId,
                'valor' => $row['valor_titulo'] ?? $row['valor'] ?? 0,
                'status' => $row['status'] ?? 'pendente',
                'data_pagamento' => $row['data_pagamento_titulo'] ?? $row['data_pagamento'] ?? null,
                'forma_pagamento' => $row['forma_pagamento_titulo'] ?? $row['forma_pagamento'] ?? null,
            ]);
        }

        $movementSummaries = $this->loadMovementSummaries(array_keys($titleMap), $titleMap);

        foreach ($rows as &$row) {
            $tipo = strtolower(trim((string) ($row['tipo'] ?? '')));
            $categoria = trim((string) ($row['categoria'] ?? ''));
            $categoriaConfig = $this->resolveCategoriaConfigurada($categoria, $tipo);
            $resolved = $this->buildGerencialPayload([], $row);
            $titleId = (int) ($row['titulo_id'] ?? $row['financeiro_id'] ?? $row['id'] ?? 0);
            $movementSummary = $movementSummaries[$titleId] ?? $this->buildMovementSummary($row, null);

            $grupoDre = trim((string) ($row['grupo_dre'] ?? ''));
            if ($grupoDre === '') {
                $grupoDre = trim((string) ($resolved['grupo_dre'] ?? ''));
            }

            $subgrupoDre = trim((string) ($row['subgrupo_dre'] ?? ''));
            if ($subgrupoDre === '') {
                $subgrupoDre = trim((string) ($resolved['subgrupo_dre'] ?? ''));
            }

            $categoriaExibicao = trim((string) ($categoriaConfig['nome'] ?? $categoria));
            if ($categoriaExibicao === '') {
                $categoriaExibicao = $tipo === 'receber' ? 'Receita avulsa' : 'Despesa operacional';
            }

            $statusResolvido = (string) ($movementSummary['status_resolvido'] ?? ($row['status'] ?? 'pendente'));
            $valorRelatorio = array_key_exists('valor_relatorio', $row)
                ? (float) $row['valor_relatorio']
                : (float) ($row['valor'] ?? 0);
            $dataVencimento = $this->normalizeDate($row['data_vencimento'] ?? null);
            $estaVencido = $dataVencimento !== null
                && $dataVencimento < date('Y-m-d')
                && in_array($statusResolvido, ['pendente', 'parcial'], true);

            $row['titulo_id'] = $titleId > 0 ? $titleId : null;
            $row['origem_registro'] = $row['origem_registro'] ?? (array_key_exists('movimento_id', $row) ? 'movimento' : 'titulo');
            $row['categoria_exibicao'] = $categoriaExibicao;
            $row['categoria_configurada'] = is_array($categoriaConfig) ? 1 : 0;
            $row['grupo_dre_resolvido'] = $grupoDre !== '' ? $grupoDre : '-';
            $row['subgrupo_dre_resolvido'] = $subgrupoDre !== '' ? $subgrupoDre : '-';
            $row['classificacao_resolvida'] = $grupoDre !== ''
                ? $grupoDre . ($subgrupoDre !== '' ? ' / ' . $subgrupoDre : '')
                : ($subgrupoDre !== '' ? $subgrupoDre : '-');
            $row['impacta_dre_resolvido'] = array_key_exists('impacta_dre', $row)
                ? (int) $row['impacta_dre']
                : (int) ($resolved['impacta_dre'] ?? 1);
            $row['impacta_fluxo_caixa_resolvido'] = array_key_exists('impacta_fluxo_caixa', $row)
                ? (int) $row['impacta_fluxo_caixa']
                : (int) ($resolved['impacta_fluxo_caixa'] ?? 1);
            $row['dre_fixo_mensal_resolvido'] = array_key_exists('dre_fixo_mensal', $row)
                ? (int) $row['dre_fixo_mensal']
                : (int) ($resolved['dre_fixo_mensal'] ?? 0);
            $row['origem_tipo_resolvido'] = trim((string) ($row['origem_tipo'] ?? ($resolved['origem_tipo'] ?? ''))) ?: '-';
            $row['origem_tipo_label_resolvido'] = $this->humanizeOriginType($row['origem_tipo_resolvido']);
            $row['data_competencia_resolvida'] = $this->normalizeDate($row['data_competencia'] ?? null)
                ?? $this->normalizeDate($row['data_vencimento'] ?? null)
                ?? $this->normalizeDate($row['data_pagamento'] ?? null);
            $row['valor_titulo'] = (float) ($movementSummary['valor_titulo'] ?? $row['valor_titulo'] ?? $row['valor'] ?? 0);
            $row['valor_movimentado'] = (float) ($movementSummary['valor_movimentado'] ?? 0);
            $row['valor_aberto'] = (float) ($movementSummary['valor_aberto'] ?? 0);
            $row['total_movimentos'] = (int) ($movementSummary['total_movimentos'] ?? 0);
            $row['ultimo_movimento_em'] = $movementSummary['ultimo_movimento_em'] ?? null;
            $row['data_pagamento_resolvida'] = $movementSummary['data_pagamento_resolvida'] ?? $this->normalizeDate($row['data_pagamento'] ?? null);
            $row['forma_pagamento_resolvida'] = $movementSummary['forma_pagamento_resolvida'] ?? null;
            $row['formas_pagamento_resumo'] = $movementSummary['formas_pagamento_resumo'] ?? null;
            $row['status_resolvido'] = $statusResolvido;
            $row['percentual_quitado'] = (float) ($movementSummary['percentual_quitado'] ?? 0);
            $row['valor_relatorio'] = $valorRelatorio;
            $row['esta_vencido'] = $estaVencido ? 1 : 0;
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<string,mixed> $merged
     * @param array<string,mixed> $classificacao
     */
    private function resolveCompetenceDate(array $merged, array $classificacao): string
    {
        if (($merged['tipo'] ?? '') === 'receber' && ((int) ($merged['os_id'] ?? 0)) > 0) {
            $dataEntrega = $this->getOsDeliveryDate((int) $merged['os_id']);
            if ($dataEntrega !== null) {
                return $dataEntrega;
            }
        }

        $candidates = [
            $merged['data_vencimento'] ?? null,
            $merged['data_pagamento'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeDate($candidate);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return date('Y-m-d');
    }

    /**
     * @param array<string,mixed> $merged
     * @return array{origem_tipo:string,origem_id:int|null}
     */
    private function resolveAutomaticOrigin(array $merged, string $categoriaNormalizada): array
    {
        $tipo = strtolower(trim((string) ($merged['tipo'] ?? '')));
        $osId = (int) ($merged['os_id'] ?? 0);

        if ($tipo === 'receber' && $osId > 0) {
            return [
                'origem_tipo' => 'os',
                'origem_id' => $osId,
            ];
        }

        if ($tipo === 'pagar' && str_contains($categoriaNormalizada, 'compra') && str_contains($categoriaNormalizada, 'peca')) {
            return [
                'origem_tipo' => $osId > 0 ? 'os_item_pendencia' : 'estoque',
                'origem_id' => $osId > 0 ? $osId : null,
            ];
        }

        if ($tipo === 'pagar' && $osId > 0) {
            return [
                'origem_tipo' => 'financeiro_os',
                'origem_id' => $osId,
            ];
        }

        return [
            'origem_tipo' => in_array($tipo, ['receber', 'pagar'], true) ? 'manual' : '',
            'origem_id' => null,
        ];
    }

    private function hasField(string $field): bool
    {
        if (! array_key_exists($field, $this->fieldExistsCache)) {
            $this->fieldExistsCache[$field] = $this->db->fieldExists($field, $this->table);
        }

        return $this->fieldExistsCache[$field];
    }

    private function hasTable(string $table): bool
    {
        if (! array_key_exists($table, $this->tableExistsCache)) {
            $this->tableExistsCache[$table] = $this->db->tableExists($table);
        }

        return $this->tableExistsCache[$table];
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function resolveMonthRange(string $mes): array
    {
        $mes = trim($mes);
        $date = \DateTimeImmutable::createFromFormat('Y-m', $mes) ?: new \DateTimeImmutable('first day of this month');

        return [
            $date->format('Y-m-01'),
            $date->format('Y-m-t'),
            $date->format('m/Y'),
        ];
    }

    private function getOsDeliveryDate(int $osId): ?string
    {
        if ($osId <= 0 || ! $this->db->tableExists('os') || ! $this->db->fieldExists('data_entrega', 'os')) {
            return null;
        }

        $row = $this->db->table('os')
            ->select('data_entrega')
            ->where('id', $osId)
            ->get()
            ->getRowArray();

        return $this->normalizeDate($row['data_entrega'] ?? null);
    }

    /**
     * @param array<int,array<string,mixed>> $realizados
     * @param array<int,array<string,mixed>> $previstos
     * @return array<int,array<string,mixed>>
     */
    private function buildCashFlowDailyRows(string $inicio, string $fim, float $saldoInicial, array $realizados, array $previstos): array
    {
        $map = [];
        $cursor = new \DateTimeImmutable($inicio);
        $fimDate = new \DateTimeImmutable($fim);

        while ($cursor <= $fimDate) {
            $key = $cursor->format('Y-m-d');
            $map[$key] = [
                'data' => $key,
                'data_label' => $cursor->format('d/m'),
                'entradas_realizadas' => 0.0,
                'saidas_realizadas' => 0.0,
                'entradas_previstas' => 0.0,
                'saidas_previstas' => 0.0,
                'saldo_do_dia' => 0.0,
                'acumulado_mes' => 0.0,
                'saldo_realizado' => 0.0,
                'saldo_projetado' => 0.0,
            ];
            $cursor = $cursor->modify('+1 day');
        }

        foreach ($realizados as $row) {
            $key = $this->normalizeDate($row['data_pagamento_resolvida'] ?? $row['data_pagamento'] ?? null);
            if ($key === null || ! isset($map[$key])) {
                continue;
            }

            $valor = (float) ($row['valor_relatorio'] ?? $row['valor'] ?? 0);
            if (($row['tipo'] ?? '') === 'receber') {
                $map[$key]['entradas_realizadas'] += $valor;
            } else {
                $map[$key]['saidas_realizadas'] += $valor;
            }
        }

        foreach ($previstos as $row) {
            $key = $this->normalizeDate($row['data_vencimento'] ?? null);
            if ($key === null || ! isset($map[$key])) {
                continue;
            }

            $valor = (float) ($row['valor_relatorio'] ?? $row['valor'] ?? 0);
            if (($row['tipo'] ?? '') === 'receber') {
                $map[$key]['entradas_previstas'] += $valor;
            } else {
                $map[$key]['saidas_previstas'] += $valor;
            }
        }

        $saldoRealizado = $saldoInicial;
        $saldoProjetado = $saldoInicial;
        $acumuladoMes = 0.0;
        foreach ($map as &$row) {
            $saldoDoDia = $row['entradas_realizadas'] - $row['saidas_realizadas'];
            $saldoRealizado += $saldoDoDia;
            $saldoProjetado += ($row['entradas_realizadas'] - $row['saidas_realizadas']) + ($row['entradas_previstas'] - $row['saidas_previstas']);
            $acumuladoMes += $saldoDoDia;
            $row['saldo_do_dia'] = $saldoDoDia;
            $row['acumulado_mes'] = $acumuladoMes;
            $row['saldo_realizado'] = $saldoRealizado;
            $row['saldo_projetado'] = $saldoProjetado;
        }
        unset($row);

        return array_values($map);
    }

    /**
     * @param array<int,array<string,mixed>> $realizados
     * @param array<int,array<string,mixed>> $previstos
     * @return array<string,array<string,mixed>>
     */
    private function buildCashFlowDailyDetails(array $realizados, array $previstos): array
    {
        $map = [];

        $ensureDay = static function (array &$days, string $key): void {
            if (! isset($days[$key])) {
                $days[$key] = [
                    'movimentos_realizados' => [],
                    'titulos_previstos' => [],
                    'movimentos_realizados_count' => 0,
                    'titulos_previstos_count' => 0,
                    'total_operacoes_count' => 0,
                ];
            }
        };

        foreach ($realizados as $row) {
            $key = $this->normalizeDate($row['data_pagamento_resolvida'] ?? $row['data_pagamento'] ?? null);
            if ($key === null) {
                continue;
            }

            $ensureDay($map, $key);
            $map[$key]['movimentos_realizados'][] = [
                'movimento_id' => (int) ($row['movimento_id'] ?? 0),
                'titulo_id' => (int) ($row['titulo_id'] ?? 0),
                'tipo' => strtolower(trim((string) ($row['tipo'] ?? ''))),
                'tipo_label' => strtolower(trim((string) ($row['tipo'] ?? ''))) === 'receber' ? 'Entrada' : 'Saída',
                'descricao' => trim((string) ($row['descricao'] ?? '')),
                'categoria' => trim((string) ($row['categoria_exibicao'] ?? $row['categoria'] ?? '')),
                'numero_os' => trim((string) ($row['numero_os'] ?? '')),
                'forma_pagamento' => trim((string) ($row['forma_pagamento'] ?? $row['formas_pagamento_resumo'] ?? '')),
                'grupo_dre' => trim((string) ($row['grupo_dre_resolvido'] ?? $row['grupo_dre'] ?? '')),
                'subgrupo_dre' => trim((string) ($row['subgrupo_dre_resolvido'] ?? $row['subgrupo_dre'] ?? '')),
                'observacoes' => trim((string) ($row['observacoes_movimento'] ?? '')),
                'cartao_operadora_nome' => trim((string) ($row['cartao_operadora_nome'] ?? '')),
                'cartao_bandeira_nome' => trim((string) ($row['cartao_bandeira_nome'] ?? '')),
                'cartao_modalidade' => trim((string) ($row['cartao_modalidade'] ?? '')),
                'cartao_parcelas' => (int) ($row['cartao_parcelas'] ?? 0),
                'cartao_valor_taxa' => round((float) ($row['cartao_valor_taxa'] ?? 0), 2),
                'cartao_valor_liquido' => round((float) ($row['cartao_valor_liquido'] ?? 0), 2),
                'valor' => round((float) ($row['valor_relatorio'] ?? $row['valor'] ?? 0), 2),
            ];
        }

        foreach ($previstos as $row) {
            $key = $this->normalizeDate($row['data_vencimento'] ?? null);
            if ($key === null) {
                continue;
            }

            $ensureDay($map, $key);
            $status = strtolower(trim((string) ($row['status_resolvido'] ?? $row['status'] ?? 'pendente')));
            $map[$key]['titulos_previstos'][] = [
                'titulo_id' => (int) ($row['titulo_id'] ?? $row['id'] ?? 0),
                'tipo' => strtolower(trim((string) ($row['tipo'] ?? ''))),
                'tipo_label' => strtolower(trim((string) ($row['tipo'] ?? ''))) === 'receber' ? 'Entrada' : 'Saída',
                'descricao' => trim((string) ($row['descricao'] ?? '')),
                'categoria' => trim((string) ($row['categoria_exibicao'] ?? $row['categoria'] ?? '')),
                'numero_os' => trim((string) ($row['numero_os'] ?? '')),
                'status' => $status,
                'status_label' => $status === 'parcial' ? 'Parcial' : 'Pendente',
                'data_vencimento' => trim((string) ($row['data_vencimento'] ?? '')),
                'grupo_dre' => trim((string) ($row['grupo_dre_resolvido'] ?? $row['grupo_dre'] ?? '')),
                'subgrupo_dre' => trim((string) ($row['subgrupo_dre_resolvido'] ?? $row['subgrupo_dre'] ?? '')),
                'valor' => round((float) ($row['valor_relatorio'] ?? $row['valor_aberto'] ?? $row['valor'] ?? 0), 2),
                'valor_titulo' => round((float) ($row['valor_titulo'] ?? $row['valor'] ?? 0), 2),
                'valor_movimentado' => round((float) ($row['valor_movimentado'] ?? 0), 2),
            ];
        }

        foreach ($map as &$day) {
            $day['movimentos_realizados_count'] = count($day['movimentos_realizados']);
            $day['titulos_previstos_count'] = count($day['titulos_previstos']);
            $day['total_operacoes_count'] = $day['movimentos_realizados_count'] + $day['titulos_previstos_count'];
        }
        unset($day);

        ksort($map);

        return $map;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildCategoryBreakdown(array $rows, string $mode = 'titulo'): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $tipo = strtolower(trim((string) ($row['tipo'] ?? '')));
            $categoria = trim((string) ($row['categoria_exibicao'] ?? $row['categoria'] ?? ''));
            if ($categoria === '') {
                $categoria = $tipo === 'receber' ? 'Receita avulsa' : 'Despesa operacional';
            }

            $grupoDre = trim((string) ($row['grupo_dre_resolvido'] ?? $row['grupo_dre'] ?? ''));
            $subgrupoDre = trim((string) ($row['subgrupo_dre_resolvido'] ?? $row['subgrupo_dre'] ?? ''));
            $key = implode('|', [$tipo, $categoria, $grupoDre, $subgrupoDre]);

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'tipo' => $tipo,
                    'tipo_label' => $tipo === 'receber' ? 'Entrada' : 'Saida',
                    'categoria' => $categoria,
                    'grupo_dre' => $grupoDre !== '' ? $grupoDre : '-',
                    'subgrupo_dre' => $subgrupoDre !== '' ? $subgrupoDre : '-',
                    'total' => 0.0,
                    'quantidade' => 0,
                    'dre_fixo_mensal' => 0,
                    'categoria_configurada' => 0,
                    'titulo_total' => 0.0,
                    'quitado_total' => 0.0,
                    'aberto_total' => 0.0,
                ];
            }

            $valorLinha = (float) ($row['valor_relatorio'] ?? $row['valor'] ?? 0);
            $valorTitulo = (float) ($row['valor_titulo'] ?? $row['valor'] ?? 0);
            $valorMovimentado = (float) ($row['valor_movimentado'] ?? 0);
            $valorAberto = (float) ($row['valor_aberto'] ?? 0);

            $grouped[$key]['total'] += $valorLinha;
            $grouped[$key]['quantidade']++;
            $grouped[$key]['dre_fixo_mensal'] = max(
                (int) $grouped[$key]['dre_fixo_mensal'],
                (int) ($row['dre_fixo_mensal_resolvido'] ?? 0)
            );
            $grouped[$key]['categoria_configurada'] = max(
                (int) $grouped[$key]['categoria_configurada'],
                (int) ($row['categoria_configurada'] ?? 0)
            );

            if ($mode === 'movimento') {
                $grouped[$key]['quitado_total'] += $valorLinha;
            } elseif ($mode === 'aberto') {
                $grouped[$key]['titulo_total'] += $valorTitulo;
                $grouped[$key]['quitado_total'] += $valorMovimentado;
                $grouped[$key]['aberto_total'] += $valorLinha;
            } else {
                $grouped[$key]['titulo_total'] += $valorTitulo;
                $grouped[$key]['quitado_total'] += $valorMovimentado;
                $grouped[$key]['aberto_total'] += $valorAberto;
            }
        }

        $result = array_values($grouped);
        usort($result, static function (array $a, array $b): int {
            if (($a['tipo'] ?? '') !== ($b['tipo'] ?? '')) {
                return ($a['tipo'] ?? '') === 'receber' ? -1 : 1;
            }

            $totalCompare = (float) ($b['total'] ?? 0) <=> (float) ($a['total'] ?? 0);
            if ($totalCompare !== 0) {
                return $totalCompare;
            }

            return strcmp((string) ($a['categoria'] ?? ''), (string) ($b['categoria'] ?? ''));
        });

        return $result;
    }

    /**
     * @param callable(array<string,mixed>):bool $filter
     * @return array<int,array{label:string,total:float}>
     */
    private function getGerencialBreakdown(string $inicio, string $fim, string $tipo, bool $receitas, callable $filter): array
    {
        $db = \Config\Database::connect();
        $hasDreFixoMensal = $this->hasField('dre_fixo_mensal');
        $campoCompetencia = $this->hasField('data_competencia')
            ? 'COALESCE(financeiro.data_competencia, financeiro.data_vencimento, financeiro.data_pagamento)'
            : 'COALESCE(financeiro.data_vencimento, financeiro.data_pagamento)';

        $select = 'financeiro.id, financeiro.os_id, financeiro.tipo, financeiro.categoria, financeiro.valor, financeiro.status, financeiro.origem_tipo, financeiro.impacta_dre, financeiro.grupo_dre, financeiro.subgrupo_dre, ' . $campoCompetencia . ' as competencia_ref';
        if ($hasDreFixoMensal) {
            $select .= ', financeiro.dre_fixo_mensal';
        }

        $rows = $db->table($this->table)
            ->select($select, false)
            ->where('financeiro.tipo', $tipo)
            ->where($campoCompetencia . ' <= ' . $db->escape($fim), null, false)
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($rows as $row) {
            $competenciaRef = $this->normalizeDate($row['competencia_ref'] ?? null);
            if ($competenciaRef === null) {
                continue;
            }

            $isDreFixoMensal = $hasDreFixoMensal
                && $tipo === 'pagar'
                && ((int) ($row['dre_fixo_mensal'] ?? 0)) === 1;

            if (! $isDreFixoMensal && ($competenciaRef < $inicio || $competenciaRef > $fim)) {
                continue;
            }

            if (! $filter($row)) {
                continue;
            }

            $label = trim((string) ($row['subgrupo_dre'] ?? ''));
            if ($label === '') {
                $label = $this->humanizeCategory((string) ($row['categoria'] ?? ''), $receitas ? 'Receita avulsa' : 'Despesa operacional');
            }

            if (! isset($grouped[$label])) {
                $grouped[$label] = 0.0;
            }

            $grouped[$label] += (float) ($row['valor'] ?? 0);
        }

        $result = [];
        foreach ($grouped as $label => $total) {
            $result[] = [
                'label' => $label,
                'total' => $total,
            ];
        }

        usort($result, static fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $result;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function resolveCategoriaConfigurada(string $categoria, string $tipo): ?array
    {
        $categoria = trim($categoria);
        $tipo = trim($tipo);
        if ($categoria === '' || $tipo === '' || ! $this->hasTable('financeiro_categorias')) {
            return null;
        }

        $cacheKey = mb_strtolower($tipo . '|' . $categoria, 'UTF-8');
        if (array_key_exists($cacheKey, $this->categoriaConfigCache)) {
            return $this->categoriaConfigCache[$cacheKey];
        }

        $categoriaLower = mb_strtolower($categoria, 'UTF-8');

        $row = $this->db->table('financeiro_categorias fc')
            ->select(
                'fc.*,
                fg.nome as dre_grupo_nome,
                fs.nome as dre_subgrupo_nome',
                false
            )
            ->join('financeiro_dre_grupos fg', 'fg.id = fc.dre_grupo_id', 'left')
            ->join('financeiro_dre_subgrupos fs', 'fs.id = fc.dre_subgrupo_id', 'left')
            ->where('fc.ativo', 1)
            ->where('LOWER(fc.nome) = ' . $this->db->escape($categoriaLower), null, false)
            ->groupStart()
                ->where('fc.tipo', $tipo)
                ->orWhere('fc.tipo', 'ambos')
            ->groupEnd()
            ->orderBy("CASE WHEN fc.tipo = " . $this->db->escape($tipo) . " THEN 0 ELSE 1 END", 'ASC', false)
            ->get()
            ->getRowArray();

        $this->categoriaConfigCache[$cacheKey] = is_array($row) ? $row : null;

        return $this->categoriaConfigCache[$cacheKey];
    }

    /**
     * @param array<int,int> $financeiroIds
     * @param array<int,array<string,mixed>> $titleMap
     * @return array<int,array<string,mixed>>
     */
    private function loadMovementSummaries(array $financeiroIds, array $titleMap = []): array
    {
        if ($financeiroIds === []) {
            return [];
        }

        $financeiroIds = array_values(array_unique(array_map('intval', $financeiroIds)));
        $summaries = [];

        if (! $this->hasMovementSupport()) {
            foreach ($financeiroIds as $financeiroId) {
                $titulo = $titleMap[$financeiroId] ?? $this->find($financeiroId);
                if (is_array($titulo)) {
                    $summaries[$financeiroId] = $this->buildMovementSummary($titulo, null);
                }
            }

            return $summaries;
        }

        $aggregates = $this->db->table('financeiro_movimentos')
            ->select('financeiro_id, COUNT(*) as total_movimentos, COALESCE(SUM(valor_movimento), 0) as valor_movimentado, MAX(data_movimento) as ultimo_movimento_em, GROUP_CONCAT(DISTINCT NULLIF(forma_pagamento, "") ORDER BY forma_pagamento SEPARATOR ", ") as formas_pagamento_resumo', false)
            ->whereIn('financeiro_id', $financeiroIds)
            ->groupBy('financeiro_id')
            ->get()
            ->getResultArray();

        $aggregateMap = [];
        foreach ($aggregates as $aggregate) {
            $aggregateMap[(int) ($aggregate['financeiro_id'] ?? 0)] = $aggregate;
        }

        if (count($titleMap) !== count($financeiroIds)) {
            $missing = array_diff($financeiroIds, array_keys($titleMap));
            if ($missing !== []) {
                $rows = $this->whereIn('id', $missing)->findAll();
                foreach ($rows as $row) {
                    $titleMap[(int) ($row['id'] ?? 0)] = $row;
                }
            }
        }

        foreach ($financeiroIds as $financeiroId) {
            $titulo = $titleMap[$financeiroId] ?? null;
            if (! is_array($titulo)) {
                continue;
            }

            $summaries[$financeiroId] = $this->buildMovementSummary($titulo, $aggregateMap[$financeiroId] ?? null);
        }

        return $summaries;
    }

    private function movementModel(): FinanceiroMovimentoModel
    {
        if ($this->movimentoModel === null) {
            $this->movimentoModel = new FinanceiroMovimentoModel();
        }

        return $this->movimentoModel;
    }

    /**
     * @param array<string,mixed>|null $aggregate
     * @return array<string,mixed>
     */
    private function buildMovementSummary(array $titulo, ?array $aggregate): array
    {
        $valorTitulo = round((float) ($titulo['valor'] ?? $titulo['valor_titulo'] ?? 0), 2);
        $valorMovimentado = $aggregate !== null
            ? round((float) ($aggregate['valor_movimentado'] ?? 0), 2)
            : round((float) ($titulo['valor_movimentado'] ?? (($titulo['status'] ?? '') === 'pago' ? $valorTitulo : 0)), 2);
        $valorAberto = max(0, round($valorTitulo - $valorMovimentado, 2));
        $statusAtual = strtolower(trim((string) ($titulo['status'] ?? 'pendente')));
        $statusResolvido = $this->resolveMovementStatus($statusAtual, $valorTitulo, $valorMovimentado);
        $ultimoMovimentoEm = $aggregate !== null
            ? $this->normalizeDate($aggregate['ultimo_movimento_em'] ?? null)
            : $this->normalizeDate($titulo['data_pagamento'] ?? null);
        $formasPagamentoResumo = trim((string) ($aggregate['formas_pagamento_resumo'] ?? $titulo['forma_pagamento'] ?? ''));
        $formaPagamentoResolvida = $formasPagamentoResumo;

        if ($aggregate !== null && ((int) ($aggregate['total_movimentos'] ?? 0)) > 1) {
            $formaPagamentoResolvida = 'multiplo';
        }

        if ($formaPagamentoResolvida === '') {
            $formaPagamentoResolvida = null;
        }

        $percentualQuitado = $valorTitulo > 0
            ? min(100, round(($valorMovimentado / $valorTitulo) * 100, 2))
            : 0.0;

        return [
            'titulo_id' => (int) ($titulo['id'] ?? $titulo['titulo_id'] ?? 0),
            'valor_titulo' => $valorTitulo,
            'valor_movimentado' => $valorMovimentado,
            'valor_aberto' => $valorAberto,
            'total_movimentos' => (int) ($aggregate['total_movimentos'] ?? $titulo['total_movimentos'] ?? ($statusResolvido === 'pago' ? 1 : 0)),
            'ultimo_movimento_em' => $ultimoMovimentoEm,
            'data_pagamento_resolvida' => $ultimoMovimentoEm,
            'formas_pagamento_resumo' => $formasPagamentoResumo !== '' ? $formasPagamentoResumo : null,
            'forma_pagamento_resolvida' => $formaPagamentoResolvida,
            'status_resolvido' => $statusResolvido,
            'percentual_quitado' => $percentualQuitado,
        ];
    }

    private function resolveMovementStatus(string $statusAtual, float $valorTitulo, float $valorMovimentado): string
    {
        if ($statusAtual === 'cancelado') {
            return 'cancelado';
        }

        if ($valorMovimentado <= 0) {
            return 'pendente';
        }

        if ($valorTitulo > 0 && $valorMovimentado + 0.001 < $valorTitulo) {
            return 'parcial';
        }

        return 'pago';
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);

        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($ascii !== false) {
                $value = strtolower($ascii);
            }
        }

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function humanizeCategory(string $categoria, string $fallback): string
    {
        $categoria = trim($categoria);
        if ($categoria === '') {
            return $fallback;
        }

        return mb_convert_case($categoria, MB_CASE_TITLE, 'UTF-8');
    }

    private function humanizeOriginType(?string $origemTipo): string
    {
        $origemTipo = trim((string) $origemTipo);
        if ($origemTipo === '' || $origemTipo === '-') {
            return '-';
        }

        $labels = self::originTypeLabels();

        return $labels[$origemTipo] ?? ucfirst(str_replace('_', ' ', $origemTipo));
    }

    private function resolveExpenseSubgroup(string $categoria): string
    {
        $normalized = $this->normalize($categoria);

        return match (true) {
            str_contains($normalized, 'aluguel') => 'Aluguel',
            str_contains($normalized, 'energia') => 'Energia',
            str_contains($normalized, 'agua') => 'Agua',
            str_contains($normalized, 'internet') => 'Internet',
            str_contains($normalized, 'telefone') => 'Telefonia',
            str_contains($normalized, 'salario'),
            str_contains($normalized, 'folha'),
            str_contains($normalized, 'pro labore') => 'Pessoal',
            str_contains($normalized, 'imposto'),
            str_contains($normalized, 'taxa'),
            str_contains($normalized, 'tarifa') => 'Taxas e impostos',
            default => $this->humanizeCategory($categoria, 'Despesa operacional'),
        };
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return substr($value, 0, 10);
    }
}
