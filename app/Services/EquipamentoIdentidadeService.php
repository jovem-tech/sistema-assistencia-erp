<?php

namespace App\Services;

use App\Models\ClienteModel;
use App\Models\EquipamentoClienteModel;
use App\Models\EquipamentoModel;
use RuntimeException;

class EquipamentoIdentidadeService
{
    private EquipamentoModel $equipamentoModel;
    private EquipamentoClienteModel $equipamentoClienteModel;
    private ClienteModel $clienteModel;

    public function __construct(
        ?EquipamentoModel $equipamentoModel = null,
        ?EquipamentoClienteModel $equipamentoClienteModel = null,
        ?ClienteModel $clienteModel = null
    ) {
        $this->equipamentoModel = $equipamentoModel ?? new EquipamentoModel();
        $this->equipamentoClienteModel = $equipamentoClienteModel ?? new EquipamentoClienteModel();
        $this->clienteModel = $clienteModel ?? new ClienteModel();
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>|null
     */
    public function detectConflict(array $payload, int $ignoreEquipamentoId = 0): ?array
    {
        $clienteId = max(0, (int) ($payload['cliente_id'] ?? 0));
        $identifiers = $this->extractIdentifiers($payload);
        if (empty($identifiers)) {
            return null;
        }

        $builder = $this->equipamentoModel
            ->select('equipamentos.id, equipamentos.cliente_id, equipamentos.numero_serie, equipamentos.imei, clientes.nome_razao as cliente_nome')
            ->join('clientes', 'clientes.id = equipamentos.cliente_id', 'left');

        if ($ignoreEquipamentoId > 0) {
            $builder->where('equipamentos.id !=', $ignoreEquipamentoId);
        }

        $candidates = $builder->findAll();
        if (empty($candidates)) {
            return null;
        }

        foreach ($identifiers as $identifier) {
            foreach ($candidates as $equipamento) {
                $match = $this->matchCandidate($identifier, $equipamento);
                if ($match === null) {
                    continue;
                }

                $equipamentoId = (int) ($equipamento['id'] ?? 0);
                $clientesRelacionados = $this->buildClientesRelacionados(
                    $equipamentoId,
                    (int) ($equipamento['cliente_id'] ?? 0),
                    (string) ($equipamento['cliente_nome'] ?? '')
                );

                $sameClient = false;
                $alreadyLinked = false;
                if ($clienteId > 0) {
                    foreach ($clientesRelacionados as $cliente) {
                        if ((int) ($cliente['id'] ?? 0) === $clienteId) {
                            $sameClient = true;
                            $alreadyLinked = !empty($cliente['is_linked']);
                            break;
                        }
                    }
                }

                return [
                    'equipment_id' => $equipamentoId,
                    'same_client' => $sameClient,
                    'already_linked' => $alreadyLinked,
                    'can_link_client' => $clienteId > 0 && !$sameClient,
                    'matched_input' => $identifier,
                    'matched_record' => $match,
                    'primary_client' => [
                        'id' => (int) ($equipamento['cliente_id'] ?? 0),
                        'nome_razao' => trim((string) ($equipamento['cliente_nome'] ?? '')),
                    ],
                    'related_clients' => $clientesRelacionados,
                    'message' => $this->buildConflictMessage($sameClient, $identifier, $equipamento, $clientesRelacionados),
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string,mixed>
     */
    public function linkClienteToEquipamento(int $equipamentoId, int $clienteId): array
    {
        if ($equipamentoId <= 0 || $clienteId <= 0) {
            throw new RuntimeException('Equipamento ou cliente invalido para o vinculo.');
        }

        $equipamento = $this->equipamentoModel->find($equipamentoId);
        if (!$equipamento) {
            throw new RuntimeException('Equipamento informado nao foi encontrado.');
        }

        $cliente = $this->clienteModel->find($clienteId);
        if (!$cliente) {
            throw new RuntimeException('Cliente informado nao foi encontrado.');
        }

        if ((int) ($equipamento['cliente_id'] ?? 0) === $clienteId) {
            return [
                'linked' => false,
                'already_primary' => true,
                'already_linked' => false,
            ];
        }

        $existingLink = $this->equipamentoClienteModel
            ->where('equipamento_id', $equipamentoId)
            ->where('cliente_id', $clienteId)
            ->first();

        if ($existingLink) {
            return [
                'linked' => false,
                'already_primary' => false,
                'already_linked' => true,
            ];
        }

        $this->equipamentoClienteModel->insert([
            'equipamento_id' => $equipamentoId,
            'cliente_id' => $clienteId,
        ]);

        return [
            'linked' => true,
            'already_primary' => false,
            'already_linked' => false,
        ];
    }

    public function normalizeIdentifier(?string $value): string
    {
        $text = strtoupper(trim((string) $value));
        if ($text === '') {
            return '';
        }

        return preg_replace('/[^A-Z0-9]+/', '', $text) ?? '';
    }

    private function isLikelyMacAddress(string $raw, string $normalized): bool
    {
        if ($normalized === '' || !preg_match('/^[0-9A-F]{12}$/', $normalized)) {
            return false;
        }

        if (preg_match('/^([0-9A-F]{2}[:-]){5}[0-9A-F]{2}$/i', trim($raw))) {
            return true;
        }

        return preg_match('/^[0-9A-F]{12}$/i', trim($raw)) === 1;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<int,array<string,string>>
     */
    private function extractIdentifiers(array $payload): array
    {
        $identifiers = [];

        $serialRaw = trim((string) ($payload['numero_serie'] ?? ''));
        $serialNormalized = $this->normalizeIdentifier($serialRaw);
        if ($serialNormalized !== '') {
            $identifiers[] = [
                'source_field' => 'numero_serie',
                'label' => $this->isLikelyMacAddress($serialRaw, $serialNormalized) ? 'MAC' : 'numero de serie',
                'raw' => $serialRaw,
                'normalized' => $serialNormalized,
            ];
        }

        $imeiRaw = trim((string) ($payload['imei'] ?? ''));
        $imeiNormalized = $this->normalizeIdentifier($imeiRaw);
        if ($imeiNormalized !== '') {
            $identifiers[] = [
                'source_field' => 'imei',
                'label' => 'IMEI',
                'raw' => $imeiRaw,
                'normalized' => $imeiNormalized,
            ];
        }

        return $identifiers;
    }

    /**
     * @param array<string,string> $identifier
     * @param array<string,mixed> $equipamento
     * @return array<string,string>|null
     */
    private function matchCandidate(array $identifier, array $equipamento): ?array
    {
        foreach (['numero_serie', 'imei'] as $recordField) {
            $recordRaw = trim((string) ($equipamento[$recordField] ?? ''));
            $recordNormalized = $this->normalizeIdentifier($recordRaw);
            if ($recordNormalized === '' || $recordNormalized !== $identifier['normalized']) {
                continue;
            }

            return [
                'record_field' => $recordField,
                'record_label' => $recordField === 'imei' ? 'IMEI' : ($this->isLikelyMacAddress($recordRaw, $recordNormalized) ? 'MAC' : 'numero de serie'),
                'raw' => $recordRaw,
                'normalized' => $recordNormalized,
            ];
        }

        return null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildClientesRelacionados(int $equipamentoId, int $principalId, string $principalNome): array
    {
        $clientes = [];

        if ($principalId > 0) {
            $clientes[] = [
                'id' => $principalId,
                'nome_razao' => trim($principalNome),
                'is_primary' => true,
                'is_linked' => false,
            ];
        }

        foreach ($this->equipamentoClienteModel->getClientesVinculados($equipamentoId) as $cliente) {
            $clienteId = (int) ($cliente['id'] ?? 0);
            if ($clienteId <= 0 || $clienteId === $principalId) {
                continue;
            }

            $clientes[] = [
                'id' => $clienteId,
                'nome_razao' => trim((string) ($cliente['nome_razao'] ?? '')),
                'is_primary' => false,
                'is_linked' => true,
            ];
        }

        return $clientes;
    }

    /**
     * @param array<string,string> $identifier
     * @param array<string,mixed> $equipamento
     * @param array<int,array<string,mixed>> $clientesRelacionados
     */
    private function buildConflictMessage(
        bool $sameClient,
        array $identifier,
        array $equipamento,
        array $clientesRelacionados
    ): string {
        $identificador = $identifier['label'] === 'IMEI'
            ? 'o IMEI'
            : 'o ' . $identifier['label'];

        $primaryClient = trim((string) ($equipamento['cliente_nome'] ?? ''));
        $relatedNames = array_values(array_filter(array_map(
            static fn(array $cliente): string => trim((string) ($cliente['nome_razao'] ?? '')),
            $clientesRelacionados
        )));

        $clientesTexto = !empty($relatedNames)
            ? implode(', ', $relatedNames)
            : ($primaryClient !== '' ? $primaryClient : 'outro cliente');

        if ($sameClient) {
            return sprintf(
                'Ja existe um equipamento cadastrado com %s %s para este cliente ou para um cliente ja vinculado. Use o cadastro existente em vez de criar outro.',
                $identificador,
                $identifier['raw'] !== '' ? '"' . $identifier['raw'] . '"' : 'informado'
            );
        }

        return sprintf(
            'Ja existe um equipamento cadastrado com %s %s para %s. Em vez de duplicar o cadastro, vincule este cliente ao equipamento existente.',
            $identificador,
            $identifier['raw'] !== '' ? '"' . $identifier['raw'] . '"' : 'informado',
            $clientesTexto
        );
    }
}
