# Rotas Internas (ERP + CRM + Mensageria)

Atualizado em 26/03/2026.

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
| POST | `/os/status-ajax/{id}` | Alterar status por AJAX na listagem | `os:editar` |
| POST | `/os/status/{id}` | Alterar status | `os:editar` |
| POST | `/os/whatsapp/{id}` | Envio WhatsApp (texto/PDF/template) | `os:editar` |
| POST | `/os/pdf/{id}/gerar` | Gerar documento PDF | `os:visualizar` |
| GET | `/osworkflow` | Tela administrativa do fluxo de trabalho da OS | `os:editar` |
| POST | `/osworkflow/salvar` | Persistir ordem e transicoes do fluxo da OS | `os:editar` |

Notas da interface de OS:
- `GET /os/nova` e `GET /os/visualizar/{id}` aceitam `?embed=1` para renderizacao em modal interno (sem sidebar/navbar).
- Em modo embed, formularios e acoes internas preservam o contexto para manter o fluxo dentro do modal.

Payload suportado em `POST /os/datatable`:
- `q`: busca global com estrategia progressiva:
  - numero de OS por caminho otimizado quando o termo parece um codigo (`OS2026...` ou digitos equivalentes)
  - cliente, equipamento e tecnico via subconsultas indexadas
  - relato textual como fallback quando nao houver match estruturado, priorizando `FULLTEXT` em `os.relato_cliente`
- `status`: lista multipla de status (`array` ou CSV em `status_list`).
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

Resposta util em `GET /os/status-meta/{id}`:
- `ok`
- `os`
  - `id`
  - `numero_os`
  - `status`
  - `estado_fluxo`
- `options`
  - grupos de status permitidos por macrofase, ja filtrados segundo o workflow ativo
- `csrfHash`

Payload aceito em `POST /os/status-ajax/{id}`:
- `status`
- `observacao` (opcional)
- token CSRF

Comportamento de `POST /os/status-ajax/{id}`:
- valida permissao e existencia da OS
- valida se o destino e permitido pelo fluxo
- persiste historico e efeitos colaterais operacionais do mesmo modo que a troca classica de status
- responde JSON para recarregar apenas o DataTable da listagem

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
| POST | `/create-message` | Envio de texto, imagem ou PDF |

Fluxo inbound local -> ERP:
- com `FORWARD_INBOUND_ENABLED=1`, o gateway encaminha inbound para `ERP_WEBHOOK_URL`
- com `FORWARD_INBOUND_MEDIA_ENABLED=1`, tenta anexar `media_base64` (respeitando `INBOUND_MEDIA_MAX_BYTES`)
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
