<?php

namespace App\Controllers\Api\V1;

use App\Models\MobileNotificationModel;
use App\Services\MensageriaService;
use App\Services\Mobile\MobileNotificationService;
use Throwable;

class NotificationsController extends BaseApiController
{
    public function index()
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->respondError('Usuario nao autenticado.', 401, 'AUTH_REQUIRED');
        }

        $onlyUnread = (string) $this->request->getGet('only_unread') === '1';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = max(1, min(100, (int) ($this->request->getGet('per_page') ?? 30)));
        $offset = ($page - 1) * $perPage;

        $model = new MobileNotificationModel();
        $builder = $model->where('usuario_id', $userId);
        if ($onlyUnread) {
            $builder->where('lida_em', null);
        }

        $totalBuilder = clone $builder;
        $total = (int) $totalBuilder->countAllResults(false);
        $items = $builder
            ->orderBy('id', 'DESC')
            ->findAll($perPage, $offset);

        return $this->respondSuccess([
            'items' => array_map(static function (array $row): array {
                $payload = null;
                $raw = trim((string) ($row['payload_json'] ?? ''));
                if ($raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $payload = $decoded;
                    }
                }

                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'tipo_evento' => (string) ($row['tipo_evento'] ?? ''),
                    'titulo' => (string) ($row['titulo'] ?? ''),
                    'corpo' => (string) ($row['corpo'] ?? ''),
                    'rota_destino' => $row['rota_destino'] ?? null,
                    'payload' => $payload,
                    'lida_em' => $row['lida_em'] ?? null,
                    'created_at' => $row['created_at'] ?? null,
                ];
            }, $items),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil(max(1, $total) / $perPage),
            ],
            'whatsapp_connection' => $this->resolveWhatsappConnectionStatus(),
        ]);
    }

    public function create()
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->respondError('Usuario nao autenticado.', 401, 'AUTH_REQUIRED');
        }

        $payload = $this->payload();
        $title = trim((string) ($payload['title'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        if ($title === '' || $body === '') {
            return $this->respondError(
                'title e body sao obrigatorios.',
                422,
                'NOTIFICATION_VALIDATION'
            );
        }

        $created = (new MobileNotificationService())->createNotification(
            $userId,
            trim((string) ($payload['event_type'] ?? 'manual.info')) ?: 'manual.info',
            $title,
            $body,
            is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
            isset($payload['route']) ? trim((string) $payload['route']) : null
        );

        if (!$created) {
            return $this->respondError('Falha ao criar notificacao.', 422, 'NOTIFICATION_CREATE_FAILED');
        }

        return $this->respondSuccess([
            'id' => (int) ($created['id'] ?? 0),
        ], 201);
    }

    public function markAsRead($id = null)
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->respondError('Usuario nao autenticado.', 401, 'AUTH_REQUIRED');
        }

        $notificationId = (int) $id;
        if ($notificationId <= 0) {
            return $this->respondError('Notificacao invalida.', 422, 'NOTIFICATION_INVALID_ID');
        }

        $model = new MobileNotificationModel();
        $item = $model->where('id', $notificationId)->where('usuario_id', $userId)->first();
        if (!$item) {
            return $this->respondError('Notificacao nao encontrada.', 404, 'NOTIFICATION_NOT_FOUND');
        }

        $model->update($notificationId, ['lida_em' => date('Y-m-d H:i:s')]);

        return $this->respondSuccess([
            'id' => $notificationId,
            'lida_em' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markAllRead()
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->respondError('Usuario nao autenticado.', 401, 'AUTH_REQUIRED');
        }

        $model = new MobileNotificationModel();
        $updated = $model
            ->where('usuario_id', $userId)
            ->where('lida_em', null)
            ->set(['lida_em' => date('Y-m-d H:i:s')])
            ->update();

        return $this->respondSuccess([
            'updated' => (bool) $updated,
        ]);
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

    /**
     * @return array<string,mixed>
     */
    private function resolveWhatsappConnectionStatus(): array
    {
        $provider = trim((string) get_config('whatsapp_direct_provider', get_config('whatsapp_provider', 'menuia')));
        if ($provider === 'menuia_api') {
            $provider = 'menuia';
        }
        if ($provider === '') {
            $provider = 'menuia';
        }

        $enabled = (string) get_config('whatsapp_enabled', '0') === '1';
        $status = [
            'enabled' => $enabled,
            'provider' => $provider,
            'provider_label' => $this->resolveProviderLabel($provider),
            'ok' => false,
            'status_code' => null,
            'failure_type' => null,
            'message' => $enabled
                ? 'Validando conexao ativa com o provedor WhatsApp...'
                : 'Integracao WhatsApp desativada nas configuracoes do ERP.',
            'checked_at' => date('Y-m-d H:i:s'),
            'last_check_status' => trim((string) get_config('whatsapp_last_check_status', '')) ?: null,
            'last_check_message' => trim((string) get_config('whatsapp_last_check_message', '')) ?: null,
            'last_check_at' => trim((string) get_config('whatsapp_last_check_at', '')) ?: null,
        ];

        if (!$enabled) {
            return $status;
        }

        try {
            $result = (new MensageriaService())->testDirectConnection(null, $provider, [], true);
            $status['ok'] = !empty($result['ok']);
            $status['status_code'] = isset($result['status_code']) ? (int) $result['status_code'] : null;
            $status['failure_type'] = isset($result['failure_type']) ? trim((string) $result['failure_type']) : null;

            $message = trim((string) ($result['message'] ?? ''));
            if ($message === '') {
                $message = !empty($result['ok'])
                    ? 'Conexao WhatsApp validada com sucesso.'
                    : 'Falha ao validar conexao com a API WhatsApp.';
            }
            $status['message'] = $message;
        } catch (Throwable $e) {
            $status['ok'] = false;
            $status['failure_type'] = 'unexpected_error';
            $status['message'] = 'Falha ao validar conexao WhatsApp: ' . $e->getMessage();
        }

        return $status;
    }

    private function resolveProviderLabel(string $provider): string
    {
        return match ($provider) {
            'api_whats_local' => 'Gateway local (Windows)',
            'api_whats_linux' => 'Gateway Linux (VPS)',
            'menuia' => 'Menuia',
            'webhook' => 'Webhook',
            default => $provider,
        };
    }
}
