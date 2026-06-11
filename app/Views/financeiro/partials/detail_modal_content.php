<?php
$tipo = (string) ($lancamento['tipo'] ?? '');
$isReceber = $tipo === 'receber';
$status = (string) ($lancamento['status_resolvido'] ?? $lancamento['status'] ?? '');
$movimentos = is_array($movimentos ?? null) ? $movimentos : [];
$valorTitulo = (float) ($lancamento['valor_titulo'] ?? $lancamento['valor'] ?? 0);
$valorMovimentado = (float) ($lancamento['valor_movimentado'] ?? 0);
$valorAberto = (float) ($lancamento['valor_aberto'] ?? max(0, $valorTitulo - $valorMovimentado));
$equipamentoLabel = is_array($osDetalhes) ? equipamento_nome_exibicao($osDetalhes) : '';
$formatDateTime = static function ($value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '-';
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return $raw;
    }

    return date('d/m/Y H:i', $timestamp);
};
?>

<div class="finance-detail-layout">
    <div class="finance-detail-hero">
        <div>
            <div class="small text-uppercase text-muted fw-semibold mb-2">
                <?= $isReceber ? 'Receita' : 'Despesa' ?>
                <?php if (! empty($lancamento['numero_os'])): ?>
                    <span class="mx-2">|</span>OS <?= esc($lancamento['numero_os']) ?>
                <?php endif; ?>
            </div>
            <h4 class="mb-1"><?= esc($lancamento['descricao'] ?? '-') ?></h4>
            <div class="text-muted">
                <?= esc($lancamento['categoria'] ?? '-') ?>
                <?php if (! $isReceber && ! empty($lancamento['fornecedor_nome'])): ?>
                    <span class="mx-1">|</span><?= esc($lancamento['fornecedor_nome']) ?>
                <?php endif; ?>
                <?php if (! empty($lancamento['cliente_nome'])): ?>
                    <span class="mx-1">|</span><?= esc($lancamento['cliente_nome']) ?>
                <?php endif; ?>
                <?php if (! empty($lancamento['equip_marca']) || ! empty($lancamento['equip_modelo']) || ! empty($lancamento['equip_resumo_tecnico'])): ?>
                    <span class="mx-1">|</span><?= esc(equipamento_nome_exibicao($lancamento)) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-md-end">
            <div class="finance-detail-amount <?= $isReceber ? 'text-success' : 'text-danger' ?>"><?= formatMoney($valorTitulo) ?></div>
            <div class="small text-muted mt-2">Quitado <?= formatMoney($valorMovimentado) ?> â€¢ Em aberto <?= formatMoney($valorAberto) ?></div>
            <div class="mt-2">
                <?php if ($status === 'pago'): ?>
                    <span class="badge bg-success">Pago</span>
                <?php elseif ($status === 'cancelado'): ?>
                    <span class="badge bg-secondary">Cancelado</span>
                <?php elseif ($status === 'parcial'): ?>
                    <span class="badge <?= ((int) ($lancamento['esta_vencido'] ?? 0)) === 1 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                        <?= ((int) ($lancamento['esta_vencido'] ?? 0)) === 1 ? 'Parcial vencido' : 'Parcial' ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">Pendente</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-5">
            <div class="card h-100 border-0 bg-body-tertiary finance-detail-card">
                <div class="card-body">
                    <h6 class="mb-3">Detalhes financeiros</h6>
                    <div class="finance-detail-pair">
                        <span>ID</span>
                        <strong>#<?= (int) ($lancamento['id'] ?? 0) ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Tipo</span>
                        <strong><?= $isReceber ? 'A receber' : 'A pagar' ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Categoria</span>
                        <strong><?= esc($lancamento['categoria'] ?? '-') ?></strong>
                    </div>
                    <?php if (! $isReceber): ?>
                    <div class="finance-detail-pair">
                        <span>Fornecedor</span>
                        <strong><?= esc($lancamento['fornecedor_nome'] ?? '-') ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="finance-detail-pair">
                        <span>Valor total</span>
                        <strong><?= formatMoney($valorTitulo) ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Valor quitado</span>
                        <strong class="text-success"><?= formatMoney($valorMovimentado) ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Saldo em aberto</span>
                        <strong class="<?= $valorAberto > 0 ? 'text-danger' : 'text-success' ?>"><?= formatMoney($valorAberto) ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Total de baixas</span>
                        <strong><?= (int) ($lancamento['total_movimentos'] ?? 0) ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Competencia</span>
                        <strong><?= formatCompetenceDate($lancamento['data_competencia'] ?? '', $lancamento['origem_tipo_resolvido'] ?? $lancamento['origem_tipo'] ?? null) ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Vencimento</span>
                        <strong><?= formatDate($lancamento['data_vencimento'] ?? '') ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Ultima baixa</span>
                        <strong><?= formatDate($lancamento['data_pagamento_resolvida'] ?? $lancamento['data_pagamento'] ?? '') ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Forma de pagamento</span>
                        <strong><?= esc($lancamento['formas_pagamento_resumo'] ?? $lancamento['forma_pagamento_resolvida'] ?? $lancamento['forma_pagamento'] ?? '-') ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Grupo DRE</span>
                        <strong><?= esc($lancamento['grupo_dre'] ?? '-') ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Subgrupo DRE</span>
                        <strong><?= esc($lancamento['subgrupo_dre'] ?? '-') ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Origem</span>
                        <strong><?= esc($lancamento['origem_tipo_label_resolvido'] ?? $lancamento['origem_tipo'] ?? '-') ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Recorrencia DRE</span>
                        <strong><?= ((int) ($lancamento['dre_fixo_mensal'] ?? 0)) === 1 ? 'Despesa fixa mensal' : 'Nao' ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>ID de origem</span>
                        <strong><?= ! empty($lancamento['origem_id']) ? (int) $lancamento['origem_id'] : '-' ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>OS vinculada</span>
                        <strong><?= ! empty($lancamento['numero_os']) ? 'OS ' . esc($lancamento['numero_os']) : 'Nao' ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Criado em</span>
                        <strong><?= esc($formatDateTime($lancamento['created_at'] ?? '')) ?></strong>
                    </div>
                    <div class="finance-detail-pair">
                        <span>Atualizado em</span>
                        <strong><?= esc($formatDateTime($lancamento['updated_at'] ?? '')) ?></strong>
                    </div>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <span class="badge text-bg-light">DRE <?= ((int) ($lancamento['impacta_dre'] ?? 1)) === 1 ? 'sim' : 'nao' ?></span>
                        <span class="badge text-bg-light">Caixa <?= ((int) ($lancamento['impacta_fluxo_caixa'] ?? 1)) === 1 ? 'sim' : 'nao' ?></span>
                    </div>
                    <?php if (! empty($lancamento['observacoes'])): ?>
                    <div class="mt-3">
                        <div class="small text-uppercase text-muted fw-semibold mb-1">Observacoes</div>
                        <div class="finance-detail-box"><?= nl2br(esc((string) $lancamento['observacoes'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 bg-body-tertiary finance-detail-card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-1">Historico de baixas</h6>
                            <div class="text-muted small">Cada registro abaixo representa uma entrada ou saida real que alimentou o fluxo de caixa.</div>
                        </div>
                        <span class="badge text-bg-light"><?= count($movimentos) ?> movimento(s)</span>
                    </div>

                    <?php if (! empty($movimentos)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Forma</th>
                                    <th>Observacoes</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimentos as $movimento): ?>
                                <tr>
                                    <td><?= formatDate($movimento['data_movimento'] ?? '') ?></td>
                                    <td><?= esc($movimento['forma_pagamento'] ?? '-') ?></td>
                                    <td><?= esc($movimento['observacoes'] ?? '-') ?></td>
                                    <td class="text-end"><?= formatMoney($movimento['valor_movimento'] ?? 0) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-muted">Nenhuma baixa registrada ate o momento para este titulo.</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (is_array($osDetalhes)): ?>
            <div class="card border-0 bg-body-tertiary finance-detail-card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-1">Contexto da ordem de servico</h6>
                            <div class="text-muted small">Quando o lancamento veio de uma OS, o modal mostra o cliente, o equipamento e o servico executado.</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= base_url('os/visualizar/' . (int) ($osDetalhes['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Abrir OS
                            </a>
                            <?php if (! empty($osDetalhes['cliente_id'])): ?>
                            <a href="<?= base_url('clientes/visualizar/' . (int) $osDetalhes['cliente_id']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                                <i class="bi bi-person me-1"></i>Cliente
                            </a>
                            <?php endif; ?>
                            <?php if (! empty($osDetalhes['equipamento_id'])): ?>
                            <a href="<?= base_url('equipamentos/visualizar/' . (int) $osDetalhes['equipamento_id']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                                <i class="bi bi-display me-1"></i>Equipamento
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="finance-detail-box h-100">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Cliente</div>
                                <div class="fw-semibold"><?= esc($osDetalhes['cliente_nome'] ?? '-') ?></div>
                                <div class="small text-muted mt-1"><?= esc($osDetalhes['cliente_documento'] ?? '-') ?></div>
                                <div class="small mt-2"><?= esc($osDetalhes['cliente_telefone'] ?? '-') ?></div>
                                <?php if (! empty($osDetalhes['cliente_telefone2'])): ?>
                                <div class="small"><?= esc($osDetalhes['cliente_telefone2']) ?></div>
                                <?php endif; ?>
                                <div class="small"><?= esc($osDetalhes['cliente_email'] ?? '-') ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="finance-detail-box h-100">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Equipamento</div>
                                <div class="fw-semibold"><?= esc($equipamentoLabel !== '' ? $equipamentoLabel : '-') ?></div>
                                <div class="small mt-2">Serie: <?= esc($osDetalhes['equip_serie'] ?? '-') ?></div>
                                <div class="small">IMEI: <?= esc($osDetalhes['equip_imei'] ?? '-') ?></div>
                                <div class="small">Cor: <?= esc($osDetalhes['equip_cor'] ?? '-') ?></div>
                                <div class="small">Senha de acesso: <?= esc($osDetalhes['equip_senha'] ?? '-') ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="finance-detail-box h-100">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Diagnostico e solucao</div>
                                <div class="small text-muted mb-1">Relato do cliente</div>
                                <div class="mb-3"><?= nl2br(esc((string) ($osDetalhes['relato_cliente'] ?? 'Nao informado.'))) ?></div>
                                <div class="small text-muted mb-1">Diagnostico tecnico</div>
                                <div class="mb-3"><?= nl2br(esc((string) ($osDetalhes['diagnostico_tecnico'] ?? 'Nao informado.'))) ?></div>
                                <div class="small text-muted mb-1">Solucao aplicada</div>
                                <div><?= nl2br(esc((string) ($osDetalhes['solucao_aplicada'] ?? 'Nao informado.'))) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="finance-detail-box h-100">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Dados operacionais</div>
                                <div class="finance-detail-pair">
                                    <span>Status da OS</span>
                                    <strong><?= esc($osDetalhes['status'] ?? '-') ?></strong>
                                </div>
                                <div class="finance-detail-pair">
                                    <span>Tecnico</span>
                                    <strong><?= esc($osDetalhes['tecnico_nome'] ?? 'Nao atribuido') ?></strong>
                                </div>
                                <div class="finance-detail-pair">
                                    <span>Entrada</span>
                                    <strong><?= formatDate($osDetalhes['data_entrada'] ?? $osDetalhes['data_abertura'] ?? '') ?></strong>
                                </div>
                                <div class="finance-detail-pair">
                                    <span>Entrega</span>
                                    <strong><?= formatDate($osDetalhes['data_entrega'] ?? '') ?></strong>
                                </div>
                                <div class="finance-detail-pair">
                                    <span>Valor final da OS</span>
                                    <strong><?= formatMoney($osDetalhes['valor_final'] ?? 0) ?></strong>
                                </div>
                                <?php if (! empty($osDetalhes['observacoes_cliente'])): ?>
                                <div class="small text-muted mt-3 mb-1">Observacoes do cliente</div>
                                <div><?= nl2br(esc((string) $osDetalhes['observacoes_cliente'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card border-0 bg-body-tertiary finance-detail-card h-100">
                        <div class="card-body">
                            <h6 class="mb-3">Itens e servicos vinculados</h6>
                            <?php if (! empty($osItens)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Descricao</th>
                                                <th>Qtd.</th>
                                                <th>Unit.</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($osItens as $item): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?= ($item['tipo'] ?? '') === 'servico' ? 'bg-primary' : 'bg-secondary' ?>">
                                                        <?= esc(ucfirst((string) ($item['tipo'] ?? '-'))) ?>
                                                    </span>
                                                </td>
                                                <td><?= esc($item['descricao'] ?? '-') ?></td>
                                                <td><?= (int) ($item['quantidade'] ?? 0) ?></td>
                                                <td><?= formatMoney($item['valor_unitario'] ?? 0) ?></td>
                                                <td><?= formatMoney($item['valor_total'] ?? 0) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">Nenhum item registrado para esta OS.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 bg-body-tertiary finance-detail-card h-100">
                        <div class="card-body">
                            <h6 class="mb-3">Defeitos relatados e observacoes</h6>
                            <?php if (! empty($osDefeitos)): ?>
                                <div class="d-flex flex-column gap-2 mb-3">
                                    <?php foreach ($osDefeitos as $defeito): ?>
                                    <div class="finance-detail-box">
                                        <div class="fw-semibold"><?= esc($defeito['nome'] ?? '-') ?></div>
                                        <div class="small text-muted"><?= esc($defeito['classificacao'] ?? '-') ?></div>
                                        <?php if (! empty($defeito['descricao'])): ?>
                                        <div class="small mt-1"><?= esc($defeito['descricao']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted mb-3">Nenhum defeito estruturado registrado.</div>
                            <?php endif; ?>

                            <?php if (! empty($osDetalhes['procedimentos_executados'])): ?>
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Procedimentos executados</div>
                            <div class="finance-detail-box"><?= nl2br(esc((string) $osDetalhes['procedimentos_executados'])) ?></div>
                            <?php endif; ?>

                            <?php if (! empty($osDetalhes['equip_observacoes'])): ?>
                            <div class="small text-uppercase text-muted fw-semibold mt-3 mb-2">Observacoes do equipamento</div>
                            <div class="finance-detail-box"><?= nl2br(esc((string) $osDetalhes['equip_observacoes'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-0 bg-body-tertiary finance-detail-card h-100">
                <div class="card-body">
                    <h6 class="mb-3">Detalhamento da conta</h6>
                    <p class="text-muted mb-0">
                        Este lancamento nao possui uma OS vinculada. O detalhamento disponivel e o proprio cadastro financeiro, incluindo classificacao, datas, forma de pagamento e observacoes.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
