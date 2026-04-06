# Rotas Internas (ERP + CRM + Mensageria)

Atualizado em 03/04/2026 para a release `2.11.2`.

## API Mobile/PWA (v1)

Base: `/api/v1`

Autenticacao:

| Metodo | Rota | Objetivo | Auth |
|---|---|---|---|
| POST | `/api/v1/auth/login` | Login mobile por email/senha e emissao de token Bearer | publica |
| GET | `/api/v1/auth/me` | Retornar usuario autenticado | `apiToken` |
| POST | `/api/v1/auth/refresh` | Renovar token de acesso | `apiToken` |
| POST | `/api/v1/auth/logout` | Revogar token atual | `apiToken` |

Operacional:

| Metodo | Rota | Objetivo | Auth |
|---|---|---|---|
| GET | `/api/v1/users` | Lista de usuarios ativos para operacao mobile | `apiToken` |
| GET | `/api/v1/clients` | Lista paginada de clientes | `apiToken` |
| GET | `/api/v1/clients/{id}` | Detalhe do cliente | `apiToken` |
| GET | `/api/v1/orders` | Lista paginada de OS | `apiToken` |
| GET | `/api/v1/orders/meta` | Metadados para abertura completa de OS no mobile (clientes, equipamentos, tecnicos, status, prioridades e defeitos por tipo) | `apiToken` |
| GET | `/api/v1/orders/{id}` | Detalhe da OS | `apiToken` |
| POST | `/api/v1/orders` | Criacao completa de OS (equivalente ao fluxo de abertura do ERP) | `apiToken` |
| PUT/PATCH | `/api/v1/orders/{id}` | Atualizacao de status/prioridade da OS | `apiToken` |
| GET | `/api/v1/conversations` | Lista de conversas para atendimento mobile | `apiToken` |
| GET | `/api/v1/conversations/{id}` | Thread da conversa com contexto basico | `apiToken` |
| GET | `/api/v1/messages?conversa_id=` | Lista ou delta de mensagens | `apiToken` |
| POST | `/api/v1/messages` | Enviar mensagem pela infraestrutura WhatsApp existente | `apiToken` |
| GET | `/api/v1/notifications` | Inbox de notificacoes do usuario | `apiToken` |
| POST | `/api/v1/notifications` | Criacao de notificacao manual/sistema | `apiToken` |
| PUT/PATCH | `/api/v1/notifications/{id}/read` | Marcar notificacao como lida | `apiToken` |
| PUT/PATCH | `/api/v1/notifications/read-all` | Marcar todas como lidas | `apiToken` |
| GET | `/api/v1/notifications/subscriptions` | Listar subscriptions push do dispositivo/usuario | `apiToken` |
| POST | `/api/v1/notifications/subscriptions` | Registrar/atualizar subscription push | `apiToken` |
| DELETE | `/api/v1/notifications/subscriptions/{id}` | Remover subscription push | `apiToken` |
| GET | `/api/v1/realtime/stream` | Stream SSE para deltas de mensagens/notificacoes | `apiToken` |

Observacoes de contrato:

- envelope JSON padrao:
  - `status`
  - `data`
  - `error`
  - `meta.timestamp`
  - `meta.request_id`
- autenticacao API por `Authorization: Bearer <token>` e fallback `access_token` em query para SSE.
- filtros e permissoes seguem RBAC existente do ERP.
- `POST /api/v1/notifications` e eventos internos que criam notificacoes (`message.inbound`, `order.created`) disparam Web Push real para subscriptions ativas do usuario.
- em falha por expiracao de endpoint, a subscription e marcada como inativa (`mobile_push_subscriptions.ativo = 0`).

Parametros uteis em `GET /api/v1/orders/meta`:
- `q`: busca textual de cliente (nome, telefone ou email)
- `cliente_id`: carrega equipamentos do cliente selecionado
- `equipamento_id`: resolve `tipo_id` para listar defeitos relacionados
- `tipo_id`: forca listagem de defeitos por tipo
- retorno pode incluir `checklist_entrada` quando houver modelo ativo para o tipo selecionado
- se a infraestrutura de checklist ainda nao estiver migrada, o endpoint nao quebra: retorna `checklist_entrada = null`

Payload util em `POST /api/v1/orders` (abertura completa mobile):
- obrigatorios:
  - `cliente_id`
  - `equipamento_id`
  - `relato_cliente`
- operacionais/financeiros:
  - `tecnico_id`, `prioridade`, `status`
  - `data_entrada`, `data_previsao`, `data_conclusao`, `data_entrega`
  - `diagnostico_tecnico`, `solucao_aplicada`
  - `observacoes_cliente`, `observacoes_internas`
  - `forma_pagamento`, `garantia_dias`, `garantia_validade`
  - `valor_mao_obra`, `valor_pecas`, `desconto`, `valor_total`, `valor_final`
