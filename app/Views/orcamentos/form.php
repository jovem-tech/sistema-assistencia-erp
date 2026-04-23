<?= $this->extend($layout ?? 'layouts/main') ?>

<?= $this->section('content') ?>
<?php
$isEdit = !empty($isEdit);
$orcamento = $orcamento ?? [];
$itens = $itens ?? [];
$statusLabels = $statusLabels ?? [];
$tipoLabels = $tipoLabels ?? [];
$clientes = $clientes ?? [];
$clienteLookupInitial = $clienteLookupInitial ?? [];
$vinculosContext = $vinculosContext ?? ['mostrar' => false, 'origem' => 'manual', 'os' => null, 'equipamento' => null, 'conversa' => null];
$equipamentoCatalog = $equipamentoCatalog ?? ['tipos' => [], 'marcasAll' => [], 'marcasByTipo' => [], 'modelosByMarca' => [], 'modelosByTipoMarca' => []];
$equipamentoManual = $equipamentoManual ?? ['tipo_id' => null, 'marca_id' => null, 'modelo_id' => null, 'cor' => '', 'cor_hex' => '', 'cor_rgb' => ''];
$equipamentoLookupInitial = $equipamentoLookupInitial ?? [];
$isEmbedded = !empty($isEmbedded);
$pacoteOfertaModuleReady = !empty($pacoteOfertaModuleReady);
$pacotesAtivosOferta = $pacotesAtivosOferta ?? [];
$equipamentoFotoFallback = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHdpZHRoPSc4MCcgaGVpZ2h0PSc4MCcgdmlld0JveD0nMCAwIDgwIDgwJz48cmVjdCB3aWR0aD0nODAnIGhlaWdodD0nODAnIHJ4PSc0MCcgZmlsbD0nI2VlZjJmZicvPjxjaXJjbGUgY3g9JzQwJyBjeT0nMzAnIHI9JzEyJyBmaWxsPScjYzdkMmZlJy8+PHRleHQgeD0nNDAnIHk9JzU4JyB0ZXh0LWFuY2hvcj0nbWlkZGxlJyBmb250LXNpemU9JzEwJyBmaWxsPScjNjQ3NDhiJz5zZW0gZm90bzwvdGV4dD48L3N2Zz4=';
$osNumeroVinculoInicial = trim((string) ($vinculosContext['os']['numero'] ?? ''));
$osTituloVinculoInicial = $osNumeroVinculoInicial !== ''
    ? (stripos($osNumeroVinculoInicial, 'OS') === 0 ? $osNumeroVinculoInicial : ('OS ' . $osNumeroVinculoInicial))
    : 'Nenhuma OS vinculada';
$osStatusVinculoInicial = trim((string) ($vinculosContext['os']['status'] ?? ''));
$equipamentoVinculoTituloInicial = trim((string) ($vinculosContext['equipamento']['tipo'] ?? ''));
$equipamentoVinculoDescricaoInicial = trim((string) (($vinculosContext['equipamento']['marca'] ?? '') . ' ' . ($vinculosContext['equipamento']['modelo'] ?? '')));
$equipamentoTituloHintInicial = trim(implode(' ', array_filter([$equipamentoVinculoTituloInicial, $equipamentoVinculoDescricaoInicial])));
$equipamentoFotoVinculoInicial = trim((string) ($vinculosContext['equipamento']['foto_url'] ?? ''));
$osTemEquipamentoVinculadoInicial = !empty($vinculosContext['os']['tem_equipamento_vinculado']);
$isPacoteBased = in_array((string) ($orcamento['status'] ?? ''), ['aguardando_pacote', 'pacote_aprovado'], true);
$oldPacoteBased = old('orcamento_baseado_pacote');
if ($oldPacoteBased !== null) {
    $isPacoteBased = (string) $oldPacoteBased === '1';
}

