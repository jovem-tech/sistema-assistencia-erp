<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-items-center gap-2">
<h2><i class="bi bi-robot me-2"></i>Chatbot e Automação 24h</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('atendimento-whatsapp-chatbot')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<?= $this->include('central_mensagens/_menu') ?>

<div class="row g-3">
    <div class="col-12 col-xxl-7">
        <div class="card glass-card h-100">
            <div class="card-header fw-semibold">Intencoes do Bot</div>
            <div class="card-body">
                <form id="formIntencao" action="<?= base_url('atendimento-whatsapp/chatbot/intencao/salvar') ?>" method="post" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="intencaoId" value="0">
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">Codigo</label>
                        <input type="text" name="codigo" class="form-control form-control-sm" placeholder="consultar_status_os" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">Nome</label>
                        <input type="text" name="nome" class="form-control form-control-sm" placeholder="Consultar status da OS" required>
                    </div>
                    <div class="col-md-4">
                <label class="form-label form-label-sm">Ação Sistema</label>
                        <input type="text" name="acao_sistema" class="form-control form-control-sm" placeholder="consultar_os_status">
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-sm">Gatilhos (separar por virgula)</label>
                        <input type="text" name="gatilhos" class="form-control form-control-sm" placeholder="status, andamento, ficou pronto">
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-sm">Resposta padrao</label>
                        <textarea name="resposta_padrao" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm">Ordem</label>
                        <input type="number" name="ordem" class="form-control form-control-sm" value="0">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="exige_consulta_erp" value="1" id="chkConsultaErp">
                            <label class="form-check-label small" for="chkConsultaErp">Consulta ERP</label>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="ativo" value="1" id="chkIntencaoAtiva" checked>
                            <label class="form-check-label small" for="chkIntencaoAtiva">Ativa</label>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFormIntencao()">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        <button type="submit" class="btn btn-glow btn-sm">
                            <i class="bi bi-save me-1"></i>Salvar
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nome</th>
                                <th>Gatilhos</th>
                            <th>Ação Sistema</th>
                                <th class="text-center">Ativo</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($intencoes)): ?>
                                <?php foreach ($intencoes as $i): ?>
                                    <tr>
                                        <td><code><?= esc($i['codigo']) ?></code></td>
                                        <td><?= esc($i['nome']) ?></td>
                                        <td class="small text-muted text-truncate" style="max-width:220px;"><?= esc((string) $i['gatilhos_json']) ?></td>
                                        <td class="text-center">
                                            <form action="<?= base_url('atendimento-whatsapp/chatbot/intencao/toggle/' . (int) $i['id']) ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-xs <?= (int) $i['ativo'] === 1 ? 'btn-success' : 'btn-outline-secondary' ?>">
                                                    <?= (int) $i['ativo'] === 1 ? 'ON' : 'OFF' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick='editIntencao(<?= json_encode($i, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteIntencao(<?= (int) $i['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr><td colspan="5" class="text-muted">Nenhuma intenção cadastrada.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xxl-5">
        <div class="card glass-card mb-3">
                <div class="card-header fw-semibold">Regras ERP -> Automação</div>
            <div class="card-body">
                <form id="formRegra" action="<?= base_url('atendimento-whatsapp/chatbot/regra/salvar') ?>" method="post" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="regraId" value="0">
                    <div class="col-md-6">
                        <label class="form-label form-label-sm">Nome</label>
                        <input type="text" name="nome" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-sm">Evento origem</label>
                        <input type="text" name="evento_origem" class="form-control form-control-sm" placeholder="os_status_alterado" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-sm">Condicao JSON</label>
                        <textarea name="condicao_json" class="form-control form-control-sm" rows="2" placeholder='{"status":"reparado_disponivel_loja"}'></textarea>
                    </div>
                    <div class="col-12">
                    <label class="form-label form-label-sm">Ação JSON</label>
                        <textarea name="acao_json" class="form-control form-control-sm" rows="2" placeholder='{"tipo":"template","template":"equipamento_pronto"}'></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="ativo" value="1" id="chkRegraAtiva" checked>
                            <label class="form-check-label small" for="chkRegraAtiva">Ativa</label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFormRegra()">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            <button type="submit" class="btn btn-glow btn-sm">Salvar regra</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Evento</th>
                                <th class="text-center">Ativo</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($regras)): ?>
                                <?php foreach ($regras as $r): ?>
                                    <tr>
                                        <td><?= esc($r['nome']) ?></td>
                                        <td class="text-center">
                                            <form action="<?= base_url('atendimento-whatsapp/chatbot/regra/toggle/' . (int) $r['id']) ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-xs <?= (int) $r['ativo'] === 1 ? 'btn-success' : 'btn-outline-secondary' ?>">
                                                    <?= (int) $r['ativo'] === 1 ? 'ON' : 'OFF' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick='editRegra(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteRegra(<?= (int) $r['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-muted">Nenhuma regra cadastrada.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-header fw-semibold">Logs do Chatbot (ultimos 200)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Cliente</th>
                            <th>Intenção</th>
                                <th>Confianca</th>
                                <th>Escalado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($logs)): ?>
                                <?php foreach ($logs as $l): ?>
                                    <tr>
                                        <td class="small"><?= esc(date('d/m H:i', strtotime((string) $l['created_at']))) ?></td>
                                        <td class="small"><?= esc((string) ($l['cliente_nome'] ?: $l['telefone'] ?: '-')) ?></td>
                                        <td class="small"><code><?= esc((string) ($l['intencao_detectada'] ?? '-')) ?></code></td>
                                        <td class="small"><?= esc((string) ($l['confianca'] ?? '-')) ?></td>
                            <td class="small"><?= (int) ($l['escalado_humano'] ?? 0) === 1 ? 'Sim' : 'Não' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-muted p-3">Sem logs do chatbot.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function editIntencao(data) {
    const form = document.getElementById('formIntencao');
    document.getElementById('intencaoId').value = data.id;
    form.querySelector('[name="codigo"]').value = data.codigo || '';
    form.querySelector('[name="nome"]').value = data.nome || '';
    form.querySelector('[name="acao_sistema"]').value = data.acao_sistema || '';
    
    // Parse gatilhos do JSON
    let gatilhos = '';
    try {
        const arr = JSON.parse(data.gatilhos_json);
        if (Array.isArray(arr)) gatilhos = arr.join(', ');
    } catch(e) {}
    form.querySelector('[name="gatilhos"]').value = gatilhos;
    
    form.querySelector('[name="resposta_padrao"]').value = data.resposta_padrao || '';
    form.querySelector('[name="ordem"]').value = data.ordem || 0;
    form.querySelector('[name="exige_consulta_erp"]').checked = parseInt(data.exige_consulta_erp) === 1;
    form.querySelector('[name="ativo"]').checked = parseInt(data.ativo) === 1;
    
    form.querySelector('[name="codigo"]').focus();
    form.classList.add('border', 'border-primary', 'p-2', 'rounded');
}