- listas e anexos:
  - `defeitos[]`
  - `acessorios_data` (JSON)
  - `checklist_entrada_data` (JSON)
  - `fotos_checklist_entrada[item_id][]` (`multipart/form-data`, fotos por item discrepante)
  - `fotos_entrada` (`multipart/form-data`, multiplo)

## ERP - Sessao

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/sessao/heartbeat` | Sincronizar atividade e validar sessao protegida em tempo real | `auth` |

Notas de sessao:
- telas protegidas publicam metadados globais para o frontend (`session-timeout-minutes`, `session-heartbeat-url`, `session-login-url`);
- quando a sessao expira em requisicao AJAX/fetch, o backend responde `401` com JSON:
  - `auth_required`
  - `session_expired`
  - `message`
  - `redirect_url`
- o frontend usa esse envelope para mostrar aviso SweetAlert2 e redirecionar ao login sem deixar a tela falhar silenciosamente.

## ERP - Dashboard

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/dashboard` | Tela principal de indicadores operacionais | `dashboard:visualizar` |
| GET | `/admin/stats` | Dataset dos graficos do dashboard (status, financeiro e OS abertas no ano) | `dashboard:visualizar` |

Query params suportados em `GET /admin/stats`:
- `ano`: ano de referencia para a serie mensal de OS abertas (padrao: ano atual).

