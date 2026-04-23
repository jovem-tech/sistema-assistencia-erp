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
    <tr><td class="label">Cliente</td><td><?= esc($os['cliente_nome']) ?></td><td class="label">Equipamento</td><td><?= esc(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></td></tr>
    <tr><td class="label">Status atual</td><td><?= esc($os['status']) ?></td><td class="label">Tecnico</td><td><?= esc($os['tecnico_nome'] ?? '-') ?></td></tr>
</table>

<div class="section-title">Defeito constatado / diagnostico</div>
<div><?= nl2br(esc($os['diagnostico_tecnico'] ?? 'Nao informado.')) ?></div>

<div class="section-title">Causa provavel</div>
<div><?= nl2br(esc($os['observacoes_internas'] ?? 'Nao informada.')) ?></div>

<div class="section-title">Testes realizados</div>
<?php if (empty($payload['itens'])): ?>
<div class="muted">Nao ha testes/servicos registrados em itens.</div>
<?php else: ?>
<ul>
<?php foreach ($payload['itens'] as $item): ?>
<li><?= esc($item['descricao'] ?? '-') ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<div class="section-title">Conclusao tecnica</div>
<div><?= nl2br(esc($os['solucao_aplicada'] ?? 'Conclusao nao registrada.')) ?></div>

<div class="footer">Laudo tecnico emitido para registro de atendimento e suporte pos-servico.</div>
</body>
</html>
