<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isEdit = !empty($isEdit);
$isCreate = !$isEdit;
$pacote = is_array($pacote ?? null) ? $pacote : [];
$niveis = is_array($niveis ?? null) ? $niveis : [];
$tiposEquipamento = array_values(array_filter((array) ($tiposEquipamento ?? []), static fn ($item): bool => trim((string) $item) !== ''));
$servicos = (array) ($servicos ?? []);

$categorias = [
    'geral' => 'Geral',
    'computadores' => 'Computadores',
    'celulares' => 'Celulares',
    'tablets' => 'Tablets',
    'impressoras' => 'Impressoras',
    'outros' => 'Outros',
];

$nivelLabels = [
    'basico' => 'Basico',
    'completo' => 'Completo (Mais pedido)',
    'premium' => 'Premium',
];

$nivelFallbackColors = [
    'basico' => '#6B7280',
    'completo' => '#D4AF37',
    'premium' => '#7C3AED',
];

$formValue = static function (string $field, $default = '') use ($pacote) {
    return old($field, $pacote[$field] ?? $default);
};

$nivelValue = static function (string $nivel, string $field, $default = '') use ($niveis) {
    $source = (array) ($niveis[$nivel] ?? []);
    $oldMap = (array) old('nivel_' . $field, []);
    $oldValue = $oldMap[$nivel] ?? null;
    if ($oldValue !== null && $oldValue !== '') {
        return $oldValue;
    }

    if (array_key_exists($field, $source)) {
        return $source[$field];
    }

    return $default;
};

$isChecked = static function (string $nivel, string $field, int $default = 0) use ($nivelValue): bool {
    $value = $nivelValue($nivel, $field, $default);
    return (int) $value === 1;
};
?>

