# 2026-04-07 - Fase 2 do Modulo de Orcamentos (Envio por WhatsApp/E-mail/PDF)

## Contexto
Evolucao da fase 1 para permitir envio operacional direto no proprio modulo de Orcamentos, com trilha completa de cada tentativa.

## Entregas tecnicas
- Novos services:
  - `app/Services/OrcamentoPdfService.php`
  - `app/Services/OrcamentoMailService.php`
- Novo template PDF:
  - `app/Views/orcamentos/pdf/orcamento.php`
- Evolucao do controller:
  - `app/Controllers/Orcamentos.php`
    - `generatePdf()`
    - `downloadPdf()`
    - `sendWhatsApp()`
    - `sendEmail()`
    - trilha de envio e promocao de status apos envio
- Evolucao do model de envios:
  - `app/Models/OrcamentoEnvioModel.php`
    - `latestByCanal()`
- Novas rotas:
  - `POST /orcamentos/pdf/{id}/gerar`
  - `GET /orcamentos/pdf/{id}`
  - `POST /orcamentos/whatsapp/{id}/enviar`
  - `POST /orcamentos/email/{id}/enviar`
- Evolucao da UI de detalhe:
  - `app/Views/orcamentos/show.php`
    - painel de envio rapido por canal
    - geracao/abertura de PDF
    - confirmacao via SweetAlert2
    - listagem detalhada da trilha de envio

## Regras implementadas
- Bloqueio de envio nos status finais: `aprovado`, `cancelado`, `convertido`.
- Renovacao automatica de token publico antes de enviar, quando ausente/expirado.
- Reuso do ultimo PDF valido para anexos; geracao automatica quando nao houver PDF disponivel.
- Registro de tentativa em `orcamento_envios` com status `pendente` -> `gerado`/`enviado`/`duplicado`/`erro`.
- Em envio bem-sucedido para cliente, o orcamento passa para `aguardando_resposta` (quando aplicavel) e registra historico.

## Resultado operacional
- O atendente consegue gerar e enviar orcamento sem sair do modulo.
- O tecnico consegue enviar proposta rapida (WhatsApp/e-mail) com PDF anexado e link publico de aprovacao.
- A gestao passa a ter rastreabilidade completa de envio por canal, com visibilidade de erro e operador responsavel.
