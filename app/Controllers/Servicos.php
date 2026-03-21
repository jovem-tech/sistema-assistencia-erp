<?php

namespace App\Controllers;

use App\Models\ServicoModel;
use App\Models\LogModel;

class Servicos extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ServicoModel();
        requirePermission('servicos');
    }

    public function index()
    {
        $data = [
            'title'    => 'Serviços',
            'servicos' => $this->model->orderBy('nãome', 'ASC')->findAll(),
        ];
        return view('servicos/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Nãovo Serviço',
        ];
        return view('servicos/form', $data);
    }

    public function store()
    {
        $rules = [
            'nãome'  => 'required|min_length[3]',
            'valor' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        // Limpar valor para formato decimal (ex: 1.250,50 -> 1250.50)
        $dados['valor'] = str_replace(',', '.', str_replace('.', '', $dados['valor'] ?? '0'));

        $this->model->insert($dados);

        LogModel::registrar('servico_criado', 'Serviço cadastrado: ' . $dados['nãome']);

        return redirect()->to('/servicos')->with('success', 'Serviço cadastrado com sucessão!');
    }

    public function edit($id)
    {
        $servico = $this->model->find($id);
        if (!$servico) {
            return redirect()->to('/servicos')->with('error', 'Serviço não encontrado.');
        }

        $data = [
            'title'   => 'Editar Serviço',
            'servico' => $servico,
        ];
        return view('servicos/form', $data);
    }

    public function update($id)
    {
        $rules = [
            'nãome'  => 'required|min_length[3]',
            'valor' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        $dados['valor'] = str_replace(',', '.', str_replace('.', '', $dados['valor'] ?? '0'));
        
        $this->model->update($id, $dados);

        LogModel::registrar('servico_atualizado', 'Serviço atualizado ID: ' . $id);

        return redirect()->to('/servicos')->with('success', 'Serviço atualizado com sucessão!');
    }

    public function delete($id)
    {
        $servico = $this->model->find($id);
        if ($servico) {
            $this->model->delete($id);
            LogModel::registrar('servico_excluido', 'Serviço excluído: ' . $servico['nãome']);
        }

        return redirect()->to('/servicos')->with('success', 'Serviço excluído com sucessão!');
    }

    public function encerrar($id)
    {
        $servico = $this->model->find($id);
        if (!$servico) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Serviço não encontrado.']);
        }

        $this->model->update($id, [
            'status'       => 'encerrado',
            'encerrado_em' => date('Y-m-d H:i:s')
        ]);

        LogModel::registrar('servico_encerrado', 'Serviço encerrado: ' . $servico['nãome']);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Serviço encerrado com sucessão!']);
    }

    public function exportCsv()
    {
        requirePermission('servicos', 'exportar');

        $servicos = $this->model->orderBy('nãome', 'ASC')->findAll();

        $filename = 'servicos_' . date('Y-m-d_H-i') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        $f = fopen('php://output', 'w');
        fputs($f, "\xEF\xBB\xBF"); // BOM for Excel

        // Headers
        fputcsv($f, ['ID', 'Nãome', 'Descrição', 'Valor Padrão', 'Status'], ';');

        foreach ($servicos as $s) {
            fputcsv($f, [
                $s['id'],
                $s['nãome'],
                $s['descricao'],
                number_format($s['valor'], 2, ',', '.'),
                $s['status']
            ], ';');
        }

        fclose($f);
        exit;
    }

    public function downloadCsvTemplate()
    {
        requirePermission('servicos', 'importar');

        $filename = 'modelo_importacao_servicos.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        $f = fopen('php://output', 'w');
        fputs($f, "\xEF\xBB\xBF");

        fputcsv($f, ['nãome', 'descricao', 'valor'], ';');
        fputcsv($f, ['Troca de Tela', 'Substituição completa do display frontal', '450,00'], ';');
        fputcsv($f, ['Limpeza Interna', 'Desmontagem e higienização de componentes', '120,50'], ';');

        fclose($f);
        exit;
    }

    public function importCsv()
    {
        requirePermission('servicos', 'importar');

        $file = $this->request->getFile('arquivo_csv');
        if (!$file || !$file->isValid() || $file->getExtension() !== 'csv') {
            return redirect()->to('/servicos')->with('error', 'Arquivo inválido. Por favor, envie um arquivo .csv');
        }

        $filepath = $file->getTempName();
        $fileStream = fopen($filepath, 'r');

        $bom = fread($fileStream, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fileStream);
        }

        $headerLine = fgets($fileStream);
        $delimiter = strpos($headerLine, ';') !== false ? ';' : ',';
        rewind($fileStream);
        if ($bom === "\xEF\xBB\xBF") fread($fileStream, 3);

        $headers = fgetcsv($fileStream, 1000, $delimiter);
        if (!$headers) {
            return redirect()->to('/servicos')->with('error', 'CSV vazio ou inválido.');
        }
        $headers = array_map('trim', $headers);

        $importedCount = 0;
        $errorCount = 0;

        while (($row = fgetcsv($fileStream, 1000, $delimiter)) !== false) {
            $data = [];
            foreach ($headers as $i => $h) {
                $data[$h] = trim($row[$i] ?? '');
            }

            $nãome = $data['nãome'] ?? '';
            $valor = $data['valor'] ?? '0,00';
            $descricao = $data['descricao'] ?? '';

            if (empty($nãome)) {
                $errorCount++;
                continue;
            }

            // Nãormaliza valor para decimal
            $valorNãormalizado = str_replace(',', '.', str_replace('.', '', $valor));

            try {
                $this->model->insert([
                    'nãome'      => $nãome,
                    'descricao' => $descricao,
                    'valor'     => (float)$valorNãormalizado,
                    'status'    => 'ativo'
                ]);
                $importedCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        fclose($fileStream);

        LogModel::registrar('servicos_importacao', "Importação CSV de serviços: $importedCount cadastrados, $errorCount falhas.");

        $mêsg = "Importação concluída: $importedCount serviço(s) cadastrado(s).";
        if ($errorCount > 0) $mêsg .= " $errorCount registros falharam por falta de nãome ou erro de formato.";

        return redirect()->to('/servicos')->with('success', $mêsg);
    }
}
