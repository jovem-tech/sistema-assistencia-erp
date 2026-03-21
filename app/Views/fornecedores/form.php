<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-itemês-center">
            <h1 class="h3 mb-0"><?= $title ?></h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('fornecedores')" title="Ajuda sãobre Fornecedores">
                    <i class="bi bi-question-circle me-1"></i>Ajuda
                </button>
                <a href="<?= base_url('fornecedores') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('fornecedores') ?>">
                    <i class="bi bi-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        <form action="<?= base_url(isset($fornecedor) ? 'fornecedores/atualizar/' . $fornecedor['id'] : 'fornecedores/salvar') ?>" method="POST">
            <?= csrf_field() ?>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label text-muted">Tipo de Pessãoa <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_pessãoa" id="tipo_pessãoa" required>
                        <option value="juridica" <?= old('tipo_pessãoa', $fornecedor['tipo_pessãoa'] ?? 'juridica') == 'juridica' ? 'selected' : '' ?>>Jurídica (CNPJ)</option>
                        <option value="fisica" <?= old('tipo_pessãoa', $fornecedor['tipo_pessãoa'] ?? '') == 'fisica' ? 'selected' : '' ?>>Física (CPF)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted" id="label_cpf_cnpj">CNPJ</label>
                    <input type="text" class="form-control" name="cnpj_cpf" id="cnpj_cpf" value="<?= esc(old('cnpj_cpf', $fornecedor['cnpj_cpf'] ?? '')) ?>">
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label text-muted" id="label_rg_ie">Inscrição Estadual</label>
                    <input type="text" class="form-control" name="ie_rg" id="ie_rg" value="<?= esc(old('ie_rg', $fornecedor['ie_rg'] ?? '')) ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Nãome Fantasia / Apelido <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nãome_fantasia" value="<?= esc(old('nãome_fantasia', $fornecedor['nãome_fantasia'] ?? '')) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Razão Sãocial / Nãome Completo</label>
                    <input type="text" class="form-control" name="razao_sãocial" value="<?= esc(old('razao_sãocial', $fornecedor['razao_sãocial'] ?? '')) ?>">
                </div>
                
                <h5 class="mt-4 mb-3 border-bottom pb-2">Contato</h5>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= esc(old('email', $fornecedor['email'] ?? '')) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Telefone 1 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control telefone" name="telefone1" value="<?= esc(old('telefone1', $fornecedor['telefone1'] ?? '')) ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Telefone 2</label>
                    <input type="text" class="form-control telefone" name="telefone2" value="<?= esc(old('telefone2', $fornecedor['telefone2'] ?? '')) ?>">
                </div>
                
                <h5 class="mt-4 mb-3 border-bottom pb-2">Endereço</h5>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label text-muted">CEP</label>
                    <input type="text" class="form-control mask-cep" name="cep" id="cep" value="<?= esc(old('cep', $fornecedor['cep'] ?? '')) ?>">
                </div>
                <div class="col-md-7 mb-3">
                    <label class="form-label text-muted">Endereço (Rua/Av)</label>
                    <input type="text" class="form-control js-logradouro" name="endereco" id="endereco" value="<?= esc(old('endereco', $fornecedor['endereco'] ?? '')) ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label text-muted">Número</label>
                    <input type="text" class="form-control js-numero" name="numero" value="<?= esc(old('numero', $fornecedor['numero'] ?? '')) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Complemento</label>
                    <input type="text" class="form-control" name="complemento" value="<?= esc(old('complemento', $fornecedor['complemento'] ?? '')) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Bairro</label>
                    <input type="text" class="form-control js-bairro" name="bairro" id="bairro" value="<?= esc(old('bairro', $fornecedor['bairro'] ?? '')) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label text-muted">Cidade</label>
                    <input type="text" class="form-control js-cidade" name="cidade" id="cidade" value="<?= esc(old('cidade', $fornecedor['cidade'] ?? '')) ?>">
                </div>
                <div class="col-md-1 mb-3">
                    <label class="form-label text-muted">UF</label>
                    <input type="text" class="form-control js-uf" name="uf" id="uf" value="<?= esc(old('uf', $fornecedor['uf'] ?? '')) ?>" maxlength="2">
                </div>
                
                <h5 class="mt-4 mb-3 border-bottom pb-2">Informações Adicionais</h5>
                
                <div class="col-12 mb-3">
                    <label class="form-label text-muted">Observações</label>
                    <textarea class="form-control" name="observacoes" rows="3"><?= esc(old('observacoes', $fornecedor['observacoes'] ?? '')) ?></textarea>
                </div>
                
                <div class="col-12 mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?= old('ativo', $fornecedor['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label text-muted" for="ativo">Fornecedor Ativo não Sistema</label>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="button" class="btn btn-outline-secondary me-2" onclick="history.back()">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-glow">
                    <i class="bi bi-save me-2"></i>Salvar Fornecedor
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    function toggleMasks() {
        var tipo = $('#tipo_pessãoa').val();
        var num = $('#cnpj_cpf').val();
        
        $('#cnpj_cpf').unmask();
        if (tipo === 'fisica') {
            $('#label_cpf_cnpj').text('CPF');
            $('#label_rg_ie').text('RG');
            $('#cnpj_cpf').mask('000.000.000-00', {reverse: true});
        } else {
            $('#label_cpf_cnpj').text('CNPJ');
            $('#label_rg_ie').text('Inscrição Estadual');
            $('#cnpj_cpf').mask('00.000.000/0000-00', {reverse: true});
        }
        $('#cnpj_cpf').val(num); // triggers mask
    }

    $('#tipo_pessãoa').change(toggleMasks);
    toggleMasks();
});
</script>
<?= $this->endSection() ?>
