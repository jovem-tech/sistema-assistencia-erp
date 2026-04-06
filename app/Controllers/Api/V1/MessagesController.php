<?php

namespace App\Controllers\Api\V1;

use App\Models\ConversaWhatsappModel;
use App\Models\MensagemWhatsappModel;
use App\Services\Mobile\MobileNotificationService;
use App\Services\WhatsAppService;
use Throwable;

class MessagesController extends BaseApiController
{
    public function index()
    {
        if ($permissionError = $this->ensurePermission('clientes', 'visualizar')) {
            return $permissionError;
        }

        $conversaId = (int) ($this->request->getGet('conversa_id') ?? 0);
        $afterId = max(0, (int) ($this->request->getGet('after_id') ?? 0));
        $limit = max(1, min(400, (int) ($this->request->getGet('limit') ?? 120)));

        if ($conversaId <= 0) {
            return $this->respondError(
                'Parametro conversa_id e obrigatorio.',
                422,
                'MESSAGE_LIST_VALIDATION'
            );
        }

        $mensagemModel = new MensagemWhatsappModel();
        $items = $afterId > 0
            ? $mensagemModel->afterId($conversaId, $afterId, $limit)
            : $mensagemModel->byConversa($conversaId, $limit);

        return $this->respondSuccess([
            'items' => $items,
            'count' => count($items),
            'conversa_id' => $conversaId,
        ]);
    }

    public function create()
    {
        if ($permissionError = $this->ensurePermission('clientes', 'visualizar')) {
            return $permissionError;
        }

        $payload = $this->payload();
        $conversaId = (int) ($payload['conversa_id'] ?? 0);
        $mensagem = trim((string) ($payload['mensagem'] ?? ''));

        if ($conversaId <= 0 || $mensagem === '') {
            return $this->respondError(
                'conversa_id e mensagem sao obrigatorios.',
                422,
                'MESSAGE_CREATE_VALIDATION'
            );
        }

        $conversa = (new ConversaWhatsappModel())->find($conversaId);
        if (!$conversa) {
            return $this->respondError('Conversa nao encontrada.', 404, 'CONVERSATION_NOT_FOUND');
        }

        $telefone = trim((string) ($conversa['telefone'] ?? ''));
        if ($telefone === '') {
            return $this->respondError('Conversa sem telefone valido.', 422, 'MESSAGE_PHONE_REQUIRED');
        }

        try {
            $result = (new WhatsAppService())->sendRaw(
                (int) ($conversa['os_id_principal'] ?? 0),
                (int) ($conversa['cliente_id'] ?? 0),
                $telefone,
                $mensagem,
                (string) ($payload['tipo_mensagem'] ?? 'manual'),
                null,
                $this->currentUserId(),
                [
                    'conversa_id' => $conversaId,
                ]
            );

            $ok = !empty($result['ok']);
            if (!$ok) {
                return $this->respondError(
                    (string) ($result['message'] ?? 'Falha ao enviar mensagem.'),
                    502,
                    'MESSAGE_SEND_FAILED',
                    $result
                );
            }

            $mensagemId = (int) ($result['mensagem_whatsapp_id'] ?? 0);
            $notificationService = new MobileNotificationService();
            $notificationService->enqueueOutbox(
                'message.sent',
                'conversation',
                $conversaId,
                [
                    'conversa_id' => $conversaId,
                    'mensagem_id' => $mensagemId > 0 ? $mensagemId : null,
                    'telefone' => $telefone,
                ]
            );

            return $this->respondSuccess([
                'ok' => true,
                'conversa_id' => $conversaId,
                'mensagem_id' => $mensagemId > 0 ? $mensagemId : null,
                'provider_message_id' => $result['message_id'] ?? null,
            ], 201);
        } catch (Throwable $e) {
            log_message('error', '[API V1][MESSAGES CREATE] ' . $e->getMessage());
            return $this->respondError(
                'Falha inesperada ao enviar mensagem.',
                500,
                'MESSAGE_SEND_UNEXPECTED'
            );
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json) && !empty($json)) {
            return $json;
        }

        $raw = $this->request->getRawInput();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }

        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }
}

