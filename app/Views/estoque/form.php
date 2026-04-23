<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = isset($peca);
$tiposEquipamento = array_values(array_filter((array) ($tiposEquipamento ?? []), static fn ($v) => trim((string) $v) !== ''));
$categoriasPeca = array_values(array_filter((array) ($categoriasPeca ?? []), static fn ($v) => trim((string) $v) !== ''));
$precificacaoEncargos = (float) str_replace(',', '.', (string) get_config('precificacao_peca_encargos_percentual', '15'));
$precificacaoMargem = (float) str_replace(',', '.', (string) get_config('precificacao_peca_margem_percentual', '45'));
$precificacaoBase = strtolower(trim((string) get_config('precificacao_peca_base', 'custo')));
if (!in_array($precificacaoBase, ['custo', 'venda'], true)) {
    $precificacaoBase = 'custo';
}
$precificacaoRespeitarPrecoVenda = !in_array(
    strtolower(trim((string) get_config('precificacao_peca_respeitar_preco_venda', '1'))),
    ['0', 'false', 'não', 'no'],
    true
);
$categoriaOverrideUrl = base_url('precificacao/categoria-override');
?>

<div class="page-header">
    <h2><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= esc((string) ($title ?? 'Peca')) ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('estoque')" title="Ajuda sobre Estoque">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('estoque') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('estoque') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= $isEdit ? base_url('estoque/atualizar/' . $peca['id']) : base_url('estoque/salvar') ?>" method="POST">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Codigo</label>
                    <input type="text" name="codigo" class="form-control" value="<?= $isEdit ? esc((string) ($peca['codigo'] ?? '')) : esc((string) ($codigo ?? '')) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" class="form-control" required value="<?= $isEdit ? esc((string) ($peca['nome'] ?? '')) : '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Categoria</label>
                    <select name="categoria" class="form-select">
                        <option value="">Selecionar categoria...</option>
                        <?php foreach ($categoriasPeca as $categoria): ?>
                            <option value="<?= esc($categoria) ?>" <?= $isEdit && strtolower(trim((string) ($peca['categoria'] ?? ''))) === strtolower(trim((string) $categoria)) ? 'selected' : '' ?>>
                                <?= esc($categoria) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Tipo Equipamento</label>
                    <input
                        type="text"
                        name="tipo_equipamento"
                        class="form-control"
                        list="tiposEquipamentoList"
                        value="<?= $isEdit ? esc((string) ($peca['tipo_equipamento'] ?? '')) : '' ?>"
                        placeholder="Ex: Smartphone, Notebook, Diverso"
                    >
                    <datalist id="tiposEquipamentoList">
                        <?php foreach ($tiposEquipamento as $tipoEquip): ?>
                            <option value="<?= esc($tipoEquip) ?>"></option>
                        <?php endforeach; ?>
                        <option value="diverso"></option>
                    </datalist>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Preco Custo (R$) *</label>
                    <input type="number" step="0.01" name="preco_custo" class="form-control" required value="<?= $isEdit ? (float) ($peca['preco_custo'] ?? 0) : '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Preco Venda (R$) *</label>
                    <input type="number" step="0.01" name="preco_venda" class="form-control" required value="<?= $isEdit ? (float) ($peca['preco_venda'] ?? 0) : '' ?>">
                </div>
                <div class="col-12">
                    <div
                        class="alert alert-info mb-0 peca-pricing-preview"
                        id="pecaPricingPreview"
                        data-base="<?= esc($precificacaoBase) ?>"
                        data-encargos="<?= esc(number_format($precificacaoEncargos, 2, '.', '')) ?>"
                        data-margem="<?= esc(number_format($precificacaoMargem, 2, '.', '')) ?>"
                        data-respeitar-venda="<?= $precificacaoRespeitarPrecoVenda ? '1' : '0' ?>"
                    >
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <strong class="mb-0">Simulacao de peca instalada (precificacao automatica)</strong>
                            <span class="badge text-bg-primary" id="pecaPricingConfigBadge">Base custo</span>
                        </div>
                        <div class="small text-muted mb-3">
                            Configuração atual: encargos <strong id="pecaPricingEncargosPct">0,00%</strong> + margem <strong id="pecaPricingMargemPct">0,00%</strong>.<br>
                            Essa regra e aplicada no orcamento/OS para garantir piso minimo da peca instalada.
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label form-label-sm text-muted mb-1">Preco base aplicado</label>
                                <input type="text" class="form-control form-control-sm" id="pecaPricingBaseValor" readonly>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label form-label-sm text-muted mb-1">Encargos (R$)</label>
                                <input type="text" class="form-control form-control-sm" id="pecaPricingEncargosValor" readonly>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label form-label-sm text-muted mb-1">Margem (R$)</label>
                                <input type="text" class="form-control form-control-sm" id="pecaPricingMargemValor" readonly>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label form-label-sm text-muted mb-1">Recomendado instalado</label>
                                <input type="text" class="form-control form-control-sm fw-semibold" id="pecaPricingRecomendadoValor" readonly>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                            <small id="pecaPricingAviso" class="text-muted mb-0"></small>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAplicarPrecoRecomendado">
                                <i class="bi bi-magic me-1"></i>Usar recomendado no preco de venda
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label">Quantidade</label>
                    <input type="number" name="quantidade_atual" class="form-control" value="<?= $isEdit ? (int) ($peca['quantidade_atual'] ?? 0) : 0 ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estoque Min.</label>
                    <input type="number" name="estoque_minimo" class="form-control" value="<?= $isEdit ? (int) ($peca['estoque_minimo'] ?? 1) : 1 ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estoque Max.</label>
                    <input type="number" name="estoque_maximo" class="form-control" value="<?= $isEdit ? esc((string) ($peca['estoque_maximo'] ?? '')) : '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cod. Fabricante</label>
                    <input type="text" name="codigo_fabricante" class="form-control" value="<?= $isEdit ? esc((string) ($peca['codigo_fabricante'] ?? '')) : '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fornecedor</label>
                    <input type="text" name="fornecedor" class="form-control" value="<?= $isEdit ? esc((string) ($peca['fornecedor'] ?? '')) : '' ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Localizacao</label>
                    <input type="text" name="localizacao" class="form-control" placeholder="Ex: Prateleira A3" value="<?= $isEdit ? esc((string) ($peca['localizacao'] ?? '')) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Modelos Compativeis</label>
                    <textarea name="modelos_compativeis" class="form-control" rows="2"><?= $isEdit ? esc((string) ($peca['modelos_compativeis'] ?? '')) : '' ?></textarea>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label">Observacoes</label>
                    <textarea name="observacoes" class="form-control" rows="2"><?= $isEdit ? esc((string) ($peca['observacoes'] ?? '')) : '' ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-glow"><i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
                <a href="<?= base_url('estoque') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
    const custoInput = document.querySelector('input[name="preco_custo"]');
    const vendaInput = document.querySelector('input[name="preco_venda"]');
    const categoriaInput = document.querySelector('select[name="categoria"]');
    const preview = document.getElementById('pecaPricingPreview');
    const btnAplicar = document.getElementById('btnAplicarPrecoRecomendado');

    if (!custoInput || !vendaInput || !preview) {
        return;
    }

    const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    const percentual = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const baseConfig = String(preview.dataset.base || 'custo').toLowerCase() === 'venda' ? 'venda' : 'custo';
    let encargosPercent = Number(preview.dataset.encargos || 0);
    let margemPercent = Number(preview.dataset.margem || 0);
    const respeitarVenda = String(preview.dataset.respeitarVenda || '1') !== '0';

    const baseBadge = document.getElementById('pecaPricingConfigBadge');
    const encargosPctEl = document.getElementById('pecaPricingEncargosPct');
    const margemPctEl = document.getElementById('pecaPricingMargemPct');
    const baseValorEl = document.getElementById('pecaPricingBaseValor');
    const encargosValorEl = document.getElementById('pecaPricingEncargosValor');
    const margemValorEl = document.getElementById('pecaPricingMargemValor');
    const recomendadoValorEl = document.getElementById('pecaPricingRecomendadoValor');
    const avisoEl = document.getElementById('pecaPricingAviso');

    const toNumber = (value) => {
        if (value === null || value === undefined) return 0;
        const raw = String(value).replace(',', '.').replace(/[^\d.-]/g, '');
        const parsed = Number(raw);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const round2 = (value) => Math.round((value + Number.EPSILON) * 100) / 100;

    const calcular = () => {
        const precoCusto = Math.max(0, toNumber(custoInput.value));
        const precoVenda = Math.max(0, toNumber(vendaInput.value));

        const basePreferida = baseConfig === 'venda' ? precoVenda : precoCusto;
        const baseAlternativa = baseConfig === 'venda' ? precoCusto : precoVenda;
        const precoBase = basePreferida > 0 ? basePreferida : baseAlternativa;

        const valorEncargos = round2(precoBase * (encargosPercent / 100));
        const valorMargem = round2(precoBase * (margemPercent / 100));
        const calculado = round2(precoBase + valorEncargos + valorMargem);
        const recomendado = respeitarVenda ? Math.max(calculado, precoVenda) : calculado;

        if (baseBadge) {
            baseBadge.textContent = baseConfig === 'venda' ? 'Base venda' : 'Base custo';
        }
        if (encargosPctEl) encargosPctEl.textContent = `${percentual.format(encargosPercent)}%`;
        if (margemPctEl) margemPctEl.textContent = `${percentual.format(margemPercent)}%`;
        if (baseValorEl) baseValorEl.value = moeda.format(precoBase);
        if (encargosValorEl) encargosValorEl.value = moeda.format(valorEncargos);
        if (margemValorEl) margemValorEl.value = moeda.format(valorMargem);
        if (recomendadoValorEl) recomendadoValorEl.value = moeda.format(recomendado);

        if (avisoEl) {
            if (baseConfig === 'venda') {
                avisoEl.className = 'text-warning mb-0';
                avisoEl.textContent = 'Base configurada como venda: o recomendado considera percentual sobre o proprio preco de venda.';
            } else if (precoVenda > 0 && precoVenda < recomendado) {
                avisoEl.className = 'text-warning mb-0';
                avisoEl.textContent = `Preco de venda atual esta abaixo do recomendado para peca instalada (${moeda.format(recomendado)}).`;
            } else if (precoVenda > 0 && precoVenda >= recomendado) {
                avisoEl.className = 'text-success mb-0';
                avisoEl.textContent = 'Preco de venda esta coerente com o recomendado para peca instalada.';
            } else {
                avisoEl.className = 'text-muted mb-0';
                avisoEl.textContent = 'Preencha custo e venda para visualizar comparativo completo.';
            }
        }

        if (btnAplicar) {
            const bloquearAplicacao = baseConfig === 'venda';
            btnAplicar.disabled = bloquearAplicacao;
            btnAplicar.title = bloquearAplicacao
                ? 'Aplicacao automatica indisponivel quando a base de calculo esta em venda.'
                : '';
        }

        return recomendado;
    };

    const carregarOverrideCategoria = async () => {
        if (!categoriaInput) return;
        const categoria = String(categoriaInput.value || '').trim();
        if (!categoria) {
            encargosPercent = Number(preview.dataset.encargos || 0);
            margemPercent = Number(preview.dataset.margem || 0);
            calcular();
            return;
        }

        try {
            const url = new URL(<?= json_encode($categoriaOverrideUrl) ?>, window.location.origin);
            url.searchParams.set('tipo', 'peca');
            url.searchParams.set('categoria', categoria);
            const response = await fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (data?.override) {
                encargosPercent = Number(data.override.encargos_percentual || 0);
                margemPercent = Number(data.override.margem_percentual || 0);
            } else {
                encargosPercent = Number(preview.dataset.encargos || 0);
                margemPercent = Number(preview.dataset.margem || 0);
            }
            calcular();
        } catch (error) {
            console.error('[Estoque] Falha ao carregar override de categoria.', error);
            encargosPercent = Number(preview.dataset.encargos || 0);
            margemPercent = Number(preview.dataset.margem || 0);
            calcular();
        }
    };

    const aplicarRecomendado = async () => {
        if (baseConfig === 'venda') {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                await window.Swal.fire({
                    icon: 'info',
                    title: 'Ação indisponivel para base venda',
                    text: 'Para aplicacao automatica no cadastro da peca, use a base de precificacao em custo.',
                    confirmButtonText: 'Entendi',
                });
            }
            return;
        }
        const recomendado = calcular();
        vendaInput.value = round2(recomendado).toFixed(2);
        calcular();
        if (window.Swal && typeof window.Swal.fire === 'function') {
            await window.Swal.fire({
                icon: 'success',
                title: 'Preco recomendado aplicado',
                text: 'O valor recomendado foi aplicado no campo Preco Venda.',
                toast: true,
                position: 'top-end',
                timer: 2200,
                showConfirmButton: false,
                timerProgressBar: true,
            });
        }
    };

    ['input', 'change', 'blur'].forEach((eventName) => {
        custoInput.addEventListener(eventName, calcular);
        vendaInput.addEventListener(eventName, calcular);
    });

    ['change', 'blur'].forEach((eventName) => {
        categoriaInput?.addEventListener(eventName, () => {
            carregarOverrideCategoria().catch(() => {});
        });
    });

    btnAplicar?.addEventListener('click', () => {
        aplicarRecomendado().catch((error) => {
            console.error('[Estoque] Falha ao aplicar preco recomendado.', error);
        });
    });

    calcular();
    carregarOverrideCategoria().catch(() => {});
})();
</script>
<?= $this->endSection() ?>
