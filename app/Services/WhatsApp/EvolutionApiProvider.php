<?php

namespace App\Services\WhatsApp;

class EvolutionApiProvider implements WhatsAppProviderInterface
{
    private string $baseUrl;
    private string $apiKey;
    private string $instance;
    private int $timeout;

    public function __construct(string $baseUrl, string $apiKey, string $instance, int $timeout = 20)
    {
        $this->baseUrl = rtrim(trim($baseUrl), '/');
        $this->apiKey = trim($apiKey);
        $this->instance = trim($instance);
        $this->timeout = max(5, $timeout);
    }

    public function sendText(string $phone, string $message, array $context = []): array
    {
        $text = trim($message);
        if ($text === '') {
            return $this->validationFailure('Mensagem vazia para envio pela Evolution API.');
        }

        if (!$this->hasCredentials()) {
            return $this->validationFailure('Configuracao da Evolution incompleta (URL, API key ou instancia).');
        }

        return $this->request(
            'POST',
            '/message/sendText/' . rawurlencode($this->instance),
            [
                'number' => $this->normalizePhone($phone),
                'text' => $text,
            ]
        );
    }

    public function sendFile(string $phone, string $filePath, string $message = '', array $context = []): array
    {
        if (!$this->hasCredentials()) {
            return $this->validationFailure('Configuracao da Evolution incompleta (URL, API key ou instancia).');
        }

        $path = trim($filePath);
        if ($path === '' || !is_file($path)) {
            return $this->validationFailure('Arquivo nao encontrado para envio pela Evolution API.');
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return $this->validationFailure('Nao foi possivel ler o arquivo para envio pela Evolution API.');
        }

        $mime = strtolower(trim((string) ($context['mime_type'] ?? $this->detectMimeByPath($path))));
        $tipoConteudo = strtolower(trim((string) ($context['tipo_conteudo'] ?? '')));
        $mediaType = $this->resolveMediaType($tipoConteudo, $mime, $path);

        return $this->request(
            'POST',
            '/message/sendMedia/' . rawurlencode($this->instance),
            [
                'number' => $this->normalizePhone($phone),
                'mediatype' => $mediaType,
                'mimetype' => $mime !== '' ? $mime : 'application/octet-stream',
                'caption' => trim($message),
                'media' => base64_encode($raw),
                'fileName' => basename($path),
            ]
        );
    }

    public function testConnection(?string $phone = null): array
    {
        if (!$this->hasCredentials()) {
            return $this->validationFailure('Configuracao da Evolution incompleta (URL, API key ou instancia).');
        }

        if (!empty($phone)) {
            return $this->sendText(
                $phone,
                '[Teste ERP] Conexao com Evolution API ativa em ' . date('d/m/Y H:i:s') . '.',
                ['tipo_evento' => 'teste_conexao']
            );
        }

        $result = $this->request('GET', '/instance/connectionState/' . rawurlencode($this->instance));
        if (empty($result['response']['instance']) || !is_array($result['response']['instance'])) {
            return $result;
        }

        $state = strtolower(trim((string) ($result['response']['instance']['state'] ?? 'unknown')));
        $ok = in_array($state, ['open', 'connecting'], true);

        $result['ok'] = $ok;
        $result['failure_type'] = $ok ? null : 'provider_unavailable';
        $result['message'] = $ok
            ? 'Evolution acessivel. Estado atual da instancia: ' . $state . '.'
            : 'Evolution acessivel, mas a instancia esta em estado "' . $state . '".';

        return $result;
    }

    public function fetchProfilePictureUrl(string $phone): array
    {
        if (!$this->hasCredentials()) {
            return $this->validationFailure('Configuracao da Evolution incompleta (URL, API key ou instancia).');
        }

        return $this->request(
            'POST',
            '/chat/fetchProfilePictureUrl/' . rawurlencode($this->instance),
            [
                'number' => $this->normalizeContactIdentifier($phone),
            ]
        );
    }

