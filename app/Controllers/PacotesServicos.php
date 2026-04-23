<?php

namespace App\Controllers;

use App\Models\EquipamentoTipoModel;
use App\Models\LogModel;
use App\Models\PacoteServicoModel;
use App\Models\PacoteServicoNivelModel;
use App\Models\ServicoModel;

class PacotesServicos extends BaseController
{
    private PacoteServicoModel $pacoteModel;
    private PacoteServicoNivelModel $nivelModel;

    public function __construct()
    {
        requirePermission('orcamentos');
        $this->pacoteModel = new PacoteServicoModel();
        $this->nivelModel = new PacoteServicoNivelModel();
    }

    public function index()
    {
        if (!$this->moduleReady()) {
            return redirect()->to('/orcamentos')->with(
                'error',
                'Modulo de pacotes ainda nao foi inicializado. Execute as migracoes para habilitar.'
            );
        }

        return view('pacotes_servicos/index', [
            'title' => 'Pacotes de Servicos',
            'pacotes' => $this->pacoteModel->withResumoNiveis(),
            'servicosMap' => $this->loadServicosMap(),
        ]);
    }

    public function create()
    {
        requirePermission('orcamentos', 'criar');
        if (!$this->moduleReady()) {
            return redirect()->to('/orcamentos')->with(
                'error',
                'Modulo de pacotes ainda nao foi inicializado. Execute as migracoes para habilitar.'
            );
        }

        return view('pacotes_servicos/form', [
            'title' => 'Novo Pacote de Servicos',
            'isEdit' => false,
            'pacote' => $this->defaultPacote(),
            'niveis' => $this->defaultNiveis(),
            'tiposEquipamento' => $this->loadTiposEquipamentoOptions(),
            'servicos' => $this->loadServicosAtivos(),
            'actionUrl' => base_url('pacotes-servicos/salvar'),
        ]);
    }

    public function store()
    {
        requirePermission('orcamentos', 'criar');
        if (!$this->moduleReady()) {
            return redirect()->to('/orcamentos')->with(
                'error',
                'Modulo de pacotes ainda nao foi inicializado. Execute as migracoes para habilitar.'
            );
        }

        $payload = $this->extractPacotePayload();
        $niveis = $this->extractNiveisPayload();
        $validationError = $this->validatePacotePayload($payload, $niveis);
        if ($validationError !== null) {
            return redirect()->back()->withInput()->with('error', $validationError);
        }

        $this->pacoteModel->db->transStart();
        $this->pacoteModel->insert($payload);
        $pacoteId = (int) $this->pacoteModel->getInsertID();
        if ($pacoteId > 0) {
            $this->persistNiveis($pacoteId, $niveis);
        }
        $this->pacoteModel->db->transComplete();

        if (!$this->pacoteModel->db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Falha ao salvar pacote de servicos.');
        }

        LogModel::registrar('pacote_servico_criado', 'Pacote de servicos criado: ' . ($payload['nome'] ?? ''));
        return redirect()->to('/pacotes-servicos')->with('success', 'Pacote de servicos cadastrado com sucesso.');
    }

    public function edit($id)
    {
        requirePermission('orcamentos', 'editar');
        if (!$this->moduleReady()) {
            return redirect()->to('/orcamentos')->with(
                'error',
                'Modulo de pacotes ainda nao foi inicializado. Execute as migracoes para habilitar.'
            );
        }

        $pacote = $this->pacoteModel->find((int) $id);
        if (!$pacote) {
            return redirect()->to('/pacotes-servicos')->with('error', 'Pacote de servicos nao encontrado.');
        }

        return view('pacotes_servicos/form', [
            'title' => 'Editar Pacote de Servicos',
            'isEdit' => true,
            'pacote' => $pacote,
            'niveis' => $this->resolveNiveisForForm((int) $id),
            'tiposEquipamento' => $this->loadTiposEquipamentoOptions(),
            'servicos' => $this->loadServicosAtivos(),
            'actionUrl' => base_url('pacotes-servicos/atualizar/' . (int) $id),
        ]);
    }

    public function preview($id)
    {
        requirePermission('orcamentos', 'visualizar');
        if (!$this->moduleReady()) {
            return redirect()->to('/orcamentos')->with(
                'error',
                'Modulo de pacotes ainda nao foi inicializado. Execute as migracoes para habilitar.'
            );
        }

        $pacoteId = (int) $id;
        $pacote = $this->pacoteModel->find($pacoteId);
        if (!$pacote) {
            return redirect()->to('/pacotes-servicos')->with('error', 'Pacote de servicos nao encontrado.');
        }

        $niveis = $this->findNiveisAtivos($pacoteId);

        $oferta = [
            'status' => 'ativo',
            'token_publico' => '',
            'pacote_servico_id' => $pacoteId,
            'pacote_nome' => trim((string) ($pacote['nome'] ?? 'Pacote de Servicos')),
            'pacote_descricao' => trim((string) ($pacote['descricao'] ?? '')),
            'expira_em' => null,
        ];

        return view('orcamentos/oferta_publica', [
            'oferta' => $oferta,
            'niveis' => $niveis,
            'clienteNome' => 'Cliente',
            'statusOferta' => 'ativo',
            'canChoose' => false,
            'isPreview' => true,
        ]);
    }