## ERP - Ordens de Servico

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/os` | Listagem de OS | `os:visualizar` |
| POST | `/os/datatable` | Dados server-side da listagem | `os:visualizar` |
| GET | `/os/fotos/{id}` | Galeria da OS para a listagem (perfil do equipamento + fotos da abertura) | `os:visualizar` |
| GET | `/os/nova` | Abertura de OS | `os:criar` |
| POST | `/os/salvar` | Criar OS | `os:criar` |
| GET | `/os/visualizar/{id}` | Detalhes da OS | `os:visualizar` |
| GET | `/os/status-meta/{id}` | Metadados do modal de troca de status na listagem | `os:visualizar` |
| GET | `/os/prazos-meta/{id}` | Metadados do modal rapido de prazos na listagem | `os:editar` |
| POST | `/os/prazos-ajax/{id}` | Atualizar apenas a previsao via modal da listagem | `os:editar` |
| GET | `/os/orcamento-meta/{id}` | Metadados do modal de orcamento da listagem | `os:editar` |
| POST | `/os/orcamento-ajax/{id}` | Gerar PDF de orcamento e opcionalmente enviar ao cliente | `os:editar` |
| POST | `/os/status-ajax/{id}` | Alterar status por AJAX na listagem | `os:editar` |
| POST | `/os/status/{id}` | Alterar status | `os:editar` |
| POST | `/os/whatsapp/{id}` | Envio WhatsApp (texto/PDF/template) | `os:editar` |
| POST | `/os/pdf/{id}/gerar` | Gerar documento PDF | `os:visualizar` |
| GET | `/osworkflow` | Tela administrativa do fluxo de trabalho da OS | `os:editar` |
| POST | `/osworkflow/salvar` | Persistir ordem e transicoes do fluxo da OS | `os:editar` |

Notas da interface de OS:
- `GET /os/nova` e `GET /os/visualizar/{id}` aceitam `?embed=1` para renderizacao em modal interno (sem sidebar/navbar).
- Em modo embed, formularios e acoes internas preservam o contexto para manter o fluxo dentro do modal.
- `GET /os/visualizar/{id}` usa um card de status com acoes rapidas (`Proxima etapa` e `Cancelar`) que submetem em `POST /os/status/{id}`.
- A listagem `/os` usa o mesmo workflow da visualizacao: clicar em `N OS` abre `/os/visualizar/{id}` e clicar em `Status` abre um modal enriquecido com historico, progresso e acoes rapidas.
- A listagem `/os` tambem aceita busca por `numero_os_legado` para localizar ordens migradas do sistema antigo.
- A listagem `/os` aceita `legado=1` para restringir o resultado apenas a ordens importadas do sistema anterior.
- A barra de busca global da navbar tambem passou a consultar `numero_os_legado`.
- Na listagem `/os`, clicar em `Cliente` abre `GET /clientes/visualizar/{id}?embed=1` dentro de modal interno.
- Na listagem `/os`, clicar em `Equipamento` abre `GET /equipamentos/visualizar/{id}?embed=1` dentro de modal interno.
- Na listagem `/os`, clicar em `Datas` chama `GET /os/prazos-meta/{id}` e salva por `POST /os/prazos-ajax/{id}`.
- Na listagem `/os`, clicar em `Valor Total` chama `GET /os/orcamento-meta/{id}` e gera/envia por `POST /os/orcamento-ajax/{id}`.

Payload suportado em `POST /os/datatable`:
- `q`: busca global com estrategia progressiva:
  - numero de OS por caminho otimizado quando o termo parece um codigo (`OS2026...` ou digitos equivalentes)
  - numero de OS legado por caminho otimizado quando houver match em `os.numero_os_legado`
  - cliente, equipamento e tecnico via subconsultas indexadas
  - relato textual como fallback quando nao houver match estruturado, priorizando `FULLTEXT` em `os.relato_cliente`
- `status`: lista multipla de status (`array` ou CSV em `status_list`).
- `legado`: quando `1`, restringe a grade a registros com `legacy_origem` ou `numero_os_legado`.
- `macrofase`: grupo macro do status (`os_status.grupo_macro`).
- `estado_fluxo`: estado operacional (`em_atendimento`, `em_execucao`, `pausado`, `pronto`, `encerrado`, `cancelado`).
- `data_inicio` / `data_fim`: intervalo index-friendly da data de abertura (`>= inicio do dia` e `< proximo dia`).
- `tecnico_id`: filtro por tecnico responsavel.
- `tipo_servico`: descricao de item de servico vinculada via subconsulta indexada em `os_itens`.
- `valor_min` / `valor_max`: faixa numerica de `os.valor_final`.
- `situacao`: atalho operacional (`em_triagem`, `em_atendimento`, `finalizado`, `equipamento_entregue`).

Notas de performance em `POST /os/datatable`:
- `recordsTotal` e `recordsFiltered` sao calculados separadamente para evitar contagem cara com todos os joins em toda requisicao.
- A pagina atual da grade e resolvida primeiro por IDs ordenados; os joins de apresentacao (`clientes`, `equipamentos`, `marcas`, `modelos`) so acontecem para os registros daquela pagina.
- O endpoint limita `length` a `100` linhas por chamada.
- Filtros cronologicos e de faixa numerica evitam wrappers como `DATE()` e `COALESCE()` nas colunas indexadas.
- O fallback textual do relato passou a usar `FULLTEXT` quando o indice dedicado esta disponivel no banco.
- A celula `N OS` da resposta paginada pode incluir, abaixo do numero oficial, os metadados:
  - `Legado: <numero_os_legado>`
  - `Origem: <legacy_origem>`

Resposta util em `GET /os/status-meta/{id}`:
- `ok`
- `os`
  - `id`
  - `numero_os`
  - `status`
  - `estado_fluxo`
  - `status_nome`
  - `prioridade`
  - `cliente_nome`
  - `cliente_telefone`
  - `cliente_email`
  - `equipamento_nome`
  - `equip_tipo`
  - `equip_tipo_label`
  - `equip_marca`
  - `equip_modelo`
  - `equip_serie`
  - `statusBadgeHtml`
  - `flowBadgeHtml`
  - `priorityBadgeHtml`
- `options`
  - grupos de status permitidos por macrofase, ja filtrados segundo o workflow ativo
- `primaryNextStatus`
  - destino principal sugerido para o fluxo normal
- `workflowTimeline`
  - macrofases com estado visual (`completed`, `current`, `probable`, `upcoming`)
- `workflowRecentHistory`
  - ultimas movimentacoes da OS para o modal rapido
- `hasClientPhone`
  - indica se a opcao de comunicar o cliente pode ser habilitada no frontend
- `csrfHash`

Resposta util em `GET /os/prazos-meta/{id}`:
- `ok`
- `os`
  - `id`
  - `numero_os`
  - `status`
  - `estado_fluxo`
  - `prioridade`
  - `cliente_nome`
  - `equipamento_nome`
  - badges renderizadas (`statusBadgeHtml`, `flowBadgeHtml`, `priorityBadgeHtml`)
- `dates`
  - `data_entrada`
  - `data_previsao`
  - `data_entrega`
  - `data_entrada_label`
  - `data_previsao_label`
  - `data_entrega_label`
  - `prazo_dias`
- `csrfHash`

Payload aceito em `POST /os/prazos-ajax/{id}`:
- `data_previsao`
- token CSRF

Comportamento de `POST /os/prazos-ajax/{id}`:
- rejeita tentativas de alterar `data_entrada` ou `data_entrega` por esse modal
- valida consistencia cronologica entre a entrada ja registrada e a nova previsao
- atualiza apenas `data_previsao` sem sair da listagem
- registra log operacional da alteracao

Resposta util em `GET /os/orcamento-meta/{id}`:
- `ok`
- `os`
  - `id`
  - `numero_os`
  - `status`
  - `estado_fluxo`
  - `prioridade`
  - `cliente_nome`
  - `cliente_telefone`
  - `cliente_email`
  - `equipamento_nome`
  - `equip_tipo`
  - `equip_tipo_label`
  - `equip_marca`
  - `equip_modelo`
  - `equip_serie`
  - badges renderizadas (`statusBadgeHtml`, `flowBadgeHtml`, `priorityBadgeHtml`)
- `budget`
  - `telefone`
  - `valor_mao_obra`
  - `valor_pecas`
  - `valor_total`
  - `desconto`
  - `valor_final`
  - labels monetarias correspondentes
  - `can_send_whatsapp`
  - `has_client_phone`
  - `documents[]`
- `csrfHash`

Payload aceito em `POST /os/orcamento-ajax/{id}`:
- `telefone`
- `mensagem_manual`
- `enviar_cliente` (opcional)
- token CSRF

Comportamento de `POST /os/orcamento-ajax/{id}`:
- gera uma nova versao do PDF de orcamento via `OsPdfService`
- pode anexar o PDF e enviar ao cliente pelo WhatsApp sem sair da listagem
- retorna `warning` quando o PDF e gerado com sucesso, mas a notificacao opcional falha

Payload aceito em `POST /os/status-ajax/{id}`:
- `status`
- `observacao_status` (opcional)
- `controla_comunicacao_cliente` (opcional)
- `comunicar_cliente` (opcional)
- token CSRF

Comportamento de `POST /os/status-ajax/{id}`:
- valida permissao e existencia da OS
- valida se o destino e permitido pelo fluxo
- persiste historico e efeitos colaterais operacionais do mesmo modo que a troca classica de status da visualizacao da OS
- aceita cancelamento direto quando o workflow permitir o destino `cancelado`
- quando `controla_comunicacao_cliente=1`, o backend:
  - mantem automacoes internas de ERP/CRM
  - pode suprimir templates automaticos
  - envia notificacao manual ao cliente apenas se `comunicar_cliente=1`
- pode retornar `warning` quando o status for salvo, mas a notificacao opcional falhar
- responde JSON para recarregar apenas o DataTable da listagem

Payload aceito em `POST /os/status/{id}`:
- `status`
- `observacao_status` (opcional)
- `controla_comunicacao_cliente` (opcional)
- `comunicar_cliente` (opcional)
- token CSRF

Comportamento de `POST /os/status/{id}`:
- atualiza o status conforme o workflow configurado
- aceita `cancelado` como destino direto a partir de qualquer etapa
- quando `controla_comunicacao_cliente=1`, o backend:
  - continua executando os efeitos internos de CRM/ERP
  - suprime templates automaticos de notificacao
  - envia comunicacao manual ao cliente somente se `comunicar_cliente=1`

Resposta util em `GET /os/fotos/{id}`:
- `ok`
- `os`
  - `id`
  - `numero_os`
- `profilePhotos[]`
  - `id`
  - `url`
  - `is_principal`
  - `label`
- `entryPhotos[]`
  - `id`
  - `url`
  - `is_principal`
  - `label`

Comportamento de `GET /os/fotos/{id}`:
- usado exclusivamente pela coluna `Foto` da listagem `/os`
- resolve a miniatura/galeria das fotos de perfil do equipamento
- resolve separadamente as fotos capturadas na abertura da OS (`tipo = recepcao`)
- responde JSON para popular o modal com abas sem recarregar a tabela

## ERP - Clientes (atalhos AJAX usados pela OS)

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/clientes/json/{id}` | Retornar payload completo do cliente para edicao rapida no modal da OS | `clientes:visualizar` |
| POST | `/clientes/salvar_ajax` | Criar/atualizar cliente via modal rapido da OS | `clientes:criar` ou `clientes:editar` |

