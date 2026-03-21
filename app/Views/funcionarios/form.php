<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><?= $title ?></h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('funcionarios')" title="Ajuda sobre Funcionários">
                    <i class="bi bi-question-circle me-1"></i>Ajuda
                </button>
                <a href="<?= base_url('funcionarios') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('funcionarios') ?>">
                    <i class="bi bi-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        <form action="<?= base_url(isset($funcionario) ? 'funcionarios/atualizar/' . $funcionario['id'] : 'funcionarios/salvar') ?>" method="POST">
            <?= csrf_field() ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Nome Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nome" value="<?= esc(old('nome', $funcionario['nome'] ?? '')) ?>" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label text-muted">CPF <span class="text-danger">*</span></label>
                    <input type="text" class="form-control cpf" name="cpf" value="<?= esc(old('cpf', $funcionario['cpf'] ?? '')) ?>" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label text-muted">RG</label>
                    <input type="text" class="form-control" name="rg" value="<?= esc(old('rg', $funcionario['rg'] ?? '')) ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label text-muted">Data de Nascimento</label>
                    <input type="date" class="form-control" name="data_nascimento" value="<?= esc(old('data_nascimento', $funcionario['data_nascimento'] ?? '')) ?>">
                </div>
                
                <h5 class="mt-4 mb-3 border-bottom pb-2">Contrato e Função</h5>

                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Cargo / Função</label>
                    <input type="text" class="form-control" name="cargo" value="<?= esc(old('cargo', $funcionario['cargo'] ?? '')) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Salário / Remuneração</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control money" name="salario" value="<?= esc(old('salario', $funcionario['salario'] ?? '0,00')) ?>">
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label text-muted">Data Admissão</label>
                    <input type="date" class="form-control" name="data_admissao" value="<?= esc(old('data_admissao', $funcionario['data_admissao'] ?? '')) ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label text-muted">Data Demissão</label>
                    <input type="date" class="form-control" name="data_demissao" value="<?= esc(old('data_demissao', $funcionario['data_demissao'] ?? '')) ?>">
                </div>
                
                <h5 class="mt-4 mb-3 border-bottom pb-2">Contato</h5>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= esc(old('email', $funcionario['email'] ?? '')) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Telefone / WhatsApp <span class="text-danger">*</span></label>
                    <input type="text" class="form-control telefone" name="telefone" value="<?= esc(old('telefone', $funcionario['telefone'] ?? '')) ?>" required>
                </div>
                
                <h5 class="mt-4 mb-3 border-bottom pb-2">Endereço</h5>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label text-muted">CEP</label>
                    <input type="text" class="form-control cep" name="cep" id="cep" value="<?= esc(old('cep', $funcionario['cep'] ?? '')) ?>">
                </div>
                <div class="col-md-7 mb-3">
                    <label class="form-label text-muted">Endereço</label>
                    <input type="text" class="form-control" name="endereco" id="endereco" value="<?= esc(old('endereco', $funcionario['endereco'] ?? '')) ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label text-muted">Número</label>
                    <input type="text" class="form-control" name="numero" value="<?= esc(old('numero', $funcionario['numero'] ?? '')) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Complemento</label>
                    <input type="text" class="form-control" name="complemento" value="<?= esc(old('complemento', $funcionario['complemento'] ?? '')) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Bairro</label>
                    <input type="text" class="form-control" name="bairro" id="bairro" value="<?= esc(old('bairro', $funcionario['bairro'] ?? '')) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label text-muted">Cidade</label>
                    <input type="text" class="form-control" name="cidade" id="cidade" value="<?= esc(old('cidade', $funcionario['cidade'] ?? '')) ?>">
                </div>
                <div class="col-md-1 mb-3">
                    <label class="form-label text-muted">UF</label>
                    <input type="text" class="form-control" name="uf" id="uf" value="<?= esc(old('uf', $funcionario['uf'] ?? '')) ?>" maxlength="2">
                </div>
                
                <h5 class="mt-4 mb-3 border-bottom pb-2">Outras Informações</h5>

                <div class="col-12 mb-3">
                    <label class="form-label text-muted">Observações</label>
                    <textarea class="form-control" name="observacoes" rows="3"><?= esc(old('observacoes', $funcionario['observacoes'] ?? '')) ?></textarea>
                </div>
                
                <div class="col-12 mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?= old('ativo', $funcionario['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label text-muted" for="ativo">Funcionário Ativo (Pode acessar a empresa/Logar se tiver conta associada)</label>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="button" class="btn btn-outline-secondary me-2" onclick="history.back()">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-glow">
                    <i class="bi bi-save me-2"></i>Salvar Funcionário
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('.cpf').mask('000.000.000-00', {reverse: true});
    $('.money').mask('#.##0,00', {reverse: true});
    // CEP API
    $('#cep').blur(function() {
        var cep = $(this).val().replace(/\D/g, '');
        if (cep != "") {
            var validacep = /^[0-9]{8}$/;
            if(validacep.test(cep)) {
                $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {
                    if (!("erro" in dados)) {
                        $("#endereco").val(dados.logradouro);
                        $("#bairro").val(dados.bairro);
                        $("#cidade").val(dados.localidade);
                        $("#uf").val(dados.uf);
                    }
                });
            }
        }
    });
});
</script>
<?= $this->endSection() ?>
