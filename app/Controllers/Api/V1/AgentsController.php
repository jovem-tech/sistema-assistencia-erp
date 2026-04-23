<?php

namespace App\Controllers\Api\V1;

use App\Models\MobileApiTokenModel;
use App\Models\MonitorAgentModel;
use App\Models\MonitorAgentSnapshotModel;
use App\Models\OsModel;
use App\Models\UsuarioModel;
use App\Services\AgentMonitor\AgentMonitorSchemaService;
use App\Services\Mobile\ApiTokenService;
use Throwable;

class AgentsController extends BaseApiController
{
    private MonitorAgentModel $agentModel;
    private MonitorAgentSnapshotModel $snapshotModel;
    private MobileApiTokenModel $tokenModel;

    public function __construct()
    {
        $this->agentModel = new MonitorAgentModel();
        $this->snapshotModel = new MonitorAgentSnapshotModel();
        $this->tokenModel = new MobileApiTokenModel();
    }

    public function bootstrapFromWarranty()
    {
        try {
            $this->ensureInfrastructure();
            $payload = $this->payload();

            $installationId = trim((string) $this->payloadValue($payload, ['installationId']));
            $warrantyOsNumber = trim((string) $this->payloadValue($payload, ['warrantyOsNumber']));
            $warrantyPublicUrl = trim((string) $this->payloadValue($payload, ['warrantyPublicUrl']));
            $erpLoginEmail = strtolower(trim((string) $this->payloadValue($payload, ['erpLoginEmail', 'loginEmail', 'email'])));

            if ($installationId === '') {
                return $this->respondError(
                    'installationId e obrigatorio para provisionar o agente.',
                    422,
                    'AGENT_BOOTSTRAP_VALIDATION'
                );
            }

            if ($erpLoginEmail === '') {
                return $this->respondError(
                    'Email do ERP e obrigatorio para provisionar o agente.',
                    422,
                    'AGENT_BOOTSTRAP_VALIDATION'
                );
            }

            [$numeroOs, $publicUrlError, $publicUrlStatus] = $this->resolveWarrantyNumber($warrantyOsNumber, $warrantyPublicUrl);
            if ($publicUrlError !== null) {
                return $this->respondError(
                    $publicUrlError,
                    $publicUrlStatus,
                    $publicUrlStatus === 403 ? 'AGENT_WARRANTY_PUBLIC_URL_INVALID' : 'AGENT_BOOTSTRAP_VALIDATION'
                );
            }

            if ($numeroOs === '') {
                return $this->respondError(
                    'Informe a O.S. da garantia ou a URL publica do selo.',
                    422,
                    'AGENT_BOOTSTRAP_VALIDATION'
                );
            }

            $order = (new OsModel())->getCompleteByNumeroOs($numeroOs);
            if (!$order) {
                return $this->respondError(
                    'OS nao encontrada para vincular o agente.',
                    404,
                    'AGENT_ORDER_NOT_FOUND'
                );
            }

            $usuario = (new UsuarioModel())
                ->where('email', $erpLoginEmail)
                ->where('ativo', 1)
                ->first();

            if (!$usuario) {
                return $this->respondError(
                    'Nao encontrei um usuario ativo com o email informado para vincular o agente.',
                    404,
                    'AGENT_USER_NOT_FOUND'
                );
            }

            $existing = $this->agentModel
                ->where('installation_id', $installationId)
                ->first();

            if ($existing && trim((string) ($existing['api_token_hash'] ?? '')) !== '') {
                $this->revokeTokenHash((string) $existing['api_token_hash']);
            }

            $tokenName = $this->buildTokenName($installationId, $numeroOs);
            $token = (new ApiTokenService())->issueToken((int) $usuario['id'], $tokenName, $this->tokenTtlHours());
            $tokenHash = hash('sha256', $token['access_token']);
            $now = date('Y-m-d H:i:s');

            $agentUuid = trim((string) ($existing['agent_uuid'] ?? ''));
            if ($agentUuid === '') {
                $agentUuid = $this->generateAgentUuid();
            }

            $agentData = [
                'agent_uuid' => $agentUuid,
                'installation_id' => $installationId,
                'usuario_id' => (int) $usuario['id'],
                'cliente_id' => (int) ($order['cliente_id'] ?? 0),
                'equipamento_id' => (int) ($order['equipamento_id'] ?? 0),
                'os_id' => (int) ($order['id'] ?? 0),
                'numero_os' => $numeroOs,
                'label' => $this->buildAgentLabel($order),
                'api_token_hash' => $tokenHash,
                'api_token_name' => $tokenName,
                'api_token_expira_em' => (string) ($token['expires_at'] ?? null),
                'hostname' => $this->limitText((string) $this->payloadValue($payload, ['hostname']), 120),
                'serial_number' => $this->limitText((string) $this->payloadValue($payload, ['serialNumber']), 120),
                'manufacturer' => $this->limitText((string) $this->payloadValue($payload, ['manufacturer']), 160),
                'model' => $this->limitText((string) $this->payloadValue($payload, ['model']), 160),
                'motherboard' => $this->limitText((string) $this->payloadValue($payload, ['motherboard']), 180),
                'bios_version' => $this->limitText((string) $this->payloadValue($payload, ['biosVersion']), 120),
                'cpu' => $this->limitText((string) $this->payloadValue($payload, ['cpu']), 255),
                'ram_gb' => $this->normalizeDecimal($this->payloadValue($payload, ['ramGb'])),
                'windows_caption' => $this->limitText((string) $this->payloadValue($payload, ['windowsCaption']), 255),
                'windows_version' => $this->limitText((string) $this->payloadValue($payload, ['windowsVersion']), 80),
                'windows_build' => $this->limitText((string) $this->payloadValue($payload, ['windowsBuild']), 80),
                'ultimo_bootstrap_em' => $now,
                'ultimo_snapshot_em' => $now,
                'ativo' => 1,
            ];

            if ($existing) {
                $this->agentModel->update((int) $existing['id'], $agentData);
            } else {
                $this->agentModel->insert($agentData);
            }

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'AgentId' => $agentUuid,
                    'AgentLabel' => $agentData['label'],
                    'ApiToken' => $token['access_token'],
                    'CheckInEndpoint' => '/api/v1/agents/check-in',
                    'CustomerId' => (int) ($order['cliente_id'] ?? 0),
                    'EquipmentId' => (int) ($order['equipamento_id'] ?? 0),
                    'InventoryIntervalMinutes' => $this->inventoryIntervalMinutes(),
                    'RetryIntervalMinutes' => $this->retryIntervalMinutes(),
                ]);
        } catch (Throwable $e) {
            log_message('error', '[API V1][AGENTS BOOTSTRAP] ' . $e->getMessage());
            return $this->respondError(
                'Falha inesperada ao provisionar o agente.',
                500,
                'AGENT_BOOTSTRAP_UNEXPECTED'
            );
        }
    }

    public function checkIn()
    {
        try {
            $this->ensureInfrastructure();
            $payload = $this->payload();
            $agentUuid = trim((string) $this->payloadValue($payload, ['agentId']));

            if ($agentUuid === '') {
                return $this->respondError(
                    'agentId e obrigatorio para o check-in do agente.',
                    422,
                    'AGENT_CHECKIN_VALIDATION'
                );
            }

            $agent = $this->agentModel
                ->where('agent_uuid', $agentUuid)
                ->where('ativo', 1)
                ->first();

            if (!$agent) {
                return $this->respondError(
                    'Agente nao encontrado para este check-in.',
                    404,
                    'AGENT_NOT_FOUND'
                );
            }

            $plainToken = $this->currentTokenRaw();
            $tokenHash = $plainToken !== '' ? hash('sha256', $plainToken) : '';
            if ($tokenHash === '' || !hash_equals((string) ($agent['api_token_hash'] ?? ''), $tokenHash)) {
                return $this->respondError(
                    'O token enviado nao corresponde ao agente provisionado.',
                    403,
                    'AGENT_TOKEN_MISMATCH'
                );
            }

            $installationId = trim((string) $this->payloadValue($payload, ['installationId']));
            if ($installationId !== '' && trim((string) ($agent['installation_id'] ?? '')) !== '' && !hash_equals((string) $agent['installation_id'], $installationId)) {
                return $this->respondError(
                    'A instalacao informada nao corresponde ao agente provisionado.',
                    409,
                    'AGENT_INSTALLATION_MISMATCH'
                );
            }

            $receivedAt = date('Y-m-d H:i:s');
            $collectedAt = $this->normalizeDateTime($this->payloadValue($payload, ['collectedAtUtc']));

            $this->agentModel->update((int) $agent['id'], [
                'hostname' => $this->limitText((string) $this->payloadValue($payload, ['hostname']), 120),
                'serial_number' => $this->limitText((string) $this->payloadValue($payload, ['serialNumber']), 120),
                'manufacturer' => $this->limitText((string) $this->payloadValue($payload, ['manufacturer']), 160),
                'model' => $this->limitText((string) $this->payloadValue($payload, ['model']), 160),
                'motherboard' => $this->limitText((string) $this->payloadValue($payload, ['motherboard']), 180),
                'bios_version' => $this->limitText((string) $this->payloadValue($payload, ['biosVersion']), 120),
                'cpu' => $this->limitText((string) $this->payloadValue($payload, ['cpu']), 255),
                'ram_gb' => $this->normalizeDecimal($this->payloadValue($payload, ['ramGb'])),
                'windows_caption' => $this->limitText((string) $this->payloadValue($payload, ['windowsCaption']), 255),
                'windows_version' => $this->limitText((string) $this->payloadValue($payload, ['windowsVersion']), 80),
                'windows_build' => $this->limitText((string) $this->payloadValue($payload, ['windowsBuild']), 80),
                'ultimo_checkin_em' => $receivedAt,
                'ultimo_snapshot_em' => $collectedAt ?? $receivedAt,
            ]);

            $snapshotPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($snapshotPayload === false) {
                $snapshotPayload = '{}';
            }

            $this->snapshotModel->insert([
                'agent_id' => (int) $agent['id'],
                'payload_json' => $snapshotPayload,
                'hostname' => $this->limitText((string) $this->payloadValue($payload, ['hostname']), 120),
                'serial_number' => $this->limitText((string) $this->payloadValue($payload, ['serialNumber']), 120),
                'collected_at' => $collectedAt,
                'received_at' => $receivedAt,
            ]);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'AgentId' => $agentUuid,
                    'ReceivedAt' => date('c'),
                    'NextCheckInMinutes' => $this->inventoryIntervalMinutes(),
                ]);
        } catch (Throwable $e) {
            log_message('error', '[API V1][AGENTS CHECKIN] ' . $e->getMessage());
            return $this->respondError(
                'Falha inesperada ao processar o check-in do agente.',
                500,
                'AGENT_CHECKIN_UNEXPECTED'
            );
        }
    }

    private function payload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json) && !empty($json)) {
            return $json;
        }

        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }

    private function payloadValue(array $payload, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $payload[$key];
            }
        }

        $lower = array_change_key_case($payload, CASE_LOWER);
        foreach ($keys as $key) {
            $lowerKey = strtolower($key);
            if (array_key_exists($lowerKey, $lower)) {
                return $lower[$lowerKey];
            }
        }

        return $default;
    }

    private function resolveWarrantyNumber(string $numeroOs, string $publicUrl): array
    {
        $numeroOs = trim($numeroOs);
        $publicUrl = trim($publicUrl);

        if ($publicUrl === '') {
            return [$numeroOs, null, 200];
        }

        $parsed = parse_url($publicUrl);
        if (!is_array($parsed)) {
            return ['', 'A URL publica do selo e invalida.', 422];
        }

        $path = (string) ($parsed['path'] ?? '');
        $marker = '/api/public/warranty/';
        $markerPos = stripos($path, $marker);
        if ($markerPos === false) {
            return ['', 'A URL publica informada nao corresponde ao formato do selo de garantia.', 422];
        }

        $numberFromUrl = urldecode(substr($path, $markerPos + strlen($marker)));
        $numberFromUrl = trim(explode('/', $numberFromUrl)[0] ?? '');
        if ($numberFromUrl === '') {
            return ['', 'Nao foi possivel identificar a O.S. a partir da URL publica do selo.', 422];
        }

        $query = [];
        parse_str((string) ($parsed['query'] ?? ''), $query);
        $signature = trim((string) ($query['sig'] ?? ''));
        if (!$this->isWarrantyPublicSignatureValid($numberFromUrl, $signature)) {
            return ['', 'A assinatura da URL publica do selo e invalida ou expirou.', 403];
        }

        if ($numeroOs !== '' && strcasecmp($numeroOs, $numberFromUrl) !== 0) {
            return ['', 'O numero da O.S. informado difere do numero presente na URL publica do selo.', 422];
        }

        return [$numeroOs !== '' ? $numeroOs : $numberFromUrl, null, 200];
    }

    private function ensureInfrastructure(): void
    {
        (new AgentMonitorSchemaService())->ensure();
    }

    private function revokeTokenHash(string $tokenHash): void
    {
        $tokenHash = trim($tokenHash);
        if ($tokenHash === '') {
            return;
        }

        $token = $this->tokenModel
            ->where('token_hash', $tokenHash)
            ->where('revogado_em', null)
            ->first();

        if ($token) {
            $this->tokenModel->update((int) $token['id'], [
                'revogado_em' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function generateAgentUuid(): string
    {
        return 'agt_' . bin2hex(random_bytes(16));
    }

    private function buildAgentLabel(array $order): string
    {
        $parts = array_values(array_filter([
            trim((string) ($order['cliente_nome'] ?? '')),
            trim((string) ($order['numero_os'] ?? $order['numero_os_legado'] ?? '')),
            $this->buildEquipmentSummary($order),
        ]));

        return $this->limitText(implode(' | ', $parts), 120);
    }

    private function buildEquipmentSummary(array $order): string
    {
        $parts = array_values(array_filter([
            trim((string) ($order['equip_tipo'] ?? '')),
            trim((string) ($order['equip_marca'] ?? '')),
            trim((string) ($order['equip_modelo'] ?? '')),
        ]));

        return implode(' - ', $parts);
    }

    private function buildTokenName(string $installationId, string $numeroOs): string
    {
        $base = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower(trim($installationId))) ?: 'agent';
        $order = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower(trim($numeroOs))) ?: 'os';
        return substr('agent-monitor-' . $order . '-' . $base, 0, 80);
    }

    private function normalizeDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace(',', '.', preg_replace('/[^0-9,.\-]/', '', (string) $value) ?? '');
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function normalizeDateTime($value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function limitText(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength)
            : substr($value, 0, $maxLength);
    }

    private function inventoryIntervalMinutes(): int
    {
        return max(5, (int) env('agent.monitor.inventoryIntervalMinutes', 240));
    }

    private function retryIntervalMinutes(): int
    {
        return max(1, (int) env('agent.monitor.retryIntervalMinutes', 15));
    }

    private function tokenTtlHours(): int
    {
        return max(24, (int) env('agent.monitor.tokenTtlHours', 26280));
    }

    private function isWarrantyPublicSignatureValid(string $numeroOs, string $signature): bool
    {
        $signature = trim($signature);
        if ($signature === '') {
            return false;
        }

        return hash_equals($this->buildWarrantyPublicSignature($numeroOs), $signature);
    }

    private function buildWarrantyPublicSignature(string $numeroOs): string
    {
        return hash_hmac('sha256', $this->normalizeWarrantyPublicNumber($numeroOs), $this->warrantyPublicSecret());
    }

    private function normalizeWarrantyPublicNumber(string $numeroOs): string
    {
        return strtolower(trim($numeroOs));
    }

    private function warrantyPublicSecret(): string
    {
        $secret = trim((string) env('warranty.publicSecret', ''));
        if ($secret !== '') {
            return $secret;
        }

        $secret = trim((string) env('encryption.key', ''));
        return $secret !== '' ? $secret : 'warranty-public-dev-secret';
    }
}