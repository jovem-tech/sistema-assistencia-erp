<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?= esc($tituloDocumento ?? 'Orçamento') ?></title>
<?= view('orcamentos/pdf/_styles') ?>
<style>
    .doc-header { border-bottom-color: #0ea5e9; }
    .doc-title { color: #0f172a; }
    .doc-subtitle { color: #64748b; }
    .grid .label { background: #f8fafc; }
    .table th { background: #f1f5f9; }
    .highlight { background: #ecfeff; border: 1px solid #bae6fd; border-radius: 8px; padding: 8px; }
</style>
</head>
<body>
<?php
$orcamento = $orcamento ?? [];
$itens = $itens ?? [];
$tipoOrcamento = (string) ($orcamento['tipo_orcamento'] ?? 'previo');
$tipoResumo = $tipoOrcamento === 'assistencia'
    ? 'Orçamento com equipamento ja recebido em assistencia e submetido a analise.'
    : 'Orçamento previo com estimativa inicial, sujeito a confirmacao apos a entrada do equipamento.';
$clienteNome = trim((string) ($orcamento['cliente_nome'] ?? ''));
if ($clienteNome === '') {
    $clienteNome = (string) ($orcamento['cliente_nome_avulso'] ?? 'Cliente eventual');
}
$numero = (string) ($orcamento['numero'] ?? '#');
$linkPublico = !empty($orcamento['token_publico']) ? base_url('orcamento/' . $orcamento['token_publico']) : '';
?>
<?= view('orcamentos/pdf/_branding', [
    'branding' => $branding ?? [],
    'tituloDocumento' => $tituloDocumento ?? 'Orçamento',
    'documentoReferencia' => $numero,
]) ?>
<div class="pdf-page-content">
    <div class="doc-header">
        <h1 class="doc-title">Orçamento <?= esc($numero) ?></h1>
        <div class="doc-subtitle">Gerado em <?= esc($geradoEm ?? date('d/m/Y H:i:s')) ?> | Versão <?= esc((string) ($orcamento['versao'] ?? 1)) ?></div>
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
            <td class="label">Tipo</td>
            <td><?= esc($tipoOrcamento === 'assistencia' ? 'Com equipamento na assistencia' : 'Previo') ?></td>
            <td class="label">Versão</td>
            <td><?= esc((string) ($orcamento['versao'] ?? 1)) ?></td>
        </tr>
        <tr>
            <td class="label">Validade</td>
            <td><?= esc(formatDate($orcamento['validade_data'] ?? null)) ?></td>
            <td class="label">Prazo execucao</td>
            <td><?= esc((string) ($orcamento['prazo_execucao'] ?? '-')) ?></td>
        </tr>
    </table>

    <div class="highlight" style="margin-bottom: 10px;">
        <?= esc($tipoResumo) ?>
    </div>

    <div class="section-title">Itens do orcamento</div>
    <table class="table">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Descrição</th>
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

    <div class="footer">
        <?php if ($tipoOrcamento === 'previo'): ?>
            A aprovacao deste orcamento previo registra a concordancia com a estimativa inicial e pode exigir nova autorizacao caso a analise técnica altere valores ou itens.
        <?php else: ?>
            A aprovacao deste orcamento autoriza a execucao dos itens descritos neste documento para o equipamento ja recebido em assistencia.
        <?php endif; ?>
    </div>
</div>
</body>
</html>