Resposta de `POST /clientes/salvar_ajax`:
- `success`
- `id`
- `nome`
- `is_update`
- `cliente`

O campo `cliente` devolve os dados persistidos apos salvar para permitir sincronizacao imediata de:
- `Select2` local
- card de resumo do cliente na OS
- atualizacao AJAX da pagina pai em modo embed

Rota embed utilizada pela listagem de OS:
- `GET /clientes/visualizar/{id}?embed=1`
  - renderiza a ficha do cliente sem sidebar/navbar
  - usada no modal contextual da coluna `Cliente`

## ERP - Equipamentos (atalho AJAX da Nova OS)

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| POST | `/equipamentos/salvar-ajax` | Criar equipamento pelo modal inline da OS | `equipamentos:criar` |
| POST | `/equipamentos/atualizar-ajax/{id}` | Atualizar equipamento pelo modal inline da OS | `equipamentos:editar` |

Regras do modal inline:
- `tipo`, `marca` e `modelo` continuam obrigatorios e, em caso de falta, o frontend retorna o usuario para a aba `Info`.
- `cor` e `ao menos uma foto` sao obrigatorios para concluir o cadastro/edicao no fluxo da OS.
- Em erro de validacao, a resposta JSON pode incluir:
  - `status: error`
  - `errors`
  - `focus_tab` (`info`, `cor` ou `foto`)

