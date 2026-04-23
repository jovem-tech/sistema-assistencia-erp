<?php

namespace App\Controllers\Api\V1;

use App\Models\ClienteModel;
use Throwable;

class ClientsController extends BaseApiController
{
    public function index()
    {
        if ($permissionError = $this->ensurePermission('clientes', 'visualizar')) {
            return $permissionError;
        }

        $q = trim((string) $this->request->getGet('q'));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = max(1, min(100, (int) ($this->request->getGet('per_page') ?? 25)));
        $offset = ($page - 1) * $perPage;

        $model = new ClienteModel();
        $builder = $model->select('id, nome_razao, telefone1, telefone2, email, cidade, uf, created_at');

        if ($q !== '') {
            $builder->groupStart()
                ->like('nome_razao', $q)
                ->orLike('telefone1', $q)
                ->orLike('email', $q)
                ->groupEnd();
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults(false);
        $items = $builder->orderBy('id', 'DESC')->findAll($perPage, $offset);

        return $this->respondSuccess([
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => (int) $total,
                'total_pages' => (int) ceil((int) $total / $perPage),
            ],
        ]);
    }

    public function show($id = null)
    {
        if ($permissionError = $this->ensurePermission('clientes', 'visualizar')) {
            return $permissionError;
        }

        $clienteId = (int) $id;
        if ($clienteId <= 0) {
            return $this->respondError('Cliente invalido.', 422, 'CLIENT_INVALID_ID');
        }

        $item = (new ClienteModel())->find($clienteId);
        if (!$item) {
            return $this->respondError('Cliente nao encontrado.', 404, 'CLIENT_NOT_FOUND');
        }

        return $this->respondSuccess($item);
    }

    public function create()
    {
        if ($permissionError = $this->ensurePermission('clientes', 'criar')) {
            return $permissionError;
        }

        $payload = $this->payload();
        $nomeRazao = $this->normalizeNome((string) ($payload['nome_razao'] ?? ''));
        $telefone1 = trim((string) ($payload['telefone1'] ?? ''));

        $nomeLen = function_exists('mb_strlen') ? mb_strlen($nomeRazao, 'UTF-8') : strlen($nomeRazao);
        if ($nomeRazao === '' || $nomeLen < 3) {
            return $this->respondError(
                'Informe o nome do cliente com pelo menos 3 caracteres.',
                422,
                'CLIENT_CREATE_INVALID_NAME'
            );
        }

        if ($telefone1 === '') {
            return $this->respondError(
                'Informe o telefone principal do cliente.',
                422,
                'CLIENT_CREATE_INVALID_PHONE'
            );
        }

        $data = [
            'tipo_pessoa' => $this->normalizeTipoPessoa((string) ($payload['tipo_pessoa'] ?? '')),
            'nome_razao' => $nomeRazao,
            'cpf_cnpj' => $this->nullableString($payload['cpf_cnpj'] ?? null),
            'rg_ie' => $this->nullableString($payload['rg_ie'] ?? null),
            'email' => $this->nullableString($payload['email'] ?? null),
            'telefone1' => $telefone1,
            'telefone2' => $this->nullableString($payload['telefone2'] ?? null),
            'nome_contato' => $this->nullableString($payload['nome_contato'] ?? null),
            'telefone_contato' => $this->nullableString($payload['telefone_contato'] ?? null),
            'cep' => $this->nullableString($payload['cep'] ?? null),
            'endereco' => $this->nullableString($payload['endereco'] ?? null),
            'numero' => $this->nullableString($payload['numero'] ?? null),
            'complemento' => $this->nullableString($payload['complemento'] ?? null),
            'bairro' => $this->nullableString($payload['bairro'] ?? null),
            'cidade' => $this->nullableString($payload['cidade'] ?? null),
            'uf' => $this->nullableUf($payload['uf'] ?? null),
            'observacoes' => $this->nullableString($payload['observacoes'] ?? null),
        ];

        $model = new ClienteModel();

        try {
            $newId = (int) $model->insert($data, true);
            if ($newId <= 0) {
                return $this->respondError(
                    'Nao foi possivel criar o cliente.',
                    422,
                    'CLIENT_CREATE_FAILED',
                    $model->errors()
                );
            }

            $created = $model->find($newId);
            return $this->respondSuccess($created, 201);
        } catch (Throwable $e) {
            log_message('error', '[API V1][CLIENTS CREATE] ' . $e->getMessage());
            return $this->respondError(
                'Falha inesperada ao criar cliente.',
                500,
                'CLIENT_CREATE_UNEXPECTED'
            );
        }
    }

