<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-items-center gap-2">
        <h2><i class="bi bi-people me-2"></i>Filas e Responsaveis</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('atendimento-whatsapp-filas')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<?= $this->include('central_mensagens/_menu') ?>

<div class="card glass-card mb-3">
    <div class="card-body">
        <form method="get" action="<?= base_url('atendimento-whatsapp/filas') ?>" class="row g-2">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="">Todos</option>
                    <?php foreach (['aberta', 'aguardando', 'resolvida', 'arquivada'] as $status): ?>
                        <option value="<?= esc($status) ?>" <?= ($filtro_status ?? '') === $status ? 'selected' : '' ?>><?= esc(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm">Prioridade</label>
                <select class="form-select form-select-sm" name="prioridade">
                    <option value="">Todas</option>
                    <?php foreach (['baixa', 'normal', 'alta', 'urgente'] as $prioridade): ?>
                        <option value="<?= esc($prioridade) ?>" <?= ($filtro_prioridade ?? '') === $prioridade ? 'selected' : '' ?>><?= esc(ucfirst($prioridade)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm">Responsavel</label>
                <select class="form-select form-select-sm" name="responsavel_id">
                    <option value="">Todos</option>
                    <?php foreach (($usuariosAtivos ?? []) as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= (int) ($filtro_responsavel_id ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="aguardandoHumano" name="aguardando_humano" <?= !empty($filtro_aguardando_humano) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="aguardandoHumano">Aguardando humano</label>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('atendimento-whatsapp/filas') ?>">Limpar</a>
                <button type="submit" class="btn btn-sm btn-glow">Aplicar filtros</button>
            </div>
        </form>
    </div>
</div>

<div class="card glass-card">
    <div class="card-header fw-semibold">Fila operacional</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Conversa</th>
                        <th>Telefone</th>
                        <th>OS</th>
                        <th>Status</th>
                        <th>Prioridade</th>
                        <th>Responsavel</th>
                        <th>Ultima msg</th>
                        <th class="text-end">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($conversas)): ?>
                        <?php foreach ($conversas as $c): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($c['cliente_nome'] ?: $c['nome_contato'] ?: 'Contato sem nome')) ?></div>
                                    <?php if ((int) ($c['nao_lidas'] ?? 0) > 0): ?>
                                        <span class="badge bg-danger mt-1"><?= (int) $c['nao_lidas'] ?> nao lida(s)</span>
                                    <?php endif; ?>
                                    <?php if ((int) ($c['aguardando_humano'] ?? 0) === 1): ?>
                                        <span class="badge bg-warning text-dark mt-1">Aguardando humano</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= esc((string) ($c['telefone'] ?? '-')) ?></td>
                                <td class="small"><?= esc((string) ($c['numero_os'] ?? '-')) ?></td>
                                <td class="small"><?= esc((string) ($c['status'] ?? '-')) ?></td>
                                <td class="small"><?= esc((string) ($c['prioridade'] ?? 'normal')) ?></td>
                                <td class="small"><?= esc((string) ($c['responsavel_nome'] ?? 'Nao atribuido')) ?></td>
                                <td class="small"><?= !empty($c['ultima_mensagem_em']) ? esc(date('d/m/Y H:i', strtotime((string) $c['ultima_mensagem_em']))) : '-' ?></td>
                                <td class="text-end">
                                    <form method="post" action="<?= base_url('atendimento-whatsapp/filas/atualizar') ?>" class="d-flex gap-1 justify-content-end">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="conversa_id" value="<?= (int) $c['id'] ?>">
                                        <select name="status" class="form-select form-select-sm" style="max-width: 125px;">
                                            <?php foreach (['aberta', 'aguardando', 'resolvida', 'arquivada'] as $status): ?>
                                                <option value="<?= esc($status) ?>" <?= ($c['status'] ?? '') === $status ? 'selected' : '' ?>><?= esc(ucfirst($status)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="prioridade" class="form-select form-select-sm" style="max-width: 120px;">
                                            <?php foreach (['baixa', 'normal', 'alta', 'urgente'] as $prioridade): ?>
                                                <option value="<?= esc($prioridade) ?>" <?= ($c['prioridade'] ?? 'normal') === $prioridade ? 'selected' : '' ?>><?= esc(ucfirst($prioridade)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="responsavel_id" class="form-select form-select-sm" style="max-width: 160px;">
                                            <option value="">Nao atribuido</option>
                                            <?php foreach (($usuariosAtivos ?? []) as $u): ?>
                                                <option value="<?= (int) $u['id'] ?>" <?= (int) ($c['responsavel_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="automacao_ativa" value="<?= (int) ($c['automacao_ativa'] ?? 1) === 1 ? '1' : '0' ?>">
                                        <input type="hidden" name="aguardando_humano" value="<?= (int) ($c['aguardando_humano'] ?? 0) === 1 ? '1' : '0' ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('atendimento-whatsapp?conversa_id=' . (int) $c['id']) ?>">Abrir</a>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-muted p-3">Nenhuma conversa encontrada com os filtros atuais.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>