Uso do `focus_tab`:
- o frontend da OS usa esse valor para abrir automaticamente a aba pendente do modal de equipamento
- o foco e reposicionado no campo visivel correspondente para o tecnico concluir a pendencia sem fechar o modal

Rota embed utilizada pela listagem de OS:
- `GET /equipamentos/visualizar/{id}?embed=1`
  - renderiza a ficha do equipamento sem sidebar/navbar
  - usada no modal contextual da coluna `Equipamento`

## ERP - Migracao legada (CLI)

Esses comandos nao sao rotas HTTP. Eles rodam no shell da aplicacao e dependem da conexao `database.legacy.*`.

| Comando | Objetivo | Observacao |
|---|---|---|
| `php spark legacy:preflight` | Validar a origem legada antes da carga | retorna `exit code 1` quando houver bloqueios |
| `php spark legacy:prepare-target` | Inspecionar ou limpar os dados operacionais ficticios da base atual | com `--execute`, apaga dados operacionais e uploads mapeados |
| `php spark legacy:import --execute` | Importar clientes, equipamentos e OS do sistema antigo | roda um preflight antes da importacao |
| `php spark legacy:import --execute --wipe-target` | Limpar a base operacional atual e depois importar o legado | usado na virada real para dados do banco `erp` |
| `php spark legacy:report` | Consolidar o ultimo run salvo | aceita `--run_id=123` |

Bloqueios tipicos do preflight:
- status sem mapeamento explicito
- relacionamentos orfaos
- aliases obrigatorios ausentes nas queries-base
- conexao legada invalida

Avisos tipicos:
- clientes sem telefone valido no legado
- nesses casos, a carga segue com aviso e persiste `telefone1` vazio no destino para respeitar o schema do ERP

Auditoria:
- `legacy_import_aliases`
- `legacy_import_runs`
- `legacy_import_events`

Notas da deduplicacao de equipamentos:
- equipamentos derivados do banco `erp` continuam recebendo `legacy_id` no formato `os-{id_legado}`
- clientes repetidos pelo mesmo `CPF/CNPJ` valido podem convergir para um cliente canonico
- quando houver `numero_serie` ou `IMEI` valido, a importacao pode reaproveitar um equipamento canonico ja migrado
- a relacao entre o alias legado e o equipamento destino fica registrada em `legacy_import_aliases`
- a mesma tabela de aliases tambem registra consolidacoes seguras de clientes por `CPF/CNPJ`
- sem identificador forte confiavel, o pipeline preserva o snapshot por OS e nao faz mesclagem automatica

