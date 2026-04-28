<?php
namespace App\Controllers;
use App\Models\ClienteModel;
use App\Models\ContatoModel;
use App\Models\ConversaWhatsappModel;
use App\Models\EquipamentoModel;
use App\Models\EquipamentoMarcaModel;
use App\Models\EquipamentoModeloModel;
use App\Models\EquipamentoTipoModel;
use App\Models\LogModel;
use App\Models\OrcamentoAprovacaoModel;
use App\Models\OrcamentoEnvioModel;
use App\Models\OrcamentoItemModel;
use App\Models\OrcamentoModel;
use App\Models\OrcamentoStatusHistoricoModel;
use App\Models\OsModel;
use App\Models\OsStatusModel;
use App\Models\PecaModel;
use App\Models\ServicoModel;
use App\Models\PacoteServicoModel;
use App\Models\PacoteServicoNivelModel;
use App\Models\PacoteOfertaModel;
use App\Services\OrcamentoConversaoService;
use App\Services\OrcamentoLifecycleService;
use App\Services\OrcamentoMailService;
use App\Services\OrcamentoPdfService;
use App\Services\OsStatusFlowService;
use App\Services\PecaPrecificacaoService;
use App\Services\ServicoPrecificacaoService;
use App\Services\OrcamentoService;
use App\Services\WhatsAppService;
use Config\Database;
class Orcamentos extends BaseController
{
    private const RELATION_TABLE = 'equipamentos_catalogo_relacoes';
    private OrcamentoModel $orcamentoModel;
    private OrcamentoItemModel $itemModel;
    private OrcamentoStatusHistoricoModel $historicoModel;
    private OrcamentoEnvioModel $envioModel;
    private OrcamentoAprovacaoModel $aprovacaoModel;
    private PacoteOfertaModel $pacoteOfertaModel;
    private OrcamentoService $orcamentoService;
    private OrcamentoPdfService $pdfService;
    private OrcamentoMailService $mailService;
    private OrcamentoLifecycleService $lifecycleService;
    private OrcamentoConversaoService $conversaoService;
    private PecaPrecificacaoService $pecaPrecificacaoService;
    private ServicoPrecificacaoService $servicoPrecificacaoService;
    private ?array $osStatusLabelMap = null;
    private ?array $orcamentoItemFieldCache = null;
    public function __construct()
    {
        requirePermission('orcamentos');
        $this->orcamentoModel = new OrcamentoModel();
        $this->itemModel = new OrcamentoItemModel();
        $this->historicoModel = new OrcamentoStatusHistoricoModel();
        $this->envioModel = new OrcamentoEnvioModel();
        $this->aprovacaoModel = new OrcamentoAprovacaoModel();
        $this->pacoteOfertaModel = new PacoteOfertaModel();
        $this->orcamentoService = new OrcamentoService();
        $this->pdfService = new OrcamentoPdfService();
        $this->mailService = new OrcamentoMailService();
        $this->lifecycleService = new OrcamentoLifecycleService();
        $this->conversaoService = new OrcamentoConversaoService();
        $this->pecaPrecificacaoService = new PecaPrecificacaoService();
        $this->servicoPrecificacaoService = new ServicoPrecificacaoService();
    }
    public function index()
    {
        $this->syncLifecycleIfDue();
        $statusFilter = trim((string) $this->request->getGet('status'));
        $tipoFilter = trim((string) $this->request->getGet('tipo'));
        $q = trim((string) $this->request->getGet('q'));
        $enviosConfirmadosAvailable = $this->orcamentoModel->db->tableExists('orcamento_envios');
        $enviosConfirmadosJoin = null;
        if ($enviosConfirmadosAvailable) {
            $enviosConfirmadosJoin = "(SELECT orcamento_id, COUNT(*) AS confirmados"
                . " FROM orcamento_envios"
                . " WHERE status IN ('enviado','duplicado')"
                . " AND canal IN ('whatsapp','email')"
                . " GROUP BY orcamento_id) envios";
        }
        $builder = $this->orcamentoModel
            ->select('orcamentos.*, clientes.nome_razao as cliente_nome, os.numero_os')
            ->join('clientes', 'clientes.id = orcamentos.cliente_id', 'left')
            ->join('os', 'os.id = orcamentos.os_id', 'left');
        if ($enviosConfirmadosAvailable && $enviosConfirmadosJoin !== null) {
            $builder->select('envios.confirmados as envios_confirmados')
                ->join($enviosConfirmadosJoin, 'envios.orcamento_id = orcamentos.id', 'left', false);
        }
        if ($statusFilter !== '') {
            if ($statusFilter === OrcamentoModel::STATUS_PENDENTE_ENVIO && $enviosConfirmadosAvailable) {
                $builder->groupStart()
                    ->where('orcamentos.status', OrcamentoModel::STATUS_PENDENTE_ENVIO)
                    ->orGroupStart()
                        ->where('orcamentos.status', OrcamentoModel::STATUS_ENVIADO)
                        ->groupStart()
                            ->where('envios.confirmados IS NULL', null, false)
                            ->orWhere('envios.confirmados <=', 0)
                        ->groupEnd()
                    ->groupEnd()
                ->groupEnd();
            } elseif ($statusFilter === OrcamentoModel::STATUS_ENVIADO && $enviosConfirmadosAvailable) {
                $builder->where('orcamentos.status', OrcamentoModel::STATUS_ENVIADO)
                    ->where('envios.confirmados IS NOT NULL', null, false)
                    ->where('envios.confirmados >', 0);
            } else {
                $builder->where('orcamentos.status', $statusFilter);
            }
        }
        if (array_key_exists($tipoFilter, $this->orcamentoModel->tipoLabels())) {
            $builder->where('orcamentos.tipo_orcamento', $tipoFilter);
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('orcamentos.numero', $q)
                ->orLike('clientes.nome_razao', $q)
                ->orLike('orcamentos.cliente_nome_avulso', $q)
                ->orLike('os.numero_os', $q)
                ->groupEnd();
        }
        $orcamentos = $builder
            ->orderBy('orcamentos.created_at', 'DESC')
            ->findAll();
        foreach ($orcamentos as &$orcamento) {
            $orcamento = $this->normalizeOrcamentoRecord($orcamento);
        }
        unset($orcamento);
        $resumo = array_fill_keys(array_keys($this->orcamentoModel->statusLabels()), 0);
        $rowsResumo = $this->orcamentoModel
            ->select('status, COUNT(*) as total')
            ->groupBy('status')
            ->findAll();
        foreach ($rowsResumo as $row) {
            $status = (string) ($row['status'] ?? '');
            $resumo[$status] = (int) ($row['total'] ?? 0);
        }
        if ($enviosConfirmadosAvailable && $enviosConfirmadosJoin !== null) {
            $pendenteExtraRow = $this->orcamentoModel->db->table('orcamentos')
                ->select('COUNT(*) AS total')
                ->join($enviosConfirmadosJoin, 'envios.orcamento_id = orcamentos.id', 'left', false)
                ->where('orcamentos.status', OrcamentoModel::STATUS_ENVIADO)
                ->groupStart()
                    ->where('envios.confirmados IS NULL', null, false)
                    ->orWhere('envios.confirmados <=', 0)
                ->groupEnd()
                ->get()
                ->getRowArray();
            $pendenteExtra = (int) ($pendenteExtraRow['total'] ?? 0);
            if ($pendenteExtra > 0) {
                $resumo[OrcamentoModel::STATUS_PENDENTE_ENVIO] = ($resumo[OrcamentoModel::STATUS_PENDENTE_ENVIO] ?? 0) + $pendenteExtra;
                $resumo[OrcamentoModel::STATUS_ENVIADO] = max(0, (int) ($resumo[OrcamentoModel::STATUS_ENVIADO] ?? 0) - $pendenteExtra);
            }
        }
        return view('orcamentos/index', [
            'title' => 'Orçamentos',
            'orcamentos' => $orcamentos,
            'statusLabels' => $this->orcamentoModel->statusLabels(),
            'tipoLabels' => $this->orcamentoModel->tipoLabels(),
            'statusFilter' => $statusFilter,
            'tipoFilter' => $tipoFilter,
            'q' => $q,
            'resumo' => $resumo,
        ]);
    }
    public function create()
    {
        requirePermission('orcamentos', 'criar');
        $isEmbedded = $this->isEmbedRequest();
        $prefill = $this->prefillFromRequest();
        $prefill['status'] = OrcamentoModel::STATUS_RASCUNHO;
        return view('orcamentos/form', $this->buildFormData([
            'title' => 'Novo Orçamento',
            'orcamento' => $prefill,
            'itens' => [],
            'isEdit' => false,
            'actionUrl' => base_url('orcamentos/salvar' . ($isEmbedded ? '?embed=1' : '')),
            'layout' => $isEmbedded ? 'layouts/embed' : 'layouts/main',
            'isEmbedded' => $isEmbedded,
        ]));
    }
    public function lookupClienteContato()
    {
        requirePermission('orcamentos', 'visualizar');
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
        }
        $term = trim((string) $this->request->getGet('q'));
        $limit = 10;
        $clientes = $this->searchClientesForLookup($term, $limit);
        $phonesFromClientes = [];
        foreach ($clientes as $cliente) {
            $phone = $this->normalizePhone((string) ($cliente['telefone1'] ?? ''));
            if ($phone !== '') {
                $phonesFromClientes[$phone] = true;
            }
            $phoneContato = $this->normalizePhone((string) ($cliente['telefone_contato'] ?? ''));
            if ($phoneContato !== '') {
                $phonesFromClientes[$phoneContato] = true;
            }
        }
        $contatos = $this->searchContatosForLookup($term, $limit, array_keys($phonesFromClientes));
        $contatoAdicionalPorCliente = $this->loadContatoAdicionalByClienteIds($contatos);
        $results = [];
        foreach ($clientes as $cliente) {
            $nome = trim((string) ($cliente['nome_razao'] ?? ''));
            $telefone = trim((string) ($cliente['telefone1'] ?? ''));
            $email = trim((string) ($cliente['email'] ?? ''));
            $text = $nome !== '' ? $nome : ('Cliente #' . (int) $cliente['id']);
            if ($telefone !== '') {
                $text .= ' | ' . $telefone;
            }
            if ($email !== '') {
                $text .= ' | ' . $email;
            }
            $results[] = array_merge([
                'id' => 'cliente:' . (int) $cliente['id'],
                'text' => $text,
                'tipo' => 'cliente',
                'cliente_id' => (int) $cliente['id'],
                'contato_id' => null,
                'nome' => $nome,
                'telefone' => $telefone,
                'email' => $email,
                'source_label' => 'Cliente',
            ], $this->buildContatoAdicionalPayload($cliente));
        }
        foreach ($contatos as $contato) {
            $nome = trim((string) ($contato['nome'] ?? ''));
            $telefone = trim((string) ($contato['telefone'] ?? ''));
            $email = trim((string) ($contato['email'] ?? ''));
            if ($nome === '') {
                $nome = trim((string) ($contato['whatsapp_nome_perfil'] ?? ''));
            }
            $text = $nome !== '' ? $nome : ('Contato #' . (int) $contato['id']);
            if ($telefone !== '') {
                $text .= ' | ' . $telefone;
            }
            if ($email !== '') {
                $text .= ' | ' . $email;
            }
            $clienteVinculadoId = (int) ($contato['cliente_id'] ?? 0);
            $contatoAdicional = $clienteVinculadoId > 0
                ? ($contatoAdicionalPorCliente[$clienteVinculadoId] ?? $this->buildContatoAdicionalPayload([]))
                : $this->buildContatoAdicionalPayload([]);
            $results[] = array_merge([
                'id' => 'contato:' . (int) $contato['id'],
                'text' => $text,
                'tipo' => 'contato',
                'cliente_id' => $clienteVinculadoId ?: null,
                'contato_id' => (int) $contato['id'],
                'nome' => $nome,
                'telefone' => $telefone,
                'email' => $email,
                'source_label' => 'Contato',
            ], $contatoAdicional);
        }
        if ($term !== '' && empty($results)) {
            $results[] = [
                'id' => 'novo_contato:' . base64_encode($term),
                'text' => 'Cadastrar novo contato: ' . $term,
                'tipo' => 'novo_contato',
                'cliente_id' => null,
                'contato_id' => null,
                'nome' => '',
                'telefone' => '',
                'email' => '',
                'source_label' => 'Novo contato',
            ];
        }
        return $this->response->setJSON([
            'results' => $results,
            'pagination' => ['more' => false],
        ]);
    }
    public function lookupEquipamentosCliente()
    {
        requirePermission('orcamentos', 'visualizar');
        $clienteId = (int) ($this->request->getGet('cliente_id') ?? 0);
        if ($clienteId <= 0) {
            return $this->response->setJSON([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
        }
        $term = trim((string) $this->request->getGet('q'));
        $termNormalized = function_exists('mb_strtolower')
            ? mb_strtolower($term, 'UTF-8')
            : strtolower($term);
        $rows = (new EquipamentoModel())->getByCliente($clienteId);
        $results = [];
        foreach ($rows as $row) {
            $item = $this->formatEquipamentoLookupResult($row);
            if (empty($item)) {
                continue;
            }
            if ($termNormalized !== '') {
                $searchText = function_exists('mb_strtolower')
                    ? mb_strtolower((string) ($item['search_text'] ?? ''), 'UTF-8')
                    : strtolower((string) ($item['search_text'] ?? ''));
                if (!str_contains($searchText, $termNormalized)) {
                    continue;
                }
            }
            unset($item['search_text']);
            $results[] = $item;
        }
        return $this->response->setJSON([
            'results' => array_slice($results, 0, 50),
            'pagination' => ['more' => false],
        ]);
    }
    public function lookupOsAbertasCliente()
    {
        requirePermission('orcamentos', 'visualizar');
        $clienteId = (int) ($this->request->getGet('cliente_id') ?? 0);
        if ($clienteId <= 0) {
            return $this->response->setJSON([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
        }

        $term = trim((string) $this->request->getGet('q'));
        $termNormalized = function_exists('mb_strtolower')
            ? mb_strtolower($term, 'UTF-8')
            : strtolower($term);

        $results = [];
        foreach ($this->loadOsAbertasCliente($clienteId) as $item) {
            if ($termNormalized !== '') {
                $searchText = function_exists('mb_strtolower')
                    ? mb_strtolower((string) ($item['search_text'] ?? ''), 'UTF-8')
                    : strtolower((string) ($item['search_text'] ?? ''));
                if (!str_contains($searchText, $termNormalized)) {
                    continue;
                }
            }

            unset($item['search_text']);
            $results[] = $item;
        }

        return $this->response->setJSON([
            'results' => array_slice($results, 0, 50),
            'pagination' => ['more' => false],
        ]);
    }
    public function itemCatalogSearch()
    {
        requirePermission('orcamentos', 'visualizar');
        $tipo = strtolower(trim((string) $this->request->getGet('tipo')));
        if (! in_array($tipo, ['peca', 'servico'], true)) {
            $tipo = 'servico';
        }
        $termo = trim((string) $this->request->getGet('q'));
        $limit = (int) ($this->request->getGet('limit') ?? ($termo === '' ? 10 : 20));
        if ($limit <= 0 || $limit > 50) {
            $limit = $termo === '' ? 10 : 20;
        }
        $categoria = trim((string) $this->request->getGet('categoria'));
        $tipoEquipamento = trim((string) $this->request->getGet('tipo_equipamento'));
        if (strtolower($categoria) === 'todos') {
            $categoria = '';
        }
        if (strtolower($tipoEquipamento) === 'todos') {
            $tipoEquipamento = '';
        }
        $incluirDiversos = filter_var(
            $this->request->getGet('incluir_diversos'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        if ($incluirDiversos === null) {
            $incluirDiversos = true;
        }
        $results = $tipo === 'peca'
            ? $this->searchCatalogoPecas($termo, $limit, $categoria, $tipoEquipamento, $incluirDiversos)
            : $this->searchCatalogoServicos($termo, $limit, $tipoEquipamento, $incluirDiversos);
        return $this->response->setJSON(['results' => $results]);
    }
    private function searchCatalogoPecas(
        string $termo,
        int $limit,
        string $categoria,
        string $tipoEquipamento,
        bool $incluirDiversos
    ): array {
        $db = Database::connect();
        $pecasHasTipoEquipamento = $db->fieldExists('tipo_equipamento', 'pecas');
        $builder = $db->table('pecas p')
            ->select('p.id, p.nome, p.codigo, p.categoria, p.preco_custo, p.preco_venda, p.quantidade_atual, p.modelos_compativeis');
        if ($pecasHasTipoEquipamento) {
            $builder->select('p.tipo_equipamento');
        } else {
            $builder->select("'' as tipo_equipamento", false);
        }
        $builder->select('COALESCE(uso.total_usos, 0) as total_usos', false)
            ->join(
                '(SELECT peca_id, COUNT(*) AS total_usos FROM os_itens WHERE peca_id IS NOT NULL GROUP BY peca_id) uso',
                'uso.peca_id = p.id',
                'left'
            );
        if ($categoria !== '') {
            $builder->where('p.categoria', $categoria);
        }
        if ($pecasHasTipoEquipamento) {
            if ($tipoEquipamento !== '') {
                $builder->groupStart()
                    ->where('p.tipo_equipamento', $tipoEquipamento);
                if ($incluirDiversos) {
                    $builder->orWhere('LOWER(TRIM(p.tipo_equipamento))', 'diverso');
                }
                $builder->groupEnd();
            } elseif (! $incluirDiversos) {
                $builder->groupStart()
                    ->where('p.tipo_equipamento IS NULL', null, false)
                    ->orWhere('LOWER(TRIM(p.tipo_equipamento)) <>', 'diverso')
                    ->groupEnd();
            }
        }
        if ($termo !== '') {
            $builder->groupStart()
                ->like('p.nome', $termo)
                ->orLike('p.codigo', $termo)
                ->orLike('p.categoria', $termo)
                ->orLike('p.modelos_compativeis', $termo)
                ->groupEnd();
        }
        $builder->where('p.ativo', 1);
        if ($termo !== '') {
            $builder->orderBy('p.nome', 'ASC');
        } else {
            $builder->orderBy('total_usos', 'DESC')
                ->orderBy('p.quantidade_atual', 'DESC')
                ->orderBy('p.nome', 'ASC');
        }
        $rows = $builder->limit($limit)->get()->getResultArray();
        $results = [];
        foreach ($rows as $row) {
            $pecaId = (int) ($row['id'] ?? 0);
            if ($pecaId <= 0) {
                continue;
            }
            $quote = $this->pecaPrecificacaoService->buildQuote($row);
            $estoque = (int) ($row['quantidade_atual'] ?? 0);
            $results[] = [
                'id' => 'peca:' . $pecaId,
                'kind' => 'peca',
                'text' => (string) ($row['nome'] ?? ''),
                'descricao' => (string) ($row['nome'] ?? ''),
                'valor_unitario' => (float) ($quote['valor_recomendado'] ?? 0),
                'peca_id' => $pecaId,
                'servico_id' => null,
                'codigo' => (string) ($row['codigo'] ?? ''),
                'meta' => (string) ($row['modelos_compativeis'] ?? ''),
                'categoria' => trim((string) ($row['categoria'] ?? '')),
                'tipo_equipamento' => trim((string) ($row['tipo_equipamento'] ?? '')),
                'estoque' => $estoque,
                'total_usos' => (int) ($row['total_usos'] ?? 0),
                'pendencia' => $estoque <= 0,
                'preco_custo' => (float) ($row['preco_custo'] ?? 0),
                'preco_venda' => (float) ($row['preco_venda'] ?? 0),
                'precificacao' => [
                    'preco_base' => (float) ($quote['preco_base'] ?? 0),
                    'percentual_encargos' => (float) ($quote['percentual_encargos'] ?? 0),
                    'valor_encargos' => (float) ($quote['valor_encargos'] ?? 0),
                    'percentual_margem' => (float) ($quote['percentual_margem'] ?? 0),
                    'valor_margem' => (float) ($quote['valor_margem'] ?? 0),
                    'valor_recomendado' => (float) ($quote['valor_recomendado'] ?? 0),
                    'modo_precificacao' => (string) ($quote['modo_precificacao'] ?? 'peca_instalada_auto'),
                ],
            ];
        }
        return $results;
    }
    private function searchCatalogoServicos(
        string $termo,
        int $limit,
        string $tipoEquipamento,
        bool $incluirDiversos
    ): array {
        $db = Database::connect();
        $servicosHasTipoEquipamento = $db->fieldExists('tipo_equipamento', 'servicos');
        $servicosHasTempoPadrao = $db->fieldExists('tempo_padrao_horas', 'servicos');
        $servicosHasCustoDiretoPadrao = $db->fieldExists('custo_direto_padrao', 'servicos');
        $osItensHasServicoId = $db->fieldExists('servico_id', 'os_itens');
        $builder = $db->table('servicos s')
            ->select('s.id, s.nome, s.descricao, s.valor');
        if ($servicosHasTipoEquipamento) {
            $builder->select('s.tipo_equipamento');
        } else {
            $builder->select("'' as tipo_equipamento", false);
        }
        if ($servicosHasTempoPadrao) {
            $builder->select('s.tempo_padrao_horas');
        } else {
            $builder->select('1.00 as tempo_padrao_horas', false);
        }
        if ($servicosHasCustoDiretoPadrao) {
            $builder->select('s.custo_direto_padrao');
        } else {
            $builder->select('0.00 as custo_direto_padrao', false);
        }
        if ($osItensHasServicoId) {
            $builder->select('COALESCE(uso.total_usos, 0) as total_usos', false)
                ->join(
                    '(SELECT servico_id, COUNT(*) AS total_usos FROM os_itens WHERE servico_id IS NOT NULL GROUP BY servico_id) uso',
                    'uso.servico_id = s.id',
                    'left'
                );
        } else {
            $builder->select('0 as total_usos', false);
        }
        if ($servicosHasTipoEquipamento) {
            if ($tipoEquipamento !== '') {
                $builder->groupStart()
                    ->where('s.tipo_equipamento', $tipoEquipamento);
                if ($incluirDiversos) {
                    $builder->orWhere('LOWER(TRIM(s.tipo_equipamento))', 'diverso');
                }
                $builder->groupEnd();
            } elseif (! $incluirDiversos) {
                $builder->groupStart()
                    ->where('s.tipo_equipamento IS NULL', null, false)
                    ->orWhere('LOWER(TRIM(s.tipo_equipamento)) <>', 'diverso')
                    ->groupEnd();
            }
        }
        if ($termo !== '') {
            $builder->groupStart()
                ->like('s.nome', $termo)
                ->orLike('s.descricao', $termo);
            if ($servicosHasTipoEquipamento) {
                $builder->orLike('s.tipo_equipamento', $termo);
            }
            $builder->groupEnd();
        }
        $builder->where('s.status', 'ativo')
            ->where('s.encerrado_em IS NULL', null, false);
        if ($termo !== '') {
            $builder->orderBy('s.nome', 'ASC');
        } else {
            $builder->orderBy('total_usos', 'DESC')
                ->orderBy('s.nome', 'ASC');
        }
        $rows = $builder->limit($limit)->get()->getResultArray();
        $results = [];
        foreach ($rows as $row) {
            $servicoId = (int) ($row['id'] ?? 0);
            if ($servicoId <= 0) {
                continue;
            }
            $quote = $this->servicoPrecificacaoService->buildQuote($row);
            $valorCatalogo = (float) ($row['valor'] ?? 0);
            if ($this->servicoPrecificacaoService->shouldApplyCatalogPrice()) {
                $valorCatalogo = (float) ($quote['valor_recomendado'] ?? $valorCatalogo);
            }
            $results[] = [
                'id' => 'servico:' . $servicoId,
                'kind' => 'servico',
                'text' => (string) ($row['nome'] ?? ''),
                'descricao' => (string) ($row['nome'] ?? ''),
                'valor_unitario' => $valorCatalogo,
                'peca_id' => null,
                'servico_id' => $servicoId,
                'codigo' => '',
                'meta' => (string) ($row['descricao'] ?? ''),
                'categoria' => '',
                'tipo_equipamento' => trim((string) ($row['tipo_equipamento'] ?? '')),
                'estoque' => null,
                'total_usos' => (int) ($row['total_usos'] ?? 0),
                'pendencia' => false,
                'precificacao' => [
                    'tempo_padrao_horas' => (float) ($quote['tempo_padrao_horas'] ?? 0),
                    'custo_mao_obra' => (float) ($quote['custo_mao_obra'] ?? 0),
                    'custo_direto_total' => (float) ($quote['custo_direto_total'] ?? 0),
                    'risco_percentual' => (float) ($quote['risco_percentual'] ?? 0),
                    'valor_risco' => (float) ($quote['valor_risco'] ?? 0),
                    'custo_total' => (float) ($quote['custo_total'] ?? 0),
                    'preco_minimo' => (float) ($quote['preco_minimo'] ?? 0),
                    'valor_recomendado' => (float) ($quote['valor_recomendado'] ?? 0),
                    'modo_precificacao' => (string) ($quote['modo_precificacao'] ?? 'servico_cadastro'),
                ],
            ];
        }
        return $results;
    }
    public function detectPacoteOferta()
    {
        requirePermission('orcamentos', 'visualizar');
        if (!$this->isPacoteOfertaModuleReady()) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => 'Módulo de ofertas de pacote não inicializado.',
                'oferta' => null,
            ]);
        }
        $clienteId = (int) ($this->request->getGet('cliente_id') ?? 0);
        $contatoId = (int) ($this->request->getGet('contato_id') ?? 0);
        $telefone = $this->normalizeWhatsAppPhone((string) ($this->request->getGet('telefone') ?? ''));
        $nomeReferencia = trim((string) ($this->request->getGet('nome_referencia') ?? ''));
        $osId = (int) ($this->request->getGet('os_id') ?? 0);
        $equipamentoId = (int) ($this->request->getGet('equipamento_id') ?? 0);
        $orcamentoId = (int) ($this->request->getGet('orcamento_id') ?? 0);
        if ($clienteId <= 0 && $contatoId <= 0 && $telefone === '' && $osId <= 0 && $equipamentoId <= 0 && $orcamentoId <= 0) {
            return $this->response->setJSON([
                'ok' => true,
                'oferta' => null,
            ]);
        }
        $this->refreshPacotesOfertasIfExpired();
        $oferta = $this->pacoteOfertaModel->findLatestByIdentity(
            $clienteId > 0 ? $clienteId : null,
            $contatoId > 0 ? $contatoId : null,
            $telefone,
            $nomeReferencia,
            $osId > 0 ? $osId : null,
            $equipamentoId > 0 ? $equipamentoId : null,
            $orcamentoId > 0 ? $orcamentoId : null
        );
        return $this->response->setJSON([
            'ok' => true,
            'oferta' => $oferta ? $this->formatPacoteOfertaApi($oferta) : null,
        ]);
    }
    public function sendPacoteOferta()
    {
        requirePermission('orcamentos', 'criar');
        if (!$this->isPacoteOfertaModuleReady()) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Módulo de ofertas de pacote não inicializado. Execute as migrações.',
            ]);
        }
        $pacoteId = (int) ($this->request->getPost('pacote_servico_id') ?? 0);
        if ($pacoteId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Selecione um pacote para enviar a oferta.',
            ]);
        }
        $pacote = (new PacoteServicoModel())
            ->where('id', $pacoteId)
            ->where('ativo', 1)
            ->first();
        if (!$pacote) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Pacote de serviços não encontrado ou inativo.',
            ]);
        }
        $niveis = (new PacoteServicoNivelModel())
            ->where('pacote_servico_id', $pacoteId)
            ->where('ativo', 1)
            ->orderBy('ordem', 'ASC')
            ->findAll();
        if (empty($niveis)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Este pacote não possui níveis ativos para envio.',
            ]);
        }
        $clienteId = (int) ($this->request->getPost('cliente_id') ?? 0);
        $contatoId = (int) ($this->request->getPost('contato_id') ?? 0);
        $osId = (int) ($this->request->getPost('os_id') ?? 0);
        $equipamentoId = (int) ($this->request->getPost('equipamento_id') ?? 0);
        $origemContexto = trim((string) ($this->request->getPost('origem_contexto') ?? 'manual'));
        if ($origemContexto === '') {
            $origemContexto = 'manual';
        }
        $enviarWhatsapp = (string) ($this->request->getPost('enviar_whatsapp') ?? '1') === '1';
        $telefone = $this->normalizeWhatsAppPhone((string) ($this->request->getPost('telefone_contato') ?? ''));
        if ($telefone === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Informe um telefone WhatsApp valido para envio da oferta.',
            ]);
        }
        if ($enviarWhatsapp && !$this->isWhatsAppPhoneValid($telefone)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Telefone WhatsApp invalido para envio da oferta.',
            ]);
        }
        // Regra oficial da oferta dinamica: validade fixa de 48 horas.
        $expiraEm = date('Y-m-d H:i:s', strtotime('+48 hours'));
        $token = $this->orcamentoService->generateToken();
        $linkPublico = base_url('pacote/oferta/' . $token);
        $mensagemPersonalizada = trim((string) ($this->request->getPost('mensagem_pacote') ?? ''));
        $mensagemPersonalizadaComLink = (string) ($this->request->getPost('mensagem_personalizada_com_link') ?? '1') === '1';
        if ($mensagemPersonalizada === '') {
            $mensagem = $this->buildDefaultPacoteOfertaMessage($clienteId, $contatoId, $telefone, $pacote, $niveis, $linkPublico, $expiraEm);
        } else {
            $mensagem = $mensagemPersonalizada;
            if ($mensagemPersonalizadaComLink) {
                $mensagem = $this->appendPacoteOfertaLink($mensagemPersonalizada, $linkPublico, $expiraEm);
            }
        }
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $this->orcamentoModel->db->transStart();
        $cancelByPhone = ($clienteId > 0 || $contatoId > 0 || $osId > 0 || $equipamentoId > 0)
            ? $telefone
            : '';
        $this->cancelActivePacotesOfertasByIdentity(
            $clienteId > 0 ? $clienteId : null,
            $contatoId > 0 ? $contatoId : null,
            $cancelByPhone,
            $osId > 0 ? $osId : null,
            $equipamentoId > 0 ? $equipamentoId : null
        );
        $this->pacoteOfertaModel->insert([
            'pacote_servico_id' => $pacoteId,
            'cliente_id' => $clienteId > 0 ? $clienteId : null,
            'contato_id' => $contatoId > 0 ? $contatoId : null,
            'telefone_destino' => $telefone,
            'os_id' => $osId > 0 ? $osId : null,
            'equipamento_id' => $equipamentoId > 0 ? $equipamentoId : null,
            'origem_contexto' => $origemContexto,
            'token_publico' => $token,
            'status' => $enviarWhatsapp ? 'enviado' : 'ativo',
            'destino_canal' => $enviarWhatsapp ? 'whatsapp' : 'manual',
            'mensagem_enviada' => $mensagem,
            'expira_em' => $expiraEm,
            'enviado_em' => $enviarWhatsapp ? date('Y-m-d H:i:s') : null,
        ]);
        $ofertaId = (int) $this->pacoteOfertaModel->getInsertID();
        if ($ofertaId <= 0) {
            $this->orcamentoModel->db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Não foi possível criar a oferta de pacote.',
            ]);
        }
        $dispatch = ['ok' => true];
        if ($enviarWhatsapp) {
            $dispatch = $this->dispatchPacoteOfertaWhatsApp(
                $ofertaId,
                $osId > 0 ? $osId : null,
                $clienteId > 0 ? $clienteId : null,
                $telefone,
                $mensagem,
                $usuarioId > 0 ? $usuarioId : null
            );
            if (empty($dispatch['ok'])) {
                $this->pacoteOfertaModel->update($ofertaId, [
                    'status' => 'erro_envio',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        $this->orcamentoModel->db->transComplete();
        if (!$this->orcamentoModel->db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Falha ao concluir o envio da oferta de pacote.',
            ]);
        }
        $oferta = $this->pacoteOfertaModel->find($ofertaId);
        LogModel::registrar('pacote_oferta_enviada', 'Oferta de pacote criada (ID ' . $ofertaId . ').');
        if (!empty($dispatch['ok'])) {
            return $this->response->setJSON([
                'ok' => true,
                'message' => $enviarWhatsapp
                    ? 'Oferta de pacote enviada ao cliente com sucesso.'
                    : 'Oferta de pacote criada com sucesso.',
                'oferta' => $oferta ? $this->formatPacoteOfertaApi($oferta) : null,
            ]);
        }
        $error = trim((string) ($dispatch['message'] ?? 'Falha ao enviar oferta via WhatsApp.'));
        return $this->response->setJSON([
            'ok' => true,
            'warning' => true,
            'message' => $error . ' O link foi gerado e pode ser compartilhado manualmente.',
            'oferta' => $oferta ? $this->formatPacoteOfertaApi($oferta) : null,
        ]);
    }
    public function store()
    {
        requirePermission('orcamentos', 'criar');
        $payload = $this->extractOrcamentoPayload();
        $tipoValidationError = $this->validateTipoOrcamentoPayload($payload);
        if ($tipoValidationError !== null) {
            return redirect()->back()->withInput()->with('error', $tipoValidationError);
        }
        $contatoValidationError = $this->validateContatoPayload($payload);
        if ($contatoValidationError !== null) {
            return redirect()->back()->withInput()->with('error', $contatoValidationError);
        }
        $itens = $this->extractItensPayload();
        $pacoteOfertaIntent = $this->extractPacoteOfertaIntent();
        $isPacoteBased = !empty($pacoteOfertaIntent['orcamento_baseado_pacote']);
        $pacoteOfertaResolution = $this->resolvePacoteOfertaForApply($pacoteOfertaIntent, $payload, null);
        if (!empty($pacoteOfertaResolution['error'])) {
            return redirect()->back()->withInput()->with('error', (string) $pacoteOfertaResolution['error']);
        }
        $pacoteOferta = $pacoteOfertaResolution['oferta'] ?? null;
        if (empty($itens) && $pacoteOferta === null && !$isPacoteBased) {
        return redirect()->back()->withInput()->with('error', 'Adicione pelo menos um item no orçamento.');
        }
        if ($isPacoteBased && $pacoteOferta === null) {
            $autoIntentError = $this->validatePacoteOfertaAutosendIntent($pacoteOfertaIntent, $payload);
            if ($autoIntentError !== null) {
                return redirect()->back()->withInput()->with('error', $autoIntentError);
            }
        }
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $now = date('Y-m-d H:i:s');
        $payload['versao'] = max(1, (int) ($payload['versao'] ?? 1));
        $payload['criado_por'] = $usuarioId > 0 ? $usuarioId : null;
        $payload['atualizado_por'] = $usuarioId > 0 ? $usuarioId : null;
        $requestedStatus = (string) ($payload['status'] ?: OrcamentoModel::STATUS_RASCUNHO);
        if ($isPacoteBased) {
            $requestedStatus = $pacoteOferta !== null
                ? OrcamentoModel::STATUS_PACOTE_APROVADO
                : OrcamentoModel::STATUS_AGUARDANDO_PACOTE;
        } elseif ($requestedStatus === OrcamentoModel::STATUS_RASCUNHO) {
            // Requisito operacional: ao salvar um novo orcamento, ele sai de rascunho para pendente de envio.
            $requestedStatus = OrcamentoModel::STATUS_PENDENTE_ENVIO;
        }
        $payload['status'] = $this->resolveApprovedStatus($payload, $requestedStatus);
        $payload['token_publico'] = $this->orcamentoService->generateToken();
        $payload['token_expira_em'] = date('Y-m-d H:i:s', strtotime('+30 days'));
        $payload = array_merge($payload, $this->statusTimestampColumns($payload['status'], $now));
        $autoOfertaResult = ['warning' => null, 'error' => null];
        $this->orcamentoModel->db->transStart();
        $this->orcamentoModel->insert($payload);
        $orcamentoId = (int) $this->orcamentoModel->getInsertID();
        if ($orcamentoId <= 0) {
            $this->orcamentoModel->db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Não foi possível salvar o orçamento.');
        }
        $numero = $this->orcamentoService->ensureNumero($this->orcamentoModel, $orcamentoId);
        if (!empty($itens)) {
            $this->persistItens($orcamentoId, $itens);
        }
        if ($pacoteOferta !== null) {
            $applyError = $this->applyPacoteOfertaToOrcamento($pacoteOferta, $orcamentoId);
            if ($applyError !== null) {
                $this->orcamentoModel->db->transRollback();
                return redirect()->back()->withInput()->with('error', $applyError);
            }
        }
        if ($isPacoteBased && $pacoteOferta === null) {
            $autoOfertaResult = $this->createPacoteOfertaForOrcamento(
                $orcamentoId,
                $payload,
                $pacoteOfertaIntent,
                $usuarioId > 0 ? $usuarioId : null
            );
            if (!empty($autoOfertaResult['error'])) {
                $this->orcamentoModel->db->transRollback();
                return redirect()->back()->withInput()->with('error', (string) $autoOfertaResult['error']);
            }
        }
        $this->recalculateOrcamentoTotals($orcamentoId);
        if ($this->isConsolidatedStatus((string) ($payload['status'] ?? OrcamentoModel::STATUS_RASCUNHO))) {
            $this->promoteContatoToCliente($orcamentoId, array_merge($payload, ['id' => $orcamentoId]), $usuarioId > 0 ? $usuarioId : null);
        }
        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $orcamentoId,
            null,
            (string) $payload['status'],
            $usuarioId > 0 ? $usuarioId : null,
            'Criação do orçamento',
            'interno'
        );
        $this->orcamentoModel->db->transComplete();
        if (!$this->orcamentoModel->db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Falha ao concluir gravacao do orcamento.');
        }
        $this->syncLinkedOsByOrcamentoStatus(
            (int) ($payload['os_id'] ?? 0),
            (string) ($payload['status'] ?? OrcamentoModel::STATUS_RASCUNHO)
        );
        LogModel::registrar('orcamento_criado', 'Orçamento ' . $numero . ' criado.');
        $successMessage = 'Orçamento criado com sucesso.';
        if (!empty($autoOfertaResult['warning'])) {
            $successMessage .= ' ' . (string) $autoOfertaResult['warning'];
        }
        return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('success', $successMessage);
    }
    private function syncLinkedOsByOrcamentoStatus(int $osId, string $orcamentoStatus): void
    {
        if ($osId <= 0) {
            return;
        }

        $db = Database::connect();
        if (!$db->tableExists('os')) {
            return;
        }

        $targetStatus = match ($orcamentoStatus) {
            OrcamentoModel::STATUS_APROVADO,
            OrcamentoModel::STATUS_CONVERTIDO => 'aguardando_reparo',
            OrcamentoModel::STATUS_RASCUNHO,
            OrcamentoModel::STATUS_PENDENTE_ENVIO,
            OrcamentoModel::STATUS_ENVIADO,
            OrcamentoModel::STATUS_AGUARDANDO,
            OrcamentoModel::STATUS_AGUARDANDO_PACOTE,
            OrcamentoModel::STATUS_PACOTE_APROVADO,
            OrcamentoModel::STATUS_PENDENTE => 'aguardando_autorizacao',
            default => null,
        };
        if ($targetStatus === null) {
            return;
        }

        $currentOs = $db->table('os')
            ->select('status')
            ->where('id', $osId)
            ->get()
            ->getFirstRow('array');

        $statusFlowService = new OsStatusFlowService();
        $currentStatus = trim((string) ($currentOs['status'] ?? ''));
        if ($statusFlowService->hasAdvancedPast($currentStatus, $targetStatus)) {
            return;
        }

        $estadoFluxo = $statusFlowService->resolveEstadoFluxo($targetStatus);

        $db->table('os')
            ->where('id', $osId)
            ->where('status <>', $targetStatus)
            ->groupStart()
                ->where('estado_fluxo IS NULL', null, false)
                ->orWhereNotIn('estado_fluxo', ['encerrado', 'cancelado'])
            ->groupEnd()
            ->update([
                'status' => $targetStatus,
                'estado_fluxo' => $estadoFluxo,
                'status_atualizado_em' => date('Y-m-d H:i:s'),
            ]);
    }
    public function show($id)
    {
        $this->syncLifecycleIfDue();
        $isEmbedded = $this->isEmbedRequest();
        $orcamento = $this->findOrcamento((int) $id);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orçamento não encontrado.');
        }
        $orcamento = $this->ensurePublicToken($orcamento);
        $itens = $this->itemModel->byOrcamento((int) $id);
        $historico = $this->historicoModel->timeline((int) $id);
        $envios = $this->envioModel->byOrcamento((int) $id);
        $aprovacoes = $this->aprovacaoModel->byOrcamento((int) $id);
        $defaultWhatsappMessage = $this->buildDefaultWhatsAppMessage($orcamento);
        $defaultEmailSubject = $this->buildDefaultEmailSubject($orcamento);
        $lastPdfEnvio = $this->envioModel->latestByCanal((int) $id, 'pdf', 'gerado');
        $lastPdfRelative = trim((string) ($lastPdfEnvio['documento_path'] ?? ''));
        $lastPdfUrl = '';
        if ($lastPdfRelative !== '' && is_file(FCPATH . ltrim($lastPdfRelative, '/\\'))) {
            $lastPdfUrl = base_url($lastPdfRelative);
        }
        $this->refreshPacotesOfertasIfExpired();
        $pacotesOfertas = $this->loadPacotesOfertasByOrcamento((int) $id);
        $pacoteOfertaPrincipal = $this->resolvePacoteOfertaPrincipal($pacotesOfertas);
        $pacotesOfertasHistorico = $this->removePacoteOfertaPrincipal(
            $pacotesOfertas,
            (int) ($pacoteOfertaPrincipal['id'] ?? 0)
        );
        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        $statusOptions = $this->buildShowStatusOptions($statusAtual);
        return view('orcamentos/show', [
            'title' => 'Visualizar Orçamento',
            'orcamento' => $orcamento,
            'itens' => $itens,
            'historico' => $historico,
            'envios' => $envios,
            'aprovacoes' => $aprovacoes,
            'statusLabels' => $this->orcamentoModel->statusLabels(),
            'tipoLabels' => $this->orcamentoModel->tipoLabels(),
            'statusOptions' => $statusOptions,
            'defaultWhatsappMessage' => $defaultWhatsappMessage,
            'defaultEmailSubject' => $defaultEmailSubject,
            'lastPdfUrl' => $lastPdfUrl,
            'pacoteOfertaPrincipal' => $pacoteOfertaPrincipal,
            'pacotesOfertasHistorico' => $pacotesOfertasHistorico,
            'layout' => $isEmbedded ? 'layouts/embed' : 'layouts/main',
            'isEmbedded' => $isEmbedded,
        ]);
    }
    public function generatePdf($id)
    {
        requirePermission('orcamentos', 'visualizar');
        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orçamento não encontrado.');
        }
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $forceNew = (string) $this->request->getPost('force_new') === '1';
        $pdfResult = $this->resolvePdfDocument(
            $orcamentoId,
            $usuarioId > 0 ? $usuarioId : null,
            'geracao_manual',
            $forceNew
        );
        if (empty($pdfResult['ok'])) {
        $error = (string) ($pdfResult['message'] ?? 'Falha ao gerar PDF do orçamento.');
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('error', $error);
        }
        LogModel::registrar('orcamento_pdf_gerado', 'PDF gerado para o orcamento ID ' . $orcamentoId . '.');
        return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('success', 'PDF do orçamento gerado com sucesso.');
    }
    public function downloadPdf($id)
    {
        requirePermission('orcamentos', 'visualizar');
        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orçamento não encontrado.');
        }
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $pdfResult = $this->resolvePdfDocument(
            $orcamentoId,
            $usuarioId > 0 ? $usuarioId : null,
            'download',
            false
        );
        if (empty($pdfResult['ok'])) {
        $error = (string) ($pdfResult['message'] ?? 'Falha ao preparar o PDF do orçamento.');
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('error', $error);
        }
        $filePath = (string) ($pdfResult['path'] ?? '');
        $fileName = (string) ($pdfResult['nome_arquivo'] ?? ('orcamento_' . $orcamentoId . '.pdf'));
        if ($filePath === '' || !is_file($filePath)) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('error', 'Arquivo PDF do orçamento não encontrado no servidor.');
        }
        $content = file_get_contents($filePath);
        if ($content === false) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('error', 'Não foi possível carregar o arquivo PDF do orçamento.');
        }
        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody($content);
    }
    public function sendWhatsApp($id)
    {
        requirePermission('orcamentos', 'editar');
        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orçamento não encontrado.');
        }
        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!$this->canDispatchByStatus($statusAtual)) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Este orçamento esta bloqueado para envio no status atual.');
        }
        $orcamento = $this->ensurePublicToken($orcamento);
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $telefone = trim((string) $this->request->getPost('telefone_contato'));
        if ($telefone === '') {
            $telefone = trim((string) ($orcamento['telefone_contato'] ?? $orcamento['conversa_telefone'] ?? ''));
        }
        if (!$this->isPhoneValid($telefone)) {
            $this->saveEnvioError(
                $orcamentoId,
                'whatsapp',
                $telefone,
                null,
            'Telefone inválido para envio do orçamento.',
                $usuarioId
            );
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Telefone invalido para envio no WhatsApp.');
        }
        $mensagem = trim((string) $this->request->getPost('mensagem_whatsapp'));
        if ($mensagem === '') {
            $mensagem = $this->buildDefaultWhatsAppMessage($orcamento);
        }
        $incluirPdf = (string) $this->request->getPost('incluir_pdf') !== '0';
        $dispatch = $this->dispatchWhatsAppMessage(
            $orcamento,
            $telefone,
            $mensagem,
            $incluirPdf,
            $usuarioId
        );
        if (!empty($dispatch['ok'])) {
            LogModel::registrar('orcamento_whatsapp', 'Orçamento ID ' . $orcamentoId . ' enviado por WhatsApp.');
            $success = !empty($dispatch['duplicate'])
                ? 'Envio duplicado evitado: mensagem ja registrada recentemente no WhatsApp.'
                : 'Orçamento enviado por WhatsApp com sucesso.';
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('success', $success);
        }
        $error = (string) ($dispatch['message'] ?? 'Falha ao enviar orçamento por WhatsApp.');
        LogModel::registrar('orcamento_whatsapp_erro', 'Falha no envio WhatsApp do orcamento ID ' . $orcamentoId . '.');
        return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('error', $error);
    }
    public function sendEmail($id)
    {
        requirePermission('orcamentos', 'editar');
        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orçamento não encontrado.');
        }
        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!$this->canDispatchByStatus($statusAtual)) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Este orçamento esta bloqueado para envio no status atual.');
        }
        $orcamento = $this->ensurePublicToken($orcamento);
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $emailDestino = trim((string) $this->request->getPost('email_contato'));
        if ($emailDestino === '') {
            $emailDestino = trim((string) ($orcamento['email_contato'] ?? ''));
        }
        if ($emailDestino === '' || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
            $this->saveEnvioError(
                $orcamentoId,
                'email',
                $emailDestino,
                null,
            'E-mail de destino inválido para envio do orçamento.',
                $usuarioId
            );
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
            ->with('error', 'E-mail de destino inválido para envio do orçamento.');
        }
        $assunto = trim((string) $this->request->getPost('assunto_email'));
        if ($assunto === '') {
            $assunto = $this->buildDefaultEmailSubject($orcamento);
        }
        $mensagemLivre = trim((string) $this->request->getPost('mensagem_email'));
        $htmlBody = $this->buildDefaultEmailBody($orcamento, $mensagemLivre);
        $incluirPdf = (string) $this->request->getPost('incluir_pdf') !== '0';
        $pdfPath = null;
        $pdfRelative = null;
        $envioId = $this->startEnvioTrace(
            $orcamentoId,
            'email',
            $emailDestino,
            $mensagemLivre !== '' ? $mensagemLivre : strip_tags($htmlBody),
            null,
            $usuarioId
        );
        if ($incluirPdf) {
            $pdfResult = $this->resolvePdfDocument(
                $orcamentoId,
                $usuarioId > 0 ? $usuarioId : null,
                'anexo_email',
                false
            );
            if (empty($pdfResult['ok'])) {
                $error = (string) ($pdfResult['message'] ?? 'Falha ao gerar PDF para envio por email.');
                $this->finishEnvioTrace($envioId, 'erro', 'dompdf', null, $error, null);
                return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('error', $error);
            }
            $pdfPath = (string) ($pdfResult['path'] ?? '');
            $pdfRelative = (string) ($pdfResult['relative'] ?? '');
        }
        $mailResult = $this->mailService->send($emailDestino, $assunto, $htmlBody, $pdfPath);
        $ok = !empty($mailResult['ok']);
        $provider = (string) ($mailResult['provider'] ?? 'email');
        $erroDetalhe = $ok ? null : (string) ($mailResult['error'] ?? $mailResult['message'] ?? 'Falha ao enviar email.');
        $this->finishEnvioTrace(
            $envioId,
            $ok ? 'enviado' : 'erro',
            $provider,
            null,
            $erroDetalhe,
            $pdfRelative
        );
        if ($ok) {
            $this->markAsDispatched($orcamento, 'email', $usuarioId > 0 ? $usuarioId : null);
            LogModel::registrar('orcamento_email', 'Orçamento ID ' . $orcamentoId . ' enviado por email.');
        return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('success', 'Orçamento enviado por e-mail com sucesso.');
        }
        LogModel::registrar('orcamento_email_erro', 'Falha no envio de email do orcamento ID ' . $orcamentoId . '.');
        return redirect()->to($this->orcamentoShowUrl($orcamentoId))
            ->with('error', (string) ($mailResult['message'] ?? 'Falha ao enviar orçamento por e-mail.'));
    }
    public function sendPacoteLink($id)
    {
        requirePermission('orcamentos', 'editar');
        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }
        if (!$this->isPacoteOfertaModuleReady()) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Modulo de ofertas dinamicas de pacote nao inicializado. Execute as migracoes.');
        }
        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!$this->canDispatchByStatus($statusAtual)) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Este orcamento esta bloqueado para novo envio de link no status atual.');
        }
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $telefoneFallback = trim((string) ($orcamento['telefone_contato'] ?? $orcamento['conversa_telefone'] ?? ''));
        $intent = [
            'pacote_servico_id' => (int) ($this->request->getPost('pacote_servico_id') ?? 0),
            'telefone' => $this->normalizeWhatsAppPhone((string) ($this->request->getPost('telefone_contato') ?? $telefoneFallback)),
            'enviar_whatsapp' => (string) ($this->request->getPost('enviar_whatsapp') ?? '1') === '1',
            'mensagem' => trim((string) ($this->request->getPost('mensagem_pacote') ?? '')),
            'mensagem_personalizada_com_link' => true,
        ];
        $validationError = $this->validatePacoteOfertaAutosendIntent($intent, $orcamento);
        if ($validationError !== null) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('error', $validationError);
        }

        $this->orcamentoModel->db->transStart();
        $autoOferta = $this->createPacoteOfertaForOrcamento(
            $orcamentoId,
            $orcamento,
            $intent,
            $usuarioId > 0 ? $usuarioId : null
        );
        if (!empty($autoOferta['error'])) {
            $this->orcamentoModel->db->transRollback();
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', (string) $autoOferta['error']);
        }
        $this->orcamentoModel->db->transComplete();
        if (!$this->orcamentoModel->db->transStatus()) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Falha ao concluir o envio da oferta de pacote.');
        }

        $warning = trim((string) ($autoOferta['warning'] ?? ''));
        if ($warning !== '') {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', $warning);
        }

        return redirect()->to($this->orcamentoShowUrl($orcamentoId))
            ->with('success', 'Oferta de pacote enviada no fluxo atualizado.');
    }
    public function edit($id)
    {
        requirePermission('orcamentos', 'editar');
        $isEmbedded = $this->isEmbedRequest();
        $orcamento = $this->findOrcamento((int) $id);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orçamento nao encontrado.');
        }
        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        $allowEmbeddedLockedEdit = $this->canEditLockedOrcamentoFromOsEmbed($orcamento);
        if ($this->orcamentoModel->isLockedStatus($statusAtual) && !$allowEmbeddedLockedEdit) {
            return redirect()->to($this->orcamentoShowUrl((int) $id))
                ->with('error', 'Este orcamento esta bloqueado para edicao direta. Crie uma revisao para enviar uma nova autorizacao.');
        }
        $itens = $this->itemModel->byOrcamento((int) $id);
        return view('orcamentos/form', $this->buildFormData([
            'title' => 'Editar Orçamento',
            'orcamento' => $orcamento,
            'itens' => $itens,
            'isEdit' => true,
            'actionUrl' => base_url('orcamentos/atualizar/' . (int) $id . ($isEmbedded ? '?embed=1' : '')),
            'layout' => $isEmbedded ? 'layouts/embed' : 'layouts/main',
            'isEmbedded' => $isEmbedded,
            'statusOptions' => $this->buildEditStatusOptions($statusAtual, $allowEmbeddedLockedEdit),
            'orcamentoLockedEmbeddedEdit' => $allowEmbeddedLockedEdit,
        ]));
    }
    public function update($id)
    {
        requirePermission('orcamentos', 'editar');
        $orcamentoId = (int) $id;
        $current = $this->orcamentoModel->find($orcamentoId);
        if (!$current) {
            return redirect()->to('/orcamentos')->with('error', 'Orçamento nao encontrado.');
        }
        $statusAnterior = (string) ($current['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        $allowEmbeddedLockedEdit = $this->canEditLockedOrcamentoFromOsEmbed($current);
        if ($this->orcamentoModel->isLockedStatus($statusAnterior) && !$allowEmbeddedLockedEdit) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Este orcamento esta bloqueado para edicao direta. Crie uma revisao para enviar uma nova autorizacao.');
        }
        $payload = $this->extractOrcamentoPayload();
        $tipoValidationError = $this->validateTipoOrcamentoPayload($payload);
        if ($tipoValidationError !== null) {
            return redirect()->back()->withInput()->with('error', $tipoValidationError);
        }
        $contatoValidationError = $this->validateContatoPayload($payload);
        if ($contatoValidationError !== null) {
            return redirect()->back()->withInput()->with('error', $contatoValidationError);
        }
        $itens = $this->extractItensPayload();
        $pacoteOfertaIntent = $allowEmbeddedLockedEdit
            ? $this->buildEmbeddedLockedPacoteIntent($statusAnterior)
            : $this->extractPacoteOfertaIntent();
        $isPacoteBased = !empty($pacoteOfertaIntent['orcamento_baseado_pacote']);
        $pacoteOfertaResolution = $this->resolvePacoteOfertaForApply($pacoteOfertaIntent, array_merge($current, $payload), $orcamentoId);
        if (!empty($pacoteOfertaResolution['error'])) {
            return redirect()->back()->withInput()->with('error', (string) $pacoteOfertaResolution['error']);
        }
        $pacoteOferta = $pacoteOfertaResolution['oferta'] ?? null;
        $linkedOferta = $this->findLatestPacoteOfertaByOrcamento($orcamentoId);
        $linkedOfertaStatus = trim((string) ($linkedOferta['status'] ?? ''));
        $hasReusableLinkedOferta = $linkedOferta !== null
            && in_array($linkedOfertaStatus, ['ativo', 'enviado', 'escolhido', 'aplicado_orcamento'], true);
        $shouldCreateLinkedOferta = !$allowEmbeddedLockedEdit
            && $isPacoteBased
            && $pacoteOferta === null
            && !$hasReusableLinkedOferta;
        if (empty($itens) && $pacoteOferta === null && !$isPacoteBased) {
            return redirect()->back()->withInput()->with('error', 'Adicione pelo menos um item no orcamento.');
        }
        if ($shouldCreateLinkedOferta) {
            $autoIntentError = $this->validatePacoteOfertaAutosendIntent($pacoteOfertaIntent, array_merge($current, $payload));
            if ($autoIntentError !== null) {
                return redirect()->back()->withInput()->with('error', $autoIntentError);
            }
        }
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $now = date('Y-m-d H:i:s');
        $payload['atualizado_por'] = $usuarioId > 0 ? $usuarioId : null;
        $requestedStatus = $allowEmbeddedLockedEdit
            ? $statusAnterior
            : (string) ($payload['status'] ?: $statusAnterior);
        if (!$allowEmbeddedLockedEdit && $isPacoteBased) {
            if ($pacoteOferta !== null || in_array($linkedOfertaStatus, ['escolhido', 'aplicado_orcamento'], true)) {
                $requestedStatus = OrcamentoModel::STATUS_PACOTE_APROVADO;
            } else {
                $requestedStatus = OrcamentoModel::STATUS_AGUARDANDO_PACOTE;
            }
        }
        $payload['status'] = $allowEmbeddedLockedEdit
            ? $statusAnterior
            : $this->resolveApprovedStatus(array_merge($current, $payload), $requestedStatus);
        $payload = array_merge($payload, $this->statusTimestampColumns($payload['status'], $now, $current));
        $autoOfertaResult = ['warning' => null, 'error' => null];
        $statusNovo = (string) $payload['status'];
        if (!$this->orcamentoService->canTransition($statusAnterior, $statusNovo)) {
            return redirect()->back()->withInput()->with('error', 'Transicao de status do orcamento nao permitida.');
        }
        $this->orcamentoModel->db->transStart();
        $this->orcamentoModel->update($orcamentoId, $payload);
        $this->itemModel->where('orcamento_id', $orcamentoId)->delete();
        if (!empty($itens)) {
            $this->persistItens($orcamentoId, $itens);
        }
        if ($pacoteOferta !== null) {
            $applyError = $this->applyPacoteOfertaToOrcamento($pacoteOferta, $orcamentoId);
            if ($applyError !== null) {
                $this->orcamentoModel->db->transRollback();
                return redirect()->back()->withInput()->with('error', $applyError);
            }
        }
        if ($shouldCreateLinkedOferta) {
            $autoOfertaResult = $this->createPacoteOfertaForOrcamento(
                $orcamentoId,
                array_merge($current, $payload),
                $pacoteOfertaIntent,
                $usuarioId > 0 ? $usuarioId : null
            );
            if (!empty($autoOfertaResult['error'])) {
                $this->orcamentoModel->db->transRollback();
                return redirect()->back()->withInput()->with('error', (string) $autoOfertaResult['error']);
            }
        }
        $this->recalculateOrcamentoTotals($orcamentoId);
        if ($statusAnterior !== $statusNovo) {
            $this->orcamentoService->registrarHistoricoStatus(
                $this->historicoModel,
                $orcamentoId,
                $statusAnterior,
                $statusNovo,
                $usuarioId > 0 ? $usuarioId : null,
                'Status alterado na edicao do orcamento',
                'interno'
            );
        }
        if ($this->isConsolidatedStatus($statusNovo)) {
            $this->promoteContatoToCliente($orcamentoId, array_merge($current, $payload, ['id' => $orcamentoId]), $usuarioId > 0 ? $usuarioId : null);
        }
        $this->orcamentoModel->db->transComplete();
        if (!$this->orcamentoModel->db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Falha ao atualizar o orcamento.');
        }
        LogModel::registrar('orcamento_atualizado', 'Orçamento ID ' . $orcamentoId . ' atualizado.');
        $successMessage = 'Orçamento atualizado com sucesso.';
        if (!empty($autoOfertaResult['warning'])) {
            $successMessage .= ' ' . (string) $autoOfertaResult['warning'];
        }
        $this->syncLinkedOsByOrcamentoStatus(
            (int) ($payload['os_id'] ?? $current['os_id'] ?? 0),
            $statusNovo
        );
        return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('success', $successMessage);
    }
    public function createRevision($id)
    {
        requirePermission('orcamentos', 'editar');
        $orcamentoId = (int) $id;
        $source = $this->findOrcamento($orcamentoId);
        if (!$source) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado para revisao.');
        }
        if (!$this->canCreateRevision($source)) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'A revisao so pode ser criada a partir de um orcamento consolidado e ainda nao convertido.');
        }

        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $revisionBaseId = $this->resolveRevisionBaseId($source);
        $novaVersao = $this->nextRevisionVersion($revisionBaseId);
        $sourceItens = $this->itemModel->byOrcamento($orcamentoId);
        $payload = $this->buildRevisionPayload($source, $revisionBaseId, $novaVersao, $usuarioId > 0 ? $usuarioId : null);

        $this->orcamentoModel->db->transStart();
        $this->orcamentoModel->insert($payload);
        $revisionId = (int) $this->orcamentoModel->getInsertID();
        if ($revisionId <= 0) {
            $this->orcamentoModel->db->transRollback();
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Nao foi possivel criar a revisao deste orcamento.');
        }

        $revisionNumero = $this->orcamentoService->ensureNumero($this->orcamentoModel, $revisionId);
        foreach ($sourceItens as $item) {
            unset($item['id'], $item['created_at'], $item['updated_at']);
            $item['orcamento_id'] = $revisionId;
            $this->itemModel->insert($item);
        }

        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $revisionId,
            null,
            OrcamentoModel::STATUS_RASCUNHO,
            $usuarioId > 0 ? $usuarioId : null,
            'Revisao criada a partir do orcamento ' . (string) ($source['numero'] ?? ('#' . $orcamentoId)) . '.',
            'interno'
        );
        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $orcamentoId,
            (string) ($source['status'] ?? OrcamentoModel::STATUS_RASCUNHO),
            (string) ($source['status'] ?? OrcamentoModel::STATUS_RASCUNHO),
            $usuarioId > 0 ? $usuarioId : null,
            'Revisao v' . $novaVersao . ' criada: ' . ($revisionNumero !== '' ? $revisionNumero : ('#' . $revisionId)) . '.',
            'interno'
        );
        $this->orcamentoModel->db->transComplete();

        if (!$this->orcamentoModel->db->transStatus()) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Falha ao concluir a criacao da revisao.');
        }

        LogModel::registrar(
            'orcamento_revisao_criada',
            'Revisao v' . $novaVersao . ' criada para o orcamento ID ' . $orcamentoId . ' (novo ID ' . $revisionId . ').'
        );

        return redirect()->to($this->withEmbedQuery('/orcamentos/editar/' . $revisionId))
            ->with('success', 'Revisao criada com sucesso. Ajuste os dados e envie a nova autorizacao ao cliente.');
    }
    public function updateStatus($id)
    {
        requirePermission('orcamentos', 'editar');
        $orcamentoId = (int) $id;
        $orcamento = $this->orcamentoModel->find($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orçamento nao encontrado.');
        }
        $statusNovo = trim((string) $this->request->getPost('status'));
        if (!array_key_exists($statusNovo, $this->orcamentoModel->statusLabels())) {
            return redirect()->back()->with('error', 'Status informado para o orcamento e invalido.');
        }
        $statusAnterior = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        $allowedStatusOptions = $this->buildShowStatusOptions($statusAnterior);
        if (!array_key_exists($statusNovo, $allowedStatusOptions)) {
            return redirect()->back()->with('error', 'Transicao bloqueada para preservar o fluxo operacional deste orcamento.');
        }
        $statusNovo = $this->resolveApprovedStatus($orcamento, $statusNovo);
        if ($statusAnterior === $statusNovo) {
            return redirect()->back()->with('success', 'Status mantido sem alteracoes.');
        }
        if (!$this->orcamentoService->canTransition($statusAnterior, $statusNovo)) {
            return redirect()->back()->with('error', 'Transicao de status do orcamento nao permitida.');
        }
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $now = date('Y-m-d H:i:s');
        $update = [
            'status' => $statusNovo,
            'atualizado_por' => $usuarioId > 0 ? $usuarioId : null,
        ];
        $update = array_merge($update, $this->statusTimestampColumns($statusNovo, $now, $orcamento));
        $this->orcamentoModel->update($orcamentoId, $update);
        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $orcamentoId,
            $statusAnterior,
            $statusNovo,
            $usuarioId > 0 ? $usuarioId : null,
            'Status alterado manualmente',
            'interno'
        );
        if ($this->isConsolidatedStatus($statusNovo)) {
            $this->promoteContatoToCliente($orcamentoId, array_merge($orcamento, $update, ['id' => $orcamentoId]), $usuarioId > 0 ? $usuarioId : null);
        }
        LogModel::registrar('orcamento_status', 'Orçamento ID ' . $orcamentoId . ' alterado para ' . $statusNovo . '.');
        $this->syncLinkedOsByOrcamentoStatus(
            (int) ($orcamento['os_id'] ?? 0),
            $statusNovo
        );
        return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('success', 'Status atualizado com sucesso.');
    }
    public function runAutomation()
    {
        requirePermission('orcamentos', 'editar');
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $summary = $this->lifecycleService->runAutomations($usuarioId > 0 ? $usuarioId : null);
        $mensagem = sprintf(
            'Automacao executada. Vencidos: %d | Follow-ups aguardando: %d | Follow-ups vencidos: %d | Pendente OS: %d.',
            (int) ($summary['orcamentos_vencidos'] ?? 0),
            (int) ($summary['followups_aguardando'] ?? 0),
            (int) ($summary['followups_vencidos'] ?? 0),
            (int) ($summary['followups_pendente_os'] ?? 0)
        );
        LogModel::registrar('orcamento_automacao_manual', $mensagem);
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok' => true,
                'message' => $mensagem,
                'summary' => $summary,
            ]);
        }
        return redirect()->to('/orcamentos')->with('success', $mensagem);
    }
    public function convert($id)
    {
        requirePermission('orcamentos', 'editar');
        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orçamento nao encontrado.');
        }
        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!in_array($statusAtual, [OrcamentoModel::STATUS_APROVADO, OrcamentoModel::STATUS_PENDENTE_OS, OrcamentoModel::STATUS_PACOTE_APROVADO], true)) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'A conversao exige orcamento aprovado.');
        }
        $tipo = strtolower(trim((string) $this->request->getPost('tipo')));
        if (!in_array($tipo, ['os', 'venda'], true)) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Tipo de conversao invalido.');
        }
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $now = date('Y-m-d H:i:s');
        $this->promoteContatoToCliente($orcamentoId, $orcamento, $usuarioId > 0 ? $usuarioId : null);
        $orcamento = $this->findOrcamento($orcamentoId) ?? $orcamento;
        if ($tipo === 'os') {
            $itens = $this->itemModel->byOrcamento($orcamentoId);
            $conversion = $this->conversaoService->convertToOs($orcamento, $itens, $usuarioId > 0 ? $usuarioId : null);
            if (empty($conversion['ok'])) {
                return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                    ->with('error', (string) ($conversion['message'] ?? 'Falha ao converter orcamento para OS.'));
            }
            $osId = (int) ($conversion['os_id'] ?? 0);
            $this->orcamentoModel->update($orcamentoId, [
                'status' => OrcamentoModel::STATUS_CONVERTIDO,
                'tipo_orcamento' => OrcamentoModel::TIPO_ASSISTENCIA,
                'os_id' => $osId > 0 ? $osId : ((int) ($orcamento['os_id'] ?? 0) ?: null),
                'convertido_tipo' => 'os',
                'convertido_id' => $osId > 0 ? $osId : null,
                'atualizado_por' => $usuarioId > 0 ? $usuarioId : null,
            ]);
            $this->orcamentoService->registrarHistoricoStatus(
                $this->historicoModel,
                $orcamentoId,
                $statusAtual,
                OrcamentoModel::STATUS_CONVERTIDO,
                $usuarioId > 0 ? $usuarioId : null,
                'Orçamento convertido para OS #' . ($osId > 0 ? $osId : '-'),
                'interno'
            );
            LogModel::registrar('orcamento_convertido_os', 'Orçamento ID ' . $orcamentoId . ' convertido para OS.');
            $mensagem = trim((string) ($conversion['message'] ?? 'Orçamento convertido para OS com sucesso.'));
            $this->syncLinkedOsByOrcamentoStatus(
                $osId > 0 ? $osId : (int) ($orcamento['os_id'] ?? 0),
                OrcamentoModel::STATUS_CONVERTIDO
            );
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))->with('success', $mensagem);
        }
        $this->orcamentoModel->update($orcamentoId, [
            'status' => OrcamentoModel::STATUS_CONVERTIDO,
            'convertido_tipo' => 'venda_manual',
            'convertido_id' => null,
            'atualizado_por' => $usuarioId > 0 ? $usuarioId : null,
            'updated_at' => $now,
        ]);
        $this->promoteContatoToCliente($orcamentoId, null, $usuarioId > 0 ? $usuarioId : null);
        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $orcamentoId,
            $statusAtual,
            OrcamentoModel::STATUS_CONVERTIDO,
            $usuarioId > 0 ? $usuarioId : null,
            'Orçamento convertido para venda manual.',
            'interno'
        );
        LogModel::registrar('orcamento_convertido_venda', 'Orçamento ID ' . $orcamentoId . ' convertido para venda manual.');
        $this->syncLinkedOsByOrcamentoStatus(
            (int) ($orcamento['os_id'] ?? 0),
            OrcamentoModel::STATUS_CONVERTIDO
        );
        return redirect()->to($this->orcamentoShowUrl($orcamentoId))
            ->with('success', 'Orçamento convertido para venda manual com sucesso.');
    }
    public function quickCreateAndSendFromConversa()
    {
        requirePermission('orcamentos', 'criar');
        requirePermission('orcamentos', 'editar');
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(405)->setJSON([
                'ok' => false,
                'message' => 'Metodo nao permitido para este endpoint.',
            ]);
        }
        $conversaId = (int) ($this->request->getPost('conversa_id') ?? 0);
        if ($conversaId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Conversa inválida para gerar orçamento rápido.',
            ]);
        }
        $conversa = (new ConversaWhatsappModel())->find($conversaId);
        if (!$conversa) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Conversa nao encontrada.',
            ]);
        }
        $itemDescricao = trim((string) $this->request->getPost('item_descricao'));
        if ($itemDescricao === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Descrição do item obrigatória para orçamento rápido.',
            ]);
        }
        $itemValor = max(0, $this->orcamentoService->normalizeMoney($this->request->getPost('item_valor')));
        if ($itemValor <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Valor do item deve ser maior que zero.',
            ]);
        }
        $telefone = trim((string) $this->request->getPost('telefone_contato'));
        if ($telefone === '') {
            $telefone = trim((string) ($conversa['telefone'] ?? ''));
        }
        if (!$this->isPhoneValid($telefone)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Telefone da conversa inválido para envio automático.',
            ]);
        }
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $quantidade = max(1, $this->orcamentoService->normalizeQuantity($this->request->getPost('item_quantidade') ?? 1));
        $itemTotal = round($quantidade * $itemValor, 2);
        $itens = [[
            'tipo_item' => trim((string) ($this->request->getPost('item_tipo') ?? 'servico')) ?: 'servico',
            'descricao' => $itemDescricao,
            'quantidade' => $quantidade,
            'valor_unitario' => round($itemValor, 2),
            'desconto' => 0,
            'acrescimo' => 0,
            'total' => $itemTotal,
            'ordem' => 1,
            'observacoes' => null,
        ]];
        $totais = $this->orcamentoService->calculateTotals($itens, 0, 0);
        $clienteId = (int) ($this->request->getPost('cliente_id') ?? 0);
        if ($clienteId <= 0) {
            $clienteId = (int) ($conversa['cliente_id'] ?? 0);
        }
        $osId = (int) ($this->request->getPost('os_id') ?? 0);
        if ($osId <= 0) {
            $osId = (int) ($conversa['os_id_principal'] ?? 0);
        }
        $equipamentoId = (int) ($this->request->getPost('equipamento_id') ?? 0);
        if ($osId > 0) {
            $osVinculada = (new OsModel())->find($osId);
            if ($osVinculada) {
                if ($clienteId <= 0) {
                    $clienteId = (int) ($osVinculada['cliente_id'] ?? 0);
                }
                if ($equipamentoId <= 0) {
                    $equipamentoId = (int) ($osVinculada['equipamento_id'] ?? 0);
                }
            }
        }
        $titulo = trim((string) $this->request->getPost('titulo'));
        if ($titulo === '') {
            $titulo = $osId > 0
                ? 'Orçamento rápido para OS #' . $osId
                : 'Orçamento rápido via conversa #' . $conversaId;
        }
        $validadeDias = (int) ($this->request->getPost('validade_dias') ?? 10);
        if ($validadeDias < 1) {
            $validadeDias = 10;
        }
        if ($validadeDias > 60) {
            $validadeDias = 60;
        }
        $clienteNomeAvulso = trim((string) $this->request->getPost('cliente_nome_avulso'));
        if ($clienteNomeAvulso === '') {
            $clienteNomeAvulso = trim((string) ($conversa['nome_contato'] ?? 'Cliente eventual'));
        }
        $payload = [
            'status' => OrcamentoModel::STATUS_RASCUNHO,
            'versao' => 1,
            'tipo_orcamento' => $this->orcamentoModel->normalizeTipo(
                (string) $this->request->getPost('tipo_orcamento'),
                $osId > 0 ? $osId : null
            ),
            'origem' => 'conversa',
            'cliente_id' => $clienteId > 0 ? $clienteId : null,
            'contato_id' => (int) ($conversa['contato_id'] ?? 0) ?: null,
            'cliente_nome_avulso' => $clienteId > 0 ? null : $clienteNomeAvulso,
            'telefone_contato' => $telefone,
            'email_contato' => trim((string) $this->request->getPost('email_contato')) ?: null,
            'os_id' => $osId > 0 ? $osId : null,
            'equipamento_id' => $equipamentoId > 0 ? $equipamentoId : null,
            'conversa_id' => $conversaId,
            'responsavel_id' => $usuarioId > 0 ? $usuarioId : null,
            'criado_por' => $usuarioId > 0 ? $usuarioId : null,
            'atualizado_por' => $usuarioId > 0 ? $usuarioId : null,
            'titulo' => $titulo,
            'validade_dias' => $validadeDias,
            'validade_data' => date('Y-m-d', strtotime('+' . $validadeDias . ' days')),
            'subtotal' => $totais['subtotal'],
            'desconto' => 0,
            'acrescimo' => 0,
            'total' => $totais['total'],
            'prazo_execucao' => null,
            'observacoes' => trim((string) $this->request->getPost('observacoes')) ?: null,
            'condicoes' => trim((string) $this->request->getPost('condicoes')) ?: null,
            'motivo_rejeicao' => null,
            'token_publico' => $this->orcamentoService->generateToken(),
            'token_expira_em' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ];
        $this->orcamentoModel->db->transStart();
        $this->orcamentoModel->insert($payload);
        $orcamentoId = (int) $this->orcamentoModel->getInsertID();
        if ($orcamentoId <= 0) {
            $this->orcamentoModel->db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Não foi possível salvar o orçamento rápido.',
            ]);
        }
        $numero = $this->orcamentoService->ensureNumero($this->orcamentoModel, $orcamentoId);
        $this->persistItens($orcamentoId, $itens);
        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $orcamentoId,
            null,
            OrcamentoModel::STATUS_RASCUNHO,
            $usuarioId > 0 ? $usuarioId : null,
            'Criação rápida pela Central de Mensagens',
            'interno'
        );
        $this->orcamentoModel->db->transComplete();
        if (!$this->orcamentoModel->db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Falha ao concluir gravação do orçamento rápido.',
            ]);
        }
        $this->syncLinkedOsByOrcamentoStatus($osId, OrcamentoModel::STATUS_RASCUNHO);
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Orçamento criado, mas não foi possível carregar o registro.',
                'orcamento_id' => $orcamentoId,
            ]);
        }
        $orcamento = $this->ensurePublicToken($orcamento);
        $mensagem = trim((string) $this->request->getPost('mensagem_whatsapp'));
        if ($mensagem === '') {
            $mensagem = $this->buildDefaultWhatsAppMessage($orcamento);
        }
        $incluirPdf = (string) $this->request->getPost('incluir_pdf') !== '0';
        $dispatch = $this->dispatchWhatsAppMessage(
            $orcamento,
            $telefone,
            $mensagem,
            $incluirPdf,
            $usuarioId
        );
        if (empty($dispatch['ok'])) {
            return $this->response->setStatusCode(502)->setJSON([
                'ok' => false,
                'message' => (string) ($dispatch['message'] ?? 'Orçamento criado, mas houve falha no envio por WhatsApp.'),
                'orcamento_id' => $orcamentoId,
                'numero' => $numero,
                'view_url' => base_url('orcamentos/visualizar/' . $orcamentoId),
            ]);
        }
        LogModel::registrar('orcamento_conversa_rapida', 'Orçamento rápido ' . $numero . ' criado e enviado pela conversa #' . $conversaId . '.');
        return $this->response->setJSON([
            'ok' => true,
            'message' => !empty($dispatch['duplicate'])
                ? 'Orçamento ' . $numero . ' gerado. Envio duplicado foi evitado automaticamente.'
                : 'Orçamento ' . $numero . ' gerado e enviado com sucesso.',
            'orcamento_id' => $orcamentoId,
            'numero' => $numero,
            'view_url' => base_url('orcamentos/visualizar/' . $orcamentoId),
            'public_url' => !empty($orcamento['token_publico']) ? base_url('orcamento/' . $orcamento['token_publico']) : null,
        ]);
    }
    public function delete($id)
    {
        requirePermission('orcamentos', 'excluir');
        $orcamentoId = (int) $id;
        $orcamento = $this->orcamentoModel->find($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orçamento nao encontrado.');
        }
        $status = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!in_array($status, [OrcamentoModel::STATUS_RASCUNHO, OrcamentoModel::STATUS_CANCELADO, OrcamentoModel::STATUS_REJEITADO], true)) {
            return redirect()->to($this->orcamentoShowUrl($orcamentoId))
                ->with('error', 'Somente orcamentos em rascunho, cancelado ou rejeitado podem ser excluidos.');
        }
        $this->orcamentoModel->delete($orcamentoId);
        LogModel::registrar('orcamento_excluido', 'Orçamento ID ' . $orcamentoId . ' excluido.');
        return redirect()->to('/orcamentos')->with('success', 'Orçamento excluido com sucesso.');
    }
    private function canDispatchByStatus(string $status): bool
    {
        return !in_array($status, [
            OrcamentoModel::STATUS_APROVADO,
            OrcamentoModel::STATUS_PENDENTE_OS,
            OrcamentoModel::STATUS_CANCELADO,
            OrcamentoModel::STATUS_CONVERTIDO,
        ], true);
    }
    /**
     * @return array<string,mixed>
     */
    private function dispatchWhatsAppMessage(
        array $orcamento,
        string $telefone,
        string $mensagem,
        bool $incluirPdf,
        int $usuarioId = 0
    ): array {
        $orcamentoId = (int) ($orcamento['id'] ?? 0);
        if ($orcamentoId <= 0) {
            return [
                'ok' => false,
                'message' => 'Orçamento invalido para envio no WhatsApp.',
            ];
        }
        $pdfPath = null;
        $pdfRelative = null;
        $envioId = $this->startEnvioTrace(
            $orcamentoId,
            'whatsapp',
            $telefone,
            $mensagem,
            null,
            $usuarioId
        );
        if ($incluirPdf) {
            $pdfResult = $this->resolvePdfDocument(
                $orcamentoId,
                $usuarioId > 0 ? $usuarioId : null,
                'anexo_whatsapp',
                false
            );
            if (empty($pdfResult['ok'])) {
                $error = (string) ($pdfResult['message'] ?? 'Falha ao gerar PDF para envio no WhatsApp.');
                $this->finishEnvioTrace($envioId, 'erro', 'dompdf', null, $error, null);
                return [
                    'ok' => false,
                    'message' => $error,
                ];
            }
            $pdfPath = (string) ($pdfResult['path'] ?? '');
            $pdfRelative = (string) ($pdfResult['relative'] ?? '');
        }
        $whatsService = new WhatsAppService();
        $result = $whatsService->sendRaw(
            (int) ($orcamento['os_id'] ?? 0),
            (int) ($orcamento['cliente_id'] ?? 0),
            $telefone,
            $mensagem,
            'orcamento_envio',
            null,
            $usuarioId > 0 ? $usuarioId : null,
            [
                'arquivo_path' => $pdfPath,
                'arquivo' => $pdfRelative,
                'conversa_id' => (int) ($orcamento['conversa_id'] ?? 0),
            ]
        );
        $ok = !empty($result['ok']);
        $duplicate = !empty($result['duplicate']);
        $statusEnvio = $ok ? ($duplicate ? 'duplicado' : 'enviado') : 'erro';
        $provider = (string) ($result['provider'] ?? get_config('whatsapp_direct_provider', get_config('whatsapp_provider', 'menuia')));
        $referencia = (string) ($result['message_id'] ?? '');
        $erroDetalhe = $ok ? null : (string) ($result['message'] ?? 'Falha ao enviar WhatsApp.');
        $this->finishEnvioTrace(
            $envioId,
            $statusEnvio,
            $provider,
            $referencia !== '' ? $referencia : null,
            $erroDetalhe,
            $pdfRelative
        );
        if ($ok) {
            $this->markAsDispatched($orcamento, 'whatsapp', $usuarioId > 0 ? $usuarioId : null);
        }
        return [
            'ok' => $ok,
            'duplicate' => $duplicate,
            'message' => $erroDetalhe ?: '',
            'raw' => $result,
        ];
    }
    private function markAsDispatched(array $orcamento, string $canal, ?int $usuarioId = null): void
    {
        $orcamentoId = (int) ($orcamento['id'] ?? 0);
        if ($orcamentoId <= 0) {
            return;
        }
        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        $statusNovo = $statusAtual;
        if (in_array($statusAtual, [
            OrcamentoModel::STATUS_RASCUNHO,
            OrcamentoModel::STATUS_PENDENTE_ENVIO,
            OrcamentoModel::STATUS_ENVIADO,
            OrcamentoModel::STATUS_AGUARDANDO,
            OrcamentoModel::STATUS_REJEITADO,
            OrcamentoModel::STATUS_VENCIDO,
        ], true)) {
            $statusNovo = OrcamentoModel::STATUS_AGUARDANDO;
        }
        $update = [
            'atualizado_por' => $usuarioId,
        ];
        if ($statusNovo !== $statusAtual) {
            $update['status'] = $statusNovo;
        }
        if (empty($orcamento['enviado_em'])) {
            $update['enviado_em'] = date('Y-m-d H:i:s');
        }
        $this->orcamentoModel->update($orcamentoId, $update);
        if ($statusNovo !== $statusAtual) {
            $this->orcamentoService->registrarHistoricoStatus(
                $this->historicoModel,
                $orcamentoId,
                $statusAtual,
                $statusNovo,
                $usuarioId,
                'Status atualizado apos envio via ' . strtoupper($canal),
                'interno'
            );
        }
        $this->syncLinkedOsByOrcamentoStatus(
            (int) ($orcamento['os_id'] ?? 0),
            $statusNovo
        );
    }
    private function syncLifecycleIfDue(): void
    {
        $lastRun = (int) (session()->get('orcamentos_lifecycle_last_run') ?? 0);
        if ($lastRun > 0 && (time() - $lastRun) < 180) {
            return;
        }
        try {
            $usuarioId = (int) (session()->get('user_id') ?? 0);
            $this->lifecycleService->runAutomations($usuarioId > 0 ? $usuarioId : null);
            session()->set('orcamentos_lifecycle_last_run', time());
        } catch (\Throwable $e) {
            log_message('warning', '[Orcamentos] automacao de vencimento/follow-up falhou: ' . $e->getMessage());
        }
    }
    /**
     * @param array<string,mixed> $scope
     */
    private function resolveApprovedStatus(array $scope, string $status): string
    {
        if ($status !== OrcamentoModel::STATUS_APROVADO) {
            return $status;
        }
        $tipoOrcamento = $this->orcamentoModel->normalizeTipo(
            (string) ($scope['tipo_orcamento'] ?? ''),
            (int) ($scope['os_id'] ?? 0) ?: null
        );

        return $this->orcamentoModel->isTipoAssistencia($tipoOrcamento, (int) ($scope['os_id'] ?? 0) ?: null)
            ? OrcamentoModel::STATUS_APROVADO
            : OrcamentoModel::STATUS_PENDENTE_OS;
    }
    private function normalizeOrigemOrcamento(string $origem): string
    {
        $origem = strtolower(trim($origem));
        if ($origem === 'conversa_rapida') {
            $origem = 'conversa';
        }

        return in_array($origem, ['manual', 'os', 'conversa', 'cliente'], true)
            ? $origem
            : 'manual';
    }
    /**
     * @param array<string,mixed> $payload
     */
    private function validateTipoOrcamentoPayload(array $payload): ?string
    {
        $tipoOrcamento = $this->orcamentoModel->normalizeTipo(
            (string) ($payload['tipo_orcamento'] ?? ''),
            (int) ($payload['os_id'] ?? 0) ?: null
        );
        if ($this->orcamentoModel->isTipoAssistencia($tipoOrcamento, (int) ($payload['os_id'] ?? 0) ?: null)
            && (int) ($payload['os_id'] ?? 0) <= 0) {
            return 'Orcamento com equipamento na assistencia exige uma OS aberta vinculada.';
        }

        return null;
    }
    /**
     * @param array<string,mixed> $orcamento
     * @return array<string,mixed>
     */
    private function normalizeOrcamentoRecord(array $orcamento): array
    {
        $orcamento['tipo_orcamento'] = $this->orcamentoModel->normalizeTipo(
            (string) ($orcamento['tipo_orcamento'] ?? ''),
            (int) ($orcamento['os_id'] ?? 0) ?: null
        );
        $orcamento['origem'] = $this->normalizeOrigemOrcamento((string) ($orcamento['origem'] ?? 'manual'));
        $orcamento['versao'] = max(1, (int) ($orcamento['versao'] ?? 1));
        $status = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if ($status === OrcamentoModel::STATUS_ENVIADO && array_key_exists('envios_confirmados', $orcamento)) {
            $confirmados = (int) ($orcamento['envios_confirmados'] ?? 0);
            if ($confirmados <= 0) {
                $status = OrcamentoModel::STATUS_PENDENTE_ENVIO;
            }
        }
        $orcamento['status'] = $status;

        return $orcamento;
    }
    /**
     * @param array<string,mixed> $orcamento
     */
    private function canCreateRevision(array $orcamento): bool
    {
        $status = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);

        return in_array($status, [
            OrcamentoModel::STATUS_APROVADO,
            OrcamentoModel::STATUS_PENDENTE_OS,
            OrcamentoModel::STATUS_PACOTE_APROVADO,
            OrcamentoModel::STATUS_REJEITADO,
            OrcamentoModel::STATUS_VENCIDO,
        ], true);
    }
    /**
     * @param array<string,mixed> $orcamento
     */
    private function resolveRevisionBaseId(array $orcamento): int
    {
        $baseId = (int) ($orcamento['orcamento_revisao_de_id'] ?? 0);
        if ($baseId > 0) {
            return $baseId;
        }

        return (int) ($orcamento['id'] ?? 0);
    }
    private function nextRevisionVersion(int $revisionBaseId): int
    {
        if ($revisionBaseId <= 0) {
            return 2;
        }

        $row = $this->orcamentoModel
            ->selectMax('versao')
            ->groupStart()
                ->where('id', $revisionBaseId)
                ->orWhere('orcamento_revisao_de_id', $revisionBaseId)
            ->groupEnd()
            ->first();

        return max(2, ((int) ($row['versao'] ?? 1)) + 1);
    }
    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    private function buildRevisionPayload(array $source, int $revisionBaseId, int $novaVersao, ?int $usuarioId = null): array
    {
        $payload = $this->normalizeOrcamentoRecord($source);
        unset(
            $payload['id'],
            $payload['cliente_nome'],
            $payload['numero_os'],
            $payload['conversa_telefone'],
            $payload['contato_nome'],
            $payload['contato_telefone'],
            $payload['contato_email'],
            $payload['revisao_base_numero'],
            $payload['revisao_base_versao']
        );

        $payload['numero'] = null;
        $payload['versao'] = max(2, $novaVersao);
        $payload['status'] = OrcamentoModel::STATUS_RASCUNHO;
        $payload['orcamento_revisao_de_id'] = $revisionBaseId > 0 ? $revisionBaseId : null;
        $payload['token_publico'] = $this->orcamentoService->generateToken();
        $payload['token_expira_em'] = date('Y-m-d H:i:s', strtotime('+30 days'));
        $payload['enviado_em'] = null;
        $payload['aprovado_em'] = null;
        $payload['rejeitado_em'] = null;
        $payload['cancelado_em'] = null;
        $payload['motivo_rejeicao'] = null;
        $payload['convertido_tipo'] = null;
        $payload['convertido_id'] = null;
        $payload['criado_por'] = $usuarioId > 0 ? $usuarioId : ((int) ($source['criado_por'] ?? 0) ?: null);
        $payload['atualizado_por'] = $usuarioId > 0 ? $usuarioId : ((int) ($source['atualizado_por'] ?? 0) ?: null);
        $payload['created_at'] = null;
        $payload['updated_at'] = null;

        return $payload;
    }
    private function ensurePublicToken(array $orcamento): array
    {
        $orcamentoId = (int) ($orcamento['id'] ?? 0);
        if ($orcamentoId <= 0) {
            return $orcamento;
        }
        $token = trim((string) ($orcamento['token_publico'] ?? ''));
        $expiraEm = trim((string) ($orcamento['token_expira_em'] ?? ''));
        $expirado = $expiraEm !== '' && strtotime($expiraEm) < time();
        if ($token !== '' && !$expirado) {
            return $orcamento;
        }
        $novoToken = $this->orcamentoService->generateToken();
        $novoExpiraEm = date('Y-m-d H:i:s', strtotime('+30 days'));
        $this->orcamentoModel->update($orcamentoId, [
            'token_publico' => $novoToken,
            'token_expira_em' => $novoExpiraEm,
        ]);
        $orcamento['token_publico'] = $novoToken;
        $orcamento['token_expira_em'] = $novoExpiraEm;
        return $orcamento;
    }
    private function resolvePdfDocument(
        int $orcamentoId,
        ?int $usuarioId,
        string $contexto,
        bool $forceGenerate = false
    ): array {
        if (!$forceGenerate) {
            $latest = $this->envioModel->latestByCanal($orcamentoId, 'pdf', 'gerado');
            $relative = trim((string) ($latest['documento_path'] ?? ''));
            if ($relative !== '') {
                $fullPath = FCPATH . ltrim($relative, '/\\');
                if (is_file($fullPath)) {
                    return [
                        'ok' => true,
                        'path' => $fullPath,
                        'relative' => $relative,
                        'url' => base_url($relative),
                        'nome_arquivo' => basename($fullPath),
                        'generated' => false,
                    ];
                }
            }
        }
        $envioId = $this->startEnvioTrace(
            $orcamentoId,
            'pdf',
            'interno:' . $contexto,
            'Geracao de PDF do orcamento (' . $contexto . ')',
            null,
            $usuarioId ?? 0
        );
        $result = $this->pdfService->gerar($orcamentoId, $usuarioId);
        if (empty($result['ok'])) {
            $error = (string) ($result['message'] ?? 'Falha ao gerar PDF do orcamento.');
            $this->finishEnvioTrace($envioId, 'erro', 'dompdf', null, $error, null);
            return [
                'ok' => false,
                'message' => $error,
            ];
        }
        $documentoPath = (string) ($result['relative'] ?? '');
        $referencia = 'v' . (string) ($result['versao'] ?? '1');
        $this->finishEnvioTrace(
            $envioId,
            'gerado',
            'dompdf',
            $referencia,
            null,
            $documentoPath
        );
        return [
            'ok' => true,
            'path' => (string) ($result['path'] ?? ''),
            'relative' => $documentoPath,
            'url' => (string) ($result['url'] ?? ''),
            'nome_arquivo' => (string) ($result['nome_arquivo'] ?? ''),
            'generated' => true,
        ];
    }
    private function startEnvioTrace(
        int $orcamentoId,
        string $canal,
        ?string $destino,
        ?string $mensagem,
        ?string $documentoPath,
        int $usuarioId = 0
    ): ?int {
        $insertId = $this->envioModel->insert([
            'orcamento_id' => $orcamentoId,
            'canal' => trim($canal),
            'destino' => $destino !== null && trim($destino) !== '' ? trim($destino) : null,
            'mensagem' => $mensagem !== null && trim($mensagem) !== '' ? trim($mensagem) : null,
            'documento_path' => $documentoPath !== null && trim($documentoPath) !== '' ? trim($documentoPath) : null,
            'status' => 'pendente',
            'provedor' => null,
            'referencia_externa' => null,
            'erro_detalhe' => null,
            'enviado_por' => $usuarioId > 0 ? $usuarioId : null,
            'enviado_em' => date('Y-m-d H:i:s'),
        ], true);
        return $insertId ? (int) $insertId : null;
    }
    private function finishEnvioTrace(
        ?int $envioId,
        string $status,
        ?string $provedor = null,
        ?string $referenciaExterna = null,
        ?string $erroDetalhe = null,
        ?string $documentoPath = null
    ): void {
        if (empty($envioId) || $envioId <= 0) {
            return;
        }
        $update = [
            'status' => trim($status) !== '' ? trim($status) : 'erro',
            'provedor' => $provedor !== null && trim($provedor) !== '' ? trim($provedor) : null,
            'referencia_externa' => $referenciaExterna !== null && trim($referenciaExterna) !== '' ? trim($referenciaExterna) : null,
            'erro_detalhe' => $erroDetalhe !== null && trim($erroDetalhe) !== '' ? trim($erroDetalhe) : null,
            'enviado_em' => date('Y-m-d H:i:s'),
        ];
        if ($documentoPath !== null && trim($documentoPath) !== '') {
            $update['documento_path'] = trim($documentoPath);
        }
        $this->envioModel->update($envioId, $update);
    }
    private function saveEnvioError(
        int $orcamentoId,
        string $canal,
        ?string $destino,
        ?string $mensagem,
        string $erroDetalhe,
        int $usuarioId = 0
    ): void {
        $envioId = $this->startEnvioTrace(
            $orcamentoId,
            $canal,
            $destino,
            $mensagem,
            null,
            $usuarioId
        );
        $this->finishEnvioTrace($envioId, 'erro', null, null, $erroDetalhe, null);
    }
    private function buildDefaultWhatsAppMessage(array $orcamento): string
    {
        $cliente = $this->resolveClienteNome($orcamento);
        $numero = trim((string) ($orcamento['numero'] ?? '#'));
        $total = formatMoney($orcamento['total'] ?? 0);
        $validade = formatDate($orcamento['validade_data'] ?? null);
        $tipoOrcamento = $this->orcamentoModel->normalizeTipo(
            (string) ($orcamento['tipo_orcamento'] ?? ''),
            (int) ($orcamento['os_id'] ?? 0) ?: null
        );
        $mensagem = 'Ola ' . $cliente . ', segue o orcamento ' . $numero . ' no valor total de ' . $total . '.';
        if ($tipoOrcamento === OrcamentoModel::TIPO_PREVIO) {
            $mensagem .= "\nEste documento representa uma estimativa inicial, sujeita a confirmacao apos a analise presencial do equipamento.";
        } else {
            $mensagem .= "\nEste documento considera o equipamento ja recebido em assistencia e a analise tecnica realizada.";
        }
        if ($validade !== '-') {
            $mensagem .= "\nValidade: " . $validade . '.';
        }
        if (!empty($orcamento['token_publico'])) {
            $mensagem .= "\nAprovacao online: " . base_url('orcamento/' . $orcamento['token_publico']);
        }
        $mensagem .= "\nFico a disposicao para qualquer duvida.";
        return $mensagem;
    }
    private function buildDefaultEmailSubject(array $orcamento): string
    {
        $numero = trim((string) ($orcamento['numero'] ?? '#'));
        $empresa = trim((string) get_config('empresa_nome', 'Assistencia Tecnica'));
        return 'Orçamento ' . $numero . ' - ' . $empresa;
    }
    private function buildDefaultEmailBody(array $orcamento, string $mensagemLivre = ''): string
    {
        $cliente = htmlspecialchars($this->resolveClienteNome($orcamento), ENT_QUOTES, 'UTF-8');
        $numero = htmlspecialchars((string) ($orcamento['numero'] ?? '#'), ENT_QUOTES, 'UTF-8');
        $total = htmlspecialchars(formatMoney($orcamento['total'] ?? 0), ENT_QUOTES, 'UTF-8');
        $validade = htmlspecialchars(formatDate($orcamento['validade_data'] ?? null), ENT_QUOTES, 'UTF-8');
        $tipoOrcamento = $this->orcamentoModel->normalizeTipo(
            (string) ($orcamento['tipo_orcamento'] ?? ''),
            (int) ($orcamento['os_id'] ?? 0) ?: null
        );
        $tipoResumo = $tipoOrcamento === OrcamentoModel::TIPO_PREVIO
            ? 'Esta proposta funciona como uma estimativa inicial e pode ser ajustada apos a analise do equipamento.'
            : 'Esta proposta reflete o diagnostico tecnico do equipamento ja recebido em assistencia.';
        $mensagemPersonalizada = trim($mensagemLivre);
        $mensagemHtml = '';
        if ($mensagemPersonalizada !== '') {
            $mensagemHtml = '<p style="margin:0 0 12px;">' .
                nl2br(htmlspecialchars($mensagemPersonalizada, ENT_QUOTES, 'UTF-8')) .
                '</p>';
        }
        $linkPublico = '';
        if (!empty($orcamento['token_publico'])) {
            $safeLink = htmlspecialchars(base_url('orcamento/' . $orcamento['token_publico']), ENT_QUOTES, 'UTF-8');
            $linkPublico = '<p style="margin:12px 0 0;"><strong>Aprovacao online:</strong> <a href="' . $safeLink . '">' . $safeLink . '</a></p>';
        }
        return
            '<div style="font-family:Arial,sans-serif;color:#1f2937;font-size:14px;line-height:1.5;">' .
                '<h2 style="margin:0 0 12px;color:#0f172a;">Orçamento ' . $numero . '</h2>' .
                '<p style="margin:0 0 12px;">Ola <strong>' . $cliente . '</strong>, segue seu orcamento em anexo.</p>' .
                '<p style="margin:0 0 12px;">' . htmlspecialchars($tipoResumo, ENT_QUOTES, 'UTF-8') . '</p>' .
                $mensagemHtml .
                '<table style="border-collapse:collapse;width:100%;max-width:520px;">' .
                    '<tr><td style="padding:6px;border:1px solid #e5e7eb;"><strong>Total</strong></td><td style="padding:6px;border:1px solid #e5e7eb;">' . $total . '</td></tr>' .
                    '<tr><td style="padding:6px;border:1px solid #e5e7eb;"><strong>Validade</strong></td><td style="padding:6px;border:1px solid #e5e7eb;">' . $validade . '</td></tr>' .
                '</table>' .
                $linkPublico .
                '<p style="margin:12px 0 0;">Agradecemos o contato.</p>' .
            '</div>';
    }
    private function isPacoteOfertaModuleReady(): bool
    {
        return $this->orcamentoModel->db->tableExists('pacotes_ofertas')
            && $this->orcamentoModel->db->tableExists('pacotes_servicos')
            && $this->orcamentoModel->db->tableExists('pacotes_servicos_niveis');
    }
    private function refreshPacotesOfertasIfExpired(): void
    {
        if (!$this->isPacoteOfertaModuleReady()) {
            return;
        }
        $this->pacoteOfertaModel->db->table('pacotes_ofertas')
            ->whereIn('status', ['ativo', 'enviado'])
            ->where('expira_em IS NOT NULL', null, false)
            ->where('expira_em <', date('Y-m-d H:i:s'))
            ->update([
                'status' => 'expirado',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $this->lifecycleService->syncPacoteAwaitingChoiceExpiry($usuarioId > 0 ? $usuarioId : null);
    }
    private function cancelActivePacotesOfertasByIdentity(
        ?int $clienteId,
        ?int $contatoId,
        string $telefone,
        ?int $osId = null,
        ?int $equipamentoId = null
    ): void
    {
        if (!$this->isPacoteOfertaModuleReady()) {
            return;
        }
        $clienteId = (int) ($clienteId ?? 0);
        $contatoId = (int) ($contatoId ?? 0);
        $osId = (int) ($osId ?? 0);
        $equipamentoId = (int) ($equipamentoId ?? 0);
        $telefone = $this->normalizeWhatsAppPhone($telefone);
        if ($clienteId <= 0 && $contatoId <= 0 && $osId <= 0 && $equipamentoId <= 0 && $telefone === '') {
            return;
        }
        $builder = $this->pacoteOfertaModel->db->table('pacotes_ofertas')
            ->whereIn('status', ['ativo', 'enviado']);
        $builder->groupStart();
        if ($clienteId > 0) {
            $builder->orWhere('cliente_id', $clienteId);
        }
        if ($contatoId > 0) {
            $builder->orWhere('contato_id', $contatoId);
        }
        if ($telefone !== '') {
            $builder->orWhere('telefone_destino', $telefone);
        }
        if ($osId > 0) {
            $builder->orWhere('os_id', $osId);
        }
        if ($equipamentoId > 0) {
            $builder->orWhere('equipamento_id', $equipamentoId);
        }
        $builder->groupEnd();
        $builder->update([
            'status' => 'cancelado',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
    /**
     * @param array<string,mixed> $oferta
     * @return array<string,mixed>
     */
    private function formatPacoteOfertaApi(array $oferta): array
    {
        $status = trim((string) ($oferta['status'] ?? 'ativo'));
        $statusLabels = [
            'ativo' => 'Ativo',
            'enviado' => 'Enviado',
            'escolhido' => 'Escolhido pelo cliente',
            'aplicado_orcamento' => 'Aplicado no orcamento',
            'expirado' => 'Expirado',
            'cancelado' => 'Cancelado',
            'erro_envio' => 'Erro no envio',
        ];
        $expiraEm = trim((string) ($oferta['expira_em'] ?? ''));
        $token = trim((string) ($oferta['token_publico'] ?? ''));
        $valorEscolhido = (float) ($oferta['valor_escolhido'] ?? 0);
        $matchScore = (int) ($oferta['_match_score'] ?? 0);
        $matchModes = trim((string) ($oferta['_match_modes'] ?? ''));
        $identityWarning = trim((string) ($oferta['_identity_warning'] ?? ''));
        $canChoose = in_array($status, ['ativo', 'enviado'], true);
        if ($canChoose && $expiraEm !== '' && strtotime($expiraEm) < time()) {
            $canChoose = false;
        }
        $canApply = $status === 'escolhido'
            && (int) ($oferta['pacote_servico_id'] ?? 0) > 0
            && $valorEscolhido >= 0;
        return [
            'id' => (int) ($oferta['id'] ?? 0),
            'status' => $status,
            'status_label' => $statusLabels[$status] ?? ucfirst($status),
            'pacote_servico_id' => (int) ($oferta['pacote_servico_id'] ?? 0),
            'pacote_nome' => trim((string) ($oferta['pacote_nome'] ?? '')),
            'pacote_tipo_equipamento' => trim((string) ($oferta['pacote_tipo_equipamento'] ?? '')),
            'cliente_id' => (int) ($oferta['cliente_id'] ?? 0) ?: null,
            'contato_id' => (int) ($oferta['contato_id'] ?? 0) ?: null,
            'telefone_destino' => $this->normalizeWhatsAppPhone((string) ($oferta['telefone_destino'] ?? '')),
            'os_id' => (int) ($oferta['os_id'] ?? 0) ?: null,
            'equipamento_id' => (int) ($oferta['equipamento_id'] ?? 0) ?: null,
            'orcamento_id' => (int) ($oferta['orcamento_id'] ?? 0) ?: null,
            'origem_contexto' => trim((string) ($oferta['origem_contexto'] ?? 'manual')),
            'nivel_escolhido' => trim((string) ($oferta['nivel_escolhido'] ?? '')),
            'nivel_nome_exibicao' => trim((string) ($oferta['nivel_nome_exibicao'] ?? '')),
            'valor_escolhido' => round($valorEscolhido, 2),
            'valor_escolhido_formatado' => formatMoney($valorEscolhido),
            'prazo_estimado' => trim((string) ($oferta['prazo_estimado'] ?? '')),
            'garantia_dias' => (int) ($oferta['garantia_dias'] ?? 0) ?: null,
            'itens_inclusos' => trim((string) ($oferta['itens_inclusos'] ?? '')),
            'argumento_venda' => trim((string) ($oferta['argumento_venda'] ?? '')),
            'mensagem_enviada' => trim((string) ($oferta['mensagem_enviada'] ?? '')),
            'enviado_em' => trim((string) ($oferta['enviado_em'] ?? '')),
            'escolhido_em' => trim((string) ($oferta['escolhido_em'] ?? '')),
            'aplicado_em' => trim((string) ($oferta['aplicado_em'] ?? '')),
            'expira_em' => $expiraEm,
            'token_publico' => $token,
            'link_publico' => $token !== '' ? base_url('pacote/oferta/' . $token) : null,
            'can_choose' => $canChoose,
            'can_apply' => $canApply,
            'match_score' => $matchScore,
            'match_modes' => $matchModes,
            'identity_warning' => $identityWarning,
        ];
    }
    /**
     * @param array<string,mixed> $pacote
     * @param array<int,array<string,mixed>> $niveis
     */
    private function buildDefaultPacoteOfertaMessage(
        int $clienteId,
        int $contatoId,
        string $telefone,
        array $pacote,
        array $niveis,
        string $linkPublico,
        string $expiraEm
    ): string {
        $cliente = $this->resolveClienteNomeForPacoteOferta($clienteId, $contatoId, $telefone);
        $pacoteNome = trim((string) ($pacote['nome'] ?? 'Pacote de servicos'));
        $expiraLabel = date('d/m/Y H:i', strtotime($expiraEm));
        $linhas = [
            'Ola ' . $cliente . ', separei as opcoes do pacote "' . $pacoteNome . '".',
            'Escolha o nivel no link abaixo:',
            $linkPublico,
            'Validade do link: 48 horas (ate ' . $expiraLabel . ').',
            '',
            'Niveis disponiveis:',
        ];
        foreach ($niveis as $nivel) {
            $nivelNome = trim((string) ($nivel['nome_exibicao'] ?? ucfirst((string) ($nivel['nivel'] ?? 'nivel'))));
            $valor = formatMoney((float) ($nivel['preco_recomendado'] ?? 0));
            $prazo = trim((string) ($nivel['prazo_estimado'] ?? ''));
            $garantia = (int) ($nivel['garantia_dias'] ?? 0);
            $detalhes = [];
            if ($prazo !== '') {
                $detalhes[] = 'prazo ' . $prazo;
            }
            if ($garantia > 0) {
                $detalhes[] = 'garantia ' . $garantia . ' dias';
            }
            $sufixo = empty($detalhes) ? '' : ' (' . implode(' | ', $detalhes) . ')';
            $linhas[] = '- ' . $nivelNome . ': ' . $valor . $sufixo;
        }
        $linhas[] = '';
        $linhas[] = 'Depois da escolha, podemos aplicar o pacote direto no seu orcamento.';
        $linhas[] = 'Qualquer duvida, estou a disposicao.';
        return implode("\n", $linhas);
    }
    private function appendPacoteOfertaLink(string $mensagem, string $linkPublico, string $expiraEm): string
    {
        $mensagem = trim($mensagem);
        $linkPublico = trim($linkPublico);
        $expiraLabel = date('d/m/Y H:i', strtotime($expiraEm));
        if ($linkPublico === '') {
            return $mensagem;
        }
        if (stripos($mensagem, $linkPublico) !== false) {
            return $mensagem;
        }
        if (preg_match('~https?://\S+~i', $mensagem) === 1) {
            return $mensagem . "\n\nValidade do link: 48 horas (ate " . $expiraLabel . ").\nLink da oferta:\n" . $linkPublico;
        }
        return $mensagem . "\n\nValidade do link: 48 horas (ate " . $expiraLabel . ").\nEscolha seu pacote neste link:\n" . $linkPublico;
    }
    /**
     * @return array<string,mixed>
     */
    private function dispatchPacoteOfertaWhatsApp(
        int $ofertaId,
        ?int $osId,
        ?int $clienteId,
        string $telefone,
        string $mensagem,
        ?int $usuarioId
    ): array {
        $telefone = $this->normalizeWhatsAppPhone($telefone);
        if (!$this->isWhatsAppPhoneValid($telefone)) {
            return [
                'ok' => false,
                'message' => 'Telefone invalido para envio da oferta.',
            ];
        }
        $whatsService = new WhatsAppService();
        $result = $whatsService->sendRaw(
            $osId ?? 0,
            $clienteId ?? 0,
            $telefone,
            $mensagem,
            'pacote_oferta_envio',
            null,
            $usuarioId,
            [
                'oferta_id' => $ofertaId,
            ]
        );
        return [
            'ok' => !empty($result['ok']),
            'duplicate' => !empty($result['duplicate']),
            'message' => (string) ($result['message'] ?? ''),
            'raw' => $result,
        ];
    }
    private function resolveClienteNomeForPacoteOferta(int $clienteId, int $contatoId, string $telefone): string
    {
        if ($clienteId > 0) {
            $cliente = (new ClienteModel())->find($clienteId);
            if ($cliente) {
                $nome = trim((string) ($cliente['nome_razao'] ?? ''));
                if ($nome !== '') {
                    return $nome;
                }
            }
        }
        if ($contatoId > 0) {
            $contato = (new ContatoModel())->find($contatoId);
            if ($contato) {
                $nome = trim((string) ($contato['nome'] ?? $contato['whatsapp_nome_perfil'] ?? ''));
                if ($nome !== '') {
                    return $nome;
                }
            }
        }
        $digits = $this->normalizeWhatsAppPhone($telefone);
        if ($digits !== '') {
            return $digits;
        }
        return 'cliente';
    }
    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadPacotesOfertasByOrcamento(int $orcamentoId): array
    {
        if ($orcamentoId <= 0 || !$this->isPacoteOfertaModuleReady()) {
            return [];
        }
        return $this->pacoteOfertaModel->byOrcamento($orcamentoId);
    }
    /**
     * @param array<int, array<string,mixed>> $ofertas
     */
    private function resolvePacoteOfertaPrincipal(array $ofertas): ?array
    {
        if (empty($ofertas)) {
            return null;
        }
        $prioridade = [
            'aplicado_orcamento' => 0,
            'escolhido' => 1,
            'enviado' => 2,
            'ativo' => 3,
            'erro_envio' => 4,
            'expirado' => 5,
            'cancelado' => 6,
        ];
        $melhor = null;
        $melhorScore = PHP_INT_MAX;
        foreach ($ofertas as $oferta) {
            $status = trim((string) ($oferta['status'] ?? ''));
            $peso = $prioridade[$status] ?? 99;
            $id = (int) ($oferta['id'] ?? 0);
            // Prioriza status mais relevante; em empate, fica a mais recente.
            $score = ($peso * 1000000) - $id;
            if ($score < $melhorScore) {
                $melhorScore = $score;
                $melhor = $oferta;
            }
        }
        return $melhor;
    }
    /**
     * @param array<int, array<string,mixed>> $ofertas
     * @return array<int, array<string,mixed>>
     */
    private function removePacoteOfertaPrincipal(array $ofertas, int $ofertaPrincipalId): array
    {
        if ($ofertaPrincipalId <= 0) {
            return $ofertas;
        }
        $resultado = [];
        foreach ($ofertas as $oferta) {
            if ((int) ($oferta['id'] ?? 0) === $ofertaPrincipalId) {
                continue;
            }
            $resultado[] = $oferta;
        }
        return $resultado;
    }
    /**
     * @return array<string,string>
     */
    private function buildShowStatusOptions(string $statusAtual): array
    {
        $statusAtual = trim($statusAtual);
        $labels = $this->orcamentoModel->statusLabels();

        $flowMap = [
            OrcamentoModel::STATUS_AGUARDANDO_PACOTE => [
                OrcamentoModel::STATUS_AGUARDANDO_PACOTE,
                OrcamentoModel::STATUS_PACOTE_APROVADO,
                OrcamentoModel::STATUS_PENDENTE,
                OrcamentoModel::STATUS_CANCELADO,
            ],
            OrcamentoModel::STATUS_PACOTE_APROVADO => [
                OrcamentoModel::STATUS_PACOTE_APROVADO,
                OrcamentoModel::STATUS_PENDENTE_OS,
                OrcamentoModel::STATUS_CONVERTIDO,
                OrcamentoModel::STATUS_CANCELADO,
            ],
            OrcamentoModel::STATUS_PENDENTE => [
                OrcamentoModel::STATUS_PENDENTE,
                OrcamentoModel::STATUS_AGUARDANDO_PACOTE,
                OrcamentoModel::STATUS_PACOTE_APROVADO,
                OrcamentoModel::STATUS_CANCELADO,
            ],
        ];

        if (isset($flowMap[$statusAtual])) {
            $opcoesFluxo = [];
            foreach ($flowMap[$statusAtual] as $codigo) {
                if (!array_key_exists($codigo, $labels)) {
                    continue;
                }
                if ($codigo !== $statusAtual && !$this->orcamentoService->canTransition($statusAtual, (string) $codigo)) {
                    continue;
                }
                $opcoesFluxo[(string) $codigo] = (string) $labels[$codigo];
            }
            if (!empty($opcoesFluxo)) {
                return $opcoesFluxo;
            }
        }

        $opcoes = [];
        foreach ($labels as $codigo => $label) {
            if ($codigo === $statusAtual || $this->orcamentoService->canTransition($statusAtual, (string) $codigo)) {
                $opcoes[(string) $codigo] = (string) $label;
            }
        }
        if (empty($opcoes)) {
            $opcoes[$statusAtual] = $labels[$statusAtual] ?? ucfirst($statusAtual);
        }
        return $opcoes;
    }
    /**
     * @return array<string,string>
     */
    private function buildEditStatusOptions(string $statusAtual, bool $preserveCurrentOnly = false): array
    {
        $statusAtual = trim($statusAtual);
        $labels = $this->orcamentoModel->statusLabels();
        if ($preserveCurrentOnly && $statusAtual !== '') {
            return [
                $statusAtual => $labels[$statusAtual] ?? ucfirst(str_replace('_', ' ', $statusAtual)),
            ];
        }

        return $this->buildShowStatusOptions($statusAtual);
    }
    /**
     * @param array<string,mixed> $orcamento
     */
    private function canEditLockedOrcamentoFromOsEmbed(array $orcamento): bool
    {
        if (!$this->isEmbedRequest()) {
            return false;
        }

        if ((int) ($orcamento['os_id'] ?? 0) <= 0) {
            return false;
        }

        return $this->orcamentoModel->isLockedStatus((string) ($orcamento['status'] ?? ''));
    }
    /**
     * @return array<string,mixed>
     */
    private function buildEmbeddedLockedPacoteIntent(string $statusAtual): array
    {
        $statusAtual = trim($statusAtual);
        return [
            'oferta_id' => 0,
            'aplicar' => false,
            'orcamento_baseado_pacote' => in_array($statusAtual, [
                OrcamentoModel::STATUS_AGUARDANDO_PACOTE,
                OrcamentoModel::STATUS_PACOTE_APROVADO,
            ], true),
            'pacote_servico_id' => 0,
            'telefone' => '',
            'enviar_whatsapp' => false,
            'mensagem' => '',
            'mensagem_personalizada_com_link' => false,
        ];
    }
    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadPacotesAtivosForOferta(): array
    {
        if (!$this->orcamentoModel->db->tableExists('pacotes_servicos')
            || !$this->orcamentoModel->db->tableExists('pacotes_servicos_niveis')) {
            return [];
        }
        return $this->loadPacotesCatalogAtivos();
    }
    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadPacotesCatalogAtivos(): array
    {
        if (!$this->orcamentoModel->db->tableExists('pacotes_servicos')
            || !$this->orcamentoModel->db->tableExists('pacotes_servicos_niveis')) {
            return [];
        }
        $rows = (new PacoteServicoModel())->withResumoNiveis();
        if (empty($rows)) {
            return [];
        }
        $result = [];
        foreach ($rows as $row) {
            if ((int) ($row['ativo'] ?? 1) !== 1) {
                continue;
            }
            $niveisResumo = [];
            foreach ((array) ($row['niveis'] ?? []) as $nivelCode => $nivelData) {
                if ((int) ($nivelData['ativo'] ?? 1) !== 1) {
                    continue;
                }
                $niveisResumo[] = [
                    'nivel' => (string) $nivelCode,
                    'nome_exibicao' => trim((string) ($nivelData['nome_exibicao'] ?? ucfirst((string) $nivelCode))),
                    'preco_recomendado' => (float) ($nivelData['preco_recomendado'] ?? 0),
                    'destaque' => (int) ($nivelData['destaque'] ?? 0),
                ];
            }
            $row['niveis_resumo'] = $niveisResumo;
            $result[] = $row;
        }
        return $result;
    }
    private function resolveClienteNome(array $orcamento): string
    {
        $cliente = trim((string) ($orcamento['cliente_nome'] ?? ''));
        if ($cliente !== '') {
            return $cliente;
        }
        $avulso = trim((string) ($orcamento['cliente_nome_avulso'] ?? ''));
        if ($avulso !== '') {
            return $avulso;
        }
        return 'cliente';
    }
    private function shouldPreserveClienteEventualIdentity(?int $clienteId, ?string $nomeAvulso): bool
    {
        return ((int) ($clienteId ?? 0)) <= 0 && trim((string) ($nomeAvulso ?? '')) !== '';
    }
    private function isPhoneValid(string $phone): bool
    {
        $digits = $this->normalizePhone($phone);
        return strlen($digits) >= 8;
    }
    private function normalizeWhatsAppPhone(string $phone): string
    {
        $digits = $this->normalizePhone($phone);
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) > 11) {
            $digits = substr($digits, 0, 11);
        }
        return $digits;
    }
    private function normalizeIdentityNameForMatch(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($ascii !== false) {
                $value = $ascii;
            }
        }
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        return trim($value);
    }
    private function isLikelySameIdentityName(string $left, string $right): bool
    {
        if ($left === '' || $right === '') {
            return false;
        }
        if ($left === $right) {
            return true;
        }
        if (strlen($left) >= 5 && str_contains($right, $left)) {
            return true;
        }
        if (strlen($right) >= 5 && str_contains($left, $right)) {
            return true;
        }
        similar_text($left, $right, $percent);
        return $percent >= 72;
    }
    private function isWhatsAppPhoneValid(string $phone): bool
    {
        $digits = $this->normalizeWhatsAppPhone($phone);
        return preg_match('/^[1-9]{2}9\d{8}$/', $digits) === 1;
    }
    /**
     * @param array<string,mixed> $payload
     */
    private function validateContatoPayload(array $payload): ?string
    {
        $telefone = trim((string) ($payload['telefone_contato'] ?? ''));
        if ($telefone !== '' && !$this->isWhatsAppPhoneValid($telefone)) {
            return 'Telefone de contato invalido. Use um celular WhatsApp com DDD no formato 11987654321.';
        }
        $email = trim((string) ($payload['email_contato'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Informe um email de contato valido para envio do orcamento.';
        }
        return null;
    }
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\\D+/', '', $phone) ?? '';
    }
    private function phoneMatches(string $needle, string $candidate): bool
    {
        if ($needle === '' || $candidate === '') {
            return false;
        }
        if ($needle === $candidate) {
            return true;
        }
        return (strlen($needle) >= 8 && str_ends_with($candidate, $needle))
            || (strlen($candidate) >= 8 && str_ends_with($needle, $candidate));
    }
    /**
     * @return array<int,array<string,mixed>>
     */
    private function searchClientesForLookup(string $term, int $limit = 10): array
    {
        $clienteModel = new ClienteModel();
        $builder = $clienteModel
            ->select('id, nome_razao, telefone1, nome_contato, telefone_contato, email, cpf_cnpj, updated_at');
        $digitsTerm = $this->normalizePhone($term);
        if ($term !== '') {
            $builder->groupStart()
                ->like('nome_razao', $term)
                ->orLike('nome_contato', $term)
                ->orLike('cpf_cnpj', $term)
                ->orLike('email', $term)
                ->orLike('telefone1', $term)
                ->orLike('telefone_contato', $term);
            if ($digitsTerm !== '') {
                $builder->orLike('telefone1', $digitsTerm)
                    ->orLike('telefone_contato', $digitsTerm);
            }
            $builder->groupEnd();
        }
        $rows = $builder
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($term === '' ? $limit : max(20, $limit * 3));
        if ($term === '') {
            return array_slice($rows, 0, $limit);
        }
        usort($rows, function (array $a, array $b) use ($term, $digitsTerm): int {
            $scoreA = $this->scoreClienteLookup($a, $term, $digitsTerm);
            $scoreB = $this->scoreClienteLookup($b, $term, $digitsTerm);
            if ($scoreA !== $scoreB) {
                return $scoreA <=> $scoreB;
            }
            return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
        });
        return array_slice($rows, 0, $limit);
    }
    private function scoreClienteLookup(array $cliente, string $term, string $digitsTerm): int
    {
        $score = 100;
        $nome = strtolower((string) ($cliente['nome_razao'] ?? ''));
        $nomeContato = strtolower((string) ($cliente['nome_contato'] ?? ''));
        $termLower = strtolower($term);
        $telefone1 = $this->normalizePhone((string) ($cliente['telefone1'] ?? ''));
        $telefoneContato = $this->normalizePhone((string) ($cliente['telefone_contato'] ?? ''));
        if ($digitsTerm !== '') {
            if ($this->phoneMatches($digitsTerm, $telefone1) || $this->phoneMatches($digitsTerm, $telefoneContato)) {
                return 0;
            }
            if ($telefone1 !== '' && str_contains($telefone1, $digitsTerm)) {
                $score = min($score, 5);
            }
            if ($telefoneContato !== '' && str_contains($telefoneContato, $digitsTerm)) {
                $score = min($score, 6);
            }
        }
        if ($nome !== '') {
            if ($termLower !== '' && str_starts_with($nome, $termLower)) {
                $score = min($score, 10);
            } elseif ($termLower !== '' && str_contains($nome, $termLower)) {
                $score = min($score, 20);
            }
        }
        if ($nomeContato !== '') {
            if ($termLower !== '' && str_starts_with($nomeContato, $termLower)) {
                $score = min($score, 14);
            } elseif ($termLower !== '' && str_contains($nomeContato, $termLower)) {
                $score = min($score, 24);
            }
        }
        return $score;
    }
    /**
     * @param array<int,string> $excludedPhones
     * @return array<int,array<string,mixed>>
     */
    private function searchContatosForLookup(string $term, int $limit = 10, array $excludedPhones = []): array
    {
        $contatoModel = new ContatoModel();
        $builder = $contatoModel
            ->select('id, cliente_id, nome, telefone, telefone_normalizado, email, whatsapp_nome_perfil, updated_at');
        $digitsTerm = $this->normalizePhone($term);
        if ($term !== '') {
            $builder->groupStart()
                ->like('nome', $term)
                ->orLike('whatsapp_nome_perfil', $term)
                ->orLike('email', $term)
                ->orLike('telefone', $term);
            if ($digitsTerm !== '') {
                $builder->orLike('telefone_normalizado', $digitsTerm);
            }
            $builder->groupEnd();
        }
        $rows = $builder
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($term === '' ? max(20, $limit * 2) : max(30, $limit * 4));
        $excluded = [];
        foreach ($excludedPhones as $phone) {
            $normalized = $this->normalizePhone((string) $phone);
            if ($normalized !== '') {
                $excluded[$normalized] = true;
            }
        }
        $filtered = [];
        foreach ($rows as $row) {
            $normalized = $this->normalizePhone((string) ($row['telefone_normalizado'] ?? $row['telefone'] ?? ''));
            if ($normalized !== '' && isset($excluded[$normalized])) {
                continue;
            }
            $filtered[] = $row;
        }
        if ($term !== '') {
            usort($filtered, function (array $a, array $b) use ($term, $digitsTerm): int {
                $scoreA = $this->scoreContatoLookup($a, $term, $digitsTerm);
                $scoreB = $this->scoreContatoLookup($b, $term, $digitsTerm);
                if ($scoreA !== $scoreB) {
                    return $scoreA <=> $scoreB;
                }
                return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
            });
        }
        return array_slice($filtered, 0, $limit);
    }
    private function scoreContatoLookup(array $contato, string $term, string $digitsTerm): int
    {
        $score = 100;
        $nome = strtolower(trim((string) ($contato['nome'] ?? $contato['whatsapp_nome_perfil'] ?? '')));
        $termLower = strtolower($term);
        $telefone = $this->normalizePhone((string) ($contato['telefone_normalizado'] ?? $contato['telefone'] ?? ''));
        if ($digitsTerm !== '') {
            if ($this->phoneMatches($digitsTerm, $telefone)) {
                return 1;
            }
            if ($telefone !== '' && str_contains($telefone, $digitsTerm)) {
                $score = min($score, 7);
            }
        }
        if ($nome !== '') {
            if ($termLower !== '' && str_starts_with($nome, $termLower)) {
                $score = min($score, 12);
            } elseif ($termLower !== '' && str_contains($nome, $termLower)) {
                $score = min($score, 22);
            }
        }
        return $score;
    }
    /**
     * @param array<string,mixed> $cliente
     * @return array<string,string>
     */
    private function buildContatoAdicionalPayload(array $cliente): array
    {
        return [
            'contato_adicional_nome' => trim((string) ($cliente['nome_contato'] ?? '')),
            'contato_adicional_telefone' => trim((string) ($cliente['telefone_contato'] ?? '')),
        ];
    }
    /**
     * @param array<int,array<string,mixed>> $contatos
     * @return array<int,array<string,string>>
     */
    private function loadContatoAdicionalByClienteIds(array $contatos): array
    {
        $clienteIds = [];
        foreach ($contatos as $contato) {
            $clienteId = (int) ($contato['cliente_id'] ?? 0);
            if ($clienteId > 0) {
                $clienteIds[$clienteId] = $clienteId;
            }
        }
        if (empty($clienteIds)) {
            return [];
        }
        $rows = (new ClienteModel())
            ->select('id, nome_contato, telefone_contato')
            ->whereIn('id', array_values($clienteIds))
            ->findAll();
        $map = [];
        foreach ($rows as $cliente) {
            $clienteId = (int) ($cliente['id'] ?? 0);
            if ($clienteId <= 0) {
                continue;
            }
            $map[$clienteId] = $this->buildContatoAdicionalPayload($cliente);
        }
        return $map;
    }
    /**
     * @return array<string,mixed>
     */
    private function buildClienteLookupInitial(array $orcamento): array
    {
        $clienteId = (int) ($orcamento['cliente_id'] ?? 0);
        $contatoId = (int) ($orcamento['contato_id'] ?? 0);
        if ($clienteId > 0) {
            $cliente = (new ClienteModel())->find($clienteId);
            if ($cliente) {
                $nome = trim((string) ($cliente['nome_razao'] ?? ''));
                $telefone = trim((string) ($cliente['telefone1'] ?? ''));
                $email = trim((string) ($cliente['email'] ?? ''));
                $text = $nome !== '' ? $nome : ('Cliente #' . $clienteId);
                if ($telefone !== '') {
                    $text .= ' | ' . $telefone;
                }
                if ($email !== '') {
                    $text .= ' | ' . $email;
                }
                return array_merge([
                    'id' => 'cliente:' . $clienteId,
                    'text' => $text,
                    'tipo' => 'cliente',
                    'cliente_id' => $clienteId,
                    'contato_id' => $contatoId > 0 ? $contatoId : null,
                    'nome' => $nome,
                    'telefone' => $telefone,
                    'email' => $email,
                    'source_label' => 'Cliente',
                ], $this->buildContatoAdicionalPayload($cliente));
            }
        }
        if ($contatoId > 0) {
            $contato = (new ContatoModel())->find($contatoId);
            if ($contato) {
                $nome = trim((string) ($contato['nome'] ?? $contato['whatsapp_nome_perfil'] ?? ''));
                $telefone = trim((string) ($contato['telefone'] ?? ''));
                $email = trim((string) ($contato['email'] ?? ''));
                $text = $nome !== '' ? $nome : ('Contato #' . $contatoId);
                if ($telefone !== '') {
                    $text .= ' | ' . $telefone;
                }
                if ($email !== '') {
                    $text .= ' | ' . $email;
                }
                $clienteVinculadoId = (int) ($contato['cliente_id'] ?? 0);
                $clienteContatoPayload = $clienteVinculadoId > 0
                    ? $this->buildContatoAdicionalPayload((new ClienteModel())
                        ->select('id, nome_contato, telefone_contato')
                        ->find($clienteVinculadoId) ?? [])
                    : $this->buildContatoAdicionalPayload([]);
                return array_merge([
                    'id' => 'contato:' . $contatoId,
                    'text' => $text,
                    'tipo' => 'contato',
                    'cliente_id' => $clienteVinculadoId ?: null,
                    'contato_id' => $contatoId,
                    'nome' => $nome,
                    'telefone' => $telefone,
                    'email' => $email,
                    'source_label' => 'Contato',
                ], $clienteContatoPayload);
            }
        }
        return [];
    }
    /**
     * @return array<string,mixed>
     */
    private function buildEquipamentoLookupInitial(array $orcamento): array
    {
        $equipamentoId = (int) ($orcamento['equipamento_id'] ?? 0);
        if ($equipamentoId <= 0) {
            return [];
        }
        $equipamento = (new EquipamentoModel())->getWithCliente($equipamentoId);
        if (!$equipamento) {
            return [];
        }
        $item = $this->formatEquipamentoLookupResult($equipamento);
        if (empty($item)) {
            return [];
        }
        $item['cliente_id'] = (int) ($equipamento['cliente_id'] ?? $orcamento['cliente_id'] ?? 0) ?: null;
        unset($item['search_text']);
        return $item;
    }
    /**
     * @return array<int,array<string,mixed>>
     */
    private function loadOsAbertasCliente(int $clienteId): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        $statusFechados = [
            'entregue_reparado',
            'entregue',
            'entregue_pagamento_pendente',
            'devolvido_sem_reparo',
            'descartado',
            'cancelado',
            'reparo_recusado',
            'irreparavel',
            'irreparavel_disponivel_loja',
        ];

        $rows = (new OsModel())
            ->select("os.id, os.numero_os, os.status, os.estado_fluxo, os.equipamento_id, os.data_abertura, os.data_previsao, tipos.nome as equip_tipo, marcas.nome as equip_marca, modelos.nome as equip_modelo, equipamentos.cor, (SELECT ef.arquivo FROM equipamentos_fotos ef WHERE ef.equipamento_id = os.equipamento_id ORDER BY ef.is_principal DESC, ef.id ASC LIMIT 1) AS foto_principal_arquivo")
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id', 'left')
            ->join('equipamentos_tipos tipos', 'tipos.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas marcas', 'marcas.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos modelos', 'modelos.id = equipamentos.modelo_id', 'left')
            ->where('os.cliente_id', $clienteId)
            ->groupStart()
                ->where('os.estado_fluxo IS NULL', null, false)
                ->orWhere("TRIM(COALESCE(os.estado_fluxo, '')) = ''", null, false)
                ->orWhereNotIn('os.estado_fluxo', ['encerrado', 'cancelado'])
            ->groupEnd()
            ->whereNotIn('os.status', $statusFechados)
            ->orderBy('os.id', 'DESC')
            ->findAll();

        $results = [];
        foreach ($rows as $row) {
            $item = $this->formatOsAbertaLookupResult($row);
            if (!empty($item)) {
                $results[] = $item;
            }
        }

        return $results;
    }
    /**
     * @param array<string,mixed> $os
     * @return array<string,mixed>
     */
    private function formatOsAbertaLookupResult(array $os): array
    {
        $osId = (int) ($os['id'] ?? 0);
        if ($osId <= 0) {
            return [];
        }

        $numero = trim((string) ($os['numero_os'] ?? ''));
        $numeroLabel = $this->formatOsNumeroLabel($numero !== '' ? $numero : ('#' . $osId));
        $tipo = trim((string) ($os['equip_tipo'] ?? ''));
        $marca = trim((string) ($os['equip_marca'] ?? ''));
        $modelo = trim((string) ($os['equip_modelo'] ?? ''));
        $cor = trim((string) ($os['cor'] ?? ''));
        $marcaModelo = trim($marca . ' ' . $modelo);
        $equipamentoLabel = trim(implode(' | ', array_filter([$tipo, $marcaModelo])));
        if ($equipamentoLabel === '') {
            $equipamentoLabel = 'Equipamento sem identificacao detalhada';
        }

        $statusCode = trim((string) ($os['status'] ?? ''));
        $statusLabel = $this->formatOsStatusLabel($statusCode);
        $searchParts = array_filter([
            $numeroLabel,
            $numero,
            (string) $osId,
            $statusCode,
            $statusLabel,
            $tipo,
            $marca,
            $modelo,
            $cor,
            $equipamentoLabel,
        ], static fn($value): bool => trim((string) $value) !== '');

        return [
            'id' => (string) $osId,
            'text' => $numeroLabel . ' - ' . $equipamentoLabel,
            'os_id' => $osId,
            'numero' => $numero,
            'numero_label' => $numeroLabel,
            'status' => $statusCode,
            'status_label' => $statusLabel,
            'estado_fluxo' => trim((string) ($os['estado_fluxo'] ?? '')),
            'equipamento_id' => (int) ($os['equipamento_id'] ?? 0) ?: null,
            'equipamento' => [
                'id' => (int) ($os['equipamento_id'] ?? 0) ?: null,
                'tipo' => $tipo,
                'marca' => $marca,
                'modelo' => $modelo,
                'cor' => $cor !== '' ? $cor : null,
                'descricao' => $equipamentoLabel,
                'foto_url' => $this->buildEquipamentoFotoUrl((string) ($os['foto_principal_arquivo'] ?? '')),
            ],
            'search_text' => implode(' ', $searchParts),
        ];
    }
    private function formatOsNumeroLabel(string $numero): string
    {
        $numero = trim($numero);
        if ($numero === '') {
            return 'OS';
        }

        return preg_match('/^OS/i', $numero) ? $numero : ('OS ' . $numero);
    }
    private function formatOsStatusLabel(string $statusCode): string
    {
        $statusCode = trim($statusCode);
        if ($statusCode === '') {
            return '';
        }

        $labels = $this->getOsStatusLabelMap();
        if (isset($labels[$statusCode]) && trim((string) $labels[$statusCode]) !== '') {
            return trim((string) $labels[$statusCode]);
        }

        return ucwords(str_replace(['_', '-'], ' ', strtolower($statusCode)));
    }
    /**
     * @return array<string,string>
     */
    private function getOsStatusLabelMap(): array
    {
        if ($this->osStatusLabelMap !== null) {
            return $this->osStatusLabelMap;
        }

        $this->osStatusLabelMap = [];
        $statusModel = new OsStatusModel();
        if (!$statusModel->db->tableExists('os_status')) {
            return $this->osStatusLabelMap;
        }

        foreach ($statusModel->select('codigo, nome')->findAll() as $row) {
            $codigo = trim((string) ($row['codigo'] ?? ''));
            $nome = trim((string) ($row['nome'] ?? ''));
            if ($codigo !== '' && $nome !== '') {
                $this->osStatusLabelMap[$codigo] = $nome;
            }
        }

        return $this->osStatusLabelMap;
    }
    /**
     * @param array<string,mixed> $equipamento
     * @return array<string,mixed>
     */
    private function formatEquipamentoLookupResult(array $equipamento): array
    {
        $equipamentoId = (int) ($equipamento['id'] ?? 0);
        if ($equipamentoId <= 0) {
            return [];
        }
        $tipo = trim((string) ($equipamento['tipo_nome'] ?? 'Equipamento'));
        $marca = trim((string) ($equipamento['marca_nome'] ?? ''));
        $modelo = trim((string) ($equipamento['modelo_nome'] ?? ''));
        $cor = trim((string) ($equipamento['cor'] ?? ''));
        $corHex = $this->normalizeHexColorValue((string) ($equipamento['cor_hex'] ?? ''));
        $corRgb = $this->normalizeRgbColorValue((string) ($equipamento['cor_rgb'] ?? ''));
        if ($corRgb === null && $corHex !== null) {
            $corRgb = $this->rgbFromHex($corHex);
        }
        $modeloLabel = trim($marca . ' ' . $modelo);
        $text = $tipo !== '' ? $tipo : ('Equipamento #' . $equipamentoId);
        if ($modeloLabel !== '') {
            $text .= ' | ' . $modeloLabel;
        }
        if ($cor !== '') {
            $text .= ' | Cor: ' . $cor;
        }
        $searchParts = array_filter([
            $text,
            $tipo,
            $marca,
            $modelo,
            $cor,
            (string) $equipamentoId,
        ], static fn($value): bool => trim((string) $value) !== '');
        return [
            'id' => 'equipamento:' . $equipamentoId,
            'text' => $text,
            'tipo' => 'equipamento',
            'equipamento_id' => $equipamentoId,
            'tipo_id' => (int) ($equipamento['tipo_id'] ?? 0) ?: null,
            'marca_id' => (int) ($equipamento['marca_id'] ?? 0) ?: null,
            'modelo_id' => (int) ($equipamento['modelo_id'] ?? 0) ?: null,
            'cor' => $cor !== '' ? $cor : null,
            'cor_hex' => $corHex,
            'cor_rgb' => $corRgb,
            'foto_url' => $this->buildEquipamentoFotoUrl((string) ($equipamento['foto_principal_arquivo'] ?? '')),
            'search_text' => implode(' ', $searchParts),
        ];
    }
    /**
     * @return array<string,mixed>
     */
    private function resolveVinculosContext(array $orcamento): array
    {
        $osId = (int) ($orcamento['os_id'] ?? 0);
        $equipamentoId = (int) ($orcamento['equipamento_id'] ?? 0);
        $conversaId = (int) ($orcamento['conversa_id'] ?? 0);
        $context = [
            'mostrar' => false,
            'origem' => trim((string) ($orcamento['origem'] ?? 'manual')) ?: 'manual',
            'os' => null,
            'equipamento' => null,
            'conversa' => null,
        ];
        if ($osId > 0) {
            $os = (new OsModel())->getComplete($osId);
            if ($os) {
                $context['mostrar'] = true;
                $context['origem'] = 'os';
                $osEquipamentoId = (int) ($os['equipamento_id'] ?? 0) ?: null;
                $context['os'] = [
                    'id' => $osId,
                    'numero' => (string) ($os['numero_os'] ?? ('#' . $osId)),
                    'status' => (string) ($os['status'] ?? ''),
                    'data_abertura' => (string) ($os['data_abertura'] ?? ''),
                    'equipamento_id' => $osEquipamentoId,
                    'tem_equipamento_vinculado' => $osEquipamentoId !== null,
                ];
                if ($equipamentoId <= 0) {
                    $equipamentoId = (int) ($os['equipamento_id'] ?? 0);
                }
            }
        }
        if ($equipamentoId > 0) {
            $equipamento = (new EquipamentoModel())->getWithCliente($equipamentoId);
            if ($equipamento) {
                $context['mostrar'] = true;
                $context['equipamento'] = [
                    'id' => $equipamentoId,
                    'tipo' => trim((string) ($equipamento['tipo_nome'] ?? '')),
                    'marca' => trim((string) ($equipamento['marca_nome'] ?? '')),
                    'modelo' => trim((string) ($equipamento['modelo_nome'] ?? '')),
                    'foto_url' => $this->buildEquipamentoFotoUrl((string) ($equipamento['foto_principal_arquivo'] ?? '')),
                ];
            }
        }
        if ($conversaId > 0) {
            $conversa = (new ConversaWhatsappModel())->find($conversaId);
            if ($conversa) {
                $context['mostrar'] = true;
                if ($context['origem'] !== 'os') {
                    $context['origem'] = 'conversa';
                }
                $context['conversa'] = [
                    'id' => $conversaId,
                    'telefone' => trim((string) ($conversa['telefone'] ?? '')),
                    'nome' => trim((string) ($conversa['nome_contato'] ?? '')),
                ];
            }
        }
        if (!$context['mostrar']) {
            $context['origem'] = 'manual';
        }
        return $context;
    }
    private function buildEquipamentoFotoUrl(string $relativePath): ?string
    {
        $relativePath = str_replace('\\', '/', ltrim(trim($relativePath), '/'));
        if ($relativePath === '') {
            return $this->missingEquipamentoPhotoDataUri();
        }
        if (preg_match('/^https?:\/\//i', $relativePath)) {
            return $relativePath;
        }
        $resolved = $this->resolveEquipamentoFotoRelativePath($relativePath);
        if ($resolved === null) {
            return $this->missingEquipamentoPhotoDataUri();
        }
        $absolute = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, ltrim($resolved, '/'));
        $version = is_file($absolute) ? ('?v=' . filemtime($absolute)) : '';
        return base_url(ltrim($resolved, '/')) . $version;
    }
    private function resolveEquipamentoFotoRelativePath(string $arquivo): ?string
    {
        $arquivo = str_replace('\\', '/', ltrim(trim($arquivo), '/'));
        if ($arquivo === '') {
            return null;
        }
        $basename = basename($arquivo);
        $candidates = [
            'uploads/equipamentos_perfil/' . $arquivo,
            'uploads/equipamentos_perfil/' . $basename,
            'uploads/equipamentos/' . $basename,
            $arquivo,
        ];
        foreach ($candidates as $candidate) {
            $candidate = str_replace('\\', '/', ltrim($candidate, '/'));
            $absolute = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (is_file($absolute)) {
                return $candidate;
            }
        }
        return null;
    }
    private function missingEquipamentoPhotoDataUri(): string
    {
        static $uri = null;
        if ($uri !== null) {
            return $uri;
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><rect width="80" height="80" rx="40" fill="#eef2ff"/><circle cx="40" cy="30" r="12" fill="#c7d2fe"/><text x="40" y="58" text-anchor="middle" font-size="10" fill="#64748b">sem foto</text></svg>';
        $uri = 'data:image/svg+xml;base64,' . base64_encode($svg);
        return $uri;
    }
    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function resolveClienteContatoPayload(array $payload): array
    {
        $clienteModel = new ClienteModel();
        $contatoModel = new ContatoModel();
        $clienteId = (int) ($payload['cliente_id'] ?? 0);
        $contatoId = (int) ($payload['contato_id'] ?? 0);
        $nomeAvulso = trim((string) ($payload['cliente_nome_avulso'] ?? ''));
        $telefone = $this->normalizeWhatsAppPhone((string) ($payload['telefone_contato'] ?? ''));
        $email = trim((string) ($payload['email_contato'] ?? ''));
        $registrarContato = (string) $this->request->getPost('registrar_contato') === '1';
        $preserveManualIdentity = fn (): bool => $this->shouldPreserveClienteEventualIdentity($clienteId, $nomeAvulso);
        if ($clienteId > 0) {
            $cliente = $clienteModel->find($clienteId);
            if (!$cliente) {
                $clienteId = 0;
            } else {
                if ($telefone === '') {
                    $telefone = $this->normalizeWhatsAppPhone((string) ($cliente['telefone1'] ?? ($cliente['telefone_contato'] ?? '')));
                }
                if ($email === '') {
                    $email = trim((string) ($cliente['email'] ?? ''));
                }
                $nomeAvulso = '';
            }
        }
        if (!$preserveManualIdentity() && $clienteId <= 0 && $this->isPhoneValid($telefone)) {
            $clienteByPhone = $this->findClienteByPhone($telefone);
            if ($clienteByPhone) {
                $clienteId = (int) ($clienteByPhone['id'] ?? 0);
                if ($email === '') {
                    $email = trim((string) ($clienteByPhone['email'] ?? ''));
                }
                if ($telefone === '') {
                    $telefone = $this->normalizeWhatsAppPhone((string) ($clienteByPhone['telefone1'] ?? ($clienteByPhone['telefone_contato'] ?? '')));
                }
                $nomeAvulso = '';
            }
        }
        if ($contatoId > 0) {
            $contato = $contatoModel->find($contatoId);
            if (!$contato) {
                $contatoId = 0;
            } else {
                if ($telefone === '') {
                    $telefone = $this->normalizeWhatsAppPhone((string) ($contato['telefone'] ?? ''));
                }
                if ($email === '') {
                    $email = trim((string) ($contato['email'] ?? ''));
                }
                if ($nomeAvulso === '') {
                    $nomeAvulso = trim((string) ($contato['nome'] ?? $contato['whatsapp_nome_perfil'] ?? ''));
                }
                if ($clienteId <= 0 && (int) ($contato['cliente_id'] ?? 0) > 0) {
                    $clienteId = (int) $contato['cliente_id'];
                    $nomeAvulso = '';
                }
            }
        }
        if (!$preserveManualIdentity() && $contatoId <= 0 && $clienteId <= 0 && $this->isPhoneValid($telefone)) {
            $contatoByPhone = $contatoModel->findByPhone($telefone);
            if ($contatoByPhone) {
                $contatoId = (int) ($contatoByPhone['id'] ?? 0);
                if ($email === '') {
                    $email = trim((string) ($contatoByPhone['email'] ?? ''));
                }
                if ($nomeAvulso === '') {
                    $nomeAvulso = trim((string) ($contatoByPhone['nome'] ?? $contatoByPhone['whatsapp_nome_perfil'] ?? ''));
                }
                if ($clienteId <= 0 && (int) ($contatoByPhone['cliente_id'] ?? 0) > 0) {
                    $clienteId = (int) $contatoByPhone['cliente_id'];
                    $nomeAvulso = '';
                }
            }
        }
        if ($preserveManualIdentity() && $contatoId <= 0 && $clienteId <= 0 && $registrarContato && $this->isPhoneValid($telefone)) {
            $cadastroExistente = $this->findClienteByPhone($telefone)
                ?? $contatoModel->findByPhone($telefone)
                ?? $this->findClienteByEmail($email)
                ?? $this->findContatoByEmail($email);

            if ($cadastroExistente === null) {
                $contatoId = $this->upsertContatoByPayload($nomeAvulso, $telefone, $email, null);
            }
        } elseif ($registrarContato && $contatoId <= 0 && $clienteId <= 0 && $this->isPhoneValid($telefone)) {
            $contatoId = $this->upsertContatoByPayload($nomeAvulso, $telefone, $email, null);
        }
        if ($clienteId > 0) {
            $payload['cliente_id'] = $clienteId;
            $payload['cliente_nome_avulso'] = null;
        } else {
            $payload['cliente_id'] = null;
            $payload['cliente_nome_avulso'] = $nomeAvulso !== '' ? $nomeAvulso : null;
        }
        if ($contatoId > 0 && $clienteId > 0) {
            $contato = $contatoModel->find($contatoId);
            if ($contato && (int) ($contato['cliente_id'] ?? 0) <= 0) {
                $contatoPayload = $contatoModel->buildClienteConvertidoPayload($clienteId, ['cliente_id' => $clienteId]);
                $contatoModel->update($contatoId, $contatoPayload);
            }
        }
        $payload['contato_id'] = $contatoId > 0 ? $contatoId : null;
        $payload['telefone_contato'] = $telefone !== '' ? $this->normalizeWhatsAppPhone($telefone) : null;
        $payload['email_contato'] = $email !== '' ? strtolower($email) : null;
        return $payload;
    }
    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function resolveEquipamentoCatalogPayload(array $payload): array
    {
        $tipoId = max(0, (int) ($payload['equipamento_tipo_id'] ?? 0));
        $marcaId = max(0, (int) ($payload['equipamento_marca_id'] ?? 0));
        $modeloId = max(0, (int) ($payload['equipamento_modelo_id'] ?? 0));
        $cor = trim((string) ($payload['equipamento_cor'] ?? ''));
        $corHex = $this->normalizeHexColorValue((string) ($payload['equipamento_cor_hex'] ?? ''));
        $corRgb = $this->normalizeRgbColorValue((string) ($payload['equipamento_cor_rgb'] ?? ''));
        if ($tipoId > 0 && !(new EquipamentoTipoModel())->find($tipoId)) {
            $tipoId = 0;
        }
        if ($marcaId > 0 && !(new EquipamentoMarcaModel())->find($marcaId)) {
            $marcaId = 0;
        }
        if ($modeloId > 0) {
            $modelo = (new EquipamentoModeloModel())->find($modeloId);
            if (!$modelo) {
                $modeloId = 0;
            } else {
                $modeloMarcaId = (int) ($modelo['marca_id'] ?? 0);
                if ($marcaId <= 0) {
                    $marcaId = $modeloMarcaId;
                } elseif ($modeloMarcaId !== $marcaId) {
                    $modeloId = 0;
                }
            }
        }
        if ($corRgb === null && $corHex !== null) {
            $corRgb = $this->rgbFromHex($corHex);
        }
        $payload['equipamento_tipo_id'] = $tipoId > 0 ? $tipoId : null;
        $payload['equipamento_marca_id'] = $marcaId > 0 ? $marcaId : null;
        $payload['equipamento_modelo_id'] = $modeloId > 0 ? $modeloId : null;
        $payload['equipamento_cor'] = $cor !== '' ? $cor : null;
        $payload['equipamento_cor_hex'] = $corHex;
        $payload['equipamento_cor_rgb'] = $corRgb;
        if ($tipoId > 0 && $marcaId > 0 && $modeloId > 0) {
            $this->syncCatalogoRelacao($tipoId, $marcaId, $modeloId);
        }
        $equipamentoId = (int) ($payload['equipamento_id'] ?? 0);
        if ($equipamentoId > 0) {
            $this->applyEquipamentoSnapshotFromId($payload, $equipamentoId);
            return $payload;
        }
        if ($tipoId <= 0 || $marcaId <= 0 || $modeloId <= 0) {
            return $payload;
        }
        $clienteId = $this->resolvePayloadClienteIdForEquipamento($payload);
        if ($clienteId <= 0) {
            return $payload;
        }
        $novoEquipamentoId = $this->findOrCreateEquipamentoId(
            $clienteId,
            $tipoId,
            $marcaId,
            $modeloId,
            $cor !== '' ? $cor : null,
            $corHex,
            $corRgb
        );
        if ($novoEquipamentoId > 0) {
            $payload['equipamento_id'] = $novoEquipamentoId;
            $this->applyEquipamentoSnapshotFromId($payload, $novoEquipamentoId);
        }
        return $payload;
    }
    /**
     * @param array<string,mixed> $payload
     */
    private function resolvePayloadClienteIdForEquipamento(array &$payload): int
    {
        $clienteId = (int) ($payload['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            return $clienteId;
        }
        $nomeAvulso = trim((string) ($payload['cliente_nome_avulso'] ?? ''));
        $contatoId = (int) ($payload['contato_id'] ?? 0);
        if ($contatoId > 0) {
            $contato = (new ContatoModel())->find($contatoId);
            if ($contato && (int) ($contato['cliente_id'] ?? 0) > 0) {
                $clienteId = (int) $contato['cliente_id'];
                $payload['cliente_id'] = $clienteId;
                return $clienteId;
            }
        }
        if ($this->shouldPreserveClienteEventualIdentity($clienteId, $nomeAvulso)) {
            return 0;
        }
        $telefone = trim((string) ($payload['telefone_contato'] ?? ''));
        if ($this->isPhoneValid($telefone)) {
            $clienteByPhone = $this->findClienteByPhone($telefone);
            if ($clienteByPhone) {
                $clienteId = (int) ($clienteByPhone['id'] ?? 0);
                if ($clienteId > 0) {
                    $payload['cliente_id'] = $clienteId;
                    return $clienteId;
                }
            }
        }
        return 0;
    }
    private function findOrCreateEquipamentoId(
        int $clienteId,
        int $tipoId,
        int $marcaId,
        int $modeloId,
        ?string $cor = null,
        ?string $corHex = null,
        ?string $corRgb = null
    ): int {
        $equipamentoModel = new EquipamentoModel();
        $builder = $equipamentoModel
            ->where('cliente_id', $clienteId)
            ->where('tipo_id', $tipoId)
            ->where('marca_id', $marcaId)
            ->where('modelo_id', $modeloId);
        if ($cor !== null && trim($cor) !== '') {
            $builder->where('cor', trim($cor));
        }
        $existente = $builder->orderBy('id', 'DESC')->first();
        if ($existente) {
            return (int) ($existente['id'] ?? 0);
        }
        $insertData = [
            'cliente_id' => $clienteId,
            'tipo_id' => $tipoId,
            'marca_id' => $marcaId,
            'modelo_id' => $modeloId,
            'cor' => $cor !== null && trim($cor) !== '' ? trim($cor) : null,
            'cor_hex' => $corHex,
            'cor_rgb' => $corRgb,
        ];
        try {
            $id = (int) $equipamentoModel->insert($insertData, true);
            if ($id > 0) {
                return $id;
            }
        } catch (\Throwable $e) {
            log_message('warning', '[Orcamentos] Falha ao criar equipamento pelo formulario: ' . $e->getMessage());
        }
        return 0;
    }
    private function normalizeHexColorValue(string $value): ?string
    {
        $hex = strtoupper(trim($value));
        if ($hex === '') {
            return null;
        }
        if ($hex[0] !== '#') {
            $hex = '#' . $hex;
        }
        return preg_match('/^#[0-9A-F]{6}$/', $hex) === 1 ? $hex : null;
    }
    private function normalizeRgbColorValue(string $value): ?string
    {
        $rgb = trim($value);
        if ($rgb === '') {
            return null;
        }
        if (preg_match('/^\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*$/', $rgb, $matches) !== 1) {
            return null;
        }
        $r = max(0, min(255, (int) $matches[1]));
        $g = max(0, min(255, (int) $matches[2]));
        $b = max(0, min(255, (int) $matches[3]));
        return $r . ',' . $g . ',' . $b;
    }
    private function rgbFromHex(string $hex): ?string
    {
        $hex = $this->normalizeHexColorValue($hex);
        if ($hex === null) {
            return null;
        }
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        return $r . ',' . $g . ',' . $b;
    }
    private function upsertContatoByPayload(string $nome, string $telefone, string $email = '', ?int $clienteId = null): ?int
    {
        if (!$this->isPhoneValid($telefone)) {
            return null;
        }
        $contatoModel = new ContatoModel();
        $contato = $contatoModel->findByPhone($telefone);
        $nome = trim($nome);
        $email = trim($email);
        $basePayload = [
            'cliente_id' => $clienteId,
            'nome' => $nome !== '' ? $nome : null,
            'telefone' => $telefone,
            'email' => $email !== '' ? $email : null,
            'origem' => 'orcamento',
        ];
        if ($contato) {
            $update = [];
            if ((int) ($contato['cliente_id'] ?? 0) <= 0 && $clienteId !== null && $clienteId > 0) {
                $update['cliente_id'] = $clienteId;
            }
            if (trim((string) ($contato['nome'] ?? '')) === '' && $nome !== '') {
                $update['nome'] = $nome;
            }
            if (trim((string) ($contato['email'] ?? '')) === '' && $email !== '') {
                $update['email'] = $email;
            }
            if (!empty($update)) {
                if ($clienteId !== null && $clienteId > 0) {
                    $update = $contatoModel->buildClienteConvertidoPayload($clienteId, $update);
                }
                $contatoModel->update((int) $contato['id'], $update);
            }
            return (int) $contato['id'];
        }
        if ($clienteId !== null && $clienteId > 0) {
            $basePayload = $contatoModel->buildClienteConvertidoPayload($clienteId, $basePayload);
        } else {
            $basePayload = $contatoModel->buildLeadPayload($basePayload, false);
        }
        $insertId = $contatoModel->insert($basePayload, true);
        return $insertId ? (int) $insertId : null;
    }
    private function findClienteByPhone(string $phone): ?array
    {
        $needle = $this->normalizePhone($phone);
        if ($needle === '') {
            return null;
        }
        $rows = (new ClienteModel())
            ->select('id, nome_razao, telefone1, telefone_contato, email')
            ->groupStart()
                ->like('telefone1', $needle)
                ->orLike('telefone_contato', $needle)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->findAll(40);
        foreach ($rows as $row) {
            $fones = [
                $this->normalizePhone((string) ($row['telefone1'] ?? '')),
                $this->normalizePhone((string) ($row['telefone_contato'] ?? '')),
            ];
            foreach ($fones as $fone) {
                if ($this->phoneMatches($needle, $fone)) {
                    return $row;
                }
            }
        }
        return null;
    }
    private function findClienteByEmail(string $email): ?array
    {
        $needle = strtolower(trim($email));
        if ($needle === '' || !filter_var($needle, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return (new ClienteModel())
            ->select('id, nome_razao, telefone1, telefone_contato, email')
            ->where('LOWER(email)', $needle)
            ->orderBy('id', 'DESC')
            ->first();
    }
    private function findContatoByEmail(string $email): ?array
    {
        $needle = strtolower(trim($email));
        if ($needle === '' || !filter_var($needle, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return (new ContatoModel())
            ->select('id, cliente_id, nome, telefone, telefone_normalizado, email, whatsapp_nome_perfil, updated_at')
            ->where('LOWER(email)', $needle)
            ->orderBy('id', 'DESC')
            ->first();
    }
    private function isConsolidatedStatus(string $status): bool
    {
        return in_array($status, [
            OrcamentoModel::STATUS_APROVADO,
            OrcamentoModel::STATUS_PENDENTE_OS,
            OrcamentoModel::STATUS_CONVERTIDO,
        ], true);
    }
    private function promoteContatoToCliente(int $orcamentoId, ?array $orcamento = null, ?int $usuarioId = null): ?int
    {
        $orcamento = $orcamento ?? $this->orcamentoModel->find($orcamentoId);
        if (!$orcamento) {
            return null;
        }
        $clienteId = (int) ($orcamento['cliente_id'] ?? 0);
        $contatoId = (int) ($orcamento['contato_id'] ?? 0);
        $telefone = trim((string) ($orcamento['telefone_contato'] ?? ''));
        $email = trim((string) ($orcamento['email_contato'] ?? ''));
        $nome = trim((string) ($orcamento['cliente_nome_avulso'] ?? ''));
        $contatoModel = new ContatoModel();
        $clienteModel = new ClienteModel();
        $clienteEventualManual = $clienteId <= 0 && $contatoId <= 0 && $nome !== '';
        $contato = null;
        if ($contatoId > 0) {
            $contato = $contatoModel->find($contatoId);
        }
        if (!$clienteEventualManual && !$contato && $this->isPhoneValid($telefone)) {
            $contato = $contatoModel->findByPhone($telefone);
            if ($contato) {
                $contatoId = (int) ($contato['id'] ?? 0);
            }
        }
        if ($contato) {
            if ($nome === '') {
                $nome = trim((string) ($contato['nome'] ?? $contato['whatsapp_nome_perfil'] ?? ''));
            }
            if ($telefone === '') {
                $telefone = trim((string) ($contato['telefone'] ?? ''));
            }
            if ($email === '') {
                $email = trim((string) ($contato['email'] ?? ''));
            }
            if ($clienteId <= 0 && (int) ($contato['cliente_id'] ?? 0) > 0) {
                $clienteId = (int) $contato['cliente_id'];
            }
        }
        if (!$clienteEventualManual && $clienteId <= 0 && $this->isPhoneValid($telefone)) {
            $cliente = $this->findClienteByPhone($telefone);
            if ($cliente) {
                $clienteId = (int) ($cliente['id'] ?? 0);
            }
        }
        if ($clienteId <= 0) {
            if (!$this->isPhoneValid($telefone)) {
                return null;
            }
            if ($nome === '') {
                $nome = 'Cliente ' . substr($this->normalizePhone($telefone), -4);
            }
            if (function_exists('mb_strlen') && mb_strlen($nome, 'UTF-8') < 3) {
                $nome .= ' contato';
            } elseif (strlen($nome) < 3) {
                $nome .= ' contato';
            }
            $clientePayload = [
                'tipo_pessoa' => 'fisica',
                'nome_razao' => $nome,
                'telefone1' => $telefone,
                'email' => $email !== '' ? $email : null,
            ];
            $insert = $clienteModel->insert($clientePayload, true);
            $clienteId = $insert ? (int) $insert : 0;
            if ($clienteId <= 0) {
                return null;
            }
        }
        if ($contatoId <= 0 && $this->isPhoneValid($telefone)) {
            $cadastroExistente = $clienteEventualManual
                ? (
                    $this->findClienteByPhone($telefone)
                    ?? $contatoModel->findByPhone($telefone)
                    ?? $this->findClienteByEmail($email)
                    ?? $this->findContatoByEmail($email)
                )
                : null;

            if (!$clienteEventualManual || $cadastroExistente === null) {
                $contatoId = $this->upsertContatoByPayload($nome, $telefone, $email, $clienteId);
            }
        } elseif ($contatoId > 0) {
            $contatoUpdate = $contatoModel->buildClienteConvertidoPayload($clienteId, ['cliente_id' => $clienteId]);
            if ($email !== '') {
                $contatoUpdate['email'] = $email;
            }
            if ($nome !== '') {
                $contatoUpdate['nome'] = $nome;
            }
            if ($telefone !== '') {
                $contatoUpdate['telefone'] = $telefone;
            }
            $contatoModel->update($contatoId, $contatoUpdate);
        }
        $updateOrcamento = [
            'cliente_id' => $clienteId,
            'cliente_nome_avulso' => null,
            'contato_id' => $contatoId > 0 ? $contatoId : null,
            'atualizado_por' => $usuarioId,
        ];
        $this->orcamentoModel->update($orcamentoId, $updateOrcamento);
        return $clienteId;
    }
    private function findOrcamento(int $orcamentoId): ?array
    {
        $builder = $this->orcamentoModel
            ->select('orcamentos.*, clientes.nome_razao as cliente_nome, os.numero_os, conversas_whatsapp.telefone as conversa_telefone, contatos.nome as contato_nome, contatos.telefone as contato_telefone, contatos.email as contato_email, revisao_base.numero as revisao_base_numero, revisao_base.versao as revisao_base_versao')
            ->join('clientes', 'clientes.id = orcamentos.cliente_id', 'left')
            ->join('os', 'os.id = orcamentos.os_id', 'left')
            ->join('conversas_whatsapp', 'conversas_whatsapp.id = orcamentos.conversa_id', 'left')
            ->join('contatos', 'contatos.id = orcamentos.contato_id', 'left')
            ->join('orcamentos revisao_base', 'revisao_base.id = orcamentos.orcamento_revisao_de_id', 'left');
        if ($this->orcamentoModel->db->tableExists('orcamento_envios')) {
            $enviosJoin = "(SELECT orcamento_id, COUNT(*) AS confirmados"
                . " FROM orcamento_envios"
                . " WHERE status IN ('enviado','duplicado')"
                . " AND canal IN ('whatsapp','email')"
                . " GROUP BY orcamento_id) envios";
            $builder->select('envios.confirmados as envios_confirmados')
                ->join($enviosJoin, 'envios.orcamento_id = orcamentos.id', 'left', false);
        }
        $row = $builder
            ->where('orcamentos.id', $orcamentoId)
            ->first();
        return $row ? $this->normalizeOrcamentoRecord($row) : null;
    }
    private function buildFormData(array $overrides = []): array
    {
        $orcamento = (array) ($overrides['orcamento'] ?? []);
        $orcamento = $this->normalizeOrcamentoRecord($orcamento);
        $clienteLookupInitial = $this->buildClienteLookupInitial($orcamento);
        $equipamentoLookupInitial = $this->buildEquipamentoLookupInitial($orcamento);
        $vinculosContext = $this->resolveVinculosContext($orcamento);
        $equipamentoCatalog = $this->buildEquipamentoCatalog();
        $equipamentoManual = $this->resolveEquipamentoManualContext($orcamento);
        $defaults = [
            'clientes' => [],
            'statusLabels' => $this->orcamentoModel->statusLabels(),
            'statusOptions' => $this->orcamentoModel->statusLabels(),
            'tipoLabels' => $this->orcamentoModel->tipoLabels(),
            'clienteLookupInitial' => $clienteLookupInitial,
            'equipamentoLookupInitial' => $equipamentoLookupInitial,
            'vinculosContext' => $vinculosContext,
            'equipamentoCatalog' => $equipamentoCatalog,
            'equipamentoManual' => $equipamentoManual,
            'pacoteOfertaModuleReady' => $this->isPacoteOfertaModuleReady(),
            'pacotesAtivosOferta' => $this->loadPacotesAtivosForOferta(),
            'orcamentoLockedEmbeddedEdit' => false,
        ];
        return array_merge($defaults, $overrides);
    }
    /**
     * @return array<string,mixed>
     */
    private function buildEquipamentoCatalog(): array
    {
        $tipoRows = (new EquipamentoTipoModel())
            ->select('id, nome')
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->findAll();
        $marcaRows = (new EquipamentoMarcaModel())
            ->select('id, nome')
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->findAll();
        $modeloRows = (new EquipamentoModeloModel())
            ->select('id, marca_id, nome')
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->findAll();
        $tipos = array_map(static fn(array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'nome' => trim((string) ($row['nome'] ?? '')),
        ], $tipoRows);
        $marcasAll = array_map(static fn(array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'nome' => trim((string) ($row['nome'] ?? '')),
        ], $marcaRows);
        $modelosByMarca = [];
        $marcaById = [];
        foreach ($marcasAll as $marca) {
            $marcaId = (int) ($marca['id'] ?? 0);
            if ($marcaId <= 0) {
                continue;
            }
            $marcaById[$marcaId] = $marca;
            $modelosByMarca[$marcaId] = [];
        }
        $modeloById = [];
        foreach ($modeloRows as $modeloRow) {
            $modeloId = (int) ($modeloRow['id'] ?? 0);
            $marcaId = (int) ($modeloRow['marca_id'] ?? 0);
            if ($modeloId <= 0 || $marcaId <= 0 || !isset($marcaById[$marcaId])) {
                continue;
            }
            $model = [
                'id' => $modeloId,
                'marca_id' => $marcaId,
                'nome' => trim((string) ($modeloRow['nome'] ?? '')),
            ];
            $modeloById[$modeloId] = $model;
            $modelosByMarca[$marcaId][] = $model;
        }
        $db = $this->orcamentoModel->db;
        $tipoMarcaRows = [];
        $tipoMarcaModeloRows = [];
        if ($db->tableExists(self::RELATION_TABLE)) {
            $tipoMarcaRows = $db->table(self::RELATION_TABLE . ' rel')
                ->select('rel.tipo_id, rel.marca_id')
                ->join('equipamentos_modelos mod', 'mod.id = rel.modelo_id', 'inner')
                ->where('rel.ativo', 1)
                ->where('mod.ativo', 1)
                ->where('rel.tipo_id >', 0)
                ->where('rel.marca_id >', 0)
                ->groupBy('rel.tipo_id, rel.marca_id')
                ->get()
                ->getResultArray();
            $tipoMarcaModeloRows = $db->table(self::RELATION_TABLE . ' rel')
                ->select('rel.tipo_id, rel.marca_id, rel.modelo_id')
                ->join('equipamentos_modelos mod', 'mod.id = rel.modelo_id', 'inner')
                ->where('rel.ativo', 1)
                ->where('mod.ativo', 1)
                ->where('rel.tipo_id >', 0)
                ->where('rel.marca_id >', 0)
                ->where('rel.modelo_id >', 0)
                ->groupBy('rel.tipo_id, rel.marca_id, rel.modelo_id')
                ->get()
                ->getResultArray();
        }
        // Fallback legado: bases antigas sem tabela de relacao ou ainda sem consolidacao.
        if (empty($tipoMarcaRows) || empty($tipoMarcaModeloRows)) {
            $legacyTipoMarcaRows = $db->table('equipamentos')
                ->select('tipo_id, marca_id')
                ->where('tipo_id >', 0)
                ->where('marca_id >', 0)
                ->groupBy('tipo_id, marca_id')
                ->get()
                ->getResultArray();
            $legacyTipoMarcaModeloRows = $db->table('equipamentos')
                ->select('tipo_id, marca_id, modelo_id')
                ->where('tipo_id >', 0)
                ->where('marca_id >', 0)
                ->where('modelo_id >', 0)
                ->groupBy('tipo_id, marca_id, modelo_id')
                ->get()
                ->getResultArray();
            $tipoMarcaRows = $this->mergeTipoMarcaRows($tipoMarcaRows, $legacyTipoMarcaRows);
            $tipoMarcaModeloRows = $this->mergeTipoMarcaModeloRows($tipoMarcaModeloRows, $legacyTipoMarcaModeloRows);
        }
        $marcasByTipo = [];
        foreach ($tipoMarcaRows as $row) {
            $tipoId = (int) ($row['tipo_id'] ?? 0);
            $marcaId = (int) ($row['marca_id'] ?? 0);
            if ($tipoId <= 0 || $marcaId <= 0 || !isset($marcaById[$marcaId])) {
                continue;
            }
            if (!isset($marcasByTipo[$tipoId])) {
                $marcasByTipo[$tipoId] = [];
            }
            $marcasByTipo[$tipoId][$marcaId] = $marcaById[$marcaId];
        }
        $modelosByTipoMarca = [];
        foreach ($tipoMarcaModeloRows as $row) {
            $tipoId = (int) ($row['tipo_id'] ?? 0);
            $marcaId = (int) ($row['marca_id'] ?? 0);
            $modeloId = (int) ($row['modelo_id'] ?? 0);
            if ($tipoId <= 0 || $marcaId <= 0 || $modeloId <= 0 || !isset($modeloById[$modeloId])) {
                continue;
            }
            if ((int) ($modeloById[$modeloId]['marca_id'] ?? 0) !== $marcaId) {
                continue;
            }
            if (!isset($modelosByTipoMarca[$tipoId])) {
                $modelosByTipoMarca[$tipoId] = [];
            }
            if (!isset($modelosByTipoMarca[$tipoId][$marcaId])) {
                $modelosByTipoMarca[$tipoId][$marcaId] = [];
            }
            $modelosByTipoMarca[$tipoId][$marcaId][$modeloId] = $modeloById[$modeloId];
        }
        // Garante que toda combinacao tipo+marca tenha lista de modelos disponivel,
        // mesmo quando ainda nao houver historico em equipamentos para aquela combinacao.
        foreach ($marcasByTipo as $tipoId => $marcasDoTipo) {
            foreach ($marcasDoTipo as $marca) {
                $marcaId = (int) ($marca['id'] ?? 0);
                if ($marcaId <= 0) {
                    continue;
                }
                if (!isset($modelosByTipoMarca[$tipoId])) {
                    $modelosByTipoMarca[$tipoId] = [];
                }
                if (!isset($modelosByTipoMarca[$tipoId][$marcaId]) || !is_array($modelosByTipoMarca[$tipoId][$marcaId])) {
                    $modelosByTipoMarca[$tipoId][$marcaId] = [];
                }
                if (empty($modelosByTipoMarca[$tipoId][$marcaId]) && isset($modelosByMarca[$marcaId])) {
                    $modelosByTipoMarca[$tipoId][$marcaId] = $modelosByMarca[$marcaId];
                }
            }
        }
        $this->sortCatalogItemsByNome($marcasAll);
        foreach ($modelosByMarca as $marcaId => $items) {
            $this->sortCatalogItemsByNome($items);
            $modelosByMarca[$marcaId] = array_values($items);
        }
        foreach ($marcasByTipo as $tipoId => $items) {
            $items = array_values($items);
            $this->sortCatalogItemsByNome($items);
            $marcasByTipo[$tipoId] = $items;
        }
        foreach ($modelosByTipoMarca as $tipoId => $marcas) {
            foreach ($marcas as $marcaId => $items) {
                $items = array_values($items);
                $this->sortCatalogItemsByNome($items);
                $modelosByTipoMarca[$tipoId][$marcaId] = $items;
            }
        }
        return [
            'tipos' => $tipos,
            'marcasAll' => $marcasAll,
            'marcasByTipo' => $marcasByTipo,
            'modelosByMarca' => $modelosByMarca,
            'modelosByTipoMarca' => $modelosByTipoMarca,
        ];
    }
    private function sortCatalogItemsByNome(array &$items): void
    {
        usort($items, static function (array $a, array $b): int {
            return strnatcasecmp((string) ($a['nome'] ?? ''), (string) ($b['nome'] ?? ''));
        });
    }
    /**
     * @param array<int,array<string,mixed>> $base
     * @param array<int,array<string,mixed>> $extra
     * @return array<int,array<string,mixed>>
     */
    private function mergeTipoMarcaRows(array $base, array $extra): array
    {
        $unique = [];
        foreach (array_merge($base, $extra) as $row) {
            $tipoId = (int) ($row['tipo_id'] ?? 0);
            $marcaId = (int) ($row['marca_id'] ?? 0);
            if ($tipoId <= 0 || $marcaId <= 0) {
                continue;
            }
            $unique[$tipoId . ':' . $marcaId] = [
                'tipo_id' => $tipoId,
                'marca_id' => $marcaId,
            ];
        }
        return array_values($unique);
    }
    /**
     * @param array<int,array<string,mixed>> $base
     * @param array<int,array<string,mixed>> $extra
     * @return array<int,array<string,mixed>>
     */
    private function mergeTipoMarcaModeloRows(array $base, array $extra): array
    {
        $unique = [];
        foreach (array_merge($base, $extra) as $row) {
            $tipoId = (int) ($row['tipo_id'] ?? 0);
            $marcaId = (int) ($row['marca_id'] ?? 0);
            $modeloId = (int) ($row['modelo_id'] ?? 0);
            if ($tipoId <= 0 || $marcaId <= 0 || $modeloId <= 0) {
                continue;
            }
            $unique[$tipoId . ':' . $marcaId . ':' . $modeloId] = [
                'tipo_id' => $tipoId,
                'marca_id' => $marcaId,
                'modelo_id' => $modeloId,
            ];
        }
        return array_values($unique);
    }
    private function syncCatalogoRelacao(int $tipoId, int $marcaId, int $modeloId): void
    {
        if ($tipoId <= 0 || $marcaId <= 0 || $modeloId <= 0) {
            return;
        }
        try {
            if (!$this->orcamentoModel->db->tableExists(self::RELATION_TABLE)) {
                return;
            }
            $this->orcamentoModel->db->query(
                'INSERT IGNORE INTO ' . self::RELATION_TABLE . ' (tipo_id, marca_id, modelo_id, ativo, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())',
                [$tipoId, $marcaId, $modeloId]
            );
        } catch (\Throwable $e) {
            log_message('warning', '[Orcamentos] Falha ao sincronizar relacao de catalogo: ' . $e->getMessage());
        }
    }
    /**
     * @return array<string,mixed>
     */
    private function resolveEquipamentoManualContext(array $orcamento): array
    {
        $manual = [
            'tipo_id' => (int) ($orcamento['equipamento_tipo_id'] ?? 0),
            'marca_id' => (int) ($orcamento['equipamento_marca_id'] ?? 0),
            'modelo_id' => (int) ($orcamento['equipamento_modelo_id'] ?? 0),
            'cor' => trim((string) ($orcamento['equipamento_cor'] ?? '')),
            'cor_hex' => $this->normalizeHexColorValue((string) ($orcamento['equipamento_cor_hex'] ?? '')) ?? '',
            'cor_rgb' => $this->normalizeRgbColorValue((string) ($orcamento['equipamento_cor_rgb'] ?? '')) ?? '',
        ];
        $equipamentoId = (int) ($orcamento['equipamento_id'] ?? 0);
        if ($equipamentoId > 0) {
            $this->applyEquipamentoSnapshotFromId($manual, $equipamentoId);
        }
        if ($manual['cor_rgb'] === '' && $manual['cor_hex'] !== '') {
            $manual['cor_rgb'] = $this->rgbFromHex($manual['cor_hex']) ?? '';
        }
        return $manual;
    }
    private function applyEquipamentoSnapshotFromId(array &$payload, int $equipamentoId): void
    {
        if ($equipamentoId <= 0) {
            return;
        }
        $equipamento = (new EquipamentoModel())->find($equipamentoId);
        if (!$equipamento) {
            return;
        }
        if ((int) ($payload['equipamento_tipo_id'] ?? 0) <= 0) {
            $payload['equipamento_tipo_id'] = (int) ($equipamento['tipo_id'] ?? 0) ?: null;
        }
        if ((int) ($payload['equipamento_marca_id'] ?? 0) <= 0) {
            $payload['equipamento_marca_id'] = (int) ($equipamento['marca_id'] ?? 0) ?: null;
        }
        if ((int) ($payload['equipamento_modelo_id'] ?? 0) <= 0) {
            $payload['equipamento_modelo_id'] = (int) ($equipamento['modelo_id'] ?? 0) ?: null;
        }
        $tipoId = (int) ($payload['equipamento_tipo_id'] ?? 0);
        $marcaId = (int) ($payload['equipamento_marca_id'] ?? 0);
        $modeloId = (int) ($payload['equipamento_modelo_id'] ?? 0);
        if ($tipoId > 0 && $marcaId > 0 && $modeloId > 0) {
            $this->syncCatalogoRelacao($tipoId, $marcaId, $modeloId);
        }
        if (trim((string) ($payload['equipamento_cor'] ?? '')) === '') {
            $payload['equipamento_cor'] = trim((string) ($equipamento['cor'] ?? '')) ?: null;
        }
        if (trim((string) ($payload['equipamento_cor_hex'] ?? '')) === '') {
            $payload['equipamento_cor_hex'] = $this->normalizeHexColorValue((string) ($equipamento['cor_hex'] ?? ''));
        }
        if (trim((string) ($payload['equipamento_cor_rgb'] ?? '')) === '') {
            $payload['equipamento_cor_rgb'] = $this->normalizeRgbColorValue((string) ($equipamento['cor_rgb'] ?? ''));
        }
    }
    private function prefillFromRequest(): array
    {
        $prefill = [
            'versao' => 1,
            'tipo_orcamento' => $this->orcamentoModel->normalizeTipo(
                (string) $this->request->getGet('tipo_orcamento'),
                (int) ($this->request->getGet('os_id') ?? 0) ?: null
            ),
            'origem' => $this->normalizeOrigemOrcamento((string) $this->request->getGet('origem')),
            'cliente_id' => (int) ($this->request->getGet('cliente_id') ?? 0) ?: null,
            'contato_id' => (int) ($this->request->getGet('contato_id') ?? 0) ?: null,
            'os_id' => (int) ($this->request->getGet('os_id') ?? 0) ?: null,
            'equipamento_id' => (int) ($this->request->getGet('equipamento_id') ?? 0) ?: null,
            'conversa_id' => (int) ($this->request->getGet('conversa_id') ?? 0) ?: null,
            'telefone_contato' => trim((string) $this->request->getGet('telefone')),
            'email_contato' => trim((string) $this->request->getGet('email')),
            'cliente_nome_avulso' => trim((string) ($this->request->getGet('nome_hint') ?? $this->request->getGet('nome'))),
            'equipamento_tipo_id' => (int) ($this->request->getGet('equipamento_tipo_id') ?? 0) ?: null,
            'equipamento_marca_id' => (int) ($this->request->getGet('equipamento_marca_id') ?? 0) ?: null,
            'equipamento_modelo_id' => (int) ($this->request->getGet('equipamento_modelo_id') ?? 0) ?: null,
            'equipamento_cor' => trim((string) $this->request->getGet('equipamento_cor')) ?: null,
            'equipamento_cor_hex' => $this->normalizeHexColorValue((string) ($this->request->getGet('equipamento_cor_hex') ?? '')),
            'equipamento_cor_rgb' => $this->normalizeRgbColorValue((string) ($this->request->getGet('equipamento_cor_rgb') ?? '')),
            'validade_dias' => 10,
            'validade_data' => date('Y-m-d', strtotime('+10 days')),
            'desconto' => 0,
            'acrescimo' => 0,
            'subtotal' => 0,
            'total' => 0,
            'titulo' => '',
            'prazo_execucao' => '3',
            'observacoes' => '',
            'condicoes' => '',
        ];

        $origemSolicitada = strtolower(trim((string) ($prefill['origem'] ?? 'manual')));
        $conversaClienteId = 0;

        $osId = (int) ($prefill['os_id'] ?? 0);
        if ($osId > 0 && $origemSolicitada !== 'conversa') {
            $prefill['origem'] = 'os';
            $os = (new OsModel())->getComplete($osId);
            if ($os) {
                $prefill['cliente_id'] = (int) ($os['cliente_id'] ?? 0) ?: $prefill['cliente_id'];
                $prefill['equipamento_id'] = (int) ($os['equipamento_id'] ?? 0) ?: $prefill['equipamento_id'];
                $prefill['telefone_contato'] = (string) ($os['cliente_telefone'] ?? $prefill['telefone_contato']);
                $prefill['email_contato'] = (string) ($os['cliente_email'] ?? $prefill['email_contato']);
                $prefill['titulo'] = 'Orcamento para OS ' . (string) ($os['numero_os'] ?? ('#' . $osId));
                $prefill['observacoes'] = (string) ($os['diagnostico_tecnico'] ?? '');
            }
        }

        $conversaId = (int) ($prefill['conversa_id'] ?? 0);
        if ($conversaId > 0) {
            if ($origemSolicitada === 'conversa' || (int) ($prefill['os_id'] ?? 0) <= 0) {
                $prefill['origem'] = 'conversa';
            }

            $conversa = (new ConversaWhatsappModel())->find($conversaId);
            if ($conversa) {
                $conversaClienteId = (int) ($conversa['cliente_id'] ?? 0);
                $conversaContatoId = (int) ($conversa['contato_id'] ?? 0);
                $conversaOsPrincipalId = (int) ($conversa['os_id_principal'] ?? 0);

                if ($origemSolicitada === 'conversa') {
                    if ($conversaClienteId > 0) {
                        $prefill['cliente_id'] = $conversaClienteId;
                    }
                    if ($conversaContatoId > 0) {
                        $prefill['contato_id'] = $conversaContatoId;
                    }
                    if ($conversaOsPrincipalId > 0) {
                        $prefill['os_id'] = $conversaOsPrincipalId;
                    }
                } else {
                    if ($conversaClienteId > 0 && empty($prefill['cliente_id'])) {
                        $prefill['cliente_id'] = $conversaClienteId;
                    }
                    if ($conversaContatoId > 0 && empty($prefill['contato_id'])) {
                        $prefill['contato_id'] = $conversaContatoId;
                    }
                    if ($conversaOsPrincipalId > 0 && empty($prefill['os_id'])) {
                        $prefill['os_id'] = $conversaOsPrincipalId;
                    }
                }

                if (trim((string) ($prefill['telefone_contato'] ?? '')) === '') {
                    $prefill['telefone_contato'] = (string) ($conversa['telefone'] ?? '');
                }
                if (trim((string) ($prefill['cliente_nome_avulso'] ?? '')) === '') {
                    $prefill['cliente_nome_avulso'] = (string) ($conversa['nome_contato'] ?? '');
                }
            }
        }

        $osId = (int) ($prefill['os_id'] ?? 0);
        if ($osId > 0 && $origemSolicitada === 'conversa') {
            $os = (new OsModel())->getComplete($osId);
            if ($os) {
                $osClienteId = (int) ($os['cliente_id'] ?? 0);
                $osInconsistenteComConversa = $conversaClienteId > 0
                    && $osClienteId > 0
                    && $osClienteId !== $conversaClienteId;

                if ($osInconsistenteComConversa) {
                    // Protege o prefill para nao puxar OS de outro cliente em conversa ativa.
                    $prefill['os_id'] = null;
                    $prefill['equipamento_id'] = null;
                    $prefill['equipamento_tipo_id'] = null;
                    $prefill['equipamento_marca_id'] = null;
                    $prefill['equipamento_modelo_id'] = null;
                    $prefill['equipamento_cor'] = null;
                    $prefill['equipamento_cor_hex'] = null;
                    $prefill['equipamento_cor_rgb'] = null;
                } else {
                    $prefill['cliente_id'] = $osClienteId > 0 ? $osClienteId : $prefill['cliente_id'];
                    $prefill['equipamento_id'] = (int) ($os['equipamento_id'] ?? 0) ?: $prefill['equipamento_id'];
                    $prefill['telefone_contato'] = (string) ($os['cliente_telefone'] ?? $prefill['telefone_contato']);
                    $prefill['email_contato'] = (string) ($os['cliente_email'] ?? $prefill['email_contato']);
                    $prefill['titulo'] = 'Orcamento para OS ' . (string) ($os['numero_os'] ?? ('#' . $osId));
                    $prefill['observacoes'] = (string) ($os['diagnostico_tecnico'] ?? $prefill['observacoes']);
                }
            }
        }

        $telefonePrefill = trim((string) ($prefill['telefone_contato'] ?? ''));
        $clienteEventualPrefill = $this->shouldPreserveClienteEventualIdentity(
            (int) ($prefill['cliente_id'] ?? 0),
            (string) ($prefill['cliente_nome_avulso'] ?? '')
        );

        if (!$clienteEventualPrefill && (int) ($prefill['cliente_id'] ?? 0) <= 0 && $this->isPhoneValid($telefonePrefill)) {
            $clienteByPhone = $this->findClienteByPhone($telefonePrefill);
            if ($clienteByPhone) {
                $prefill['cliente_id'] = (int) ($clienteByPhone['id'] ?? 0) ?: null;
                if (trim((string) ($prefill['email_contato'] ?? '')) === '') {
                    $prefill['email_contato'] = (string) ($clienteByPhone['email'] ?? '');
                }
                if (trim((string) ($prefill['telefone_contato'] ?? '')) === '') {
                    $prefill['telefone_contato'] = (string) ($clienteByPhone['telefone1'] ?? $clienteByPhone['telefone_contato'] ?? '');
                }
            }
        }
        if (
            !$clienteEventualPrefill
            && (int) ($prefill['cliente_id'] ?? 0) <= 0
            && (int) ($prefill['contato_id'] ?? 0) <= 0
            && $this->isPhoneValid($telefonePrefill)
        ) {
            $contatoByPhone = (new ContatoModel())->findByPhone($telefonePrefill);
            if ($contatoByPhone) {
                $prefill['contato_id'] = (int) ($contatoByPhone['id'] ?? 0) ?: null;
                if (trim((string) ($prefill['email_contato'] ?? '')) === '') {
                    $prefill['email_contato'] = (string) ($contatoByPhone['email'] ?? '');
                }
                if (trim((string) ($prefill['cliente_nome_avulso'] ?? '')) === '') {
                    $prefill['cliente_nome_avulso'] = (string) ($contatoByPhone['nome'] ?? $contatoByPhone['whatsapp_nome_perfil'] ?? '');
                }
            }
        }
        $clienteId = (int) ($prefill['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            $cliente = (new ClienteModel())->find($clienteId);
            if ($cliente) {
                if (trim((string) ($prefill['telefone_contato'] ?? '')) === '') {
                    $prefill['telefone_contato'] = (string) ($cliente['telefone1'] ?? '');
                }
                if (trim((string) ($prefill['email_contato'] ?? '')) === '') {
                    $prefill['email_contato'] = (string) ($cliente['email'] ?? '');
                }
                $prefill['cliente_nome_avulso'] = '';
            }
        }
        $contatoId = (int) ($prefill['contato_id'] ?? 0);
        if ($contatoId > 0) {
            $contato = (new ContatoModel())->find($contatoId);
            if ($contato) {
                if (trim((string) ($prefill['telefone_contato'] ?? '')) === '') {
                    $prefill['telefone_contato'] = (string) ($contato['telefone'] ?? '');
                }
                if (trim((string) ($prefill['email_contato'] ?? '')) === '') {
                    $prefill['email_contato'] = (string) ($contato['email'] ?? '');
                }
                if ((int) ($prefill['cliente_id'] ?? 0) <= 0 && (int) ($contato['cliente_id'] ?? 0) > 0) {
                    $prefill['cliente_id'] = (int) $contato['cliente_id'];
                }
                if (trim((string) ($prefill['cliente_nome_avulso'] ?? '')) === '') {
                    $prefill['cliente_nome_avulso'] = (string) ($contato['nome'] ?? $contato['whatsapp_nome_perfil'] ?? '');
                }
            }
        }
        $equipamentoId = (int) ($prefill['equipamento_id'] ?? 0);
        if ($equipamentoId > 0 && empty($prefill['os_id'])) {
            $equipamento = (new EquipamentoModel())->find($equipamentoId);
            if ($equipamento && empty($prefill['cliente_id'])) {
                $prefill['cliente_id'] = (int) ($equipamento['cliente_id'] ?? 0) ?: null;
            }
        }
        if ($equipamentoId > 0) {
            $this->applyEquipamentoSnapshotFromId($prefill, $equipamentoId);
        }
        if ((int) ($prefill['cliente_id'] ?? 0) <= 0 && (int) ($prefill['contato_id'] ?? 0) <= 0) {
            $telefoneFallback = trim((string) ($prefill['telefone_contato'] ?? ''));
            if (trim((string) ($prefill['cliente_nome_avulso'] ?? '')) === '' && $telefoneFallback !== '') {
                $prefill['cliente_nome_avulso'] = $telefoneFallback;
            }
        }
        $prefill['tipo_orcamento'] = $this->orcamentoModel->normalizeTipo(
            (string) ($prefill['tipo_orcamento'] ?? ''),
            (int) ($prefill['os_id'] ?? 0) ?: null
        );

        return $prefill;
    }
    /**
     * @return array{
     *   oferta_id:int,
     *   aplicar:bool,
     *   orcamento_baseado_pacote:bool,
     *   pacote_servico_id:int,
     *   telefone:string,
     *   enviar_whatsapp:bool,
     *   mensagem:string,
     *   mensagem_personalizada_com_link:bool
     * }
     */
    private function extractPacoteOfertaIntent(): array
    {
        $mensagemComLinkRaw = $this->request->getPost('pacote_oferta_mensagem_com_link');
        $enviarWhatsappRaw = $this->request->getPost('pacote_oferta_enviar_whatsapp');
        return [
            'oferta_id' => (int) ($this->request->getPost('pacote_oferta_id') ?? 0),
            'aplicar' => (string) ($this->request->getPost('aplicar_pacote_oferta') ?? '0') === '1',
            'orcamento_baseado_pacote' => (string) ($this->request->getPost('orcamento_baseado_pacote') ?? '0') === '1',
            'pacote_servico_id' => (int) ($this->request->getPost('pacote_oferta_pacote_id') ?? 0),
            'telefone' => $this->normalizeWhatsAppPhone((string) ($this->request->getPost('pacote_oferta_telefone') ?? '')),
            'enviar_whatsapp' => $enviarWhatsappRaw === null ? true : ((string) $enviarWhatsappRaw === '1'),
            'mensagem' => trim((string) ($this->request->getPost('pacote_oferta_mensagem') ?? '')),
            'mensagem_personalizada_com_link' => $mensagemComLinkRaw === null ? true : ((string) $mensagemComLinkRaw === '1'),
        ];
    }
    /**
     * @param array<string,mixed> $intent
     * @param array<string,mixed> $payload
     */
    private function validatePacoteOfertaAutosendIntent(array $intent, array $payload): ?string
    {
        if (!$this->isPacoteOfertaModuleReady()) {
            return 'Modulo de ofertas de pacote nao inicializado. Execute as migracoes.';
        }
        $pacoteId = (int) ($intent['pacote_servico_id'] ?? 0);
        if ($pacoteId <= 0) {
            return 'Selecione um pacote para envio automatico da oferta.';
        }
        $telefone = $this->normalizeWhatsAppPhone((string) ($intent['telefone'] ?? $payload['telefone_contato'] ?? ''));
        if ($telefone === '') {
            return 'Informe um telefone WhatsApp valido para envio da oferta de pacote.';
        }
        $enviarWhatsapp = !empty($intent['enviar_whatsapp']);
        if ($enviarWhatsapp && !$this->isWhatsAppPhoneValid($telefone)) {
            return 'Telefone WhatsApp invalido para envio da oferta de pacote.';
        }
        return null;
    }
    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $intent
     * @return array{oferta_id:?int,warning:?string,error:?string}
     */
    private function createPacoteOfertaForOrcamento(
        int $orcamentoId,
        array $payload,
        array $intent,
        ?int $usuarioId = null
    ): array {
        if ($orcamentoId <= 0) {
            return ['oferta_id' => null, 'warning' => null, 'error' => 'Orcamento invalido para gerar oferta de pacote.'];
        }
        if (!$this->isPacoteOfertaModuleReady()) {
            return ['oferta_id' => null, 'warning' => null, 'error' => 'Modulo de ofertas de pacote nao inicializado.'];
        }
        $existing = $this->findLatestPacoteOfertaByOrcamento($orcamentoId);
        $existingStatus = trim((string) ($existing['status'] ?? ''));
        if ($existing !== null && in_array($existingStatus, ['ativo', 'enviado', 'escolhido', 'aplicado_orcamento'], true)) {
            return ['oferta_id' => (int) ($existing['id'] ?? 0) ?: null, 'warning' => null, 'error' => null];
        }
        $pacoteId = (int) ($intent['pacote_servico_id'] ?? 0);
        if ($pacoteId <= 0) {
            return ['oferta_id' => null, 'warning' => null, 'error' => 'Selecione um pacote para envio automatico da oferta.'];
        }
        $pacote = (new PacoteServicoModel())
            ->where('id', $pacoteId)
            ->where('ativo', 1)
            ->first();
        if (!$pacote) {
            return ['oferta_id' => null, 'warning' => null, 'error' => 'Pacote de servicos nao encontrado ou inativo para envio automatico.'];
        }
        $niveis = (new PacoteServicoNivelModel())
            ->where('pacote_servico_id', $pacoteId)
            ->where('ativo', 1)
            ->orderBy('ordem', 'ASC')
            ->findAll();
        if (empty($niveis)) {
            return ['oferta_id' => null, 'warning' => null, 'error' => 'Este pacote nao possui niveis ativos para envio automatico.'];
        }
        $clienteId = (int) ($payload['cliente_id'] ?? 0);
        $contatoId = (int) ($payload['contato_id'] ?? 0);
        $osId = (int) ($payload['os_id'] ?? 0);
        $equipamentoId = (int) ($payload['equipamento_id'] ?? 0);
        $telefone = $this->normalizeWhatsAppPhone((string) ($intent['telefone'] ?? $payload['telefone_contato'] ?? ''));
        if ($telefone === '') {
            return ['oferta_id' => null, 'warning' => null, 'error' => 'Informe um telefone WhatsApp valido para envio da oferta de pacote.'];
        }
        $enviarWhatsapp = !empty($intent['enviar_whatsapp']);
        if ($enviarWhatsapp && !$this->isWhatsAppPhoneValid($telefone)) {
            return ['oferta_id' => null, 'warning' => null, 'error' => 'Telefone WhatsApp invalido para envio da oferta de pacote.'];
        }
        // Regra oficial: link da oferta dinamica sempre com validade de 48 horas.
        $expiraEm = date('Y-m-d H:i:s', strtotime('+48 hours'));
        $token = $this->orcamentoService->generateToken();
        $linkPublico = base_url('pacote/oferta/' . $token);
        $mensagemPersonalizada = trim((string) ($intent['mensagem'] ?? ''));
        $mensagemPersonalizadaComLink = !empty($intent['mensagem_personalizada_com_link']);
        if ($mensagemPersonalizada === '') {
            $mensagem = $this->buildDefaultPacoteOfertaMessage(
                $clienteId > 0 ? $clienteId : 0,
                $contatoId > 0 ? $contatoId : 0,
                $telefone,
                $pacote,
                $niveis,
                $linkPublico,
                $expiraEm
            );
        } else {
            $mensagem = $mensagemPersonalizada;
            if ($mensagemPersonalizadaComLink) {
                $mensagem = $this->appendPacoteOfertaLink($mensagemPersonalizada, $linkPublico, $expiraEm);
            }
        }
        $cancelByPhone = ($clienteId > 0 || $contatoId > 0 || $osId > 0 || $equipamentoId > 0)
            ? $telefone
            : '';
        $this->cancelActivePacotesOfertasByIdentity(
            $clienteId > 0 ? $clienteId : null,
            $contatoId > 0 ? $contatoId : null,
            $cancelByPhone,
            $osId > 0 ? $osId : null,
            $equipamentoId > 0 ? $equipamentoId : null
        );
        if ($existing !== null && (int) ($existing['id'] ?? 0) > 0 && $existingStatus === 'erro_envio') {
            $this->pacoteOfertaModel->update((int) $existing['id'], [
                'status' => 'cancelado',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->pacoteOfertaModel->insert([
            'pacote_servico_id' => $pacoteId,
            'cliente_id' => $clienteId > 0 ? $clienteId : null,
            'contato_id' => $contatoId > 0 ? $contatoId : null,
            'telefone_destino' => $telefone,
            'os_id' => $osId > 0 ? $osId : null,
            'equipamento_id' => $equipamentoId > 0 ? $equipamentoId : null,
            'orcamento_id' => $orcamentoId,
            'origem_contexto' => trim((string) ($payload['origem'] ?? 'manual')) ?: 'manual',
            'token_publico' => $token,
            'status' => $enviarWhatsapp ? 'enviado' : 'ativo',
            'destino_canal' => $enviarWhatsapp ? 'whatsapp' : 'manual',
            'mensagem_enviada' => $mensagem,
            'expira_em' => $expiraEm,
            'enviado_em' => $enviarWhatsapp ? date('Y-m-d H:i:s') : null,
        ]);
        $ofertaId = (int) $this->pacoteOfertaModel->getInsertID();
        if ($ofertaId <= 0) {
            return ['oferta_id' => null, 'warning' => null, 'error' => 'Nao foi possivel criar a oferta automatica de pacote para este orcamento.'];
        }
        $warning = null;
        if ($enviarWhatsapp) {
            $dispatch = $this->dispatchPacoteOfertaWhatsApp(
                $ofertaId,
                $osId > 0 ? $osId : null,
                $clienteId > 0 ? $clienteId : null,
                $telefone,
                $mensagem,
                $usuarioId
            );
            if (empty($dispatch['ok'])) {
                $this->pacoteOfertaModel->update($ofertaId, [
                    'status' => 'erro_envio',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $errorMessage = trim((string) ($dispatch['message'] ?? 'Falha ao enviar oferta via WhatsApp.'));
                $warning = $errorMessage . ' O orcamento foi salvo e o link da oferta pode ser compartilhado manualmente.';
            }
        }
        LogModel::registrar('pacote_oferta_enviada', 'Oferta de pacote criada automaticamente no orcamento ID ' . $orcamentoId . ' (oferta ' . $ofertaId . ').');
        return ['oferta_id' => $ofertaId, 'warning' => $warning, 'error' => null];
    }
    private function findLatestPacoteOfertaByOrcamento(int $orcamentoId): ?array
    {
        if ($orcamentoId <= 0 || !$this->isPacoteOfertaModuleReady()) {
            return null;
        }
        $row = $this->pacoteOfertaModel
            ->where('orcamento_id', $orcamentoId)
            ->whereIn('status', ['ativo', 'enviado', 'escolhido', 'aplicado_orcamento', 'erro_envio'])
            ->orderBy('id', 'DESC')
            ->first();
        return $row ?: null;
    }
    /**
     * @param array<string,mixed> $intent
     * @param array<string,mixed> $payload
     * @return array{oferta:?array,error:?string}
     */
    private function resolvePacoteOfertaForApply(array $intent, array $payload, ?int $orcamentoId = null): array
    {
        if (empty($intent['aplicar'])) {
            return ['oferta' => null, 'error' => null];
        }
        $ofertaId = (int) ($intent['oferta_id'] ?? 0);
        if ($ofertaId <= 0) {
            return ['oferta' => null, 'error' => 'Selecione uma oferta de pacote valida para aplicar no orcamento.'];
        }
        if (!$this->isPacoteOfertaModuleReady()) {
            return ['oferta' => null, 'error' => 'Modulo de ofertas de pacote nao inicializado.'];
        }
        $this->refreshPacotesOfertasIfExpired();
        $oferta = $this->pacoteOfertaModel->find($ofertaId);
        if (!$oferta) {
            return ['oferta' => null, 'error' => 'Oferta de pacote nao encontrada.'];
        }
        $status = trim((string) ($oferta['status'] ?? ''));
        $statusAllowed = ['escolhido'];
        if ($orcamentoId !== null && $orcamentoId > 0) {
            $statusAllowed[] = 'aplicado_orcamento';
        }
        if (!in_array($status, $statusAllowed, true)) {
            return ['oferta' => null, 'error' => 'A oferta selecionada ainda nao foi escolhida pelo cliente ou ja nao pode ser aplicada.'];
        }
        $ofertaOsId = (int) ($oferta['os_id'] ?? 0);
        if ($ofertaOsId > 0 && $status === 'aplicado_orcamento') {
            return ['oferta' => null, 'error' => 'Esta oferta ja foi implantada em uma OS e nao pode ser reaplicada.'];
        }
        $ofertaOrcamentoId = (int) ($oferta['orcamento_id'] ?? 0);
        if ($ofertaOrcamentoId > 0 && $orcamentoId !== null && $ofertaOrcamentoId !== $orcamentoId) {
            return ['oferta' => null, 'error' => 'Esta oferta ja foi vinculada a outro orcamento.'];
        }
        if ($ofertaOrcamentoId > 0) {
            $orcamentoOferta = $this->orcamentoModel->find($ofertaOrcamentoId);
            $orcamentoOfertaOsId = (int) ($orcamentoOferta['os_id'] ?? 0);
            if ($orcamentoOfertaOsId > 0 && $status === 'aplicado_orcamento') {
                return ['oferta' => null, 'error' => 'Esta oferta ja foi implantada em uma OS e nao deve ser sugerida novamente.'];
            }
        }
        $payloadTelefone = $this->normalizeWhatsAppPhone((string) ($payload['telefone_contato'] ?? ''));
        $payloadClienteId = (int) ($payload['cliente_id'] ?? 0);
        $payloadContatoId = (int) ($payload['contato_id'] ?? 0);
        $payloadOsId = (int) ($payload['os_id'] ?? 0);
        $payloadEquipamentoId = (int) ($payload['equipamento_id'] ?? 0);
        $matchByCliente = $payloadClienteId > 0 && $payloadClienteId === (int) ($oferta['cliente_id'] ?? 0);
        $matchByContato = $payloadContatoId > 0 && $payloadContatoId === (int) ($oferta['contato_id'] ?? 0);
        $matchByTelefone = $payloadTelefone !== '' && $payloadTelefone === $this->normalizeWhatsAppPhone((string) ($oferta['telefone_destino'] ?? ''));
        $matchByOs = $payloadOsId > 0 && $payloadOsId === (int) ($oferta['os_id'] ?? 0);
        $matchByEquipamento = $payloadEquipamentoId > 0 && $payloadEquipamentoId === (int) ($oferta['equipamento_id'] ?? 0);
        if (!$matchByCliente && !$matchByContato && !$matchByTelefone && !$matchByOs && !$matchByEquipamento) {
            return ['oferta' => null, 'error' => 'A oferta selecionada nao corresponde ao cliente/contexto deste orcamento.'];
        }
        $ofertaOsId = (int) ($oferta['os_id'] ?? 0);
        if ($payloadOsId > 0 && !$matchByOs) {
            return ['oferta' => null, 'error' => 'A oferta nao corresponde a OS deste orcamento e nao pode ser aplicada automaticamente.'];
        }
        $ofertaEquipamentoId = (int) ($oferta['equipamento_id'] ?? 0);
        if ($payloadEquipamentoId > 0 && !$matchByEquipamento) {
            return ['oferta' => null, 'error' => 'A oferta nao corresponde ao equipamento deste orcamento e nao pode ser aplicada automaticamente.'];
        }
        if (!$matchByCliente && !$matchByContato && $matchByTelefone) {
            $payloadNome = trim((string) ($payload['cliente_nome_avulso'] ?? ''));
            if ($payloadNome === '' && $payloadClienteId > 0) {
                $clientePayload = (new ClienteModel())->find($payloadClienteId);
                $payloadNome = trim((string) ($clientePayload['nome_razao'] ?? ''));
            }
            if ($payloadNome === '' && $payloadContatoId > 0) {
                $contatoPayload = (new ContatoModel())->find($payloadContatoId);
                $payloadNome = trim((string) ($contatoPayload['nome'] ?? $contatoPayload['whatsapp_nome_perfil'] ?? ''));
            }
            $ofertaCtx = null;
            $ofertaToken = trim((string) ($oferta['token_publico'] ?? ''));
            if ($ofertaToken !== '') {
                $ofertaCtx = $this->pacoteOfertaModel->findByTokenWithContext($ofertaToken);
            }
            $ofertaNome = trim((string) (($ofertaCtx['cliente_nome'] ?? $ofertaCtx['contato_nome'] ?? $ofertaCtx['contato_nome_perfil'] ?? '')));
            $payloadNomeNorm = $this->normalizeIdentityNameForMatch($payloadNome);
            $ofertaNomeNorm = $this->normalizeIdentityNameForMatch($ofertaNome);
            if ($payloadNomeNorm === '' || $ofertaNomeNorm === '') {
                return ['oferta' => null, 'error' => 'A oferta encontrada usa apenas o mesmo telefone e nao possui contexto de nome suficiente. Revise o cliente para evitar aplicar no orcamento errado.'];
            }
            if (!$this->isLikelySameIdentityName($payloadNomeNorm, $ofertaNomeNorm)) {
                return ['oferta' => null, 'error' => 'A oferta encontrada usa o mesmo telefone, mas o nome/contexto diverge. Para seguranca, selecione a oferta correta manualmente.'];
            }
        }
        if ($matchByTelefone && !$matchByOs && !$matchByEquipamento) {
            $payloadNome = trim((string) ($payload['cliente_nome_avulso'] ?? ''));
            if ($payloadNome === '' && $payloadClienteId > 0) {
                $clientePayload = (new ClienteModel())->find($payloadClienteId);
                $payloadNome = trim((string) ($clientePayload['nome_razao'] ?? ''));
            }
            if ($payloadNome === '' && $payloadContatoId > 0) {
                $contatoPayload = (new ContatoModel())->find($payloadContatoId);
                $payloadNome = trim((string) ($contatoPayload['nome'] ?? $contatoPayload['whatsapp_nome_perfil'] ?? ''));
            }
            $ofertaCtx = null;
            $ofertaToken = trim((string) ($oferta['token_publico'] ?? ''));
            if ($ofertaToken !== '') {
                $ofertaCtx = $this->pacoteOfertaModel->findByTokenWithContext($ofertaToken);
            }
            $ofertaNome = trim((string) (($ofertaCtx['cliente_nome'] ?? $ofertaCtx['contato_nome'] ?? $ofertaCtx['contato_nome_perfil'] ?? '')));
            $payloadNomeNorm = $this->normalizeIdentityNameForMatch($payloadNome);
            $ofertaNomeNorm = $this->normalizeIdentityNameForMatch($ofertaNome);
            if ($payloadNomeNorm !== '' && $ofertaNomeNorm !== '' && !$this->isLikelySameIdentityName($payloadNomeNorm, $ofertaNomeNorm)) {
                return ['oferta' => null, 'error' => 'A oferta encontrada usa o mesmo telefone, mas o nome/contexto diverge. Para seguranca, selecione a oferta correta manualmente.'];
            }
        }
        if ((int) ($oferta['pacote_servico_id'] ?? 0) <= 0) {
            return ['oferta' => null, 'error' => 'A oferta selecionada nao possui pacote valido para aplicacao.'];
        }
        return ['oferta' => $oferta, 'error' => null];
    }
    /**
     * @param array<string,mixed> $oferta
     */
    private function applyPacoteOfertaToOrcamento(array $oferta, int $orcamentoId): ?string
    {
        if ($orcamentoId <= 0) {
            return 'Orcamento invalido para aplicar a oferta de pacote.';
        }
        $ofertaId = (int) ($oferta['id'] ?? 0);
        if ($ofertaId <= 0) {
            return 'Oferta de pacote invalida.';
        }
        $pacoteId = (int) ($oferta['pacote_servico_id'] ?? 0);
        if ($pacoteId <= 0) {
            return 'Pacote da oferta nao encontrado.';
        }
        $pacoteNome = trim((string) ($oferta['pacote_nome'] ?? ''));
        if ($pacoteNome === '') {
            $pacote = (new PacoteServicoModel())->find($pacoteId);
            $pacoteNome = trim((string) ($pacote['nome'] ?? 'Pacote de servicos'));
        }
        if ($pacoteNome === '') {
            $pacoteNome = 'Pacote de servicos';
        }
        $nivelCode = trim((string) ($oferta['nivel_escolhido'] ?? ''));
        $nivelNome = trim((string) ($oferta['nivel_nome_exibicao'] ?? ''));
        if ($nivelCode !== '' && $nivelNome === '') {
            $nivelRow = (new PacoteServicoNivelModel())
                ->where('pacote_servico_id', $pacoteId)
                ->where('nivel', $nivelCode)
                ->first();
            $nivelNome = trim((string) ($nivelRow['nome_exibicao'] ?? ucfirst($nivelCode)));
            if ((float) ($oferta['valor_escolhido'] ?? 0) <= 0 && $nivelRow) {
                $oferta['valor_escolhido'] = (float) ($nivelRow['preco_recomendado'] ?? 0);
                $oferta['garantia_dias'] = (int) ($nivelRow['garantia_dias'] ?? 0) ?: null;
                $oferta['prazo_estimado'] = trim((string) ($nivelRow['prazo_estimado'] ?? '')) ?: null;
                $oferta['itens_inclusos'] = trim((string) ($nivelRow['itens_inclusos'] ?? '')) ?: null;
                $oferta['argumento_venda'] = trim((string) ($nivelRow['argumento_venda'] ?? '')) ?: null;
            }
        }
        if ($nivelNome === '') {
            $nivelNome = 'Nivel escolhido';
        }
        $valorEscolhido = max(0, (float) ($oferta['valor_escolhido'] ?? 0));
        if ($valorEscolhido <= 0) {
            return 'A oferta selecionada nao possui valor recomendado valido para aplicacao.';
        }
        $descricao = 'Pacote ' . $pacoteNome . ' - ' . $nivelNome;
        $observacoes = $this->buildPacoteOfertaItemObservacao($ofertaId, $oferta);
        $ordemItem = $this->nextOrcamentoItemOrder($orcamentoId);
        $orcamentoItemId = (int) ($oferta['orcamento_item_id'] ?? 0);
        $currentItem = null;
        if ($orcamentoItemId > 0) {
            $currentItem = $this->itemModel
                ->where('id', $orcamentoItemId)
                ->where('orcamento_id', $orcamentoId)
                ->first();
            if ($currentItem) {
                $ordemItem = max(1, (int) ($currentItem['ordem'] ?? $ordemItem));
            }
        }
        $itemPayload = [
            'orcamento_id' => $orcamentoId,
            'tipo_item' => 'combo',
            'referencia_id' => $pacoteId,
            'descricao' => $descricao,
            'quantidade' => 1,
            'valor_unitario' => round($valorEscolhido, 2),
            'desconto' => 0,
            'acrescimo' => 0,
            'total' => round($valorEscolhido, 2),
            'ordem' => $ordemItem,
            'observacoes' => $observacoes,
        ];
        if ($currentItem) {
            $this->itemModel->update((int) $currentItem['id'], $itemPayload);
            $orcamentoItemId = (int) $currentItem['id'];
        } else {
            $this->itemModel->insert($itemPayload);
            $orcamentoItemId = (int) $this->itemModel->getInsertID();
            if ($orcamentoItemId <= 0) {
                return 'Nao foi possivel inserir o item do pacote escolhido no orcamento.';
            }
        }
        $ofertaUpdate = [
            'status' => 'aplicado_orcamento',
            'orcamento_id' => $orcamentoId,
            'orcamento_item_id' => $orcamentoItemId > 0 ? $orcamentoItemId : null,
            'aplicado_em' => date('Y-m-d H:i:s'),
        ];
        $orcamentoRow = $this->orcamentoModel->find($orcamentoId);
        $orcamentoValidadeData = trim((string) ($orcamentoRow['validade_data'] ?? ''));
        if ($orcamentoValidadeData !== '' && strtotime($orcamentoValidadeData) !== false) {
            $ofertaUpdate['expira_em'] = date('Y-m-d 23:59:59', strtotime($orcamentoValidadeData));
        }
        $this->pacoteOfertaModel->update($ofertaId, $ofertaUpdate);
        return null;
    }
    /**
     * @param array<string,mixed> $oferta
     */
    private function buildPacoteOfertaItemObservacao(int $ofertaId, array $oferta): string
    {
        $partes = [
            'Oferta dinamica #' . $ofertaId,
        ];
        $prazo = trim((string) ($oferta['prazo_estimado'] ?? ''));
        if ($prazo !== '') {
            $partes[] = 'Prazo estimado: ' . $prazo;
        }
        $garantia = (int) ($oferta['garantia_dias'] ?? 0);
        if ($garantia > 0) {
            $partes[] = 'Garantia: ' . $garantia . ' dias';
        }
        $itensInclusos = trim((string) ($oferta['itens_inclusos'] ?? ''));
        if ($itensInclusos !== '') {
            $resumo = preg_replace('/\s*[\r\n]+\s*/', '; ', $itensInclusos);
            $partes[] = 'Inclusos: ' . trim((string) $resumo);
        }
        return implode(' | ', $partes);
    }
    private function extractOrcamentoPayload(): array
    {
        $status = trim((string) $this->request->getPost('status'));
        if (!array_key_exists($status, $this->orcamentoModel->statusLabels())) {
            $status = OrcamentoModel::STATUS_RASCUNHO;
        }
        $clienteId = (int) ($this->request->getPost('cliente_id') ?? 0);
        $desconto = max(0, $this->orcamentoService->normalizeMoney($this->request->getPost('desconto')));
        $acrescimo = max(0, $this->orcamentoService->normalizeMoney($this->request->getPost('acrescimo')));
        $itens = $this->extractItensPayload();
        $totais = $this->orcamentoService->calculateTotals($itens, $desconto, $acrescimo);
        $validadeDias = (int) ($this->request->getPost('validade_dias') ?? 10);
        if ($validadeDias <= 0) {
            $validadeDias = 10;
        }
        if ($validadeDias > 365) {
            $validadeDias = 365;
        }
        $validadeData = date('Y-m-d', strtotime('+' . $validadeDias . ' days'));
        $prazoExecucao = trim((string) $this->request->getPost('prazo_execucao'));
        $prazosPermitidos = ['1', '3', '7', '15', '30'];
        if ($prazoExecucao !== '' && !in_array($prazoExecucao, $prazosPermitidos, true)) {
            $prazoExecucao = '3';
        }
        $telefoneContatoInput = trim((string) $this->request->getPost('telefone_contato'));
        $telefoneContato = $this->normalizeWhatsAppPhone($telefoneContatoInput);
        $emailContato = strtolower(trim((string) $this->request->getPost('email_contato')));
        $payload = [
            'versao' => max(1, (int) ($this->request->getPost('versao') ?? 1)),
            'tipo_orcamento' => $this->orcamentoModel->normalizeTipo(
                (string) $this->request->getPost('tipo_orcamento'),
                (int) ($this->request->getPost('os_id') ?? 0) ?: null
            ),
            'status' => $status,
            'origem' => $this->normalizeOrigemOrcamento((string) $this->request->getPost('origem')),
            'cliente_id' => $clienteId > 0 ? $clienteId : null,
            'contato_id' => (int) ($this->request->getPost('contato_id') ?? 0) ?: null,
            'cliente_nome_avulso' => trim((string) $this->request->getPost('cliente_nome_avulso')) ?: null,
            'telefone_contato' => $telefoneContato !== '' ? $telefoneContato : null,
            'email_contato' => $emailContato !== '' ? $emailContato : null,
            'os_id' => (int) ($this->request->getPost('os_id') ?? 0) ?: null,
            'equipamento_id' => (int) ($this->request->getPost('equipamento_id') ?? 0) ?: null,
            'equipamento_tipo_id' => (int) ($this->request->getPost('equipamento_tipo_id') ?? 0) ?: null,
            'equipamento_marca_id' => (int) ($this->request->getPost('equipamento_marca_id') ?? 0) ?: null,
            'equipamento_modelo_id' => (int) ($this->request->getPost('equipamento_modelo_id') ?? 0) ?: null,
            'equipamento_cor' => trim((string) $this->request->getPost('equipamento_cor')) ?: null,
            'equipamento_cor_hex' => $this->normalizeHexColorValue((string) ($this->request->getPost('equipamento_cor_hex') ?? '')),
            'equipamento_cor_rgb' => $this->normalizeRgbColorValue((string) ($this->request->getPost('equipamento_cor_rgb') ?? '')),
            'conversa_id' => (int) ($this->request->getPost('conversa_id') ?? 0) ?: null,
            'responsavel_id' => (int) ($this->request->getPost('responsavel_id') ?? 0) ?: null,
            'titulo' => trim((string) $this->request->getPost('titulo')) ?: null,
            'validade_dias' => $validadeDias,
            'validade_data' => $validadeData,
            'subtotal' => $totais['subtotal'],
            'desconto' => $desconto,
            'acrescimo' => $acrescimo,
            'total' => $totais['total'],
            'prazo_execucao' => $prazoExecucao !== '' ? $prazoExecucao : null,
            'observacoes' => trim((string) $this->request->getPost('observacoes')) ?: null,
            'condicoes' => trim((string) $this->request->getPost('condicoes')) ?: null,
            'motivo_rejeicao' => trim((string) $this->request->getPost('motivo_rejeicao')) ?: null,
        ];
        $payload = $this->resolveClienteContatoPayload($payload);
        return $this->resolveEquipamentoCatalogPayload($payload);
    }
    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractItensPayload(): array
    {
        $tipos = (array) $this->request->getPost('item_tipo');
        $descricoes = (array) $this->request->getPost('item_descricao');
        $quantidades = (array) $this->request->getPost('item_quantidade');
        $valores = (array) $this->request->getPost('item_valor_unitario');
        $descontos = (array) $this->request->getPost('item_desconto');
        $acrescimos = (array) $this->request->getPost('item_acrescimo');
        $observacoes = (array) $this->request->getPost('item_observacao');
        $referencias = (array) $this->request->getPost('item_referencia_id');
        $totalLinhas = max(count($descricoes), count($tipos), count($quantidades), count($valores));
        $pecaModel = new PecaModel();
        $servicoModel = new ServicoModel();
        $pecaCache = [];
        $servicoCache = [];
        $itemFieldMap = $this->orcamentoItemFieldMap();
        $itens = [];
        for ($i = 0; $i < $totalLinhas; $i++) {
            $descricao = trim((string) ($descricoes[$i] ?? ''));
            if ($descricao === '') {
                continue;
            }
            $tipo = trim((string) ($tipos[$i] ?? 'servico'));
            if ($tipo === '') {
                $tipo = 'servico';
            }
            $quantidade = $this->orcamentoService->normalizeQuantity($quantidades[$i] ?? 1);
            $valorUnitario = max(0, $this->orcamentoService->normalizeMoney($valores[$i] ?? 0));
            $desconto = max(0, $this->orcamentoService->normalizeMoney($descontos[$i] ?? 0));
            $acrescimo = max(0, $this->orcamentoService->normalizeMoney($acrescimos[$i] ?? 0));
            $total = ($quantidade * $valorUnitario) - $desconto + $acrescimo;
            if ($total < 0) {
                $total = 0;
            }
            $referenciaRaw = $referencias[$i] ?? null;
            $referenciaId = $this->extractNumericId($referenciaRaw);
            if (! in_array($tipo, ['peca', 'servico'], true)) {
                $referenciaId = 0;
            }
            $itemPayload = [
                'tipo_item' => $tipo,
                'referencia_id' => $referenciaId > 0 ? $referenciaId : null,
                'descricao' => $descricao,
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'desconto' => $desconto,
                'acrescimo' => $acrescimo,
                'total' => round($total, 2),
                'ordem' => count($itens) + 1,
                'observacoes' => trim((string) ($observacoes[$i] ?? '')) ?: null,
            ];

            if ($tipo === 'peca' && $referenciaId > 0) {
                if (!array_key_exists($referenciaId, $pecaCache)) {
                    $pecaCache[$referenciaId] = $pecaModel->find($referenciaId) ?? [];
                }
                $peca = (array) $pecaCache[$referenciaId];
                if (!empty($peca)) {
                    $quote = $this->pecaPrecificacaoService->applyMinimumPrice($peca, $valorUnitario);
                    $valorAplicado = max(0, (float) ($quote['valor_aplicado'] ?? $valorUnitario));
                    $totalAjustado = ($quantidade * $valorAplicado) - $desconto + $acrescimo;
                    if ($totalAjustado < 0) {
                        $totalAjustado = 0;
                    }
                    $itemPayload['valor_unitario'] = round($valorAplicado, 2);
                    $itemPayload['total'] = round($totalAjustado, 2);
                    $itemPayload = array_merge($itemPayload, $this->buildPrecificacaoItemFields($quote, $itemFieldMap));
                }
            }
            if ($tipo === 'servico' && $referenciaId > 0 && $this->servicoPrecificacaoService->shouldApplyMinimumPrice()) {
                if (!array_key_exists($referenciaId, $servicoCache)) {
                    $servicoCache[$referenciaId] = $servicoModel->find($referenciaId) ?? [];
                }
                $servico = (array) $servicoCache[$referenciaId];
                if (!empty($servico)) {
                    $quoteServico = $this->servicoPrecificacaoService->applyMinimumPrice($servico, $valorUnitario);
                    $valorAplicado = max(0, (float) ($quoteServico['valor_aplicado'] ?? $valorUnitario));
                    $totalAjustado = ($quantidade * $valorAplicado) - $desconto + $acrescimo;
                    if ($totalAjustado < 0) {
                        $totalAjustado = 0;
                    }
                    $itemPayload['valor_unitario'] = round($valorAplicado, 2);
                    $itemPayload['total'] = round($totalAjustado, 2);
                    $itemPayload = array_merge($itemPayload, $this->buildPrecificacaoItemFields($quoteServico, $itemFieldMap));
                }
            }

            $itens[] = $itemPayload;
        }
        return $itens;
    }
    /**
     * @param array<int, array<string, mixed>> $itens
     */
    private function persistItens(int $orcamentoId, array $itens): void
    {
        foreach ($itens as $item) {
            $item['orcamento_id'] = $orcamentoId;
            $this->itemModel->insert($item);
        }
    }
    private function nextOrcamentoItemOrder(int $orcamentoId): int
    {
        $row = $this->itemModel
            ->selectMax('ordem')
            ->where('orcamento_id', $orcamentoId)
            ->first();
        return max(1, (int) ($row['ordem'] ?? 0) + 1);
    }
    private function recalculateOrcamentoTotals(int $orcamentoId): void
    {
        if ($orcamentoId <= 0) {
            return;
        }
        $orcamento = $this->orcamentoModel->find($orcamentoId);
        if (!$orcamento) {
            return;
        }
        $itens = $this->itemModel->byOrcamento($orcamentoId);
        $subtotal = 0.0;
        foreach ($itens as $item) {
            $subtotal += (float) ($item['total'] ?? 0);
        }
        $subtotal = round($subtotal, 2);
        $desconto = max(0, (float) ($orcamento['desconto'] ?? 0));
        $acrescimo = max(0, (float) ($orcamento['acrescimo'] ?? 0));
        $total = max(0, round($subtotal - $desconto + $acrescimo, 2));
        $this->orcamentoModel->update($orcamentoId, [
            'subtotal' => $subtotal,
            'total' => $total,
        ]);
    }

    /**
     * @return array<string,bool>
     */
    private function orcamentoItemFieldMap(): array
    {
        if ($this->orcamentoItemFieldCache !== null) {
            return $this->orcamentoItemFieldCache;
        }
        $db = Database::connect();
        $fields = [
            'preco_custo_referencia',
            'preco_venda_referencia',
            'preco_base',
            'percentual_encargos',
            'valor_encargos',
            'percentual_margem',
            'valor_margem',
            'valor_recomendado',
            'modo_precificacao',
        ];
        $map = [];
        foreach ($fields as $field) {
            $map[$field] = $db->fieldExists($field, 'orcamento_itens');
        }
        $this->orcamentoItemFieldCache = $map;
        return $map;
    }

    /**
     * @param array<string,mixed> $quote
     * @param array<string,bool> $fieldMap
     * @return array<string,mixed>
     */
    private function buildPrecificacaoItemFields(array $quote, array $fieldMap): array
    {
        $payload = [];
        $source = [
            'preco_custo_referencia' => round((float) ($quote['preco_custo_referencia'] ?? $quote['custo_total'] ?? 0), 2),
            'preco_venda_referencia' => round((float) ($quote['preco_venda_referencia'] ?? $quote['valor_cadastro'] ?? 0), 2),
            'preco_base' => round((float) ($quote['preco_base'] ?? $quote['custo_total'] ?? 0), 2),
            'percentual_encargos' => round((float) ($quote['percentual_encargos'] ?? $quote['risco_percentual'] ?? 0), 2),
            'valor_encargos' => round((float) ($quote['valor_encargos'] ?? $quote['valor_risco'] ?? 0), 2),
            'percentual_margem' => round((float) ($quote['percentual_margem'] ?? $quote['margem_percentual'] ?? 0), 2),
            'valor_margem' => round((float) ($quote['valor_margem'] ?? 0), 2),
            'valor_recomendado' => round((float) ($quote['valor_recomendado'] ?? 0), 2),
            'modo_precificacao' => trim((string) ($quote['modo_precificacao'] ?? 'peca_instalada_auto')),
        ];
        foreach ($source as $field => $value) {
            if (($fieldMap[$field] ?? false) !== true) {
                continue;
            }
            $payload[$field] = $value;
        }
        return $payload;
    }

    private function extractNumericId($value): int
    {
        if (is_int($value)) {
            return $value;
        }
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return 0;
        }
        if (ctype_digit($raw)) {
            return (int) $raw;
        }
        if (preg_match('/^(?:peca|servico):(\d+)$/i', $raw, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }
    private function statusTimestampColumns(string $status, string $now, ?array $current = null): array
    {
        $columns = [];
        $current = $current ?? [];
        if ($status === OrcamentoModel::STATUS_ENVIADO && empty($current['enviado_em'])) {
            $columns['enviado_em'] = $now;
        }
        if (in_array($status, [OrcamentoModel::STATUS_APROVADO, OrcamentoModel::STATUS_PENDENTE_OS], true) && empty($current['aprovado_em'])) {
            $columns['aprovado_em'] = $now;
        }
        if ($status === OrcamentoModel::STATUS_REJEITADO && empty($current['rejeitado_em'])) {
            $columns['rejeitado_em'] = $now;
        }
        if ($status === OrcamentoModel::STATUS_CANCELADO && empty($current['cancelado_em'])) {
            $columns['cancelado_em'] = $now;
        }
        return $columns;
    }
    private function isEmbedRequest(): bool
    {
        $embed = strtolower(trim((string) $this->request->getGet('embed')));
        return in_array($embed, ['1', 'true', 'yes', 'sim'], true);
    }
    private function withEmbedQuery(string $url): string
    {
        if (!$this->isEmbedRequest()) {
            return $url;
        }
        return str_contains($url, '?') ? ($url . '&embed=1') : ($url . '?embed=1');
    }
    private function orcamentoShowUrl(int $orcamentoId): string
    {
        return $this->withEmbedQuery('/orcamentos/visualizar/' . $orcamentoId);
    }
}
