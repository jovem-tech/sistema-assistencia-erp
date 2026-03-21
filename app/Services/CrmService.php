<?php

namespace App\Services;

use App\Models\ChatbotRegraErpModel;
use App\Models\CrmEventoModel;
use App\Models\CrmFollowupModel;
use App\Models\CrmInteracaoModel;
use App\Models\CrmPipelineModel;
use App\Models\OsModel;
use App\Models\OsStatusModel;

class CrmService
{
    private CrmEventoModel $eventoModel;
    private CrmInteracaoModel $interacaoModel;
    private CrmFollowupModel $followupModel;
    private CrmPipelineModel $pipelineModel;
    private ChatbotRegraErpModel $regraErpModel;
    private OsModel $osModel;
    private OsStatusModel $statusModel;

    public function __construct()
    {
        $this->eventoModel = new CrmEventoModel();
        $this->interacaoModel = new CrmInteracaoModel();
        $this->followupModel = new CrmFollowupModel();
        $this->pipelineModel = new CrmPipelineModel();
        $this->regraErpModel = new ChatbotRegraErpModel();
        $this->osModel = new OsModel();
        $this->statusModel = new OsStatusModel();
    }

    public function registerEvent(array $data): ?int
    {
        if (!$this->eventoModel->db->tableExists('crm_eventos')) {
            return null;
        }

        $payload = $data['payload_json'] ?? null;
        if (is_array($payload)) {
            $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        $insert = [
            'cliente_id' => $data['cliente_id'] ?? null,
            'equipamento_id' => $data['equipamento_id'] ?? null,
            'os_id' => $data['os_id'] ?? null,
            'conversa_id' => $data['conversa_id'] ?? null,
            'tipo_evento' => (string) ($data['tipo_evento'] ?? 'evento'),
            'titulo' => (string) ($data['titulo'] ?? 'Evento CRM'),
            'descricao' => $data['descricao'] ?? null,
            'origem' => (string) ($data['origem'] ?? 'sistema'),
            'usuario_id' => $data['usuario_id'] ?? null,
            'data_evento' => (string) ($data['data_evento'] ?? date('Y-m-d H:i:s')),
            'payload_json' => $payload,
        ];

        return $this->eventoModel->insert($insert, true) ?: null;
    }

    public function registerInteraction(array $data): ?int
    {
        if (!$this->interacaoModel->db->tableExists('crm_interacoes')) {
            return null;
        }

        $payload = $data['payload_json'] ?? null;
        if (is_array($payload)) {
            $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        $insert = [
            'cliente_id' => $data['cliente_id'] ?? null,
            'os_id' => $data['os_id'] ?? null,
            'conversa_id' => $data['conversa_id'] ?? null,
            'tipo' => (string) ($data['tipo'] ?? 'registro'),
            'descricao' => (string) ($data['descricao'] ?? ''),
            'canal' => (string) ($data['canal'] ?? 'sistema'),
            'usuario_id' => $data['usuario_id'] ?? null,
            'data_interacao' => (string) ($data['data_interacao'] ?? date('Y-m-d H:i:s')),
            'payload_json' => $payload,
        ];

        return $this->interacaoModel->insert($insert, true) ?: null;
    }

    public function createFollowup(array $data): ?int
    {
        if (!$this->followupModel->db->tableExists('crm_followups')) {
            return null;
        }

        $insert = [
            'cliente_id' => $data['cliente_id'] ?? null,
            'os_id' => $data['os_id'] ?? null,
            'titulo' => (string) ($data['titulo'] ?? 'Follow-up'),
            'descricao' => $data['descricao'] ?? null,
            'data_prevista' => (string) ($data['data_prevista'] ?? date('Y-m-d H:i:s')),
            'status' => (string) ($data['status'] ?? 'pendente'),
            'usuario_responsavel' => $data['usuario_responsavel'] ?? null,
            'origem_evento' => $data['origem_evento'] ?? null,
            'concluido_em' => $data['concluido_em'] ?? null,
        ];

        return $this->followupModel->insert($insert, true) ?: null;
    }

    public function registerOsEvent(
        int $osId,
        string $tipoEvento,
        string $titulo,
        ?string $descricao = null,
        ?int $usuarioId = null,
        array $payload = []
    ): ?int {
        $os = $this->osModel->getComplete($osId);
        if (!$os) {
            return null;
        }

        $this->syncPipelineFromOs($os, $usuarioId);

        return $this->registerEvent([
            'cliente_id' => $os['cliente_id'] ?? null,
            'equipamento_id' => $os['equipamento_id'] ?? null,
            'os_id' => $osId,
            'tipo_evento' => $tipoEvento,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'origem' => 'os',
            'usuario_id' => $usuarioId,
            'data_evento' => date('Y-m-d H:i:s'),
            'payload_json' => $payload,
        ]);
    }

    public function syncPipelineFromOs(array $os, ?int $usuarioId = null): void
    {
        if (!$this->pipelineModel->db->tableExists('crm_pipeline')) {
            return;
        }

        $osId = (int) ($os['id'] ?? 0);
        if ($osId <= 0) {
            return;
        }

        $etapa = $this->resolvePipelineStage((string) ($os['status'] ?? ''), (string) ($os['estado_fluxo'] ?? ''));
        $existing = $this->pipelineModel->where('os_id', $osId)->first();
        $payload = [
            'cliente_id' => $os['cliente_id'] ?? null,
            'os_id' => $osId,
            'etapa_atual' => $etapa,
            'data_entrada_etapa' => date('Y-m-d H:i:s'),
            'usuario_responsavel' => $usuarioId,
            'status' => 'ativo',
        ];

        if ($existing) {
            if ((string) ($existing['etapa_atual'] ?? '') === $etapa) {
                return;
            }
            $this->pipelineModel->update((int) $existing['id'], $payload);
            return;
        }

        $this->pipelineModel->insert($payload);
    }

    public function applyStatusAutomation(int $osId, string $statusCode, ?int $usuarioId = null): void
    {
        $os = $this->osModel->getComplete($osId);
        if (!$os) {
            return;
        }

        $statusCode = strtolower(trim($statusCode));
        $statusRow = $this->statusModel->db->tableExists('os_status')
            ? $this->statusModel->byCode($statusCode)
            : null;
        $grupoMacro = strtolower(trim((string) ($statusRow['grupo_macro'] ?? '')));
        $estadoFluxo = strtolower(trim((string) ($os['estado_fluxo'] ?? '')));

        $this->registerOsEvent(
            $osId,
            'os_status_alterado',
            'Status da OS atualizado',
            'OS alterada para "' . $this->humanizeStatus($statusCode) . '".',
            $usuarioId,
            [
                'status' => $statusCode,
                'grupo_macro' => $grupoMacro,
                'estado_fluxo' => $estadoFluxo,
            ]
        );

        $map = [
            'triagem' => ['os_aberta', 'OS aberta', 'OS registrada no atendimento'],
            'diagnostico' => ['diagnostico_iniciado', 'Diagnostico iniciado', 'Diagnostico tecnico em andamento'],
            'aguardando_autorizacao' => ['orcamento_aguardando', 'Aguardando autorizacao', 'Orcamento aguardando retorno do cliente'],
            'aguardando_peca' => ['os_pausada_peca', 'Aguardando peca', 'Atendimento pausado aguardando peca'],
            'reparado_disponivel_loja' => ['equipamento_pronto', 'Equipamento pronto', 'Equipamento pronto para retirada'],
            'entregue_reparado' => ['os_entregue', 'Equipamento entregue', 'Atendimento finalizado com entrega'],
            'devolvido_sem_reparo' => ['os_devolvida_sem_reparo', 'Devolucao sem reparo', 'Atendimento finalizado sem reparo'],
        ];

        if (isset($map[$statusCode])) {
            [$tipo, $titulo, $descricao] = $map[$statusCode];
            $this->registerOsEvent($osId, $tipo, $titulo, $descricao, $usuarioId, ['status' => $statusCode]);
        }

        $ruleResult = $this->runErpRules(
            'os_status_alterado',
            $os,
            [
                'status' => $statusCode,
                'grupo_macro' => $grupoMacro,
                'estado_fluxo' => $estadoFluxo,
            ],
            $usuarioId
        );

        if (empty($ruleResult['template_sent'])) {
            $this->sendLegacyTemplateByStatus($os, $statusCode, $usuarioId);
        }

        if (empty($ruleResult['followup_created'])) {
            $this->createLegacyFollowupsByStatus($os, $statusCode, $usuarioId);
        }
    }

    /**
     * @param array<string,mixed> $os
     * @param array<string,mixed> $context
     * @return array{matched:int,executed:int,template_sent:bool,followup_created:bool}
     */
    private function runErpRules(string $eventoOrigem, array $os, array $context, ?int $usuarioId = null): array
    {
        if (!$this->regraErpModel->db->tableExists('chatbot_regras_erp')) {
            return [
                'matched' => 0,
                'executed' => 0,
                'template_sent' => false,
                'followup_created' => false,
            ];
        }

        $rules = $this->regraErpModel->ativasPorEvento($eventoOrigem);
        if (empty($rules)) {
            return [
                'matched' => 0,
                'executed' => 0,
                'template_sent' => false,
                'followup_created' => false,
            ];
        }

        $summary = [
            'matched' => 0,
            'executed' => 0,
            'template_sent' => false,
            'followup_created' => false,
        ];

        foreach ($rules as $rule) {
            $cond = $this->decodeJson((string) ($rule['condicao_json'] ?? ''), []);
            if (!$this->ruleMatches($cond, $context, $os)) {
                continue;
            }
            $summary['matched']++;

            $action = $this->decodeJson((string) ($rule['acao_json'] ?? ''), []);
            $result = $this->executeRuleAction($rule, $action, $os, $context, $usuarioId);

            if (!empty($result['executed'])) {
                $summary['executed']++;
            }
            if (!empty($result['template_sent'])) {
                $summary['template_sent'] = true;
            }
            if (!empty($result['followup_created'])) {
                $summary['followup_created'] = true;
            }
        }

        return $summary;
    }

    /**
     * @param array<string,mixed> $rule
     * @param array<string,mixed> $action
     * @param array<string,mixed> $os
     * @param array<string,mixed> $context
     * @return array{executed:bool,template_sent:bool,followup_created:bool}
     */
    private function executeRuleAction(array $rule, array $action, array $os, array $context, ?int $usuarioId = null): array
    {
        $result = [
            'executed' => false,
            'template_sent' => false,
            'followup_created' => false,
        ];

        $type = strtolower(trim((string) ($action['tipo'] ?? 'template')));
        $osId = (int) ($os['id'] ?? 0);
        $status = strtolower(trim((string) ($context['status'] ?? '')));

        try {
            if ($type === 'followup') {
                $delayDays = (int) ($action['delay_days'] ?? 0);
                $delayHours = (int) ($action['delay_hours'] ?? 0);
                $delayMinutes = (int) ($action['delay_minutes'] ?? 0);
                $timestamp = time() + ($delayDays * 86400) + ($delayHours * 3600) + ($delayMinutes * 60);
                if ($timestamp < time()) {
                    $timestamp = time();
                }

                $followupId = $this->createFollowup([
                    'cliente_id' => $os['cliente_id'] ?? null,
                    'os_id' => $osId > 0 ? $osId : null,
                    'titulo' => trim((string) ($action['titulo'] ?? 'Follow-up automatico de atendimento')),
                    'descricao' => trim((string) ($action['descricao'] ?? ('Regra automatica: ' . (string) ($rule['nome'] ?? '')))),
                    'data_prevista' => date('Y-m-d H:i:s', $timestamp),
                    'status' => trim((string) ($action['status'] ?? 'pendente')),
                    'usuario_responsavel' => $usuarioId,
                    'origem_evento' => trim((string) ($action['origem_evento'] ?? ('regra_erp_' . ((int) ($rule['id'] ?? 0))))),
                ]);

                $result['executed'] = !empty($followupId);
                $result['followup_created'] = !empty($followupId);
                return $result;
            }

            if ($type === 'crm_evento') {
                $eventId = $this->registerEvent([
                    'cliente_id' => $os['cliente_id'] ?? null,
                    'equipamento_id' => $os['equipamento_id'] ?? null,
                    'os_id' => $osId > 0 ? $osId : null,
                    'tipo_evento' => trim((string) ($action['tipo_evento'] ?? 'evento_automatico')),
                    'titulo' => trim((string) ($action['titulo'] ?? ('Evento automatico: ' . (string) ($rule['nome'] ?? '')))),
                    'descricao' => trim((string) ($action['descricao'] ?? 'Evento disparado por regra ERP.')),
                    'origem' => 'automacao_erp',
                    'usuario_id' => $usuarioId,
                    'data_evento' => date('Y-m-d H:i:s'),
                    'payload_json' => [
                        'rule_id' => (int) ($rule['id'] ?? 0),
                        'rule_name' => (string) ($rule['nome'] ?? ''),
                        'context' => $context,
                    ],
                ]);

                $result['executed'] = !empty($eventId);
                return $result;
            }

            $templateCodeRaw = trim((string) ($action['template'] ?? ''));
            $templateCode = $this->resolveTemplateCode($templateCodeRaw !== '' ? $templateCodeRaw : $this->legacyTemplateCodeForStatus($status));
            if ($templateCode === '') {
                return $result;
            }

            $extra = [];
            $pdfType = trim((string) ($action['pdf_tipo'] ?? ''));
            if ($pdfType === '' && (bool) ($action['anexar_pdf_status'] ?? false)) {
                $pdfType = $this->legacyPdfTypeForStatus($status);
            }
            if ($pdfType !== '' && $osId > 0) {
                try {
                    $pdfResult = (new OsPdfService())->gerar($osId, $pdfType, $usuarioId);
                    if (!empty($pdfResult['ok'])) {
                        if (!empty($pdfResult['url'])) {
                            $extra['pdf_url'] = (string) $pdfResult['url'];
                        }
                        if (!empty($pdfResult['path']) && is_file((string) $pdfResult['path'])) {
                            $extra['arquivo_path'] = (string) $pdfResult['path'];
                            $extra['arquivo'] = (string) ($pdfResult['relative'] ?? '');
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('warning', 'Falha ao gerar PDF por regra ERP (OS ' . $osId . '): ' . $e->getMessage());
                }
            }

            $send = (new WhatsAppService())->sendByTemplate($os, $templateCode, $usuarioId, $extra);
            $result['executed'] = true;
            $result['template_sent'] = !empty($send['ok']);

            if (empty($send['ok'])) {
                log_message(
                    'warning',
                    'Automacao ERP falhou ao enviar template "' . $templateCode . '" para OS ' . $osId
                    . ': ' . (string) ($send['message'] ?? 'erro desconhecido')
                );
            }
            return $result;
        } catch (\Throwable $e) {
            log_message(
                'warning',
                'Falha ao executar regra ERP #' . (int) ($rule['id'] ?? 0)
                . ' (OS ' . $osId . '): ' . $e->getMessage()
            );
            return $result;
        }
    }

    /**
     * @param array<string,mixed> $condition
     * @param array<string,mixed> $context
     * @param array<string,mixed> $os
     */
    private function ruleMatches(array $condition, array $context, array $os): bool
    {
        if (empty($condition)) {
            return true;
        }

        $bag = [
            'status' => strtolower(trim((string) ($context['status'] ?? ''))),
            'grupo_macro' => strtolower(trim((string) ($context['grupo_macro'] ?? ''))),
            'estado_fluxo' => strtolower(trim((string) ($context['estado_fluxo'] ?? ''))),
            'os_id' => (int) ($os['id'] ?? 0),
            'cliente_id' => (int) ($os['cliente_id'] ?? 0),
            'numero_os' => strtolower(trim((string) ($os['numero_os'] ?? ''))),
        ];

        foreach ($condition as $rawKey => $expectedValue) {
            $key = strtolower(trim((string) $rawKey));
            if ($key === '') {
                continue;
            }

            if (str_ends_with($key, '_in')) {
                $field = substr($key, 0, -3);
                $current = strtolower(trim((string) ($bag[$field] ?? '')));
                $expectedList = is_array($expectedValue) ? $expectedValue : [$expectedValue];
                $normalizedList = array_map(
                    static fn ($item) => strtolower(trim((string) $item)),
                    $expectedList
                );
                if (!in_array($current, $normalizedList, true)) {
                    return false;
                }
                continue;
            }

            if (str_ends_with($key, '_not_in')) {
                $field = substr($key, 0, -7);
                $current = strtolower(trim((string) ($bag[$field] ?? '')));
                $expectedList = is_array($expectedValue) ? $expectedValue : [$expectedValue];
                $normalizedList = array_map(
                    static fn ($item) => strtolower(trim((string) $item)),
                    $expectedList
                );
                if (in_array($current, $normalizedList, true)) {
                    return false;
                }
                continue;
            }

            $current = strtolower(trim((string) ($bag[$key] ?? '')));
            $expected = strtolower(trim((string) $expectedValue));
            if ($current !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string,mixed> $default
     * @return array<string,mixed>
     */
    private function decodeJson(string $raw, array $default = []): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * @param array<string,mixed> $os
     */
    private function sendLegacyTemplateByStatus(array $os, string $statusCode, ?int $usuarioId = null): void
    {
        $statusCode = strtolower(trim($statusCode));
        $template = $this->legacyTemplateCodeForStatus($statusCode);
        if ($template === '') {
            return;
        }

        $template = $this->resolveTemplateCode($template);
        if ($template === '') {
            return;
        }

        $extra = [];
        $pdfType = $this->legacyPdfTypeForStatus($statusCode);
        $osId = (int) ($os['id'] ?? 0);
        if ($pdfType !== '' && $osId > 0) {
            try {
                $pdfResult = (new OsPdfService())->gerar($osId, $pdfType, $usuarioId);
                if (!empty($pdfResult['ok'])) {
                    if (!empty($pdfResult['url'])) {
                        $extra['pdf_url'] = (string) $pdfResult['url'];
                    }
                    if (!empty($pdfResult['path']) && is_file((string) $pdfResult['path'])) {
                        $extra['arquivo_path'] = (string) $pdfResult['path'];
                        $extra['arquivo'] = (string) ($pdfResult['relative'] ?? '');
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'Falha ao gerar PDF automatico legado da OS ' . $osId . ': ' . $e->getMessage());
            }
        }

        try {
            (new WhatsAppService())->sendByTemplate($os, $template, $usuarioId, $extra);
        } catch (\Throwable $e) {
            log_message('warning', 'Falha ao enviar WhatsApp automatico legado da OS ' . $osId . ': ' . $e->getMessage());
        }
    }

    /**
     * @param array<string,mixed> $os
     */
    private function createLegacyFollowupsByStatus(array $os, string $statusCode, ?int $usuarioId = null): void
    {
        $statusCode = strtolower(trim($statusCode));
        $osId = (int) ($os['id'] ?? 0);
        if ($osId <= 0) {
            return;
        }

        if ($statusCode === 'aguardando_autorizacao') {
            $this->createFollowup([
                'cliente_id' => $os['cliente_id'] ?? null,
                'os_id' => $osId,
                'titulo' => 'Cobrar aprovacao do orcamento',
                'descricao' => 'Contato de retorno para aprovacao da OS ' . ($os['numero_os'] ?? '#' . $osId),
                'data_prevista' => date('Y-m-d H:i:s', strtotime('+2 days')),
                'status' => 'pendente',
                'usuario_responsavel' => $usuarioId,
                'origem_evento' => 'status_aguardando_autorizacao',
            ]);
            return;
        }

        if ($statusCode === 'entregue_reparado') {
            $this->createFollowup([
                'cliente_id' => $os['cliente_id'] ?? null,
                'os_id' => $osId,
                'titulo' => 'Pos-atendimento da OS',
                'descricao' => 'Validar satisfacao do cliente apos entrega da OS ' . ($os['numero_os'] ?? '#' . $osId),
                'data_prevista' => date('Y-m-d H:i:s', strtotime('+7 days')),
                'status' => 'pendente',
                'usuario_responsavel' => $usuarioId,
                'origem_evento' => 'status_entregue_reparado',
            ]);
        }
    }

    private function legacyTemplateCodeForStatus(string $statusCode): string
    {
        $map = [
            'triagem' => 'os_aberta',
            'aguardando_autorizacao' => 'aguardando_autorizacao',
            'aguardando_peca' => 'aguardando_peca',
            'reparado_disponivel_loja' => 'pronto_retirada',
            'entregue_reparado' => 'entrega_concluida',
            'devolvido_sem_reparo' => 'devolucao_sem_reparo',
            'reparo_recusado' => 'devolucao_sem_reparo',
        ];

        return (string) ($map[$statusCode] ?? '');
    }

    private function legacyPdfTypeForStatus(string $statusCode): string
    {
        $map = [
            'triagem' => 'abertura',
            'aguardando_autorizacao' => 'orcamento',
            'reparado_disponivel_loja' => 'laudo',
            'entregue_reparado' => 'entrega',
            'devolvido_sem_reparo' => 'devolucao_sem_reparo',
            'reparo_recusado' => 'devolucao_sem_reparo',
        ];

        return (string) ($map[$statusCode] ?? '');
    }

    private function resolveTemplateCode(string $raw): string
    {
        $raw = strtolower(trim($raw));
        if ($raw === '') {
            return '';
        }

        $alias = [
            'equipamento_pronto' => 'pronto_retirada',
            'orcamento_enviado' => 'aguardando_autorizacao',
        ];

        $resolved = $alias[$raw] ?? $raw;

        if (!$this->dbTableExists('whatsapp_templates')) {
            return $resolved;
        }

        $exists = $this->osModel->db->table('whatsapp_templates')
            ->where('codigo', $resolved)
            ->where('ativo', 1)
            ->countAllResults();
        if ($exists > 0) {
            return $resolved;
        }

        return '';
    }

    private function humanizeStatus(string $statusCode): string
    {
        $label = str_replace('_', ' ', strtolower(trim($statusCode)));
        return ucwords($label);
    }

    private function dbTableExists(string $table): bool
    {
        try {
            return $this->osModel->db->tableExists($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolvePipelineStage(string $statusCode, string $estadoFluxo): string
    {
        $statusCode = strtolower(trim($statusCode));
        $estadoFluxo = strtolower(trim($estadoFluxo));

        $statusRow = null;
        if ($this->statusModel->db->tableExists('os_status')) {
            $statusRow = $this->statusModel->byCode($statusCode);
        }
        $macro = strtolower(trim((string) ($statusRow['grupo_macro'] ?? '')));

        return match (true) {
            $statusCode === 'triagem' => 'novo_atendimento',
            in_array($macro, ['recepcao'], true) => 'equipamento_recebido',
            in_array($macro, ['diagnostico'], true) => 'em_diagnostico',
            in_array($macro, ['orcamento'], true) => 'aguardando_aprovacao',
            in_array($macro, ['execucao', 'qualidade'], true) => 'em_reparo',
            in_array($macro, ['concluido'], true) => 'pronto_retirada',
            in_array($macro, ['encerrado', 'cancelado', 'finalizado_sem_reparo'], true) => 'entregue',
            $estadoFluxo === 'pronto' => 'pronto_retirada',
            $estadoFluxo === 'encerrado' => 'entregue',
            default => 'novo_atendimento',
        };
    }
}
