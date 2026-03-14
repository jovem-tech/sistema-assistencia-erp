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
            'title'    => 'Novo Equipamento',
            'clientes' => $clienteModel->orderBy('nome_razao', 'ASC')->findAll(),
            'tipos'    => $tipoModel->orderBy('nome', 'ASC')->findAll(),
            'marcas'   => $marcaModel->orderBy('nome', 'ASC')->findAll()
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

        // Processar upload de fotos
        if ($imagefile = $this->request->getFiles()) {
            $fotoModel = new EquipamentoFotoModel();
            
            // Buscar dados para nomeação
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

        if ($this->request->getGet('redirect') === 'os') {
            return redirect()->back()->with('success', 'Equipamento cadastrado!');
        }

        return redirect()->to('/equipamentos')
            ->with('success', 'Equipamento cadastrado com sucesso!');
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

        $data = [
            'title'        => 'Editar Equipamento',
            'equipamento'  => $equipamento,
            'clientes'     => $clienteModel->orderBy('nome_razao', 'ASC')->findAll(),
            'tipos'        => $tipoModel->orderBy('nome', 'ASC')->findAll(),
            'marcas'       => $marcaModel->orderBy('nome', 'ASC')->findAll(),
            'modelos'      => $modeloModel->where('marca_id', $equipamento['marca_id'])->orderBy('nome', 'ASC')->findAll(),
            'fotos'        => $fotoModel->where('equipamento_id', $id)->findAll()
        ];
        return view('equipamentos/form', $data);
    }

    public function update($id)
    {
        $dados = (array) $this->request->getPost();
        $dados = $this->processarMarcaModelo($dados);
        
        $this->model->update($id, $dados);

        // Processar upload de fotos
        if ($imagefile = $this->request->getFiles()) {
            $fotoModel = new EquipamentoFotoModel();
            
            // Buscar dados para nomeação
            $equip = $this->model->find($id);
            $marcaModel  = new EquipamentoMarcaModel();
            $modeloModel = new EquipamentoModeloModel();
            $marca  = $marcaModel->find($equip['marca_id'])['nome'] ?? 'marca';
            $modelo = $modeloModel->find($equip['modelo_id'])['nome'] ?? 'modelo';
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

        return redirect()->to('/equipamentos')
            ->with('success', 'Equipamento atualizado com sucesso!');
    }

    public function delete($id)
    {
        // Ao excluir equipamento, as fotos j  devem ser exclu das devido CASCADE do banco,
        // Mas podemos deletar o registro f sico (arquivo.jpg) como melhoria depois.

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
            $path1 = FCPATH . 'uploads/equipamentos_perfil/' . $foto['arquivo'];
            $path2 = FCPATH . 'uploads/equipamentos/' . $foto['arquivo']; // fallback legado
            if (file_exists($path1)) @unlink($path1);
            if (file_exists($path2)) @unlink($path2);
            $fotoModel->delete($fotoId);
            return $this->response->setJSON(['success' => true]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Foto n o encontrada']);
    }

    public function show($id)
    {
        $equipamento = $this->model->getWithCliente($id);
        if (!$equipamento) {
            return redirect()->to('/equipamentos')->with('error', 'Equipamento n o encontrado.');
        }

        $fotoModel = new EquipamentoFotoModel();
        $osModel   = new OsModel();

        $equipamentoClienteModel = new EquipamentoClienteModel();
        $clienteModel = new ClienteModel();

        $data = [
            'title'        => 'Detalhes do Equipamento',
            'equipamento'  => $equipamento,
            'fotos'        => $fotoModel->where('equipamento_id', $id)->orderBy('is_principal', 'DESC')->findAll(),
            'ordens'       => $osModel->where('equipamento_id', $id)->orderBy('created_at', 'DESC')->findAll(),
            'vinculados'   => $equipamentoClienteModel->getClientesVinculados($id),
            'clientes_all' => $clienteModel->orderBy('nome_razao', 'ASC')->findAll(), // For modal dropdown
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
        $fotoModel = new EquipamentoFotoModel();
        $fotos = $fotoModel->where('equipamento_id', $equipamentoId)->orderBy('is_principal', 'DESC')->findAll();

        foreach ($fotos as &$f) {
            if (file_exists(FCPATH . 'uploads/equipamentos_perfil/' . $f['arquivo'])) {
                $f['url'] = base_url('uploads/equipamentos_perfil/' . $f['arquivo']);
            } else {
                $f['url'] = base_url('uploads/equipamentos/' . $f['arquivo']);
            }
        }

        return $this->response->setJSON($fotos);
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

        // Processar upload de foto principal
        $fotoUrl = null;
        if ($imagefile = $this->request->getFiles()) {
            $fotoModel = new EquipamentoFotoModel();
            if (isset($imagefile['foto_perfil'])) {
                $img = $imagefile['foto_perfil'];
                if ($img->isValid() && !$img->hasMoved()) {
                    $marcaModel  = new EquipamentoMarcaModel();
                    $modeloModel = new EquipamentoModeloModel();
                    $marca  = $marcaModel->find($dados['marca_id'])['nome'] ?? 'marca';
                    $modelo = $modeloModel->find($dados['modelo_id'])['nome'] ?? 'modelo';
                    $slug   = strtolower(url_title($marca . '_' . $modelo, '_', true));
                    
                    $ext = $img->getExtension();
                    $newName = $slug . '_perfil_' . time() . '.' . $ext;
                    $img->move(FCPATH . 'uploads/equipamentos_perfil', $newName);
                    
                    $fotoModel->insert([
                        'equipamento_id' => $equipId,
                        'arquivo'        => $newName,
                        'is_principal'   => 1,
                        'created_at'     => date('Y-m-d H:i:s')
                    ]);
                    $fotoUrl = base_url('uploads/equipamentos_perfil/' . $newName);
                }
            }
        }

        // Busca dados completos para retornar ao JS
        $equip = $this->model->select(
            'equipamentos.*, et.nome as tipo_nome, em.nome as marca_nome, emod.nome as modelo_nome, et.id as tipo_id'
        )
        ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
        ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
        ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
        ->find($equipId);

        LogModel::registrar('equipamento_criado', 'Equipamento cadastrado via OS (ID: ' . $equipId . ')');

        return $this->response->setJSON([
            'status'    => 'success',
            'equipamento' => $equip,
            'foto_url'  => $fotoUrl
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

    public function desvincularCliente($equipamento_id, $cliente_id)
    {
        $equipamentoClienteModel = new EquipamentoClienteModel();
        $equipamentoClienteModel->where('equipamento_id', $equipamento_id)
                                ->where('cliente_id', $cliente_id)
                                ->delete();

        return redirect()->back()->with('success', 'V nculo removido com sucesso!');
    }
    /**
     * Auxiliar para processar marca_id e modelo_id que podem ser strings (novos cadastros)
     */
    private function processarMarcaModelo(array $dados)
    {
        // Tratar Marca Dinâmica
        if (isset($dados['marca_id']) && !is_numeric($dados['marca_id'])) {
            $marcaModel = new \App\Models\EquipamentoMarcaModel();
            $marcaModel->insert(['nome' => $dados['marca_id']]);
            $dados['marca_id'] = $marcaModel->getInsertID();
        }

        // Tratar Modelo Dinâmico
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

        return $dados;
    }
}
