<?php

namespace App\Controllers;

use App\Models\OsPdfTemplateModel;
use App\Models\WhatsappTemplateModel;
use App\Services\OsPdfTemplateService;

class ConhecimentoTemplates extends BaseController
{
    private OsPdfTemplateModel $pdfTemplateModel;
    private WhatsappTemplateModel $whatsappTemplateModel;
    private OsPdfTemplateService $pdfTemplateService;

    public function __construct()
    {
        $this->pdfTemplateModel = new OsPdfTemplateModel();
        $this->whatsappTemplateModel = new WhatsappTemplateModel();
        $this->pdfTemplateService = new OsPdfTemplateService();
    }

    public function pdfs()
    {
        requirePermission('os', 'editar');

        $editId = (int) ($this->request->getGet('edit') ?? 0);
        $editItem = $editId > 0 ? $this->pdfTemplateModel->find($editId) : null;

        return view('conhecimento/pdf_templates', [
            'title' => 'Modelos PDF da OS',
            'templates' => $this->pdfTemplateModel->getAllOrdered(),
            'editItem' => $editItem,
            'placeholders' => $this->pdfTemplateService->placeholderCatalog(),
        ]);
    }

    public function savePdf()
    {
        requirePermission('os', 'editar');

        $id = (int) ($this->request->getPost('id') ?? 0);
        $payload = [
            'codigo' => strtolower(trim((string) $this->request->getPost('codigo'))),
            'nome' => trim((string) $this->request->getPost('nome')),
            'descricao' => trim((string) $this->request->getPost('descricao')),
            'conteudo_html' => trim((string) $this->request->getPost('conteudo_html')),
            'ordem' => (int) ($this->request->getPost('ordem') ?? 0),
            'ativo' => (int) ($this->request->getPost('ativo') ? 1 : 0),
        ];

        if ($payload['codigo'] === '' || $payload['nome'] === '' || $payload['conteudo_html'] === '') {
            return redirect()->to(base_url('conhecimento/modelos-pdf' . ($id > 0 ? '?edit=' . $id : '')))
                ->with('error', 'Preencha código, nome e conteúdo HTML do modelo PDF.');
        }

        $exists = $this->pdfTemplateModel->where('codigo', $payload['codigo']);
        if ($id > 0) {
            $exists->where('id !=', $id);
        }
        if ($exists->countAllResults() > 0) {
            return redirect()->to(base_url('conhecimento/modelos-pdf' . ($id > 0 ? '?edit=' . $id : '')))
                ->with('error', 'Já existe um modelo PDF com este código.');
        }

        if ($id > 0) {
            $this->pdfTemplateModel->update($id, $payload);
            $message = 'Modelo PDF atualizado com sucesso.';
        } else {
            $this->pdfTemplateModel->insert($payload);
            $message = 'Modelo PDF criado com sucesso.';
        }

        return redirect()->to(base_url('conhecimento/modelos-pdf'))->with('success', $message);
    }

    public function togglePdf(int $id)
    {
        requirePermission('os', 'editar');

        $item = $this->pdfTemplateModel->find($id);
        if (!$item) {
            return redirect()->to(base_url('conhecimento/modelos-pdf'))->with('error', 'Modelo PDF não encontrado.');
        }

        $this->pdfTemplateModel->update($id, ['ativo' => empty($item['ativo']) ? 1 : 0]);

        return redirect()->to(base_url('conhecimento/modelos-pdf'))
            ->with('success', empty($item['ativo']) ? 'Modelo PDF ativado.' : 'Modelo PDF desativado.');
    }

    public function whatsapp()
    {
        requirePermission('os', 'editar');

        $editId = (int) ($this->request->getGet('edit') ?? 0);
        $editItem = $editId > 0 ? $this->whatsappTemplateModel->find($editId) : null;

        return view('conhecimento/whatsapp_templates', [
            'title' => 'Templates de WhatsApp',
            'templates' => $this->whatsappTemplateModel->orderBy('nome', 'ASC')->findAll(),
            'editItem' => $editItem,
            'placeholders' => $this->whatsappPlaceholders(),
        ]);
    }

    public function saveWhatsapp()
    {
        requirePermission('os', 'editar');

        $id = (int) ($this->request->getPost('id') ?? 0);
        $payload = [
            'codigo' => strtolower(trim((string) $this->request->getPost('codigo'))),
            'nome' => trim((string) $this->request->getPost('nome')),
            'evento' => trim((string) $this->request->getPost('evento')),
            'conteudo' => trim((string) $this->request->getPost('conteudo')),
            'ativo' => (int) ($this->request->getPost('ativo') ? 1 : 0),
        ];

        if ($payload['codigo'] === '' || $payload['nome'] === '' || $payload['conteudo'] === '') {
            return redirect()->to(base_url('conhecimento/templates-whatsapp' . ($id > 0 ? '?edit=' . $id : '')))
                ->with('error', 'Preencha código, nome e conteúdo do template de WhatsApp.');
        }

        $exists = $this->whatsappTemplateModel->where('codigo', $payload['codigo']);
        if ($id > 0) {
            $exists->where('id !=', $id);
        }
        if ($exists->countAllResults() > 0) {
            return redirect()->to(base_url('conhecimento/templates-whatsapp' . ($id > 0 ? '?edit=' . $id : '')))
                ->with('error', 'Já existe um template de WhatsApp com este código.');
        }

        if ($id > 0) {
            $this->whatsappTemplateModel->update($id, $payload);
            $message = 'Template de WhatsApp atualizado com sucesso.';
        } else {
            $this->whatsappTemplateModel->insert($payload);
            $message = 'Template de WhatsApp criado com sucesso.';
        }

        return redirect()->to(base_url('conhecimento/templates-whatsapp'))->with('success', $message);
    }

    public function toggleWhatsapp(int $id)
    {
        requirePermission('os', 'editar');

        $item = $this->whatsappTemplateModel->find($id);
        if (!$item) {
            return redirect()->to(base_url('conhecimento/templates-whatsapp'))->with('error', 'Template de WhatsApp não encontrado.');
        }

        $this->whatsappTemplateModel->update($id, ['ativo' => empty($item['ativo']) ? 1 : 0]);

        return redirect()->to(base_url('conhecimento/templates-whatsapp'))
            ->with('success', empty($item['ativo']) ? 'Template de WhatsApp ativado.' : 'Template de WhatsApp desativado.');
    }

    private function whatsappPlaceholders(): array
    {
        return [
            ['token' => '{{numero_os}}', 'descricao' => 'Número da ordem de serviço.'],
            ['token' => '{{data_abertura}}', 'descricao' => 'Data/hora da abertura da OS.'],
            ['token' => '{{equipamento}}', 'descricao' => 'Resumo de marca e modelo do equipamento.'],
            ['token' => '{{cliente}}', 'descricao' => 'Nome do cliente.'],
            ['token' => '{{valor_final}}', 'descricao' => 'Valor final da OS formatado.'],
            ['token' => '{{status}}', 'descricao' => 'Status atual da OS.'],
            ['token' => '{{pdf_url}}', 'descricao' => 'URL do PDF quando houver anexo digital.'],
        ];
    }
}
