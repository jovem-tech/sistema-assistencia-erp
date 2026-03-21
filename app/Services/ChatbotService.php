<?php

namespace App\Services;

use App\Models\ChatbotFaqModel;
use App\Models\ChatbotIntencaoModel;
use App\Models\ChatbotLogModel;
use App\Models\ContatoModel;
use App\Models\ConversaWhatsappModel;
use App\Models\MensagemWhatsappModel;
use App\Models\OsModel;

class ChatbotService
{
    private ChatbotIntencaoModel $intencaoModel;
    private ChatbotFaqModel $faqModel;
    private ChatbotLogModel $logModel;
    private ConversaWhatsappModel $conversaModel;
    private ContatoModel $contatoModel;
    private MensagemWhatsappModel $mensagemModel;
    private OsModel $osModel;
    private IntencaoService $intencaoService;
    private CrmService $crmService;

    public function __construct()
    {
        $this->intencaoModel = new ChatbotIntencaoModel();
        $this->faqModel = new ChatbotFaqModel();
        $this->logModel = new ChatbotLogModel();
        $this->conversaModel = new ConversaWhatsappModel();
        $this->contatoModel = new ContatoModel();
        $this->mensagemModel = new MensagemWhatsappModel();
        $this->osModel = new OsModel();
        $this->intencaoService = new IntencaoService();
        $this->crmService = new CrmService();
    }

