<?php

namespace App\Controllers;

use App\Models\ConfiguracaoModel;
use App\Models\LogModel;

class Configuracoes extends BaseController
{
    public function __construct()
    {
        requirePermission('configuracoes');
    }

    public function index()
    {
        $model = new ConfiguracaoModel();
        
        $todasConfiguracoes = $model->findAll();
        $configs = [];
        foreach ($todasConfiguracoes as $c) {
            $configs[$c['chave']] = $c['valor'];
        }
        
        $data = [
            'title' => 'Configurações',
            'configs' => $configs
        ];
        
        return view('configuracoes/index', $data);
    }
    
    public function save()
    {
        $model = new ConfiguracaoModel();
        $posts = $this->request->getPost();
        
        foreach ($posts as $chave => $valor) {
            if ($chave != 'csrf_test_name') {
                $model->setConfig($chave, $valor);
            }
        }
        
        $uploadPath = 'uploads/sistema';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $logo = $this->request->getFile('sistema_logo');
        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            if (in_array($logo->getExtension(), ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                $newName = $logo->getRandomName();
                $logo->move($uploadPath, $newName);
                
                $oldLogo = get_config('sistema_logo');
                if ($oldLogo && file_exists($uploadPath . '/' . $oldLogo)) {
                    unlink($uploadPath . '/' . $oldLogo);
                }
                
                $model->setConfig('sistema_logo', $newName);
            }
        }

        $favicon = $this->request->getFile('sistema_icone');
        if ($favicon && $favicon->isValid() && !$favicon->hasMoved()) {
            if (in_array($favicon->getExtension(), ['jpg', 'jpeg', 'png', 'ico', 'x-icon'])) {
                $newName = $favicon->getRandomName();
                $favicon->move($uploadPath, $newName);
                
                $oldFavicon = get_config('sistema_icone');
                if ($oldFavicon && file_exists($uploadPath . '/' . $oldFavicon)) {
                    unlink($uploadPath . '/' . $oldFavicon);
                }
                
                $model->setConfig('sistema_icone', $newName);
            }
        }
        
        if (class_exists('App\Models\LogModel')) {
            LogModel::registrar('configuracao', 'Configurações do sistema atualizadas');
        }
        
        return redirect()->to('/configuracoes')->with('success', 'Configurações salvas com sucesso!');
    }
}
