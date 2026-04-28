<?php

namespace App\Services;

use App\Models\AcessorioOsModel;
use App\Models\EstadoFisicoOsModel;
use App\Models\OsDocumentoModel;
use App\Models\OsItemModel;
use App\Models\OsModel;
use App\Models\OrcamentoModel;

class OsPdfService
{
    private OsModel $osModel;
    private OsItemModel $itemModel;
    private OsDocumentoModel $documentoModel;
    private PdfBrandingService $pdfBrandingService;
    private OsPdfTemplateService $templateService;

    public function __construct()
    {
        $this->osModel = new OsModel();
        $this->itemModel = new OsItemModel();
        $this->documentoModel = new OsDocumentoModel();
        $this->pdfBrandingService = new PdfBrandingService();
        $this->templateService = new OsPdfTemplateService();
    }

    public function tiposDisponiveis(): array
    {
        return $this->templateService->getTemplateOptions();
    }

    public function gerar(int $osId, string $tipo, ?int $usuarioId = null): array
    {
        if (!class_exists('Dompdf\\Dompdf')) {
            return [
                'ok' => false,
                'message' => 'Biblioteca Dompdf não instalada. Execute: composer require dompdf/dompdf:^2.0',
            ];
        }

        $os = $this->osModel->getComplete($osId);
        if (!$os) {
            return ['ok' => false, 'message' => 'OS não encontrada para gerar PDF.'];
        }

        $tipos = $this->tiposDisponiveis();
        if (!isset($tipos[$tipo])) {
            return ['ok' => false, 'message' => 'Tipo de documento inválido.'];
        }

        if ($tipo === 'orcamento') {
            return $this->gerarDocumentoOficialDeOrcamento($osId, $usuarioId);
        }

        $payload = $this->buildPayload($osId);
        $template = $this->templateService->findByCode($tipo);
        $legacyViewPath = APPPATH . 'Views/os/pdf/' . $tipo . '.php';

        if ($template !== null && trim((string) ($template['conteudo_html'] ?? '')) !== '') {
            $conteudoHtml = $this->templateService->renderTemplateHtml($template, $os, $payload);
            $html = view('os/pdf/template_padrao', [
                'os' => $os,
                'branding' => $this->pdfBrandingService->getContext(),
                'tituloDocumento' => $tipos[$tipo],
                'geradoEm' => date('d/m/Y H:i:s'),
                'conteudoHtml' => $conteudoHtml,
            ]);
        } elseif (is_file($legacyViewPath)) {
            $html = view('os/pdf/' . $tipo, [
                'os' => $os,
                'payload' => $payload,
                'branding' => $this->pdfBrandingService->getContext(),
                'tituloDocumento' => $tipos[$tipo],
                'geradoEm' => date('d/m/Y H:i:s'),
            ]);
        } else {
            return [
                'ok' => false,
                'message' => 'Nenhum modelo PDF ativo foi encontrado para este tipo de documento.',
            ];
        }

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

    public function syncBudgetDocumentsForOs(int $osId, ?array $orcamento = null): void
    {
        if ($osId <= 0 || !$this->documentoModel->db->tableExists('os_documentos')) {
            return;
        }

        $orcamentoModel = new OrcamentoModel();
        $orcamentoService = new OrcamentoService();

        if ($orcamento === null || (int) ($orcamento['id'] ?? 0) <= 0) {
            if (!$orcamentoModel->db->tableExists('orcamentos')) {
                return;
            }

            $orcamento = $orcamentoModel
                ->select('id, numero')
                ->where('os_id', $osId)
                ->orderBy('created_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();
        }

        if (!is_array($orcamento) || (int) ($orcamento['id'] ?? 0) <= 0) {
            return;
        }

        $orcamentoId = (int) ($orcamento['id'] ?? 0);
        $numero = trim((string) ($orcamento['numero'] ?? ''));
        if ($numero === '') {
            $numero = $orcamentoService->ensureNumero($orcamentoModel, $orcamentoId);
        }
        if ($numero === '') {
            return;
        }

        $slug = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $numero) ?? 'orcamento');
        $slug = trim($slug, '_') ?: 'orcamento';
        $folderPath = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'orcamentos' . DIRECTORY_SEPARATOR . 'ORC_' . $slug;
        if (!is_dir($folderPath)) {
            return;
        }

        $files = glob($folderPath . DIRECTORY_SEPARATOR . 'orcamento_v*.pdf') ?: [];
        foreach ($files as $filePath) {
            $fileName = basename((string) $filePath);
            if (preg_match('/orcamento_v(\d+)\.pdf$/i', $fileName, $matches) !== 1) {
                continue;
            }

            $relative = str_replace('\\', '/', ltrim(substr((string) $filePath, strlen(rtrim(FCPATH, '/\\'))), '/\\'));
            $exists = $this->documentoModel
                ->where('os_id', $osId)
                ->where('tipo_documento', 'orcamento')
                ->where('arquivo', $relative)
                ->countAllResults();

            if ($exists > 0) {
                continue;
            }

            $this->documentoModel->insert([
                'os_id' => $osId,
                'tipo_documento' => 'orcamento',
                'arquivo' => $relative,
                'versao' => (int) ($matches[1] ?? 1),
                'hash_sha1' => sha1_file($filePath) ?: null,
                'gerado_por' => null,
            ]);
        }
    }