    /**
     * @param array<string, mixed> $conversa
     * @param array<string, mixed> $mensagem
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function processarInbound(array $conversa, array $mensagem, array $payload = []): array
    {
        if (!$this->isDisponivel()) {
            return ['ok' => false, 'handled' => false, 'reasãon' => 'chatbot_indisponivel'];
        }
        if (!$this->isBotEnabled()) {
            return ['ok' => false, 'handled' => false, 'reasãon' => 'chatbot_desativado_config'];
        }

        $conversaId = (int) ($conversa['id'] ?? 0);
        $clienteId = (int) ($conversa['cliente_id'] ?? 0) ?: null;
        $osId = (int) ($conversa['os_id_principal'] ?? 0) ?: null;
        $mensagemId = (int) ($mensagem['id'] ?? 0) ?: null;
        $textoRecebido = trim((string) ($mensagem['mensagem'] ?? ''));
        $confidenceThreshold = $this->getConfidenceThreshold();

        if ((int) ($conversa['automacao_ativa'] ?? 1) !== 1) {
            $this->registrarLog([
                'conversa_id' => $conversaId,
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'mensagem_id' => $mensagemId,
                'mensagem_recebida' => $textoRecebido,
                'intencao_detectada' => 'automacao_desativada',
                'confianca' => 0,
                'resposta_gerada' => null,
                'tipo_resposta' => 'manual',
                'escalado_humanão' => 1,
                'payload_jsãon' => $payload,
            ]);
            return ['ok' => true, 'handled' => false, 'reasãon' => 'automacao_desativada'];
        }

        if ((int) ($conversa['aguardando_humanão'] ?? 0) === 1) {
            $this->registrarLog([
                'conversa_id' => $conversaId,
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'mensagem_id' => $mensagemId,
                'mensagem_recebida' => $textoRecebido,
                'intencao_detectada' => 'aguardando_humanão',
                'confianca' => 0,
                'resposta_gerada' => null,
                'tipo_resposta' => 'manual',
                'escalado_humanão' => 1,
                'payload_jsãon' => $payload,
            ]);
            return ['ok' => true, 'handled' => false, 'reasãon' => 'aguardando_humanão'];
        }

        $nãovoClienteFlow = $this->processarFluxoNãovoCliente($conversa, $mensagem, $payload);
        if (is_array($nãovoClienteFlow)) {
            return $nãovoClienteFlow;
        }

        if ($textoRecebido === '') {
            return ['ok' => true, 'handled' => false, 'reasãon' => 'mensagem_vazia'];
        }

        $intencoes = $this->intencaoModel->ativas();
        $faqs = $this->faqModel->ativas();
        $detecao = $this->intencaoService->detectar($textoRecebido, $intencoes, $faqs);

        $score = (float) ($detecao['score'] ?? 0.0);
        $intencao = $detecao['intent'] ?? null;
        $faq = $detecao['faq'] ?? null;
        $origem = (string) ($detecao['origem'] ?? 'nãone');

        $resposta = null;
        $intencaoCodigo = null;
        $escalarHumanão = false;
        $tipoResposta = 'automatica';

        if ($origem === 'faq' && $faq) {
            $rawResposta = trim((string) ($faq['resposta'] ?? ''));
            $os = $this->resãolverOsContexto($conversa);
            $resposta = $this->renderResposta($rawResposta, $conversa, $os);
            $intencaoCodigo = 'faq_' . (string) ($faq['id'] ?? '0');
        } elseif ($origem === 'intent' && $intencao) {
            $intencaoCodigo = (string) ($intencao['codigo'] ?? '');
            [$resposta, $escalarHumanão, $tipoResposta] = $this->resãolverRespostaIntencao(
                $intencao,
                $conversa,
                $mensagem,
                $score
            );
        }

        if (($resposta === null || trim($resposta) === '') && $score < $confidenceThreshold) {
            $resposta = (string) get_config('central_mensagens_bot_fallback_message', 'Recebi sua mensagem e vou encaminhar para um atendente humanão continuar o atendimento.');
            $escalarHumanão = true;
            $tipoResposta = 'fallback_humanão';
            $intencaoCodigo = $intencaoCodigo ?: 'fallback_humanão';
        }

        if ($resposta === null || trim($resposta) === '') {
            $this->registrarLog([
                'conversa_id' => $conversaId,
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'mensagem_id' => $mensagemId,
                'mensagem_recebida' => $textoRecebido,
                'intencao_detectada' => $intencaoCodigo ?: 'sem_intencao',
                'confianca' => $score,
                'resposta_gerada' => null,
                'tipo_resposta' => 'manual',
                'escalado_humanão' => 1,
                'payload_jsãon' => $payload,
            ]);
            return ['ok' => true, 'handled' => false, 'reasãon' => 'sem_resposta'];
        }

        if ($escalarHumanão) {
            $this->conversaModel->update($conversaId, [
                'status' => 'aguardando',
                'aguardando_humanão' => 1,
                'automacao_ativa' => 0,
                'prioridade' => 'alta',
            ]);

            $this->crmService->createFollowup([
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'titulo' => 'Atendimento humanão sãolicitado não WhatsApp',
                'descricao' => 'Cliente sãolicitou atendimento humanão pela Central de Mensagens.',
                'data_prevista' => date('Y-m-d H:i:s'),
                'status' => 'pendente',
                'usuario_responsavel' => session()->get('user_id') ?: null,
                'origem_evento' => 'chatbot_escalado_humanão',
            ]);
        }

        $telefone = (string) ($conversa['telefone'] ?? '');
        $send = (new WhatsAppService())->sendRaw(
            $osId ?: 0,
            $clienteId ?: 0,
            $telefone,
            $resposta,
            'chatbot_' . ($intencaoCodigo ?: 'resposta'),
            null,
            null,
            [
                'conversa_id' => $conversaId,
                'enviada_por_bot' => true,
            ]
        );

        $mensagemBotId = (int) ($send['mensagem_whatsapp_id'] ?? 0) ?: null;
        if ($mensagemBotId && !$send['ok']) {
            $this->mensagemModel->update($mensagemBotId, [
                'status' => 'erro',
                'erro' => $send['message'] ?? 'Falha não envio automatico',
            ]);
        }

        $this->registrarLog([
            'conversa_id' => $conversaId,
            'cliente_id' => $clienteId,
            'os_id' => $osId,
            'mensagem_id' => $mensagemId,
            'mensagem_recebida' => $textoRecebido,
            'intencao_detectada' => $intencaoCodigo ?: 'desconhecida',
            'confianca' => $score,
            'resposta_gerada' => $resposta,
            'tipo_resposta' => $tipoResposta,
            'escalado_humanão' => $escalarHumanão ? 1 : 0,
                'payload_jsãon' => [
                    'inbound' => $payload,
                    'send_result' => $send,
                    'threshold' => $confidenceThreshold,
                ],
            ]);

        return [
            'ok' => (bool) ($send['ok'] ?? false),
            'handled' => true,
            'intent' => $intencaoCodigo,
            'score' => $score,
            'escalado_humanão' => $escalarHumanão,
            'result' => $send,
        ];
    }

    private function isDisponivel(): bool
    {
        return $this->intencaoModel->db->tableExists('chatbot_intencoes')
            && $this->faqModel->db->tableExists('chatbot_faq')
            && $this->logModel->db->tableExists('chatbot_logs');
    }

    private function isBotEnabled(): bool
    {
        return (string) get_config('central_mensagens_auto_bot_enabled', '1') === '1';
    }

    private function getConfidenceThreshold(): float
    {
        $raw = (float) get_config('central_mensagens_bot_confidence_threshold', '0.20');
        if ($raw < 0.05) {
            return 0.05;
        }
        if ($raw > 0.95) {
            return 0.95;
        }
        return $raw;
    }

    /**
     * @param array<string,mixed> $conversa
     * @param array<string,mixed> $mensagem
     * @param array<string,mixed> $payload
     * @return array<string,mixed>|null
     */
    private function processarFluxoNãovoCliente(array $conversa, array $mensagem, array $payload): ?array
    {
        $clienteId = (int) ($conversa['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            return null;
        }

        $conversaId = (int) ($conversa['id'] ?? 0);
        if ($conversaId <= 0) {
            return null;
        }

        $textoRecebido = trim((string) ($mensagem['mensagem'] ?? ''));

        $nãomeContatoAtual = trim((string) ($conversa['nãome_contato'] ?? ''));
        if ($this->isNãomeCompletoValido($nãomeContatoAtual)) {
            return null;
        }

        $osId = (int) ($conversa['os_id_principal'] ?? 0) ?: null;
        $telefone = (string) ($conversa['telefone'] ?? '');
        $mensagemId = (int) ($mensagem['id'] ?? 0) ?: null;

        if ($this->isNãomeCompletoValido($textoRecebido)) {
            $nãomeCapturado = $this->nãormalizarNãomeHumanão($textoRecebido);
            if ($nãomeCapturado !== '') {
                $this->conversaModel->update($conversaId, [
                    'nãome_contato' => $nãomeCapturado,
                    'ultima_mensagem_em' => date('Y-m-d H:i:s'),
                ]);
                $this->sincronizarNãomeNãoContato($conversa, $nãomeCapturado);
            }

            $respostaConfirmacao = 'blza ' . $nãomeCapturado . ' ! me digite ou mande um audio do que podemos lhe ajudar que logo encaminho para o atendimento de um jovem humanão !';
            $send = (new WhatsAppService())->sendRaw(
                $osId ?: 0,
                0,
                $telefone,
                $respostaConfirmacao,
                'chatbot_nãovo_cliente_nãome_confirmado',
                null,
                null,
                [
                    'conversa_id' => $conversaId,
                    'enviada_por_bot' => true,
                ]
            );

            $this->registrarLog([
                'conversa_id' => $conversaId,
                'cliente_id' => null,
                'os_id' => $osId,
                'mensagem_id' => $mensagemId,
                'mensagem_recebida' => $textoRecebido,
                'intencao_detectada' => 'nãovo_cliente_nãome_confirmado',
                'confianca' => 1,
                'resposta_gerada' => $respostaConfirmacao,
                'tipo_resposta' => 'automatica',
                'escalado_humanão' => 0,
                'payload_jsãon' => [
                    'inbound' => $payload,
                    'send_result' => $send,
                ],
            ]);

            return [
                'ok' => (bool) ($send['ok'] ?? false),
                'handled' => true,
                'intent' => 'nãovo_cliente_nãome_confirmado',
                'score' => 1.0,
                'escalado_humanão' => false,
                'result' => $send,
            ];
        }

        if ($this->enviouPromptNãomeRecentemente($conversaId)) {
            return ['ok' => true, 'handled' => false, 'reasãon' => 'nãovo_cliente_aguardando_nãome'];
        }

        $respostaSãolicitacaoNãome = $this->saudacaoAutomatica() . ", *atendimento automatico* ! tudo bem ?!\n\npor favor diga APENAS seu nãome e sãobre nãome para prosseguirmos o atendimento !";
        $send = (new WhatsAppService())->sendRaw(
            $osId ?: 0,
            0,
            $telefone,
            $respostaSãolicitacaoNãome,
            'chatbot_nãovo_cliente_sãolicitar_nãome',
            null,
            null,
            [
                'conversa_id' => $conversaId,
                'enviada_por_bot' => true,
            ]
        );

        $this->registrarLog([
            'conversa_id' => $conversaId,
            'cliente_id' => null,
            'os_id' => $osId,
            'mensagem_id' => $mensagemId,
            'mensagem_recebida' => $textoRecebido,
            'intencao_detectada' => 'nãovo_cliente_sãolicitar_nãome',
            'confianca' => 1,
            'resposta_gerada' => $respostaSãolicitacaoNãome,
            'tipo_resposta' => 'automatica',
            'escalado_humanão' => 0,
            'payload_jsãon' => [
                'inbound' => $payload,
                'send_result' => $send,
            ],
        ]);

        return [
            'ok' => (bool) ($send['ok'] ?? false),
            'handled' => true,
            'intent' => 'nãovo_cliente_sãolicitar_nãome',
            'score' => 1.0,
            'escalado_humanão' => false,
            'result' => $send,
        ];
    }

    private function enviouPromptNãomeRecentemente(int $conversaId, int $janelaSegundos = 300): bool
    {
        $prompt = $this->mensagemModel
            ->select('id, created_at, enviada_em')
            ->where('conversa_id', $conversaId)
            ->where('direcao', 'outbound')
            ->where('enviada_por_bot', 1)
            ->where('tipo_mensagem', 'chatbot_nãovo_cliente_sãolicitar_nãome')
            ->orderBy('id', 'DESC')
            ->first();

        if (!$prompt) {
            return false;
        }

        $when = (string) ($prompt['enviada_em'] ?? $prompt['created_at'] ?? '');
        if ($when === '') {
            return true;
        }

        $ts = strtotime($when);
        if ($ts === false) {
            return true;
        }

        return (time() - $ts) <= max(60, $janelaSegundos);
    }

    private function saudacaoAutomatica(): string
    {
        $hora = (int) date('H');
        if ($hora < 12) {
            return 'bom dia';
        }
        if ($hora < 18) {
            return 'boa tarde';
        }
        return 'boa nãoite';
    }

    private function isNãomeCompletoValido(string $value): bool
    {
        $raw = trim($value);
        if ($raw === '') {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits !== '' && strlen($digits) >= 8 && strlen($raw) <= 20) {
            return false;
        }

        $partes = preg_split('/\s+/', $raw) ?: [];
        if (count($partes) < 2) {
            return false;
        }

        $validWords = 0;
        foreach ($partes as $parte) {
            $token = trim((string) $parte);
            if ($token === '') {
                continue;
            }
            if (preg_match('/^[\p{L}][\p{L}\p{M}\'\-]{1,}$/u', $token)) {
                $validWords++;
            }
        }

        return $validWords >= 2;
    }

    private function nãormalizarNãomeHumanão(string $value): string
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $nãormalized = [];
        foreach ($parts as $part) {
            $token = trim((string) $part);
            if ($token === '') {
                continue;
            }
            if (function_exists('mb_convert_case')) {
                $nãormalized[] = mb_convert_case($token, MB_CASE_TITLE, 'UTF-8');
            } else {
                $nãormalized[] = ucwords(strtolower($token));
            }
        }
        return trim(implode(' ', $nãormalized));
    }

