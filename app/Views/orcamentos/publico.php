<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orçamento <?= esc((string) ($orcamento['numero'] ?? '')) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
    <style>
        body { background: #f4f6f9; }
        .orc-public-wrap { max-width: 920px; margin: 24px auto; }
        .orc-card { border: 0; border-radius: 14px; box-shadow: 0 12px 30px rgba(0,0,0,.08); }
        @media (max-width: 430px) {
            .orc-public-wrap { margin: 12px auto; padding: 0 8px; }
        }
    </style>
</head>
<body>
<?php
$statusLabels = $statusLabels ?? [];
$tipoLabels = $tipoLabels ?? [];
$status = (string) ($orcamento['status'] ?? 'rascunho');
$tipoOrcamento = (string) ($orcamento['tipo_orcamento'] ?? 'previo');
$clienteNome = trim((string) ($orcamento['cliente_nome'] ?? ''));
if ($clienteNome === '') {
    $clienteNome = (string) ($orcamento['cliente_nome_avulso'] ?? 'Cliente');
}
?>
<div class="container orc-public-wrap">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc((string) session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card orc-card mb-3">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
    <h4 class="mb-1">Orçamento <?= esc((string) ($orcamento['numero'] ?? '')) ?></h4>
                    <div class="text-muted">Cliente: <?= esc($clienteNome) ?></div>
                    <div class="small text-muted mt-1">Tipo: <?= esc($tipoLabels[$tipoOrcamento] ?? ucfirst($tipoOrcamento)) ?></div>
                </div>
                <span class="badge bg-secondary"><?= esc($statusLabels[$status] ?? ucfirst($status)) ?></span>
            </div>

            <?php if ($tipoOrcamento === 'previo'): ?>
                <div class="alert alert-info small">
                    Esta pagina apresenta uma <strong>estimativa inicial</strong>. Se o equipamento ainda não estiver na assistencia, os valores podem ser confirmados ou ajustados apos a analise presencial.
                </div>
            <?php else: ?>
                <div class="alert alert-light border small">
                    Esta pagina apresenta o <strong>orcamento tecnico</strong> do equipamento que ja esta em assistencia.
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-3">
                    <div class="small text-muted">Validade</div>
                    <div><?= esc(formatDate($orcamento['validade_data'] ?? null)) ?></div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="small text-muted">Telefone</div>
                    <div><?= esc((string) ($orcamento['telefone_contato'] ?? '-')) ?></div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="small text-muted">Email</div>
                    <div><?= esc((string) ($orcamento['email_contato'] ?? '-')) ?></div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="small text-muted">Total</div>
                    <div class="fw-semibold fs-5"><?= esc(formatMoney($orcamento['total'] ?? 0)) ?></div>
                </div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qtd.</th>
                            <th>Valor unit.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($itens ?? []) as $item): ?>
                        <tr>
                            <td><?= esc((string) ($item['descricao'] ?? '-')) ?></td>
                            <td><?= esc(number_format((float) ($item['quantidade'] ?? 0), 2, ',', '.')) ?></td>
                            <td><?= esc(formatMoney($item['valor_unitario'] ?? 0)) ?></td>
                            <td><?= esc(formatMoney($item['total'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($orcamento['condicoes'])): ?>
                <div class="mb-3">
                    <div class="small text-muted">Condicoes</div>
                    <div><?= nl2br(esc((string) $orcamento['condicoes'])) ?></div>
                </div>
            <?php endif; ?>

            <?php if (in_array($status, ['aprovado', 'pendente_abertura_os', 'pacote_aprovado', 'pendente', 'rejeitado', 'cancelado', 'convertido'], true)): ?>
                <div class="alert alert-info mb-0">
            Este orçamento já foi finalizado com o status: <strong><?= esc($statusLabels[$status] ?? ucfirst($status)) ?></strong>.
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <form method="POST" action="<?= base_url('orcamento/aprovar/' . (string) ($orcamento['token_publico'] ?? '')) ?>">
                            <?= csrf_field() ?>
                            <label class="form-label">Mensagem (opcional)</label>
                            <textarea name="resposta_cliente" class="form-control mb-2" rows="3" placeholder="Ex.: Pode seguir com o servico."></textarea>
                    <button type="submit" class="btn btn-success w-100"><?= $tipoOrcamento === 'previo' ? 'Aprovar estimativa inicial' : 'Aprovar orçamento' ?></button>
                        </form>
                    </div>
                    <div class="col-12 col-md-6">
                        <form method="POST" action="<?= base_url('orcamento/recusar/' . (string) ($orcamento['token_publico'] ?? '')) ?>">
                            <?= csrf_field() ?>
                            <label class="form-label">Motivo da rejeicao</label>
                            <textarea name="resposta_cliente" class="form-control mb-2" rows="3" placeholder="Ex.: Valor acima do esperado."></textarea>
                    <button type="submit" class="btn btn-outline-danger w-100">Rejeitar orçamento</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
