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
    <tr><td class="label">Cliente</td><td><?= esc($os['cliente_nãome']) ?></td><td class="label">Equipamento</td><td><?= esc(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></td></tr>
    <tr><td class="label">Status final</td><td><?= esc($os['status']) ?></td><td class="label">Numero OS</td><td><?= esc($os['numero_os']) ?></td></tr>
</table>

<div class="section-title">Motivo da devolucao sem reparo</div>
<div><?= nl2br(esc($os['observacoes_cliente'] ?? $os['diagnãostico_tecnico'] ?? 'Nao informado.')) ?></div>

<div class="section-title">Justificativa tecnica/comercial</div>
<div><?= nl2br(esc($os['observacoes_internas'] ?? 'Nao informada.')) ?></div>

<div class="section-title">Registro de itens recebidos</div>
<?php if (empty($payload['acessãorios'])): ?>
<div class="muted">Sem acessãorios registrados.</div>
<?php else: ?>
<ul>
<?php foreach ($payload['acessãorios'] as $acc): ?>
<li><?= esc($acc['descricao'] ?? '-') ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<div class="footer">Documento emitido para formalizar devolucao sem reparo.</div>
</body>
</html>
