<?php

namespace App\Models;

use CodeIgniter\Model;

class PacoteOfertaModel extends Model
{
    protected $table = 'pacotes_ofertas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'pacote_servico_id',
        'cliente_id',
        'contato_id',
        'telefone_destino',
        'os_id',
        'equipamento_id',
        'origem_contexto',
        'token_publico',
        'status',
        'destino_canal',
        'mensagem_enviada',
        'expira_em',
        'enviado_em',
        'visualizado_em',
        'escolhido_em',
        'aplicado_em',
        'nivel_escolhido',
        'nivel_nome_exibicao',
        'valor_escolhido',
        'garantia_dias',
        'prazo_estimado',
        'itens_inclusos',
        'argumento_venda',
        'orcamento_id',
        'orcamento_item_id',
        'ip_escolha',
        'user_agent_escolha',
    ];

    public function findByTokenWithContext(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $row = $this->select(
            'pacotes_ofertas.*, ' .
            'pacotes_servicos.nome as pacote_nome, pacotes_servicos.descricao as pacote_descricao, pacotes_servicos.tipo_equipamento as pacote_tipo_equipamento, ' .
            'clientes.nome_razao as cliente_nome, ' .
            'contatos.nome as contato_nome, contatos.whatsapp_nome_perfil as contato_nome_perfil, ' .
            'orcamentos.numero as orcamento_numero, orcamentos.status as orcamento_status'
        )
            ->join('pacotes_servicos', 'pacotes_servicos.id = pacotes_ofertas.pacote_servico_id', 'left')
            ->join('clientes', 'clientes.id = pacotes_ofertas.cliente_id', 'left')
            ->join('contatos', 'contatos.id = pacotes_ofertas.contato_id', 'left')
            ->join('orcamentos', 'orcamentos.id = pacotes_ofertas.orcamento_id', 'left')
            ->where('pacotes_ofertas.token_publico', $token)
            ->first();

        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function byOrcamento(int $orcamentoId): array
    {
        if ($orcamentoId <= 0) {
            return [];
        }

        return $this->select(
            'pacotes_ofertas.*, ' .
            'pacotes_servicos.nome as pacote_nome, pacotes_servicos.tipo_equipamento as pacote_tipo_equipamento'
        )
            ->join('pacotes_servicos', 'pacotes_servicos.id = pacotes_ofertas.pacote_servico_id', 'left')
            ->where('pacotes_ofertas.orcamento_id', $orcamentoId)
            ->orderBy('pacotes_ofertas.id', 'DESC')
            ->findAll();
    }

    public function findLatestByIdentity(
        ?int $clienteId,
        ?int $contatoId,
        string $telefoneDigits,
        ?string $nomeReferencia = null,
        ?int $osId = null,
        ?int $equipamentoId = null,
        ?int $orcamentoId = null
    ): ?array
    {
        $orcamentoId = (int) ($orcamentoId ?? 0);
        $statusAllowed = ['ativo', 'enviado', 'escolhido', 'erro_envio', 'expirado'];
        if ($orcamentoId > 0) {
            $statusAllowed[] = 'aplicado_orcamento';
        }
        $builder = $this->select(
            'pacotes_ofertas.*, ' .
            'pacotes_servicos.nome as pacote_nome, pacotes_servicos.tipo_equipamento as pacote_tipo_equipamento, ' .
            'clientes.nome_razao as cliente_nome, ' .
            'contatos.nome as contato_nome, contatos.whatsapp_nome_perfil as contato_nome_perfil, ' .
            'orcamentos.os_id as orcamento_os_id, orcamentos.status as orcamento_status'
        )
            ->join('pacotes_servicos', 'pacotes_servicos.id = pacotes_ofertas.pacote_servico_id', 'left')
            ->join('clientes', 'clientes.id = pacotes_ofertas.cliente_id', 'left')
            ->join('contatos', 'contatos.id = pacotes_ofertas.contato_id', 'left')
            ->join('orcamentos', 'orcamentos.id = pacotes_ofertas.orcamento_id', 'left')
            ->whereIn('pacotes_ofertas.status', $statusAllowed)
            // Se o pacote ja entrou em OS (escolhido/aplicado com OS vinculada), nao deve mais ser sugerido.
            ->where(
                "NOT (pacotes_ofertas.status IN ('escolhido','aplicado_orcamento') AND (COALESCE(pacotes_ofertas.os_id,0) > 0 OR COALESCE(orcamentos.os_id,0) > 0))",
                null,
                false
            );
        if ($orcamentoId > 0) {
            $builder->groupStart()
                ->where('pacotes_ofertas.orcamento_id', $orcamentoId)
                ->orWhere('pacotes_ofertas.orcamento_id IS NULL', null, false)
                ->groupEnd();
        } else {
            $builder->groupStart()
                ->where('pacotes_ofertas.orcamento_id IS NULL', null, false)
                ->orWhere('pacotes_ofertas.orcamento_id', 0)
                ->groupEnd();
        }

        $hasCondition = false;
        $builder->groupStart();

        if (($clienteId ?? 0) > 0) {
            $builder->orWhere('pacotes_ofertas.cliente_id', (int) $clienteId);
            $hasCondition = true;
        }

        if (($contatoId ?? 0) > 0) {
            $builder->orWhere('pacotes_ofertas.contato_id', (int) $contatoId);
            $hasCondition = true;
        }

        $telefoneDigits = trim($telefoneDigits);
        if ($telefoneDigits !== '') {
            $builder->orWhere('pacotes_ofertas.telefone_destino', $telefoneDigits);
            $hasCondition = true;
        }

        $osId = (int) ($osId ?? 0);
        if ($osId > 0) {
            $builder->orWhere('pacotes_ofertas.os_id', $osId);
            $hasCondition = true;
        }

        $equipamentoId = (int) ($equipamentoId ?? 0);
        if ($equipamentoId > 0) {
            $builder->orWhere('pacotes_ofertas.equipamento_id', $equipamentoId);
            $hasCondition = true;
        }

        $builder->groupEnd();

        if (!$hasCondition) {
            return null;
        }

        $rows = $builder
            ->orderBy('pacotes_ofertas.updated_at', 'DESC')
            ->orderBy('pacotes_ofertas.id', 'DESC')
            ->findAll(30);

        if (empty($rows)) {
            return null;
        }

        $clienteId = (int) ($clienteId ?? 0);
        $contatoId = (int) ($contatoId ?? 0);
        $telefoneDigits = trim($telefoneDigits);
        $osId = (int) ($osId ?? 0);
        $equipamentoId = (int) ($equipamentoId ?? 0);
        $nomeReferenciaNorm = $this->normalizeIdentityText((string) ($nomeReferencia ?? ''));

        $best = null;
        $bestScore = -999999;

        foreach ($rows as $row) {
            $ofertaClienteId = (int) ($row['cliente_id'] ?? 0);
            $ofertaContatoId = (int) ($row['contato_id'] ?? 0);
            $ofertaTelefone = trim((string) ($row['telefone_destino'] ?? ''));
            $ofertaOsId = (int) ($row['os_id'] ?? 0);
            $ofertaEquipamentoId = (int) ($row['equipamento_id'] ?? 0);
            $ofertaNome = trim((string) ($row['cliente_nome'] ?? $row['contato_nome'] ?? $row['contato_nome_perfil'] ?? ''));
            $ofertaNomeNorm = $this->normalizeIdentityText($ofertaNome);

            $matchByCliente = $clienteId > 0 && $ofertaClienteId > 0 && $clienteId === $ofertaClienteId;
            $matchByContato = $contatoId > 0 && $ofertaContatoId > 0 && $contatoId === $ofertaContatoId;
            $matchByTelefone = $telefoneDigits !== '' && $ofertaTelefone !== '' && $telefoneDigits === $ofertaTelefone;
            $matchByOs = $osId > 0 && $ofertaOsId > 0 && $osId === $ofertaOsId;
            $matchByEquipamento = $equipamentoId > 0 && $ofertaEquipamentoId > 0 && $equipamentoId === $ofertaEquipamentoId;

            if (!$matchByCliente && !$matchByContato && !$matchByTelefone && !$matchByOs && !$matchByEquipamento) {
                continue;
            }

            // Se o orcamento atual tem contexto de OS/equipamento, ofertas de outro contexto nao devem ser sugeridas.
            if ($osId > 0 && !$matchByOs) {
                continue;
            }
            if ($equipamentoId > 0 && !$matchByEquipamento) {
                continue;
            }

            $hasStrongContextMatch = $matchByCliente || $matchByContato || $matchByOs || $matchByEquipamento;

            // Regra de deteccao inteligente:
            // telefone isolado nao basta; exige nome com alta semelhanca.
            if (!$hasStrongContextMatch && $matchByTelefone) {
                if ($nomeReferenciaNorm === '' || $ofertaNomeNorm === '') {
                    continue;
                }
                if (!$this->isLikelySameName($nomeReferenciaNorm, $ofertaNomeNorm)) {
                    continue;
                }
                similar_text($nomeReferenciaNorm, $ofertaNomeNorm, $namePercent);
                if ($namePercent < 85) {
                    continue;
                }
            }

            // Se houver divergencia de nome e o sinal principal for telefone compartilhado,
            // nao sugerir automaticamente sem contexto forte (OS/equipamento).
            if ($matchByTelefone && !$hasStrongContextMatch && $nomeReferenciaNorm !== '' && $ofertaNomeNorm !== '') {
                if (!$this->isLikelySameName($nomeReferenciaNorm, $ofertaNomeNorm)) {
                    continue;
                }
            }

            $score = 0;
            $modes = [];
            $warnings = [];

            if ($matchByCliente) {
                $score += 130;
                $modes[] = 'cliente_id';
            } elseif ($clienteId > 0 && $ofertaClienteId > 0 && $clienteId !== $ofertaClienteId) {
                $score -= 50;
                $warnings[] = 'Cliente da oferta diferente do cliente atual.';
            }

            if ($matchByContato) {
                $score += 110;
                $modes[] = 'contato_id';
            } elseif ($contatoId > 0 && $ofertaContatoId > 0 && $contatoId !== $ofertaContatoId) {
                $score -= 40;
                $warnings[] = 'Contato da oferta diferente do contato atual.';
            }

            if ($matchByTelefone) {
                $score += 70;
                $modes[] = 'telefone';
            } elseif ($telefoneDigits !== '' && $ofertaTelefone !== '' && $telefoneDigits !== $ofertaTelefone) {
                $score -= 20;
            }

            if ($matchByOs) {
                $score += 170;
                $modes[] = 'os_id';
            } elseif ($osId > 0 && $ofertaOsId > 0 && $osId !== $ofertaOsId) {
                $score -= 80;
                $warnings[] = 'OS da oferta diferente da OS atual.';
            }

            if ($matchByEquipamento) {
                $score += 140;
                $modes[] = 'equipamento_id';
            } elseif ($equipamentoId > 0 && $ofertaEquipamentoId > 0 && $equipamentoId !== $ofertaEquipamentoId) {
                $score -= 70;
                $warnings[] = 'Equipamento da oferta diferente do equipamento atual.';
            }

            if ($nomeReferenciaNorm !== '' && $ofertaNomeNorm !== '') {
                if ($this->isLikelySameName($nomeReferenciaNorm, $ofertaNomeNorm)) {
                    $score += 30;
                    $modes[] = 'nome';
                } else {
                    $score -= 18;
                    $warnings[] = 'Telefone coincide, mas o nome de referencia parece diferente.';
                }
            }

            $status = trim((string) ($row['status'] ?? ''));
            if ($status === 'escolhido') {
                $score += 8;
            } elseif ($status === 'erro_envio') {
                $score -= 5;
            } elseif ($status === 'aplicado_orcamento') {
                $score -= 10;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $row['_match_score'] = $score;
                $row['_match_modes'] = implode(',', array_values(array_unique($modes)));
                $row['_identity_warning'] = implode(' ', array_values(array_unique($warnings)));
                $best = $row;
            }
        }

        if ($best === null) {
            return null;
        }

        $matchModes = array_values(array_filter(array_map('trim', explode(',', (string) ($best['_match_modes'] ?? '')))));
        $hasStrongMode = !empty(array_intersect($matchModes, ['cliente_id', 'contato_id', 'os_id', 'equipamento_id']));
        if (!$hasStrongMode) {
            // Modo somente telefone/nome: exige score minimo mais alto para evitar colisao entre contatos.
            if ($bestScore < 95 || !in_array('nome', $matchModes, true)) {
                return null;
            }
        }

        return $best;
    }

    private function normalizeIdentityText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($ascii !== false) {
                $value = $ascii;
            }
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }

    private function isLikelySameName(string $left, string $right): bool
    {
        if ($left === '' || $right === '') {
            return false;
        }
        if ($left === $right) {
            return true;
        }

        if (strlen($left) >= 5 && str_contains($right, $left)) {
            return true;
        }
        if (strlen($right) >= 5 && str_contains($left, $right)) {
            return true;
        }

        similar_text($left, $right, $percent);
        return $percent >= 72;
    }
}