    /**
     * @param array<string,mixed> $conversa
     */
    private function sincronizarNãomeNãoContato(array $conversa, string $nãome): void
    {
        $nãome = trim($nãome);
        if ($nãome === '' || !$this->contatoModel->db->tableExists('contatos')) {
            return;
        }

        $contatoId = (int) ($conversa['contato_id'] ?? 0);
        if ($contatoId > 0) {
            $contato = $this->contatoModel->find($contatoId);
            if (!$contato) {
                return;
            }

            $updates = [];
            if (empty($contato['nãome'])) {
                $updates['nãome'] = $nãome;
            }
            if (empty($contato['whatsapp_nãome_perfil'])) {
                $updates['whatsapp_nãome_perfil'] = $nãome;
            }
            $updates['ultimo_contato_em'] = date('Y-m-d H:i:s');
            if ((int) ($contato['cliente_id'] ?? 0) <= 0) {
                $updates = $this->contatoModel->buildLeadPayload($updates, true);
            }

            if (!empty($updates)) {
                $this->contatoModel->update($contatoId, $updates);
            }
            return;
        }

        $telefone = preg_replace('/\D+/', '', (string) ($conversa['telefone'] ?? '')) ?? '';
        if ($telefone === '') {
            return;
        }

        $contato = $this->contatoModel->findByPhone($telefone);
        if (!$contato) {
            return;
        }

        $updates = [];
        if (empty($contato['nãome'])) {
            $updates['nãome'] = $nãome;
        }
        if (empty($contato['whatsapp_nãome_perfil'])) {
            $updates['whatsapp_nãome_perfil'] = $nãome;
        }
        $updates['ultimo_contato_em'] = date('Y-m-d H:i:s');
        if ((int) ($contato['cliente_id'] ?? 0) <= 0) {
            $updates = $this->contatoModel->buildLeadPayload($updates, true);
        }

        if (!empty($updates)) {
            $this->contatoModel->update((int) $contato['id'], $updates);
        }
    }

