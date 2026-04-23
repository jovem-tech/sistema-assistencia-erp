<?php

namespace App\Services\Mobile;

use App\Models\MobileEventOutboxModel;
use App\Models\MobileNotificationModel;
use App\Models\MobileNotificationTargetModel;
use App\Models\MobilePushSubscriptionModel;

class MobileNotificationService
{
    private MobileNotificationModel $notificationModel;
    private MobileNotificationTargetModel $targetModel;
    private MobileEventOutboxModel $outboxModel;
    private MobilePushSubscriptionModel $subscriptionModel;
    private WebPushService $webPushService;

    public function __construct()
    {
        $this->notificationModel = new MobileNotificationModel();
        $this->targetModel = new MobileNotificationTargetModel();
        $this->outboxModel = new MobileEventOutboxModel();
        $this->subscriptionModel = new MobilePushSubscriptionModel();
        $this->webPushService = new WebPushService();
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<int,array{tipo:string,id:int|null}> $targets
     * @return array<string,mixed>|null
     */
    public function createNotification(
        int $usuarioId,
        string $eventType,
        string $title,
        string $body,
        array $payload = [],
        ?string $route = null,
        array $targets = []
    ): ?array {
        if ($usuarioId <= 0) {
            return null;
        }

        $id = (int) $this->notificationModel->insert([
            'usuario_id' => $usuarioId,
            'tipo_evento' => trim($eventType) !== '' ? trim($eventType) : 'system.info',
            'titulo' => trim($title) !== '' ? trim($title) : 'Atualizacao',
            'corpo' => trim($body) !== '' ? trim($body) : 'Voce tem uma nova atualizacao.',
            'rota_destino' => $route !== null ? trim($route) : null,
            'payload_json' => !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
        ], true);

        if ($id <= 0) {
            return null;
        }

        foreach ($targets as $target) {
            $tipo = trim((string) ($target['tipo'] ?? ''));
            $targetId = isset($target['id']) ? (int) $target['id'] : null;
            if ($tipo === '') {
                continue;
            }

            $this->targetModel->insert([
                'notification_id' => $id,
                'tipo_alvo' => $tipo,
                'alvo_id' => $targetId > 0 ? $targetId : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $created = $this->notificationModel->find($id);
        if (!is_array($created)) {
            return null;
        }

        $this->dispatchPushForNotification($created);

        return $created;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function enqueueOutbox(
        string $eventType,
        string $aggregateType,
        ?int $aggregateId,
        array $payload,
        ?string $eventKey = null
    ): void {
        $eventType = trim($eventType);
        $aggregateType = trim($aggregateType);
        if ($eventType === '' || $aggregateType === '') {
            return;
        }

        $key = trim((string) $eventKey);
        if ($key === '') {
            $hashBase = $eventType . '|' . $aggregateType . '|' . (string) ($aggregateId ?? 0) . '|' . json_encode($payload, JSON_UNESCAPED_UNICODE);
            $key = hash('sha256', $hashBase);
        }

        $exists = $this->outboxModel->where('event_key', $key)->first();
        if ($exists) {
            return;
        }

        $this->outboxModel->insert([
            'event_key' => $key,
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => 'pending',
            'tentativas' => 0,
            'disponivel_em' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<int,int> $userIds
     * @param array<string,mixed> $payload
     * @param array<int,array{tipo:string,id:int|null}> $targets
     */
    public function notifyUsers(
        array $userIds,
        string $eventType,
        string $title,
        string $body,
        array $payload = [],
        ?string $route = null,
        array $targets = []
    ): int {
        $created = 0;
        $uniqueUserIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));

        foreach ($uniqueUserIds as $userId) {
            $result = $this->createNotification(
                $userId,
                $eventType,
                $title,
                $body,
                $payload,
                $route,
                $targets
            );
            if (is_array($result) && (int) ($result['id'] ?? 0) > 0) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * @param array<string,mixed> $notification
     */
    private function dispatchPushForNotification(array $notification): void
    {
        $notificationId = (int) ($notification['id'] ?? 0);
        $userId = (int) ($notification['usuario_id'] ?? 0);
        if ($notificationId <= 0 || $userId <= 0) {
            return;
        }

        $subscriptions = $this->subscriptionModel
            ->where('usuario_id', $userId)
            ->where('ativo', 1)
            ->findAll(30);

        if (empty($subscriptions)) {
            return;
        }

        $payload = [
            'title' => (string) ($notification['titulo'] ?? ''),
            'body' => (string) ($notification['corpo'] ?? ''),
            'route' => trim((string) ($notification['rota_destino'] ?? '/conversas')) ?: '/conversas',
            'payload' => $this->decodePayloadJson((string) ($notification['payload_json'] ?? '')),
        ];

        $result = $this->webPushService->sendToSubscriptions($subscriptions, $payload);
        if (($result['sent'] ?? 0) > 0) {
            $this->notificationModel->update($notificationId, [
                'enviada_push_em' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private function decodePayloadJson(string $json): ?array
    {
        $raw = trim($json);
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
