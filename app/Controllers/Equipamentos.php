<?php

namespace App\Controllers;

use App\Models\EquipamentoModel;
use App\Models\ClienteModel;
use App\Models\EquipamentoTipoModel;
use App\Models\EquipamentoMarcaModel;
use App\Models\EquipamentoModeloModel;
use App\Models\EquipamentoFotoModel;
use App\Models\EquipamentoClienteModel;
use App\Models\EquipamentoLifecycleHistoricoModel;
use App\Models\LogModel;
use App\Models\MonitorAgentModel;
use App\Models\OsModel;
use App\Services\EquipamentoIdentidadeService;
use App\Services\EquipamentoProfileService;

class Equipamentos extends BaseController
{
    private const MAX_FOTOS_POR_EQUIPAMENTO = 4;
    private const RELATION_TABLE = 'equipamentos_catalogo_relacoes';
    private const ENCERRAMENTO_MOTIVOS = [
        'retirada_pecas' => 'Retirada de pecas',
        'irreparavel' => 'Problema irreparavel',
        'descartado' => 'Descartado',
        'pecas_vendidas' => 'Pecas vendidas para outros clientes',
        'outro' => 'Outro motivo',
    ];
    private const REATIVACAO_MOTIVOS = [
        'recuperado' => 'Recuperado',
        'recondicionado' => 'Recondicionado',
        'reparado' => 'Reparado',
        'voltou_operacao' => 'Voltou a funcionar',
        'outro' => 'Outro motivo',
    ];

    protected $model;
    private EquipamentoProfileService $profileService;
    private EquipamentoIdentidadeService $identidadeService;

    public function __construct()
    {
        $this->model = new EquipamentoModel();
        $this->profileService = new EquipamentoProfileService();
        $this->identidadeService = new EquipamentoIdentidadeService($this->model, new EquipamentoClienteModel(), new ClienteModel());
        requirePermission('equipamentos');
    }

    public function index()
    {
        $data = [
            'title'        => 'Equipamentos',
            'equipamentos' => $this->model->getWithCliente(),
        ];
        return view('equipamentos/index', $data);
    }

    public function create()
    {
        $clienteModel = new ClienteModel();
        $tipoModel = new EquipamentoTipoModel();
        $marcaModel = new EquipamentoMarcaModel();
        $data = [
            'title'    => 'Novo Equipamento',
            'clientes' => $clienteModel->orderBy('nome_razao', 'ASC')->findAll(),
            'tipos'    => $tipoModel->orderBy('nome', 'ASC')->findAll(),
            'marcas'   => $marcaModel->orderBy('nome', 'ASC')->findAll(),
            'desktopCatalogDefaults' => $this->profileService->getDesktopMountedCatalogDefaultsMeta(),
        ];
        return view('equipamentos/form', $data);
    }

    public function store()
    {
        $dados = (array) $this->request->getPost();
        $validationErrors = $this->validateEquipamentoPayload($dados);
        if (!empty($validationErrors)) {
            return redirect()->back()->withInput()->with('errors', $validationErrors);
        }

        $duplicateConflict = $this->identidadeService->detectConflict($dados);
        if ($duplicateConflict !== null) {
            return redirect()->back()
                ->withInput()
                ->with('error', $duplicateConflict['message'])
                ->with('duplicate_equipment', $this->buildDuplicateConflictPayload($duplicateConflict));
        }

        $dados = $this->processarMarcaModelo($dados);
        $dados = $this->profileService->prepareForPersist($dados);
        $this->syncCatalogoRelacaoFromPayload($dados);
        $dados = $this->normalizeSenhaAcessoPayload($dados);

        $this->model->insert($dados);
        $equipId = $this->model->getInsertID();
        $uploadResult = $this->appendEquipamentoFotos(
            $equipId,
            $this->collectAjaxUploadedFotos(),
            false
        );

        // Processar upload de fotos
        if (false && ($imagefile = $this->request->getFiles())) {
            $fotoModel = new EquipamentoFotoModel();

            // Buscar dados para nomeaÃƒÂ§ÃƒÂ£o
            $marcaModel = new EquipamentoMarcaModel();
            $modeloModel = new EquipamentoModeloModel();

            $marca  = $marcaModel->find($dados['marca_id'])['nome'] ?? 'marca';
            $modelo = $modeloModel->find($dados['modelo_id'])['nome'] ?? 'modelo';
            $slug   = strtolower(url_title($marca . '_' . $modelo, '_', true));

            $is_principal = 1;

            if (isset($imagefile['fotos'])) {
                foreach ($imagefile['fotos'] as $index => $img) {
                    if ($img->isValid() && !$img->hasMoved()) {
                        $ext = $img->getExtension();
                        $newName = $slug . '_' . ($index + 1) . '_' . time() . '.' . $ext;
                        $img->move(FCPATH . 'uploads/equipamentos_perfil', $newName);

                        $fotoModel->insert([
                            'equipamento_id' => $equipId,
                            'arquivo'        => $newName,
                            'is_principal'   => $is_principal,
                            'created_at'     => date('Y-m-d H:i:s')
                        ]);
                        $is_principal = 0; // Apenas a primeira fica true
                    }
                }
            }
        }

        LogModel::registrar('equipamento_criado', 'Equipamento ID Cadastrado: ' . $equipId);

        $warning = $uploadResult['warning'] ?? null;
        if ($this->request->getGet('redirect') === 'os') {
            $redirect = redirect()->back()->with('success', 'Equipamento cadastrado!');
            if ($warning) {
                $redirect = $redirect->with('warning', $warning);
            }
            return $redirect;
        }

        $redirect = redirect()->to('/equipamentos')
            ->with('success', 'Equipamento cadastrado com sucesso!');
        if ($warning) {
            $redirect = $redirect->with('warning', $warning);
        }
        return $redirect;
    }

    public function edit($id)
    {
        $equipamento = $this->model->find($id);
        if (!$equipamento) {
            return redirect()->to('/equipamentos')
                ->with('error', 'Equipamento nÃƒÂ£o encontrado.');
        }

        $clienteModel = new ClienteModel();
        $tipoModel    = new EquipamentoTipoModel();
        $marcaModel   = new EquipamentoMarcaModel();
        $modeloModel  = new EquipamentoModeloModel();

        $fotoModel    = new EquipamentoFotoModel();
        $isEmbedded = $this->request->getGet('embed') === '1';

        $this->normalizeEquipamentoFotosStorage((int) $id);

        $fotos = $fotoModel->where('equipamento_id', $id)->findAll();
        $data = [
            'title'        => 'Editar Equipamento',
            'equipamento'  => $equipamento,
            'clientes'     => $clienteModel->orderBy('nome_razao', 'ASC')->findAll(),
            'tipos'        => $tipoModel->orderBy('nome', 'ASC')->findAll(),
            'marcas'       => $marcaModel->orderBy('nome', 'ASC')->findAll(),
            'modelos'      => $modeloModel->where('marca_id', $equipamento['marca_id'])->orderBy('nome', 'ASC')->findAll(),
            'fotos'        => $this->hydrateFotosUrls($fotos),
            'desktopCatalogDefaults' => $this->profileService->getDesktopMountedCatalogDefaultsMeta(),
        ];
        return view('equipamentos/form', $data);
    }

    public function update($id)
    {
        $dados = (array) $this->request->getPost();
        $equipAtual = $this->model->find((int) $id) ?? [];
        $validationErrors = $this->validateEquipamentoPayload($dados, $equipAtual);
        if (!empty($validationErrors)) {
            return redirect()->back()->withInput()->with('errors', $validationErrors);
        }

        $duplicateConflict = $this->identidadeService->detectConflict($dados, (int) $id);
        if ($duplicateConflict !== null) {
            return redirect()->back()
                ->withInput()
                ->with('error', $duplicateConflict['message'])
                ->with('duplicate_equipment', $this->buildDuplicateConflictPayload($duplicateConflict));
        }

        $dados = $this->processarMarcaModelo($dados);
        $dados = $this->profileService->prepareForPersist($dados, $equipAtual);
        $this->syncCatalogoRelacaoFromPayload($dados);
        $dados = $this->normalizeSenhaAcessoPayload($dados);

        $this->model->update($id, $dados);
        $this->normalizeEquipamentoFotosStorage((int) $id);
        $uploadResult = $this->appendEquipamentoFotos(
            (int) $id,
            $this->collectAjaxUploadedFotos(),
            false
        );

        // Processar upload de fotos
        if (false && ($imagefile = $this->request->getFiles())) {
            $fotoModel = new EquipamentoFotoModel();

            // Buscar dados para nomeaÃƒÂ§ÃƒÂ£o
            $equip = $this->model->find($id);
            $marcaModel  = new EquipamentoMarcaModel();
            $modeloModel = new EquipamentoModeloModel();
            $marca  = $marcaModel->find($equip['marca_id'])['nome'] ?? 'marca';
            $modelo = $modeloModel->find($equip['modelo_id'])['nome'] ?? 'modelo';
            $slug   = strtolower(url_title($marca . '_' . $modelo, '_', true));

            // Verifica se jÃƒÂ¡ existe uma foto principal para este equipamento
            $hasPrincipal = $fotoModel->where('equipamento_id', $id)->where('is_principal', 1)->first() ? 0 : 1;
            $is_principal = $hasPrincipal;

            if (isset($imagefile['fotos'])) {
                foreach ($imagefile['fotos'] as $index => $img) {
                    if ($img->isValid() && !$img->hasMoved()) {
                        $ext = $img->getExtension();
                        $newName = $slug . '_edit_' . ($index + 1) . '_' . time() . '.' . $ext;
                        $img->move(FCPATH . 'uploads/equipamentos_perfil', $newName);

                        $fotoModel->insert([
                            'equipamento_id' => $id,
                            'arquivo'        => $newName,
                            'is_principal'   => $is_principal,
                            'created_at'     => date('Y-m-d H:i:s')
                        ]);
                        $is_principal = 0;
                    }
                }
            }
        }

        LogModel::registrar('equipamento_atualizado', 'Equipamento atualizado ID: ' . $id);

        $redirect = redirect()->to('/equipamentos')
            ->with('success', 'Equipamento atualizado com sucesso!');
        $warning = $uploadResult['warning'] ?? null;
        if ($warning) {
            $redirect = $redirect->with('warning', $warning);
        }
        return $redirect;
    }

