<?php

namespace App\Controllers;

use App\Models\EquipamentoModeloModel;

/**
 * Endpoint de busca hibrida de modelos (local + sugestoes externas).
 */
class ModeloBridge extends BaseController
{
    private const MIN_CHARS = 3;
    private const MAX_SUGESTOES = 5;
    private const RELATION_TABLE = 'equipamentos_catalogo_relacoes';

    public function buscar()
    {
        $query = trim((string) $this->request->getGet('q'));
        $marca = trim((string) $this->request->getGet('marca'));
        $marcaId = (int) ($this->request->getGet('marca_id') ?? 0);
        $tipo = trim((string) $this->request->getGet('tipo'));
        $tipoId = (int) ($this->request->getGet('tipo_id') ?? 0);

        $locais = $this->buscarLocais($query, $marcaId, $tipoId, $marca, $tipo);

        $externos = [];
        if (strlen($query) >= self::MIN_CHARS && count($locais) < self::MAX_SUGESTOES) {
            $externos = $this->buscarSugestoesGoogle($query, $marca, $tipo);
        }

        $results = [];
        if (!empty($locais)) {
            $results[] = [
                'text' => 'Modelos Cadastrados',
                'children' => $locais,
            ];
        }

        if (!empty($externos)) {
            $results[] = [
                'text' => 'Sugestoes da Internet (Auto-cadastro)',
                'children' => $externos,
            ];
        }

        return $this->response->setJSON(['results' => $results]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buscarLocais(string $query, int $marcaId, int $tipoId, string $marca, string $tipo): array
    {
        $modeloModel = new EquipamentoModeloModel();
        $builder = $modeloModel
            ->select('equipamentos_modelos.id, equipamentos_modelos.nome as text')
            ->where('equipamentos_modelos.ativo', 1);

        if ($marcaId > 0) {
            $builder->where('equipamentos_modelos.marca_id', $marcaId);
        }

        $usingRelationFilter = false;
        if ($marcaId > 0 && $tipoId > 0 && $this->hasCatalogoRelacaoTable()) {
            $builder
                ->join(
                    self::RELATION_TABLE . ' rel',
                    'rel.modelo_id = equipamentos_modelos.id AND rel.marca_id = equipamentos_modelos.marca_id',
                    'inner'
                )
                ->where('rel.tipo_id', $tipoId)
                ->where('rel.ativo', 1);
            $usingRelationFilter = true;
        }

        if ($query !== '') {
            $builder->like('equipamentos_modelos.nome', $query);
        }

        $locais = [];
        if ($marcaId > 0 || $query !== '') {
            $limit = ($query === '') ? 50 : 10;
            $locais = $builder
                ->orderBy('equipamentos_modelos.nome', 'ASC')
                ->limit($limit)
                ->find();
        }

        // Fallback legado: se filtro tipo+marca ainda nao tiver relacao consolidada,
        // mantem o comportamento por marca para evitar lista vazia.
        if ($usingRelationFilter && empty($locais)) {
            $fallbackBuilder = $modeloModel
                ->select('equipamentos_modelos.id, equipamentos_modelos.nome as text')
                ->where('equipamentos_modelos.ativo', 1)
                ->where('equipamentos_modelos.marca_id', $marcaId);

            if ($query !== '') {
                $fallbackBuilder->like('equipamentos_modelos.nome', $query);
            }

            $limit = ($query === '') ? 50 : 10;
            $locais = $fallbackBuilder
                ->orderBy('equipamentos_modelos.nome', 'ASC')
                ->limit($limit)
                ->find();
        }

        foreach ($locais as &$item) {
            $item['source'] = 'local';
            $item['modelo'] = (string) ($item['text'] ?? '');
            $item['marca'] = $marca;
            $item['tipo'] = $tipo;
        }
        unset($item);

        return $locais;
    }

    private function hasCatalogoRelacaoTable(): bool
    {
        try {
            return \Config\Database::connect()->tableExists(self::RELATION_TABLE);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buscarSugestoesGoogle(string $query, string $marca = '', string $tipo = ''): array
    {
        $parts = array_filter([$this->normalizarTipo($tipo), $marca, $query]);
        $searchQuery = implode(' ', $parts);

        try {
            $client = \Config\Services::curlrequest();
            $response = $client->get('https://suggestqueries.google.com/complete/search', [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ],
                'query' => [
                    'client' => 'chrome',
                    'q' => $searchQuery,
                    'oe' => 'utf8',
                    'hl' => 'pt-BR',
                ],
                'verify' => false,
                'timeout' => 5,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('warning', "[ModeloBridge] Google Suggest status {$response->getStatusCode()} para query: {$searchQuery}");
                return [];
            }

            $data = json_decode($response->getBody(), true);
            if (!isset($data[1]) || !is_array($data[1])) {
                return [];
            }

            $sugestoes = [];
            $duplicados = [];

            foreach ($data[1] as $item) {
                if ($this->pareceUrl((string) $item)) {
                    continue;
                }

                $modeloOnly = $this->extrairModeloApenas((string) $item, $marca, $tipo);
                if ($modeloOnly === '' || strlen($modeloOnly) < 2) {
                    continue;
                }

                $chave = mb_strtolower((string) preg_replace('/\s+/', ' ', $modeloOnly), 'UTF-8');
                if (in_array($chave, $duplicados, true)) {
                    continue;
                }
                $duplicados[] = $chave;

                $nomeFormatado = mb_convert_case($modeloOnly, MB_CASE_TITLE, 'UTF-8');
                $sugestoes[] = [
                    'id' => 'EXT|GGL_' . substr(md5($modeloOnly), 0, 8),
                    'text' => $nomeFormatado,
                    'modelo' => $nomeFormatado,
                    'marca' => $marca,
                    'tipo' => $tipo,
                    'source' => 'google',
                ];

                if (count($sugestoes) >= self::MAX_SUGESTOES) {
                    break;
                }
            }

            return $sugestoes;
        } catch (\Throwable $e) {
            log_message('error', '[ModeloBridge] Excecao: ' . $e->getMessage());
            return [];
        }
    }

    private function normalizarTipo(string $tipo): string
    {
        $mapa = [
            'smartfone' => 'smartphone',
            'smartphone' => 'smartphone',
            'celular' => 'smartphone',
            'tablet' => 'tablet',
            'notebook' => 'notebook',
            'laptop' => 'notebook',
            'computador' => 'computador',
            'desktop' => 'computador',
            'pc' => 'computador',
            'smart tv' => 'smart tv',
            'tv' => 'tv',
            'console' => 'console',
            'videogame' => 'videogame',
            'camera' => 'camera',
            'impressora' => 'impressora',
            'monitor' => 'monitor',
        ];

        $tipoNormalizado = mb_strtolower(trim($tipo), 'UTF-8');
        foreach ($mapa as $chave => $valor) {
            if (str_contains($tipoNormalizado, $chave)) {
                return $valor;
            }
        }

        return $tipoNormalizado;
    }

    private function pareceUrl(string $texto): bool
    {
        return (bool) preg_match(
            '#(https?://|www\.|\.com|\.br|mercadolivre|amazon|americanas|shopee|casasbahia|magalu)#i',
            $texto
        );
    }

    private function extrairModeloApenas(string $titulo, string $marca = '', string $tipo = ''): string
    {
        $termosAnuncio = [
            'Novo',
            'Original',
            'Lacrado',
            'Com Garantia',
            'Nota Fiscal',
            'Barato',
            'Promocao',
            'Frete Gratis',
            'Oferta',
            'Desbloqueado',
            'Nacional',
            'Vitrine',
            'Semi Novo',
            'Seminovo',
            'usado',
            'Brinde',
            'pronta entrega',
            'comprar',
            'preco',
            'melhor',
        ];
        $titulo = str_ireplace($termosAnuncio, '', $titulo);

        if ($tipo !== '') {
            $tipoNorm = $this->normalizarTipo($tipo);
            $titulo = preg_replace('#\b' . preg_quote($tipoNorm, '#') . '\b#iu', '', $titulo) ?? $titulo;
            $titulo = preg_replace('#\b' . preg_quote($tipo, '#') . '\b#iu', '', $titulo) ?? $titulo;
        }

        if ($marca !== '') {
            $titulo = preg_replace('#\b' . preg_quote($marca, '#') . '\b#iu', '', $titulo) ?? $titulo;
        }

        $titulo = preg_replace('/^[-_,\.\s]+/', '', $titulo) ?? $titulo;
        $titulo = preg_replace('/\s+/', ' ', $titulo) ?? $titulo;

        return trim(mb_strimwidth($titulo, 0, 80, '...'));
    }
}
