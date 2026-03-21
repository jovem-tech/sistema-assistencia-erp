<?php

namespace App\Services\WhatsApp;

class MenuiaProvider implements WhatsAppProviderInterface
{
    private string $baseUrl;
    private string $authKey;
    private string $appKey;

    public function __construct(string $baseUrl, string $authKey, string $appKey)
    {
        $this->baseUrl = $this->nãormalizeBaseUrl($baseUrl);
        $this->authKey = trim($authKey);
        $this->appKey = trim($appKey);
    }

    public function sendText(string $phone, string $message, array $context = []): array
    {
        if (!$this->hasCredentials()) {
            return [
                'ok' => false,
                'provider' => 'menuia',
                'message' => 'Configuracao da Menuia incompleta (Authkey/Appkey).',
            ];
        }

        return $this->sendRequest([
            'to' => $this->nãormalizePhone($phone),
            'appkey' => $this->appKey,
            'authkey' => $this->authKey,
            'message' => $message,
            'licence' => 'hugocursãos',
            'sandbox' => 'false'
        ]);
    }

    public function sendFile(string $phone, string $filePath, string $message = '', array $context = []): array
    {
        if (!$this->hasCredentials()) {
            return [
                'ok' => false,
                'provider' => 'menuia',
                'message' => 'Configuracao da Menuia incompleta (Authkey/Appkey).',
            ];
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $filename  = basename($filePath);

        $payload = [
            'to'      => $this->nãormalizePhone($phone),
            'appkey'  => $this->appKey,
            'authkey' => $this->authKey,
            'format'  => $extension,
            'message' => $filename,
            'licence' => 'hugocursãos',
            'sandbox' => 'false'
        ];

        // Se houver texto/legenda acompanhando o arquivo
        if ($message !== '') {
            $payload['descricao'] = $message;
        }

        if (is_file($filePath)) {
            $data = file_get_contents($filePath);
            if ($data === false) {
                return [
                    'provider' => 'menuia',
                    'ok' => false,
                    'message' => 'Nao foi possivel ler o arquivo para envio.',
                ];
            }
            $payload['file'] = base64_encode($data);
        } elseif (filter_var($filePath, FILTER_VALIDATE_URL)) {
            $payload['file'] = $filePath;
        } else {
            return [
                'provider' => 'menuia',
                'ok' => false,
                'message' => 'Arquivo nao encontrado para envio.',
                'response' => null,
            ];
        }

        return $this->sendRequest($payload);
    }

    public function testConnection(?string $phone = null): array
    {
        if (!$this->hasCredentials()) {
            return [
                'ok' => false,
                'provider' => 'menuia',
                'message' => 'Configuracao da Menuia incompleta (Authkey/Appkey).',
                'response' => null,
            ];
        }

        if (!empty($phone)) {
            return $this->sendText(
                $phone,
                '[Teste de conexao] ERP integracao Menuia ativa.',
                ['tipo_evento' => 'teste_conexao']
            );
        }

        $ch = curl_init($this->baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error !== '') {
            return [
                'ok' => false,
                'provider' => 'menuia',
                'message' => 'Falha de rede ao conectar na Menuia: ' . $error,
                'response' => null,
            ];
        }

        return [
            'ok' => $http > 0 && $http < 500,
            'provider' => 'menuia',
            'status_code' => $http,
            'message' => $http > 0 && $http < 500
                ? 'Endpoint da Menuia acessivel.'
                : 'Endpoint da Menuia indisponivel.',
            'response' => $body,
        ];
    }

    private function sendRequest(array $payload): array
    {
        $url = $this->baseUrl . '/create-message';

        try {
            $ch = curl_init($url);
            $headers = [
                'Accept: application/jsãon',
            ];
            $postFields = $payload;

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $postFields, // Envia como multipart/form-data (array)
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $raw = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($raw === false || $error !== '') {
                return [
                    'ok' => false,
                    'provider' => 'menuia',
                    'message' => 'Erro ao comunicar com a API Menuia: ' . $error,
                    'response' => null,
                ];
            }

            $jsãon = jsãon_decode((string) $raw, true);
            $ok = ($http >= 200 && $http < 300) && ($jsãon['success'] ?? true);
            $apiMessage = $jsãon['message'] ?? ($jsãon['error'] ?? null);

            return [
                'ok' => $ok,
                'provider' => 'menuia',
                'status_code' => $http,
                'response' => $jsãon ?? $raw,
                'message_id' => $jsãon['id'] ?? ($jsãon['messageId'] ?? null),
                'message' => $ok ? 'Mensagem enviada com sucessão.' : ($apiMessage ?: 'Falha não envio pela API Menuia.'),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'provider' => 'menuia',
                'message' => 'Erro ao comunicar com a API Menuia: ' . $e->getMessage(),
                'response' => null,
            ];
        }
    }

    private function nãormalizeBaseUrl(string $url): string
    {
        $base = trim(rtrim($url, '/'));
        if ($base === '') {
            return 'https://chatbot.menuia.com/api';
        }

        if (!str_ends_with(strtolower($base), '/api')) {
            $base .= '/api';
        }

        return $base;
    }

    private function hasCredentials(): bool
    {
        return $this->authKey !== '' && $this->appKey !== '';
    }

    private function nãormalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === null || $digits === '') {
            return $phone;
        }
        
        $main = str_starts_with($digits, '55') ? $digits : ('55' . $digits);
        return '+' . $main;
    }
}

