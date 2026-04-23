<?php

namespace App\Services;

use App\Models\MensagemWhatsappModel;
use App\Models\WhatsappEnvioModel;
use App\Models\WhatsappMensagemModel;
use App\Models\WhatsappTemplateModel;
use App\Services\WhatsApp\BulkMessageProviderInterface;
use App\Services\WhatsApp\MetaOfficialProvider;
use App\Services\WhatsApp\NullBulkProvider;
use App\Services\WhatsApp\WhatsAppProviderInterface;

class WhatsAppService
{
    private WhatsappEnvioModel $envioModel;
    private WhatsappMensagemModel $mensagemModel;
    private WhatsappTemplateModel $templateModel;
    private ?MensagemWhatsappModel $mensagensWhatsappModel = null;
    private MensageriaService $mensageriaService;
    private CentralMensagensService $centralMensagensService;

    public function __construct()
    {
        $this->envioModel = new WhatsappEnvioModel();
        $this->mensagemModel = new WhatsappMensagemModel();
        $this->templateModel = new WhatsappTemplateModel();
        $this->mensageriaService = new MensageriaService();
        $this->centralMensagensService = new CentralMensagensService();

        $mensagensModel = new MensagemWhatsappModel();
        if ($mensagensModel->db->tableExists('mensagens_whatsapp')) {
            $this->mensagensWhatsappModel = $mensagensModel;
        }
    }

    public function getTemplates(): array
    {
        if (!$this->templateModel->db->tableExists('whatsapp_templates')) {
            return [];
        }
        return $this->templateModel->getActive();
    }

    public function sendByTemplate(array $os, string $templateCode, ?int $userId = null, array $extra = []): array
    {
        $template = $this->templateModel->byCode($templateCode);
        if (!$template) {
            return ['ok' => false, 'message' => 'Template de WhatsApp nao encontrado.'];
        }

        $payload = $this->renderTemplate($template['conteudo'], $os, $extra);
        $options = [
            'template_codigo' => $templateCode,
            'arquivo_path' => $extra['arquivo_path'] ?? null,
            'arquivo' => $extra['arquivo'] ?? null,
        ];

        return $this->sendRaw(
            (int)($os['id'] ?? 0),
            (int)($os['cliente_id'] ?? 0),
            $os['cliente_telefone'] ?? '',
            $payload,
            $template['evento'] ?? $templateCode,
            $template['id'] ?? null,
            $userId,
            $options
        );
    }

