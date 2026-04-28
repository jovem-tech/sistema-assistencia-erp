<?php
$printOptions = is_array($printOptions ?? null) ? $printOptions : [];
$printFormat = (string) ($printOptions['format'] ?? 'a4');
$includePhotos = !empty($printOptions['include_photos']);
$isThermal = $printFormat === '80mm';
$branding = is_array($branding ?? null) ? $branding : [];
$cliente = is_array($cliente ?? null) ? $cliente : [];
$equipamento = is_array($equipamento ?? null) ? $equipamento : [];
$itensOs = is_array($itensOs ?? null) ? $itensOs : [];
$defeitos = is_array($defeitos ?? null) ? $defeitos : [];
$acessorios = is_array($acessorios ?? null) ? $acessorios : [];
$estadoFisico = is_array($estadoFisico ?? null) ? $estadoFisico : [];
$procedimentosExecutados = is_array($procedimentosExecutados ?? null) ? $procedimentosExecutados : [];
$notasLegadas = is_array($notasLegadas ?? null) ? $notasLegadas : [];
$orcamentoResumo = is_array($orcamentoResumo ?? null) ? $orcamentoResumo : ['items' => []];
$photoGroups = is_array($photoGroups ?? null) ? $photoGroups : [];
$resumoFinanceiro = is_array($resumoFinanceiro ?? null) ? $resumoFinanceiro : [];
$statusLabel = trim((string) ($statusLabel ?? ''));
$estadoFluxoLabel = trim((string) ($estadoFluxoLabel ?? ''));
$formatLabel = trim((string) ($formatLabel ?? ($isThermal ? 'Bobina 80mm' : 'Folha A4')));
$clienteEnderecoCompleto = trim((string) ($clienteEnderecoCompleto ?? ''));
$generatedAt = trim((string) ($generatedAt ?? date('d/m/Y H:i:s')));
$fotoPerfilPrincipal = is_array($fotoPerfilPrincipal ?? null) ? $fotoPerfilPrincipal : null;
$orcamento = is_array($orcamento ?? null) ? $orcamento : null;
$checklistEntrada = is_array($checklistEntrada ?? null) ? $checklistEntrada : null;
$checklistItems = array_values((array) ($checklistEntrada['itens'] ?? []));
$showEquipmentPhotoSlot = !$isThermal && $includePhotos;
$orcamentoItems = array_values((array) ($orcamentoResumo['items'] ?? []));
$renderMode = trim((string) ($renderMode ?? 'preview'));
$autoPrint = !empty($autoPrint);
$renderablePhotoGroups = array_values(array_filter(array_map(
    static function (array $group): ?array {
        $photos = array_values(array_filter(
            (array) ($group['photos'] ?? []),
            static fn (array $photo): bool => !empty($photo['url'])
        ));

        if ($photos === []) {
            return null;
        }

        $group['photos'] = $photos;
        return $group;
    },
    $photoGroups
)));
$hasPhotoPage = !$isThermal && $includePhotos && $renderablePhotoGroups !== [];
$a4TotalPages = $isThermal ? 1 : ($hasPhotoPage ? 3 : 2);
$usePreviewPageShell = !$isThermal && $renderMode !== 'pdf';

$displayValue = static function ($value, string $fallback = '-'): string {
    $text = trim((string) $value);
    return $text !== '' ? $text : $fallback;
};

$renderDateTime = static function ($value, bool $withTime = true): string {
    $text = trim((string) $value);
    if ($text === '') {
        return '-';
    }

    $timestamp = strtotime($text);
    if ($timestamp === false) {
        return '-';
    }

    return $withTime ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp);
};

$renderList = static function (array $items): string {
    $values = array_values(array_filter(array_map(
        static fn ($item): string => trim((string) $item),
        $items
    ), static fn (string $item): bool => $item !== ''));

    return $values !== [] ? implode(' | ', $values) : '-';
};

$placeholderInitials = static function (string $value): string {
    $words = preg_split('/\s+/', trim($value)) ?: [];
    $letters = [];

    foreach ($words as $word) {
        $clean = trim($word);
        if ($clean === '') {
            continue;
        }
        $letters[] = mb_strtoupper(mb_substr($clean, 0, 1, 'UTF-8'), 'UTF-8');
        if (count($letters) >= 2) {
            break;
        }
    }

    return $letters !== [] ? implode('', $letters) : 'JT';
};

$empresaNome = trim((string) ($branding['empresa_nome'] ?? 'Assistencia Tecnica'));
$empresaTelefone = trim((string) ($branding['empresa_telefone'] ?? ''));
$empresaEmail = trim((string) ($branding['empresa_email'] ?? ''));
$empresaEndereco = trim((string) ($branding['empresa_endereco'] ?? ''));
$logoDataUri = trim((string) ($branding['header_logo_data_uri'] ?? ''));

$clienteTelefones = array_values(array_filter([
    trim((string) ($cliente['telefone1'] ?? $os['cliente_telefone'] ?? '')),
    trim((string) ($cliente['telefone2'] ?? '')),
    trim((string) ($cliente['telefone_contato'] ?? '')),
], static fn (string $value): bool => $value !== ''));

$garantiaTexto = 'Nao informada';
if ((int) ($resumoFinanceiro['garantia_dias'] ?? 0) > 0) {
    $garantiaTexto = (int) $resumoFinanceiro['garantia_dias'] . ' dias';
    if (!empty($resumoFinanceiro['garantia_validade'])) {
        $garantiaTexto .= ' ate ' . formatDate($resumoFinanceiro['garantia_validade']);
    }
} elseif (!empty($resumoFinanceiro['garantia_validade'])) {
    $garantiaTexto = 'Valida ate ' . formatDate($resumoFinanceiro['garantia_validade']);
}

$observacoesCliente = trim((string) ($os['observacoes_cliente'] ?? ''));
$observacoesInternas = trim((string) ($os['observacoes_internas'] ?? ''));
$relatoCliente = trim((string) ($os['relato_cliente'] ?? ''));
$diagnosticoTecnico = trim((string) ($os['diagnostico_tecnico'] ?? ''));
$solucaoAplicada = trim((string) ($os['solucao_aplicada'] ?? ''));
$procedimentosTexto = $procedimentosExecutados !== [] ? implode("\n", $procedimentosExecutados) : 'Nenhum procedimento registrado.';

