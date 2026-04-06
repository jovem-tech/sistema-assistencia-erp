<?php

namespace App\Controllers\Api\V1;

use App\Models\MensagemWhatsappModel;
use App\Models\MobileNotificationModel;

class RealtimeController extends BaseApiController
{
    public function stream()
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->respondError('Usuario nao autenticado.', 401, 'AUTH_REQUIRED');
        }

        if ($permissionError = $this->ensurePermission('clientes', 'visualizar')) {
            return $permissionError;
        }

        $afterMessageId = max(0, (int) ($this->request->getGet('after_message_id') ?? 0));
        $afterNotificationId = max(0, (int) ($this->request->getGet('after_notification_id') ?? 0));
        $conversaId = max(0, (int) ($this->request->getGet('conversa_id') ?? 0));

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        echo ": connected\n\n";
        @ob_flush();
        @flush();

        $deadline = time() + 25;
        $notificationModel = new MobileNotificationModel();
        $messageModel = new MensagemWhatsappModel();

        while (time() < $deadline) {
            if (connection_aborted()) {
                break;
            }

            $notificationBuilder = $notificationModel
                ->where('usuario_id', $userId)
                ->where('id >', $afterNotificationId);

            $notifications = $notificationBuilder
                ->orderBy('id', 'ASC')
                ->findAll(50);

            $messages = [];
            if ($conversaId > 0) {
                $messages = $messageModel->afterId($conversaId, $afterMessageId, 80);
            }

            if (!empty($notifications) || !empty($messages)) {
                if (!empty($notifications)) {
                    $last = end($notifications);
                    $afterNotificationId = (int) ($last['id'] ?? $afterNotificationId);
                }

                if (!empty($messages)) {
                    $lastMessage = end($messages);
                    $afterMessageId = (int) ($lastMessage['id'] ?? $afterMessageId);
                }

                $payload = [
                    'notifications' => array_map(static function (array $row): array {
                        return [
                            'id' => (int) ($row['id'] ?? 0),
                            'tipo_evento' => (string) ($row['tipo_evento'] ?? ''),
                            'titulo' => (string) ($row['titulo'] ?? ''),
                            'corpo' => (string) ($row['corpo'] ?? ''),
                            'rota_destino' => $row['rota_destino'] ?? null,
                            'created_at' => $row['created_at'] ?? null,
                        ];
                    }, $notifications),
                    'messages' => $messages,
                    'cursor' => [
                        'after_message_id' => $afterMessageId,
                        'after_notification_id' => $afterNotificationId,
                    ],
                ];

                echo "event: delta\n";
                echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush();
                @flush();
                continue;
            }

            echo "event: ping\n";
            echo 'data: {"ts":"' . date('c') . "\"}\n\n";
            @ob_flush();
            @flush();
            sleep(2);
        }

        echo "event: end\n";
        echo 'data: {"reason":"timeout"}' . "\n\n";
        @ob_flush();
        @flush();
        exit;
    }
}