    private function buildPayload(int $osId): array
    {
        $os = $this->osModel->getComplete($osId);
        $itens = $this->itemModel->getByOs($osId);
        $acessorios = (new AcessorioOsModel())->where('os_id', $osId)->orderBy('id', 'ASC')->findAll();
        $estadoFisico = (new EstadoFisicoOsModel())->where('os_id', $osId)->orderBy('id', 'ASC')->findAll();

        $totais = [
            'servicos' => 0.0,
            'pecas' => 0.0,
        ];
        $servicos = [];
        $pecas = [];

        foreach ($itens as $item) {
            $valor = (float)($item['valor_total'] ?? 0);
            if (($item['tipo'] ?? '') === 'peca') {
                $totais['pecas'] += $valor;
                $pecas[] = $item;
            } else {
                $totais['servicos'] += $valor;
                $servicos[] = $item;
            }
        }

        $procedimentosExecutados = $this->extractTextList((string) ($os['procedimentos_executados'] ?? ''));
        $garantiaDias = (int) ($os['garantia_dias'] ?? 0);
        $garantiaValidade = trim((string) ($os['garantia_validade'] ?? ''));
        $formaPagamento = trim((string) ($os['forma_pagamento'] ?? ''));
        $statusAtual = trim((string) ($os['status'] ?? ''));

        return [
            'itens' => $itens,
            'servicos' => $servicos,
            'pecas' => $pecas,
            'acessorios' => $acessorios,
            'estado_fisico' => $estadoFisico,
            'totais' => $totais,
            'procedimentos_executados' => $procedimentosExecutados,
            'resumo_cobranca' => [
                'valor_mao_obra' => (float) ($os['valor_mao_obra'] ?? $totais['servicos']),
                'valor_mao_obra_label' => formatMoney((float) ($os['valor_mao_obra'] ?? $totais['servicos'])),
                'valor_pecas' => (float) ($os['valor_pecas'] ?? $totais['pecas']),
                'valor_pecas_label' => formatMoney((float) ($os['valor_pecas'] ?? $totais['pecas'])),
                'valor_total' => (float) ($os['valor_total'] ?? ($totais['servicos'] + $totais['pecas'])),
                'valor_total_label' => formatMoney((float) ($os['valor_total'] ?? ($totais['servicos'] + $totais['pecas']))),
                'desconto' => (float) ($os['desconto'] ?? 0),
                'desconto_label' => formatMoney((float) ($os['desconto'] ?? 0)),
                'valor_final' => (float) ($os['valor_final'] ?? 0),
                'valor_final_label' => formatMoney((float) ($os['valor_final'] ?? 0)),
                'forma_pagamento' => $formaPagamento !== '' ? $formaPagamento : 'A combinar',
                'status_atual' => $statusAtual !== '' ? $this->templateService->labelForStatusCode($statusAtual) : '-',
                'garantia_label' => $this->buildGarantiaLabel($garantiaDias, $garantiaValidade),
                'data_entrega_label' => $this->formatDateTimeLabel((string) ($os['data_entrega'] ?? '')),
                'prazo_label' => $this->formatDateTimeLabel((string) ($os['data_previsao'] ?? '')),
            ],
        ];
    }

    private function extractTextList(string $value): array
    {
        $parts = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $parts = array_map(static fn (string $item): string => trim($item), $parts);
        $parts = array_values(array_filter($parts, static fn (string $item): bool => $item !== ''));

        return $parts;
    }

    private function buildGarantiaLabel(int $garantiaDias, string $garantiaValidade): string
    {
        $parts = [];
        if ($garantiaDias > 0) {
            $parts[] = $garantiaDias . ' dias';
        }

        if ($garantiaValidade !== '') {
            $parts[] = 'ate ' . $this->formatDateTimeLabel($garantiaValidade, false);
        }

        return !empty($parts) ? implode(' | ', $parts) : 'Não informada';
    }

    private function formatDateTimeLabel(string $value, bool $withTime = true): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '-';
        }

        $timestamp = strtotime($raw);
        if (!$timestamp) {
            return '-';
        }

        return $withTime ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp);
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

    private function gerarDocumentoOficialDeOrcamento(int $osId, ?int $usuarioId = null): array
    {
        $orcamentoModel = new OrcamentoModel();
        if (!$orcamentoModel->db->tableExists('orcamentos')) {
            return [
                'ok' => false,
                'needs_budget' => true,
                'message' => 'Crie primeiro um orçamento vinculado à OS para gerar este PDF.',
            ];
        }

        $orcamento = $orcamentoModel
            ->select('id, numero')
            ->where('os_id', $osId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        if (!$orcamento) {
            return [
                'ok' => false,
                'needs_budget' => true,
                'message' => 'Crie primeiro um orçamento vinculado à OS para gerar este PDF.',
            ];
        }

        $pdfResult = (new OrcamentoPdfService())->gerar((int) ($orcamento['id'] ?? 0), $usuarioId);
        if (empty($pdfResult['ok'])) {
            return $pdfResult;
        }

        $relative = (string) ($pdfResult['relative'] ?? '');
        $exists = $relative !== ''
            ? $this->documentoModel
                ->where('os_id', $osId)
                ->where('tipo_documento', 'orcamento')
                ->where('arquivo', $relative)
                ->countAllResults()
            : 0;

        if ($relative !== '' && $exists === 0) {
            $this->documentoModel->insert([
                'os_id' => $osId,
                'tipo_documento' => 'orcamento',
                'arquivo' => $relative,
                'versao' => (int) ($pdfResult['versao'] ?? 1),
                'hash_sha1' => $pdfResult['hash_sha1'] ?? null,
                'gerado_por' => $usuarioId,
            ]);
        }

        return [
            'ok' => true,
            'path' => $pdfResult['path'] ?? '',
            'relative' => $relative,
            'url' => $pdfResult['url'] ?? '',
            'tipo' => 'orcamento',
            'versao' => (int) ($pdfResult['versao'] ?? 1),
            'orcamento_id' => (int) ($orcamento['id'] ?? 0),
        ];
    }
}
