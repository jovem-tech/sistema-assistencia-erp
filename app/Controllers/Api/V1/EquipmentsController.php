<?php

namespace App\Controllers\Api\V1;

use App\Models\ClienteModel;
use App\Models\EquipamentoClienteModel;
use App\Models\EquipamentoFotoModel;
use App\Models\EquipamentoMarcaModel;
use App\Models\EquipamentoModeloModel;
use App\Models\EquipamentoModel;
use App\Models\EquipamentoTipoModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use Throwable;

class EquipmentsController extends BaseApiController
{
    private const MAX_FOTOS_POR_EQUIPAMENTO = 4;
    private const MAX_FOTO_BYTES = 2097152; // 2MB
    private const FOTO_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function catalog()
    {
        if ($permissionError = $this->ensurePermission('equipamentos', 'visualizar')) {
            return $permissionError;
        }

        $tipoId = max(0, (int) ($this->request->getGet('tipo_id') ?? 0));
        $marcaId = max(0, (int) ($this->request->getGet('marca_id') ?? 0));

        $tipos = (new EquipamentoTipoModel())
            ->select('id, nome, ativo')
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->findAll();

        $marcasBuilder = (new EquipamentoMarcaModel())
            ->select('equipamentos_marcas.id, equipamentos_marcas.nome, equipamentos_marcas.ativo')
            ->where('equipamentos_marcas.ativo', 1);

        $marcas = [];
        if ($tipoId > 0) {
            $brandRows = (new EquipamentoModel())
                ->select('marca_id')
                ->distinct()
                ->where('tipo_id', $tipoId)
                ->where('marca_id >', 0)
                ->findAll();

            $brandIds = array_values(array_unique(array_filter(array_map(
                static fn(array $row): int => (int) ($row['marca_id'] ?? 0),
                $brandRows
            ))));

            if (!empty($brandIds)) {
                $marcas = $marcasBuilder
                    ->whereIn('equipamentos_marcas.id', $brandIds)
                    ->orderBy('equipamentos_marcas.nome', 'ASC')
                    ->findAll();
            }
        } else {
            $marcas = $marcasBuilder
                ->orderBy('equipamentos_marcas.nome', 'ASC')
                ->findAll();
        }

        $modelosBuilder = (new EquipamentoModeloModel())
            ->select('equipamentos_modelos.id, equipamentos_modelos.marca_id, equipamentos_modelos.nome, equipamentos_modelos.ativo')
            ->where('equipamentos_modelos.ativo', 1);

        if ($marcaId > 0) {
            $modelosBuilder->where('equipamentos_modelos.marca_id', $marcaId);
        }

        $modelos = [];
        if ($tipoId > 0) {
            $modelRows = (new EquipamentoModel())
                ->select('modelo_id')
                ->distinct()
                ->where('tipo_id', $tipoId)
                ->where('modelo_id >', 0);

            if ($marcaId > 0) {
                $modelRows->where('marca_id', $marcaId);
            }

            $modelIds = array_values(array_unique(array_filter(array_map(
                static fn(array $row): int => (int) ($row['modelo_id'] ?? 0),
                $modelRows->findAll()
            ))));

            if (!empty($modelIds)) {
                $modelos = $modelosBuilder
                    ->whereIn('equipamentos_modelos.id', $modelIds)
                    ->orderBy('equipamentos_modelos.nome', 'ASC')
                    ->findAll();
            }
        } else {
            $modelos = $modelosBuilder
                ->orderBy('equipamentos_modelos.nome', 'ASC')
                ->findAll();
        }

        return $this->respondSuccess([
            'tipos' => $tipos,
            'marcas' => $marcas,
            'modelos' => $modelos,
        ]);
    }

    public function show($id = null)
    {
        if ($permissionError = $this->ensurePermission('equipamentos', 'visualizar')) {
            return $permissionError;
        }

        $equipamentoId = (int) $id;
        if ($equipamentoId <= 0) {
            return $this->respondError('Equipamento invalido.', 422, 'EQUIPMENT_INVALID_ID');
        }

        $item = (new EquipamentoModel())->getWithCliente($equipamentoId);
        if (!$item) {
            return $this->respondError('Equipamento nao encontrado.', 404, 'EQUIPMENT_NOT_FOUND');
        }

        return $this->respondSuccess($this->composeEquipmentResponse($equipamentoId, $item));
    }

