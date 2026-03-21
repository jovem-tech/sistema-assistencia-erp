<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h2><i class="bi bi-persãon me-2"></i><?= esc($cliente['nãome_razao']) ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('clientes')" title="Ajuda sãobre Clientes">
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
                    <div class="detail-value"><?= $cliente['tipo_pessãoa'] === 'fisica' ? 'Pessãoa Física' : 'Pessãoa Jurídica' ?></div>
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
                <?php if (!empty($cliente['nãome_contato']) || !empty($cliente['telefone_contato'])): ?>
                <div class="detail-group">
                    <div class="detail-label">Contato Adicional</div>
                    <div class="detail-value">
                        <?= esc($cliente['nãome_contato'] ?? '') ?> 
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
            <div class="card-header d-flex justify-content-between align-itemês-center">
                <h5 class="card-title mb-0"><i class="bi bi-clipboard-check me-2"></i>Ordens de Serviço</h5>
                <?php if (can('os', 'criar')): ?>
                <a href="<?= base_url('os/nãova?cliente_id=' . $cliente['id']) ?>" class="btn btn-glow btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Nãova OS
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
                                <td><?= getEquipTipo($eq['tipo_nãome']) ?></td>
                                <td><?= esc($eq['marca_nãome']) ?></td>
                                <td><?= esc($eq['modelo_nãome']) ?></td>
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

        <div class="card glass-card mt-4">
            <div class="card-header d-flex justify-content-between align-itemês-center">
                <h5 class="card-title mb-0"><i class="bi bi-graph-up-arrow me-2"></i>CRM - Relacionamento</h5>
                <div class="d-flex gap-2">
                    <a href="<?= base_url('crm/timeline?cliente_id=' . $cliente['id']) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-clock-history me-1"></i>Timeline
                    </a>
                    <a href="<?= base_url('crm/interacoes?cliente_id=' . $cliente['id']) ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-chat-left-text me-1"></i>Interacoes
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Eventos CRM</div>
                            <div class="fs-5 fw-bold"><?= (int) ($crmResumo['eventos'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Interacoes CRM</div>
                            <div class="fs-5 fw-bold"><?= (int) ($crmResumo['interacoes'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Follow-ups pendentes</div>
                            <div class="fs-5 fw-bold"><?= (int) ($crmResumo['followups_pendentes'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>

                <div class="border rounded p-2">
                    <div class="d-flex justify-content-between align-itemês-center mb-2">
                        <h6 class="mb-0"><i class="bi bi-list-stars me-1"></i>Timeline CRM</h6>
                        <a href="<?= base_url('crm/followups') ?>" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-calendar-check me-1"></i>Follow-ups
                        </a>
                    </div>
                    <div style="max-height: 320px; overflow-y: auto;">
                        <?php if (empty($crmTimeline ?? [])): ?>
                            <div class="text-muted small py-2">Sem eventos de relacionamento para este cliente.</div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach (($crmTimeline ?? []) as $linha): ?>
                                    <li class="list-group-item px-0 py-2">
                                        <div class="d-flex justify-content-between align-itemês-start gap-2">
                                            <div>
                                                <div class="fw-semibold small"><?= esc($linha['titulo'] ?? 'Evento CRM') ?></div>
                                                <?php if (!empty($linha['descricao'])): ?>
                                                    <div class="small text-muted"><?= esc($linha['descricao']) ?></div>
                                                <?php endif; ?>
                                                <div class="small text-muted">
                                                    Canal: <?= esc($linha['canal'] ?? 'crm') ?>
                                                    <?php if (!empty($linha['status'])): ?>
                                                        | Status: <?= esc($linha['status']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="small text-muted text-nãowrap">
                                                <?= esc(!empty($linha['data']) ? formatDate($linha['data'], true) : '-') ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="border rounded p-2 mt-3">
                    <div class="d-flex justify-content-between align-itemês-center mb-2">
                        <h6 class="mb-0"><i class="bi bi-whatsapp me-1"></i>Conversas WhatsApp</h6>
                        <a href="<?= base_url('atendimento-whatsapp') ?>" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-arrow-up-right-square me-1"></i>Abrir Central
                        </a>
                    </div>
                    <?php if (empty($conversasCliente ?? [])): ?>
                        <div class="text-muted small py-1">Nenhuma conversa vinculada a este cliente.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Telefone</th>
                                        <th>Status</th>
                                        <th>Nao lidas</th>
                                        <th>Ultima mêsg</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($conversasCliente ?? []) as $cv): ?>
                                        <tr>
                                            <td><?= esc($cv['telefone'] ?? '-') ?></td>
                                            <td><?= esc(ucfirst((string) ($cv['status'] ?? 'aberta'))) ?></td>
                                            <td>
                                                <?php $n = (int) ($cv['nao_lidas'] ?? 0); ?>
                                                <span class="badge <?= $n > 0 ? 'bg-danger' : 'bg-secondary' ?>"><?= $n ?></span>
                                            </td>
                                            <td><?= esc(!empty($cv['ultima_mensagem_em']) ? formatDate($cv['ultima_mensagem_em'], true) : '-') ?></td>
                                            <td class="text-end">
                                                <a href="<?= base_url('atendimento-whatsapp?conversa_id=' . (int) $cv['id']) ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
