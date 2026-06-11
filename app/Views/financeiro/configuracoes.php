<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$totalCategorias = count($categorias ?? []);
$totalGrupos = count($dre_grupos ?? []);
$totalSubgrupos = count($dre_subgrupos ?? []);
?>

<div class="page-header">
    <h2><i class="bi bi-sliders me-2"></i><?= esc($title) ?></h2>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('financeiro')" title="Ajuda sobre Financeiro">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('relatorios/dre') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-bar-chart-line me-1"></i>DRE
        </a>
        <a href="<?= base_url('relatorios/fluxo-caixa') ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-graph-up-arrow me-1"></i>Fluxo de Caixa
        </a>
        <a href="<?= base_url('financeiro') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar ao Financeiro
        </a>
    </div>
</div>

<div class="alert alert-light border shadow-sm mb-4">
    <strong>Governanca do financeiro gerencial.</strong>
    <span class="d-block mt-1">Cadastre aqui as `categorias financeiras` que aparecem no lançamento e mantenha a estrutura de `grupo` e `subgrupo DRE` organizada para a operacao e para os relatorios.</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-semibold mb-2">Categorias financeiras</div>
                <div class="display-6 fw-bold mb-1"><?= $totalCategorias ?></div>
                <div class="text-muted small">Usadas no dropdown do lancamento financeiro.</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-semibold mb-2">Grupos DRE</div>
                <div class="display-6 fw-bold mb-1"><?= $totalGrupos ?></div>
                <div class="text-muted small">Estrutura principal da demonstracao gerencial.</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-semibold mb-2">Subgrupos DRE</div>
                <div class="display-6 fw-bold mb-1"><?= $totalSubgrupos ?></div>
                <div class="text-muted small">Detalham a leitura da DRE e os defaults das categorias.</div>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <ul class="nav nav-pills finance-config-tabs mb-4" id="financeConfigTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="finance-tab-categorias-btn" data-bs-toggle="pill" data-bs-target="#tab-categorias" type="button" role="tab" aria-controls="tab-categorias" aria-selected="true">
                    Categorias
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="finance-tab-grupos-btn" data-bs-toggle="pill" data-bs-target="#tab-grupos" type="button" role="tab" aria-controls="tab-grupos" aria-selected="false">
                    Grupos DRE
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="finance-tab-subgrupos-btn" data-bs-toggle="pill" data-bs-target="#tab-subgrupos" type="button" role="tab" aria-controls="tab-subgrupos" aria-selected="false">
                    Subgrupos DRE
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-categorias" role="tabpanel" aria-labelledby="finance-tab-categorias-btn">
                <div class="finance-config-section-head">
                    <div>
                        <div class="finance-config-kicker">Dropdown do lancamento</div>
                        <h5 class="mb-1">Categorias financeiras</h5>
                        <p class="text-muted mb-0">Cada categoria pode sugerir grupo, subgrupo e comportamentos padrao ao abrir um novo lancamento.</p>
                    </div>
                    <?php if (can('financeiro', 'editar')): ?>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCategoria" data-mode="create">
                        <i class="bi bi-plus-lg me-1"></i>Nova categoria
                    </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover stack-table">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Tipo</th>
                                <th>Default DRE</th>
                                <th>Padroes</th>
                                <th>Status</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($categorias)): ?>
                                <?php foreach ($categorias as $categoria): ?>
                                <tr>
                                    <td data-label="Categoria">
                                        <div class="fw-semibold"><?= esc($categoria['nome'] ?? '-') ?></div>
                                        <div class="small text-muted">Ordem <?= (int) ($categoria['ordem_exibicao'] ?? 0) ?></div>
                                    </td>
                                    <td data-label="Tipo">
                                        <span class="badge text-bg-light"><?= esc(ucfirst((string) ($categoria['tipo'] ?? 'ambos'))) ?></span>
                                    </td>
                                    <td data-label="Default DRE">
                                        <div class="fw-semibold"><?= esc($categoria['dre_grupo_nome'] ?? 'Sem grupo padrao') ?></div>
                                        <div class="small text-muted"><?= esc($categoria['dre_subgrupo_nome'] ?? 'Sem subgrupo padrao') ?></div>
                                    </td>
                                    <td data-label="Padroes">
                                        <div class="small d-flex flex-wrap gap-1">
                                            <span class="badge text-bg-light">DRE <?= ((int) ($categoria['impacta_dre_padrao'] ?? 1)) === 1 ? 'sim' : 'nao' ?></span>
                                            <span class="badge text-bg-light">Caixa <?= ((int) ($categoria['impacta_fluxo_caixa_padrao'] ?? 1)) === 1 ? 'sim' : 'nao' ?></span>
                                            <?php if ((int) ($categoria['dre_fixo_mensal_padrao'] ?? 0) === 1): ?>
                                            <span class="badge text-bg-info">Fixa mensal</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td data-label="Status">
                                        <span class="badge <?= ((int) ($categoria['ativo'] ?? 1)) === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ((int) ($categoria['ativo'] ?? 1)) === 1 ? 'Ativa' : 'Inativa' ?>
                                        </span>
                                    </td>
                                    <td data-label="Acoes">
                                        <div class="action-btns">
                                            <?php if (can('financeiro', 'editar')): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary btn-edit-categoria"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalCategoria"
                                                data-id="<?= (int) $categoria['id'] ?>"
                                                data-nome="<?= esc($categoria['nome'] ?? '') ?>"
                                                data-tipo="<?= esc($categoria['tipo'] ?? 'ambos') ?>"
                                                data-grupo-id="<?= (int) ($categoria['dre_grupo_id'] ?? 0) ?>"
                                                data-subgrupo-id="<?= (int) ($categoria['dre_subgrupo_id'] ?? 0) ?>"
                                                data-impacta-dre="<?= (int) ($categoria['impacta_dre_padrao'] ?? 1) ?>"
                                                data-impacta-caixa="<?= (int) ($categoria['impacta_fluxo_caixa_padrao'] ?? 1) ?>"
                                                data-dre-fixo-mensal="<?= (int) ($categoria['dre_fixo_mensal_padrao'] ?? 0) ?>"
                                                data-ordem="<?= (int) ($categoria['ordem_exibicao'] ?? 0) ?>"
                                                data-ativo="<?= (int) ($categoria['ativo'] ?? 1) ?>"
                                                title="Editar"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (can('financeiro', 'excluir')): ?>
                                            <form action="<?= base_url('financeiro/configuracoes/categorias/excluir/' . (int) $categoria['id']) ?>" method="POST" class="d-inline-block finance-config-delete-form" data-entity="categoria" data-name="<?= esc($categoria['nome'] ?? '') ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nenhuma categoria financeira cadastrada.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-grupos" role="tabpanel" aria-labelledby="finance-tab-grupos-btn">
                <div class="finance-config-section-head">
                    <div>
                        <div class="finance-config-kicker">Estrutura principal da demonstracao</div>
                        <h5 class="mb-1">Grupos DRE</h5>
                        <p class="text-muted mb-0">Organizam a estrutura maior da DRE, como `Receita Operacional`, `Despesas Operacionais` e `Custo Direto`.</p>
                    </div>
                    <?php if (can('financeiro', 'editar')): ?>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGrupoDre" data-mode="create">
                        <i class="bi bi-plus-lg me-1"></i>Novo grupo
                    </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover stack-table">
                        <thead>
                            <tr>
                                <th>Grupo DRE</th>
                                <th>Descricao</th>
                                <th>Ordem</th>
                                <th>Status</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($dre_grupos)): ?>
                                <?php foreach ($dre_grupos as $grupo): ?>
                                <tr>
                                    <td data-label="Grupo DRE">
                                        <div class="fw-semibold"><?= esc($grupo['nome'] ?? '-') ?></div>
                                    </td>
                                    <td data-label="Descricao"><?= esc($grupo['descricao'] ?? '-') ?></td>
                                    <td data-label="Ordem"><?= (int) ($grupo['ordem_exibicao'] ?? 0) ?></td>
                                    <td data-label="Status">
                                        <span class="badge <?= ((int) ($grupo['ativo'] ?? 1)) === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ((int) ($grupo['ativo'] ?? 1)) === 1 ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td data-label="Acoes">
                                        <div class="action-btns">
                                            <?php if (can('financeiro', 'editar')): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary btn-edit-grupo"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalGrupoDre"
                                                data-id="<?= (int) $grupo['id'] ?>"
                                                data-nome="<?= esc($grupo['nome'] ?? '') ?>"
                                                data-descricao="<?= esc($grupo['descricao'] ?? '') ?>"
                                                data-ordem="<?= (int) ($grupo['ordem_exibicao'] ?? 0) ?>"
                                                data-ativo="<?= (int) ($grupo['ativo'] ?? 1) ?>"
                                                title="Editar"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (can('financeiro', 'excluir')): ?>
                                            <form action="<?= base_url('financeiro/configuracoes/dre/grupos/excluir/' . (int) $grupo['id']) ?>" method="POST" class="d-inline-block finance-config-delete-form" data-entity="grupo DRE" data-name="<?= esc($grupo['nome'] ?? '') ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Nenhum grupo DRE cadastrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-subgrupos" role="tabpanel" aria-labelledby="finance-tab-subgrupos-btn">
                <div class="finance-config-section-head">
                    <div>
                        <div class="finance-config-kicker">Detalhamento gerencial</div>
                        <h5 class="mb-1">Subgrupos DRE</h5>
                        <p class="text-muted mb-0">Refinam o grupo principal e aparecem no dropdown do lancamento para melhorar a leitura da DRE.</p>
                    </div>
                    <?php if (can('financeiro', 'editar')): ?>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSubgrupoDre" data-mode="create">
                        <i class="bi bi-plus-lg me-1"></i>Novo subgrupo
                    </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover stack-table">
                        <thead>
                            <tr>
                                <th>Subgrupo DRE</th>
                                <th>Grupo</th>
                                <th>Descricao</th>
                                <th>Ordem</th>
                                <th>Status</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($dre_subgrupos)): ?>
                                <?php foreach ($dre_subgrupos as $subgrupo): ?>
                                <tr>
                                    <td data-label="Subgrupo DRE">
                                        <div class="fw-semibold"><?= esc($subgrupo['nome'] ?? '-') ?></div>
                                    </td>
                                    <td data-label="Grupo"><?= esc($subgrupo['grupo_nome'] ?? '-') ?></td>
                                    <td data-label="Descricao"><?= esc($subgrupo['descricao'] ?? '-') ?></td>
                                    <td data-label="Ordem"><?= (int) ($subgrupo['ordem_exibicao'] ?? 0) ?></td>
                                    <td data-label="Status">
                                        <span class="badge <?= ((int) ($subgrupo['ativo'] ?? 1)) === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ((int) ($subgrupo['ativo'] ?? 1)) === 1 ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td data-label="Acoes">
                                        <div class="action-btns">
                                            <?php if (can('financeiro', 'editar')): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary btn-edit-subgrupo"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalSubgrupoDre"
                                                data-id="<?= (int) $subgrupo['id'] ?>"
                                                data-nome="<?= esc($subgrupo['nome'] ?? '') ?>"
                                                data-grupo-id="<?= (int) ($subgrupo['grupo_id'] ?? 0) ?>"
                                                data-descricao="<?= esc($subgrupo['descricao'] ?? '') ?>"
                                                data-ordem="<?= (int) ($subgrupo['ordem_exibicao'] ?? 0) ?>"
                                                data-ativo="<?= (int) ($subgrupo['ativo'] ?? 1) ?>"
                                                title="Editar"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (can('financeiro', 'excluir')): ?>
                                            <form action="<?= base_url('financeiro/configuracoes/dre/subgrupos/excluir/' . (int) $subgrupo['id']) ?>" method="POST" class="d-inline-block finance-config-delete-form" data-entity="subgrupo DRE" data-name="<?= esc($subgrupo['nome'] ?? '') ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nenhum subgrupo DRE cadastrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (can('financeiro', 'editar')): ?>
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCategoriaTitle">Nova categoria financeira</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= base_url('financeiro/configuracoes/categorias/salvar') ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="categoriaId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" name="nome" id="categoriaNome" maxlength="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo" id="categoriaTipo" class="form-select" required>
                                <option value="receber">Receber</option>
                                <option value="pagar">Pagar</option>
                                <option value="ambos">Ambos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ordem</label>
                            <input type="number" class="form-control" name="ordem_exibicao" id="categoriaOrdem" min="0" step="1" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Grupo DRE padrao</label>
                            <select name="dre_grupo_id" id="categoriaGrupoDreId" class="form-select">
                                <option value="">Sem grupo padrao</option>
                                <?php foreach ($dre_grupos as $grupo): ?>
                                <option value="<?= (int) $grupo['id'] ?>"><?= esc($grupo['nome'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subgrupo DRE padrao</label>
                            <select name="dre_subgrupo_id" id="categoriaSubgrupoDreId" class="form-select">
                                <option value="">Sem subgrupo padrao</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100">
                                <input type="hidden" name="impacta_dre_padrao" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="categoriaImpactaDre" name="impacta_dre_padrao" value="1" checked>
                                    <label class="form-check-label fw-semibold" for="categoriaImpactaDre">Impacta DRE</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100">
                                <input type="hidden" name="impacta_fluxo_caixa_padrao" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="categoriaImpactaCaixa" name="impacta_fluxo_caixa_padrao" value="1" checked>
                                    <label class="form-check-label fw-semibold" for="categoriaImpactaCaixa">Impacta caixa</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100">
                                <input type="hidden" name="dre_fixo_mensal_padrao" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="categoriaDreFixoMensal" name="dre_fixo_mensal_padrao" value="1">
                                    <label class="form-check-label fw-semibold" for="categoriaDreFixoMensal">Sugere fixa mensal</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <input type="hidden" name="ativo" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="categoriaAtivo" name="ativo" value="1" checked>
                            <label class="form-check-label fw-semibold" for="categoriaAtivo">Categoria ativa</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar categoria</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGrupoDre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGrupoDreTitle">Novo grupo DRE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= base_url('financeiro/configuracoes/dre/grupos/salvar') ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="grupoDreId">
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" name="nome" id="grupoDreNome" maxlength="80" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descricao</label>
                        <input type="text" class="form-control" name="descricao" id="grupoDreDescricao" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ordem</label>
                        <input type="number" class="form-control" name="ordem_exibicao" id="grupoDreOrdem" min="0" step="1" value="0">
                    </div>
                    <input type="hidden" name="ativo" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="grupoDreAtivo" name="ativo" value="1" checked>
                        <label class="form-check-label fw-semibold" for="grupoDreAtivo">Grupo ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar grupo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSubgrupoDre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSubgrupoDreTitle">Novo subgrupo DRE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= base_url('financeiro/configuracoes/dre/subgrupos/salvar') ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="subgrupoDreId">
                    <div class="mb-3">
                        <label class="form-label">Grupo DRE *</label>
                        <select name="grupo_id" id="subgrupoDreGrupoId" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($dre_grupos as $grupo): ?>
                            <option value="<?= (int) $grupo['id'] ?>"><?= esc($grupo['nome'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" name="nome" id="subgrupoDreNome" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descricao</label>
                        <input type="text" class="form-control" name="descricao" id="subgrupoDreDescricao" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ordem</label>
                        <input type="number" class="form-control" name="ordem_exibicao" id="subgrupoDreOrdem" min="0" step="1" value="0">
                    </div>
                    <input type="hidden" name="ativo" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="subgrupoDreAtivo" name="ativo" value="1" checked>
                        <label class="form-check-label fw-semibold" for="subgrupoDreAtivo">Subgrupo ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar subgrupo</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
.finance-config-tabs {
    gap: 0.55rem;
}

.finance-config-tabs .nav-link {
    border-radius: 999px;
    border: 1px solid #d9dff4;
    background: #f7f9ff;
    color: #51608a;
    font-weight: 600;
    padding: 0.48rem 0.95rem;
}

.finance-config-tabs .nav-link.active {
    background: #5b5ce2;
    border-color: #5b5ce2;
    color: #fff;
    box-shadow: 0 6px 18px rgba(91, 92, 226, 0.22);
}

.finance-config-section-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.95rem 1rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(59, 130, 246, 0.06));
    border: 1px solid rgba(148, 163, 184, 0.18);
}

.finance-config-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

@media (max-width: 575.98px) {
    .finance-config-section-head {
        flex-direction: column;
        align-items: stretch;
    }

    .stack-table thead {
        display: none;
    }

    .stack-table,
    .stack-table tbody,
    .stack-table tr,
    .stack-table td {
        display: block;
        width: 100%;
    }

    .stack-table tr {
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.82);
    }

    .stack-table td {
        border: 0;
        padding: 0.35rem 0;
    }

    .stack-table td::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 0.25rem;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const dreSubgrupos = <?= json_encode(array_values($dre_subgrupos ?? []), JSON_UNESCAPED_UNICODE) ?>;
    const financeConfigTabs = document.getElementById('financeConfigTabs');
    const modalCategoria = document.getElementById('modalCategoria');
    const categoriaTipoField = document.getElementById('categoriaTipo');
    const categoriaGrupoField = document.getElementById('categoriaGrupoDreId');
    const categoriaSubgrupoField = document.getElementById('categoriaSubgrupoDreId');
    const categoriaDreFixoMensalField = document.getElementById('categoriaDreFixoMensal');

    function activateTabFromHash() {
        if (!financeConfigTabs || !window.location.hash) {
            return;
        }

        const hash = window.location.hash;
        const trigger = financeConfigTabs.querySelector(`[data-bs-target="${hash}"]`);
        if (!trigger || typeof bootstrap === 'undefined') {
            return;
        }

        bootstrap.Tab.getOrCreateInstance(trigger).show();
    }

    function populateCategorySubgrupos(groupId, selectedValue = '') {
        if (!categoriaSubgrupoField) {
            return;
        }

        categoriaSubgrupoField.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = groupId ? 'Sem subgrupo padrao' : 'Selecione primeiro o grupo';
        categoriaSubgrupoField.appendChild(placeholder);

        dreSubgrupos
            .filter((item) => String(item.grupo_id || '') === String(groupId || '') && Number(item.ativo || 1) === 1)
            .forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.nome;
                option.selected = String(item.id) === String(selectedValue || '');
                categoriaSubgrupoField.appendChild(option);
            });
    }

    function syncCategoriaModalRules() {
        if (!categoriaTipoField || !categoriaDreFixoMensalField) {
            return;
        }

        const isPagar = categoriaTipoField.value === 'pagar' || categoriaTipoField.value === 'ambos';
        categoriaDreFixoMensalField.disabled = !isPagar;

        if (!isPagar) {
            categoriaDreFixoMensalField.checked = false;
        }
    }

    if (categoriaGrupoField) {
        categoriaGrupoField.addEventListener('change', function () {
            populateCategorySubgrupos(categoriaGrupoField.value, '');
        });
    }

    if (categoriaTipoField) {
        categoriaTipoField.addEventListener('change', syncCategoriaModalRules);
    }

    if (modalCategoria) {
        modalCategoria.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            const isEdit = trigger && trigger.classList.contains('btn-edit-categoria');

            document.getElementById('modalCategoriaTitle').textContent = isEdit ? 'Editar categoria financeira' : 'Nova categoria financeira';
            document.getElementById('categoriaId').value = isEdit ? (trigger.getAttribute('data-id') || '') : '';
            document.getElementById('categoriaNome').value = isEdit ? (trigger.getAttribute('data-nome') || '') : '';
            document.getElementById('categoriaTipo').value = isEdit ? (trigger.getAttribute('data-tipo') || 'ambos') : 'ambos';
            document.getElementById('categoriaOrdem').value = isEdit ? (trigger.getAttribute('data-ordem') || '0') : '0';
            document.getElementById('categoriaGrupoDreId').value = isEdit ? (trigger.getAttribute('data-grupo-id') || '') : '';
            populateCategorySubgrupos(document.getElementById('categoriaGrupoDreId').value, isEdit ? (trigger.getAttribute('data-subgrupo-id') || '') : '');
            document.getElementById('categoriaImpactaDre').checked = isEdit ? (trigger.getAttribute('data-impacta-dre') === '1') : true;
            document.getElementById('categoriaImpactaCaixa').checked = isEdit ? (trigger.getAttribute('data-impacta-caixa') === '1') : true;
            document.getElementById('categoriaDreFixoMensal').checked = isEdit ? (trigger.getAttribute('data-dre-fixo-mensal') === '1') : false;
            document.getElementById('categoriaAtivo').checked = isEdit ? (trigger.getAttribute('data-ativo') === '1') : true;
            syncCategoriaModalRules();
        });
    }

    const modalGrupo = document.getElementById('modalGrupoDre');
    if (modalGrupo) {
        modalGrupo.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            const isEdit = trigger && trigger.classList.contains('btn-edit-grupo');

            document.getElementById('modalGrupoDreTitle').textContent = isEdit ? 'Editar grupo DRE' : 'Novo grupo DRE';
            document.getElementById('grupoDreId').value = isEdit ? (trigger.getAttribute('data-id') || '') : '';
            document.getElementById('grupoDreNome').value = isEdit ? (trigger.getAttribute('data-nome') || '') : '';
            document.getElementById('grupoDreDescricao').value = isEdit ? (trigger.getAttribute('data-descricao') || '') : '';
            document.getElementById('grupoDreOrdem').value = isEdit ? (trigger.getAttribute('data-ordem') || '0') : '0';
            document.getElementById('grupoDreAtivo').checked = isEdit ? (trigger.getAttribute('data-ativo') === '1') : true;
        });
    }

    const modalSubgrupo = document.getElementById('modalSubgrupoDre');
    if (modalSubgrupo) {
        modalSubgrupo.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            const isEdit = trigger && trigger.classList.contains('btn-edit-subgrupo');

            document.getElementById('modalSubgrupoDreTitle').textContent = isEdit ? 'Editar subgrupo DRE' : 'Novo subgrupo DRE';
            document.getElementById('subgrupoDreId').value = isEdit ? (trigger.getAttribute('data-id') || '') : '';
            document.getElementById('subgrupoDreGrupoId').value = isEdit ? (trigger.getAttribute('data-grupo-id') || '') : '';
            document.getElementById('subgrupoDreNome').value = isEdit ? (trigger.getAttribute('data-nome') || '') : '';
            document.getElementById('subgrupoDreDescricao').value = isEdit ? (trigger.getAttribute('data-descricao') || '') : '';
            document.getElementById('subgrupoDreOrdem').value = isEdit ? (trigger.getAttribute('data-ordem') || '0') : '0';
            document.getElementById('subgrupoDreAtivo').checked = isEdit ? (trigger.getAttribute('data-ativo') === '1') : true;
        });
    }

    document.querySelectorAll('.finance-config-delete-form').forEach((form) => {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const entity = form.getAttribute('data-entity') || 'registro';
            const name = form.getAttribute('data-name') || '';

            if (typeof Swal === 'undefined') {
                form.submit();
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: 'Excluir registro?',
                text: name ? `Deseja realmente excluir ${entity} "${name}"?` : `Deseja realmente excluir este ${entity}?`,
                showCancelButton: true,
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    syncCategoriaModalRules();
    activateTabFromHash();
})();
</script>
<?= $this->endSection() ?>
