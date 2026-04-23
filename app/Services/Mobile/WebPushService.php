<?php

namespace App\Services\Mobile;

use App\Models\MobilePushSubscriptionModel;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class WebPushService
{
    private const DEFAULT_TTL = 120;
    private const DEFAULT_URGENCY = 'high';

    private MobilePushSubscriptionModel $subscriptionModel;

    /**
     * @var array<string,string>|null
     */
    private ?array $vapidConfig = null;

    public function __construct()
    {
        $this->subscriptionModel = new MobilePushSubscriptionModel();
    }

    /**
     * @param array<int,array<string,mixed>> $subscriptions
     * @param array<string,mixed> $payload
     * @return array{sent:int,failed:int,disabled:int,reason:?string}
     */
    public function sendToSubscriptions(array $subscriptions, array $payload): array
    {
        if (empty($subscriptions)) {
            return [
                'sent' => 0,
                'failed' => 0,
                'disabled' => 0,
                'reason' => 'NO_SUBSCRIPTIONS',
            ];
        }

        $vapid = $this->resolveVapidConfig();
        if ($vapid === null) {
            log_message(
                'warning',
                '[Mobile WebPush] VAPID nao configurado. Defina MOBILE_VAPID_PUBLIC_KEY e MOBILE_VAPID_PRIVATE_KEY.'
            );
            return [
                'sent' => 0,
                'failed' => count($subscriptions),
                'disabled' => 0,
                'reason' => 'VAPID_NOT_CONFIGURED',
            ];
        }

        $notificationPayload = $this->normalizeNotificationPayload($payload);
        $encodedPayload = json_encode($notificationPayload, JSON_UNESCAPED_UNICODE);
        if (!is_string($encodedPayload) || $encodedPayload === '') {
            return [
                'sent' => 0,
                'failed' => count($subscriptions),
                'disabled' => 0,
                'reason' => 'PAYLOAD_ENCODE_FAILED',
            ];
        }

        $webPush = new WebPush([
            'VAPID' => $vapid,
        ]);

        $queuedByEndpointHash = [];

        foreach ($subscriptions as $row) {
            $endpoint = trim((string) ($row['endpoint'] ?? ''));
            if ($endpoint === '') {
                continue;
            }

            $subscriptionData = [
                'endpoint' => $endpoint,
            ];

            $publicKey = trim((string) ($row['chave_p256dh'] ?? ''));
            $authToken = trim((string) ($row['chave_auth'] ?? ''));
            if ($publicKey !== '' && $authToken !== '') {
                $subscriptionData['keys'] = [
                    'p256dh' => $publicKey,
                    'auth' => $authToken,
                ];
            }

            try {
                $subscription = Subscription::create($subscriptionData);
                $webPush->queueNotification($subscription, $encodedPayload, [
                    'TTL' => self::DEFAULT_TTL,
                    'urgency' => self::DEFAULT_URGENCY,
                ]);
                $queuedByEndpointHash[hash('sha256', $endpoint)] = $row;
            } catch (Throwable $e) {
                log_message('error', '[Mobile WebPush] Falha ao enfileirar subscription: ' . $e->getMessage());
            }
        }

        if (empty($queuedByEndpointHash)) {
            return [
                'sent' => 0,
                'failed' => count($subscriptions),
                'disabled' => 0,
                'reason' => 'QUEUE_EMPTY',
            ];
        }

        $sent = 0;
        $failed = 0;
        $disabled = 0;

        foreach ($webPush->flush() as $report) {
            $endpoint = (string) $report->getRequest()->getUri();
            $source = $queuedByEndpointHash[hash('sha256', $endpoint)] ?? null;

            if ($report->isSuccess()) {
                $sent++;
                if ($source && !empty($source['id'])) {
                    $this->subscriptionModel->update((int) $source['id'], [
                        'ultimo_ping_em' => date('Y-m-d H:i:s'),
                        'ativo' => 1,
                    ]);
                }
                continue;
            }

            $failed++;
            $reason = trim((string) $report->getReason());
            log_message(
                'warning',
                '[Mobile WebPush] Falha no envio push. endpoint=' . $endpoint . ' motivo=' . $reason
            );

            if ($source && !empty($source['id']) && $report->isSubscriptionExpired()) {
                $this->subscriptionModel->update((int) $source['id'], [
                    'ativo' => 0,
                    'ultimo_ping_em' => date('Y-m-d H:i:s'),
                ]);
                $disabled++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'disabled' => $disabled,
            'reason' => null,
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function normalizeNotificationPayload(array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        $route = trim((string) ($payload['route'] ?? '/conversas'));
        $customPayload = $payload['payload'] ?? null;

        return [
            'title' => $title !== '' ? $title : 'Assistencia',
            'body' => $body !== '' ? $body : 'Nova atualizacao no atendimento.',
            'route' => $route !== '' ? $route : '/conversas',
            'payload' => is_array($customPayload) ? $customPayload : null,
        ];
    }

    /**
     * @return array<string,string>|null
     */
    private function resolveVapidConfig(): ?array
    {
        if ($this->vapidConfig !== null) {
            return $this->vapidConfig;
        }

        $publicKey = $this->resolveConfigValue('MOBILE_VAPID_PUBLIC_KEY', 'mobile_vapid_public_key');
        $privateKey = $this->resolveConfigValue('MOBILE_VAPID_PRIVATE_KEY', 'mobile_vapid_private_key');
        $subject = $this->resolveConfigValue('MOBILE_VAPID_SUBJECT', 'mobile_vapid_subject');

        if ($publicKey === '' || $privateKey === '') {
            return null;
        }

        if ($subject === '') {
            $host = trim((string) parse_url(base_url('/'), PHP_URL_HOST));
            $subject = $host !== '' ? ('mailto:suporte@' . $host) : 'mailto:suporte@localhost';
        }

        $this->vapidConfig = [
            'subject' => $subject,
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ];

        return $this->vapidConfig;
    }

    private function resolveConfigValue(string $envKey, string $dbKey): string
    {
        $value = trim((string) (env($envKey) ?? ''));
        if ($value !== '') {
            return $value;
        }

        if (function_exists('get_config')) {
            $configValue = get_config($dbKey, '');
            $value = trim((string) $configValue);
        }

        return $value;
    }
}
