<?php

namespace App\Services;

use App\Models\ClienteModel;
use App\Models\ContatoModel;
use App\Models\ConversaOsModel;
use App\Models\ConversaTagModel;
use App\Models\ConversaWhatsappModel;
use App\Models\CrmMensagemModel;
use App\Models\CrmTagModel;
use App\Models\MensagemWhatsappModel;
use App\Models\MobilePushSubscriptionModel;
use App\Models\OsModel;
use App\Models\UsuarioModel;
use App\Models\WhatsappInboundModel;
use App\Services\ChatbotService;
use App\Services\Mobile\MobileNotificationService;
use App\Services\Mobile\MobilePermissionService;
use CodeIgniter\HTTP\Files\UploadedFile;

class CentralMensagensService
{
    private const MEDIA_BASE_DIR = 'uploads/central_mensagens';
    private const OUTBOUND_DIRECTIONS = ['outbound', 'saida', 'sent', 'enviado', 'enviada'];

    private ConversaWhatsappModel $conversaModel;
    private ConversaOsModel $conversaOsModel;
    private ConversaTagModel $conversaTagModel;
    private MensagemWhatsappModel $mensagemModel;
    private CrmMensagemModel $crmMensagemModel;
    private CrmTagModel $crmTagModel;
    private WhatsappInboundModel $inboundModel;
    private ClienteModel $clienteModel;
    private ContatoModel $contatoModel;
    private UsuarioModel $usuarioModel;
    private OsModel $osModel;
    private CrmService $crmService;
    private MobileNotificationService $mobileNotificationService;
    private MobilePushSubscriptionModel $mobilePushSubscriptionModel;
    private MobilePermissionService $mobilePermissionService;

    public function __construct()
    {
        $this->conversaModel = new ConversaWhatsappModel();
        $this->conversaOsModel = new ConversaOsModel();
        $this->conversaTagModel = new ConversaTagModel();
        $this->mensagemModel = new MensagemWhatsappModel();
        $this->crmMensagemModel = new CrmMensagemModel();
        $this->crmTagModel = new CrmTagModel();
        $this->inboundModel = new WhatsappInboundModel();
        $this->clienteModel = new ClienteModel();
        $this->contatoModel = new ContatoModel();
        $this->usuarioModel = new UsuarioModel();
        $this->osModel = new OsModel();
        $this->crmService = new CrmService();
        $this->mobileNotificationService = new MobileNotificationService();
        $this->mobilePushSubscriptionModel = new MobilePushSubscriptionModel();
        $this->mobilePermissionService = new MobilePermissionService();
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        return str_starts_with($digits, '55') ? $digits : ('55' . $digits);
    }

    /**
     * @param array<string,mixed> $payload
     * @param list<string> $paths
     * @param mixed $default
     * @return mixed
     */
    private function payloadPathValue(array $payload, array $paths, $default = null)
    {
        foreach ($paths as $path) {
            $segments = explode('.', $path);
            $cursor = $payload;
            $resolved = true;

            foreach ($segments as $segment) {
                if (is_array($cursor) && array_key_exists($segment, $cursor)) {
                    $cursor = $cursor[$segment];
                    continue;
                }

                $resolved = false;
                break;
            }

            if ($resolved && $cursor !== null && $cursor !== '') {
                return $cursor;
            }
        }

        return $default;
    }

    /**
     * @param array<string,mixed> $payload
     * @param list<string> $paths
     */
    private function payloadPathString(array $payload, array $paths, string $default = ''): string
    {
        $value = $this->payloadPathValue($payload, $paths, $default);
        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return $default;
    }

    /**
     * @param array<string,mixed> $payload
     * @param list<string> $paths
     */
    private function payloadPathBool(array $payload, array $paths): ?bool
    {
        $value = $this->payloadPathValue($payload, $paths, null);
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if ($normalized === '') {
                return null;
            }
            if (in_array($normalized, ['1', 'true', 'yes', 'sim', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'nao', 'off'], true)) {
                return false;
            }
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function inferOutboundFromPayload(array $payload): bool
    {
        $explicit = $this->payloadPathBool($payload, [
            'from_me',
            'fromMe',
            'is_from_me',
            'isFromMe',
            'outbound',
            'is_outbound',
            'isOutbound',
            'sent_by_me',
            'sentByMe',
            'self',
            'data.from_me',
            'data.fromMe',
            'data.outbound',
            'data.is_outbound',
            'data.sent_by_me',
            'data.self',
        ]);
        if ($explicit !== null) {
            return $explicit;
        }

        $direction = strtolower($this->payloadPathString($payload, [
            'direction',
            'direcao',
            'tipo_direcao',
            'data.direction',
            'data.direcao',
        ]));

        return in_array($direction, self::OUTBOUND_DIRECTIONS, true);
    }

    public function resolveConversationForOutgoing(
        string $phone,
        ?int $clienteId = null,
        ?int $osId = null,
        string $provider = 'menuia',
        ?string $nomeContato = null
    ): ?array {
        if (!$this->conversaModel->db->tableExists('conversas_whatsapp')) {
            return null;
        }

        $autoBotEnabled = (string) get_config('central_mensagens_auto_bot_enabled', '1') === '1';
        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            return null;
        }

        $contato = $this->resolveContatoByPhone($normalized, $nomeContato, $clienteId);
        $contatoId = (int) ($contato['id'] ?? 0) ?: null;
        $clienteIdContato = (int) ($contato['cliente_id'] ?? 0) ?: null;
        if (!$clienteId && $clienteIdContato) {
            $clienteId = $clienteIdContato;
        }

        $conversa = $this->conversaModel->findByPhone($normalized);
        if (!$conversa) {
            if (!$clienteId) {
                $cliente = $this->findClienteByPhone($normalized);
                $clienteId = (int) ($cliente['id'] ?? 0) ?: null;
                $nomeContato = $nomeContato ?: ($cliente['nome_razao'] ?? null);
            }

            if (!$nomeContato && $contato && !empty($contato['nome'])) {
                $nomeContato = (string) $contato['nome'];
            }

            if ($contatoId && !$clienteId && $clienteIdContato) {
                $clienteId = $clienteIdContato;
            }

            if (!$osId && $clienteId) {
                $osId = $this->findOpenOsByCliente($clienteId);
            }

            $conversaId = $this->conversaModel->insert([
                'cliente_id' => $clienteId,
                'contato_id' => $contatoId,
                'os_id_principal' => $osId,
                'telefone' => $normalized,
                'nome_contato' => $nomeContato,
                'status' => 'aberta',
                'ultima_mensagem_em' => date('Y-m-d H:i:s'),
                'primeira_mensagem_em' => date('Y-m-d H:i:s'),
                'nao_lidas' => 0,
                'origem_provider' => $provider,
                'canal' => 'whatsapp',
                'automacao_ativa' => $autoBotEnabled ? 1 : 0,
                'aguardando_humano' => $autoBotEnabled ? 0 : 1,
                'prioridade' => 'normal',
            ], true);

            if (!$conversaId) {
                return null;
            }

            $conversa = $this->conversaModel->find((int) $conversaId);
        } else {
            $update = [
                'origem_provider' => $provider,
            ];
            if ($nomeContato && empty($conversa['nome_contato'])) {
                $update['nome_contato'] = $nomeContato;
            }
            if ($contatoId && empty($conversa['contato_id'])) {
                $update['contato_id'] = $contatoId;
            }
            if ($clienteId && empty($conversa['cliente_id'])) {
                $update['cliente_id'] = $clienteId;
            }
            if (!$clienteId && $clienteIdContato && empty($conversa['cliente_id'])) {
                $update['cliente_id'] = $clienteIdContato;
            }
            if ($osId && empty($conversa['os_id_principal'])) {
                $update['os_id_principal'] = $osId;
            }
            if (empty($conversa['primeira_mensagem_em'])) {
                $update['primeira_mensagem_em'] = date('Y-m-d H:i:s');
            }
            $this->conversaModel->update((int) $conversa['id'], $update);
            $conversa = $this->conversaModel->find((int) $conversa['id']);
        }

        if ($conversa && !$contatoId) {
            $nomeContatoSync = trim((string) ($conversa['nome_contato'] ?? $nomeContato ?? ''));
            $contatoSync = $this->resolveContatoByPhone(
                (string) ($conversa['telefone'] ?? $normalized),
                $nomeContatoSync !== '' ? $nomeContatoSync : null,
                (int) ($conversa['cliente_id'] ?? 0) ?: null
            );
            $contatoId = (int) ($contatoSync['id'] ?? 0) ?: null;
            if ($contatoId && empty($conversa['contato_id'])) {
                $this->conversaModel->update((int) $conversa['id'], ['contato_id' => $contatoId]);
                $conversa = $this->conversaModel->find((int) $conversa['id']);
            }
        }

        if ($conversa && !empty($osId) && $this->conversaOsModel->db->tableExists('conversa_os')) {
            $this->bindOsToConversa((int) $conversa['id'], (int) $osId, true);
        }

        return $conversa;
    }

