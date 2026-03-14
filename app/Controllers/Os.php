<?php

namespace App\Controllers;

use App\Models\OsModel;
use App\Models\OsItemModel;
use App\Models\ClienteModel;
use App\Models\EquipamentoModel;
use App\Models\UsuarioModel;
use App\Models\FuncionarioModel;
use App\Models\PecaModel;
use App\Models\MovimentacaoModel;
use App\Models\FinanceiroModel;
use App\Models\DefeitoModel;
use App\Models\LogModel;
use App\Models\OsFotoModel;
use App\Models\EquipamentoFotoModel;
use App\Models\AcessorioOsModel;
use App\Models\FotoAcessorioModel;

class Os extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new OsModel();
        requirePermission('os');
    }

    public function index()
    {
        $status = $this->request->getGet('status');
        
        $data = [
            'title'  => 'Ordens de Serviço',
            'filtro_status' => $status,
        ];
        return view('os/index', $data);
    }

    public function datatable()
    {
        $status = $this->request->getPost('status');
        
        $builder = $this->model->select(
                'os.*,
                clientes.nome_razao as cliente_nome,
                em.nome as equip_marca, emod.nome as equip_modelo,
                funcionarios.nome as tecnico_nome'
            )
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->join('funcionarios', 'funcionarios.id = os.tecnico_id', 'left');
                    
        if ($status && $status !== 'todos') {
            $builder->where('os.status', $status);
        }

        $columns = [
            'os.numero_os', 'clientes.nome_razao', 'em.nome', 
            'os.relato_cliente', 'os.data_abertura', 'os.status', 'os.valor_final'
        ];
        
        $searchable = ['os.numero_os', 'clientes.nome_razao', 'em.nome', 'emod.nome'];

        return $this->respondDatatable($builder, $columns, $searchable, function ($row) {
            
            $statusBadge = match($row['status']) {
                'aguardando_analise'   => '<span class="badge bg-secondary">Aguardando Análise</span>',
                'aguardando_orcamento' => '<span class="badge bg-warning text-dark">Aguardando Orçamento</span>',
                'aguardando_aprovacao' => '<span class="badge bg-info">Aguardando Aprovação</span>',
                'aprovado'             => '<span class="badge bg-primary">Aprovado</span>',
                'reprovado'            => '<span class="badge bg-danger">Reprovado</span>',
                'em_reparo'            => '<span class="badge bg-primary">Em Reparo</span>',
                'aguardando_peca'      => '<span class="badge bg-warning text-dark">Aguardando Peça</span>',
                'pronto'               => '<span class="badge bg-success">Pronto</span>',
                'entregue'             => '<span class="badge bg-success bg-opacity-75">Entregue</span>',
                'cancelado'            => '<span class="badge bg-dark">Cancelado</span>',
                default                => '<span class="badge bg-secondary">Desconhecido</span>'
            };

            $valorFormatado = ($row['valor_final'] ?? 0) > 0
                ? 'R$ ' . number_format($row['valor_final'], 2, ',', '.')
                : '-';
            $dataAbertura = date('d/m/Y', strtotime($row['data_abertura']));
            $equipamento  = trim(($row['equip_marca'] ?? '') . ' ' . ($row['equip_modelo'] ?? '')) ?: '-';

            $acoes = '<div class="btn-group btn-group-sm">
                        <a href="'.base_url('os/visualizar/'.$row['id']).'" class="btn btn-outline-info" title="Visualizar"><i class="bi bi-eye"></i></a>';
            if (can('os', 'editar')) {
                $acoes .= '<a href="'.base_url('os/editar/'.$row['id']).'" class="btn btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>';
            }
            if (can('os', 'encerrar')) {
                $acoes .= '<a href="javascript:void(0)" class="btn btn-outline-warning" title="Encerrar" onclick="confirmarEncerramento(\'os\', '.$row['id'].')"><i class="bi bi-archive"></i></a>';
            }
            $acoes .= '</div>';

            return [
                '<strong>#' . esc($row['numero_os']) . '</strong>',
                '<div class="fw-semibold">'.esc($row['cliente_nome']).'</div>',
                esc($equipamento),
                '<span class="text-truncate d-inline-block" style="max-width: 150px;">'.esc($row['relato_cliente']).'</span>',
                $dataAbertura,
                $statusBadge,
                $valorFormatado,
                $acoes
            ];
        });
    }

    public function create()
    {
        $clienteModel    = new ClienteModel();
        $funcionarioModel = new FuncionarioModel();
        $tipoModel       = new \App\Models\EquipamentoTipoModel();
        $marcaModel      = new \App\Models\EquipamentoMarcaModel();

        $data = [
            'title'    => 'Nova Ordem de Serviço',
            'clientes' => $clienteModel->orderBy('nome_razao', 'ASC')->findAll(),
            'tecnicos' => $funcionarioModel->getTecnicos(),
            'tipos'    => $tipoModel->orderBy('nome', 'ASC')->findAll(),
            'marcas'   => $marcaModel->orderBy('nome', 'ASC')->findAll(),
        ];
        return view('os/form', $data);
    }

    public function store()
    {
        $rules = [
            'cliente_id'     => 'required|integer',
            'equipamento_id' => 'required|integer',
            'relato_cliente' => 'required|min_length[10]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        $dados['numero_os']    = $this->model->generateNumeroOs();
        $dados['data_abertura'] = date('Y-m-d H:i:s');
        $dados['status']        = $dados['status'] ?? 'aguardando_analise';

        $this->model->insert($dados);
        $osId = $this->model->getInsertID();

        // Salva defeitos selecionados
        $defeitoIds = $this->request->getPost('defeitos') ?? [];
        if (!empty($defeitoIds)) {
            $defeitoModel = new DefeitoModel();
            $defeitoModel->saveOsDefeitos($osId, $defeitoIds);
        }

        // Salva fotos de estado do equipamento na abertura
        if ($files = $this->request->getFiles()) {
            if (!empty($files['fotos_entrada'])) {
                $fotoOsModel = new \App\Models\OsFotoModel();
                $osNumero = $dados['numero_os'];
                $slug = strtolower(url_title($osNumero, '_', true));

                foreach ($files['fotos_entrada'] as $index => $img) {
                    if ($img->isValid() && !$img->hasMoved()) {
                        $ext = $img->getExtension();
                        $newName = $slug . '_entrada_' . ($index + 1) . '_' . time() . '.' . $ext;
                        $img->move(FCPATH . 'uploads/os_anormalidades', $newName);
                        
                        $fotoOsModel->insert([
                            'os_id'    => $osId,
                            'tipo'     => 'recepcao',
                            'arquivo'  => $newName,
                        ]);
                    }
                }
            }
        }

        $this->persistAccessoryData($osId, $dados['numero_os']);

        LogModel::registrar('os_criada', 'OS criada: ' . $dados['numero_os']);

        return redirect()->to('/os/visualizar/' . $osId)
            ->with('success', 'Ordem de Serviço ' . $dados['numero_os'] . ' criada com sucesso!');
    }

    public function show($id)
    {
        $os = $this->model->getComplete($id);
        if (!$os) {
            return redirect()->to('/os')
                ->with('error', 'OS não encontrada.');
        }

        $itemModel = new OsItemModel();
        $defeitoModel = new \App\Models\DefeitoModel();
        $procedimentoModel = new \App\Models\EquipamentoDefeitoProcedimentoModel();

        $defeitos = $defeitoModel->getByOs($id);
        foreach ($defeitos as &$defeito) {
            $defeito['procedimentos'] = $procedimentoModel->getByDefeito($defeito['defeito_id']);
        }

        $acessorioModel = new AcessorioOsModel();
        $fotoAcessorioModel = new FotoAcessorioModel();
        $acessoriosFolder = 'uploads/acessorios/OS_' . $this->normalizeOsSlug($os['numero_os']) . '/';
        $acessorios = $acessorioModel->where('os_id', $id)->orderBy('id', 'ASC')->findAll();
        foreach ($acessorios as &$acessorio) {
            $fotos = $fotoAcessorioModel->where('acessorio_id', $acessorio['id'])->findAll();
            foreach ($fotos as &$foto) {
                $fotoPath = FCPATH . $acessoriosFolder . $foto['arquivo'];
                if (!file_exists($fotoPath)) {
                    $foto = null;
                    continue;
                }
                $foto['url'] = base_url($acessoriosFolder . $foto['arquivo']);
            }
            $acessorio['fotos'] = array_values(array_filter($fotos));
        }

        // Fotos do Equipamento e da OS
        $fotoEquipModel = new EquipamentoFotoModel();
        $fotoOsModel = new OsFotoModel();

        $fotos_equip = $fotoEquipModel->where('equipamento_id', $os['equipamento_id'])->findAll();
        foreach ($fotos_equip as &$f) {
            $pathPerfil = FCPATH . 'uploads/equipamentos_perfil/' . $f['arquivo'];
            $f['url'] = file_exists($pathPerfil) 
                ? base_url('uploads/equipamentos_perfil/' . $f['arquivo']) 
                : base_url('uploads/equipamentos/' . $f['arquivo']);
        }

        $fotos_entrada = $fotoOsModel->where('os_id', $id)->where('tipo', 'recepcao')->findAll();
        foreach ($fotos_entrada as &$f) {
            $pathAnormal = FCPATH . 'uploads/os_anormalidades/' . $f['arquivo'];
            $f['url'] = file_exists($pathAnormal) 
                ? base_url('uploads/os_anormalidades/' . $f['arquivo']) 
                : base_url('uploads/os/' . $f['arquivo']);
        }

        $data = [
            'title'          => 'OS ' . $os['numero_os'],
            'os'             => $os,
            'itens'          => $itemModel->getByOs($id),
            'defeitos'       => $defeitos,
            'fotos_equip'    => $fotos_equip,
            'fotos_entrada'  => $fotos_entrada,
            'acessorios'     => $acessorios,
            'acessorios_folder' => $acessoriosFolder,
        ];
        return view('os/show', $data);
    }

    public function edit($id)
    {
        $os = $this->model->getComplete($id);
        if (!$os) {
            return redirect()->to('/os')
                ->with('error', 'OS não encontrada.');
        }

        $clienteModel = new ClienteModel();
        $equipamentoModel = new EquipamentoModel();
        $funcionarioModel = new FuncionarioModel();
        $itemModel = new OsItemModel();

        // Fotos da OS (entrada)
        $fotoOsModel = new OsFotoModel();
        $fotos_entrada = $fotoOsModel->where('os_id', $id)->where('tipo', 'recepcao')->findAll();
        foreach ($fotos_entrada as &$f) {
            $pathAnormal = FCPATH . 'uploads/os_anormalidades/' . $f['arquivo'];
            $f['url'] = file_exists($pathAnormal) 
                ? base_url('uploads/os_anormalidades/' . $f['arquivo']) 
                : base_url('uploads/os/' . $f['arquivo']);
        }

        $data = [
            'title'        => 'Editar OS ' . $os['numero_os'],
            'os'           => $os,
            'clientes'     => $clienteModel->orderBy('nome_razao', 'ASC')->findAll(),
            'equipamentos' => $equipamentoModel->getByCliente($os['cliente_id']),
            'tecnicos'     => $funcionarioModel->getTecnicos(),
            'itens'        => $itemModel->getByOs($id),
            'defeitosSelected' => (new DefeitoModel())->getByOs($id),
            'fotos_entrada'    => $fotos_entrada,
        ];
        return view('os/form', $data);
    }

    public function update($id)
    {
        $dados = $this->request->getPost();
        
        // Calculate totals
        if (isset($dados['valor_mao_obra']) || isset($dados['valor_pecas'])) {
            $maoObra = (float)($dados['valor_mao_obra'] ?? 0);
            $pecas = (float)($dados['valor_pecas'] ?? 0);
            $desconto = (float)($dados['desconto'] ?? 0);
            $dados['valor_total'] = $maoObra + $pecas;
            $dados['valor_final'] = $dados['valor_total'] - $desconto;
        }

        $this->model->update($id, $dados);
        
        // Salva novas fotos de estado do equipamento
        if ($files = $this->request->getFiles()) {
            if (!empty($files['fotos_entrada'])) {
                $fotoOsModel = new \App\Models\OsFotoModel();
                $osObj = $this->model->find($id);
                $slug = strtolower(url_title($osObj['numero_os'], '_', true));

                foreach ($files['fotos_entrada'] as $index => $img) {
                    if ($img && $img->isValid() && !$img->hasMoved()) {
                        $ext = $img->getExtension();
                        $newName = $slug . '_edit_' . ($index + 1) . '_' . time() . '.' . $ext;
                        $img->move(FCPATH . 'uploads/os_anormalidades', $newName);
                        
                        $fotoOsModel->insert([
                            'os_id'    => $id,
                            'tipo'     => 'recepcao',
                            'arquivo'  => $newName,
                        ]);
                    }
                }
            }
        }

        // Salva defeitos selecionados
        $defeitoIds = $this->request->getPost('defeitos') ?? [];
        $defeitoModel = new DefeitoModel();
        $defeitoModel->saveOsDefeitos($id, $defeitoIds);

        $osRecord = $this->model->find($id);
        if ($osRecord) {
            $this->persistAccessoryData($id, $osRecord['numero_os'], true);
        }

        LogModel::registrar('os_atualizada', 'OS atualizada ID: ' . $id);

        return redirect()->to('/os/visualizar/' . $id)
            ->with('success', 'OS atualizada com sucesso!');
    }

    public function updateStatus($id)
    {
        $status = $this->request->getPost('status');
        $os = $this->model->find($id);

        if (!$os) {
            return $this->response->setJSON(['error' => 'OS não encontrada']);
        }

        $updateData = ['status' => $status];

        // Set dates based on status
        if ($status === 'pronto') {
            $updateData['data_conclusao'] = date('Y-m-d H:i:s');
            $updateData['garantia_validade'] = date('Y-m-d', strtotime('+' . ($os['garantia_dias'] ?? 90) . ' days'));
        } elseif ($status === 'entregue') {
            $updateData['data_entrega'] = date('Y-m-d H:i:s');
            
            // Create financial entry
            if ($os['valor_final'] > 0) {
                $finModel = new FinanceiroModel();
                $finModel->insert([
                    'os_id'           => $id,
                    'tipo'            => 'receber',
                    'categoria'       => 'Serviço',
                    'descricao'       => 'OS ' . $os['numero_os'],
                    'valor'           => $os['valor_final'],
                    'status'          => 'pendente',
                    'data_vencimento' => date('Y-m-d'),
                ]);
            }
        } elseif ($status === 'aprovado') {
            $updateData['orcamento_aprovado'] = 1;
            $updateData['data_aprovacao'] = date('Y-m-d H:i:s');
        }

        $this->model->update($id, $updateData);

        LogModel::registrar('os_status', 'Status da OS ' . $os['numero_os'] . ' alterado para: ' . $status);

        return redirect()->to('/os/visualizar/' . $id)
            ->with('success', 'Status atualizado com sucesso!');
    }

    public function addItem()
    {
        $itemModel = new OsItemModel();
        $dados = $this->request->getPost();
        
        $dados['valor_total'] = $dados['quantidade'] * $dados['valor_unitario'];
        $itemModel->insert($dados);

        // If it's a part, update stock
        if ($dados['tipo'] === 'peca' && !empty($dados['peca_id'])) {
            $pecaModel = new PecaModel();
            $peca = $pecaModel->find($dados['peca_id']);
            if ($peca) {
                $pecaModel->update($dados['peca_id'], [
                    'quantidade_atual' => $peca['quantidade_atual'] - $dados['quantidade']
                ]);
                
                // Register movement
                $movModel = new MovimentacaoModel();
                $movModel->insert([
                    'peca_id'        => $dados['peca_id'],
                    'os_id'          => $dados['os_id'],
                    'tipo'           => 'saida',
                    'quantidade'     => $dados['quantidade'],
                    'motivo'         => 'Consumo em OS',
                    'responsavel_id' => session()->get('user_id'),
                ]);
            }
        }

        // Update OS totals
        $this->recalcularTotaisOs($dados['os_id']);

        return redirect()->to('/os/visualizar/' . $dados['os_id'])
            ->with('success', 'Item adicionado com sucesso!');
    }

    public function removeItem($id)
    {
        $itemModel = new OsItemModel();
        $item = $itemModel->find($id);
        
        if ($item) {
            $osId = $item['os_id'];
            
            // Reverse stock if it's a part
            if ($item['tipo'] === 'peca' && !empty($item['peca_id'])) {
                $pecaModel = new PecaModel();
                $peca = $pecaModel->find($item['peca_id']);
                if ($peca) {
                    $pecaModel->update($item['peca_id'], [
                        'quantidade_atual' => $peca['quantidade_atual'] + $item['quantidade']
                    ]);
                }
            }
            
            $itemModel->delete($id);
            $this->recalcularTotaisOs($osId);

            return redirect()->to('/os/visualizar/' . $osId)
                ->with('success', 'Item removido com sucesso!');
        }

        return redirect()->back()->with('error', 'Item não encontrado.');
    }

    private function recalcularTotaisOs($osId)
    {
        $itemModel = new OsItemModel();
        $db = \Config\Database::connect();

        $servicos = $db->table('os_itens')
            ->selectSum('valor_total')
            ->where('os_id', $osId)
            ->where('tipo', 'servico')
            ->get()->getRow()->valor_total ?? 0;

        $pecas = $db->table('os_itens')
            ->selectSum('valor_total')
            ->where('os_id', $osId)
            ->where('tipo', 'peca')
            ->get()->getRow()->valor_total ?? 0;

        $os = $this->model->find($osId);
        $desconto = $os['desconto'] ?? 0;
        $total = (float)$servicos + (float)$pecas;

        $this->model->update($osId, [
            'valor_mao_obra' => (float)$servicos,
            'valor_pecas'    => (float)$pecas,
            'valor_total'    => $total,
            'valor_final'    => $total - $desconto,
        ]);
    }

    private function persistAccessoryData(int $osId, string $numeroOs, bool $replaceExisting = false): void
    {
        $entries = $this->getAccessoryEntries();
        $filesMap = $this->collectAccessoryFiles();

        if ($replaceExisting) {
            (new AcessorioOsModel())->deleteByOs($osId);
            $this->clearAccessoryFolder($numeroOs);
        }

        if (empty($entries) && empty($filesMap)) {
            return;
        }

        $acessorioModel = new AcessorioOsModel();
        $fotoModel = new FotoAcessorioModel();
        $slug = $this->normalizeOsSlug($numeroOs);
        $folder = $this->ensureAccessoryDirectory($slug);
        $sequence = 1;

        foreach ($entries as $entry) {
            $description = trim($entry['text'] ?? '');
            if ($description === '') {
                continue;
            }

            $acessorioModel->insert([
                'os_id' => $osId,
                'descricao' => $description,
                'tipo' => $entry['key'] ?? null,
                'valores' => !empty($entry['values']) ? json_encode($entry['values'], JSON_UNESCAPED_UNICODE) : null,
            ]);

            $acessorioId = $acessorioModel->getInsertID();
            if (!$acessorioId) {
                continue;
            }

            $entryFiles = $filesMap[$entry['id']] ?? [];
            foreach ($entryFiles as $file) {
                $this->saveAccessoryPhoto($file, $folder, $slug, $sequence, $acessorioId, $fotoModel);
            }
        }
    }

    private function getAccessoryEntries(): array
    {
        $raw = $this->request->getPost('acessorios_data');
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, function ($entry) {
            return !empty(trim($entry['text'] ?? ''));
        }));
    }

    private function collectAccessoryFiles(): array
    {
        $mapped = [];
        if (empty($_FILES['fotos_acessorios']['name'] ?? null)) {
            return $mapped;
        }

        foreach ($_FILES['fotos_acessorios']['name'] as $entryId => $files) {
            foreach ($files as $index => $name) {
                $error = $_FILES['fotos_acessorios']['error'][$entryId][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK) {
                    continue;
                }

                $tmpName = $_FILES['fotos_acessorios']['tmp_name'][$entryId][$index];
                if (!is_uploaded_file($tmpName)) {
                    continue;
                }

                $mapped[$entryId][] = [
                    'name'     => $name,
                    'tmp_name' => $tmpName,
                ];
            }
        }

        return $mapped;
    }

    private function normalizeOsSlug(string $numeroOs): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_]/', '', str_replace('-', '_', $numeroOs));
        $clean = preg_replace('/^OS_?/i', '', $clean);
        return $clean ?: 'os';
    }

    private function ensureAccessoryDirectory(string $slug): string
    {
        $base = FCPATH . 'uploads/acessorios/';
        if (!is_dir($base)) {
            mkdir($base, 0755, true);
        }

        $path = $base . 'OS_' . $slug . '/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    private function clearAccessoryFolder(string $numeroOs): void
    {
        $slug = $this->normalizeOsSlug($numeroOs);
        $path = FCPATH . 'uploads/acessorios/OS_' . $slug . '/';
        if (!is_dir($path)) {
            return;
        }

        foreach (glob($path . '*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function saveAccessoryPhoto(array $file, string $folder, string $slug, int &$sequence, int $acessorioId, FotoAcessorioModel $fotoModel): void
    {
        try {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $name = "acessorio_{$slug}_{$sequence}";
            if ($extension) {
                $name .= '.' . $extension;
            }

            $destination = $folder . $name;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new \RuntimeException('Falha ao mover upload');
            }

            $fotoModel->insert([
                'acessorio_id' => $acessorioId,
                'arquivo' => $name,
            ]);
            $sequence++;
        } catch (\Throwable $e) {
            log_message('warning', 'Erro ao salvar foto de acessório: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $os = $this->model->getComplete($id);
        if (!$os) {
            return redirect()->back()->with('error', 'OS não encontrada.');
        }

        $itemModel = new OsItemModel();
        $defeitoModel = new \App\Models\DefeitoModel();
        $procedimentoModel = new \App\Models\EquipamentoDefeitoProcedimentoModel();

        $defeitos = $defeitoModel->getByOs($id);
        foreach ($defeitos as &$defeito) {
            $defeito['procedimentos'] = $procedimentoModel->getByDefeito($defeito['defeito_id']);
        }

        $data = [
            'os'       => $os,
            'itens'    => $itemModel->getByOs($id),
            'defeitos' => $defeitos
        ];
        return view('os/print', $data);
    }
}
