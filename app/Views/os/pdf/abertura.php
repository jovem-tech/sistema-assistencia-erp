<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?= esc($tituloDocumento) ?></title>
<?= view('os/pdf/_styles') ?>
</head>
<body>
<div class="doc-header">
    <h1 class="doc-title"><?= esc($tituloDocumento) ?> - <?= esc($os['numero_os']) ?></h1>
    <div class="doc-subtitle">Gerado em <?= esc($geradoEm) ?></div>
</div>

<table class="grid">
    <tr><td class="label">Numero da OS</td><td><?= esc($os['numero_os']) ?></td><td class="label">Data de abertura</td><td><?= esc(formatDate($os['data_abertura'], true)) ?></td></tr>
    <tr><td class="label">Cliente</td><td><?= esc($os['cliente_nome']) ?></td><td class="label">Telefone</td><td><?= esc($os['cliente_telefone'] ?? '-') ?></td></tr>
    <tr><td class="label">Equipamento</td><td><?= esc(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></td><td class="label">Serie</td><td><?= esc($os['equip_serie'] ?? '-') ?></td></tr>
    <tr><td class="label">Status</td><td><?= esc($os['status']) ?></td><td class="label">Prioridade</td><td><?= esc($os['prioridade'] ?? 'normal') ?></td></tr>
</table>

<div class="section-title">Relato do cliente</div>
<div><?= nl2br(esc($os['relato_cliente'] ?? '-')) ?></div>

<div class="section-title">Acessorios recebidos</div>
<?php if (empty($payload['acessorios'])): ?>
<div class="muted">Nenhum acessorio registrado.</div>
<?php else: ?>
<ul>
<?php foreach ($payload['acessorios'] as $acc): ?>
<li><?= esc($acc['descricao'] ?? '-') ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<div class="section-title">Estado fisico na entrada</div>
<?php if (empty($payload['estado_fisico'])): ?>
<div class="muted">Sem avarias registradas.</div>
<?php else: ?>
<ul>
<?php foreach ($payload['estado_fisico'] as $estado): ?>
<li><?= esc($estado['descricao_dano'] ?? '-') ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<div class="footer">Documento emitido automaticamente pelo Sistema de Assistencia Tecnica.</div>
</body>
</html>