    public function update($id)
    {
        requirePermission('orcamentos', 'editar');
        if (!$this->moduleReady()) {
            return redirect()->to('/orcamentos')->with(
                'error',
                'Modulo de pacotes ainda nao foi inicializado. Execute as migracoes para habilitar.'
            );
        }

        $pacoteId = (int) $id;
        $current = $this->pacoteModel->find($pacoteId);
        if (!$current) {
            return redirect()->to('/pacotes-servicos')->with('error', 'Pacote de servicos nao encontrado.');
        }

        $payload = $this->extractPacotePayload();
        $niveis = $this->extractNiveisPayload();
        $validationError = $this->validatePacotePayload($payload, $niveis);
        if ($validationError !== null) {
            return redirect()->back()->withInput()->with('error', $validationError);
        }

        $this->pacoteModel->db->transStart();
        $this->pacoteModel->update($pacoteId, $payload);
        $this->persistNiveis($pacoteId, $niveis);
        $this->pacoteModel->db->transComplete();

        if (!$this->pacoteModel->db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Falha ao atualizar pacote de servicos.');
        }

        LogModel::registrar('pacote_servico_atualizado', 'Pacote de servicos atualizado ID: ' . $pacoteId);
        return redirect()->to('/pacotes-servicos')->with('success', 'Pacote de servicos atualizado com sucesso.');
    }

    public function delete($id)
    {
        requirePermission('orcamentos', 'excluir');
        if (!$this->moduleReady()) {
            return redirect()->to('/orcamentos')->with(
                'error',
                'Modulo de pacotes ainda nao foi inicializado. Execute as migracoes para habilitar.'
            );
        }

        $pacoteId = (int) $id;
        $pacote = $this->pacoteModel->find($pacoteId);
        if (!$pacote) {
            return redirect()->to('/pacotes-servicos')->with('error', 'Pacote de servicos nao encontrado.');
        }

        $this->pacoteModel->delete($pacoteId);
        LogModel::registrar('pacote_servico_excluido', 'Pacote de servicos excluido: ' . ($pacote['nome'] ?? ''));

        return redirect()->to('/pacotes-servicos')->with('success', 'Pacote de servicos excluido com sucesso.');
    }

    private function moduleReady(): bool
    {
        return $this->pacoteModel->db->tableExists('pacotes_servicos')
            && $this->pacoteModel->db->tableExists('pacotes_servicos_niveis');
    }

    private function validatePacotePayload(array $payload, array $niveis): ?string
    {
        if (trim((string) ($payload['nome'] ?? '')) === '') {
            return 'Informe o nome do pacote de servicos.';
        }

        if (mb_strlen(trim((string) ($payload['nome'] ?? '')), 'UTF-8') < 3) {
            return 'Nome do pacote deve ter ao menos 3 caracteres.';
        }

        foreach (['basico', 'completo', 'premium'] as $nivel) {
            if (!isset($niveis[$nivel])) {
                return 'Preencha os tres niveis do pacote (basico, completo e premium).';
            }
        }

        return null;
    }

    private function defaultPacote(): array
    {
        return [
            'nome' => '',
            'categoria' => 'computadores',
            'tipo_equipamento' => '',
            'servico_referencia_id' => null,
            'descricao' => '',
            'metodologia_origem' => 'Passo 05 - 3 Pacotes',
            'ordem_apresentacao' => 0,
            'ativo' => 1,
        ];
    }

