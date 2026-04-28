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

        return $options + ['orcamento' => 'Orçamento'];
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
            ['token' => '{{numero_os}}', 'descricao' => 'Número completo da ordem de serviço.'],
            ['token' => '{{cliente_nome}}', 'descricao' => 'Nome do cliente vinculado à OS.'],
            ['token' => '{{cliente_telefone}}', 'descricao' => 'Telefone principal do cliente.'],
            ['token' => '{{cliente_email}}', 'descricao' => 'E-mail principal do cliente.'],
            ['token' => '{{equipamento_tipo}}', 'descricao' => 'Tipo do equipamento.'],
            ['token' => '{{equipamento_marca}}', 'descricao' => 'Marca do equipamento.'],
            ['token' => '{{equipamento_modelo}}', 'descricao' => 'Modelo do equipamento.'],
            ['token' => '{{equipamento_serie}}', 'descricao' => 'Série, IMEI ou identificador do equipamento.'],
            ['token' => '{{equipamento_resumo}}', 'descricao' => 'Resumo consolidado do equipamento.'],
            ['token' => '{{status_atual}}', 'descricao' => 'Status atual da OS em linguagem amigável.'],
            ['token' => '{{prioridade}}', 'descricao' => 'Prioridade operacional da OS.'],
            ['token' => '{{data_abertura}}', 'descricao' => 'Data/hora da abertura da OS.'],
            ['token' => '{{data_entrada}}', 'descricao' => 'Data/hora de entrada do equipamento.'],
            ['token' => '{{data_previsao}}', 'descricao' => 'Previsão de entrega formatada.'],
            ['token' => '{{data_entrega}}', 'descricao' => 'Data de entrega registrada, quando existir.'],
            ['token' => '{{relato_cliente}}', 'descricao' => 'Relato informado pelo cliente.'],
            ['token' => '{{diagnostico}}', 'descricao' => 'Diagnóstico técnico consolidado.'],
            ['token' => '{{solucao_aplicada}}', 'descricao' => 'Solução aplicada na OS.'],
            ['token' => '{{observacoes_cliente}}', 'descricao' => 'Observações voltadas ao cliente.'],
            ['token' => '{{observacoes_internas}}', 'descricao' => 'Observações internas da equipe.'],
            ['token' => '{{valor_total}}', 'descricao' => 'Subtotal financeiro da OS.'],
            ['token' => '{{valor_final}}', 'descricao' => 'Valor final da OS.'],
            ['token' => '{{desconto}}', 'descricao' => 'Desconto financeiro formatado.'],
            ['token' => '{{forma_pagamento}}', 'descricao' => 'Forma de pagamento registrada.'],
            ['token' => '{{procedimentos_executados_html}}', 'descricao' => 'Lista HTML com os procedimentos executados.'],
            ['token' => '{{acessorios_html}}', 'descricao' => 'Lista HTML com acessórios cadastrados.'],
            ['token' => '{{estado_fisico_html}}', 'descricao' => 'Lista HTML com estado físico/avarias.'],
            ['token' => '{{servicos_html}}', 'descricao' => 'Tabela HTML com itens de serviço.'],
            ['token' => '{{pecas_html}}', 'descricao' => 'Tabela HTML com peças vinculadas.'],
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
        $equipamentoResumo = trim(implode(' | ', array_values(array_filter([
            trim((string) ($os['equip_tipo'] ?? '')),
            trim((string) ($os['equip_marca'] ?? '')),
            trim((string) ($os['equip_modelo'] ?? '')),
        ], static fn (string $value): bool => $value !== ''))));

        return [
            '{{numero_os}}' => $this->escape((string) ($os['numero_os'] ?? '-')),
            '{{cliente_nome}}' => $this->escape((string) ($os['cliente_nome'] ?? '-')),
            '{{cliente_telefone}}' => $this->escape((string) ($os['cliente_telefone'] ?? '-')),
            '{{cliente_email}}' => $this->escape((string) ($os['cliente_email'] ?? '-')),
            '{{equipamento_tipo}}' => $this->escape((string) ($os['equip_tipo'] ?? '-')),
            '{{equipamento_marca}}' => $this->escape((string) ($os['equip_marca'] ?? '-')),
            '{{equipamento_modelo}}' => $this->escape((string) ($os['equip_modelo'] ?? '-')),
            '{{equipamento_serie}}' => $this->escape((string) ($os['equip_serial'] ?? $os['serial'] ?? '-')),
            '{{equipamento_resumo}}' => $this->escape($equipamentoResumo !== '' ? $equipamentoResumo : '-'),
            '{{status_atual}}' => $this->escape($this->humanizeStatus((string) ($os['status'] ?? ''))),
            '{{prioridade}}' => $this->escape((string) ($os['prioridade'] ?? 'Normal')),
            '{{data_abertura}}' => $this->escape(formatDate($os['data_abertura'] ?? null, true)),
            '{{data_entrada}}' => $this->escape(formatDate($os['data_entrada'] ?? null, true)),
            '{{data_previsao}}' => $this->escape(formatDate($os['data_previsao'] ?? null)),
            '{{data_entrega}}' => $this->escape(formatDate($os['data_entrega'] ?? null, true)),
            '{{relato_cliente}}' => nl2br($this->escape((string) ($os['relato'] ?? 'Não informado.'))),
            '{{diagnostico}}' => nl2br($this->escape((string) ($os['diagnostico'] ?? 'Não informado.'))),
            '{{solucao_aplicada}}' => nl2br($this->escape((string) ($os['solucao'] ?? 'Não informada.'))),
            '{{observacoes_cliente}}' => nl2br($this->escape((string) ($os['observacoes_cliente'] ?? 'Não informadas.'))),
            '{{observacoes_internas}}' => nl2br($this->escape((string) ($os['observacoes_internas'] ?? 'Não informadas.'))),
            '{{valor_total}}' => $this->escape(formatMoney($os['valor_total'] ?? ($totais['servicos'] ?? 0) + ($totais['pecas'] ?? 0))),
            '{{valor_final}}' => $this->escape(formatMoney($os['valor_final'] ?? 0)),
            '{{desconto}}' => $this->escape(formatMoney($os['desconto'] ?? 0)),
            '{{forma_pagamento}}' => $this->escape((string) ($resumoCobranca['forma_pagamento'] ?? 'A combinar')),
            '{{procedimentos_executados_html}}' => $this->buildSimpleList(
                (array) ($payload['procedimentos_executados'] ?? []),
                'Nenhum procedimento registrado.'
            ),
            '{{acessorios_html}}' => $this->buildSimpleList(
                array_map(
                    static fn (array $item): string => trim((string) ($item['descricao'] ?? '')),
                    (array) ($payload['acessorios'] ?? [])
                ),
                'Nenhum acessório informado.'
            ),
            '{{estado_fisico_html}}' => $this->buildSimpleList(
                array_map(
                    static fn (array $item): string => trim((string) ($item['descricao_dano'] ?? '')),
                    (array) ($payload['estado_fisico'] ?? [])
                ),
                'Nenhum dano físico registrado.'
            ),
            '{{servicos_html}}' => $this->buildItemsTable((array) ($payload['servicos'] ?? []), 'Nenhum serviço lançado.'),
            '{{pecas_html}}' => $this->buildItemsTable((array) ($payload['pecas'] ?? []), 'Nenhuma peça lançada.'),
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

        $html = '<ul class="doc-list">';
        foreach ($items as $item) {
            $html .= '<li>' . $this->escape($item) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    private function buildItemsTable(array $items, string $emptyMessage): string
    {
        if (empty($items)) {
            return '<div class="highlight-box muted">' . $this->escape($emptyMessage) . '</div>';
        }

        $html = '<table class="table"><thead><tr><th>Descrição</th><th>Qtd</th><th>Valor unit.</th><th>Total</th></tr></thead><tbody>';
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
        $garantia = trim((string) ($resumo['garantia_label'] ?? 'Não informada'));
        $status = trim((string) ($resumo['status_atual'] ?? '-'));
        $dataEntrega = trim((string) ($resumo['data_entrega_label'] ?? '-'));
        $prazo = trim((string) ($resumo['prazo_label'] ?? '-'));

        return '<table class="grid">'
            . '<tr><td class="label">Mão de obra</td><td>' . $this->escape((string) ($resumo['valor_mao_obra_label'] ?? 'R$ 0,00')) . '</td><td class="label">Peças</td><td>' . $this->escape((string) ($resumo['valor_pecas_label'] ?? 'R$ 0,00')) . '</td></tr>'
            . '<tr><td class="label">Subtotal</td><td>' . $this->escape((string) ($resumo['valor_total_label'] ?? 'R$ 0,00')) . '</td><td class="label">Desconto</td><td>' . $this->escape((string) ($resumo['desconto_label'] ?? 'R$ 0,00')) . '</td></tr>'
            . '<tr><td class="label">Valor final</td><td>' . $this->escape((string) ($resumo['valor_final_label'] ?? 'R$ 0,00')) . '</td><td class="label">Forma de pagamento</td><td>' . $this->escape((string) ($resumo['forma_pagamento'] ?? 'A combinar')) . '</td></tr>'
            . '<tr><td class="label">Garantia</td><td>' . $this->escape($garantia) . '</td><td class="label">Status atual</td><td>' . $this->escape($status) . '</td></tr>'
            . '<tr><td class="label">Previsão</td><td>' . $this->escape($prazo) . '</td><td class="label">Entrega</td><td>' . $this->escape($dataEntrega) . '</td></tr>'
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
            'diagnostico' => 'Diagnóstico Técnico',
            'aguardando_avaliacao' => 'Aguardando Avaliação',
            'verificacao_garantia' => 'Verificação de Garantia',
            'aguardando_orcamento' => 'Aguardando Orçamento',
            'aguardando_autorizacao' => 'Aguardando Autorização',
            'aguardando_reparo' => 'Aguardando Reparo',
            'reparo_execucao' => 'Em Execução do Serviço',
            'cumprimento_garantia' => 'Cumprimento de Garantia',
            'retrabalho' => 'Retrabalho',
            'testes_operacionais' => 'Testes Operacionais',
            'testes_finais' => 'Testes Finais',
            'aguardando_peca' => 'Aguardando Peça',
            'pagamento_pendente' => 'Pagamento Pendente',
            'entregue_pagamento_pendente' => 'Entregue - Pendência Financeira',
            'reparo_concluido' => 'Reparo Concluído',
            'reparado_disponivel_loja' => 'Reparado, Disponível na Loja',
            'garantia_concluida' => 'Garantia Concluída',
            'irreparavel' => 'Irreparável',
            'irreparavel_disponivel_loja' => 'Irreparável, Disponível para Retirada',
            'reparo_recusado' => 'Reparo Recusado',
            'entregue_reparado' => 'Equipamento Entregue',
            'devolvido_sem_reparo' => 'Devolvido Sem Reparo',
            'descartado' => 'Equipamento Descartado',
            'cancelado' => 'Cancelado',
            'aguardando_analise' => 'Aguardando Análise',
            'aguardando_aprovacao' => 'Aguardando Aprovação',
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
            'laudo' => 'Laudo técnico',
            'cobranca_manutencao' => 'Cobrança / manutenção',
            'entrega' => 'Comprovante de entrega',
            'devolucao_sem_reparo' => 'Devolução sem reparo',
            'orcamento' => 'Orçamento',
        ];
    }
}