    public function sendRaw(
        int $osId,
        int $clienteId,
        string $telefone,
        string $conteudo,
        ?string $tipoEvento = null,
        ?int $templateId = null,
        ?int $userId = null,
        array $options = []
    ): array {
        $provider = $this->resolveDirectProvider();
        $providerName = (string) get_config('whatsapp_direct_provider', get_config('whatsapp_provider', 'menuia'));
        $filePath = trim((string)($options['arquivo_path'] ?? ''));
        $fileRelative = trim((string)($options['arquivo'] ?? ''));
        $templateCode = trim((string)($options['template_codigo'] ?? ''));
        $mimeFromOption = strtolower(trim((string)($options['mime_type'] ?? '')));
        $tipoConteudoOption = strtolower(trim((string)($options['tipo_conteudo'] ?? '')));
        $arquivoNomeOption = trim((string)($options['arquivo_nome'] ?? ''));
        $arquivoTamanhoOption = (int) ($options['arquivo_tamanho'] ?? 0);
        $conversaId = (int)($options['conversa_id'] ?? 0);
        $enviadaPorBot = !empty($options['enviada_por_bot']);
        $enviadaPorUsuarioId = isset($options['enviada_por_usuario_id'])
            ? (int) $options['enviada_por_usuario_id']
            : ($enviadaPorBot ? 0 : (int) ($userId ?? 0));
        $replyToMessageId = (int) ($options['reply_to_message_id'] ?? 0);
        $replyToText = trim((string) ($options['reply_to_text'] ?? ''));
        $replyToAuthor = trim((string) ($options['reply_to_author'] ?? ''));
        $hasFile = $filePath !== '';

        $tipoConteudo = 'texto';
        $mimeOutbound = '';
        if ($hasFile) {
            $mimeOutbound = $mimeFromOption !== '' ? $mimeFromOption : $this->resolveMimeByPath($filePath);
            if ($tipoConteudoOption !== '') {
                $tipoConteudo = $tipoConteudoOption;
            } else {
                $tipoConteudo = $this->tipoConteudoByMime($mimeOutbound, $filePath, $fileRelative);
            }
            if ($tipoConteudo === 'pdf' && $conteudo !== '') {
                $tipoConteudo = 'texto_pdf';
            }
        }

        $conversa = null;
        if ($conversaId > 0) {
            $conversa = (new \App\Models\ConversaWhatsappModel())->find($conversaId);
        }
        if (!$conversa) {
            $conversa = $this->centralMensagensService->resolveConversationForOutgoing(
                $telefone,
                $clienteId > 0 ? $clienteId : null,
                $osId > 0 ? $osId : null,
                $providerName
            );
        }
        $conversaId = (int)($conversa['id'] ?? 0) ?: null;
        if ($conversaId && $osId > 0) {
            $this->centralMensagensService->bindOsToConversa($conversaId, $osId, true);
        }

        $recentDuplicate = $this->findRecentOutboundSendDuplicate(
            $conversaId ?: null,
            $telefone,
            $conteudo,
            $fileRelative,
            $tipoEvento ?: 'manual'
        );
        if ($recentDuplicate) {
            return [
                'ok' => true,
                'duplicate' => true,
                'provider' => $providerName,
                'status_code' => 202,
                'message' => 'Envio ignorado para evitar duplicidade por clique duplo.',
                'response' => ['dedup' => true, 'message_id' => $recentDuplicate['provider_message_id'] ?? null],
                'message_id' => $recentDuplicate['provider_message_id'] ?? null,
                'log_id' => null,
                'envio_id' => null,
                'mensagem_whatsapp_id' => (int) ($recentDuplicate['id'] ?? 0) ?: null,
                'conversa_id' => $conversaId ?: null,
            ];
        }

        $envioId = null;
        if ($this->envioModel->db->tableExists('whatsapp_envios')) {
            $envioId = $this->envioModel->insert([
                'os_id' => $osId ?: null,
                'cliente_id' => $clienteId ?: null,
                'telefone' => $telefone,
                'tipo_envio' => 'direto',
                'tipo_conteudo' => $tipoConteudo,
                'template_codigo' => $templateCode !== '' ? $templateCode : null,
                'mensagem' => $conteudo !== '' ? $conteudo : null,
                'arquivo' => $fileRelative !== '' ? $fileRelative : null,
                'provedor' => $providerName,
                'status' => 'pendente',
                'usuario_id' => $userId,
            ], true);
        }

        $mensagemWhatsappId = null;
        if ($this->mensagensWhatsappModel !== null) {
            $mensagemWhatsappId = $this->mensagensWhatsappModel->insert([
                'conversa_id' => $conversaId,
                'provider' => $providerName,
                'direcao' => 'outbound',
                'tipo_conteudo' => $tipoConteudo,
                'cliente_id' => $clienteId ?: null,
                'os_id' => $osId ?: null,
                'telefone' => $telefone,
                'tipo_mensagem' => $tipoEvento ?: 'manual',
                'mensagem' => $conteudo !== '' ? $conteudo : null,
                'arquivo' => $fileRelative !== '' ? $fileRelative : null,
                'anexo_path' => $fileRelative !== '' ? $fileRelative : null,
                'status' => 'pendente',
                'payload' => json_encode([
                    'tipo_conteudo' => $tipoConteudo,
                    'template_codigo' => $templateCode !== '' ? $templateCode : null,
                    'file_name' => $arquivoNomeOption !== '' ? $arquivoNomeOption : null,
                    'file_size' => $arquivoTamanhoOption > 0 ? $arquivoTamanhoOption : null,
                    'reply_to' => (
                        $replyToMessageId > 0 || $replyToText !== ''
                        ? [
                            'id' => $replyToMessageId > 0 ? $replyToMessageId : null,
                            'text' => $replyToText !== '' ? $replyToText : null,
                            'author' => $replyToAuthor !== '' ? $replyToAuthor : null,
                        ]
                        : null
                    ),
                ], JSON_UNESCAPED_UNICODE),
                'usuario_id' => $userId,
                'mime_type' => $hasFile ? ($mimeOutbound !== '' ? $mimeOutbound : null) : null,
                'recebida_em' => null,
                'enviada_por_bot' => $enviadaPorBot ? 1 : 0,
                'enviada_por_usuario_id' => $enviadaPorUsuarioId > 0 ? $enviadaPorUsuarioId : null,
            ], true);
        }

        $legacyId = null;
        if ($this->mensagemModel->db->tableExists('whatsapp_mensagens')) {
            $legacyId = $this->mensagemModel->insert([
                'os_id' => $osId ?: null,
                'cliente_id' => $clienteId ?: null,
                'template_id' => $templateId,
                'provedor' => $providerName,
                'tipo_evento' => $tipoEvento,
                'telefone' => $telefone,
                'conteudo' => $conteudo,
                'status_envio' => 'pendente',
                'enviado_por' => $userId,
            ], true);
        }

        if ($hasFile) {
            $result = $provider->sendFile($telefone, $filePath, $conteudo, [
                'os_id' => $osId,
                'cliente_id' => $clienteId,
                'tipo_evento' => $tipoEvento,
                'tipo_envio' => 'direto',
                'mime_type' => $mimeOutbound,
                'tipo_conteudo' => $tipoConteudo,
            ]);
        } else {
            $result = $provider->sendText($telefone, $conteudo, [
                'os_id' => $osId,
                'cliente_id' => $clienteId,
                'tipo_evento' => $tipoEvento,
                'tipo_envio' => 'direto',
            ]);
        }

        if ($envioId) {
            $this->envioModel->update($envioId, [
                'status' => !empty($result['ok']) ? 'enviado' : 'erro',
                'resposta_api' => isset($result['response']) ? json_encode($result['response'], JSON_UNESCAPED_UNICODE) : null,
            ]);
        }

        if ($mensagemWhatsappId && $this->mensagensWhatsappModel !== null) {
            $this->mensagensWhatsappModel->update($mensagemWhatsappId, [
                'status' => !empty($result['ok']) ? 'enviado' : 'erro',
                'provider_message_id' => $result['message_id'] ?? null,
                'resposta_api' => isset($result['response']) ? json_encode($result['response'], JSON_UNESCAPED_UNICODE) : null,
                'erro' => !empty($result['ok']) ? null : ($result['message'] ?? 'Falha no envio'),
                'enviada_em' => !empty($result['ok']) ? date('Y-m-d H:i:s') : null,
            ]);
        }

        if ($legacyId) {
            $this->mensagemModel->update($legacyId, [
                'status_envio' => !empty($result['ok']) ? 'enviado' : 'erro',
                'api_message_id' => $result['message_id'] ?? null,
                'api_response' => isset($result['response']) ? json_encode($result['response'], JSON_UNESCAPED_UNICODE) : null,
                'erro_detalhe' => !empty($result['ok']) ? null : ($result['message'] ?? 'Falha no envio'),
            ]);
        }

        $result['log_id'] = $legacyId ?: null;
        $result['envio_id'] = $envioId ?: null;
        $result['mensagem_whatsapp_id'] = $mensagemWhatsappId ?: null;
        $result['conversa_id'] = $conversaId ?: null;

        $this->centralMensagensService->afterOutboundSent(
            $conversaId ?: null,
            $clienteId ?: null,
            $osId ?: null,
            $conteudo,
            $tipoConteudo,
            $userId,
            [
                'ok' => !empty($result['ok']),
                'status_code' => $result['status_code'] ?? null,
                'provider' => $providerName,
                'template_codigo' => $templateCode !== '' ? $templateCode : null,
                'mime_type' => $mimeOutbound !== '' ? $mimeOutbound : null,
                'arquivo' => $fileRelative !== '' ? $fileRelative : null,
                'arquivo_nome' => $arquivoNomeOption !== '' ? $arquivoNomeOption : null,
                'arquivo_tamanho' => $arquivoTamanhoOption > 0 ? $arquivoTamanhoOption : null,
                'enviada_por_bot' => $enviadaPorBot ? 1 : 0,
                'enviada_por_usuario_id' => $enviadaPorUsuarioId > 0 ? $enviadaPorUsuarioId : null,
                'reply_to_message_id' => $replyToMessageId > 0 ? $replyToMessageId : null,
                'reply_to_text' => $replyToText !== '' ? $replyToText : null,
                'reply_to_author' => $replyToAuthor !== '' ? $replyToAuthor : null,
            ]
        );
        return $result;
    }