    private function defaultNiveis(): array
    {
        return [
            'basico' => [
                'nivel' => 'basico',
                'nome_exibicao' => 'Basico',
                'cor_hex' => '#6B7280',
                'preco_min' => 0,
                'preco_recomendado' => 0,
                'preco_max' => 0,
                'prazo_estimado' => '',
                'garantia_dias' => 15,
                'itens_inclusos' => '',
                'argumento_venda' => '',
                'destaque' => 0,
                'ordem' => 1,
                'ativo' => 1,
            ],
            'completo' => [
                'nivel' => 'completo',
                'nome_exibicao' => 'Completo',
                'cor_hex' => '#D4AF37',
                'preco_min' => 0,
                'preco_recomendado' => 0,
                'preco_max' => 0,
                'prazo_estimado' => '',
                'garantia_dias' => 30,
                'itens_inclusos' => '',
                'argumento_venda' => '',
                'destaque' => 1,
                'ordem' => 2,
                'ativo' => 1,
            ],
            'premium' => [
                'nivel' => 'premium',
                'nome_exibicao' => 'Premium',
                'cor_hex' => '#7C3AED',
                'preco_min' => 0,
                'preco_recomendado' => 0,
                'preco_max' => 0,
                'prazo_estimado' => '',
                'garantia_dias' => 60,
                'itens_inclusos' => '',
                'argumento_venda' => '',
                'destaque' => 0,
                'ordem' => 3,
                'ativo' => 1,
            ],
        ];
    }

    private function resolveNiveisForForm(int $pacoteId): array
    {
        $result = $this->defaultNiveis();
        if ($pacoteId <= 0) {
            return $result;
        }

        $rows = $this->nivelModel->byPacote($pacoteId);
        foreach ($rows as $row) {
            $nivel = trim((string) ($row['nivel'] ?? ''));
            if (!isset($result[$nivel])) {
                continue;
            }
            $result[$nivel] = array_merge($result[$nivel], $row);
        }

        return $result;
    }

    private function extractPacotePayload(): array
    {
        $categoria = trim((string) $this->request->getPost('categoria'));
        if ($categoria === '') {
            $categoria = 'geral';
        }

        $servicoReferenciaId = (int) ($this->request->getPost('servico_referencia_id') ?? 0);

        return [
            'nome' => trim((string) $this->request->getPost('nome')),
            'categoria' => $categoria,
            'tipo_equipamento' => trim((string) $this->request->getPost('tipo_equipamento')) ?: null,
            'servico_referencia_id' => $servicoReferenciaId > 0 ? $servicoReferenciaId : null,
            'descricao' => trim((string) $this->request->getPost('descricao')) ?: null,
            'metodologia_origem' => trim((string) $this->request->getPost('metodologia_origem')) ?: 'Passo 05 - 3 Pacotes',
            'ordem_apresentacao' => (int) ($this->request->getPost('ordem_apresentacao') ?? 0),
            'ativo' => ((string) ($this->request->getPost('ativo') ?? '1')) === '1' ? 1 : 0,
        ];
    }

    private function extractNiveisPayload(): array
    {
        $defaults = $this->defaultNiveis();

        $nomeMap = (array) $this->request->getPost('nivel_nome_exibicao');
        $corMap = (array) $this->request->getPost('nivel_cor_hex');
        $minMap = (array) $this->request->getPost('nivel_preco_min');
        $recMap = (array) $this->request->getPost('nivel_preco_recomendado');
        $maxMap = (array) $this->request->getPost('nivel_preco_max');
        $prazoMap = (array) $this->request->getPost('nivel_prazo_estimado');
        $garantiaMap = (array) $this->request->getPost('nivel_garantia_dias');
        $itensMap = (array) $this->request->getPost('nivel_itens_inclusos');
        $argumentoMap = (array) $this->request->getPost('nivel_argumento_venda');
        $destaqueMap = (array) $this->request->getPost('nivel_destaque');
        $ativoMap = (array) $this->request->getPost('nivel_ativo');

        $result = [];
        foreach (['basico', 'completo', 'premium'] as $nivel) {
            $data = $defaults[$nivel];
            $data['nome_exibicao'] = trim((string) ($nomeMap[$nivel] ?? $data['nome_exibicao']));
            $data['cor_hex'] = $this->normalizeHexColor((string) ($corMap[$nivel] ?? $data['cor_hex'])) ?? $data['cor_hex'];
            $data['preco_min'] = max(0, $this->normalizeMoney($minMap[$nivel] ?? 0));
            $data['preco_recomendado'] = max(0, $this->normalizeMoney($recMap[$nivel] ?? 0));
            $data['preco_max'] = max(0, $this->normalizeMoney($maxMap[$nivel] ?? 0));
            if ($data['preco_max'] < $data['preco_min']) {
                $tmp = $data['preco_max'];
                $data['preco_max'] = $data['preco_min'];
                $data['preco_min'] = $tmp;
            }

            if ($data['preco_recomendado'] < $data['preco_min'] || $data['preco_recomendado'] > $data['preco_max']) {
                $data['preco_recomendado'] = round(($data['preco_min'] + $data['preco_max']) / 2, 2);
            }

            $data['prazo_estimado'] = trim((string) ($prazoMap[$nivel] ?? '')) ?: null;
            $data['garantia_dias'] = max(0, (int) ($garantiaMap[$nivel] ?? $data['garantia_dias']));
            $data['itens_inclusos'] = trim((string) ($itensMap[$nivel] ?? '')) ?: null;
            $data['argumento_venda'] = trim((string) ($argumentoMap[$nivel] ?? '')) ?: null;
            $data['destaque'] = !empty($destaqueMap[$nivel]) ? 1 : 0;
            $data['ativo'] = !empty($ativoMap[$nivel]) ? 1 : 0;
            $result[$nivel] = $data;
        }

        return $result;
    }