    public function delete($id)
    {
        $fotoModel = new EquipamentoFotoModel();
        $fotos = $fotoModel->where('equipamento_id', (int) $id)->findAll();
        foreach ($fotos as $foto) {
            $path = $this->resolveFotoAbsolutePath((string) ($foto['arquivo'] ?? ''));
            if ($path && is_file($path)) {
                @unlink($path);
                $this->removeEmptyPerfilFolder($path);
            }
        }

        $this->model->delete($id);
        LogModel::registrar('equipamento_excluido', 'Equipamento exclu do ID: ' . $id);

        return redirect()->to('/equipamentos')
            ->with('success', 'Equipamento exclu do com sucesso!');
    }

    public function deleteFoto($fotoId)
    {
        $fotoModel = new EquipamentoFotoModel();
        $foto = $fotoModel->find($fotoId);

        if ($foto) {
            $equipamentoId = (int) ($foto['equipamento_id'] ?? 0);
            $eraPrincipal = ((int) ($foto['is_principal'] ?? 0) === 1);
            $path = $this->resolveFotoAbsolutePath((string) $foto['arquivo']);
            if ($path && file_exists($path)) {
                @unlink($path);
                $this->removeEmptyPerfilFolder($path);
            }
            $fotoModel->delete($fotoId);

            // Garante que sempre exista exatamente uma foto principal quando houver fotos restantes.
            if ($equipamentoId > 0) {
                $fotosRestantes = $fotoModel->where('equipamento_id', $equipamentoId)->findAll();
                if (!empty($fotosRestantes)) {
                    $principalAtual = null;
                    foreach ($fotosRestantes as $f) {
                        if ((int) ($f['is_principal'] ?? 0) === 1) {
                            $principalAtual = $f;
                            break;
                        }
                    }

                    if (!$principalAtual || $eraPrincipal) {
                        $fotoModel->where('equipamento_id', $equipamentoId)->set(['is_principal' => 0])->update();
                        $novoPrincipal = $fotosRestantes[0];
                        $fotoModel->update($novoPrincipal['id'], ['is_principal' => 1]);
                    }
                }
            }

            $total = 0;
            if ($equipamentoId > 0) {
                $total = (int) $fotoModel->where('equipamento_id', $equipamentoId)->countAllResults();
            }

            return $this->response->setJSON([
                'success' => true,
                'equipamento_id' => $equipamentoId,
                'total_fotos' => $total,
                'fotos' => $this->getHydratedFotosByEquipamentoId($equipamentoId),
            ]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Foto n o encontrada']);
    }

    public function show($id)
    {
        $equipamento = $this->model->getWithCliente($id);
        if (!$equipamento) {
            return redirect()->to('/equipamentos')->with('error', 'Equipamento n o encontrado.');
        }

        $isEmbedded = $this->request->getGet('embed') === '1';

        $this->normalizeEquipamentoFotosStorage((int) $id);
        $fotoModel = new EquipamentoFotoModel();
        $osModel   = new OsModel();
        $agentMonitorModel = new MonitorAgentModel();

        $equipamentoClienteModel = new EquipamentoClienteModel();
        $clienteModel = new ClienteModel();
        $historicoLifecycleData = $this->buildLifecycleHistoryViewData((int) $id);

        $fotos = $fotoModel->where('equipamento_id', $id)->orderBy('is_principal', 'DESC')->findAll();
        $data = [
            'title'        => 'Detalhes do Equipamento',
            'equipamento'  => $equipamento,
            'fotos'        => $this->hydrateFotosUrls($fotos),
            'ordens'       => $osModel->where('equipamento_id', $id)->orderBy('created_at', 'DESC')->findAll(),
            'vinculados'   => $equipamentoClienteModel->getClientesVinculados($id),
            'historicoLifecycle' => $historicoLifecycleData['historicoLifecycle'],
            'historicoLifecycleCount' => $historicoLifecycleData['historicoLifecycleCount'],
            'clientes_all' => $clienteModel->orderBy('nome_razao', 'ASC')->findAll(), // For modal dropdown
            'agentMonitor' => $agentMonitorModel->where('equipamento_id', (int) $id)->orderBy('ultimo_snapshot_em', 'DESC')->first(),
            'layout' => $isEmbedded ? 'layouts/embed' : 'layouts/main',
            'isEmbedded' => $isEmbedded,
        ];

        return view('equipamentos/show', $data);
    }

    public function encerrar($id)
    {
        $equipamentoId = max(0, (int) $id);
        if ($equipamentoId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Equipamento invalido para encerramento.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        if (! $this->model->supportsOperationalLifecycle()) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'O ciclo de vida operacional do equipamento ainda nao foi preparado neste ambiente. Execute a migration antes de encerrar registros.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $equipamento = $this->model->getWithCliente($equipamentoId);
        if (! $equipamento) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Equipamento nao encontrado.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        if (equipamento_esta_encerrado($equipamento)) {
            $lifecycleHistoryData = $this->buildLifecycleHistoryViewData($equipamentoId);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Este equipamento ja estava encerrado.',
                'equipamento' => $this->findEquipamentoForJson($equipamentoId),
                'lifecycle_history_html' => view('equipamentos/partials/lifecycle_history', $lifecycleHistoryData),
                'lifecycle_history_count' => $lifecycleHistoryData['historicoLifecycleCount'],
                'csrfHash' => csrf_hash(),
            ]);
        }

        $osAbertasCount = $this->equipamentoOsAbertasCount($equipamentoId);
        if ($osAbertasCount > 0) {
            $message = $osAbertasCount === 1
                ? 'Existe 1 OS em andamento neste equipamento. Finalize ou cancele a OS antes de encerrar sua vida util.'
                : 'Existem ' . $osAbertasCount . ' OS em andamento neste equipamento. Finalize ou cancele as OS antes de encerrar sua vida util.';

            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => $message,
                'os_abertas_count' => $osAbertasCount,
                'csrfHash' => csrf_hash(),
            ]);
        }

        $motivo = strtolower(trim((string) ($this->request->getPost('motivo_encerramento') ?? '')));
        $observacao = trim((string) ($this->request->getPost('observacao_encerramento') ?? ''));

