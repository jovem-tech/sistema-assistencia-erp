<?php

namespace App\Controllers;

use App\Models\FinanceiroCartaoBandeiraModel;
use App\Models\FinanceiroCartaoOperadoraModel;
use App\Models\FinanceiroCartaoTaxaModel;
use App\Models\LogModel;
use App\Services\FinanceiroCartaoService;

class FinanceiroCartoes extends BaseController
{
    private FinanceiroCartaoOperadoraModel $operadoraModel;
    private FinanceiroCartaoBandeiraModel $bandeiraModel;
    private FinanceiroCartaoTaxaModel $taxaModel;
    private FinanceiroCartaoService $service;

    public function __construct()
    {
        $this->operadoraModel = new FinanceiroCartaoOperadoraModel();
        $this->bandeiraModel = new FinanceiroCartaoBandeiraModel();
        $this->taxaModel = new FinanceiroCartaoTaxaModel();
        $this->service = new FinanceiroCartaoService();
    }

    public function index()
    {
        return view('financeiro/cartoes', [
            'title' => 'CartÃµes e Taxas',
            'operadoras' => $this->operadoraModel->orderBy('ativo', 'DESC')->orderBy('ordem_exibicao', 'ASC')->orderBy('nome', 'ASC')->findAll(),
            'bandeiras' => $this->bandeiraModel->orderBy('ativo', 'DESC')->orderBy('ordem_exibicao', 'ASC')->orderBy('nome', 'ASC')->findAll(),
            'taxas' => $this->buildTaxaRows(),
            'cartaoConfigReady' => $this->service->isReady(),
            'simuladorDataset' => $this->service->buildActiveDataset(),
        ]);
    }

    public function saveOperadora()
    {
        $id = (int) ($this->request->getPost('id') ?? 0);
        $nome = trim((string) $this->request->getPost('nome'));

        if ($nome === '') {
            return redirect()->to('/financeiro/cartoes')->with('error', 'Informe o nome da operadora.');
        }

        $payload = [
            'nome' => $nome,
            'descricao' => $this->nullableString($this->request->getPost('descricao')),
            'ordem_exibicao' => max(0, (int) ($this->request->getPost('ordem_exibicao') ?? 0)),
            'prazo_padrao_dias' => max(0, (int) ($this->request->getPost('prazo_padrao_dias') ?? 0)),
            'ativo' => $this->booleanFlag($this->request->getPost('ativo'), 1),
        ];

        if ($id > 0) {
            $this->operadoraModel->update($id, $payload);
            LogModel::registrar('financeiro_cartao_operadora_atualizada', 'Operadora de cartÃ£o atualizada: ' . $nome);
            return redirect()->to('/financeiro/cartoes')->with('success', 'Operadora atualizada com sucesso.');
        }

        $this->operadoraModel->insert($payload);
        LogModel::registrar('financeiro_cartao_operadora_criada', 'Operadora de cartÃ£o criada: ' . $nome);
        return redirect()->to('/financeiro/cartoes')->with('success', 'Operadora cadastrada com sucesso.');
    }

    public function disableOperadora($id)
    {
        $row = $this->operadoraModel->find((int) $id);
        if (! $row) {
            return redirect()->to('/financeiro/cartoes')->with('error', 'Operadora nÃ£o encontrada.');
        }

        $this->operadoraModel->update((int) $id, ['ativo' => 0]);
        LogModel::registrar('financeiro_cartao_operadora_desativada', 'Operadora de cartÃ£o desativada: ' . ($row['nome'] ?? ''));
        return redirect()->to('/financeiro/cartoes')->with('success', 'Operadora desativada com sucesso.');
    }

    public function saveBandeira()
    {
        $id = (int) ($this->request->getPost('id') ?? 0);
        $nome = trim((string) $this->request->getPost('nome'));

        if ($nome === '') {
            return redirect()->to('/financeiro/cartoes')->with('error', 'Informe o nome da bandeira.');
        }

        $payload = [
            'nome' => $nome,
            'ordem_exibicao' => max(0, (int) ($this->request->getPost('ordem_exibicao') ?? 0)),
            'ativo' => $this->booleanFlag($this->request->getPost('ativo'), 1),
        ];

        if ($id > 0) {
            $this->bandeiraModel->update($id, $payload);
            LogModel::registrar('financeiro_cartao_bandeira_atualizada', 'Bandeira de cartÃ£o atualizada: ' . $nome);
            return redirect()->to('/financeiro/cartoes')->with('success', 'Bandeira atualizada com sucesso.');
        }

        $this->bandeiraModel->insert($payload);
        LogModel::registrar('financeiro_cartao_bandeira_criada', 'Bandeira de cartÃ£o criada: ' . $nome);
        return redirect()->to('/financeiro/cartoes')->with('success', 'Bandeira cadastrada com sucesso.');
    }

