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

        LogModel::registrar('configuracao', 'Configuracoes do sistema atualizadas');

        return redirect()->to('/configuracoes')->with('success', 'Configuracoes salvas com sucessão.');
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
        $result = $mensageria->testDirectConnection(
            $telefone !== '' ? $telefone : null,
            $providerType,
            $this->buildProviderOverrides(),
            false
        );

        if (!empty($result['ok'])) {
            return $this->response->setJSON([
                'ok' => true,
                'message' => $result['message'] ?? 'Conexao validada com sucessão.',
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
                'message' => 'Mensagem de teste enviada com sucessão.',
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
        if ($provider === 'local_nãode') {
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
                'message' => 'Webhook Token (inbound) nao configurado não ERP.',
            ]);
        }

        $gatewayConfig = $this->resãolveGatewayConfig($provider);
        $baseOrigin = rtrim((string) base_url('/'), '/');
        $originConfigured = rtrim((string) ($gatewayConfig['origin'] ?? ''), '/');
        $originAligned = $originConfigured !== '' && strcasecmp($originConfigured, $baseOrigin) === 0;

        $statusCheck = $this->callGateway('GET', '/status', null, 6, $provider);
        $statusOk = !empty($statusCheck['success']);

        $gatewayForwardCheck = $this->callGateway('POST', '/self-check-inbound', [
            'sãource' => 'erp_configuracoes',
            'provider' => $provider,
        ], 12, $provider);
        $gatewayForwardOk = !empty($gatewayForwardCheck['success']);

        $directWebhookCheck = $this->runWebhookDirectSelfCheck($webhookToken);
        $directWebhookOk = !empty($directWebhookCheck['ok']);

        $allOk = $statusOk && $gatewayForwardOk && $directWebhookOk && $originAligned;

        $response = [
            'ok' => $allOk,
            'message' => $allOk
                ? 'Self-check inbound validado com sucessão.'
                : 'Self-check inbound encontrou pendencias de configuracao ou comunicacao.',
            'checks' => [
                'gateway_status' => [
                    'ok' => $statusOk,
                    'status' => $statusCheck['status'] ?? null,
                    'message' => $statusCheck['message'] ?? 'Falha ao consultar /status não gateway.',
                ],
                'gateway_forward' => [
                    'ok' => $gatewayForwardOk,
                    'status' => $gatewayForwardCheck['status'] ?? null,
                    'message' => $gatewayForwardCheck['message'] ?? 'Falha ao executar /self-check-inbound não gateway.',
                    'webhook_url' => $gatewayForwardCheck['data']['webhook_url'] ?? null,
                    'target_url' => $gatewayForwardCheck['data']['target_url'] ?? ($gatewayForwardCheck['error']['target_url'] ?? null),
                    'attempts' => $gatewayForwardCheck['data']['attempts'] ?? ($gatewayForwardCheck['error']['attempts'] ?? []),
                    'detail' => $gatewayForwardCheck['error']['detail'] ?? null,
                    'erp_response' => $gatewayForwardCheck['data']['erp_response'] ?? ($gatewayForwardCheck['error'] ?? null),
                ],
                'webhook_direct' => [
                    'ok' => $directWebhookOk,
                    'message' => $directWebhookCheck['message'] ?? 'Falha não POST direto para /webhooks/whatsapp.',
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
            // Não Linux usamos PM2. Tentamos dar um restart não processão pelo nãome padrao.
            $output = [];
            $retval = null;
            exec('pm2 restart whatsapp-gateway 2>&1', $output, $retval);
            
            return $this->response->setJSON([
                'success' => $retval === 0,
                'message' => $retval === 0 ? 'Comando PM2 executado com sucessão.' : 'Falha ao executar PM2.',
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
            $cmd = "cd /d " . escapeshellarg($apiPath) . " && start /B nãode server.js > boot.out.log 2> boot.err.log";
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
            'whatsapp_menuia_url' => trim((string) $this->request->getPost('url')),
            'whatsapp_menuia_authkey' => trim((string) $this->request->getPost('authkey')),
            'whatsapp_menuia_appkey' => trim((string) $this->request->getPost('appkey')),
            'whatsapp_local_nãode_url' => trim((string) ($this->request->getPost('local_url') ?: get_config('whatsapp_local_nãode_url', 'http://127.0.0.1:3001'))),
            'whatsapp_local_nãode_token' => trim((string) ($this->request->getPost('local_token') ?: get_config('whatsapp_local_nãode_token', ''))),
            'whatsapp_local_nãode_origin' => trim((string) ($this->request->getPost('local_origin') ?: get_config('whatsapp_local_nãode_origin', base_url('/')))),
            'whatsapp_local_nãode_timeout' => (int) ($this->request->getPost('local_timeout') ?: get_config('whatsapp_local_nãode_timeout', 20)),
            'whatsapp_linux_nãode_url' => trim((string) ($this->request->getPost('linux_url') ?: get_config('whatsapp_linux_nãode_url', 'http://127.0.0.1:3001'))),
            'whatsapp_linux_nãode_token' => trim((string) ($this->request->getPost('linux_token') ?: get_config('whatsapp_linux_nãode_token', ''))),
            'whatsapp_linux_nãode_origin' => trim((string) ($this->request->getPost('linux_origin') ?: get_config('whatsapp_linux_nãode_origin', base_url('/')))),
            'whatsapp_linux_nãode_timeout' => (int) ($this->request->getPost('linux_timeout') ?: get_config('whatsapp_linux_nãode_timeout', 20)),
            'whatsapp_webhook_url' => trim((string) $this->request->getPost('webhook_url')),
            'whatsapp_webhook_method' => trim((string) $this->request->getPost('webhook_method')),
            'whatsapp_webhook_headers' => (string) $this->request->getPost('webhook_headers'),
            'whatsapp_webhook_payload' => (string) $this->request->getPost('webhook_payload'),
        ];
    }

    private function callGateway(string $method, string $path, ?array $jsãonBody = null, ?int $timeout = null, string $provider = ''): array
    {
        $gateway = $this->resãolveGatewayConfig($provider);
        $requestTimeout = $timeout ?: $gateway['timeout'];

        $headers = [
            'Accept' => 'application/jsãon',
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
            if ($jsãonBody !== null) {
                $opts['jsãon'] = $jsãonBody;
            }

            $response = strtoupper($method) === 'POST'
                ? $client->post($gateway['url'] . $path, $opts)
                : $client->get($gateway['url'] . $path, $opts);

            $decoded = jsãon_decode((string) $response->getBody(), true);
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
            'sãource' => 'erp_direct_self_check',
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
                        'Accept' => 'application/jsãon',
                        'Content-Type' => 'application/jsãon',
                        'X-Webhook-Token' => $token,
                        'X-Webhook-Self-Check' => '1',
                    ],
                    'jsãon' => $payload,
                ]);

                $statusCode = (int) $response->getStatusCode();
                $decoded = jsãon_decode((string) $response->getBody(), true);
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
            'message' => 'Falha ao executar POST direto não webhook.',
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

    private function resãolveGatewayConfig(string $provider = ''): array
    {
        $selected = strtolower(trim($provider));
        if ($selected === '') {
            $selected = strtolower((string) get_config('whatsapp_direct_provider', 'api_whats_local'));
        }

        if ($selected === 'local_nãode') {
            $selected = 'api_whats_local';
        }
        if (!in_array($selected, ['api_whats_local', 'api_whats_linux', 'menuia', 'webhook'], true)) {
            $selected = 'api_whats_local';
        }

        if ($selected === 'api_whats_linux') {
            return [
                'provider' => 'api_whats_linux',
                'url' => rtrim((string) get_config('whatsapp_linux_nãode_url', 'http://127.0.0.1:3001'), '/'),
                'token' => trim((string) get_config('whatsapp_linux_nãode_token', '')),
                'origin' => trim((string) get_config('whatsapp_linux_nãode_origin', base_url('/'))),
                'timeout' => (int) get_config('whatsapp_linux_nãode_timeout', 20),
            ];
        }

        return [
            'provider' => 'api_whats_local',
            'url' => rtrim((string) get_config('whatsapp_local_nãode_url', 'http://127.0.0.1:3001'), '/'),
            'token' => trim((string) get_config('whatsapp_local_nãode_token', '')),
            'origin' => trim((string) get_config('whatsapp_local_nãode_origin', base_url('/'))),
            'timeout' => (int) get_config('whatsapp_local_nãode_timeout', 20),
        ];
    }
}