    public function update($id = null)
    {
        if ($permissionError = $this->ensurePermission('clientes', 'editar')) {
            return $permissionError;
        }

        $clienteId = (int) $id;
        if ($clienteId <= 0) {
            return $this->respondError('Cliente invalido.', 422, 'CLIENT_INVALID_ID');
        }

        $model = new ClienteModel();
        $existing = $model->find($clienteId);
        if (!$existing) {
            return $this->respondError('Cliente nao encontrado.', 404, 'CLIENT_NOT_FOUND');
        }

        $payload = $this->payload();
        $allowed = [];

        if (array_key_exists('tipo_pessoa', $payload)) {
            $allowed['tipo_pessoa'] = $this->normalizeTipoPessoa((string) ($payload['tipo_pessoa'] ?? ''));
        }
        if (array_key_exists('nome_razao', $payload)) {
            $nomeRazao = $this->normalizeNome((string) ($payload['nome_razao'] ?? ''));
            $nomeLen = function_exists('mb_strlen') ? mb_strlen($nomeRazao, 'UTF-8') : strlen($nomeRazao);
            if ($nomeRazao === '' || $nomeLen < 3) {
                return $this->respondError(
                    'Informe o nome do cliente com pelo menos 3 caracteres.',
                    422,
                    'CLIENT_UPDATE_INVALID_NAME'
                );
            }
            $allowed['nome_razao'] = $nomeRazao;
        }
        if (array_key_exists('telefone1', $payload)) {
            $telefone1 = trim((string) ($payload['telefone1'] ?? ''));
            if ($telefone1 === '') {
                return $this->respondError(
                    'O telefone principal do cliente nao pode ficar vazio.',
                    422,
                    'CLIENT_UPDATE_INVALID_PHONE'
                );
            }
            $allowed['telefone1'] = $telefone1;
        }

        foreach ([
            'cpf_cnpj',
            'rg_ie',
            'email',
            'telefone2',
            'nome_contato',
            'telefone_contato',
            'cep',
            'endereco',
            'numero',
            'complemento',
            'bairro',
            'cidade',
            'observacoes',
        ] as $field) {
            if (array_key_exists($field, $payload)) {
                $allowed[$field] = $this->nullableString($payload[$field] ?? null);
            }
        }

        if (array_key_exists('uf', $payload)) {
            $allowed['uf'] = $this->nullableUf($payload['uf'] ?? null);
        }

        if (empty($allowed)) {
            return $this->respondError(
                'Nenhum campo valido foi enviado para atualizacao do cliente.',
                422,
                'CLIENT_UPDATE_EMPTY'
            );
        }

        try {
            $updated = $model->update($clienteId, $allowed);
            if (!$updated) {
                return $this->respondError(
                    'Nao foi possivel atualizar o cliente.',
                    422,
                    'CLIENT_UPDATE_FAILED',
                    $model->errors()
                );
            }

            $fresh = $model->find($clienteId);
            return $this->respondSuccess($fresh);
        } catch (Throwable $e) {
            log_message('error', '[API V1][CLIENTS UPDATE] ' . $e->getMessage());
            return $this->respondError(
                'Falha inesperada ao atualizar cliente.',
                500,
                'CLIENT_UPDATE_UNEXPECTED'
            );
        }
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

    private function normalizeNome(string $nome): string
    {
        $nome = preg_replace('/\s+/u', ' ', trim($nome)) ?? '';
        if ($nome === '') {
            return '';
        }

        if (function_exists('mb_strtolower') && function_exists('mb_convert_case')) {
            return mb_convert_case(mb_strtolower($nome, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($nome));
    }

    private function normalizeTipoPessoa(string $tipo): string
    {
        $value = strtolower(trim($tipo));
        return in_array($value, ['pf', 'pj'], true) ? $value : 'pf';
    }

    /**
     * @param mixed $value
     */
    private function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        return $text !== '' ? $text : null;
    }

    /**
     * @param mixed $value
     */
    private function nullableUf($value): ?string
    {
        $text = strtoupper(trim((string) $value));
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, 2);
    }
}
