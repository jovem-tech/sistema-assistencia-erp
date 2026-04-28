<?php

namespace App\Services;

use App\Models\AcessorioOsModel;
use App\Models\ClienteModel;
use App\Models\DefeitoModel;
use App\Models\EquipamentoFotoModel;
use App\Models\EquipamentoDefeitoProcedimentoModel;
use App\Models\EquipamentoModel;
use App\Models\EstadoFisicoOsModel;
use App\Models\FotoAcessorioModel;
use App\Models\FotoEstadoFisicoModel;
use App\Models\OrcamentoItemModel;
use App\Models\OrcamentoModel;
use App\Models\OsFotoModel;
use App\Models\OsItemModel;
use App\Models\OsModel;
use App\Models\OsNotaLegadaModel;
use App\Models\OsStatusModel;
use Config\Database;

class OsPrintService
{
    public const FORMAT_A4 = 'a4';
    public const FORMAT_THERMAL = '80mm';

    private OsModel $osModel;
    private ClienteModel $clienteModel;
    private EquipamentoModel $equipamentoModel;
    private OsItemModel $itemModel;
    private DefeitoModel $defeitoModel;
    private EquipamentoDefeitoProcedimentoModel $procedimentoModel;
    private AcessorioOsModel $acessorioModel;
    private FotoAcessorioModel $fotoAcessorioModel;
    private EstadoFisicoOsModel $estadoFisicoModel;
    private FotoEstadoFisicoModel $fotoEstadoFisicoModel;
    private OsFotoModel $osFotoModel;
    private EquipamentoFotoModel $equipamentoFotoModel;
    private OsNotaLegadaModel $notaLegadaModel;
    private OsStatusModel $statusModel;
    private PdfBrandingService $brandingService;

    public function __construct()
    {
        $this->osModel = new OsModel();
        $this->clienteModel = new ClienteModel();
        $this->equipamentoModel = new EquipamentoModel();
        $this->itemModel = new OsItemModel();
        $this->defeitoModel = new DefeitoModel();
        $this->procedimentoModel = new EquipamentoDefeitoProcedimentoModel();
        $this->acessorioModel = new AcessorioOsModel();
        $this->fotoAcessorioModel = new FotoAcessorioModel();
        $this->estadoFisicoModel = new EstadoFisicoOsModel();
        $this->fotoEstadoFisicoModel = new FotoEstadoFisicoModel();
        $this->osFotoModel = new OsFotoModel();
        $this->equipamentoFotoModel = new EquipamentoFotoModel();
        $this->notaLegadaModel = new OsNotaLegadaModel();
        $this->statusModel = new OsStatusModel();
        $this->brandingService = new PdfBrandingService();
    }

    public function availableFormats(): array
    {
        return [
            self::FORMAT_A4 => 'Folha A4',
            self::FORMAT_THERMAL => 'Bobina 80mm',
        ];
    }

