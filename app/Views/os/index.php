<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .os-list-page .os-filtros-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: flex-end;
    }

    .os-list-page .os-filtros-actions .btn-reset {
        min-width: 42px;
    }

    @media (max-width: 1199.98px) {
        .os-list-page .os-filtros-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .os-list-page .os-filtros-actions {
            width: 100%;
        }

        .os-list-page .os-filtros-actions .btn {
            width: 100%;
        }

        .os-list-page .os-filtros-actions .btn-reset {
            width: 100%;
        }
    }
</style>

<div class="os-list-page">
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <h2><i class="bi bi-clipboard-check me-2"></i>Ordens de Servico</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('ordens-de-servico')" title="Ajuda sobre este modulo">
            <i class="bi bi-question-circle me-1"></i> Ajuda
        </button>
    </div>
    <?php if (can('os', 'criar')): ?>
    <a href="<?= base_url('os/nova') ?>" class="btn btn-glow">
        <i class="bi bi-plus-lg me-1"></i>Nova OS
    </a>
    <?php endif; ?>
</div>

<div class="card glass-card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-xxl-3 col-xl-4 col-lg-6">
                <label class="form-label">Status detalhado</label>
                <select id="filtroStatusSelect" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (($statusGrouped ?? []) as $macro => $items): ?>
                        <?php if (empty($items)) continue; ?>
                        <optgroup label="<?= esc(ucwords(str_replace('_', ' ', (string) $macro))) ?>">
                            <?php foreach ($items as $item): ?>
                                <option value="<?= esc($item['codigo']) ?>" <?= (($filtro_status ?? '') === ($item['codigo'] ?? '')) ? 'selected' : '' ?>>
                                    <?= esc($item['nome'] ?? $item['codigo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-xxl-3 col-xl-4 col-lg-6">
                <label class="form-label">Macrofase</label>
                <select id="filtroMacrofaseSelect" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach (($macrofases ?? []) as $codigo => $nome): ?>
                        <option value="<?= esc($codigo) ?>" <?= (($filtro_macrofase ?? '') === $codigo) ? 'selected' : '' ?>>
                            <?= esc($nome) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-xxl-3 col-xl-4 col-lg-6">
                <label class="form-label">Estado do fluxo</label>
                <select id="filtroFluxoSelect" class="form-select">
                    <option value="">Todos</option>
                    <?php
                    $estados = [
                        'em_atendimento' => 'Em atendimento',
                        'em_execucao' => 'Em execucao',
                        'pausado' => 'Pausado',
                        'pronto' => 'Pronto',
                        'encerrado' => 'Encerrado',
                        'cancelado' => 'Cancelado',
                    ];
                    foreach ($estados as $codigo => $nome):
                    ?>
                        <option value="<?= esc($codigo) ?>" <?= (($filtro_estado_fluxo ?? '') === $codigo) ? 'selected' : '' ?>>
                            <?= esc($nome) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-xxl-3 col-xl-12 col-lg-6">
                <div class="os-filtros-actions">
                    <button type="button" class="btn btn-glow" id="btnAplicarFiltros">
                        <i class="bi bi-funnel me-1"></i>Aplicar
                    </button>
                    <a href="<?= base_url('os') ?>" class="btn btn-outline-secondary d-flex align-items-center justify-content-center btn-reset" title="Limpar filtros">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card os-table-wrap ds-table-responsive-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="osTable">
                <thead>
                    <tr>
                        <th>N OS</th>
                        <th>Cliente</th>
                        <th>Equipamento</th>
                        <th>Relato</th>
                        <th>Data Abertura</th>
                        <th>Status</th>
                        <th>Valor Total</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusSel = document.getElementById('filtroStatusSelect');
    const macroSel = document.getElementById('filtroMacrofaseSelect');
    const fluxoSel = document.getElementById('filtroFluxoSelect');
    const btnAplicar = document.getElementById('btnAplicarFiltros');
    const tableEl = document.getElementById('osTable');

    const applyCellLabels = () => {
        if (!tableEl) return;
        const headers = Array.from(tableEl.querySelectorAll('thead th')).map((th) => (th.textContent || '').trim());
        const rows = tableEl.querySelectorAll('tbody tr');
        rows.forEach((row) => {
            row.querySelectorAll('td').forEach((td, idx) => {
                td.setAttribute('data-label', headers[idx] || '');
                if (idx === headers.length - 1) {
                    td.classList.add('col-acoes');
                }
            });
        });
    };

    const applyResponsiveColumns = (dt) => {
        const w = window.innerWidth || 1200;

        // baseline desktop
        dt.column(0).visible(true); // N OS
        dt.column(1).visible(true); // Cliente
        dt.column(2).visible(true); // Equipamento
        dt.column(3).visible(true); // Relato
        dt.column(4).visible(true); // Data abertura
        dt.column(5).visible(true); // Status
        dt.column(6).visible(true); // Valor
        dt.column(7).visible(true); // Acoes

        if (w < 1500) {
            dt.column(3).visible(false); // relato
        }

        if (w < 1280) {
            dt.column(6).visible(false); // valor
        }

        if (w < 1024) {
            dt.column(2).visible(false); // equipamento
        }

        if (w < 860) {
            dt.column(4).visible(false); // data
        }

        dt.columns.adjust();
        applyCellLabels();
    };

    const table = $('#osTable').DataTable({
        language: {
            url: '<?= base_url("assets/json/pt-BR.json") ?>'
        },
        scrollX: false,
        autoWidth: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url("os/datatable") ?>',
            type: 'POST',
            data: function (d) {
                d.<?= csrf_token() ?> = '<?= csrf_hash() ?>';
                d.status = statusSel?.value || '';
                d.macrofase = macroSel?.value || '';
                d.estado_fluxo = fluxoSel?.value || '';
            }
        },
        order: [[4, 'desc']],
        drawCallback: function () {
            applyCellLabels();
        },
        initComplete: function () {
            applyResponsiveColumns(this.api());
        }
    });

    const reloadTable = () => table.ajax.reload();
    btnAplicar?.addEventListener('click', reloadTable);
    statusSel?.addEventListener('change', reloadTable);
    macroSel?.addEventListener('change', reloadTable);
    fluxoSel?.addEventListener('change', reloadTable);

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        if (resizeTimer) clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            applyResponsiveColumns(table);
        }, 120);
    });
});
</script>
<?= $this->endSection() ?>
