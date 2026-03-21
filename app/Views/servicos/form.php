<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header mb-4 d-flex justify-content-between align-items-center">
    <h2 class="mb-0">
        <i class="bi bi-gear-wide-connected me-2"></i>
        <?= isset($servico) ? 'Editar Serviço' : 'Novo Serviço' ?>
    </h2>
    <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('servicos')" title="Ajuda sobre Serviços">
        <i class="bi bi-question-circle me-1"></i>Ajuda
    </button>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card glass-card">
            <div class="card-body">
                <form action="<?= isset($servico) ? base_url('servicos/atualizar/' . $servico['id']) : base_url('servicos/salvar') ?>" method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Serviço <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control" value="<?= old('nome', $servico['nome'] ?? '') ?>" required placeholder="Ex: Troca de Tela">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descri??o</label>
                        <textarea name="descricao" class="form-control" rows="3" placeholder="Detalhes técnicos do serviço..."><?= old('descricao', $servico['descricao'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Valor Padrão (R$) <span class="text-danger">*</span></label>
                            <input type="text" name="valor" class="form-control money" value="<?= old('valor', isset($servico) ? number_format($servico['valor'], 2, ',', '.') : '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="ativo" <?= (old('status', $servico['status'] ?? '') === 'ativo') ? 'selected' : '' ?>>Ativo</option>
                                <option value="inativo" <?= (old('status', $servico['status'] ?? '') === 'inativo') ? 'selected' : '' ?>>Inativo</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4 border-secondary opacity-10">

                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('servicos') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('servicos') ?>">
                            <i class="bi bi-arrow-left me-1"></i>Voltar
                        </a>
                        <button type="submit" class="btn btn-primary btn-glow px-4">
                            <i class="bi bi-save me-1"></i>Salvar Serviço
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