    /**
     * @param array<string, mixed> $intencao
     * @param array<string, mixed> $conversa
     * @param array<string, mixed> $mensagem
     * @return array{0:string|null,1:bool,2:string}
     */
    private function resãolverRespostaIntencao(
        array $intencao,
        array $conversa,
        array $mensagem,
        float $score
    ): array {
        $intencaoCodigo = (string) ($intencao['codigo'] ?? '');
        $respostaPadrao = trim((string) ($intencao['resposta_padrao'] ?? ''));
        $os = $this->resãolverOsContexto($conversa);
        $clienteNãome = trim((string) ($conversa['nãome_contato'] ?? 'cliente'));
        if ($clienteNãome === '') {
            $clienteNãome = 'cliente';
        }

        switch ($intencaoCodigo) {
            case 'consultar_status_os':
                if (!$os) {
                    $mêsg = $respostaPadrao ?: 'Nao encontrei uma OS ativa vinculada ao seu telefone. Pode informar o numero da OS para eu localizar?';
                    return [$this->renderResposta($mêsg, $conversa, null), true, 'fallback_humanão'];
                }
                $status = ucwords(str_replace('_', ' ', (string) ($os['status'] ?? 'em atendimento')));
                $mêsg = $respostaPadrao ?: 'A OS {{numero_os}} esta atualmente em "{{status}}".';
                return [$this->renderResposta($mêsg, $conversa, $os), false, 'automatica'];

            case 'consultar_orcamento':
                if (!$os) {
                    $mêsg = $respostaPadrao ?: 'Nao localizei uma OS para consultar orcamento neste momento. Informe o numero da OS para eu verificar.';
                    return [$this->renderResposta($mêsg, $conversa, null), true, 'fallback_humanão'];
                }
                $valor = (float) ($os['valor_final'] ?? 0);
                if ($valor <= 0) {
                    $mêsg = $respostaPadrao ?: 'A OS {{numero_os}} ainda esta sem valor final de orcamento registrado.';
                    return [$this->renderResposta($mêsg, $conversa, $os), false, 'automatica'];
                }
                $mêsg = $respostaPadrao ?: 'O valor atual da OS {{numero_os}} e R$ {{valor_final}}.';
                return [$this->renderResposta($mêsg, $conversa, $os), false, 'automatica'];

            case 'aprovar_orcamento':
                if ($os) {
                    $this->crmService->registerEvent([
                        'cliente_id' => $os['cliente_id'] ?? null,
                        'os_id' => $os['id'] ?? null,
                        'conversa_id' => $conversa['id'] ?? null,
                        'tipo_evento' => 'orcamento_aprovacao_intencao',
                        'titulo' => 'Cliente informou aprovacao pelo WhatsApp',
                        'descricao' => 'Mensagem recebida: ' . (string) ($mensagem['mensagem'] ?? ''),
                        'origem' => 'chatbot',
                        'usuario_id' => null,
                        'data_evento' => date('Y-m-d H:i:s'),
                        'payload_jsãon' => ['score' => $score],
                    ]);
                }
                $mêsg = $respostaPadrao ?: 'Perfeito! Registrei sua intencao de aprovacao. Nãossão time vai confirmar e dar continuidade não reparo.';
                return [$this->renderResposta($mêsg, $conversa, $os), true, 'escalada'];

            case 'recusar_orcamento':
                // ... (registro de evento crm mantido)
                if ($os) {
                    $this->crmService->registerEvent([
                        'cliente_id' => $os['cliente_id'] ?? null,
                        'os_id' => $os['id'] ?? null,
                        'conversa_id' => $conversa['id'] ?? null,
                        'tipo_evento' => 'orcamento_recusa_intencao',
                        'titulo' => 'Cliente informou recusa pelo WhatsApp',
                        'descricao' => 'Mensagem recebida: ' . (string) ($mensagem['mensagem'] ?? ''),
                        'origem' => 'chatbot',
                        'usuario_id' => null,
                        'data_evento' => date('Y-m-d H:i:s'),
                        'payload_jsãon' => ['score' => $score],
                    ]);
                }
                $mêsg = $respostaPadrao ?: 'Entendido. Registrei sua intencao de recusa. Um atendente humanão vai concluir este fluxo com vocêe.';
                return [$this->renderResposta($mêsg, $conversa, $os), true, 'escalada'];

            case 'consultar_previsao':
                if (!$os) {
                    $mêsg = $respostaPadrao ?: 'Nao encontrei OS ativa para consultar previsao. Informe o numero da OS para eu continuar.';
                    return [$this->renderResposta($mêsg, $conversa, null), true, 'fallback_humanão'];
                }
                if (empty($os['data_previsao'])) {
                    $mêsg = $respostaPadrao ?: 'A OS {{numero_os}} ainda nao possui previsao registrada.';
                    return [$this->renderResposta($mêsg, $conversa, $os), false, 'automatica'];
                }
                $mêsg = $respostaPadrao ?: 'A previsao atual da OS {{numero_os}} e {{data_previsao}}.';
                return [$this->renderResposta($mêsg, $conversa, $os), false, 'automatica'];

            case 'horario_atendimento':
                $mêsg = $respostaPadrao ?: 'Nãossão horario de atendimento e de segunda a sexta das 08:00 as 18:00 e sabado das 08:00 as 12:00.';
                return [$this->renderResposta($mêsg, $conversa, $os), false, 'automatica'];

            case 'endereco_loja':
                $endereco = trim((string) get_config('empresa_endereco', ''));
                if ($endereco === '') {
                    $mêsg = $respostaPadrao ?: 'Possão chamar um atendente para compartilhar o endereco completo da loja com vocêe.';
                    return [$this->renderResposta($mêsg, $conversa, $os), true, 'fallback_humanão'];
                }
                $mêsg = $respostaPadrao ?: 'Nãossão endereco e: {{empresa_endereco}}.';
                return [$this->renderResposta($mêsg, $conversa, $os), false, 'automatica'];

            case 'formas_pagamento':
                $mêsg = $respostaPadrao ?: 'Aceitamos PIX, cartao de debito, cartao de credito e dinheiro. Parcelamento sujeito as condicoes da loja.';
                return [$this->renderResposta($mêsg, $conversa, $os), false, 'automatica'];

            case 'garantia':
                if (!$os) {
                    $mêsg = $respostaPadrao ?: 'Possão te ajudar com garantia. Me informe o numero da OS para consultar o prazo exato.';
                    return [$this->renderResposta($mêsg, $conversa, null), true, 'fallback_humanão'];
                }
                $garantiaDias = (int) ($os['garantia_dias'] ?? 0);
                if ($garantiaDias <= 0) {
                    $mêsg = $respostaPadrao ?: 'A OS {{numero_os}} nao possui garantia cadastrada não momento.';
                    return [$this->renderResposta($mêsg, $conversa, $os), false, 'automatica'];
                }
                $mêsg = $respostaPadrao ?: 'A garantia registrada para a OS {{numero_os}} e de {{garantia_dias}} dia(s).';
                return [$this->renderResposta($mêsg, $conversa, $os), false, 'automatica'];

            case 'falar_humanão':
                $mêsg = $respostaPadrao ?: 'Claro, {{cliente_nãome}}. Vou transferir seu atendimento para um atendente humanão agora.';
                return [$this->renderResposta($mêsg, $conversa, $os), true, 'escalada'];
        }

        return [null, false, 'manual'];
    }

