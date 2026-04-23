<?php

namespace App\Services;

class PacoteOfertaPdfService
{
    private PdfBrandingService $pdfBrandingService;

    public function __construct()
    {
        $this->pdfBrandingService = new PdfBrandingService();
    }

    public function gerarComparativo(int $ofertaId, array $payload): array
    {
        if ($ofertaId <= 0) {
            return [
                'ok' => false,
                'message' => 'Oferta invalida para gerar PDF comparativo.',
            ];
        }

        if (!class_exists('Dompdf\\Dompdf')) {
            return [
                'ok' => false,
                'message' => 'Biblioteca Dompdf nao instalada. Execute: composer require dompdf/dompdf:^2.0',
            ];
        }

        $folderInfo = $this->ensureFolder($ofertaId);
        $versao = $this->nextVersion($folderInfo['path']);
        $fileName = sprintf('pacote_oferta_comparativo_v%d.pdf', $versao);
        $fullPath = $folderInfo['path'] . $fileName;

        $html = view('orcamentos/pdf/pacote_oferta_comparativo', array_merge([
            'branding' => $this->pdfBrandingService->getContext(),
            'tituloDocumento' => 'Comparativo de pacote e itens extras',
            'geradoEm' => date('d/m/Y H:i:s'),
        ], $payload));

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
                'message' => 'Falha ao gravar arquivo PDF comparativo da oferta.',
            ];
        }

        $relative = $folderInfo['relative'] . $fileName;

        return [
            'ok' => true,
            'oferta_id' => $ofertaId,
            'versao' => $versao,
            'path' => $fullPath,
            'relative' => $relative,
            'url' => base_url($relative),
            'nome_arquivo' => $fileName,
            'hash_sha1' => sha1_file($fullPath) ?: null,
        ];
    }

    private function ensureFolder(int $ofertaId): array
    {
        $relative = 'uploads/pacotes/ofertas/oferta_' . $ofertaId . '/';
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
        $files = glob(rtrim($folderPath, '/\\') . DIRECTORY_SEPARATOR . 'pacote_oferta_comparativo_v*.pdf') ?: [];

        foreach ($files as $filePath) {
            $base = basename((string) $filePath);
            if (preg_match('/pacote_oferta_comparativo_v(\\d+)\\.pdf$/i', $base, $matches) !== 1) {
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
