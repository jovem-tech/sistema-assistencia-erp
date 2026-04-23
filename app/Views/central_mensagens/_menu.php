<div class="card glass-card mb-4">
    <div class="card-body p-2">
        <nav class="nav nav-pills d-flex flex-wrap gap-1">
<a class="nav-link btn-sm <?= ($cmActive ?? '') === 'conversas' ? 'active shadow-sm' : 'text-secondary' ?>" href="<?= base_url('atendimento-whatsapp') ?>">
                <i class="bi bi-inboxes me-1"></i>WhatsApp OS
            </a>
<a class="nav-link btn-sm <?= ($cmActive ?? '') === 'chatbot' ? 'active shadow-sm' : 'text-secondary' ?>" href="<?= base_url('atendimento-whatsapp/chatbot') ?>">
                <i class="bi bi-robot me-1"></i>Chatbot
            </a>
<a class="nav-link btn-sm <?= ($cmActive ?? '') === 'metricas' ? 'active shadow-sm' : 'text-secondary' ?>" href="<?= base_url('atendimento-whatsapp/metricas') ?>">
                <i class="bi bi-graph-up me-1"></i>Métricas
            </a>
<a class="nav-link btn-sm <?= ($cmActive ?? '') === 'respostas' ? 'active shadow-sm' : 'text-secondary' ?>" href="<?= base_url('atendimento-whatsapp/respostas-rapidas') ?>">
                <i class="bi bi-chat-dots me-1"></i>Respostas Rápidas
            </a>
<a class="nav-link btn-sm <?= ($cmActive ?? '') === 'fluxos' ? 'active shadow-sm' : 'text-secondary' ?>" href="<?= base_url('atendimento-whatsapp/fluxos') ?>">
                <i class="bi bi-diagram-2 me-1"></i>Fluxos
            </a>
<a class="nav-link btn-sm <?= ($cmActive ?? '') === 'faq' ? 'active shadow-sm' : 'text-secondary' ?>" href="<?= base_url('atendimento-whatsapp/faq') ?>">
                <i class="bi bi-question-circle me-1"></i>FAQ
            </a>
<a class="nav-link btn-sm <?= ($cmActive ?? '') === 'filas' ? 'active shadow-sm' : 'text-secondary' ?>" href="<?= base_url('atendimento-whatsapp/filas') ?>">
                <i class="bi bi-people me-1"></i>Filas
            </a>
<a class="nav-link btn-sm <?= ($cmActive ?? '') === 'configuracoes' ? 'active shadow-sm' : 'text-secondary' ?>" href="<?= base_url('atendimento-whatsapp/configuracoes') ?>">
                <i class="bi bi-sliders me-1"></i>Configurações
            </a>
        </nav>
    </div>
</div>
