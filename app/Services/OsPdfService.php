<?php

namespace App\Services;

use App\Models\AcessorioOsModel;
use App\Models\EstadoFisicoOsModel;
use App\Models\OsDocumentoModel;
use App\Models\OsItemModel;
use App\Models\OsModel;

class OsPdfService
{
    private OsModel $osModel;
    private OsItemModel $itemModel;
    private OsDocumentoModel $documentoModel;

    public function __construct()
    {
        $this->osModel = new OsModel();
        $this->itemModel = new OsItemModel();
        $this->documentoModel = new OsDocumentoModel();
    }

    public function tiposDisponiveis(): array
    {
        return [
            'abertura' => 'Comprovante de Abertura',
            'orcamento' => 'Orcamento',
            'laudo' => 'Laudo Tecnico',
            'entrega' => 'Comprovante de Entrega',
            'devolucao_sem_reparo' => 'Devolucao Sem Reparo',
        ];
    }

    public function gerar(int $osId, string $tipo, ?int $usuarioId = null): array
    {
        if (!class_exists('Dompdf\\Dompdf')) {
            return [
                'ok' => false,
                'message' => 'Biblioteca Dompdf nao instalada. Execute: composer require dompdf/dompdf:^2.0',
            ];
        }

        $os = $this->osModel->getComplete($osId);
        if (!$os) {
            return ['ok' => false, 'message' => 'OS nao encontrada para gerar PDF.'];
        }

        $tipos = $this->tiposDisponiveis();
        if (!isset($tipos[$tipo])) {
            return ['ok' => false, 'message' => 'Tipo de documento invalido.'];
        }

        $payload = $this->buildPayload($osId);
        $html = view('os/pdf/' . $tipo, [
            'os' => $os,
            'payload' => $payload,
            'tituloDocumento' => $tipos[$tipo],
            'geradoEm' => date('d/m/Y H:i:s'),
        ]);

        $folderInfo = $this->ensureFolder((string)$os['numero_os']);
        $versao = $this->nextVersion($osId, $tipo);
        $nomeArquivo = sprintf('%s_v%d.pdf', $tipo, $versao);
        $fullPath = $folderInfo['path'] . $nomeArquivo;

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        file_put_contents($fullPath, $dompdf->output());

        $relative = $folderInfo['relative'] . $nomeArquivo;
        $this->documentoModel->insert([
            'os_id' => $osId,
            'tipo_documento' => $tipo,
            'arquivo' => $relative,
            'versao' => $versao,
            'hash_sha1' => sha1_file($fullPath) ?: null,
            'gerado_por' => $usuarioId,
        ]);

        return [
            'ok' => true,
            'path' => $fullPath,
            'relative' => $relative,
            'url' => base_url($relative),
            'tipo' => $tipo,
            'versao' => $versao,
        ];
    }

    private function buildPayload(int $osId): array
    {
        $itens = $this->itemModel->getByOs($osId);
        $acessorios = (new AcessorioOsModel())->where('os_id', $osId)->orderBy('id', 'ASC')->findAll();
        $estadoFisico = (new EstadoFisicoOsModel())->where('os_id', $osId)->orderBy('id', 'ASC')->findAll();

        $totais = [
            'servicos' => 0.0,
            'pecas' => 0.0,
        ];

        foreach ($itens as $item) {
            $valor = (float)($item['valor_total'] ?? 0);
            if (($item['tipo'] ?? '') === 'peca') {
                $totais['pecas'] += $valor;
            } else {
                $totais['servicos'] += $valor;
            }
        }

        return [
            'itens' => $itens,
            'acessorios' => $acessorios,
            'estado_fisico' => $estadoFisico,
            'totais' => $totais,
        ];
    }

    private function ensureFolder(string $numeroOs): array
    {
        $slug = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $numeroOs) ?? 'os');
        $slug = trim($slug, '_');
        $slug = $slug ?: 'os';

        $relative = 'uploads/os_documentos/OS_' . $slug . '/';
        $path = FCPATH . $relative;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return ['path' => $path, 'relative' => $relative];
    }

    private function nextVersion(int $osId, string $tipo): int
    {
        $last = $this->documentoModel
            ->where('os_id', $osId)
            ->where('tipo_documento', $tipo)
            ->orderBy('versao', 'DESC')
            ->first();

        return (int)($last['versao'] ?? 0) + 1;
    }
}