    public function registerInboundFromPayload(array $payload, string $provider = 'webhook', ?int $usuarioId = null): ?int
    {
        if (!$this->mensagemModel->db->tableExists('mensagens_whatsapp')) {
            return null;
        }

        $isOutbound = $this->inferOutboundFromPayload($payload);

        $from = $this->payloadPathString($payload, [
            'from',
            'sender',
            'remetente',
            'phone',
            'telefone',
            'number',
            'author',
            'chat_id',
            'jid',
            'contact.phone',
            'contact.number',
            'contact.jid',
            'data.from',
            'data.sender',
            'data.remetente',
            'data.phone',
            'data.telefone',
            'data.number',
            'data.author',
            'data.chat_id',
            'data.jid',
            'data.contact.phone',
            'data.contact.number',
            'data.contact.jid',
        ]);
        $to = $this->payloadPathString($payload, [
            'to',
            'recipient',
            'destinatario',
            'phone_to',
            'target',
            'data.to',
            'data.recipient',
            'data.destinatario',
            'data.phone_to',
            'data.target',
        ]);

        $phoneRef = $isOutbound ? $to : $from;
        if (trim($phoneRef) === '') {
            $phoneRef = $this->payloadPathString($payload, [
                'chat_id',
                'number',
                'sender',
                'recipient',
                'from',
                'telefone',
                'phone',
                'jid',
                'contact.phone',
                'contact.number',
                'data.chat_id',
                'data.number',
                'data.sender',
                'data.recipient',
                'data.from',
                'data.telefone',
                'data.phone',
                'data.jid',
                'data.contact.phone',
                'data.contact.number',
            ]);
        }

        $message = $this->payloadPathString($payload, [
            'message',
            'mensagem',
            'text',
            'body',
            'conteudo',
            'caption',
            'description',
            'content.text',
            'content.message',
            'data.message',
            'data.mensagem',
            'data.text',
            'data.body',
            'data.conteudo',
            'data.caption',
            'data.description',
            'data.content.text',
            'data.content.message',
        ]);
        $mimeType = strtolower($this->payloadPathString($payload, [
            'media_mime_type',
            'mime_type',
            'mime',
            'mimetype',
            'data.media_mime_type',
            'data.mime_type',
            'data.mime',
            'data.mimetype',
        ]));
        $mediaBase64 = $this->payloadPathString($payload, [
            'media_base64',
            'file',
            'base64',
            'media.base64',
            'data.media_base64',
            'data.file',
            'data.base64',
            'data.media.base64',
        ]);
        $mediaFilename = $this->payloadPathString($payload, [
            'media_filename',
            'filename',
            'file_name',
            'name',
            'media.filename',
            'data.media_filename',
            'data.filename',
            'data.file_name',
            'data.name',
            'data.media.filename',
        ]);
        $tipoConteudoPayload = strtolower($this->payloadPathString($payload, [
            'tipo_conteudo',
            'type',
            'message_type',
            'media_type',
            'data.tipo_conteudo',
            'data.type',
            'data.message_type',
            'data.media_type',
        ]));
        $hasMedia = $this->payloadPathBool($payload, [
            'has_media',
            'hasMedia',
            'media',
            'data.has_media',
            'data.hasMedia',
            'data.media',
        ]) === true || $mediaBase64 !== '' || $mimeType !== '';

        if ($hasMedia && $message !== '') {
            $messageCompact = preg_replace('/\s+/', '', $message) ?? '';
            if (strlen($messageCompact) > 800 && preg_match('/^[A-Za-z0-9+\/=]+$/', substr($messageCompact, 0, 800))) {
                // Evita poluir o chat com base64 indevido no campo textual.
                $message = '';
            }
        }

        $phone = $this->normalizePhone($phoneRef);
        if ($phone === '') {
            return null;
        }
        if ($message === '' && !$hasMedia) {
            return null;
        }

        $messageId = $this->payloadPathString($payload, [
            'message_id',
            'messageId',
            'id',
            'msg_id',
            'key.id',
            'data.message_id',
            'data.messageId',
            'data.id',
            'data.msg_id',
            'data.key.id',
        ]);
        if ($messageId !== '') {
            $existing = $this->mensagemModel
                ->where('provider', $provider)
                ->where('provider_message_id', $messageId)
                ->orderBy('id', 'DESC')
                ->first();

            if (!$existing) {
                $existing = $this->mensagemModel
                    ->where('provider_message_id', $messageId)
                    ->groupStart()
                    ->where('telefone', $phone)
                    ->orWhere('direcao', $isOutbound ? 'outbound' : 'inbound')
                    ->groupEnd()
                    ->orderBy('id', 'DESC')
                    ->first();
            }

            if ($existing) {
                $updates = [
                    'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ];
                if ($isOutbound) {
                    $updates['direcao'] = 'outbound';
                    $updates['status'] = 'enviada';
                    if (empty($existing['enviada_em'])) {
                        $updates['enviada_em'] = date('Y-m-d H:i:s');
                    }
                }
                if ($message !== '' && empty($existing['mensagem'])) {
                    $updates['mensagem'] = $message;
                }
                if ($phone !== '' && empty($existing['telefone'])) {
                    $updates['telefone'] = $phone;
                }
                if ($hasMedia) {
                    $existingMime = strtolower(trim((string) ($existing['mime_type'] ?? '')));
                    $existingArquivo = trim((string) ($existing['arquivo'] ?? $existing['anexo_path'] ?? ''));
                    $existingTipo = strtolower(trim((string) ($existing['tipo_conteudo'] ?? '')));

                    $resolvedTipo = $this->resolveInboundContentType(
                        $tipoConteudoPayload,
                        $mimeType !== '' ? $mimeType : ($existingMime !== '' ? $existingMime : null),
                        $existingArquivo !== '' ? $existingArquivo : null
                    );
                    if ($resolvedTipo !== '' && $resolvedTipo !== $existingTipo) {
                        $updates['tipo_conteudo'] = $resolvedTipo;
                    }
                    if ($mimeType !== '' && $existingMime === '') {
                        $updates['mime_type'] = $mimeType;
                    }

                    $shouldHydrateMissingMedia = $mediaBase64 !== '' && (
                        $existingArquivo === ''
                        || in_array($existingTipo, ['texto', 'arquivo', 'ptt', 'voice', 'voice_note'], true)
                    );

                    if ($shouldHydrateMissingMedia) {
                        $savedMedia = $this->saveInboundMedia($mediaBase64, $mimeType, $mediaFilename, $phone);
                        if ($savedMedia) {
                            $arquivoInbound = $savedMedia['arquivo'] ?? null;
                            $mimeInbound = $savedMedia['mime_type'] ?? ($mimeType !== '' ? $mimeType : null);
                            $tipoInbound = $this->resolveInboundContentType($tipoConteudoPayload, $mimeInbound, $arquivoInbound);

                            if (!empty($arquivoInbound)) {
                                $updates['arquivo'] = $arquivoInbound;
                                $updates['anexo_path'] = $arquivoInbound;
                            }
                            if (!empty($mimeInbound)) {
                                $updates['mime_type'] = $mimeInbound;
                            }
                            if ($tipoInbound !== '') {
                                $updates['tipo_conteudo'] = $tipoInbound;
                            }
                        }
                    }
                }
                if (!empty($updates)) {
                    $this->mensagemModel->update((int) $existing['id'], $updates);
                }

                return (int) $existing['id'];
            }

            if ($isOutbound) {
                $candidate = $this->findRecentOutboundCandidate($phone, $provider);
                if ($candidate && $this->outboundCandidateMatches($candidate, $message, $hasMedia)) {
                    $candidateProvider = trim((string) ($candidate['provider'] ?? ''));
                    $updatePayload = [
                        'provider_message_id' => $messageId,
                        'status' => 'enviada',
                        'enviada_em' => date('Y-m-d H:i:s'),
                        'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    ];
                    if ($candidateProvider === '') {
                        $updatePayload['provider'] = $provider;
                    }
                    $this->mensagemModel->update((int) $candidate['id'], $updatePayload);

                    return (int) $candidate['id'];
                }
            }
        }

        if ($isOutbound && $messageId === '') {
            $candidate = $this->findRecentOutboundCandidate($phone, $provider);
            if ($candidate && $this->outboundCandidateMatches($candidate, $message, $hasMedia)) {
                $candidateMensagem = trim((string) ($candidate['mensagem'] ?? ''));
                $updatePayload = [
                    'status' => 'enviada',
                    'enviada_em' => date('Y-m-d H:i:s'),
                    'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ];
                if ($message !== '' && $candidateMensagem === '') {
                    $updatePayload['mensagem'] = $message;
                }
                $this->mensagemModel->update((int) $candidate['id'], $updatePayload);

                return (int) $candidate['id'];
            }
        }

        $cliente = $this->findClienteByPhone($phone);
        $clienteId = $cliente ? (int) $cliente['id'] : null;
        $osId = $clienteId ? $this->findOpenOsByCliente($clienteId) : null;
        $profileName = $this->extractProfileNameFromPayload($payload);
        $nomeContato = $cliente['nome_razao'] ?? $profileName ?? null;
        $conversa = $this->resolveConversationForOutgoing($phone, $clienteId, $osId, $provider, $nomeContato);
        $conversaId = (int) ($conversa['id'] ?? 0) ?: null;
        $mediaSaved = $hasMedia ? $this->saveInboundMedia($mediaBase64, $mimeType, $mediaFilename, $phone) : null;
        $arquivoInbound = $mediaSaved['arquivo'] ?? null;
        $mimeInbound = $mediaSaved['mime_type'] ?? ($mimeType !== '' ? $mimeType : null);
        $tipoConteudo = $this->resolveInboundContentType($tipoConteudoPayload, $mimeInbound, $arquivoInbound);

        $insert = [
            'conversa_id' => $conversaId,
            'provider' => $provider,
            'provider_message_id' => $messageId !== '' ? $messageId : null,
            'direcao' => $isOutbound ? 'outbound' : 'inbound',
            'tipo_conteudo' => $tipoConteudo,
            'mime_type' => $mimeInbound,
            'cliente_id' => $clienteId,
            'os_id' => $osId,
            'telefone' => $phone,
            'tipo_mensagem' => $isOutbound ? 'outbound_externo' : 'inbound',
            'mensagem' => $message !== '' ? $message : null,
            'arquivo' => $arquivoInbound,
            'anexo_path' => $arquivoInbound,
            'status' => $isOutbound ? 'enviada' : 'recebida',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'lida_em' => $isOutbound ? date('Y-m-d H:i:s') : null,
            'enviada_em' => $isOutbound ? date('Y-m-d H:i:s') : null,
            'recebida_em' => $isOutbound ? null : date('Y-m-d H:i:s'),
            'usuario_id' => $usuarioId,
            'enviada_por_bot' => 0,
            'enviada_por_usuario_id' => null,
        ];

        if ($isOutbound) {
            $duplicate = $this->findRecentOutboundDuplicate(
                $conversaId,
                $phone,
                $message,
                $tipoConteudo,
                $mimeInbound,
                $arquivoInbound
            );
            if ($duplicate) {
                $updatePayload = [
                    'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'status' => 'enviada',
                ];
                if ($messageId !== '' && empty($duplicate['provider_message_id'])) {
                    $updatePayload['provider_message_id'] = $messageId;
                }
                if (empty($duplicate['provider']) && $provider !== '') {
                    $updatePayload['provider'] = $provider;
                }
                if (empty($duplicate['enviada_em'])) {
                    $updatePayload['enviada_em'] = date('Y-m-d H:i:s');
                }
                $this->mensagemModel->update((int) $duplicate['id'], $updatePayload);

                return (int) $duplicate['id'];
            }
        }

        $mensagemId = $this->mensagemModel->insert($insert, true);
        if (!$mensagemId) {
            return null;
        }

        $reopenedByInbound = false;
        if ($conversaId) {
            $statusAtualConversa = strtolower(trim((string) ($conversa['status'] ?? 'aberta')));
            $updateConversa = [
                'ultima_mensagem_em' => date('Y-m-d H:i:s'),
                'primeira_mensagem_em' => (empty($conversa['primeira_mensagem_em']) ? date('Y-m-d H:i:s') : $conversa['primeira_mensagem_em']),
            ];
            if (!$isOutbound) {
                $updateConversa['nao_lidas'] = (int) ($conversa['nao_lidas'] ?? 0) + 1;
                if ($statusAtualConversa === 'resolvida') {
                    $updateConversa['status'] = 'aberta';
                    $reopenedByInbound = true;
                }
            }
            $this->conversaModel->update($conversaId, $updateConversa);
            $conversa = $this->conversaModel->find($conversaId);
        }

        $descricaoEvento = $message !== ''
            ? $message
            : ('Arquivo ' . ($isOutbound ? 'enviado' : 'recebido') . ' via WhatsApp: ' . ($mediaFilename !== '' ? $mediaFilename : 'sem_nome'));

        if ($isOutbound) {
            $this->crmService->registerInteraction([
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'conversa_id' => $conversaId,
                'tipo' => 'mensagem_enviada',
                'descricao' => $descricaoEvento,
                'canal' => 'whatsapp',
                'usuario_id' => $usuarioId,
                'data_interacao' => date('Y-m-d H:i:s'),
                'payload_json' => $payload,
            ]);

            $this->crmService->registerEvent([
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'conversa_id' => $conversaId,
                'tipo_evento' => 'whatsapp_enviada_externa',
                'titulo' => 'Mensagem enviada fora do ERP (WhatsApp)',
                'descricao' => $descricaoEvento,
                'origem' => 'whatsapp',
                'usuario_id' => $usuarioId,
                'data_evento' => date('Y-m-d H:i:s'),
                'payload_json' => $payload,
            ]);

            $this->registerCrmMensagem([
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'conversa_id' => $conversaId,
                'provider' => $provider,
                'direcao' => 'outbound',
                'tipo_conteudo' => $tipoConteudo,
                'conteudo' => $descricaoEvento,
                'arquivo' => $arquivoInbound,
                'status' => 'enviada',
                'payload_json' => $payload,
                'usuario_id' => $usuarioId,
            ]);
        } else {
            if ($reopenedByInbound && $conversaId) {
                $this->crmService->registerEvent([
                    'cliente_id' => $clienteId,
                    'os_id' => $osId,
                    'conversa_id' => $conversaId,
                    'tipo_evento' => 'conversa_reaberta_inbound',
                    'titulo' => 'Conversa reaberta automaticamente',
                    'descricao' => 'Nova mensagem inbound alterou o status de "resolvida" para "aberta".',
                    'origem' => 'central_mensagens',
                    'usuario_id' => $usuarioId,
                    'data_evento' => date('Y-m-d H:i:s'),
                    'payload_json' => [
                        'before' => 'resolvida',
                        'after' => 'aberta',
                    ],
                ]);
            }

            $this->crmService->registerInteraction([
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'conversa_id' => $conversaId,
                'tipo' => 'mensagem_recebida',
                'descricao' => $descricaoEvento,
                'canal' => 'whatsapp',
                'usuario_id' => $usuarioId,
                'data_interacao' => date('Y-m-d H:i:s'),
                'payload_json' => $payload,
            ]);

            $this->crmService->registerEvent([
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'conversa_id' => $conversaId,
                'tipo_evento' => 'whatsapp_recebida',
                'titulo' => 'Mensagem recebida no WhatsApp',
                'descricao' => $descricaoEvento,
                'origem' => 'whatsapp',
                'usuario_id' => $usuarioId,
                'data_evento' => date('Y-m-d H:i:s'),
                'payload_json' => $payload,
            ]);

            $this->registerCrmMensagem([
                'cliente_id' => $clienteId,
                'os_id' => $osId,
                'conversa_id' => $conversaId,
                'provider' => $provider,
                'direcao' => 'inbound',
                'tipo_conteudo' => $tipoConteudo,
                'conteudo' => $descricaoEvento,
                'arquivo' => $arquivoInbound,
                'status' => 'recebida',
                'payload_json' => $payload,
                'usuario_id' => $usuarioId,
            ]);

            // Fase 3: chatbot inteligente de atendimento 24h
            if ($conversaId && $conversa) {
                try {
                    (new ChatbotService())->processarInbound(
                        $conversa,
                        $this->mensagemModel->find((int) $mensagemId) ?? [],
                        $payload
                    );
                } catch (\Throwable $e) {
                    log_message('warning', 'Falha ao processar chatbot inbound: ' . $e->getMessage());
                }
            }

            $this->notifyMobileInbound(
                $conversa,
                (int) $mensagemId,
                $message,
                $tipoConteudo,
                $mediaFilename,
                $phone,
                $clienteId,
                $osId,
                $usuarioId
            );
        }

        return (int) $mensagemId;
    }

    public function afterOutboundSent(
        ?int $conversaId,
        ?int $clienteId,
        ?int $osId,
        string $mensagem,
        string $tipoMensagem,
        ?int $usuarioId = null,
        array $payload = []
    ): void {
        $this->crmService->registerInteraction([
            'cliente_id' => $clienteId,
            'os_id' => $osId,
            'conversa_id' => $conversaId,
            'tipo' => 'mensagem_enviada',
            'descricao' => $mensagem,
            'canal' => 'whatsapp',
            'usuario_id' => $usuarioId,
            'data_interacao' => date('Y-m-d H:i:s'),
            'payload_json' => $payload,
        ]);

        $this->crmService->registerEvent([
            'cliente_id' => $clienteId,
            'os_id' => $osId,
            'conversa_id' => $conversaId,
            'tipo_evento' => 'whatsapp_enviada',
            'titulo' => 'Mensagem enviada no WhatsApp',
            'descricao' => $mensagem !== '' ? $mensagem : ('Envio tipo: ' . $tipoMensagem),
            'origem' => 'whatsapp',
            'usuario_id' => $usuarioId,
            'data_evento' => date('Y-m-d H:i:s'),
            'payload_json' => $payload,
        ]);

        if ($conversaId) {
            $this->conversaModel->update($conversaId, [
                'ultima_mensagem_em' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->registerCrmMensagem([
            'cliente_id' => $clienteId,
            'os_id' => $osId,
            'conversa_id' => $conversaId,
            'provider' => (string) ($payload['provider'] ?? get_config('whatsapp_direct_provider', 'menuia')),
            'direcao' => 'outbound',
            'tipo_conteudo' => (string) ($payload['tipo_conteudo'] ?? 'texto'),
            'conteudo' => $mensagem,
            'arquivo' => $payload['arquivo'] ?? null,
            'status' => !empty($payload['ok']) ? 'enviada' : 'erro',
            'payload_json' => $payload,
            'usuario_id' => $usuarioId,
        ]);
    }

    public function bindOsToConversa(int $conversaId, int $osId, bool $principal = false): void
    {
        if (!$this->conversaOsModel->db->tableExists('conversa_os')) {
            return;
        }

        $exists = $this->conversaOsModel
            ->where('conversa_id', $conversaId)
            ->where('os_id', $osId)
            ->first();

        if ($exists) {
            $update = [];
            if ($principal && (int) ($exists['principal'] ?? 0) !== 1) {
                $update['principal'] = 1;
            }
            if (!empty($update)) {
                $this->conversaOsModel->update((int) $exists['id'], $update);
            }
        } else {
            $this->conversaOsModel->insert([
                'conversa_id' => $conversaId,
                'os_id' => $osId,
                'principal' => $principal ? 1 : 0,
            ]);
        }

        if ($principal) {
            $updates = ['os_id_principal' => $osId];
            $os = $this->osModel->select('id, cliente_id')->find($osId);
            $clienteIdOs = (int) ($os['cliente_id'] ?? 0);
            if ($clienteIdOs > 0) {
                $updates['cliente_id'] = $clienteIdOs;
            }
            $this->conversaModel->update($conversaId, $updates);

            if ($clienteIdOs > 0 && $this->contatoModel->db->tableExists('contatos')) {
                $conversaAtualizada = $this->conversaModel->find($conversaId);
                $contatoId = (int) ($conversaAtualizada['contato_id'] ?? 0);
                if ($contatoId > 0) {
                    $contato = $this->contatoModel->find($contatoId);
                    if ($contato && (int) ($contato['cliente_id'] ?? 0) <= 0) {
                        $this->contatoModel->update(
                            $contatoId,
                            $this->contatoModel->buildClienteConvertidoPayload($clienteIdOs)
                        );
                    }
                }
            }
        }
    }

    public function markConversationRead(int $conversaId): void
    {
        if (!$this->conversaModel->db->tableExists('conversas_whatsapp')) {
            return;
        }
        $this->conversaModel->update($conversaId, ['nao_lidas' => 0]);
        if ($this->mensagemModel->db->tableExists('mensagens_whatsapp')) {
            $this->mensagemModel->db->table('mensagens_whatsapp')
                ->where('conversa_id', $conversaId)
                ->where('direcao', 'inbound')
                ->where('lida_em', null)
                ->set('lida_em', date('Y-m-d H:i:s'))
                ->update();
        }
    }

    public function updateConversationMeta(int $conversaId, array $payload, ?int $usuarioId = null): bool
    {
        if (!$this->conversaModel->db->tableExists('conversas_whatsapp')) {
            return false;
        }

        $conversa = $this->conversaModel->find($conversaId);
        if (!$conversa) {
            return false;
        }

        $beforeStatus = (string) ($conversa['status'] ?? 'aberta');
        $beforeResponsavel = (int) ($conversa['responsavel_id'] ?? 0);
        $beforeAutomacao = (int) ($conversa['automacao_ativa'] ?? 1);
        $beforeAguardandoHumano = (int) ($conversa['aguardando_humano'] ?? 0);
        $beforePrioridade = (string) ($conversa['prioridade'] ?? 'normal');

        $updates = [];
        if (array_key_exists('status', $payload)) {
            $status = strtolower(trim((string) $payload['status']));
            if (in_array($status, ['aberta', 'aguardando', 'resolvida', 'arquivada'], true)) {
                $updates['status'] = $status;
            }
        }

        if (array_key_exists('responsavel_id', $payload)) {
            $responsavelId = (int) $payload['responsavel_id'];
            $updates['responsavel_id'] = $responsavelId > 0 ? $responsavelId : null;
        }

        if (array_key_exists('automacao_ativa', $payload)) {
            $automacaoAtiva = (int) $payload['automacao_ativa'] === 1 ? 1 : 0;
            $updates['automacao_ativa'] = $automacaoAtiva;
            if ($automacaoAtiva === 1 && !array_key_exists('aguardando_humano', $payload)) {
                $updates['aguardando_humano'] = 0;
            }
        }

        if (array_key_exists('aguardando_humano', $payload)) {
            $updates['aguardando_humano'] = (int) $payload['aguardando_humano'] === 1 ? 1 : 0;
        }

        if (array_key_exists('prioridade', $payload)) {
            $prioridade = strtolower(trim((string) $payload['prioridade']));
            if (in_array($prioridade, ['baixa', 'normal', 'alta', 'urgente'], true)) {
                $updates['prioridade'] = $prioridade;
            }
        }

        if (!empty($updates)) {
            $this->conversaModel->update($conversaId, $updates);
        }

        if (array_key_exists('tag_ids', $payload) && $this->conversaTagModel->db->tableExists('conversa_tags')) {
            $tagIdsRaw = is_array($payload['tag_ids']) ? $payload['tag_ids'] : [];
            $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIdsRaw), static fn ($id) => $id > 0)));

            $validTagIds = [];
            if (!empty($tagIds) && $this->crmTagModel->db->tableExists('crm_tags')) {
                $rows = $this->crmTagModel->select('id')->whereIn('id', $tagIds)->where('ativo', 1)->findAll();
                $validTagIds = array_map(static fn ($row) => (int) $row['id'], $rows);
            }

            $this->conversaTagModel->where('conversa_id', $conversaId)->delete();
            foreach ($validTagIds as $tagId) {
                $this->conversaTagModel->insert([
                    'conversa_id' => $conversaId,
                    'tag_id' => $tagId,
                ]);
            }
        }

        $after = $this->conversaModel->find($conversaId);
        $afterStatus = (string) ($after['status'] ?? $beforeStatus);
        $afterResponsavel = (int) ($after['responsavel_id'] ?? 0);
        $afterAutomacao = (int) ($after['automacao_ativa'] ?? $beforeAutomacao);
        $afterAguardandoHumano = (int) ($after['aguardando_humano'] ?? $beforeAguardandoHumano);
        $afterPrioridade = (string) ($after['prioridade'] ?? $beforePrioridade);

        if ($beforeStatus !== $afterStatus) {
            $this->crmService->registerEvent([
                'cliente_id' => $after['cliente_id'] ?? null,
                'os_id' => $after['os_id_principal'] ?? null,
                'conversa_id' => $conversaId,
                'tipo_evento' => 'conversa_status_atualizado',
                'titulo' => 'Status da conversa atualizado',
                'descricao' => 'Status alterado de "' . $beforeStatus . '" para "' . $afterStatus . '".',
                'origem' => 'central_mensagens',
                'usuario_id' => $usuarioId,
                'data_evento' => date('Y-m-d H:i:s'),
                'payload_json' => [
                    'before' => $beforeStatus,
                    'after' => $afterStatus,
                ],
            ]);
        }

        if ($beforeResponsavel !== $afterResponsavel) {
            $this->crmService->registerEvent([
                'cliente_id' => $after['cliente_id'] ?? null,
                'os_id' => $after['os_id_principal'] ?? null,
                'conversa_id' => $conversaId,
                'tipo_evento' => 'conversa_responsavel_atualizado',
                'titulo' => 'Responsavel da conversa atualizado',
                'descricao' => $afterResponsavel > 0
                    ? ('Conversa atribuida ao usuario ID ' . $afterResponsavel . '.')
                    : 'Conversa removida de atribuicao.',
                'origem' => 'central_mensagens',
                'usuario_id' => $usuarioId,
                'data_evento' => date('Y-m-d H:i:s'),
                'payload_json' => [
                    'before' => $beforeResponsavel > 0 ? $beforeResponsavel : null,
                    'after' => $afterResponsavel > 0 ? $afterResponsavel : null,
                ],
            ]);
        }

        if ($beforeAutomacao !== $afterAutomacao) {
            $this->crmService->registerEvent([
                'cliente_id' => $after['cliente_id'] ?? null,
                'os_id' => $after['os_id_principal'] ?? null,
                'conversa_id' => $conversaId,
                'tipo_evento' => 'conversa_automacao_atualizada',
                'titulo' => 'Automacao da conversa atualizada',
                'descricao' => $afterAutomacao === 1 ? 'Autoatendimento ativado.' : 'Autoatendimento desativado.',
                'origem' => 'central_mensagens',
                'usuario_id' => $usuarioId,
                'data_evento' => date('Y-m-d H:i:s'),
                'payload_json' => [
                    'before' => $beforeAutomacao,
                    'after' => $afterAutomacao,
                ],
            ]);
        }

        if ($beforeAguardandoHumano !== $afterAguardandoHumano) {
            $this->crmService->registerEvent([
                'cliente_id' => $after['cliente_id'] ?? null,
                'os_id' => $after['os_id_principal'] ?? null,
                'conversa_id' => $conversaId,
                'tipo_evento' => 'conversa_aguardando_humano_atualizada',
                'titulo' => 'Escalonamento humano atualizado',
                'descricao' => $afterAguardandoHumano === 1 ? 'Conversa marcada como aguardando atendente humano.' : 'Conversa removida da fila de espera humana.',
                'origem' => 'central_mensagens',
                'usuario_id' => $usuarioId,
                'data_evento' => date('Y-m-d H:i:s'),
                'payload_json' => [
                    'before' => $beforeAguardandoHumano,
                    'after' => $afterAguardandoHumano,
                ],
            ]);
        }

        if ($beforePrioridade !== $afterPrioridade) {
            $this->crmService->registerEvent([
                'cliente_id' => $after['cliente_id'] ?? null,
                'os_id' => $after['os_id_principal'] ?? null,
                'conversa_id' => $conversaId,
                'tipo_evento' => 'conversa_prioridade_atualizada',
                'titulo' => 'Prioridade da conversa atualizada',
                'descricao' => 'Prioridade alterada de "' . $beforePrioridade . '" para "' . $afterPrioridade . '".',
                'origem' => 'central_mensagens',
                'usuario_id' => $usuarioId,
                'data_evento' => date('Y-m-d H:i:s'),
                'payload_json' => [
                    'before' => $beforePrioridade,
                    'after' => $afterPrioridade,
                ],
            ]);
        }

        return true;
    }

    public function getConversaTagIds(int $conversaId): array
    {
        if (!$this->conversaTagModel->db->tableExists('conversa_tags')) {
            return [];
        }

        $rows = $this->conversaTagModel
            ->select('tag_id')
            ->where('conversa_id', $conversaId)
            ->findAll();

        return array_map(static fn ($row) => (int) $row['tag_id'], $rows);
    }

    public function getTagCatalog(): array
    {
        if (!$this->crmTagModel->db->tableExists('crm_tags')) {
            return [];
        }
        return $this->crmTagModel->ativas();
    }

    public function getResponsaveisAtivos(): array
    {
        if (!$this->usuarioModel->db->tableExists('usuarios')) {
            return [];
        }

        return $this->usuarioModel
            ->select('id, nome, email')
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->findAll();
    }

    public function syncInboundQueue(int $limit = 100, bool $forceGatewayHistory = false): int
    {
        $count = $this->syncGatewayHistoryIfNeeded($forceGatewayHistory);
        $count += $this->processPendingInboundRows($limit);

        return $count;
    }

    /**
     * Processa apenas a fila local de inbound ja recebida pelo webhook, sem acionar sync de historico no gateway.
     */
    public function processInboundQueueOnly(int $limit = 100): int
    {
        return $this->processPendingInboundRows($limit);
    }

    private function processPendingInboundRows(int $limit = 100): int
    {
        $processed = 0;
        if (!$this->inboundModel->db->tableExists('whatsapp_inbound')) {
            return 0;
        }
        $rows = $this->inboundModel->where('processado', 0)->orderBy('id', 'ASC')->findAll($limit);
        foreach ($rows as $row) {
            $payload = json_decode((string) ($row['payload'] ?? ''), true);
            if (!is_array($payload)) {
                $payload = [];
            }
            if (!empty($row['remetente'])) {
                $payload['from'] = $payload['from'] ?? $row['remetente'];
                $payload['sender'] = $payload['sender'] ?? $row['remetente'];
                $payload['remetente'] = $payload['remetente'] ?? $row['remetente'];
                $payload['phone'] = $payload['phone'] ?? $row['remetente'];
                $payload['number'] = $payload['number'] ?? $row['remetente'];
            }
            if (!empty($row['conteudo'])) {
                $payload['message'] = $payload['message'] ?? $row['conteudo'];
                $payload['text'] = $payload['text'] ?? $row['conteudo'];
                $payload['body'] = $payload['body'] ?? $row['conteudo'];
                $payload['conteudo'] = $payload['conteudo'] ?? $row['conteudo'];
            }
            if (!empty($row['provedor'])) {
                $payload['provider'] = $payload['provider'] ?? $row['provedor'];
            }

            $ok = $this->registerInboundFromPayload($payload, (string) ($row['provedor'] ?? 'webhook'));
            if ($ok) {
                $this->inboundModel->update((int) $row['id'], ['processado' => 1]);
                $processed++;
            }
        }

        return $processed;
    }

    private function syncGatewayHistoryIfNeeded(bool $force = false): int
    {
        $provider = trim((string) get_config('whatsapp_direct_provider', get_config('whatsapp_provider', 'menuia')));
        if (!in_array($provider, ['api_whats_local', 'local_node', 'api_whats_linux'], true)) {
            return 0;
        }

        $cacheKey = 'cm_gateway_history_sync_at';
        $now = time();
        try {
            $cache = cache();
            if ($cache) {
                $last = (int) ($cache->get($cacheKey) ?? 0);
                if (!$force && $last > 0 && ($now - $last) < 8) {
                    return 0;
                }
                $cache->save($cacheKey, $now, 15);
            }
        } catch (\Throwable $e) {
            // segue sem cache caso o serviço esteja indisponível
        }

        $baseUrl = '';
        $token = '';
        $origin = '';
        $timeout = 20;
        $providerId = 'api_whats_local';

        if ($provider === 'api_whats_linux') {
            $baseUrl = trim((string) get_config('whatsapp_linux_node_url', 'http://127.0.0.1:3001'));
            $token = trim((string) get_config('whatsapp_linux_node_token', ''));
            $origin = trim((string) get_config('whatsapp_linux_node_origin', base_url('/')));
            $timeout = max(5, (int) get_config('whatsapp_linux_node_timeout', 20));
            $providerId = 'api_whats_linux';
        } else {
            $baseUrl = trim((string) get_config('whatsapp_local_node_url', 'http://127.0.0.1:3001'));
            $token = trim((string) get_config('whatsapp_local_node_token', ''));
            $origin = trim((string) get_config('whatsapp_local_node_origin', base_url('/')));
            $timeout = max(5, (int) get_config('whatsapp_local_node_timeout', 20));
            $providerId = 'api_whats_local';
        }

        if ($baseUrl === '') {
            return 0;
        }

        $limitChats = $force ? 10 : 6;
        $perChat = $force ? 10 : 6;
        $maxTotal = $force ? 120 : 60;
        $sinceSeconds = $force ? 86400 : 21600;
        $endpoint = rtrim($baseUrl, '/') . '/sync-chat-history'
            . '?limit_chats=' . $limitChats
            . '&per_chat=' . $perChat
            . '&max_total=' . $maxTotal
            . '&since_seconds=' . $sinceSeconds;
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        if ($token !== '') {
            $headers[] = 'X-Api-Token: ' . $token;
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        if ($origin !== '') {
            $headers[] = 'X-ERP-Origin: ' . $origin;
            $headers[] = 'Origin: ' . $origin;
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $error !== '' || $http < 200 || $http >= 300) {
            return 0;
        }

        $json = json_decode((string) $raw, true);
        if (!is_array($json) || empty($json['success'])) {
            return 0;
        }

        $items = $json['data']['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            return 0;
        }

        $count = 0;
        foreach ($items as $payload) {
            if (!is_array($payload)) {
                continue;
            }
            $providerFromPayload = trim((string) ($payload['provider'] ?? ''));
            $sourceProvider = $providerFromPayload !== '' ? $providerFromPayload : $providerId;
            $ok = $this->registerInboundFromPayload($payload, $sourceProvider);
            if ($ok) {
                $count++;
            }
        }

        return $count;
    }

    private function findClienteByPhone(string $phone): ?array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        $suffix = substr($digits, -8);
        if ($suffix === '') {
            return null;
        }

        return $this->clienteModel
            ->groupStart()
            ->like('telefone1', $suffix)
            ->orLike('telefone2', $suffix)
            ->orLike('telefone_contato', $suffix)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->first();
    }

    private function resolveContatoByPhone(string $phone, ?string $nomeContato = null, ?int $clienteId = null): ?array
    {
        if (!$this->contatoModel->db->tableExists('contatos')) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $phone) ?? '';
        if ($normalized === '') {
            return null;
        }

        $safeNome = $this->sanitizeProfileName($nomeContato);
        $now = date('Y-m-d H:i:s');

        $contato = $this->contatoModel->findByPhone($normalized);
        if (!$contato) {
            $suffix = substr($normalized, -11);
            if ($suffix !== '' && $suffix !== $normalized) {
                $contato = $this->contatoModel->findByPhone($suffix);
            }
        }

        if ($contato) {
            $contatoId = (int) ($contato['id'] ?? 0);
            if ($contatoId <= 0) {
                return $contato;
            }

            $updates = [
                'ultimo_contato_em' => $now,
            ];
            if ($clienteId && (int) ($contato['cliente_id'] ?? 0) <= 0) {
                $updates = $this->contatoModel->buildClienteConvertidoPayload($clienteId, $updates);
            }
            if ($safeNome && empty($contato['nome'])) {
                $updates['nome'] = $safeNome;
            }
            if ($safeNome && empty($contato['whatsapp_nome_perfil'])) {
                $updates['whatsapp_nome_perfil'] = $safeNome;
            }
            if (
                !$clienteId
                && (int) ($contato['cliente_id'] ?? 0) <= 0
                && $safeNome !== ''
            ) {
                $updates = $this->contatoModel->buildLeadPayload($updates, true);
            }
            if (empty($contato['telefone'])) {
                $updates['telefone'] = $normalized;
            }
            if (empty($contato['telefone_normalizado'])) {
                $updates['telefone_normalizado'] = $normalized;
            }

            if (!empty($updates)) {
                $this->contatoModel->update($contatoId, $updates);
            }

            return $this->contatoModel->find($contatoId) ?: $contato;
        }

        $insertPayload = [
            'nome' => $safeNome ?: null,
            'telefone' => $normalized,
            'telefone_normalizado' => $normalized,
            'whatsapp_nome_perfil' => $safeNome ?: null,
            'origem' => 'whatsapp',
            'ultimo_contato_em' => $now,
        ];

        if ($clienteId) {
            $insertPayload = $this->contatoModel->buildClienteConvertidoPayload($clienteId, $insertPayload);
        } else {
            $insertPayload = $this->contatoModel->buildLeadPayload($insertPayload, $safeNome !== '');
        }

        $insertId = (int) $this->contatoModel->insert($insertPayload, true);

        if ($insertId <= 0) {
            return null;
        }

        return $this->contatoModel->find($insertId);
    }

    private function findOpenOsByCliente(int $clienteId): ?int
    {
        $row = $this->osModel
            ->select('id')
            ->where('cliente_id', $clienteId)
            ->whereNotIn('estado_fluxo', ['encerrado', 'cancelado'])
            ->orderBy('id', 'DESC')
            ->first();

        if (!$row) {
            $row = $this->osModel
                ->select('id')
                ->where('cliente_id', $clienteId)
                ->orderBy('id', 'DESC')
                ->first();
        }

        return $row ? (int) $row['id'] : null;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function extractProfileNameFromPayload(array $payload): ?string
    {
        $candidates = [
            $payload['name'] ?? null,
            $payload['nome'] ?? null,
            $payload['notify_name'] ?? null,
            $payload['notifyName'] ?? null,
            $payload['display_name'] ?? null,
            $payload['displayName'] ?? null,
            $payload['push_name'] ?? null,
            $payload['pushName'] ?? null,
            $payload['sender_name'] ?? null,
            $payload['senderName'] ?? null,
            $payload['contact_name'] ?? null,
            $payload['contactName'] ?? null,
            $payload['profile_name'] ?? null,
            $payload['profileName'] ?? null,
            $payload['contact']['name'] ?? null,
            $payload['contact']['display_name'] ?? null,
            $payload['contact']['profile_name'] ?? null,
            $payload['data']['name'] ?? null,
            $payload['data']['nome'] ?? null,
            $payload['data']['notify_name'] ?? null,
            $payload['data']['notifyName'] ?? null,
            $payload['data']['display_name'] ?? null,
            $payload['data']['displayName'] ?? null,
            $payload['data']['push_name'] ?? null,
            $payload['data']['pushName'] ?? null,
            $payload['data']['sender_name'] ?? null,
            $payload['data']['senderName'] ?? null,
            $payload['data']['contact_name'] ?? null,
            $payload['data']['contactName'] ?? null,
            $payload['data']['profile_name'] ?? null,
            $payload['data']['profileName'] ?? null,
            $payload['data']['contact']['name'] ?? null,
            $payload['data']['contact']['display_name'] ?? null,
            $payload['data']['contact']['profile_name'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $safe = $this->sanitizeProfileName($candidate);
            if ($safe !== null) {
                return $safe;
            }
        }

        return null;
    }

    /**
     * @param mixed $raw
     */
    private function sanitizeProfileName($raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits !== '' && strlen($digits) >= 8 && strlen($value) <= 20) {
            return null;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        if ($length < 2) {
            return null;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, 120);
        }

        return substr($value, 0, 120);
    }

    private function resolveInboundContentType(string $payloadType, ?string $mimeType, ?string $arquivo): string
    {
        $type = strtolower(trim($payloadType));
        if ($type !== '' && $type !== 'chat') {
            if ($type === 'ptt' || str_contains($type, 'voice') || str_contains($type, 'audio')) {
                return 'audio';
            }
            if (str_contains($type, 'image')) {
                return 'imagem';
            }
            if (str_contains($type, 'video')) {
                return 'video';
            }
            if (str_contains($type, 'document')) {
                $mime = strtolower(trim((string) $mimeType));
                return $mime === 'application/pdf' ? 'pdf' : 'arquivo';
            }
            if ($type === 'text' || $type === 'chat') {
                return 'texto';
            }
            return $type;
        }

        $mime = strtolower(trim((string) $mimeType));
        if ($mime !== '') {
            if (str_starts_with($mime, 'image/')) {
                return 'imagem';
            }
            if (str_starts_with($mime, 'audio/')) {
                return 'audio';
            }
            if (str_starts_with($mime, 'video/')) {
                return 'video';
            }
            if ($mime === 'application/pdf') {
                return 'pdf';
            }
            return 'arquivo';
        }

        return !empty($arquivo) ? 'arquivo' : 'texto';
    }

    private function findRecentOutboundCandidate(string $phone, string $provider = ''): ?array
    {
        $provider = trim($provider);
        if ($provider !== '') {
            $candidate = $this->mensagemModel
                ->where('provider', $provider)
                ->where('direcao', 'outbound')
                ->where('telefone', $phone)
                ->where('provider_message_id', null)
                ->orderBy('id', 'DESC')
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        return $this->mensagemModel
            ->where('direcao', 'outbound')
            ->where('telefone', $phone)
            ->where('provider_message_id', null)
            ->orderBy('id', 'DESC')
            ->first();
    }

    private function outboundCandidateMatches(array $candidate, string $message, bool $hasMedia): bool
    {
        $referenceTs = !empty($candidate['created_at'])
            ? strtotime((string) $candidate['created_at'])
            : (!empty($candidate['updated_at']) ? strtotime((string) $candidate['updated_at']) : false);
        $isRecent = $referenceTs !== false ? (time() - $referenceTs) <= 300 : true;
        if (!$isRecent) {
            return false;
        }

        $incomingMessage = trim($message);
        $candidateMessage = trim((string) ($candidate['mensagem'] ?? ''));
        if ($incomingMessage !== '' && $candidateMessage !== '' && $candidateMessage !== $incomingMessage) {
            return false;
        }

        if ($hasMedia) {
            $candidateArquivo = trim((string) ($candidate['arquivo'] ?? $candidate['anexo_path'] ?? ''));
            $candidateTipo = strtolower(trim((string) ($candidate['tipo_conteudo'] ?? '')));
            $candidateMime = strtolower(trim((string) ($candidate['mime_type'] ?? '')));
            if (
                $candidateArquivo === ''
                && !in_array($candidateTipo, ['imagem', 'video', 'audio', 'pdf', 'arquivo'], true)
                && $candidateMime === ''
            ) {
                return false;
            }
        }

        return true;
    }

    private function findRecentOutboundDuplicate(
        ?int $conversaId,
        string $phone,
        string $message,
        string $tipoConteudo,
        ?string $mimeType,
        ?string $arquivo
    ): ?array {
        $builder = $this->mensagemModel
            ->where('direcao', 'outbound')
            ->where('telefone', $phone)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 45))
            ->orderBy('id', 'DESC');

        if (!empty($conversaId)) {
            $builder->where('conversa_id', (int) $conversaId);
        }

        $normalizedMessage = trim($message);
        if ($normalizedMessage !== '') {
            $builder->where('mensagem', $normalizedMessage);
        } else {
            $builder->groupStart()
                ->where('mensagem', null)
                ->orWhere('mensagem', '')
                ->groupEnd();
        }

        $normalizedTipo = strtolower(trim($tipoConteudo));
        if ($normalizedTipo !== '') {
            $builder->where('tipo_conteudo', $normalizedTipo);
        }

        $normalizedArquivo = trim((string) $arquivo);
        if ($normalizedArquivo !== '') {
            $builder->groupStart()
                ->where('arquivo', $normalizedArquivo)
                ->orWhere('anexo_path', $normalizedArquivo)
                ->groupEnd();
        } elseif ($normalizedMessage === '') {
            $normalizedMime = strtolower(trim((string) $mimeType));
            if ($normalizedMime !== '') {
                $builder->where('mime_type', $normalizedMime);
            }
        }

        return $builder->first();
    }

    /**
     * @return array{arquivo:string,mime_type:string,tipo_conteudo:string,arquivo_nome:string,tamanho_bytes:int}|null
     */
    private function saveInboundMedia(string $base64, string $mimeType, string $filename, string $phone): ?array
    {
        $raw = trim($base64);
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'data:')) {
            $comma = strpos($raw, ',');
            if ($comma !== false) {
                $meta = substr($raw, 5, $comma - 5);
                $raw = substr($raw, $comma + 1);
                if ($mimeType === '' && str_contains($meta, ';')) {
                    $mimeType = trim((string) strstr($meta, ';', true));
                } elseif ($mimeType === '') {
                    $mimeType = trim($meta);
                }
            }
        }