    public function buildDocumentContext(int $osId, array $options = []): ?array
    {
        $normalizedOptions = $this->normalizeOptions($options);
        $os = $this->osModel->getComplete($osId);
        if (!$os) {
            return null;
        }

        $cliente = $this->clienteModel->find((int) ($os['cliente_id'] ?? 0)) ?? [];
        $equipamento = $this->equipamentoModel->getWithCliente((int) ($os['equipamento_id'] ?? 0)) ?? [];

        $itensOs = $this->itemModel->getByOs($osId);
        $defeitos = $this->loadDefeitos($osId);
        $acessorios = $this->loadAcessorios($osId, (string) ($os['numero_os'] ?? ''));
        $estadoFisico = $this->loadEstadoFisico($osId, (string) ($os['numero_os'] ?? ''));
        $fotosPerfil = $this->loadFotosPerfil((int) ($os['equipamento_id'] ?? 0));
        $fotoPerfilPrincipal = $this->resolvePrimaryPhoto($fotosPerfil);
        $fotosEntrada = $this->loadFotosEntrada($osId);
        $checklistEntrada = $this->loadChecklistEntrada($osId, $os, $equipamento);
        $orcamento = $this->loadLatestOrcamento($osId);
        $orcamentoResumo = $orcamento !== null
            ? $this->summarizeOrcamentoItems((int) ($orcamento['id'] ?? 0))
            : [
                'items' => [],
                'groups' => [],
                'total_items' => 0,
                'total_quantity' => 0.0,
            ];

        $procedimentosExecutados = $this->splitTextLines((string) ($os['procedimentos_executados'] ?? ''));
        $notasLegadas = $this->loadNotasLegadas($osId);
        $resumoFinanceiro = $this->buildFinancialSummary($os, $itensOs);
        $photoGroups = $normalizedOptions['include_photos']
            ? $this->buildPhotoGroups($fotosPerfil, $fotosEntrada, $acessorios, $estadoFisico, $checklistEntrada)
            : [];

        return [
            'os' => $os,
            'cliente' => $cliente,
            'equipamento' => $equipamento,
            'itensOs' => $itensOs,
            'defeitos' => $defeitos,
            'acessorios' => $acessorios,
            'estadoFisico' => $estadoFisico,
            'fotosPerfil' => $fotosPerfil,
            'fotoPerfilPrincipal' => $fotoPerfilPrincipal,
            'fotosEntrada' => $fotosEntrada,
            'checklistEntrada' => $checklistEntrada,
            'orcamento' => $orcamento,
            'orcamentoResumo' => $orcamentoResumo,
            'notasLegadas' => $notasLegadas,
            'procedimentosExecutados' => $procedimentosExecutados,
            'resumoFinanceiro' => $resumoFinanceiro,
            'photoGroups' => $photoGroups,
            'branding' => $this->brandingService->getContext(),
            'printOptions' => $normalizedOptions,
            'formatLabel' => $this->availableFormats()[$normalizedOptions['format']] ?? 'Folha A4',
            'clienteEnderecoCompleto' => $this->buildClientAddress($cliente),
            'statusLabel' => $this->resolveOsStatusLabel((string) ($os['status'] ?? '')),
            'estadoFluxoLabel' => $this->humanizeText((string) ($os['estado_fluxo'] ?? '')),
            'generatedAt' => date('d/m/Y H:i:s'),
            'renderMode' => trim((string) ($options['render_mode'] ?? 'preview')) === 'pdf' ? 'pdf' : 'preview',
        ];
    }

    public function generatePdf(int $osId, array $options = []): array
    {
        if (!class_exists('Dompdf\\Dompdf')) {
            return [
                'ok' => false,
                'message' => 'Biblioteca Dompdf nao instalada para gerar o PDF da impressao.',
            ];
        }

        $options['render_mode'] = 'pdf';
        $context = $this->buildDocumentContext($osId, $options);
        if ($context === null) {
            return [
                'ok' => false,
                'message' => 'OS nao encontrada para gerar o PDF de impressao.',
            ];
        }

        $html = view('os/print', $context);
        $outputDir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'os_print';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $numeroOs = trim((string) ($context['os']['numero_os'] ?? ('os_' . $osId)));
        $format = (string) ($context['printOptions']['format'] ?? self::FORMAT_A4);
        $slug = $this->slug($numeroOs);
        $fileName = sprintf(
            'os_%s_%s_%s.pdf',
            $slug,
            $format === self::FORMAT_THERMAL ? '80mm' : 'a4',
            date('Ymd_His')
        );
        $filePath = $outputDir . DIRECTORY_SEPARATOR . $fileName;

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);

        if ($format === self::FORMAT_THERMAL) {
            $dompdf->setPaper([0, 0, 226.77, 6000], 'portrait');
        } else {
            $dompdf->setPaper('A4', 'portrait');
        }

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $this->decoratePdfFooter($dompdf, $context, $format);
        file_put_contents($filePath, $dompdf->output());

