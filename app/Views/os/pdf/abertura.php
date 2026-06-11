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
    <tr><td class="label">Numero da OS</td><td><?= esc($os['numero_os']) ?></td><td class="label">Data de abertura</td><td><?= esc(formatDate($os['data_abertura'], true)) ?></td></tr>
    <tr><td class="label">Cliente</td><td><?= esc($os['cliente_nome']) ?></td><td class="label">Telefone</td><td><?= esc($os['cliente_telefone'] ?? '-') ?></td></tr>
    <tr><td class="label">Equipamento</td><td><?= esc(equipamento_nome_exibicao($os)) ?></td><td class="label">Serie</td><td><?= esc($os['equip_serie'] ?? '-') ?></td></tr>
    <tr><td class="label">Status</td><td><?= esc($os['status']) ?></td><td class="label">Prioridade</td><td><?= esc($os['prioridade'] ?? 'normal') ?></td></tr>
</table>

<div class="section-title">Relato do cliente</div>
<div><?= nl2br(esc($os['relato_cliente'] ?? '-')) ?></div>

<div class="section-title">Acessorios recebidos</div>
<?php if (empty($payload['acessorios'])): ?>
<div class="muted">Nenhum acessorio registrado.</div>
<?php else: ?>
<ul>
<?php foreach ($payload['acessorios'] as $acc): ?>
<?php
    $accLabel = trim((string) ($acc['descricao'] ?? '-'));
    $accResumo = array_values(array_filter(array_map(
        static fn ($item): string => trim((string) $item),
        (array) ($acc['valores_resumo'] ?? [])
    ), static fn (string $item): bool => $item !== ''));
?>
<li><?= esc($accResumo ? ($accLabel . ' (' . implode(' | ', $accResumo) . ')') : $accLabel) ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<div class="section-title">Estado fisico na entrada</div>
<?php
    $checklistEntrada = is_array($payload['checklist_entrada'] ?? null) ? $payload['checklist_entrada'] : null;
    $checklistPendencias = array_values(array_filter(array_map(
        static function (array $item): ?array {
            $status = strtolower(trim((string) ($item['status'] ?? '')));
            if ($status !== 'discrepancia') {
                return null;
            }

            $descricao = trim((string) ($item['descricao'] ?? ''));
            if ($descricao === '') {
                return null;
            }

            return [
                'descricao' => $descricao,
                'observacao' => trim((string) ($item['observacao'] ?? '')),
            ];
        },
        (array) ($checklistEntrada['itens'] ?? [])
    )));
    $observacoesEstadoChecklist = trim((string) ($checklistEntrada['observacoes_estado'] ?? ''));
?>
<?php if (empty($payload['estado_fisico']) && $checklistPendencias === [] && $observacoesEstadoChecklist === ''): ?>
<div class="muted">Sem avarias registradas.</div>
<?php endif; ?>
<?php if (!empty($payload['estado_fisico'])): ?>
<ul>
<?php foreach ($payload['estado_fisico'] as $estado): ?>
<?php
    $estadoLabel = trim((string) ($estado['descricao_dano'] ?? '-'));
    $estadoResumo = array_values(array_filter(array_map(
        static fn ($item): string => trim((string) $item),
        (array) ($estado['valores_resumo'] ?? [])
    ), static fn (string $item): bool => $item !== ''));
?>
<li><?= esc($estadoResumo ? ($estadoLabel . ' (' . implode(' | ', $estadoResumo) . ')') : $estadoLabel) ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php if ($checklistPendencias !== []): ?>
<div class="section-title">Pendencias do checklist de entrada</div>
<ul>
<?php foreach ($checklistPendencias as $pendencia): ?>
<li>
    <?= esc((string) ($pendencia['descricao'] ?? '-')) ?>
    <?php if (!empty($pendencia['observacao'])): ?>
        - <?= esc((string) $pendencia['observacao']) ?>
    <?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php if ($observacoesEstadoChecklist !== ''): ?>
<div class="section-title">Observacoes do estado na entrada</div>
<div><?= nl2br(esc($observacoesEstadoChecklist)) ?></div>
<?php endif; ?>

<?php if (!empty($payload['include_photos']) && !empty($payload['photo_groups'])): ?>
<?= view('os/pdf/_photo_annex', ['photoGroups' => (array) ($payload['photo_groups'] ?? [])]) ?>
<?php endif; ?>

<div class="footer">Documento emitido automaticamente pelo Sistema de Assistencia Tecnica.</div>
</body>
</html>
