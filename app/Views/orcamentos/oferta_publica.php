<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Escolha do Pacote - Oferta</title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
    <style>
        :root {
            --brand: #2f4d8f;
            --ink: #12213f;
            --muted: #5b6a86;
        }
        body {
            background: radial-gradient(circle at top right, #e9f0ff 0%, #f7f9fc 45%, #eef2f8 100%);
            color: var(--ink);
        }
        .oferta-wrap {
            max-width: 1180px;
            margin: 22px auto;
            padding: 0 12px;
        }
        .hero-card,
        .nivel-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.09);
            background: #fff;
        }
        .hero-chip {
            border-radius: 999px;
            background: rgba(47, 77, 143, 0.12);
            color: var(--brand);
            font-weight: 600;
            font-size: .82rem;
            padding: .4rem .75rem;
        }
        .hero-subtitle {
            color: var(--muted);
            font-size: .92rem;
        }
        .nivel-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .nivel-topo {
            border-radius: 16px 16px 0 0;
            padding: 16px 18px;
            color: #fff;
        }
        .nivel-topo small {
            opacity: .92;
        }
        .nivel-body {
            padding: 16px 18px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            flex: 1;
        }
        .valor-principal {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1;
            color: #0f172a;
        }
        .faixa {
            font-size: .86rem;
            color: var(--muted);
        }
        .itens-lista {
            margin: 0;
            padding-left: 1rem;
            color: #334155;
            font-size: .9rem;
            display: grid;
            gap: .25rem;
        }
        .meta-line {
            font-size: .86rem;
            color: var(--muted);
        }
        .badge-destaque {
            border-radius: 999px;
            background: #fef3c7;
            color: #92400e;
            font-size: .73rem;
            padding: .22rem .55rem;
            font-weight: 700;
        }
        .status-box {
            border: 1px dashed #bfd0ef;
            border-radius: 12px;
            background: #f8fbff;
            padding: .85rem .95rem;
            font-size: .9rem;
            color: #31415f;
        }
        .status-box strong {
            color: #102241;
        }
        .btn-escolher {
            width: 100%;
            font-weight: 600;
        }
        @media (max-width: 430px) {
            .oferta-wrap {
                margin: 12px auto 16px;
                padding: 0 8px;
            }
            .hero-card,
            .nivel-card {
                border-radius: 12px;
            }
            .nivel-topo {
                border-radius: 12px 12px 0 0;
                padding: 14px;
            }
            .nivel-body {
                padding: 14px;
            }
            .valor-principal {
                font-size: 1.35rem;
            }
        }
        @media (max-width: 390px) {
            .hero-chip {
                font-size: .76rem;
            }
            .hero-subtitle {
                font-size: .85rem;
            }
            .itens-lista {
                font-size: .84rem;
            }
        }
        @media (max-width: 360px) {
            .btn-escolher {
                font-size: .88rem;
                padding: .45rem .55rem;
            }
            .faixa,
            .meta-line {
                font-size: .8rem;
            }
        }
        @media (max-width: 320px) {
            .valor-principal {
                font-size: 1.2rem;
            }
            .hero-subtitle {
                font-size: .8rem;
            }
        }
    </style>
