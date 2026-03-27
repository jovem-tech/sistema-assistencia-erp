<?php

namespace App\Controllers;

use App\Models\LogModel;
use App\Services\OsStatusFlowService;

class OsWorkflow extends BaseController
{
    private OsStatusFlowService $workflowService;

    public function __construct()
    {
        requirePermission('os', 'editar');
        $this->workflowService = new OsStatusFlowService();
    }

    public function index()
    {
        $statuses = $this->workflowService->getAllStatusesOrdered();
        $transitionCodeMap = $this->workflowService->getTransitionMap(true);
        $idByCode = [];

        foreach ($statuses as $status) {
            $code = (string) ($status['codigo'] ?? '');
            $id = (int) ($status['id'] ?? 0);
            if ($code !== '' && $id > 0) {
                $idByCode[$code] = $id;
            }
        }

        $transitionIdMap = [];
        foreach ($transitionCodeMap as $originCode => $destinations) {
            if (!isset($idByCode[$originCode])) {
                continue;
            }

            $originId = $idByCode[$originCode];
            $transitionIdMap[$originId] = array_values(array_filter(array_map(
                static fn (string $destinationCode): int => (int) ($idByCode[$destinationCode] ?? 0),
                (array) $destinations
            )));
        }

        return view('os_workflow/index', [
            'title' => 'Fluxo de Trabalho da OS',
            'statuses' => $statuses,
            'transitionIdMap' => $transitionIdMap,
            'hasConfiguredTransitions' => $this->workflowService->hasConfiguredTransitions(),
        ]);
    }

    public function save()
    {
        $statusPayload = $this->request->getPost('status') ?? [];
        $transitionPayload = $this->request->getPost('transitions') ?? [];

        $result = $this->workflowService->saveWorkflowConfig(
            is_array($statusPayload) ? $statusPayload : [],
            is_array($transitionPayload) ? $transitionPayload : []
        );

        if (empty($result['ok'])) {
            return redirect()->back()->withInput()->with('error', $result['message'] ?? 'Nao foi possivel salvar o workflow da OS.');
        }

        LogModel::registrar('os_workflow_atualizado', 'Fluxo de trabalho da OS atualizado.');

        return redirect()->to(base_url('osworkflow'))
            ->with('success', $result['message'] ?? 'Fluxo de trabalho salvo com sucesso.');
    }
}
