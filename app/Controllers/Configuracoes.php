<?php

namespace App\Controllers;

use App\Models\ConfiguracaoModel;
use App\Models\LogModel;
use App\Services\MensageriaService;

class Configuracoes extends BaseController
{
    public function __construct()
    {
        requirePermission('configuracoes');
    }

    public function index()
    {
        $model = new ConfiguracaoModel();

        $todasConfiguracoes = $model->findAll();
        $configs = [];
        foreach ($todasConfiguracoes as $c) {
            $configs[$c['chave']] = $c['valor'];
        }

        $data = [
            'title' => 'Configuracoes',
            'configs' => $configs,
        ];

        return view('configuracoes/index', $data);
    }

    public function save()
    {
        $model = new ConfiguracaoModel();
        $posts = $this->request->getPost();
        $previousMenuiaUrl = $this->normalizeMenuiaUrl((string) get_config('whatsapp_menuia_url', 'https://chatbot.menuia.com/api'));
        $previousMenuiaApp = trim((string) get_config('whatsapp_menuia_appkey', ''));
        $previousMenuiaAuth = trim((string) get_config('whatsapp_menuia_authkey', ''));

        if (array_key_exists('sessao_inatividade_minutos', $posts)) {
            $timeoutMinutes = (int) $posts['sessao_inatividade_minutos'];
            if ($timeoutMinutes < 5) {
                $timeoutMinutes = 5;
            }

            $posts['sessao_inatividade_minutos'] = (string) min($timeoutMinutes, 1440);
        }

        if (array_key_exists('whatsapp_menuia_url', $posts)) {
            $posts['whatsapp_menuia_url'] = $this->normalizeMenuiaUrl((string) $posts['whatsapp_menuia_url']);
        }

        $nextMenuiaUrl = array_key_exists('whatsapp_menuia_url', $posts)
            ? (string) $posts['whatsapp_menuia_url']
            : $previousMenuiaUrl;
        $nextMenuiaApp = array_key_exists('whatsapp_menuia_appkey', $posts)
            ? trim((string) $posts['whatsapp_menuia_appkey'])
            : $previousMenuiaApp;
        $nextMenuiaAuth = array_key_exists('whatsapp_menuia_authkey', $posts)
            ? trim((string) $posts['whatsapp_menuia_authkey'])
            : $previousMenuiaAuth;

        foreach ($posts as $chave => $valor) {
            if ($chave !== 'csrf_test_name') {
                $model->setConfig($chave, $valor);
            }
        }

        $uploadPath = 'uploads/sistema';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $logo = $this->request->getFile('sistema_logo');
        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            if (in_array($logo->getExtension(), ['jpg', 'jpeg', 'png', 'gif', 'svg'], true)) {
                $newName = $logo->getRandomName();
                $logo->move($uploadPath, $newName);

                $oldLogo = get_config('sistema_logo');
                if ($oldLogo && file_exists($uploadPath . '/' . $oldLogo)) {
                    unlink($uploadPath . '/' . $oldLogo);
                }

                $model->setConfig('sistema_logo', $newName);
            }
        }

        $favicon = $this->request->getFile('sistema_icone');
        if ($favicon && $favicon->isValid() && !$favicon->hasMoved()) {
            if (in_array($favicon->getExtension(), ['jpg', 'jpeg', 'png', 'ico', 'x-icon'], true)) {
                $newName = $favicon->getRandomName();
                $favicon->move($uploadPath, $newName);

                $oldFavicon = get_config('sistema_icone');
                if ($oldFavicon && file_exists($uploadPath . '/' . $oldFavicon)) {
                    unlink($uploadPath . '/' . $oldFavicon);
                }

                $model->setConfig('sistema_icone', $newName);
            }
        }

        if (
            $previousMenuiaUrl !== $nextMenuiaUrl
            || $previousMenuiaApp !== $nextMenuiaApp
            || $previousMenuiaAuth !== $nextMenuiaAuth
        ) {
            $this->clearWhatsAppConnectionStatus();
        }

        LogModel::registrar('configuracao', 'Configuracoes do sistema atualizadas');

