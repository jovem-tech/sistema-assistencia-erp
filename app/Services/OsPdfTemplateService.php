<?php

namespace App\Services;

use App\Models\OsPdfTemplateModel;
use App\Models\OsStatusModel;

class OsPdfTemplateService
{
    private OsPdfTemplateModel $templateModel;
    private ?array $statusLabelCache = null;

    public function __construct()
    {
        $this->templateModel = new OsPdfTemplateModel();
    }

    public function getTemplateOptions(): array
    {
        $templates = $this->templateModel->getActive();
        if (empty($templates)) {
            return $this->fallbackOptions();
        }

        $options = [];
        foreach ($templates as $template) {
            $codigo = trim((string) ($template['codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $options[$codigo] = trim((string) ($template['nome'] ?? $codigo));
        }

        return $options + ['orcamento' => 'Orcamento'];
    }

    public function findByCode(string $codigo): ?array
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return null;
        }

        return $this->templateModel->byCode($codigo);
    }

    public function placeholderCatalog(): array
    {
        return [
            ['token' => '{{numero_os}}', 'descricao' => 'Numero completo da ordem de servico.'],
            ['token' => '{{cliente_nome}}', 'descricao' => 'Nome do cliente vinculado a OS.'],
            ['token' => '{{cliente_telefone}}', 'descricao' => 'Telefone principal do cliente.'],
            ['token' => '{{cliente_email}}', 'descricao' => 'E-mail principal do cliente.'],
            ['token' => '{{equipamento_tipo}}', 'descricao' => 'Tipo do equipamento.'],
            ['token' => '{{equipamento_marca}}', 'descricao' => 'Marca do equipamento.'],
            ['token' => '{{equipamento_modelo}}', 'descricao' => 'Modelo do equipamento.'],
            ['token' => '{{equipamento_serie}}', 'descricao' => 'Serie, IMEI ou identificador do equipamento.'],
            ['token' => '{{equipamento_resumo}}', 'descricao' => 'Resumo consolidado do equipamento.'],
            ['token' => '{{status_atual}}', 'descricao' => 'Status atual da OS em linguagem amigavel.'],
            ['token' => '{{prioridade}}', 'descricao' => 'Prioridade operacional da OS.'],
            ['token' => '{{data_abertura}}', 'descricao' => 'Data/hora da abertura da OS.'],
            ['token' => '{{data_entrada}}', 'descricao' => 'Data/hora de entrada do equipamento.'],
            ['token' => '{{data_previsao}}', 'descricao' => 'Previsao de entrega formatada.'],
            ['token' => '{{data_entrega}}', 'descricao' => 'Data de entrega registrada, quando existir.'],
            ['token' => '{{relato_cliente}}', 'descricao' => 'Relato informado pelo cliente.'],
            ['token' => '{{diagnostico}}', 'descricao' => 'Diagnostico tecnico consolidado.'],
            ['token' => '{{solucao_aplicada}}', 'descricao' => 'Solucao aplicada na OS.'],
            ['token' => '{{observacoes_cliente}}', 'descricao' => 'Observacoes voltadas ao cliente.'],
            ['token' => '{{observacoes_internas}}', 'descricao' => 'Observacoes internas da equipe.'],
            ['token' => '{{valor_total}}', 'descricao' => 'Subtotal financeiro da OS.'],
            ['token' => '{{valor_final}}', 'descricao' => 'Valor final da OS.'],
            ['token' => '{{desconto}}', 'descricao' => 'Desconto financeiro formatado.'],
            ['token' => '{{forma_pagamento}}', 'descricao' => 'Forma de pagamento registrada.'],
            ['token' => '{{procedimentos_executados_html}}', 'descricao' => 'Lista HTML com os procedimentos executados.'],
            ['token' => '{{acessorios_html}}', 'descricao' => 'Lista HTML com acessorios cadastrados.'],
            ['token' => '{{estado_fisico_html}}', 'descricao' => 'Lista HTML com estado fisico, pendencias do checklist e observacoes de entrada.'],
            ['token' => '{{servicos_html}}', 'descricao' => 'Tabela HTML com itens de servico.'],
            ['token' => '{{pecas_html}}', 'descricao' => 'Tabela HTML com pecas vinculadas.'],
            ['token' => '{{resumo_financeiro_html}}', 'descricao' => 'Tabela HTML resumindo valores, desconto, total e garantia.'],
        ];
    }

    public function renderTemplateHtml(array $template, array $os, array $payload = []): string
    {
        $conteudo = (string) ($template['conteudo_html'] ?? '');
        if (trim($conteudo) === '') {
            return '';
        }

        return strtr($conteudo, $this->buildVariables($os, $payload));
    }

    private function buildVariables(array $os, array $payload): array
    {
        $totais = is_array($payload['totais'] ?? null) ? $payload['totais'] : [];
        $resumoCobranca = is_array($payload['resumo_cobranca'] ?? null) ? $payload['resumo_cobranca'] : [];
        $acessorios = array_values((array) ($payload['acessorios'] ?? []));
        $estadoFisico = array_values((array) ($payload['estado_fisico'] ?? []));
        $checklistEntrada = is_array($payload['checklist_entrada'] ?? null) ? $payload['checklist_entrada'] : null;
        $equipamentoNome = equipamento_nome_exibicao($os);
        $equipamentoResumo = trim(implode(' | ', array_values(array_filter([
            trim((string) ($os['equip_tipo'] ?? '')),
            $equipamentoNome,
        ], static fn (string $value): bool => $value !== ''))));
        $equipamentoSerie = trim((string) ($os['equip_serie'] ?? $os['equip_serial'] ?? $os['serial'] ?? $os['equip_imei'] ?? '-'));

        return [
            '{{numero_os}}' => $this->escape((string) ($os['numero_os'] ?? '-')),
            '{{cliente_nome}}' => $this->escape((string) ($os['cliente_nome'] ?? '-')),
            '{{cliente_telefone}}' => $this->escape((string) ($os['cliente_telefone'] ?? '-')),
            '{{cliente_email}}' => $this->escape((string) ($os['cliente_email'] ?? '-')),
            '{{equipamento_tipo}}' => $this->escape((string) ($os['equip_tipo'] ?? '-')),
            '{{equipamento_marca}}' => $this->escape((string) ($os['equip_marca'] ?? '-')),
            '{{equipamento_modelo}}' => $this->escape($equipamentoNome !== '' ? $equipamentoNome : (string) ($os['equip_modelo'] ?? '-')),
            '{{equipamento_serie}}' => $this->escape($equipamentoSerie !== '' ? $equipamentoSerie : '-'),
            '{{equipamento_resumo}}' => $this->escape($equipamentoResumo !== '' ? $equipamentoResumo : '-'),
            '{{status_atual}}' => $this->escape($this->humanizeStatus((string) ($os['status'] ?? ''))),
            '{{prioridade}}' => $this->escape((string) ($os['prioridade'] ?? 'Normal')),
            '{{data_abertura}}' => $this->escape(formatDate($os['data_abertura'] ?? null, true)),
            '{{data_entrada}}' => $this->escape(formatDate($os['data_entrada'] ?? null, true)),
            '{{data_previsao}}' => $this->escape(formatDate($os['data_previsao'] ?? null)),
            '{{data_entrega}}' => $this->escape(formatDate($os['data_entrega'] ?? null, true)),
            '{{relato_cliente}}' => nl2br($this->escape((string) ($os['relato_cliente'] ?? 'Nao informado.'))),
            '{{diagnostico}}' => nl2br($this->escape((string) ($os['diagnostico_tecnico'] ?? 'Nao informado.'))),
            '{{solucao_aplicada}}' => nl2br($this->escape((string) ($os['solucao_aplicada'] ?? 'Nao informada.'))),
            '{{observacoes_cliente}}' => nl2br($this->escape((string) ($os['observacoes_cliente'] ?? 'Nao informadas.'))),
            '{{observacoes_internas}}' => nl2br($this->escape((string) ($os['observacoes_internas'] ?? 'Nao informadas.'))),
            '{{valor_total}}' => $this->escape(formatMoney($os['valor_total'] ?? ($totais['servicos'] ?? 0) + ($totais['pecas'] ?? 0))),
            '{{valor_final}}' => $this->escape(formatMoney($os['valor_final'] ?? 0)),
            '{{desconto}}' => $this->escape(formatMoney($os['desconto'] ?? 0)),
            '{{forma_pagamento}}' => $this->escape((string) ($resumoCobranca['forma_pagamento'] ?? 'A combinar')),
            '{{procedimentos_executados_html}}' => $this->buildSimpleList(
                (array) ($payload['procedimentos_executados'] ?? []),
                'Nenhum procedimento registrado.'
            ),
            '{{acessorios_html}}' => $this->buildAcessoriosHtml($acessorios),
            '{{estado_fisico_html}}' => $this->buildEstadoFisicoHtml($estadoFisico, $checklistEntrada),
            '{{servicos_html}}' => $this->buildItemsTable((array) ($payload['servicos'] ?? []), 'Nenhum servico lancado.'),
            '{{pecas_html}}' => $this->buildItemsTable((array) ($payload['pecas'] ?? []), 'Nenhuma peca lancada.'),
            '{{resumo_financeiro_html}}' => $this->buildResumoFinanceiro($resumoCobranca),
        ];
    }

    private function buildSimpleList(array $items, string $emptyMessage): string
    {
        $items = array_values(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            $items
        ), static fn (string $value): bool => $value !== ''));

