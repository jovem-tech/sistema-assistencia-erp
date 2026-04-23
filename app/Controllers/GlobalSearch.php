<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\GlobalSearchService;

class GlobalSearch extends BaseController
{
    protected $searchService;

    public function __construct()
    {
        $this->searchService = new GlobalSearchService();
    }

    /**
     * Endpoint para busca AJAX
     */
    public function index()
    {
        $term = $this->request->getGet('q');
        $filter = $this->request->getGet('filter') ?? 'all';

        if (empty($term) || strlen($term) < 2) {
            return $this->response->setJSON([]);
        }

        $results = $this->searchService->search($term, $filter);

        return $this->response->setJSON($results);
    }

    /**
     * Página de resultados (se for necessário fallback ou expansão)
     */
    public function results()
    {
        $term = $this->request->getGet('q');
        $filter = $this->request->getGet('filter') ?? 'all';

        $data = [
            'title' => 'Resultados da Busca',
            'term' => $term,
            'filter' => $filter,
            'results' => $this->searchService->search($term, $filter)
        ];

        return view('search/results', $data);
    }
}
