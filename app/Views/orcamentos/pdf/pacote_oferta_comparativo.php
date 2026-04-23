<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?= esc($tituloDocumento ?? 'Comparativo de pacote') ?></title>
<?= view('orcamentos/pdf/_styles') ?>
<style>
    .doc-header { border-bottom-color: #0ea5e9; }
    .doc-title { color: #0f172a; }
    .doc-subtitle { color: #64748b; }
    .grid .label { background: #f8fafc; }
    .table th { background: #f1f5f9; }
    .note { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px; font-size: 11px; }
</style>
</head>
<body>
<?php
$pacote = $pacote ?? [];
$niveis = $niveis ?? [];
$extras = $extras ?? [];
$extrasTotal = (float) ($extrasTotal ?? 0);
$clienteNome = trim((string) ($clienteNome ?? 'Cliente'));
$telefoneContato = trim((string) ($telefoneContato ?? ''));
$osNumero = trim((string) ($osNumero ?? ''));
$linkPublico = trim((string) ($linkPublico ?? ''));
$pacoteNome = trim((string) ($pacote['nome'] ?? 'Pacote de servicos'));
?>
<?= view('orcamentos/pdf/_branding', [
    'branding' => $branding ?? [],
    'tituloDocumento' => $tituloDocumento ?? 'Comparativo de pacote e itens extras',
    'documentoReferencia' => $osNumero !== '' ? $osNumero : $pacoteNome,
]) ?>
<div class="pdf-page-content">
    <div class="doc-header">
        <h1 class="doc-title"><?= esc($pacoteNome) ?> - Comparativo</h1>
        <div class="doc-subtitle">Gerado em <?= esc($geradoEm ?? date('d/m/Y H:i:s')) ?></div>
    </div>

    <table class="grid">
        <tr>
            <td class="label">Cliente</td>
            <td><?= esc($clienteNome) ?></td>
            <td class="label">Contato</td>
            <td><?= esc($telefoneContato !== '' ? $telefoneContato : '-') ?></td>
        </tr>
        <tr>
            <td class="label">OS vinculada</td>
            <td><?= esc($osNumero !== '' ? $osNumero : '-') ?></td>
            <td class="label">Pacote</td>
            <td><?= esc($pacoteNome) ?></td>
        </tr>
    </table>

    <div class="section-title">Como ler este comparativo</div>
    <div class="note">
        Este documento mostra o valor de cada nivel do pacote somado aos itens extras fora do pacote.
        Escolha o nivel desejado no link enviado pela equipe. O total final sera a soma do nivel escolhido
        com os itens extras listados abaixo.
        <br><br>
        <strong>Exemplo didatico (valores fixos apenas para entendimento):</strong>
        <br>Itens extras (fora do pacote): R$ 100,00 + R$ 100,00 = <strong>R$ 200,00</strong>
        <br>Pacote Basico: R$ 120,00 + R$ 200,00 = <strong>R$ 320,00</strong>
        <br>Pacote Completo: R$ 220,00 + R$ 200,00 = <strong>R$ 420,00</strong>
        <br>Pacote Premium: R$ 320,00 + R$ 200,00 = <strong>R$ 520,00</strong>
    </div>

    <div class="section-title">Itens extras fora do pacote</div>
    <table class="table">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Descrição</th>
                <th>Qtd</th>
                <th>Valor unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($extras)): ?>
                <tr>
                    <td colspan="5" class="muted">Nenhum item extra foi informado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($extras as $item): ?>
                    <?php $tipoLabel = strtolower(trim((string) ($item['tipo'] ?? 'servico'))) === 'peca' ? 'Peca' : 'Serviço'; ?>
                    <tr>
                        <td><?= esc($tipoLabel) ?></td>
                        <td>
                            <?= esc((string) ($item['descricao'] ?? '-')) ?>
                            <?php if (!empty($item['observacoes'])): ?>
                                <br><span class="muted"><?= esc((string) $item['observacoes']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="right"><?= esc(number_format((float) ($item['quantidade'] ?? 0), 2, ',', '.')) ?></td>
                        <td class="right"><?= esc(formatMoney($item['valor_unitario'] ?? 0)) ?></td>
                        <td class="right"><strong><?= esc(formatMoney($item['total'] ?? 0)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="grid" style="margin-top: 10px;">
        <tr>
            <td class="label">Total de itens extras</td>
            <td><strong><?= esc(formatMoney($extrasTotal)) ?></strong></td>
        </tr>
    </table>

    <div class="section-title">Comparativo por nivel do pacote</div>
    <table class="table">
        <thead>
            <tr>
                <th>Nivel</th>
                <th>Valor do pacote</th>
                <th>Extras</th>
                <th>Total final</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($niveis)): ?>
                <tr>
                    <td colspan="4" class="muted">Não há niveis ativos para este pacote.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($niveis as $nivel): ?>
                    <?php
                    $nivelNome = trim((string) ($nivel['nome_exibicao'] ?? ucfirst((string) ($nivel['nivel'] ?? 'nivel'))));
                    $valorPacote = (float) ($nivel['preco_recomendado'] ?? 0);
                    $totalFinal = $valorPacote + $extrasTotal;
                    ?>
                    <tr>
                        <td><?= esc($nivelNome) ?></td>
                        <td class="right"><?= esc(formatMoney($valorPacote)) ?></td>
                        <td class="right"><?= esc(formatMoney($extrasTotal)) ?></td>
                        <td class="right"><strong><?= esc(formatMoney($totalFinal)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($linkPublico !== ''): ?>
        <div class="section-title">Link de escolha</div>
        <div class="note">
            Para escolher o nivel do pacote, utilize o link enviado pela equipe:
            <br><?= esc($linkPublico) ?>
        </div>
    <?php endif; ?>

    <div class="footer">Este comparativo ajuda na escolha do nivel ideal. Se tiver duvidas, fale com nossa equipe.</div>
</div>
</body>
</html>