        if (empty($items)) {
            return '<div class="highlight-box muted">' . $this->escape($emptyMessage) . '</div>';
        }

        return $this->buildSimpleListBody($items);
    }

    private function buildSimpleListBody(array $items): string
    {
        $items = array_values(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            $items
        ), static fn (string $value): bool => $value !== ''));

        if ($items === []) {
            return '';
        }

        $html = '<ul class="doc-list">';
        foreach ($items as $item) {
            $html .= '<li>' . $this->escape($item) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    private function buildAcessoriosHtml(array $acessorios): string
    {
        if ($acessorios === []) {
            return '<div class="highlight-box muted">Nenhum acessorio informado.</div>';
        }

        $items = [];
        foreach ($acessorios as $acessorio) {
            $descricao = trim((string) ($acessorio['descricao'] ?? ''));
            if ($descricao === '') {
                continue;
            }

            $detalhes = array_values(array_filter(
                array_map(
                    static fn ($value): string => trim((string) $value),
                    (array) ($acessorio['valores_resumo'] ?? [])
                ),
                static fn (string $value): bool => $value !== ''
            ));

            $items[] = $detalhes === []
                ? $descricao
                : ($descricao . ' (' . implode(' | ', $detalhes) . ')');
        }

        return $this->buildSimpleList($items, 'Nenhum acessorio informado.');
    }

    private function buildEstadoFisicoHtml(array $estadoFisico, ?array $checklistEntrada): string
    {
        $blocos = [];
        $descricoesRegistradas = [];
        $registrosEstado = [];

        foreach ($estadoFisico as $item) {
            $descricao = trim((string) ($item['descricao_dano'] ?? ''));
            if ($descricao === '') {
                continue;
            }

            $descricoesRegistradas[strtolower($descricao)] = true;
            $detalhes = array_values(array_filter(
                array_map(
                    static fn ($value): string => trim((string) $value),
                    (array) ($item['valores_resumo'] ?? [])
                ),
                static fn (string $value): bool => $value !== ''
            ));

            $registrosEstado[] = $detalhes === []
                ? $descricao
                : ($descricao . ' (' . implode(' | ', $detalhes) . ')');
        }

        if ($registrosEstado !== []) {
            $blocos[] = '<div class="highlight-box"><strong>Estado fisico registrado</strong>' . $this->buildSimpleListBody($registrosEstado) . '</div>';
        }

        $pendenciasChecklist = [];
        foreach ((array) ($checklistEntrada['itens'] ?? []) as $itemChecklist) {
            $status = strtolower(trim((string) ($itemChecklist['status'] ?? '')));
            if ($status !== 'discrepancia') {
                continue;
            }

            $descricao = trim((string) ($itemChecklist['descricao'] ?? ''));
            if ($descricao === '') {
                continue;
            }

            $observacao = trim((string) ($itemChecklist['observacao'] ?? ''));
            $jaRegistrado = isset($descricoesRegistradas[strtolower($descricao)]);
            if ($jaRegistrado && $observacao === '') {
                continue;
            }

            $pendenciasChecklist[] = $observacao !== ''
                ? ($descricao . ' - Obs.: ' . $observacao)
                : $descricao;
        }

        if ($pendenciasChecklist !== []) {
            $blocos[] = '<div class="highlight-box"><strong>Pendencias do checklist de entrada</strong>' . $this->buildSimpleListBody($pendenciasChecklist) . '</div>';
        }

        $observacoesEstado = trim((string) ($checklistEntrada['observacoes_estado'] ?? ''));
        if ($observacoesEstado !== '') {
            $blocos[] = '<div class="highlight-box"><strong>Observacoes do estado na entrada</strong><br>' . nl2br($this->escape($observacoesEstado)) . '</div>';
        }

        if ($blocos === []) {
            return '<div class="highlight-box muted">Nenhum dano fisico registrado.</div>';
        }

        return implode('', $blocos);
    }

    private function buildItemsTable(array $items, string $emptyMessage): string
    {
        if (empty($items)) {
            return '<div class="highlight-box muted">' . $this->escape($emptyMessage) . '</div>';
        }

        $html = '<table class="table"><thead><tr><th>Descricao</th><th>Qtd</th><th>Valor unit.</th><th>Total</th></tr></thead><tbody>';
        foreach ($items as $item) {
            $html .= '<tr>';
            $html .= '<td>' . $this->escape((string) ($item['descricao'] ?? '-')) . '</td>';
            $html .= '<td class="right">' . $this->escape(number_format((float) ($item['quantidade'] ?? 0), 2, ',', '.')) . '</td>';
            $html .= '<td class="right">' . $this->escape(formatMoney($item['valor_unitario'] ?? 0)) . '</td>';
            $html .= '<td class="right"><strong>' . $this->escape(formatMoney($item['valor_total'] ?? 0)) . '</strong></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    private function buildResumoFinanceiro(array $resumo): string
    {
        $garantia = trim((string) ($resumo['garantia_label'] ?? 'Nao informada'));
        $status = trim((string) ($resumo['status_atual'] ?? '-'));
        $dataEntrega = trim((string) ($resumo['data_entrega_label'] ?? '-'));
        $prazo = trim((string) ($resumo['prazo_label'] ?? '-'));

        return '<table class="grid">'
            . '<tr><td class="label">Mao de obra</td><td>' . $this->escape((string) ($resumo['valor_mao_obra_label'] ?? 'R$ 0,00')) . '</td><td class="label">Pecas</td><td>' . $this->escape((string) ($resumo['valor_pecas_label'] ?? 'R$ 0,00')) . '</td></tr>'
            . '<tr><td class="label">Subtotal</td><td>' . $this->escape((string) ($resumo['valor_total_label'] ?? 'R$ 0,00')) . '</td><td class="label">Desconto</td><td>' . $this->escape((string) ($resumo['desconto_label'] ?? 'R$ 0,00')) . '</td></tr>'
            . '<tr><td class="label">Valor final</td><td>' . $this->escape((string) ($resumo['valor_final_label'] ?? 'R$ 0,00')) . '</td><td class="label">Forma de pagamento</td><td>' . $this->escape((string) ($resumo['forma_pagamento'] ?? 'A combinar')) . '</td></tr>'
            . '<tr><td class="label">Garantia</td><td>' . $this->escape($garantia) . '</td><td class="label">Status atual</td><td>' . $this->escape($status) . '</td></tr>'
            . '<tr><td class="label">Previsao</td><td>' . $this->escape($prazo) . '</td><td class="label">Entrega</td><td>' . $this->escape($dataEntrega) . '</td></tr>'
            . '</table>';
    }

    private function humanizeStatus(string $status): string
    {
        return $this->labelForStatusCode($status);
    }

    public function labelForStatusCode(string $status): string
    {
        $status = trim($status);
        if ($status === '') {
            return '-';
        }

        if ($this->statusLabelCache === null) {
            $this->statusLabelCache = [];
            $model = new OsStatusModel();
            if ($model->db->tableExists('os_status')) {
                foreach ($model->findAll() as $row) {
                    $codigo = trim((string) ($row['codigo'] ?? ''));
                    $nome = trim((string) ($row['nome'] ?? ''));
                    if ($codigo !== '' && $nome !== '') {
                        $this->statusLabelCache[$codigo] = $nome;
                    }
                }
            }
        }

        if (isset($this->statusLabelCache[$status]) && $this->statusLabelCache[$status] !== '') {
            return $this->statusLabelCache[$status];
        }

        $fallback = [
            'triagem' => 'Triagem',
            'diagnostico' => 'Diagnostico Tecnico',
            'aguardando_avaliacao' => 'Aguardando Avaliacao',
            'verificacao_garantia' => 'Verificacao de Garantia',
            'aguardando_orcamento' => 'Aguardando Orcamento',
            'aguardando_autorizacao' => 'Aguardando Autorizacao',
            'aguardando_reparo' => 'Aguardando Reparo',
            'reparo_execucao' => 'Em Execucao do Servico',
            'cumprimento_garantia' => 'Cumprimento de Garantia',
            'retrabalho' => 'Retrabalho',
            'testes_operacionais' => 'Testes Operacionais',
            'testes_finais' => 'Testes Finais',
            'aguardando_peca' => 'Aguardando Peca',
            'pagamento_pendente' => 'Pagamento Pendente',
            'entregue_pagamento_pendente' => 'Entregue - Pendencia Financeira',
            'reparo_concluido' => 'Reparo Concluido',
            'reparado_disponivel_loja' => 'Reparado, Disponivel na Loja',
            'garantia_concluida' => 'Garantia Concluida',
            'irreparavel' => 'Irreparavel',
            'irreparavel_disponivel_loja' => 'Irreparavel, Disponivel para Retirada',
            'reparo_recusado' => 'Reparo Recusado',
            'entregue_reparado' => 'Equipamento Entregue',
            'devolvido_sem_reparo' => 'Devolvido Sem Reparo',
            'descartado' => 'Equipamento Descartado',
            'cancelado' => 'Cancelado',
            'aguardando_analise' => 'Aguardando Analise',
            'aguardando_aprovacao' => 'Aguardando Aprovacao',
            'aprovado' => 'Aprovado',
            'em_reparo' => 'Em Reparo',
            'pronto' => 'Pronto',
            'entregue' => 'Entregue',
        ];

        return $fallback[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function fallbackOptions(): array
    {
        return [
            'abertura' => 'Comprovante de abertura',
            'laudo' => 'Laudo tecnico',
            'cobranca_manutencao' => 'Cobranca / manutencao',
            'entrega' => 'Comprovante de entrega',
            'devolucao_sem_reparo' => 'Devolucao sem reparo',
            'orcamento' => 'Orcamento',
        ];
    }
}