$equipamentoResumo = trim((string) (($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? '')));
$equipamentoTipo = trim((string) ($os['equip_tipo'] ?? $equipamento['tipo_nome'] ?? ''));
$equipamentoCor = trim((string) ($equipamento['cor'] ?? ''));
$equipamentoImei = trim((string) ($equipamento['imei'] ?? ''));
$equipamentoSerie = trim((string) ($os['equip_serie'] ?? $equipamento['numero_serie'] ?? ''));
$equipamentoSenha = trim((string) ($equipamento['senha_acesso'] ?? ''));
$equipmentPhotoUrl = !empty($fotoPerfilPrincipal['url']) ? trim((string) $fotoPerfilPrincipal['url']) : '';

$companyHeaderMeta = array_values(array_filter([
    $empresaTelefone !== '' ? 'Tel: ' . $empresaTelefone : '',
    $empresaEmail !== '' ? 'E-mail: ' . $empresaEmail : '',
    $empresaEndereco,
], static fn (string $item): bool => $item !== ''));

$companyHeaderMetaText = $companyHeaderMeta !== [] ? implode(' | ', $companyHeaderMeta) : '';
$footerSummaryText = $displayValue($empresaNome) . ' - ' . (string) ($os['numero_os'] ?? '#') . ' - Gerado em ' . $displayValue($generatedAt);
$tecnicoResponsavel = $displayValue($os['tecnico_nome'] ?? '', 'Nao atribuido');
$clienteTelefonePrincipal = $displayValue($clienteTelefones[0] ?? ($os['cliente_telefone'] ?? ''));