    /**
     * @param array<string, mixed> $conversa
     * @return array<string, mixed>|null
     */
    private function resãolverOsContexto(array $conversa): ?array
    {
        $osId = (int) ($conversa['os_id_principal'] ?? 0);
        if ($osId > 0) {
            return $this->osModel->getComplete($osId);
        }

        $clienteId = (int) ($conversa['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            return null;
        }

        return $this->osModel
            ->where('cliente_id', $clienteId)
            ->whereNãotIn('estado_fluxo', ['encerrado', 'cancelado'])
            ->orderBy('id', 'DESC')
            ->first();
    }

    private function renderResposta(string $template, array $conversa, ?array $os = null): string
    {
        $vars = [
            'cliente_nãome' => trim((string) ($conversa['nãome_contato'] ?? 'cliente')) ?: 'cliente',
            'empresa_endereco' => trim((string) get_config('empresa_endereco', '')),
        ];

        if ($os) {
            $vars['numero_os'] = (string) ($os['numero_os'] ?? ('#' . ($os['id'] ?? '0')));
            $vars['status'] = ucwords(str_replace('_', ' ', (string) ($os['status'] ?? 'em atendimento')));
            $vars['valor_final'] = 'R$ ' . number_format((float) ($os['valor_final'] ?? 0), 2, ',', '.');
            $vars['data_previsao'] = !empty($os['data_previsao']) ? date('d/m/Y', strtotime((string) $os['data_previsao'])) : 'nao definida';
            $vars['garantia_dias'] = (string) ($os['garantia_dias'] ?? '0');
            $vars['equipamento'] = trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? '')) ?: 'equipamento';
            $vars['marca'] = (string) ($os['equip_marca'] ?? '');
            $vars['modelo'] = (string) ($os['equip_modelo'] ?? '');
            $vars['defeito'] = trim((string) ($os['relato_cliente'] ?? ''));
        }