    public function disableBandeira($id)
    {
        $row = $this->bandeiraModel->find((int) $id);
        if (! $row) {
            return redirect()->to('/financeiro/cartoes')->with('error', 'Bandeira nÃ£o encontrada.');
        }

        $this->bandeiraModel->update((int) $id, ['ativo' => 0]);
        LogModel::registrar('financeiro_cartao_bandeira_desativada', 'Bandeira de cartÃ£o desativada: ' . ($row['nome'] ?? ''));
        return redirect()->to('/financeiro/cartoes')->with('success', 'Bandeira desativada com sucesso.');
    }

    public function saveTaxa()
    {
        $id = (int) ($this->request->getPost('id') ?? 0);
        $operadoraId = (int) ($this->request->getPost('operadora_id') ?? 0);
        $modalidade = strtolower(trim((string) $this->request->getPost('modalidade')));
        $parcelasInicial = max(1, (int) ($this->request->getPost('parcelas_inicial') ?? 1));
        $parcelasFinal = max($parcelasInicial, (int) ($this->request->getPost('parcelas_final') ?? $parcelasInicial));

        if ($operadoraId <= 0) {
            return redirect()->to('/financeiro/cartoes')->with('error', 'Selecione a operadora da taxa.');
        }

        if (! in_array($modalidade, ['credito', 'debito'], true)) {
            return redirect()->to('/financeiro/cartoes')->with('error', 'Selecione uma modalidade vÃ¡lida para a taxa.');
        }

        $payload = [
            'operadora_id' => $operadoraId,
            'bandeira_id' => ($this->request->getPost('bandeira_id') !== null && $this->request->getPost('bandeira_id') !== '')
                ? (int) $this->request->getPost('bandeira_id')
                : null,
            'modalidade' => $modalidade,
            'parcelas_inicial' => $parcelasInicial,
            'parcelas_final' => $modalidade === 'debito' ? 1 : $parcelasFinal,
            'taxa_percentual' => $this->decimal($this->request->getPost('taxa_percentual')),
            'taxa_fixa' => $this->decimal($this->request->getPost('taxa_fixa')),
            'prazo_recebimento_dias' => max(0, (int) ($this->request->getPost('prazo_recebimento_dias') ?? 0)),
            'observacoes' => $this->nullableString($this->request->getPost('observacoes')),
            'ativo' => $this->booleanFlag($this->request->getPost('ativo'), 1),
        ];

        if ($id > 0) {
            $this->taxaModel->update($id, $payload);
            LogModel::registrar('financeiro_cartao_taxa_atualizada', 'Taxa de cartÃ£o atualizada ID: ' . $id);
            return redirect()->to('/financeiro/cartoes')->with('success', 'Taxa atualizada com sucesso.');
        }

        $this->taxaModel->insert($payload);
        LogModel::registrar('financeiro_cartao_taxa_criada', 'Taxa de cartÃ£o criada para operadora ID: ' . $operadoraId);
        return redirect()->to('/financeiro/cartoes')->with('success', 'Taxa cadastrada com sucesso.');
    }

    public function disableTaxa($id)
    {
        $row = $this->taxaModel->find((int) $id);
        if (! $row) {
            return redirect()->to('/financeiro/cartoes')->with('error', 'Taxa nÃ£o encontrada.');
        }

        $this->taxaModel->update((int) $id, ['ativo' => 0]);
        LogModel::registrar('financeiro_cartao_taxa_desativada', 'Taxa de cartÃ£o desativada ID: ' . (int) ($row['id'] ?? 0));
        return redirect()->to('/financeiro/cartoes')->with('success', 'Taxa desativada com sucesso.');
    }

    public function simulate()
    {
        try {
            $simulation = $this->service->simulate($this->request->getPost() ?: $this->request->getJSON(true) ?: []);
            return $this->response->setJSON([
                'ok' => true,
                'simulation' => $simulation,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'NÃ£o foi possÃ­vel simular a venda no cartÃ£o.',
            ]);
        }
    }

    private function buildTaxaRows(): array
    {
        if (! $this->service->isReady()) {
            return [];
        }

        return $this->taxaModel
            ->select('financeiro_cartao_taxas.*, financeiro_cartao_operadoras.nome as operadora_nome, financeiro_cartao_bandeiras.nome as bandeira_nome')
            ->join('financeiro_cartao_operadoras', 'financeiro_cartao_operadoras.id = financeiro_cartao_taxas.operadora_id')
            ->join('financeiro_cartao_bandeiras', 'financeiro_cartao_bandeiras.id = financeiro_cartao_taxas.bandeira_id', 'left')
            ->orderBy('financeiro_cartao_taxas.ativo', 'DESC')
            ->orderBy('financeiro_cartao_operadoras.ordem_exibicao', 'ASC')
            ->orderBy('financeiro_cartao_taxas.modalidade', 'ASC')
            ->orderBy('financeiro_cartao_taxas.parcelas_inicial', 'ASC')
            ->findAll();
    }

    private function nullableString($value): ?string
    {
        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }

    private function booleanFlag($value, int $default = 0): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'sim', 'on'], true) ? 1 : 0;
    }

    private function decimal($value): float
    {
        $normalized = str_replace(['R$', ' '], '', trim((string) $value));
        if ($normalized === '') {
            return 0.0;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return round((float) $normalized, 4);
    }
}
