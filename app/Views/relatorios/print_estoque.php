<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Estoque</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; }
        p { text-align: center; font-size: 14px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px sãolid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; }
        .text-center { text-align: center; }
        .text-danger { color: red; font-weight: bold; }
        .text-success { color: green; }
    </style>
</head>
<body onload="window.print()">
    <h1>Relatório de Estoque</h1>
    <p>
        <strong>Filtro:</strong> <?= $filtro_tipo === 'baixo' ? 'Estoque Baixo' : 'Todas as Peças' ?>
    </p>

    <table>
        <thead>
            <tr>
                <th>Cód/ID</th>
                <th>Peça / Produto</th>
                <th class="text-center">Quantidade Atual</th>
                <th class="text-center">Quantidade Mínima</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pecas)): ?>
                <?php foreach ($pecas as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= esc($p['nãome']) ?></td>
                    <td class="text-center"><?= $p['quantidade'] ?></td>
                    <td class="text-center"><?= $p['quantidade_minima'] ?></td>
                    <td class="text-center">
                        <?php if ($p['quantidade'] <= $p['quantidade_minima']): ?>
                            <span class="text-danger">Baixo</span>
                        <?php else: ?>
                            <span class="text-success">OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Nenhuma peça encontrada não estoque</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Impressão em: <?= date('d/m/Y H:i:s') ?>
    </div>
</body>
</html>