function resetFormIntencao() {
    const form = document.getElementById('formIntencao');
    form.reset();
    document.getElementById('intencaoId').value = 0;
    form.classList.remove('border', 'border-primary', 'p-2', 'rounded');
}

function deleteIntencao(id) {
    Swal.fire({
            title: 'Excluir intenção?',
            text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sim, excluir'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= base_url('atendimento-whatsapp/chatbot/intencao/deletar') ?>/' + id;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '<?= csrf_token() ?>';
            csrf.value = '<?= csrf_hash() ?>';
            
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function editRegra(data) {
    const form = document.getElementById('formRegra');
    document.getElementById('regraId').value = data.id;
    form.querySelector('[name="nome"]').value = data.nome || '';
    form.querySelector('[name="evento_origem"]').value = data.evento_origem || '';
    form.querySelector('[name="condicao_json"]').value = data.condicao_json || '';
    form.querySelector('[name="acao_json"]').value = data.acao_json || '';
    form.querySelector('[name="ativo"]').checked = parseInt(data.ativo) === 1;
    
    form.querySelector('[name="nome"]').focus();
    form.classList.add('border', 'border-primary', 'p-2', 'rounded');
}

function resetFormRegra() {
    const form = document.getElementById('formRegra');
    form.reset();
    document.getElementById('regraId').value = 0;
    form.classList.remove('border', 'border-primary', 'p-2', 'rounded');
}

function deleteRegra(id) {
    Swal.fire({
        title: 'Excluir regra ERP?',
            text: "Deseja remover esta automação?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sim, excluir'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= base_url('atendimento-whatsapp/chatbot/regra/deletar') ?>/' + id;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '<?= csrf_token() ?>';
            csrf.value = '<?= csrf_hash() ?>';
            
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
<?= $this->endSection() ?>


