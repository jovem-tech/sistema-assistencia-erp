<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = isset($cliente);
$tipoPessoaValue = old('tipo_pessoa', $cliente['tipo_pessoa'] ?? 'fisica');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h2><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $title ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('clientes')" title="Ajuda sobre Clientes">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('clientes') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('clientes') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form id="clienteForm" action="<?= $isEdit ? base_url('clientes/atualizar/' . $cliente['id']) : base_url('clientes/salvar') ?>" method="POST" data-cnpj-lookup-url="<?= base_url('clientes/consultar-cnpj') ?>">
            <?= csrf_field() ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label text-muted" for="tipo_pessoa">Tipo de Pessoa</label>
                    <select name="tipo_pessoa" id="tipo_pessoa" class="form-select">
                        <option value="fisica" <?= $tipoPessoaValue === 'fisica' ? 'selected' : '' ?>>Pessoa Física</option>
                        <option value="juridica" <?= $tipoPessoaValue === 'juridica' ? 'selected' : '' ?>>Pessoa Jurídica</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted" for="cpf_cnpj" id="label_cpf_cnpj">CPF</label>
                    <div class="position-relative">
                        <input
                            type="text"
                            name="cpf_cnpj"
                            id="cpf_cnpj"
                            class="form-control"
                            value="<?= esc($isEdit ? ($cliente['cpf_cnpj'] ?? '') : old('cpf_cnpj')) ?>"
                            autocomplete="off"
                        >
                    </div>
                    <div id="cnpjLookupFeedback" class="form-text d-none"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted" for="rg_ie" id="label_rg_ie">RG</label>
                    <input type="text" name="rg_ie" id="rg_ie" class="form-control" value="<?= esc($isEdit ? ($cliente['rg_ie'] ?? '') : old('rg_ie')) ?>">
                </div>
            </div>

            <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-person me-1"></i>Dados Pessoais</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="nome_razao">Nome / Razão Social *</label>
                    <input type="text" name="nome_razao" id="nome_razao" class="form-control" data-auto-title-case="person-name" required value="<?= esc($isEdit ? $cliente['nome_razao'] : old('nome_razao')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="telefone1">Telefone 1 *</label>
                    <input type="text" name="telefone1" id="telefone1" class="form-control mask-telefone" required value="<?= esc($isEdit ? $cliente['telefone1'] : old('telefone1')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted" for="telefone2">Telefone 2 (Opcional)</label>
                    <input type="text" name="telefone2" id="telefone2" class="form-control mask-telefone" value="<?= esc($isEdit ? ($cliente['telefone2'] ?? '') : old('telefone2')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted" for="email">Email (Opcional)</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= esc($isEdit ? ($cliente['email'] ?? '') : old('email')) ?>">
                </div>
            </div>

            <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-people me-1"></i>Contato Adicional <span class="text-lowercase">(Opcional)</span></h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-muted" for="nome_contato">Nome (Ex: Esposa, Filho, Vizinho)</label>
                    <input type="text" name="nome_contato" id="nome_contato" class="form-control" value="<?= esc($isEdit ? ($cliente['nome_contato'] ?? '') : old('nome_contato')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted" for="telefone_contato">Telefone do Contato</label>
                    <input type="text" name="telefone_contato" id="telefone_contato" class="form-control mask-telefone" value="<?= esc($isEdit ? ($cliente['telefone_contato'] ?? '') : old('telefone_contato')) ?>">
                </div>
            </div>

            <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-geo-alt me-1"></i>Endereço</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label text-muted" for="cep">CEP</label>
                    <input type="text" name="cep" id="cep" class="form-control mask-cep" value="<?= esc($isEdit ? ($cliente['cep'] ?? '') : old('cep')) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label text-muted" for="endereco">Endereço</label>
                    <input type="text" name="endereco" id="endereco" class="form-control js-logradouro" value="<?= esc($isEdit ? ($cliente['endereco'] ?? '') : old('endereco')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted" for="numero">Número</label>
                    <input type="text" name="numero" id="numero" class="form-control js-numero" value="<?= esc($isEdit ? ($cliente['numero'] ?? '') : old('numero')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted" for="complemento">Complemento</label>
                    <input type="text" name="complemento" id="complemento" class="form-control" value="<?= esc($isEdit ? ($cliente['complemento'] ?? '') : old('complemento')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted" for="bairro">Bairro</label>
                    <input type="text" name="bairro" id="bairro" class="form-control js-bairro" value="<?= esc($isEdit ? ($cliente['bairro'] ?? '') : old('bairro')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted" for="cidade">Cidade</label>
                    <input type="text" name="cidade" id="cidade" class="form-control js-cidade" value="<?= esc($isEdit ? ($cliente['cidade'] ?? '') : old('cidade')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted" for="uf">UF</label>
                    <select name="uf" id="uf" class="form-select js-uf">
                        <option value="">--</option>
                        <?php
                        $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                        foreach ($ufs as $uf): ?>
                            <option value="<?= $uf ?>" <?= old('uf', $cliente['uf'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label text-muted" for="observacoes">Observações</label>
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="3"><?= esc($isEdit ? ($cliente['observacoes'] ?? '') : old('observacoes')) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-3 flex-wrap">
                <button type="submit" class="btn btn-glow">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
                </button>
                <a href="<?= base_url('clientes') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    const $form = $('#clienteForm');
    const $tipoPessoa = $('#tipo_pessoa');
    const $cpfCnpj = $('#cpf_cnpj');
    const $labelCpfCnpj = $('#label_cpf_cnpj');
    const $labelRgIe = $('#label_rg_ie');
    const $feedback = $('#cnpjLookupFeedback');
    const lookupUrl = String($form.data('cnpjLookupUrl') || '');
    const LOOKUP_DEBOUNCE_MS = 700;
    const LOOKUP_FAILURE_COOLDOWN_MS = 60000;
    let resolvedCnpj = '';
    let lookupInFlight = false;
    let lookupTimer = null;
    let lastLookupAttempt = {
        cnpj: '',
        success: null,
        at: 0
    };

    function normalizeDigits(value) {
        return String(value || '').replace(/\D/g, '');
    }

    function showLookupFeedback(message, tone) {
        const toneClass = tone === 'warning' ? 'text-warning' : (tone === 'success' ? 'text-success' : 'text-muted');
        $feedback.removeClass('d-none text-warning text-success text-muted').addClass(toneClass).text(message || '');
    }

    function clearLookupFeedback() {
        $feedback.addClass('d-none').removeClass('text-warning text-success text-muted').text('');
    }

    function notifyLookupIssue(title, text, icon) {
        window.DSFeedback.fire({
            icon: icon || 'warning',
            title: title,
            text: text,
            confirmButtonText: 'Entendi',
        });
    }

    function formatTelefone(value) {
        const digits = normalizeDigits(value);
        if (digits.length === 11) {
            return digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
        if (digits.length === 10) {
            return digits.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        }
        return value || '';
    }

    function formatCep(value) {
        const digits = normalizeDigits(value);
        if (digits.length === 8) {
            return digits.replace(/(\d{5})(\d{3})/, '$1-$2');
        }
        return value || '';
    }

    function resetLookupTimer() {
        if (lookupTimer) {
            window.clearTimeout(lookupTimer);
            lookupTimer = null;
        }
    }

    function rememberLookupAttempt(cnpjDigits, success) {
        lastLookupAttempt = {
            cnpj: cnpjDigits,
            success: success === true,
            at: Date.now()
        };
    }

    function shouldThrottleFailedLookup(cnpjDigits) {
        return lastLookupAttempt.cnpj === cnpjDigits
            && lastLookupAttempt.success === false
            && (Date.now() - lastLookupAttempt.at) < LOOKUP_FAILURE_COOLDOWN_MS;
    }

    function setLookupLoading(isLoading) {
        lookupInFlight = isLoading === true;
        $cpfCnpj.toggleClass('loading-input', lookupInFlight);
        $cpfCnpj.parent().toggleClass('position-relative', lookupInFlight || $cpfCnpj.parent().hasClass('position-relative'));
        $cpfCnpj.siblings('.js-cnpj-lookup-spinner').remove();

        if (!lookupInFlight) {
            return;
        }

        $('<div class="spinner-border spinner-border-sm position-absolute js-cnpj-lookup-spinner" style="right: 10px; top: 12px; z-index: 5;" role="status" aria-hidden="true"></div>').insertAfter($cpfCnpj);
    }

    function fillField(selector, value, formatter) {
        const $field = $(selector);
        if (!$field.length) {
            return;
        }

        const preparedValue = typeof formatter === 'function' ? formatter(value) : String(value || '').trim();
        if (!preparedValue) {
            return;
        }

        const currentValue = String($field.val() || '').trim();
        const lastAutofilledValue = String($field.data('cnpjAutofilledValue') || '').trim();
        if (currentValue !== '' && currentValue !== lastAutofilledValue) {
            return;
        }

        $field.val(preparedValue).data('cnpjAutofilledValue', preparedValue).trigger('change');
    }

    function focusNextField() {
        const $preferred = $('#telefone1').val().trim() === '' ? $('#telefone1') : ($('#numero').val().trim() === '' ? $('#numero') : $('#email'));
        if ($preferred.length) {
            $preferred.trigger('focus');
        }
    }

    function applyPessoaMask() {
        const tipo = String($tipoPessoa.val() || 'fisica');
        const currentValue = $cpfCnpj.val();

        if ($.fn.unmask) {
            $cpfCnpj.unmask();
        }

        if (tipo === 'juridica') {
            $labelCpfCnpj.text('CNPJ');
            $labelRgIe.text('RG / IE');
            if ($.fn.mask) {
                $cpfCnpj.mask('00.000.000/0000-00', { reverse: true });
            }
            showLookupFeedback('Ao informar um CNPJ valido, o sistema tenta preencher automaticamente razao social, contatos e endereco.', 'muted');
        } else {
            $labelCpfCnpj.text('CPF');
            $labelRgIe.text('RG');
            if ($.fn.mask) {
                $cpfCnpj.mask('000.000.000-00', { reverse: true });
            }
            clearLookupFeedback();
            resolvedCnpj = '';
        }

        $cpfCnpj.val(currentValue);
    }

    function applyLookupData(payload) {
        fillField('#nome_razao', payload.razao_social || payload.nome_fantasia);
        fillField('#email', payload.email);
        fillField('#telefone1', payload.telefone1, formatTelefone);
        fillField('#telefone2', payload.telefone2, formatTelefone);
        fillField('#cep', payload.cep, formatCep);
        fillField('#endereco', payload.endereco);
        fillField('#numero', payload.numero);
        fillField('#complemento', payload.complemento);
        fillField('#bairro', payload.bairro);
        fillField('#cidade', payload.cidade);
        fillField('#uf', payload.uf);

        if (payload.situacao_cadastral && payload.situacao_cadastral.toUpperCase() !== 'ATIVA') {
            showLookupFeedback('CNPJ localizado. Situacao cadastral atual: ' + payload.situacao_cadastral + '.', 'warning');
        } else {
            showLookupFeedback('CNPJ localizado. Dados principais preenchidos automaticamente.', 'success');
        }

        focusNextField();
    }

    function lookupCnpj() {
        const tipo = String($tipoPessoa.val() || 'fisica');
        const cnpjDigits = normalizeDigits($cpfCnpj.val());

        if (
            tipo !== 'juridica'
            || cnpjDigits.length !== 14
            || lookupInFlight
            || resolvedCnpj === cnpjDigits
            || !lookupUrl
            || shouldThrottleFailedLookup(cnpjDigits)
        ) {
            return;
        }

        resetLookupTimer();
        setLookupLoading(true);

        $.ajax({
            url: lookupUrl,
            method: 'GET',
            dataType: 'json',
            data: { cnpj: cnpjDigits }
        }).done(function (response) {
            setLookupLoading(false);

            if (!response || response.success !== true || !response.data) {
                resolvedCnpj = '';
                rememberLookupAttempt(cnpjDigits, false);
                notifyLookupIssue('Consulta de CNPJ', response && response.message ? response.message : 'Não foi possível consultar este CNPJ agora.', 'warning');
                return;
            }

            resolvedCnpj = cnpjDigits;
            rememberLookupAttempt(cnpjDigits, true);
            applyLookupData(response.data);
        }).fail(function (xhr) {
            setLookupLoading(false);
            resolvedCnpj = '';
            rememberLookupAttempt(cnpjDigits, false);

            const response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
            const message = response && response.message
                ? response.message
                : 'Não foi possível consultar o CNPJ agora. Voce pode continuar o preenchimento manualmente.';

            console.error('[Clientes] Falha ao consultar CNPJ.', {
                status: xhr ? xhr.status : null,
                response: response
            });

            notifyLookupIssue('Consulta de CNPJ', message, xhr && xhr.status >= 500 ? 'error' : 'warning');
        });
    }

    $tipoPessoa.on('change', function () {
        applyPessoaMask();
        lookupCnpj();
    });

    $cpfCnpj.on('input', function () {
        const cnpjDigits = normalizeDigits($cpfCnpj.val());
        if (cnpjDigits.length < 14) {
            resolvedCnpj = '';
            resetLookupTimer();
            if (String($tipoPessoa.val() || '') === 'juridica') {
                showLookupFeedback('Ao informar um CNPJ valido, o sistema tenta preencher automaticamente razao social, contatos e endereco.', 'muted');
            }
            return;
        }

        resetLookupTimer();
        lookupTimer = window.setTimeout(function () {
            lookupCnpj();
        }, LOOKUP_DEBOUNCE_MS);
    });

    $cpfCnpj.on('blur', function () {
        resetLookupTimer();
        lookupCnpj();
    });

    applyPessoaMask();
});
</script>
<?= $this->endSection() ?>
