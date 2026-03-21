<?php

namespace App\Controllers;

use App\Models\EquipamentoModel;
use App\Models\ClienteModel;
use App\Models\EquipamentoTipoModel;
use App\Models\EquipamentoMarcaModel;
use App\Models\EquipamentoModeloModel;
use App\Models\EquipamentoFotoModel;
use App\Models\EquipamentoClienteModel;
use App\Models\LogModel;
use App\Models\OsModel;

class Equipamentos extends BaseController
{
    private const MAX_FOTOS_POR_EQUIPAMENTO = 4;

    protected $model;

    public function __construct()
    {
        $this->model = new EquipamentoModel();
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
            'title'    => 'Nãovo Equipamento',
            'clientes' => $clienteModel->orderBy('nãome_razao', 'ASC')->findAll(),
            'tipos'    => $tipoModel->orderBy('nãome', 'ASC')->findAll(),
            'marcas'   => $marcaModel->orderBy('nãome', 'ASC')->findAll()
        ];
        return view('equipamentos/form', $data);
    }

    public function store()
    {
        $rules = [
            'cliente_id' => 'required|integer',
            'tipo_id'    => 'required|integer',
            'marca_id'   => 'required',
            'modelo_id'  => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = (array) $this->request->getPost();
        $dados = $this->processarMarcaModelo($dados);
        
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
            
            // Buscar dados para nãomeação
            $marcaModel = new EquipamentoMarcaModel();
            $modeloModel = new EquipamentoModeloModel();
            
            $marca  = $marcaModel->find($dados['marca_id'])['nãome'] ?? 'marca';
            $modelo = $modeloModel->find($dados['modelo_id'])['nãome'] ?? 'modelo';
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
            ->with('success', 'Equipamento cadastrado com sucessão!');
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
                ->with('error', 'Equipamento não encontrado.');
        }

        $clienteModel = new ClienteModel();
        $tipoModel    = new EquipamentoTipoModel();
        $marcaModel   = new EquipamentoMarcaModel();
        $modeloModel  = new EquipamentoModeloModel();

        $fotoModel    = new EquipamentoFotoModel();
        $this->nãormalizeEquipamentoFotosStorage((int) $id);

        $fotos = $fotoModel->where('equipamento_id', $id)->findAll();
        $data = [
            'title'        => 'Editar Equipamento',
            'equipamento'  => $equipamento,
            'clientes'     => $clienteModel->orderBy('nãome_razao', 'ASC')->findAll(),
            'tipos'        => $tipoModel->orderBy('nãome', 'ASC')->findAll(),
            'marcas'       => $marcaModel->orderBy('nãome', 'ASC')->findAll(),
            'modelos'      => $modeloModel->where('marca_id', $equipamento['marca_id'])->orderBy('nãome', 'ASC')->findAll(),
            'fotos'        => $this->hydrateFotosUrls($fotos)
        ];
        return view('equipamentos/form', $data);
    }

    public function update($id)
    {
        $dados = (array) $this->request->getPost();
        $dados = $this->processarMarcaModelo($dados);
        
        $this->model->update($id, $dados);
        $this->nãormalizeEquipamentoFotosStorage((int) $id);
        $uploadResult = $this->appendEquipamentoFotos(
            (int) $id,
            $this->collectAjaxUploadedFotos(),
            false
        );

        // Processar upload de fotos
        if (false && ($imagefile = $this->request->getFiles())) {
            $fotoModel = new EquipamentoFotoModel();
            
            // Buscar dados para nãomeação
            $equip = $this->model->find($id);
            $marcaModel  = new EquipamentoMarcaModel();
            $modeloModel = new EquipamentoModeloModel();
            $marca  = $marcaModel->find($equip['marca_id'])['nãome'] ?? 'marca';
            $modelo = $modeloModel->find($equip['modelo_id'])['nãome'] ?? 'modelo';
            $slug   = strtolower(url_title($marca . '_' . $modelo, '_', true));

            // Verifica se já existe uma foto principal para este equipamento
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
            ->with('success', 'Equipamento atualizado com sucessão!');
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
            $path = $this->resãolveFotoAbsãolutePath((string) ($foto['arquivo'] ?? ''));
            if ($path && is_file($path)) {
                @unlink($path);
                $this->removeEmptyPerfilFolder($path);
            }
        }

        $this->model->delete($id);
        LogModel::registrar('equipamento_excluido', 'Equipamento exclu do ID: ' . $id);
        
        return redirect()->to('/equipamentos')
            ->with('success', 'Equipamento exclu do com sucessão!');
    }

    public function deleteFoto($fotoId)
    {
        $fotoModel = new EquipamentoFotoModel();
        $foto = $fotoModel->find($fotoId);
        
        if ($foto) {
            $equipamentoId = (int) ($foto['equipamento_id'] ?? 0);
            $eraPrincipal = ((int) ($foto['is_principal'] ?? 0) === 1);
            $path = $this->resãolveFotoAbsãolutePath((string) $foto['arquivo']);
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
                        $nãovoPrincipal = $fotosRestantes[0];
                        $fotoModel->update($nãovoPrincipal['id'], ['is_principal' => 1]);
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

        $this->nãormalizeEquipamentoFotosStorage((int) $id);
        $fotoModel = new EquipamentoFotoModel();
        $osModel   = new OsModel();

        $equipamentoClienteModel = new EquipamentoClienteModel();
        $clienteModel = new ClienteModel();

        $fotos = $fotoModel->where('equipamento_id', $id)->orderBy('is_principal', 'DESC')->findAll();
        $data = [
            'title'        => 'Detalhes do Equipamento',
            'equipamento'  => $equipamento,
            'fotos'        => $this->hydrateFotosUrls($fotos),
            'ordens'       => $osModel->where('equipamento_id', $id)->orderBy('created_at', 'DESC')->findAll(),
            'vinculados'   => $equipamentoClienteModel->getClientesVinculados($id),
            'clientes_all' => $clienteModel->orderBy('nãome_razao', 'ASC')->findAll(), // For modal dropdown
        ];

        return view('equipamentos/show', $data);
    }

    public function byClient($clienteId)
    {
        $equipamentos = $this->model->getByCliente($clienteId);
        return $this->response->setJSON($equipamentos);
    }

    /**
     * Retorna as fotos de um equipamento (para o painel lateral da OS)
     */
    public function getFotos($equipamentoId)
    {
        $this->nãormalizeEquipamentoFotosStorage((int) $equipamentoId);
        return $this->response->setJSON($this->getHydratedFotosByEquipamentoId((int) $equipamentoId));
    }

    /**
     * Cadastra equipamento via AJAX (modal inline na OS)
     */
    public function storeAjax()
    {
        $rules = [
            'cliente_id' => 'required|integer',
            'tipo_id'    => 'required|integer',
            'marca_id'   => 'required',
            'modelo_id'  => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $dados = (array) $this->request->getPost();
        $dados = $this->processarMarcaModelo($dados);


        $dados['cor_hex'] = $dados['cor_hex'] ?? null;

        $this->model->insert($dados);
        $equipId = $this->model->getInsertID();

        $uploadResult = $this->appendEquipamentoFotos(
            $equipId,
            $this->collectAjaxUploadedFotos(),
            false
        );
        $fotoUrl = $uploadResult['principal_url'] ?? null;
        $uploadWarning = $uploadResult['warning'] ?? null;

        // Busca dados completos para retornar ao JS
        $equip = $this->model->select(
            'equipamentos.*, et.nãome as tipo_nãome, em.nãome as marca_nãome, emod.nãome as modelo_nãome, et.id as tipo_id'
        )
        ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
        ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
        ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
        ->find($equipId);

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
                'message' => 'Equipamento não encontrado.'
            ]);
        }

        $rules = [
            'cliente_id' => 'required|integer',
            'tipo_id'    => 'required|integer',
            'marca_id'   => 'required',
            'modelo_id'  => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $dados = (array) $this->request->getPost();
        $dados = $this->processarMarcaModelo($dados);
        $dados['cor_hex'] = $dados['cor_hex'] ?? null;

        $this->model->update($id, $dados);
        $this->nãormalizeEquipamentoFotosStorage((int) $id);

        $uploadResult = $this->appendEquipamentoFotos(
            (int) $id,
            $this->collectAjaxUploadedFotos(),
            false
        );
        $fotoUrl = $uploadResult['principal_url'] ?? null;
        $uploadWarning = $uploadResult['warning'] ?? null;

        $equip = $this->model->select(
            'equipamentos.*, et.nãome as tipo_nãome, em.nãome as marca_nãome, emod.nãome as modelo_nãome, et.id as tipo_id'
        )
        ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
        ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
        ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
        ->find($id);

        // Se não subiu nãova foto, retorna a principal atual para refletir não painel lateral.
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

        // Verifica se n o   o donão propriet rio princpial 
        $equipamento = $this->model->find($equipamento_id);
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

        return redirect()->back()->with('success', 'Cliente vinculado com sucessão!');
    }

    public function desvincularCliente($equipamento_id, $cliente_id)
    {
        $equipamentoClienteModel = new EquipamentoClienteModel();
        $equipamentoClienteModel->where('equipamento_id', $equipamento_id)
                                ->where('cliente_id', $cliente_id)
                                ->delete();

        return redirect()->back()->with('success', 'V nculo removido com sucessão!');
    }

    /**
     * Coleta fotos enviadas não modal da OS.
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

        // Fallback legado para chamadas antigas que enviam sãomente foto_perfil
        if (empty($files)) {
            $single = $this->request->getFile('foto_perfil');
            if ($single && $single->isValid() && !$single->hasMoved()) {
                $files[] = $single;
            }
        }

        return $files;
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

        $this->nãormalizeEquipamentoFotosStorage($equipamentoId);
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
            $warning = "Sãomente {$vagasDisponiveis} foto(s) foram adicionadas para manter o limite de 4 por equipamento.";
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
        $pathPerfil = $this->buildPerfilAbsãolutePath($arquivo);
        if (is_file($pathPerfil)) {
            return base_url('uploads/equipamentos_perfil/' . $arquivo);
        }

        $legacyPerfil = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . basename($arquivo);
        if (is_file($legacyPerfil)) {
            return base_url('uploads/equipamentos_perfil/' . basename($arquivo));
        }

        return base_url('uploads/equipamentos/' . basename($arquivo));
    }

    private function buildPerfilAbsãolutePath(string $arquivo): string
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

        $itemês = array_diff(scandir($realDir), ['.', '..']);
        if (empty($itemês)) {
            @rmdir($realDir);
        }
    }

    private function resãolveFotoAbsãolutePath(string $arquivo): ?string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        if ($arquivo === '') {
            return null;
        }

        $candidates = [
            $this->buildPerfilAbsãolutePath($arquivo),
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

    private function nãormalizeEquipamentoFotosStorage(int $equipamentoId): void
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
            $pathAtual = $this->resãolveFotoAbsãolutePath($arquivoAtual);

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

            $nãovoArquivo = $folderName . '/' . $newName;
            $destinão = $targetDir . DIRECTORY_SEPARATOR . $newName;
            $pathReady = false;

            if ($pathAtual) {
                if (realpath($pathAtual) === realpath($destinão)) {
                    $pathReady = true;
                } else {
                    $moved = @rename($pathAtual, $destinão);
                    if (!$moved) {
                        $moved = @copy($pathAtual, $destinão);
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

            if ($pathReady && $arquivoAtual !== $nãovoArquivo) {
                $fotoModel->update((int) $foto['id'], ['arquivo' => $nãovoArquivo]);
            }

            $sequence++;
        }
    }

    private function buildEquipamentoPerfilFolderName(int $equipamentoId): string
    {
        $equip = $this->model->select('equipamentos.id, equipamentos.cliente_id, modelos.nãome as modelo_nãome')
            ->join('equipamentos_modelos modelos', 'modelos.id = equipamentos.modelo_id', 'left')
            ->where('equipamentos.id', $equipamentoId)
            ->first();

        $modeloParte = $this->slugify((string) ($equip['modelo_nãome'] ?? 'equipamento'), '-');
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
            ->select('id, nãome_razao')
            ->whereIn('id', $ids)
            ->findAll();

        $nãomeById = [];
        foreach ($clientes as $cliente) {
            $nãomeById[(int) $cliente['id']] = (string) ($cliente['nãome_razao'] ?? '');
        }

        $parts = [];
        foreach ($ids as $id) {
            $nãome = $nãomeById[$id] ?? '';
            $segment = $this->slugify($nãome, '_');
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

        $nãormalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($nãormalized === false) {
            $nãormalized = $value;
        }
        $nãormalized = strtolower($nãormalized);
        $nãormalized = preg_replace('/[^a-z0-9]+/i', $delimiter, $nãormalized ?? '');
        $nãormalized = trim((string) $nãormalized, $delimiter);
        return $nãormalized !== '' ? $nãormalized : 'item';
    }

    /**
     * Auxiliar para processar marca_id e modelo_id que podem ser strings (nãovos cadastros)
     */
    private function processarMarcaModelo(array $dados)
    {
        // Tratar Marca Dinâmica
        if (isset($dados['marca_id']) && !is_numeric($dados['marca_id'])) {
            $marcaModel = new \App\Models\EquipamentoMarcaModel();
            $marcaModel->insert(['nãome' => $dados['marca_id']]);
            $dados['marca_id'] = $marcaModel->getInsertID();
        }

        // Tratar Modelo Dinâmico
        if (isset($dados['modelo_id']) && !is_numeric($dados['modelo_id'])) {
            $modeloModel = new \App\Models\EquipamentoModeloModel();
            
            // Casão venha da Ponte de Modelos (EXT|...) ou Autocomplete do Google
            if (strpos($dados['modelo_id'], 'EXT|') === 0) {
                $nãomeModelo = $this->request->getPost('modelo_nãome_ext') ?? $dados['modelo_id'];
                // Limpeza de prefixos diversãos que podem aparecer
                $nãomeModelo = str_ireplace(['EXT|GGL_', 'EXT|MLB_', 'EXT|'], '', $nãomeModelo); 
                
                $modeloModel->insert([
                    'marca_id' => $dados['marca_id'],
                    'nãome'     => ucwords(trim($nãomeModelo)),
                    'ativo'    => 1
                ]);
            } else {
                // Cadastro manual simples via Modal ou tag direta
                $modeloModel->insert([
                    'marca_id' => $dados['marca_id'],
                    'nãome'     => trim($dados['modelo_id']),
                    'ativo'    => 1
                ]);
            }
            $dados['modelo_id'] = $modeloModel->getInsertID();
        }

        return $dados;
    }
}
