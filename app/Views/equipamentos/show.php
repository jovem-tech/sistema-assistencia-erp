<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h2><i class="bi bi-display me-2"></i><?= esc($title) ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('equipamentos')" title="Ajuda sobre Equipamentos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('equipamentos/editar/' . $equipamento['id']) ?>" class="btn btn-glow"><i class="bi bi-pencil me-1"></i>Editar</a>
        <a href="<?= base_url('equipamentos') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('equipamentos') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<div class="row g-4">
    <!-- Seção 1: Foto e Card Principal -->
    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-body text-center">
                <div class="mb-4">
                    <?php
                    $principalArr = array_filter($fotos ?? [], fn($f) => (int) ($f['is_principal'] ?? 0) === 1);
                    $fotoPrincipal = !empty($principalArr) ? array_values($principalArr)[0] : (!empty($fotos) ? $fotos[0] : null);
                    $urlPrincipal = $fotoPrincipal['url'] ?? null;
                    ?>
                    
                    <?php if ($urlPrincipal): ?>
                        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= $urlPrincipal ?>" class="rounded bg-body-tertiary d-flex align-items-center justify-content-center overflow-hidden mx-auto border text-decoration-none" style="width: 200px; height: 200px; display: block; cursor: zoom-in;">
                            <img src="<?= $urlPrincipal ?>" alt="Foto Principal" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                        </a>
                    <?php else: ?>
                        <div class="rounded bg-body-tertiary d-flex align-items-center justify-content-center mx-auto border text-body-secondary" style="width: 200px; height: 200px;">
                            <div class="text-center">
                                <i class="bi bi-camera fs-1"></i>
                                <div class="mt-2">Sem foto</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <h4 class="mb-1 text-body"><?= esc($equipamento['marca_nome'] ?? 'Sem Marca') ?> <?= esc($equipamento['modelo_nome'] ?? 'Sem Modelo') ?></h4>
                <div class="text-body-secondary mb-3"><?= esc($equipamento['tipo_nome'] ?? '') ?></div>
                
                <?php if (!empty($equipamento['cor'])): ?>
                <div class="d-inline-flex align-items-center bg-body-tertiary px-3 py-1 rounded-pill border text-body">
                    <span class="d-inline-block rounded-circle me-2 border shadow-sm" style="width: 16px; height: 16px; background-color: <?= esc($equipamento['cor_hex'] ?? '#ccc') ?>;"></span>
                    <span><?= esc($equipamento['cor']) ?></span>
                </div>
                <?php endif; ?>

                <?php if(count($fotos) > 1): ?>
                <div class="mt-4 pt-3 border-top">
                    <h6 class="text-start mb-3 text-body">Galeria (<span id="galleryCount"><?= count($fotos) ?></span>)</h6>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <?php foreach($fotos as $foto): 
                            $urlThumb = $foto['url'] ?? null;
                        ?>
                            <?php if ($urlThumb): ?>
                            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= $urlThumb ?>" class="border rounded d-inline-block overflow-hidden" style="width: 60px; height: 60px; cursor: zoom-in;">
                                <img src="<?= $urlThumb ?>" class="w-100 h-100 object-fit-cover" title="<?= $foto['is_principal'] ? 'Principal' : 'Foto' ?>">
                            </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="row g-4 h-100">
            <!-- Seção 2: Informações do Equipamento e Seção 3: Proprietário -->
            <div class="col-12">
                <div class="card glass-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 border-end-md mb-4 mb-md-0">
                                <h5 class="text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Detalhes do Equipamento</h5>
                                
                                <table class="table table-sm table-borderless mb-0" style="--bs-table-bg: transparent;">
                                    <tbody>
                                        <tr>
                                            <th class="ps-0 w-40 text-body-secondary fw-normal">Nº Série:</th>
                                            <td class="fw-medium text-body"><?= !empty($equipamento['numero_serie']) ? esc($equipamento['numero_serie']) : '<span class="text-body-secondary opacity-50">N/I</span>' ?></td>
                                        </tr>
                                        <tr>
                                            <th class="ps-0 text-body-secondary fw-normal">IMEI:</th>
                                            <td class="fw-medium text-body"><?= !empty($equipamento['imei']) ? esc($equipamento['imei']) : '<span class="text-body-secondary opacity-50">N/I</span>' ?></td>
                                        </tr>
                                        <tr>
                                            <th class="ps-0 text-body-secondary fw-normal">Senha Acesso:</th>
                                            <td class="fw-medium text-body"><?= !empty($equipamento['senha_acesso']) ? esc($equipamento['senha_acesso']) : '<span class="text-body-secondary opacity-50">N/I</span>' ?></td>
                                        </tr>
                                        <tr>
                                            <th class="ps-0 text-body-secondary fw-normal">Cadastrado:</th>
                                            <td class="fw-medium text-body"><?= date('d/m/Y H:i', strtotime($equipamento['created_at'])) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="col-md-6 ps-md-4">
                                <h5 class="text-primary mb-3"><i class="bi bi-person-badge me-2"></i>Proprietário e Vínculos</h5>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" title="Proprietário Principal" style="width:40px; height:40px;">
                                        <i class="bi bi-star-fill fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-bold"><a href="<?= base_url('clientes/visualizar/' . $equipamento['cliente_id']) ?>" class="text-decoration-none text-body"><?= esc($equipamento['cliente_nome']) ?></a></h6>
                                        <small class="text-muted">Proprietário Principal</small>
                                    </div>
                                </div>
                                <hr class="border-secondary opacity-25">
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-body fw-bold mb-0">Clientes Vinculados</h6>
                                    <?php if(can('equipamentos', 'editar')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#vincularClienteModal">
                                            <i class="bi bi-link-45deg"></i> Vincular
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <?php if(empty($vinculados)): ?>
                                    <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Nenhum outro cliente está vinculado a utilizar este equipamento.</p>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush bg-transparent">
                                        <?php foreach($vinculados as $vinc): ?>
                                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-25">
                                                <div>
                                                    <i class="bi bi-person me-2 text-body-secondary"></i>
                                                    <a href="<?= base_url('clientes/visualizar/' . $vinc['id']) ?>" class="text-decoration-none text-body"><?= esc($vinc['nome_razao']) ?></a>
                                                </div>
                                                <?php if(can('equipamentos', 'editar')): ?>
                                                    <a href="<?= base_url('equipamentos/desvincular-cliente/' . $equipamento['id'] . '/' . $vinc['id']) ?>" class="btn btn-sm btn-link text-danger p-0" title="Desvincular Cliente" onclick="return confirm('Tem certeza que deseja desvincular este cliente do equipamento?');"><i class="bi bi-x-circle"></i></a>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                
                            </div>
                        </div>

                        <?php if(!empty($equipamento['estado_fisico']) || !empty($equipamento['acessorios']) || !empty($equipamento['observacoes'])): ?>
                        <div class="mt-4 pt-4 border-top">
                            <?php if(!empty($equipamento['estado_fisico'])): ?>
                            <div class="mb-3">
                                <h6 class="text-warning mb-1">Estado Físico</h6>
                                <p class="text-body-secondary small mb-0"><?= nl2br(esc($equipamento['estado_fisico'])) ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($equipamento['acessorios'])): ?>
                            <div class="mb-3">
                                <h6 class="text-warning mb-1">Acessórios Informados</h6>
                                <p class="text-body-secondary small mb-0"><?= nl2br(esc($equipamento['acessorios'])) ?></p>
                            </div>
                            <?php endif; ?>

                            <?php if(!empty($equipamento['observacoes'])): ?>
                            <div class="mb-0">
                                <h6 class="text-warning mb-1">Observações Adicionais</h6>
                                <p class="text-body-secondary small mb-0"><?= nl2br(esc($equipamento['observacoes'])) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Seção 4: Ordens de Serviço -->
            <div class="col-12 pt-3">
                <div class="card glass-card">
                    <div class="card-header border-bottom pb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-primary"><i class="bi bi-clipboard-data me-2"></i>Ordens de Serviço Vinculadas</h5>
                            <a href="<?= base_url('os/nova?equipamento=' . $equipamento['id']) ?>" class="btn btn-sm btn-glow"><i class="bi bi-plus me-1"></i>Nova OS</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if(empty($ordens)): ?>
                            <div class="text-center p-5 text-body-secondary">
                                <i class="bi bi-inbox fs-1 mb-2"></i>
                                <p class="mb-0">Nenhuma Ordem de Serviço cadastrada para este equipamento.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 align-middle" style="--bs-table-bg: transparent;">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>OS</th>
                                            <th>Status</th>
                                            <th>Abertura</th>
                                            <th>Síntese do Problema</th>
                                            <th class="text-end">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($ordens as $os): ?>
                                            <tr>
                                                <td class="fw-bold"><a href="<?= base_url('os/visualizar/' . $os['id']) ?>" class="text-decoration-none text-body">#<?= esc($os['numero_os']) ?></a></td>
                                                <td>
                                                    <span class="badge status-<?= $os['status'] ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $os['status'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($os['data_abertura'])) ?></td>
                                                <td class="small text-truncate text-body" style="max-width: 250px;"><?= esc($os['relato_cliente']) ?></td>
                                                <td class="text-end">
                                                    <a href="<?= base_url('os/visualizar/' . $os['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Visualizar OS"><i class="bi bi-eye"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Image View Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body text-center p-0 position-relative">
                <div class="d-inline-block position-relative">
                    <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" aria-label="Close" style="top: 10px; right: 10px; z-index: 1055; filter: invert(1); opacity: 1; background-color: rgba(0,0,0,0.6); border-radius: 50%; padding: 0.8rem; box-shadow: 0 4px 12px rgba(0,0,0,0.5);"></button>
                    <img src="" id="modalImagePreview" class="img-fluid rounded shadow-lg" style="max-height: 85vh; object-fit: contain; background: rgba(0,0,0,0.8);">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Vincular Cliente -->