    public function createBrand()
    {
        if ($permissionError = $this->ensurePermission('equipamentos', 'criar')) {
            return $permissionError;
        }

        $payload = $this->payload();
        $nome = trim((string) ($payload['nome'] ?? ''));
        if ($nome === '') {
            return $this->respondError('Informe o nome da marca.', 422, 'EQUIPMENT_BRAND_NAME_REQUIRED');
        }

        $model = new EquipamentoMarcaModel();
        $existing = $model->where('nome', $nome)->first();
        if ($existing) {
            return $this->respondSuccess([
                'id' => (int) ($existing['id'] ?? 0),
                'nome' => (string) ($existing['nome'] ?? $nome),
                'ativo' => (int) ($existing['ativo'] ?? 1),
            ]);
        }

        $id = (int) $model->insert([
            'nome' => $nome,
            'ativo' => 1,
        ], true);

        if ($id <= 0) {
            return $this->respondError('Nao foi possivel cadastrar a marca.', 422, 'EQUIPMENT_BRAND_CREATE_FAILED', $model->errors());
        }

        return $this->respondSuccess([
            'id' => $id,
            'nome' => $nome,
            'ativo' => 1,
        ], 201);
    }

    public function createModel()
    {
        if ($permissionError = $this->ensurePermission('equipamentos', 'criar')) {
            return $permissionError;
        }

        $payload = $this->payload();
        $marcaId = max(0, (int) ($payload['marca_id'] ?? 0));
        $nome = trim((string) ($payload['nome'] ?? ''));

        if ($marcaId <= 0) {
            return $this->respondError('Selecione a marca antes de cadastrar o modelo.', 422, 'EQUIPMENT_MODEL_BRAND_REQUIRED');
        }
        if (!(new EquipamentoMarcaModel())->find($marcaId)) {
            return $this->respondError('Marca informada nao foi encontrada.', 404, 'EQUIPMENT_MODEL_BRAND_NOT_FOUND');
        }
        if ($nome === '') {
            return $this->respondError('Informe o nome do modelo.', 422, 'EQUIPMENT_MODEL_NAME_REQUIRED');
        }

        $model = new EquipamentoModeloModel();
        $existing = $model
            ->where('marca_id', $marcaId)
            ->where('nome', $nome)
            ->first();
        if ($existing) {
            return $this->respondSuccess([
                'id' => (int) ($existing['id'] ?? 0),
                'marca_id' => $marcaId,
                'nome' => (string) ($existing['nome'] ?? $nome),
                'ativo' => (int) ($existing['ativo'] ?? 1),
            ]);
        }

        $id = (int) $model->insert([
            'marca_id' => $marcaId,
            'nome' => $nome,
            'ativo' => 1,
        ], true);

        if ($id <= 0) {
            return $this->respondError('Nao foi possivel cadastrar o modelo.', 422, 'EQUIPMENT_MODEL_CREATE_FAILED', $model->errors());
        }

        return $this->respondSuccess([
            'id' => $id,
            'marca_id' => $marcaId,
            'nome' => $nome,
            'ativo' => 1,
        ], 201);
    }

