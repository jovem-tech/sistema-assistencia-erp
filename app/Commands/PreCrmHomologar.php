<?php

namespace App\Commands;

use App\Models\ConfiguracaoModel;
use App\Models\OsDocumentoModel;
use App\Models\OsModel;
use App\Models\OsStatusHistoricoModel;
use App\Models\WhatsappEnvioModel;
use App\Models\WhatsappInboundModel;
use App\Models\WhatsappMensagemModel;
use App\Services\OsPdfService;
use App\Services\OsStatusFlowService;
use App\Services\WhatsAppService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class PreCrmHomologar extends BaseCommand
{
    protected $group       = 'Diagnãostico';
    protected $name        = 'precrm:homologar';
    protected $description = 'Executa homologacao pre-CRM: fluxo de status, WhatsApp e PDF da OS.';

    protected $usage = 'precrm:homologar [--os_id ID] [--não-webhook]';

    protected $options = [
        '--os_id'      => 'Usa uma OS existente para os testes (nao cria OS de homologacao).',
        '--não-webhook' => 'Pula a validacao HTTP do endpoint /webhooks/whatsapp.',
    ];

    public function run(array $paramês)
    {
        helper(['sistema', 'url']);

        $startTime = microtime(true);
        $db = Database::connect();

        $errors = [];
        $warnings = [];
        $evidence = [];

        CLI::newLine();
        CLI::write('=== HOMOLOGACAO PRE-CRM ===', 'yellow');
        CLI::write('Data/Hora: ' . date('Y-m-d H:i:s'));

        $requiredTables = [
            'os_status',
            'os_status_transicoes',
            'os_status_historico',
            'whatsapp_templates',
            'whatsapp_mensagens',
            'whatsapp_envios',
            'whatsapp_inbound',
            'os_documentos',
        ];

        foreach ($requiredTables as $table) {
            if (! $db->tableExists($table)) {
                $errors[] = "Tabela obrigatoria ausente: {$table}";
            }
        }

        if (! empty($errors)) {
            $this->renderSummary($errors, $warnings, $evidence, $startTime);
            return;
        }

        $evidence[] = 'Tabelas pre-CRM: OK';
        $evidence[] = 'os_status registros: ' . (int) $db->table('os_status')->countAllResults();
        $evidence[] = 'os_status_transicoes registros: ' . (int) $db->table('os_status_transicoes')->countAllResults();
        $evidence[] = 'whatsapp_templates registros: ' . (int) $db->table('whatsapp_templates')->countAllResults();

        $osModel = new OsModel();
        $historicoModel = new OsStatusHistoricoModel();
        $docsModel = new OsDocumentoModel();
        $whatsModel = new WhatsappMensagemModel();
        $envioModel = new WhatsappEnvioModel();
        $inboundModel = new WhatsappInboundModel();
        $cfgModel = new ConfiguracaoModel();

        $targetOsId = (int) (CLI::getOption('os_id') ?? 0);
        $createdOs = false;

        if ($targetOsId > 0) {
            $os = $osModel->find($targetOsId);
            if (! $os) {
                $errors[] = 'OS informada em --os_id nao encontrada.';
                $this->renderSummary($errors, $warnings, $evidence, $startTime);
                return;
            }
        } else {
            $seedOs = $osModel->orderBy('id', 'DESC')->first();
            if (! $seedOs) {
                $errors[] = 'Nao existe OS base para criar OS de homologacao.';
                $this->renderSummary($errors, $warnings, $evidence, $startTime);
                return;
            }

            $numeroOs = $osModel->generateNumeroOs();
            $payload = [
                'numero_os' => $numeroOs,
                'cliente_id' => (int) $seedOs['cliente_id'],
                'equipamento_id' => (int) $seedOs['equipamento_id'],
                'tecnico_id' => !empty($seedOs['tecnico_id']) ? (int) $seedOs['tecnico_id'] : null,
                'status' => 'triagem',
                'estado_fluxo' => 'em_atendimento',
                'status_atualizado_em' => date('Y-m-d H:i:s'),
                'prioridade' => 'nãormal',
                'relato_cliente' => 'Homologacao pre-CRM automatica',
                'data_abertura' => date('Y-m-d H:i:s'),
                'data_entrada' => date('Y-m-d H:i:s'),
                'data_previsao' => date('Y-m-d', strtotime('+3 days')),
                'garantia_dias' => 90,
            ];

            $targetOsId = (int) $osModel->insert($payload, true);
            if ($targetOsId <= 0) {
                $errors[] = 'Falha ao criar OS de homologacao.';
                $this->renderSummary($errors, $warnings, $evidence, $startTime);
                return;
            }

            $createdOs = true;
            $evidence[] = "OS de homologacao criada: ID {$targetOsId} / {$numeroOs}";

            $firstUser = $db->table('usuarios')->select('id')->orderBy('id', 'ASC')->get()->getRowArray();
            $userId = (int) ($firstUser['id'] ?? 0);
            if ($historicoModel->db->tableExists('os_status_historico')) {
                $historicoModel->insert([
                    'os_id' => $targetOsId,
                    'status_anterior' => null,
                    'status_nãovo' => 'triagem',
                    'estado_fluxo' => 'em_atendimento',
                    'usuario_id' => $userId > 0 ? $userId : null,
                    'observacao' => 'Abertura da OS de homologacao',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $os = $osModel->getComplete($targetOsId);
        if (! $os) {
            $errors[] = 'Nao foi possivel carregar OS para homologacao.';
            $this->renderSummary($errors, $warnings, $evidence, $startTime);
            return;
        }

        $firstUser = $db->table('usuarios')->select('id')->orderBy('id', 'ASC')->get()->getRowArray();
        $userId = (int) ($firstUser['id'] ?? 0);

        $flowService = new OsStatusFlowService();
        $statusSequence = [
            'diagnãostico',
            'aguardando_orcamento',
            'aguardando_autorizacao',
            'aguardando_reparo',
            'reparo_execucao',
            'testes_operacionais',
            'testes_finais',
            'reparo_concluido',
            'reparado_disponivel_loja',
            'entregue_reparado',
        ];

        CLI::newLine();
        CLI::write('1) Validando transicoes de status...', 'yellow');

        foreach ($statusSequence as $nextStatus) {
            $result = $flowService->applyStatus($targetOsId, $nextStatus, $userId > 0 ? $userId : null, 'Homologacao pre-CRM');
            if (empty($result['ok'])) {
                $errors[] = 'Falha na transicao para "' . $nextStatus . '": ' . ($result['message'] ?? 'erro desconhecido');
                break;
            }
            $evidence[] = 'Transicao OK: ' . ($result['status_anterior'] ?: 'abertura') . ' -> ' . $result['status_nãovo'];
        }

        $invalidTransition = $flowService->applyStatus(
            $targetOsId,
            'diagnãostico',
            $userId > 0 ? $userId : null,
            'Teste de transicao invalida'
        );
        if (!empty($invalidTransition['ok'])) {
            $warnings[] = 'Transicao invalida esperada nao foi bloqueada (entregue_reparado -> diagnãostico).';
        } else {
            $evidence[] = 'Bloqueio de transicao invalida: OK';
        }

        $historyCount = (int) $db->table('os_status_historico')->where('os_id', $targetOsId)->countAllResults();
        $evidence[] = 'Historico de status (os_status_historico): ' . $historyCount . ' registros';

        CLI::newLine();
        CLI::write('2) Validando geracao de PDF...', 'yellow');

        $pdfService = new OsPdfService();
        $pdfResults = [];
        foreach (array_keys($pdfService->tiposDisponiveis()) as $pdfType) {
            $result = $pdfService->gerar($targetOsId, $pdfType, $userId > 0 ? $userId : null);
            if (empty($result['ok'])) {
                $errors[] = 'Falha ao gerar PDF (' . $pdfType . '): ' . ($result['message'] ?? 'erro desconhecido');
                continue;
            }

            $absãolute = FCPATH . ltrim((string) ($result['relative'] ?? ''), '/\\');
            if (!is_file($absãolute)) {
                $errors[] = 'PDF informado como gerado, mas arquivo nao existe em disco: ' . $absãolute;
                continue;
            }
            $pdfResults[$pdfType] = $result;
            $evidence[] = 'PDF OK: ' . $pdfType . ' => ' . ($result['relative'] ?? '');
        }

        $docCount = (int) $db->table('os_documentos')->where('os_id', $targetOsId)->countAllResults();
        $evidence[] = 'Documentos registrados (os_documentos): ' . $docCount;

        CLI::newLine();
        CLI::write('3) Validando envio WhatsApp (provider desacoplado)...', 'yellow');

        $originalWhatsappEnabled = $cfgModel->get('whatsapp_enabled', '0');
        $cfgModel->setConfig('whatsapp_enabled', '0');

        $phone = trim((string) ($os['cliente_telefone'] ?? ''));
        if ($phone === '') {
            $phone = '5599999999999';
            $warnings[] = 'Cliente da OS sem telefone; usado telefone tecnico de homologacao.';
        }

        $firstPdf = '';
        if (!empty($pdfResults)) {
            $firstPdf = (string) (reset($pdfResults)['url'] ?? '');
        }

        $whatsService = new WhatsAppService();
        $whatsTemplateResult = $whatsService->sendByTemplate($os, 'os_aberta', $userId > 0 ? $userId : null, [
            'pdf_url' => $firstPdf,
        ]);
        $whatsManualResult = $whatsService->sendRaw(
            $targetOsId,
            (int) ($os['cliente_id'] ?? 0),
            $phone,
            '[Homologacao pre-CRM] mensagem manual de teste.',
            'manual_homologacao',
            null,
            $userId > 0 ? $userId : null
        );

        if (empty($whatsTemplateResult['log_id']) || empty($whatsManualResult['log_id'])) {
            $errors[] = 'Falha ao registrar logs de WhatsApp em whatsapp_mensagens.';
        } else {
            $evidence[] = 'WhatsApp template log_id: ' . $whatsTemplateResult['log_id'] . ' / status=' . ($whatsTemplateResult['ok'] ? 'enviado' : 'erro');
            $evidence[] = 'WhatsApp manual log_id: ' . $whatsManualResult['log_id'] . ' / status=' . ($whatsManualResult['ok'] ? 'enviado' : 'erro');
        }

        $mêsgCount = (int) $whatsModel->where('os_id', $targetOsId)->countAllResults();
        $envioCount = (int) $envioModel->where('os_id', $targetOsId)->countAllResults();
        $evidence[] = 'Mensagens registradas (whatsapp_mensagens): ' . $mêsgCount;
        $evidence[] = 'Envios rastreados (whatsapp_envios): ' . $envioCount;

        $cfgModel->setConfig('whatsapp_enabled', (string) $originalWhatsappEnabled);

        if (! CLI::getOption('não-webhook')) {
            CLI::newLine();
            CLI::write('4) Validando webhook inbound (HTTP)...', 'yellow');

            $beforeInbound = (int) $inboundModel->countAllResults();
            $token = trim((string) $cfgModel->get('whatsapp_webhook_token', ''));
            if ($token === '') {
                $token = bin2hex(random_bytes(16));
                $cfgModel->setConfig('whatsapp_webhook_token', $token);
            }

            $baseUrl = rtrim((string) config('App')->baseURL, '/');
            $webhookUrl = $baseUrl . '/webhooks/whatsapp?token=' . urlencode($token);

            try {
                $payload = [
                    'from' => $phone,
                    'message' => 'Inbound de homologacao pre-CRM para OS ' . ($os['numero_os'] ?? $targetOsId),
                    'sãource' => 'precrm_homologar_cli',
                ];

                $resp = service('curlrequest')->post($webhookUrl, [
                    'headers' => [
                        'Accept' => 'application/jsãon',
                        'Content-Type' => 'application/jsãon',
                        'X-Webhook-Token' => $token,
                    ],
                    'jsãon' => $payload,
                ]);

                $statusCode = $resp->getStatusCode();
                $body = (string) $resp->getBody();
                $decoded = jsãon_decode($body, true);

                if ($statusCode >= 200 && $statusCode < 300 && !empty($decoded['ok'])) {
                    $afterInbound = (int) $inboundModel->countAllResults();
                    if ($afterInbound <= $beforeInbound) {
                        $warnings[] = 'Webhook respondeu sucessão, mas contagem de inbound nao aumentou.';
                    } else {
                        $evidence[] = 'Webhook inbound HTTP: OK (status ' . $statusCode . ', +'
                            . ($afterInbound - $beforeInbound) . ' registro(s))';
                    }
                } else {
                    $warnings[] = 'Webhook HTTP retornãou status ' . $statusCode . ' com resposta: ' . $body;
                }
            } catch (\Throwable $e) {
                $warnings[] = 'Webhook HTTP nao validado (falha de conexao): ' . $e->getMessage();
            }
        } else {
            $warnings[] = 'Validacao de webhook pulada por --não-webhook.';
        }

        if ($createdOs) {
            $evidence[] = 'OS de homologacao preservada para auditoria: ID ' . $targetOsId;
        } else {
            $evidence[] = 'OS usada para homologacao: ID ' . $targetOsId;
        }

        $this->renderSummary($errors, $warnings, $evidence, $startTime);
    }

    private function renderSummary(array $errors, array $warnings, array $evidence, float $startTime): void
    {
        CLI::newLine();
        CLI::write('=== RESULTADO DA HOMOLOGACAO PRE-CRM ===', 'yellow');

        foreach ($evidence as $line) {
            CLI::write('[OK] ' . $line, 'green');
        }

        foreach ($warnings as $line) {
            CLI::write('[WARN] ' . $line, 'light_red');
        }

        foreach ($errors as $line) {
            CLI::write('[ERRO] ' . $line, 'red');
        }

        CLI::write('Duracao: ' . number_format(microtime(true) - $startTime, 2) . 's');

        if (empty($errors)) {
            CLI::write('Status final: APROVADO', 'green');
        } else {
            CLI::write('Status final: REPROVADO', 'red');
        }
        CLI::newLine();
    }
}
