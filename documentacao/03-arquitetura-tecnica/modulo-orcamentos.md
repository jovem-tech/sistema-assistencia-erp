# Arquitetura Tecnica - Modulo Orcamentos

## Objetivo

Este documento resume a estrutura tecnica do modulo de `Orcamentos` no ERP web, com foco no fluxo `Dados do Cliente` consolidado na release `2.15.1` e na notificacao web em tempo real publicada na release `2.15.17`.

## Estrutura principal

- controller web: `app/Controllers/Orcamentos.php`
- models principais:
  - `app/Models/OrcamentoModel.php`
  - `app/Models/OrcamentoItemModel.php`
  - `app/Models/OrcamentoEnvioModel.php`
  - `app/Models/OrcamentoAprovacaoModel.php`
  - `app/Models/OrcamentoStatusHistoricoModel.php`
- services principais:
  - `app/Services/OrcamentoService.php`
  - `app/Services/OrcamentoPdfService.php`
  - `app/Services/OrcamentoMailService.php`
  - `app/Services/OrcamentoLifecycleService.php`
  - `app/Services/OrcamentoConversaoService.php`
- views:
  - `app/Views/orcamentos/form.php`
  - `app/Views/orcamentos/index.php`
  - `app/Views/orcamentos/show.php`

## Fluxo tecnico de Dados do Cliente

### 1. Lookup inicial

O frontend usa `clienteLookupInitial` para montar o estado da selecao quando o orcamento ja existe ou chega com prefill.

Esse payload e montado em `Orcamentos::buildClienteLookupInitial()`.

Na release `2.15.1`, o payload inicial passou a carregar tambem:

- `contato_adicional_nome`
- `contato_adicional_telefone`

### 2. Busca dinamica

O Select2 consulta `Orcamentos::lookupClienteContato()`.

O endpoint combina:

- clientes vindos de `searchClientesForLookup()`;
- contatos vindos de `searchContatosForLookup()`.

No lado de clientes, a busca agora inclui:

- `nome_razao`
- `nome_contato`
- `cpf_cnpj`
- `email`
- `telefone1`
- `telefone_contato`

No lado de contatos, o backend pode herdar o resumo do contato adicional do cliente relacionado para manter a UI consistente mesmo quando a selecao principal for um contato.

### 3. Aplicacao no formulario

No frontend, `applyLookupSelection()` atualiza:

- `cliente_id`
- `contato_id`
- `telefone_contato`
- `email_contato`
- estado do campo `cliente_nome_avulso`
- card `Contato adicional do cliente`

O card informativo usa o mesmo payload do lookup, sem dependencia de nova chamada AJAX.

### 4. Persistencia

Antes de salvar, `validateContatoPayload()` e `resolveClienteContatoPayload()` tratam o bloco de cliente e contato.

Regra consolidada em `2.15.1`:

- `telefone_contato` pode ser vazio;
- se vier preenchido, continua passando por `isWhatsAppPhoneValid()`;
- quando salvo vazio, o payload final grava `null`;
- fallbacks de cliente e contato ainda podem reaproveitar telefones ja cadastrados em etapas posteriores do fluxo.

## Comportamento reativo

O bloco `Dados do Cliente` foi mantido dentro do padrao reativo do sistema:

- selecionar cliente atualiza telefone, email e contato adicional imediatamente;
- limpar a selecao remove o resumo adicional sem refresh;
- carregar uma edicao existente reaplica o mesmo estado inicial no primeiro render.

## Resposta publica e notificacao web em tempo real

Na release `2.15.17`, o controller complementar `app/Controllers/Orcamento.php` passou a disparar notificacoes internas quando o cliente responde o orcamento pelo link publico.

### Ponto de disparo

- `Orcamento::aprovar($token)`:
  - atualiza o status comercial;
  - registra aprovacao em `orcamento_aprovacoes`;
  - sincroniza a OS vinculada quando existir;
  - chama `notifyStaffAboutPublicBudgetStatusChange(...)`.
- `Orcamento::recusar($token)`:
  - atualiza o status para `rejeitado`;
  - registra a resposta do cliente;
  - chama o mesmo emissor de notificacao interna.

### Resolucao de destinatarios

O metodo `resolveBudgetNotificationRecipients()` consulta usuarios ativos e filtra apenas quem possui permissao para:

- `os:visualizar`; ou
- `orcamentos:visualizar`.

Com isso, a notificacao vai apenas para a equipe que realmente consegue agir sobre a OS ou sobre a proposta.

### Transporte e inbox web

O emissor reutiliza `App\Services\Mobile\MobileNotificationService`, persistindo o evento em `mobile_notifications` e `mobile_notification_targets`.

Na camada web, a navbar passa a consumir esse mesmo inbox por meio de `app/Controllers/Notificacoes.php`:

- `GET /notificacoes/navbar-feed`
- `GET /notificacoes/stream`
- `POST /notificacoes/lida/{id}`
- `POST /notificacoes/lidas`

O endpoint `stream` usa `text/event-stream` com autenticacao de sessao, cursor `after_id`, eventos `delta/ping/end` e fallback de polling no frontend quando SSE nao estiver disponivel.

### Resolucao de rota da notificacao

O campo `rota_destino` das notificacoes desse fluxo passou a ser persistido com `site_url(...)` no backend.

No frontend, `public/assets/js/navbar-notifications.js` tambem normaliza rotas antigas que ainda venham com prefixo `/`, convertendo-as para o contexto correto do ERP antes da navegacao.

Com isso, o clique na notificacao continua funcional mesmo em ambientes com:

- `index.php` habilitado;
- aplicacao publicada em subdiretorio;
- itens antigos ainda gravados com caminho absoluto da raiz do host.

### Reflexo na listagem de OS

O script global `public/assets/js/navbar-notifications.js` publica o evento de janela `erp:notification` sempre que chega uma notificacao nova do tipo `orcamento.public_status_changed`.

A listagem `/os`, por meio de `public/assets/js/os-list-filters.js`, escuta esse evento para:

- recarregar a DataTable automaticamente;
- atualizar o badge comercial de orcamento na coluna `Status`;
- reidratar o modal `Alterar status da OS` quando ele estiver aberto para a mesma ordem.

## Help mapping

O botao `Ajuda` do formulario usa `openDocPage('orcamentos')`, que aponta para:

- `documentacao/01-manual-do-usuario/orcamentos.md`

Com isso, a ajuda contextual da tela fica alinhada ao comportamento publicado na release `2.15.1`.
