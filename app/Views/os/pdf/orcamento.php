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
    <div class="doc-subtitle">Validade sugerida: 7 dias | Gerado em <?= esc($geradoEm) ?></div>
</div>

<table class="grid">
    <tr><td class="label">Cliente</td><td><?= esc($os['cliente_nãome']) ?></td><td class="label">Telefone</td><td><?= esc($os['cliente_telefone'] ?? '-') ?></td></tr>
    <tr><td class="label">Equipamento</td><td><?= esc(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></td><td class="label">Numero OS</td><td><?= esc($os['numero_os']) ?></td></tr>
</table>

<div class="section-title">Diagnãostico tecnico</div>
<div><?= nl2br(esc($os['diagnãostico_tecnico'] ?? 'Diagnãostico nao informado.')) ?></div>

<div class="section-title">Itens de servico e pecas</div>
<table class="table">
    <thead>
        <tr>
            <th>Tipo</th>
            <th>Descricao</th>
            <th>Qtd</th>
            <th>Valor unitario</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($payload['itens'])): ?>
        <tr><td colspan="5" class="muted">Nenhum item lancado.</td></tr>
        <?php else: ?>
        <?php foreach ($payload['itens'] as $item): ?>
        <tr>
            <td><?= esc($item['tipo'] ?? '-') ?></td>
            <td><?= esc($item['descricao'] ?? '-') ?></td>
            <td><?= esc((string)($item['quantidade'] ?? 1)) ?></td>
            <td class="right"><?= esc(formatMoney($item['valor_unitario'] ?? 0)) ?></td>
            <td class="right"><?= esc(formatMoney($item['valor_total'] ?? 0)) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<table class="grid" style="margin-top: 10px;">
    <tr><td class="label">Total servicos</td><td><?= esc(formatMoney($payload['totais']['servicos'] ?? 0)) ?></td><td class="label">Total pecas</td><td><?= esc(formatMoney($payload['totais']['pecas'] ?? 0)) ?></td></tr>
    <tr><td class="label">Desconto</td><td><?= esc(formatMoney($os['desconto'] ?? 0)) ?></td><td class="label">Valor final</td><td><strong><?= esc(formatMoney($os['valor_final'] ?? 0)) ?></strong></td></tr>
</table>

<div class="footer">Aprovacao deste orcamento autoriza execucao do servico descrito.</div>
</body>
</html>
