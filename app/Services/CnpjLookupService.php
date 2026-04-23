<?php

namespace App\Services;

class CnpjLookupService
{
    private const CACHE_TTL_SECONDS = 86400;

    /**
     * @var array<int, array<string, string>>
     */
    private array $providers = [
        [
            'name' => 'brasilapi',
            'url' => 'https://brasilapi.com.br/api/cnpj/v1/',
        ],
        [
            'name' => 'cnpjws_publica',
            'url' => 'https://publica.cnpj.ws/cnpj/',
        ],
    ];

    public function lookup(string $cnpj): array
    {
        $cnpjDigits = preg_replace('/\D+/', '', $cnpj) ?? '';

        if (strlen($cnpjDigits) !== 14) {
            return [
                'success' => false,
                'status' => 'validation_error',
                'message' => 'Informe um CNPJ valido com 14 digitos.',
            ];
        }

        $cacheKey = 'cnpj_lookup_' . $cnpjDigits;
        $cache = cache();
        $cached = $cache ? $cache->get($cacheKey) : null;
        if (is_array($cached) && ($cached['success'] ?? false) === true) {
            return $cached;
        }

        $notFoundCount = 0;
        $rateLimitedCount = 0;
        $lastErrorMessage = 'Nao foi possivel consultar o CNPJ agora. Voce pode continuar o cadastro manualmente.';

        foreach ($this->providers as $provider) {
            $result = $this->queryProvider($provider, $cnpjDigits);

            if (($result['success'] ?? false) === true) {
                if ($cache) {
                    $cache->save($cacheKey, $result, self::CACHE_TTL_SECONDS);
                }

                return $result;
            }

            $status = (string) ($result['status'] ?? '');
            $lastErrorMessage = (string) ($result['message'] ?? $lastErrorMessage);

            if ($status === 'not_found') {
                $notFoundCount++;
                continue;
            }

            if ($status === 'rate_limited') {
                $rateLimitedCount++;
                continue;
            }
        }

        if ($notFoundCount === count($this->providers)) {
            return [
                'success' => false,
                'status' => 'not_found',
                'message' => 'Nao encontramos dados publicos para este CNPJ.',
            ];
        }

        if ($rateLimitedCount === count($this->providers)) {
            return [
                'success' => false,
                'status' => 'rate_limited',
                'message' => 'Os provedores publicos de CNPJ atingiram o limite de consultas temporariamente. Aguarde um minuto e tente novamente, ou siga com o cadastro manual.',
            ];
        }

        return [
            'success' => false,
            'status' => 'provider_unreachable',
            'message' => $lastErrorMessage,
        ];
    }