## ERP - Pessoas (Contatos)

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/contatos` | Listar agenda de contatos | `clientes:visualizar` |
| GET | `/contatos/novo` | Formulario de novo contato | `clientes:criar` |
| POST | `/contatos/salvar` | Criar contato | `clientes:criar` |
| GET | `/contatos/editar/{id}` | Formulario de edicao | `clientes:editar` |
| POST | `/contatos/atualizar/{id}` | Atualizar contato | `clientes:editar` |
| GET | `/contatos/excluir/{id}` | Excluir contato | `clientes:excluir` |

## CRM

| Metodo | Rota | Objetivo |
|---|---|---|
| GET | `/crm/timeline` | Timeline unificada de eventos por cliente/OS |
| GET | `/crm/interacoes` | Listagem de interacoes CRM |
| POST | `/crm/interacoes/salvar` | Registro manual de interacao |
| GET | `/crm/followups` | Lista de follow-ups |
| POST | `/crm/followups/salvar` | Criar follow-up |
| POST | `/crm/followups/{id}/status` | Atualizar status do follow-up |
| GET | `/crm/pipeline` | Kanban de pipeline operacional por OS |
| GET | `/crm/campanhas` | Painel de automacoes/templates/segmentacao CRM |
| GET | `/crm/metricas-marketing` | Dashboard de metricas para marketing/growth |
| POST | `/crm/metricas-marketing/engajamento` | Salvar periodos de engajamento temporal |
| GET | `/crm/clientes-inativos` | Lista de clientes sem OS recente |
| POST | `/crm/clientes-inativos/followup` | Criar follow-up de reativacao |

Query params suportados em `GET /crm/metricas-marketing`:
- `periodo`: `hoje`, `7d`, `30d`, `90d`, `mes_atual`, `mes_anterior`, `custom`
- `inicio`, `fim`: obrigatorios quando `periodo=custom` (formato `YYYY-MM-DD`)
- `canal`: filtro por canal/origem (ex.: `whatsapp`)
- `responsavel_id`: filtro por responsavel da conversa para recortes operacionais
- `status`: filtro por status da conversa (ex.: `aberta`, `aguardando`, `resolvida`, `arquivada`)
- `tag_id`: filtro por tag aplicada na conversa (`conversa_tags.tag_id`)

## Central de Mensagens

| Metodo | Rota | Objetivo |
|---|---|---|
| GET | `/atendimento-whatsapp` | Inbox principal |
| GET | `/atendimento-whatsapp/chatbot` | Gestao de intencoes e regras do bot |
| GET | `/atendimento-whatsapp/faq` | Gestao de FAQ do atendimento |
| GET | `/atendimento-whatsapp/respostas-rapidas` | Catalogo de respostas rapidas |
| GET | `/atendimento-whatsapp/fluxos` | Fluxos de atendimento |
| GET | `/atendimento-whatsapp/filas` | Fila operacional e atribuicao de responsavel |
| GET | `/atendimento-whatsapp/metricas` | Dashboard de metricas da central |
| GET | `/atendimento-whatsapp/configuracoes` | Parametros operacionais da central |
| GET | `/atendimento-whatsapp/conversas` | Lista de conversas (filtros) |
| GET | `/atendimento-whatsapp/conversa/{id}` | Thread + contexto cliente/OS (JSON para AJAX da central) |
| GET | `/atendimento-whatsapp/conversa/{id}/novas` | Atualizacao incremental da thread (after_id) |
| GET | `/atendimento-whatsapp/conversa/{id}/stream` | Stream SSE da thread em tempo quase real (mensagens e ping) |
| POST | `/atendimento-whatsapp/conversa/{id}/cadastrar-contato` | Cadastrar/atualizar contato e vincular na conversa |
| POST | `/atendimento-whatsapp/enviar` | Enviar texto/PDF/anexo para conversa |
| POST | `/atendimento-whatsapp/vincular-os` | Vincular conversa a OS |
| POST | `/atendimento-whatsapp/atualizar-meta` | Atualizar status/responsavel/tags da conversa |
| POST | `/atendimento-whatsapp/sync-inbound` | Sincronizar fila de inbound |
| POST | `/atendimento-whatsapp/chatbot/intencao/salvar` | Criar/atualizar intencao |
| POST | `/atendimento-whatsapp/chatbot/intencao/toggle/{id}` | Ativar/desativar intencao |
| POST | `/atendimento-whatsapp/chatbot/regra/salvar` | Criar/atualizar regra ERP |
| POST | `/atendimento-whatsapp/chatbot/regra/toggle/{id}` | Ativar/desativar regra ERP |
| POST | `/atendimento-whatsapp/faq/salvar` | Criar/atualizar FAQ |
| POST | `/atendimento-whatsapp/faq/toggle/{id}` | Ativar/desativar FAQ |
| POST | `/atendimento-whatsapp/respostas-rapidas/salvar` | Criar/atualizar resposta rapida |
| POST | `/atendimento-whatsapp/respostas-rapidas/toggle/{id}` | Ativar/desativar resposta rapida |
| POST | `/atendimento-whatsapp/fluxos/salvar` | Criar/atualizar fluxo |
| POST | `/atendimento-whatsapp/fluxos/toggle/{id}` | Ativar/desativar fluxo |
| POST | `/atendimento-whatsapp/filas/atualizar` | Atualizar fila da conversa |
| POST | `/atendimento-whatsapp/metricas/consolidar-diario` | Consolidar agregado diario |
| POST | `/atendimento-whatsapp/configuracoes/salvar` | Salvar parametros da central |

Notas de resposta:
- `GET /atendimento-whatsapp/conversas` retorna metadados da ultima mensagem para UX estilo WhatsApp (`ultima_mensagem_texto`, `ultima_mensagem_tipo`, `ultima_mensagem_direcao`, `ultima_mensagem_bot`).
- `GET /atendimento-whatsapp/conversa/{id}` retorna `unread_before` para permitir separador visual de nao lidas na thread antes de marcar como lida.
- quando `GET /atendimento-whatsapp/conversa/{id}` for acessado diretamente no navegador sem `AJAX`, o controller redireciona para `GET /atendimento-whatsapp?conversa_id={id}` para abrir a interface completa da central em vez de expor JSON cru.
- mensagens com anexo podem retornar metadados de disponibilidade de arquivo:
  - `arquivo_disponivel` (`1` disponivel, `0` ausente no disco)
  - `arquivo_original` e `anexo_path_original` quando a referencia legada existe, mas o arquivo fisico nao foi encontrado
  - quando `arquivo_disponivel=0`, os campos `arquivo` e `anexo_path` retornam `null` para evitar 404 no frontend
- `GET /atendimento-whatsapp/conversa/{id}/novas` retorna apenas mensagens com `id > after_id`, sem recarregar toda a thread.
- `GET /atendimento-whatsapp/conversas` e `GET /atendimento-whatsapp/conversa/{id}/novas` priorizam processamento rapido da fila local inbound (sem sync pesado de historico em toda chamada), para manter polling responsivo.
- `GET /atendimento-whatsapp/conversa/{id}` segue o mesmo principio de resposta rapida, sem execucao de sync pesado no caminho critico.
- `GET /atendimento-whatsapp/conversa/{id}/stream` suporta:
  - `probe=1` (pre-check em JSON)
  - `handshake=1` (validacao de `Content-Type: text/event-stream`)
  - `after_id` (stream incremental a partir do ultimo id)
- Endpoints operacionais da Central (`conversas`, `conversa`, `novas`, `stream`, `enviar`, `vincular-os`, `atualizar-meta`, `sync-inbound`, `conversa/{id}/cadastrar-contato`) seguem envelope padrao:
  - sucesso: `{ ok: true, status, code, ... }`
  - erro: `{ ok: false, status, code, message }`
- Padrao de observabilidade por endpoint:
  - cada falha gera `code` tecnico (ex.: `CM_CONVERSAS_LIST_ERROR`, `CM_ENVIO_PROVIDER_FAILED`, `CM_META_ERROR`)
  - contexto operacional e stack reduzida vao para log interno (`logs`) e `log_message` para diagnostico rapido.
- `POST /atendimento-whatsapp/enviar`:
  - usa `422` apenas para erro corrigivel pelo usuario (conteudo vazio, conversa/telefone ausente, PDF/anexo invalido)
  - usa `503` com `code = CM_ENVIO_PROVIDER_UNAVAILABLE` quando o provider/gateway estiver inacessivel, indisponivel ou mal configurado
  - devolve mensagem operacional amigavel para a UI, mantendo o detalhe tecnico apenas no contexto de log
  - libera lock de sessao antes da chamada ao provider para reduzir bloqueio concorrente com polling da mesma thread
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`:
  - body: `nome` (opcional)
  - sucesso: `CM_CONTATO_LINKED_OK`
  - erro comum: `CM_CONTATO_FORBIDDEN`, `CM_CONTATO_PHONE_REQUIRED`, `CM_CONTATO_CREATE_FAILED`, `CM_CONTATO_LINK_ERROR`.