        return redirect()->to('/configuracoes')->with('success', 'Configuracoes salvas com sucesso.');
    }

    public function testWhatsAppConnection()
    {
        $telefone = trim((string) $this->request->getPost('telefone'));
        if ($telefone === '') {
            $telefone = trim((string) get_config('whatsapp_test_phone', ''));
        }

        $providerType = trim((string) $this->request->getPost('provider'));
        if ($providerType === '') {
            $providerType = (string) get_config('whatsapp_direct_provider', 'menuia');
        }

        $mensageria = new MensageriaService();
        $overrides = $this->buildProviderOverrides();
        $result = $mensageria->testDirectConnection(
            $telefone !== '' ? $telefone : null,
            $providerType,
            $overrides,
            false
        );

        $this->persistWhatsAppConnectionStatus(
            $providerType,
            !empty($result['ok']),
            (string) ($result['message'] ?? ''),
            $overrides
        );

        if (!empty($result['ok'])) {
            return $this->response->setJSON([
                'ok' => true,
                'message' => $result['message'] ?? 'Conexao validada com sucesso.',
                'response' => $result['response'] ?? null,
            ]);
        }

        return $this->response->setStatusCode(422)->setJSON([
            'ok' => false,
            'message' => $result['message'] ?? 'Falha ao validar conexao do provedor WhatsApp.',
            'response' => $result['response'] ?? null,
            'status_code' => $result['status_code'] ?? null,
        ]);
    }

    public function sendWhatsAppTestMessage()
    {
        $telefone = trim((string) $this->request->getPost('telefone'));
        $mensagem = trim((string) $this->request->getPost('mensagem'));

        if ($telefone === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Informe o telefone de teste.',
            ]);
        }

        if ($mensagem === '') {
            $mensagem = '[Teste de integracao] Mensagem de teste enviada pelo ERP.';
        }

        $providerType = trim((string) $this->request->getPost('provider'));
        if ($providerType === '') {
            $providerType = (string) get_config('whatsapp_direct_provider', 'menuia');
        }

        $mensageria = new MensageriaService();
        $result = $mensageria->sendDirectText(
            $telefone,
            $mensagem,
            ['tipo_evento' => 'teste_manual'],
            $providerType,
            $this->buildProviderOverrides(),
            false
        );

        if (!empty($result['ok'])) {
            LogModel::registrar('whatsapp_teste', 'Mensagem de teste WhatsApp enviada para ' . $telefone);
            return $this->response->setJSON([
                'ok' => true,
                'message' => 'Mensagem de teste enviada com sucesso.',
                'result' => $result,
            ]);
        }

        return $this->response->setStatusCode(422)->setJSON([
            'ok' => false,
            'message' => $result['message'] ?? 'Falha ao enviar mensagem de teste.',
            'result' => $result,
            'status_code' => $result['status_code'] ?? null,
        ]);
    }

    public function whatsappInboundSelfCheck()
    {
        $provider = trim((string) $this->request->getPost('provider'));
        if ($provider === '') {
            $provider = trim((string) get_config('whatsapp_direct_provider', 'api_whats_local'));
        }
        if ($provider === 'local_node') {
            $provider = 'api_whats_local';
        }

        if (!in_array($provider, ['api_whats_local', 'api_whats_linux'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Self-check inbound disponivel apenas para API Local (Windows) e API Linux (VPS).',
            ]);
        }

        $webhookToken = trim((string) get_config('whatsapp_webhook_token', ''));
        if ($webhookToken === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Webhook Token (inbound) nao configurado no ERP.',
            ]);
        }

        $gatewayConfig = $this->resolveGatewayConfig($provider);
        $baseOrigin = rtrim((string) base_url('/'), '/');
        $originConfigured = rtrim((string) ($gatewayConfig['origin'] ?? ''), '/');
        $originAligned = $originConfigured !== '' && strcasecmp($originConfigured, $baseOrigin) === 0;

        $statusCheck = $this->callGateway('GET', '/status', null, 6, $provider);
        $statusOk = !empty($statusCheck['success']);

        $gatewayForwardCheck = $this->callGateway('POST', '/self-check-inbound', [
            'source' => 'erp_configuracoes',
            'provider' => $provider,
        ], 12, $provider);
        $gatewayForwardOk = !empty($gatewayForwardCheck['success']);

        $directWebhookCheck = $this->runWebhookDirectSelfCheck($webhookToken);
        $directWebhookOk = !empty($directWebhookCheck['ok']);

        $allOk = $statusOk && $gatewayForwardOk && $directWebhookOk && $originAligned;

        $response = [
            'ok' => $allOk,
            'message' => $allOk
                ? 'Self-check inbound validado com sucesso.'
                : 'Self-check inbound encontrou pendencias de configuracao ou comunicacao.',
            'checks' => [
                'gateway_status' => [
                    'ok' => $statusOk,
                    'status' => $statusCheck['status'] ?? null,
                    'message' => $statusCheck['message'] ?? 'Falha ao consultar /status no gateway.',
                ],
                'gateway_forward' => [
                    'ok' => $gatewayForwardOk,
                    'status' => $gatewayForwardCheck['status'] ?? null,
                    'message' => $gatewayForwardCheck['message'] ?? 'Falha ao executar /self-check-inbound no gateway.',
                    'webhook_url' => $gatewayForwardCheck['data']['webhook_url'] ?? null,
                    'target_url' => $gatewayForwardCheck['data']['target_url'] ?? ($gatewayForwardCheck['error']['target_url'] ?? null),
                    'attempts' => $gatewayForwardCheck['data']['attempts'] ?? ($gatewayForwardCheck['error']['attempts'] ?? []),
                    'detail' => $gatewayForwardCheck['error']['detail'] ?? null,
                    'erp_response' => $gatewayForwardCheck['data']['erp_response'] ?? ($gatewayForwardCheck['error'] ?? null),
                ],
                'webhook_direct' => [
                    'ok' => $directWebhookOk,
                    'message' => $directWebhookCheck['message'] ?? 'Falha no POST direto para /webhooks/whatsapp.',
                    'url' => $directWebhookCheck['url'] ?? null,
                    'status_code' => $directWebhookCheck['status_code'] ?? null,
                    'attempts' => $directWebhookCheck['attempts'] ?? [],
                    'detail' => $directWebhookCheck['error'] ?? null,
                ],
                'origin_alignment' => [
                    'ok' => $originAligned,
                    'expected' => $baseOrigin,
                    'configured' => $originConfigured,
                ],
            ],
            'meta' => [
                'provider' => $provider,
                'gateway_url' => $gatewayConfig['url'] ?? null,
                'expected_webhook_url' => rtrim((string) base_url('webhooks/whatsapp'), '/'),
            ],
        ];

        if (!$allOk) {
            return $this->response->setStatusCode(422)->setJSON($response);
        }

        return $this->response->setJSON($response);
    }

    public function whatsappLocalStatus()
    {
        $provider = trim((string) $this->request->getGet('provider'));
        $result = $this->callGateway('GET', '/status', null, 3, $provider);
        return $this->response->setJSON($result);
    }

    public function whatsappLocalQr()
    {
        $provider = trim((string) $this->request->getGet('provider'));
        $result = $this->callGateway('GET', '/qr', null, 6, $provider);
        return $this->response->setJSON($result);
    }

    public function whatsappLocalRestart()
    {
        $provider = trim((string) ($this->request->getPost('provider') ?: $this->request->getGet('provider')));
        $clean = $this->request->getPost('clean') === 'true';
        $result = $this->callGateway('POST', '/restart', ['clean' => $clean], null, $provider);
        return $this->response->setJSON($result);
    }

    public function whatsappLocalLogout()
    {
        $provider = trim((string) ($this->request->getPost('provider') ?: $this->request->getGet('provider')));
        $result = $this->callGateway('POST', '/logout', [], null, $provider);
        return $this->response->setJSON($result);
    }

    public function whatsappLocalStart()
    {
        $provider = trim((string) ($this->request->getPost('provider') ?: $this->request->getGet('provider'))) ?: 'api_whats_local';

        $isWindows = stripos(PHP_OS, 'WIN') === 0;
        if ($provider === 'api_whats_linux') {
            // No Linux usamos PM2. Tentamos dar um restart no processo pelo nome padrao.
            $output = [];
            $retval = null;
            exec('pm2 restart whatsapp-gateway 2>&1', $output, $retval);
            
            return $this->response->setJSON([
                'success' => $retval === 0,
                'message' => $retval === 0 ? 'Comando PM2 executado com sucesso.' : 'Falha ao executar PM2.',
                'output' => $output
            ]);
        }

        // Para Windows (XAMPP / Local)
        $apiPath = ROOTPATH . 'whatsapp-api';
        if (!is_dir($apiPath)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Pasta whatsapp-api nao encontrada.']);
        }

        if ($isWindows) {
            // Comando para Windows: inicia oculto em background
            // Usamos 'start /B' para nao abrir janela de terminal e direcionamos logs
            $cmd = "cd /d " . escapeshellarg($apiPath) . " && start /B node server.js > boot.out.log 2> boot.err.log";
            pclose(popen($cmd, "r"));
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Comando de inicializacao enviado para o Windows. Aguarde alguns segundos.',
            ]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Sistema operacional nao suportado para auto-start direto.']);
    }

    private function buildProviderOverrides(): array
    {
        return [
            'whatsapp_menuia_url' => $this->normalizeMenuiaUrl((string) $this->request->getPost('url')),
            'whatsapp_menuia_authkey' => trim((string) $this->request->getPost('authkey')),
            'whatsapp_menuia_appkey' => trim((string) $this->request->getPost('appkey')),
            'whatsapp_local_node_url' => trim((string) ($this->request->getPost('local_url') ?: get_config('whatsapp_local_node_url', 'http://127.0.0.1:3001'))),
            'whatsapp_local_node_token' => trim((string) ($this->request->getPost('local_token') ?: get_config('whatsapp_local_node_token', ''))),
            'whatsapp_local_node_origin' => trim((string) ($this->request->getPost('local_origin') ?: get_config('whatsapp_local_node_origin', base_url('/')))),
            'whatsapp_local_node_timeout' => (int) ($this->request->getPost('local_timeout') ?: get_config('whatsapp_local_node_timeout', 20)),
            'whatsapp_linux_node_url' => trim((string) ($this->request->getPost('linux_url') ?: get_config('whatsapp_linux_node_url', 'http://127.0.0.1:3001'))),
            'whatsapp_linux_node_token' => trim((string) ($this->request->getPost('linux_token') ?: get_config('whatsapp_linux_node_token', ''))),
            'whatsapp_linux_node_origin' => trim((string) ($this->request->getPost('linux_origin') ?: get_config('whatsapp_linux_node_origin', base_url('/')))),
            'whatsapp_linux_node_timeout' => (int) ($this->request->getPost('linux_timeout') ?: get_config('whatsapp_linux_node_timeout', 20)),
            'whatsapp_webhook_url' => trim((string) $this->request->getPost('webhook_url')),
            'whatsapp_webhook_method' => trim((string) $this->request->getPost('webhook_method')),
            'whatsapp_webhook_headers' => (string) $this->request->getPost('webhook_headers'),
            'whatsapp_webhook_payload' => (string) $this->request->getPost('webhook_payload'),
        ];
    }

    private function normalizeMenuiaUrl(string $url): string
    {
        $normalized = trim(rtrim($url, '/'));
        if ($normalized === '') {
            return 'https://chatbot.menuia.com/api';
        }

        $parts = parse_url($normalized);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === 'api.menuia.com') {
            return 'https://chatbot.menuia.com/api';
        }

        if (!str_ends_with(strtolower($normalized), '/api')) {
            $normalized .= '/api';
        }

        return $normalized;
    }

    private function persistWhatsAppConnectionStatus(string $provider, bool $success, string $message, array $overrides = []): void
    {
        $model = new ConfiguracaoModel();
        $model->setConfig('whatsapp_last_check_provider', $provider);
        $model->setConfig('whatsapp_last_check_status', $success ? 'success' : 'error');
        $model->setConfig('whatsapp_last_check_message', trim($message));
        $model->setConfig('whatsapp_last_check_at', date('Y-m-d H:i:s'));
        $model->setConfig(
            'whatsapp_last_check_signature',
            $provider === 'menuia'
                ? $this->buildMenuiaCredentialSignature(
                    (string) ($overrides['whatsapp_menuia_url'] ?? get_config('whatsapp_menuia_url', 'https://chatbot.menuia.com/api')),
                    (string) ($overrides['whatsapp_menuia_appkey'] ?? get_config('whatsapp_menuia_appkey', '')),
                    (string) ($overrides['whatsapp_menuia_authkey'] ?? get_config('whatsapp_menuia_authkey', ''))
                )
                : ''
        );
    }

    private function clearWhatsAppConnectionStatus(): void
    {
        $model = new ConfiguracaoModel();
        $model->setConfig('whatsapp_last_check_provider', '');
        $model->setConfig('whatsapp_last_check_status', '');
        $model->setConfig('whatsapp_last_check_message', '');
        $model->setConfig('whatsapp_last_check_at', '');
        $model->setConfig('whatsapp_last_check_signature', '');
    }

    private function buildMenuiaCredentialSignature(string $url, string $appKey, string $authKey): string
    {
        $normalizedUrl = $this->normalizeMenuiaUrl($url);
        $normalizedAppKey = trim($appKey);
        $normalizedAuthKey = trim($authKey);

        if ($normalizedUrl === '' || $normalizedAppKey === '' || $normalizedAuthKey === '') {
            return '';
        }

        return strtolower($normalizedUrl) . '|' . $normalizedAppKey . '|' . $normalizedAuthKey;
    }

    private function callGateway(string $method, string $path, ?array $jsonBody = null, ?int $timeout = null, string $provider = ''): array
    {
        $gateway = $this->resolveGatewayConfig($provider);
        $requestTimeout = $timeout ?: $gateway['timeout'];

        $headers = [
            'Accept' => 'application/json',
        ];
        if ($gateway['token'] !== '') {
            $headers['X-Api-Token'] = $gateway['token'];
            $headers['Authorization'] = 'Bearer ' . $gateway['token'];
        }
        if ($gateway['origin'] !== '') {
            $headers['X-ERP-Origin'] = $gateway['origin'];
            $headers['Origin'] = $gateway['origin'];
        }

        $client = \Config\Services::curlrequest();
        try {
            $opts = [
                'timeout' => max(2, $requestTimeout),
                'http_errors' => false,
                'headers' => $headers,
            ];
            if ($jsonBody !== null) {
                $opts['json'] = $jsonBody;
            }

            $response = strtoupper($method) === 'POST'
                ? $client->post($gateway['url'] . $path, $opts)
                : $client->get($gateway['url'] . $path, $opts);

            $decoded = json_decode((string) $response->getBody(), true);
            if (is_array($decoded)) {
                return $decoded;
            }
            return [
                'success' => false,
                'status' => 'invalid_response',
                'message' => 'Resposta invalida do gateway.',
                'error' => [
                    'body' => (string) $response->getBody(),
                    'status_code' => $response->getStatusCode(),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'gateway_unreachable',
                'message' => 'Servidor do gateway inacessivel.',
                'error' => [
                    'detail' => $e->getMessage(),
                    'url' => $gateway['url'] . $path,
                    'provider' => $gateway['provider'],
                ],
            ];
        }
    }

    private function runWebhookDirectSelfCheck(string $token): array
    {
        $webhookUrl = (string) base_url('webhooks/whatsapp');
        $payload = [
            'self_check' => true,
            'source' => 'erp_direct_self_check',
            'timestamp' => gmdate('c'),
        ];

        $client = \Config\Services::curlrequest();
        $attempts = [];

        foreach ($this->buildWebhookCandidates($webhookUrl) as $candidateUrl) {
            try {
                $response = $client->post($candidateUrl, [
                    'timeout' => 10,
                    'http_errors' => false,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'X-Webhook-Token' => $token,
                        'X-Webhook-Self-Check' => '1',
                    ],
                    'json' => $payload,
                ]);

                $statusCode = (int) $response->getStatusCode();
                $decoded = json_decode((string) $response->getBody(), true);
                $ok = $statusCode >= 200 && $statusCode < 300 && is_array($decoded) && !empty($decoded['ok']);

                $attemptInfo = [
                    'url' => $candidateUrl,
                    'status_code' => $statusCode,
                    'message' => is_array($decoded)
                        ? ((string) ($decoded['message'] ?? 'Webhook respondeu sem mensagem.'))
                        : 'Resposta invalida do webhook.',
                ];
                $attempts[] = $attemptInfo;

                if ($ok) {
                    return [
                        'ok' => true,
                        'url' => $candidateUrl,
                        'status_code' => $statusCode,
                        'message' => $attemptInfo['message'],
                        'response' => is_array($decoded) ? $decoded : ['raw' => (string) $response->getBody()],
                        'attempts' => $attempts,
                    ];
                }
            } catch (\Throwable $e) {
                $attempts[] = [
                    'url' => $candidateUrl,
                    'status_code' => 0,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $last = end($attempts);
        return [
            'ok' => false,
            'url' => (string) ($last['url'] ?? $webhookUrl),
            'status_code' => (int) ($last['status_code'] ?? 0),
            'message' => 'Falha ao executar POST direto no webhook.',
            'error' => (string) ($last['message'] ?? 'Falha desconhecida.'),
            'attempts' => $attempts,
        ];
    }

    private function buildWebhookCandidates(string $url): array
    {
        $url = trim($url);
        if ($url === '') {
            return [];
        }

        $candidates = [$url];
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === 'localhost') {
            $alt = $this->replaceHostInUrl($url, '127.0.0.1');
            if ($alt !== null) {
                $candidates[] = $alt;
            }
        } elseif ($host === '127.0.0.1' || $host === '::1') {
            $alt = $this->replaceHostInUrl($url, 'localhost');
            if ($alt !== null) {
                $candidates[] = $alt;
            }
        }

        return array_values(array_unique($candidates));
    }

    private function replaceHostInUrl(string $url, string $newHost): ?string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme'])) {
            return null;
        }

        $result = $parts['scheme'] . '://';
        if (isset($parts['user'])) {
            $result .= $parts['user'];
            if (isset($parts['pass'])) {
                $result .= ':' . $parts['pass'];
            }
            $result .= '@';
        }

        $result .= $newHost;
        if (isset($parts['port'])) {
            $result .= ':' . $parts['port'];
        }

        $result .= $parts['path'] ?? '';
        if (isset($parts['query']) && $parts['query'] !== '') {
            $result .= '?' . $parts['query'];
        }
        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $result .= '#' . $parts['fragment'];
        }

        return $result;
    }

    private function resolveGatewayConfig(string $provider = ''): array
    {
        $selected = strtolower(trim($provider));
        if ($selected === '') {
            $selected = strtolower((string) get_config('whatsapp_direct_provider', 'api_whats_local'));
        }

        if ($selected === 'local_node') {
            $selected = 'api_whats_local';
        }
        if (!in_array($selected, ['api_whats_local', 'api_whats_linux', 'menuia', 'webhook'], true)) {
            $selected = 'api_whats_local';
        }

        if ($selected === 'api_whats_linux') {
            return [
                'provider' => 'api_whats_linux',
                'url' => rtrim((string) get_config('whatsapp_linux_node_url', 'http://127.0.0.1:3001'), '/'),
                'token' => trim((string) get_config('whatsapp_linux_node_token', '')),
                'origin' => trim((string) get_config('whatsapp_linux_node_origin', base_url('/'))),
                'timeout' => (int) get_config('whatsapp_linux_node_timeout', 20),
            ];
        }

        return [
            'provider' => 'api_whats_local',
            'url' => rtrim((string) get_config('whatsapp_local_node_url', 'http://127.0.0.1:3001'), '/'),
            'token' => trim((string) get_config('whatsapp_local_node_token', '')),
            'origin' => trim((string) get_config('whatsapp_local_node_origin', base_url('/'))),
            'timeout' => (int) get_config('whatsapp_local_node_timeout', 20),
        ];
    }
}
