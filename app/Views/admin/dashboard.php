<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
    <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('dashboard')" title="Ajuda sobre o Dashboard">
        <i class="bi bi-question-circle me-1"></i>Ajuda
    </button>
</div>

<!-- Stats Cards Row -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-primary">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">OS Abertas</span>
                    <h2 class="stat-value"><?= $stats['total_abertas'] ?? 0 ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <a href="<?= base_url('os') ?>"><i class="bi bi-arrow-right me-1"></i>Ver detalhes</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-success">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Faturamento Mês</span>
                    <h2 class="stat-value">R$ <?= number_format($stats['faturamento_mes'] ?? 0, 2, ',', '.') ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <a href="<?= base_url('financeiro') ?>"><i class="bi bi-arrow-right me-1"></i>Ver financeiro</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-warning">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Aguardando Análise</span>
                    <h2 class="stat-value"><?= $stats['aguardando_analise'] ?? 0 ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <a href="<?= base_url('os?status=aguardando_analise') ?>"><i class="bi bi-arrow-right me-1"></i>Ver pendentes</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-card-info">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Total Clientes</span>
                    <h2 class="stat-value"><?= $total_clientes ?? 0 ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <a href="<?= base_url('clientes') ?>"><i class="bi bi-arrow-right me-1"></i>Ver clientes</a>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card glass-card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-graph-up me-2"></i>Faturamento Mensal</h5>
            </div>
            <div class="card-body">
                <canvas id="chartFaturamento" height="280"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4">
        <div class="card glass-card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-pie-chart me-2"></i>OS por Status</h5>
            </div>
            <div class="card-body">
                <canvas id="chartStatus" height="280"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats + Recent OS -->
<div class="row g-4 mb-4">
    <!-- Financial Summary -->
    <div class="col-xl-4">
        <div class="card glass-card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-wallet2 me-2"></i>Resumo Financeiro</h5>
            </div>
            <div class="card-body">
                <div class="finance-item finance-income">
                    <div class="finance-label">
                        <i class="bi bi-arrow-up-circle-fill text-success me-2"></i>
                        Receitas do Mês
                    </div>
                    <div class="finance-value text-success">
                        R$ <?= number_format($resumo_financeiro['receitas'] ?? 0, 2, ',', '.') ?>
                    </div>
                </div>
                <div class="finance-item finance-expense">
                    <div class="finance-label">
                        <i class="bi bi-arrow-down-circle-fill text-danger me-2"></i>
                        Despesas do Mês
                    </div>
                    <div class="finance-value text-danger">
                        R$ <?= number_format($resumo_financeiro['despesas'] ?? 0, 2, ',', '.') ?>
                    </div>
                </div>
                <hr>
                <div class="finance-item finance-profit">
                    <div class="finance-label">
                        <i class="bi bi-cash-stack me-2"></i>
                        <strong>Lucro</strong>
                    </div>
                    <div class="finance-value <?= ($resumo_financeiro['lucro'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <strong>R$ <?= number_format($resumo_financeiro['lucro'] ?? 0, 2, ',', '.') ?></strong>
                    </div>
                </div>
                <div class="finance-item mt-3">
                    <div class="finance-label">
                        <i class="bi bi-exclamation-circle text-warning me-2"></i>
                        Pendentes
                    </div>
                    <div class="finance-value text-warning">
                        R$ <?= number_format($resumo_financeiro['pendentes'] ?? 0, 2, ',', '.') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent OS -->
    <div class="col-xl-8">
        <div class="card glass-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Últimas Ordens de Serviço</h5>
                <?php if (can('os', 'criar')): ?>
                <a href="<?= base_url('os/nova') ?>" class="btn btn-glow btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Nova OS
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nº OS</th>
                                <th>Cliente</th>
                                <th>Equipamento</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($os_recentes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2">Nenhuma OS encontrada</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($os_recentes as $os): ?>
                                <tr>
                                    <td><strong><?= esc($os['numero_os']) ?></strong></td>
                                    <td><?= esc($os['cliente_nome']) ?></td>
                                    <td><?= esc($os['equip_marca'] . ' ' . $os['equip_modelo']) ?></td>
                                    <td><?= getStatusBadge($os['status']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($os['created_at'])) ?></td>
                                    <td>
                                        <a href="<?= base_url('os/visualizar/' . $os['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Alert -->
<?php if (!empty($estoque_baixo)): ?>
<div class="row g-4">
    <div class="col-12">
        <div class="card glass-card border-warning">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="card-title mb-0 text-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Alerta de Estoque Baixo
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Peça</th>
                                <th>Qtd. Atual</th>
                                <th>Mínimo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estoque_baixo as $peca): ?>
                            <tr>
                                <td><?= esc($peca['codigo']) ?></td>
                                <td><?= esc($peca['nome']) ?></td>
                                <td><span class="badge bg-danger"><?= $peca['quantidade_atual'] ?></span></td>
                                <td><?= $peca['estoque_minimo'] ?></td>
                                <td>
                                    <?php if (can('estoque', 'editar')): ?>
                                    <a href="<?= base_url('estoque/editar/' . $peca['id']) ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load chart data
    fetch('<?= base_url('admin/stats') ?>')
        .then(res => res.json())
        .then(data => {
            // Revenue Chart
            const ctxFat = document.getElementById('chartFaturamento').getContext('2d');
            new Chart(ctxFat, {
                type: 'bar',
                data: {
                    labels: data.faturamento.map(f => f.label),
                    datasets: [{
                        label: 'Faturamento (R$)',
                        data: data.faturamento.map(f => f.valor),
                        backgroundColor: 'rgba(99, 102, 241, 0.5)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#94a3b8' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8' }
                        }
                    }
                }
            });

            // Status Chart
            const statusLabels = {
                'aguardando_analise': 'Aguard. Análise',
                'aguardando_orcamento': 'Aguard. Orçamento',
                'aguardando_aprovacao': 'Aguard. Aprovação',
                'aprovado': 'Aprovado',
                'em_reparo': 'Em Reparo',
                'aguardando_peca': 'Aguard. Peça',
                'pronto': 'Pronto',
            };
            const statusColors = [
                '#f59e0b', '#8b5cf6', '#3b82f6', '#10b981', 
                '#6366f1', '#ef4444', '#22c55e'
            ];

            const ctxStatus = document.getElementById('chartStatus').getContext('2d');
            new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: data.status_count.map(s => statusLabels[s.status] || s.status),
                    datasets: [{
                        data: data.status_count.map(s => s.total),
                        backgroundColor: statusColors.slice(0, data.status_count.length),
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#94a3b8', padding: 12, font: { size: 11 } }
                        }
                    }
                }
            });
        })
        .catch(err => console.log('Erro ao carregar gráficos:', err));
});
</script>
<?= $this->endSection() ?>