- No frontend, quando o handshake do SSE falha (ex.: endpoint retornando HTML), o sistema cai automaticamente para polling incremental sem interromper a operacao.
- No frontend, respostas `401/403` da Central de Mensagens encerram polling/stream e redirecionam para `/login` com alerta unico de sessao expirada.
- No frontend, respostas `502/503/504`, timeout e falhas de rede/CORS recebem mensagens padronizadas para diagnostico operacional (backend/gateway).
- `POST /atendimento-whatsapp/enviar` aceita multipart com campo `anexo` (foto/video/audio/pdf/arquivo), alem de `mensagem` e `documento_id`.
- As rotas antigas `/central-mensagens/*` continuam ativas apenas como alias de compatibilidade.

## ERP - Configuracao WhatsApp

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/configuracoes` | Tela de configuracao | `configuracoes:visualizar` |
| POST | `/configuracoes/salvar` | Persistir configuracoes | `configuracoes:editar` |
| POST | `/configuracoes/whatsapp/testar-conexao` | Teste provider direto selecionado | `configuracoes:editar` |
| POST | `/configuracoes/whatsapp/enviar-teste` | Envio de texto de teste | `configuracoes:editar` |
| GET | `/configuracoes/whatsapp/local-status` | Proxy `GET /status` do gateway | `configuracoes:visualizar` |
| GET | `/configuracoes/whatsapp/local-qr` | Proxy `GET /qr` do gateway | `configuracoes:visualizar` |
| POST | `/configuracoes/whatsapp/local-restart` | Proxy `POST /restart` do gateway | `configuracoes:editar` |
| POST | `/configuracoes/whatsapp/local-logout` | Proxy `POST /logout` do gateway | `configuracoes:editar` |
| POST | `/configuracoes/whatsapp/local-start` | Inicia Node/PM2 do gateway | `configuracoes:editar` |
| POST | `/configuracoes/whatsapp/self-check-inbound` | Diagnostico automatico inbound (gateway + webhook + token + origem) | `configuracoes:editar` |

Configuracoes gerais relevantes persistidas em `POST /configuracoes/salvar`:
- `sessao_inatividade_minutos`: timeout configuravel de inatividade da sessao (5 a 1440 minutos)

## Busca Global

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/api/busca-global` | Endpoint de busca AJAX multi-módulo | `Autenticado` |
| GET | `/busca/resultados` | Página dedicada de resultados (expandida) | `Autenticado` |