</head>
<body>
<?php
$statusMap = [
    'ativo' => 'Ativa',
    'enviado' => 'Enviada',
    'escolhido' => 'Escolhido',
    'aplicado_orcamento' => 'Aplicado no orcamento',
    'expirado' => 'Expirada',
    'cancelado' => 'Cancelada',
    'erro_envio' => 'Erro de envio',
];
$isPreview = !empty($isPreview);
$statusOferta = (string) ($statusOferta ?? ($oferta['status'] ?? 'ativo'));
$clienteNome = trim((string) ($clienteNome ?? 'Cliente'));
$expiraEm = trim((string) ($oferta['expira_em'] ?? ''));
$pacoteNome = trim((string) ($oferta['pacote_nome'] ?? 'Pacote de Serviços'));
$pacoteDescricao = trim((string) ($oferta['pacote_descricao'] ?? ''));
$nivelEscolhido = trim((string) ($oferta['nivel_escolhido'] ?? ''));
$nivelEscolhidoNome = trim((string) ($oferta['nivel_nome_exibicao'] ?? ''));
$valorEscolhido = (float) ($oferta['valor_escolhido'] ?? 0);
?>
<div class="oferta-wrap">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success shadow-sm"><?= esc((string) session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger shadow-sm"><?= esc((string) session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="hero-card p-3 p-md-4 mb-3">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
            <div>
                <h4 class="mb-1">Escolha Seu Pacote</h4>
                <div class="hero-subtitle">Oferta personalizada para <?= esc($clienteNome) ?></div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="hero-chip">Status: <?= esc($statusMap[$statusOferta] ?? ucfirst($statusOferta)) ?></span>
            </div>
        </div>
        <div class="status-box">
            <strong><?= esc($pacoteNome) ?></strong>
            <?php if ($pacoteDescricao !== ''): ?>
                <div class="mt-1"><?= esc($pacoteDescricao) ?></div>
            <?php endif; ?>
            <?php if ($expiraEm !== ''): ?>
                <div class="mt-1">Valido ate: <strong><?= esc(formatDate($expiraEm, true)) ?></strong></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$isPreview && !$canChoose && $statusOferta === 'escolhido'): ?>
        <div class="alert alert-success mb-3">
            Pacote ja escolhido: <strong><?= esc($nivelEscolhidoNome !== '' ? $nivelEscolhidoNome : ucfirst($nivelEscolhido)) ?></strong>
            <?php if ($valorEscolhido > 0): ?>
                (<?= esc(formatMoney($valorEscolhido)) ?>)
            <?php endif; ?>.
        </div>
    <?php elseif (!$isPreview && !$canChoose && $statusOferta === 'aplicado_orcamento'): ?>
        <div class="alert alert-success mb-3">
            Escolha confirmada e aplicada no orcamento.
        </div>
    <?php elseif (!$isPreview && !$canChoose): ?>
        <div class="alert alert-warning mb-3">
            Esta oferta não aceita novas escolhas no momento. Solicite um novo link para a equipe.
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <?php foreach (($niveis ?? []) as $nivel): ?>
            <?php
            $nivelCode = trim((string) ($nivel['nivel'] ?? ''));
            $nomeExibicao = trim((string) ($nivel['nome_exibicao'] ?? ucfirst($nivelCode)));
            $corHex = strtoupper(trim((string) ($nivel['cor_hex'] ?? '#4F46E5')));
            if (!preg_match('/^#[0-9A-F]{6}$/', $corHex)) {
                $corHex = '#4F46E5';
            }
            $precoMin = (float) ($nivel['preco_min'] ?? 0);
            $precoRecomendado = (float) ($nivel['preco_recomendado'] ?? 0);
            $precoMax = (float) ($nivel['preco_max'] ?? 0);
            $prazo = trim((string) ($nivel['prazo_estimado'] ?? ''));
            $garantia = (int) ($nivel['garantia_dias'] ?? 0);
            $itensInclusosRaw = trim((string) ($nivel['itens_inclusos'] ?? ''));
            $itensInclusos = $itensInclusosRaw !== '' ? preg_split('/\r\n|\r|\n/', $itensInclusosRaw) : [];
            $argumentoVenda = trim((string) ($nivel['argumento_venda'] ?? ''));
            $isDestaque = (int) ($nivel['destaque'] ?? 0) === 1;
            $jaEscolhido = in_array($statusOferta, ['escolhido', 'aplicado_orcamento'], true)
                && $nivelCode !== ''
                && $nivelCode === $nivelEscolhido;
            ?>
            <div class="col-12 col-lg-4">
                <div class="nivel-card">
                    <div class="nivel-topo" style="background: <?= esc($corHex) ?>;">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <strong><?= esc($nomeExibicao) ?></strong>
                            <?php if ($isDestaque): ?>
                                <span class="badge-destaque">Mais escolhido</span>
                            <?php endif; ?>
                        </div>
                        <small>Nivel <?= esc(strtoupper($nivelCode)) ?></small>
                    </div>
                    <div class="nivel-body">
                        <div>
                            <div class="valor-principal"><?= esc(formatMoney($precoRecomendado)) ?></div>
                            <div class="faixa">Faixa: <?= esc(formatMoney($precoMin)) ?> a <?= esc(formatMoney($precoMax)) ?></div>
                        </div>

                        <div class="meta-line">
                            <?php if ($prazo !== ''): ?>
                                <div><strong>Prazo:</strong> <?= esc($prazo) ?></div>
                            <?php endif; ?>
                            <?php if ($garantia > 0): ?>
                                <div><strong>Garantia:</strong> <?= esc((string) $garantia) ?> dias</div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($itensInclusos)): ?>
                            <div>
                                <div class="small fw-semibold mb-1">Inclui:</div>
                                <ul class="itens-lista">
                                    <?php foreach ($itensInclusos as $itemTexto): ?>
                                        <?php $itemTexto = trim((string) $itemTexto); ?>
                                        <?php if ($itemTexto === '') continue; ?>
                                        <li><?= esc($itemTexto) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($argumentoVenda !== ''): ?>
                            <div class="small text-muted"><?= esc($argumentoVenda) ?></div>
                        <?php endif; ?>

                        <?php if ($isPreview): ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-escolher" disabled>
                                Visualizacao
                            </button>
                        <?php elseif ($jaEscolhido): ?>
                            <button type="button" class="btn btn-success btn-sm btn-escolher" disabled>
                                Pacote escolhido
                            </button>
                        <?php elseif ($canChoose): ?>
                            <form method="POST" action="<?= base_url('pacote/oferta/escolher/' . (string) ($oferta['token_publico'] ?? '')) ?>" data-confirm-pacote>
                                <?= csrf_field() ?>
                                <input type="hidden" name="nivel" value="<?= esc($nivelCode) ?>">
                                <button type="submit" class="btn btn-primary btn-sm btn-escolher">
                                    Escolher <?= esc($nomeExibicao) ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-escolher" disabled>
                                Indisponivel
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script>
(function () {
    const forms = document.querySelectorAll('form[data-confirm-pacote]');
    forms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const confirmed = await window.DSFeedback.confirm({
                icon: 'question',
                title: 'Confirmar pacote?',
                text: 'Ao confirmar, a equipe podera aplicar este nivel no seu orcamento.',
                showCancelButton: true,
                confirmButtonText: 'Confirmar escolha',
                cancelButtonText: 'Voltar',
            });

            if (!confirmed) {
                return;
            }

            form.dataset.confirmed = '1';
            form.submit();
        });
    });
})();
</script>
</body>
</html>