    public function create()
    {
        if ($permissionError = $this->ensurePermission('equipamentos', 'criar')) {
            return $permissionError;
        }

        $payload = $this->payload();
        $clienteId = max(0, (int) ($payload['cliente_id'] ?? 0));
        if ($clienteId <= 0) {
            return $this->respondError('Selecione um cliente para o equipamento.', 422, 'EQUIPMENT_CREATE_CLIENT_REQUIRED');
        }

        if (!(new ClienteModel())->find($clienteId)) {
            return $this->respondError('Cliente informado nao foi encontrado.', 404, 'EQUIPMENT_CREATE_CLIENT_NOT_FOUND');
        }

        $tipoId = max(0, (int) ($payload['tipo_id'] ?? 0));
        if ($tipoId <= 0) {
            return $this->respondError('Selecione o tipo do equipamento.', 422, 'EQUIPMENT_CREATE_TYPE_REQUIRED');
        }
        if (!(new EquipamentoTipoModel())->find($tipoId)) {
            return $this->respondError('Tipo de equipamento nao encontrado.', 404, 'EQUIPMENT_CREATE_TYPE_NOT_FOUND');
        }

        $marcaId = $this->resolveBrandId($payload);
        if ($marcaId <= 0) {
            return $this->respondError('Selecione ou informe a marca do equipamento.', 422, 'EQUIPMENT_CREATE_BRAND_REQUIRED');
        }

        $modeloId = $this->resolveModelId($payload, $marcaId);
        if ($modeloId <= 0) {
            return $this->respondError('Selecione ou informe o modelo do equipamento.', 422, 'EQUIPMENT_CREATE_MODEL_REQUIRED');
        }

        $cor = $this->nullableString($payload['cor'] ?? null);
        $corHex = $this->normalizeHexColor((string) ($payload['cor_hex'] ?? ''));
        if ($cor === null || $corHex === null) {
            return $this->respondError('Informe a cor correta do equipamento.', 422, 'EQUIPMENT_CREATE_COLOR_REQUIRED');
        }

        $uploadedFiles = $this->collectUploadedFotos();
        $photosValidation = $this->validateUploadedFotos($uploadedFiles, 0);
        if (!$photosValidation['ok']) {
            log_message('warning', '[API V1][EQUIPMENTS CREATE] validacao de fotos falhou: ' . json_encode([
                'cliente_id' => $clienteId,
                'tipo_id' => $tipoId,
                'marca_id' => $marcaId,
                'modelo_id' => $modeloId,
                'uploaded_files' => count($uploadedFiles),
                'message' => (string) ($photosValidation['message'] ?? ''),
            ], JSON_UNESCAPED_UNICODE));
            return $this->respondError(
                (string) ($photosValidation['message'] ?? 'Falha na validacao das fotos do equipamento.'),
                422,
                'EQUIPMENT_CREATE_PHOTO_REQUIRED'
            );
        }

        $data = [
            'cliente_id' => $clienteId,
            'tipo_id' => $tipoId,
            'marca_id' => $marcaId,
            'modelo_id' => $modeloId,
            'cor' => $cor,
            'cor_hex' => $corHex,
            'cor_rgb' => $this->resolveRgbPayload($payload['cor_rgb'] ?? null, $corHex),
            'numero_serie' => $this->nullableString($payload['numero_serie'] ?? null),
            'imei' => $this->nullableString($payload['imei'] ?? null),
            'senha_acesso' => $this->nullableString($payload['senha_acesso'] ?? null),
            'estado_fisico' => $this->nullableString($payload['estado_fisico'] ?? null),
            'acessorios' => $this->nullableString($payload['acessorios'] ?? null),
            'observacoes' => $this->nullableString($payload['observacoes'] ?? null),
        ];

        $model = new EquipamentoModel();

        try {
            $newId = (int) $model->insert($data, true);
            if ($newId <= 0) {
                log_message('warning', '[API V1][EQUIPMENTS CREATE] insert falhou: ' . json_encode([
                    'payload' => $data,
                    'errors' => $model->errors(),
                ], JSON_UNESCAPED_UNICODE));
                return $this->respondError(
                    'Nao foi possivel cadastrar o equipamento.',
                    422,
                    'EQUIPMENT_CREATE_FAILED',
                    $model->errors()
                );
            }

            $uploadResult = $this->appendEquipamentoFotos($newId, $uploadedFiles);
            $created = $model->getWithCliente($newId);
            $response = $this->composeEquipmentResponse($newId, $created);
            if (!empty($uploadResult['warning'])) {
                $response['warning'] = $uploadResult['warning'];
            }

            return $this->respondSuccess($response, 201);
        } catch (Throwable $e) {
            log_message('error', '[API V1][EQUIPMENTS CREATE] ' . $e->getMessage());
            return $this->respondError(
                'Falha inesperada ao cadastrar equipamento.',
                500,
                'EQUIPMENT_CREATE_UNEXPECTED'
            );
        }
    }