<div class="page-header mb-4 d-flex justify-content-between align-items-center">
    <h2 class="mb-0">
        <i class="bi bi-box-seam me-2"></i>
        <?= $isEdit ? 'Editar Pacote de Servicos' : 'Novo Pacote de Servicos' ?>
    </h2>
    <div class="d-flex gap-2">
        <?php if ($isCreate): ?>
        <button type="button" id="btnAplicarExemploPacote" class="btn btn-sm btn-outline-primary rounded-pill">
            <i class="bi bi-magic me-1"></i>Preencher exemplo
        </button>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('pacotes-servicos')" title="Ajuda sobre Pacotes de Servicos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<form action="<?= esc((string) ($actionUrl ?? base_url('pacotes-servicos/salvar'))) ?>" method="POST">
    <?= csrf_field() ?>

    <div class="card glass-card mb-4">
        <div class="card-header bg-transparent border-0 pb-0">
            <h5 class="mb-0">Dados do Pacote</h5>
            <small class="text-muted">Configure o pacote principal e a referencia metodologica.</small>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Nome do pacote <span class="text-danger">*</span></label>
                    <input type="text" name="nome" class="form-control" required value="<?= esc((string) $formValue('nome')) ?>" placeholder="Ex: Troca de Tela (Celular)">
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Categoria</label>
                    <select name="categoria" class="form-select">
                        <?php $categoriaAtual = (string) $formValue('categoria', 'geral'); ?>
                        <?php foreach ($categorias as $value => $label): ?>
                        <option value="<?= esc($value) ?>" <?= $categoriaAtual === $value ? 'selected' : '' ?>>
                            <?= esc($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Tipo de equipamento</label>
                    <input
                        type="text"
                        name="tipo_equipamento"
                        class="form-control"
                        list="tiposEquipamentoPacote"
                        value="<?= esc((string) $formValue('tipo_equipamento')) ?>"
                        placeholder="Ex: Smartphone, Notebook"
                    >
                    <datalist id="tiposEquipamentoPacote">
                        <?php foreach ($tiposEquipamento as $tipoEquip): ?>
                        <option value="<?= esc((string) $tipoEquip) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="col-lg-5">
                    <label class="form-label">Servico de referencia (opcional)</label>
                    <?php $servicoAtual = (int) $formValue('servico_referencia_id', 0); ?>
                    <select name="servico_referencia_id" class="form-select">
                        <option value="0">Nao vincular</option>
                        <?php foreach ($servicos as $servico): ?>
                        <?php $servicoId = (int) ($servico['id'] ?? 0); ?>
                        <option value="<?= $servicoId ?>" <?= $servicoAtual === $servicoId ? 'selected' : '' ?>>
                            <?= esc((string) ($servico['nome'] ?? ('Servico #' . $servicoId))) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Ordem</label>
                    <input type="number" min="0" step="1" name="ordem_apresentacao" class="form-control" value="<?= esc((string) $formValue('ordem_apresentacao', 0)) ?>">
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Status</label>
                    <?php $ativoAtual = ((string) $formValue('ativo', '1')) === '1'; ?>
                    <select name="ativo" class="form-select">
                        <option value="1" <?= $ativoAtual ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= !$ativoAtual ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Metodologia de origem</label>
                    <input type="text" name="metodologia_origem" class="form-control" value="<?= esc((string) $formValue('metodologia_origem', 'Passo 05 - 3 Pacotes')) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Descricao do pacote</label>
                    <textarea name="descricao" class="form-control" rows="3" placeholder="Resumo operacional do pacote e sua aplicacao na venda consultiva."><?= esc((string) $formValue('descricao')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card glass-card mb-4">
        <div class="card-header bg-transparent border-0 pb-0">
            <h5 class="mb-0">Niveis do Pacote (Basico, Completo e Premium)</h5>
            <small class="text-muted">Preencha os 3 niveis com faixa de preco, garantia, prazo e argumento de venda.</small>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <div class="fw-semibold mb-1">Como preencher os niveis</div>
                <div class="small">
                    1) Defina faixa de preco (`Min`, `Recomendado`, `Max`) para cada nivel.<br>
                    2) Use o `Completo` como opcao mais equilibrada (mais recomendada).<br>
                    3) Descreva `Itens inclusos` com um item por linha.<br>
                    4) Em qualquer campo, passe o mouse no icone <i class="bi bi-info-circle text-info"></i> para ver orientacoes.
                </div>
            </div>
            <div class="row g-3">
                <?php foreach (['basico', 'completo', 'premium'] as $nivel): ?>
                <div class="col-12 col-xl-4">
                    <div class="card border h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <strong><?= esc($nivelLabels[$nivel]) ?></strong>
                            <span class="badge <?= $nivel === 'completo' ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                <?= strtoupper(esc($nivel)) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label">
                                    Nome de exibicao
                                    <i class="bi bi-info-circle text-info ms-1" data-bs-toggle="tooltip" title="Nome curto que aparece para o cliente na apresentacao do nivel."></i>
                                </label>
                                <input
                                    type="text"
                                    name="nivel_nome_exibicao[<?= $nivel ?>]"
                                    class="form-control"
                                    data-bs-toggle="tooltip"
                                    title="Exemplo: Basico, Completo, Premium ou nomes customizados."
                                    value="<?= esc((string) $nivelValue($nivel, 'nome_exibicao', ucfirst($nivel))) ?>"
                                >
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-7">
                                    <label class="form-label">
                                        Cor HEX
                                        <i class="bi bi-info-circle text-info ms-1" data-bs-toggle="tooltip" title="Cor visual do nivel. Use formato #RRGGBB."></i>
                                    </label>
                                    <input
                                        type="text"
                                        maxlength="7"
                                        name="nivel_cor_hex[<?= $nivel ?>]"
                                        class="form-control js-nivel-color-text"
                                        data-target="#nivel_color_picker_<?= $nivel ?>"
                                        data-bs-toggle="tooltip"
                                        title="Exemplo: #D4AF37."
                                        value="<?= esc((string) $nivelValue($nivel, 'cor_hex', $nivelFallbackColors[$nivel])) ?>"
                                    >
                                </div>
                                <div class="col-5">
                                    <label class="form-label">
                                        Preview
                                        <i class="bi bi-info-circle text-info ms-1" data-bs-toggle="tooltip" title="Seleciona visualmente a cor e atualiza o campo HEX."></i>
                                    </label>
                                    <input
                                        type="color"
                                        id="nivel_color_picker_<?= $nivel ?>"
                                        class="form-control form-control-color w-100 js-nivel-color-picker"
                                        data-target="[name='nivel_cor_hex[<?= $nivel ?>]']"
                                        data-bs-toggle="tooltip"
                                        title="Clique para escolher a cor visual do nivel."
                                        value="<?= esc((string) $nivelValue($nivel, 'cor_hex', $nivelFallbackColors[$nivel])) ?>"
                                    >
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-4">
                                    <label class="form-label">
                                        Min
                                        <i class="bi bi-info-circle text-info ms-1" data-bs-toggle="tooltip" title="Menor preco permitido para este nivel."></i>
                                    </label>
                                    <input type="number" step="0.01" min="0" name="nivel_preco_min[<?= $nivel ?>]" class="form-control" data-bs-toggle="tooltip" title="Informe o piso de preco." value="<?= esc((string) $nivelValue($nivel, 'preco_min', 0)) ?>">
                                </div>
                                <div class="col-4">
                                    <label class="form-label">
                                        Recomendado
                                        <i class="bi bi-info-circle text-info ms-1" data-bs-toggle="tooltip" title="Preco de venda sugerido (ancora principal do nivel)."></i>
                                    </label>
                                    <input type="number" step="0.01" min="0" name="nivel_preco_recomendado[<?= $nivel ?>]" class="form-control" data-bs-toggle="tooltip" title="Informe o preco sugerido para o tecnico usar na proposta." value="<?= esc((string) $nivelValue($nivel, 'preco_recomendado', 0)) ?>">
                                </div>
                                <div class="col-4">
                                    <label class="form-label">
                                        Max
                                        <i class="bi bi-info-circle text-info ms-1" data-bs-toggle="tooltip" title="Maior preco permitido para este nivel."></i>
                                    </label>
                                    <input type="number" step="0.01" min="0" name="nivel_preco_max[<?= $nivel ?>]" class="form-control" data-bs-toggle="tooltip" title="Informe o teto de preco." value="<?= esc((string) $nivelValue($nivel, 'preco_max', 0)) ?>">
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-7">
                                    <label class="form-label">
                                        Prazo estimado
                                        <i class="bi bi-info-circle text-info ms-1" data-bs-toggle="tooltip" title="Tempo medio de conclusao para este nivel."></i>
                                    </label>
                                    <input type="text" name="nivel_prazo_estimado[<?= $nivel ?>]" class="form-control" data-bs-toggle="tooltip" title="Exemplo: ate 24h, 1 a 2h, prioritario." value="<?= esc((string) $nivelValue($nivel, 'prazo_estimado')) ?>" placeholder="Ex: ate 24h">
                                </div>
                                <div class="col-5">
                                    <label class="form-label">
                                        Garantia (dias)
                                        <i class="bi bi-info-circle text-info ms-1" data-bs-toggle="tooltip" title="Prazo de garantia prometido para o nivel."></i>
                                    </label>
                                    <input type="number" min="0" step="1" name="nivel_garantia_dias[<?= $nivel ?>]" class="form-control" data-bs-toggle="tooltip" title="Exemplo: 15, 30, 60 ou 90 dias." value="<?= esc((string) $nivelValue($nivel, 'garantia_dias', 0)) ?>">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">
                                    Itens inclusos
                                    <i class="bi bi-info-circle text-info ms-1" data-bs-toggle="tooltip" title="Liste exatamente o que sera entregue nesse nivel, um item por linha."></i>
                                </label>
                                <textarea name="nivel_itens_inclusos[<?= $nivel ?>]" class="form-control" rows="4" data-bs-toggle="tooltip" title="Separe por linha para facilitar leitura comercial." placeholder="Um item por linha."><?= esc((string) $nivelValue($nivel, 'itens_inclusos')) ?></textarea>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">
                                    Argumento de venda
                                    <i class="bi bi-info-circle text-info ms-1" data-bs-toggle="tooltip" title="Frase curta para o atendente justificar o valor e converter o cliente."></i>
                                </label>
                                <textarea name="nivel_argumento_venda[<?= $nivel ?>]" class="form-control" rows="3" data-bs-toggle="tooltip" title="Exemplo: Melhor custo-beneficio para resolver e prevenir retorno." placeholder="Como defender este nivel para o cliente."><?= esc((string) $nivelValue($nivel, 'argumento_venda')) ?></textarea>
                            </div>

                            <div class="d-flex gap-3 mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="nivel_destaque_<?= $nivel ?>" name="nivel_destaque[<?= $nivel ?>]" value="1" data-bs-toggle="tooltip" title="Marque o nivel que deve receber maior foco comercial." <?= $isChecked($nivel, 'destaque', $nivel === 'completo' ? 1 : 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="nivel_destaque_<?= $nivel ?>">Destaque</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="nivel_ativo_<?= $nivel ?>" name="nivel_ativo[<?= $nivel ?>]" value="1" data-bs-toggle="tooltip" title="Desmarque para ocultar este nivel sem excluir o pacote." <?= $isChecked($nivel, 'ativo', 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="nivel_ativo_<?= $nivel ?>">Ativo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-5">
        <a href="<?= base_url('pacotes-servicos') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('pacotes-servicos') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
        <button type="submit" class="btn btn-primary btn-glow px-4">
            <i class="bi bi-save me-1"></i><?= $isEdit ? 'Salvar alteracoes' : 'Salvar pacote' ?>
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function') {
        document.querySelectorAll('[data-bs-toggle=\"tooltip\"]').forEach(function (el) {
            new window.bootstrap.Tooltip(el);
        });
    }

    document.querySelectorAll('.js-nivel-color-text').forEach(function (input) {
        input.addEventListener('input', function () {
            const targetSelector = input.dataset.target || '';
            const target = targetSelector ? document.querySelector(targetSelector) : null;
            if (!target) {
                return;
            }

            let value = String(input.value || '').trim();
            if (value.length === 6 && value.charAt(0) !== '#') {
                value = '#' + value;
            }
            if (/^#[0-9a-fA-F]{6}$/.test(value)) {
                target.value = value;
            }
        });
    });

    document.querySelectorAll('.js-nivel-color-picker').forEach(function (picker) {
        picker.addEventListener('input', function () {
            const targetSelector = picker.dataset.target || '';
            const target = targetSelector ? document.querySelector(targetSelector) : null;
            if (target) {
                target.value = picker.value || '';
            }
        });
    });

    const btnExemplo = document.getElementById('btnAplicarExemploPacote');
    if (btnExemplo) {
        btnExemplo.addEventListener('click', function () {
            const setField = function (selector, value) {
                const field = document.querySelector(selector);
                if (!field) {
                    return;
                }

                if (field.type === 'checkbox') {
                    field.checked = Boolean(value);
                } else {
                    field.value = value;
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };

            setField('[name=\"nome\"]', 'Troca de Tela - Pacote de Conversao');
            setField('[name=\"categoria\"]', 'celulares');
            setField('[name=\"tipo_equipamento\"]', 'Smartphone');
            setField('[name=\"servico_referencia_id\"]', '0');
            setField('[name=\"ordem_apresentacao\"]', '50');
            setField('[name=\"ativo\"]', '1');
            setField('[name=\"metodologia_origem\"]', 'Passo 05 - 3 Pacotes');
            setField('[name=\"descricao\"]', 'Exemplo pronto para troca de tela com ancoragem comercial Basico/Completo/Premium.');

            setField('[name=\"nivel_nome_exibicao[basico]\"]', 'Basico');
            setField('[name=\"nivel_cor_hex[basico]\"]', '#6B7280');
            setField('#nivel_color_picker_basico', '#6b7280');
            setField('[name=\"nivel_preco_min[basico]\"]', '120');
            setField('[name=\"nivel_preco_recomendado[basico]\"]', '220');
            setField('[name=\"nivel_preco_max[basico]\"]', '320');
            setField('[name=\"nivel_prazo_estimado[basico]\"]', '1h a 2h');
            setField('[name=\"nivel_garantia_dias[basico]\"]', '15');
            setField('[name=\"nivel_itens_inclusos[basico]\"]', "Troca simples da tela\nTeste de toque\nTeste de camera frontal e sensores\nLimpeza externa");
            setField('[name=\"nivel_argumento_venda[basico]\"]', 'Opcao funcional para resolver rapido com menor investimento inicial.');
            setField('[name=\"nivel_destaque[basico]\"]', false);
            setField('[name=\"nivel_ativo[basico]\"]', true);

            setField('[name=\"nivel_nome_exibicao[completo]\"]', 'Completo');
            setField('[name=\"nivel_cor_hex[completo]\"]', '#D4AF37');
            setField('#nivel_color_picker_completo', '#d4af37');
            setField('[name=\"nivel_preco_min[completo]\"]', '170');
            setField('[name=\"nivel_preco_recomendado[completo]\"]', '300');
            setField('[name=\"nivel_preco_max[completo]\"]', '400');
            setField('[name=\"nivel_prazo_estimado[completo]\"]', '1h a 2h');
            setField('[name=\"nivel_garantia_dias[completo]\"]', '30');
            setField('[name=\"nivel_itens_inclusos[completo]\"]', "Tudo do Basico\nLimpeza interna\nVerificacao de bateria\nRevisao de conectores\nTeste completo de audio/camera/sensores");
            setField('[name=\"nivel_argumento_venda[completo]\"]', 'Melhor custo-beneficio para resolver e revisar o aparelho completo.');
            setField('[name=\"nivel_destaque[completo]\"]', true);
            setField('[name=\"nivel_ativo[completo]\"]', true);

            setField('[name=\"nivel_nome_exibicao[premium]\"]', 'Premium');
            setField('[name=\"nivel_cor_hex[premium]\"]', '#7C3AED');
            setField('#nivel_color_picker_premium', '#7c3aed');
            setField('[name=\"nivel_preco_min[premium]\"]', '240');
            setField('[name=\"nivel_preco_recomendado[premium]\"]', '380');
            setField('[name=\"nivel_preco_max[premium]\"]', '490');
            setField('[name=\"nivel_prazo_estimado[premium]\"]', 'prioritario');
            setField('[name=\"nivel_garantia_dias[premium]\"]', '60');
            setField('[name=\"nivel_itens_inclusos[premium]\"]', "Tudo do Completo\nPelicula instalada\nDiagnostico completo\nRelatorio fotografico antes/depois\nEntrega prioritaria");
            setField('[name=\"nivel_argumento_venda[premium]\"]', 'Para quem quer maxima tranquilidade e atendimento prioritario.');
            setField('[name=\"nivel_destaque[premium]\"]', false);
            setField('[name=\"nivel_ativo[premium]\"]', true);

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Exemplo preenchido',
                    text: 'Os campos dos 3 niveis foram preenchidos com um pacote de exemplo.',
                    confirmButtonText: 'OK'
                });
            }
        });
    }
});
</script>

<?= $this->endSection() ?>
