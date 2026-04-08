<?php

namespace App\Services;

use App\Models\OrcamentoItemModel;
use App\Models\OrcamentoModel;

class OrcamentoPdfService
{
    private OrcamentoModel $orcamentoModel;
    private OrcamentoItemModel $itemModel;
    private OrcamentoService $orcamentoService;

    public function __construct()
    {
        $this->orcamentoModel = new OrcamentoModel();
        $this->itemModel = new OrcamentoItemModel();
        $this->orcamentoService = new OrcamentoService();
    }

    public function gerar(int $orcamentoId, ?int $usuarioId = null): array
    {
        if (!class_exists('Dompdf\\Dompdf')) {
            return [
                'ok' => false,
                'message' => 'Biblioteca Dompdf nao instalada. Execute: composer require dompdf/dompdf:^2.0',
            ];
        }

        $orcamento = $this->orcamentoModel
            ->select('orcamentos.*, clientes.nome_razao as cliente_nome, os.numero_os')
            ->join('clientes', 'clientes.id = orcamentos.cliente_id', 'left')
            ->join('os', 'os.id = orcamentos.os_id', 'left')
            ->where('orcamentos.id', $orcamentoId)
            ->first();

        if (!$orcamento) {
            return [
                'ok' => false,
                'message' => 'Orcamento nao encontrado para gerar PDF.',
            ];
        }

        $numero = $this->orcamentoService->ensureNumero($this->orcamentoModel, $orcamentoId);
        if ($numero !== '') {
            $orcamento['numero'] = $numero;
        }

        $itens = $this->itemModel->byOrcamento($orcamentoId);
        $folderInfo = $this->ensureFolder((string) ($orcamento['numero'] ?? ('orcamento_' . $orcamentoId)));
        $versao = $this->nextVersion($folderInfo['path']);
        $fileName = sprintf('orcamento_v%d.pdf', $versao);
        $fullPath = $folderInfo['path'] . $fileName;

        $html = view('orcamentos/pdf/orcamento', [
            'orcamento' => $orcamento,
            'itens' => $itens,
            'tituloDocumento' => 'Orcamento',
            'geradoEm' => date('d/m/Y H:i:s'),
        ]);

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);

        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        if (file_put_contents($fullPath, $dompdf->output()) === false) {
            return [
                'ok' => false,
                'message' => 'Falha ao gravar arquivo PDF do orcamento.',
            ];
        }

        $relative = $folderInfo['relative'] . $fileName;

        return [
            'ok' => true,
            'orcamento_id' => $orcamentoId,
            'versao' => $versao,
            'path' => $fullPath,
            'relative' => $relative,
            'url' => base_url($relative),
            'nome_arquivo' => $fileName,
            'hash_sha1' => sha1_file($fullPath) ?: null,
            'gerado_por' => $usuarioId,
        ];
    }

    private function ensureFolder(string $numero): array
    {
        $slug = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $numero) ?? 'orcamento');
        $slug = trim($slug, '_');
        $slug = $slug !== '' ? $slug : 'orcamento';

        $relative = 'uploads/orcamentos/ORC_' . $slug . '/';
        $path = FCPATH . $relative;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return [
            'path' => $path,
            'relative' => $relative,
        ];
    }

    private function nextVersion(string $folderPath): int
    {
        $currentVersion = 0;
        $files = glob(rtrim($folderPath, '/\\') . DIRECTORY_SEPARATOR . 'orcamento_v*.pdf') ?: [];

        foreach ($files as $filePath) {
            $base = basename((string) $filePath);
            if (preg_match('/orcamento_v(\d+)\.pdf$/i', $base, $matches) !== 1) {
                continue;
            }
            $version = (int) ($matches[1] ?? 0);
            if ($version > $currentVersion) {
                $currentVersion = $version;
            }
        }

        return $currentVersion + 1;
    }
}
