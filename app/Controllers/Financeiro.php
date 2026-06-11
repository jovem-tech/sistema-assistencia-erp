<?php

namespace App\Controllers;

use App\Models\FinanceiroCategoriaModel;
use App\Models\FinanceiroDreGrupoModel;
use App\Models\FinanceiroDreSubgrupoModel;
use App\Models\FinanceiroModel;
use App\Models\FornecedorModel;
use App\Models\LogModel;
use App\Services\OsSettlementService;

class Financeiro extends BaseController
{
    protected FinanceiroModel $model;

    public function __construct()
    {
        $this->model = new FinanceiroModel();
        requirePermission('financeiro');
    }

    public function index()
    {
        $tipo = $this->request->getGet('tipo') ?? 'todos';
        $status = $this->request->getGet('status') ?? 'todos';
        $dreFixoMensal = $this->request->getGet('dre_fixo_mensal');
        $filtroDreFixoMensal = ($dreFixoMensal === '1') ? '1' : 'todos';

        $builder = $this->buildListingQuery();

        if ($tipo !== 'todos') {
            $builder->where('financeiro.tipo', $tipo);
        }

        if ($status !== 'todos') {
            $builder->where('financeiro.status', $status);
        }

        if ($filtroDreFixoMensal === '1') {
            $builder
                ->where('financeiro.tipo', 'pagar')
                ->where('financeiro.dre_fixo_mensal', 1);
        }

        $lancamentos = $builder->orderBy('financeiro.data_vencimento', 'DESC')->findAll();
        $lancamentos = $this->model->enrichReportRows($lancamentos);

        return view('financeiro/index', [
            'title' => 'Financeiro',
            'lancamentos' => $lancamentos,
            'resumo' => $this->model->getResumoMensal(),
            'filtro_tipo' => $tipo,
            'filtro_status' => $status,
            'filtro_dre_fixo_mensal' => $filtroDreFixoMensal,
        ]);
    }

