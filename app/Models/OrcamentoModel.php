<?php

namespace App\Models;

use CodeIgniter\Model;

class OrcamentoModel extends Model
{
    public const TIPO_PREVIO = 'previo';
    public const TIPO_ASSISTENCIA = 'assistencia';

    public const STATUS_RASCUNHO = 'rascunho';
    public const STATUS_PENDENTE_ENVIO = 'pendente_envio';
    public const STATUS_ENVIADO = 'enviado';
    public const STATUS_AGUARDANDO = 'aguardando_resposta';
    public const STATUS_AGUARDANDO_PACOTE = 'aguardando_pacote';
    public const STATUS_PACOTE_APROVADO = 'pacote_aprovado';
    public const STATUS_PENDENTE = 'pendente';
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
        'tipo_orcamento',
        'orcamento_revisao_de_id',
        'status',
        'origem',
        'cliente_id',
        'contato_id',
        'cliente_nome_avulso',
        'telefone_contato',
        'email_contato',
        'os_id',
        'equipamento_id',
        'equipamento_tipo_id',
        'equipamento_marca_id',
        'equipamento_modelo_id',
        'equipamento_cor',
        'equipamento_cor_hex',
        'equipamento_cor_rgb',
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
            self::STATUS_PENDENTE_ENVIO => 'Pendente de envio para aprovacao do cliente',
            self::STATUS_ENVIADO   => 'Enviado',
            self::STATUS_AGUARDANDO => 'Aguardando resposta',
            self::STATUS_AGUARDANDO_PACOTE => 'Aguardando escolha/aprovacao do pacote',
            self::STATUS_PACOTE_APROVADO => 'Pacote escolhido e aprovado',
            self::STATUS_PENDENTE => 'Pendente (sem retorno do pacote)',
            self::STATUS_APROVADO  => 'Aprovado',
            self::STATUS_PENDENTE_OS => 'Aprovado (pendente de OS)',
            self::STATUS_REJEITADO => 'Rejeitado',
            self::STATUS_VENCIDO   => 'Vencido',
            self::STATUS_CANCELADO => 'Cancelado',
            self::STATUS_CONVERTIDO => 'Convertido',
        ];
    }

    public function tipoLabels(): array
    {
        return [
            self::TIPO_PREVIO => 'Orcamento previo',
            self::TIPO_ASSISTENCIA => 'Orcamento com equipamento na assistencia',
        ];
    }

    public function normalizeTipo(?string $tipo, ?int $osId = null): string
    {
        $tipo = strtolower(trim((string) $tipo));
        if ($osId !== null && $osId > 0) {
            return self::TIPO_ASSISTENCIA;
        }

        if (!in_array($tipo, [self::TIPO_PREVIO, self::TIPO_ASSISTENCIA], true)) {
            return self::TIPO_PREVIO;
        }

        return $tipo;
    }

    public function isTipoAssistencia(?string $tipo, ?int $osId = null): bool
    {
        return $this->normalizeTipo($tipo, $osId) === self::TIPO_ASSISTENCIA;
    }

    public function isLockedStatus(string $status): bool
    {
        return in_array($status, [self::STATUS_APROVADO, self::STATUS_PENDENTE_OS, self::STATUS_PACOTE_APROVADO, self::STATUS_CONVERTIDO], true);
    }
}
