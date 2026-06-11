<?php

namespace App\Services;

use App\Models\EquipamentoMarcaModel;
use App\Models\EquipamentoModel;
use App\Models\EquipamentoModeloModel;
use App\Models\EquipamentoTipoModel;

class EquipamentoProfileService
{
    private EquipamentoTipoModel $tipoModel;
    private EquipamentoMarcaModel $marcaModel;
    private EquipamentoModeloModel $modeloModel;
    private EquipamentoModel $equipamentoModel;

    /** @var array<int,string> */
    private array $tipoNomeCache = [];

    public function __construct()
    {
        $this->tipoModel = new EquipamentoTipoModel();
        $this->marcaModel = new EquipamentoMarcaModel();
        $this->modeloModel = new EquipamentoModeloModel();
        $this->equipamentoModel = new EquipamentoModel();
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $current
     * @return array<string,mixed>
     */
    public function prepareForPersist(array $payload, array $current = []): array
    {
        foreach ($this->technicalFields() as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $this->nullableString($payload[$field]);
            }
        }

        foreach (['desktop_modalidade', 'gabinete_identificacao_status', 'configuracao_status', 'configuracao_origem'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $this->nullableSlug($payload[$field]);
            }
        }

        $merged = array_merge($current, $payload);
        $tipoId = (int) ($merged['tipo_id'] ?? 0);
        $tipoNome = $this->resolveTipoNome($tipoId, $merged);
        $isDesktop = equipamento_is_desktop_tipo($tipoNome);

        if ($isDesktop) {
            $modalidade = $this->resolveDesktopMode($merged);
            $payload['desktop_modalidade'] = $modalidade;

            if ($modalidade === 'montado') {
                $defaults = $this->ensureDesktopMountedCatalogDefaults();
                $payload['marca_id'] = $defaults['marca_id'];
                $payload['modelo_id'] = $defaults['modelo_id'];
            }
        } elseif (array_key_exists('desktop_modalidade', $payload)) {
            $payload['desktop_modalidade'] = null;
        }

        $effective = array_merge($current, $payload);
        $effectiveForSummary = $effective;
        unset($effectiveForSummary['resumo_tecnico'], $effectiveForSummary['equip_resumo_tecnico'], $effectiveForSummary['technical_summary']);

        $resumoTecnico = equipamento_resumo_tecnico($effectiveForSummary);
        $payload['resumo_tecnico'] = $resumoTecnico !== '' ? $resumoTecnico : null;

        if (empty($payload['configuracao_status'])) {
            $payload['configuracao_status'] = $this->inferConfiguracaoStatus($effective, $isDesktop);
        }

        if (empty($payload['configuracao_origem'])) {
            $payload['configuracao_origem'] = $this->inferConfiguracaoOrigem($effective, $current);
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $current
     * @return array<string,string>
     */
    public function validateCatalogRules(array $payload, array $current = []): array
    {
        $errors = [];
        $merged = array_merge($current, $payload);
        $tipoId = (int) ($merged['tipo_id'] ?? 0);
        $tipoNome = $this->resolveTipoNome($tipoId, $merged);
        $isDesktop = equipamento_is_desktop_tipo($tipoNome);
        $modalidade = $isDesktop ? $this->resolveDesktopMode($merged) : null;

        $brandPresent = $this->hasCatalogValue($merged, 'marca_id', 'marca_nome');
        $modelPresent = $this->hasCatalogValue($merged, 'modelo_id', 'modelo_nome');

        if (!$isDesktop || $modalidade !== 'montado') {
            if (!$brandPresent) {
                $errors['marca_id'] = 'Selecione a marca do equipamento.';
            }
            if (!$modelPresent) {
                $errors['modelo_id'] = 'Selecione o modelo do equipamento.';
            }
        }

        return $errors;
    }

    /**
     * @return array{marca_id:int,modelo_id:int,marca_nome:string,modelo_nome:string}
     */
    public function getDesktopMountedCatalogDefaultsMeta(): array
    {
        $defaults = $this->ensureDesktopMountedCatalogDefaults();

        return [
            'marca_id' => $defaults['marca_id'],
            'modelo_id' => $defaults['modelo_id'],
            'marca_nome' => $defaults['marca_nome'],
            'modelo_nome' => $defaults['modelo_nome'],
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function syncEquipmentFromAgent(int $equipamentoId, array $payload, ?string $detectedAt = null): array
    {
        if ($equipamentoId <= 0) {
            return [];
        }

        $equipamento = $this->equipamentoModel->find($equipamentoId);
        if (!$equipamento) {
            return [];
        }

        $tipoNome = $this->resolveTipoNome((int) ($equipamento['tipo_id'] ?? 0), $equipamento);
        if (!equipamento_is_desktop_tipo($tipoNome) && !equipamento_is_notebook_tipo($tipoNome)) {
            return [];
        }

        $update = [
            'placa_mae' => $this->nullableString($payload['motherboard'] ?? null),
            'chipset' => $this->nullableString($payload['chipset'] ?? null),
            'processador' => $this->nullableString($payload['cpu'] ?? null),
            'memoria_ram' => $this->normalizeMemorySummary($payload),
            'armazenamento' => $this->normalizeStorageSummary($payload),
            'placa_video' => $this->nullableString($payload['gpu'] ?? null),
            'configuracao_status' => 'sincronizado_agente',
            'configuracao_detectada_em' => $detectedAt ?: date('Y-m-d H:i:s'),
        ];

        if (equipamento_is_desktop_tipo($tipoNome)) {
            if (equipamento_normalize_text($equipamento['desktop_modalidade'] ?? '') === '') {
                $update['desktop_modalidade'] = $this->resolveDesktopMode([
                    'desktop_modalidade' => '',
                    'marca_nome' => $payload['manufacturer'] ?? '',
                    'modelo_nome' => $payload['model'] ?? '',
                    'motherboard' => $payload['motherboard'] ?? '',
                ]);
            }

            $chassis = $this->nullableString($payload['chassisType'] ?? null);
            if ($chassis !== null && equipamento_normalize_text($equipamento['gabinete_tipo'] ?? '') === '') {
                $update['gabinete_tipo'] = $chassis;
                $update['gabinete_identificacao_status'] = 'detectado';
            }
        }

        $hasExistingManualData = $this->hasTechnicalData($equipamento);
        $update['configuracao_origem'] = $hasExistingManualData ? 'misto' : 'agente';

        $update = array_filter(
            $update,
            static fn($value): bool => $value !== null && $value !== ''
        );

        if (empty($update)) {
            return [];
        }

        $update = $this->prepareForPersist($update, $equipamento);
        $this->equipamentoModel->update($equipamentoId, $update);

        return $update;
    }

    /**
     * @param array<string,mixed> $row
     */
    public function appendDerivedFields(array $row): array
    {
        $row['technical_summary'] = equipamento_resumo_tecnico($row);
        $row['display_name'] = equipamento_nome_exibicao($row);
        $row['display_label'] = equipamento_rotulo_exibicao($row);
        $row['desktop_modalidade_label'] = equipamento_modalidade_label($row['desktop_modalidade'] ?? '');
        $row['configuracao_status_label'] = equipamento_configuracao_status_label($row['configuracao_status'] ?? '');
        $row['status_operacional_label'] = equipamento_status_operacional_label($row['status_operacional'] ?? 'ativo');
        $row['motivo_encerramento_label'] = equipamento_motivo_encerramento_label($row['motivo_encerramento'] ?? '');
        $row['is_encerrado'] = equipamento_esta_encerrado($row);
        $row['encerrado_em_label'] = !empty($row['encerrado_em'])
            ? date('d/m/Y H:i', strtotime((string) $row['encerrado_em']))
            : '';

        return $row;
    }

    /**
     * @return array<int,string>
     */
    private function technicalFields(): array
    {
        return [
            'gabinete_tipo',
            'gabinete_observacao',
            'placa_mae',
            'chipset',
            'processador',
            'memoria_ram',
            'armazenamento',
            'placa_video',
            'fonte_alimentacao',
            'resumo_tecnico',
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    private function resolveDesktopMode(array $data): string
    {
        $raw = $this->nullableSlug($data['desktop_modalidade'] ?? null);
        if (in_array($raw, ['montado', 'oem'], true)) {
            return $raw;
        }

        $brand = mb_strtolower(equipamento_value_from_keys($data, ['marca_nome', 'marca', 'manufacturer']));
        $model = mb_strtolower(equipamento_value_from_keys($data, ['modelo_nome', 'modelo', 'model']));
        $hasTechnical = $this->hasTechnicalData($data);

        if ($hasTechnical) {
            return 'montado';
        }

        if ($brand !== '' || $model !== '') {
            $genericSignals = ['montado', 'generica', 'generico', 'desktop montado'];
            if (!in_array($brand, $genericSignals, true) && !in_array($model, $genericSignals, true)) {
                return 'oem';
            }
        }

        return 'montado';
    }

    /**
     * @param array<string,mixed> $data
     */
    private function inferConfiguracaoStatus(array $data, bool $isDesktop): string
    {
        if ($this->nullableString($data['configuracao_detectada_em'] ?? null) !== null) {
            return 'sincronizado_agente';
        }

        $hasTechnical = $this->hasTechnicalData($data);
        if ($hasTechnical && !$isDesktop) {
            return 'completo';
        }

        if ($isDesktop) {
            $mode = $this->resolveDesktopMode($data);
            if ($mode === 'montado' && !$hasTechnical) {
                return 'pendente_bancada';
            }
        }

        return $hasTechnical ? 'completo' : 'manual';
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $current
     */
    private function inferConfiguracaoOrigem(array $data, array $current = []): string
    {
        if ($this->nullableString($data['configuracao_detectada_em'] ?? null) !== null) {
            return $this->hasTechnicalData($current) ? 'misto' : 'agente';
        }

        return 'manual';
    }

    /**
     * @param array<string,mixed> $data
     */
    private function hasTechnicalData(array $data): bool
    {
        foreach ($this->technicalFields() as $field) {
            if ($this->nullableString($data[$field] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function normalizeMemorySummary(array $payload): ?string
    {
        $memorySummary = $this->nullableString($payload['memorySummary'] ?? null);
        if ($memorySummary !== null) {
            return $memorySummary;
        }

        $ramGb = $this->nullableString($payload['ramGb'] ?? null);
        if ($ramGb === null) {
            return null;
        }

        return equipamento_format_ram_label($ramGb);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function normalizeStorageSummary(array $payload): ?string
    {
        $storageSummary = $this->nullableString($payload['storageSummary'] ?? null);
        if ($storageSummary !== null) {
            return $storageSummary;
        }

        $storage = $payload['storageDevices'] ?? $payload['disks'] ?? null;
        if (!is_array($storage) || empty($storage)) {
            return null;
        }

        $parts = [];
        foreach ($storage as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim(implode(' ', array_filter([
                $this->nullableString($item['type'] ?? null),
                $this->nullableString($item['model'] ?? null),
                $this->nullableString($item['sizeLabel'] ?? null),
            ])));

            if ($label !== '' && !in_array($label, $parts, true)) {
                $parts[] = $label;
            }
        }

        return !empty($parts) ? implode(' | ', $parts) : null;
    }

    /**
     * @return array{marca_id:int,modelo_id:int,marca_nome:string,modelo_nome:string}
     */
    private function ensureDesktopMountedCatalogDefaults(): array
    {
        $marcaNome = 'Montado';
        $modeloNome = 'Desktop montado';

        $marca = $this->marcaModel->where('nome', $marcaNome)->first();
        if (!$marca) {
            $marcaId = (int) $this->marcaModel->insert([
                'nome' => $marcaNome,
                'ativo' => 1,
            ], true);
            $marca = ['id' => $marcaId, 'nome' => $marcaNome];
        }

        $marcaId = (int) ($marca['id'] ?? 0);
        $modelo = $this->modeloModel
            ->where('marca_id', $marcaId)
            ->where('nome', $modeloNome)
            ->first();

        if (!$modelo) {
            $modeloId = (int) $this->modeloModel->insert([
                'marca_id' => $marcaId,
                'nome' => $modeloNome,
                'ativo' => 1,
            ], true);
            $modelo = ['id' => $modeloId, 'nome' => $modeloNome];
        }

        return [
            'marca_id' => $marcaId,
            'modelo_id' => (int) ($modelo['id'] ?? 0),
            'marca_nome' => $marcaNome,
            'modelo_nome' => $modeloNome,
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    private function hasCatalogValue(array $data, string $idField, string $nameField): bool
    {
        if ((int) ($data[$idField] ?? 0) > 0) {
            return true;
        }

        return $this->nullableString($data[$nameField] ?? null) !== null;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function resolveTipoNome(int $tipoId, array $data): string
    {
        $inline = equipamento_value_from_keys($data, ['tipo_nome', 'equip_tipo', 'tipo']);
        if ($inline !== '') {
            return $inline;
        }

        if ($tipoId <= 0) {
            return '';
        }

        if (!isset($this->tipoNomeCache[$tipoId])) {
            $tipo = $this->tipoModel->find($tipoId);
            $this->tipoNomeCache[$tipoId] = equipamento_normalize_text($tipo['nome'] ?? '');
        }

        return $this->tipoNomeCache[$tipoId];
    }

    private function nullableString($value): ?string
    {
        $text = equipamento_normalize_text($value);
        return $text !== '' ? $text : null;
    }

    private function nullableSlug($value): ?string
    {
        $text = $this->nullableString($value);
        if ($text === null) {
            return null;
        }

        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9_]+/u', '_', $text) ?? $text;
        $text = trim($text, '_');

        return $text !== '' ? $text : null;
    }
}
