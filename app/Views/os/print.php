<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>OS <?= esc($os['numero_os']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin-bottom: 4px; }
        .header p { font-size: 11px; color: #666; }
        .section { margin-bottom: 15px; }
        .section-title { background: #f0f0f0; padding: 6px 10px; font-weight: bold; font-size: 13px; margin-bottom: 8px; border-left: 3px solid #333; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; padding: 0 10px; }
        .info-item { display: flex; gap: 6px; padding: 3px 0; }
        .info-label { font-weight: bold; min-width: 100px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background: #f0f0f0; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
        .signature { margin-top: 40px; display: flex; justify-content: space-around; }
        .signature div { text-align: center; width: 200px; }
        .signature-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 4px; }
        @media print { body { padding: 10px; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>ORDEM DE SERVIÇO</h1>
        <p>Nº <?= esc($os['numero_os']) ?> | Data: <?= formatDate($os['data_abertura'], true) ?></p>
    </div>

    <div class="section">
        <div class="section-title">DADOS DO CLIENTE</div>
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Cliente:</span> <?= esc($os['cliente_nome']) ?></div>
            <div class="info-item"><span class="info-label">Telefone:</span> <?= esc($os['cliente_telefone'] ?? '-') ?></div>
            <div class="info-item"><span class="info-label">Email:</span> <?= esc($os['cliente_email'] ?? '-') ?></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">EQUIPAMENTO</div>
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Tipo:</span> <?= getEquipTipo($os['equip_tipo']) ?></div>
            <div class="info-item"><span class="info-label">Marca/Modelo:</span> <?= esc($os['equip_marca'] . ' ' . $os['equip_modelo']) ?></div>
            <div class="info-item"><span class="info-label">Nº Série:</span> <?= esc($os['equip_serie'] ?? '-') ?></div>
            <div class="info-item"><span class="info-label">Status:</span> <?= ucfirst(str_replace('_', ' ', $os['status'])) ?></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">RELATO DO CLIENTE</div>
        <p style="padding: 0 10px;"><?= nl2br(esc($os['relato_cliente'])) ?></p>
    </div>

    <?php if (!empty($os['diagnostico_tecnico'])): ?>
    <div class="section">
        <div class="section-title">DIAGNÓSTICO TÉCNICO</div>
        <p style="padding: 0 10px;"><?= nl2br(esc($os['diagnostico_tecnico'])) ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($defeitos)): ?>
    <div class="section">
        <div class="section-title">BASE DE CONHECIMENTO - CHECKLIST DE REPARO</div>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; padding: 0 10px;">
            <?php foreach($defeitos as $def): ?>
            <div style="flex: 1; min-width: 45%; margin-bottom: 10px;">
                <p style="font-weight: bold; border-bottom: 1px dotted #ccc; margin-bottom: 5px;"><?= esc($def['nome']) ?>:</p>
                <?php foreach($def['procedimentos'] as $idx => $proc): ?>
                <div style="display: flex; margin-bottom: 3px;">
                    <span style="width: 15px; height: 15px; border: 1px solid #333; display: inline-block; margin-right: 5px;"></span>
                    <span style="font-size: 10px;"><?= esc($proc['descricao']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($itens)): ?>
    <div class="section">
        <div class="section-title">SERVIÇOS E PEÇAS</div>
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Descrição</th>
                    <th>Qtd</th>
                    <th class="text-right">Valor Unit.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                <tr>
                    <td><?= $item['tipo'] === 'servico' ? 'Serviço' : 'Peça' ?></td>
                    <td><?= esc($item['descricao']) ?></td>
                    <td><?= $item['quantidade'] ?></td>
                    <td class="text-right"><?= formatMoney($item['valor_unitario']) ?></td>
                    <td class="text-right"><?= formatMoney($item['valor_total']) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4" class="text-right">Total:</td>
                    <td class="text-right"><?= formatMoney($os['valor_total']) ?></td>
                </tr>
                <?php if ($os['desconto'] > 0): ?>
                <tr>
                    <td colspan="4" class="text-right">Desconto:</td>
                    <td class="text-right">- <?= formatMoney($os['desconto']) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td colspan="4" class="text-right">VALOR FINAL:</td>
                    <td class="text-right"><?= formatMoney($os['valor_final']) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="section">
        <p style="padding: 5px 10px; font-size: 11px;">
            <strong>Garantia:</strong> <?= $os['garantia_dias'] ?> dias a partir da data de entrega.
            <?php if (!empty($os['garantia_validade'])): ?>
            Válida até <?= formatDate($os['garantia_validade']) ?>.
            <?php endif; ?>
        </p>
    </div>

    <div class="signature">
        <div>
            <div class="signature-line">Técnico Responsável</div>
        </div>
        <div>
            <div class="signature-line">Cliente</div>
        </div>
    </div>

    <div class="footer">
        <p>Documento gerado em <?= date('d/m/Y H:i:s') ?></p>
    </div>
</body>
</html>