    private function persistNiveis(int $pacoteId, array $niveis): void
    {
        if ($pacoteId <= 0) {
            return;
        }

        $this->nivelModel->where('pacote_servico_id', $pacoteId)->delete();
        foreach ($niveis as $nivelCode => $nivelData) {
            $this->nivelModel->insert([
                'pacote_servico_id' => $pacoteId,
                'nivel' => $nivelCode,
                'nome_exibicao' => trim((string) ($nivelData['nome_exibicao'] ?? '')) ?: ucfirst($nivelCode),
                'cor_hex' => $this->normalizeHexColor((string) ($nivelData['cor_hex'] ?? '')),
                'preco_min' => (float) ($nivelData['preco_min'] ?? 0),
                'preco_recomendado' => (float) ($nivelData['preco_recomendado'] ?? 0),
                'preco_max' => (float) ($nivelData['preco_max'] ?? 0),
                'prazo_estimado' => $nivelData['prazo_estimado'] ?? null,
                'garantia_dias' => (int) ($nivelData['garantia_dias'] ?? 0),
                'itens_inclusos' => $nivelData['itens_inclusos'] ?? null,
                'argumento_venda' => $nivelData['argumento_venda'] ?? null,
                'destaque' => !empty($nivelData['destaque']) ? 1 : 0,
                'ordem' => (int) ($nivelData['ordem'] ?? 0),
                'ativo' => !empty($nivelData['ativo']) ? 1 : 0,
            ]);
        }
    }

    private function loadTiposEquipamentoOptions(): array
    {
        try {
            $rows = (new EquipamentoTipoModel())
                ->where('ativo', 1)
                ->orderBy('nome', 'ASC')
                ->findAll();

            $values = array_map(
                static fn (array $row): string => trim((string) ($row['nome'] ?? '')),
                $rows
            );

            $values = array_values(array_filter($values, static fn (string $value): bool => $value !== ''));
            if (!in_array('Diverso', $values, true)) {
                $values[] = 'Diverso';
            }
            return $values;
        } catch (\Throwable $e) {
            log_message('warning', '[PacotesServicos] Falha ao carregar tipos de equipamento: ' . $e->getMessage());
            return ['Diverso'];
        }
    }

    private function loadServicosAtivos(): array
    {
        try {
            return (new ServicoModel())
                ->where('status', 'ativo')
                ->where('encerrado_em IS NULL', null, false)
                ->orderBy('nome', 'ASC')
                ->findAll();
        } catch (\Throwable $e) {
            log_message('warning', '[PacotesServicos] Falha ao carregar servicos ativos: ' . $e->getMessage());
            return [];
        }
    }

    private function loadServicosMap(): array
    {
        try {
            $rows = (new ServicoModel())
                ->select('id, nome')
                ->orderBy('nome', 'ASC')
                ->findAll();

            $map = [];
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $map[$id] = trim((string) ($row['nome'] ?? ''));
            }

            return $map;
        } catch (\Throwable $e) {
            log_message('warning', '[PacotesServicos] Falha ao montar mapa de servicos: ' . $e->getMessage());
            return [];
        }
    }

    private function normalizeMoney($value): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }

        $raw = preg_replace('/[^0-9,.\-]/', '', $raw) ?? '';
        if ($raw === '') {
            return 0.0;
        }

        $hasComma = str_contains($raw, ',');
        $hasDot = str_contains($raw, '.');

        if ($hasComma && $hasDot) {
            if (strrpos($raw, ',') > strrpos($raw, '.')) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($hasComma) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        return round((float) $raw, 2);
    }

    private function normalizeHexColor(string $color): ?string
    {
        $color = trim($color);
        if ($color === '') {
            return null;
        }

        if ($color[0] !== '#') {
            $color = '#' . $color;
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return null;
        }

        return strtoupper($color);
    }

    private function findNiveisAtivos(int $pacoteId): array
    {
        if ($pacoteId <= 0) {
            return [];
        }

        return $this->nivelModel
            ->where('pacote_servico_id', $pacoteId)
            ->where('ativo', 1)
            ->orderBy('ordem', 'ASC')
            ->findAll();
    }
}