        $binary = base64_decode($raw, true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $mime = strtolower(trim($mimeType));
        if ($mime === '' || $mime === 'application/octet-stream') {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_buffer($finfo, $binary);
                if (is_string($detected) && $detected !== '') {
                    $mime = strtolower($detected);
                }
                finfo_close($finfo);
            }
            if ($mime === '') {
                $mime = 'application/octet-stream';
            }
        }

        return $this->storeBinaryForPhone(
            $phone,
            $binary,
            $mime,
            $filename !== '' ? $filename : ('inbound_' . date('Ymd_His'))
        );
    }

    /**
     * Armazena arquivo enviado via Central de Mensagens por telefone/tipo.
     *
     * @return array{arquivo:string,mime_type:string,tipo_conteudo:string,arquivo_nome:string,tamanho_bytes:int}|null
     */
    public function storeOutboundUpload(UploadedFile $file, string $phone): ?array
    {
        if (!$file->isValid()) {
            return null;
        }

        $tmp = $file->getTempName();
        if (!is_file($tmp)) {
            return null;
        }

        $binary = @file_get_contents($tmp);
        if ($binary === false || $binary === '') {
            return null;
        }

        $mime = strtolower(trim((string) $file->getMimeType()));
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }

        $clientName = trim((string) $file->getClientName());
        if ($clientName === '') {
            $clientName = 'anexo_' . date('Ymd_His') . '.' . $this->extensionByMime($mime);
        }

        return $this->storeBinaryForPhone($phone, $binary, $mime, $clientName);
    }

    /**
     * Copia arquivo existente (ex.: PDF da OS) para estrutura organizada da Central.
     *
     * @return array{arquivo:string,mime_type:string,tipo_conteudo:string,arquivo_nome:string,tamanho_bytes:int}|null
     */
    public function copyFileToPhoneMedia(
        string $sourcePath,
        string $phone,
        ?string $originalName = null,
        ?string $forcedMime = null
    ): ?array {
        $path = trim($sourcePath);
        if ($path === '' || !is_file($path)) {
            return null;
        }

        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return null;
        }

        $mime = strtolower(trim((string) ($forcedMime ?? '')));
        if ($mime === '' || $mime === 'application/octet-stream') {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $path);
                if (is_string($detected) && trim($detected) !== '') {
                    $mime = strtolower(trim($detected));
                }
                finfo_close($finfo);
            }
        }
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }

        $name = trim((string) ($originalName ?? ''));
        if ($name === '') {
            $name = basename($path);
        }

        return $this->storeBinaryForPhone($phone, $binary, $mime, $name);
    }

    /**
     * @return array{arquivo:string,mime_type:string,tipo_conteudo:string,arquivo_nome:string,tamanho_bytes:int}|null
     */
    private function storeBinaryForPhone(string $phone, string $binary, string $mime, string $filename): ?array
    {
        $folder = $this->mediaFolderByMime($mime, $filename);
        $safePhone = preg_replace('/\D+/', '', $phone) ?: 'sem_numero';
        $safeName = $this->sanitizeFileName($filename);
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        if ($ext === '') {
            $safeName .= '.' . $this->extensionByMime($mime);
        }

        $relativeDir = self::MEDIA_BASE_DIR . '/' . $safePhone . '/' . $folder;
        $targetDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        if (!is_dir($targetDir)) {
            log_message('error', '[CentralMensagens] Falha ao criar diretorio de midia: {dir}', ['dir' => $targetDir]);
            return null;
        }

        $destName = $safeName;
        $seq = 1;
        while (is_file($targetDir . DIRECTORY_SEPARATOR . $destName)) {
            $nameNoExt = pathinfo($safeName, PATHINFO_FILENAME);
            $extension = pathinfo($safeName, PATHINFO_EXTENSION);
            $destName = $nameNoExt . '_' . $seq . ($extension !== '' ? ('.' . $extension) : '');
            $seq++;
        }

        $destPath = $targetDir . DIRECTORY_SEPARATOR . $destName;
        if (@file_put_contents($destPath, $binary) === false) {
            log_message('error', '[CentralMensagens] Falha ao gravar arquivo de midia: {path}', ['path' => $destPath]);
            return null;
        }

        return [
            'arquivo' => $relativeDir . '/' . $destName,
            'mime_type' => $mime,
            'tipo_conteudo' => $this->tipoConteudoByMime($mime, $destName),
            'arquivo_nome' => $destName,
            'tamanho_bytes' => strlen($binary),
        ];
    }

    private function mediaFolderByMime(string $mime, string $filename = ''): string
    {
        $tipo = $this->tipoConteudoByMime($mime, $filename);
        return match ($tipo) {
            'imagem' => 'foto',
            'video' => 'video',
            'audio' => 'audio',
            'pdf' => 'pdf',
            default => 'arquivo',
        };
    }

    private function tipoConteudoByMime(string $mime, string $filename = ''): string
    {
        $m = strtolower(trim($mime));
        if ($m !== '') {
            if (str_starts_with($m, 'image/')) {
                return 'imagem';
            }
            if (str_starts_with($m, 'video/')) {
                return 'video';
            }
            if (str_starts_with($m, 'audio/')) {
                return 'audio';
            }
            if ($m === 'application/pdf') {
                return 'pdf';
            }
            if (str_starts_with($m, 'application/') || str_starts_with($m, 'text/')) {
                return 'arquivo';
            }
        }

        $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true)) {
            return 'imagem';
        }
        if (in_array($ext, ['mp4', 'webm', 'mov', 'mkv'], true)) {
            return 'video';
        }
        if (in_array($ext, ['mp3', 'ogg', 'wav', 'm4a', 'aac', 'opus'], true)) {
            return 'audio';
        }
        if ($ext === 'pdf') {
            return 'pdf';
        }
        return 'arquivo';
    }

    private function sanitizeFileName(string $value): string
    {
        $clean = preg_replace('/[^\w\-.]+/u', '_', $value) ?? '';
        $clean = trim($clean, '._');
        return $clean !== '' ? $clean : ('arquivo_' . date('Ymd_His'));
    }

    private function extensionByMime(string $mime): string
    {
        return match (strtolower($mime)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/mp4', 'audio/x-m4a' => 'm4a',
            'audio/aac' => 'aac',
            'audio/opus' => 'opus',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            default => 'bin',
        };
    }

    private function notifyMobileInbound(
        ?array $conversa,
        int $mensagemId,
        string $mensagem,
        string $tipoConteudo,
        string $mediaFilename,
        string $phone,
        ?int $clienteId,
        ?int $osId,
        ?int $originUserId = null
    ): void {
        $conversaId = (int) ($conversa['id'] ?? 0);
        if ($conversaId <= 0 || $mensagemId <= 0) {
            return;
        }

        $recipientIds = $this->resolveMobileNotificationRecipients($conversa, $originUserId);
        if (empty($recipientIds)) {
            return;
        }

        $nomeContato = trim((string) ($conversa['nome_contato'] ?? ''));
        if ($nomeContato === '') {
            $nomeContato = $phone;
        }

        $title = 'Nova mensagem de ' . $nomeContato;
        $body = $this->buildMobileInboundPreview($mensagem, $tipoConteudo, $mediaFilename);
        $route = '/conversas/' . $conversaId;

        $payload = [
            'conversa_id' => $conversaId,
            'mensagem_id' => $mensagemId,
            'cliente_id' => $clienteId ?: null,
            'os_id' => $osId ?: null,
            'tipo_conteudo' => $tipoConteudo !== '' ? $tipoConteudo : 'texto',
        ];

        $targets = [
            ['tipo' => 'conversation', 'id' => $conversaId],
        ];
        if ((int) ($clienteId ?? 0) > 0) {
            $targets[] = ['tipo' => 'client', 'id' => (int) $clienteId];
        }
        if ((int) ($osId ?? 0) > 0) {
            $targets[] = ['tipo' => 'order', 'id' => (int) $osId];
        }

        $created = $this->mobileNotificationService->notifyUsers(
            $recipientIds,
            'message.inbound',
            $title,
            $body,
            $payload,
            $route,
            $targets
        );

        if ($created <= 0) {
            log_message(
                'warning',
                '[CentralMensagens] Falha ao criar notificacao mobile inbound. conversa_id=' . $conversaId
            );
        }
    }

    /**
     * @param array<string,mixed>|null $conversa
     * @return array<int,int>
     */
    private function resolveMobileNotificationRecipients(?array $conversa, ?int $originUserId = null): array
    {
        $subscriptionRows = $this->mobilePushSubscriptionModel
            ->select('usuario_id')
            ->where('ativo', 1)
            ->groupBy('usuario_id')
            ->findAll();

        if (empty($subscriptionRows)) {
            return [];
        }

        $usersWithDevice = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['usuario_id'] ?? 0),
            $subscriptionRows
        ), static fn (int $id): bool => $id > 0)));

        if (empty($usersWithDevice)) {
            return [];
        }

        $responsavelId = (int) ($conversa['responsavel_id'] ?? 0);
        if ($responsavelId > 0 && in_array($responsavelId, $usersWithDevice, true)) {
            if ($originUserId !== null && $originUserId > 0 && $originUserId === $responsavelId) {
                return [];
            }
            return [$responsavelId];
        }

        $users = $this->usuarioModel
            ->select('id, perfil, grupo_id, ativo')
            ->whereIn('id', $usersWithDevice)
            ->where('ativo', 1)
            ->findAll();

        $recipients = [];
        foreach ($users as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            if ($originUserId !== null && $originUserId > 0 && $originUserId === $userId) {
                continue;
            }
            if (!$this->mobilePermissionService->userCan($user, 'clientes', 'visualizar')) {
                continue;
            }
            $recipients[] = $userId;
            if (count($recipients) >= 20) {
                break;
            }
        }

        return array_values(array_unique($recipients));
    }

    private function buildMobileInboundPreview(string $mensagem, string $tipoConteudo, string $mediaFilename): string
    {
        $texto = trim($mensagem);
        if ($texto !== '') {
            return $this->truncateText($texto, 120);
        }

        $tipo = strtolower(trim($tipoConteudo));
        $label = match ($tipo) {
            'audio', 'ptt', 'voice', 'voice_note' => '[audio]',
            'video' => '[video]',
            'imagem', 'image', 'foto' => '[imagem]',
            'pdf' => '[pdf]',
            'arquivo', 'documento', 'file' => '[arquivo]',
            default => 'Nova mensagem recebida',
        };

        $file = trim($mediaFilename);
        if ($file !== '' && in_array($label, ['[audio]', '[video]', '[imagem]', '[pdf]', '[arquivo]'], true)) {
            return $this->truncateText($label . ' ' . $file, 120);
        }

        return $label;
    }

    private function truncateText(string $text, int $limit): string
    {
        $value = trim($text);
        if ($value === '' || $limit <= 0) {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value, 'UTF-8') <= $limit) {
                return $value;
            }
            return rtrim(mb_substr($value, 0, $limit - 1, 'UTF-8')) . '...';
        }

        if (strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(substr($value, 0, $limit - 1)) . '...';
    }

    private function registerCrmMensagem(array $payload): ?int
    {
        if (!$this->crmMensagemModel->db->tableExists('crm_mensagens')) {
            return null;
        }

        $data = [
            'cliente_id' => $payload['cliente_id'] ?? null,
            'os_id' => $payload['os_id'] ?? null,
            'conversa_id' => $payload['conversa_id'] ?? null,
            'canal' => 'whatsapp',
            'provider' => $payload['provider'] ?? null,
            'direcao' => (string) ($payload['direcao'] ?? 'outbound'),
            'tipo_conteudo' => (string) ($payload['tipo_conteudo'] ?? 'texto'),
            'conteudo' => $payload['conteudo'] ?? null,
            'arquivo' => $payload['arquivo'] ?? null,
            'status' => (string) ($payload['status'] ?? 'registrada'),
            'payload_json' => is_array($payload['payload_json'] ?? null)
                ? json_encode($payload['payload_json'], JSON_UNESCAPED_UNICODE)
                : ($payload['payload_json'] ?? null),
            'usuario_id' => $payload['usuario_id'] ?? null,
            'data_mensagem' => date('Y-m-d H:i:s'),
        ];

        return $this->crmMensagemModel->insert($data, true) ?: null;
    }
}
