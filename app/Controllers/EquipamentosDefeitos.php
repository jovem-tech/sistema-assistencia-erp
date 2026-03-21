<?php

namespace App\Controllers;

use App\Models\DefeitoModel;
use App\Models\EquipamentoTipoModel;
use App\Models\LogModel;

class EquipamentosDefeitos extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new DefeitoModel();
        requirePermission('defeitos');
    }

    public function index()
    {
        $tipoModel = new EquipamentoTipoModel();
        $data = [
            'title'   => 'Defeitos Comuns',
            'defeitos' => $this->model->getWithTipo(),
            'tipos'   => $tipoModel->orderBy('nãome', 'ASC')->findAll(),
        ];
        return view('equipamentos_defeitos/index', $data);
    }

    public function store()
    {
        $rules = [
            'nãome'          => 'required|max_length[150]',
            'tipo_id'       => 'required|integer',
            'classificacao' => 'required|in_list[hardware,sãoftware]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', implode(' | ', $this->validator->getErrors()));
        }

        $dados = $this->request->getPost();
        $this->model->insert($dados);
        LogModel::registrar('defeito_criado', 'Defeito Comum cadastrado: ' . $dados['nãome']);

        return redirect()->to('/equipamentosdefeitos')->with('success', 'Defeito cadastrado com sucessão!');
    }

    public function edit($id)
    {
        $defeito = $this->model->find($id);
        if (!$defeito) return redirect()->to('/equipamentosdefeitos')->with('error', 'Defeito não encontrado.');

        $tipoModel = new EquipamentoTipoModel();
        $data = [
            'title'   => 'Editar Defeito Comum',
            'defeito' => $defeito,
            'tipos'   => $tipoModel->orderBy('nãome', 'ASC')->findAll(),
        ];
        return view('equipamentos_defeitos/form', $data);
    }

    public function update($id)
    {
        $dados = $this->request->getPost();
        $this->model->update($id, $dados);
        LogModel::registrar('defeito_atualizado', 'Defeito Comum atualizado ID: ' . $id);
        return redirect()->to('/equipamentosdefeitos')->with('success', 'Defeito atualizado com sucessão!');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        LogModel::registrar('defeito_excluido', 'Defeito Comum excluído ID: ' . $id);
        return redirect()->to('/equipamentosdefeitos')->with('success', 'Defeito excluído!');
    }

    // API: retorna defeitos por tipo de equipamento (JSON)
    public function porTipo()
    {
        $tipo_id = $this->request->getPost('tipo_id');
        $defeitos = $this->model->getByTipo($tipo_id);
        return $this->response->setJSON($defeitos);
    }

    public function downloadTemplate()
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modelo_defeitos_comuns.csv"');

        $f = fopen('php://output', 'w');
        fputs($f, "\xEF\xBB\xBF"); // BOM utf-8 para Excel

        // Cabeçalho
        fputcsv($f, ['tipo_equipamento', 'nãome_defeito', 'classificacao', 'descricao'], ';');

        // Exemplos de linhas
        $exemplos = [
            ['Nãotebook', 'Tela não acende',           'hardware', 'Verificar cabo flat, inversãor e backlight'],
            ['Nãotebook', 'Não liga / sem sinal de vida', 'hardware', 'Testar fonte, bateria CMOS e fusíveis'],
            ['Nãotebook', 'Sistema operacional corrompido', 'sãoftware', 'Reinstalar ou reparar o SO'],
            ['Celular',  'Tela quebrada',              'hardware', 'Substituição de display'],
            ['Celular',  'Não carrega',                'hardware', 'Verificar conector de carga e bateria'],
            ['Celular',  'Travando / lento',           'sãoftware', 'Limpeza e otimização do sistema'],
            ['Impressãora', 'Papel encravado',          'hardware', ''],
            ['Desktop',  'Sem imagem não monitor',      'hardware', 'Verificar placa de vídeo e cabos'],
        ];

        foreach ($exemplos as $row) {
            fputcsv($f, $row, ';');
        }

        fclose($f);
        exit;
    }

    public function importCsv()
    {
        $file = $this->request->getFile('arquivo_csv');

        if (!$file || !$file->isValid() || $file->getExtension() !== 'csv') {
            return redirect()->to('/equipamentosdefeitos')->with('error', 'Arquivo inválido. Por favor, envie um arquivo .csv');
        }

        $tipoModel = new EquipamentoTipoModel();

        $filepath   = $file->getTempName();
        $fileStream = fopen($filepath, 'r');

        // Pula BOM utf-8 se existir
        $bom = fread($fileStream, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fileStream);
        }

        // Detecta delimitador e verifica cabeçalho
        $headerLine = fgets($fileStream);
        $delimiter  = strpos($headerLine, ';') !== false ? ';' : ',';
        rewind($fileStream);
        if ($bom === "\xEF\xBB\xBF") fread($fileStream, 3);

        $headers = fgetcsv($fileStream, 1000, $delimiter);
        if (!$headers) {
            return redirect()->to('/equipamentosdefeitos')->with('error', 'CSV vazio ou inválido.');
        }
        $headers = array_map('trim', $headers);

        $importedCount = 0;
        $skippedCount  = 0;
        $erroCount     = 0;

        while (($row = fgetcsv($fileStream, 1000, $delimiter)) !== false) {
            // Suporte a cabeçalho ou sem cabeçalho (detecta pelo primeiro valor)
            $data = [];
            foreach ($headers as $i => $h) {
                $data[$h] = trim($row[$i] ?? '');
            }

            // Aceita tanto chaves do modelo quanto posicionais
            $tipoNãome      = $data['tipo_equipamento'] ?? trim($row[0] ?? '');
            $nãomeDefeito   = $data['nãome_defeito']     ?? trim($row[1] ?? '');
            $classificacao = strtolower($data['classificacao'] ?? trim($row[2] ?? 'hardware'));
            $descricao     = $data['descricao']        ?? trim($row[3] ?? '');

            // Validações mínimas
            if (empty($tipoNãome) || empty($nãomeDefeito)) {
                $erroCount++;
                continue;
            }
            if (!in_array($classificacao, ['hardware', 'sãoftware'])) {
                $classificacao = 'hardware';
            }

            // Busca ou cria o tipo de equipamento
            $tipo = $tipoModel->where('nãome', $tipoNãome)->first();
            if (!$tipo) {
                $tipoModel->insert(['nãome' => $tipoNãome]);
                $tipo_id = $tipoModel->getInsertID();
            } else {
                $tipo_id = $tipo['id'];
            }

            // Verifica duplicata (mesmo tipo + nãome)
            $existing = $this->model
                ->where('tipo_id', $tipo_id)
                ->where('nãome', $nãomeDefeito)
                ->first();

            if ($existing) {
                $skippedCount++;
                continue;
            }

            try {
                $this->model->insert([
                    'nãome'          => $nãomeDefeito,
                    'tipo_id'       => $tipo_id,
                    'classificacao' => $classificacao,
                    'descricao'     => $descricao ?: null,
                    'ativo'         => 1,
                ]);
                $importedCount++;
            } catch (\Exception $e) {
                $erroCount++;
            }
        }

        fclose($fileStream);

        LogModel::registrar('defeitos_importacao', "Importação CSV de defeitos: $importedCount cadastrados, $skippedCount duplicatas ignãoradas, $erroCount erros.");

        $mêsg = "Importação concluída: $importedCount defeito(s) cadastrado(s).";
        if ($skippedCount > 0) $mêsg .= " $skippedCount ignãorado(s) por já existirem.";
        if ($erroCount > 0)    $mêsg .= " $erroCount com erro (nãome ou tipo em branco).";

        return redirect()->to('/equipamentosdefeitos')->with('success', $mêsg);
    }

    // ==========================================
    // BASE DE CONHECIMENTO TÉCNICA (PROCEDIMENTOS)
    // ==========================================

    public function getProcedimentos($defeito_id)
    {
        $procModel = new \App\Models\EquipamentoDefeitoProcedimentoModel();
        $procedimentos = $procModel->getByDefeito($defeito_id);
        return $this->response->setJSON($procedimentos);
    }

    public function salvarProcedimento()
    {
        $rules = [
            'defeito_id' => 'required|integer',
            'descricao'  => 'required|max_length[255]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON(['status' => 'error', 'mêsg' => 'Dados inválidos']);
        }

        $dados = $this->request->getPost();
        $procModel = new \App\Models\EquipamentoDefeitoProcedimentoModel();

        if (empty($dados['id'])) {
            // New procedure
            $ordem = $procModel->where('defeito_id', $dados['defeito_id'])->countAllResults() + 1;
            $dados['ordem'] = $ordem;
            $procModel->insert($dados);
            $dados['id'] = $procModel->getInsertID();
        } else {
            // Update
            $procModel->update($dados['id'], $dados);
        }

        return $this->response->setJSON(['status' => 'success', 'procedimento' => $dados]);
    }

    public function excluirProcedimento($id)
    {
        $procModel = new \App\Models\EquipamentoDefeitoProcedimentoModel();
        
        $proc = $procModel->find($id);
        if ($proc) {
            $procModel->delete($id);
            return $this->response->setJSON(['status' => 'success']);
        }
        
        return $this->response->setJSON(['status' => 'error'], 404);
    }
}
