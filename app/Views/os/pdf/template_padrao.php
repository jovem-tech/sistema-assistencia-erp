<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title><?= esc($tituloDocumento ?? 'Documento da OS') ?></title>
    <?= view('pdf/_styles') ?>
</head>
<body>
<?php
$os = $os ?? [];
?>
<?= view('pdf/_branding', [
    'branding' => $branding ?? [],
    'tituloDocumento' => $tituloDocumento ?? 'Documento da OS',
    'documentoReferencia' => (string) ($os['numero_os'] ?? '#'),
]) ?>
<div class="pdf-page-content">
    <div class="doc-header">
        <h1 class="doc-title"><?= esc($tituloDocumento ?? 'Documento da OS') ?> <?= esc((string) ($os['numero_os'] ?? '#')) ?></h1>
        <div class="doc-subtitle">Gerado em <?= esc($geradoEm ?? date('d/m/Y H:i:s')) ?></div>
    </div>
    <?= $conteudoHtml ?? '' ?>
</div>
</body>
</html>