if (empty($itens)) {
    $itens = [[
        'tipo_item' => 'servico',
        'descricao' => '',
        'quantidade' => 1,
        'valor_unitario' => 0,
        'desconto' => 0,
        'acrescimo' => 0,
        'total' => 0,
        'observacoes' => '',
    ]];
}
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="mb-0"><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= esc($title ?? html_entity_decode('Or&ccedil;amento', ENT_QUOTES, 'UTF-8')) ?></h2>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('orcamentos')" title="Ajuda sobre Or&ccedil;amentos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (!$isEmbedded): ?>
            <?php if (!empty($orcamento['id'])): ?>
                <a href="<?= base_url('orcamentos/visualizar/' . (int) $orcamento['id']) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Voltar
                </a>
            <?php else: ?>
                <a href="<?= base_url('orcamentos') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Voltar
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= esc($actionUrl ?? base_url('orcamentos/salvar')) ?>" method="POST" id="orcamentoForm">
            <?= csrf_field() ?>

            <input type="hidden" name="versao" value="<?= esc((string) ($orcamento['versao'] ?? 1)) ?>">
            <input type="hidden" name="os_id" id="orcamentoOsId" value="<?= esc((string) ($orcamento['os_id'] ?? '')) ?>">
            <input type="hidden" name="equipamento_id" id="orcamentoEquipamentoId" value="<?= esc((string) ($orcamento['equipamento_id'] ?? '')) ?>">
            <input type="hidden" name="conversa_id" id="orcamentoConversaId" value="<?= esc((string) ($orcamento['conversa_id'] ?? '')) ?>">
            <input type="hidden" name="pacote_oferta_id" id="orcamentoPacoteOfertaId" value="">
            <input type="hidden" name="aplicar_pacote_oferta" id="orcamentoAplicarPacoteOferta" value="0">
            <input type="hidden" id="orcamentoOsNumeroHint" value="<?= esc((string) ($vinculosContext['os']['numero'] ?? '')) ?>">
            <input type="hidden" id="orcamentoEquipamentoTituloHint" value="<?= esc($equipamentoTituloHintInicial) ?>">

            <div class="alert alert-info d-none orc-draft-alert" id="orcamentoDraftRecoverBar" role="alert">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-2">
                    <div class="small mb-0">
                        <i class="bi bi-clock-history me-1"></i>
                        Encontramos um rascunho salvo automaticamente para este or&ccedil;amento.
                        <span class="text-muted" id="orcamentoDraftSavedAt"></span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnOrcamentoDraftDiscard">Descartar</button>
                        <button type="button" class="btn btn-sm btn-info" id="btnOrcamentoDraftRestore">Restaurar</button>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3 orc-section-card">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="mb-0">Dados do Cliente</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Cliente cadastrado</label>
                            <select class="form-select" id="orcamentoClienteLookup">
                                <?php if (!empty($clienteLookupInitial)): ?>
                                    <option value="<?= esc((string) ($clienteLookupInitial['id'] ?? '')) ?>" selected><?= esc((string) ($clienteLookupInitial['text'] ?? '')) ?></option>
                                <?php endif; ?>
                            </select>
                            <input type="hidden" name="cliente_id" id="orcamentoClienteId" value="<?= esc((string) ($orcamento['cliente_id'] ?? '')) ?>">
                            <input type="hidden" name="contato_id" id="orcamentoContatoId" value="<?= esc((string) ($orcamento['contato_id'] ?? '')) ?>">
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Nome do cliente eventual</label>
                            <input type="text" class="form-control" id="orcamentoNomeAvulso" name="cliente_nome_avulso" value="<?= esc((string) ($orcamento['cliente_nome_avulso'] ?? '')) ?>" placeholder="Preencher apenas para cliente sem cadastro">
                            <div class="form-check mt-2 d-none" id="orcamentoRegistrarContatoWrap">
                                <input class="form-check-input" type="checkbox" value="1" id="orcamentoRegistrarContato" name="registrar_contato">
                                <label class="form-check-label" for="orcamentoRegistrarContato">
                                    Registrar como contato quando não houver cadastro existente
                                </label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Telefone de contato</label>
                            <input
                                type="text"
                                class="form-control"
                                id="orcamentoTelefone"
                                name="telefone_contato"
                                value="<?= esc((string) ($orcamento['telefone_contato'] ?? '')) ?>"
                                inputmode="numeric"
                                maxlength="15"
                                autocomplete="tel-national"
                                placeholder="(11) 98765-4321"
                                required
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Email de contato</label>
                            <input
                                type="email"
                                class="form-control"
                                id="orcamentoEmail"
                                name="email_contato"
                                value="<?= esc((string) ($orcamento['email_contato'] ?? '')) ?>"
                                autocomplete="email"
                                maxlength="160"
                                placeholder="cliente@dominio.com"
                            >
                            <small class="text-muted d-block mt-1">Opcional. Quando informado, este e-mail poder&aacute; ser utilizado para envio do or&ccedil;amento.</small>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $equipTipoSelecionado = (int) ($equipamentoManual['tipo_id'] ?? 0);
            $equipMarcaSelecionada = (int) ($equipamentoManual['marca_id'] ?? 0);
            $equipModeloSelecionado = (int) ($equipamentoManual['modelo_id'] ?? 0);
            $equipCorNome = trim((string) ($equipamentoManual['cor'] ?? ''));
            $equipCorHex = strtoupper(trim((string) ($equipamentoManual['cor_hex'] ?? '')));
            $equipCorRgb = trim((string) ($equipamentoManual['cor_rgb'] ?? ''));
            $equipCorPicker = preg_match('/^#[0-9A-F]{6}$/', $equipCorHex) ? $equipCorHex : '#FFFFFF';
            ?>

            <div class="card border-0 shadow-sm mb-3 orc-section-card">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="mb-0">Dados do Equipamento</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3<?= !empty($vinculosContext['mostrar']) ? '' : ' d-none' ?>" id="orcamentoVinculosVisual">
                        <div class="col-12 col-lg-4<?= !empty($vinculosContext['os']) ? '' : ' d-none' ?>" id="orcamentoVinculoOsCol">
                            <div class="card border-0 bg-light-subtle h-100 orc-vinculo-card">
                                <div class="card-body py-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                        <div>
                                            <small class="text-muted d-block mb-1">Vinculo OS</small>
                                            <div class="fw-semibold" id="orcamentoVinculoOsTitulo"><?= esc($osTituloVinculoInicial) ?></div>
                                        </div>
                                        <span class="badge rounded-pill text-bg-light d-none" id="orcamentoVinculoOsCounter"></span>
                                    </div>
                                    <small class="text-muted d-block mt-1<?= $osStatusVinculoInicial !== '' ? '' : ' d-none' ?>" id="orcamentoVinculoOsStatus">
                                        <?= $osStatusVinculoInicial !== '' ? esc('Status: ' . $osStatusVinculoInicial) : '' ?>
                                    </small>
                                    <div class="mt-2 d-none" id="orcamentoOsLookupWrap">
                                        <label class="form-label small text-muted mb-1" for="orcamentoOsLookup">OS abertas deste cliente</label>
                                        <select class="form-select form-select-sm" id="orcamentoOsLookup">
                                            <option value="">Selecione uma OS aberta...</option>
                                        </select>
                                    </div>
                                    <small class="text-muted d-block mt-2 d-none" id="orcamentoOsLookupHelp"></small>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4<?= !empty($vinculosContext['equipamento']) ? '' : ' d-none' ?>" id="orcamentoVinculoEquipamentoCol">
                            <div class="card border-0 bg-light-subtle h-100 orc-vinculo-card">
                                <div class="card-body py-3">
                                    <small class="text-muted d-block mb-1">Vinculo Equipamento</small>
                                    <div class="d-flex align-items-center gap-2">
                                        <img
                                            src="<?= esc($equipamentoFotoVinculoInicial !== '' ? $equipamentoFotoVinculoInicial : $equipamentoFotoFallback) ?>"
                                            alt="Foto do equipamento"
                                            class="rounded-circle border<?= !empty($vinculosContext['equipamento']) ? '' : ' d-none' ?>"
                                            id="orcamentoVinculoEquipamentoFoto"
                                            data-placeholder-src="<?= esc($equipamentoFotoFallback) ?>"
                                            width="40"
                                            height="40"
                                            style="object-fit:cover;"
                                            onerror="this.onerror=null;this.src=this.dataset.placeholderSrc || '<?= esc($equipamentoFotoFallback) ?>';"
                                        >
                                        <div>
                                            <div class="fw-semibold" id="orcamentoVinculoEquipamentoTitulo">
                                                <?= esc($equipamentoVinculoTituloInicial !== '' ? $equipamentoVinculoTituloInicial : 'Equipamento') ?>
                                            </div>
                                            <small class="text-muted" id="orcamentoVinculoEquipamentoDescricao">
                                                <?= esc($equipamentoVinculoDescricaoInicial) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4<?= !empty($vinculosContext['conversa']) ? '' : ' d-none' ?>" id="orcamentoVinculoConversaCol">
                            <div class="card border-0 bg-light-subtle h-100 orc-vinculo-card">
                                <div class="card-body py-3">
                                    <small class="text-muted d-block mb-1">Vinculo Conversa</small>
                                    <div class="fw-semibold">Conversa #<?= esc((string) ($vinculosContext['conversa']['id'] ?? '')) ?></div>
                                    <small class="text-muted">
                                        <?= esc(trim((string) (($vinculosContext['conversa']['nome'] ?? '') . ' ' . ($vinculosContext['conversa']['telefone'] ?? '')))) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-top<?= $osTemEquipamentoVinculadoInicial ? ' d-none' : '' ?>" id="orcamentoEquipamentoManualSection" data-os-has-linked-equip="<?= $osTemEquipamentoVinculadoInicial ? '1' : '0' ?>">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <h6 class="mb-0">Cadastro do equipamento para este or&ccedil;amento</h6>
                                <small class="text-muted">Preencha quando não houver equipamento vinculado automaticamente.</small>
                            </div>
                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-12 col-xl-9">
                                    <label class="form-label">Equipamentos ja cadastrados para este cliente</label>
                                    <select class="form-select" id="orcamentoEquipamentoLookup">
                                        <?php if (!empty($equipamentoLookupInitial)): ?>
                                            <option value="<?= esc((string) ($equipamentoLookupInitial['id'] ?? '')) ?>" selected>
                                                <?= esc((string) ($equipamentoLookupInitial['text'] ?? '')) ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                    <!-- <small class="text-muted d-block mt-1">Selecione um equipamento existente ou use o cadastro manual para novo equipamento.</small> -->
                                </div>
                                <div class="col-12 col-xl-3">
                                    <button type="button" class="btn btn-outline-primary w-100" id="btnOrcNovoEquipamentoManual">
                                        <i class="bi bi-plus-lg me-1"></i>Cadastrar novo equipamento
                                    </button>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-light border small py-2 px-3 mb-0" id="orcamentoEquipamentoLookupHint">
                                        Selecione um cliente cadastrado para listar os equipamentos vinculados.
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 d-none" id="orcamentoEquipamentoManualFields">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Tipo de equipamento</label>
                                    <select class="form-select" id="orcEquipTipo" name="equipamento_tipo_id">
                                        <option value="">Selecione o tipo...</option>
                                        <?php foreach (($equipamentoCatalog['tipos'] ?? []) as $tipo): ?>
                                            <?php $tipoId = (int) ($tipo['id'] ?? 0); ?>
                                            <option value="<?= $tipoId ?>" <?= $equipTipoSelecionado === $tipoId ? 'selected' : '' ?>>
                                                <?= esc((string) ($tipo['nome'] ?? ('Tipo #' . $tipoId))) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label d-flex justify-content-between align-items-center flex-wrap gap-1">
                                        <span>Marca</span>
                                        <span class="d-inline-flex gap-1 orc-inline-actions">
                                            <?php if (function_exists('can') && can('equipamentos', 'criar')): ?>
                                                <button type="button" class="btn btn-success btn-sm py-0 px-2" id="btnOrcNovaMarca">
                                                    <i class="bi bi-plus-lg"></i><span class="ms-1">Adicionar</span>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (function_exists('can') && can('equipamentos', 'editar')): ?>
                                                <button type="button" class="btn btn-outline-info btn-sm py-0 px-2" id="btnOrcEditarMarca">
                                                    <i class="bi bi-pencil"></i><span class="ms-1">Editar</span>
                                                </button>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                    <select class="form-select orc-equip-select2" id="orcEquipMarca" name="equipamento_marca_id" data-selected="<?= esc((string) $equipMarcaSelecionada) ?>">
                                        <option value="">Selecione o tipo primeiro...</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label d-flex justify-content-between align-items-center flex-wrap gap-1">
                                        <span>Modelo</span>
                                        <span class="d-inline-flex gap-1 orc-inline-actions">
                                            <?php if (function_exists('can') && can('equipamentos', 'criar')): ?>
                                                <button type="button" class="btn btn-success btn-sm py-0 px-2" id="btnOrcNovoModelo">
                                                    <i class="bi bi-plus-lg"></i><span class="ms-1">Adicionar</span>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (function_exists('can') && can('equipamentos', 'editar')): ?>
                                                <button type="button" class="btn btn-outline-info btn-sm py-0 px-2" id="btnOrcEditarModelo">
                                                    <i class="bi bi-pencil"></i><span class="ms-1">Editar</span>
                                                </button>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                    <select class="form-select orc-equip-select2" id="orcEquipModelo" name="equipamento_modelo_id" data-selected="<?= esc((string) $equipModeloSelecionado) ?>">
                                        <option value="">Selecione a marca primeiro...</option>
                                    </select>
                                </div>
                                <div class="col-12 col-lg-8">
                                    <label class="form-label">Cor</label>
                                    <input type="hidden" id="orcEquipCorHex" name="equipamento_cor_hex" value="<?= esc($equipCorHex) ?>">
                                    <input type="hidden" id="orcEquipCorRgb" name="equipamento_cor_rgb" value="<?= esc($equipCorRgb) ?>">
                                    <div class="row g-2">
                                        <div class="col-12 col-md-5">
                                            <div id="orcColorPreviewBox" class="orc-color-preview border rounded-3 d-flex flex-column align-items-center justify-content-center">
                                                <span id="orcColorPreviewHex" class="orc-color-preview-hex">---</span>
                                                <span id="orcColorPreviewName" class="orc-color-preview-name">Cor não selecionada</span>
                                            </div>
                                            <div class="input-group input-group-sm mt-2">
                                                <input type="color" class="form-control form-control-color p-1 flex-shrink-0" id="orcEquipCorPicker" value="<?= esc($equipCorPicker) ?>" title="Cor">
                                                <input type="text" class="form-control" id="orcEquipCorNome" name="equipamento_cor" value="<?= esc($equipCorNome) ?>" placeholder="Nome da cor">
                                            </div>
                                            <div class="d-flex flex-wrap gap-1 mt-2" id="orcCoresProximasGrid"></div>
                                        </div>
                                        <div class="col-12 col-md-7">
                                            <div id="orcColorCatalog" class="orc-color-catalog custom-scrollbar pe-1"></div>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-2">Padrao de cor alinhado com a abertura da OS (nome + HEX + RGB).</small>
                                </div>
                            </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 orc-section-card">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="mb-0">Dados Operacionais</h5>
                </div>
                <div class="card-body">
                    <?php
                    $origens = [
                        'manual' => 'Manual / Balcao',
                        'os' => 'Ordem de servico',
                        'conversa' => 'Central de mensagens',
                        'cliente' => 'Cadastro de cliente',
                    ];
                    $tipoAtual = (string) ($orcamento['tipo_orcamento'] ?? 'previo');
                    $prazoAtual = trim((string) ($orcamento['prazo_execucao'] ?? '3'));
                    $prazosExecucao = ['1', '3', '7', '15', '30'];
                    $tituloColClass = $isEdit ? 'col-12 col-lg-8' : 'col-12';
                    ?>
                    <div class="row g-3">
                        <?php if ($isEdit): ?>
                            <div class="col-12 col-lg-4">
                                <label class="form-label">Número</label>
                                <input type="text" class="form-control" value="<?= esc((string) ($orcamento['numero'] ?? 'Gerado ao salvar')) ?>" readonly>
                            </div>
                        <?php endif; ?>
                        <div class="<?= esc($tituloColClass) ?>">
                            <label class="form-label">Titulo</label>
                            <input type="text" class="form-control" id="orcamentoTitulo" name="titulo" value="<?= esc((string) ($orcamento['titulo'] ?? '')) ?>" placeholder="Ex.: Or&ccedil;amento para reparo de notebook">
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                <small class="text-muted fw-semibold mb-0">Titulos rapidos:</small>
                                <div class="orc-title-presets d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-titulo-template="Or&ccedil;amento para {{cliente}}">
                                        Or&ccedil;amento para cliente
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-titulo-template="Or&ccedil;amento OS {{os}} - {{cliente}}">
                                        Or&ccedil;amento com OS
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-titulo-template="Analise técnica - {{equipamento}}">
                                        Analise técnica
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-titulo-template="Troca de peca - {{equipamento}}">
                                        Troca de peca
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-titulo-template="Pacote de servicos para {{cliente}}">
                                        Pacote de servicos
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                <small class="text-muted fw-semibold mb-0">Inserir no titulo:</small>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-titulo-insert="cliente">+ Cliente</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-titulo-insert="os">+ OS</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-titulo-insert="equipamento">+ Equipamento</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Tipo do orcamento</label>
                            <select name="tipo_orcamento" class="form-select" id="orcamentoTipo">
                                <?php foreach ($tipoLabels as $tipoCode => $tipoLabel): ?>
                                    <option value="<?= esc($tipoCode) ?>" <?= $tipoAtual === $tipoCode ? 'selected' : '' ?>><?= esc($tipoLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted d-block mt-1" id="orcamentoTipoHelp">
                                <?= $tipoAtual === 'assistencia'
                                    ? 'Use quando o equipamento ja estiver na assistencia e vinculado a uma OS.'
                                    : 'Use para estimativa inicial quando o equipamento ainda não tiver entrado na assistencia.' ?>
                            </small>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" id="orcamentoStatus">
                                <?php foreach ($statusLabels as $statusCode => $statusName): ?>
                                    <?php $selected = ((string) ($orcamento['status'] ?? 'rascunho')) === $statusCode ? 'selected' : ''; ?>
                                    <option value="<?= esc($statusCode) ?>" <?= $selected ?>><?= esc($statusName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Origem</label>
                            <select name="origem" class="form-select">
                                <?php foreach ($origens as $originCode => $originLabel): ?>
                                    <option value="<?= esc($originCode) ?>" <?= ((string) ($orcamento['origem'] ?? 'manual')) === $originCode ? 'selected' : '' ?>><?= esc($originLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Validade (dias)</label>
                                    <input type="number" class="form-control" min="1" max="365" id="orcamentoValidadeDias" name="validade_dias" value="<?= esc((string) ($orcamento['validade_dias'] ?? 10)) ?>">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Validade (data)</label>
                                    <input type="date" class="form-control" id="orcamentoValidadeData" name="validade_data" value="<?= esc((string) ($orcamento['validade_data'] ?? '')) ?>" readonly>
                                    <small class="text-muted">Data calculada automaticamente pelos dias corridos da validade.</small>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Prazo de execucao</label>
                                    <select class="form-select" name="prazo_execucao" id="orcamentoPrazoExecucao">
                                        <?php foreach ($prazosExecucao as $prazoOpcao): ?>
                                            <?php $labelPrazo = $prazoOpcao === '1' ? '1 dia' : $prazoOpcao . ' dias'; ?>
                                            <option value="<?= esc($prazoOpcao) ?>" <?= $prazoAtual === $prazoOpcao ? 'selected' : '' ?>><?= esc($labelPrazo) ?></option>
                                        <?php endforeach; ?>
                                        <?php if ($prazoAtual !== '' && !in_array($prazoAtual, $prazosExecucao, true)): ?>
                                            <option value="<?= esc($prazoAtual) ?>" selected><?= esc($prazoAtual) ?> dias</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($pacoteOfertaModuleReady): ?>
                <div class="card border-0 shadow-sm mb-3 orc-section-card" id="orcPacoteOfertaCard">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">Oferta dinamica de pacote</h5>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnOrcPacoteOfertaRefresh">
                                <i class="bi bi-arrow-clockwise me-1"></i>Atualizar status
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-2">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="orcPacoteBaseadoSwitch"
                                        name="orcamento_baseado_pacote"
                                        value="1"
                                        <?= $isPacoteBased ? 'checked' : '' ?>
                                    >
                                    <label class="form-check-label" for="orcPacoteBaseadoSwitch">
                                        Orçamento baseado em pacote de serviço (enviar link ao salvar e aguardar escolha do cliente)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-lg-4">
                                <label class="form-label">Pacote para enviar</label>
                                <select class="form-select" id="orcPacoteOfertaPacoteId" name="pacote_oferta_pacote_id">
                                    <option value="">Selecione o pacote...</option>
                                    <?php foreach ($pacotesAtivosOferta as $pacoteOferta): ?>
                                        <?php $pacoteOfertaId = (int) ($pacoteOferta['id'] ?? 0); ?>
                                        <?php if ($pacoteOfertaId <= 0) continue; ?>
                                        <option value="<?= esc((string) $pacoteOfertaId) ?>">
                                            <?= esc(trim((string) ($pacoteOferta['nome'] ?? ('Pacote #' . $pacoteOfertaId)))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label class="form-label">Telefone WhatsApp</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="orcPacoteOfertaTelefone"
                                    name="pacote_oferta_telefone"
                                    value="<?= esc((string) ($orcamento['telefone_contato'] ?? '')) ?>"
                                    placeholder="(11) 98765-4321"
                                >
                            </div>
                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label">Validade do link</label>
                                <select class="form-select" id="orcPacoteOfertaExpiraDias" disabled>
                                    <option value="2" selected>48 horas</option>
                                </select>
                            </div>
                            <div class="col-12 col-lg-3 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="orcPacoteOfertaEnviarWhatsapp" name="pacote_oferta_enviar_whatsapp" checked>
                                    <label class="form-check-label" for="orcPacoteOfertaEnviarWhatsapp">
                                        Enviar automaticamente no WhatsApp
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Mensagem (opcional)</label>
                                <textarea class="form-control" id="orcPacoteOfertaMensagem" name="pacote_oferta_mensagem" rows="3" placeholder="Se vazio, o sistema monta uma mensagem pronta com link e niveis."></textarea>
                            </div>
                            <div class="col-12 d-none" id="orcPacoteOfertaMensagemLinkWrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="orcPacoteOfertaMensagemComLink" name="pacote_oferta_mensagem_com_link" checked>
                                    <label class="form-check-label" for="orcPacoteOfertaMensagemComLink">
                                        Incluir o link da oferta junto da mensagem personalizada
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary" id="btnEnviarPacoteOferta">
                                    <i class="bi bi-send me-1"></i>Enviar oferta
                                </button>
                                <small class="text-muted align-self-center">
                                    O sistema identifica cliente/contato/telefone com validacao contextual inteligente para detectar a oferta escolhida.
                                </small>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-light border mb-0 d-none" id="orcPacoteOfertaStatusWrap">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <div class="fw-semibold" id="orcPacoteOfertaStatusTitle">Nenhuma oferta identificada</div>
                                            <div class="small text-muted" id="orcPacoteOfertaStatusDesc"></div>
                                        </div>
                                        <span class="badge text-bg-secondary" id="orcPacoteOfertaStatusBadge">-</span>
                                    </div>
                                    <div class="small mt-2 text-muted" id="orcPacoteOfertaDetalhes"></div>
                                    <div class="form-check mt-2 d-none" id="orcPacoteOfertaApplyWrap">
                                        <input class="form-check-input" type="checkbox" id="orcPacoteOfertaApplyCheck" checked>
                                        <label class="form-check-label" for="orcPacoteOfertaApplyCheck">
                                            Aplicar automaticamente no orcamento ao salvar
                                        </label>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-2 d-none" id="orcPacoteOfertaLinkWrap">
                                        <a href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" id="orcPacoteOfertaLinkBtn">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Abrir pagina do cliente
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnOrcPacoteOfertaCopyLink">
                                            <i class="bi bi-clipboard me-1"></i>Copiar link
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-3 orc-section-card" id="orcSecaoItens">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">Itens do Or&ccedil;amento</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddItem">
                            <i class="bi bi-plus-lg me-1"></i>Adicionar item
                        </button>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive mb-0">
                        <table class="table table-hover align-middle mb-0" id="orcamentoItensTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 120px;">Tipo</th>
                                    <th style="min-width: 240px;">Descrição</th>
                                    <th style="min-width: 90px;">Qtd.</th>
                                    <th style="min-width: 120px;">Valor unit.</th>
                                    <th style="min-width: 120px;">Desconto</th>
                                    <th style="min-width: 120px;">Acrescimo</th>
                                    <th style="min-width: 120px;">Total</th>
                                    <th class="text-center">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="orcamentoItensBody">
                                <?php foreach ($itens as $item): ?>
                                    <tr class="orc-item-row">
                                        <td data-label="Tipo">
                                            <select name="item_tipo[]" class="form-select form-select-sm">
                                                <?php
                                                $itemTipo = (string) ($item['tipo_item'] ?? 'servico');
                                                $tiposItem = ['servico' => 'Serviço', 'peca' => 'Peca', 'combo' => 'Combo', 'avulso' => 'Avulso'];
                                                foreach ($tiposItem as $tipoItemCode => $tipoItemLabel):
                                                ?>
                                                    <option value="<?= esc($tipoItemCode) ?>" <?= $itemTipo === $tipoItemCode ? 'selected' : '' ?>><?= esc($tipoItemLabel) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td data-label="Descrição">
                                            <div class="orc-item-desc">
                                                <select class="form-select form-select-sm item-catalog-select d-none" data-placeholder="Buscar no catalogo..."></select>
                                                <input type="text" class="form-control form-control-sm item-descricao" name="item_descricao[]" value="<?= esc((string) ($item['descricao'] ?? '')) ?>">
                                                <input type="hidden" class="item-referencia-id" name="item_referencia_id[]" value="<?= esc((string) ($item['referencia_id'] ?? '')) ?>">
                                            </div>
                                            <small
                                                class="item-pricing-meta d-none"
                                                data-preco-base="<?= esc(number_format((float) ($item['preco_base'] ?? 0), 2, '.', '')) ?>"
                                                data-percentual-encargos="<?= esc(number_format((float) ($item['percentual_encargos'] ?? 0), 2, '.', '')) ?>"
                                                data-valor-encargos="<?= esc(number_format((float) ($item['valor_encargos'] ?? 0), 2, '.', '')) ?>"
                                                data-percentual-margem="<?= esc(number_format((float) ($item['percentual_margem'] ?? 0), 2, '.', '')) ?>"
                                                data-valor-margem="<?= esc(number_format((float) ($item['valor_margem'] ?? 0), 2, '.', '')) ?>"
                                                data-valor-recomendado="<?= esc(number_format((float) ($item['valor_recomendado'] ?? 0), 2, '.', '')) ?>"
                                                data-modo-precificacao="<?= esc((string) ($item['modo_precificacao'] ?? '')) ?>"
                                            ></small>
                                            <input type="text" class="form-control form-control-sm mt-1" name="item_observacao[]" value="<?= esc((string) ($item['observacoes'] ?? '')) ?>" placeholder="Observacao do item (opcional)">
                                        </td>
                                        <td data-label="Qtd."><input type="number" step="0.01" min="0.01" class="form-control form-control-sm item-qty" name="item_quantidade[]" value="<?= esc((string) ($item['quantidade'] ?? 1)) ?>"></td>
                                        <td data-label="Valor unit."><input type="text" class="form-control form-control-sm item-unit" name="item_valor_unitario[]" value="<?= esc(number_format((float) ($item['valor_unitario'] ?? 0), 2, '.', '')) ?>"></td>
                                        <td data-label="Desconto"><input type="text" class="form-control form-control-sm item-desconto" name="item_desconto[]" value="<?= esc(number_format((float) ($item['desconto'] ?? 0), 2, '.', '')) ?>"></td>
                                        <td data-label="Acrescimo"><input type="text" class="form-control form-control-sm item-acrescimo" name="item_acrescimo[]" value="<?= esc(number_format((float) ($item['acrescimo'] ?? 0), 2, '.', '')) ?>"></td>
                                        <td data-label="Total"><input type="text" class="form-control form-control-sm item-total" value="<?= esc(number_format((float) ($item['total'] ?? 0), 2, '.', '')) ?>" readonly></td>
                                        <td data-label="Ação" class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" title="Remover item">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 orc-section-card" id="orcSecaoFinanceiro">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="mb-0">Financeiro do Or&ccedil;amento</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label">Subtotal</label>
                            <input type="text" class="form-control" id="orcSubtotalDisplay" value="<?= esc(number_format((float) ($orcamento['subtotal'] ?? 0), 2, '.', '')) ?>" readonly>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Desconto global</label>
                            <input type="text" class="form-control" id="orcDescontoInput" name="desconto" value="<?= esc(number_format((float) ($orcamento['desconto'] ?? 0), 2, '.', '')) ?>">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Acrescimo global</label>
                            <input type="text" class="form-control" id="orcAcrescimoInput" name="acrescimo" value="<?= esc(number_format((float) ($orcamento['acrescimo'] ?? 0), 2, '.', '')) ?>">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Total final</label>
                            <input type="text" class="form-control fw-semibold" id="orcTotalDisplay" value="<?= esc(number_format((float) ($orcamento['total'] ?? 0), 2, '.', '')) ?>" readonly>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Condicoes</label>
                            <textarea class="form-control" name="condicoes" rows="4"><?= esc((string) ($orcamento['condicoes'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Observacoes</label>
                            <textarea class="form-control" name="observacoes" rows="4"><?= esc((string) ($orcamento['observacoes'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-12 d-none" id="motivoRejeicaoWrap">
                            <label class="form-label">Motivo da rejeicao</label>
                            <textarea class="form-control" name="motivo_rejeicao" rows="3"><?= esc((string) ($orcamento['motivo_rejeicao'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-glow" id="btnSalvarOrcamento" data-loading-text="Salvando or&ccedil;amento...">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Atualizar or&ccedil;amento' : 'Salvar or&ccedil;amento' ?>
                </button>
                <?php if (!$isEmbedded): ?>
                    <?php if (!empty($orcamento['id'])): ?>
                        <a href="<?= base_url('orcamentos/visualizar/' . (int) $orcamento['id']) ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <?php else: ?>
                        <a href="<?= base_url('orcamentos') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalNovaMarcaOrc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content glass-card shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="labelModalMarcaOrc"><i class="bi bi-tag text-warning me-2"></i>Nova Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="inputEditarMarcaIdOrc" value="">
                <input type="text" class="form-control" id="inputNovaMarcaOrc" placeholder="Ex.: Samsung, Apple...">
                <div id="errorNovaMarcaOrc" class="text-danger small mt-2 d-none"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-glow w-100" id="btnSalvarMarcaOrc">Salvar Marca</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoModeloOrc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content glass-card shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="labelModalModeloOrc"><i class="bi bi-cpu text-warning me-2"></i>Novo Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="inputEditarModeloIdOrc" value="">
                <div class="mb-2">
                    <label class="small text-muted">Marca selecionada:</label>
                    <input type="text" id="displayMarcaOrc" class="form-control form-control-sm bg-transparent" readonly>
                </div>
                <div>
                    <label class="form-label fw-bold mb-1">Nome do modelo *</label>
                    <input type="text" id="inputNovoModeloOrc" class="form-control" placeholder="Ex.: Galaxy A15, IdeaPad 3...">
                </div>
                <div id="errorNovoModeloOrc" class="text-danger small mt-2 d-none"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-glow" id="btnSalvarModeloOrc">
                    <i class="bi bi-check-lg me-1"></i>Salvar Modelo
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
(function () {
    const formEl = document.getElementById('orcamentoForm');
    const tableBody = document.getElementById('orcamentoItensBody');
    const btnAddItem = document.getElementById('btnAddItem');
    const descontoInput = document.getElementById('orcDescontoInput');
    const acrescimoInput = document.getElementById('orcAcrescimoInput');
    const subtotalDisplay = document.getElementById('orcSubtotalDisplay');
    const totalDisplay = document.getElementById('orcTotalDisplay');
    const statusSelect = document.getElementById('orcamentoStatus');
    const tipoSelect = document.getElementById('orcamentoTipo');
    const tipoHelp = document.getElementById('orcamentoTipoHelp');
    const motivoRejeicaoWrap = document.getElementById('motivoRejeicaoWrap');
    const clienteLookupSelect = document.getElementById('orcamentoClienteLookup');
    const clienteIdInput = document.getElementById('orcamentoClienteId');
    const contatoIdInput = document.getElementById('orcamentoContatoId');
    const osIdInput = document.getElementById('orcamentoOsId');
    const osNumeroHintInput = document.getElementById('orcamentoOsNumeroHint');
    const equipamentoIdInput = document.getElementById('orcamentoEquipamentoId');
    const equipamentoTituloHintInput = document.getElementById('orcamentoEquipamentoTituloHint');
    const conversaIdInput = document.getElementById('orcamentoConversaId');
    const nomeAvulsoInput = document.getElementById('orcamentoNomeAvulso');
    const tituloInput = document.getElementById('orcamentoTitulo');
    const origemSelect = formEl?.querySelector('[name="origem"]');
    const prazoExecucaoSelect = document.getElementById('orcamentoPrazoExecucao');
    const condicoesInput = formEl?.querySelector('[name="condicoes"]');
    const observacoesInput = formEl?.querySelector('[name="observacoes"]');
    const motivoRejeicaoInput = formEl?.querySelector('[name="motivo_rejeicao"]');
    const vinculosVisual = document.getElementById('orcamentoVinculosVisual');
    const tituloTemplateButtons = Array.from(document.querySelectorAll('[data-titulo-template]'));
    const tituloInsertButtons = Array.from(document.querySelectorAll('[data-titulo-insert]'));
    const draftRecoverBar = document.getElementById('orcamentoDraftRecoverBar');
    const draftSavedAtLabel = document.getElementById('orcamentoDraftSavedAt');
    const btnDraftRestore = document.getElementById('btnOrcamentoDraftRestore');
    const btnDraftDiscard = document.getElementById('btnOrcamentoDraftDiscard');
    const submitButton = document.getElementById('btnSalvarOrcamento');
    const isCreateMode = <?= $isEdit ? 'false' : 'true' ?>;
    const DEFAULT_TIPO_ORCAMENTO = <?= json_encode((string) ($orcamento['tipo_orcamento'] ?? 'previo')) ?>;
    const DRAFT_STATUS_RASCUNHO = 'rascunho';
    const DRAFT_STATUS_PENDENTE_ENVIO = 'pendente_envio';
    const DRAFT_STORAGE_KEY = `orcamentos:nova:draft:${window.location.pathname}:${window.location.search}`;
    const DEFAULT_ORIGEM = 'manual';
    const DEFAULT_PRAZO_EXECUCAO = '3';
    const DEFAULT_VALIDADE_DIAS = 10;
    const registrarContatoWrap = document.getElementById('orcamentoRegistrarContatoWrap');
    const registrarContatoCheckbox = document.getElementById('orcamentoRegistrarContato');
    const telefoneInput = document.getElementById('orcamentoTelefone');
    const emailInput = document.getElementById('orcamentoEmail');
    const validadeDiasInput = document.getElementById('orcamentoValidadeDias');
    const validadeDataInput = document.getElementById('orcamentoValidadeData');
    const clienteLookupUrl = <?= json_encode(base_url('orcamentos/clientes/lookup')) ?>;
    const clienteLookupInitial = <?= json_encode($clienteLookupInitial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const equipamentoLookupUrl = <?= json_encode(base_url('orcamentos/equipamentos/cliente')) ?>;
    const osAbertasLookupUrl = <?= json_encode(base_url('orcamentos/os-abertas/cliente')) ?>;
    const equipamentoLookupInitial = <?= json_encode($equipamentoLookupInitial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const equipamentoCatalog = <?= json_encode($equipamentoCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const equipamentoLookupSelect = document.getElementById('orcamentoEquipamentoLookup');
    const equipamentoLookupHint = document.getElementById('orcamentoEquipamentoLookupHint');
    const osLookupSelect = document.getElementById('orcamentoOsLookup');
    const osLookupWrap = document.getElementById('orcamentoOsLookupWrap');
    const osLookupHelp = document.getElementById('orcamentoOsLookupHelp');
    const vinculoOsCol = document.getElementById('orcamentoVinculoOsCol');
    const vinculoOsTitulo = document.getElementById('orcamentoVinculoOsTitulo');
    const vinculoOsStatus = document.getElementById('orcamentoVinculoOsStatus');
    const vinculoOsCounter = document.getElementById('orcamentoVinculoOsCounter');
    const vinculoEquipamentoCol = document.getElementById('orcamentoVinculoEquipamentoCol');
    const vinculoEquipamentoFoto = document.getElementById('orcamentoVinculoEquipamentoFoto');
    const vinculoEquipamentoTitulo = document.getElementById('orcamentoVinculoEquipamentoTitulo');
    const vinculoEquipamentoDescricao = document.getElementById('orcamentoVinculoEquipamentoDescricao');
    const vinculoConversaCol = document.getElementById('orcamentoVinculoConversaCol');
    const btnOrcNovoEquipamentoManual = document.getElementById('btnOrcNovoEquipamentoManual');
    const equipamentoManualFieldsWrap = document.getElementById('orcamentoEquipamentoManualFields');
    const equipTipoSelect = document.getElementById('orcEquipTipo');
    const equipMarcaSelect = document.getElementById('orcEquipMarca');
    const equipModeloSelect = document.getElementById('orcEquipModelo');
    const equipCorPicker = document.getElementById('orcEquipCorPicker');
    const equipCorNomeInput = document.getElementById('orcEquipCorNome');
    const equipCorHexInput = document.getElementById('orcEquipCorHex');
    const equipCorRgbInput = document.getElementById('orcEquipCorRgb');
    const colorPreviewBox = document.getElementById('orcColorPreviewBox');
    const colorPreviewHex = document.getElementById('orcColorPreviewHex');
    const colorPreviewName = document.getElementById('orcColorPreviewName');
    const colorNearestGrid = document.getElementById('orcCoresProximasGrid');
    const colorCatalogContainer = document.getElementById('orcColorCatalog');
    const btnOrcNovaMarca = document.getElementById('btnOrcNovaMarca');
    const btnOrcEditarMarca = document.getElementById('btnOrcEditarMarca');
    const btnOrcNovoModelo = document.getElementById('btnOrcNovoModelo');
    const btnOrcEditarModelo = document.getElementById('btnOrcEditarModelo');
    const btnSalvarMarcaOrc = document.getElementById('btnSalvarMarcaOrc');
    const btnSalvarModeloOrc = document.getElementById('btnSalvarModeloOrc');
    const inputNovaMarcaOrc = document.getElementById('inputNovaMarcaOrc');
    const inputNovoModeloOrc = document.getElementById('inputNovoModeloOrc');
    const inputEditarMarcaIdOrc = document.getElementById('inputEditarMarcaIdOrc');
    const inputEditarModeloIdOrc = document.getElementById('inputEditarModeloIdOrc');
    const labelModalMarcaOrc = document.getElementById('labelModalMarcaOrc');
    const labelModalModeloOrc = document.getElementById('labelModalModeloOrc');
    const displayMarcaOrc = document.getElementById('displayMarcaOrc');
    const errorNovaMarcaOrc = document.getElementById('errorNovaMarcaOrc');
    const errorNovoModeloOrc = document.getElementById('errorNovoModeloOrc');
    const csrfTokenName = <?= json_encode(csrf_token()) ?>;
    const csrfHashValue = <?= json_encode(csrf_hash()) ?>;
    const equipamentoMarcaSalvarUrl = <?= json_encode(base_url('equipamentosmarcas/salvar_ajax')) ?>;
    const equipamentoMarcaAtualizarBaseUrl = <?= json_encode(base_url('equipamentosmarcas/atualizar_ajax')) ?>;
    const equipamentoModeloSalvarUrl = <?= json_encode(base_url('equipamentosmodelos/salvar_ajax')) ?>;
    const equipamentoModeloAtualizarBaseUrl = <?= json_encode(base_url('equipamentosmodelos/atualizar_ajax')) ?>;
    const equipamentoModeloPorMarcaUrl = <?= json_encode(base_url('equipamentosmodelos/por-marca')) ?>;
    const itemCatalogUrl = <?= json_encode(base_url('orcamentos/item/catalogo')) ?>;
    const currentOrcamentoId = <?= json_encode((int) ($orcamento['id'] ?? 0)) ?>;
    const pacoteOfertaModuleReady = <?= $pacoteOfertaModuleReady ? 'true' : 'false' ?>;
    const pacoteOfertaDetectUrl = <?= json_encode(base_url('orcamentos/pacotes/oferta/detectar')) ?>;
    const pacoteOfertaSendUrl = <?= json_encode(base_url('orcamentos/pacotes/oferta/enviar')) ?>;
    const pacoteBaseadoSwitch = document.getElementById('orcPacoteBaseadoSwitch');
    const pacoteOfertaApplyIdInput = document.getElementById('orcamentoPacoteOfertaId');
    const pacoteOfertaApplyFlagInput = document.getElementById('orcamentoAplicarPacoteOferta');
    const pacoteOfertaPacoteSelect = document.getElementById('orcPacoteOfertaPacoteId');
    const pacoteOfertaTelefoneInput = document.getElementById('orcPacoteOfertaTelefone');
    const pacoteOfertaExpiraSelect = document.getElementById('orcPacoteOfertaExpiraDias');
    const pacoteOfertaEnviarWhatsappCheckbox = document.getElementById('orcPacoteOfertaEnviarWhatsapp');
    const pacoteOfertaMensagemInput = document.getElementById('orcPacoteOfertaMensagem');
    const pacoteOfertaMensagemLinkWrap = document.getElementById('orcPacoteOfertaMensagemLinkWrap');
    const pacoteOfertaMensagemComLinkCheckbox = document.getElementById('orcPacoteOfertaMensagemComLink');
    const pacoteOfertaStatusWrap = document.getElementById('orcPacoteOfertaStatusWrap');
    const pacoteOfertaStatusTitle = document.getElementById('orcPacoteOfertaStatusTitle');
    const pacoteOfertaStatusDesc = document.getElementById('orcPacoteOfertaStatusDesc');
    const pacoteOfertaStatusBadge = document.getElementById('orcPacoteOfertaStatusBadge');
    const pacoteOfertaDetalhes = document.getElementById('orcPacoteOfertaDetalhes');
    const pacoteOfertaApplyWrap = document.getElementById('orcPacoteOfertaApplyWrap');
    const pacoteOfertaApplyCheck = document.getElementById('orcPacoteOfertaApplyCheck');
    const pacoteOfertaLinkWrap = document.getElementById('orcPacoteOfertaLinkWrap');
    const pacoteOfertaLinkBtn = document.getElementById('orcPacoteOfertaLinkBtn');
    const btnPacoteOfertaCopyLink = document.getElementById('btnOrcPacoteOfertaCopyLink');
    const btnPacoteOfertaRefresh = document.getElementById('btnOrcPacoteOfertaRefresh');
    const btnPacoteOfertaEnviar = document.getElementById('btnEnviarPacoteOferta');
    const pacoteOfertaStatusLabels = {
        ativo: 'Ativo',
        enviado: 'Enviado',
        escolhido: 'Escolhido pelo cliente',
        aplicado_orcamento: 'Aplicado no orcamento',
        expirado: 'Expirado',
        cancelado: 'Cancelado',
        erro_envio: 'Erro de envio',
    };
    const pacoteOfertaSendLabels = {
        send: 'Enviar oferta',
        resend: 'Reenviar oferta',
    };
    const setPacoteOfertaSendLabel = (isResend = false) => {
        if (!btnPacoteOfertaEnviar) {
            return;
        }
        const label = isResend ? pacoteOfertaSendLabels.resend : pacoteOfertaSendLabels.send;
        btnPacoteOfertaEnviar.dataset.mode = isResend ? 'resend' : 'send';
        btnPacoteOfertaEnviar.innerHTML = `<i class="bi bi-send me-1"></i>${label}`;
    };

    const toNumber = (value) => {
        if (value === null || value === undefined) return 0;
        let raw = String(value).trim();
        if (raw === '') return 0;
        raw = raw.replace(/[^\d,.\-]/g, '');
        if (raw.includes(',') && raw.includes('.')) {
            raw = raw.replace(/\./g, '');
        }
        raw = raw.replace(',', '.');
        const parsed = Number(raw);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const fixed = (value) => (Math.round((value + Number.EPSILON) * 100) / 100).toFixed(2);

    const recalcRow = (row) => {
        const qty = Math.max(0.01, toNumber(row.querySelector('.item-qty')?.value));
        const unit = Math.max(0, toNumber(row.querySelector('.item-unit')?.value));
        const desconto = Math.max(0, toNumber(row.querySelector('.item-desconto')?.value));
        const acrescimo = Math.max(0, toNumber(row.querySelector('.item-acrescimo')?.value));
        const total = Math.max(0, (qty * unit) - desconto + acrescimo);
        const totalInput = row.querySelector('.item-total');
        if (totalInput) totalInput.value = fixed(total);
        return total;
    };

    const recalcAll = () => {
        let subtotal = 0;
        tableBody.querySelectorAll('.orc-item-row').forEach((row) => {
            subtotal += recalcRow(row);
        });
        const desconto = Math.max(0, toNumber(descontoInput?.value));
        const acrescimo = Math.max(0, toNumber(acrescimoInput?.value));
        const total = Math.max(0, subtotal - desconto + acrescimo);
        if (subtotalDisplay) subtotalDisplay.value = fixed(subtotal);
        if (totalDisplay) totalDisplay.value = fixed(total);
    };

    const setSubmitLoading = (enabled) => {
        if (!submitButton) return;
        if (enabled) {
            if (!submitButton.dataset.originalHtml) {
                submitButton.dataset.originalHtml = submitButton.innerHTML;
            }
            const loadingText = submitButton.getAttribute('data-loading-text') || 'Salvando...';
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
            return;
        }

        if (submitButton.dataset.originalHtml) {
            submitButton.innerHTML = submitButton.dataset.originalHtml;
        }
        submitButton.disabled = false;
        submitButton.removeAttribute('aria-busy');
    };

    const normalizeId = (value) => String(value ?? '').trim();

    const getTipoOrcamentoSubmitBlockState = () => {
        if (!formEl || !tipoSelect || !osIdInput) {
            return null;
        }

        const tipoAssistencia = isTipoAssistencia();
        const osSelecionada = normalizeId(osIdInput.value || '');
        const possuiVinculoOs = osSelecionada !== '';

        if (tipoAssistencia && !possuiVinculoOs) {
            return {
                code: 'assistencia_sem_os',
                title: 'Selecione uma OS aberta',
                text: 'Para salvar um orcamento com equipamento na assistencia, escolha primeiro uma OS aberta vinculada ao equipamento em atendimento.',
                focusTarget: osLookupSelect || tipoSelect || null,
            };
        }

        if (!tipoAssistencia && possuiVinculoOs) {
            return {
                code: 'previo_com_os',
                title: 'Revise o tipo do orcamento',
                text: 'Este orcamento ainda possui uma OS vinculada. Mantenha o tipo como "com equipamento na assistencia" ou remova o vinculo da OS antes de salvar.',
                focusTarget: tipoSelect || osLookupSelect || null,
            };
        }

        return null;
    };

    const blockInvalidTipoOrcamentoSubmit = async (context = 'submit', blockState = null) => {
        const state = blockState || getTipoOrcamentoSubmitBlockState();
        if (!state) {
            return false;
        }

        await window.DSFeedback.warning(state.title, state.text, {
            confirmButtonText: 'Entendi',
        });

        if (state.focusTarget) {
            state.focusTarget.focus();
        }

        console.error('[Orçamentos] Bloqueio de submit no frontend.', {
            context,
            code: state.code,
            tipo_orcamento: tipoSelect?.value || '',
            os_id: osIdInput?.value || '',
            cliente_id: clienteIdInput?.value || '',
        });
        return true;
    };

    const interceptInvalidTipoOrcamentoSubmit = (event, context = 'submit') => {
        const blockState = getTipoOrcamentoSubmitBlockState();
        if (!blockState) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        blockInvalidTipoOrcamentoSubmit(context, blockState).catch((error) => {
            console.error('[Orçamentos] Falha ao exibir bloqueio de submit no frontend.', error);
        });
    };

    if (formEl) {
        formEl.addEventListener('submit', (event) => {
            interceptInvalidTipoOrcamentoSubmit(event, 'form-capture');
        }, true);
    }

    if (submitButton) {
        submitButton.addEventListener('click', (event) => {
            interceptInvalidTipoOrcamentoSubmit(event, 'button-capture');
        }, true);
    }

    const escapeHtmlSafe = (raw) => String(raw ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const formatMoneyLabel = (value) => {
        const numberValue = Number(value);
        if (!Number.isFinite(numberValue)) return '';
        return numberValue.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    const formatPercentLabel = (value) => {
        const numberValue = Number(value);
        if (!Number.isFinite(numberValue)) return '0,00%';
        return `${numberValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
    };

    const clearItemPricingMeta = (row) => {
        const metaEl = row.querySelector('.item-pricing-meta');
        if (metaEl) {
            metaEl.classList.add('d-none');
            metaEl.classList.remove('text-primary', 'text-warning');
            metaEl.textContent = '';
        }
        delete row.dataset.pieceRecommendedPrice;
        delete row.dataset.piecePricingMode;
        const unitInput = row.querySelector('.item-unit');
        if (unitInput) {
            unitInput.classList.remove('is-invalid');
        }
    };

    const setItemPricingMeta = (row, pricingMeta) => {
        const metaEl = row.querySelector('.item-pricing-meta');
        if (!metaEl) {
            return;
        }
        if (!pricingMeta || !Number.isFinite(pricingMeta.valorRecomendado) || pricingMeta.valorRecomendado <= 0) {
            clearItemPricingMeta(row);
            return;
        }

        row.dataset.pieceRecommendedPrice = fixed(pricingMeta.valorRecomendado);
        row.dataset.piecePricingMode = String(pricingMeta.modoPrecificacao || 'peca_instalada_auto');

        const detalhe = `Peca instalada: base ${formatMoneyLabel(pricingMeta.precoBase)} + encargos ${formatPercentLabel(pricingMeta.percentualEncargos)} (${formatMoneyLabel(pricingMeta.valorEncargos)}) + margem ${formatPercentLabel(pricingMeta.percentualMargem)} (${formatMoneyLabel(pricingMeta.valorMargem)}) = recomendado ${formatMoneyLabel(pricingMeta.valorRecomendado)}.`;
        metaEl.textContent = detalhe;
        metaEl.classList.remove('d-none', 'text-warning');
        metaEl.classList.add('text-primary');
    };

    const resolvePiecePricingMetaFromCatalogItem = (item) => {
        if (String(item?.kind || '').toLowerCase() !== 'peca') {
            return null;
        }
        const precificacao = item?.precificacao || {};
        const valorRecomendado = Number(precificacao?.valor_recomendado ?? item?.valor_unitario ?? NaN);
        if (!Number.isFinite(valorRecomendado) || valorRecomendado <= 0) {
            return null;
        }

        return {
            precoBase: Number(precificacao?.preco_base ?? item?.preco_custo ?? item?.preco_venda ?? 0),
            percentualEncargos: Number(precificacao?.percentual_encargos ?? 0),
            valorEncargos: Number(precificacao?.valor_encargos ?? 0),
            percentualMargem: Number(precificacao?.percentual_margem ?? 0),
            valorMargem: Number(precificacao?.valor_margem ?? 0),
            valorRecomendado,
            modoPrecificacao: String(precificacao?.modo_precificacao || 'peca_instalada_auto'),
        };
    };

    const resolvePiecePricingMetaFromRow = (row) => {
        const tipoSelect = row.querySelector('[name="item_tipo[]"]');
        if (String(tipoSelect?.value || '').toLowerCase() !== 'peca') {
            return null;
        }
        const metaEl = row.querySelector('.item-pricing-meta');
        if (!metaEl) {
            return null;
        }
        const valorRecomendado = Number(metaEl.dataset.valorRecomendado || row.dataset.pieceRecommendedPrice || NaN);
        if (!Number.isFinite(valorRecomendado) || valorRecomendado <= 0) {
            return null;
        }

        return {
            precoBase: Number(metaEl.dataset.precoBase || 0),
            percentualEncargos: Number(metaEl.dataset.percentualEncargos || 0),
            valorEncargos: Number(metaEl.dataset.valorEncargos || 0),
            percentualMargem: Number(metaEl.dataset.percentualMargem || 0),
            valorMargem: Number(metaEl.dataset.valorMargem || 0),
            valorRecomendado,
            modoPrecificacao: String(metaEl.dataset.modoPrecificacao || row.dataset.piecePricingMode || 'peca_instalada_auto'),
        };
    };

    const enforcePieceMinimumPrice = async (row, notifyUser = false) => {
        const tipoSelect = row.querySelector('[name="item_tipo[]"]');
        if (String(tipoSelect?.value || '').toLowerCase() !== 'peca') {
            return;
        }
        const valorRecomendado = Number(row.dataset.pieceRecommendedPrice || NaN);
        if (!Number.isFinite(valorRecomendado) || valorRecomendado <= 0) {
            return;
        }

        const unitInput = row.querySelector('.item-unit');
        if (!unitInput) {
            return;
        }

        const valorDigitado = Math.max(0, toNumber(unitInput.value));
        if (valorDigitado + 0.00001 >= valorRecomendado) {
            unitInput.classList.remove('is-invalid');
            return;
        }

        unitInput.value = fixed(valorRecomendado);
        unitInput.classList.remove('is-invalid');
        recalcAll();

        if (notifyUser && window.Swal) {
            await window.Swal.fire({
                icon: 'info',
                title: 'Valor ajustado para peca instalada',
                text: `O piso minimo recomendado e ${formatMoneyLabel(valorRecomendado)}.`,
                toast: true,
                position: 'top-end',
                timer: 2500,
                showConfirmButton: false,
                timerProgressBar: true,
            });
        }
    };

    const hydrateRowPricingMeta = (row) => {
        const pricingMeta = resolvePiecePricingMetaFromRow(row);
        if (!pricingMeta) {
            clearItemPricingMeta(row);
            return;
        }
        setItemPricingMeta(row, pricingMeta);
    };

    const resolveCatalogReferenciaId = (item) => {
        const pecaId = parseInt(String(item?.peca_id ?? ''), 10);
        if (Number.isFinite(pecaId) && pecaId > 0) return pecaId;
        const servicoId = parseInt(String(item?.servico_id ?? ''), 10);
        if (Number.isFinite(servicoId) && servicoId > 0) return servicoId;
        const rawId = String(item?.id || '');
        const match = rawId.match(/^(?:peca|servico):(\d+)$/i);
        if (match) return parseInt(match[1], 10);
        return null;
    };

    const hasSelect2 = () => {
        const jq = window.jQuery || window.$;
        return Boolean(jq && jq.fn && typeof jq.fn.select2 === 'function');
    };

    const ensureCatalogSelection = (row, tipo) => {
        const catalogSelect = row.querySelector('.item-catalog-select');
        const descricaoInput = row.querySelector('.item-descricao');
        if (!catalogSelect || !descricaoInput || !hasSelect2()) return;
        const descricao = String(descricaoInput.value || '').trim();
        if (descricao === '') return;

        const referenciaInput = row.querySelector('.item-referencia-id');
        const referenciaId = String(referenciaInput?.value || '').trim();
        const optionValue = referenciaId !== '' ? `${tipo}:${referenciaId}` : descricao;
        const jq = window.jQuery || window.$;
        const $catalog = jq(catalogSelect);

        catalogSelect.dataset.suppressCatalogSync = '1';
        const existingOption = Array.from(catalogSelect.options || []).find((option) => option.value === optionValue);
        if (existingOption) {
            existingOption.text = descricao;
            existingOption.selected = true;
            $catalog.trigger('change');
        } else {
            const option = new Option(descricao, optionValue, true, true);
            $catalog.append(option).trigger('change');
        }
        delete catalogSelect.dataset.suppressCatalogSync;
    };

    const initItemCatalogSelect = (row) => {
        const catalogSelect = row.querySelector('.item-catalog-select');
        const tipoSelect = row.querySelector('[name="item_tipo[]"]');
        const descricaoInput = row.querySelector('.item-descricao');
        const referenciaInput = row.querySelector('.item-referencia-id');
        const valorUnitInput = row.querySelector('.item-unit');

        if (!catalogSelect || !tipoSelect || !descricaoInput) return;
        if (!hasSelect2()) {
            catalogSelect.classList.add('d-none');
            descricaoInput.classList.remove('d-none');
            descricaoInput.required = true;
            return;
        }

        const jq = window.jQuery || window.$;
        const $catalog = jq(catalogSelect);
        if (!$catalog.hasClass('select2-hidden-accessible')) {
            const modalParent = $catalog.closest('.modal');
            const select2Options = {
                width: '100%',
                allowClear: true,
                minimumInputLength: 0,
                placeholder: catalogSelect.dataset.placeholder || 'Buscar no catalogo...',
                ajax: {
                    url: itemCatalogUrl,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({
                        q: params.term || '',
                        tipo: String(tipoSelect.value || 'servico').toLowerCase(),
                        limit: params.term ? 20 : 10,
                    }),
                    processResults: (response) => ({
                        results: Array.isArray(response?.results) ? response.results : [],
                    }),
                },
                templateResult: (item) => {
                    if (item.loading) return escapeHtmlSafe(item.text || '');
                    const isPeca = String(item?.kind || '').toLowerCase() === 'peca';
                    const priceLabelRaw = formatMoneyLabel(item.valor_unitario);
                    const priceLabel = isPeca && priceLabelRaw
                        ? `Recomendado (peca instalada): ${priceLabelRaw}`
                        : priceLabelRaw;
                    const estoqueLabel = item.kind === 'peca' && item.estoque !== null
                        ? `Estoque: ${item.estoque}`
                        : '';
                    const metaParts = [];
                    if (item.codigo) metaParts.push(item.codigo);
                    if (item.categoria) metaParts.push(item.categoria);
                    if (item.tipo_equipamento) metaParts.push(item.tipo_equipamento);
                    const metaLabel = metaParts.length ? metaParts.join(' • ') : '';
                    const infoLine = [priceLabel, estoqueLabel].filter(Boolean).join(' | ');
                    return `
                        <div class="d-flex flex-column">
                            <div class="fw-semibold">${escapeHtmlSafe(item.text || '')}</div>
                            ${metaLabel ? `<small class="text-muted">${escapeHtmlSafe(metaLabel)}</small>` : ''}
                            ${infoLine ? `<small class="text-muted">${escapeHtmlSafe(infoLine)}</small>` : ''}
                        </div>
                    `;
                },
                templateSelection: (item) => escapeHtmlSafe(item?.text || item?.descricao || ''),
                escapeMarkup: (markup) => markup,
            };
            if (modalParent.length > 0) {
                select2Options.dropdownParent = modalParent;
            }
            $catalog.select2(select2Options);
        }

        $catalog.off('.orcItemCatalog');
        $catalog.on('select2:select.orcItemCatalog', (event) => {
            if (catalogSelect.dataset.suppressCatalogSync === '1') return;
            const item = event?.params?.data || {};
            const descricao = String(item?.descricao || item?.text || '').trim();
            if (descricaoInput) {
                descricaoInput.value = descricao;
                descricaoInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            const referenciaId = resolveCatalogReferenciaId(item);
            if (referenciaInput && referenciaId !== null) {
                referenciaInput.value = String(referenciaId);
            }
            const valorUnitario = Number(item?.valor_unitario ?? NaN);
            if (valorUnitInput && Number.isFinite(valorUnitario)) {
                valorUnitInput.value = fixed(valorUnitario);
            }
            const pricingMeta = resolvePiecePricingMetaFromCatalogItem(item);
            if (pricingMeta) {
                setItemPricingMeta(row, pricingMeta);
            } else {
                clearItemPricingMeta(row);
            }
            recalcAll();
        });

        $catalog.on('select2:clear.orcItemCatalog', () => {
            if (catalogSelect.dataset.suppressCatalogSync === '1') return;
            if (descricaoInput) {
                descricaoInput.value = '';
                descricaoInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (referenciaInput) {
                referenciaInput.value = '';
            }
            if (valorUnitInput) {
                valorUnitInput.value = fixed(0);
            }
            clearItemPricingMeta(row);
            recalcAll();
        });
    };

    const syncItemCatalogVisibility = (row) => {
        const tipoSelect = row.querySelector('[name="item_tipo[]"]');
        const descricaoInput = row.querySelector('.item-descricao');
        const catalogSelect = row.querySelector('.item-catalog-select');
        const referenciaInput = row.querySelector('.item-referencia-id');

        if (!tipoSelect || !descricaoInput || !catalogSelect) return;
        const tipo = String(tipoSelect.value || 'servico').toLowerCase();
        const isCatalog = ['peca', 'servico'].includes(tipo);

        if (isCatalog && hasSelect2()) {
            catalogSelect.classList.remove('d-none');
            descricaoInput.classList.add('d-none');
            descricaoInput.required = false;
            initItemCatalogSelect(row);
            ensureCatalogSelection(row, tipo);
            if (tipo === 'peca') {
                hydrateRowPricingMeta(row);
            } else {
                clearItemPricingMeta(row);
            }
            return;
        }

        catalogSelect.classList.add('d-none');
        descricaoInput.classList.remove('d-none');
        descricaoInput.required = true;
        if (hasSelect2()) {
            const jq = window.jQuery || window.$;
            const $catalog = jq(catalogSelect);
            if ($catalog.hasClass('select2-hidden-accessible')) {
                catalogSelect.dataset.suppressCatalogSync = '1';
                $catalog.val(null).trigger('change');
                delete catalogSelect.dataset.suppressCatalogSync;
            }
        } else {
            catalogSelect.value = '';
        }
        if (referenciaInput) {
            referenciaInput.value = '';
        }
        clearItemPricingMeta(row);
    };

    const bindRow = (row) => {
        row.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', recalcAll);
        });
        const unitInput = row.querySelector('.item-unit');
        if (unitInput) {
            unitInput.addEventListener('blur', () => {
                enforcePieceMinimumPrice(row, true).catch((error) => {
                    console.error('[Orçamentos] Falha ao aplicar piso minimo de peca instalada.', error);
                });
            });
        }
        const tipoSelect = row.querySelector('[name="item_tipo[]"]');
        if (tipoSelect) {
            tipoSelect.addEventListener('change', () => {
                syncItemCatalogVisibility(row);
            });
        }
        const removeBtn = row.querySelector('.btn-remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', async () => {
                const proceed = await window.DSFeedback.confirm({
                    icon: 'warning',
                    title: 'Remover item?',
                    text: 'Este item sera removido do orcamento.',
                    showCancelButton: true,
                    confirmButtonText: 'Remover',
                    cancelButtonText: 'Cancelar',
                });

                if (!proceed) return;
                row.remove();
                if (!tableBody.querySelector('.orc-item-row')) {
                    addRow();
                }
                recalcAll();
            });
        }
        syncItemCatalogVisibility(row);
        hydrateRowPricingMeta(row);
        enforcePieceMinimumPrice(row, false).catch((error) => {
            console.error('[Orçamentos] Falha ao aplicar piso minimo inicial de peca instalada.', error);
        });
    };

    const addRow = () => {
        const tr = document.createElement('tr');
        tr.className = 'orc-item-row';
        tr.innerHTML = `
            <td data-label="Tipo">
                <select name="item_tipo[]" class="form-select form-select-sm">
                    <option value="servico">Serviço</option>
                    <option value="peca">Peca</option>
                    <option value="combo">Combo</option>
                    <option value="avulso">Avulso</option>
                </select>
            </td>
            <td data-label="Descrição">
                <div class="orc-item-desc">
                    <select class="form-select form-select-sm item-catalog-select d-none" data-placeholder="Buscar no catalogo..."></select>
                    <input type="text" class="form-control form-control-sm item-descricao" name="item_descricao[]">
                    <input type="hidden" class="item-referencia-id" name="item_referencia_id[]" value="">
                </div>
                <small class="item-pricing-meta d-none"></small>
                <input type="text" class="form-control form-control-sm mt-1" name="item_observacao[]" placeholder="Observacao do item (opcional)">
            </td>
            <td data-label="Qtd."><input type="number" step="0.01" min="0.01" class="form-control form-control-sm item-qty" name="item_quantidade[]" value="1"></td>
            <td data-label="Valor unit."><input type="text" class="form-control form-control-sm item-unit" name="item_valor_unitario[]" value="0.00"></td>
            <td data-label="Desconto"><input type="text" class="form-control form-control-sm item-desconto" name="item_desconto[]" value="0.00"></td>
            <td data-label="Acrescimo"><input type="text" class="form-control form-control-sm item-acrescimo" name="item_acrescimo[]" value="0.00"></td>
            <td data-label="Total"><input type="text" class="form-control form-control-sm item-total" value="0.00" readonly></td>
            <td data-label="Ação" class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" title="Remover item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(tr);
        bindRow(tr);
        recalcAll();
    };

    if (btnAddItem) {
        btnAddItem.addEventListener('click', addRow);
    }

    if (descontoInput) descontoInput.addEventListener('input', recalcAll);
    if (acrescimoInput) acrescimoInput.addEventListener('input', recalcAll);

    tableBody.querySelectorAll('.orc-item-row').forEach(bindRow);

    const syncStatusFields = () => {
        if (!statusSelect || !motivoRejeicaoWrap) return;
        if (statusSelect.value === 'rejeitado') {
            motivoRejeicaoWrap.classList.remove('d-none');
        } else {
            motivoRejeicaoWrap.classList.add('d-none');
        }
    };
    if (statusSelect) {
        statusSelect.addEventListener('change', syncStatusFields);
        syncStatusFields();
    }

    const applyValidadeDias = () => {
        if (!validadeDiasInput || !validadeDataInput) return;
        let dias = parseInt(validadeDiasInput.value, 10);
        if (!Number.isFinite(dias) || dias < 1) dias = 1;
        if (dias > 365) dias = 365;
        validadeDiasInput.value = String(dias);
        const base = new Date();
        base.setHours(0, 0, 0, 0);
        base.setDate(base.getDate() + dias);
        const yyyy = base.getFullYear();
        const mm = String(base.getMonth() + 1).padStart(2, '0');
        const dd = String(base.getDate()).padStart(2, '0');
        validadeDataInput.value = `${yyyy}-${mm}-${dd}`;
    };

    if (validadeDiasInput) {
        validadeDiasInput.addEventListener('input', applyValidadeDias);
        validadeDiasInput.addEventListener('change', applyValidadeDias);
    }

    const getTipoOrcamento = () => String(tipoSelect?.value || DEFAULT_TIPO_ORCAMENTO || 'previo').trim().toLowerCase();
    const isTipoAssistencia = () => getTipoOrcamento() === 'assistencia';

    const syncTipoOrcamentoHelp = () => {
        if (!tipoHelp) return;
        tipoHelp.textContent = isTipoAssistencia()
            ? 'Use quando o equipamento ja estiver na assistencia e vinculado a uma OS aberta.'
            : 'Use para estimativa inicial quando o equipamento ainda não tiver entrado na assistencia.';
    };

    const normalizeTitleChunk = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();

    const selectedText = (selectElement) => {
        if (!selectElement || !selectElement.options || selectElement.selectedIndex < 0) {
            return '';
        }
        return normalizeTitleChunk(selectElement.options[selectElement.selectedIndex]?.text || '');
    };

    const formatOsNumeroLabel = (value) => {
        const normalized = normalizeTitleChunk(value);
        if (normalized === '') {
            return 'OS';
        }
        return normalized.toUpperCase().startsWith('OS') ? normalized : `OS ${normalized}`;
    };

    const toggleVinculoColumn = (element, visible) => {
        if (!element) return;
        element.classList.toggle('d-none', !visible);
    };

    const refreshVinculosVisualVisibility = () => {
        if (!vinculosVisual) return;
        const shouldShow = [vinculoOsCol, vinculoEquipamentoCol, vinculoConversaCol]
            .some((element) => element && !element.classList.contains('d-none'));
        vinculosVisual.classList.toggle('d-none', !shouldShow);
    };

    const resetOsLookupOptions = () => {
        if (!osLookupSelect) return;
        osLookupSelect.innerHTML = '<option value="">Selecione uma OS aberta...</option>';
        osLookupSelect.value = '';
        osLookupSelect.disabled = true;
    };

    const setOsLookupHelp = (message = '') => {
        if (!osLookupHelp) return;
        const text = normalizeTitleChunk(message);
        osLookupHelp.textContent = text;
        osLookupHelp.classList.toggle('d-none', text === '');
    };

    const clearVinculoOsCard = (options = {}) => {
        const hideColumn = options.hideColumn !== false;
        if (osIdInput) osIdInput.value = '';
        if (osNumeroHintInput) osNumeroHintInput.value = '';
        if (vinculoOsTitulo) {
            vinculoOsTitulo.textContent = 'Nenhuma OS vinculada';
        }
        if (vinculoOsStatus) {
            vinculoOsStatus.textContent = '';
            vinculoOsStatus.classList.add('d-none');
        }
        if (vinculoOsCounter) {
            vinculoOsCounter.textContent = '';
            vinculoOsCounter.classList.add('d-none');
        }
        if (osLookupWrap) {
            osLookupWrap.classList.add('d-none');
        }
        resetOsLookupOptions();
        setOsLookupHelp('');
        toggleVinculoColumn(vinculoOsCol, !hideColumn);
        refreshVinculosVisualVisibility();
    };

    const renderVinculoEquipamentoCard = (equipamento) => {
        const displayText = normalizeTitleChunk(equipamento?.display_text || '');
        const tipo = normalizeTitleChunk(equipamento?.tipo || '');
        const marca = normalizeTitleChunk(equipamento?.marca || '');
        const modelo = normalizeTitleChunk(equipamento?.modelo || '');
        const descricao = normalizeTitleChunk(
            equipamento?.descricao || [marca, modelo].filter(Boolean).join(' ')
        );
        const hint = displayText !== ''
            ? displayText
            : normalizeTitleChunk([tipo, descricao].filter(Boolean).join(' '));
        const fotoUrl = normalizeTitleChunk(equipamento?.foto_url || '');

        if (equipamentoTituloHintInput) {
            equipamentoTituloHintInput.value = hint;
        }
        if (equipamentoIdInput && equipamento?.id) {
            equipamentoIdInput.value = String(equipamento.id);
        }
        if (vinculoEquipamentoTitulo) {
            vinculoEquipamentoTitulo.textContent = displayText || tipo || 'Equipamento';
        }
        if (vinculoEquipamentoDescricao) {
            vinculoEquipamentoDescricao.textContent = descricao;
        }
        if (vinculoEquipamentoFoto) {
            const fallback = vinculoEquipamentoFoto.dataset.placeholderSrc || '';
            vinculoEquipamentoFoto.src = fotoUrl || fallback;
            vinculoEquipamentoFoto.classList.toggle('d-none', !(fotoUrl || fallback));
        }
        toggleVinculoColumn(vinculoEquipamentoCol, true);
        refreshVinculosVisualVisibility();
    };

    const clearVinculoEquipamentoCard = (options = {}) => {
        const hideColumn = options.hideColumn !== false;
        const keepEquipamentoId = options.keepEquipamentoId === true;
        const keepHint = options.keepHint === true;
        if (!keepEquipamentoId && equipamentoIdInput) {
            equipamentoIdInput.value = '';
        }
        if (!keepHint && equipamentoTituloHintInput) {
            equipamentoTituloHintInput.value = '';
        }
        if (vinculoEquipamentoTitulo) {
            vinculoEquipamentoTitulo.textContent = 'Equipamento';
        }
        if (vinculoEquipamentoDescricao) {
            vinculoEquipamentoDescricao.textContent = '';
        }
        if (vinculoEquipamentoFoto) {
            const fallback = vinculoEquipamentoFoto.dataset.placeholderSrc || '';
            vinculoEquipamentoFoto.src = fallback;
            vinculoEquipamentoFoto.classList.add('d-none');
        }
        toggleVinculoColumn(vinculoEquipamentoCol, !hideColumn);
        refreshVinculosVisualVisibility();
    };

    const renderVinculoOsCard = (item, options = {}) => {
        const totalAbertas = Number(options.totalAbertas || 0);
        const statusLabel = normalizeTitleChunk(item?.status_label || item?.status || '');
        if (osIdInput) {
            osIdInput.value = String(item?.os_id || item?.id || '').trim();
        }
        if (osNumeroHintInput) {
            osNumeroHintInput.value = normalizeTitleChunk(item?.numero || item?.numero_label || '');
        }
        if (vinculoOsTitulo) {
            vinculoOsTitulo.textContent = formatOsNumeroLabel(item?.numero_label || item?.numero || '');
        }
        if (vinculoOsStatus) {
            vinculoOsStatus.textContent = statusLabel ? `Status: ${statusLabel}` : '';
            vinculoOsStatus.classList.toggle('d-none', statusLabel === '');
        }
        if (vinculoOsCounter) {
            if (totalAbertas > 1) {
                vinculoOsCounter.textContent = `${totalAbertas} abertas`;
                vinculoOsCounter.classList.remove('d-none');
            } else {
                vinculoOsCounter.textContent = '';
                vinculoOsCounter.classList.add('d-none');
            }
        }
        toggleVinculoColumn(vinculoOsCol, true);
        refreshVinculosVisualVisibility();
    };

    const isPlaceholderLabel = (value) => {
        const normalized = normalizeTitleChunk(value).toLowerCase();
        if (!normalized) return true;
        return normalized.includes('selecione') || normalized.includes('nenhum') || normalized.includes('primeiro');
    };

    const resolveClienteTitulo = () => {
        const clienteSelecionado = selectedText(clienteLookupSelect);
        if (!isPlaceholderLabel(clienteSelecionado)) {
            return clienteSelecionado;
        }
        const clienteAvulso = normalizeTitleChunk(nomeAvulsoInput?.value || '');
        if (clienteAvulso !== '') {
            return clienteAvulso;
        }
        return 'Cliente';
    };

    const resolveOsTitulo = () => {
        const osNumero = normalizeTitleChunk(osNumeroHintInput?.value || '');
        if (osNumero !== '') {
            return osNumero.toUpperCase().startsWith('OS') ? osNumero : `OS ${osNumero}`;
        }
        const osId = normalizeTitleChunk(osIdInput?.value || '');
        if (osId !== '') {
            return `OS #${osId}`;
        }
        return 'OS';
    };

    const resolveEquipamentoTitulo = () => {
        const equipamentoHint = normalizeTitleChunk(equipamentoTituloHintInput?.value || '');
        if (equipamentoHint !== '') {
            return equipamentoHint;
        }

        const equipamentoSelecionado = selectedText(equipamentoLookupSelect);
        if (!isPlaceholderLabel(equipamentoSelecionado)) {
            return equipamentoSelecionado;
        }

        const partes = [
            selectedText(equipTipoSelect),
            selectedText(equipMarcaSelect),
            selectedText(equipModeloSelect),
        ].filter((parte) => !isPlaceholderLabel(parte));

        if (partes.length > 0) {
            return partes.join(' ');
        }

        const equipamentoId = normalizeTitleChunk(equipamentoIdInput?.value || '');
        if (equipamentoId !== '') {
            return `Equipamento #${equipamentoId}`;
        }

        return 'Equipamento';
    };

    const renderTituloTemplate = (templateRaw) => {
        const template = normalizeTitleChunk(templateRaw);
        if (template === '') return '';

        const context = {
            cliente: resolveClienteTitulo(),
            os: resolveOsTitulo(),
            equipamento: resolveEquipamentoTitulo(),
        };

        return template
            .replace(/\{\{cliente\}\}/gi, context.cliente)
            .replace(/\{\{os\}\}/gi, context.os)
            .replace(/\{\{equipamento\}\}/gi, context.equipamento)
            .replace(/\s+-\s+-/g, ' - ')
            .replace(/\s{2,}/g, ' ')
            .trim();
    };

    const setTituloValue = (value) => {
        if (!tituloInput) return;
        tituloInput.value = normalizeTitleChunk(value);
        tituloInput.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const appendTituloValue = (value) => {
        if (!tituloInput) return;
        const piece = normalizeTitleChunk(value);
        if (piece === '') return;
        const atual = normalizeTitleChunk(tituloInput.value || '');
        setTituloValue(atual === '' ? piece : `${atual} - ${piece}`);
    };

    tituloTemplateButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const template = button.getAttribute('data-titulo-template') || '';
            const tituloRenderizado = renderTituloTemplate(template);
            if (tituloRenderizado !== '') {
                setTituloValue(tituloRenderizado);
            }
        });
    });

    tituloInsertButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const key = normalizeTitleChunk(button.getAttribute('data-titulo-insert') || '').toLowerCase();
            if (key === 'cliente') {
                appendTituloValue(resolveClienteTitulo());
                return;
            }
            if (key === 'os') {
                appendTituloValue(resolveOsTitulo());
                return;
            }
            if (key === 'equipamento') {
                appendTituloValue(resolveEquipamentoTitulo());
            }
        });
    });

    let draftSaveTimer = null;
    let skipDraftPersistence = false;

    const hasDraftStorage = () => {
        try {
            return typeof window.localStorage !== 'undefined';
        } catch (error) {
            return false;
        }
    };

    const cssEscapeName = (value) => {
        if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
            return CSS.escape(value);
        }
        return String(value).replace(/["\\]/g, '\\$&');
    };

    const getSelectState = (selectEl) => {
        if (!selectEl) return null;
        const value = String(selectEl.value || '').trim();
        if (value === '') return null;
        const text = normalizeTitleChunk(selectEl.options[selectEl.selectedIndex]?.text || '');
        if (text === '') return null;
        return { value, text };
    };

    const setSelectState = (selectEl, state) => {
        if (!selectEl || !state || !state.value) return;
        const value = String(state.value);
        const text = normalizeTitleChunk(state.text || value);
        let option = Array.from(selectEl.options).find((opt) => String(opt.value) === value);
        if (!option) {
            option = new Option(text, value, true, true);
            selectEl.add(option);
        } else {
            option.selected = true;
        }
        selectEl.value = value;
        const jq = window.jQuery || window.$;
        if (jq && jq.fn && typeof jq.fn.select2 === 'function' && selectEl.classList.contains('select2-hidden-accessible')) {
            jq(selectEl).trigger('change');
            return;
        }
        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const setSelectValueOrFirst = (selectEl, preferredValue = '') => {
        if (!selectEl) return;
        const options = Array.from(selectEl.options || []);
        const normalizedPreferred = String(preferredValue ?? '').trim();
        if (normalizedPreferred !== '' && options.some((opt) => String(opt.value) === normalizedPreferred)) {
            selectEl.value = normalizedPreferred;
        } else if (options.some((opt) => String(opt.value) === '')) {
            selectEl.value = '';
        } else if (options.length > 0) {
            selectEl.value = String(options[0].value ?? '');
        } else {
            selectEl.value = '';
        }
        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const clearDraftStorage = () => {
        if (!hasDraftStorage()) return;
        window.localStorage.removeItem(DRAFT_STORAGE_KEY);
    };

    const readDraftStorage = () => {
        if (!hasDraftStorage()) return null;
        try {
            const raw = window.localStorage.getItem(DRAFT_STORAGE_KEY);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') return null;
            return parsed;
        } catch (error) {
            return null;
        }
    };

    const collectItemRowsSnapshot = () => {
        const rows = Array.from(tableBody?.querySelectorAll('.orc-item-row') || []);
        return rows.map((row) => ({
            tipo: String(row.querySelector('[name="item_tipo[]"]')?.value || ''),
            descricao: String(row.querySelector('[name="item_descricao[]"]')?.value || ''),
            referencia_id: String(row.querySelector('[name="item_referencia_id[]"]')?.value || ''),
            observacao: String(row.querySelector('[name="item_observacao[]"]')?.value || ''),
            quantidade: String(row.querySelector('[name="item_quantidade[]"]')?.value || ''),
            valor_unitario: String(row.querySelector('[name="item_valor_unitario[]"]')?.value || ''),
            desconto: String(row.querySelector('[name="item_desconto[]"]')?.value || ''),
            acrescimo: String(row.querySelector('[name="item_acrescimo[]"]')?.value || ''),
        }));
    };

    const rebuildItemRowsFromSnapshot = (rowsSnapshot) => {
        if (!tableBody) return;
        tableBody.querySelectorAll('.orc-item-row').forEach((row) => row.remove());

        const rows = Array.isArray(rowsSnapshot) ? rowsSnapshot.filter((row) => row && typeof row === 'object') : [];
        if (rows.length === 0) {
            addRow();
            return;
        }

        rows.forEach((rowData) => {
            addRow();
            const row = tableBody.querySelector('.orc-item-row:last-child');
            if (!row) return;
            const setValue = (selector, value) => {
                const input = row.querySelector(selector);
                if (!input) return;
                input.value = String(value ?? '');
            };
            setValue('[name="item_tipo[]"]', rowData.tipo ?? 'servico');
            setValue('[name="item_descricao[]"]', rowData.descricao ?? '');
            setValue('[name="item_referencia_id[]"]', rowData.referencia_id ?? '');
            setValue('[name="item_observacao[]"]', rowData.observacao ?? '');
            setValue('[name="item_quantidade[]"]', rowData.quantidade ?? '1');
            setValue('[name="item_valor_unitario[]"]', rowData.valor_unitario ?? '0.00');
            setValue('[name="item_desconto[]"]', rowData.desconto ?? '0.00');
            setValue('[name="item_acrescimo[]"]', rowData.acrescimo ?? '0.00');
            syncItemCatalogVisibility(row);
        });
    };

    const resetFormForClienteChange = () => {
        if (!formEl) return;

        if (clienteIdInput) clienteIdInput.value = '';
        if (contatoIdInput) contatoIdInput.value = '';
        if (osIdInput) osIdInput.value = '';
        if (osNumeroHintInput) osNumeroHintInput.value = '';
        if (equipamentoIdInput) equipamentoIdInput.value = '';
        if (conversaIdInput) conversaIdInput.value = '';

        if (nomeAvulsoInput) nomeAvulsoInput.value = '';
        if (telefoneInput) {
            telefoneInput.value = '';
            telefoneInput.setCustomValidity('');
        }
        if (emailInput) {
            emailInput.value = '';
            emailInput.setCustomValidity('');
        }
        if (tituloInput) tituloInput.value = '';

        setNomeAvulsoLocked(false);
        setRegistrarContatoVisibility(false, false);

        if (statusSelect) {
            statusSelect.value = DRAFT_STATUS_RASCUNHO;
            statusSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
        setSelectValueOrFirst(origemSelect, DEFAULT_ORIGEM);
        setSelectValueOrFirst(prazoExecucaoSelect, DEFAULT_PRAZO_EXECUCAO);

        if (validadeDiasInput) {
            validadeDiasInput.value = String(DEFAULT_VALIDADE_DIAS);
        }
        applyValidadeDias();

        if (descontoInput) descontoInput.value = fixed(0);
        if (acrescimoInput) acrescimoInput.value = fixed(0);

        if (condicoesInput) condicoesInput.value = '';
        if (observacoesInput) observacoesInput.value = '';
        if (motivoRejeicaoInput) motivoRejeicaoInput.value = '';

        rebuildItemRowsFromSnapshot([]);
        recalcAll();
        syncStatusFields();

        if (vinculosVisual) {
            clearVinculoOsCard();
            clearVinculoEquipamentoCard();
            toggleVinculoColumn(vinculoConversaCol, false);
            refreshVinculosVisualVisibility();
        }
    };

    const collectFieldValuesSnapshot = () => {
        const payload = {};
        if (!formEl) return payload;

        const formData = new FormData(formEl);
        formData.forEach((value, key) => {
            if (key === csrfTokenName || key === 'item_total[]') {
                return;
            }
            if (!Array.isArray(payload[key])) {
                payload[key] = [];
            }
            payload[key].push(String(value ?? ''));
        });

        return payload;
    };

    const applyFieldValuesSnapshot = (fieldValues) => {
        if (!formEl || !fieldValues || typeof fieldValues !== 'object') return;

        Object.entries(fieldValues).forEach(([name, valuesRaw]) => {
            if (name.startsWith('item_') || name === csrfTokenName || name === 'item_total[]') {
                return;
            }

            const values = Array.isArray(valuesRaw) ? valuesRaw : [valuesRaw];
            const elements = formEl.querySelectorAll(`[name="${cssEscapeName(name)}"]`);
            if (!elements.length) {
                return;
            }

            const first = elements[0];
            if (first.type === 'checkbox') {
                const enabled = values.some((value) => ['1', 'true', 'on'].includes(String(value).toLowerCase()));
                elements.forEach((checkbox) => {
                    checkbox.checked = enabled;
                });
                return;
            }

            if (first.type === 'radio') {
                elements.forEach((radio) => {
                    radio.checked = values.includes(radio.value);
                });
                return;
            }

            elements.forEach((element, index) => {
                const nextValue = values[index] ?? values[0] ?? '';
                element.value = String(nextValue ?? '');
            });
        });
    };

    const collectDraftSnapshot = () => {
        if (!isCreateMode || !formEl) return null;
        if (String(statusSelect?.value || DRAFT_STATUS_RASCUNHO) !== DRAFT_STATUS_RASCUNHO) {
            return null;
        }

        return {
            version: 1,
            updated_at: new Date().toISOString(),
            status: DRAFT_STATUS_RASCUNHO,
            field_values: collectFieldValuesSnapshot(),
            items: collectItemRowsSnapshot(),
            cliente_lookup: getSelectState(clienteLookupSelect),
            equipamento_lookup: getSelectState(equipamentoLookupSelect),
        };
    };

    const persistDraftSnapshot = () => {
        if (skipDraftPersistence) return;
        if (!hasDraftStorage()) return;
        if (!isCreateMode) return;

        const snapshot = collectDraftSnapshot();
        if (!snapshot) {
            clearDraftStorage();
            return;
        }

        try {
            window.localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(snapshot));
        } catch (error) {
            // ignore quota/storage errors
        }
    };

    const queueDraftPersistence = () => {
        if (skipDraftPersistence || !isCreateMode) return;
        if (draftSaveTimer) {
            window.clearTimeout(draftSaveTimer);
        }
        draftSaveTimer = window.setTimeout(() => {
            persistDraftSnapshot();
        }, 320);
    };

    const hideDraftAlert = () => {
        if (!draftRecoverBar) return;
        draftRecoverBar.classList.add('d-none');
    };

    const formatDraftSavedAt = (value) => {
        if (!value) return '';
        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) return '';
        return parsed.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
    };

    const showDraftAlert = (snapshot) => {
        if (!draftRecoverBar) return;
        draftRecoverBar.classList.remove('d-none');
        if (draftSavedAtLabel) {
            const label = formatDraftSavedAt(snapshot?.updated_at || '');
            draftSavedAtLabel.textContent = label ? `Salvo em ${label}.` : '';
        }
    };

    const hasSnapshotFieldValue = (fieldValues, fieldName) => {
        if (!fieldValues || !fieldName) return false;
        const raw = fieldValues[fieldName];
        if (Array.isArray(raw)) {
            return raw.some((value) => String(value ?? '').trim() !== '');
        }
        return String(raw ?? '').trim() !== '';
    };

    const hasDraftClienteOuEquipamento = (snapshot) => {
        if (!snapshot || typeof snapshot !== 'object') return false;
        if (String(snapshot?.cliente_lookup?.value || '').trim() !== '') {
            return true;
        }
        const fieldValues = snapshot.field_values || {};
        const clienteFields = ['cliente_id', 'contato_id', 'cliente_nome_avulso', 'telefone_contato', 'email_contato'];
        if (clienteFields.some((field) => hasSnapshotFieldValue(fieldValues, field))) {
            return true;
        }

        if (String(snapshot?.equipamento_lookup?.value || '').trim() !== '') {
            return true;
        }
        const equipamentoFields = [
            'equipamento_id',
            'equipamento_tipo_id',
            'equipamento_marca_id',
            'equipamento_modelo_id',
            'equipamento_cor',
            'equipamento_cor_hex',
            'equipamento_cor_rgb',
        ];
        return equipamentoFields.some((field) => hasSnapshotFieldValue(fieldValues, field));
    };

    const restoreDraftSnapshot = async (snapshot) => {
        if (!snapshot || typeof snapshot !== 'object' || !formEl) return;

        skipDraftPersistence = true;
        try {
            applyFieldValuesSnapshot(snapshot.field_values || {});
            rebuildItemRowsFromSnapshot(snapshot.items || []);
            setSelectState(clienteLookupSelect, snapshot.cliente_lookup || null);
            setSelectState(equipamentoLookupSelect, snapshot.equipamento_lookup || null);

            const tipoId = String((snapshot.field_values?.equipamento_tipo_id?.[0] ?? '') || '');
            const marcaId = String((snapshot.field_values?.equipamento_marca_id?.[0] ?? '') || '');
            const modeloId = String((snapshot.field_values?.equipamento_modelo_id?.[0] ?? '') || '');

            if (equipTipoSelect && tipoId !== '') {
                equipTipoSelect.value = tipoId;
                equipTipoSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            window.setTimeout(() => {
                if (equipMarcaSelect && marcaId !== '') {
                    equipMarcaSelect.value = marcaId;
                    equipMarcaSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }, 90);

            window.setTimeout(() => {
                if (equipModeloSelect && modeloId !== '') {
                    equipModeloSelect.value = modeloId;
                    equipModeloSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }, 180);

            if (equipCorPicker) {
                equipCorPicker.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (equipCorNomeInput) {
                equipCorNomeInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            applyValidadeDias();
            recalcAll();
            syncStatusFields();
            syncTelefoneValidation();
            syncEmailValidation();

            hideDraftAlert();
        } finally {
            skipDraftPersistence = false;
            queueDraftPersistence();
        }
    };

    const promptDraftRecovery = async () => {
        if (!isCreateMode) return;
        const snapshot = readDraftStorage();
        if (!snapshot || String(snapshot.status || '') !== DRAFT_STATUS_RASCUNHO) {
            clearDraftStorage();
            hideDraftAlert();
            return;
        }
        if (!hasDraftClienteOuEquipamento(snapshot)) {
            clearDraftStorage();
            hideDraftAlert();
            return;
        }

        showDraftAlert(snapshot);

        if (btnDraftRestore) {
            btnDraftRestore.onclick = async () => {
                hideDraftAlert();
                await restoreDraftSnapshot(snapshot);
            };
        }

        if (btnDraftDiscard) {
            btnDraftDiscard.onclick = () => {
                clearDraftStorage();
                hideDraftAlert();
                if (draftSavedAtLabel) {
                    draftSavedAtLabel.textContent = '';
                }
            };
        }
    };

    if (formEl && isCreateMode) {
        formEl.addEventListener('input', queueDraftPersistence, true);
        formEl.addEventListener('change', queueDraftPersistence, true);
        window.addEventListener('beforeunload', persistDraftSnapshot);
    }

    const decodeBase64 = (value) => {
        try {
            return window.atob(value || '');
        } catch (error) {
            return '';
        }
    };

    const nomeAvulsoPlaceholderDefault = 'Preencher apenas para cliente sem cadastro';
    const nomeAvulsoPlaceholderLocked = 'Remova o cliente cadastrado para editar este campo';

    const setNomeAvulsoLocked = (locked) => {
        if (!nomeAvulsoInput) return;
        nomeAvulsoInput.readOnly = locked;
        nomeAvulsoInput.classList.toggle('bg-light', locked);
        nomeAvulsoInput.classList.toggle('text-muted', locked);
        nomeAvulsoInput.placeholder = locked ? nomeAvulsoPlaceholderLocked : nomeAvulsoPlaceholderDefault;
    };

    const normalizeWhatsappDigits = (rawValue) => {
        let digits = String(rawValue ?? '').replace(/\D+/g, '');
        if (digits.startsWith('55') && digits.length > 11) {
            digits = digits.slice(2);
        }
        if (digits.length > 11) {
            digits = digits.slice(0, 11);
        }
        return digits;
    };

    const isWhatsappMobileValid = (rawValue) => /^[1-9]{2}9\d{8}$/.test(normalizeWhatsappDigits(rawValue));

    const formatWhatsappPhone = (rawValue) => {
        const digits = normalizeWhatsappDigits(rawValue);
        if (digits.length <= 2) return digits;
        const ddd = digits.slice(0, 2);
        const numero = digits.slice(2);
        if (numero.length <= 5) {
            return `(${ddd}) ${numero}`;
        }
        return `(${ddd}) ${numero.slice(0, 5)}-${numero.slice(5, 9)}`;
    };

    const syncTelefoneValidation = () => {
        if (!telefoneInput) return '';
        const normalized = normalizeWhatsappDigits(telefoneInput.value);
        const masked = formatWhatsappPhone(normalized);
        telefoneInput.value = masked;

        if (normalized === '') {
            telefoneInput.setCustomValidity('Informe um telefone celular com DDD para WhatsApp.');
            return normalized;
        }
        if (!/^[1-9]{2}9\d{8}$/.test(normalized)) {
            telefoneInput.setCustomValidity('Use um celular WhatsApp valido com DDD. Ex.: (11) 98765-4321.');
            return normalized;
        }
        telefoneInput.setCustomValidity('');
        return normalized;
    };

    const isEmailValid = (emailValue) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(String(emailValue ?? '').trim());

    const syncEmailValidation = () => {
        if (!emailInput) return '';
        const trimmed = String(emailInput.value || '').trim();
        emailInput.value = trimmed;
        if (trimmed === '') {
            emailInput.setCustomValidity('');
            return trimmed;
        }
        if (!isEmailValid(trimmed)) {
            emailInput.setCustomValidity('Informe um e-mail de contato v\u00e1lido para envio do or\u00e7amento.');
            return trimmed;
        }
        emailInput.setCustomValidity('');
        return trimmed;
    };

    const hasOrcamentoOsContext = () => {
        const osRaw = String(osIdInput?.value || '').trim();
        const osId = parseInt(osRaw, 10);
        if (Number.isFinite(osId) && osId > 0) {
            return true;
        }
        const origem = String(origemSelect?.value || '').trim().toLowerCase();
        return origem === 'os';
    };

    const syncPacoteOfertaPhoneFromContato = (force = false) => {
        const contatoDigits = normalizeWhatsappDigits(String(telefoneInput?.value || ''));
        if (!pacoteOfertaTelefoneInput) {
            return contatoDigits;
        }

        if (force || !hasOrcamentoOsContext()) {
            pacoteOfertaTelefoneInput.value = formatWhatsappPhone(contatoDigits);
            return contatoDigits;
        }

        const ofertaDigits = normalizeWhatsappDigits(String(pacoteOfertaTelefoneInput.value || ''));
        if (ofertaDigits === '' && contatoDigits !== '') {
            pacoteOfertaTelefoneInput.value = formatWhatsappPhone(contatoDigits);
            return contatoDigits;
        }

        pacoteOfertaTelefoneInput.value = formatWhatsappPhone(ofertaDigits);
        return ofertaDigits || contatoDigits;
    };

    const syncPacoteOfertaMensagemOption = () => {
        if (!pacoteOfertaMensagemInput || !pacoteOfertaMensagemLinkWrap || !pacoteOfertaMensagemComLinkCheckbox) {
            return;
        }
        const hasCustomMessage = String(pacoteOfertaMensagemInput.value || '').trim() !== '';
        pacoteOfertaMensagemLinkWrap.classList.toggle('d-none', !hasCustomMessage);
        pacoteOfertaMensagemComLinkCheckbox.disabled = !hasCustomMessage;
        if (!hasCustomMessage) {
            pacoteOfertaMensagemComLinkCheckbox.checked = true;
        }
    };

    const clearAutoFilledClienteData = () => {
        if (clienteIdInput) clienteIdInput.value = '';
        if (contatoIdInput) contatoIdInput.value = '';
        if (nomeAvulsoInput) nomeAvulsoInput.value = '';
        if (telefoneInput) {
            telefoneInput.value = '';
            telefoneInput.setCustomValidity('');
        }
        if (emailInput) {
            emailInput.value = '';
            emailInput.setCustomValidity('');
        }
        if (pacoteOfertaTelefoneInput) {
            pacoteOfertaTelefoneInput.value = '';
        }
        clearPacoteOfertaState();
    };

    const setRegistrarContatoVisibility = (visible, checked = false) => {
        if (!registrarContatoWrap || !registrarContatoCheckbox) return;
        if (visible) {
            registrarContatoWrap.classList.remove('d-none');
            registrarContatoCheckbox.checked = checked;
            return;
        }
        registrarContatoWrap.classList.add('d-none');
        registrarContatoCheckbox.checked = false;
    };

    const focusPrimeiroCampoEquipamento = () => {
        const tryFocus = () => {
            if (!equipamentoManualFieldsWrap || equipamentoManualFieldsWrap.classList.contains('d-none')) {
                return false;
            }

            const firstField = [
                equipTipoSelect,
                equipMarcaSelect,
                equipModeloSelect,
                equipCorNomeInput,
            ].find((field) => field && !field.disabled);

            if (!firstField) {
                return false;
            }

            firstField.focus();
            return true;
        };

        if (tryFocus()) {
            return;
        }

        window.requestAnimationFrame(() => {
            tryFocus();
        });
    };

    const bindClienteProgressiveEnterFlow = (field, onAdvance) => {
        if (!field) {
            return;
        }

        field.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) {
                return;
            }

            event.preventDefault();

            if (typeof onAdvance === 'function') {
                onAdvance();
            }
        });
    };

    const notifyClienteSelectionChanged = () => {
        document.dispatchEvent(new CustomEvent('orcamento:cliente-change', {
            detail: {
                clienteId: String(clienteIdInput?.value || '').trim(),
                contatoId: String(contatoIdInput?.value || '').trim(),
            },
        }));
    };

    let currentClienteLookupKey = String(clienteIdInput?.value || '').trim();
    const resolveClienteLookupKey = (item) => {
        if (item) {
            const fromItem = String(item?.cliente_id || '').trim();
            if (fromItem !== '') return fromItem;
            return '';
        }
        return String(clienteIdInput?.value || '').trim();
    };

    const handleClienteLookupChange = (nextKey, options = {}) => {
        const next = String(nextKey || '').trim();
        const skipReset = Boolean(options.skipReset);
        if (skipReset) {
            currentClienteLookupKey = next;
            return;
        }
        if (next === currentClienteLookupKey) return;
        resetFormForClienteChange();
        currentClienteLookupKey = next;
    };

    const applyLookupSelection = (item, options = {}) => {
        const nextKey = resolveClienteLookupKey(item);
        handleClienteLookupChange(nextKey, options);
        const tipo = String(item?.tipo || '');
        const clienteId = item?.cliente_id ? String(item.cliente_id) : '';
        const contatoId = item?.contato_id ? String(item.contato_id) : '';
        const nome = String(item?.nome || '');
        const telefone = String(item?.telefone || '');
        const email = String(item?.email || '');

        if (clienteIdInput) clienteIdInput.value = clienteId;
        if (contatoIdInput) contatoIdInput.value = contatoId;

        if (tipo === 'cliente') {
            if (nomeAvulsoInput) nomeAvulsoInput.value = '';
            if (telefoneInput) telefoneInput.value = telefone;
            if (emailInput) emailInput.value = email;
            syncTelefoneValidation();
            syncPacoteOfertaPhoneFromContato();
            syncEmailValidation();
            setNomeAvulsoLocked(true);
            setRegistrarContatoVisibility(false);
            notifyClienteSelectionChanged();
            return;
        }

        if (tipo === 'contato') {
            if (nomeAvulsoInput && nome !== '') nomeAvulsoInput.value = nome;
            if (telefoneInput) telefoneInput.value = telefone;
            if (emailInput) emailInput.value = email;
            syncTelefoneValidation();
            syncPacoteOfertaPhoneFromContato();
            syncEmailValidation();
            setNomeAvulsoLocked(true);
            setRegistrarContatoVisibility(false);
            notifyClienteSelectionChanged();
            return;
        }

        if (tipo === 'novo_contato') {
            if (clienteIdInput) clienteIdInput.value = '';
            if (contatoIdInput) contatoIdInput.value = '';
            const encoded = String(item?.id || '').split(':')[1] || '';
            const termo = decodeBase64(encoded).trim();
            const telefoneGuess = termo.replace(/\D+/g, '');
            if (telefoneInput && telefoneGuess.length >= 8) {
                telefoneInput.value = termo;
            } else if (nomeAvulsoInput && termo !== '') {
                nomeAvulsoInput.value = termo;
            }
            syncTelefoneValidation();
            syncPacoteOfertaPhoneFromContato();
            syncEmailValidation();
            setNomeAvulsoLocked(false);
            setRegistrarContatoVisibility(true, true);
            notifyClienteSelectionChanged();
            return;
        }

        if (nomeAvulsoInput && clienteId === '') {
            nomeAvulsoInput.value = nomeAvulsoInput.value || nome;
        }
        setNomeAvulsoLocked(clienteId !== '' || contatoId !== '');
        syncTelefoneValidation();
        syncPacoteOfertaPhoneFromContato();
        syncEmailValidation();
        setRegistrarContatoVisibility(clienteId === '' && contatoId === '', false);
        notifyClienteSelectionChanged();
    };

    const escapeHtml = (raw) => String(raw ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    let pacoteOfertaDetectTimer = null;
    let pacoteOfertaRequestToken = 0;

    const formatCurrencyBr = (value) => {
        const parsed = Number(value ?? 0);
        const safeValue = Number.isFinite(parsed) ? parsed : 0;
        return safeValue.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    const formatDateTimeBr = (value) => {
        const raw = String(value ?? '').trim();
        if (!raw) return '';
        const parsed = new Date(raw.replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return raw;
        return parsed.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
    };

    const syncPacoteOfertaApplyHidden = () => {
        if (!pacoteOfertaApplyFlagInput) return;
        const canApply = !pacoteOfertaApplyCheck?.disabled && !!pacoteOfertaApplyCheck?.checked;
        pacoteOfertaApplyFlagInput.value = canApply ? '1' : '0';
    };

    const clearPacoteOfertaState = () => {
        if (pacoteOfertaApplyIdInput) pacoteOfertaApplyIdInput.value = '';
        if (pacoteOfertaApplyFlagInput) pacoteOfertaApplyFlagInput.value = '0';
        if (pacoteOfertaApplyCheck) {
            pacoteOfertaApplyCheck.checked = false;
            pacoteOfertaApplyCheck.disabled = true;
        }
        if (pacoteOfertaApplyWrap) pacoteOfertaApplyWrap.classList.add('d-none');
        if (pacoteOfertaLinkBtn) pacoteOfertaLinkBtn.href = '#';
        if (pacoteOfertaLinkWrap) pacoteOfertaLinkWrap.classList.add('d-none');
        if (pacoteOfertaStatusWrap) pacoteOfertaStatusWrap.classList.add('d-none');
        if (pacoteOfertaStatusTitle) pacoteOfertaStatusTitle.textContent = 'Nenhuma oferta identificada';
        if (pacoteOfertaStatusDesc) pacoteOfertaStatusDesc.textContent = '';
        if (pacoteOfertaStatusBadge) pacoteOfertaStatusBadge.textContent = '-';
        if (pacoteOfertaDetalhes) pacoteOfertaDetalhes.textContent = '';
        setPacoteOfertaSendLabel(false);
    };

    const renderPacoteOfertaState = (oferta, feedbackMessage = '') => {
        if (!pacoteOfertaModuleReady || !pacoteOfertaStatusWrap) {
            return;
        }

        if (!oferta || !oferta.id) {
            clearPacoteOfertaState();
            return;
        }

        const status = String(oferta.status || 'ativo').trim();
        const statusLabel = String(oferta.status_label || pacoteOfertaStatusLabels[status] || status || 'Ativo');
        const pacoteNome = String(oferta.pacote_nome || 'Pacote de servicos').trim();
        const pacoteId = String(oferta.pacote_servico_id || '').trim();
        const nivelNome = String(oferta.nivel_nome_exibicao || oferta.nivel_escolhido || '').trim();
        const valor = Number(oferta.valor_escolhido || 0);
        const expiraEm = formatDateTimeBr(oferta.expira_em || '');
        const enviadoEm = formatDateTimeBr(oferta.enviado_em || '');
        const escolhidoEm = formatDateTimeBr(oferta.escolhido_em || '');
        const aplicadoEm = formatDateTimeBr(oferta.aplicado_em || '');

        if (pacoteOfertaStatusTitle) {
            pacoteOfertaStatusTitle.textContent = pacoteNome;
        }
        if (pacoteOfertaPacoteSelect && pacoteId) {
            const hasOption = Array.from(pacoteOfertaPacoteSelect.options).some((opt) => String(opt.value || '') === pacoteId);
            if (hasOption) {
                pacoteOfertaPacoteSelect.value = pacoteId;
            }
        }
        if (pacoteOfertaTelefoneInput) {
            const telefoneOferta = normalizeWhatsappDigits(String(oferta.telefone_destino || ''));
            if (telefoneOferta) {
                pacoteOfertaTelefoneInput.value = formatWhatsappPhone(telefoneOferta);
            }
        }
        if (!hasOrcamentoOsContext()) {
            syncPacoteOfertaPhoneFromContato(true);
        }
        if (pacoteOfertaStatusDesc) {
            pacoteOfertaStatusDesc.textContent = feedbackMessage || 'Oferta detectada automaticamente por identidade/contexto.';
        }
        if (pacoteOfertaStatusBadge) {
            pacoteOfertaStatusBadge.textContent = statusLabel;
            pacoteOfertaStatusBadge.className = 'badge text-bg-secondary';
            if (status === 'escolhido') pacoteOfertaStatusBadge.className = 'badge text-bg-success';
            if (status === 'aplicado_orcamento') pacoteOfertaStatusBadge.className = 'badge text-bg-primary';
            if (status === 'erro_envio' || status === 'cancelado') pacoteOfertaStatusBadge.className = 'badge text-bg-danger';
            if (status === 'expirado') pacoteOfertaStatusBadge.className = 'badge text-bg-warning';
        }
        const shouldResend = ['ativo', 'enviado', 'expirado', 'erro_envio'].includes(status);
        setPacoteOfertaSendLabel(shouldResend);

        const details = [];
        if (nivelNome) details.push(`Nivel escolhido: ${nivelNome}`);
        if (Number.isFinite(valor) && valor > 0) details.push(`Valor: ${formatCurrencyBr(valor)}`);
        if (expiraEm) details.push(`Expira: ${expiraEm}`);
        if (enviadoEm) details.push(`Enviado: ${enviadoEm}`);
        if (escolhidoEm) details.push(`Escolhido: ${escolhidoEm}`);
        if (aplicadoEm) details.push(`Aplicado: ${aplicadoEm}`);
        const identityWarning = String(oferta.identity_warning || '').trim();
        if (identityWarning !== '') details.push(`Alerta: ${identityWarning}`);
        if (pacoteOfertaDetalhes) {
            pacoteOfertaDetalhes.textContent = details.join(' | ');
        }

        if (pacoteOfertaApplyIdInput) {
            pacoteOfertaApplyIdInput.value = String(oferta.id || '');
        }
        const canApply = !!oferta.can_apply && status === 'escolhido';
        if (pacoteOfertaApplyWrap) {
            pacoteOfertaApplyWrap.classList.toggle('d-none', !canApply);
        }
        if (pacoteOfertaApplyCheck) {
            pacoteOfertaApplyCheck.checked = canApply;
            pacoteOfertaApplyCheck.disabled = !canApply;
        }
        syncPacoteOfertaApplyHidden();

        const publicLink = String(oferta.link_publico || '').trim();
        const hasLink = publicLink !== '';
        if (pacoteOfertaLinkWrap) {
            pacoteOfertaLinkWrap.classList.toggle('d-none', !hasLink);
        }
        if (pacoteOfertaLinkBtn) {
            pacoteOfertaLinkBtn.href = hasLink ? publicLink : '#';
        }

        pacoteOfertaStatusWrap.classList.remove('d-none');
    };

    const buildPacoteOfertaNomeReferencia = () => {
        const nomeEventual = String(nomeAvulsoInput?.value || '').trim();
        if (nomeEventual !== '') {
            return nomeEventual;
        }

        if (clienteLookupSelect && clienteLookupSelect.selectedIndex >= 0) {
            const selectedOption = clienteLookupSelect.options[clienteLookupSelect.selectedIndex];
            const selectedText = String(selectedOption?.text || '').trim();
            if (selectedText !== '') {
                return selectedText.split('|')[0].trim();
            }
        }

        return '';
    };

    const buildPacoteOfertaIdentity = () => {
        const clienteId = String(clienteIdInput?.value || '').trim();
        const contatoId = String(contatoIdInput?.value || '').trim();
        const osId = String(osIdInput?.value || '').trim();
        const equipamentoId = String(equipamentoIdInput?.value || '').trim();
        const telefoneContato = normalizeWhatsappDigits(String(telefoneInput?.value || ''));
        const telefoneOferta = normalizeWhatsappDigits(String(pacoteOfertaTelefoneInput?.value || ''));
        const telefone = hasOrcamentoOsContext()
            ? (telefoneOferta || telefoneContato)
            : (telefoneContato || telefoneOferta);
        const nomeReferencia = buildPacoteOfertaNomeReferencia();
        return {
            cliente_id: clienteId,
            contato_id: contatoId,
            os_id: osId,
            equipamento_id: equipamentoId,
            orcamento_id: currentOrcamentoId > 0 ? String(currentOrcamentoId) : '',
            telefone,
            nome_referencia: nomeReferencia,
        };
    };

    const detectPacoteOferta = async () => {
        if (!pacoteOfertaModuleReady) return;
        const identity = buildPacoteOfertaIdentity();
        if (!identity.cliente_id && !identity.contato_id && !identity.telefone && !identity.os_id && !identity.equipamento_id && !identity.orcamento_id) {
            clearPacoteOfertaState();
            return;
        }

        const requestToken = ++pacoteOfertaRequestToken;
        const params = new URLSearchParams();
        if (identity.cliente_id) params.set('cliente_id', identity.cliente_id);
        if (identity.contato_id) params.set('contato_id', identity.contato_id);
        if (identity.os_id) params.set('os_id', identity.os_id);
        if (identity.equipamento_id) params.set('equipamento_id', identity.equipamento_id);
        if (identity.orcamento_id) params.set('orcamento_id', identity.orcamento_id);
        if (identity.telefone) params.set('telefone', identity.telefone);
        if (identity.nome_referencia) params.set('nome_referencia', identity.nome_referencia);

        try {
            const response = await fetch(`${pacoteOfertaDetectUrl}?${params.toString()}`, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) return;
            const payload = await response.json();
            if (requestToken !== pacoteOfertaRequestToken) return;
            renderPacoteOfertaState(payload?.oferta || null);
        } catch (error) {
            console.error('[Orçamentos] Falha ao detectar oferta dinamica de pacote.', error);
        }
    };

    const scheduleDetectPacoteOferta = () => {
        if (!pacoteOfertaModuleReady) return;
        if (pacoteOfertaDetectTimer) {
            window.clearTimeout(pacoteOfertaDetectTimer);
        }
        pacoteOfertaDetectTimer = window.setTimeout(() => {
            detectPacoteOferta();
        }, 350);
    };

    const sendPacoteOferta = async () => {
        if (!pacoteOfertaModuleReady || !btnPacoteOfertaEnviar) {
            return;
        }

        const pacoteId = String(pacoteOfertaPacoteSelect?.value || '').trim();
        if (!pacoteId) {
            if (window.Swal) {
                await window.Swal.fire('Selecione um pacote', 'Escolha o pacote antes de enviar a oferta.', 'warning');
            }
            return;
        }

        const telefoneContato = normalizeWhatsappDigits(String(telefoneInput?.value || ''));
        const telefoneOferta = normalizeWhatsappDigits(String(pacoteOfertaTelefoneInput?.value || ''));
        const telefoneDigits = hasOrcamentoOsContext()
            ? (telefoneOferta || telefoneContato)
            : (telefoneContato || telefoneOferta);
        if (!isWhatsappMobileValid(telefoneDigits)) {
            if (window.Swal) {
                await window.Swal.fire('Telefone invalido', 'Informe um celular WhatsApp valido com DDD para envio da oferta.', 'warning');
            }
            return;
        }

        const mensagemPersonalizada = String(pacoteOfertaMensagemInput?.value || '').trim();
        const incluirLinkMensagemPersonalizada = mensagemPersonalizada === ''
            ? true
            : !!pacoteOfertaMensagemComLinkCheckbox?.checked;

        const formData = new FormData();
        formData.append('pacote_servico_id', pacoteId);
        formData.append('cliente_id', String(clienteIdInput?.value || '').trim());
        formData.append('contato_id', String(contatoIdInput?.value || '').trim());
        formData.append('os_id', String(osIdInput?.value || '').trim());
        formData.append('equipamento_id', String(equipamentoIdInput?.value || '').trim());
        formData.append('origem_contexto', String(origemSelect?.value || 'manual').trim() || 'manual');
        formData.append('telefone_contato', telefoneDigits);
        formData.append('expira_dias', String(pacoteOfertaExpiraSelect?.value || '10'));
        formData.append('mensagem_pacote', mensagemPersonalizada);
        formData.append('mensagem_personalizada_com_link', incluirLinkMensagemPersonalizada ? '1' : '0');
        formData.append('enviar_whatsapp', pacoteOfertaEnviarWhatsappCheckbox?.checked ? '1' : '0');
        formData.append('itens_snapshot', JSON.stringify(collectItemRowsSnapshot()));
        formData.append(csrfTokenName, csrfHashValue);

        btnPacoteOfertaEnviar.disabled = true;
        try {
            const response = await fetch(pacoteOfertaSendUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });
            const payload = await response.json();
            if (!response.ok || !payload?.ok) {
                const message = String(payload?.message || 'Não foi possível enviar a oferta de pacote.');
                await window.DSFeedback.error('Falha no envio', message);
                return;
            }

            if (pacoteOfertaTelefoneInput) {
                pacoteOfertaTelefoneInput.value = formatWhatsappPhone(telefoneDigits);
            }
            renderPacoteOfertaState(payload?.oferta || null, String(payload?.message || 'Oferta criada com sucesso.'));
            if (window.Swal) {
                await window.Swal.fire({
                    icon: payload?.warning ? 'warning' : 'success',
                    title: payload?.warning ? 'Oferta criada com alerta' : 'Oferta enviada',
                    text: String(payload?.message || 'Oferta processada com sucesso.'),
                });
            }
        } catch (error) {
            console.error('[Orçamentos] Falha ao enviar oferta dinamica de pacote.', error);
            if (window.Swal) {
                await window.Swal.fire('Erro de comunicação', 'Não foi possível concluir o envio da oferta.', 'error');
            }
        } finally {
            btnPacoteOfertaEnviar.disabled = false;
            scheduleDetectPacoteOferta();
        }
    };

    const initClienteLookup = () => {
        const jq = window.jQuery;
        if (!jq || !jq.fn || typeof jq.fn.select2 !== 'function' || !clienteLookupSelect) {
            setRegistrarContatoVisibility((clienteIdInput?.value || '') === '' && (contatoIdInput?.value || '') === '', false);
            currentClienteLookupKey = resolveClienteLookupKey(null);
            return;
        }

        const renderResult = (item) => {
            if (item.loading) return escapeHtml(item.text || '');
            const tipo = String(item?.tipo || '');
            const label = tipo === 'cliente' ? 'CLIENTE' : (tipo === 'contato' ? 'CONTATO' : (tipo === 'novo_contato' ? 'NOVO' : 'ITEM'));
            const badgeClass = tipo === 'cliente' ? 'bg-primary-subtle text-primary' : (tipo === 'contato' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning');
            const telefone = item?.telefone ? ` | ${escapeHtml(item.telefone)}` : '';
            const email = item?.email ? ` | ${escapeHtml(item.email)}` : '';
            return `
                <div class="d-flex flex-column">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge ${badgeClass}">${label}</span>
                        <span>${escapeHtml(item.text || '')}</span>
                    </div>
                    ${(telefone || email) ? `<small class="text-muted">${telefone}${email}</small>` : ''}
                </div>
            `;
        };

        const renderSelection = (item) => {
            if (!item) return '';
            const text = item.text || '';
            return escapeHtml(text);
        };

        const $lookup = jq(clienteLookupSelect);
        $lookup.select2({
            width: '100%',
            allowClear: true,
            minimumInputLength: 0,
            placeholder: 'Buscar por nome, telefone ou e-mail...',
            ajax: {
                url: clienteLookupUrl,
                dataType: 'json',
                delay: 250,
                data: (params) => ({
                    q: params.term || '',
                    page: params.page || 1,
                }),
                processResults: (response) => ({
                    results: Array.isArray(response?.results) ? response.results : [],
                    pagination: response?.pagination || { more: false },
                }),
            },
            templateResult: renderResult,
            templateSelection: renderSelection,
            escapeMarkup: (markup) => markup,
        });

        $lookup.on('select2:select', (event) => {
            applyLookupSelection(event?.params?.data || null);
        });

        $lookup.on('select2:clear', () => {
            handleClienteLookupChange('', {});
            clearAutoFilledClienteData();
            setNomeAvulsoLocked(false);
            setRegistrarContatoVisibility(true, false);
            notifyClienteSelectionChanged();
        });

        if (clienteLookupInitial && clienteLookupInitial.id) {
            const option = new Option(clienteLookupInitial.text || '', clienteLookupInitial.id, true, true);
            $lookup.append(option).trigger('change');
            applyLookupSelection(clienteLookupInitial, { skipReset: true });
        } else {
            setNomeAvulsoLocked(false);
            setRegistrarContatoVisibility((clienteIdInput?.value || '') === '' && (contatoIdInput?.value || '') === '', false);
            syncTelefoneValidation();
            syncEmailValidation();
            notifyClienteSelectionChanged();
            currentClienteLookupKey = resolveClienteLookupKey(null);
        }
    };

    const initEquipamentoCatalogEditor = () => {
        if (!equipTipoSelect || !equipMarcaSelect || !equipModeloSelect) {
            return;
        }

        const normalizeId = (value) => String(value ?? '').trim();
        const normalizeName = (value) => String(value ?? '').trim();
        const asArray = (value) => (Array.isArray(value) ? value : []);
        const asObject = (value) => (value && typeof value === 'object' ? value : {});
        const sortByNome = (list) => list.sort((a, b) => normalizeName(a.nome).localeCompare(normalizeName(b.nome), 'pt-BR', { sensitivity: 'base' }));

        const normalizeSimpleList = (list) => {
            const out = [];
            asArray(list).forEach((item) => {
                const id = normalizeId(item?.id);
                const nome = normalizeName(item?.nome);
                if (!id || !nome) return;
                out.push({ id, nome });
            });
            return sortByNome(out);
        };

        const normalizeModelList = (list) => {
            const out = [];
            asArray(list).forEach((item) => {
                const id = normalizeId(item?.id);
                const marcaId = normalizeId(item?.marca_id);
                const nome = normalizeName(item?.nome);
                if (!id || !nome) return;
                out.push({ id, marca_id: marcaId, nome });
            });
            return sortByNome(out);
        };

        const jq = window.jQuery;
        const hasSelect2 = Boolean(jq && jq.fn && typeof jq.fn.select2 === 'function');

        const initCatalogSelect2 = (select, placeholderText) => {
            if (!hasSelect2 || !select) return;
            const $select = jq(select);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.off('.orcEquipCatalog');
                $select.select2('destroy');
            }

            const modalParent = $select.closest('.modal');
            const select2Options = {
                width: '100%',
                allowClear: true,
                minimumResultsForSearch: 0,
                placeholder: placeholderText || 'Selecione...',
            };
            if (modalParent.length > 0) {
                select2Options.dropdownParent = modalParent;
            }

            $select.select2(select2Options);
            $select.trigger('change.select2');
        };

        const catalogState = {
            marcasAll: normalizeSimpleList(equipamentoCatalog?.marcasAll),
            marcasByTipo: {},
            modelosByMarca: {},
            modelosByTipoMarca: {},
        };

        Object.entries(asObject(equipamentoCatalog?.marcasByTipo)).forEach(([tipoId, marcas]) => {
            const tipoKey = normalizeId(tipoId);
            if (!tipoKey) return;
            catalogState.marcasByTipo[tipoKey] = normalizeSimpleList(marcas);
        });

        Object.entries(asObject(equipamentoCatalog?.modelosByMarca)).forEach(([marcaId, modelos]) => {
            const marcaKey = normalizeId(marcaId);
            if (!marcaKey) return;
            catalogState.modelosByMarca[marcaKey] = normalizeModelList(modelos);
        });

        Object.entries(asObject(equipamentoCatalog?.modelosByTipoMarca)).forEach(([tipoId, marcasMap]) => {
            const tipoKey = normalizeId(tipoId);
            if (!tipoKey) return;
            catalogState.modelosByTipoMarca[tipoKey] = {};
            Object.entries(asObject(marcasMap)).forEach(([marcaId, modelos]) => {
                const marcaKey = normalizeId(marcaId);
                if (!marcaKey) return;
                catalogState.modelosByTipoMarca[tipoKey][marcaKey] = normalizeModelList(modelos);
            });
        });

        const initialMarca = normalizeId(equipMarcaSelect.dataset.selected || '');
        const initialModelo = normalizeId(equipModeloSelect.dataset.selected || '');

        const setSelectOptions = (select, options, placeholder, selectedValue = '') => {
            if (!select) return;
            select.innerHTML = '';
            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = placeholder;
            select.appendChild(placeholderOption);

            options.forEach((item) => {
                const option = document.createElement('option');
                option.value = normalizeId(item.id);
                option.textContent = normalizeName(item.nome);
                select.appendChild(option);
            });

            const candidate = normalizeId(selectedValue);
            if (candidate !== '' && Array.from(select.options).some((opt) => opt.value === candidate)) {
                select.value = candidate;
            } else {
                select.value = '';
            }

            initCatalogSelect2(select, placeholder);
        };

        const getMarcasByTipo = (tipoId) => {
            const tipoKey = normalizeId(tipoId);
            if (!tipoKey) return [];
            const marcasDoTipo = catalogState.marcasByTipo[tipoKey] || [];
            if (marcasDoTipo.length > 0) return marcasDoTipo;
            return catalogState.marcasAll;
        };

        const getModelosByTipoMarca = (tipoId, marcaId) => {
            const tipoKey = normalizeId(tipoId);
            const marcaKey = normalizeId(marcaId);
            if (!marcaKey) return [];

            if (tipoKey) {
                const modelosTipoMarca = catalogState.modelosByTipoMarca[tipoKey]?.[marcaKey] || [];
                if (modelosTipoMarca.length > 0) {
                    return modelosTipoMarca;
                }
            }

            // Fallback alinhado ao comportamento da abertura da OS:
            // quando não houver histórico da combinacao tipo+marca,
            // listar os modelos da marca para não travar a selecao.
            return catalogState.modelosByMarca[marcaKey] || [];
        };

        let modeloRequestToken = 0;
        const fetchModelosByTipoMarca = async (tipoId, marcaId) => {
            const tipoKey = normalizeId(tipoId);
            const marcaKey = normalizeId(marcaId);
            if (!marcaKey) return [];

            const params = new URLSearchParams({ marca_id: marcaKey });
            if (tipoKey) {
                params.set('tipo_id', tipoKey);
            }

            const response = await fetch(`${equipamentoModeloPorMarcaUrl}?${params.toString()}`, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            const modelos = normalizeModelList(payload);
            return modelos;
        };

        const refreshMarcaSelect = (selectedMarca = '', selectedModelo = '') => {
            const tipoId = normalizeId(equipTipoSelect.value);
            if (!tipoId) {
                setSelectOptions(equipMarcaSelect, [], 'Selecione o tipo primeiro...', '');
                setSelectOptions(equipModeloSelect, [], 'Selecione a marca primeiro...', '');
                return;
            }

            const marcas = getMarcasByTipo(tipoId);
            setSelectOptions(
                equipMarcaSelect,
                marcas,
                marcas.length > 0 ? 'Selecione a marca...' : 'Nenhuma marca para este tipo',
                selectedMarca
            );
            refreshModeloSelect(selectedModelo);
        };

        const refreshModeloSelect = async (selectedModelo = '') => {
            const tipoId = normalizeId(equipTipoSelect.value);
            const marcaId = normalizeId(equipMarcaSelect.value);
            if (!tipoId || !marcaId) {
                setSelectOptions(equipModeloSelect, [], 'Selecione a marca primeiro...', '');
                return;
            }

            let modelos = getModelosByTipoMarca(tipoId, marcaId);
            const requestToken = ++modeloRequestToken;
            try {
                const modelosRemotos = await fetchModelosByTipoMarca(tipoId, marcaId);
                if (requestToken !== modeloRequestToken) {
                    return;
                }

                if (modelosRemotos.length > 0) {
                    modelos = modelosRemotos;
                    catalogState.modelosByMarca[marcaId] = [...modelosRemotos];
                    if (!catalogState.modelosByTipoMarca[tipoId]) {
                        catalogState.modelosByTipoMarca[tipoId] = {};
                    }
                    catalogState.modelosByTipoMarca[tipoId][marcaId] = [...modelosRemotos];
                }
            } catch (error) {
                console.error('[Orçamentos] Falha ao carregar modelos por marca/tipo.', error);
            }

            setSelectOptions(
                equipModeloSelect,
                modelos,
                modelos.length > 0 ? 'Selecione o modelo...' : 'Nenhum modelo para esta combinacao',
                selectedModelo
            );
        };

        const upsertMarca = (tipoId, marca) => {
            const marcaId = normalizeId(marca?.id);
            const marcaNome = normalizeName(marca?.nome);
            const tipoKey = normalizeId(tipoId);
            if (!marcaId || !marcaNome) return;

            if (!catalogState.marcasAll.some((item) => normalizeId(item.id) === marcaId)) {
                catalogState.marcasAll.push({ id: marcaId, nome: marcaNome });
                sortByNome(catalogState.marcasAll);
            }

            if (tipoKey) {
                if (!Array.isArray(catalogState.marcasByTipo[tipoKey])) {
                    catalogState.marcasByTipo[tipoKey] = [];
                }
                if (!catalogState.marcasByTipo[tipoKey].some((item) => normalizeId(item.id) === marcaId)) {
                    catalogState.marcasByTipo[tipoKey].push({ id: marcaId, nome: marcaNome });
                    sortByNome(catalogState.marcasByTipo[tipoKey]);
                }
            }
        };

        const upsertModelo = (tipoId, marcaId, modelo) => {
            const tipoKey = normalizeId(tipoId);
            const marcaKey = normalizeId(marcaId);
            const modeloId = normalizeId(modelo?.id);
            const modeloNome = normalizeName(modelo?.nome);
            if (!marcaKey || !modeloId || !modeloNome) return;

            if (!Array.isArray(catalogState.modelosByMarca[marcaKey])) {
                catalogState.modelosByMarca[marcaKey] = [];
            }
            if (!catalogState.modelosByMarca[marcaKey].some((item) => normalizeId(item.id) === modeloId)) {
                catalogState.modelosByMarca[marcaKey].push({ id: modeloId, marca_id: marcaKey, nome: modeloNome });
                sortByNome(catalogState.modelosByMarca[marcaKey]);
            }

            if (tipoKey) {
                if (!catalogState.modelosByTipoMarca[tipoKey]) {
                    catalogState.modelosByTipoMarca[tipoKey] = {};
                }
                if (!Array.isArray(catalogState.modelosByTipoMarca[tipoKey][marcaKey])) {
                    catalogState.modelosByTipoMarca[tipoKey][marcaKey] = [];
                }
                if (!catalogState.modelosByTipoMarca[tipoKey][marcaKey].some((item) => normalizeId(item.id) === modeloId)) {
                    catalogState.modelosByTipoMarca[tipoKey][marcaKey].push({ id: modeloId, marca_id: marcaKey, nome: modeloNome });
                    sortByNome(catalogState.modelosByTipoMarca[tipoKey][marcaKey]);
                }
            }
        };

        const renameMarcaInCatalog = (marcaId, newName) => {
            const marcaKey = normalizeId(marcaId);
            const safeName = normalizeName(newName);
            if (!marcaKey || !safeName) return;

            catalogState.marcasAll = catalogState.marcasAll.map((item) => (
                normalizeId(item.id) === marcaKey
                    ? { ...item, nome: safeName }
                    : item
            ));
            sortByNome(catalogState.marcasAll);

            Object.keys(catalogState.marcasByTipo).forEach((tipoKey) => {
                const marcas = asArray(catalogState.marcasByTipo[tipoKey]).map((item) => (
                    normalizeId(item.id) === marcaKey
                        ? { ...item, nome: safeName }
                        : item
                ));
                sortByNome(marcas);
                catalogState.marcasByTipo[tipoKey] = marcas;
            });
        };

        const renameModeloInCatalog = (modeloId, marcaId, newName) => {
            const modeloKey = normalizeId(modeloId);
            const marcaKey = normalizeId(marcaId);
            const safeName = normalizeName(newName);
            if (!modeloKey || !marcaKey || !safeName) return;

            if (Array.isArray(catalogState.modelosByMarca[marcaKey])) {
                catalogState.modelosByMarca[marcaKey] = catalogState.modelosByMarca[marcaKey].map((item) => (
                    normalizeId(item.id) === modeloKey
                        ? { ...item, nome: safeName, marca_id: marcaKey }
                        : item
                ));
                sortByNome(catalogState.modelosByMarca[marcaKey]);
            }

            Object.keys(catalogState.modelosByTipoMarca).forEach((tipoKey) => {
                const modelos = asArray(catalogState.modelosByTipoMarca[tipoKey]?.[marcaKey]);
                if (modelos.length <= 0) return;
                catalogState.modelosByTipoMarca[tipoKey][marcaKey] = modelos.map((item) => (
                    normalizeId(item.id) === modeloKey
                        ? { ...item, nome: safeName, marca_id: marcaKey }
                        : item
                ));
                sortByNome(catalogState.modelosByTipoMarca[tipoKey][marcaKey]);
            });
        };

        const normalizeHex = (hex) => {
            const value = normalizeName(hex).toUpperCase();
            return /^#[0-9A-F]{6}$/.test(value) ? value : '';
        };

        const professionalColors = [
            {
                category: 'Neutras',
                colors: [
                    { hex: '#000000', name: 'Preto' },
                    { hex: '#2F4F4F', name: 'Grafite' },
                    { hex: '#41464D', name: 'Graphite' },
                    { hex: '#5C5B57', name: 'Titanium' },
                    { hex: '#696969', name: 'Cinza Escuro' },
                    { hex: '#BEBEBE', name: 'Cinza' },
                    { hex: '#FFFFFF', name: 'Branco' },
                    { hex: '#F8F8FF', name: 'Branco Gelo' },
                    { hex: '#FFFFF0', name: 'Marfim' },
                ],
            },
            {
                category: 'Azuis e Marinhos',
                colors: [
                    { hex: '#191970', name: 'Azul Meia-Noite' },
                    { hex: '#000080', name: 'Azul Marinho' },
                    { hex: '#0000FF', name: 'Azul Puro' },
                    { hex: '#4169E1', name: 'Azul Real' },
                    { hex: '#1E90FF', name: 'Azul Ceu' },
                    { hex: '#87CEEB', name: 'Azul Celeste' },
                    { hex: '#5F9EA0', name: 'Azul Petroleo' },
                ],
            },
            {
                category: 'Verdes e Mentas',
                colors: [
                    { hex: '#006400', name: 'Verde Escuro' },
                    { hex: '#2E8B57', name: 'Verde Floresta' },
                    { hex: '#008000', name: 'Verde Puro' },
                    { hex: '#32CD32', name: 'Verde Vivo' },
                    { hex: '#98FB98', name: 'Verde Claro' },
                    { hex: '#F5FFFA', name: 'Verde Menta' },
                ],
            },
            {
                category: 'Vermelhos e Corais',
                colors: [
                    { hex: '#8B0000', name: 'Vermelho Escuro' },
                    { hex: '#B22222', name: 'Vermelho Tijolo' },
                    { hex: '#FF0000', name: 'Vermelho' },
                    { hex: '#FF4500', name: 'Vermelho Alaranjado' },
                    { hex: '#FF6347', name: 'Tomate' },
                    { hex: '#FFA500', name: 'Laranja' },
                ],
            },
            {
                category: 'Amarelos e Dourados',
                colors: [
                    { hex: '#DAA520', name: 'Dourado' },
                    { hex: '#FFD700', name: 'Dourado Vivo' },
                    { hex: '#FFFF00', name: 'Amarelo' },
                    { hex: '#F5F5DC', name: 'Bege' },
                    { hex: '#FFF8DC', name: 'Marfim Claro' },
                ],
            },
            {
                category: 'Roxos e Rosas',
                colors: [
                    { hex: '#4B0082', name: 'Indigo' },
                    { hex: '#2D1B69', name: 'Violeta' },
                    { hex: '#800080', name: 'Roxo Puro' },
                    { hex: '#DA70D6', name: 'Lilas' },
                    { hex: '#FF1493', name: 'Pink' },
                    { hex: '#AA336A', name: 'Rose Gold' },
                ],
            },
        ];

        const flatColorCatalog = professionalColors.flatMap((group) => group.colors.map((color) => ({
            hex: normalizeHex(color.hex),
            name: normalizeName(color.name),
        }))).filter((color) => color.hex && color.name);

        const hexToRgbObject = (hex) => {
            const safeHex = normalizeHex(hex);
            if (!safeHex) return null;
            return {
                r: parseInt(safeHex.slice(1, 3), 16),
                g: parseInt(safeHex.slice(3, 5), 16),
                b: parseInt(safeHex.slice(5, 7), 16),
            };
        };

        const hexToRgb = (hex) => {
            const rgb = hexToRgbObject(hex);
            return rgb ? `${rgb.r},${rgb.g},${rgb.b}` : '';
        };

        const colorDistance = (hexA, hexB) => {
            const rgbA = hexToRgbObject(hexA);
            const rgbB = hexToRgbObject(hexB);
            if (!rgbA || !rgbB) return Number.POSITIVE_INFINITY;
            return Math.sqrt(
                Math.pow(rgbA.r - rgbB.r, 2) +
                Math.pow(rgbA.g - rgbB.g, 2) +
                Math.pow(rgbA.b - rgbB.b, 2)
            );
        };

        const getTextColor = (hex) => {
            const rgb = hexToRgbObject(hex);
            if (!rgb) return '#6c757d';
            const lum = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
            return lum > 0.6 ? '#1a1a1a' : '#ffffff';
        };

        const findColorByName = (name) => {
            const normalizedName = normalizeName(name).toLowerCase();
            if (!normalizedName) return null;
            return flatColorCatalog.find((item) => normalizeName(item.name).toLowerCase() === normalizedName) || null;
        };

        const findNearestColorByHex = (hex) => {
            const safeHex = normalizeHex(hex);
            if (!safeHex || !flatColorCatalog.length) return null;
            let best = null;
            let bestDistance = Number.POSITIVE_INFINITY;
            flatColorCatalog.forEach((item) => {
                const dist = colorDistance(safeHex, item.hex);
                if (dist < bestDistance) {
                    bestDistance = dist;
                    best = item;
                }
            });
            return best;
        };

        const buildColorCatalog = () => {
            if (!colorCatalogContainer) return;
            const selectedHex = normalizeHex(equipCorHexInput?.value || '');
            colorCatalogContainer.innerHTML = '';

            professionalColors.forEach((group) => {
                const groupWrap = document.createElement('div');
                groupWrap.className = 'orc-color-group';

                const heading = document.createElement('div');
                heading.className = 'orc-color-group-title';
                heading.textContent = group.category;
                groupWrap.appendChild(heading);

                const list = document.createElement('div');
                list.className = 'list-group list-group-flush border rounded-3 overflow-hidden';

                group.colors.forEach((item) => {
                    const itemHex = normalizeHex(item.hex);
                    const itemName = normalizeName(item.name);
                    if (!itemHex || !itemName) return;

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 px-3 orc-color-item';
                    if (selectedHex && selectedHex === itemHex) {
                        button.classList.add('active', 'bg-primary-subtle', 'text-primary', 'fw-semibold');
                    }
                    button.innerHTML = `
                        <span class="rounded-circle border flex-shrink-0" style="width:18px;height:18px;background:${itemHex};"></span>
                        <span class="flex-grow-1 text-start">${itemName}</span>
                        <small class="font-monospace opacity-75">${itemHex}</small>
                    `;
                    button.addEventListener('click', () => updateColorUI(itemHex, itemName));
                    list.appendChild(button);
                });

                groupWrap.appendChild(list);
                colorCatalogContainer.appendChild(groupWrap);
            });
        };

        const updateNearestColors = (hex) => {
            if (!colorNearestGrid) return;
            const safeHex = normalizeHex(hex);
            colorNearestGrid.innerHTML = '';
            if (!safeHex) return;

            const nearest = flatColorCatalog
                .map((item) => ({ ...item, dist: colorDistance(safeHex, item.hex) }))
                .sort((a, b) => a.dist - b.dist)
                .slice(0, 6);

            nearest.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'rounded-circle border orc-color-near-btn';
                button.title = `${item.name} (${item.hex})`;
                button.style.background = item.hex;
                button.addEventListener('click', () => updateColorUI(item.hex, item.name));
                colorNearestGrid.appendChild(button);
            });
        };

        const updateColorUI = (hex, name) => {
            const safeHex = normalizeHex(hex);
            const safeName = normalizeName(name);
            const nearest = safeHex ? findNearestColorByHex(safeHex) : null;
            const effectiveName = safeName || nearest?.name || '';
            const rgbValue = safeHex ? hexToRgb(safeHex) : '';

            if (equipCorHexInput) equipCorHexInput.value = safeHex;
            if (equipCorRgbInput) equipCorRgbInput.value = rgbValue;
            if (equipCorNomeInput) equipCorNomeInput.value = effectiveName;
            if (equipCorPicker) equipCorPicker.value = safeHex || '#FFFFFF';

            if (colorPreviewBox && colorPreviewHex && colorPreviewName) {
                if (!safeHex) {
                    colorPreviewBox.style.background = 'rgba(0, 0, 0, 0.05)';
                    colorPreviewHex.textContent = '---';
                    colorPreviewHex.style.color = '#6c757d';
                    colorPreviewName.textContent = effectiveName || 'Cor não selecionada';
                    colorPreviewName.style.color = '#6c757d';
                } else {
                    const textColor = getTextColor(safeHex);
                    colorPreviewBox.style.background = safeHex;
                    colorPreviewHex.textContent = safeHex;
                    colorPreviewHex.style.color = textColor;
                    colorPreviewName.textContent = effectiveName || safeHex;
                    colorPreviewName.style.color = textColor === '#ffffff' ? 'rgba(255,255,255,0.75)' : 'rgba(0,0,0,0.65)';
                }
            }

            updateNearestColors(safeHex);
            buildColorCatalog();
        };

        const manualSection = document.getElementById('orcamentoEquipamentoManualSection');
        const manualFields = [
            equipTipoSelect,
            equipMarcaSelect,
            equipModeloSelect,
            equipCorPicker,
            equipCorNomeInput,
            btnOrcNovaMarca,
            btnOrcNovoModelo,
        ];

        const hasLinkedEquipamentoInSelectedOs = () => {
            const selectedOsId = normalizeId(osIdInput?.value || '');
            if (!selectedOsId) {
                return false;
            }

            const selectedOs = findOsAbertaById(selectedOsId);
            if (selectedOs) {
                return normalizeId(selectedOs?.equipamento_id || selectedOs?.equipamento?.id || '') !== '';
            }

            return manualSection?.dataset.osHasLinkedEquip === '1';
        };

        const setManualSectionVisible = (visible) => {
            if (!manualSection) {
                return;
            }

            manualSection.classList.toggle('d-none', !visible);
        };

        const setEquipamentoLookupHint = (message, tone = 'light') => {
            if (!equipamentoLookupHint) return;
            equipamentoLookupHint.className = 'alert border small py-2 px-3 mb-0';
            if (tone === 'info') {
                equipamentoLookupHint.classList.add('alert-info');
            } else if (tone === 'warning') {
                equipamentoLookupHint.classList.add('alert-warning');
            } else {
                equipamentoLookupHint.classList.add('alert-light');
            }
            equipamentoLookupHint.textContent = message;
        };

        const setManualLocked = (locked) => {
            manualFields.forEach((field) => {
                if (!field) return;
                field.disabled = locked;
            });
            if (manualSection) {
                manualSection.classList.toggle('orc-manual-locked', locked);
            }
        };

        const setManualFieldsVisible = (visible) => {
            if (!equipamentoManualFieldsWrap) return;
            equipamentoManualFieldsWrap.classList.toggle('d-none', !visible);
        };

        const clearManualCatalogFields = () => {
            if (equipTipoSelect) {
                equipTipoSelect.value = '';
            }
            refreshMarcaSelect('', '');
            updateColorUI('', '');
        };

        let suppressEquipamentoLookupClear = false;
        let currentClienteLookupForEquip = normalizeId(clienteIdInput?.value || '');
        let manualCadastroPorBotao = false;
        let visibilityRequestToken = 0;
        let osLookupRequestToken = 0;
        let currentOsAbertasOptions = [];
        const clienteEquipamentosCountCache = new Map();

        const clearEquipamentoLookupSelection = () => {
            if (!equipamentoLookupSelect) {
                return;
            }

            if (hasSelect2) {
                const $equipLookup = jq(equipamentoLookupSelect);
                if ($equipLookup.hasClass('select2-hidden-accessible')) {
                    suppressEquipamentoLookupClear = true;
                    $equipLookup.val(null).trigger('change');
                    suppressEquipamentoLookupClear = false;
                    return;
                }
            }

            equipamentoLookupSelect.value = '';
        };

        const findOsAbertaById = (osId) => {
            const targetId = normalizeId(osId);
            if (!targetId) {
                return null;
            }

            return currentOsAbertasOptions.find((item) => normalizeId(item?.os_id || item?.id) === targetId) || null;
        };

        const populateOsLookupOptions = (options, selectedOsId = '') => {
            if (!osLookupSelect) {
                return;
            }

            resetOsLookupOptions();
            options.forEach((item) => {
                const option = document.createElement('option');
                option.value = normalizeId(item?.os_id || item?.id);
                option.textContent = normalizeName(item?.text || item?.numero_label || option.value);
                osLookupSelect.appendChild(option);
            });

            osLookupSelect.disabled = options.length === 0;
            if (selectedOsId && options.some((item) => normalizeId(item?.os_id || item?.id) === selectedOsId)) {
                osLookupSelect.value = selectedOsId;
            }
        };

        const renderOsLookupPrompt = (totalAbertas, message = '') => {
            if (osIdInput) {
                osIdInput.value = '';
            }
            if (osNumeroHintInput) {
                osNumeroHintInput.value = '';
            }
            if (vinculoOsTitulo) {
                vinculoOsTitulo.textContent = totalAbertas > 0 ? 'Selecione a OS aberta' : 'Nenhuma OS aberta encontrada';
            }
            if (vinculoOsStatus) {
                vinculoOsStatus.textContent = '';
                vinculoOsStatus.classList.add('d-none');
            }
            if (vinculoOsCounter) {
                if (totalAbertas > 1) {
                    vinculoOsCounter.textContent = `${totalAbertas} abertas`;
                    vinculoOsCounter.classList.remove('d-none');
                } else {
                    vinculoOsCounter.textContent = '';
                    vinculoOsCounter.classList.add('d-none');
                }
            }
            if (osLookupWrap) {
                const shouldKeepVisible = isTipoAssistencia() && totalAbertas > 0;
                osLookupWrap.classList.toggle('d-none', totalAbertas <= 1 && !shouldKeepVisible);
            }
            setOsLookupHelp(message);
            if (manualSection) {
                manualSection.dataset.osHasLinkedEquip = '0';
            }
            setManualSectionVisible(true);
            toggleVinculoColumn(vinculoOsCol, true);
            refreshVinculosVisualVisibility();
        };

        const applyOsAbertaSelection = (item, options = {}) => {
            const totalAbertas = Number(options.totalAbertas || currentOsAbertasOptions.length || 0);
            const osSelecionadaId = normalizeId(item?.os_id || item?.id);
            const osHasLinkedEquipamento = normalizeId(item?.equipamento_id || item?.equipamento?.id || '') !== '';

            if (manualSection) {
                manualSection.dataset.osHasLinkedEquip = osHasLinkedEquipamento ? '1' : '0';
            }
            setManualSectionVisible(!osHasLinkedEquipamento);

            if (!osSelecionadaId) {
                clearVinculoOsCard({ hideColumn: totalAbertas <= 0 });
                clearVinculoEquipamentoCard({ hideColumn: true });
                refreshManualFieldsVisibility();
                scheduleDetectPacoteOferta();
                return;
            }

            if (tipoSelect && !isTipoAssistencia()) {
                tipoSelect.value = 'assistencia';
                syncTipoOrcamentoHelp();
            }

            renderVinculoOsCard(item, { totalAbertas });
            if (osLookupWrap) {
                const shouldKeepVisible = isTipoAssistencia() && totalAbertas > 0;
                osLookupWrap.classList.toggle('d-none', totalAbertas <= 1 && !shouldKeepVisible);
            }
            setOsLookupHelp(
                totalAbertas > 1
                    ? 'Cliente com mais de uma OS aberta. Escolha abaixo a OS correta para este orcamento.'
                    : 'OS aberta vinculada automaticamente para este orcamento.'
            );
            if (osLookupSelect) {
                osLookupSelect.value = osSelecionadaId;
            }

            clearEquipamentoLookupSelection();
            clearManualCatalogFields();
            if (item?.equipamento && typeof item.equipamento === 'object') {
                renderVinculoEquipamentoCard({
                    ...item.equipamento,
                    id: item?.equipamento_id || item?.equipamento?.id || '',
                });
            } else {
                clearVinculoEquipamentoCard({ hideColumn: true });
            }

            manualCadastroPorBotao = false;
            refreshManualFieldsVisibility();
            scheduleDetectPacoteOferta();
        };

        const loadOsAbertasByCliente = async (clienteId, preferredOsId = '') => {
            const clienteKey = normalizeId(clienteId);
            const preferredKey = normalizeId(preferredOsId);
            currentOsAbertasOptions = [];

            if (!clienteKey) {
                clearVinculoOsCard();
                return;
            }

            const requestToken = ++osLookupRequestToken;
            toggleVinculoColumn(vinculoOsCol, true);
            if (vinculoOsTitulo) {
                vinculoOsTitulo.textContent = 'Carregando OS abertas...';
            }
            if (vinculoOsStatus) {
                vinculoOsStatus.textContent = '';
                vinculoOsStatus.classList.add('d-none');
            }
            if (vinculoOsCounter) {
                vinculoOsCounter.textContent = '';
                vinculoOsCounter.classList.add('d-none');
            }
            if (osLookupWrap) {
                osLookupWrap.classList.add('d-none');
            }
            setOsLookupHelp('Consultando OS abertas deste cliente...');
            refreshVinculosVisualVisibility();

            try {
                const query = new URLSearchParams({
                    cliente_id: clienteKey,
                    q: '',
                });
                const response = await fetch(`${osAbertasLookupUrl}?${query.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                if (requestToken !== osLookupRequestToken) {
                    return;
                }

                currentOsAbertasOptions = Array.isArray(payload?.results) ? payload.results : [];
                populateOsLookupOptions(currentOsAbertasOptions, preferredKey);

                const osAtual = preferredKey ? findOsAbertaById(preferredKey) : null;
                if (osAtual) {
                    applyOsAbertaSelection(osAtual, { totalAbertas: currentOsAbertasOptions.length });
                    return;
                }

                if (currentOsAbertasOptions.length === 1) {
                    if (isTipoAssistencia()) {
                        applyOsAbertaSelection(currentOsAbertasOptions[0], { totalAbertas: 1 });
                        return;
                    }
                    renderOsLookupPrompt(
                        1,
                        'Existe 1 OS aberta para este cliente. Se este orcamento for de assistencia, selecione a OS para herdar o equipamento.'
                    );
                    return;
                }

                if (currentOsAbertasOptions.length > 1) {
                    renderOsLookupPrompt(
                        currentOsAbertasOptions.length,
                        'Cliente com mais de uma OS aberta. Selecione abaixo a OS correta junto com o equipamento vinculado.'
                    );
                    return;
                }

                renderOsLookupPrompt(0, 'Este cliente não possui OS aberta elegivel para vinculo automatico.');
            } catch (error) {
                console.error('[Orçamentos] Falha ao consultar OS abertas do cliente.', error);
                if (requestToken !== osLookupRequestToken) {
                    return;
                }

                currentOsAbertasOptions = [];
                clearVinculoOsCard({ hideColumn: false });
                if (vinculoOsTitulo) {
                    vinculoOsTitulo.textContent = 'Falha ao carregar OS abertas';
                }
                setOsLookupHelp('Não foi possível carregar as OS abertas deste cliente agora. Tente novamente em instantes.');
                toggleVinculoColumn(vinculoOsCol, true);
                refreshVinculosVisualVisibility();
            }
        };

        const fetchClienteEquipamentosCount = async (clienteId) => {
            const clienteKey = normalizeId(clienteId);
            if (!clienteKey) return 0;
            if (clienteEquipamentosCountCache.has(clienteKey)) {
                return Number(clienteEquipamentosCountCache.get(clienteKey)) || 0;
            }

            try {
                const query = new URLSearchParams({
                    cliente_id: clienteKey,
                    q: '',
                    page: '1',
                });
                const response = await fetch(`${equipamentoLookupUrl}?${query.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                const payload = await response.json();
                const count = Array.isArray(payload?.results) ? payload.results.length : 0;
                clienteEquipamentosCountCache.set(clienteKey, count);
                return count;
            } catch (error) {
                console.error('[Orçamentos] Falha ao consultar equipamentos do cliente para controle de visibilidade.', error);
                return 0;
            }
        };

        const activateManualEquipamentoMode = (options = {}) => {
            const clearLookup = Boolean(options.clearLookup);
            const resetFields = Boolean(options.resetFields);
            const viaBotao = Boolean(options.viaBotao);
            const message = normalizeName(options.message || 'Preencha o cadastro manual para novo equipamento deste or\u00e7amento.');
            if (viaBotao) {
                manualCadastroPorBotao = true;
            }
            if (equipamentoIdInput) {
                equipamentoIdInput.value = '';
            }
            if (equipamentoTituloHintInput) {
                equipamentoTituloHintInput.value = '';
            }
            clearVinculoEquipamentoCard({ hideColumn: true, keepEquipamentoId: true });
            setManualLocked(false);
            setManualFieldsVisible(true);
            if (resetFields) {
                clearManualCatalogFields();
            }
            if (clearLookup && equipamentoLookupSelect) {
                if (hasSelect2) {
                    const $equipLookup = jq(equipamentoLookupSelect);
                    if ($equipLookup.hasClass('select2-hidden-accessible')) {
                        suppressEquipamentoLookupClear = true;
                        $equipLookup.val(null).trigger('change');
                        suppressEquipamentoLookupClear = false;
                    }
                } else {
                    equipamentoLookupSelect.value = '';
                }
            }
            setEquipamentoLookupHint(message, 'info');
        };

        const refreshManualFieldsVisibility = async () => {
            const clienteId = normalizeId(clienteIdInput?.value || '');
            const nomeClienteEventual = normalizeName(nomeAvulsoInput?.value || '');
            const osSelecionada = normalizeId(osIdInput?.value || '');
            const equipamentoSelecionado = normalizeId(equipamentoIdInput?.value || '');
            const tipoAssistencia = isTipoAssistencia();
            const requestToken = ++visibilityRequestToken;
            const osTemEquipamentoVinculado = hasLinkedEquipamentoInSelectedOs();
            const osSemEquipamentoVinculado = osSelecionada !== '' && !osTemEquipamentoVinculado;

            setManualSectionVisible(!osTemEquipamentoVinculado);
            if (manualSection) {
                manualSection.dataset.osHasLinkedEquip = osTemEquipamentoVinculado ? '1' : '0';
            }

            if (btnOrcNovoEquipamentoManual) {
                btnOrcNovoEquipamentoManual.disabled = false;
            }

            if (!clienteId) {
                if (equipamentoLookupSelect) {
                    equipamentoLookupSelect.disabled = true;
                }
                if (tipoAssistencia) {
                    if (btnOrcNovoEquipamentoManual) {
                        btnOrcNovoEquipamentoManual.disabled = true;
                    }
                    setManualLocked(true);
                    setManualFieldsVisible(false);
                    setEquipamentoLookupHint('Selecione primeiro o cliente e a OS aberta para orcamento com equipamento na assistencia.', 'light');
                    return;
                }
                setManualLocked(false);
                const mostrarCadastro = nomeClienteEventual !== '';
                setManualFieldsVisible(mostrarCadastro);
                if (mostrarCadastro) {
                    setEquipamentoLookupHint('Nome do cliente eventual preenchido. Informe os dados do equipamento.', 'info');
                } else {
                    setEquipamentoLookupHint('Preencha o nome do cliente eventual para liberar o cadastro do equipamento.', 'light');
                }
                return;
            }

            if (osSelecionada !== '' && osTemEquipamentoVinculado) {
                if (equipamentoLookupSelect) {
                    equipamentoLookupSelect.disabled = true;
                }
                if (btnOrcNovoEquipamentoManual) {
                    btnOrcNovoEquipamentoManual.disabled = true;
                }
                setManualLocked(true);
                setManualFieldsVisible(false);
                setEquipamentoLookupHint(
                    'Equipamento herdado da OS selecionada. Para trocar o equipamento, altere primeiro o vinculo da OS aberta.',
                    'info'
                );
                return;
            }

            if (tipoAssistencia && !osSemEquipamentoVinculado) {
                if (equipamentoLookupSelect) {
                    equipamentoLookupSelect.disabled = true;
                }
                if (btnOrcNovoEquipamentoManual) {
                    btnOrcNovoEquipamentoManual.disabled = true;
                }
                setManualLocked(true);
                setManualFieldsVisible(false);
                if (currentOsAbertasOptions.length > 0) {
                    setEquipamentoLookupHint('Selecione uma OS aberta para herdar automaticamente o equipamento em assistencia.', 'info');
                } else {
                    setEquipamentoLookupHint('Não há OS aberta elegivel para este cliente. Abra uma OS ou mude o tipo para orcamento previo.', 'warning');
                }
                return;
            }

            if (equipamentoLookupSelect) {
                equipamentoLookupSelect.disabled = false;
            }

            if (equipamentoSelecionado !== '') {
                setManualFieldsVisible(false);
                setManualLocked(true);
                setEquipamentoLookupHint(
                    'Equipamento cadastrado selecionado. Clique em "Cadastrar novo equipamento" para inserir um novo manualmente.',
                    'info'
                );
                return;
            }

            const equipamentosCount = await fetchClienteEquipamentosCount(clienteId);
            if (requestToken !== visibilityRequestToken) {
                return;
            }

            if (equipamentosCount > 0) {
                const mostrarCadastro = manualCadastroPorBotao;
                setManualLocked(false);
                setManualFieldsVisible(mostrarCadastro);
                if (mostrarCadastro) {
                    setEquipamentoLookupHint(
                        osSemEquipamentoVinculado
                            ? 'OS selecionada sem equipamento vinculado. Cadastro manual ativo para preencher tipo, marca, modelo e cor.'
                            : 'Cadastro manual ativo. Preencha tipo, marca, modelo e cor.',
                        'info'
                    );
                } else {
                    setEquipamentoLookupHint(
                        osSemEquipamentoVinculado
                            ? 'OS selecionada sem equipamento vinculado. Selecione um equipamento existente ou clique em "Cadastrar novo equipamento".'
                            : 'Cliente possui equipamentos cadastrados. Selecione um existente ou clique em "Cadastrar novo equipamento".',
                        osSemEquipamentoVinculado ? 'info' : 'light'
                    );
                }
                return;
            }

            setManualLocked(false);
            setManualFieldsVisible(true);
            setEquipamentoLookupHint(
                osSemEquipamentoVinculado
                    ? 'OS selecionada sem equipamento vinculado. Preencha o cadastro manual do equipamento.'
                    : 'Cliente sem equipamentos cadastrados. Preencha o cadastro manual do equipamento.',
                'info'
            );
        };

        const handleTipoOrcamentoChange = async (options = {}) => {
            const silent = options.silent === true;
            const forceReload = options.forceReload === true;
            const clienteId = normalizeId(clienteIdInput?.value || '');
            const osSelecionada = normalizeId(osIdInput?.value || '');

            syncTipoOrcamentoHelp();

            if (!isTipoAssistencia() && osSelecionada !== '') {
                if (tipoSelect) {
                    tipoSelect.value = 'assistencia';
                }
                syncTipoOrcamentoHelp();

                if (!silent && window.Swal) {
                    await window.Swal.fire({
                        icon: 'info',
                        title: 'Tipo ajustado automaticamente',
                        text: 'Como este orcamento esta vinculado a uma OS aberta, ele precisa permanecer como "Orçamento com equipamento na assistencia".',
                        confirmButtonText: 'Entendi',
                    });
                }
            }

            if (isTipoAssistencia()) {
                manualCadastroPorBotao = false;
            }

            await refreshManualFieldsVisibility();

            if (clienteId !== '' && (forceReload || currentOsAbertasOptions.length > 0 || isTipoAssistencia() || osSelecionada !== '')) {
                await loadOsAbertasByCliente(clienteId, normalizeId(osIdInput?.value || ''));
            }
        };

        const applyExistingEquipamento = (item) => {
            const equipamentoId = normalizeId(item?.equipamento_id);
            if (!equipamentoId) {
                if (equipamentoIdInput) {
                    equipamentoIdInput.value = '';
                }
                if (equipamentoTituloHintInput) {
                    equipamentoTituloHintInput.value = '';
                }
                clearVinculoEquipamentoCard({ hideColumn: true, keepEquipamentoId: true });
                setManualLocked(false);
                setManualFieldsVisible(false);
                refreshManualFieldsVisibility();
                return;
            }

            manualCadastroPorBotao = false;
            if (equipamentoIdInput) {
                equipamentoIdInput.value = equipamentoId;
            }
            renderVinculoEquipamentoCard({
                id: equipamentoId,
                display_text: item?.text || '',
                descricao: normalizeName(item?.cor) ? `Cor: ${normalizeName(item?.cor)}` : '',
                foto_url: item?.foto_url || '',
            });

            const tipoId = normalizeId(item?.tipo_id);
            const marcaId = normalizeId(item?.marca_id);
            const modeloId = normalizeId(item?.modelo_id);
            if (tipoId) {
                equipTipoSelect.value = tipoId;
            } else {
                equipTipoSelect.value = '';
            }
            refreshMarcaSelect(marcaId, modeloId);

            const corHex = normalizeHex(item?.cor_hex || '');
            const corNome = normalizeName(item?.cor || '');
            updateColorUI(corHex, corNome);
            if (equipamentoTituloHintInput) {
                equipamentoTituloHintInput.value = normalizeName(item?.text || '');
            }

            setManualLocked(true);
            setManualFieldsVisible(false);
            setEquipamentoLookupHint(
                'Equipamento cadastrado selecionado. Clique em "Cadastrar novo equipamento" para editar os campos manuais.',
                'info'
            );
        };

        const initEquipamentoLookupByCliente = () => {
            if (!equipamentoLookupSelect) {
                return;
            }

            if (hasSelect2) {
                const $equipLookup = jq(equipamentoLookupSelect);
                if ($equipLookup.hasClass('select2-hidden-accessible')) {
                    $equipLookup.off('.orcEquipLookup');
                    $equipLookup.select2('destroy');
                }

                const modalParent = $equipLookup.closest('.modal');
                const select2Options = {
                    width: '100%',
                    allowClear: true,
                    minimumInputLength: 0,
                    placeholder: 'Selecione um equipamento ja cadastrado...',
                    ajax: {
                        url: equipamentoLookupUrl,
                        dataType: 'json',
                        delay: 250,
                        data: (params) => ({
                            q: params.term || '',
                            cliente_id: normalizeId(clienteIdInput?.value || ''),
                            page: params.page || 1,
                        }),
                        processResults: (response) => ({
                            results: Array.isArray(response?.results) ? response.results : [],
                            pagination: response?.pagination || { more: false },
                        }),
                    },
                    templateResult: (item) => {
                        if (item.loading) return escapeHtml(item.text || '');
                        const fotoUrl = normalizeName(item?.foto_url);
                        const corLabel = normalizeName(item?.cor);
                        return `
                            <div class="d-flex align-items-center gap-2">
                                <img src="${escapeHtml(fotoUrl || '')}" alt="" class="rounded-circle border flex-shrink-0 ${fotoUrl ? '' : 'd-none'}" width="28" height="28" style="object-fit:cover;">
                                <div class="d-flex flex-column">
                                    <span>${escapeHtml(item.text || '')}</span>
                                    ${corLabel ? `<small class="text-muted">Cor: ${escapeHtml(corLabel)}</small>` : ''}
                                </div>
                            </div>
                        `;
                    },
                    templateSelection: (item) => escapeHtml(item?.text || ''),
                    escapeMarkup: (markup) => markup,
                };
                if (modalParent.length > 0) {
                    select2Options.dropdownParent = modalParent;
                }

                $equipLookup.select2(select2Options);
                $equipLookup.on('select2:select.orcEquipLookup', (event) => {
                    applyExistingEquipamento(event?.params?.data || null);
                });
                $equipLookup.on('select2:clear.orcEquipLookup', () => {
                    if (suppressEquipamentoLookupClear) {
                        return;
                    }
                    if (equipamentoIdInput) {
                        equipamentoIdInput.value = '';
                    }
                    if (equipamentoTituloHintInput) {
                        equipamentoTituloHintInput.value = '';
                    }
                    clearVinculoEquipamentoCard({ hideColumn: true, keepEquipamentoId: true });
                    manualCadastroPorBotao = false;
                    setManualLocked(false);
                    refreshManualFieldsVisibility();
                });

                if (equipamentoLookupInitial && equipamentoLookupInitial.id) {
                    const option = new Option(
                        equipamentoLookupInitial.text || '',
                        equipamentoLookupInitial.id,
                        true,
                        true
                    );
                    $equipLookup.append(option).trigger('change');
                    applyExistingEquipamento(equipamentoLookupInitial);
                }
            }

            btnOrcNovoEquipamentoManual?.addEventListener('click', () => {
                const clienteId = normalizeId(clienteIdInput?.value || '');
                const nomeClienteEventual = normalizeName(nomeAvulsoInput?.value || '');
                if (!clienteId && nomeClienteEventual === '') {
                    if (window.Swal) {
                        window.Swal.fire('Preencha o cliente eventual', 'Informe o nome do cliente eventual para liberar o cadastro do equipamento.', 'warning');
                    }
                    return;
                }

                activateManualEquipamentoMode({
                    clearLookup: true,
                    resetFields: true,
                    viaBotao: true,
                    message: 'Cadastro manual ativo. Preencha tipo, marca, modelo e cor.',
                });
            });

            osLookupSelect?.addEventListener('change', () => {
                const selecionada = findOsAbertaById(osLookupSelect.value);
                if (selecionada) {
                    applyOsAbertaSelection(selecionada, { totalAbertas: currentOsAbertasOptions.length });
                    return;
                }

                applyOsAbertaSelection(null, { totalAbertas: currentOsAbertasOptions.length });
            });

            document.addEventListener('orcamento:cliente-change', async () => {
                const nextClienteId = normalizeId(clienteIdInput?.value || '');
                const clienteMudou = nextClienteId !== currentClienteLookupForEquip;
                currentClienteLookupForEquip = nextClienteId;
                if (clienteMudou) {
                    manualCadastroPorBotao = false;
                    if (equipamentoIdInput) {
                        equipamentoIdInput.value = '';
                    }
                    if (equipamentoTituloHintInput) {
                        equipamentoTituloHintInput.value = '';
                    }
                    clearEquipamentoLookupSelection();
                    clearVinculoEquipamentoCard({ hideColumn: true, keepEquipamentoId: true });
                    setManualLocked(false);
                    clearManualCatalogFields();
                }
                await handleTipoOrcamentoChange({ silent: true, forceReload: true });
            });

            nomeAvulsoInput?.addEventListener('input', () => {
                if (normalizeId(clienteIdInput?.value || '') !== '') {
                    return;
                }
                refreshManualFieldsVisibility();
            });
            nomeAvulsoInput?.addEventListener('change', () => {
                if (normalizeId(clienteIdInput?.value || '') !== '') {
                    return;
                }
                refreshManualFieldsVisibility();
            });

            bindClienteProgressiveEnterFlow(nomeAvulsoInput, () => {
                const clienteId = normalizeId(clienteIdInput?.value || '');
                const telefoneDigits = normalizeWhatsappDigits(String(telefoneInput?.value || ''));
                if (clienteId === '' && telefoneInput && telefoneDigits === '') {
                    telefoneInput.focus();
                    telefoneInput.select?.();
                    return;
                }
                focusPrimeiroCampoEquipamento();
            });

            bindClienteProgressiveEnterFlow(telefoneInput, () => {
                focusPrimeiroCampoEquipamento();
            });

            bindClienteProgressiveEnterFlow(emailInput, () => {
                focusPrimeiroCampoEquipamento();
            });

            tipoSelect?.addEventListener('change', () => {
                handleTipoOrcamentoChange({ silent: false, forceReload: true });
            });

            syncTipoOrcamentoHelp();
            handleTipoOrcamentoChange({ silent: true, forceReload: true });
        };

        equipTipoSelect.addEventListener('change', () => {
            if (normalizeId(equipamentoIdInput?.value || '') !== '') {
                if (equipamentoIdInput) equipamentoIdInput.value = '';
                if (equipamentoTituloHintInput) equipamentoTituloHintInput.value = '';
                clearVinculoEquipamentoCard({ hideColumn: true, keepEquipamentoId: true });
                setManualLocked(false);
                setEquipamentoLookupHint('Cadastro manual ativo para novo equipamento.', 'info');
            }
            refreshMarcaSelect('', '');
        });
        equipMarcaSelect.addEventListener('change', () => {
            if (normalizeId(equipamentoIdInput?.value || '') !== '') {
                if (equipamentoIdInput) equipamentoIdInput.value = '';
                if (equipamentoTituloHintInput) equipamentoTituloHintInput.value = '';
                clearVinculoEquipamentoCard({ hideColumn: true, keepEquipamentoId: true });
                setManualLocked(false);
                setEquipamentoLookupHint('Cadastro manual ativo para novo equipamento.', 'info');
            }
            refreshModeloSelect('');
        });
        if (hasSelect2) {
            const $equipMarca = jq(equipMarcaSelect);
            $equipMarca.off('select2:select.orcEquipMarca select2:clear.orcEquipMarca');
            $equipMarca.on('select2:select.orcEquipMarca select2:clear.orcEquipMarca', () => {
                if (normalizeId(equipamentoIdInput?.value || '') !== '') {
                    if (equipamentoIdInput) equipamentoIdInput.value = '';
                    if (equipamentoTituloHintInput) equipamentoTituloHintInput.value = '';
                    clearVinculoEquipamentoCard({ hideColumn: true, keepEquipamentoId: true });
                    setManualLocked(false);
                    setEquipamentoLookupHint('Cadastro manual ativo para novo equipamento.', 'info');
                }
                refreshModeloSelect('');
            });
        }

        if (equipCorPicker) {
            equipCorPicker.addEventListener('input', () => {
                const safeHex = normalizeHex(equipCorPicker.value);
                const nearest = findNearestColorByHex(safeHex);
                updateColorUI(safeHex, nearest?.name || '');
            });
        }
        if (equipCorNomeInput) {
            equipCorNomeInput.addEventListener('input', () => {
                const typedName = normalizeName(equipCorNomeInput.value);
                const byName = findColorByName(typedName);
                if (byName) {
                    updateColorUI(byName.hex, byName.name);
                    return;
                }

                const safeHex = normalizeHex(equipCorHexInput?.value || equipCorPicker?.value || '');
                updateColorUI(safeHex, typedName);
            });
        }

        const modalNovaMarcaEl = document.getElementById('modalNovaMarcaOrc');
        const modalNovoModeloEl = document.getElementById('modalNovoModeloOrc');
        const modalNovaMarca = (window.bootstrap && modalNovaMarcaEl) ? new window.bootstrap.Modal(modalNovaMarcaEl) : null;
        const modalNovoModelo = (window.bootstrap && modalNovoModeloEl) ? new window.bootstrap.Modal(modalNovoModeloEl) : null;
        let marcaModalMode = 'create';
        let modeloModalMode = 'create';

        const showMarcaModal = (mode) => {
            marcaModalMode = mode === 'edit' ? 'edit' : 'create';
            if (errorNovaMarcaOrc) {
                errorNovaMarcaOrc.classList.add('d-none');
                errorNovaMarcaOrc.textContent = '';
            }

            if (marcaModalMode === 'edit') {
                const marcaId = normalizeId(equipMarcaSelect.value);
                const marcaNome = normalizeName(equipMarcaSelect.options[equipMarcaSelect.selectedIndex]?.text || '');
                if (!marcaId) {
                    if (window.Swal) {
                        window.Swal.fire('Selecione uma marca', 'Escolha uma marca para editar.', 'warning');
                    }
                    return;
                }
                if (inputEditarMarcaIdOrc) inputEditarMarcaIdOrc.value = marcaId;
                if (inputNovaMarcaOrc) inputNovaMarcaOrc.value = marcaNome;
                if (labelModalMarcaOrc) labelModalMarcaOrc.innerHTML = '<i class="bi bi-pencil text-warning me-2"></i>Editar Marca';
                if (btnSalvarMarcaOrc) btnSalvarMarcaOrc.textContent = 'Salvar alteracao';
            } else {
                if (inputEditarMarcaIdOrc) inputEditarMarcaIdOrc.value = '';
                if (inputNovaMarcaOrc) inputNovaMarcaOrc.value = '';
                if (labelModalMarcaOrc) labelModalMarcaOrc.innerHTML = '<i class="bi bi-tag text-warning me-2"></i>Nova Marca';
                if (btnSalvarMarcaOrc) btnSalvarMarcaOrc.textContent = 'Salvar Marca';
            }

            modalNovaMarca?.show();
        };

        const showModeloModal = (mode) => {
            const marcaId = normalizeId(equipMarcaSelect.value);
            if (!marcaId) {
                if (window.Swal) {
                    window.Swal.fire('Selecione uma marca', 'Escolha a marca antes de cadastrar ou editar um modelo.', 'warning');
                }
                return;
            }

            modeloModalMode = mode === 'edit' ? 'edit' : 'create';
            if (displayMarcaOrc) {
                displayMarcaOrc.value = equipMarcaSelect.options[equipMarcaSelect.selectedIndex]?.text || '';
            }
            if (errorNovoModeloOrc) {
                errorNovoModeloOrc.classList.add('d-none');
                errorNovoModeloOrc.textContent = '';
            }

            if (modeloModalMode === 'edit') {
                const modeloId = normalizeId(equipModeloSelect.value);
                const modeloNome = normalizeName(equipModeloSelect.options[equipModeloSelect.selectedIndex]?.text || '');
                if (!modeloId) {
                    if (window.Swal) {
                        window.Swal.fire('Selecione um modelo', 'Escolha um modelo para editar.', 'warning');
                    }
                    return;
                }
                if (inputEditarModeloIdOrc) inputEditarModeloIdOrc.value = modeloId;
                if (inputNovoModeloOrc) inputNovoModeloOrc.value = modeloNome;
                if (labelModalModeloOrc) labelModalModeloOrc.innerHTML = '<i class="bi bi-pencil text-warning me-2"></i>Editar Modelo';
                if (btnSalvarModeloOrc) btnSalvarModeloOrc.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar alteracao';
            } else {
                if (inputEditarModeloIdOrc) inputEditarModeloIdOrc.value = '';
                if (inputNovoModeloOrc) inputNovoModeloOrc.value = '';
                if (labelModalModeloOrc) labelModalModeloOrc.innerHTML = '<i class="bi bi-cpu text-warning me-2"></i>Novo Modelo';
                if (btnSalvarModeloOrc) btnSalvarModeloOrc.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar Modelo';
            }

            modalNovoModelo?.show();
        };

        btnOrcNovaMarca?.addEventListener('click', () => showMarcaModal('create'));
        btnOrcEditarMarca?.addEventListener('click', () => showMarcaModal('edit'));
        btnOrcNovoModelo?.addEventListener('click', () => showModeloModal('create'));
        btnOrcEditarModelo?.addEventListener('click', () => showModeloModal('edit'));

        btnSalvarMarcaOrc?.addEventListener('click', async function () {
            const nome = normalizeName(inputNovaMarcaOrc?.value || '');
            const editingMarcaId = normalizeId(inputEditarMarcaIdOrc?.value || '');
            if (!nome) return;

            this.disabled = true;
            const formData = new FormData();
            formData.append('nome', nome);
            formData.append(csrfTokenName, csrfHashValue);

            const isEditMode = marcaModalMode === 'edit' && editingMarcaId !== '';
            const requestUrl = isEditMode
                ? `${equipamentoMarcaAtualizarBaseUrl}/${encodeURIComponent(editingMarcaId)}`
                : equipamentoMarcaSalvarUrl;

            try {
                const response = await fetch(requestUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                });
                const result = await response.json();
                if (!result?.success) {
                    const message = normalizeName(result?.message || 'Não foi possível salvar a marca.');
                    if (errorNovaMarcaOrc) {
                        errorNovaMarcaOrc.textContent = message;
                        errorNovaMarcaOrc.classList.remove('d-none');
                    }
                    return;
                }

                const tipoId = normalizeId(equipTipoSelect.value);
                const resultId = normalizeId(result.id || editingMarcaId);
                const resultName = normalizeName(result.nome || nome);

                if (isEditMode) {
                    renameMarcaInCatalog(resultId, resultName);
                    refreshMarcaSelect(resultId, normalizeId(equipModeloSelect.value));
                } else {
                    upsertMarca(tipoId, { id: resultId, nome: resultName });
                    refreshMarcaSelect(resultId, '');
                    refreshModeloSelect('');
                }

                modalNovaMarca?.hide();
            } catch (error) {
                if (errorNovaMarcaOrc) {
                    errorNovaMarcaOrc.textContent = 'Falha de comunicação ao salvar marca.';
                    errorNovaMarcaOrc.classList.remove('d-none');
                }
            } finally {
                this.disabled = false;
            }
        });

        btnSalvarModeloOrc?.addEventListener('click', async function () {
            const nome = normalizeName(inputNovoModeloOrc?.value || '');
            const marcaId = normalizeId(equipMarcaSelect.value);
            const tipoId = normalizeId(equipTipoSelect.value);
            const editingModeloId = normalizeId(inputEditarModeloIdOrc?.value || '');
            if (!nome || !marcaId) return;

            this.disabled = true;
            const formData = new FormData();
            formData.append('nome', nome);
            formData.append('marca_id', marcaId);
            if (tipoId) {
                formData.append('tipo_id', tipoId);
            }
            formData.append(csrfTokenName, csrfHashValue);

            const isEditMode = modeloModalMode === 'edit' && editingModeloId !== '';
            const requestUrl = isEditMode
                ? `${equipamentoModeloAtualizarBaseUrl}/${encodeURIComponent(editingModeloId)}`
                : equipamentoModeloSalvarUrl;

            try {
                const response = await fetch(requestUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                });
                const result = await response.json();
                if (!result?.success) {
                    const message = normalizeName(result?.message || 'Não foi possível salvar o modelo.');
                    if (errorNovoModeloOrc) {
                        errorNovoModeloOrc.textContent = message;
                        errorNovoModeloOrc.classList.remove('d-none');
                    }
                    return;
                }

                const resultId = normalizeId(result.id || editingModeloId);
                const resultName = normalizeName(result.nome || nome);
                const resultMarcaId = normalizeId(result.marca_id || marcaId);

                if (isEditMode) {
                    renameModeloInCatalog(resultId, resultMarcaId, resultName);
                } else {
                    upsertModelo(tipoId, resultMarcaId, { id: resultId, marca_id: resultMarcaId, nome: resultName });
                }

                refreshModeloSelect(resultId);
                modalNovoModelo?.hide();
            } catch (error) {
                if (errorNovoModeloOrc) {
                    errorNovoModeloOrc.textContent = 'Falha de comunicação ao salvar modelo.';
                    errorNovoModeloOrc.classList.remove('d-none');
                }
            } finally {
                this.disabled = false;
            }
        });

        refreshMarcaSelect(initialMarca, initialModelo);
        buildColorCatalog();
        const initialHexColor = normalizeHex(equipCorHexInput?.value || equipCorPicker?.value || '');
        const initialColorName = normalizeName(equipCorNomeInput?.value || '');
        if (initialHexColor) {
            const nearest = findNearestColorByHex(initialHexColor);
            updateColorUI(initialHexColor, initialColorName || nearest?.name || '');
        } else if (initialColorName) {
            const byName = findColorByName(initialColorName);
            if (byName) {
                updateColorUI(byName.hex, byName.name);
            } else {
                updateColorUI('', initialColorName);
            }
        } else {
            updateColorUI('', '');
        }

        initEquipamentoLookupByCliente();
    };

    initClienteLookup();
    initEquipamentoCatalogEditor();
    applyValidadeDias();
    setNomeAvulsoLocked((clienteIdInput?.value || '').trim() !== '' || (contatoIdInput?.value || '').trim() !== '');
    promptDraftRecovery();

    if (telefoneInput) {
        const handlePhoneInputChange = () => {
            syncTelefoneValidation();
            syncPacoteOfertaPhoneFromContato(true);
            scheduleDetectPacoteOferta();
        };
        ['input', 'blur', 'change'].forEach((eventName) => {
            telefoneInput.addEventListener(eventName, handlePhoneInputChange);
        });
        handlePhoneInputChange();
    }

    if (pacoteOfertaTelefoneInput) {
        const handlePacotePhoneInput = () => {
            if (!hasOrcamentoOsContext()) {
                syncPacoteOfertaPhoneFromContato(true);
            } else {
                const normalized = normalizeWhatsappDigits(pacoteOfertaTelefoneInput.value);
                pacoteOfertaTelefoneInput.value = formatWhatsappPhone(normalized);
            }
            scheduleDetectPacoteOferta();
        };
        ['input', 'blur', 'change'].forEach((eventName) => {
            pacoteOfertaTelefoneInput.addEventListener(eventName, handlePacotePhoneInput);
        });
    }

    if (pacoteOfertaMensagemInput) {
        ['input', 'blur', 'change'].forEach((eventName) => {
            pacoteOfertaMensagemInput.addEventListener(eventName, syncPacoteOfertaMensagemOption);
        });
        syncPacoteOfertaMensagemOption();
    }

    if (origemSelect) {
        origemSelect.addEventListener('change', () => {
            syncPacoteOfertaPhoneFromContato();
            scheduleDetectPacoteOferta();
        });
    }

    if (emailInput) {
        ['input', 'blur', 'change'].forEach((eventName) => {
            emailInput.addEventListener(eventName, syncEmailValidation);
        });
        syncEmailValidation();
    }

    if (nomeAvulsoInput) {
        ['input', 'blur', 'change'].forEach((eventName) => {
            nomeAvulsoInput.addEventListener(eventName, () => {
                scheduleDetectPacoteOferta();
            });
        });
    }

    document.addEventListener('orcamento:cliente-change', () => {
        scheduleDetectPacoteOferta();
    });

    pacoteOfertaApplyCheck?.addEventListener('change', syncPacoteOfertaApplyHidden);
    btnPacoteOfertaRefresh?.addEventListener('click', () => {
        detectPacoteOferta();
    });
    btnPacoteOfertaEnviar?.addEventListener('click', async () => {
        await sendPacoteOferta();
    });
    btnPacoteOfertaCopyLink?.addEventListener('click', async () => {
        const url = String(pacoteOfertaLinkBtn?.href || '').trim();
        if (!url || url === '#') {
            return;
        }
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(url);
            } else {
                const ta = document.createElement('textarea');
                ta.value = url;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
            }
            if (window.Swal) {
                await window.Swal.fire('Link copiado', 'O link da oferta foi copiado para a area de transferencia.', 'success');
            }
        } catch (error) {
            console.error('[Orçamentos] Falha ao copiar link da oferta.', error);
            if (window.Swal) {
                await window.Swal.fire('Falha ao copiar', 'Não foi possível copiar o link automaticamente.', 'warning');
            }
        }
    });

    if (formEl) {
        formEl.addEventListener('submit', async (event) => {
            if (formEl.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            formEl.dataset.submitting = '1';
            setSubmitLoading(true);

            const phoneDigits = syncTelefoneValidation();
            const emailValue = syncEmailValidation();
            const errors = [];
            const isPacoteBaseado = !!pacoteBaseadoSwitch?.checked;
            const tipoAssistencia = isTipoAssistencia();
            const clienteSelecionado = normalizeId(clienteIdInput?.value || '');
            const osSelecionada = normalizeId(osIdInput?.value || '');

            if (tipoAssistencia && clienteSelecionado === '') {
                errors.push('Tipo de orcamento: selecione um cliente cadastrado para vincular a OS aberta do equipamento em assistencia.');
            }

            if (tipoAssistencia && osSelecionada === '') {
                errors.push('Tipo de orcamento: selecione uma OS aberta para herdar o equipamento ja em assistencia.');
            }

            if (!tipoAssistencia && osSelecionada !== '') {
                errors.push('Tipo de orcamento: um orcamento previo não pode permanecer vinculado a uma OS aberta. Ajuste o tipo para "com equipamento na assistencia".');
            }

            if (!isWhatsappMobileValid(phoneDigits)) {
                errors.push('Telefone de contato: informe um celular WhatsApp com DDD (ex.: 11987654321).');
            }

            if (emailValue !== '' && !isEmailValid(emailValue)) {
                errors.push('Email de contato: informe um e-mail v\u00e1lido para envio do or\u00e7amento.');
            }

            syncPacoteOfertaApplyHidden();
            const pacoteOfertaApplyFlag = String(pacoteOfertaApplyFlagInput?.value || '0');
            const pacoteOfertaId = String(pacoteOfertaApplyIdInput?.value || '').trim();
            if (pacoteOfertaApplyFlag === '1' && pacoteOfertaId === '') {
                errors.push('Não foi possível aplicar a oferta de pacote. Atualize o status da oferta antes de salvar.');
            }
            const pacoteIdSelecionado = String(pacoteOfertaPacoteSelect?.value || '').trim();
            const pacoteTelefoneDigits = normalizeWhatsappDigits(
                String(pacoteOfertaTelefoneInput?.value || phoneDigits || '')
            );
            if (isPacoteBaseado && pacoteOfertaApplyFlag !== '1' && pacoteIdSelecionado === '') {
                errors.push('Orçamento baseado em pacote: selecione o pacote para envio da oferta.');
            }
            if (isPacoteBaseado && !isWhatsappMobileValid(pacoteTelefoneDigits)) {
                errors.push('Orçamento baseado em pacote: informe um telefone WhatsApp valido para envio do link.');
            }

            if (errors.length > 0) {
                event.preventDefault();
                formEl.dataset.submitting = '0';
                setSubmitLoading(false);
                await window.DSFeedback.fire({
                    icon: 'warning',
                    title: 'Revise os dados do orcamento',
                    html: errors.map((item) => `<div class="text-start">${escapeHtml(item)}</div>`).join(''),
                    confirmButtonText: 'Entendi',
                });
                return;
            }

            if (telefoneInput) {
                telefoneInput.value = normalizeWhatsappDigits(phoneDigits);
            }
            if (emailInput) {
                emailInput.value = emailValue;
            }
            if (pacoteOfertaTelefoneInput) {
                pacoteOfertaTelefoneInput.value = formatWhatsappPhone(pacoteTelefoneDigits);
            }

            if (isCreateMode && statusSelect && String(statusSelect.value || '') === DRAFT_STATUS_RASCUNHO) {
                statusSelect.value = isPacoteBaseado ? 'aguardando_pacote' : DRAFT_STATUS_PENDENTE_ENVIO;
                syncStatusFields();
            }

            clearDraftStorage();
            hideDraftAlert();
        });
    }

    scheduleDetectPacoteOferta();
    recalcAll();
})();
</script>
<style>
.orc-title-presets .btn,
.orc-title-presets .btn:focus {
    line-height: 1.2;
}
.orc-vinculo-card {
    border-radius: .85rem;
}
.orc-section-card {
    border-radius: 1rem;
}
.orc-section-card .card-header h5 {
    font-size: 1rem;
    font-weight: 700;
}
.orc-manual-locked .row.g-3 {
    opacity: .75;
}
.orc-manual-locked .row.g-3 .form-control:disabled,
.orc-manual-locked .row.g-3 .form-select:disabled {
    background-color: #f8f9fa;
}
.orc-inline-actions .btn,
.orc-inline-actions .btn:focus {
    line-height: 1.25;
}
#orcEquipCorPicker {
    width: 48px;
    max-width: 48px;
}
.orc-color-preview {
    min-height: 84px;
    background: rgba(0, 0, 0, 0.05);
    transition: background .2s ease;
}
.orc-color-preview-hex {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: .86rem;
    font-weight: 700;
    color: #6c757d;
}
.orc-color-preview-name {
    font-size: .76rem;
    color: #6c757d;
}
.orc-color-near-btn {
    width: 22px;
    height: 22px;
    cursor: pointer;
}
.orc-color-catalog {
    max-height: 228px;
    overflow-y: auto;
}
.orc-color-group + .orc-color-group {
    margin-top: .6rem;
}
.orc-color-group-title {
    font-size: .74rem;
    font-weight: 700;
    color: #6c757d;
    margin-bottom: .3rem;
}
.orc-color-item {
    font-size: .82rem;
}
.orc-color-item small {
    font-size: .7rem;
}
.item-pricing-meta {
    display: block;
    margin-top: .35rem;
    font-size: .74rem;
    line-height: 1.35;
}
.select2-container--default .select2-selection--single {
    min-height: 38px;
    border: 1px solid #dee2e6;
    border-radius: .5rem;
    padding: .2rem .35rem;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 30px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
@media (max-width: 430px) {
    .orc-draft-alert .d-flex.flex-wrap.gap-2 {
        width: 100%;
    }
    .orc-draft-alert .btn {
        width: 100%;
    }
    .orc-title-presets .btn {
        width: 100%;
    }
    .orc-inline-actions {
        width: 100%;
        justify-content: flex-start;
    }
    .orc-inline-actions .btn,
    .orc-inline-actions a.btn {
        font-size: .72rem;
    }
    .orc-color-catalog {
        max-height: 182px;
    }
    .orc-color-item {
        font-size: .78rem;
    }
    #orcamentoItensTable thead {
        display: none;
    }
    #orcamentoItensTable tbody tr {
        display: block;
        margin-bottom: .75rem;
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: .75rem;
    }
    #orcamentoItensTable tbody td {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .75rem;
        border: 0;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        padding: .65rem .75rem;
        white-space: normal;
    }
    #orcamentoItensTable tbody td::before {
        content: attr(data-label);
        min-width: 86px;
        font-weight: 600;
        color: #6c757d;
        font-size: .8rem;
    }
    #orcamentoItensTable tbody td:last-child {
        border-bottom: 0;
    }
}
@media (max-width: 390px) {
    #orcamentoItensTable tbody td::before {
        min-width: 80px;
    }
}
@media (max-width: 360px) {
    #orcamentoItensTable tbody td {
        padding: .55rem .6rem;
    }
    #orcEquipCorPicker {
        width: 44px;
        max-width: 44px;
    }
    .orc-color-preview {
        min-height: 76px;
    }
}
@media (max-width: 320px) {
    #orcamentoItensTable tbody td {
        gap: .5rem;
        padding: .5rem;
    }
    #orcamentoItensTable tbody td::before {
        min-width: 72px;
        font-size: .74rem;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->endSection() ?>

