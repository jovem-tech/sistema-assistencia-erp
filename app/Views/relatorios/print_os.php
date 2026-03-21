<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de OS por Período</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; }
        p { text-align: center; font-size: 14px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; }
    </style>
</head>
<body onload="window.print()">
    <h1>Relatório de Ordens de Serviço</h1>
    <p>
        <strong>Período:</strong> <?= date('d/m/Y', strtotime($data_inicial)) ?> até <?= date('d/m/Y', strtotime($data_final)) ?><br>
        <strong>Status:</strong> <?= ucwords(str_replace('_', ' ', $status ?? 'Todos')) ?>
    </p>

    <table>
        <thead>
            <tr>
                <th>Nº OS</th>
                <th>Cliente</th>
                <th>Equipamento</th>
                <th>Status</th>
                <th>Data Entrada</th>
                <th>Valor Total</th>
            </tr>
        </thead>
        <tbody>
            <?php $total = 0; ?>
            <?php if (!empty($ordens)): ?>
                <?php foreach ($ordens as $os): ?>
                <tr>
                    <td><?= $os['id'] ?></td>
                    <td><?= esc($os['cliente_nome']) ?></td>
                    <td><?= esc($os['equip_marca'] . ' ' . $os['equip_modelo']) ?></td>
                    <td><?= ucwords(str_replace('_', ' ', $os['status'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($os['created_at'])) ?></td>
                    <td class="text-right">R$ <?= number_format($os['valor_total'] ?? 0, 2, ',', '.') ?></td>
                </tr>
                <?php $total += ($os['valor_total'] ?? 0); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Nenhuma ordem de serviço encontrada</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Total:</th>
                <th class="text-right">R$ <?= number_format($total, 2, ',', '.') ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Impresso em: <?= date('d/m/Y H:i:s') ?>
    </div>
</body>
</html>
