<?php

namespace App\Services\WhatsApp;

class LocalGatewayProvider implements WhatsAppProviderInterface
{
    private string $baseUrl;
    private string $apiToken;
    private string $erpOrigin;
    private int $timeout;
    private string $providerId;
    private string $providerLabel;

    public function __construct(
        string $baseUrl,
        string $apiToken = '',
        string $erpOrigin = '',
        int $timeout = 20,
        string $providerId = 'api_whats_local',
        string $providerLabel = 'gateway local'
    ) {
        $this->baseUrl = $this->normalizeBaseUrl($baseUrl);
        $this->apiToken = trim($apiToken);
        $this->erpOrigin = trim($erpOrigin);
        $this->timeout = max(5, $timeout);
        $this->providerId = trim($providerId) !== '' ? trim($providerId) : 'api_whats_local';
        $this->providerLabel = trim($providerLabel) !== '' ? trim($providerLabel) : 'gateway local';
    }

    public function sendText(string $phone, string $message, array $context = []): array
    {
        $text = trim($message);
        if ($text === '') {
            return [
                'ok' => false,
                'provider' => $this->providerId,
                'message' => 'Mensagem vazia para envio no ' . $this->providerLabel . '.',
                'response' => null,
            ];
        }

        $payload = [
            'to' => $this->normalizePhone($phone),
            'number' => $this->normalizePhone($phone),
            'message' => $text,
        ];

        return $this->request('POST', '/create-message', $payload);
    }

    public function sendFile(string $phone, string $filePath, string $message = '', array $context = []): array
    {
        $path = trim($filePath);
        if ($path === '' || !is_file($path)) {
            return [
                'ok' => false,
                'provider' => $this->providerId,
                'message' => 'Arquivo nao encontrado para envio no ' . $this->providerLabel . '.',
                'response' => null,
            ];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return [
                'ok' => false,
                'provider' => $this->providerId,
                'message' => 'Nao foi possivel ler o arquivo para envio no ' . $this->providerLabel . '.',
                'response' => null,
            ];
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $normalizedPhone = $this->normalizePhone($phone);
        $payload = [
            'to' => $normalizedPhone,
            'number' => $normalizedPhone,
            'message' => trim($message) !== '' ? trim($message) : basename($path),
            'descricao' => trim($message),
            'format' => $extension !== '' ? $extension : 'pdf',
            'mime' => $context['mime_type'] ?? '',
            'isVideo' => ($context['tipo_conteudo'] ?? '') === 'video',
            'filename' => basename($path),
            'file' => base64_encode($raw),
            'media' => base64_encode($raw),
        ];

        return $this->request('POST', '/create-message', $payload);
    }

    public function testConnection(?string $phone = null): array
    {
        if (!empty($phone)) {
            return $this->sendText(
                $phone,
                '[Teste ERP] Conexao com ' . strtoupper($this->providerId) . ' ativa.',
                ['tipo_evento' => 'teste_conexao']
            );
        }

        $result = $this->request('GET', '/status');
        if (empty($result['ok'])) {
            return $result;
        }

        $response = $result['response'] ?? [];
        $ready = (bool) (($response['data']['ready'] ?? false));
        $status = (string) ($response['status'] ?? 'unknown');
        $result['message'] = $ready
            ? ucfirst($this->providerLabel) . ' conectado e pronto para envio.'
            : (ucfirst($this->providerLabel) . ' acessivel, status atual: ' . $status . '.');

        return $result;
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        if ($this->baseUrl === '') {
            return [
                'ok' => false,
                'provider' => $this->providerId,
                'message' => 'URL do ' . $this->providerLabel . ' nao configurada.',
                'response' => null,
            ];
        }

        $url = $this->baseUrl . $path;
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if ($this->apiToken !== '') {
            $headers[] = 'X-Api-Token: ' . $this->apiToken;
            $headers[] = 'Authorization: Bearer ' . $this->apiToken;
        }
        if ($this->erpOrigin !== '') {
            $headers[] = 'X-ERP-Origin: ' . $this->erpOrigin;
            $headers[] = 'Origin: ' . $this->erpOrigin;
        }

        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ];

        if (strtoupper($method) !== 'GET') {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $error !== '') {
            return [
                'ok' => false,
                'provider' => $this->providerId,
                'status_code' => 0,
                'message' => 'Falha de rede ao comunicar com ' . $this->providerLabel . ': ' . $error,
                'response' => null,
            ];
        }

        $json = json_decode((string) $raw, true);
        if (!is_array($json)) {
            $json = [
                'success' => $http >= 200 && $http < 300,
                'status' => $http >= 200 && $http < 300 ? 'ok' : 'error',
                'message' => $http >= 200 && $http < 300 ? 'Resposta nao-JSON recebida.' : 'Falha na resposta do gateway.',
                'data' => ['raw' => (string) $raw],
            ];
        }

        $ok = ($http >= 200 && $http < 300) && !empty($json['success']);
        $apiMessage = (string) ($json['message'] ?? '');
        $messageId = $json['data']['message_id'] ?? null;

        return [
            'ok' => $ok,
            'provider' => $this->providerId,
            'status_code' => $http,
            'response' => $json,
            'message_id' => $messageId,
            'message' => $ok
                ? ($apiMessage !== '' ? $apiMessage : 'Mensagem enviada por ' . $this->providerLabel . '.')
                : ($apiMessage !== '' ? $apiMessage : 'Falha em ' . $this->providerLabel . '.'),
        ];
    }

    private function normalizeBaseUrl(string $url): string
    {
        $base = trim(rtrim($url, '/'));
        if ($base === '') {
            return '';
        }
        return $base;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === null || $digits === '') {
            return $phone;
        }

        return str_starts_with($digits, '55') ? $digits : ('55' . $digits);
    }
}
