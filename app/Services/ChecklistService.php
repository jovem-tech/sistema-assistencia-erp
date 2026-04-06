<?php

namespace App\Services;

use App\Models\ChecklistExecucaoModel;
use App\Models\ChecklistFotoModel;
use App\Models\ChecklistItemModel;
use App\Models\ChecklistModeloModel;
use App\Models\ChecklistRespostaModel;
use App\Models\ChecklistTipoModel;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use RuntimeException;
use Throwable;

class ChecklistService
{
    public const STATUS_OK = 'ok';
    public const STATUS_DISCREPANCIA = 'discrepancia';
    public const STATUS_NAO_VERIFICADO = 'nao_verificado';

    private ChecklistTipoModel $tipoModel;
    private ChecklistModeloModel $modeloModel;
    private ChecklistItemModel $itemModel;
    private ChecklistExecucaoModel $execucaoModel;
    private ChecklistRespostaModel $respostaModel;
    private ChecklistFotoModel $fotoModel;

    public function __construct()
    {
        $this->tipoModel = new ChecklistTipoModel();
        $this->modeloModel = new ChecklistModeloModel();
        $this->itemModel = new ChecklistItemModel();
        $this->execucaoModel = new ChecklistExecucaoModel();
        $this->respostaModel = new ChecklistRespostaModel();
        $this->fotoModel = new ChecklistFotoModel();
    }

    public function getTipoByCodigo(string $codigo): ?array
    {
        return $this->tipoModel->findByCodigo($codigo);
    }

    /**
     * @return array<string,mixed>
     */
    public function getPayloadForOs(int $osId, string $checklistCodigo, int $tipoEquipamentoId, string $numeroOs = ''): array
    {
        $tipo = $this->requireTipo($checklistCodigo);
        $modelo = $this->modeloModel->findAtivoPorTipo((int) $tipo['id'], $tipoEquipamentoId);
        $execucao = $this->execucaoModel->findByOsAndTipo($osId, (int) $tipo['id']);

        if ($modelo === null) {
            return [
                'tipo' => $tipo,
                'modelo' => null,
                'numero_os' => $numeroOs,
                'possui_modelo' => false,
                'execucao' => $execucao,
                'itens' => [],
                'resumo' => $this->buildSummary($execucao),
            ];
        }

        $itensModelo = $this->itemModel->findAtivosPorModelo((int) $modelo['id']);
        $respostas = [];
        $fotosPorResposta = [];

        if ($execucao !== null) {
            $respostas = $this->respostaModel->findByExecucao((int) $execucao['id']);
            $respostaIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $respostas);
            $fotos = $this->fotoModel->findByRespostaIds($respostaIds);

            foreach ($fotos as $foto) {
                $respostaId = (int) ($foto['checklist_resposta_id'] ?? 0);
                $fotosPorResposta[$respostaId][] = $this->normalizeFoto($foto);
            }
        }

        $respostaMap = [];
        foreach ($respostas as $resposta) {
            $respostaMap[(int) ($resposta['checklist_item_id'] ?? 0)] = $resposta;
        }

        $itens = [];
        foreach ($itensModelo as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            $resposta = $respostaMap[$itemId] ?? null;
            $respostaId = (int) ($resposta['id'] ?? 0);
            $status = (string) ($resposta['status'] ?? self::STATUS_NAO_VERIFICADO);
            $itens[] = [
                'id' => $itemId,
                'descricao' => trim((string) ($item['descricao'] ?? '')),
                'ordem' => (int) ($item['ordem'] ?? 0),
                'status' => $status !== '' ? $status : self::STATUS_NAO_VERIFICADO,
                'observacao' => trim((string) ($resposta['observacao'] ?? '')),
                'resposta_id' => $respostaId > 0 ? $respostaId : null,
                'fotos' => $respostaId > 0 ? ($fotosPorResposta[$respostaId] ?? []) : [],
            ];
        }