        return [
            'ok' => true,
            'path' => $filePath,
            'file_name' => $fileName,
            'cleanup' => true,
            'context' => $context,
        ];
    }

    public function normalizeOptions(array $options): array
    {
        $format = strtolower(trim((string) ($options['format'] ?? $options['formato'] ?? self::FORMAT_A4)));
        if (!in_array($format, [self::FORMAT_A4, self::FORMAT_THERMAL], true)) {
            $format = self::FORMAT_A4;
        }

        return [
            'format' => $format,
            'include_photos' => $this->toBool($options['include_photos'] ?? $options['incluir_fotos'] ?? false),
        ];
    }

    public function cleanupTemporaryFile(string $path): void
    {
        $safePath = trim($path);
        if ($safePath === '' || !is_file($safePath)) {
            return;
        }

        $tempRoot = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'os_print';
        $normalizedTempRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tempRoot);
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $safePath);

        if (str_starts_with($normalizedPath, $normalizedTempRoot)) {
            @unlink($safePath);
        }
    }

    private function decoratePdfFooter(\Dompdf\Dompdf $dompdf, array $context, string $format): void
    {
        $canvas = $dompdf->getCanvas();
        if ($canvas === null) {
            return;
        }

        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
        if ($font === null) {
            return;
        }

        $fontSize = $format === self::FORMAT_THERMAL ? 6.5 : 7.0;
        $leftMargin = $format === self::FORMAT_THERMAL ? 10.0 : 24.0;
        $bottomOffset = $format === self::FORMAT_THERMAL ? 12.0 : 16.0;
        $pageWidth = (float) $canvas->get_width();
        $pageHeight = (float) $canvas->get_height();
        $y = max($fontSize + 4.0, $pageHeight - $bottomOffset);
        $rightText = 'Pagina {PAGE_NUM} de {PAGE_COUNT}';
        $rightWidth = (float) $fontMetrics->getTextWidth('Pagina 999 de 999', $font, $fontSize);
        $rightX = max($leftMargin + 120.0, $pageWidth - $leftMargin - $rightWidth);
        $maxLeftWidth = max(120.0, $rightX - $leftMargin - 10.0);
        $leftText = $this->truncatePdfFooterText(
            $this->buildPdfFooterSummary($context),
            $fontMetrics,
            $font,
            $fontSize,
            $maxLeftWidth
        );
        $color = [0.39, 0.45, 0.55];

        $canvas->page_text($leftMargin, $y, $leftText, $font, $fontSize, $color);
        $canvas->page_text($rightX, $y, $rightText, $font, $fontSize, $color);
    }

    private function buildPdfFooterSummary(array $context): string
    {
        $empresaNome = trim((string) (($context['branding']['empresa_nome'] ?? '') ?: 'Assistencia Tecnica'));
        $numeroOs = trim((string) ($context['os']['numero_os'] ?? '#'));
        $generatedAt = trim((string) ($context['generatedAt'] ?? ''));

        return trim($empresaNome . ' - ' . $numeroOs . ' - Gerado em ' . $generatedAt);
    }

    private function truncatePdfFooterText(
        string $text,
        \Dompdf\FontMetrics $fontMetrics,
        $font,
        float $fontSize,
        float $maxWidth
    ): string {
        $safeText = trim($text);
        if ($safeText === '') {
            return '';
        }

        if ((float) $fontMetrics->getTextWidth($safeText, $font, $fontSize) <= $maxWidth) {
            return $safeText;
        }

        $ellipsis = '...';
        $length = function_exists('mb_strlen') ? mb_strlen($safeText, 'UTF-8') : strlen($safeText);

        while ($length > 1) {
            $length--;
            $candidate = function_exists('mb_substr')
                ? mb_substr($safeText, 0, $length, 'UTF-8') . $ellipsis
                : substr($safeText, 0, $length) . $ellipsis;

            if ((float) $fontMetrics->getTextWidth($candidate, $font, $fontSize) <= $maxWidth) {
                return $candidate;
            }
        }

        return $ellipsis;
    }

    private function buildFinancialSummary(array $os, array $itensOs): array
    {
        $totais = [
            'servicos' => 0.0,
            'pecas' => 0.0,
        ];

        foreach ($itensOs as $item) {
            $valor = (float) ($item['valor_total'] ?? 0);
            if (strtolower(trim((string) ($item['tipo'] ?? ''))) === 'peca') {
                $totais['pecas'] += $valor;
                continue;
            }

            $totais['servicos'] += $valor;
        }

        return [
            'valor_mao_obra' => (float) ($os['valor_mao_obra'] ?? $totais['servicos']),
            'valor_pecas' => (float) ($os['valor_pecas'] ?? $totais['pecas']),
            'valor_total' => (float) ($os['valor_total'] ?? ($totais['servicos'] + $totais['pecas'])),
            'desconto' => (float) ($os['desconto'] ?? 0),
            'valor_final' => (float) ($os['valor_final'] ?? 0),
            'forma_pagamento' => trim((string) ($os['forma_pagamento'] ?? '')),
            'garantia_dias' => (int) ($os['garantia_dias'] ?? 0),
            'garantia_validade' => (string) ($os['garantia_validade'] ?? ''),
        ];
    }

    private function buildClientAddress(array $cliente): string
    {
        $parts = [];
        $street = trim((string) ($cliente['endereco'] ?? ''));
        $number = trim((string) ($cliente['numero'] ?? ''));
        $complement = trim((string) ($cliente['complemento'] ?? ''));
        $district = trim((string) ($cliente['bairro'] ?? ''));
        $city = trim((string) ($cliente['cidade'] ?? ''));
        $state = trim((string) ($cliente['uf'] ?? ''));
        $zip = trim((string) ($cliente['cep'] ?? ''));

        if ($street !== '') {
            $line = $street;
            if ($number !== '') {
                $line .= ', ' . $number;
            }
            if ($complement !== '') {
                $line .= ' - ' . $complement;
            }
            $parts[] = $line;
        }

        $cityLine = trim(implode(' - ', array_filter([$district, trim(implode('/', array_filter([$city, $state])))], static fn ($item): bool => trim((string) $item) !== '')));
        if ($cityLine !== '') {
            $parts[] = $cityLine;
        }

        if ($zip !== '') {
            $parts[] = 'CEP ' . $zip;
        }

        return implode(' | ', $parts);
    }

    private function loadDefeitos(int $osId): array
    {
        $defeitos = $this->defeitoModel->getByOs($osId);

        foreach ($defeitos as &$defeito) {
            $defeito['procedimentos'] = $this->procedimentoModel->getByDefeito((int) ($defeito['defeito_id'] ?? 0));
        }
        unset($defeito);

        return $defeitos;
    }

    private function buildPhotoGroups(
        array $fotosPerfil,
        array $fotosEntrada,
        array $acessorios,
        array $estadoFisico,
        ?array $checklistEntrada
    ): array {
        $groups = [];

        if (!empty($fotosEntrada)) {
            $groups[] = [
                'key' => 'entrada',
                'label' => 'Fotos de entrada',
                'photos' => array_map(
                    static fn (array $foto, int $index): array => [
                        'url' => (string) ($foto['url'] ?? ''),
                        'label' => 'Entrada ' . ($index + 1),
                    ],
                    $fotosEntrada,
                    array_keys($fotosEntrada)
                ),
            ];
        }

        $fotosAcessorios = [];
        foreach ($acessorios as $acessorio) {
            foreach ((array) ($acessorio['fotos'] ?? []) as $index => $foto) {
                $fotosAcessorios[] = [
                    'url' => (string) ($foto['url'] ?? ''),
                    'label' => trim((string) ($acessorio['descricao'] ?? 'Acessorio')) . ' - Foto ' . ((int) $index + 1),
                ];
            }
        }
        if (!empty($fotosAcessorios)) {
            $groups[] = [
                'key' => 'acessorios',
                'label' => 'Fotos de acessorios',
                'photos' => $fotosAcessorios,
            ];
        }

        if (!empty($fotosPerfil)) {
            $groups[] = [
                'key' => 'perfil',
                'label' => 'Fotos de perfil do equipamento',
                'photos' => array_map(
                    static function (array $foto, int $index): array {
                        $label = 'Perfil ' . ($index + 1);
                        if ((int) ($foto['is_principal'] ?? 0) === 1) {
                            $label .= ' (principal)';
                        }

                        return [
                            'url' => (string) ($foto['url'] ?? ''),
                            'label' => $label,
                        ];
                    },
                    $fotosPerfil,
                    array_keys($fotosPerfil)
                ),
            ];
        }

        $fotosEstadoFisico = [];
        foreach ($estadoFisico as $item) {
            foreach ((array) ($item['fotos'] ?? []) as $index => $foto) {
                $fotosEstadoFisico[] = [
                    'url' => (string) ($foto['url'] ?? ''),
                    'label' => trim((string) ($item['descricao_dano'] ?? 'Estado fisico')) . ' - Foto ' . ((int) $index + 1),
                ];
            }
        }
        if (!empty($fotosEstadoFisico)) {
            $groups[] = [
                'key' => 'estado_fisico',
                'label' => 'Fotos de estado fisico',
                'photos' => $fotosEstadoFisico,
            ];
        }

        $fotosChecklist = [];
        foreach ((array) ($checklistEntrada['itens'] ?? []) as $itemChecklist) {
            foreach ((array) ($itemChecklist['fotos'] ?? []) as $index => $foto) {
                $fotosChecklist[] = [
                    'url' => (string) ($foto['url'] ?? ''),
                    'label' => trim((string) ($itemChecklist['descricao'] ?? 'Checklist')) . ' - Foto ' . ((int) $index + 1),
                ];
            }
        }
        if (!empty($fotosChecklist)) {
            $groups[] = [
                'key' => 'checklist',
                'label' => 'Fotos do checklist',
                'photos' => $fotosChecklist,
            ];
        }

        return $groups;
    }

    private function loadAcessorios(int $osId, string $numeroOs): array
    {
        $acessorios = $this->acessorioModel->where('os_id', $osId)->orderBy('id', 'ASC')->findAll();

        foreach ($acessorios as &$acessorio) {
            $acessorio['valores_resumo'] = $this->flattenJsonValues((string) ($acessorio['valores'] ?? ''));
            $acessorio['fotos'] = [];

            $fotos = $this->fotoAcessorioModel
                ->where('acessorio_id', (int) ($acessorio['id'] ?? 0))
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($fotos as $foto) {
                $url = $this->resolveAccessoryPhotoUrl($numeroOs, (string) ($foto['arquivo'] ?? ''));
                if ($url === null) {
                    continue;
                }

                $acessorio['fotos'][] = [
                    'id' => (int) ($foto['id'] ?? 0),
                    'url' => $this->appendAssetVersion($url, (string) ($foto['id'] ?? '')),
                ];
            }
        }
        unset($acessorio);

        return $acessorios;
    }

    private function loadEstadoFisico(int $osId, string $numeroOs): array
    {
        $itens = $this->estadoFisicoModel->where('os_id', $osId)->orderBy('id', 'ASC')->findAll();

        foreach ($itens as &$item) {
            $item['valores_resumo'] = $this->flattenJsonValues((string) ($item['valores'] ?? ''));
            $item['fotos'] = [];

            $fotos = $this->fotoEstadoFisicoModel
                ->where('estado_fisico_id', (int) ($item['id'] ?? 0))
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($fotos as $foto) {
                $url = $this->resolveEstadoFisicoPhotoUrl($numeroOs, (string) ($foto['arquivo'] ?? ''));
                if ($url === null) {
                    continue;
                }

                $item['fotos'][] = [
                    'id' => (int) ($foto['id'] ?? 0),
                    'url' => $this->appendAssetVersion($url, (string) ($foto['id'] ?? '')),
                ];
            }
        }
        unset($item);

        return $itens;
    }

    private function loadFotosEntrada(int $osId): array
    {
        $fotos = $this->osFotoModel
            ->where('os_id', $osId)
            ->where('tipo', 'recepcao')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($fotos as &$foto) {
            $foto['url'] = $this->appendAssetVersion(
                $this->resolveOsEntradaFotoPublicUrl((string) ($foto['arquivo'] ?? '')),
                (string) ($foto['id'] ?? '')
            );
        }
        unset($foto);

        return $fotos;
    }

    private function loadFotosPerfil(int $equipamentoId): array
    {
        if ($equipamentoId <= 0) {
            return [];
        }

        $fotos = $this->equipamentoFotoModel
            ->where('equipamento_id', $equipamentoId)
            ->orderBy('is_principal', 'DESC')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($fotos as &$foto) {
            $foto['url'] = $this->appendAssetVersion(
                $this->resolveEquipamentoFotoPublicUrl((string) ($foto['arquivo'] ?? '')),
                (string) ($foto['id'] ?? '')
            );
        }
        unset($foto);

        return $fotos;
    }

    private function resolvePrimaryPhoto(array $fotos): ?array
    {
        foreach ($fotos as $foto) {
            if ((int) ($foto['is_principal'] ?? 0) === 1) {
                return $foto;
            }
        }

        return $fotos[0] ?? null;
    }

    private function loadChecklistEntrada(int $osId, array $os, array $equipamento): ?array
    {
        $equipamentoId = (int) ($os['equipamento_id'] ?? 0);
        $tipoId = (int) ($equipamento['tipo_id'] ?? 0);
        if ($equipamentoId <= 0 || $tipoId <= 0) {
            return null;
        }

        try {
            $payload = (new ChecklistService())->getPayloadForOs(
                $osId,
                'entrada',
                $tipoId,
                (string) ($os['numero_os'] ?? '')
            );
            $payload['tipo_equipamento_nome'] = trim((string) ($equipamento['tipo_nome'] ?? ''));
            return $payload;
        } catch (\Throwable $e) {
            log_message('error', '[OS Print] Falha ao carregar checklist da OS ' . $osId . ': ' . $e->getMessage());
            return null;
        }
    }

    private function loadLatestOrcamento(int $osId): ?array
    {
        if ($osId <= 0) {
            return null;
        }

        $db = Database::connect();
        if (!$db->tableExists('orcamentos')) {
            return null;
        }

        $orcamento = $db->table('orcamentos')
            ->select('orcamentos.id, orcamentos.os_id, orcamentos.numero, orcamentos.status, orcamentos.tipo_orcamento, orcamentos.subtotal, orcamentos.desconto, orcamentos.acrescimo, orcamentos.total, orcamentos.validade_data, orcamentos.prazo_execucao, orcamentos.created_at, orcamentos.updated_at, orcamentos.telefone_contato, orcamentos.email_contato')
            ->where('os_id', $osId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getFirstRow('array');

        if (!$orcamento) {
            return null;
        }

        $orcamentoModel = new OrcamentoModel();
        $status = (string) ($orcamento['status'] ?? '');
        $tipo = (string) ($orcamento['tipo_orcamento'] ?? '');

        return [
            'id' => (int) ($orcamento['id'] ?? 0),
            'numero' => (string) ($orcamento['numero'] ?? ''),
            'status' => $status,
            'status_label' => $orcamentoModel->statusLabels()[$status] ?? $this->humanizeText($status),
            'tipo_orcamento' => $tipo,
            'tipo_label' => $orcamentoModel->tipoLabels()[$tipo] ?? $this->humanizeText($tipo),
            'subtotal' => (float) ($orcamento['subtotal'] ?? 0),
            'desconto' => (float) ($orcamento['desconto'] ?? 0),
            'acrescimo' => (float) ($orcamento['acrescimo'] ?? 0),
            'total' => (float) ($orcamento['total'] ?? 0),
            'validade_data' => (string) ($orcamento['validade_data'] ?? ''),
            'prazo_execucao' => (string) ($orcamento['prazo_execucao'] ?? ''),
            'telefone_contato' => (string) ($orcamento['telefone_contato'] ?? ''),
            'email_contato' => (string) ($orcamento['email_contato'] ?? ''),
        ];
    }

    private function summarizeOrcamentoItems(int $orcamentoId): array
    {
        $db = Database::connect();
        if ($orcamentoId <= 0 || !$db->tableExists('orcamento_itens')) {
            return [
                'items' => [],
                'groups' => [],
                'total_items' => 0,
                'total_quantity' => 0.0,
            ];
        }

        $items = (new OrcamentoItemModel())->byOrcamento($orcamentoId);
        $groups = [];
        $totalQuantity = 0.0;

        foreach ($items as &$item) {
            $tipoKey = strtolower(trim((string) ($item['tipo_item'] ?? 'item')));
            if ($tipoKey === '') {
                $tipoKey = 'item';
            }

            $tipoLabel = $this->resolveOrcamentoItemTypeLabel($tipoKey);
            $item['tipo_item_label'] = $tipoLabel;
            $totalQuantity += (float) ($item['quantidade'] ?? 0);

            if (!isset($groups[$tipoKey])) {
                $groups[$tipoKey] = [
                    'key' => $tipoKey,
                    'label' => $tipoLabel,
                    'count' => 0,
                    'quantity' => 0.0,
                    'total' => 0.0,
                ];
            }

            $groups[$tipoKey]['count']++;
            $groups[$tipoKey]['quantity'] += (float) ($item['quantidade'] ?? 0);
            $groups[$tipoKey]['total'] += (float) ($item['total'] ?? 0);
        }
        unset($item);

        return [
            'items' => $items,
            'groups' => array_values($groups),
            'total_items' => count($items),
            'total_quantity' => $totalQuantity,
        ];
    }

    private function resolveOrcamentoItemTypeLabel(string $typeKey): string
    {
        $normalized = strtolower(trim($typeKey));

        return match ($normalized) {
            'servico', 'servicos' => 'Servico',
            'peca', 'pecas' => 'Peca',
            'pacote', 'pacotes', 'pacote_servico', 'pacote_servicos' => 'Pacote',
            'diverso', 'outro', 'outros' => 'Diverso',
            default => $this->humanizeText($normalized),
        };
    }

    private function loadNotasLegadas(int $osId): array
    {
        if (!$this->notaLegadaModel->db->tableExists('os_notas_legadas')) {
            return [];
        }

        return $this->notaLegadaModel
            ->where('os_id', $osId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    private function splitTextLines(string $value): array
    {
        $parts = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $parts = array_map(static fn (string $item): string => trim($item), $parts);

        return array_values(array_filter($parts, static fn (string $item): bool => $item !== ''));
    }

    private function flattenJsonValues(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        $lines = [];
        foreach ($decoded as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_values(array_filter(array_map(
                    static fn ($item): string => trim((string) $item),
                    $value
                ), static fn (string $item): bool => $item !== '')));
            } elseif (is_bool($value)) {
                $value = $value ? 'Sim' : 'Nao';
            } else {
                $value = trim((string) $value);
            }

            if ($value === '') {
                continue;
            }

            $label = $this->humanizeText((string) $key);
            $lines[] = $label !== '' ? ($label . ': ' . $value) : $value;
        }

        return $lines;
    }

    private function resolveOsStatusLabel(string $status): string
    {
        $safeStatus = trim($status);
        if ($safeStatus === '') {
            return '-';
        }

        if ($this->statusModel->db->tableExists('os_status')) {
            $row = $this->statusModel->byCode($safeStatus);
            $nome = trim((string) ($row['nome'] ?? ''));
            if ($nome !== '') {
                return $nome;
            }
        }

        return $this->humanizeText($safeStatus);
    }

    private function resolveAccessoryPhotoUrl(string $numeroOs, string $fileName): ?string
    {
        $cleanFileName = trim($fileName);
        if ($cleanFileName === '') {
            return null;
        }

        foreach ($this->buildAccessoryFolderCandidates($numeroOs) as $relativeFolder) {
            $candidatePath = FCPATH . $relativeFolder . $cleanFileName;
            if (is_file($candidatePath)) {
                return $this->buildImageDataUri($candidatePath);
            }
        }

        return null;
    }

    private function resolveEstadoFisicoPhotoUrl(string $numeroOs, string $fileName): ?string
    {
        $cleanFileName = trim($fileName);
        if ($cleanFileName === '') {
            return null;
        }

        $slug = $this->slug($numeroOs);
        $relativeFolder = 'uploads/estado_fisico/OS_' . $slug . '/';
        $candidatePath = FCPATH . $relativeFolder . $cleanFileName;

        if (is_file($candidatePath)) {
            return $this->buildImageDataUri($candidatePath);
        }

        return null;
    }

    private function buildAccessoryFolderCandidates(string $numeroOs): array
    {
        $slug = $this->slug($numeroOs);

        return [
            'uploads/acessorios/' . $slug . '/',
            'uploads/acessorios/OS_' . $slug . '/',
        ];
    }

    private function resolveEquipamentoFotoPublicUrl(string $arquivo): string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        if ($arquivo === '') {
            return $this->missingImageDataUri();
        }

        $arquivoFs = str_replace('/', DIRECTORY_SEPARATOR, $arquivo);
        $basename = basename($arquivo);
        $candidates = [
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . $arquivoFs,
                'url' => base_url('uploads/equipamentos_perfil/' . $arquivo),
            ],
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . $basename,
                'url' => base_url('uploads/equipamentos_perfil/' . $basename),
            ],
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos' . DIRECTORY_SEPARATOR . $basename,
                'url' => base_url('uploads/equipamentos/' . $basename),
            ],
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate['path'])) {
                return $this->buildImageDataUri($candidate['path']);
            }
        }

        return $this->missingImageDataUri();
    }

    private function resolveOsEntradaFotoPublicUrl(string $arquivo): string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        if ($arquivo === '') {
            return $this->missingImageDataUri();
        }

        $arquivoFs = str_replace('/', DIRECTORY_SEPARATOR, $arquivo);
        $basename = basename($arquivo);
        $candidates = [
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'os_anormalidades' . DIRECTORY_SEPARATOR . $arquivoFs,
                'url' => base_url('uploads/os_anormalidades/' . $arquivo),
            ],
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'os_anormalidades' . DIRECTORY_SEPARATOR . $basename,
                'url' => base_url('uploads/os_anormalidades/' . $basename),
            ],
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'os' . DIRECTORY_SEPARATOR . $basename,
                'url' => base_url('uploads/os/' . $basename),
            ],
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate['path'])) {
                return $this->buildImageDataUri($candidate['path']);
            }
        }

        return $this->missingImageDataUri();
    }

    private function buildImageDataUri(string $absolutePath): string
    {
        if (!is_file($absolutePath)) {
            return $this->missingImageDataUri();
        }

        $mime = mime_content_type($absolutePath);
        if (!is_string($mime) || trim($mime) === '') {
            $mime = 'image/jpeg';
        }

        $content = @file_get_contents($absolutePath);
        if ($content === false) {
            return $this->missingImageDataUri();
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function appendAssetVersion(string $url, string $version = ''): string
    {
        if ($url === '' || str_starts_with($url, 'data:') || trim($version) === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
    }

    private function missingImageDataUri(): string
    {
        static $uri = null;
        if ($uri !== null) {
            return $uri;
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="180" height="120" viewBox="0 0 180 120"><rect width="180" height="120" rx="10" fill="#eef2ff"/><rect x="62" y="34" width="56" height="36" rx="6" fill="#c7d2fe"/><circle cx="90" cy="52" r="10" fill="#818cf8"/><text x="90" y="96" text-anchor="middle" font-size="12" fill="#64748b">sem foto</text></svg>';
        $uri = 'data:image/svg+xml;base64,' . base64_encode($svg);

        return $uri;
    }

    private function humanizeText(string $value): string
    {
        $value = trim(str_replace('_', ' ', $value));
        if ($value === '') {
            return '';
        }

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    private function slug(string $value): string
    {
        $normalized = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
        $normalized = is_string($normalized) && $normalized !== '' ? $normalized : $value;
        $normalized = strtolower(trim($normalized));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?: 'os';

        return trim($normalized, '_') ?: 'os';
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(
            strtolower(trim((string) $value)),
            ['1', 'true', 'yes', 'sim', 'on'],
            true
        );
    }
}