$checklistStatusMeta = static function (string $status): array {
    $normalized = strtolower(trim($status));
    return match ($normalized) {
        'ok' => ['label' => 'OK', 'class' => 'ok', 'marker' => 'OK'],
        'danificado', 'discrepancia', 'com discrepancia' => ['label' => 'Danificado', 'class' => 'warn', 'marker' => '!'],
        'ausente' => ['label' => 'Ausente', 'class' => 'warn', 'marker' => '!'],
        default => ['label' => $status !== '' ? $status : 'Pendente', 'class' => 'warn', 'marker' => '!'],
    };
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impress&atilde;o da OS <?= esc((string) ($os['numero_os'] ?? '#')) ?></title>
    <style>
        @page {
            size: <?= $isThermal ? '80mm auto' : 'A4 portrait' ?>;
            margin: <?= $isThermal ? '4mm' : '10mm' ?>;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.35;
            color: #0f172a;
            background: #eef3f8;
        }

        .print-shell {
            width: 100%;
            padding: 12px;
        }

        .document-sheet {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px;
        }

        .page-shell {
            width: 100%;
            border-collapse: collapse;
        }

        .page-shell-break {
            page-break-before: always;
            margin-top: 10px;
        }

        .page-shell-content {
            vertical-align: top;
        }

        .pdf-hard-break {
            display: none;
        }

        .header-table,
        .info-table,
        .grid-table,
        .dual-table,
        .financial-table,
        .items-table,
        .orcamento-table,
        .photo-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td,
        .info-table td,
        .grid-table td,
        .dual-table td,
        .financial-table td,
        .items-table td,
        .items-table th,
        .orcamento-table td,
        .orcamento-table th,
        .photo-table td {
            vertical-align: top;
        }

        .company-header {
            background: #1a2b4a;
            color: #ffffff;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 10px;
        }

        .logo-slot {
            width: 48px;
        }

        .logo-badge {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            text-align: center;
            line-height: 42px;
            font-weight: 700;
            font-size: 12pt;
            overflow: hidden;
        }

        .logo-badge img {
            width: 100%;
            height: 100%;
            display: block;
        }

        .company-title {
            font-size: 16pt;
            font-weight: 700;
            line-height: 1.1;
            margin: 0;
        }

        .company-meta {
            font-size: 8pt;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 4px;
        }

        .info-card {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .os-title {
            margin: 0;
            font-size: 16pt;
            line-height: 1.1;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .os-subtitle {
            margin: 4px 0 0;
            font-size: 8pt;
            color: #64748b;
        }

        .badge-wrap {
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            margin-left: 6px;
            margin-bottom: 6px;
            font-size: 7pt;
            font-weight: 700;
            color: #ffffff;
        }

        .status-badge.status {
            background: #ea580c;
        }

        .status-badge.flow {
            background: #7c3aed;
        }

        .status-badge.photos {
            background: #2563ab;
        }

        .section {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .section-title {
            background: #1a2b4a;
            color: #ffffff;
            border-radius: 10px 10px 0 0;
            padding: 8px 10px;
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .section-body {
            border: 1px solid #cbd5e1;
            border-top: 0;
            border-radius: 0 0 10px 10px;
            background: #ffffff;
            padding: 10px;
        }

        .section-note {
            margin: 0 0 8px;
            font-size: 8pt;
            color: #64748b;
        }

        .field-cell {
            padding-right: 12px;
            padding-bottom: 8px;
        }

        .field-label {
            display: block;
            margin-bottom: 3px;
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .field-value {
            display: block;
            font-size: 9.5pt;
            color: #0f172a;
            font-weight: 700;
            word-break: break-word;
        }

        .divider {
            border-top: 1px solid #cbd5e1;
            margin: 8px 0 10px;
        }

        .equipment-photo-cell {
            width: 115px;
            padding-right: 12px;
        }

        .equipment-photo-box {
            width: 100%;
            min-height: 92px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #e8f0f8;
            overflow: hidden;
            text-align: center;
        }

        .equipment-photo-box img {
            width: 100%;
            height: auto;
            display: block;
        }

        .equipment-photo-placeholder {
            padding: 34px 8px;
            font-size: 7pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }

        .text-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            padding: 10px;
            min-height: 100%;
        }

        .text-box-title {
            margin: 0 0 6px;
            font-size: 8pt;
            color: #1a2b4a;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .sub-title {
            margin: 8px 0 4px;
            font-size: 7pt;
            color: #2563ab;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .text-content {
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 8.5pt;
            color: #0f172a;
            line-height: 1.5;
        }

        .checklist-item {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            padding: 8px 10px;
            margin-bottom: 8px;
        }

        .checklist-marker {
            display: inline-block;
            width: 18px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            border-radius: 999px;
            font-size: 7pt;
            font-weight: 700;
            margin-right: 8px;
            vertical-align: middle;
        }

        .checklist-text {
            display: inline-block;
            width: 64%;
            vertical-align: middle;
            font-size: 8.5pt;
            color: #0f172a;
            font-weight: 600;
        }

        .checklist-badge {
            display: inline-block;
            vertical-align: middle;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 7pt;
            font-weight: 700;
        }

        .is-ok .checklist-marker,
        .is-ok .checklist-badge {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #86efac;
        }

        .is-warn .checklist-marker,
        .is-warn .checklist-badge {
            background: #ffedd5;
            color: #ea580c;
            border: 1px solid #fdba74;
        }

        .soft-card {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #e8f0f8;
            padding: 9px 10px;
            margin-bottom: 8px;
        }

        .soft-card strong {
            display: block;
            margin-bottom: 4px;
            font-size: 8.5pt;
            color: #0f172a;
        }

        .soft-card p,
        .soft-card small {
            margin: 0;
            font-size: 8pt;
            color: #64748b;
            line-height: 1.45;
        }

        .pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            background: #dbeafe;
            color: #2563ab;
            font-size: 7pt;
            font-weight: 700;
            margin-top: 4px;
        }

        .financial-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #e8f0f8;
            padding: 10px;
            margin-right: 8px;
        }

        .financial-box .field-value {
            margin-top: 4px;
            font-size: 10pt;
        }

        .financial-box.total-final .field-value {
            color: #2563ab;
        }

        .table-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .table-list th,
        .table-list td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            text-align: left;
            font-size: 8pt;
            vertical-align: top;
        }

        .table-list th {
            background: #e8f0f8;
            color: #1a2b4a;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .notes-list .soft-card {
            background: #f8fafc;
        }

        .photo-group {
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }

        .photo-group-title {
            background: #e8f0f8;
            color: #1a2b4a;
            padding: 8px 10px;
            border-bottom: 1px solid #cbd5e1;
            font-size: 8pt;
            font-weight: 700;
        }

        .photo-card {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            background: #e8f0f8;
            margin: 10px;
        }

        .photo-card img {
            width: 100%;
            height: auto;
            display: block;
        }

        .photo-card-label {
            padding: 8px;
            font-size: 7.5pt;
            color: #64748b;
        }

        .doc-footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            font-size: 7.5pt;
            color: #64748b;
        }

        .page-counter {
            text-align: right;
        }

        .page-counter-text {
            display: inline-block;
            min-width: 120px;
            text-align: right;
        }

        .a4-page-break {
            page-break-before: always;
        }

        .a4-photos-page-break {
            page-break-before: always;
        }

        .is-thermal .print-shell {
            padding: 0;
        }

        .is-thermal .document-sheet {
            border-radius: 0;
            border-left: 0;
            border-right: 0;
        }

        .is-thermal .page-shell-break {
            page-break-before: auto;
            margin-top: 0;
        }

        .is-thermal .badge-wrap {
            text-align: left;
        }

        .is-thermal .status-badge {
            margin-top: 6px;
            margin-left: 0;
            margin-right: 6px;
        }

        .is-thermal .grid-table td,
        .is-thermal .dual-table td,
        .is-thermal .financial-table td,
        .is-thermal .photo-table td {
            display: block;
            width: 100% !important;
        }

        .is-thermal .equipment-photo-cell {
            padding-right: 0;
            padding-bottom: 10px;
        }

        .is-thermal .checklist-text {
            width: 66%;
        }

        .is-thermal .a4-page-break,
        .is-thermal .a4-photos-page-break {
            page-break-before: auto;
        }

        .thermal-receipt {
            width: 100%;
            max-width: 72mm;
            margin: 0 auto;
            color: #111827;
            font-family: "DejaVu Sans Mono", "Courier New", monospace;
            font-size: 8pt;
            line-height: 1.45;
        }

        .thermal-center {
            text-align: center;
        }

        .thermal-company-name {
            margin: 0;
            font-size: 11pt;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .thermal-meta,
        .thermal-footer {
            font-size: 7pt;
            color: #374151;
        }

        .thermal-divider {
            margin: 7px 0;
            border-top: 1px solid #111827;
        }

        .thermal-divider--double {
            position: relative;
            margin: 8px 0 10px;
            border-top: 1px solid #111827;
        }

        .thermal-divider--double::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 3px;
            border-top: 1px solid #111827;
        }

        .thermal-os-label {
            margin: 0;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .thermal-os-number {
            margin: 4px 0 0;
            font-size: 14pt;
            font-weight: 700;
            line-height: 1.15;
        }

        .thermal-status {
            margin: 7px 0 0;
            font-size: 7.2pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .thermal-section {
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .thermal-section-title {
            margin: 0 0 6px;
            padding: 3px 0;
            border-top: 1px solid #111827;
            border-bottom: 1px solid #111827;
            font-size: 7.4pt;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .thermal-row,
        .thermal-item-row {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .thermal-label,
        .thermal-value,
        .thermal-item-label,
        .thermal-item-value {
            display: table-cell;
            vertical-align: top;
        }

        .thermal-label,
        .thermal-item-label {
            width: 40%;
            padding-right: 6px;
            font-size: 7pt;
            color: #374151;
            text-transform: uppercase;
        }

        .thermal-value,
        .thermal-item-value {
            text-align: right;
            font-weight: 700;
            word-break: break-word;
        }

        .thermal-block-label {
            display: block;
            margin: 7px 0 2px;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .thermal-text {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .thermal-checklist-item,
        .thermal-budget-item {
            padding: 5px 0;
            border-bottom: 1px dashed #9ca3af;
        }

        .thermal-checklist-item:last-child,
        .thermal-budget-item:last-child {
            border-bottom: 0;
        }

        .thermal-checklist-marker {
            display: inline-block;
            min-width: 26px;
            font-weight: 700;
        }

        .thermal-budget-item-title {
            margin: 0 0 3px;
            font-weight: 700;
            word-break: break-word;
        }

        .thermal-budget-item-meta {
            margin: 0;
            font-size: 7pt;
            color: #374151;
        }

        .thermal-signature {
            margin-top: 14px;
            padding-top: 16px;
            border-top: 1px solid #111827;
            text-align: center;
            font-size: 7pt;
            text-transform: uppercase;
        }

        .render-mode-pdf .doc-footer {
            display: none;
        }

        .render-mode-pdf {
            background: #ffffff;
        }

        .render-mode-pdf .print-shell {
            padding: 0;
        }

        .render-mode-pdf .document-sheet {
            border: 0;
            border-radius: 0;
            padding: 0;
        }

        .render-mode-pdf .page-shell-break {
            margin-top: 0;
        }

        .render-mode-pdf .section {
            page-break-inside: auto;
        }

        .render-mode-pdf .pdf-hard-break {
            display: block;
            height: 0;
            margin: 0;
            page-break-before: always;
        }

        .render-mode-pdf .section-body,
        .render-mode-pdf .soft-card,
        .render-mode-pdf .checklist-item,
        .render-mode-pdf .photo-card,
        .render-mode-pdf .table-list tr,
        .render-mode-pdf .grid-table tr,
        .render-mode-pdf .dual-table tr,
        .render-mode-pdf .financial-table tr,
        .render-mode-pdf .photo-table tr,
        .render-mode-pdf .orcamento-table tr,
        .render-mode-pdf .items-table tr {
            page-break-inside: avoid;
        }

        .render-mode-pdf .a4-page-break,
        .render-mode-pdf .a4-photos-page-break {
            page-break-before: auto;
        }

        .render-mode-pdf .page-shell-break {
            page-break-before: always;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .print-shell {
                padding: 0;
            }

            .document-sheet {
                border: 0;
                border-radius: 0;
            }

            .page-shell-break {
                margin-top: 0;
            }

            .page-shell .a4-page-break,
            .page-shell .a4-photos-page-break {
                page-break-before: auto;
            }
        }
    </style>
</head>
<body class="<?= $isThermal ? 'is-thermal' : 'is-a4' ?> <?= $renderMode === 'pdf' ? 'render-mode-pdf' : 'render-mode-preview' ?>">
    <div class="print-shell">
        <div class="document-sheet">
            <?php if ($isThermal): ?>
                <div class="thermal-receipt">
                    <div class="thermal-center">
                        <p class="thermal-company-name"><?= esc($displayValue($empresaNome)) ?></p>
                        <?php if ($empresaTelefone !== ''): ?>
                            <div class="thermal-meta"><?= esc($empresaTelefone) ?></div>
                        <?php endif; ?>
                        <?php if ($empresaEmail !== ''): ?>
                            <div class="thermal-meta"><?= esc($empresaEmail) ?></div>
                        <?php endif; ?>
                        <?php if ($empresaEndereco !== ''): ?>
                            <div class="thermal-meta"><?= esc($empresaEndereco) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="thermal-divider--double"></div>

                    <div class="thermal-center">
                        <p class="thermal-os-label">Ordem de Servico</p>
                        <p class="thermal-os-number"><?= esc((string) ($os['numero_os'] ?? '#')) ?></p>
                        <div class="thermal-status">[ <?= esc($displayValue($statusLabel, 'Sem status')) ?> ]</div>
                        <div class="thermal-status">[ Fluxo: <?= esc($displayValue($estadoFluxoLabel, 'Sem fluxo')) ?> ]</div>
                    </div>

                    <div class="thermal-section">
                        <div class="thermal-row">
                            <span class="thermal-label">Abertura</span>
                            <span class="thermal-value"><?= esc($renderDateTime($os['data_abertura'] ?? '', true)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Previsao</span>
                            <span class="thermal-value"><?= esc($renderDateTime($os['data_previsao'] ?? '', false)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Entrega</span>
                            <span class="thermal-value"><?= esc($renderDateTime($os['data_entrega'] ?? '', false)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Gerado em</span>
                            <span class="thermal-value"><?= esc($displayValue($generatedAt)) ?></span>
                        </div>
                    </div>

                    <section class="thermal-section">
                        <h2 class="thermal-section-title">Tecnico</h2>
                        <div class="thermal-row">
                            <span class="thermal-label">Responsavel</span>
                            <span class="thermal-value"><?= esc($tecnicoResponsavel) ?></span>
                        </div>
                    </section>

                    <section class="thermal-section">
                        <h2 class="thermal-section-title">Cliente</h2>
                        <div class="thermal-row">
                            <span class="thermal-label">Nome</span>
                            <span class="thermal-value"><?= esc($displayValue($os['cliente_nome'] ?? '')) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Telefone</span>
                            <span class="thermal-value"><?= esc($clienteTelefonePrincipal) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">E-mail</span>
                            <span class="thermal-value"><?= esc($displayValue($cliente['email'] ?? $os['cliente_email'] ?? '')) ?></span>
                        </div>
                        <span class="thermal-block-label">Endereco</span>
                        <p class="thermal-text"><?= esc($displayValue($clienteEnderecoCompleto)) ?></p>
                    </section>

                    <section class="thermal-section">
                        <h2 class="thermal-section-title">Equipamento</h2>
                        <div class="thermal-row">
                            <span class="thermal-label">Tipo</span>
                            <span class="thermal-value"><?= esc($displayValue($equipamentoTipo)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Modelo</span>
                            <span class="thermal-value"><?= esc($displayValue($equipamentoResumo)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Cor</span>
                            <span class="thermal-value"><?= esc($displayValue($equipamentoCor)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Serie</span>
                            <span class="thermal-value"><?= esc($displayValue($equipamentoSerie)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">IMEI</span>
                            <span class="thermal-value"><?= esc($displayValue($equipamentoImei)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Senha</span>
                            <span class="thermal-value"><?= esc($displayValue($equipamentoSenha)) ?></span>
                        </div>
                    </section>

                    <section class="thermal-section">
                        <h2 class="thermal-section-title">Relato do Cliente</h2>
                        <p class="thermal-text"><?= esc($displayValue($relatoCliente, 'Nao informado.')) ?></p>
                        <?php if ($observacoesCliente !== ''): ?>
                            <span class="thermal-block-label">Observacoes do cliente</span>
                            <p class="thermal-text"><?= esc($observacoesCliente) ?></p>
                        <?php endif; ?>
                    </section>

                    <section class="thermal-section">
                        <h2 class="thermal-section-title">Diagnostico Tecnico</h2>
                        <span class="thermal-block-label">Diagnostico</span>
                        <p class="thermal-text"><?= esc($displayValue($diagnosticoTecnico, 'Nao informado.')) ?></p>
                        <span class="thermal-block-label">Solucao aplicada</span>
                        <p class="thermal-text"><?= esc($displayValue($solucaoAplicada, 'Nao informada.')) ?></p>
                        <span class="thermal-block-label">Procedimentos executados</span>
                        <p class="thermal-text"><?= esc($procedimentosTexto) ?></p>
                        <?php if ($observacoesInternas !== ''): ?>
                            <span class="thermal-block-label">Observacoes internas</span>
                            <p class="thermal-text"><?= esc($observacoesInternas) ?></p>
                        <?php endif; ?>
                    </section>

                    <section class="thermal-section">
                        <h2 class="thermal-section-title">Checklist de Entrada</h2>
                        <?php if ($checklistItems === []): ?>
                            <p class="thermal-text">Nenhum item de checklist foi registrado para esta OS.</p>
                        <?php else: ?>
                            <?php foreach ($checklistItems as $itemChecklist): ?>
                                <?php $meta = $checklistStatusMeta((string) ($itemChecklist['status'] ?? '')); ?>
                                <div class="thermal-checklist-item">
                                    <span class="thermal-checklist-marker">[<?= esc($meta['marker']) ?>]</span>
                                    <strong><?= esc((string) ($itemChecklist['descricao'] ?? 'Item do checklist')) ?></strong>
                                    <div class="thermal-meta"><?= esc($meta['label']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>

                    <section class="thermal-section">
                        <h2 class="thermal-section-title">Resumo Financeiro</h2>
                        <div class="thermal-row">
                            <span class="thermal-label">Mao de obra</span>
                            <span class="thermal-value"><?= esc(formatMoney($resumoFinanceiro['valor_mao_obra'] ?? 0)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Pecas</span>
                            <span class="thermal-value"><?= esc(formatMoney($resumoFinanceiro['valor_pecas'] ?? 0)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Subtotal</span>
                            <span class="thermal-value"><?= esc(formatMoney($resumoFinanceiro['valor_total'] ?? 0)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Desconto</span>
                            <span class="thermal-value"><?= esc(formatMoney($resumoFinanceiro['desconto'] ?? 0)) ?></span>
                        </div>
                        <div class="thermal-divider"></div>
                        <div class="thermal-row">
                            <span class="thermal-label"><strong>Total final</strong></span>
                            <span class="thermal-value"><?= esc(formatMoney($resumoFinanceiro['valor_final'] ?? 0)) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Pagamento</span>
                            <span class="thermal-value"><?= esc($displayValue($resumoFinanceiro['forma_pagamento'] ?? '', 'A combinar')) ?></span>
                        </div>
                        <div class="thermal-row">
                            <span class="thermal-label">Garantia</span>
                            <span class="thermal-value"><?= esc($garantiaTexto) ?></span>
                        </div>
                    </section>

                    <?php if ($orcamento !== null): ?>
                        <section class="thermal-section">
                            <h2 class="thermal-section-title">Orcamento Vinculado</h2>
                            <div class="thermal-row">
                                <span class="thermal-label">Numero</span>
                                <span class="thermal-value"><?= esc($displayValue($orcamento['numero'] ?? '')) ?></span>
                            </div>
                            <div class="thermal-row">
                                <span class="thermal-label">Status</span>
                                <span class="thermal-value"><?= esc($displayValue($orcamento['status_label'] ?? '')) ?></span>
                            </div>
                            <div class="thermal-row">
                                <span class="thermal-label">Validade</span>
                                <span class="thermal-value"><?= esc($renderDateTime($orcamento['validade_data'] ?? '', false)) ?></span>
                            </div>
                            <span class="thermal-block-label">Tipo</span>
                            <p class="thermal-text"><?= esc($displayValue($orcamento['tipo_label'] ?? '')) ?></p>

                            <?php if ($orcamentoItems !== []): ?>
                                <?php foreach ($orcamentoItems as $item): ?>
                                    <div class="thermal-budget-item">
                                        <p class="thermal-budget-item-title"><?= esc((string) ($item['descricao'] ?? 'Item do orcamento')) ?></p>
                                        <p class="thermal-budget-item-meta">
                                            <?= esc((string) ($item['tipo_item_label'] ?? 'Item')) ?>
                                            | Qtd: <?= esc((string) ($item['quantidade'] ?? '0')) ?>
                                            | Unit: <?= esc(formatMoney($item['valor_unitario'] ?? 0)) ?>
                                        </p>
                                        <div class="thermal-row">
                                            <span class="thermal-label">Total</span>
                                            <span class="thermal-value"><?= esc(formatMoney($item['total'] ?? 0)) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <?php if ($notasLegadas !== []): ?>
                        <section class="thermal-section">
                            <h2 class="thermal-section-title">Notas Complementares</h2>
                            <?php foreach ($notasLegadas as $nota): ?>
                                <div class="thermal-budget-item">
                                    <p class="thermal-budget-item-title"><?= esc($renderDateTime($nota['created_at'] ?? '', true)) ?></p>
                                    <p class="thermal-text"><?= esc((string) ($nota['conteudo'] ?? $nota['descricao'] ?? 'Registro adicional')) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                    <div class="thermal-signature">Assinatura do cliente</div>

                    <div class="thermal-divider--double"></div>
                    <div class="thermal-center thermal-footer">
                        <div><strong><?= esc((string) ($os['numero_os'] ?? '#')) ?></strong></div>
                        <div><?= esc($displayValue($generatedAt)) ?></div>
                        <div><?= esc($displayValue($empresaNome)) ?></div>
                    </div>
                </div>
            <?php else: ?>
            <?php if ($usePreviewPageShell): ?>
                <table class="page-shell">
                    <tr>
                        <td class="page-shell-content">
            <?php endif; ?>
            <div class="company-header">
                <table class="header-table">
                    <tr>
                        <td class="logo-slot">
                            <div class="logo-badge">
                                <?php if ($logoDataUri !== ''): ?>
                                    <img src="<?= esc($logoDataUri) ?>" alt="Logo da empresa">
                                <?php else: ?>
                                    <?= esc($placeholderInitials($empresaNome)) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <h1 class="company-title"><?= esc($displayValue($empresaNome)) ?></h1>
                            <?php if ($companyHeaderMetaText !== ''): ?>
                                <div class="company-meta"><?= esc($companyHeaderMetaText) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="info-card">
                <table class="info-table">
                    <tr>
                        <td>
                            <h2 class="os-title">Ordem de Servi&ccedil;o <?= esc((string) ($os['numero_os'] ?? '#')) ?></h2>
                            <p class="os-subtitle">Visualiza&ccedil;&atilde;o pronta para impress&atilde;o em <?= esc($displayValue($formatLabel)) ?></p>
                        </td>
                    </tr>
                </table>

                <div class="divider"></div>

                <table class="grid-table">
                    <tr>
                        <td class="field-cell" style="width:25%;">
                            <span class="field-label">Data de abertura</span>
                            <span class="field-value"><?= esc($renderDateTime($os['data_abertura'] ?? '', true)) ?></span>
                        </td>
                        <td class="field-cell" style="width:25%;">
                            <span class="field-label">Status atual</span>
                            <span class="field-value"><?= esc($displayValue($statusLabel, 'Sem status')) ?></span>
                        </td>
                        <td class="field-cell" style="width:25%;">
                            <span class="field-label">Fluxo</span>
                            <span class="field-value"><?= esc($displayValue($estadoFluxoLabel, 'Sem fluxo')) ?></span>
                        </td>
                        <td class="field-cell" style="width:25%;">
                            <span class="field-label">Previs&atilde;o</span>
                            <span class="field-value"><?= esc($renderDateTime($os['data_previsao'] ?? '', false)) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="field-cell">
                            <span class="field-label">Entrega</span>
                            <span class="field-value"><?= esc($renderDateTime($os['data_entrega'] ?? '', false)) ?></span>
                        </td>
                        <td class="field-cell">
                            <span class="field-label">T&eacute;cnico respons&aacute;vel</span>
                            <span class="field-value"><?= esc($displayValue($os['tecnico_nome'] ?? '', 'Nao atribuido')) ?></span>
                        </td>
                        <td class="field-cell">
                            <span class="field-label">Gerado em</span>
                            <span class="field-value"><?= esc($displayValue($generatedAt)) ?></span>
                        </td>
                    </tr>
                </table>
            </div>

            <section class="section">
                <div class="section-title">Dados do Cliente</div>
                <div class="section-body">
                    <table class="grid-table">
                        <tr>
                            <td class="field-cell" style="width:33.33%;">
                                <span class="field-label">Cliente</span>
                                <span class="field-value"><?= esc($displayValue($os['cliente_nome'] ?? '')) ?></span>
                            </td>
                            <td class="field-cell" style="width:33.33%;">
                                <span class="field-label">Telefone</span>
                                <span class="field-value"><?= esc($displayValue($clienteTelefones[0] ?? ($os['cliente_telefone'] ?? ''))) ?></span>
                            </td>
                            <td class="field-cell" style="width:33.33%;">
                                <span class="field-label">E-mail</span>
                                <span class="field-value"><?= esc($displayValue($cliente['email'] ?? $os['cliente_email'] ?? '')) ?></span>
                            </td>
                        </tr>
                    </table>
                    <div class="divider"></div>
                    <span class="field-label">Endere&ccedil;o completo</span>
                    <span class="field-value"><?= esc($displayValue($clienteEnderecoCompleto)) ?></span>
                </div>
            </section>

            <section class="section">
                <div class="section-title">Equipamento</div>
                <div class="section-body">
                    <p class="section-note">Dados tecnicos consolidados do equipamento vinculado a esta ordem de servico.</p>
                    <table class="grid-table">
                        <tr>
                            <?php if ($showEquipmentPhotoSlot): ?>
                                <td class="equipment-photo-cell">
                                    <div class="equipment-photo-box">
                                        <?php if ($equipmentPhotoUrl !== ''): ?>
                                            <img src="<?= esc($equipmentPhotoUrl) ?>" alt="Foto principal do equipamento">
                                        <?php else: ?>
                                            <div class="equipment-photo-placeholder">Foto do equipamento</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td>
                                <table class="grid-table">
                                    <tr>
                                        <td class="field-cell" style="width:33.33%;">
                                            <span class="field-label">Tipo</span>
                                            <span class="field-value"><?= esc($displayValue($equipamentoTipo)) ?></span>
                                        </td>
                                        <td class="field-cell" style="width:33.33%;">
                                            <span class="field-label">Marca / Modelo</span>
                                            <span class="field-value"><?= esc($displayValue($equipamentoResumo)) ?></span>
                                        </td>
                                        <td class="field-cell" style="width:33.33%;">
                                            <span class="field-label">Cor</span>
                                            <span class="field-value"><?= esc($displayValue($equipamentoCor)) ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="field-cell">
                                            <span class="field-label">Numero de serie</span>
                                            <span class="field-value"><?= esc($displayValue($equipamentoSerie)) ?></span>
                                        </td>
                                        <td class="field-cell">
                                            <span class="field-label">IMEI</span>
                                            <span class="field-value"><?= esc($displayValue($equipamentoImei)) ?></span>
                                        </td>
                                        <td class="field-cell">
                                            <span class="field-label">Senha de acesso</span>
                                            <span class="field-value"><?= esc($displayValue($equipamentoSenha)) ?></span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                </div>
            </section>

            <section class="section">
                <div class="section-title">Relato do Cliente e Diagnostico Tecnico</div>
                <div class="section-body">
                    <table class="dual-table">
                        <tr>
                            <td style="width:49%;padding-right:10px;">
                                <div class="sub-title" style="margin-top:0;">Relato do cliente</div>
                                <div class="text-content"><?= esc($displayValue($relatoCliente, 'Nao informado.')) ?></div>
                                <?php if ($observacoesCliente !== ''): ?>
                                    <div class="sub-title">Observacoes do cliente</div>
                                    <div class="text-content"><?= esc($observacoesCliente) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="width:51%;">
                                <div class="sub-title" style="margin-top:0;">Diagnostico tecnico</div>
                                <div class="sub-title">Diagnostico</div>
                                <div class="text-content"><?= esc($displayValue($diagnosticoTecnico, 'Nao informado.')) ?></div>

                                <div class="sub-title">Solucao aplicada</div>
                                <div class="text-content"><?= esc($displayValue($solucaoAplicada, 'Nao informada.')) ?></div>

                                <div class="sub-title">Procedimentos executados</div>
                                <div class="text-content"><?= esc($procedimentosTexto) ?></div>

                                <?php if ($observacoesInternas !== ''): ?>
                                    <div class="sub-title">Observacoes internas</div>
                                    <div class="text-content"><?= esc($observacoesInternas) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </section>

            <?php if ($usePreviewPageShell): ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="doc-footer">
                            <table class="grid-table">
                                <tr>
                                    <td><?= esc($footerSummaryText) ?></td>
                                    <td class="page-counter" style="width:130px;">
                                        <?php if ($renderMode !== 'pdf'): ?>
                                            <span class="page-counter-text">Pagina 1 de <?= esc((string) $a4TotalPages) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <table class="page-shell page-shell-break">
                    <tr>
                        <td class="page-shell-content">
            <?php elseif (!$isThermal): ?>
                <div class="pdf-hard-break"></div>
            <?php endif; ?>

            <section class="section">
                <div class="section-title">Checklist de Entrada</div>
                <div class="section-body">
                    <?php if ($checklistItems === []): ?>
                        <div class="text-content">Nenhum item de checklist foi registrado para esta OS.</div>
                    <?php else: ?>
                        <table class="grid-table">
                            <?php foreach (array_chunk($checklistItems, 2) as $rowItems): ?>
                                <tr>
                                    <?php foreach ($rowItems as $itemChecklist): ?>
                                        <?php $meta = $checklistStatusMeta((string) ($itemChecklist['status'] ?? '')); ?>
                                        <td style="width:50%;padding-right:10px;">
                                            <div class="checklist-item is-<?= esc($meta['class']) ?>">
                                                <span class="checklist-marker"><?= esc($meta['marker']) ?></span>
                                                <span class="checklist-text"><?= esc((string) ($itemChecklist['descricao'] ?? 'Item do checklist')) ?></span>
                                                <span class="checklist-badge"><?= esc($meta['label']) ?></span>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                    <?php if (count($rowItems) < 2): ?>
                                        <td style="width:50%;"></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($acessorios !== [] || $estadoFisico !== []): ?>
                <section class="section">
                    <div class="section-title">Acessorios e Estado Fisico</div>
                    <div class="section-body">
                        <table class="dual-table">
                            <tr>
                                <td style="width:49%;padding-right:10px;">
                                    <div class="sub-title" style="margin-top:0;">Acessorios registrados</div>
                                    <?php if ($acessorios === []): ?>
                                        <div class="text-content">Nenhum acessorio registrado.</div>
                                    <?php else: ?>
                                        <?php foreach ($acessorios as $acessorio): ?>
                                            <div class="soft-card">
                                                <strong><?= esc((string) ($acessorio['descricao'] ?? 'Acessorio')) ?></strong>
                                                <p><?= esc(!empty($acessorio['valores_resumo']) ? $renderList((array) $acessorio['valores_resumo']) : 'Sem detalhes adicionais.') ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td style="width:51%;">
                                    <div class="sub-title" style="margin-top:0;">Estado fisico do equipamento</div>
                                    <?php if ($estadoFisico === []): ?>
                                        <div class="text-content">Nenhum registro de estado fisico anexado.</div>
                                    <?php else: ?>
                                        <?php foreach ($estadoFisico as $itemEstado): ?>
                                            <div class="soft-card">
                                                <strong><?= esc((string) ($itemEstado['descricao_dano'] ?? 'Registro')) ?></strong>
                                                <p><?= esc(!empty($itemEstado['valores_resumo']) ? $renderList((array) $itemEstado['valores_resumo']) : 'Sem detalhes adicionais.') ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($defeitos !== []): ?>
                <section class="section">
                    <div class="section-title">Defeitos Relatados e Referencias de Reparo</div>
                    <div class="section-body">
                        <?php foreach ($defeitos as $defeito): ?>
                            <div class="soft-card">
                                <strong><?= esc((string) ($defeito['nome'] ?? 'Defeito registrado')) ?></strong>
                                <p>
                                    <?= esc((string) ($defeito['tipo_nome'] ?? 'Tipo nao informado')) ?>
                                    <?php if (!empty($defeito['classificacao'])): ?>
                                        | <?= esc(ucfirst((string) $defeito['classificacao'])) ?>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($defeito['descricao'])): ?>
                                    <div class="text-content" style="margin-top:6px;"><?= esc((string) $defeito['descricao']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($defeito['procedimentos'])): ?>
                                    <?php foreach ((array) $defeito['procedimentos'] as $procedimentoDefeito): ?>
                                        <span class="pill"><?= esc((string) ($procedimentoDefeito['descricao'] ?? 'Procedimento de reparo')) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="section">
                <div class="section-title">Itens e Servicos Lancados na OS</div>
                <div class="section-body">
                    <?php if ($itensOs === []): ?>
                        <div class="text-content">Nenhum item financeiro foi lancado diretamente na OS.</div>
                    <?php else: ?>
                        <table class="table-list items-table">
                            <thead>
                                <tr>
                                    <th>Descricao</th>
                                    <th>Tipo</th>
                                    <th>Qtd</th>
                                    <th>Valor unitario</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itensOs as $item): ?>
                                    <?php $tipoItem = strtolower(trim((string) ($item['tipo'] ?? 'item'))); ?>
                                    <tr>
                                        <td>
                                            <?= esc((string) ($item['descricao'] ?? 'Item da OS')) ?>
                                            <?php if (!empty($item['observacao'])): ?>
                                                <div style="margin-top:4px;color:#64748b;"><?= esc((string) $item['observacao']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($tipoItem === 'peca' ? 'Peca' : 'Servico') ?></td>
                                        <td><?= esc((string) ($item['quantidade'] ?? '0')) ?></td>
                                        <td><?= esc(formatMoney($item['valor_unitario'] ?? 0)) ?></td>
                                        <td><strong><?= esc(formatMoney($item['valor_total'] ?? 0)) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

            <section class="section">
                <div class="section-title">Resumo Financeiro</div>
                <div class="section-body">
                    <table class="financial-table">
                        <tr>
                            <td style="width:20%;padding-right:8px;"><div class="financial-box"><span class="field-label">Mao de obra</span><span class="field-value"><?= esc(formatMoney($resumoFinanceiro['valor_mao_obra'] ?? 0)) ?></span></div></td>
                            <td style="width:20%;padding-right:8px;"><div class="financial-box"><span class="field-label">Pecas</span><span class="field-value"><?= esc(formatMoney($resumoFinanceiro['valor_pecas'] ?? 0)) ?></span></div></td>
                            <td style="width:20%;padding-right:8px;"><div class="financial-box"><span class="field-label">Subtotal</span><span class="field-value"><?= esc(formatMoney($resumoFinanceiro['valor_total'] ?? 0)) ?></span></div></td>
                            <td style="width:20%;padding-right:8px;"><div class="financial-box"><span class="field-label">Desconto</span><span class="field-value"><?= esc(formatMoney($resumoFinanceiro['desconto'] ?? 0)) ?></span></div></td>
                            <td style="width:20%;"><div class="financial-box total-final"><span class="field-label">Total final</span><span class="field-value"><?= esc(formatMoney($resumoFinanceiro['valor_final'] ?? 0)) ?></span></div></td>
                        </tr>
                    </table>

                    <div class="divider"></div>

                    <table class="grid-table">
                        <tr>
                            <td class="field-cell" style="width:33.33%;">
                                <span class="field-label">Forma de pagamento</span>
                                <span class="field-value"><?= esc($displayValue($resumoFinanceiro['forma_pagamento'] ?? '', 'A combinar')) ?></span>
                            </td>
                            <td class="field-cell" style="width:33.33%;">
                                <span class="field-label">Garantia</span>
                                <span class="field-value"><?= esc($garantiaTexto) ?></span>
                            </td>
                            <td class="field-cell" style="width:33.33%;">
                                <span class="field-label">Valor final</span>
                                <span class="field-value" style="color:#2563ab;"><?= esc(formatMoney($resumoFinanceiro['valor_final'] ?? 0)) ?></span>
                            </td>
                        </tr>
                    </table>

                    <div class="divider"></div>

                    
                </div>
            </section>

            <?php if ($orcamento !== null): ?>
                <section class="section">
                    <div class="section-title">Orcamento Vinculado</div>
                    <div class="section-body">
                        <table class="grid-table">
                            <tr>
                                <td class="field-cell" style="width:33.33%;">
                                    <span class="field-label">Numero</span>
                                    <span class="field-value"><?= esc($displayValue($orcamento['numero'] ?? '')) ?></span>
                                </td>
                                <td class="field-cell" style="width:33.33%;">
                                    <span class="field-label">Status</span>
                                    <span class="field-value"><?= esc($displayValue($orcamento['status_label'] ?? '')) ?></span>
                                </td>
                                <td class="field-cell" style="width:33.33%;">
                                    <span class="field-label">Validade</span>
                                    <span class="field-value"><?= esc($renderDateTime($orcamento['validade_data'] ?? '', false)) ?></span>
                                </td>
                            </tr>
                        </table>

                        <div class="divider"></div>

                        <span class="field-label">Tipo do orcamento</span>
                        <span class="field-value"><?= esc($displayValue($orcamento['tipo_label'] ?? '')) ?></span>

                        <?php if ($orcamentoItems !== []): ?>
                            <table class="table-list orcamento-table">
                                <thead>
                                    <tr>
                                        <th>Descricao</th>
                                        <th>Tipo</th>
                                        <th>Qtd</th>
                                        <th>Valor unitario</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orcamentoItems as $item): ?>
                                        <tr>
                                            <td><?= esc((string) ($item['descricao'] ?? 'Item do orcamento')) ?></td>
                                            <td><?= esc((string) ($item['tipo_item_label'] ?? 'Item')) ?></td>
                                            <td><?= esc((string) ($item['quantidade'] ?? '0')) ?></td>
                                            <td><?= esc(formatMoney($item['valor_unitario'] ?? 0)) ?></td>
                                            <td><strong><?= esc(formatMoney($item['total'] ?? 0)) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($notasLegadas !== []): ?>
                <section class="section">
                    <div class="section-title">Notas Complementares da OS</div>
                    <div class="section-body notes-list">
                        <?php foreach ($notasLegadas as $nota): ?>
                            <div class="soft-card">
                                <strong><?= esc($renderDateTime($nota['created_at'] ?? '', true)) ?></strong>
                                <p><?= esc((string) ($nota['conteudo'] ?? $nota['descricao'] ?? 'Registro adicional')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($usePreviewPageShell): ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="doc-footer">
                            <table class="grid-table">
                                <tr>
                                    <td><?= esc($footerSummaryText) ?></td>
                                    <td class="page-counter" style="width:130px;">
                                        <?php if ($renderMode !== 'pdf'): ?>
                                            <span class="page-counter-text">Pagina 2 de <?= esc((string) $a4TotalPages) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            <?php endif; ?>

            <?php if ($includePhotos && $renderablePhotoGroups !== []): ?>
                <?php if ($usePreviewPageShell): ?>
                    <table class="page-shell page-shell-break">
                        <tr>
                            <td class="page-shell-content">
                <?php elseif (!$isThermal): ?>
                    <div class="pdf-hard-break"></div>
                <?php endif; ?>
                <section class="section <?= $usePreviewPageShell ? 'a4-photos-page-break' : '' ?>">
                    <div class="section-title">Fotos Anexadas</div>
                    <div class="section-body">
                        <?php foreach ($renderablePhotoGroups as $group): ?>
                            <?php $groupPhotos = array_values((array) ($group['photos'] ?? [])); ?>
                            <div class="photo-group">
                                <div class="photo-group-title"><?= esc((string) ($group['label'] ?? 'Fotos')) ?></div>
                                <table class="photo-table">
                                    <?php foreach (array_chunk($groupPhotos, 3) as $rowPhotos): ?>
                                        <tr>
                                            <?php foreach ($rowPhotos as $photo): ?>
                                                <td style="width:33.33%;">
                                                    <div class="photo-card">
                                                        <img src="<?= esc((string) ($photo['url'] ?? '')) ?>" alt="<?= esc((string) ($photo['label'] ?? 'Foto da OS')) ?>">
                                                        <div class="photo-card-label"><?= esc((string) ($photo['label'] ?? 'Foto da OS')) ?></div>
                                                    </div>
                                                </td>
                                            <?php endforeach; ?>
                                            <?php while (count($rowPhotos) < 3): $rowPhotos[] = null; ?>
                                                <td style="width:33.33%;"></td>
                                            <?php endwhile; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php if ($usePreviewPageShell): ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="doc-footer">
                                <table class="grid-table">
                                    <tr>
                                        <td><?= esc($footerSummaryText) ?></td>
                                        <td class="page-counter" style="width:130px;">
                                            <?php if ($renderMode !== 'pdf'): ?>
                                                <span class="page-counter-text">Pagina 3 de <?= esc((string) $a4TotalPages) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($isThermal): ?>
                <div class="doc-footer">
                    <table class="grid-table">
                        <tr>
                            <td><?= esc($footerSummaryText) ?></td>
                            <td class="page-counter" style="width:130px;">
                                <?php if ($renderMode !== 'pdf'): ?>
                                    <span class="page-counter-text">Pagina 1</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($isThermal && $autoPrint && $renderMode !== 'pdf'): ?>
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () {
                    window.print();
                }, 180);
            });

            window.addEventListener('afterprint', function () {
                if (window.opener) {
                    window.close();
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
