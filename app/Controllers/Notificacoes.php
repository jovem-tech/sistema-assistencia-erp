<?php

namespace App\Controllers;

use App\Models\MobileNotificationModel;

class Notificacoes extends BaseController
{
    private MobileNotificationModel $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new MobileNotificationModel();
    }

    public function navbarFeed()
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'ok' => false,
                    'message' => 'Usuario nao autenticado.',
                ]);
        }

        $limit = max(1, min(12, (int) ($this->request->getGet('limit') ?? 8)));
        $rows = $this->notificationModel
            ->where('usuario_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll($limit);

        $unreadCount = (int) $this->notificationModel
            ->where('usuario_id', $userId)
            ->where('lida_em', null)
            ->countAllResults();

        $lastNotificationId = 0;
        foreach ($rows as $row) {
            $lastNotificationId = max($lastNotificationId, (int) ($row['id'] ?? 0));
        }

        return $this->response->setJSON([
            'ok' => true,
            'items' => array_map(fn (array $row): array => $this->mapNotificationRow($row), $rows),
            'unread_count' => $unreadCount,
            'last_notification_id' => $lastNotificationId,
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function stream()
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setBody('Usuario nao autenticado.');
        }

        $afterId = max(0, (int) ($this->request->getGet('after_id') ?? 0));

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream; charset=UTF-8');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        echo ": connected\n\n";
        @ob_flush();
        @flush();

        $deadline = time() + 25;
        while (time() < $deadline) {
            if (connection_aborted()) {
                break;
            }

            $rows = $this->notificationModel
                ->where('usuario_id', $userId)
                ->where('id >', $afterId)
                ->orderBy('id', 'ASC')
                ->findAll(40);

            if (!empty($rows)) {
                $mapped = array_map(fn (array $row): array => $this->mapNotificationRow($row), $rows);
                $lastRow = end($rows);
                $afterId = (int) ($lastRow['id'] ?? $afterId);
                $unreadCount = (int) $this->notificationModel
                    ->where('usuario_id', $userId)
                    ->where('lida_em', null)
                    ->countAllResults();

                echo "event: delta\n";
                echo 'data: ' . json_encode([
                    'notifications' => $mapped,
                    'cursor' => [
                        'after_id' => $afterId,
                    ],
                    'unread_count' => $unreadCount,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                @ob_flush();
                @flush();
                continue;
            }

            echo "event: ping\n";
            echo 'data: ' . json_encode([
                'ts' => date('c'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
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

    public function markAsRead($id = null)
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'ok' => false,
                    'message' => 'Usuario nao autenticado.',
                ]);
        }

        $notificationId = (int) $id;
        if ($notificationId <= 0) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'ok' => false,
                    'message' => 'Notificacao invalida.',
                ]);
        }

        $row = $this->notificationModel
            ->where('id', $notificationId)
            ->where('usuario_id', $userId)
            ->first();

        if (!$row) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'ok' => false,
                    'message' => 'Notificacao nao encontrada.',
                ]);
        }

        $timestamp = date('Y-m-d H:i:s');
        $this->notificationModel->update($notificationId, [
            'lida_em' => $timestamp,
        ]);

        $unreadCount = (int) $this->notificationModel
            ->where('usuario_id', $userId)
            ->where('lida_em', null)
            ->countAllResults();

        return $this->response->setJSON([
            'ok' => true,
            'id' => $notificationId,
            'lida_em' => $timestamp,
            'unread_count' => $unreadCount,
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function markAllRead()
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'ok' => false,
                    'message' => 'Usuario nao autenticado.',
                ]);
        }

        $timestamp = date('Y-m-d H:i:s');
        $this->notificationModel
            ->where('usuario_id', $userId)
            ->where('lida_em', null)
            ->set([
                'lida_em' => $timestamp,
            ])
            ->update();

        return $this->response->setJSON([
            'ok' => true,
            'updated_at' => $timestamp,
            'unread_count' => 0,
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function currentUserId(): int
    {
        return (int) (session()->get('user_id') ?? 0);
    }

    /**
     * @return array<string,mixed>
     */
    private function mapNotificationRow(array $row): array
    {
        $payload = null;
        $rawPayload = trim((string) ($row['payload_json'] ?? ''));
        if ($rawPayload !== '') {
            $decoded = json_decode($rawPayload, true);
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
    }
}
