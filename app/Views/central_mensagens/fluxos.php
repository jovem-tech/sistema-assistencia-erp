<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-items-center gap-2">
        <h2><i class="bi bi-diagram-2 me-2"></i>Fluxos de Atendimento</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('atendimento-whatsapp-fluxos')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<?= $this->include('central_mensagens/_menu') ?>

<div class="row g-3">
    <div class="col-12 col-xl-5">
        <div class="card glass-card">
            <div class="card-header fw-semibold">Novo fluxo</div>
            <div class="card-body">
                <form action="<?= base_url('atendimento-whatsapp/fluxos/salvar') ?>" method="post" class="row g-2">
                    <?= csrf_field() ?>
                    <div class="col-md-8">
                        <label class="form-label form-label-sm">Nome</label>
                        <input type="text" name="nome" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">Tipo</label>
                        <input type="text" name="tipo_fluxo" class="form-control form-control-sm" placeholder="operacional" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-sm">Descrição</label>
                        <textarea name="descricao" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-sm">Etapas (virgula)</label>
                        <input type="text" name="etapas" class="form-control form-control-sm" placeholder="triagem, diagnostico, orcamento, reparo, pronto">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">Ordem</label>
                        <input type="number" name="ordem" class="form-control form-control-sm" value="0">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="ativo" value="1" id="fluxoAtivo" checked>
                            <label class="form-check-label small" for="fluxoAtivo">Ativo</label>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end justify-content-end">
                        <button type="submit" class="btn btn-glow btn-sm">Salvar fluxo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card glass-card">
            <div class="card-header fw-semibold">Fluxos cadastrados</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Etapas</th>
                                <th>Ordem</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($fluxos)): ?>
                                <?php foreach ($fluxos as $f): ?>
                                    <tr>
                                        <td><?= esc($f['nome']) ?></td>
                                        <td><span class="badge bg-secondary"><?= esc($f['tipo_fluxo']) ?></span></td>
                                        <td class="small text-truncate" style="max-width: 320px;"><?= esc((string) ($f['etapas_json'] ?? '[]')) ?></td>
                                        <td><?= (int) ($f['ordem'] ?? 0) ?></td>
                                        <td class="text-center">
                                            <form action="<?= base_url('atendimento-whatsapp/fluxos/toggle/' . (int) $f['id']) ?>" method="post">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm <?= (int) $f['ativo'] === 1 ? 'btn-success' : 'btn-outline-secondary' ?>">
                                                    <?= (int) $f['ativo'] === 1 ? 'Ativo' : 'Inativo' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-muted p-3">Nenhum fluxo cadastrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>