    /**
     * @param array<string, string> $provider
     */
    private function queryProvider(array $provider, string $cnpjDigits): array
    {
        $client = \Config\Services::curlrequest();
        $providerName = (string) ($provider['name'] ?? 'unknown');
        $providerUrl = (string) ($provider['url'] ?? '');

        try {
            $response = $client->get($providerUrl . $cnpjDigits, [
                'timeout' => 12,
                'http_errors' => false,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[CNPJ Lookup] Falha de comunicacao com o provedor {provider}: {message}', [
                'provider' => $providerName,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'provider_unreachable',
                'message' => 'Nao foi possivel consultar o CNPJ agora. Voce pode continuar o cadastro manualmente.',
            ];
        }

        $statusCode = (int) $response->getStatusCode();
        $body = (string) $response->getBody();
        $payload = json_decode($body, true);

        if ($statusCode === 404) {
            return [
                'success' => false,
                'status' => 'not_found',
                'message' => 'Nao encontramos dados publicos para este CNPJ.',
            ];
        }

        if ($statusCode === 429) {
            log_message('warning', '[CNPJ Lookup] Provedor {provider} com limite temporario. HTTP 429. Body: {body}', [
                'provider' => $providerName,
                'body' => $body,
            ]);

            return [
                'success' => false,
                'status' => 'rate_limited',
                'message' => 'O provedor publico consultado atingiu o limite de requisicoes temporariamente.',
            ];
        }

        if ($statusCode < 200 || $statusCode >= 300 || !is_array($payload)) {
            log_message('error', '[CNPJ Lookup] Resposta invalida do provedor {provider}. HTTP {status}. Body: {body}', [
                'provider' => $providerName,
                'status' => $statusCode,
                'body' => $body,
            ]);

            return [
                'success' => false,
                'status' => 'invalid_response',
                'message' => 'O servico de consulta de CNPJ retornou uma resposta invalida. Tente novamente em instantes.',
            ];
        }

        $mapped = $this->mapPayload($providerName, $cnpjDigits, $payload);
        if ($mapped === null) {
            log_message('error', '[CNPJ Lookup] Payload sem estrutura suportada do provedor {provider}. Body: {body}', [
                'provider' => $providerName,
                'body' => $body,
            ]);

            return [
                'success' => false,
                'status' => 'invalid_response',
                'message' => 'O provedor de consulta retornou dados em formato inesperado.',
            ];
        }

        return [
            'success' => true,
            'status' => 'ok',
            'message' => 'CNPJ localizado com sucesso.',
            'provider' => $providerName,
            'data' => $mapped,
        ];
    }

    private function mapPayload(string $providerName, string $cnpjDigits, array $payload): ?array
    {
        return match ($providerName) {
            'brasilapi' => $this->mapBrasilApiPayload($cnpjDigits, $payload),
            'cnpjws_publica' => $this->mapCnpjWsPayload($cnpjDigits, $payload),
            default => null,
        };
    }

    private function mapBrasilApiPayload(string $cnpjDigits, array $payload): array
    {
        return [
            'cnpj' => $cnpjDigits,
            'razao_social' => trim((string) ($payload['razao_social'] ?? '')),
            'nome_fantasia' => trim((string) ($payload['nome_fantasia'] ?? '')),
            'email' => $this->normalizeEmail((string) ($payload['email'] ?? '')),
            'telefone1' => $this->normalizeDigits((string) ($payload['ddd_telefone_1'] ?? '')),
            'telefone2' => $this->normalizeDigits((string) ($payload['ddd_telefone_2'] ?? '')),
            'cep' => $this->normalizeDigits((string) ($payload['cep'] ?? '')),
            'endereco' => trim((string) ($payload['logradouro'] ?? '')),
            'numero' => trim((string) ($payload['numero'] ?? '')),
            'complemento' => trim((string) ($payload['complemento'] ?? '')),
            'bairro' => trim((string) ($payload['bairro'] ?? '')),
            'cidade' => trim((string) ($payload['municipio'] ?? '')),
            'uf' => strtoupper(trim((string) ($payload['uf'] ?? ''))),
            'situacao_cadastral' => trim((string) ($payload['descricao_situacao_cadastral'] ?? '')),
        ];
    }

    private function mapCnpjWsPayload(string $cnpjDigits, array $payload): ?array
    {
        $estabelecimento = isset($payload['estabelecimento']) && is_array($payload['estabelecimento'])
            ? $payload['estabelecimento']
            : null;

        if ($estabelecimento === null) {
            return null;
        }

        $telefone1 = $this->joinPhone(
            (string) ($estabelecimento['ddd1'] ?? ''),
            (string) ($estabelecimento['telefone1'] ?? '')
        );
        $telefone2 = $this->joinPhone(
            (string) ($estabelecimento['ddd2'] ?? ''),
            (string) ($estabelecimento['telefone2'] ?? '')
        );
        $tipoLogradouro = trim((string) ($estabelecimento['tipo_logradouro'] ?? ''));
        $logradouro = trim((string) ($estabelecimento['logradouro'] ?? ''));

        return [
            'cnpj' => $cnpjDigits,
            'razao_social' => trim((string) ($payload['razao_social'] ?? '')),
            'nome_fantasia' => trim((string) ($estabelecimento['nome_fantasia'] ?? '')),
            'email' => $this->normalizeEmail((string) ($estabelecimento['email'] ?? '')),
            'telefone1' => $telefone1,
            'telefone2' => $telefone2,
            'cep' => $this->normalizeDigits((string) ($estabelecimento['cep'] ?? '')),
            'endereco' => trim(trim($tipoLogradouro . ' ' . $logradouro)),
            'numero' => trim((string) ($estabelecimento['numero'] ?? '')),
            'complemento' => trim((string) ($estabelecimento['complemento'] ?? '')),
            'bairro' => trim((string) ($estabelecimento['bairro'] ?? '')),
            'cidade' => trim((string) (($estabelecimento['cidade']['nome'] ?? '') ?: '')),
            'uf' => strtoupper(trim((string) (($estabelecimento['estado']['sigla'] ?? '') ?: ''))),
            'situacao_cadastral' => trim((string) ($estabelecimento['situacao_cadastral'] ?? '')),
        ];
    }

    private function normalizeDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function normalizeEmail(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }

    private function joinPhone(string $ddd, string $number): string
    {
        return $this->normalizeDigits($ddd . $number);
    }
}
