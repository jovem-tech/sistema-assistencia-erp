# Modulo de Orcamentos

## Objetivo
O modulo `Orcamentos` centraliza a criacao e a gestao de orcamentos para:
- atendimento rapido (balcao, telefone, WhatsApp),
- orcamentos derivados de OS em andamento,
- clientes cadastrados e clientes eventuais.

## Entregas da Fase 1
- Estrutura de dados dedicada (`orcamentos`, `orcamento_itens`, `orcamento_status_historico`, `orcamento_envios`, `orcamento_aprovacoes`).
- CRUD web completo no ERP:
  - listar,
  - criar,
  - editar,
  - visualizar,
  - excluir (com regra de status).
- Entrada no sidebar em `Comercial > Orcamentos`.
- Atalhos de criacao:
  - pela OS aberta (`Gerar orcamento` em `os/show`),
  - pela Central de Mensagens (`Novo orcamento` no painel de contexto).
- Link publico com token para aprovacao/rejeicao do cliente:
  - `GET /orcamento/{token}`
  - `POST /orcamento/aprovar/{token}`
  - `POST /orcamento/recusar/{token}`

## Entregas da Fase 2
- Envio operacional direto da tela de visualizacao do orcamento:
  - gerar PDF;
  - enviar por WhatsApp;
  - enviar por e-mail.
- Novo template PDF dedicado do modulo:
  - `app/Views/orcamentos/pdf/orcamento.php`.
- Novo servico de PDF:
  - `app/Services/OrcamentoPdfService.php`.
- Novo servico de e-mail:
  - `app/Services/OrcamentoMailService.php`.
- Rotas de envio/arquivo:
  - `POST /orcamentos/pdf/{id}/gerar`
  - `GET /orcamentos/pdf/{id}`
  - `POST /orcamentos/whatsapp/{id}/enviar`
  - `POST /orcamentos/email/{id}/enviar`
- Trilha completa de envio reforcada em `orcamento_envios` com status, provedor, referencia e erro.

## Entregas da Fase 3
- Conversao de aprovado para execucao:
  - `POST /orcamentos/converter/{id}` com destino `os` ou `venda`.
- Regra de aprovacao publica refinada:
  - aprovado avulso (sem OS) -> `pendente_abertura_os`;
  - aprovado com OS -> `aprovado`.
- Automacao de ciclo:
  - vencimento automatico por `validade_data`;
  - follow-up CRM automatico para aguardando, vencido e pendente de OS.
- Acao operacional manual:
  - `POST /orcamentos/automacao/executar` no painel.
- Command de rotina:
  - `php spark orcamentos:lifecycle`.
- Central de Mensagens:
  - botao `Gerar e enviar orcamento` dentro da conversa;
  - endpoint `POST /orcamentos/central-mensagens/gerar-enviar`;
  - contexto da conversa exibe lista de orcamentos relacionados.
- Sidebar comercial com submenu de gestao:
  - painel,
  - aguardando resposta,
  - pendentes de OS,
  - novo orcamento.

## Fluxos cobertos nesta fase
1. Usuario abre `Orcamentos` no sidebar.
2. Cria novo orcamento manual ou pre-preenchido por OS/conversa.
3. Adiciona itens com calculo automatico de subtotal e total.
4. Salva em `rascunho` (ou outro status selecionado).
5. Acompanha historico de status, envios e acoes publicas.
6. Converte aprovado em OS/venda quando necessario.
7. Processa automacao de vencimento/follow-up manualmente ou por rotina CLI.

## Regras principais da Fase 1
- Minimo de um item por orcamento.
- Cliente pode ser cadastrado (`cliente_id`) ou eventual (`cliente_nome_avulso`).
- Orcamentos `aprovado` e `convertido` ficam bloqueados para edicao.
- Exclusao apenas para `rascunho`, `cancelado` ou `rejeitado`.
- Cada orcamento recebe:
  - `numero` interno no formato `ORC-YYMM-XXXXXX`,
  - `token_publico` para aprovacao externa.

## Regras principais da Fase 2
- Envios por WhatsApp/e-mail ficam bloqueados para `aprovado`, `cancelado` e `convertido`.
- Em envio bem-sucedido para cliente, o status passa para `aguardando_resposta` (quando aplicavel).
- Token publico e renovado automaticamente se estiver ausente/expirado antes de montar mensagem de envio.
- Ao anexar PDF no envio, o modulo reutiliza a ultima versao valida; se nao existir, gera nova versao automaticamente.

## Regras principais da Fase 3
- Aprovacao por link publico:
  - com `os_id` -> `aprovado`;
  - sem `os_id` -> `pendente_abertura_os`.
- Conversao exige orcamento em `aprovado` ou `pendente_abertura_os`.
- Conversao para OS cria OS automaticamente quando necessario e migra itens aprovados para `os_itens`.
- Automacao marca `vencido` por validade e gera follow-ups sem duplicidade por `origem_evento`.

## Arquivos de referencia
- Controller interno: `app/Controllers/Orcamentos.php`
- Controller publico: `app/Controllers/Orcamento.php`
- Modelos:
  - `app/Models/OrcamentoModel.php`
  - `app/Models/OrcamentoItemModel.php`
  - `app/Models/OrcamentoStatusHistoricoModel.php`
  - `app/Models/OrcamentoEnvioModel.php`
  - `app/Models/OrcamentoAprovacaoModel.php`
- Service: `app/Services/OrcamentoService.php`
- Service: `app/Services/OrcamentoPdfService.php`
- Service: `app/Services/OrcamentoMailService.php`
- Service: `app/Services/OrcamentoConversaoService.php`
- Service: `app/Services/OrcamentoLifecycleService.php`
- Command: `app/Commands/OrcamentosLifecycle.php`
- Views:
  - `app/Views/orcamentos/index.php`
  - `app/Views/orcamentos/form.php`
  - `app/Views/orcamentos/show.php`
  - `app/Views/orcamentos/publico.php`
  - `app/Views/orcamentos/pdf/orcamento.php`
- Migration:
  - `app/Database/Migrations/2026-04-07-230000_CreateOrcamentosModuleInfrastructure.php`
