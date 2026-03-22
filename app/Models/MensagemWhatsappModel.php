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
        $rows = $this->select('mensagens_whatsapp.*, usuarios.nome as usuario_nome')
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
        $rows = $this->select('mensagens_whatsapp.*, usuarios.nome as usuario_nome')
            ->join(
                'usuarios',
                'usuarios.id = COALESCE(mensagens_whatsapp.enviada_por_usuario_id, mensagens_whatsapp.usuario_id)',
                'left',
                false
            )
            ->where('mensagens_whatsapp.conversa_id', $conversaId)
            // Carrega a janela mais recente para evitar esconder mensagens novas
            // em conversas longas (ex.: respostas externas via WhatsApp celular).
            ->orderBy('mensagens_whatsapp.id', 'DESC')
            ->findAll($limit);

        return $this->appendOrigem(array_reverse($rows));
    }

    public function afterId(int $conversaId, int $afterId, int $limit = 120): array
    {
        $rows = $this->select('mensagens_whatsapp.*, usuarios.nome as usuario_nome')
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
            $row['origem'] = $this->resolveOrigem($row);
            $row = $this->normalizeMediaAvailability($row);
        }
        unset($row);

        return $rows;
    }

    private function normalizeMediaAvailability(array $row): array
    {
        $arquivo = trim((string) ($row['arquivo'] ?? ''));
        $anexoPath = trim((string) ($row['anexo_path'] ?? ''));
        $candidate = $arquivo !== '' ? $arquivo : $anexoPath;

        if ($candidate === '') {
            $row['arquivo_disponivel'] = 1;
            return $row;
        }

        if ($this->isExternalMediaPath($candidate)) {
            $row['arquivo_disponivel'] = 1;
            return $row;
        }

        $relative = $this->normalizeRelativeUploadPath($candidate);
        if ($relative === '') {
            $row['arquivo_disponivel'] = 0;
            $row['arquivo_original'] = $arquivo !== '' ? $arquivo : $candidate;
            $row['anexo_path_original'] = $anexoPath !== '' ? $anexoPath : $candidate;
            $row['arquivo'] = null;
            $row['anexo_path'] = null;
            return $row;
        }

        $publicRoot = $this->publicRootPath();
        $absolute = $publicRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);

        if (is_file($absolute)) {
            $row['arquivo_disponivel'] = 1;
            return $row;
        }

        // Mantem o historico da mensagem sem gerar request 404 no frontend.
        $row['arquivo_disponivel'] = 0;
        $row['arquivo_original'] = $arquivo !== '' ? $arquivo : $candidate;
        $row['anexo_path_original'] = $anexoPath !== '' ? $anexoPath : $candidate;
        $row['arquivo'] = null;
        $row['anexo_path'] = null;

        return $row;
    }

    private function normalizeRelativeUploadPath(string $path): string
    {
        $clean = trim($path);
        if ($clean === '') {
            return '';
        }

        $clean = preg_replace('/[#?].*$/', '', $clean) ?? '';
        $clean = trim(str_replace('\\', '/', $clean));
        $clean = preg_replace('#^/+?#', '', $clean) ?? '';
        $clean = preg_replace('#^\./+#', '', $clean) ?? '';

        if (str_starts_with($clean, 'public/')) {
            $clean = substr($clean, 7);
        }

        if ($clean === '' || str_contains($clean, '..')) {
            return '';
        }

        return $clean;
    }

    private function isExternalMediaPath(string $path): bool
    {
        $value = strtolower(trim($path));
        if ($value === '') {
            return false;
        }

        return str_starts_with($value, 'http://')
            || str_starts_with($value, 'https://')
            || str_starts_with($value, '//')
            || str_starts_with($value, 'data:');
    }

    private function publicRootPath(): string
    {
        if (defined('FCPATH') && is_string(FCPATH) && FCPATH !== '') {
            return rtrim(FCPATH, '\\/');
        }

        return rtrim(ROOTPATH . 'public', '\\/');
    }

    private function resolveOrigem(array $row): string
    {
        $existing = strtolower(trim((string) ($row['origem'] ?? '')));
        if (in_array($existing, ['sistema', 'externo', 'chatbot'], true)) {
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
            if ($tipoMensagem === 'outbound_externo' || str_contains($tipoMensagem, 'externo')) {
                return 'externo';
            }
            return 'sistema';
        }

        return 'externo';
    }

}
