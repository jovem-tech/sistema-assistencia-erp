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
    <tr><td class="label">Cliente</td><td><?= esc($os['cliente_nãome']) ?></td><td class="label">Telefone</td><td><?= esc($os['cliente_telefone'] ?? '-') ?></td></tr>
    <tr><td class="label">Equipamento</td><td><?= esc(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></td><td class="label">Data entrega</td><td><?= esc(formatDate($os['data_entrega'] ?? date('Y-m-d H:i:s'), true)) ?></td></tr>
    <tr><td class="label">Status final</td><td><?= esc($os['status']) ?></td><td class="label">Valor final</td><td><?= esc(formatMoney($os['valor_final'] ?? 0)) ?></td></tr>
</table>

<div class="section-title">Descricao do servico executado</div>
<div><?= nl2br(esc($os['sãolucao_aplicada'] ?? 'Servico concluido conforme OS.')) ?></div>

<div class="section-title">Checklist de itens executados</div>
<?php if (empty($payload['itens'])): ?>
<div class="muted">Sem itens registrados.</div>
<?php else: ?>
<ul>
<?php foreach ($payload['itens'] as $item): ?>
<li><?= esc($item['descricao'] ?? '-') ?> (<?= esc((string)($item['quantidade'] ?? 1)) ?>x)</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<div class="footer">Comprovante de entrega/retirada. Assinatura do cliente: _______________________</div>
</body>
</html>
