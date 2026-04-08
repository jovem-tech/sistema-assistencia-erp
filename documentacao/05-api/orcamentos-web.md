# Rotas Web - Orcamentos (Fase 1)

> Nesta fase, o modulo de orcamentos foi entregue em rotas web (ERP) e endpoints publicos por token.

## Rotas autenticadas (RBAC)
- `GET /orcamentos` - listagem
- `GET /orcamentos/novo` - formulario de criacao
- `POST /orcamentos/salvar` - persistencia de novo orcamento
- `GET /orcamentos/visualizar/{id}` - detalhe
- `GET /orcamentos/editar/{id}` - formulario de edicao
- `POST /orcamentos/atualizar/{id}` - atualizar dados
- `POST /orcamentos/status/{id}` - alterar status
- `POST /orcamentos/converter/{id}` - converter aprovado em OS/venda
- `POST /orcamentos/automacao/executar` - executar vencimento/follow-up sob demanda
- `POST /orcamentos/pdf/{id}/gerar` - gerar nova versao de PDF
- `GET /orcamentos/pdf/{id}` - abrir/baixar PDF atual
- `POST /orcamentos/whatsapp/{id}/enviar` - enviar orcamento por WhatsApp
- `POST /orcamentos/email/{id}/enviar` - enviar orcamento por e-mail
- `POST /orcamentos/central-mensagens/gerar-enviar` - criar e enviar orcamento rapido dentro da conversa
- `GET /orcamentos/excluir/{id}` - excluir (regras de status)

Filtro de permissao:
- `permission:orcamentos:visualizar`
- `permission:orcamentos:criar`
- `permission:orcamentos:editar`
- `permission:orcamentos:excluir`

## Trilha de envio (fase 2)
- Toda tentativa de PDF/WhatsApp/e-mail gera ou atualiza registro em `orcamento_envios`.
- Campos rastreados: `canal`, `destino`, `mensagem`, `documento_path`, `status`, `provedor`, `referencia_externa`, `erro_detalhe`, `enviado_por`, `enviado_em`.
- Envio bem-sucedido para cliente pode promover o orcamento para `aguardando_resposta`.

## Conversao e automacao (fase 3)
- Conversao:
  - `tipo=os` em `POST /orcamentos/converter/{id}`:
    - reaproveita OS vinculada quando existir;
    - ou cria nova OS automaticamente (cliente + equipamento obrigatorios).
  - `tipo=venda` em `POST /orcamentos/converter/{id}`:
    - registra conversao comercial manual (`convertido_tipo=venda_manual`).
- Automacao:
  - marca `vencido` quando `validade_data` expira em status pendente;
  - cria follow-ups CRM deduplicados por `origem_evento`.

## Rotas publicas (cliente externo)
- `GET /orcamento/{token}` - visualizar orcamento pelo token
- `POST /orcamento/aprovar/{token}` - aprovar orcamento
- `POST /orcamento/recusar/{token}` - rejeitar orcamento

Regra de aprovacao publica (fase 3):
- aprovado com OS vinculada => `aprovado`;
- aprovado sem OS vinculada => `pendente_abertura_os`.

Regras de seguranca:
- token unico por orcamento (`token_publico`)
- expiracao controlada por `token_expira_em`
- registro de IP/User-Agent em `orcamento_aprovacoes`
