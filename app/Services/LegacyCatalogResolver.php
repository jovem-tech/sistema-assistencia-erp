<?php

namespace App\Services;

use App\Models\EquipamentoMarcaModel;
use App\Models\EquipamentoModeloModel;
use App\Models\EquipamentoTipoModel;

class LegacyCatalogResolver
{
    private EquipamentoTipoModel $tipoModel;
    private EquipamentoMarcaModel $marcaModel;
    private EquipamentoModeloModel $modeloModel;
    private LegacyRecordNormalizer $normalizer;
    private bool $allowAutoCreate;

    /**
     * @var array<string, int>
     */
    private array $tipoCache = [];

    /**
     * @var array<string, int>
     */
    private array $marcaCache = [];

    /**
     * @var array<string, int>
     */
    private array $modeloCache = [];

    public function __construct(?LegacyRecordNormalizer $normalizer = null, bool $allowAutoCreate = true)
    {
        $this->tipoModel = new EquipamentoTipoModel();
        $this->marcaModel = new EquipamentoMarcaModel();
        $this->modeloModel = new EquipamentoModeloModel();
        $this->normalizer = $normalizer ?? new LegacyRecordNormalizer();
        $this->allowAutoCreate = $allowAutoCreate;
    }

    public function resolveTipoId(?string $nome): ?int
    {
        return $this->resolveSimpleCatalogId($nome, $this->tipoCache, $this->tipoModel);
    }

    public function resolveMarcaId(?string $nome): ?int
    {
        return $this->resolveSimpleCatalogId($nome, $this->marcaCache, $this->marcaModel);
    }

    public function resolveModeloId(int $marcaId, ?string $nome): ?int
    {
        $normalizedName = $this->normalizer->normalizeCatalogName($nome);
        if ($normalizedName === null) {
            return null;
        }

        $cacheKey = $marcaId . '|' . mb_strtolower($normalizedName, 'UTF-8');
        if (isset($this->modeloCache[$cacheKey])) {
            return $this->modeloCache[$cacheKey];
        }

        $existing = $this->modeloModel
            ->where('marca_id', $marcaId)
            ->where('LOWER(nome) = ' . $this->modeloModel->db->escape(mb_strtolower($normalizedName, 'UTF-8')), null, false)
            ->first();

        if ($existing) {
            return $this->modeloCache[$cacheKey] = (int) $existing['id'];
        }

        if (! $this->allowAutoCreate) {
            return null;
        }

        $id = $this->modeloModel->insert([
            'marca_id' => $marcaId,
            'nome'     => $normalizedName,
            'ativo'    => 1,
        ], true);

        return $this->modeloCache[$cacheKey] = (int) $id;
    }

    private function resolveSimpleCatalogId(?string $nome, array &$cache, $model): ?int
    {
        $normalizedName = $this->normalizer->normalizeCatalogName($nome);
        if ($normalizedName === null) {
            return null;
        }

        $cacheKey = mb_strtolower($normalizedName, 'UTF-8');
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $existing = $model
            ->where('LOWER(nome) = ' . $model->db->escape($cacheKey), null, false)
            ->first();

        if ($existing) {
            return $cache[$cacheKey] = (int) $existing['id'];
        }

        if (! $this->allowAutoCreate) {
            return null;
        }

        $id = $model->insert([
            'nome'  => $normalizedName,
            'ativo' => 1,
        ], true);

        return $cache[$cacheKey] = (int) $id;
    }
}