    public function update($id = null)
    {
        if ($permissionError = $this->ensurePermission('equipamentos', 'editar')) {
            return $permissionError;
        }

        $equipamentoId = (int) $id;
        if ($equipamentoId <= 0) {
            return $this->respondError('Equipamento invalido.', 422, 'EQUIPMENT_INVALID_ID');
        }

        $model = new EquipamentoModel();
        $existing = $model->find($equipamentoId);
        if (!$existing) {
            return $this->respondError('Equipamento nao encontrado.', 404, 'EQUIPMENT_NOT_FOUND');
        }

        $payload = $this->payload();
        $allowed = [];

        if (array_key_exists('cliente_id', $payload)) {
            $clienteId = max(0, (int) ($payload['cliente_id'] ?? 0));
            if ($clienteId <= 0) {
                return $this->respondError('Cliente invalido para o equipamento.', 422, 'EQUIPMENT_UPDATE_CLIENT_REQUIRED');
            }
            if (!(new ClienteModel())->find($clienteId)) {
                return $this->respondError('Cliente informado nao foi encontrado.', 404, 'EQUIPMENT_UPDATE_CLIENT_NOT_FOUND');
            }
            $allowed['cliente_id'] = $clienteId;
        }

        if (array_key_exists('tipo_id', $payload)) {
            $tipoId = max(0, (int) ($payload['tipo_id'] ?? 0));
            if ($tipoId <= 0) {
                return $this->respondError('Tipo invalido para o equipamento.', 422, 'EQUIPMENT_UPDATE_TYPE_REQUIRED');
            }
            if (!(new EquipamentoTipoModel())->find($tipoId)) {
                return $this->respondError('Tipo de equipamento nao encontrado.', 404, 'EQUIPMENT_UPDATE_TYPE_NOT_FOUND');
            }
            $allowed['tipo_id'] = $tipoId;
        }

        $needsBrandResolution = array_key_exists('marca_id', $payload) || array_key_exists('marca_nome', $payload);
        if ($needsBrandResolution) {
            $marcaId = $this->resolveBrandId($payload);
            if ($marcaId <= 0) {
                return $this->respondError('Selecione ou informe a marca do equipamento.', 422, 'EQUIPMENT_UPDATE_BRAND_REQUIRED');
            }
            $allowed['marca_id'] = $marcaId;
        }

        $needsModelResolution = array_key_exists('modelo_id', $payload) || array_key_exists('modelo_nome', $payload);
        if ($needsModelResolution) {
            $marcaIdForModel = (int) ($allowed['marca_id'] ?? $existing['marca_id'] ?? 0);
            if ($marcaIdForModel <= 0) {
                return $this->respondError('Informe a marca antes de definir o modelo.', 422, 'EQUIPMENT_UPDATE_MODEL_BRAND_REQUIRED');
            }

            $modeloId = $this->resolveModelId($payload, $marcaIdForModel);
            if ($modeloId <= 0) {
                return $this->respondError('Selecione ou informe o modelo do equipamento.', 422, 'EQUIPMENT_UPDATE_MODEL_REQUIRED');
            }
            $allowed['modelo_id'] = $modeloId;
        }

        foreach (['numero_serie', 'imei', 'senha_acesso', 'estado_fisico', 'acessorios', 'observacoes'] as $field) {
            if (array_key_exists($field, $payload)) {
                $allowed[$field] = $this->nullableString($payload[$field] ?? null);
            }
        }

        $corValue = array_key_exists('cor', $payload)
            ? $this->nullableString($payload['cor'] ?? null)
            : $this->nullableString($existing['cor'] ?? null);
        $corHexValue = array_key_exists('cor_hex', $payload)
            ? $this->normalizeHexColor((string) ($payload['cor_hex'] ?? ''))
            : $this->normalizeHexColor((string) ($existing['cor_hex'] ?? ''));
        if ($corValue === null || $corHexValue === null) {
            return $this->respondError('Informe a cor correta do equipamento.', 422, 'EQUIPMENT_UPDATE_COLOR_REQUIRED');
        }

        if (array_key_exists('cor', $payload)) {
            $allowed['cor'] = $corValue;
        }
        if (array_key_exists('cor_hex', $payload)) {
            $allowed['cor_hex'] = $corHexValue;
        }
        if (array_key_exists('cor_rgb', $payload)) {
            $allowed['cor_rgb'] = $this->resolveRgbPayload($payload['cor_rgb'] ?? null, $corHexValue);
        } elseif (array_key_exists('cor_hex', $payload)) {
            $allowed['cor_rgb'] = $this->resolveRgbPayload(null, $corHexValue);
        }

        $uploadedFiles = $this->collectUploadedFotos();
        $photosValidation = $this->validateUploadedFotos($uploadedFiles, $equipamentoId);
        if (!$photosValidation['ok']) {
            return $this->respondError(
                (string) ($photosValidation['message'] ?? 'Falha na validacao das fotos do equipamento.'),
                422,
                'EQUIPMENT_UPDATE_PHOTO_REQUIRED'
            );
        }

        if (empty($allowed) && empty($uploadedFiles)) {
            return $this->respondError(
                'Nenhum campo valido foi enviado para atualizar o equipamento.',
                422,
                'EQUIPMENT_UPDATE_EMPTY'
            );
        }

        try {
            if (!empty($allowed)) {
                $updated = $model->update($equipamentoId, $allowed);
                if (!$updated) {
                    return $this->respondError(
                        'Nao foi possivel atualizar o equipamento.',
                        422,
                        'EQUIPMENT_UPDATE_FAILED',
                        $model->errors()
                    );
                }
            }

            $uploadResult = $this->appendEquipamentoFotos($equipamentoId, $uploadedFiles);
            $fresh = $model->getWithCliente($equipamentoId);
            $response = $this->composeEquipmentResponse($equipamentoId, $fresh);
            if (!empty($uploadResult['warning'])) {
                $response['warning'] = $uploadResult['warning'];
            }

            return $this->respondSuccess($response);
        } catch (Throwable $e) {
            log_message('error', '[API V1][EQUIPMENTS UPDATE] ' . $e->getMessage());
            return $this->respondError(
                'Falha inesperada ao atualizar equipamento.',
                500,
                'EQUIPMENT_UPDATE_UNEXPECTED'
            );
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(): array
    {
        $contentType = strtolower((string) $this->request->getHeaderLine('Content-Type'));
        if (strpos($contentType, 'application/json') !== false) {
            try {
                $json = $this->request->getJSON(true);
                if (is_array($json) && !empty($json)) {
                    return $json;
                }
            } catch (Throwable $e) {
                log_message('warning', '[API V1][EQUIPMENTS PAYLOAD] JSON invalido ignorado: ' . $e->getMessage());
            }
        }

        $post = $this->request->getPost();
        if (is_array($post) && !empty($post)) {
            return $post;
        }

        $raw = $this->request->getRawInput();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }

        return [];
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function resolveBrandId(array $payload): int
    {
        $marcaId = max(0, (int) ($payload['marca_id'] ?? 0));
        $brandModel = new EquipamentoMarcaModel();

        if ($marcaId > 0) {
            return $brandModel->find($marcaId) ? $marcaId : 0;
        }

        $marcaNome = trim((string) ($payload['marca_nome'] ?? ''));
        if ($marcaNome === '') {
            return 0;
        }

        $existing = $brandModel->where('nome', $marcaNome)->first();
        if ($existing) {
            return (int) ($existing['id'] ?? 0);
        }

        $brandModel->insert([
            'nome' => $marcaNome,
            'ativo' => 1,
        ]);
        return (int) $brandModel->getInsertID();
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function resolveModelId(array $payload, int $marcaId): int
    {
        $modeloId = max(0, (int) ($payload['modelo_id'] ?? 0));
        $modelModel = new EquipamentoModeloModel();

        if ($modeloId > 0) {
            $existing = $modelModel->find($modeloId);
            if (!$existing) {
                return 0;
            }

            if ((int) ($existing['marca_id'] ?? 0) !== $marcaId) {
                return 0;
            }

            return $modeloId;
        }

        $modeloNome = trim((string) ($payload['modelo_nome'] ?? ''));
        if ($modeloNome === '') {
            return 0;
        }

        $existing = $modelModel
            ->where('marca_id', $marcaId)
            ->where('nome', $modeloNome)
            ->first();
        if ($existing) {
            return (int) ($existing['id'] ?? 0);
        }

        $modelModel->insert([
            'marca_id' => $marcaId,
            'nome' => $modeloNome,
            'ativo' => 1,
        ]);

        return (int) $modelModel->getInsertID();
    }

    /**
     * @return array<int,UploadedFile>
     */
    private function collectUploadedFotos(): array
    {
        $files = [];

        foreach (['fotos', 'fotos[]'] as $field) {
            $multi = $this->request->getFileMultiple($field);
            if (!is_array($multi)) {
                continue;
            }
            foreach ($multi as $file) {
                if ($file instanceof UploadedFile && $file->isValid() && !$file->hasMoved()) {
                    $files[] = $file;
                }
            }
        }

        if (empty($files)) {
            $single = $this->request->getFile('foto_perfil');
            if ($single instanceof UploadedFile && $single->isValid() && !$single->hasMoved()) {
                $files[] = $single;
            }
        }

        return $files;
    }

    /**
     * @param array<int,UploadedFile> $uploadedFiles
     * @return array{ok:bool,message?:string}
     */
    private function validateUploadedFotos(array $uploadedFiles, int $equipamentoId = 0): array
    {
        $existingCount = $this->countExistingPhotos($equipamentoId);
        if (($existingCount + count($uploadedFiles)) <= 0) {
            return [
                'ok' => false,
                'message' => 'Adicione ao menos uma foto do equipamento.',
            ];
        }

        foreach ($uploadedFiles as $file) {
            if (!$file->isValid() || $file->hasMoved()) {
                return [
                    'ok' => false,
                    'message' => 'Uma das fotos enviadas e invalida.',
                ];
            }

            $mimeType = strtolower((string) $file->getMimeType());
            if (!in_array($mimeType, self::FOTO_MIME_TYPES, true)) {
                return [
                    'ok' => false,
                    'message' => 'Formato de foto nao suportado. Use JPG, PNG ou WEBP.',
                ];
            }

            $size = (int) ($file->getSize() ?? 0);
            if ($size > self::MAX_FOTO_BYTES) {
                return [
                    'ok' => false,
                    'message' => 'Cada foto deve ter no maximo 2MB.',
                ];
            }
        }

        return ['ok' => true];
    }

    private function countExistingPhotos(int $equipamentoId): int
    {
        if ($equipamentoId <= 0) {
            return 0;
        }

        return (int) (new EquipamentoFotoModel())
            ->where('equipamento_id', $equipamentoId)
            ->countAllResults();
    }

    /**
     * @param array<int,UploadedFile> $uploadedFiles
     * @return array{warning:?string,principal_url:?string}
     */
    private function appendEquipamentoFotos(int $equipamentoId, array $uploadedFiles): array
    {
        if ($equipamentoId <= 0 || empty($uploadedFiles)) {
            return ['warning' => null, 'principal_url' => null];
        }

        $this->normalizeEquipamentoFotosStorage($equipamentoId);
        $fotoModel = new EquipamentoFotoModel();
        $existingCount = $this->countExistingPhotos($equipamentoId);
        $availableSlots = max(0, self::MAX_FOTOS_POR_EQUIPAMENTO - $existingCount);
        if ($availableSlots <= 0) {
            return [
                'warning' => 'Este equipamento ja possui 4 fotos. Remova uma foto antes de adicionar outra.',
                'principal_url' => null,
            ];
        }

        $warning = null;
        if (count($uploadedFiles) > $availableSlots) {
            $warning = "Somente {$availableSlots} foto(s) foram adicionadas para manter o limite de 4 por equipamento.";
        }

        $filesToPersist = array_slice($uploadedFiles, 0, $availableSlots);
        if (empty($filesToPersist)) {
            return ['warning' => $warning, 'principal_url' => null];
        }

        $folderName = $this->buildEquipamentoPerfilFolderName($equipamentoId);
        $targetDir = $this->ensurePerfilFolder($folderName);
        $nextIndex = $this->getNextPerfilIndex($targetDir);

        $hasPrincipal = $fotoModel
            ->where('equipamento_id', $equipamentoId)
            ->where('is_principal', 1)
            ->first();
        $isPrincipalFlag = $hasPrincipal ? 0 : 1;

        $principalUrl = null;
        foreach ($filesToPersist as $file) {
            $ext = strtolower((string) $file->getExtension());
            if ($ext === '') {
                $ext = 'jpg';
            }

            $newName = "perfil_{$nextIndex}.{$ext}";
            while (is_file($targetDir . DIRECTORY_SEPARATOR . $newName)) {
                $nextIndex++;
                $newName = "perfil_{$nextIndex}.{$ext}";
            }

            $file->move($targetDir, $newName);
            $relativePath = $folderName . '/' . $newName;
            $fotoModel->insert([
                'equipamento_id' => $equipamentoId,
                'arquivo' => $relativePath,
                'is_principal' => $isPrincipalFlag,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($isPrincipalFlag === 1) {
                $principalUrl = $this->buildFotoPublicUrl($relativePath);
            }

            $isPrincipalFlag = 0;
            $nextIndex++;
        }

        return [
            'warning' => $warning,
            'principal_url' => $principalUrl,
        ];
    }

    private function ensurePerfilFolder(string $folderName): string
    {
        $baseDir = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil';
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
        }

        $folderName = trim(str_replace(['\\', '/'], '-', $folderName), '-');
        if ($folderName === '') {
            $folderName = 'equipamento-cliente';
        }

        $target = $baseDir . DIRECTORY_SEPARATOR . $folderName;
        if (!is_dir($target)) {
            @mkdir($target, 0775, true);
        }

        return $target;
    }

    private function getNextPerfilIndex(string $dirAbs): int
    {
        if (!is_dir($dirAbs)) {
            return 1;
        }

        $max = 0;
        foreach (scandir($dirAbs) ?: [] as $item) {
            if (!preg_match('/^perfil_(\d+)\.(jpg|jpeg|png|webp)$/i', (string) $item, $match)) {
                continue;
            }
            $index = (int) ($match[1] ?? 0);
            if ($index > $max) {
                $max = $index;
            }
        }

        return $max + 1;
    }

    private function buildFotoPublicUrl(string $arquivo): string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        if ($arquivo === '') {
            return $this->missingImageDataUri();
        }

        $pathPerfil = $this->buildPerfilAbsolutePath($arquivo);
        if (is_file($pathPerfil)) {
            return base_url('uploads/equipamentos_perfil/' . $arquivo);
        }

        $legacyPerfil = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . basename($arquivo);
        if (is_file($legacyPerfil)) {
            return base_url('uploads/equipamentos_perfil/' . basename($arquivo));
        }

        $legacyUploadPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos' . DIRECTORY_SEPARATOR . basename($arquivo);
        if (is_file($legacyUploadPath)) {
            return base_url('uploads/equipamentos/' . basename($arquivo));
        }

        return $this->missingImageDataUri();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getHydratedFotosByEquipamentoId(int $equipamentoId): array
    {
        if ($equipamentoId <= 0) {
            return [];
        }

        $fotos = (new EquipamentoFotoModel())
            ->where('equipamento_id', $equipamentoId)
            ->orderBy('is_principal', 'DESC')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($fotos as &$foto) {
            $foto['arquivo'] = str_replace('\\', '/', (string) ($foto['arquivo'] ?? ''));
            $foto['url'] = $this->buildFotoPublicUrl((string) ($foto['arquivo'] ?? ''));
        }
        unset($foto);

        return $fotos;
    }

    private function normalizeHexColor(string $value): ?string
    {
        $raw = strtoupper(trim(str_replace('#', '', $value)));
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^[0-9A-F]{3}$/', $raw)) {
            $raw = $raw[0] . $raw[0] . $raw[1] . $raw[1] . $raw[2] . $raw[2];
        }

        if (!preg_match('/^[0-9A-F]{6}$/', $raw)) {
            return null;
        }

        return '#' . $raw;
    }

    private function resolveRgbPayload($value, string $fallbackHex): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text !== '') {
            return $text;
        }

        $hex = str_replace('#', '', $fallbackHex);
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return null;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
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
     * @param array<string,mixed> $item
     * @return array<string,mixed>
     */
    private function normalizeEquipmentResponse(array $item): array
    {
        $marcaNome = trim((string) ($item['marca_nome'] ?? ''));
        $modeloNome = trim((string) ($item['modelo_nome'] ?? ''));
        $tipoNome = trim((string) ($item['tipo_nome'] ?? ''));
        $labelParts = array_values(array_filter([$marcaNome, $modeloNome, $tipoNome]));

        $item['label'] = !empty($labelParts)
            ? implode(' - ', $labelParts)
            : ('Equipamento #' . (int) ($item['id'] ?? 0));

        return $item;
    }

    /**
     * @param array<string,mixed>|null $item
     * @return array<string,mixed>
     */
    private function composeEquipmentResponse(int $equipamentoId, ?array $item = null): array
    {
        $model = new EquipamentoModel();
        $equip = $item ?: $model->getWithCliente($equipamentoId);
        if (!$equip) {
            return [];
        }

        $response = $this->normalizeEquipmentResponse($equip);
        $fotos = $this->getHydratedFotosByEquipamentoId($equipamentoId);
        $response['fotos'] = $fotos;

        $principal = null;
        foreach ($fotos as $foto) {
            if ((int) ($foto['is_principal'] ?? 0) === 1) {
                $principal = $foto;
                break;
            }
        }
        if (!$principal && !empty($fotos)) {
            $principal = $fotos[0];
        }
        $response['foto_principal_url'] = $principal['url'] ?? null;

        return $response;
    }

    private function missingImageDataUri(): string
    {
        static $uri = null;
        if ($uri !== null) {
            return $uri;
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="180" height="120" viewBox="0 0 180 120"><rect width="180" height="120" rx="10" fill="#eef2ff"/><rect x="62" y="34" width="56" height="36" rx="6" fill="#c7d2fe"/><circle cx="90" cy="52" r="10" fill="#818cf8"/><text x="90" y="96" text-anchor="middle" font-size="12" fill="#64748b">sem foto</text></svg>';
        $uri = 'data:image/svg+xml;base64,' . base64_encode($svg);
        return $uri;
    }

    private function buildPerfilAbsolutePath(string $arquivo): string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        $relative = str_replace('/', DIRECTORY_SEPARATOR, $arquivo);
        return FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . $relative;
    }

    private function removeEmptyPerfilFolder(string $filePath): void
    {
        $baseDir = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil';
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            return;
        }

        $realBase = realpath($baseDir);
        $realDir = realpath($dir);
        if (!$realBase || !$realDir) {
            return;
        }
        if (strpos($realDir, $realBase) !== 0 || $realDir === $realBase) {
            return;
        }

        $items = array_diff(scandir($realDir), ['.', '..']);
        if (empty($items)) {
            @rmdir($realDir);
        }
    }

    private function resolveFotoAbsolutePath(string $arquivo): ?string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        if ($arquivo === '') {
            return null;
        }

        $candidates = [
            $this->buildPerfilAbsolutePath($arquivo),
            FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . basename($arquivo),
            FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos' . DIRECTORY_SEPARATOR . basename($arquivo),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeEquipamentoFotosStorage(int $equipamentoId): void
    {
        if ($equipamentoId <= 0) {
            return;
        }

        $fotoModel = new EquipamentoFotoModel();
        $fotos = $fotoModel->where('equipamento_id', $equipamentoId)
            ->orderBy('is_principal', 'DESC')
            ->orderBy('id', 'ASC')
            ->findAll();

        if (empty($fotos)) {
            return;
        }

        $folderName = $this->buildEquipamentoPerfilFolderName($equipamentoId);
        $targetDir = $this->ensurePerfilFolder($folderName);

        $usedNames = [];
        $sequence = 1;
        foreach ($fotos as $foto) {
            $arquivoAtual = str_replace('\\', '/', ltrim((string) ($foto['arquivo'] ?? ''), '/'));
            $pathAtual = $this->resolveFotoAbsolutePath($arquivoAtual);

            $ext = strtolower((string) pathinfo($arquivoAtual, PATHINFO_EXTENSION));
            if ($ext === '' && $pathAtual) {
                $ext = strtolower((string) pathinfo($pathAtual, PATHINFO_EXTENSION));
            }
            if ($ext === '') {
                $ext = 'jpg';
            }

            $newName = "perfil_{$sequence}.{$ext}";
            while (isset($usedNames[$newName]) || is_file($targetDir . DIRECTORY_SEPARATOR . $newName)) {
                $existingAbs = $targetDir . DIRECTORY_SEPARATOR . $newName;
                if ($pathAtual && realpath($pathAtual) === realpath($existingAbs)) {
                    break;
                }
                $sequence++;
                $newName = "perfil_{$sequence}.{$ext}";
            }
            $usedNames[$newName] = true;

            $novoArquivo = $folderName . '/' . $newName;
            $destino = $targetDir . DIRECTORY_SEPARATOR . $newName;
            $pathReady = false;

            if ($pathAtual) {
                if (realpath($pathAtual) === realpath($destino)) {
                    $pathReady = true;
                } else {
                    $moved = @rename($pathAtual, $destino);
                    if (!$moved) {
                        $moved = @copy($pathAtual, $destino);
                        if ($moved) {
                            @unlink($pathAtual);
                        }
                    }
                    if ($moved) {
                        $this->removeEmptyPerfilFolder($pathAtual);
                        $pathReady = true;
                    }
                }
            } elseif (strpos($arquivoAtual, $folderName . '/') === 0) {
                $pathReady = true;
            }

            if ($pathReady && $arquivoAtual !== $novoArquivo) {
                $fotoModel->update((int) $foto['id'], ['arquivo' => $novoArquivo]);
            }

            $sequence++;
        }
    }

    private function buildEquipamentoPerfilFolderName(int $equipamentoId): string
    {
        $equip = (new EquipamentoModel())
            ->select('equipamentos.id, equipamentos.cliente_id, modelos.nome as modelo_nome')
            ->join('equipamentos_modelos modelos', 'modelos.id = equipamentos.modelo_id', 'left')
            ->where('equipamentos.id', $equipamentoId)
            ->first();

        $modeloParte = $this->slugify((string) ($equip['modelo_nome'] ?? 'equipamento'), '-');
        $clientesPartes = $this->getClienteFolderParts($equipamentoId, isset($equip['cliente_id']) ? (int) $equip['cliente_id'] : 0);
        if (empty($clientesPartes)) {
            $clientesPartes = ['cliente'];
        }

        $folderBase = trim($modeloParte . '-' . implode('-', $clientesPartes), '-');
        if ($folderBase === '') {
            $folderBase = 'equipamento-cliente';
        }

        $fotoModel = new EquipamentoFotoModel();
        $conflict = $fotoModel->where('equipamento_id !=', $equipamentoId)
            ->like('arquivo', $folderBase . '/', 'after')
            ->first();

        if ($conflict) {
            return $folderBase . '-eq' . $equipamentoId;
        }

        return $folderBase;
    }

    /**
     * @return array<int,string>
     */
    private function getClienteFolderParts(int $equipamentoId, int $clientePrincipalId = 0): array
    {
        $ids = [];
        if ($clientePrincipalId > 0) {
            $ids[] = $clientePrincipalId;
        }

        $vinculos = (new EquipamentoClienteModel())
            ->select('cliente_id')
            ->where('equipamento_id', $equipamentoId)
            ->findAll();

        foreach ($vinculos as $vinculo) {
            $cid = (int) ($vinculo['cliente_id'] ?? 0);
            if ($cid > 0 && !in_array($cid, $ids, true)) {
                $ids[] = $cid;
            }
        }

        if (empty($ids)) {
            return [];
        }

        $clientes = (new ClienteModel())
            ->select('id, nome_razao')
            ->whereIn('id', $ids)
            ->findAll();

        $nomeById = [];
        foreach ($clientes as $cliente) {
            $nomeById[(int) ($cliente['id'] ?? 0)] = (string) ($cliente['nome_razao'] ?? '');
        }

        $parts = [];
        foreach ($ids as $cid) {
            $nome = $nomeById[$cid] ?? ('cliente-' . $cid);
            $segment = $this->slugify($nome, '_');
            if ($segment !== '' && !in_array($segment, $parts, true)) {
                $parts[] = $segment;
            }
        }

        return $parts;
    }

    private function slugify(string $value, string $delimiter = '-'): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'item';
        }

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($normalized === false) {
            $normalized = $value;
        }

        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/i', $delimiter, $normalized ?? '');
        $normalized = trim((string) $normalized, $delimiter);

        return $normalized !== '' ? $normalized : 'item';
    }
}
