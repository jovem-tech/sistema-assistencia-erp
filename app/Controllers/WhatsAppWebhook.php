<?php

namespace App\Controllers;

use App\Models\WhatsappInboundModel;
use App\Services\CentralMensagensService;

class WhatsAppWebhook extends BaseController
{
    /**
     * @param array<string,mixed> $payload
     * @param list<string> $paths
     */
    private function payloadPathString(array $payload, array $paths, string $default = ''): string
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

            if ($resolved && $cursor !== null && $cursor !== '' && is_scalar($cursor)) {
                return trim((string) $cursor);
            }
        }

        return $default;
    }

    public function receive()
    {
        $tokenExpected = trim((string) get_config('whatsapp_webhook_token', ''));
        $tokenReceived = trim((string) (
            $this->request->getHeaderLine('X-Webhook-Token')
            ?: $this->request->getGet('token')
            ?: $this->request->getPost('token')
        ));

        if ($tokenExpected !== '' && !hash_equals($tokenExpected, $tokenReceived)) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON(['ok' => false, 'message' => 'Token de webhook invalido.']);
        }

        $payload = $this->request->getJSON(true);
        if (!is_array($payload) || empty($payload)) {
            $payload = $this->request->getPost();
        }
        if (!is_array($payload) || empty($payload)) {
            $raw = (string) $this->request->getBody();
            $decoded = json_decode($raw, true);
            $payload = is_array($decoded) ? $decoded : ['raw' => $raw];
        }

        $selfCheckHeader = trim((string) $this->request->getHeaderLine('X-Webhook-Self-Check'));
        $selfCheckPayload = filter_var($payload['self_check'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $isSelfCheck = $selfCheckHeader === '1' || $selfCheckPayload === true;
        if ($isSelfCheck) {
            return $this->response->setJSON([
                'ok' => true,
                'self_check' => true,
                'message' => 'Webhook inbound validado com sucesso.',
                'received_at' => date('c'),
                'provider' => trim((string) get_config('whatsapp_direct_provider', get_config('whatsapp_provider', 'menuia'))),
            ]);
        }

        $remetente = $this->payloadPathString($payload, [
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
        ]);
        $conteudo = $this->payloadPathString($payload, [
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
        $provedor = $this->payloadPathString($payload, [
            'provider',
            'source',
            'gateway',
            'data.provider',
            'data.source',
            'data.gateway',
        ], trim((string) get_config('whatsapp_direct_provider', get_config('whatsapp_provider', 'menuia'))));

        $model = new WhatsappInboundModel();
        if (!$model->db->tableExists('whatsapp_inbound')) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON(['ok' => false, 'message' => 'Tabela whatsapp_inbound nao encontrada.']);
        }

        $model->insert([
            'provedor' => $provedor ?: 'menuia',
            'remetente' => $remetente !== '' ? $remetente : null,
            'conteudo' => $conteudo !== '' ? $conteudo : null,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'processado' => 0,
        ]);
        $inboundId = (int) $model->getInsertID();

        try {
            (new CentralMensagensService())->registerInboundFromPayload($payload, $provedor ?: 'menuia');
            if ($inboundId > 0 && $model->db->tableExists('whatsapp_inbound')) {
                $model->update($inboundId, ['processado' => 1]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Falha ao integrar webhook na Central de Mensagens: ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Webhook recebido.',
        ]);
    }
}
