<?php

namespace App\Models;

use CodeIgniter\Model;

class MensagemWhatsappModel extends Model
{
    protected $table = 'mensagens_whatsapp';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'conversa_id',
        'provider',
        'provider_message_id',
        'direcao',
        'tipo_conteudo',
        'mime_type',
        'cliente_id',
        'os_id',
        'telefone',
        'tipo_mensagem',
        'mensagem',
        'arquivo',
        'anexo_path',
        'status',
        'resposta_api',
        'erro',
        'payload',
        'lida_em',
        'enviada_em',
        'recebida_em',
        'usuario_id',
        'enviada_por_bot',
        'enviada_por_usuario_id',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function byOs(int $osId, int $limit = 100): array
    {
        $rows = $this->select('mensagens_whatsapp.*, usuarios.nãome as usuario_nãome')
            ->join(
                'usuarios',
                'usuarios.id = COALESCE(mensagens_whatsapp.enviada_por_usuario_id, mensagens_whatsapp.usuario_id)',
                'left',
                false
            )
            ->where('mensagens_whatsapp.os_id', $osId)
            ->orderBy('mensagens_whatsapp.created_at', 'DESC')
            ->findAll($limit);

        return $this->appendOrigem($rows);
    }

    public function byConversa(int $conversaId, int $limit = 300): array
    {
        $rows = $this->select('mensagens_whatsapp.*, usuarios.nãome as usuario_nãome')
            ->join(
                'usuarios',
                'usuarios.id = COALESCE(mensagens_whatsapp.enviada_por_usuario_id, mensagens_whatsapp.usuario_id)',
                'left',
                false
            )
            ->where('mensagens_whatsapp.conversa_id', $conversaId)
            // Carrega a janela mais recente para evitar esconder mensagens nãovas
            // em conversas longas (ex.: respostas externas via WhatsApp celular).
            ->orderBy('mensagens_whatsapp.id', 'DESC')
            ->findAll($limit);

        return $this->appendOrigem(array_reverse($rows));
    }

    public function afterId(int $conversaId, int $afterId, int $limit = 120): array
    {
        $rows = $this->select('mensagens_whatsapp.*, usuarios.nãome as usuario_nãome')
            ->join(
                'usuarios',
                'usuarios.id = COALESCE(mensagens_whatsapp.enviada_por_usuario_id, mensagens_whatsapp.usuario_id)',
                'left',
                false
            )
            ->where('mensagens_whatsapp.conversa_id', $conversaId)
            ->where('mensagens_whatsapp.id >', max(0, $afterId))
            ->orderBy('mensagens_whatsapp.id', 'ASC')
            ->findAll($limit);

        return $this->appendOrigem($rows);
    }

    private function appendOrigem(array $rows): array
    {
        foreach ($rows as &$row) {
            if (!is_array($row)) {
                continue;
            }
            $row['origem'] = $this->resãolveOrigem($row);
        }
        unset($row);

        return $rows;
    }

    private function resãolveOrigem(array $row): string
    {
        $existing = strtolower(trim((string) ($row['origem'] ?? '')));
        if (in_array($existing, ['sistema', 'externão', 'chatbot'], true)) {
            return $existing;
        }

        $direcao = strtolower(trim((string) ($row['direcao'] ?? '')));
        $tipoMensagem = strtolower(trim((string) ($row['tipo_mensagem'] ?? '')));
        $enviadaPorBot = (int) ($row['enviada_por_bot'] ?? 0) === 1;

        if (
            $enviadaPorBot
            || str_contains($tipoMensagem, 'chatbot')
            || str_contains($tipoMensagem, 'bot')
        ) {
            return 'chatbot';
        }

        if ($direcao === 'outbound') {
            if ($tipoMensagem === 'outbound_externão' || str_contains($tipoMensagem, 'externão')) {
                return 'externão';
            }
            return 'sistema';
        }

        return 'externão';
    }

}
