<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h2><i class="bi bi-person me-2"></i><?= esc($cliente['nome_razao']) ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('clientes')" title="Ajuda sobre Clientes">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('clientes', 'editar')): ?>
        <a href="<?= base_url('clientes/editar/' . $cliente['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <?php endif; ?>
        <a href="<?= base_url('clientes') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('clientes') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Client Info -->
    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Dados do Cliente</h5>
            </div>
            <div class="card-body">
                <div class="detail-group">
                    <div class="detail-label">Tipo</div>
                    <div class="detail-value"><?= $cliente['tipo_pessoa'] === 'fisica' ? 'Pessoa Física' : 'Pessoa Jurídica' ?></div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">CPF/CNPJ</div>
                    <div class="detail-value"><?= esc($cliente['cpf_cnpj'] ?? '-') ?></div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Telefone</div>
                    <div class="detail-value"><?= esc($cliente['telefone1']) ?> <?= $cliente['telefone2'] ? '/ ' . esc($cliente['telefone2']) : '' ?></div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?= esc($cliente['email'] ?? '-') ?></div>
                </div>
                <?php if (!empty($cliente['nome_contato']) || !empty($cliente['telefone_contato'])): ?>
                <div class="detail-group">
                    <div class="detail-label">Contato Adicional</div>
                    <div class="detail-value">
                        <?= esc($cliente['nome_contato'] ?? '') ?> 
                        <?= !empty($cliente['telefone_contato']) ? ' - ' . esc($cliente['telefone_contato']) : '' ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="detail-group">
                    <div class="detail-label">Endereço</div>
                    <div class="detail-value">
                        <?= esc(($cliente['endereco'] ?? '') . ($cliente['numero'] ? ', ' . $cliente['numero'] : '')) ?>
                        <?= $cliente['complemento'] ? ' - ' . esc($cliente['complemento']) : '' ?><br>
                        <?= esc(($cliente['bairro'] ?? '') . ' - ' . ($cliente['cidade'] ?? '') . '/' . ($cliente['uf'] ?? '')) ?>
                        <?= $cliente['cep'] ? ' - CEP: ' . esc($cliente['cep']) : '' ?>
                    </div>
                </div>
                <?php if (!empty($cliente['observacoes'])): ?>
                <div class="detail-group">
                    <div class="detail-label">Observações</div>
                    <div class="detail-value"><?= nl2br(esc($cliente['observacoes'])) ?></div>
                </div>
                <?php endif; ?>
                <div class="detail-group">
                    <div class="detail-label">Cadastrado em</div>
                    <div class="detail-value"><?= formatDate($cliente['created_at'], true) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- OS History -->
    <div class="col-md-8">
        <div class="card glass-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-clipboard-check me-2"></i>Ordens de Serviço</h5>
                <?php if (can('os', 'criar')): ?>
                <a href="<?= base_url('os/nova?cliente_id=' . $cliente['id']) ?>" class="btn btn-glow btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Nova OS
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nº OS</th>
                                <th>Equipamento</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Valor</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ordens)): ?>
                            <tr><td colspan="6" class="text-center py-3 text-muted">Nenhuma OS encontrada</td></tr>
                            <?php else: foreach ($ordens as $os): ?>
                            <tr>
                                <td><strong><?= esc($os['numero_os']) ?></strong></td>
                                <td><?= esc($os['equip_marca'] . ' ' . $os['equip_modelo']) ?></td>
                                <td><?= getStatusBadge($os['status']) ?></td>
                                <td><?= formatDate($os['created_at']) ?></td>
                                <td><?= formatMoney($os['valor_final']) ?></td>
                                <td>
                                    <a href="<?= base_url('os/visualizar/' . $os['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Equipment list -->
        <div class="card glass-card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-laptop me-2"></i>Equipamentos</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Nº Série</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipamentos)): ?>
                            <tr><td colspan="5" class="text-center py-3 text-muted">Nenhum equipamento cadastrado</td></tr>
                            <?php else: foreach ($equipamentos as $eq): ?>
                            <tr>
                                <td><?= getEquipTipo($eq['tipo_nome']) ?></td>
                                <td><?= esc($eq['marca_nome']) ?></td>
                                <td><?= esc($eq['modelo_nome']) ?></td>
                                <td><?= esc($eq['numero_serie'] ?? '-') ?></td>
                                <td>
                                    <?php if (can('equipamentos', 'editar')): ?>
                                    <a href="<?= base_url('equipamentos/editar/' . $eq['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
