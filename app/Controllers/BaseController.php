<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;
    protected $helpers = ['form', 'url', 'sistema'];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do nãot put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do nãot edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Processa requisições DataTables não lado do Servidor.
     * Retorna o JSON correspondente.
     */
    protected function respondDatatable($model, $columns, $searchableColumns, $callback)
    {
        $request = $this->request;

        $draw = intval($request->getPostGet('draw'));
        $start = intval($request->getPostGet('start'));
        $length = intval($request->getPostGet('length'));
        $length = $length < 1 ? 10 : $length;
        $search = $request->getPostGet('search')['value'] ?? '';
        $order = $request->getPostGet('order') ?? [];

        // Backup do builder para contagens
        $totalRecords = $model->countAllResults(false);

        if (!empty($search)) {
            $model->groupStart();
            foreach ($searchableColumns as $col) {
                $model->orLike($col, $search);
            }
            $model->groupEnd();
        }

        $filteredRecords = $model->countAllResults(false);

        if (!empty($order)) {
            $colIdx = intval($order[0]['column']);
            $dir = $order[0]['dir'] === 'asc' ? 'asc' : 'desc';
            if (isset($columns[$colIdx])) {
                $model->orderBy($columns[$colIdx], $dir);
            }
        }

        $model->limit($length, $start);
        $dados = $model->find();

        $rows = [];
        foreach ($dados as $row) {
            $rows[] = $callback($row);
        }

        return $this->response->setJSON([
            "draw" => $draw,
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $filteredRecords,
            "data" => $rows
        ]);
    }
}