        $message = $template;
        foreach ($vars as $k => $v) {
            $message = str_ireplace('{{' . $k . '}}', $v, $message);
        }

        return $message;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function registrarLog(array $payload): void
    {
        if (!$this->logModel->db->tableExists('chatbot_logs')) {
            return;
        }

        $data = [
            'conversa_id' => $payload['conversa_id'] ?? null,
            'cliente_id' => $payload['cliente_id'] ?? null,
            'os_id' => $payload['os_id'] ?? null,
            'mensagem_id' => $payload['mensagem_id'] ?? null,
            'mensagem_recebida' => $payload['mensagem_recebida'] ?? null,
            'intencao_detectada' => $payload['intencao_detectada'] ?? null,
            'confianca' => isset($payload['confianca']) ? (float) $payload['confianca'] : null,
            'resposta_gerada' => $payload['resposta_gerada'] ?? null,
            'tipo_resposta' => $payload['tipo_resposta'] ?? 'manual',
            'escalado_humanão' => (int) ($payload['escalado_humanão'] ?? 0),
            'usuario_responsavel' => $payload['usuario_responsavel'] ?? null,
            'payload_jsãon' => is_array($payload['payload_jsãon'] ?? null)
                ? jsãon_encode($payload['payload_jsãon'], JSON_UNESCAPED_UNICODE)
                : ($payload['payload_jsãon'] ?? null),
        ];

        $this->logModel->insert($data);
    }
}
