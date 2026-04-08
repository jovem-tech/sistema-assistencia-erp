<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?= esc($tituloDocumento ?? 'Orcamento') ?></title>
<style>
    body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
    .doc-header { border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; margin-bottom: 16px; }
    .doc-title { font-size: 18px; font-weight: 700; margin: 0; color: #0f172a; }
    .doc-subtitle { font-size: 11px; color: #64748b; margin-top: 4px; }
    .grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .grid td { padding: 6px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
    .grid .label { width: 26%; background: #f8fafc; font-weight: 600; }
    .section-title { margin: 14px 0 8px; font-size: 13px; font-weight: 700; color: #111827; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { border: 1px solid #e5e7eb; padding: 7px; }
    .table th { background: #f1f5f9; font-weight: 700; }
    .muted { color: #64748b; }
    .right { text-align: right; }
    .highlight { background: #ecfeff; border: 1px solid #bae6fd; border-radius: 8px; padding: 8px; }
    .footer { margin-top: 22px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
</head>
<body>
<?php
$orcamento = $orcamento ?? [];
$itens = $itens ?? [];
$clienteNome = trim((string) ($orcamento['cliente_nome'] ?? ''));
if ($clienteNome === '') {
    $clienteNome = (string) ($orcamento['cliente_nome_avulso'] ?? 'Cliente eventual');
}
$numero = (string) ($orcamento['numero'] ?? '#');
$linkPublico = !empty($orcamento['token_publico']) ? base_url('orcamento/' . $orcamento['token_publico']) : '';
?>
<div class="doc-header">
    <h1 class="doc-title">Orcamento <?= esc($numero) ?></h1>
    <div class="doc-subtitle">Gerado em <?= esc($geradoEm ?? date('d/m/Y H:i:s')) ?></div>
</div>

<table class="grid">
    <tr>
        <td class="label">Cliente</td>
        <td><?= esc($clienteNome) ?></td>
        <td class="label">Contato</td>
        <td><?= esc((string) ($orcamento['telefone_contato'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Email</td>
        <td><?= esc((string) ($orcamento['email_contato'] ?? '-')) ?></td>
        <td class="label">OS vinculada</td>
        <td><?= esc((string) ($orcamento['numero_os'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Validade</td>
        <td><?= esc(formatDate($orcamento['validade_data'] ?? null)) ?></td>
        <td class="label">Prazo execucao</td>
        <td><?= esc((string) ($orcamento['prazo_execucao'] ?? '-')) ?></td>
    </tr>
</table>

<div class="section-title">Itens do orcamento</div>
<table class="table">
    <thead>
        <tr>
            <th>Tipo</th>
            <th>Descricao</th>
            <th>Qtd</th>
            <th>Valor unitario</th>
            <th>Desconto</th>
            <th>Acrescimo</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($itens)): ?>
            <tr>
                <td colspan="7" class="muted">Nenhum item cadastrado neste orcamento.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td><?= esc(ucfirst((string) ($item['tipo_item'] ?? 'item'))) ?></td>
                    <td>
                        <?= esc((string) ($item['descricao'] ?? '-')) ?>
                        <?php if (!empty($item['observacoes'])): ?>
                            <br><span class="muted"><?= esc((string) $item['observacoes']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="right"><?= esc(number_format((float) ($item['quantidade'] ?? 0), 2, ',', '.')) ?></td>
                    <td class="right"><?= esc(formatMoney($item['valor_unitario'] ?? 0)) ?></td>
                    <td class="right"><?= esc(formatMoney($item['desconto'] ?? 0)) ?></td>
                    <td class="right"><?= esc(formatMoney($item['acrescimo'] ?? 0)) ?></td>
                    <td class="right"><strong><?= esc(formatMoney($item['total'] ?? 0)) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<table class="grid" style="margin-top: 10px;">
    <tr>
        <td class="label">Subtotal</td>
        <td><?= esc(formatMoney($orcamento['subtotal'] ?? 0)) ?></td>
        <td class="label">Desconto</td>
        <td><?= esc(formatMoney($orcamento['desconto'] ?? 0)) ?></td>
    </tr>
    <tr>
        <td class="label">Acrescimo</td>
        <td><?= esc(formatMoney($orcamento['acrescimo'] ?? 0)) ?></td>
        <td class="label">Total final</td>
        <td><strong><?= esc(formatMoney($orcamento['total'] ?? 0)) ?></strong></td>
    </tr>
</table>

<?php if (!empty($orcamento['condicoes'])): ?>
    <div class="section-title">Condicoes</div>
    <div><?= nl2br(esc((string) $orcamento['condicoes'])) ?></div>
<?php endif; ?>

<?php if (!empty($orcamento['observacoes'])): ?>
    <div class="section-title">Observacoes</div>
    <div><?= nl2br(esc((string) $orcamento['observacoes'])) ?></div>
<?php endif; ?>

<?php if ($linkPublico !== ''): ?>
    <div class="section-title">Aprovacao online</div>
    <div class="highlight">
        O cliente pode aprovar ou rejeitar pelo link: <?= esc($linkPublico) ?>
    </div>
<?php endif; ?>

<div class="footer">Aprovacao deste orcamento autoriza a execucao dos itens descritos neste documento.</div>
</body>
</html>
