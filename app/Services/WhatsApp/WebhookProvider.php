<?php

namespace App\Services\WhatsApp;

/**
 * Provedor genérico via Webhook (API Rest)
 * Permite integrar com qualquer serviço de WhatsApp que aceite requisições HTTP JSON
 */
class WebhookProvider implements WhatsAppProviderInterface
{
    private string $url;
    private string $method;
    private array $headers;
    private string $payloadTemplate;

    public function __construct(string $url, string $method = 'POST', string $headersJson = '', string $payloadTemplate = '')
    {
        $this->url = trim($url);
        $this->method = strtoupper(trim($method) ?: 'POST');
        $this->headers = $this->parseHeaders($headersJson);
        $this->payloadTemplate = $payloadTemplate ?: '{"to": "{{phone}}", "message": "{{message}}"}';
    }

    public function sendText(string $phone, string $message, array $context = []): array
    {
        if ($this->url === '') {
            return ['ok' => false, 'message' => 'URL do Webhook não configurada.'];
        }

        $payload = $this->renderPayload($phone, $message);
        return $this->executeRequest($payload);
    }

    public function sendFile(string $phone, string $filePath, string $message = '', array $context = []): array
    {
        // Webhook genérico padrão não lida bem com binários complexos automaticamente.
        // Enviamos o texto base para não quebrar o fluxo.
        $text = $message !== '' ? $message : ("Envio de arquivo: " . basename($filePath));
        return $this->sendText($phone, $text);
    }

    public function testConnection(?string $phone = null): array
    {
        if ($phone) {
            return $this->sendText($phone, "Teste de integração via Webhook Genérico.");
        }
        
        if ($this->url === '') {
            return ['ok' => false, 'message' => 'URL do Webhook está vazia.'];
        }

        return ['ok' => true, 'message' => 'Configuração de Webhook pronta.'];
    }

    private function parseHeaders(string $json): array
    {
        $data = json_decode($json, true);
        $headers = [];
        
        $hasContentType = false;
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $headers[] = "$k: $v";
                if (strtolower($k) === 'content-type') $hasContentType = true;
            }
        }

        if (!$hasContentType) {
            $headers[] = 'Content-Type: application/json';
        }
        $headers[] = 'Accept: application/json';

        return $headers;
    }

    private function renderPayload(string $phone, string $message): string
    {
        $rendered = $this->payloadTemplate;
        
        // Proteção básica para JSON
        $jsonMessage = json_encode($message);
        $jsonMessage = trim($jsonMessage, '"'); 
        
        $rendered = str_replace('{{phone}}', $phone, $rendered);
        $rendered = str_replace('{{message}}', $jsonMessage, $rendered);
        
        return $rendered;
    }

    private function executeRequest(string $payload): array
    {
        $ch = curl_init($this->url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false, // Flexibilidade para ambiente local/proxy
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $ok = ($httpCode >= 200 && $httpCode < 300);
        $jsonRes = json_decode((string)$response, true);

        return [
            'ok' => $ok,
            'provider' => 'webhook',
            'status_code' => $httpCode,
            'response' => $jsonRes ?: $response,
            'message' => $ok ? 'Requisição enviada com sucesso.' : ('Falha no Webhook: ' . ($error ?: "HTTP $httpCode")),
        ];
    }
}
