<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SyncEvolvedRbacModules extends Migration
{
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $moduleRegistry = [
        'crm' => [
            'nome' => 'CRM',
            'icone' => 'bi-diagram-3-fill',
            'ordem_menu' => 25,
            'permissoes' => ['visualizar', 'criar', 'editar'],
            'inherits' => [
                'visualizar' => ['clientes:visualizar'],
                'criar' => ['clientes:visualizar'],
                'editar' => ['clientes:visualizar', 'clientes:editar'],
            ],
        ],
        'atendimento_whatsapp' => [
            'nome' => 'Atendimento WhatsApp',
            'icone' => 'bi-whatsapp',
            'ordem_menu' => 26,
            'permissoes' => ['visualizar', 'editar'],
            'inherits' => [
                'visualizar' => ['clientes:visualizar'],
                'editar' => ['clientes:visualizar', 'clientes:editar'],
            ],
        ],
        'precificacao' => [
            'nome' => 'Precificacao',
            'icone' => 'bi-calculator',
            'ordem_menu' => 82,
            'permissoes' => ['visualizar', 'editar'],
            'inherits' => [
                'visualizar' => ['orcamentos:visualizar'],
                'editar' => ['orcamentos:editar'],
            ],
        ],
    ];

    public function up()
    {
        if (
            !$this->db->tableExists('modulos')
            || !$this->db->tableExists('permissoes')
            || !$this->db->tableExists('grupo_permissoes')
        ) {
            return;
        }

        $permissionIds = $this->loadPermissionIds();
        if ($permissionIds === []) {
            return;
        }

        $moduleIds = [];
        foreach ($this->moduleRegistry as $slug => $definition) {
            $moduleIds[$slug] = $this->ensureModule($slug, $definition);
        }

        foreach ($this->moduleRegistry as $slug => $definition) {
            $moduleId = (int) ($moduleIds[$slug] ?? 0);
            if ($moduleId <= 0) {
                continue;
            }

            $this->seedInheritedPermissions($moduleId, $definition, $permissionIds);
        }
    }

    public function down()
    {
        if (
            !$this->db->tableExists('modulos')
            || !$this->db->tableExists('grupo_permissoes')
        ) {
            return;
        }

        foreach (array_keys($this->moduleRegistry) as $slug) {
            $module = $this->db->table('modulos')
                ->select('id')
                ->where('slug', $slug)
                ->get()
                ->getRowArray();

            $moduleId = (int) ($module['id'] ?? 0);
            if ($moduleId <= 0) {
                continue;
            }

            $this->db->table('grupo_permissoes')
                ->where('modulo_id', $moduleId)
                ->delete();

            $this->db->table('modulos')
                ->where('id', $moduleId)
                ->delete();
        }
    }

    /**
     * @return array<string,int>
     */
    private function loadPermissionIds(): array
    {
        $rows = $this->db->table('permissoes')
            ->select('id, slug')
            ->get()
            ->getResultArray();

        $permissionIds = [];
        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            $id = (int) ($row['id'] ?? 0);
            if ($slug === '' || $id <= 0) {
                continue;
            }

            $permissionIds[$slug] = $id;
        }

        return $permissionIds;
    }

    /**
     * @param array<string,mixed> $definition
     */
    private function ensureModule(string $slug, array $definition): int
    {
        $table = $this->db->table('modulos');
        $existing = $table->where('slug', $slug)->get()->getRowArray();

        $payload = [
            'nome' => (string) ($definition['nome'] ?? ucfirst($slug)),
            'slug' => $slug,
            'icone' => (string) ($definition['icone'] ?? 'bi-grid'),
            'ordem_menu' => (int) ($definition['ordem_menu'] ?? 0),
            'ativo' => 1,
        ];

        if ($existing) {
            $id = (int) ($existing['id'] ?? 0);
            if ($id > 0) {
                $table->where('id', $id)->update($payload);
                return $id;
            }
        }

        $table->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,int> $permissionIds
     */
    private function seedInheritedPermissions(int $moduleId, array $definition, array $permissionIds): void
    {
        $allowedPermissions = array_values(array_filter(
            array_map(
                static fn ($slug): string => trim((string) $slug),
                (array) ($definition['permissoes'] ?? [])
            ),
            static fn (string $slug): bool => $slug !== ''
        ));

        if ($allowedPermissions === []) {
            return;
        }

        $inheritanceMap = is_array($definition['inherits'] ?? null) ? $definition['inherits'] : [];

        foreach ($allowedPermissions as $permissionSlug) {
            $permissionId = (int) ($permissionIds[$permissionSlug] ?? 0);
            if ($permissionId <= 0) {
                continue;
            }

            $sourceDescriptors = array_values(array_filter(
                array_map(
                    static fn ($value): string => trim((string) $value),
                    (array) ($inheritanceMap[$permissionSlug] ?? [])
                ),
                static fn (string $value): bool => $value !== ''
            ));

            $groupIds = $this->resolveEligibleGroups($sourceDescriptors);
            if (!in_array(1, $groupIds, true)) {
                $groupIds[] = 1;
            }

            foreach ($groupIds as $groupId) {
                $this->ensureGroupPermission($groupId, $moduleId, $permissionId);
            }
        }
    }

    /**
     * @param array<int,string> $sourceDescriptors
     * @return array<int,int>
     */
    private function resolveEligibleGroups(array $sourceDescriptors): array
    {
        $groupIds = [];

        foreach ($sourceDescriptors as $descriptor) {
            [$moduleSlug, $permissionSlug] = array_pad(explode(':', $descriptor, 2), 2, '');
            $moduleSlug = trim($moduleSlug);
            $permissionSlug = trim($permissionSlug);
            if ($moduleSlug === '' || $permissionSlug === '') {
                continue;
            }

            $rows = $this->db->table('grupo_permissoes gp')
                ->distinct()
                ->select('gp.grupo_id')
                ->join('modulos m', 'm.id = gp.modulo_id', 'inner')
                ->join('permissoes p', 'p.id = gp.permissao_id', 'inner')
                ->where('m.slug', $moduleSlug)
                ->where('p.slug', $permissionSlug)
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $groupId = (int) ($row['grupo_id'] ?? 0);
                if ($groupId > 0 && !in_array($groupId, $groupIds, true)) {
                    $groupIds[] = $groupId;
                }
            }
        }

        return $groupIds;
    }

    private function ensureGroupPermission(int $groupId, int $moduleId, int $permissionId): void
    {
        $exists = $this->db->table('grupo_permissoes')
            ->where('grupo_id', $groupId)
            ->where('modulo_id', $moduleId)
            ->where('permissao_id', $permissionId)
            ->countAllResults();

        if ($exists > 0) {
            return;
        }

        $this->db->table('grupo_permissoes')->insert([
            'grupo_id' => $groupId,
            'modulo_id' => $moduleId,
            'permissao_id' => $permissionId,
        ]);
    }
}