Notas da Busca Global:
- O parâmetro `q` é obrigatório para o termo de busca (mínimo 2 caracteres).
- Suporta parâmetro opcional `filter` (`all`, `os`, `clientes`, `whatsapp`, `equipamentos`, `servicos`, `pecas`, `modules`).
- Retorna JSON estruturado e agrupado por categorias.
- Respeita permissões `can(modulo, visualizar)` dinamicamente para cada grupo de resultados.

Os proxies aceitam parametro `provider`:
- `api_whats_local`
- `api_whats_linux`

Detalhe do self-check:
- retorna checklist por etapa com `ok`, `message`, `target_url`/`url` e `detail` para diagnostico rapido.

## ERP - Inbound

| Metodo | Rota | Objetivo | Auth |
|---|---|---|---|
| POST | `/webhooks/whatsapp` | Receber payload inbound e gravar em `whatsapp_inbound` | `X-Webhook-Token` ou `?token=` |

Payload inbound aceito (resumo):
- `from`/`sender`/`number`
- `message`/`text`/`body` (texto)
- `has_media`
- `media_mime_type`
- `media_filename`
- `media_base64` (quando encaminhado pelo gateway local)

Observacao:
- quando `self_check=true` (ou header `X-Webhook-Self-Check: 1`), o endpoint valida token/rota e retorna sucesso sem gravar mensagem inbound.

## Gateway Node (`whatsapp-api/server.js`)

Configuracao por `.env`:
- `HOST`
- `PORT`
- `API_TOKEN`
- `ERP_ORIGIN`
- `WHATSAPP_SESSION_PATH` (ou `SESSION_PATH`)
- `LOGS_DIR`
- `ERP_WEBHOOK_URL`
- `ERP_WEBHOOK_TOKEN`
- `ERP_WEBHOOK_TIMEOUT_MS`
- `FORWARD_INBOUND_ENABLED`
- `FORWARD_INBOUND_MEDIA_ENABLED`
- `INBOUND_MEDIA_MAX_BYTES`

Endpoints:

| Metodo | Endpoint | Descricao |
|---|---|---|
| GET | `/health` | Healthcheck do processo |
| GET | `/status` | Status operacional, metadados e QR (quando houver) |
| GET | `/qr` | QR atual para autenticacao |
| POST | `/restart` | Reiniciar inicializacao do client |
| POST | `/logout` | Destruir sessao para novo vinculo |
| POST | `/self-check-inbound` | Teste de encaminhamento inbound para o webhook ERP configurado no `.env` |
| GET | `/sync-chat-history` | Coleta historico recente por chat para reconciliacao inbound/outbound |
| POST | `/create-message` | Envio de texto, imagem ou PDF |

Fluxo inbound local -> ERP:
- com `FORWARD_INBOUND_ENABLED=1`, o gateway encaminha inbound para `ERP_WEBHOOK_URL`
- com `FORWARD_INBOUND_MEDIA_ENABLED=1`, tenta anexar `media_base64` (respeitando `INBOUND_MEDIA_MAX_BYTES`)
- em `GET /sync-chat-history`, quando `FORWARD_INBOUND_MEDIA_ENABLED=1`, o gateway tambem tenta baixar e incluir midia no payload historico
- token opcional via header `X-Webhook-Token: ERP_WEBHOOK_TOKEN`
- se houver falha de loopback local, o gateway tenta fallback automatico entre `localhost` e `127.0.0.1` para o webhook ERP.

## Formato padrao de resposta

Sucesso:
```json
{
  "success": true,
  "status": "connected",
  "message": "Status do gateway local.",
  "data": {}
}
```

Erro:
```json
{
  "success": false,
  "status": "send_failed",
  "message": "Falha ao enviar mensagem.",
  "error": {}
}
```