    public function fetchChats(): array
    {
        if (!$this->hasCredentials()) {
            return $this->validationFailure('Configuracao da Evolution incompleta (URL, API key ou instancia).');
        }

        return $this->request(
            'POST',
            '/chat/findChats/' . rawurlencode($this->instance),
            []
        );
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        if ($this->baseUrl === '') {
            return $this->validationFailure('URL da Evolution API nao configurada.');
        }

        $headers = [
            'Accept: application/json',
        ];

        if ($this->apiKey !== '') {
            $headers[] = 'apikey: ' . $this->apiKey;
        }

        $ch = curl_init($this->baseUrl . $path);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ];

        if (strtoupper($method) !== 'GET') {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
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
                'provider' => 'evolution',
                'status_code' => 0,
                'failure_type' => $this->classifyCurlFailure($error),
                'message' => 'Falha de rede ao comunicar com a Evolution API: ' . $error,
                'response' => null,
            ];
        }

        $json = json_decode((string) $raw, true);
        $response = is_array($json) ? $json : ['raw' => (string) $raw];
        $ok = $http >= 200 && $http < 300;

        return [
            'ok' => $ok,
            'provider' => 'evolution',
            'status_code' => $http,
            'failure_type' => $ok ? null : $this->classifyHttpFailure($http),
            'message' => $ok
                ? 'Operacao realizada com sucesso na Evolution API.'
                : $this->extractErrorMessage($response, $http),
            'message_id' => $response['key']['id'] ?? null,
            'response' => $response,
        ];
    }

    private function hasCredentials(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '' && $this->instance !== '';
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return $digits;
    }

    private function normalizeContactIdentifier(string $phone): string
    {
        $digits = $this->normalizePhone($phone);
        if ($digits === '') {
            return $phone;
        }

        if (str_contains($phone, '@')) {
            return trim($phone);
        }

        return $digits . '@s.whatsapp.net';
    }

    private function resolveMediaType(string $tipoConteudo, string $mime, string $path): string
    {
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        if (in_array($tipoConteudo, ['imagem', 'image'], true)) {
            return 'image';
        }

        if (in_array($tipoConteudo, ['video'], true)) {
            return 'video';
        }

        if (in_array($tipoConteudo, ['audio', 'ptt', 'voice', 'voice_note'], true)) {
            return 'audio';
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'image';
        }
        if (in_array($extension, ['mp4', 'mov', 'avi', 'mkv', 'webm'], true)) {
            return 'video';
        }

        return 'document';
    }

    private function detectMimeByPath(string $path): string
    {
        if (function_exists('mime_content_type')) {
            $detected = mime_content_type($path);
            if (is_string($detected) && trim($detected) !== '') {
                return trim($detected);
            }
        }

        return 'application/octet-stream';
    }

    private function classifyCurlFailure(string $error): string
    {
        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower(trim($error), 'UTF-8')
            : strtolower(trim($error));

        if ($normalized === '') {
            return 'gateway_unreachable';
        }

        if (str_contains($normalized, 'timed out') || str_contains($normalized, 'timeout')) {
            return 'gateway_timeout';
        }

        return 'gateway_unreachable';
    }

    private function classifyHttpFailure(int $httpCode): string
    {
        if ($httpCode === 408 || $httpCode === 429) {
            return 'provider_unavailable';
        }

        if ($httpCode >= 500) {
            return 'provider_unavailable';
        }

        return 'provider_rejected';
    }

    /**
     * @param array<string,mixed> $response
     */
    private function extractErrorMessage(array $response, int $statusCode): string
    {
        $message = trim((string) (
            $response['message'] ?? $response['error'] ?? $response['response']['message'] ?? ''
        ));

        if ($message !== '') {
            return $message;
        }

        return 'Falha na Evolution API (HTTP ' . $statusCode . ').';
    }

    private function validationFailure(string $message): array
    {
        return [
            'ok' => false,
            'provider' => 'evolution',
            'failure_type' => 'validation',
            'message' => $message,
            'response' => null,
        ];
    }
}