    public function details($id)
    {
        try {
            $lancamento = $this->buildListingQuery()
                ->where('financeiro.id', (int) $id)
                ->first();

            if (! is_array($lancamento)) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Lancamento nao encontrado.',
                    ]);
            }

            $lancamento = $this->model->enrichReportRows([$lancamento])[0] ?? $lancamento;
            $osDetalhes = null;
            $osItens = [];
            $osDefeitos = [];
            $movimentos = $this->model->listMovements((int) $lancamento['id']);

            if ((int) ($lancamento['os_id'] ?? 0) > 0) {
                $osDetalhes = $this->fetchOsDetails((int) $lancamento['os_id']);
                if ($osDetalhes !== null) {
                    $osItens = $this->fetchOsItems((int) $lancamento['os_id']);
                    $osDefeitos = $this->fetchOsDefeitos((int) $lancamento['os_id']);
                }
            }

            $title = (($lancamento['tipo'] ?? '') === 'receber' ? 'Receita' : 'Despesa') . ' #' . (int) $lancamento['id'];
            $html = view('financeiro/partials/detail_modal_content', [
                'lancamento' => $lancamento,
                'osDetalhes' => $osDetalhes,
                'osItens' => $osItens,
                'osDefeitos' => $osDefeitos,
                'movimentos' => $movimentos,
            ]);

            return $this->response->setJSON([
                'success' => true,
                'title' => $title,
                'html' => $html,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Financeiro::details] Falha ao montar detalhamento #{id}: {message}', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Nao foi possivel carregar os detalhes deste lancamento agora.',
                ]);
        }
    }

    public function create()
    {
        return view('financeiro/form', $this->buildFormViewData('Novo lancamento'));
    }

    public function store()
    {
        $dados = $this->sanitizePayload($this->request->getPost());

        try {
            $this->model->insert($dados);
            $financeiroId = (int) $this->model->getInsertID();
            $this->model->finalizeTitleAfterSave($financeiroId, $dados);

            LogModel::registrar('financeiro_criado', 'Lancamento criado: ' . ($dados['descricao'] ?? 'sem descricao'));

            return redirect()->to('/financeiro')
                ->with('success', 'Lancamento criado com sucesso!');
        } catch (\Throwable $e) {
            log_message('error', '[Financeiro::store] Falha ao criar lancamento: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage() !== '' ? $e->getMessage() : 'Nao foi possivel criar o lancamento.');
        }
    }

    public function edit($id)
    {
        $lancamento = $this->model->find($id);
        if (! $lancamento) {
            return redirect()->to('/financeiro')
                ->with('error', 'Lancamento nao encontrado.');
        }

        return view('financeiro/form', $this->buildFormViewData('Editar lancamento', $lancamento));
    }

    public function update($id)
    {
        $dados = $this->sanitizePayload($this->request->getPost());

        try {
            $lancamentoAtual = $this->model->find($id);
            if (! is_array($lancamentoAtual)) {
                return redirect()->to('/financeiro')
                    ->with('error', 'Lancamento nao encontrado.');
            }

            $this->guardTitleMutationAgainstMovements($lancamentoAtual, $dados);

            $this->model->update($id, $dados);
            $this->model->finalizeTitleAfterSave((int) $id, $dados);

            LogModel::registrar('financeiro_atualizado', 'Lancamento atualizado ID: ' . $id);

            return redirect()->to('/financeiro')
                ->with('success', 'Lancamento atualizado com sucesso!');
        } catch (\Throwable $e) {
            log_message('error', '[Financeiro::update] Falha ao atualizar lancamento #{id}: {message}', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage() !== '' ? $e->getMessage() : 'Nao foi possivel atualizar o lancamento.');
        }
    }

    public function delete($id)
    {
        $this->model->delete($id);
        LogModel::registrar('financeiro_excluido', 'Lancamento excluido ID: ' . $id);

        return redirect()->to('/financeiro')
            ->with('success', 'Lancamento excluido com sucesso!');
    }

    public function pay($id)
    {
        $dados = $this->sanitizeMovementPayload($this->request->getPost());

        try {
            $summary = $this->model->registerMovement((int) $id, $dados);

            LogModel::registrar('financeiro_baixa', 'Baixa no lancamento ID: ' . $id);

            $mensagem = ((string) ($summary['status_resolvido'] ?? '')) === 'pago'
                ? 'Baixa registrada e titulo liquidado com sucesso!'
                : 'Baixa parcial registrada com sucesso!';

            try {
                (new OsSettlementService())->syncClosureStatusFromFinanceiro((int) $id, session()->get('user_id') ?: null);
            } catch (\Throwable $e) {
                log_message('warning', '[Financeiro::pay] Baixa registrada, mas a sincronizacao da OS pendente falhou #{id}: {message}', [
                    'id' => $id,
                    'message' => $e->getMessage(),
                ]);
            }

            return redirect()->to('/financeiro')
                ->with('success', $mensagem);
        } catch (\Throwable $e) {
            log_message('error', '[Financeiro::pay] Falha ao registrar baixa #{id}: {message}', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage() !== '' ? $e->getMessage() : 'Nao foi possivel registrar a baixa.');
        }
    }

    /**
     * @param array<string,mixed>|null $lancamento
     * @return array<string,mixed>
     */
    private function buildFormViewData(string $title, ?array $lancamento = null): array
    {
        $catalogos = $this->loadCatalogosFinanceiros();
        $movementSummary = null;
        $movements = [];

        if (is_array($lancamento) && ! empty($lancamento['id'])) {
            $movementSummary = $this->model->getMovementSummaryForTitle((int) $lancamento['id'], $lancamento);
            $movements = $this->model->listMovements((int) $lancamento['id']);
            $lancamento = $this->model->enrichReportRows([$lancamento])[0] ?? $lancamento;
        }

        return [
            'title' => $title,
            'lancamento' => $lancamento,
            'dre_groups' => $catalogos['dre_groups'],
            'dre_subgroups' => $catalogos['dre_subgroups'],
            'financeiro_categories' => $catalogos['categories'],
            'origin_type_labels' => FinanceiroModel::originTypeLabels(),
            'fornecedores' => $this->loadFornecedoresFinanceiros(),
            'supports_fornecedor_link' => $this->model->supportsFornecedorLink(),
            'movement_summary' => $movementSummary,
            'movements' => $movements,
        ];
    }

    /**
     * @param array<string,mixed> $dados
     * @return array<string,mixed>
     */
    private function sanitizePayload(array $dados): array
    {
        $payload = $dados;
        $competenciaMes = array_key_exists('data_competencia_mes', $payload)
            ? trim((string) $payload['data_competencia_mes'])
            : null;

        unset($payload['origem_tipo'], $payload['origem_id']);
        unset($payload['data_competencia_mes']);

        foreach (['tipo', 'categoria', 'descricao', 'forma_pagamento', 'status', 'observacoes', 'grupo_dre', 'subgrupo_dre'] as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $payload[$field] = trim((string) $payload[$field]);
            if ($payload[$field] === '') {
                $payload[$field] = null;
            }
        }

        foreach (['valor'] as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $payload[$field] = (float) $payload[$field];
        }

        foreach (['os_id'] as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $payload[$field] = $payload[$field] === '' || $payload[$field] === null
                ? null
                : (int) $payload[$field];
        }

        foreach (['data_vencimento', 'data_pagamento', 'data_competencia'] as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $payload[$field] = trim((string) $payload[$field]);
            if ($payload[$field] === '') {
                $payload[$field] = null;
            }
        }

        if ($competenciaMes !== null) {
            $payload['data_competencia'] = $this->normalizeCompetenciaMes($competenciaMes);
        }

        if ($this->model->supportsFornecedorLink()) {
            $tipo = (string) ($payload['tipo'] ?? '');
            $payload['fornecedor_id'] = ($tipo === 'pagar' && ! empty($payload['fornecedor_id']))
                ? (int) $payload['fornecedor_id']
                : null;
        } else {
            unset($payload['fornecedor_id']);
        }

        foreach (['impacta_dre', 'impacta_fluxo_caixa', 'dre_fixo_mensal'] as $field) {
            $payload[$field] = (int) ($payload[$field] ?? 0);
        }

        if (($payload['status'] ?? null) !== 'pago' && empty($payload['data_pagamento'])) {
            $payload['data_pagamento'] = null;
        }

        return $payload;
    }

    private function normalizeCompetenciaMes(string $competenciaMes): ?string
    {
        $competenciaMes = trim($competenciaMes);
        if ($competenciaMes === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $competenciaMes) === 1) {
            return $competenciaMes . '-01';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $competenciaMes) === 1) {
            return $competenciaMes;
        }

        return null;
    }

    /**
     * @param array<string,mixed> $dados
     * @return array<string,mixed>
     */
    private function sanitizeMovementPayload(array $dados): array
    {
        $payload = $dados;

        foreach (['data_pagamento', 'data_movimento'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = trim((string) $payload[$field]);
                if ($payload[$field] === '') {
                    $payload[$field] = null;
                }
            }
        }

        foreach (['forma_pagamento', 'observacoes', 'observacoes_movimento', 'documento_ref'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = trim((string) $payload[$field]);
                if ($payload[$field] === '') {
                    $payload[$field] = null;
                }
            }
        }

        $payload['valor_movimento'] = round((float) ($payload['valor_movimento'] ?? 0), 2);
        $payload['impacta_fluxo_caixa'] = (int) ($payload['impacta_fluxo_caixa'] ?? 1);

        return $payload;
    }

    /**
     * @param array<string,mixed> $lancamentoAtual
     * @param array<string,mixed> $dados
     */
    private function guardTitleMutationAgainstMovements(array $lancamentoAtual, array $dados): void
    {
        if (! $this->model->hasMovementSupport() || empty($lancamentoAtual['id'])) {
            return;
        }

        $summary = $this->model->getMovementSummaryForTitle((int) $lancamentoAtual['id'], $lancamentoAtual);
        if ((int) ($summary['total_movimentos'] ?? 0) <= 0) {
            return;
        }

        if (array_key_exists('tipo', $dados) && (string) $dados['tipo'] !== (string) ($lancamentoAtual['tipo'] ?? '')) {
            throw new \RuntimeException('Nao e possivel alterar o tipo de um titulo que ja possui movimentacoes registradas.');
        }

        if (array_key_exists('impacta_fluxo_caixa', $dados) && (int) $dados['impacta_fluxo_caixa'] !== 1) {
            throw new \RuntimeException('Um titulo que ja possui movimentos realizados deve continuar impactando o fluxo de caixa.');
        }

        $statusDestino = (string) ($dados['status'] ?? $lancamentoAtual['status'] ?? 'pendente');
        if ($statusDestino === 'cancelado') {
            throw new \RuntimeException('Nao e possivel cancelar um titulo que ja possui movimentacoes registradas.');
        }

        if (array_key_exists('valor', $dados) && round((float) $dados['valor'], 2) + 0.001 < round((float) ($summary['valor_movimentado'] ?? 0), 2)) {
            throw new \RuntimeException('O valor total do titulo nao pode ficar menor que o valor ja baixado.');
        }
    }

    private function buildListingQuery(): FinanceiroModel
    {
        $builder = $this->model
            ->select(
                'financeiro.*,
                os.numero_os,
                os.status as os_status,
                os.relato_cliente as os_relato_cliente,
                os.diagnostico_tecnico as os_diagnostico_tecnico,
                os.solucao_aplicada as os_solucao_aplicada,
                os.procedimentos_executados as os_procedimentos_executados,
                clientes.nome_razao as cliente_nome,
                et.nome as equip_tipo,
                em.nome as equip_marca,
                emod.nome as equip_modelo,
                equipamentos.resumo_tecnico as equip_resumo_tecnico,
                equipamentos.desktop_modalidade as equip_desktop_modalidade',
                false
            )
            ->join('os', 'os.id = financeiro.os_id', 'left')
            ->join('clientes', 'clientes.id = os.cliente_id', 'left')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id', 'left')
            ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left');

        if ($this->model->supportsFornecedorLink()) {
            $builder
                ->select('fornecedores.nome_fantasia as fornecedor_nome, fornecedores.razao_social as fornecedor_razao_social', false)
                ->join('fornecedores', 'fornecedores.id = financeiro.fornecedor_id', 'left');
        }

        return $builder;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function fetchOsDetails(int $osId): ?array
    {
        $db = \Config\Database::connect();

        $row = $db->table('os')
            ->select(
                'os.*,
                clientes.nome_razao as cliente_nome,
                clientes.telefone1 as cliente_telefone,
                clientes.telefone2 as cliente_telefone2,
                clientes.email as cliente_email,
                clientes.cpf_cnpj as cliente_documento,
                equipamentos.id as equipamento_id,
                equipamentos.numero_serie as equip_serie,
                equipamentos.imei as equip_imei,
                equipamentos.cor as equip_cor,
                equipamentos.cor_hex as equip_cor_hex,
                equipamentos.senha_acesso as equip_senha,
                equipamentos.observacoes as equip_observacoes,
                equipamentos.resumo_tecnico as equip_resumo_tecnico,
                equipamentos.desktop_modalidade as equip_desktop_modalidade,
                et.nome as equip_tipo,
                em.nome as equip_marca,
                emod.nome as equip_modelo,
                funcionarios.nome as tecnico_nome',
                false
            )
            ->join('clientes', 'clientes.id = os.cliente_id', 'left')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id', 'left')
            ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->join('funcionarios', 'funcionarios.id = os.tecnico_id', 'left')
            ->where('os.id', $osId)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchOsItems(int $osId): array
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('os_itens')) {
            return [];
        }

        return $db->table('os_itens')
            ->select('tipo, descricao, quantidade, valor_unitario, valor_total')
            ->where('os_id', $osId)
            ->orderBy('tipo', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchOsDefeitos(int $osId): array
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('os_defeitos') || ! $db->tableExists('equipamentos_defeitos')) {
            return [];
        }

        return $db->table('os_defeitos od')
            ->select('ed.nome, ed.classificacao, ed.descricao', false)
            ->join('equipamentos_defeitos ed', 'ed.id = od.defeito_id', 'left')
            ->where('od.os_id', $osId)
            ->orderBy('ed.nome', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array{categories: array<int,array<string,mixed>>, dre_groups: array<int,array<string,mixed>>, dre_subgroups: array<int,array<string,mixed>>}
     */
    private function loadCatalogosFinanceiros(): array
    {
        $categoriaModel = new FinanceiroCategoriaModel();
        $grupoModel = new FinanceiroDreGrupoModel();
        $subgrupoModel = new FinanceiroDreSubgrupoModel();

        $categories = $categoriaModel->isTableReady() ? $categoriaModel->getAllComDefaults(true) : [];
        $dreGroups = $grupoModel->isTableReady() ? $grupoModel->getActiveForSelect() : [];
        $dreSubgroups = $subgrupoModel->isTableReady() ? $subgrupoModel->getAllWithGroups(true) : [];

        if ($dreGroups === []) {
            foreach (FinanceiroModel::dreGroupOptions() as $nome => $label) {
                $dreGroups[] = [
                    'id' => $nome,
                    'nome' => $label,
                ];
            }
        }

        return [
            'categories' => $categories,
            'dre_groups' => $dreGroups,
            'dre_subgroups' => $dreSubgroups,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function loadFornecedoresFinanceiros(): array
    {
        if (! $this->model->supportsFornecedorLink()) {
            return [];
        }

        return (new FornecedorModel())
            ->orderBy('ativo', 'DESC')
            ->orderBy('nome_fantasia', 'ASC')
            ->findAll();
    }
}
