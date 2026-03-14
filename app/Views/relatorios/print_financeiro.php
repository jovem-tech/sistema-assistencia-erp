<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório Financeiro</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; }
        p { text-align: center; font-size: 14px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; }
        .resumo { display: flex; justify-content: space-around; margin-bottom: 20px; text-align: center; }
        .resumo-item { border: 1px solid #ccc; padding: 10px; width: 30%; font-weight: bold; }
        .text-success { color: green; }
        .text-danger { color: red; }
    </style>
</head>
<body onload="window.print()">
    <h1>Relatório Financeiro</h1>
    <p>
        <strong>Mês/Ano Ref:</strong> <?= empty($filtro_mes) ? 'Todos' : date('m/Y', strtotime($filtro_mes . '-01')) ?>
    </p>

    <div class="resumo">
        <div class="resumo-item text-success">
            Receitas Pagas <br><br>
            R$ <?= number_format($resumo['receitas'], 2, ',', '.') ?>
        </div>
        <div class="resumo-item text-danger">
            Despesas Pagas <br><br>
            R$ <?= number_format($resumo['despesas'], 2, ',', '.') ?>
        </div>
        <div class="resumo-item">
            Lucro Mês <br><br>
            R$ <?= number_format($resumo['lucro'], 2, ',', '.') ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Tipo</th>
                <th>Vencimento</th>
                <th>Valor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($lancamentos)): ?>
                <?php foreach ($lancamentos as $l): ?>
                <tr>
                    <td><?= esc($l['descricao']) ?></td>
                    <td><?= $l['tipo'] === 'receber' ? 'Receita' : 'Despesa' ?></td>
                    <td><?= date('d/m/Y', strtotime($l['data_vencimento'])) ?></td>
                    <td class="text-right">R$ <?= number_format($l['valor'], 2, ',', '.') ?></td>
                    <td><?= ucfirst($l['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Nenhum lançamento no período</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Impresso em: <?= date('d/m/Y H:i:s') ?>
    </div>
</body>
</html>
