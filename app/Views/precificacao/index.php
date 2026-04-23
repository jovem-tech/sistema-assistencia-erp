<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$cfg = (array) ($configs ?? []);
$componentesPeca = (array) ($componentesPeca ?? []);
$componentesServicoCusto = (array) ($componentesServicoCusto ?? []);
$componentesServicoRisco = (array) ($componentesServicoRisco ?? []);
$pecas = (array) ($pecas ?? []);
$servicos = (array) ($servicos ?? []);
$rulesPeca = (array) ($rulesPeca ?? []);
$rulesServico = (array) ($rulesServico ?? []);
$componentesTableReady = (bool) ($componentesTableReady ?? false);
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-calculator me-2"></i>Módulo de Precificação</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('precificacao')" title="Ajuda sobre Precificação">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('orcamentos') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('orcamentos') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<?php if (!$componentesTableReady): ?>
<div class="alert alert-warning border-warning-subtle mb-3">
    <strong>Atenção:</strong> a tabela <code>precificacao_componentes</code> ainda não existe nesta base.
    Execute as migrações para habilitar os componentes de encargos/custos/risco.
</div>
<?php endif; ?>

<div class="card glass-card mb-3">
    <div class="card-body">
        <form action="<?= base_url('precificacao/salvar') ?>" method="POST" id="formPrecificacao">
            <?= csrf_field() ?>

            <h5 class="border-bottom pb-2 mb-3">1) Parâmetros Gerais - Peça Instalada</h5>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Base da Peça</label>
                    <select class="form-select" name="precificacao_peca_base">
                        <option value="custo" <?= (($cfg['precificacao_peca_base'] ?? 'custo') === 'custo') ? 'selected' : '' ?>>Custo da peça</option>
                        <option value="venda" <?= (($cfg['precificacao_peca_base'] ?? 'custo') === 'venda') ? 'selected' : '' ?>>Venda da peça</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Encargos totais fallback (%)</label>
                    <input type="number" class="form-control" name="precificacao_peca_encargos_percentual" min="0" max="300" step="0.01" value="<?= esc((string) ($cfg['precificacao_peca_encargos_percentual'] ?? '15')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Margem da peça (%)</label>
                    <input type="number" class="form-control" name="precificacao_peca_margem_percentual" min="0" max="300" step="0.01" value="<?= esc((string) ($cfg['precificacao_peca_margem_percentual'] ?? '45')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Respeitar preço de venda</label>
                    <select class="form-select" name="precificacao_peca_respeitar_preco_venda">
                        <option value="1" <?= (($cfg['precificacao_peca_respeitar_preco_venda'] ?? '1') !== '0') ? 'selected' : '' ?>>Sim</option>
                        <option value="0" <?= (($cfg['precificacao_peca_respeitar_preco_venda'] ?? '1') === '0') ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Usar componentes de encargos da peça</label>
                    <select class="form-select" name="precificacao_peca_usa_componentes">
                        <option value="1" <?= (($cfg['precificacao_peca_usa_componentes'] ?? '1') !== '0') ? 'selected' : '' ?>>Sim (recomendado)</option>
                        <option value="0" <?= (($cfg['precificacao_peca_usa_componentes'] ?? '1') === '0') ? 'selected' : '' ?>>Não (usar fallback)</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Resumo operacional atual da peça</label>
                    <div class="form-control bg-light-subtle d-flex flex-wrap gap-2 align-items-center min-h-38">
                        <span class="badge bg-primary">Encargos aplicados: <?= esc(number_format((float) ($rulesPeca['encargos_percentual'] ?? 0), 2, ',', '.')) ?>%</span>
                        <span class="badge bg-secondary">Margem: <?= esc(number_format((float) ($rulesPeca['margem_percentual'] ?? 0), 2, ',', '.')) ?>%</span>
                        <span class="badge bg-dark">Base: <?= esc((string) ($rulesPeca['base'] ?? 'custo')) ?></span>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-sm align-middle" id="tableComponentesPeca">
                    <thead>
                    <tr>
                        <th>Componente de Encargo (peça)</th>
                        <th style="width: 180px;">Percentual (%)</th>
                        <th style="width: 80px;" class="text-center">Ação</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($componentesPeca): foreach ($componentesPeca as $comp): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="componentes_peca_id[]" value="<?= (int) ($comp['id'] ?? 0) ?>">
                                <input type="text" class="form-control form-control-sm" name="componentes_peca_nome[]" value="<?= esc((string) ($comp['nome'] ?? '')) ?>" placeholder="Ex.: garantia da peça">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="componentes_peca_valor[]" value="<?= esc(number_format((float) ($comp['valor'] ?? 0), 2, '.', '')) ?>">
                            </td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td>
                                <input type="hidden" name="componentes_peca_id[]" value="">
                                <input type="text" class="form-control form-control-sm" name="componentes_peca_nome[]" value="Triagem e testes da peça" placeholder="Ex.: garantia da peça">
                            </td>
                            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="componentes_peca_valor[]" value="4.00"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddCompPeca"><i class="bi bi-plus-lg me-1"></i>Adicionar componente da peça</button>
            </div>

            <h5 class="border-bottom pb-2 mb-3">2) Parâmetros Gerais - Serviços</h5>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Custo hora produtiva (R$)</label>
                    <input type="number" class="form-control" name="precificacao_servico_custo_hora_produtiva" step="0.01" min="0" value="<?= esc((string) ($cfg['precificacao_servico_custo_hora_produtiva'] ?? '40')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Margem alvo serviço (%)</label>
                    <input type="number" class="form-control" name="precificacao_servico_margem_percentual" step="0.01" min="0" max="300" value="<?= esc((string) ($cfg['precificacao_servico_margem_percentual'] ?? '25')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Taxa de recebimento (%)</label>
                    <input type="number" class="form-control" name="precificacao_servico_taxa_recebimento_percentual" step="0.01" min="0" max="100" value="<?= esc((string) ($cfg['precificacao_servico_taxa_recebimento_percentual'] ?? '3.5')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Imposto (%)</label>
                    <input type="number" class="form-control" name="precificacao_servico_imposto_percentual" step="0.01" min="0" max="100" value="<?= esc((string) ($cfg['precificacao_servico_imposto_percentual'] ?? '0')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tempo padrão (h)</label>
                    <input type="number" class="form-control" name="precificacao_servico_tempo_padrao_horas" step="0.01" min="0.1" value="<?= esc((string) ($cfg['precificacao_servico_tempo_padrao_horas'] ?? '1')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Usar componentes de serviço</label>
                    <select class="form-select" name="precificacao_servico_usa_componentes">
                        <option value="1" <?= (($cfg['precificacao_servico_usa_componentes'] ?? '1') !== '0') ? 'selected' : '' ?>>Sim</option>
                        <option value="0" <?= (($cfg['precificacao_servico_usa_componentes'] ?? '1') === '0') ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Aplicar recomendado no catálogo</label>
                    <select class="form-select" name="precificacao_servico_aplicar_catalogo">
                        <option value="1" <?= (($cfg['precificacao_servico_aplicar_catalogo'] ?? '1') !== '0') ? 'selected' : '' ?>>Sim</option>
                        <option value="0" <?= (($cfg['precificacao_servico_aplicar_catalogo'] ?? '1') === '0') ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Aplicar piso no salvamento</label>
                    <select class="form-select" name="precificacao_servico_aplicar_piso">
                        <option value="1" <?= (($cfg['precificacao_servico_aplicar_piso'] ?? '0') !== '0') ? 'selected' : '' ?>>Sim</option>
                        <option value="0" <?= (($cfg['precificacao_servico_aplicar_piso'] ?? '0') === '0') ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Resumo serviço (regras ativas)</label>
                    <div class="form-control bg-light-subtle d-flex flex-wrap gap-2 align-items-center min-h-38">
                        <span class="badge bg-primary">Margem: <?= esc(number_format((float) ($rulesServico['margem_percentual'] ?? 0), 2, ',', '.')) ?>%</span>
                        <span class="badge bg-secondary">Taxa: <?= esc(number_format((float) ($rulesServico['taxa_recebimento_percentual'] ?? 0), 2, ',', '.')) ?>%</span>
                        <span class="badge bg-dark">Imposto: <?= esc(number_format((float) ($rulesServico['imposto_percentual'] ?? 0), 2, ',', '.')) ?>%</span>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="tableCompServicoCusto">
                            <thead>
                            <tr>
                                <th>Componente de custo direto (R$)</th>
                                <th style="width: 160px;">Valor</th>
                                <th style="width: 80px;" class="text-center">Ação</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($componentesServicoCusto): foreach ($componentesServicoCusto as $comp): ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="componentes_servico_custo_id[]" value="<?= (int) ($comp['id'] ?? 0) ?>">
                                        <input type="text" class="form-control form-control-sm" name="componentes_servico_custo_nome[]" value="<?= esc((string) ($comp['nome'] ?? '')) ?>" placeholder="Ex.: consumíveis">
                                    </td>
                                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="componentes_servico_custo_valor[]" value="<?= esc(number_format((float) ($comp['valor'] ?? 0), 2, '.', '')) ?>"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="componentes_servico_custo_id[]" value="">
                                        <input type="text" class="form-control form-control-sm" name="componentes_servico_custo_nome[]" value="Consumíveis e limpeza técnica" placeholder="Ex.: consumíveis">
                                    </td>
                                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="componentes_servico_custo_valor[]" value="6.00"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddCompServicoCusto"><i class="bi bi-plus-lg me-1"></i>Adicionar custo direto</button>
                </div>
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="tableCompServicoRisco">
                            <thead>
                            <tr>
                                <th>Componente de risco/garantia (%)</th>
                                <th style="width: 160px;">Percentual</th>
                                <th style="width: 80px;" class="text-center">Ação</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($componentesServicoRisco): foreach ($componentesServicoRisco as $comp): ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="componentes_servico_risco_id[]" value="<?= (int) ($comp['id'] ?? 0) ?>">
                                        <input type="text" class="form-control form-control-sm" name="componentes_servico_risco_nome[]" value="<?= esc((string) ($comp['nome'] ?? '')) ?>" placeholder="Ex.: retrabalho">
                                    </td>
                                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="componentes_servico_risco_valor[]" value="<?= esc(number_format((float) ($comp['valor'] ?? 0), 2, '.', '')) ?>"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="componentes_servico_risco_id[]" value="">
                                        <input type="text" class="form-control form-control-sm" name="componentes_servico_risco_nome[]" value="Reserva de garantia e retrabalho" placeholder="Ex.: retrabalho">
                                    </td>
                                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="componentes_servico_risco_valor[]" value="3.00"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddCompServicoRisco"><i class="bi bi-plus-lg me-1"></i>Adicionar risco</button>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-glow px-4">
                    <i class="bi bi-save me-1"></i>Salvar módulo de precificação
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <h5 class="border-bottom pb-2 mb-3">3) Simulador (validação operacional)</h5>
        <div class="row g-4">
            <div class="col-lg-6">
                <h6 class="mb-3">Simular peça instalada</h6>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Peça cadastrada (opcional)</label>
                        <select class="form-select form-select-sm" id="simPecaId">
                            <option value="">Selecionar peça...</option>
                            <?php foreach ($pecas as $peca): ?>
                                <option value="<?= (int) ($peca['id'] ?? 0) ?>"><?= esc((string) ($peca['nome'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Preço de custo (R$)</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="simPecaCusto" value="80.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Preço de venda (R$)</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="simPecaVenda" value="0.00">
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnSimularPeca"><i class="bi bi-play-fill me-1"></i>Simular peça</button>
                    </div>
                    <div class="col-12">
                        <pre class="bg-light border rounded p-2 small mb-0" id="simPecaResultado">Aguardando simulação...</pre>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h6 class="mb-3">Simular serviço</h6>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Serviço cadastrado (opcional)</label>
                        <select class="form-select form-select-sm" id="simServicoId">
                            <option value="">Selecionar serviço...</option>
                            <?php foreach ($servicos as $servico): ?>
                                <option value="<?= (int) ($servico['id'] ?? 0) ?>"><?= esc((string) ($servico['nome'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tempo (h)</label>
                        <input type="number" step="0.01" min="0.1" class="form-control form-control-sm" id="simServicoTempo" value="1.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Custo direto (R$)</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="simServicoCustoDireto" value="20.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valor de cadastro (R$)</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="simServicoValorCadastro" value="0.00">
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnSimularServico"><i class="bi bi-play-fill me-1"></i>Simular serviço</button>
                    </div>
                    <div class="col-12">
                        <pre class="bg-light border rounded p-2 small mb-0" id="simServicoResultado">Aguardando simulação...</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
    const form = document.getElementById('formPrecificacao');
    const simPecaUrl = <?= json_encode(base_url('precificacao/simular-peca')) ?>;
    const simServicoUrl = <?= json_encode(base_url('precificacao/simular-servico')) ?>;

    const csrfInput = form?.querySelector('input[name="csrf_test_name"]');
    const csrfName = csrfInput?.name || 'csrf_test_name';
    const getCsrf = () => (csrfInput?.value || '');

    const addRow = (tableId, namePrefix, defaultName = '', defaultValue = '0.00') => {
        const table = document.getElementById(tableId);
        const tbody = table?.querySelector('tbody');
        if (!tbody) return;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="hidden" name="${namePrefix}_id[]" value="">
                <input type="text" class="form-control form-control-sm" name="${namePrefix}_nome[]" value="${defaultName}" placeholder="Nome do componente">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="${namePrefix}_valor[]" value="${defaultValue}">
            </td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
    };

    document.getElementById('btnAddCompPeca')?.addEventListener('click', () => {
        addRow('tableComponentesPeca', 'componentes_peca');
    });
    document.getElementById('btnAddCompServicoCusto')?.addEventListener('click', () => {
        addRow('tableCompServicoCusto', 'componentes_servico_custo');
    });
    document.getElementById('btnAddCompServicoRisco')?.addEventListener('click', () => {
        addRow('tableCompServicoRisco', 'componentes_servico_risco');
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        const btn = target.closest('.btn-remove-row');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (tr) tr.remove();
    });

    const postForm = async (url, payload) => {
        const formData = new FormData();
        Object.entries(payload).forEach(([key, value]) => formData.append(key, String(value ?? '')));
        formData.append(csrfName, getCsrf());
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    };

    const formatBRL = (value) => Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    document.getElementById('btnSimularPeca')?.addEventListener('click', async () => {
        const out = document.getElementById('simPecaResultado');
        if (!out) return;
        out.textContent = 'Simulando...';
        try {
            const data = await postForm(simPecaUrl, {
                peca_id: document.getElementById('simPecaId')?.value || '',
                preco_custo: document.getElementById('simPecaCusto')?.value || '0',
                preco_venda: document.getElementById('simPecaVenda')?.value || '0',
            });
            const q = data?.quote || {};
            out.textContent = [
                `Base: ${formatBRL(q.preco_base)}`,
                `Encargos: ${q.percentual_encargos}% (${formatBRL(q.valor_encargos)})`,
                `Margem: ${q.percentual_margem}% (${formatBRL(q.valor_margem)})`,
                `Recomendado: ${formatBRL(q.valor_recomendado)}`,
                `Modo: ${q.modo_precificacao || '-'}`,
            ].join('\n');
        } catch (error) {
            out.textContent = `Falha na simulação: ${error.message}`;
            if (window.Swal) {
                window.Swal.fire('Falha', 'Não foi possível simular a peça.', 'error');
            }
        }
    });

    document.getElementById('btnSimularServico')?.addEventListener('click', async () => {
        const out = document.getElementById('simServicoResultado');
        if (!out) return;
        out.textContent = 'Simulando...';
        try {
            const data = await postForm(simServicoUrl, {
                servico_id: document.getElementById('simServicoId')?.value || '',
                tempo_horas: document.getElementById('simServicoTempo')?.value || '1',
                custo_direto_padrao: document.getElementById('simServicoCustoDireto')?.value || '0',
                valor_cadastro: document.getElementById('simServicoValorCadastro')?.value || '0',
            });
            const q = data?.quote || {};
            out.textContent = [
                `Tempo padrão: ${q.tempo_padrao_horas}h`,
                `Mão de obra: ${formatBRL(q.custo_mao_obra)}`,
                `Custos diretos: ${formatBRL(q.custo_direto_total)}`,
                `Risco: ${q.risco_percentual}% (${formatBRL(q.valor_risco)})`,
                `Custo total: ${formatBRL(q.custo_total)}`,
                `Preço mínimo técnico: ${formatBRL(q.preco_minimo)}`,
                `Valor recomendado: ${formatBRL(q.valor_recomendado)}`,
            ].join('\n');
        } catch (error) {
            out.textContent = `Falha na simulação: ${error.message}`;
            if (window.Swal) {
                window.Swal.fire('Falha', 'Não foi possível simular o serviço.', 'error');
            }
        }
    });
})();
</script>
<style>
.min-h-38 {
    min-height: 38px;
}
</style>
<?= $this->endSection() ?>

