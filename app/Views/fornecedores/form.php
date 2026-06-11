<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><?= $title ?></h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('fornecedores')" title="Ajuda sobre Fornecedores">
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
        <form
            id="fornecedorForm"
            action="<?= base_url(isset($fornecedor) ? 'fornecedores/atualizar/' . $fornecedor['id'] : 'fornecedores/salvar') ?>"
            method="POST"
            data-cnpj-lookup-url="<?= base_url('fornecedores/consultar-cnpj') ?>"
        >
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label text-muted">Tipo de Pessoa <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_pessoa" id="tipo_pessoa" required>
                        <option value="juridica" <?= old('tipo_pessoa', $fornecedor['tipo_pessoa'] ?? 'juridica') == 'juridica' ? 'selected' : '' ?>>Juridica (CNPJ)</option>
                        <option value="fisica" <?= old('tipo_pessoa', $fornecedor['tipo_pessoa'] ?? '') == 'fisica' ? 'selected' : '' ?>>Fisica (CPF)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted" id="label_cpf_cnpj">CNPJ</label>
                    <div class="position-relative">
                        <input
                            type="text"
                            class="form-control"
                            name="cnpj_cpf"
                            id="cnpj_cpf"
                            value="<?= esc(old('cnpj_cpf', $fornecedor['cnpj_cpf'] ?? '')) ?>"
                            autocomplete="off"
                        >
                    </div>
                    <div id="cnpjLookupFeedback" class="form-text d-none"></div>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label text-muted" id="label_rg_ie">Inscricao Estadual</label>
                    <input type="text" class="form-control" name="ie_rg" id="ie_rg" value="<?= esc(old('ie_rg', $fornecedor['ie_rg'] ?? '')) ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Nome Fantasia / Apelido <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nome_fantasia" id="nome_fantasia" value="<?= esc(old('nome_fantasia', $fornecedor['nome_fantasia'] ?? '')) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Razao Social / Nome Completo</label>
                    <input type="text" class="form-control" name="razao_social" id="razao_social" value="<?= esc(old('razao_social', $fornecedor['razao_social'] ?? '')) ?>">
                </div>

                <h5 class="mt-4 mb-3 border-bottom pb-2">Contato</h5>

                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Email</label>
                    <input type="email" class="form-control" name="email" id="email" value="<?= esc(old('email', $fornecedor['email'] ?? '')) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Telefone 1 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control telefone" name="telefone1" id="telefone1" value="<?= esc(old('telefone1', $fornecedor['telefone1'] ?? '')) ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Telefone 2</label>
                    <input type="text" class="form-control telefone" name="telefone2" id="telefone2" value="<?= esc(old('telefone2', $fornecedor['telefone2'] ?? '')) ?>">
                </div>

                <h5 class="mt-4 mb-3 border-bottom pb-2">Endereco</h5>

                <div class="col-md-3 mb-3">
                    <label class="form-label text-muted">CEP</label>
                    <input type="text" class="form-control mask-cep" name="cep" id="cep" value="<?= esc(old('cep', $fornecedor['cep'] ?? '')) ?>">
                </div>
                <div class="col-md-7 mb-3">
                    <label class="form-label text-muted">Endereco (Rua/Av)</label>
                    <input type="text" class="form-control js-logradouro" name="endereco" id="endereco" value="<?= esc(old('endereco', $fornecedor['endereco'] ?? '')) ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label text-muted">Numero</label>
                    <input type="text" class="form-control js-numero" name="numero" id="numero" value="<?= esc(old('numero', $fornecedor['numero'] ?? '')) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label text-muted">Complemento</label>
                    <input type="text" class="form-control" name="complemento" id="complemento" value="<?= esc(old('complemento', $fornecedor['complemento'] ?? '')) ?>">
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

                <h5 class="mt-4 mb-3 border-bottom pb-2">Informacoes Adicionais</h5>

                <div class="col-12 mb-3">
                    <label class="form-label text-muted">Observacoes</label>
                    <textarea class="form-control" name="observacoes" rows="3"><?= esc(old('observacoes', $fornecedor['observacoes'] ?? '')) ?></textarea>
                </div>

                <div class="col-12 mb-4">
                    <div class="form-check form-switch">
                        <input type="hidden" name="ativo" value="0">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" <?= old('ativo', $fornecedor['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label text-muted" for="ativo">Fornecedor Ativo no Sistema</label>
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
$(function () {
    const $form = $('#fornecedorForm');
    const $tipoPessoa = $('#tipo_pessoa');
    const $cpfCnpj = $('#cnpj_cpf');
    const $labelCpfCnpj = $('#label_cpf_cnpj');
    const $labelRgIe = $('#label_rg_ie');
    const $feedback = $('#cnpjLookupFeedback');
    const lookupUrl = String($form.data('cnpjLookupUrl') || '');
    const feedbackApi = window.DSFeedback && typeof window.DSFeedback.fire === 'function'
        ? window.DSFeedback
        : (window.Swal && typeof window.Swal.fire === 'function' ? window.Swal : null);
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
        if (feedbackApi) {
            feedbackApi.fire({
                icon: icon || 'warning',
                title: title,
                text: text,
                confirmButtonText: 'Entendi',
            });
            return;
        }

        console.error('[Fornecedores] Alerta de consulta de CNPJ sem feedback visual disponivel.', {
            title: title,
            text: text,
            icon: icon
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
        const tipo = String($tipoPessoa.val() || 'juridica');
        const currentValue = $cpfCnpj.val();

        if ($.fn.unmask) {
            $cpfCnpj.unmask();
        }

        if (tipo === 'juridica') {
            $labelCpfCnpj.text('CNPJ');
            $labelRgIe.text('Inscricao Estadual');
            if ($.fn.mask) {
                $cpfCnpj.mask('00.000.000/0000-00', { reverse: true });
            }
            showLookupFeedback('Ao informar um CNPJ valido, o sistema tenta preencher automaticamente razao social, nome fantasia, inscricao estadual, contatos e endereco.', 'muted');
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
        fillField('#ie_rg', payload.ie_rg);
        fillField('#nome_fantasia', payload.nome_fantasia || payload.razao_social);
        fillField('#razao_social', payload.razao_social);
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

        if (payload.situacao_cadastral && String(payload.situacao_cadastral).toUpperCase() !== 'ATIVA') {
            showLookupFeedback('CNPJ localizado. Situacao cadastral atual: ' + payload.situacao_cadastral + '.', 'warning');
        } else {
            showLookupFeedback('CNPJ localizado. Dados principais do fornecedor preenchidos automaticamente.', 'success');
        }

        focusNextField();
    }

    function lookupCnpj() {
        const tipo = String($tipoPessoa.val() || 'juridica');
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
                notifyLookupIssue('Consulta de CNPJ', response && response.message ? response.message : 'Nao foi possivel consultar este CNPJ agora.', 'warning');
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
                : 'Nao foi possivel consultar o CNPJ agora. Voce pode continuar o preenchimento manualmente.';

            console.error('[Fornecedores] Falha ao consultar CNPJ.', {
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
                showLookupFeedback('Ao informar um CNPJ valido, o sistema tenta preencher automaticamente razao social, nome fantasia, inscricao estadual, contatos e endereco.', 'muted');
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