        return [
            'tipo' => $tipo,
            'modelo' => $modelo,
            'numero_os' => $numeroOs,
            'possui_modelo' => true,
            'execucao' => $execucao,
            'itens' => $itens,
            'resumo' => $this->buildSummary($execucao),
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $filesByItem
     * @return array<string,mixed>
     */
    public function saveExecution(
        int $osId,
        string $numeroOs,
        string $checklistCodigo,
        int $tipoEquipamentoId,
        array $payload,
        array $filesByItem = []
    ): array {
        $tipo = $this->requireTipo($checklistCodigo);
        $modelo = $this->modeloModel->findAtivoPorTipo((int) $tipo['id'], $tipoEquipamentoId);
        if ($modelo === null) {
            throw new RuntimeException('Nao existe checklist configurado para este tipo de equipamento.');
        }

        $itensModelo = $this->itemModel->findAtivosPorModelo((int) $modelo['id']);
        $itemsPayload = $this->normalizeItemsPayload($payload['itens'] ?? []);
        $payloadMap = [];
        foreach ($itemsPayload as $itemPayload) {
            $payloadMap[(int) ($itemPayload['item_id'] ?? 0)] = $itemPayload;
        }

        $db = Database::connect();
        $db->transBegin();

        try {
            $execucao = $this->execucaoModel->findByOsAndTipo($osId, (int) $tipo['id']);
            $execucaoData = [
                'os_id' => $osId,
                'checklist_tipo_id' => (int) $tipo['id'],
                'checklist_modelo_id' => (int) $modelo['id'],
                'tipo_equipamento_id' => $tipoEquipamentoId,
                'status' => 'preenchido',
                'total_itens' => count($itensModelo),
                'total_discrepancias' => 0,
                'resumo_texto' => null,
                'concluido_em' => date('Y-m-d H:i:s'),
            ];

            if ($execucao === null) {
                $this->execucaoModel->insert($execucaoData);
                $execucaoId = (int) $this->execucaoModel->getInsertID();
            } else {
                $execucaoId = (int) $execucao['id'];
                $this->execucaoModel->update($execucaoId, $execucaoData);
            }

            if ($execucaoId <= 0) {
                throw new RuntimeException('Nao foi possivel salvar a execucao do checklist.');
            }

            $respostasExistentes = $this->respostaModel->findByExecucao($execucaoId);
            $respostasMap = [];
            foreach ($respostasExistentes as $resposta) {
                $respostasMap[(int) ($resposta['checklist_item_id'] ?? 0)] = $resposta;
            }

            $totalDiscrepancias = 0;
            $resumoItens = [];

            foreach ($itensModelo as $item) {
                $itemId = (int) ($item['id'] ?? 0);
                $itemPayload = $payloadMap[$itemId] ?? [];
                $status = $this->normalizeStatus((string) ($itemPayload['status'] ?? self::STATUS_NAO_VERIFICADO));
                $observacao = trim((string) ($itemPayload['observacao'] ?? ''));

                if ($status === self::STATUS_DISCREPANCIA) {
                    $totalDiscrepancias++;
                    $resumoItens[] = trim((string) ($item['descricao'] ?? ''));
                }

                $respostaData = [
                    'checklist_execucao_id' => $execucaoId,
                    'checklist_item_id' => $itemId,
                    'descricao_item' => trim((string) ($item['descricao'] ?? '')),
                    'ordem' => (int) ($item['ordem'] ?? 0),
                    'status' => $status,
                    'observacao' => $observacao !== '' ? $observacao : null,
                ];

                $resposta = $respostasMap[$itemId] ?? null;
                if ($resposta === null) {
                    $this->respostaModel->insert($respostaData);
                    $respostaId = (int) $this->respostaModel->getInsertID();
                } else {
                    $respostaId = (int) ($resposta['id'] ?? 0);
                    $this->respostaModel->update($respostaId, $respostaData);
                }

                if ($respostaId <= 0) {
                    throw new RuntimeException('Falha ao persistir respostas do checklist.');
                }

                $retainedPhotoIds = array_map('intval', (array) ($itemPayload['retained_photo_ids'] ?? []));
                $deletedPhotoIds = array_map('intval', (array) ($itemPayload['deleted_photo_ids'] ?? []));
                $this->syncRespostaFotos(
                    $respostaId,
                    $numeroOs,
                    (string) $tipo['codigo'],
                    (string) ($payload['tipo_equipamento_nome'] ?? ''),
                    $filesByItem[(string) $itemId] ?? [],
                    $retainedPhotoIds,
                    $deletedPhotoIds
                );
            }

            $resumoTexto = $totalDiscrepancias > 0
                ? implode('; ', array_slice($resumoItens, 0, 5))
                : 'Nenhuma discrepancia registrada.';

            $this->execucaoModel->update($execucaoId, [
                'total_discrepancias' => $totalDiscrepancias,
                'resumo_texto' => $resumoTexto,
            ]);

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', '[Checklist] Falha ao salvar checklist da OS ' . $osId . ': ' . $e->getMessage());
            throw $e;
        }

        return $this->getPayloadForOs($osId, $checklistCodigo, $tipoEquipamentoId, $numeroOs);
    }

    /**
     * @return array<string,mixed>
     */
    public function buildSummary(?array $execucao): array
    {
        $totalDiscrepancias = (int) ($execucao['total_discrepancias'] ?? 0);
        $preenchido = $execucao !== null;

        if (!$preenchido) {
            return [
                'preenchido' => false,
                'total_discrepancias' => 0,
                'label' => 'Checklist nao preenchido',
                'variant' => 'secondary',
            ];
        }

        if ($totalDiscrepancias <= 0) {
            return [
                'preenchido' => true,
                'total_discrepancias' => 0,
                'label' => '0 discrepancias',
                'variant' => 'success',
            ];
        }

        $label = $totalDiscrepancias === 1
            ? '1 item com discrepancia'
            : $totalDiscrepancias . ' discrepancias registradas';

        return [
            'preenchido' => true,
            'total_discrepancias' => $totalDiscrepancias,
            'label' => $label,
            'variant' => 'warning',
        ];
    }

    /**
     * @param mixed $items
     * @return list<array<string,mixed>>
     */
    private function normalizeItemsPayload($items): array
    {
        if (is_string($items) && $items !== '') {
            $decoded = json_decode($items, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }

        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized[] = [
                'item_id' => (int) ($item['item_id'] ?? $item['id'] ?? 0),
                'status' => (string) ($item['status'] ?? self::STATUS_NAO_VERIFICADO),
                'observacao' => (string) ($item['observacao'] ?? ''),
                'retained_photo_ids' => is_array($item['retained_photo_ids'] ?? null) ? $item['retained_photo_ids'] : [],
                'deleted_photo_ids' => is_array($item['deleted_photo_ids'] ?? null) ? $item['deleted_photo_ids'] : [],
            ];
        }

        return $normalized;
    }

    private function normalizeStatus(string $status): string
    {
        $status = trim($status);
        if (in_array($status, [self::STATUS_OK, self::STATUS_DISCREPANCIA, self::STATUS_NAO_VERIFICADO], true)) {
            return $status;
        }

        return self::STATUS_NAO_VERIFICADO;
    }

    /**
     * @param array<int,mixed> $uploadedFiles
     * @param array<int,int> $retainedPhotoIds
     * @param array<int,int> $deletedPhotoIds
     */
    private function syncRespostaFotos(
        int $respostaId,
        string $numeroOs,
        string $checklistCodigo,
        string $tipoEquipamentoNome,
        array $uploadedFiles,
        array $retainedPhotoIds,
        array $deletedPhotoIds
    ): void {
        $existing = $this->fotoModel->where('checklist_resposta_id', $respostaId)->orderBy('ordem', 'ASC')->findAll();
        $keepIds = array_filter($retainedPhotoIds, static fn(int $id): bool => $id > 0);
        $deleteIds = array_filter($deletedPhotoIds, static fn(int $id): bool => $id > 0);
        $ordem = count($keepIds);

        foreach ($existing as $foto) {
            $fotoId = (int) ($foto['id'] ?? 0);
            if (in_array($fotoId, $deleteIds, true) || (!empty($keepIds) && !in_array($fotoId, $keepIds, true))) {
                $this->deleteStoredFoto((string) ($foto['arquivo'] ?? ''));
                $this->fotoModel->delete($fotoId);
            }
        }

        foreach ($uploadedFiles as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $ordem++;
            $relativePath = $this->saveFotoArquivo($file, $numeroOs, $checklistCodigo, $tipoEquipamentoNome, $ordem);
            $this->fotoModel->insert([
                'checklist_resposta_id' => $respostaId,
                'arquivo' => $relativePath,
                'arquivo_original' => $file->getClientName(),
                'ordem' => $ordem,
            ]);
        }
    }

    private function saveFotoArquivo(
        UploadedFile $file,
        string $numeroOs,
        string $checklistCodigo,
        string $tipoEquipamentoNome,
        int $ordem
    ): string {
        $extension = strtolower($file->getExtension() ?: $file->getClientExtension() ?: 'jpg');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException('Formato de foto do checklist nao permitido.');
        }

        if (($file->getSizeByUnit('mb') ?? 0) > 15) {
            throw new RuntimeException('A foto do checklist excede o tamanho maximo permitido.');
        }

        $numeroSlug = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($numeroOs)) ?: 'os';
        $folderSlug = 'checklist_' . $this->slug($checklistCodigo);
        $tipoSlug = $this->slug($tipoEquipamentoNome !== '' ? $tipoEquipamentoNome : 'equipamento');
        $baseDir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'checklist' . DIRECTORY_SEPARATOR . $numeroSlug . DIRECTORY_SEPARATOR . $folderSlug;

        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Nao foi possivel criar a pasta do checklist.');
        }

        $fileNameBase = sprintf('checklist_%s_%s_%s_%d', $this->slug($checklistCodigo), $tipoSlug, $numeroSlug, $ordem);
        $fileName = $fileNameBase . '.' . $extension;
        $counter = 1;

        while (is_file($baseDir . DIRECTORY_SEPARATOR . $fileName)) {
            $fileName = $fileNameBase . '_' . $counter . '.' . $extension;
            $counter++;
        }

        $file->move($baseDir, $fileName, true);

        return 'uploads/checklist/' . $numeroSlug . '/' . $folderSlug . '/' . $fileName;
    }

    private function deleteStoredFoto(string $relativePath): void
    {
        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativePath, '/\\'));
        if ($relativePath === '') {
            return;
        }

        $absolutePath = FCPATH . $relativePath;
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    /**
     * @param array<string,mixed> $foto
     * @return array<string,mixed>
     */
    private function normalizeFoto(array $foto): array
    {
        $arquivo = trim((string) ($foto['arquivo'] ?? ''));
        $url = $arquivo !== '' ? base_url(str_replace('\\', '/', ltrim($arquivo, '/\\'))) : null;

        return [
            'id' => (int) ($foto['id'] ?? 0),
            'arquivo' => $arquivo,
            'arquivo_original' => trim((string) ($foto['arquivo_original'] ?? '')),
            'ordem' => (int) ($foto['ordem'] ?? 0),
            'url' => $url,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function requireTipo(string $codigo): array
    {
        $tipo = $this->tipoModel->findByCodigo($codigo);
        if ($tipo === null) {
            throw new RuntimeException('Tipo de checklist nao encontrado: ' . $codigo);
        }

        return $tipo;
    }

    private function slug(string $value): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $normalized = strtolower(trim((string) $normalized));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?: 'item';
        return trim($normalized, '_') ?: 'item';
    }
}