    public function testDirectConnection(?string $phone = null): array
    {
        return $this->mensageriaService->testDirectConnection($phone, null, [], false);
    }

    public function sendTestMessage(string $phone, string $message, ?int $userId = null): array
    {
        return $this->sendRaw(
            0,
            0,
            $phone,
            $message,
            'teste_manual',
            null,
            $userId,
            []
        );
    }

    public function resolveBulkProvider(): BulkMessageProviderInterface
    {
        $provider = (string) get_config('whatsapp_bulk_provider', 'meta_oficial');
        if ($provider === 'meta_oficial') {
            return new MetaOfficialProvider();
        }
        return new NullBulkProvider();
    }

    private function resolveDirectProvider(): WhatsAppProviderInterface
    {
        return $this->mensageriaService->resolveDirectProvider();
    }

    private function renderTemplate(string $template, array $os, array $extra = []): string
    {
        $vars = [
            'numero_os' => $os['numero_os'] ?? '',
            'data_abertura' => !empty($os['data_abertura']) ? date('d/m/Y H:i', strtotime($os['data_abertura'])) : '',
            'equipamento' => trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? '')),
            'cliente' => $os['cliente_nome'] ?? '',
            'valor_final' => isset($os['valor_final']) ? formatMoney((float)$os['valor_final']) : 'R$ 0,00',
            'status' => $os['status'] ?? '',
        ];

        foreach ($extra as $k => $v) {
            $vars[$k] = (string) $v;
        }

        $message = $template;
        foreach ($vars as $key => $value) {
            $message = str_replace('{{' . $key . '}}', (string) $value, $message);
        }

        $pdfUrl = trim((string)($vars['pdf_url'] ?? ''));
        if ($pdfUrl !== '' && !str_contains($template, '{{pdf_url}}')) {
            $message .= "\n\nPDF da OS: " . $pdfUrl;
        }

        return $message;
    }

    private function findRecentOutboundSendDuplicate(
        ?int $conversaId,
        string $telefone,
        string $mensagem,
        string $arquivo,
        string $tipoMensagem
    ): ?array {
        if ($this->mensagensWhatsappModel === null) {
            return null;
        }

        $builder = $this->mensagensWhatsappModel
            ->where('direcao', 'outbound')
            ->where('telefone', $telefone)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 3))
            ->whereIn('status', ['pendente', 'enviado', 'enviada'])
            ->orderBy('id', 'DESC');

        if (!empty($conversaId)) {
            $builder->where('conversa_id', (int) $conversaId);
        }

        $safeTipo = trim($tipoMensagem);
        if ($safeTipo !== '') {
            $builder->where('tipo_mensagem', $safeTipo);
        }

        $safeMensagem = trim($mensagem);
        if ($safeMensagem !== '') {
            $builder->where('mensagem', $safeMensagem);
        } else {
            $builder->groupStart()
                ->where('mensagem', null)
                ->orWhere('mensagem', '')
                ->groupEnd();
        }

        $safeArquivo = trim($arquivo);
        if ($safeArquivo !== '') {
            $builder->groupStart()
                ->where('arquivo', $safeArquivo)
                ->orWhere('anexo_path', $safeArquivo)
                ->groupEnd();
        } else {
            $builder->groupStart()
                ->where('arquivo', null)
                ->orWhere('arquivo', '')
                ->groupEnd()
                ->groupStart()
                ->where('anexo_path', null)
                ->orWhere('anexo_path', '')
                ->groupEnd();
        }

        return $builder->first();
    }

    private function resolveMimeByPath(string $filePath): string
    {
        $path = trim($filePath);
        if ($path === '' || !is_file($path)) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return '';
        }

        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);
        return is_string($mime) ? strtolower(trim($mime)) : '';
    }

    private function tipoConteudoByMime(string $mime, string $filePath = '', string $relativePath = ''): string
    {
        $m = strtolower(trim($mime));
        if ($m !== '') {
            if (str_starts_with($m, 'image/')) {
                return 'imagem';
            }
            if (str_starts_with($m, 'audio/')) {
                return 'audio';
            }
            if (str_starts_with($m, 'video/')) {
                return 'video';
            }
            if ($m === 'application/pdf') {
                return 'pdf';
            }
            return 'arquivo';
        }

        $candidate = $relativePath !== '' ? $relativePath : $filePath;
        $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp' => 'imagem',
            'mp3', 'ogg', 'wav', 'm4a', 'aac', 'opus' => 'audio',
            'mp4', 'webm', 'mov', 'mkv' => 'video',
            'pdf' => 'pdf',
            default => 'arquivo',
        };
    }
}
