<?php

namespace App\Controllers;

use App\Models\ServicoModel;
use App\Models\LogModel;
use App\Models\EquipamentoTipoModel;

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
            'servicos' => $this->model->orderBy('nome', 'ASC')->findAll(),
        ];
        return view('servicos/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Novo Serviço',
            'tiposEquipamento' => $this->loadTiposEquipamentoOptions(),
        ];
        return view('servicos/form', $data);
    }

    public function store()
    {
        $rules = [
            'nome'  => 'required|min_length[3]',
            'valor' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        $dados['valor'] = str_replace(',', '.', str_replace('.', '', $dados['valor'] ?? '0'));
        $dados['tempo_padrao_horas'] = $this->normalizeDecimalInput($dados['tempo_padrao_horas'] ?? '1');
        if ($dados['tempo_padrao_horas'] <= 0) {
            $dados['tempo_padrao_horas'] = 1.0;
        }
        $dados['custo_direto_padrao'] = max(0.0, $this->normalizeDecimalInput($dados['custo_direto_padrao'] ?? '0'));
        $dados['tipo_equipamento'] = trim((string) ($dados['tipo_equipamento'] ?? ''));
        if ($dados['tipo_equipamento'] === '') {
            $dados['tipo_equipamento'] = null;
        }
        $dados['status'] = trim((string) ($dados['status'] ?? 'ativo')) ?: 'ativo';

        $this->model->insert($dados);

        LogModel::registrar('servico_criado', 'Serviço cadastrado: ' . ($dados['nome'] ?? '')); 

        return redirect()->to('/servicos')->with('success', 'Serviço cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $servico = $this->model->find($id);
        if (! $servico) {
            return redirect()->to('/servicos')->with('error', 'Serviço não encontrado.');
        }

        $data = [
            'title'   => 'Editar Serviço',
            'servico' => $servico,
            'tiposEquipamento' => $this->loadTiposEquipamentoOptions(),
        ];
        return view('servicos/form', $data);
    }

    public function update($id)
    {
        $rules = [
            'nome'  => 'required|min_length[3]',
            'valor' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        $dados['valor'] = str_replace(',', '.', str_replace('.', '', $dados['valor'] ?? '0'));
        $dados['tempo_padrao_horas'] = $this->normalizeDecimalInput($dados['tempo_padrao_horas'] ?? '1');
        if ($dados['tempo_padrao_horas'] <= 0) {
            $dados['tempo_padrao_horas'] = 1.0;
        }
        $dados['custo_direto_padrao'] = max(0.0, $this->normalizeDecimalInput($dados['custo_direto_padrao'] ?? '0'));
        $dados['tipo_equipamento'] = trim((string) ($dados['tipo_equipamento'] ?? ''));
        if ($dados['tipo_equipamento'] === '') {
            $dados['tipo_equipamento'] = null;
        }

        $this->model->update($id, $dados);

        LogModel::registrar('servico_atualizado', 'Serviço atualizado ID: ' . $id);

        return redirect()->to('/servicos')->with('success', 'Serviço atualizado com sucesso!');
    }

    public function delete($id)
    {
        $servico = $this->model->find($id);
        if ($servico) {
            $this->model->delete($id);
            LogModel::registrar('servico_excluido', 'Serviço excluído: ' . ($servico['nome'] ?? ''));
        }

        return redirect()->to('/servicos')->with('success', 'Serviço excluído com sucesso!');
    }

    public function encerrar($id)
    {
        $servico = $this->model->find($id);
        if (! $servico) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Serviço não encontrado.']);
        }

        $this->model->update($id, [
            'status'       => 'encerrado',
            'encerrado_em' => date('Y-m-d H:i:s'),
        ]);

        LogModel::registrar('servico_encerrado', 'Serviço encerrado: ' . ($servico['nome'] ?? ''));

        return $this->response->setJSON(['status' => 'success', 'message' => 'Serviço encerrado com sucesso!']);
    }

    public function exportCsv()
    {
        requirePermission('servicos', 'exportar');

        $servicos = $this->model->orderBy('nome', 'ASC')->findAll();

        $filename = 'servicos_' . date('Y-m-d_H-i') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        $f = fopen('php://output', 'w');
        fputs($f, "\xEF\xBB\xBF");

        fputcsv($f, ['ID', 'Nome', 'Descrição', 'Tipo de Equipamento', 'Valor Padrão', 'Tempo Padrão (h)', 'Custo Direto Padrão', 'Status'], ';');

        foreach ($servicos as $s) {
            fputcsv($f, [
                $s['id'] ?? '',
                $s['nome'] ?? '',
                $s['descricao'] ?? '',
                $s['tipo_equipamento'] ?? '',
                number_format((float) ($s['valor'] ?? 0), 2, ',', '.'),
                number_format((float) ($s['tempo_padrao_horas'] ?? 1), 2, ',', '.'),
                number_format((float) ($s['custo_direto_padrao'] ?? 0), 2, ',', '.'),
                $s['status'] ?? '',
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

        fputcsv($f, ['nome', 'descricao', 'tipo_equipamento', 'valor', 'tempo_padrao_horas', 'custo_direto_padrao'], ';');
        fputcsv($f, ['Troca de Tela', 'Substituição completa do display frontal', 'Smartphone', '450,00', '1,00', '20,00'], ';');
        fputcsv($f, ['Limpeza Interna', 'Desmontagem e higienização de componentes', 'Notebook', '120,50', '0,80', '8,00'], ';');

        fclose($f);
        exit;
    }

    public function importCsv()
    {
        requirePermission('servicos', 'importar');

        $file = $this->request->getFile('arquivo_csv');
        if (! $file || ! $file->isValid() || $file->getExtension() !== 'csv') {
            return redirect()->to('/servicos')->with('error', 'Arquivo inválido. Envie um arquivo CSV.');
        }

        $filepath = $file->getTempName();
        $fileStream = fopen($filepath, 'r');

        $bom = fread($fileStream, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fileStream);
        }

        $headerLine = fgets($fileStream);
        $delimiter = strpos((string) $headerLine, ';') !== false ? ';' : ',';
        rewind($fileStream);
        if ($bom === "\xEF\xBB\xBF") {
            fread($fileStream, 3);
        }

        $headers = fgetcsv($fileStream, 1000, $delimiter);
        if (! $headers) {
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

            $nome = trim((string) ($data['nome'] ?? ''));
            $valor = $data['valor'] ?? '0,00';
            $descricao = $data['descricao'] ?? '';
            $tipoEquipamento = trim((string) ($data['tipo_equipamento'] ?? ($data['tipo equipamento'] ?? '')));
            $tempoPadrao = $this->normalizeDecimalInput($data['tempo_padrao_horas'] ?? ($data['tempo padrao (h)'] ?? '1'));
            if ($tempoPadrao <= 0) {
                $tempoPadrao = 1.0;
            }
            $custoDiretoPadrao = max(0.0, $this->normalizeDecimalInput($data['custo_direto_padrao'] ?? '0'));

            if ($nome === '') {
                $errorCount++;
                continue;
            }

            $valorNormalizado = str_replace(',', '.', str_replace('.', '', (string) $valor));

            try {
                $this->model->insert([
                    'nome'      => $nome,
                    'descricao' => $descricao,
                    'tipo_equipamento' => $tipoEquipamento !== '' ? $tipoEquipamento : null,
                    'valor'     => (float) $valorNormalizado,
                    'tempo_padrao_horas' => $tempoPadrao,
                    'custo_direto_padrao' => $custoDiretoPadrao,
                    'status'    => 'ativo',
                ]);
                $importedCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        fclose($fileStream);

        LogModel::registrar('servicos_importacao', "Importação CSV de serviços: $importedCount cadastrados, $errorCount falhas.");

        $msg = "Importação concluída: $importedCount serviço(s) cadastrado(s).";
        if ($errorCount > 0) {
            $msg .= " $errorCount registros falharam por falta de nome ou erro de formato.";
        }

        return redirect()->to('/servicos')->with('success', $msg);
    }

    private function loadTiposEquipamentoOptions(): array
    {
        try {
            return array_values(array_filter(array_map(
                static fn (array $row): string => trim((string) ($row['nome'] ?? '')),
                (new EquipamentoTipoModel())
                    ->where('ativo', 1)
                    ->orderBy('nome', 'ASC')
                    ->findAll()
            )));
        } catch (\Throwable $e) {
            log_message('warning', '[Servicos] Falha ao carregar tipos de equipamento: ' . $e->getMessage());
            return [];
        }
    }

    private function normalizeDecimalInput($value): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
        }
        $raw = str_replace(',', '.', $raw);
        if (!is_numeric($raw)) {
            return 0.0;
        }

        return (float) $raw;
    }
}
