<?php

namespace App\Models;

use CodeIgniter\Model;

class OrcamentoModel extends Model
{
    public const STATUS_RASCUNHO = 'rascunho';
    public const STATUS_ENVIADO = 'enviado';
    public const STATUS_AGUARDANDO = 'aguardando_resposta';
    public const STATUS_APROVADO = 'aprovado';
    public const STATUS_PENDENTE_OS = 'pendente_abertura_os';
    public const STATUS_REJEITADO = 'rejeitado';
    public const STATUS_VENCIDO = 'vencido';
    public const STATUS_CANCELADO = 'cancelado';
    public const STATUS_CONVERTIDO = 'convertido';

    protected $table      = 'orcamentos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'numero',
        'versao',
        'status',
        'origem',
        'cliente_id',
        'cliente_nome_avulso',
        'telefone_contato',
        'email_contato',
        'os_id',
        'equipamento_id',
        'conversa_id',
        'responsavel_id',
        'criado_por',
        'atualizado_por',
        'titulo',
        'validade_dias',
        'validade_data',
        'subtotal',
        'desconto',
        'acrescimo',
        'total',
        'prazo_execucao',
        'observacoes',
        'condicoes',
        'token_publico',
        'token_expira_em',
        'enviado_em',
        'aprovado_em',
        'rejeitado_em',
        'cancelado_em',
        'motivo_rejeicao',
        'convertido_tipo',
        'convertido_id',
    ];

    public function statusLabels(): array
    {
        return [
            self::STATUS_RASCUNHO  => 'Rascunho',
            self::STATUS_ENVIADO   => 'Enviado',
            self::STATUS_AGUARDANDO => 'Aguardando resposta',
            self::STATUS_APROVADO  => 'Aprovado',
            self::STATUS_PENDENTE_OS => 'Aprovado (pendente de OS)',
            self::STATUS_REJEITADO => 'Rejeitado',
            self::STATUS_VENCIDO   => 'Vencido',
            self::STATUS_CANCELADO => 'Cancelado',
            self::STATUS_CONVERTIDO => 'Convertido',
        ];
    }

    public function isLockedStatus(string $status): bool
    {
        return in_array($status, [self::STATUS_APROVADO, self::STATUS_PENDENTE_OS, self::STATUS_CONVERTIDO], true);
    }
}