        if ($motivo === '' || !array_key_exists($motivo, self::ENCERRAMENTO_MOTIVOS)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Selecione um motivo valido para encerrar o equipamento.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $payload = [
            'status_operacional' => 'encerrado',
            'motivo_encerramento' => $motivo,
            'observacao_encerramento' => $observacao !== '' ? $observacao : null,
            'encerrado_em' => date('Y-m-d H:i:s'),
        ];

        $this->model->update($equipamentoId, $payload);

        $equipamentoAtualizado = $this->findEquipamentoForJson($equipamentoId);
        LogModel::registrar(
            'equipamento_encerrado',
            'Equipamento encerrado ID ' . $equipamentoId
                . ' | Motivo: ' . (self::ENCERRAMENTO_MOTIVOS[$motivo] ?? $motivo)
                . ($observacao !== '' ? ' | Observacao: ' . $observacao : '')
        );

        $this->recordLifecycleHistoryEvent($equipamentoId, 'encerrado', [
            'motivo' => $motivo,
            'observacao' => $observacao,
            'status_anterior' => (string) ($equipamento['status_operacional'] ?? 'ativo'),
            'status_novo' => 'encerrado',
        ]);

        $lifecycleHistoryData = $this->buildLifecycleHistoryViewData($equipamentoId);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Equipamento encerrado com sucesso. Ele permanecera no historico do cliente, mas nao podera receber novas OS.',
            'equipamento' => $equipamentoAtualizado,
            'lifecycle_history_html' => view('equipamentos/partials/lifecycle_history', $lifecycleHistoryData),
            'lifecycle_history_count' => $lifecycleHistoryData['historicoLifecycleCount'],
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function reativar($id)
    {
        $equipamentoId = max(0, (int) $id);
        if ($equipamentoId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Equipamento invalido para reativacao.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        if (! $this->model->supportsOperationalLifecycle()) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'O ciclo de vida operacional do equipamento ainda nao foi preparado neste ambiente. Execute a migration antes de reativar registros.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $equipamento = $this->model->getWithCliente($equipamentoId);
        if (! $equipamento) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Equipamento nao encontrado.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        if (! equipamento_esta_encerrado($equipamento)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Este equipamento ja esta ativo na operacao.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $motivoAnterior = trim((string) ($equipamento['motivo_encerramento_label'] ?? ''));
        $encerradoEmAnterior = trim((string) ($equipamento['encerrado_em_label'] ?? ''));
        $observacaoAnterior = trim((string) ($equipamento['observacao_encerramento'] ?? ''));

        $motivo = strtolower(trim((string) ($this->request->getPost('motivo_reativacao') ?? '')));
        $observacao = trim((string) ($this->request->getPost('observacao_reativacao') ?? ''));

        if ($motivo === '' || !array_key_exists($motivo, self::REATIVACAO_MOTIVOS)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Selecione um motivo valido para reativar o equipamento.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $payload = [
            'status_operacional' => 'ativo',
            'motivo_encerramento' => null,
            'observacao_encerramento' => null,
            'encerrado_em' => null,
        ];

        $this->model->update($equipamentoId, $payload);

        $equipamentoAtualizado = $this->findEquipamentoForJson($equipamentoId);
        LogModel::registrar(
            'equipamento_reativado',
            'Equipamento reativado ID ' . $equipamentoId
                . ($encerradoEmAnterior !== '' ? ' | Encerrado em: ' . $encerradoEmAnterior : '')
                . ($motivoAnterior !== '' ? ' | Motivo anterior: ' . $motivoAnterior : '')
                . ($observacaoAnterior !== '' ? ' | Observacao anterior: ' . $observacaoAnterior : '')
                . ' | Motivo da reativacao: ' . (self::REATIVACAO_MOTIVOS[$motivo] ?? $motivo)
                . ($observacao !== '' ? ' | Observacao: ' . $observacao : '')
        );

        $this->recordLifecycleHistoryEvent($equipamentoId, 'reativado', [
            'motivo' => $motivo,
            'observacao' => $observacao,
            'status_anterior' => 'encerrado',
            'status_novo' => 'ativo',
        ]);

        $lifecycleHistoryData = $this->buildLifecycleHistoryViewData($equipamentoId);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Equipamento reativado com sucesso. Ele voltou a aceitar novas OS e novos vinculos operacionais.',
            'equipamento' => $equipamentoAtualizado,
            'lifecycle_history_html' => view('equipamentos/partials/lifecycle_history', $lifecycleHistoryData),
            'lifecycle_history_count' => $lifecycleHistoryData['historicoLifecycleCount'],
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function byClient($clienteId)
    {
        $selectedId = max(0, (int) ($this->request->getGet('selected_id') ?? 0));
        $equipamentos = array_map(function (array $equipamento): array {
            $fotoArquivo = trim((string) ($equipamento['foto_principal_arquivo'] ?? ''));
            $equipamento['foto_url'] = $fotoArquivo !== '' ? $this->buildFotoPublicUrl($fotoArquivo) : '';
            return $equipamento;
        }, $this->model->getByCliente((int) $clienteId, true, $selectedId > 0 ? [$selectedId] : []));
        return $this->response->setJSON($equipamentos);
    }

    /**
     * Retorna as fotos de um equipamento (para o painel lateral da OS)
     */
    public function getFotos($equipamentoId)
    {
        $this->normalizeEquipamentoFotosStorage((int) $equipamentoId);
        return $this->response->setJSON($this->getHydratedFotosByEquipamentoId((int) $equipamentoId));
    }

    /**
     * Cadastra equipamento via AJAX (modal inline na OS)
     */
    public function storeAjax()
    {
        $dados = (array) $this->request->getPost();
        $validationErrors = $this->validateEquipamentoPayload($dados);
        if (!empty($validationErrors)) {
            return $this->response->setJSON([
                'status'    => 'error',
                'errors'    => $this->translateEquipamentoValidationErrors($validationErrors),
                'focus_tab' => $this->resolveEquipamentoValidationFocusTab($validationErrors),
            ]);
        }

        $duplicateConflict = $this->identidadeService->detectConflict($dados);
        if ($duplicateConflict !== null) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => 'duplicate_conflict',
                'message' => $duplicateConflict['message'],
                'focus_tab' => 'info',
                'duplicate' => $this->buildDuplicateConflictPayload($duplicateConflict),
            ]);
        }

        $dados = $this->processarMarcaModelo($dados);
        $dados = $this->profileService->prepareForPersist($dados);
        $this->syncCatalogoRelacaoFromPayload($dados);
        $dados = $this->normalizeSenhaAcessoPayload($dados);
        $dados['cor_hex'] = $dados['cor_hex'] ?? null;
        $uploadedFiles = $this->collectAjaxUploadedFotos();
        $assetValidation = $this->validateAjaxEquipamentoAssets($dados, $uploadedFiles);
        if (!empty($assetValidation['errors'])) {
            return $this->response->setJSON([
                'status'    => 'error',
                'errors'    => $assetValidation['errors'],
                'focus_tab' => $assetValidation['focus_tab'],
            ]);
        }

        $this->model->insert($dados);
        $equipId = $this->model->getInsertID();

        $uploadResult = $this->appendEquipamentoFotos(
            $equipId,
            $uploadedFiles,
            false
        );
        $fotoUrl = $uploadResult['principal_url'] ?? null;
        $uploadWarning = $uploadResult['warning'] ?? null;

        $equip = $this->findEquipamentoForJson($equipId);

        LogModel::registrar('equipamento_criado', 'Equipamento cadastrado via OS (ID: ' . $equipId . ')');

        return $this->response->setJSON([
            'status'    => 'success',
            'equipamento' => $equip,
            'foto_url'  => $fotoUrl,
            'fotos'     => $this->getHydratedFotosByEquipamentoId((int) $equipId),
            'warning'   => $uploadWarning
        ]);
    }

    /**
     * Atualiza equipamento via AJAX (modal inline na OS)
     */
    public function updateAjax($id)
    {
        $equipAtual = $this->model->find($id);
        if (!$equipAtual) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Equipamento nÃƒÂ£o encontrado.'
            ]);
        }

        $dados = (array) $this->request->getPost();
        $validationErrors = $this->validateEquipamentoPayload($dados, $equipAtual);
        if (!empty($validationErrors)) {
            return $this->response->setJSON([
                'status'    => 'error',
                'errors'    => $this->translateEquipamentoValidationErrors($validationErrors),
                'focus_tab' => $this->resolveEquipamentoValidationFocusTab($validationErrors),
            ]);
        }

