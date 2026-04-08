<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$isEdit = !empty($isEdit);
$orcamento = $orcamento ?? [];
$itens = $itens ?? [];
$statusLabels = $statusLabels ?? [];
$clientes = $clientes ?? [];

if (empty($itens)) {
    $itens = [[
        'tipo_item' => 'servico',
        'descricao' => '',
        'quantidade' => 1,
        'valor_unitario' => 0,
        'desconto' => 0,
        'acrescimo' => 0,
        'total' => 0,
        'observacoes' => '',
    ]];
}
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="mb-0"><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= esc($title ?? 'Orcamento') ?></h2>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('orcamentos')" title="Ajuda sobre Orcamentos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (!empty($orcamento['id'])): ?>
            <a href="<?= base_url('orcamentos/visualizar/' . (int) $orcamento['id']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Voltar
            </a>
        <?php else: ?>
            <a href="<?= base_url('orcamentos') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Voltar
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= esc($actionUrl ?? base_url('orcamentos/salvar')) ?>" method="POST" id="orcamentoForm">
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-3">
                    <label class="form-label">Numero</label>
                    <input type="text" class="form-control" value="<?= esc((string) ($orcamento['numero'] ?? 'Gerado ao salvar')) ?>" readonly>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" id="orcamentoStatus">
                        <?php foreach ($statusLabels as $statusCode => $statusName): ?>
                            <?php $selected = ((string) ($orcamento['status'] ?? 'rascunho')) === $statusCode ? 'selected' : ''; ?>
                            <option value="<?= esc($statusCode) ?>" <?= $selected ?>><?= esc($statusName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Origem</label>
                    <select name="origem" class="form-select">
                        <?php
                        $origens = [
                            'manual' => 'Manual / Balcao',
                            'os' => 'Ordem de servico',
                            'conversa' => 'Central de mensagens',
                            'cliente' => 'Cadastro de cliente',
                        ];
                        foreach ($origens as $originCode => $originLabel):
                        ?>
                            <option value="<?= esc($originCode) ?>" <?= ((string) ($orcamento['origem'] ?? 'manual')) === $originCode ? 'selected' : '' ?>><?= esc($originLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Validade (dias)</label>
                    <input type="number" class="form-control" min="1" max="90" name="validade_dias" value="<?= esc((string) ($orcamento['validade_dias'] ?? 7)) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Validade (data)</label>
                    <input type="date" class="form-control" name="validade_data" value="<?= esc((string) ($orcamento['validade_data'] ?? '')) ?>">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">Titulo</label>
                    <input type="text" class="form-control" name="titulo" value="<?= esc((string) ($orcamento['titulo'] ?? '')) ?>" placeholder="Ex.: Orcamento para reparo de notebook">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Cliente cadastrado</label>
                    <select name="cliente_id" class="form-select" id="orcamentoClienteSelect">
                        <option value="">Cliente eventual (sem cadastro)</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <?php $selected = (int) ($orcamento['cliente_id'] ?? 0) === (int) $cliente['id'] ? 'selected' : ''; ?>
                            <option
                                value="<?= (int) $cliente['id'] ?>"
                                data-telefone="<?= esc((string) ($cliente['telefone1'] ?? '')) ?>"
                                data-email="<?= esc((string) ($cliente['email'] ?? '')) ?>"
                                <?= $selected ?>
                            >
                                <?= esc((string) ($cliente['nome_razao'] ?? ('Cliente #' . $cliente['id']))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Nome do cliente eventual</label>
                    <input type="text" class="form-control" name="cliente_nome_avulso" value="<?= esc((string) ($orcamento['cliente_nome_avulso'] ?? '')) ?>" placeholder="Preencher apenas para cliente sem cadastro">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Telefone de contato</label>
                    <input type="text" class="form-control" id="orcamentoTelefone" name="telefone_contato" value="<?= esc((string) ($orcamento['telefone_contato'] ?? '')) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Email de contato</label>
                    <input type="email" class="form-control" id="orcamentoEmail" name="email_contato" value="<?= esc((string) ($orcamento['email_contato'] ?? '')) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Prazo de execucao</label>
                    <input type="text" class="form-control" name="prazo_execucao" value="<?= esc((string) ($orcamento['prazo_execucao'] ?? '')) ?>" placeholder="Ex.: 3 dias uteis">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <label class="form-label">Vinculo OS</label>
                    <input type="number" class="form-control" name="os_id" value="<?= esc((string) ($orcamento['os_id'] ?? '')) ?>" placeholder="Opcional">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Vinculo Equipamento</label>
                    <input type="number" class="form-control" name="equipamento_id" value="<?= esc((string) ($orcamento['equipamento_id'] ?? '')) ?>" placeholder="Opcional">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Vinculo Conversa</label>
                    <input type="number" class="form-control" name="conversa_id" value="<?= esc((string) ($orcamento['conversa_id'] ?? '')) ?>" placeholder="Opcional">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <h5 class="mb-0">Itens do Orcamento</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddItem">
                    <i class="bi bi-plus-lg me-1"></i>Adicionar item
                </button>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-hover align-middle mb-0" id="orcamentoItensTable">
                    <thead>
                        <tr>
                            <th style="min-width: 120px;">Tipo</th>
                            <th style="min-width: 240px;">Descricao</th>
                            <th style="min-width: 90px;">Qtd.</th>
                            <th style="min-width: 120px;">Valor unit.</th>
                            <th style="min-width: 120px;">Desconto</th>
                            <th style="min-width: 120px;">Acrescimo</th>
                            <th style="min-width: 120px;">Total</th>
                            <th class="text-center">Acao</th>
                        </tr>
                    </thead>
                    <tbody id="orcamentoItensBody">
                        <?php foreach ($itens as $item): ?>
                            <tr class="orc-item-row">
                                <td data-label="Tipo">
                                    <select name="item_tipo[]" class="form-select form-select-sm">
                                        <?php
                                        $itemTipo = (string) ($item['tipo_item'] ?? 'servico');
                                        $tiposItem = ['servico' => 'Servico', 'peca' => 'Peca', 'combo' => 'Combo', 'avulso' => 'Avulso'];
                                        foreach ($tiposItem as $tipoItemCode => $tipoItemLabel):
                                        ?>
                                            <option value="<?= esc($tipoItemCode) ?>" <?= $itemTipo === $tipoItemCode ? 'selected' : '' ?>><?= esc($tipoItemLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-label="Descricao">
                                    <input type="text" class="form-control form-control-sm" name="item_descricao[]" value="<?= esc((string) ($item['descricao'] ?? '')) ?>" required>
                                    <input type="text" class="form-control form-control-sm mt-1" name="item_observacao[]" value="<?= esc((string) ($item['observacoes'] ?? '')) ?>" placeholder="Observacao do item (opcional)">
                                </td>
                                <td data-label="Qtd."><input type="number" step="0.01" min="0.01" class="form-control form-control-sm item-qty" name="item_quantidade[]" value="<?= esc((string) ($item['quantidade'] ?? 1)) ?>"></td>
                                <td data-label="Valor unit."><input type="text" class="form-control form-control-sm item-unit" name="item_valor_unitario[]" value="<?= esc(number_format((float) ($item['valor_unitario'] ?? 0), 2, '.', '')) ?>"></td>
                                <td data-label="Desconto"><input type="text" class="form-control form-control-sm item-desconto" name="item_desconto[]" value="<?= esc(number_format((float) ($item['desconto'] ?? 0), 2, '.', '')) ?>"></td>
                                <td data-label="Acrescimo"><input type="text" class="form-control form-control-sm item-acrescimo" name="item_acrescimo[]" value="<?= esc(number_format((float) ($item['acrescimo'] ?? 0), 2, '.', '')) ?>"></td>
                                <td data-label="Total"><input type="text" class="form-control form-control-sm item-total" value="<?= esc(number_format((float) ($item['total'] ?? 0), 2, '.', '')) ?>" readonly></td>
                                <td data-label="Acao" class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" title="Remover item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-3">
                    <label class="form-label">Subtotal</label>
                    <input type="text" class="form-control" id="orcSubtotalDisplay" value="<?= esc(number_format((float) ($orcamento['subtotal'] ?? 0), 2, '.', '')) ?>" readonly>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Desconto global</label>
                    <input type="text" class="form-control" id="orcDescontoInput" name="desconto" value="<?= esc(number_format((float) ($orcamento['desconto'] ?? 0), 2, '.', '')) ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Acrescimo global</label>
                    <input type="text" class="form-control" id="orcAcrescimoInput" name="acrescimo" value="<?= esc(number_format((float) ($orcamento['acrescimo'] ?? 0), 2, '.', '')) ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Total final</label>
                    <input type="text" class="form-control fw-semibold" id="orcTotalDisplay" value="<?= esc(number_format((float) ($orcamento['total'] ?? 0), 2, '.', '')) ?>" readonly>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label">Condicoes</label>
                    <textarea class="form-control" name="condicoes" rows="4"><?= esc((string) ($orcamento['condicoes'] ?? '')) ?></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Observacoes</label>
                    <textarea class="form-control" name="observacoes" rows="4"><?= esc((string) ($orcamento['observacoes'] ?? '')) ?></textarea>
                </div>
                <div class="col-12 d-none" id="motivoRejeicaoWrap">
                    <label class="form-label">Motivo da rejeicao</label>
                    <textarea class="form-control" name="motivo_rejeicao" rows="3"><?= esc((string) ($orcamento['motivo_rejeicao'] ?? '')) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-glow">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Atualizar orcamento' : 'Salvar orcamento' ?>
                </button>
                <?php if (!empty($orcamento['id'])): ?>
                    <a href="<?= base_url('orcamentos/visualizar/' . (int) $orcamento['id']) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <?php else: ?>
                    <a href="<?= base_url('orcamentos') ?>" class="btn btn-outline-secondary">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
(function () {
    const tableBody = document.getElementById('orcamentoItensBody');
    const btnAddItem = document.getElementById('btnAddItem');
    const descontoInput = document.getElementById('orcDescontoInput');
    const acrescimoInput = document.getElementById('orcAcrescimoInput');
    const subtotalDisplay = document.getElementById('orcSubtotalDisplay');
    const totalDisplay = document.getElementById('orcTotalDisplay');
    const statusSelect = document.getElementById('orcamentoStatus');
    const motivoRejeicaoWrap = document.getElementById('motivoRejeicaoWrap');
    const clienteSelect = document.getElementById('orcamentoClienteSelect');
    const telefoneInput = document.getElementById('orcamentoTelefone');
    const emailInput = document.getElementById('orcamentoEmail');

    const toNumber = (value) => {
        if (value === null || value === undefined) return 0;
        let raw = String(value).trim();
        if (raw === '') return 0;
        raw = raw.replace(/[^\d,.\-]/g, '');
        if (raw.includes(',') && raw.includes('.')) {
            raw = raw.replace(/\./g, '');
        }
        raw = raw.replace(',', '.');
        const parsed = Number(raw);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const fixed = (value) => (Math.round((value + Number.EPSILON) * 100) / 100).toFixed(2);

    const recalcRow = (row) => {
        const qty = Math.max(0.01, toNumber(row.querySelector('.item-qty')?.value));
        const unit = Math.max(0, toNumber(row.querySelector('.item-unit')?.value));
        const desconto = Math.max(0, toNumber(row.querySelector('.item-desconto')?.value));
        const acrescimo = Math.max(0, toNumber(row.querySelector('.item-acrescimo')?.value));
        const total = Math.max(0, (qty * unit) - desconto + acrescimo);
        const totalInput = row.querySelector('.item-total');
        if (totalInput) totalInput.value = fixed(total);
        return total;
    };

    const recalcAll = () => {
        let subtotal = 0;
        tableBody.querySelectorAll('.orc-item-row').forEach((row) => {
            subtotal += recalcRow(row);
        });
        const desconto = Math.max(0, toNumber(descontoInput?.value));
        const acrescimo = Math.max(0, toNumber(acrescimoInput?.value));
        const total = Math.max(0, subtotal - desconto + acrescimo);
        if (subtotalDisplay) subtotalDisplay.value = fixed(subtotal);
        if (totalDisplay) totalDisplay.value = fixed(total);
    };

    const bindRow = (row) => {
        row.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', recalcAll);
        });
        const removeBtn = row.querySelector('.btn-remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', async () => {
                const proceed = await (window.Swal
                    ? window.Swal.fire({
                        icon: 'warning',
                        title: 'Remover item?',
                        text: 'Este item sera removido do orcamento.',
                        showCancelButton: true,
                        confirmButtonText: 'Remover',
                        cancelButtonText: 'Cancelar',
                    }).then((r) => r.isConfirmed)
                    : Promise.resolve(window.confirm('Remover este item?')));

                if (!proceed) return;
                row.remove();
                if (!tableBody.querySelector('.orc-item-row')) {
                    addRow();
                }
                recalcAll();
            });
        }
    };

    const addRow = () => {
        const tr = document.createElement('tr');
        tr.className = 'orc-item-row';
        tr.innerHTML = `
            <td data-label="Tipo">
                <select name="item_tipo[]" class="form-select form-select-sm">
                    <option value="servico">Servico</option>
                    <option value="peca">Peca</option>
                    <option value="combo">Combo</option>
                    <option value="avulso">Avulso</option>
                </select>
            </td>
            <td data-label="Descricao">
                <input type="text" class="form-control form-control-sm" name="item_descricao[]" required>
                <input type="text" class="form-control form-control-sm mt-1" name="item_observacao[]" placeholder="Observacao do item (opcional)">
            </td>
            <td data-label="Qtd."><input type="number" step="0.01" min="0.01" class="form-control form-control-sm item-qty" name="item_quantidade[]" value="1"></td>
            <td data-label="Valor unit."><input type="text" class="form-control form-control-sm item-unit" name="item_valor_unitario[]" value="0.00"></td>
            <td data-label="Desconto"><input type="text" class="form-control form-control-sm item-desconto" name="item_desconto[]" value="0.00"></td>
            <td data-label="Acrescimo"><input type="text" class="form-control form-control-sm item-acrescimo" name="item_acrescimo[]" value="0.00"></td>
            <td data-label="Total"><input type="text" class="form-control form-control-sm item-total" value="0.00" readonly></td>
            <td data-label="Acao" class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" title="Remover item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(tr);
        bindRow(tr);
        recalcAll();
    };

    if (btnAddItem) {
        btnAddItem.addEventListener('click', addRow);
    }

    if (descontoInput) descontoInput.addEventListener('input', recalcAll);
    if (acrescimoInput) acrescimoInput.addEventListener('input', recalcAll);

    tableBody.querySelectorAll('.orc-item-row').forEach(bindRow);

    const syncStatusFields = () => {
        if (!statusSelect || !motivoRejeicaoWrap) return;
        if (statusSelect.value === 'rejeitado') {
            motivoRejeicaoWrap.classList.remove('d-none');
        } else {
            motivoRejeicaoWrap.classList.add('d-none');
        }
    };
    if (statusSelect) {
        statusSelect.addEventListener('change', syncStatusFields);
        syncStatusFields();
    }

    const syncClienteContato = () => {
        if (!clienteSelect) return;
        const selected = clienteSelect.options[clienteSelect.selectedIndex];
        if (!selected || selected.value === '') return;
        const phone = selected.getAttribute('data-telefone') || '';
        const email = selected.getAttribute('data-email') || '';
        if (telefoneInput && telefoneInput.value.trim() === '') {
            telefoneInput.value = phone;
        }
        if (emailInput && emailInput.value.trim() === '') {
            emailInput.value = email;
        }
    };
    if (clienteSelect) {
        clienteSelect.addEventListener('change', syncClienteContato);
    }

    recalcAll();
})();
</script>
<style>
@media (max-width: 430px) {
    #orcamentoItensTable thead {
        display: none;
    }
    #orcamentoItensTable tbody tr {
        display: block;
        margin-bottom: .75rem;
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: .75rem;
    }
    #orcamentoItensTable tbody td {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .75rem;
        border: 0;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        padding: .65rem .75rem;
        white-space: normal;
    }
    #orcamentoItensTable tbody td::before {
        content: attr(data-label);
        min-width: 86px;
        font-weight: 600;
        color: #6c757d;
        font-size: .8rem;
    }
    #orcamentoItensTable tbody td:last-child {
        border-bottom: 0;
    }
}
@media (max-width: 390px) {
    #orcamentoItensTable tbody td::before {
        min-width: 80px;
    }
}
@media (max-width: 360px) {
    #orcamentoItensTable tbody td {
        padding: .55rem .6rem;
    }
}
@media (max-width: 320px) {
    #orcamentoItensTable tbody td {
        gap: .5rem;
        padding: .5rem;
    }
    #orcamentoItensTable tbody td::before {
        min-width: 72px;
        font-size: .74rem;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
