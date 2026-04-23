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
            return ['ok' => false, 'handled' => false, 'reason' => 'chatbot_indisponivel'];
        }
        if (!$this->isBotEnabled()) {
            return ['ok' => false, 'handled' => false, 'reason' => 'chatbot_desativado_config'];
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
                'escalado_humano' => 1,
                'payload_json' => $payload,
            ]);
            return ['ok' => true, 'handled' => false, 'reason' => 'automacao_desativada'];
        }

        if ((int) ($conversa['aguardando_humano'] ?? 0) === 1) {
            $this->registrarLog([
                'conversa_id' => $conversaId,
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'mensagem_id' => $mensagemId,
                'mensagem_recebida' => $textoRecebido,
                'intencao_detectada' => 'aguardando_humano',
                'confianca' => 0,
                'resposta_gerada' => null,
                'tipo_resposta' => 'manual',
                'escalado_humano' => 1,
                'payload_json' => $payload,
            ]);
            return ['ok' => true, 'handled' => false, 'reason' => 'aguardando_humano'];
        }

        $novoClienteFlow = $this->processarFluxoNovoCliente($conversa, $mensagem, $payload);
        if (is_array($novoClienteFlow)) {
            return $novoClienteFlow;
        }

        if ($textoRecebido === '') {
            return ['ok' => true, 'handled' => false, 'reason' => 'mensagem_vazia'];
        }

        $intencoes = $this->intencaoModel->ativas();
        $faqs = $this->faqModel->ativas();
        $detecao = $this->intencaoService->detectar($textoRecebido, $intencoes, $faqs);

        $score = (float) ($detecao['score'] ?? 0.0);
        $intencao = $detecao['intent'] ?? null;
        $faq = $detecao['faq'] ?? null;
        $origem = (string) ($detecao['origem'] ?? 'none');

        $resposta = null;
        $intencaoCodigo = null;
        $escalarHumano = false;
        $tipoResposta = 'automatica';

        if ($origem === 'faq' && $faq) {
            $rawResposta = trim((string) ($faq['resposta'] ?? ''));
            $os = $this->resolverOsContexto($conversa);
            $resposta = $this->renderResposta($rawResposta, $conversa, $os);
            $intencaoCodigo = 'faq_' . (string) ($faq['id'] ?? '0');
        } elseif ($origem === 'intent' && $intencao) {
            $intencaoCodigo = (string) ($intencao['codigo'] ?? '');
            [$resposta, $escalarHumano, $tipoResposta] = $this->resolverRespostaIntencao(
                $intencao,
                $conversa,
                $mensagem,
                $score
            );
        }

        if (($resposta === null || trim($resposta) === '') && $score < $confidenceThreshold) {
            $resposta = (string) get_config('central_mensagens_bot_fallback_message', 'Recebi sua mensagem e vou encaminhar para um atendente humano continuar o atendimento.');
            $escalarHumano = true;
            $tipoResposta = 'fallback_humano';
            $intencaoCodigo = $intencaoCodigo ?: 'fallback_humano';
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
                'escalado_humano' => 1,
                'payload_json' => $payload,
            ]);
            return ['ok' => true, 'handled' => false, 'reason' => 'sem_resposta'];
        }

        if ($escalarHumano) {
            $this->conversaModel->update($conversaId, [
                'status' => 'aguardando',
                'aguardando_humano' => 1,
                'automacao_ativa' => 0,
                'prioridade' => 'alta',
            ]);

            $this->crmService->createFollowup([
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'titulo' => 'Atendimento humano solicitado no WhatsApp',
                'descricao' => 'Cliente solicitou atendimento humano pela Central de Mensagens.',
                'data_prevista' => date('Y-m-d H:i:s'),
                'status' => 'pendente',
                'usuario_responsavel' => session()->get('user_id') ?: null,
                'origem_evento' => 'chatbot_escalado_humano',
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
                'erro' => $send['message'] ?? 'Falha no envio automatico',
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
            'escalado_humano' => $escalarHumano ? 1 : 0,
                'payload_json' => [
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
            'escalado_humano' => $escalarHumano,
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
    private function processarFluxoNovoCliente(array $conversa, array $mensagem, array $payload): ?array
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

        $nomeContatoAtual = trim((string) ($conversa['nome_contato'] ?? ''));
        if ($this->isNomeCompletoValido($nomeContatoAtual)) {
            return null;
        }

        $osId = (int) ($conversa['os_id_principal'] ?? 0) ?: null;
        $telefone = (string) ($conversa['telefone'] ?? '');
        $mensagemId = (int) ($mensagem['id'] ?? 0) ?: null;

        if ($this->isNomeCompletoValido($textoRecebido)) {
            $nomeCapturado = $this->normalizarNomeHumano($textoRecebido);
            if ($nomeCapturado !== '') {
                $this->conversaModel->update($conversaId, [
                    'nome_contato' => $nomeCapturado,
                    'ultima_mensagem_em' => date('Y-m-d H:i:s'),
                ]);
                $this->sincronizarNomeNoContato($conversa, $nomeCapturado);
            }

            $respostaConfirmacao = 'blza ' . $nomeCapturado . ' ! me digite ou mande um audio do que podemos lhe ajudar que logo encaminho para o atendimento de um jovem humano !';
            $send = (new WhatsAppService())->sendRaw(
                $osId ?: 0,
                0,
                $telefone,
                $respostaConfirmacao,
                'chatbot_novo_cliente_nome_confirmado',
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
                'intencao_detectada' => 'novo_cliente_nome_confirmado',
                'confianca' => 1,
                'resposta_gerada' => $respostaConfirmacao,
                'tipo_resposta' => 'automatica',
                'escalado_humano' => 0,
                'payload_json' => [
                    'inbound' => $payload,
                    'send_result' => $send,
                ],
            ]);

            return [
                'ok' => (bool) ($send['ok'] ?? false),
                'handled' => true,
                'intent' => 'novo_cliente_nome_confirmado',
                'score' => 1.0,
                'escalado_humano' => false,
                'result' => $send,
            ];
        }

        if ($this->enviouPromptNomeRecentemente($conversaId)) {
            return ['ok' => true, 'handled' => false, 'reason' => 'novo_cliente_aguardando_nome'];
        }

        $respostaSolicitacaoNome = $this->saudacaoAutomatica() . ", *atendimento automatico* ! tudo bem ?!\n\npor favor diga APENAS seu nome e sobre nome para prosseguirmos o atendimento !";
        $send = (new WhatsAppService())->sendRaw(
            $osId ?: 0,
            0,
            $telefone,
            $respostaSolicitacaoNome,
            'chatbot_novo_cliente_solicitar_nome',
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
            'intencao_detectada' => 'novo_cliente_solicitar_nome',
            'confianca' => 1,
            'resposta_gerada' => $respostaSolicitacaoNome,
            'tipo_resposta' => 'automatica',
            'escalado_humano' => 0,
            'payload_json' => [
                'inbound' => $payload,
                'send_result' => $send,
            ],
        ]);

        return [
            'ok' => (bool) ($send['ok'] ?? false),
            'handled' => true,
            'intent' => 'novo_cliente_solicitar_nome',
            'score' => 1.0,
            'escalado_humano' => false,
            'result' => $send,
        ];
    }

    private function enviouPromptNomeRecentemente(int $conversaId, int $janelaSegundos = 300): bool
    {
        $prompt = $this->mensagemModel
            ->select('id, created_at, enviada_em')
            ->where('conversa_id', $conversaId)
            ->where('direcao', 'outbound')
            ->where('enviada_por_bot', 1)
            ->where('tipo_mensagem', 'chatbot_novo_cliente_solicitar_nome')
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
        return 'boa noite';
    }

    private function isNomeCompletoValido(string $value): bool
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

    private function normalizarNomeHumano(string $value): string
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $normalized = [];
        foreach ($parts as $part) {
            $token = trim((string) $part);
            if ($token === '') {
                continue;
            }
            if (function_exists('mb_convert_case')) {
                $normalized[] = mb_convert_case($token, MB_CASE_TITLE, 'UTF-8');
            } else {
                $normalized[] = ucwords(strtolower($token));
            }
        }
        return trim(implode(' ', $normalized));
    }

    /**
     * @param array<string,mixed> $conversa
     */
    private function sincronizarNomeNoContato(array $conversa, string $nome): void
    {
        $nome = trim($nome);
        if ($nome === '' || !$this->contatoModel->db->tableExists('contatos')) {
            return;
        }

        $contatoId = (int) ($conversa['contato_id'] ?? 0);
        if ($contatoId > 0) {
            $contato = $this->contatoModel->find($contatoId);
            if (!$contato) {
                return;
            }

            $updates = [];
            if (empty($contato['nome'])) {
                $updates['nome'] = $nome;
            }
            if (empty($contato['whatsapp_nome_perfil'])) {
                $updates['whatsapp_nome_perfil'] = $nome;
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
        if (empty($contato['nome'])) {
            $updates['nome'] = $nome;
        }
        if (empty($contato['whatsapp_nome_perfil'])) {
            $updates['whatsapp_nome_perfil'] = $nome;
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
    private function resolverRespostaIntencao(
        array $intencao,
        array $conversa,
        array $mensagem,
        float $score
    ): array {
        $intencaoCodigo = (string) ($intencao['codigo'] ?? '');
        $respostaPadrao = trim((string) ($intencao['resposta_padrao'] ?? ''));
        $os = $this->resolverOsContexto($conversa);
        $clienteNome = trim((string) ($conversa['nome_contato'] ?? 'cliente'));
        if ($clienteNome === '') {
            $clienteNome = 'cliente';
        }

        switch ($intencaoCodigo) {
            case 'consultar_status_os':
                if (!$os) {
                    $msg = $respostaPadrao ?: 'Nao encontrei uma OS ativa vinculada ao seu telefone. Pode informar o numero da OS para eu localizar?';
                    return [$this->renderResposta($msg, $conversa, null), true, 'fallback_humano'];
                }
                $status = ucwords(str_replace('_', ' ', (string) ($os['status'] ?? 'em atendimento')));
                $msg = $respostaPadrao ?: 'A OS {{numero_os}} esta atualmente em "{{status}}".';
                return [$this->renderResposta($msg, $conversa, $os), false, 'automatica'];

            case 'consultar_orcamento':
                if (!$os) {
                    $msg = $respostaPadrao ?: 'Nao localizei uma OS para consultar orcamento neste momento. Informe o numero da OS para eu verificar.';
                    return [$this->renderResposta($msg, $conversa, null), true, 'fallback_humano'];
                }
                $valor = (float) ($os['valor_final'] ?? 0);
                if ($valor <= 0) {
                    $msg = $respostaPadrao ?: 'A OS {{numero_os}} ainda esta sem valor final de orcamento registrado.';
                    return [$this->renderResposta($msg, $conversa, $os), false, 'automatica'];
                }
                $msg = $respostaPadrao ?: 'O valor atual da OS {{numero_os}} e R$ {{valor_final}}.';
                return [$this->renderResposta($msg, $conversa, $os), false, 'automatica'];

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
                        'payload_json' => ['score' => $score],
                    ]);
                }
                $msg = $respostaPadrao ?: 'Perfeito! Registrei sua intencao de aprovacao. Nosso time vai confirmar e dar continuidade no reparo.';
                return [$this->renderResposta($msg, $conversa, $os), true, 'escalada'];

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
                        'payload_json' => ['score' => $score],
                    ]);
                }
                $msg = $respostaPadrao ?: 'Entendido. Registrei sua intencao de recusa. Um atendente humano vai concluir este fluxo com voce.';
                return [$this->renderResposta($msg, $conversa, $os), true, 'escalada'];

            case 'consultar_previsao':
                if (!$os) {
                    $msg = $respostaPadrao ?: 'Nao encontrei OS ativa para consultar previsao. Informe o numero da OS para eu continuar.';
                    return [$this->renderResposta($msg, $conversa, null), true, 'fallback_humano'];
                }
                if (empty($os['data_previsao'])) {
                    $msg = $respostaPadrao ?: 'A OS {{numero_os}} ainda nao possui previsao registrada.';
                    return [$this->renderResposta($msg, $conversa, $os), false, 'automatica'];
                }
                $msg = $respostaPadrao ?: 'A previsao atual da OS {{numero_os}} e {{data_previsao}}.';
                return [$this->renderResposta($msg, $conversa, $os), false, 'automatica'];

            case 'horario_atendimento':
                $msg = $respostaPadrao ?: 'Nosso horario de atendimento e de segunda a sexta das 08:00 as 18:00 e sabado das 08:00 as 12:00.';
                return [$this->renderResposta($msg, $conversa, $os), false, 'automatica'];

            case 'endereco_loja':
                $endereco = trim((string) get_config('empresa_endereco', ''));
                if ($endereco === '') {
                    $msg = $respostaPadrao ?: 'Posso chamar um atendente para compartilhar o endereco completo da loja com voce.';
                    return [$this->renderResposta($msg, $conversa, $os), true, 'fallback_humano'];
                }
                $msg = $respostaPadrao ?: 'Nosso endereco e: {{empresa_endereco}}.';
                return [$this->renderResposta($msg, $conversa, $os), false, 'automatica'];

            case 'formas_pagamento':
                $msg = $respostaPadrao ?: 'Aceitamos PIX, cartao de debito, cartao de credito e dinheiro. Parcelamento sujeito as condicoes da loja.';
                return [$this->renderResposta($msg, $conversa, $os), false, 'automatica'];

            case 'garantia':
                if (!$os) {
                    $msg = $respostaPadrao ?: 'Posso te ajudar com garantia. Me informe o numero da OS para consultar o prazo exato.';
                    return [$this->renderResposta($msg, $conversa, null), true, 'fallback_humano'];
                }
                $garantiaDias = (int) ($os['garantia_dias'] ?? 0);
                if ($garantiaDias <= 0) {
                    $msg = $respostaPadrao ?: 'A OS {{numero_os}} nao possui garantia cadastrada no momento.';
                    return [$this->renderResposta($msg, $conversa, $os), false, 'automatica'];
                }
                $msg = $respostaPadrao ?: 'A garantia registrada para a OS {{numero_os}} e de {{garantia_dias}} dia(s).';
                return [$this->renderResposta($msg, $conversa, $os), false, 'automatica'];

            case 'falar_humano':
                $msg = $respostaPadrao ?: 'Claro, {{cliente_nome}}. Vou transferir seu atendimento para um atendente humano agora.';
                return [$this->renderResposta($msg, $conversa, $os), true, 'escalada'];
        }

        return [null, false, 'manual'];
    }

    /**
     * @param array<string, mixed> $conversa
     * @return array<string, mixed>|null
     */
    private function resolverOsContexto(array $conversa): ?array
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
            ->whereNotIn('estado_fluxo', ['encerrado', 'cancelado'])
            ->orderBy('id', 'DESC')
            ->first();
    }

    private function renderResposta(string $template, array $conversa, ?array $os = null): string
    {
        $vars = [
            'cliente_nome' => trim((string) ($conversa['nome_contato'] ?? 'cliente')) ?: 'cliente',
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
            'escalado_humano' => (int) ($payload['escalado_humano'] ?? 0),
            'usuario_responsavel' => $payload['usuario_responsavel'] ?? null,
            'payload_json' => is_array($payload['payload_json'] ?? null)
                ? json_encode($payload['payload_json'], JSON_UNESCAPED_UNICODE)
                : ($payload['payload_json'] ?? null),
        ];

        $this->logModel->insert($data);
    }
}