        $duplicateConflict = $this->identidadeService->detectConflict($dados, (int) $id);
        if ($duplicateConflict !== null) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => 'duplicate_conflict',
                'message' => $duplicateConflict['message'],
                'focus_tab' => 'info',
                'duplicate' => $this->buildDuplicateConflictPayload($duplicateConflict),
            ]);
        }

        $dados = $this->processarMarcaModelo($dados);
        $dados = $this->profileService->prepareForPersist($dados, $equipAtual);
        $this->syncCatalogoRelacaoFromPayload($dados);
        $dados = $this->normalizeSenhaAcessoPayload($dados);
        $dados['cor_hex'] = $dados['cor_hex'] ?? null;
        $uploadedFiles = $this->collectAjaxUploadedFotos();
        $assetValidation = $this->validateAjaxEquipamentoAssets($dados, $uploadedFiles, (int) $id);
        if (!empty($assetValidation['errors'])) {
            return $this->response->setJSON([
                'status'    => 'error',
                'errors'    => $assetValidation['errors'],
                'focus_tab' => $assetValidation['focus_tab'],
            ]);
        }

        $this->model->update($id, $dados);
        $this->normalizeEquipamentoFotosStorage((int) $id);

        $uploadResult = $this->appendEquipamentoFotos(
            (int) $id,
            $uploadedFiles,
            false
        );
        $fotoUrl = $uploadResult['principal_url'] ?? null;
        $uploadWarning = $uploadResult['warning'] ?? null;

        $equip = $this->findEquipamentoForJson((int) $id);

        // Se nÃƒÂ£o subiu nova foto, retorna a principal atual para refletir no painel lateral.
        if (!$fotoUrl) {
            $fotoModel = new EquipamentoFotoModel();
            $fotoPrincipal = $fotoModel->where('equipamento_id', $id)
                ->orderBy('is_principal', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();
            if ($fotoPrincipal) {
                $fotoUrl = $this->buildFotoPublicUrl((string) $fotoPrincipal['arquivo']);
            }
        }

        LogModel::registrar('equipamento_atualizado', 'Equipamento atualizado via OS (ID: ' . $id . ')');

        return $this->response->setJSON([
            'status'      => 'success',
            'equipamento' => $equip,
            'foto_url'    => $fotoUrl,
            'fotos'       => $this->getHydratedFotosByEquipamentoId((int) $id),
            'warning'     => $uploadWarning
        ]);
    }

    public function benchCollectorSnapshotLocal()
    {
        try {
            $this->enrichBenchCollectorSnapshotFileWithErpContext();
            return $this->response->setJSON($this->buildBenchCollectorSnapshotResponsePayload());
        } catch (\Throwable $e) {
            log_message('error', '[Equipamentos] Falha ao ler snapshot local do coletor: ' . $e->getMessage());

            return $this->respondBenchCollectorFailure($e, 'ler o snapshot local do coletor');
        }
    }

    public function benchCollectorCollectLocal()
    {
        try {
            $run = $this->runBenchCollectorLocalCapture();
            $finalSnapshotPath = $this->enrichBenchCollectorSnapshotFileWithErpContext();
            $cleanup = $this->cleanupBenchCollectorTemporaryArtifacts($finalSnapshotPath);
            return $this->response->setJSON($this->buildBenchCollectorSnapshotResponsePayload([
                'collector' => array_merge($run, [
                    'cleanup' => $cleanup,
                ]),
                'message' => 'Coleta local concluida com sucesso.',
            ]));
        } catch (\Throwable $e) {
            log_message('error', '[Equipamentos] Falha ao executar coleta local do coletor: ' . $e->getMessage());

            return $this->respondBenchCollectorFailure($e, 'executar a coleta local do coletor');
        }
    }

    public function setFotoPrincipal($fotoId)
    {
        $fotoModel = new EquipamentoFotoModel();
        $foto = $fotoModel->find((int) $fotoId);

        if (!$foto) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Foto nao encontrada.'
            ]);
        }

        $equipamentoId = (int) ($foto['equipamento_id'] ?? 0);
        if ($equipamentoId <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Equipamento da foto nao encontrado.'
            ]);
        }

        $fotoModel->where('equipamento_id', $equipamentoId)->set(['is_principal' => 0])->update();
        $fotoModel->update((int) $foto['id'], ['is_principal' => 1]);

        return $this->response->setJSON([
            'success' => true,
            'equipamento_id' => $equipamentoId,
            'fotos' => $this->getHydratedFotosByEquipamentoId($equipamentoId),
        ]);
    }

    public function vincularCliente()
    {
        $equipamento_id = $this->request->getPost('equipamento_id');
        $cliente_id     = $this->request->getPost('cliente_id');

        if (!$equipamento_id || !$cliente_id) {
            return redirect()->back()->with('error', 'Equipamento ou Cliente n o informado.');
        }

        // Verifica se n o   o dono propriet rio princpial
        $equipamento = $this->model->find($equipamento_id);
        if ($equipamento && $this->model->supportsOperationalLifecycle() && equipamento_esta_encerrado($equipamento)) {
            return redirect()->back()->with('error', 'Equipamentos encerrados permanecem apenas no historico e nao aceitam novos vinculos operacionais.');
        }
        if ($equipamento['cliente_id'] == $cliente_id) {
            return redirect()->back()->with('error', 'Este cliente j   o propriet rio principal do equipamento.');
        }

        $equipamentoClienteModel = new EquipamentoClienteModel();
        // Verifica se n o est  vinculado j
        $existe = $equipamentoClienteModel->where('equipamento_id', $equipamento_id)
                                          ->where('cliente_id', $cliente_id)
                                          ->first();
        if ($existe) {
            return redirect()->back()->with('error', 'Este cliente j  est  vinculado a este equipamento.');
        }

        $equipamentoClienteModel->insert([
            'equipamento_id' => $equipamento_id,
            'cliente_id'     => $cliente_id
        ]);

        return redirect()->back()->with('success', 'Cliente vinculado com sucesso!');
    }

    public function vincularExistenteAjax()
    {
        try {
            $equipamentoId = max(0, (int) ($this->request->getPost('equipamento_id') ?? 0));
            $clienteId = max(0, (int) ($this->request->getPost('cliente_id') ?? 0));

            $equipamento = $this->model->find($equipamentoId);
            if ($equipamento && $this->model->supportsOperationalLifecycle() && equipamento_esta_encerrado($equipamento)) {
                throw new \RuntimeException('Equipamentos encerrados permanecem apenas no historico e nao aceitam novos vinculos operacionais.');
            }

            $result = $this->identidadeService->linkClienteToEquipamento($equipamentoId, $clienteId);
            $equipamento = $this->findEquipamentoForJson($equipamentoId);
            if ($equipamento === null) {
                throw new \RuntimeException('Equipamento vinculado nao foi encontrado para retorno.');
            }

            LogModel::registrar('equipamento_cliente_vinculado', 'Cliente ' . $clienteId . ' vinculado ao equipamento existente ID ' . $equipamentoId);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => !empty($result['linked'])
                    ? 'Cliente vinculado ao equipamento existente com sucesso.'
                    : 'O cliente ja estava associado a este equipamento.',
                'link_result' => $result,
                'equipamento' => $equipamento,
                'fotos' => $this->getHydratedFotosByEquipamentoId($equipamentoId),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'Nao foi possivel vincular o cliente ao equipamento existente.',
            ]);
        }
    }

    public function desvincularCliente($equipamento_id, $cliente_id)
    {
        $equipamentoClienteModel = new EquipamentoClienteModel();
        $equipamentoClienteModel->where('equipamento_id', $equipamento_id)
                                ->where('cliente_id', $cliente_id)
                                ->delete();

        return redirect()->back()->with('success', 'V nculo removido com sucesso!');
    }

    /**
     * Coleta fotos enviadas no modal da OS.
     * Mantem compatibilidade com campos legados: fotos[] e foto_perfil.
     */
    private function collectAjaxUploadedFotos(): array
    {
        $files = [];

        $multi = $this->request->getFileMultiple('fotos');
        if (is_array($multi)) {
            foreach ($multi as $file) {
                if ($file && $file->isValid() && !$file->hasMoved()) {
                    $files[] = $file;
                }
            }
        }

        // Fallback legado para chamadas antigas que enviam somente foto_perfil
        if (empty($files)) {
            $single = $this->request->getFile('foto_perfil');
            if ($single && $single->isValid() && !$single->hasMoved()) {
                $files[] = $single;
            }
        }

        return $files;
    }

    private function validateAjaxEquipamentoAssets(array $dados, array $uploadedFiles, int $equipamentoId = 0): array
    {
        $errors = [];
        $focusTab = null;

        $corHex = trim((string) ($dados['cor_hex'] ?? ''));
        $corNome = trim((string) ($dados['cor'] ?? ''));
        if ($corHex === '' || $corNome === '') {
            $errors['cor'] = 'Informe a cor correta do equipamento.';
            $focusTab = 'cor';
        }

        $fotosExistentes = 0;
        if ($equipamentoId > 0) {
            $fotoModel = new EquipamentoFotoModel();
            $fotosExistentes = (int) $fotoModel->where('equipamento_id', $equipamentoId)->countAllResults();
        }

        if (($fotosExistentes + count($uploadedFiles)) <= 0) {
            $errors['fotos'] = 'Adicione ao menos uma foto do equipamento.';
            $focusTab = $focusTab ?: 'foto';
        }

        return [
            'errors'    => $errors,
            'focus_tab' => $focusTab,
        ];
    }

    /**
     * @param array<string,mixed> $dados
     * @param array<string,mixed> $current
     * @return array<string,string>
     */
    private function validateEquipamentoPayload(array $dados, array $current = []): array
    {
        $errors = [];

        if ((int) ($dados['cliente_id'] ?? $current['cliente_id'] ?? 0) <= 0) {
            $errors['cliente_id'] = 'Selecione o cliente do equipamento.';
        }

        if ((int) ($dados['tipo_id'] ?? $current['tipo_id'] ?? 0) <= 0) {
            $errors['tipo_id'] = 'Selecione o tipo do equipamento.';
        }

        return array_merge($errors, $this->profileService->validateCatalogRules($dados, $current));
    }

    private function resolveEquipamentoValidationFocusTab(array $errors): string
    {
        foreach (['tipo_id', 'marca_id', 'modelo_id', 'cliente_id'] as $field) {
            if (!empty($errors[$field])) {
                return 'info';
            }
        }

        foreach (['cor', 'cor_hex'] as $field) {
            if (!empty($errors[$field])) {
                return 'cor';
            }
        }

        foreach (['fotos', 'foto_perfil'] as $field) {
            if (!empty($errors[$field])) {
                return 'foto';
            }
        }

        return 'info';
    }

    private function translateEquipamentoValidationErrors(array $errors): array
    {
        $labels = [
            'cliente_id' => 'Selecione o cliente do equipamento.',
            'tipo_id'    => 'Selecione o tipo do equipamento.',
            'marca_id'   => 'Selecione a marca do equipamento.',
            'modelo_id'  => 'Selecione o modelo do equipamento.',
            'cor'        => 'Informe a cor correta do equipamento.',
            'cor_hex'    => 'Informe a cor correta do equipamento.',
            'fotos'      => 'Adicione ao menos uma foto do equipamento.',
            'foto_perfil'=> 'Adicione ao menos uma foto do equipamento.',
        ];

        $translated = [];
        foreach ($errors as $field => $message) {
            $translated[$field] = $labels[$field] ?? $message;
        }

        return $translated;
    }

    /**
     * @param array<string,mixed> $conflict
     * @return array<string,mixed>
     */
    private function buildDuplicateConflictPayload(array $conflict): array
    {
        $equipmentId = (int) ($conflict['equipment_id'] ?? 0);
        $equipamento = $equipmentId > 0 ? $this->findEquipamentoForJson($equipmentId) : null;

        return [
            'equipment_id' => $equipmentId,
            'same_client' => !empty($conflict['same_client']),
            'already_linked' => !empty($conflict['already_linked']),
            'can_link_client' => !empty($conflict['can_link_client']),
            'matched_input' => $conflict['matched_input'] ?? null,
            'matched_record' => $conflict['matched_record'] ?? null,
            'primary_client' => $conflict['primary_client'] ?? null,
            'related_clients' => $conflict['related_clients'] ?? [],
            'message' => (string) ($conflict['message'] ?? ''),
            'edit_url' => $equipmentId > 0 ? base_url('equipamentos/editar/' . $equipmentId) : '',
            'show_url' => $equipmentId > 0 ? base_url('equipamentos/visualizar/' . $equipmentId) : '',
            'equipamento' => $equipamento,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findEquipamentoForJson(int $equipamentoId): ?array
    {
        if ($equipamentoId <= 0) {
            return null;
        }

        $equip = $this->model->select(
            'equipamentos.*, et.nome as tipo_nome, em.nome as marca_nome, emod.nome as modelo_nome, et.id as tipo_id'
        )
            ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->find($equipamentoId);

        if (!$equip) {
            return null;
        }

        $equip = $this->profileService->appendDerivedFields($equip);
        $equip['fotos'] = $this->getHydratedFotosByEquipamentoId($equipamentoId);
        $equip['cliente_vinculados'] = (new EquipamentoClienteModel())->getClientesVinculados($equipamentoId);

        return $equip;
    }

    private function equipamentoOsAbertasCount(int $equipamentoId): int
    {
        if ($equipamentoId <= 0) {
            return 0;
        }

        $builder = (new OsModel())->where('equipamento_id', $equipamentoId);
        $db = \Config\Database::connect();

        if ($db->fieldExists('estado_fluxo', 'os')) {
            $builder->groupStart()
                ->where('estado_fluxo IS NULL', null, false)
                ->orWhere("TRIM(COALESCE(estado_fluxo, '')) = ''", null, false)
                ->orWhereNotIn('estado_fluxo', ['encerrado', 'cancelado'])
            ->groupEnd();
        } else {
            $builder->whereNotIn('status', ['entregue_reparado', 'devolvido_sem_reparo', 'descartado', 'cancelado']);
        }

        return (int) $builder->countAllResults();
    }

    /**
     * @return array{historicoLifecycle: array<int, array<string,mixed>>, historicoLifecycleCount: int}
     */
    private function buildLifecycleHistoryViewData(int $equipamentoId): array
    {
        $historicoModel = new EquipamentoLifecycleHistoricoModel();
        $historicoLifecycle = $historicoModel->byEquipamento($equipamentoId);

        return [
            'historicoLifecycle' => $historicoLifecycle,
            'historicoLifecycleCount' => count($historicoLifecycle),
        ];
    }

    private function recordLifecycleHistoryEvent(int $equipamentoId, string $evento, array $data = []): ?array
    {
        $historicoModel = new EquipamentoLifecycleHistoricoModel();
        if (! $historicoModel->supportsLifecycleHistory()) {
            return null;
        }

        $registro = $historicoModel->registrarEvento($equipamentoId, $evento, $data);
        if ($registro === null) {
            log_message(
                'warning',
                '[Equipamentos] Nao foi possivel registrar historico de ciclo de vida do equipamento {equipamento_id} para o evento {evento}.',
                [
                    'equipamento_id' => $equipamentoId,
                    'evento' => $evento,
                ]
            );
        }

        return $registro;
    }

    private function appendEquipamentoFotos(int $equipamentoId, array $uploadedFiles, bool $forceNewAsPrincipal = false): array
    {
        $files = [];
        foreach ($uploadedFiles as $file) {
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $files[] = $file;
            }
        }

        if (empty($files)) {
            return ['warning' => null, 'principal_url' => null];
        }

        $this->normalizeEquipamentoFotosStorage($equipamentoId);
        $fotoModel = new EquipamentoFotoModel();
        $fotosExistentes = (int) $fotoModel->where('equipamento_id', $equipamentoId)->countAllResults();
        $vagasDisponiveis = max(0, self::MAX_FOTOS_POR_EQUIPAMENTO - $fotosExistentes);

        if ($vagasDisponiveis <= 0) {
            return [
                'warning' => 'Este equipamento ja possui 4 fotos. Remova uma foto antes de adicionar outra.',
                'principal_url' => null
            ];
        }

        $warning = null;
        if (count($files) > $vagasDisponiveis) {
            $warning = "Somente {$vagasDisponiveis} foto(s) foram adicionadas para manter o limite de 4 por equipamento.";
        }
        $files = array_slice($files, 0, $vagasDisponiveis);

        $folderName = $this->buildEquipamentoPerfilFolderName($equipamentoId);
        $dirAbs = $this->ensurePerfilFolder($folderName);
        $nextIndex = $this->getNextPerfilIndex($dirAbs);

        $isPrincipal = 0;
        if ($forceNewAsPrincipal) {
            $fotoModel->where('equipamento_id', $equipamentoId)->set(['is_principal' => 0])->update();
            $isPrincipal = 1;
        } else {
            $hasPrincipal = $fotoModel->where('equipamento_id', $equipamentoId)->where('is_principal', 1)->first();
            $isPrincipal = $hasPrincipal ? 0 : 1;
        }

        $principalUrl = null;
        foreach ($files as $file) {
            $ext = strtolower((string) $file->getExtension());
            if ($ext === '') {
                $ext = 'jpg';
            }

            $newName = "perfil_{$nextIndex}.{$ext}";
            while (is_file($dirAbs . DIRECTORY_SEPARATOR . $newName)) {
                $nextIndex++;
                $newName = "perfil_{$nextIndex}.{$ext}";
            }

            $file->move($dirAbs, $newName);
            $relativePath = $folderName . '/' . $newName;

            $fotoModel->insert([
                'equipamento_id' => $equipamentoId,
                'arquivo'        => $relativePath,
                'is_principal'   => $isPrincipal,
                'created_at'     => date('Y-m-d H:i:s')
            ]);

            if ($isPrincipal === 1) {
                $principalUrl = $this->buildFotoPublicUrl($relativePath);
            }

            $isPrincipal = 0;
            $nextIndex++;
        }

        return [
            'warning' => $warning,
            'principal_url' => $principalUrl
        ];
    }

    private function hydrateFotosUrls(array $fotos): array
    {
        foreach ($fotos as &$foto) {
            $foto['arquivo'] = str_replace('\\', '/', (string) ($foto['arquivo'] ?? ''));
            $foto['url'] = $this->buildFotoPublicUrl($foto['arquivo']);
        }
        unset($foto);
        return $fotos;
    }

    private function getHydratedFotosByEquipamentoId(int $equipamentoId): array
    {
        if ($equipamentoId <= 0) {
            return [];
        }

        $fotoModel = new EquipamentoFotoModel();
        $fotos = $fotoModel->where('equipamento_id', $equipamentoId)
            ->orderBy('is_principal', 'DESC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->hydrateFotosUrls($fotos);
    }

    private function buildFotoPublicUrl(string $arquivo): string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        if ($arquivo === '') {
            return $this->missingImageDataUri();
        }

        $pathPerfil = $this->buildPerfilAbsolutePath($arquivo);
        if (is_file($pathPerfil)) {
            return base_url('uploads/equipamentos_perfil/' . $arquivo);
        }

        $legacyPerfil = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . basename($arquivo);
        if (is_file($legacyPerfil)) {
            return base_url('uploads/equipamentos_perfil/' . basename($arquivo));
        }

        $legacyUploadPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos' . DIRECTORY_SEPARATOR . basename($arquivo);
        if (is_file($legacyUploadPath)) {
            return base_url('uploads/equipamentos/' . basename($arquivo));
        }

        return $this->missingImageDataUri();
    }

    private function missingImageDataUri(): string
    {
        static $uri = null;
        if ($uri !== null) {
            return $uri;
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="180" height="120" viewBox="0 0 180 120"><rect width="180" height="120" rx="10" fill="#eef2ff"/><rect x="62" y="34" width="56" height="36" rx="6" fill="#c7d2fe"/><circle cx="90" cy="52" r="10" fill="#818cf8"/><text x="90" y="96" text-anchor="middle" font-size="12" fill="#64748b">sem foto</text></svg>';
        $uri = 'data:image/svg+xml;base64,' . base64_encode($svg);
        return $uri;
    }

    private function buildPerfilAbsolutePath(string $arquivo): string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        $relative = str_replace('/', DIRECTORY_SEPARATOR, $arquivo);
        return FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . $relative;
    }

    private function removeEmptyPerfilFolder(string $filePath): void
    {
        $baseDir = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil';
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            return;
        }

        $realBase = realpath($baseDir);
        $realDir = realpath($dir);
        if (!$realBase || !$realDir) {
            return;
        }
        if (strpos($realDir, $realBase) !== 0 || $realDir === $realBase) {
            return;
        }

        $items = array_diff(scandir($realDir), ['.', '..']);
        if (empty($items)) {
            @rmdir($realDir);
        }
    }

    private function resolveFotoAbsolutePath(string $arquivo): ?string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        if ($arquivo === '') {
            return null;
        }

        $candidates = [
            $this->buildPerfilAbsolutePath($arquivo),
            FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . basename($arquivo),
            FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos' . DIRECTORY_SEPARATOR . basename($arquivo),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeEquipamentoFotosStorage(int $equipamentoId): void
    {
        if ($equipamentoId <= 0) {
            return;
        }

        $fotoModel = new EquipamentoFotoModel();
        $fotos = $fotoModel->where('equipamento_id', $equipamentoId)
            ->orderBy('is_principal', 'DESC')
            ->orderBy('id', 'ASC')
            ->findAll();

        if (empty($fotos)) {
            return;
        }

        $folderName = $this->buildEquipamentoPerfilFolderName($equipamentoId);
        $targetDir = $this->ensurePerfilFolder($folderName);

        $usedNames = [];
        $sequence = 1;
        foreach ($fotos as $foto) {
            $arquivoAtual = str_replace('\\', '/', ltrim((string) ($foto['arquivo'] ?? ''), '/'));
            $pathAtual = $this->resolveFotoAbsolutePath($arquivoAtual);

            $ext = strtolower((string) pathinfo($arquivoAtual, PATHINFO_EXTENSION));
            if ($ext === '' && $pathAtual) {
                $ext = strtolower((string) pathinfo($pathAtual, PATHINFO_EXTENSION));
            }
            if ($ext === '') {
                $ext = 'jpg';
            }

            $newName = "perfil_{$sequence}.{$ext}";
            while (isset($usedNames[$newName]) || is_file($targetDir . DIRECTORY_SEPARATOR . $newName)) {
                $existingAbs = $targetDir . DIRECTORY_SEPARATOR . $newName;
                if ($pathAtual && realpath($pathAtual) === realpath($existingAbs)) {
                    break;
                }
                $sequence++;
                $newName = "perfil_{$sequence}.{$ext}";
            }
            $usedNames[$newName] = true;

            $novoArquivo = $folderName . '/' . $newName;
            $destino = $targetDir . DIRECTORY_SEPARATOR . $newName;
            $pathReady = false;

            if ($pathAtual) {
                if (realpath($pathAtual) === realpath($destino)) {
                    $pathReady = true;
                } else {
                    $moved = @rename($pathAtual, $destino);
                    if (!$moved) {
                        $moved = @copy($pathAtual, $destino);
                        if ($moved) {
                            @unlink($pathAtual);
                        }
                    }
                    if ($moved) {
                        $this->removeEmptyPerfilFolder($pathAtual);
                        $pathReady = true;
                    }
                }
            } elseif (strpos($arquivoAtual, $folderName . '/') === 0) {
                $pathReady = true;
            }

            if ($pathReady && $arquivoAtual !== $novoArquivo) {
                $fotoModel->update((int) $foto['id'], ['arquivo' => $novoArquivo]);
            }

            $sequence++;
        }
    }

    private function buildEquipamentoPerfilFolderName(int $equipamentoId): string
    {
        $equip = $this->model->select('equipamentos.id, equipamentos.cliente_id, modelos.nome as modelo_nome')
            ->join('equipamentos_modelos modelos', 'modelos.id = equipamentos.modelo_id', 'left')
            ->where('equipamentos.id', $equipamentoId)
            ->first();

        $modeloParte = $this->slugify((string) ($equip['modelo_nome'] ?? 'equipamento'), '-');
        $clientesPartes = $this->getClienteFolderParts($equipamentoId, isset($equip['cliente_id']) ? (int) $equip['cliente_id'] : 0);
        if (empty($clientesPartes)) {
            $clientesPartes = ['cliente'];
        }

        $folderBase = trim($modeloParte . '-' . implode('-', $clientesPartes), '-');
        if ($folderBase === '') {
            $folderBase = 'equipamento-cliente';
        }

        $fotoModel = new EquipamentoFotoModel();
        $conflict = $fotoModel->where('equipamento_id !=', $equipamentoId)
            ->like('arquivo', $folderBase . '/', 'after')
            ->first();

        if ($conflict) {
            return $folderBase . '-eq' . $equipamentoId;
        }

        return $folderBase;
    }

    private function ensurePerfilFolder(string $folderName): string
    {
        $baseDir = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil';
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
        }

        $folderName = trim(str_replace(['\\', '/'], '-', $folderName), '-');
        if ($folderName === '') {
            $folderName = 'equipamento-cliente';
        }

        $dir = $baseDir . DIRECTORY_SEPARATOR . $folderName;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private function getNextPerfilIndex(string $dirAbs): int
    {
        if (!is_dir($dirAbs)) {
            return 1;
        }

        $max = 0;
        foreach (scandir($dirAbs) ?: [] as $item) {
            if (!preg_match('/^perfil_(\d+)\.(jpg|jpeg|png|webp)$/i', (string) $item, $match)) {
                continue;
            }
            $index = (int) ($match[1] ?? 0);
            if ($index > $max) {
                $max = $index;
            }
        }
        return $max + 1;
    }

    private function getClienteFolderParts(int $equipamentoId, int $clientePrincipalId = 0): array
    {
        $ids = [];
        if ($clientePrincipalId > 0) {
            $ids[] = $clientePrincipalId;
        }

        $vinculos = (new EquipamentoClienteModel())
            ->select('cliente_id')
            ->where('equipamento_id', $equipamentoId)
            ->findAll();

        foreach ($vinculos as $vinculo) {
            $cid = (int) ($vinculo['cliente_id'] ?? 0);
            if ($cid > 0 && !in_array($cid, $ids, true)) {
                $ids[] = $cid;
            }
        }

        if (empty($ids)) {
            return [];
        }

        $clientes = (new ClienteModel())
            ->select('id, nome_razao')
            ->whereIn('id', $ids)
            ->findAll();

        $nomeById = [];
        foreach ($clientes as $cliente) {
            $nomeById[(int) $cliente['id']] = (string) ($cliente['nome_razao'] ?? '');
        }

        $parts = [];
        foreach ($ids as $id) {
            $nome = $nomeById[$id] ?? '';
            $segment = $this->slugify($nome, '_');
            if ($segment !== '' && !in_array($segment, $parts, true)) {
                $parts[] = $segment;
            }
        }
        return $parts;
    }

    private function slugify(string $value, string $delimiter = '-'): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'item';
        }

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($normalized === false) {
            $normalized = $value;
        }
        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/i', $delimiter, $normalized ?? '');
        $normalized = trim((string) $normalized, $delimiter);
        return $normalized !== '' ? $normalized : 'item';
    }

    private function normalizeSenhaAcessoPayload(array $dados): array
    {
        $senhaTipo = strtolower(trim((string) ($dados['senha_tipo'] ?? '')));
        $senhaTexto = trim((string) ($dados['senha_acesso'] ?? ''));
        $senhaDesenho = trim((string) ($dados['senha_desenho'] ?? ''));

        $patternSource = '';
        if ($senhaTipo === 'desenho' && $senhaDesenho !== '') {
            $patternSource = $senhaDesenho;
        } elseif ($senhaTipo !== 'texto' && str_starts_with($senhaTexto, 'desenho_')) {
            $patternSource = substr($senhaTexto, 8);
            $senhaTipo = 'desenho';
        }

        if ($senhaTipo === 'desenho') {
            $parts = preg_split('/[^1-9]+/', $patternSource) ?: [];
            $sequence = [];
            foreach ($parts as $part) {
                $point = (string) $part;
                if ($point === '' || in_array($point, $sequence, true)) {
                    continue;
                }
                $sequence[] = $point;
            }

            $dados['senha_acesso'] = !empty($sequence)
                ? 'desenho_' . implode('-', $sequence)
                : '';
        } else {
            $dados['senha_acesso'] = $senhaTexto;
        }

        unset($dados['senha_tipo'], $dados['senha_desenho']);
        return $dados;
    }

    /**
     * Auxiliar para processar marca_id e modelo_id que podem ser strings (novos cadastros)
     */
    private function processarMarcaModelo(array $dados)
    {
        // Tratar Marca DinÃƒÂ¢mica
        if (isset($dados['marca_id']) && !is_numeric($dados['marca_id'])) {
            $marcaModel = new \App\Models\EquipamentoMarcaModel();
            $marcaModel->insert(['nome' => $dados['marca_id']]);
            $dados['marca_id'] = $marcaModel->getInsertID();
        }

        // Tratar Modelo DinÃƒÂ¢mico
        if (isset($dados['modelo_id']) && !is_numeric($dados['modelo_id'])) {
            $modeloModel = new \App\Models\EquipamentoModeloModel();

            // Caso venha da Ponte de Modelos (EXT|...) ou Autocomplete do Google
            if (strpos($dados['modelo_id'], 'EXT|') === 0) {
                $nomeModelo = $this->request->getPost('modelo_nome_ext') ?? $dados['modelo_id'];
                // Limpeza de prefixos diversos que podem aparecer
                $nomeModelo = str_ireplace(['EXT|GGL_', 'EXT|MLB_', 'EXT|'], '', $nomeModelo);

                $modeloModel->insert([
                    'marca_id' => $dados['marca_id'],
                    'nome'     => ucwords(trim($nomeModelo)),
                    'ativo'    => 1
                ]);
            } else {
                // Cadastro manual simples via Modal ou tag direta
                $modeloModel->insert([
                    'marca_id' => $dados['marca_id'],
                    'nome'     => trim($dados['modelo_id']),
                    'ativo'    => 1
                ]);
            }
            $dados['modelo_id'] = $modeloModel->getInsertID();
        }

        $this->syncCatalogoRelacaoFromPayload($dados);
        return $dados;
    }

    private function resolveBenchCollectorSnapshotPath(): ?string
    {
        $candidates = [];
        $numeroOs = $this->resolveRequestedBenchCollectorNumeroOs();
        if ($numeroOs !== '') {
            $candidates[] = $this->getBenchCollectorNamedSnapshotPath($numeroOs);
        }

        $candidates[] = 'C:\\JovemTechBenchCollector\\last-snapshot.json';
        $candidates[] = 'C:\\JovemTechBenchCollector\\snapshot.json';

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        $rootPath = $this->getBenchCollectorLocalRootPath();
        if (is_dir($rootPath)) {
            $namedSnapshots = glob($rootPath . DIRECTORY_SEPARATOR . 'inf_*.json') ?: [];
            usort($namedSnapshots, static function (string $a, string $b): int {
                return (int) (@filemtime($b) ?: 0) <=> (int) (@filemtime($a) ?: 0);
            });

            foreach ($namedSnapshots as $path) {
                if (is_file($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    private function buildBenchCollectorSnapshotResponsePayload(array $extra = []): array
    {
        $snapshotData = $this->readBenchCollectorSnapshotPayload();

        return array_merge([
            'success' => true,
            'source_path' => $snapshotData['snapshot_path'],
            'saved_at_utc' => $snapshotData['saved_at_utc'],
            'collected_at_utc' => $snapshotData['collected_at_utc'],
            'document' => $snapshotData['document'],
            'snapshot' => $snapshotData['snapshot'],
            'mapped' => $snapshotData['mapped'],
        ], $extra);
    }

    private function readBenchCollectorSnapshotPayload(): array
    {
        $snapshotPath = $this->resolveBenchCollectorSnapshotPath();
        if ($snapshotPath === null) {
            throw new \RuntimeException('Nao encontrei o snapshot local do coletor em C:\\JovemTechBenchCollector\\inf_<numero_os>.json nem nos arquivos de fallback da coleta.', 404);
        }

        $raw = @file_get_contents($snapshotPath);
        if ($raw === false || trim($raw) === '') {
            throw new \RuntimeException('O arquivo do coletor foi encontrado, mas esta vazio ou indisponivel para leitura.', 422);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('O snapshot local do coletor nao esta em JSON valido.', 422);
        }

        $snapshot = $decoded['snapshot'] ?? $decoded;
        if (!is_array($snapshot)) {
            throw new \RuntimeException('O snapshot local nao contem um payload de inventario reconhecido.', 422);
        }

        return [
            'snapshot_path' => $snapshotPath,
            'document' => $decoded,
            'saved_at_utc' => (string) ($decoded['savedAtUtc'] ?? $snapshot['collectedAtUtc'] ?? ''),
            'collected_at_utc' => (string) ($decoded['collectedAtUtc'] ?? $snapshot['collectedAtUtc'] ?? ''),
            'snapshot' => $snapshot,
            'mapped' => $this->mapBenchCollectorSnapshotToEquipamentoFields($snapshot),
        ];
    }

    private function enrichBenchCollectorSnapshotFileWithErpContext(): string
    {
        $snapshotPath = $this->resolveBenchCollectorSnapshotPath();
        if ($snapshotPath === null) {
            return '';
        }

        $raw = @file_get_contents($snapshotPath);
        if ($raw === false || trim($raw) === '') {
            return $snapshotPath;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $snapshotPath;
        }

        $snapshot = $decoded['snapshot'] ?? $decoded;
        if (!is_array($snapshot)) {
            return $snapshotPath;
        }

        $document = $this->buildBenchCollectorDigitalDocumentPayload($decoded, $snapshot);
        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Nao foi possivel serializar o snapshot enriquecido do coletor local.', 500);
        }

        $finalPath = $this->resolveBenchCollectorFinalDocumentPath($snapshotPath, $document);
        if (@file_put_contents($finalPath, $json) === false) {
            throw new \RuntimeException('Nao foi possivel atualizar o arquivo local do coletor com os dados da OS digital.', 500);
        }

        if ($finalPath !== $snapshotPath && is_file($snapshotPath)) {
            @unlink($snapshotPath);
        }

        clearstatcache();
        return $finalPath;
    }

    private function buildBenchCollectorDigitalDocumentPayload(array $decoded, array $snapshot): array
    {
        $context = $this->resolveBenchCollectorDigitalDocumentContext();
        $collectedAtUtc = trim((string) ($decoded['collectedAtUtc'] ?? $snapshot['collectedAtUtc'] ?? ''));
        if ($collectedAtUtc === '') {
            $collectedAtUtc = gmdate('Y-m-d\TH:i:s\Z');
        }

        $collectedAtTimestamp = strtotime($collectedAtUtc) ?: time();
        $savedAtUtc = trim((string) ($decoded['savedAtUtc'] ?? ''));
        $savedAtTimestamp = $savedAtUtc !== '' ? (strtotime($savedAtUtc) ?: time()) : time();

        $serviceOrder = $context['serviceOrder'] ?? [];
        $customer = $context['customer'] ?? [];
        $company = $context['company'] ?? [];

        $erpBaseUrl = trim((string) ($decoded['erpBaseUrl'] ?? ''));
        if ($erpBaseUrl === '') {
            $erpBaseUrl = base_url();
        }

        $warrantyOsNumber = trim((string) ($decoded['warrantyOsNumber'] ?? ''));
        if ($warrantyOsNumber === '' && !empty($serviceOrder['numero'])) {
            $warrantyOsNumber = trim((string) $serviceOrder['numero']);
        }

        $warrantyPublicUrl = trim((string) ($decoded['warrantyPublicUrl'] ?? ''));
        if ($warrantyPublicUrl === '' && !empty($serviceOrder['publicUrl'])) {
            $warrantyPublicUrl = trim((string) $serviceOrder['publicUrl']);
        }

        return [
            'source' => (string) ($decoded['source'] ?? 'JovemTechBenchCollector'),
            'documentType' => !empty($serviceOrder) ? 'digital_service_order' : (string) ($decoded['documentType'] ?? 'inventory_snapshot'),
            'erpBaseUrl' => $erpBaseUrl,
            'warrantyOsNumber' => $warrantyOsNumber,
            'warrantyPublicUrl' => $warrantyPublicUrl,
            'erpLoginEmail' => (string) ($decoded['erpLoginEmail'] ?? ''),
            'installationId' => (string) ($decoded['installationId'] ?? $snapshot['installationId'] ?? ''),
            'agentId' => (string) ($decoded['agentId'] ?? ''),
            'agentLabel' => (string) ($decoded['agentLabel'] ?? ''),
            'collectedAtUtc' => $collectedAtUtc,
            'collectedAtLocal' => date('d/m/Y H:i:s', $collectedAtTimestamp),
            'savedAtUtc' => $savedAtUtc !== '' ? $savedAtUtc : gmdate('Y-m-d\TH:i:s\Z', $savedAtTimestamp),
            'savedAtLocal' => date('d/m/Y H:i:s', $savedAtTimestamp),
            'serviceOrder' => $serviceOrder,
            'customer' => $customer,
            'company' => $company,
            'snapshot' => $snapshot,
        ];
    }

    private function resolveBenchCollectorFinalDocumentPath(string $currentPath, array $document): string
    {
        $numeroOs = trim((string) ($document['warrantyOsNumber'] ?? $document['serviceOrder']['numero'] ?? ''));
        if ($numeroOs === '') {
            return $currentPath;
        }

        return $this->getBenchCollectorNamedSnapshotPath($numeroOs);
    }

    private function resolveBenchCollectorDigitalDocumentContext(): array
    {
        $company = [
            'name' => trim((string) get_config('empresa_nome', 'Jovem Tech')),
            'phone' => trim((string) get_config('empresa_telefone', '')),
            'email' => trim((string) get_config('empresa_email', '')),
            'address' => trim((string) get_config('empresa_endereco', '')),
        ];

        $order = null;
        $osId = (int) $this->request->getGet('os_id');
        if ($osId > 0) {
            $order = (new OsModel())->getComplete($osId);
        }

        if (!$order) {
            $numeroOs = trim((string) $this->request->getGet('numero_os'));
            if ($numeroOs !== '') {
                $order = (new OsModel())->getCompleteByNumeroOs($numeroOs);
            }
        }

        if ($order) {
            $numeroOs = trim((string) ($order['numero_os'] ?? $order['numero_os_legado'] ?? ''));
            $equipmentLabel = equipamento_rotulo_exibicao($order);
            $customer = [
                'id' => (int) ($order['cliente_id'] ?? 0),
                'name' => trim((string) ($order['cliente_nome'] ?? '')),
                'phone' => trim((string) ($order['cliente_telefone'] ?? '')),
                'email' => trim((string) ($order['cliente_email'] ?? '')),
            ];

            return [
                'company' => $company,
                'customer' => $customer,
                'serviceOrder' => [
                    'id' => (int) ($order['id'] ?? 0),
                    'numero' => $numeroOs,
                    'numeroLegado' => trim((string) ($order['numero_os_legado'] ?? '')),
                    'publicUrl' => $numeroOs !== '' ? $this->buildBenchCollectorWarrantyPublicUrl($numeroOs) : '',
                    'status' => trim((string) ($order['status'] ?? '')),
                    'prioridade' => trim((string) ($order['prioridade'] ?? '')),
                    'clienteNome' => $customer['name'],
                    'clienteTelefone' => $customer['phone'],
                    'clienteEmail' => $customer['email'],
                    'equipamento' => $equipmentLabel,
                    'tecnico' => trim((string) ($order['tecnico_nome'] ?? '')),
                    'relatoCliente' => trim((string) ($order['relato_cliente'] ?? '')),
                    'dataAbertura' => trim((string) ($order['data_abertura'] ?? '')),
                    'dataEntrada' => trim((string) ($order['data_entrada'] ?? '')),
                    'dataPrevisao' => trim((string) ($order['data_previsao'] ?? '')),
                    'dataConclusao' => trim((string) ($order['data_conclusao'] ?? '')),
                ],
            ];
        }

        $clienteId = (int) $this->request->getGet('cliente_id');
        $customer = [];
        if ($clienteId > 0) {
            $cliente = (new ClienteModel())->find($clienteId);
            if (is_array($cliente)) {
                $customer = [
                    'id' => (int) ($cliente['id'] ?? 0),
                    'name' => trim((string) ($cliente['nome_razao'] ?? '')),
                    'phone' => trim((string) ($cliente['telefone1'] ?? $cliente['telefone'] ?? '')),
                    'email' => trim((string) ($cliente['email'] ?? '')),
                ];
            }
        }

        if (empty($customer)) {
            $customer = [
                'id' => $clienteId,
                'name' => trim((string) $this->request->getGet('cliente_nome')),
                'phone' => trim((string) $this->request->getGet('cliente_telefone')),
                'email' => trim((string) $this->request->getGet('cliente_email')),
            ];
        }

        $numeroOs = trim((string) $this->request->getGet('numero_os'));
        $serviceOrder = [
            'id' => 0,
            'numero' => $numeroOs,
            'numeroLegado' => '',
            'publicUrl' => $numeroOs !== '' ? $this->buildBenchCollectorWarrantyPublicUrl($numeroOs) : '',
            'status' => trim((string) $this->request->getGet('status')),
            'prioridade' => trim((string) $this->request->getGet('prioridade')),
            'clienteNome' => trim((string) ($customer['name'] ?? '')),
            'clienteTelefone' => trim((string) ($customer['phone'] ?? '')),
            'clienteEmail' => trim((string) ($customer['email'] ?? '')),
            'equipamento' => trim((string) $this->request->getGet('equipamento_rotulo')),
            'tecnico' => trim((string) $this->request->getGet('tecnico_nome')),
            'relatoCliente' => trim((string) $this->request->getGet('relato_cliente')),
            'dataAbertura' => trim((string) $this->request->getGet('data_abertura')),
            'dataEntrada' => trim((string) $this->request->getGet('data_entrada')),
            'dataPrevisao' => trim((string) $this->request->getGet('data_previsao')),
            'dataConclusao' => trim((string) $this->request->getGet('data_conclusao')),
        ];

        if (
            $serviceOrder['numero'] === ''
            && $serviceOrder['status'] === ''
            && $serviceOrder['prioridade'] === ''
            && $serviceOrder['equipamento'] === ''
            && $serviceOrder['tecnico'] === ''
            && $serviceOrder['relatoCliente'] === ''
            && $serviceOrder['dataEntrada'] === ''
            && $serviceOrder['dataPrevisao'] === ''
        ) {
            $serviceOrder = [];
        }

        return [
            'company' => $company,
            'customer' => $customer,
            'serviceOrder' => $serviceOrder,
        ];
    }

    private function buildBenchCollectorWarrantyPublicUrl(string $numeroOs): string
    {
        $numeroOs = trim($numeroOs);
        if ($numeroOs === '') {
            return '';
        }

        $signature = hash_hmac(
            'sha256',
            strtolower($numeroOs),
            trim((string) env('warranty.publicSecret', '')) ?: 'warranty-public-dev-secret'
        );

        return site_url('api/public/warranty/' . rawurlencode($numeroOs)) . '?sig=' . rawurlencode($signature);
    }

    private function resolveRequestedBenchCollectorNumeroOs(): string
    {
        $numeroOs = trim((string) $this->request->getGet('numero_os'));
        if ($numeroOs !== '') {
            return $numeroOs;
        }

        $osId = (int) $this->request->getGet('os_id');
        if ($osId > 0) {
            $order = (new OsModel())->getComplete($osId);
            if (is_array($order)) {
                return trim((string) ($order['numero_os'] ?? $order['numero_os_legado'] ?? ''));
            }
        }

        return '';
    }

    private function getBenchCollectorNamedSnapshotPath(string $numeroOs): string
    {
        $token = $this->normalizeBenchCollectorFileToken($numeroOs);
        if ($token === '') {
            return $this->getBenchCollectorLocalRootPath() . DIRECTORY_SEPARATOR . 'last-snapshot.json';
        }

        return $this->getBenchCollectorLocalRootPath() . DIRECTORY_SEPARATOR . 'inf_' . $token . '.json';
    }

    private function normalizeBenchCollectorFileToken(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function mapBenchCollectorSnapshotToEquipamentoFields(array $snapshot): array
    {
        $motherboard = trim((string) ($snapshot['motherboard'] ?? ''));
        $chipset = trim((string) ($snapshot['chipset'] ?? ''));
        $cpu = trim((string) ($snapshot['cpu'] ?? ''));
        $gpu = trim((string) ($snapshot['gpu'] ?? ''));
        $storageSummary = trim((string) ($snapshot['storageSummary'] ?? ''));
        $memorySummary = trim((string) ($snapshot['memorySummary'] ?? ''));
        $ramGb = $snapshot['ramGb'] ?? null;
        $serialNumber = trim((string) ($snapshot['serialNumber'] ?? ''));
        $manufacturer = trim((string) ($snapshot['manufacturer'] ?? ''));
        $model = trim((string) ($snapshot['model'] ?? ''));
        $deviceType = trim((string) ($snapshot['deviceType'] ?? ''));
        $chassisType = trim((string) ($snapshot['chassisType'] ?? ''));
        $serialSource = trim((string) ($snapshot['serialSource'] ?? ''));
        $gabineteStatus = '';
        $gabineteTipo = '';
        $catalogModel = $chipset !== '' ? $chipset : $model;

        if ($memorySummary === '' && $ramGb !== null && $ramGb !== '') {
            $memorySummary = rtrim(rtrim(number_format((float) $ramGb, 2, '.', ''), '0'), '.') . ' GB';
        }

        if (strtolower($deviceType) === 'desktop') {
            $gabineteStatus = $chassisType !== '' ? 'detectado' : 'a_confirmar';
            $gabineteTipo = $this->mapChassisTypeToGabineteTipo($chassisType);
        }

        return [
            'numero_serie' => $serialNumber,
            'numero_serie_origem' => $serialSource,
            'placa_mae' => $motherboard,
            'chipset' => $chipset,
            'processador' => $cpu,
            'memoria_ram' => $memorySummary,
            'armazenamento' => $storageSummary,
            'placa_video' => $gpu,
            'gabinete_identificacao_status' => $gabineteStatus,
            'gabinete_tipo' => $gabineteTipo,
            'device_type' => $deviceType,
            'chassis_type' => $chassisType,
            'manufacturer' => $manufacturer,
            'catalog_model' => $catalogModel,
            'model' => $model,
        ];
    }

    private function runBenchCollectorLocalCapture(): array
    {
        if (!$this->isWindowsHost()) {
            throw new \RuntimeException('A coleta local automatica so esta disponivel quando o ERP estiver rodando em Windows na mesma maquina da bancada.', 422);
        }

        if (!function_exists('exec')) {
            throw new \RuntimeException('O PHP deste ambiente nao permite executar o coletor local automaticamente.', 500);
        }

        $installInfo = $this->ensureBenchCollectorInstalled();
        $this->clearBenchCollectorSnapshotCache();

        $command = '"' . $installInfo['executable_path'] . '" --dry-run --no-prompt --no-save-config';
        $requestedNumeroOs = $this->resolveRequestedBenchCollectorNumeroOs();
        if ($requestedNumeroOs !== '') {
            $command .= ' --warranty-os-number ' . $this->escapeBenchCollectorCommandArgument($requestedNumeroOs);
        }

        $output = [];
        $exitCode = 1;
        @exec($command . ' 2>&1', $output, $exitCode);

        $result = [
            'executable_path' => $installInfo['executable_path'],
            'installed_now' => $installInfo['installed_now'],
            'output' => trim(implode("\n", $output)),
            'exit_code' => $exitCode,
        ];

        if ($exitCode !== 0) {
            $snapshotPath = $this->resolveBenchCollectorSnapshotPath();
            if ($snapshotPath !== null) {
                $result['warning'] = 'O coletor retornou aviso na execucao, mas um snapshot local foi encontrado apos a tentativa.';
                return $result;
            }

            $details = $result['output'] !== ''
                ? ' Saida do coletor: ' . $result['output']
                : '';
            throw new \RuntimeException('Nao foi possivel executar o coletor local automaticamente.' . $details, 500);
        }

        if ($this->resolveBenchCollectorSnapshotPath() === null) {
            throw new \RuntimeException('O coletor foi executado, mas nao gerou o snapshot local esperado em C:\\JovemTechBenchCollector.', 422);
        }

        return $result;
    }

    private function ensureBenchCollectorInstalled(): array
    {
        $rootPath = $this->getBenchCollectorLocalRootPath();
        $sourceExe = $this->getBenchCollectorPublishedExecutablePath();
        $sourceReadme = $this->getBenchCollectorPublishedReadmePath();
        $targetExe = $this->getBenchCollectorLocalExecutablePath();

        if (!is_file($sourceExe)) {
            throw new \RuntimeException('Nao encontrei o executavel publicado do coletor em public/assets/agents/bench-collector/win-x64.', 500);
        }

        if (!is_dir($rootPath) && !@mkdir($rootPath, 0777, true) && !is_dir($rootPath)) {
            throw new \RuntimeException('Nao foi possivel criar a pasta local C:\\JovemTechBenchCollector para o coletor.', 500);
        }

        $targetExists = is_file($targetExe);
        $sourceMtime = @filemtime($sourceExe) ?: 0;
        $targetMtime = $targetExists ? (@filemtime($targetExe) ?: 0) : 0;
        $installedNow = false;
        if (
            !$targetExists
            || @filesize($targetExe) !== @filesize($sourceExe)
            || $sourceMtime > $targetMtime
        ) {
            if (!@copy($sourceExe, $targetExe)) {
                throw new \RuntimeException('Nao foi possivel copiar o coletor para C:\\JovemTechBenchCollector.', 500);
            }
            $installedNow = true;
        }

        if (is_file($sourceReadme)) {
            @copy($sourceReadme, $rootPath . DIRECTORY_SEPARATOR . 'README.md');
        }

        return [
            'root_path' => $rootPath,
            'executable_path' => $targetExe,
            'installed_now' => $installedNow,
        ];
    }

    private function clearBenchCollectorSnapshotCache(): void
    {
        $paths = [
            'C:\\JovemTechBenchCollector\\last-snapshot.json',
            'C:\\JovemTechBenchCollector\\snapshot.json',
        ];

        $numeroOs = $this->resolveRequestedBenchCollectorNumeroOs();
        if ($numeroOs !== '') {
            $paths[] = $this->getBenchCollectorNamedSnapshotPath($numeroOs);
        }

        foreach (array_unique($paths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        clearstatcache();
    }

    private function cleanupBenchCollectorTemporaryArtifacts(string $keepSnapshotPath = ''): array
    {
        $removed = [];

        foreach ([
            $this->getBenchCollectorLocalExecutablePath(),
            $this->getBenchCollectorLocalRootPath() . DIRECTORY_SEPARATOR . 'README.md',
        ] as $path) {
            if (is_file($path) && @unlink($path)) {
                $removed[] = $path;
            }
        }

        foreach ([
            'C:\\JovemTechBenchCollector\\last-snapshot.json',
            'C:\\JovemTechBenchCollector\\snapshot.json',
        ] as $path) {
            if ($keepSnapshotPath !== '' && strcasecmp($path, $keepSnapshotPath) === 0) {
                continue;
            }
            if (is_file($path)) {
                @unlink($path);
            }
        }

        clearstatcache();

        return [
            'removed_paths' => $removed,
            'kept_snapshot_path' => $keepSnapshotPath,
        ];
    }

    private function getBenchCollectorPublishedExecutablePath(): string
    {
        return rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'agents' . DIRECTORY_SEPARATOR . 'bench-collector' . DIRECTORY_SEPARATOR . 'win-x64' . DIRECTORY_SEPARATOR . 'JovemTechBenchCollector.exe';
    }

    private function getBenchCollectorPublishedReadmePath(): string
    {
        return rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'agents' . DIRECTORY_SEPARATOR . 'bench-collector' . DIRECTORY_SEPARATOR . 'win-x64' . DIRECTORY_SEPARATOR . 'README.md';
    }

    private function getBenchCollectorLocalRootPath(): string
    {
        return 'C:\\JovemTechBenchCollector';
    }

    private function getBenchCollectorLocalExecutablePath(): string
    {
        return $this->getBenchCollectorLocalRootPath() . DIRECTORY_SEPARATOR . 'JovemTechBenchCollector.exe';
    }

    private function escapeBenchCollectorCommandArgument(string $value): string
    {
        return '"' . str_replace('"', '\"', trim($value)) . '"';
    }

    private function isWindowsHost(): bool
    {
        return strtoupper(substr(PHP_OS_FAMILY, 0, 3)) === 'WIN';
    }

    private function respondBenchCollectorFailure(\Throwable $e, string $context)
    {
        $statusCode = (int) $e->getCode();
        if ($statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        return $this->response->setStatusCode($statusCode)->setJSON([
            'success' => false,
            'message' => $e->getMessage() !== ''
                ? $e->getMessage()
                : 'Falha inesperada ao ' . $context . '.',
        ]);
    }

    private function mapChassisTypeToGabineteTipo(string $chassisType): string
    {
        $label = strtolower(trim($chassisType));
        if ($label === '') {
            return '';
        }

        if (str_contains($label, 'rack')) {
            return 'Rack / Industrial';
        }

        if (str_contains($label, 'mini tower')) {
            return 'Mini Tower';
        }

        if (str_contains($label, 'full tower')) {
            return 'Full Tower';
        }

        if (str_contains($label, 'lunch box')
            || str_contains($label, 'mini pc')
            || str_contains($label, 'stick pc')
            || str_contains($label, 'pizza box')
            || str_contains($label, 'sealed case')
            || str_contains($label, 'cube')) {
            return 'Compacto / Cube';
        }

        if (str_contains($label, 'low profile')
            || str_contains($label, 'space saving')
            || $label === 'desktop') {
            return 'Slim / SFF';
        }

        if (str_contains($label, 'tower')) {
            return 'Mid Tower';
        }

        return 'Nao identificado / A confirmar';
    }

    private function syncCatalogoRelacaoFromPayload(array $dados): void
    {
        $tipoId = (int) ($dados['tipo_id'] ?? 0);
        $marcaId = (int) ($dados['marca_id'] ?? 0);
        $modeloId = (int) ($dados['modelo_id'] ?? 0);
        if ($tipoId <= 0 || $marcaId <= 0 || $modeloId <= 0) {
            return;
        }

        try {
            if (!$this->model->db->tableExists(self::RELATION_TABLE)) {
                return;
            }

            $this->model->db->query(
                'INSERT IGNORE INTO ' . self::RELATION_TABLE . ' (tipo_id, marca_id, modelo_id, ativo, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())',
                [$tipoId, $marcaId, $modeloId]
            );
        } catch (\Throwable $e) {
            log_message('warning', '[Equipamentos] Falha ao sincronizar relacao tipo+marca+modelo: ' . $e->getMessage());
        }
    }
}
