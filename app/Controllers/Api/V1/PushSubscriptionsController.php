<?php

namespace App\Controllers\Api\V1;

use App\Models\MobilePushSubscriptionModel;

class PushSubscriptionsController extends BaseApiController
{
    public function index()
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->respondError('Usuario nao autenticado.', 401, 'AUTH_REQUIRED');
        }

        $items = (new MobilePushSubscriptionModel())
            ->select('id, endpoint, device_label, ativo, ultimo_ping_em, created_at')
            ->where('usuario_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll(50);

        return $this->respondSuccess([
            'items' => $items,
            'count' => count($items),
        ]);
    }

    public function create()
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->respondError('Usuario nao autenticado.', 401, 'AUTH_REQUIRED');
        }

        $payload = $this->payload();
        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        $keys = is_array($payload['keys'] ?? null) ? $payload['keys'] : [];
        $p256dh = trim((string) ($keys['p256dh'] ?? ($payload['p256dh'] ?? '')));
        $auth = trim((string) ($keys['auth'] ?? ($payload['auth'] ?? '')));
        $deviceLabel = trim((string) ($payload['device_label'] ?? ''));

        if ($endpoint === '') {
            return $this->respondError(
                'endpoint da subscription e obrigatorio.',
                422,
                'PUSH_SUBSCRIPTION_VALIDATION'
            );
        }

        $endpointHash = hash('sha256', $endpoint);
        $model = new MobilePushSubscriptionModel();
        $existing = $model->where('endpoint_hash', $endpointHash)->first();

        $data = [
            'usuario_id' => $userId,
            'endpoint_hash' => $endpointHash,
            'endpoint' => $endpoint,
            'chave_p256dh' => $p256dh !== '' ? $p256dh : null,
            'chave_auth' => $auth !== '' ? $auth : null,
            'user_agent' => trim((string) ($this->request->getHeaderLine('User-Agent') ?? '')) ?: null,
            'device_label' => $deviceLabel !== '' ? $deviceLabel : null,
            'ativo' => 1,
            'ultimo_ping_em' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $model->update((int) $existing['id'], $data);
            $id = (int) $existing['id'];
        } else {
            $id = (int) $model->insert($data, true);
        }

        return $this->respondSuccess([
            'id' => $id,
            'endpoint_hash' => $endpointHash,
        ], 201);
    }

    public function delete($id = null)
    {
        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return $this->respondError('Usuario nao autenticado.', 401, 'AUTH_REQUIRED');
        }

        $subscriptionId = (int) $id;
        if ($subscriptionId <= 0) {
            return $this->respondError('Subscription invalida.', 422, 'PUSH_SUBSCRIPTION_INVALID_ID');
        }

        $model = new MobilePushSubscriptionModel();
        $item = $model->where('id', $subscriptionId)->where('usuario_id', $userId)->first();
        if (!$item) {
            return $this->respondError('Subscription nao encontrada.', 404, 'PUSH_SUBSCRIPTION_NOT_FOUND');
        }

        $model->delete($subscriptionId);

        return $this->respondSuccess([
            'deleted' => true,
            'id' => $subscriptionId,
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
}

