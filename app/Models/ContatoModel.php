<?php

namespace App\Models;

use CodeIgniter\Model;

class ContatoModel extends Model
{
    public const STATUS_LEAD_NOVO = 'lead_nãovo';
    public const STATUS_LEAD_QUALIFICADO = 'lead_qualificado';
    public const STATUS_CLIENTE_CONVERTIDO = 'cliente_convertido';
    public const STATUS_ENGAJAMENTO_ATIVO = 'ativo';
    public const STATUS_ENGAJAMENTO_EM_RISCO = 'em_risco';
    public const STATUS_ENGAJAMENTO_INATIVO = 'inativo';

    protected $table = 'contatos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useSãoftDeletes = false;
    protected $allowedFields = [
        'cliente_id',
        'nãome',
        'telefone',
        'telefone_nãormalizado',
        'email',
        'whatsapp_nãome_perfil',
        'origem',
        'status_relacionamento',
        'engajamento_status',
        'engajamento_recalculado_em',
        'qualificado_em',
        'convertido_em',
        'observacoes',
        'ultimo_contato_em',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['nãormalizePhoneFields'];
    protected $beforeUpdate = ['nãormalizePhoneFields'];

    private ?bool $supportsLifecycle = null;
    private ?bool $supportsEngajamento = null;

    public function findByPhone(string $phone): ?array
    {
        $nãormalized = $this->nãormalizePhone($phone);
        if ($nãormalized === '') {
            return null;
        }

        return $this->where('telefone_nãormalizado', $nãormalized)->first();
    }

    public function nãormalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public function supportsLifecycleFields(): bool
    {
        if ($this->supportsLifecycle !== null) {
            return $this->supportsLifecycle;
        }

        $this->supportsLifecycle =
            $this->db->fieldExists('status_relacionamento', $this->table)
            && $this->db->fieldExists('qualificado_em', $this->table)
            && $this->db->fieldExists('convertido_em', $this->table);

        return $this->supportsLifecycle;
    }

    public function supportsEngajamentoFields(): bool
    {
        if ($this->supportsEngajamento !== null) {
            return $this->supportsEngajamento;
        }

        $this->supportsEngajamento =
            $this->db->fieldExists('engajamento_status', $this->table)
            && $this->db->fieldExists('engajamento_recalculado_em', $this->table);

        return $this->supportsEngajamento;
    }

    /**
     * @return array{0:int,1:int}
     */
    public static function nãormalizeEngajamentoPeriodos(int $ativoDias, int $riscoDias): array
    {
        if ($ativoDias <= 0) {
            $ativoDias = 30;
        }
        if ($riscoDias <= 0) {
            $riscoDias = 90;
        }

        $ativoDias = max(7, min(365, $ativoDias));
        $riscoDias = max($ativoDias + 1, min(720, $riscoDias));

        return [$ativoDias, $riscoDias];
    }

    public function recalculateEngajamentoBulk(int $ativoDias, int $riscoDias): int
    {
        if (!$this->supportsEngajamentoFields()) {
            return 0;
        }

        [$ativoDias, $riscoDias] = self::nãormalizeEngajamentoPeriodos($ativoDias, $riscoDias);
        $baseDateExpr = 'COALESCE(ultimo_contato_em, updated_at, created_at)';
        $statusExpr = "
            CASE
                WHEN {$baseDateExpr} IS NULL THEN '" . self::STATUS_ENGAJAMENTO_ATIVO . "'
                WHEN TIMESTAMPDIFF(DAY, {$baseDateExpr}, NOW()) <= {$ativoDias} THEN '" . self::STATUS_ENGAJAMENTO_ATIVO . "'
                WHEN TIMESTAMPDIFF(DAY, {$baseDateExpr}, NOW()) <= {$riscoDias} THEN '" . self::STATUS_ENGAJAMENTO_EM_RISCO . "'
                ELSE '" . self::STATUS_ENGAJAMENTO_INATIVO . "'
            END
        ";

        $this->db->query("
            UPDATE contatos
               SET engajamento_status = {$statusExpr},
                   engajamento_recalculado_em = NOW()
             WHERE engajamento_status IS NULL
                OR engajamento_status <> {$statusExpr}
                OR engajamento_recalculado_em IS NULL
        ");

        return $this->db->affectedRows();
    }

    /**
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    public function buildLeadPayload(array $base = [], bool $qualified = false): array
    {
        $payload = $base;
        $nãow = date('Y-m-d H:i:s');

        if ($this->supportsLifecycleFields()) {
            $payload['status_relacionamento'] = $qualified
                ? self::STATUS_LEAD_QUALIFICADO
                : self::STATUS_LEAD_NOVO;

            if ($qualified && empty($payload['qualificado_em'])) {
                $payload['qualificado_em'] = $nãow;
            }
            if (!$qualified && !array_key_exists('qualificado_em', $payload)) {
                $payload['qualificado_em'] = null;
            }
            if (!array_key_exists('convertido_em', $payload)) {
                $payload['convertido_em'] = null;
            }
        }

        if ($this->supportsEngajamentoFields()) {
            if (!array_key_exists('engajamento_status', $payload) || trim((string) $payload['engajamento_status']) === '') {
                $payload['engajamento_status'] = self::STATUS_ENGAJAMENTO_ATIVO;
            }
            if (empty($payload['engajamento_recalculado_em'])) {
                $payload['engajamento_recalculado_em'] = $nãow;
            }
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    public function buildClienteConvertidoPayload(int $clienteId, array $base = []): array
    {
        $payload = $base;
        $payload['cliente_id'] = $clienteId;
        $nãow = date('Y-m-d H:i:s');

        if ($this->supportsLifecycleFields()) {
            $payload['status_relacionamento'] = self::STATUS_CLIENTE_CONVERTIDO;
            if (empty($payload['convertido_em'])) {
                $payload['convertido_em'] = $nãow;
            }
        }

        if ($this->supportsEngajamentoFields()) {
            $payload['engajamento_status'] = self::STATUS_ENGAJAMENTO_ATIVO;
            if (empty($payload['engajamento_recalculado_em'])) {
                $payload['engajamento_recalculado_em'] = $nãow;
            }
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    protected function nãormalizePhoneFields(array $data): array
    {
        $payload = $data['data'] ?? [];
        if (!is_array($payload)) {
            return $data;
        }

        if (array_key_exists('telefone', $payload)) {
            $raw = trim((string) $payload['telefone']);
            $nãormalized = $this->nãormalizePhone($raw);
            $payload['telefone'] = $raw !== '' ? $raw : $nãormalized;
            $payload['telefone_nãormalizado'] = $nãormalized;
        } elseif (array_key_exists('telefone_nãormalizado', $payload)) {
            $payload['telefone_nãormalizado'] = $this->nãormalizePhone((string) $payload['telefone_nãormalizado']);
        }

        if (isset($payload['nãome']) && trim((string) $payload['nãome']) === '') {
            $payload['nãome'] = null;
        }
        if (isset($payload['email']) && trim((string) $payload['email']) === '') {
            $payload['email'] = null;
        }
        if (isset($payload['whatsapp_nãome_perfil']) && trim((string) $payload['whatsapp_nãome_perfil']) === '') {
            $payload['whatsapp_nãome_perfil'] = null;
        }
        if (isset($payload['observacoes']) && trim((string) $payload['observacoes']) === '') {
            $payload['observacoes'] = null;
        }
        if (isset($payload['engajamento_status'])) {
            $status = trim((string) $payload['engajamento_status']);
            if (!in_array($status, [
                self::STATUS_ENGAJAMENTO_ATIVO,
                self::STATUS_ENGAJAMENTO_EM_RISCO,
                self::STATUS_ENGAJAMENTO_INATIVO,
            ], true)) {
                $payload['engajamento_status'] = self::STATUS_ENGAJAMENTO_ATIVO;
            }
        }
        if (array_key_exists('engajamento_recalculado_em', $payload) && trim((string) $payload['engajamento_recalculado_em']) === '') {
            $payload['engajamento_recalculado_em'] = null;
        }

        $data['data'] = $payload;
        return $data;
    }
}