<?php if(can('equipamentos', 'editar')): ?>
<div class="modal fade" id="vincularClienteModal" tabindex="-1" aria-labelledby="vincularClienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-secondary">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title text-body" id="vincularClienteModalLabel"><i class="bi bi-link-45deg me-2 text-primary"></i> Vincular Novo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('equipamentos/vincular-cliente') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="equipamento_id" value="<?= $equipamento['id'] ?>">
                <div class="modal-body">
                    <p class="text-muted small mb-3">Selecione um cliente para autorizar o uso deste equipamento. Ele passará a aparecer na lista de equipamentos do cliente ao abrir novas Ordens de Serviço.</p>
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label text-body">Selecione o Cliente</label>
                        <select class="form-select" id="cliente_id" name="cliente_id" required>
                            <option value="">Buscar cliente...</option>
                            <?php foreach($clientes_all as $cli): ?>
                                <option value="<?= $cli['id'] ?>"><?= esc($cli['nome_razao']) ?> <?= !empty($cli['cpf_cnpj']) ? '('.$cli['cpf_cnpj'].')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Vincular Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Image Lightbox
        const imageModal = document.getElementById('imageModal');
        if (imageModal) {
            imageModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const imgSrc = button.getAttribute('data-img-src');
                const modalImg = imageModal.querySelector('#modalImagePreview');
                modalImg.src = imgSrc;
            });
            imageModal.addEventListener('hidden.bs.modal', function () {
                const modalImg = imageModal.querySelector('#modalImagePreview');
                modalImg.src = ''; // Clear image out of memory
            });
        }
        
        // Ativar select2 caso esteja dispon vel globalmente
        if(typeof jQuery !== 'undefined' && $.fn.select2) {
            $('#cliente_id').select2({
                dropdownParent: $('#vincularClienteModal'),
                theme: 'bootstrap-5'
            });
        }
    });
</script>
<?= $this->endSection() ?>
