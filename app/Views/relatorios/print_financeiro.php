<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Movimentacoes Financeiras</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #111827; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 4px; }
        p { text-align: center; font-size: 14px; margin: 0 0 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background-color: #f3f4f6; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #6b7280; }
        .resumo { display: flex; justify-content: space-around; gap: 12px; margin-bottom: 20px; text-align: center; }
        .resumo-item { border: 1px solid #d1d5db; padding: 10px; width: 100%; font-weight: bold; }
        .text-success { color: #15803d; }
        .text-danger { color: #b91c1c; }
        .text-primary { color: #1d4ed8; }
    </style>
</head>
<body onload="window.print()">
    <h1>Movimentacoes Financeiras</h1>
    <p>
        <strong>Mes/ano ref:</strong> <?= empty($filtro_mes) ? 'Todos' : date('m/Y', strtotime($filtro_mes . '-01')) ?>
    </p>

    <div class="resumo">
        <div class="resumo-item text-success">
            Entradas realizadas<br><br>
            <?= formatMoney($resumo['receitas'] ?? 0) ?>
        </div>
        <div class="resumo-item text-danger">
            Saidas realizadas<br><br>
            <?= formatMoney($resumo['despesas'] ?? 0) ?>
        </div>
        <div class="resumo-item text-primary">
            Resultado de caixa<br><br>
            <?= formatMoney($resumo['resultado_caixa'] ?? $resumo['lucro'] ?? 0) ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descricao</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Competencia</th>
                <th>Vencimento</th>
                <th>Ult. baixa</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Classificacao</th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($lancamentos)): ?>
                <?php foreach ($lancamentos as $l): ?>
                <tr>
                    <td><?= esc($l['descricao'] ?? '-') ?></td>
                    <td>
                        <?= esc($l['categoria_exibicao'] ?? $l['categoria'] ?? '-') ?><br>
                        <small><?= (($l['categoria_configurada'] ?? 0) == 1) ? 'Catalogada' : 'Legado/manual' ?></small>
                    </td>
                    <td><?= ($l['tipo'] ?? '') === 'receber' ? 'Receita' : 'Despesa' ?></td>
                    <td><?= formatCompetenceDate($l['data_competencia_resolvida'] ?? $l['data_competencia'] ?? '', $l['origem_tipo_resolvido'] ?? $l['origem_tipo'] ?? null) ?></td>
                    <td><?= ! empty($l['data_vencimento']) ? date('d/m/Y', strtotime($l['data_vencimento'])) : '-' ?></td>
                    <td><?= ! empty($l['data_pagamento_resolvida']) ? date('d/m/Y', strtotime($l['data_pagamento_resolvida'])) : (! empty($l['data_pagamento']) ? date('d/m/Y', strtotime($l['data_pagamento'])) : '-') ?></td>
                    <td class="text-right">
                        <?= formatMoney($l['valor_titulo'] ?? $l['valor'] ?? 0) ?><br>
                        <small>Quitado: <?= formatMoney($l['valor_movimentado'] ?? 0) ?></small><br>
                        <small>Aberto: <?= formatMoney($l['valor_aberto'] ?? 0) ?></small>
                    </td>
                    <td>
                        <?= ucfirst((string) ($l['status_resolvido'] ?? $l['status'] ?? '')) ?><br>
                        <small><?= (int) ($l['total_movimentos'] ?? 0) ?> baixa(s)</small>
                    </td>
                    <td>
                        <?= esc($l['grupo_dre_resolvido'] ?? $l['grupo_dre'] ?? '-') ?><br>
                        <small><?= esc($l['subgrupo_dre_resolvido'] ?? $l['subgrupo_dre'] ?? '-') ?></small>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center;">Nenhum lancamento no periodo.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Impresso em: <?= date('d/m/Y H:i:s') ?>
    </div>
</body>
</html>
