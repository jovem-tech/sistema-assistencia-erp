<?php

namespace App\Controllers;

use App\Models\PecaModel;
use App\Models\MovimentacaoModel;
use App\Models\LogModel;
use App\Models\EquipamentoTipoModel;
use App\Models\PrecificacaoCategoriaModel;

class Estoque extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new PecaModel();
        requirePermission('estoque');
    }

    public function index()
    {
        $data = [
            'title' => 'Estoque de Peças',
            'pecas' => $this->model->where('ativo', 1)->orderBy('nome', 'ASC')->findAll(),
        ];
        return view('estoque/index', $data);
    }

    public function create()
    {
        $data = [
            'title'  => 'Nova Peça',
            'codigo' => $this->model->generateCodigo(),
            'tiposEquipamento' => $this->loadTiposEquipamentoOptions(),
            'categoriasPeca' => $this->loadCategoriasPecaOptions(),
        ];
        return view('estoque/form', $data);
    }

    public function store()
    {
        $rules = [
            'nome'        => 'required',
            'preco_custo' => 'required',
            'preco_venda' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        if (empty($dados['codigo'])) {
            $dados['codigo'] = $this->model->generateCodigo();
        }

        $dados['tipo_equipamento'] = trim((string) ($dados['tipo_equipamento'] ?? ''));
        if ($dados['tipo_equipamento'] === '') {
            $dados['tipo_equipamento'] = null;
        }

        $this->model->insert($dados);
        LogModel::registrar('peca_criada', 'Peça cadastrada: ' . ($dados['nome'] ?? ''));

        return redirect()->to('/estoque')->with('success', 'Peça cadastrada com sucesso!');
    }

    public function edit($id)
    {
        $peca = $this->model->find($id);
        if (! $peca) {
            return redirect()->to('/estoque')->with('error', 'Peça não encontrada.');
        }

        $data = [
            'title' => 'Editar Peça',
            'peca'  => $peca,
            'tiposEquipamento' => $this->loadTiposEquipamentoOptions(),
            'categoriasPeca' => $this->loadCategoriasPecaOptions((string) ($peca['categoria'] ?? '')),
        ];
        return view('estoque/form', $data);
    }

    public function update($id)
    {
        $dados = $this->request->getPost();
        $dados['tipo_equipamento'] = trim((string) ($dados['tipo_equipamento'] ?? ''));
        if ($dados['tipo_equipamento'] === '') {
            $dados['tipo_equipamento'] = null;
        }

        $this->model->update($id, $dados);
        LogModel::registrar('peca_atualizada', 'Peça atualizada ID: ' . $id);

        return redirect()->to('/estoque')->with('success', 'Peça atualizada com sucesso!');
    }

    public function delete($id)
    {
        $this->model->update($id, ['ativo' => 0]);
        LogModel::registrar('peca_desativada', 'Peça desativada ID: ' . $id);

        return redirect()->to('/estoque')->with('success', 'Peça removida com sucesso!');
    }

    public function movement()
    {
        $movModel = new MovimentacaoModel();
        $dados = $this->request->getPost();

        $peca = $this->model->find($dados['peca_id']);
        if (! $peca) {
            return redirect()->back()->with('error', 'Peça não encontrada.');
        }

        $movModel->insert([
            'peca_id'        => $dados['peca_id'],
            'tipo'           => $dados['tipo'],
            'quantidade'     => $dados['quantidade'],
            'motivo'         => $dados['motivo'] ?? '',
            'responsavel_id' => session()->get('user_id'),
        ]);

        $novaQtd = (int) ($peca['quantidade_atual'] ?? 0);
        if (($dados['tipo'] ?? '') === 'entrada') {
            $novaQtd += (int) ($dados['quantidade'] ?? 0);
        } elseif (($dados['tipo'] ?? '') === 'saida') {
            $novaQtd -= (int) ($dados['quantidade'] ?? 0);
        } else {
            $novaQtd = (int) ($dados['quantidade'] ?? 0);
        }

        $this->model->update($dados['peca_id'], ['quantidade_atual' => max(0, $novaQtd)]);

        LogModel::registrar('estoque_movimentacao', ucfirst((string) ($dados['tipo'] ?? '')) . ' de ' . (int) ($dados['quantidade'] ?? 0) . ' unid. - ' . ($peca['nome'] ?? ''));

        return redirect()->to('/estoque')->with('success', 'Movimentação registrada com sucesso!');
    }

    public function movements($id)
    {
        $movModel = new MovimentacaoModel();
        $peca = $this->model->find($id);

        $data = [
            'title'         => 'Movimentações - ' . ($peca['nome'] ?? ''),
            'peca'          => $peca,
            'movimentacoes' => $movModel->getByPeca($id),
        ];
        return view('estoque/movimentacoes', $data);
    }

    public function search()
    {
        $term = $this->request->getGet('q');
        $results = $this->model->search($term);
        return $this->response->setJSON($results);
    }

    public function exportCsv()
    {
        requirePermission('estoque', 'exportar');

        $pecas = $this->model->where('ativo', 1)->orderBy('nome', 'ASC')->findAll();

        $filename = 'estoque_pecas_' . date('Y-m-d_H-i') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        $f = fopen('php://output', 'w');
        fputs($f, "\xEF\xBB\xBF");

        fputcsv($f, ['Código', 'Cód. Fabricante', 'Nome', 'Categoria', 'Tipo de Equipamento', 'Modelos Compatíveis', 'Fornecedor', 'Localização', 'Custo', 'Venda', 'Qtd Atual', 'Mínimo', 'Observações'], ';');

        foreach ($pecas as $p) {
            fputcsv($f, [
                $p['codigo'] ?? '',
                $p['codigo_fabricante'] ?? '',
                $p['nome'] ?? '',
                $p['categoria'] ?? '',
                $p['tipo_equipamento'] ?? '',
                $p['modelos_compativeis'] ?? '',
                $p['fornecedor'] ?? '',
                $p['localizacao'] ?? '',
                number_format((float) ($p['preco_custo'] ?? 0), 2, ',', '.'),
                number_format((float) ($p['preco_venda'] ?? 0), 2, ',', '.'),
                (int) ($p['quantidade_atual'] ?? 0),
                (int) ($p['estoque_minimo'] ?? 0),
                $p['observacoes'] ?? '',
            ], ';');
        }

        fclose($f);
        exit;
    }

    public function downloadCsvTemplate()
    {
        requirePermission('estoque', 'importar');

        $filename = 'modelo_importacao_estoque.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        $f = fopen('php://output', 'w');
        fputs($f, "\xEF\xBB\xBF");

        fputcsv($f, ['codigo', 'codigo_fabricante', 'nome', 'categoria', 'tipo_equipamento', 'modelos_compativeis', 'fornecedor', 'localizacao', 'preco_custo', 'preco_venda', 'quantidade_atual', 'estoque_minimo', 'observacoes'], ';');
        fputcsv($f, ['PC00001', 'SN123456', 'Tela iPhone 13 OLED', 'Telas', 'Smartphone', 'iPhone 13, iPhone 13 Pro', 'Apple Parts', 'Gaveta A1', '850,00', '1400,00', '5', '2', 'Peça importada classe AAA'], ';');
        fputcsv($f, ['PC00002', 'BAT-SAM-G990', 'Bateria Samsung S21', 'Baterias', 'Smartphone', 'Galaxy S21 (G990)', 'Distribuidora X', 'Gaveta B4', '120,00', '350,00', '10', '3', ''], ';');

        fclose($f);
        exit;
    }

    public function importCsv()
    {
        requirePermission('estoque', 'importar');

        $file = $this->request->getFile('arquivo_csv');
        if (! $file || ! $file->isValid() || $file->getExtension() !== 'csv') {
            return redirect()->to('/estoque')->with('error', 'Arquivo inválido. Envie um arquivo CSV.');
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
            return redirect()->to('/estoque')->with('error', 'CSV vazio ou inválido.');
        }
        $headers = array_map('trim', $headers);

        $importedCount = 0;
        $errorCount = 0;

        while (($row = fgetcsv($fileStream, 1000, $delimiter)) !== false) {
            $data = [];
            foreach ($headers as $i => $h) {
                if (isset($row[$i])) {
                    $data[$h] = trim($row[$i]);
                }
            }

            if (empty($data['nome'])) {
                $errorCount++;
                continue;
            }

            $custo = str_replace(',', '.', str_replace('.', '', (string) ($data['preco_custo'] ?? '0')));
            $venda = str_replace(',', '.', str_replace('.', '', (string) ($data['preco_venda'] ?? '0')));
            $tipoEquipamento = trim((string) ($data['tipo_equipamento'] ?? ($data['tipo equipamento'] ?? '')));

            $pecaData = [
                'codigo'              => $data['codigo'] ?? $this->model->generateCodigo(),
                'codigo_fabricante'   => $data['codigo_fabricante'] ?? '',
                'nome'                => $data['nome'],
                'categoria'           => $data['categoria'] ?? '',
                'tipo_equipamento'    => $tipoEquipamento !== '' ? $tipoEquipamento : null,
                'modelos_compativeis' => $data['modelos_compativeis'] ?? '',
                'fornecedor'          => $data['fornecedor'] ?? '',
                'localizacao'         => $data['localizacao'] ?? '',
                'preco_custo'         => (float) $custo,
                'preco_venda'         => (float) $venda,
                'quantidade_atual'    => (int) ($data['quantidade_atual'] ?? 0),
                'estoque_minimo'      => (int) ($data['estoque_minimo'] ?? 0),
                'observacoes'         => $data['observacoes'] ?? '',
                'ativo'               => 1,
            ];

            try {
                $this->model->insert($pecaData);
                $importedCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        fclose($fileStream);

        LogModel::registrar('estoque_importacao', "Importação CSV de estoque: $importedCount cadastrados, $errorCount falhas.");

        $msg = "Importação concluída: $importedCount peça(s) cadastrada(s).";
        if ($errorCount > 0) {
            $msg .= " $errorCount registros falharam por falta de nome ou erro de formato.";
        }

        return redirect()->to('/estoque')->with('success', $msg);
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
            log_message('warning', '[Estoque] Falha ao carregar tipos de equipamento: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @return array<int,string>
     */
    private function loadCategoriasPecaOptions(?string $categoriaAtual = null): array
    {
        $options = [];
        $push = static function (array &$bucket, string $value): void {
            $normalized = trim($value);
            if ($normalized === '') {
                return;
            }

            $key = function_exists('mb_strtolower')
                ? mb_strtolower($normalized)
                : strtolower($normalized);

            if (! array_key_exists($key, $bucket)) {
                $bucket[$key] = $normalized;
            }
        };

        foreach ($this->model->getCategoriasAtivas() as $categoria) {
            $push($options, (string) $categoria);
        }

        try {
            $precificacaoCategoriaModel = new PrecificacaoCategoriaModel();
            if ($precificacaoCategoriaModel->isTableReady()) {
                foreach ($precificacaoCategoriaModel->getAtivosPorTipo('peca') as $row) {
                    $push($options, (string) ($row['categoria_nome'] ?? ''));
                }
            }
        } catch (\Throwable $e) {
            log_message('debug', '[Estoque] Não foi possível carregar categorias da precificação: ' . $e->getMessage());
        }

        $push($options, (string) ($categoriaAtual ?? ''));

        $values = array_values($options);
        usort($values, static fn (string $a, string $b): int => strcasecmp($a, $b));

        return $values;
    }
}
