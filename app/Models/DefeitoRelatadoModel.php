<?php

namespace App\Models;

use CodeIgniter\Model;

class DefeitoRelatadoModel extends Model
{
    protected $table = 'defeitos_relatados';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $allowedFields = [
        'categoria',
        'texto_relato',
        'icone',
        'ordem_exibicao',
        'ativo',
        'slug',
        'observacoes',
    ];

    protected $validationRules = [
        'categoria'      => 'required|max_length[80]',
        'texto_relato'   => 'required|max_length[255]',
        'icone'          => 'permit_empty|max_length[20]',
        'ordem_exibicao' => 'permit_empty|integer',
        'ativo'          => 'permit_empty|in_list[0,1]',
        'slug'           => 'permit_empty|max_length[120]',
    ];

    public function getAllOrdered(?string $categoria = null): array
    {
        $builder = $this;
        if ($categoria !== null && $categoria !== '') {
            $aliases = $this->getCategoryAliases($categoria);
            if (count($aliases) > 1) {
                $builder = $builder->whereIn('categoria', $aliases);
            } else {
                $builder = $builder->where('categoria', $aliases[0] ?? $categoria);
            }
        }

        return $builder->orderBy('categoria', 'ASC')
            ->orderBy('ordem_exibicao', 'ASC')
            ->orderBy('texto_relato', 'ASC')
            ->findAll();
    }

    public function getDistinctCategories(): array
    {
        $rows = $this->select('categoria')
            ->distinct()
            ->orderBy('categoria', 'ASC')
            ->findAll();

        $categorias = array_values(array_filter(array_map(function (array $row): string {
            $value = trim((string) ($row['categoria'] ?? ''));
            return $this->normalizeCategoryLabel($value);
        }, $rows)));

        $categorias = array_values(array_unique($categorias));
        usort($categorias, static fn(string $a, string $b) => strcasecmp($a, $b));

        return $categorias;
    }

    public function getActiveGrouped(): array
    {
        $rows = [];
        try {
            $rows = $this->where('ativo', 1)
                ->orderBy('categoria', 'ASC')
                ->orderBy('ordem_exibicao', 'ASC')
                ->orderBy('texto_relato', 'ASC')
                ->findAll();
        } catch (\Throwable $e) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $categoria = $this->normalizeCategoryLabel((string) ($row['categoria'] ?? 'Outros'));
            if (! isset($grouped[$categoria])) {
                $grouped[$categoria] = [
                    'categoria' => $categoria,
                    'icone'     => $row['icone'] ?: $this->defaultIconForCategory($categoria),
                    'itens'     => [],
                ];
            }
            $grouped[$categoria]['itens'][] = $row;
        }

        $preferredOrder = [
            'Energia',
            'Bateria',
            'Tela',
            'Áudio',
            'Câmera',
            'Conectividade',
            'Sistema',
            'Danos',
            'Conectores',
        ];

        uasort($grouped, function (array $a, array $b) use ($preferredOrder) {
            $idxA = array_search($a['categoria'], $preferredOrder, true);
            $idxB = array_search($b['categoria'], $preferredOrder, true);
            $idxA = ($idxA === false) ? 999 : $idxA;
            $idxB = ($idxB === false) ? 999 : $idxB;
            if ($idxA !== $idxB) {
                return $idxA <=> $idxB;
            }

            return strcasecmp($a['categoria'], $b['categoria']);
        });

        return array_values($grouped);
    }

    public function normalizeSlug(string $value): string
    {
        $slug = mb_strtolower(trim($value), 'UTF-8');
        $slug = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $slug) ?? '';
        $slug = preg_replace('/[\s_-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return mb_substr($slug, 0, 120, 'UTF-8');
    }

    public function normalizeCategoryLabel(string $category): string
    {
        $value = trim($category);
        $map = [
            'audio'  => 'Áudio',
            'áudio'  => 'Áudio',
            'camera' => 'Câmera',
            'câmera' => 'Câmera',
        ];
        $key = mb_strtolower($value, 'UTF-8');

        return $map[$key] ?? $value;
    }

    private function getCategoryAliases(string $category): array
    {
        $value = trim($category);
        $key = mb_strtolower($value, 'UTF-8');

        $groups = [
            'audio'  => ['Audio', 'audio', 'Áudio', 'áudio'],
            'áudio'  => ['Audio', 'audio', 'Áudio', 'áudio'],
            'camera' => ['Camera', 'camera', 'Câmera', 'câmera'],
            'câmera' => ['Camera', 'camera', 'Câmera', 'câmera'],
        ];

        return $groups[$key] ?? [$value];
    }

    private function defaultIconForCategory(string $category): string
    {
        $map = [
            'energia'       => '🔧',
            'bateria'       => '🔋',
            'tela'          => '📱',
            'áudio'         => '🔊',
            'audio'         => '🔊',
            'câmera'        => '📷',
            'camera'        => '📷',
            'conectividade' => '📡',
            'sistema'       => '💾',
            'danos'         => '💧',
            'conectores'    => '🔌',
        ];
        $key = mb_strtolower(trim($category), 'UTF-8');

        return $map[$key] ?? '📝';
    }
}
