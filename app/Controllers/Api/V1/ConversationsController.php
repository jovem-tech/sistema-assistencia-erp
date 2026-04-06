<?php

namespace App\Controllers\Api\V1;

use App\Models\ConversaWhatsappModel;
use App\Models\MensagemWhatsappModel;

class ConversationsController extends BaseApiController
{
    public function index()
    {
        if ($permissionError = $this->ensurePermission('clientes', 'visualizar')) {
            return $permissionError;
        }

        $q = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        $limit = max(1, min(120, (int) ($this->request->getGet('limit') ?? 50)));

        $model = new ConversaWhatsappModel();
        $builder = $model
            ->select('conversas_whatsapp.id, conversas_whatsapp.telefone, conversas_whatsapp.nome_contato, conversas_whatsapp.status, conversas_whatsapp.prioridade, conversas_whatsapp.nao_lidas, conversas_whatsapp.ultima_mensagem_em, conversas_whatsapp.aguardando_humano, conversas_whatsapp.automacao_ativa, clientes.nome_razao AS cliente_nome, usuarios.nome AS responsavel_nome')
            ->join('clientes', 'clientes.id = conversas_whatsapp.cliente_id', 'left')
            ->join('usuarios', 'usuarios.id = conversas_whatsapp.responsavel_id', 'left');

        if ($q !== '') {
            $builder->groupStart()
                ->like('conversas_whatsapp.telefone', $q)
                ->orLike('conversas_whatsapp.nome_contato', $q)
                ->orLike('clientes.nome_razao', $q)
                ->groupEnd();
        }
        if ($status !== '') {
            $builder->where('conversas_whatsapp.status', $status);
        }

        $items = $builder
            ->orderBy('conversas_whatsapp.ultima_mensagem_em', 'DESC')
            ->orderBy('conversas_whatsapp.id', 'DESC')
            ->findAll($limit);

        return $this->respondSuccess([
            'items' => $items,
            'count' => count($items),
        ]);
    }

    public function show($id = null)
    {
        if ($permissionError = $this->ensurePermission('clientes', 'visualizar')) {
            return $permissionError;
        }

        $conversaId = (int) $id;
        if ($conversaId <= 0) {
            return $this->respondError('Conversa invalida.', 422, 'CONVERSATION_INVALID_ID');
        }

        $conversaModel = new ConversaWhatsappModel();
        $conversa = $conversaModel
            ->select('conversas_whatsapp.*, clientes.nome_razao AS cliente_nome, clientes.telefone1 AS cliente_telefone, clientes.email AS cliente_email, os.id AS os_id, os.numero_os, usuarios.nome AS responsavel_nome')
            ->join('clientes', 'clientes.id = conversas_whatsapp.cliente_id', 'left')
            ->join('os', 'os.id = conversas_whatsapp.os_id_principal', 'left')
            ->join('usuarios', 'usuarios.id = conversas_whatsapp.responsavel_id', 'left')
            ->where('conversas_whatsapp.id', $conversaId)
            ->first();

        if (!$conversa) {
            return $this->respondError('Conversa nao encontrada.', 404, 'CONVERSATION_NOT_FOUND');
        }

        $mensagens = (new MensagemWhatsappModel())->byConversa($conversaId, 120);

        return $this->respondSuccess([
            'conversa' => $conversa,
            'mensagens' => $mensagens,
        ]);
    }
}

