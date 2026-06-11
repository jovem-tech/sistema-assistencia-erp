# Rotas Internas (ERP + CRM + Mensageria)

Atualizado em 08/06/2026 para a linha `2.23.13`.

## Rotas publicas web

| Metodo | Rota | Objetivo | Auth |
|---|---|---|---|
| GET | `/` | Tela atual de login do ERP | publica |
| GET | `/login` | Alias explicito da tela de login | publica |
| GET | `/site` | Landing comercial publica alinhada ao estado real atual do ERP | publica |
| GET | `/apresentacao` | Alias publico da mesma landing comercial | publica |
| GET | `/orcamento/{token}` | Link publico de visualizacao/aprovacao de orcamento | publica |
| GET | `/orcamento/pacote/{token}` | Compatibilidade legada para links publicos de pacote | publica |
| GET | `/pacote/oferta/{token}` | Oferta publica dinamica de pacote | publica |
| GET | `/api/public/warranty/{segment}` | Consulta publica do selo/garantia | publica |

Observacoes:

- a landing publica usa dados institucionais do proprio ERP quando estiverem configurados em `Configuracoes`;
- as rotas `/site` e `/apresentacao` nao substituem a raiz atual do sistema, preservando o fluxo operacional de login em `/`.

## Acesso protegido ao app mobile/PWA

| Metodo | Rota | Objetivo | Auth |
|---|---|---|---|
| GET | `/atendimento-mobile` | Redirecionador protegido para o app mobile/PWA, usando `mobile_pwa_url` com fallback para `/atendimento-mobile-app/login` | `permission:atendimento_whatsapp:visualizar` |

Observacoes:

- a rota exige `atendimento_whatsapp:visualizar`;
- em navegacao normal, o acesso continua validando se o dispositivo aparenta ser mobile;
- `?preview=1` libera validacao em desktop para homologacao.

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
| GET | `/api/v1/equipments/catalog` | Catalogo de tipos, marcas e modelos com defaults de `Desktop montado` | `apiToken` |
| POST | `/api/v1/equipments/brands` | Criar marca de equipamento | `apiToken` |
| POST | `/api/v1/equipments/models` | Criar modelo de equipamento | `apiToken` |
| GET | `/api/v1/equipments/{id}` | Detalhe do equipamento com `display_name`, `display_label` e resumo tecnico | `apiToken` |
| POST | `/api/v1/equipments` | Criar equipamento com suporte a `Desktop montado` e campos tecnicos | `apiToken` |
| POST/PUT/PATCH | `/api/v1/equipments/{id}` | Atualizar equipamento e campos tecnicos | `apiToken` |
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
- quando `cliente_id` estiver presente, o endpoint devolve apenas equipamentos ativos por padrao; se `equipamento_id` apontar para um cadastro encerrado ja vinculado a uma OS historica, ele continua no payload apenas para preservar a edicao desse registro
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
  - `diagnostico_tecnico`, `solucao_aplicada`, `procedimentos_executados`
  - `observacoes_cliente`, `observacoes_internas`
  - `forma_pagamento`, `garantia_dias`, `garantia_validade`
  - `valor_mao_obra`, `valor_pecas`, `desconto`, `valor_total`, `valor_final`
- listas e anexos:
  - `defeitos[]`
  - `acessorios_data` (JSON)
  - `checklist_entrada_data` (JSON)
  - `fotos_checklist_entrada[item_id][]` (`multipart/form-data`, fotos por item discrepante)
  - `fotos_entrada` (`multipart/form-data`, multiplo)

Payload util em `POST /api/v1/equipments` e `POST/PUT/PATCH /api/v1/equipments/{id}`:
- base:
  - `cliente_id`
  - `tipo_id`
  - `marca_id` ou `marca_nome`
  - `modelo_id` ou `modelo_nome`
  - `cor`, `cor_hex`, `cor_rgb`
  - `numero_serie`, `imei`, `senha_acesso`, `estado_fisico`, `acessorios`, `observacoes`
- desktop tecnico:
  - `desktop_modalidade` (`oem` ou `montado`)
  - `gabinete_tipo`
  - `gabinete_identificacao_status`
  - `gabinete_observacao`
  - `placa_mae`
  - `chipset`
  - `processador`
  - `memoria_ram`
  - `armazenamento`
  - `placa_video`
  - `fonte_alimentacao`
- comportamento:
  - em `Desktop montado`, o backend aplica automaticamente o catalogo padrao `Montado > Desktop montado`
  - a resposta passa a devolver `display_name`, `display_label`, `technical_summary`, `configuracao_status_label`, `status_operacional`, `is_encerrado`, `motivo_encerramento_label` e `encerrado_em_label`
  - quando `numero_serie`, `MAC` ou `IMEI` ja apontarem para equipamento existente, a API responde `409` com `error.code = EQUIPMENT_DUPLICATE_IDENTIFIER` e metadados do conflito para reaproveitamento seguro do cadastro

## API interna - Agentes de inventario

| Metodo | Rota | Objetivo | Auth |
|---|---|---|---|
| POST | `/api/v1/agents/bootstrap-from-warranty` | Provisionar agente para uma OS/equipamento de bancada | publica |
| POST | `/api/v1/agents/check-in` | Receber snapshot de inventario do agente e sincronizar o equipamento | `apiToken` do agente |

Payload util em `POST /api/v1/agents/bootstrap-from-warranty`:
- `installationId`
- `warrantyOsNumber` ou `warrantyPublicUrl`
- `erpLoginEmail`
- opcionalmente, ja no bootstrap:
  - `hostname`
  - `serialNumber`
  - `manufacturer`
  - `model`
  - `deviceType`
  - `chassisType`
  - `motherboard`
  - `chipset`
  - `biosVersion`
  - `cpu`
  - `gpu`
  - `ramGb`
  - `storageSummary`
  - `windowsCaption`
  - `windowsVersion`
  - `windowsBuild`

Payload util em `POST /api/v1/agents/check-in`:
- `agentId`
- `installationId`
- `hostname`
- `serialNumber`
- `manufacturer`
- `model`
- `deviceType`
- `chassisType`
- `motherboard`
- `chipset`
- `biosVersion`
- `cpu`
- `gpu`
- `ramGb`
- `memorySummary`
- `storageSummary`
- `storageDevices[]`
- `windowsCaption`
- `windowsVersion`
- `windowsBuild`
- `collectedAtUtc`

Comportamento:
- o bootstrap vincula o agente a uma `OS`, a um `cliente` e a um `equipamento`;
- o check-in grava o snapshot bruto em `monitor_agent_snapshots`;
- o ultimo estado resumido fica em `monitor_agents`;
- para `Desktop` e `Notebook`, o backend sincroniza automaticamente os campos tecnicos em `equipamentos`.

Clientes oficiais desta API interna:
- `public/assets/agents/JovemTechBenchCollector-win-x64.zip` (`JovemTechBenchCollector.exe`), coletor portatil principal da bancada;
- `public/assets/agents/jovemtec-monitor-agent.ps1`, fallback tecnico em PowerShell.

## ERP - Coletor local de bancada

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/equipamentos/bench-collector/snapshot-local` | Ler o snapshot salvo localmente em `C:\JovemTechBenchCollector\inf_<numero_os>.json` ou no fallback local e devolver os campos ja mapeados para o formulario tecnico | `equipamentos:editar` |
| GET | `/equipamentos/bench-collector/coletar-local` | Garantir o coletor em `C:\JovemTechBenchCollector`, executar uma coleta local nova e devolver o snapshot mapeado para o formulario | `equipamentos:editar` |

Comportamento:
- destinado a cenarios em que o ERP esta rodando na mesma maquina Windows da bancada;
- `GET /equipamentos/bench-collector/coletar-local` garante a pasta `C:\JovemTechBenchCollector`, copia o `.exe` publicado quando necessario e executa uma coleta local nova em `--dry-run`;
- quando houver `OS`, o arquivo final segue o padrao `C:\JovemTechBenchCollector\inf_<numero_os>.json`;
- sem `OS`, o fallback continua sendo `C:\JovemTechBenchCollector\last-snapshot.json`;
- aceita o formato novo do coletor em `snapshot` e tambem payload plano por compatibilidade;
- o ERP agora regrava esse arquivo local como uma `OS digital` quando houver contexto disponivel, preenchendo `documentType`, `collectedAtUtc`, `collectedAtLocal`, `savedAtUtc`, `savedAtLocal`, `serviceOrder`, `customer` e `company`;
- ao concluir a coleta automatica local com sucesso, o ERP remove `JovemTechBenchCollector.exe` e `README.md` da pasta `C:\JovemTechBenchCollector`, mantendo apenas o JSON final;
- devolve campos prontos para preencher `serie`, `marca`, `modelo`, `placa_mae`, `chipset`, `processador`, `memoria_ram`, `armazenamento`, `placa_video` e sugestao de `gabinete` quando o equipamento detectado for `Desktop`;
- no payload mapeado para o formulario, o campo catalogado `modelo` passa a priorizar o `chipset`; o `model` original do inventario continua preservado no snapshot/documento completo como fallback tecnico;
- a serie prioriza a `BIOS` e usa o `MAC` da placa de rede como fallback quando a BIOS nao fornecer um valor confiavel.

Query params opcionais em `GET /equipamentos/bench-collector/coletar-local` e `GET /equipamentos/bench-collector/snapshot-local`:
- `os_id`
- `numero_os`
- `cliente_id`
- `cliente_nome`
- `cliente_telefone`
- `status`
- `prioridade`
- `tecnico_nome`
- `relato_cliente`
- `data_entrada`
- `data_previsao`
- `equipamento_rotulo`

Resposta complementar:
- `document`: payload completo do snapshot local atual (`inf_<numero_os>.json` ou fallback) ja enriquecido com a `OS digital` quando houver contexto.

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

## ERP - Notificacoes Web

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/notificacoes/navbar-feed` | Feed inicial do sino da navbar (ultimas notificacoes + contador nao lido) | `auth` |
| GET | `/notificacoes/stream` | Stream SSE autenticado para deltas em tempo real da navbar | `auth` |
| POST | `/notificacoes/lida/{id}` | Marcar uma notificacao da navbar como lida | `auth` |
| POST | `/notificacoes/lidas` | Marcar todas as notificacoes da navbar como lidas | `auth` |
| POST | `/notificacoes/limpar-lidas` | Remover do inbox web as notificacoes ja lidas do usuario atual | `auth` |

Notas do fluxo web:

- o feed e o stream reutilizam `mobile_notifications` como inbox unico entre web e app;
- `GET /notificacoes/stream` aceita `after_id` para retomar do ultimo evento conhecido;
- o stream publica eventos `delta`, `ping` e `end`;
- cada `delta` devolve:
  - `notifications`
  - `cursor.after_id`
  - `unread_count`
- o frontend da navbar usa fallback de polling quando `EventSource` nao estiver disponivel ou quando o stream cair;
- o clique no item do feed abre um modal com o teor da notificacao e so navega para a conversa vinculada quando houver confirmacao do operador;
- rotas legadas de conversa (`/conversas/{id}`) sao normalizadas para `/atendimento-whatsapp?conversa_id={id}` no backend e no frontend.

## ERP - Dashboard

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/dashboard` | Tela principal de indicadores operacionais | `dashboard:visualizar` |
| GET | `/admin/stats` | Dataset dos graficos do dashboard (status, financeiro, OS abertas no ano e OS entregues reparadas no ano) | `dashboard:visualizar` |

Query params suportados em `GET /admin/stats`:
- `ano`: ano de referencia para a serie mensal de OS abertas e OS entregues reparadas (padrao: ano atual).

Campos relevantes em `GET /admin/stats` para o grafico principal:
- `os_abertas_ano[].label`: rotulo do mes.
- `os_abertas_ano[].total`: total de OS abertas no mes.
- `os_abertas_ano[].entregues_reparadas`: total de OS entregues reparadas no mesmo mes da serie.

Notas do resumo financeiro do dashboard:
- o bloco financeiro do dashboard continua representando `caixa realizado`;
- o indicador visivel foi renomeado de `Lucro` para `Resultado caixa`;
- para leitura economica por competencia, o operador deve abrir `GET /relatorios/dre`.

## ERP - Financeiro

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/financeiro` | Painel financeiro operacional | `financeiro:visualizar` |
| GET | `/financeiro/configuracoes` | Tela de configuracoes financeiras (categorias, grupos e subgrupos DRE) | `financeiro:visualizar` |
| GET | `/financeiro/cartoes` | Tela de operadoras, bandeiras, taxas e simulador de cartoes | `financeiro:visualizar` |
| POST | `/financeiro/cartoes/operadoras/salvar` | Criar ou atualizar operadora de maquininha | `financeiro:editar` |
| POST | `/financeiro/cartoes/operadoras/desativar/{id}` | Desativar operadora de maquininha | `financeiro:excluir` |
| POST | `/financeiro/cartoes/bandeiras/salvar` | Criar ou atualizar bandeira de cartao | `financeiro:editar` |
| POST | `/financeiro/cartoes/bandeiras/desativar/{id}` | Desativar bandeira de cartao | `financeiro:excluir` |
| POST | `/financeiro/cartoes/taxas/salvar` | Criar ou atualizar taxa de cartao | `financeiro:editar` |
| POST | `/financeiro/cartoes/taxas/desativar/{id}` | Desativar taxa de cartao | `financeiro:excluir` |
| POST | `/financeiro/cartoes/simular` | Simular venda no cartao com taxa e valor liquido | `financeiro:visualizar` |
| POST | `/financeiro/configuracoes/categorias/salvar` | Criar ou atualizar categoria financeira | `financeiro:editar` |
| POST | `/financeiro/configuracoes/categorias/excluir/{id}` | Excluir categoria financeira | `financeiro:excluir` |
| POST | `/financeiro/configuracoes/dre/grupos/salvar` | Criar ou atualizar grupo DRE | `financeiro:editar` |
| POST | `/financeiro/configuracoes/dre/grupos/excluir/{id}` | Excluir grupo DRE | `financeiro:excluir` |
| POST | `/financeiro/configuracoes/dre/subgrupos/salvar` | Criar ou atualizar subgrupo DRE | `financeiro:editar` |
| POST | `/financeiro/configuracoes/dre/subgrupos/excluir/{id}` | Excluir subgrupo DRE | `financeiro:excluir` |
| GET | `/financeiro/detalhes/{id}` | Retornar JSON com o HTML do modal detalhado do lancamento | `financeiro:visualizar` |
| GET | `/financeiro/novo` | Formulario de novo lancamento | `financeiro:criar` |
| POST | `/financeiro/salvar` | Criar lancamento financeiro | `financeiro:criar` |
| GET | `/financeiro/editar/{id}` | Formulario de edicao de lancamento | `financeiro:editar` |
| POST | `/financeiro/atualizar/{id}` | Atualizar lancamento financeiro | `financeiro:editar` |
| GET | `/financeiro/excluir/{id}` | Excluir lancamento | `financeiro:excluir` |
| POST | `/financeiro/baixar/{id}` | Registrar baixa de pagamento/recebimento | `financeiro:editar` |

Campos gerenciais aceitos em criar/editar:
- `fornecedor_id`
- `data_competencia`
- `data_competencia_mes`
- `grupo_dre`
- `subgrupo_dre`
- `impacta_dre`
- `impacta_fluxo_caixa`

Observacoes de comportamento:
- `origem_tipo` e `origem_id` continuam persistidos na tabela `financeiro`, mas passam a ser calculados automaticamente pelo backend;
- quando o formulario enviar `data_competencia_mes` no formato `YYYY-MM`, o backend converte para `data_competencia = YYYY-MM-01`;
- integracoes tecnicas ainda podem enviar `data_competencia` completa quando precisarem controlar a data exata;
- se `data_competencia` vier vazia, o backend usa `data_vencimento` como fallback e, em receitas ligadas a `OS`, prioriza `data_entrega`.

Campos aceitos em `POST /financeiro/baixar/{id}`:
- `valor_movimento`
- `data_pagamento`
- `forma_pagamento`
- `observacoes_movimento`
- `impacta_fluxo_caixa`

Campos uteis em `POST /financeiro/cartoes/simular`:
- `valor_bruto` ou `valor`
- `operadora_id`
- `bandeira_id` (opcional)
- `modalidade` ou `forma_pagamento`
- `parcelas` (opcional; em debito o backend fixa `1`)

Comportamento atual:
- `GET /financeiro/configuracoes` centraliza os catalogos que abastecem os dropdowns do lancamento financeiro e a estrutura da DRE;
- `GET /financeiro/cartoes` centraliza operadoras, bandeiras, taxas e simulacao do recebimento liquido por maquininha;
- `Categoria`, `Grupo DRE` e `Subgrupo DRE` deixaram de depender apenas de texto livre no formulario;
- o campo `Fornecedor` aparece apenas em lancamentos `A pagar`, usando o cadastro oficial do modulo `Fornecedores`;
- `GET /financeiro/detalhes/{id}` abastece o modal da listagem com os detalhes do titulo, incluindo OS, cliente, equipamento, diagnostico, itens e o historico de baixas quando houver;
- quando o titulo nao possui `OS` vinculada, o endpoint continua respondendo com sucesso e entrega apenas o contexto financeiro da conta;
- em falha interna de renderizacao, o endpoint devolve JSON com `success = false` e mensagem amigavel para o frontend tratar sem quebrar o modal.
- `POST /financeiro/baixar/{id}` passou a registrar um `movimento financeiro`, permitindo `baixa parcial` e `multiplas baixas` no mesmo titulo;
- o backend recalcula automaticamente `status`, `valor quitado`, `saldo em aberto`, `ultima baixa` e `formas de pagamento` do titulo a partir dos movimentos;
- quando esse titulo estiver vinculado a uma OS em `entregue_pagamento_pendente`, `POST /financeiro/baixar/{id}` tambem sincroniza o encerramento definitivo da OS assim que o saldo em aberto chegar a zero;
- `POST /financeiro/cartoes/simular` responde com `valor_taxa`, `valor_liquido`, `taxa_percentual`, `taxa_fixa`, `parcelas`, `modalidade`, `prazo_recebimento_dias` e `data_prevista_recebimento`;
- quando a classificacao gerencial nao e enviada, o backend tenta inferir a partir do tipo, categoria e vinculo com OS.

## ERP - Relatorios Financeiros

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/relatorios/financeiro` | Relatorio operacional de movimentacoes financeiras | `relatorios:visualizar` |
| GET | `/relatorios/dre` | DRE gerencial por competencia | `relatorios:visualizar` |
| GET | `/relatorios/fluxo-caixa` | Fluxo de caixa realizado e projetado | `relatorios:visualizar` |

Query params uteis:
- `mes=YYYY-MM`

Leitura funcional:
- `/relatorios/financeiro` mostra a lista operacional de titulos, o `resultado de caixa`, o `valor quitado`, o `saldo em aberto` e a composicao do mes por categoria, reaproveitando o catalogo financeiro configurado;
- `/relatorios/dre` mostra `receita liquida`, `custos diretos`, `lucro bruto` e `resultado liquido`;
- `/relatorios/fluxo-caixa` mostra `saldo inicial`, `saldo final`, `previstos`, `saldo projetado`, abas de `Grade diaria`, `Movimentos`, `Titulos previstos`, `Resumo` e `Dashboard`, alem da composicao de realizados/previstos por categoria com a mesma classificacao do modulo financeiro;
- no `fluxo de caixa`, o `realizado` passa a ler cada movimento baixado e o `previsto` passa a ler apenas o `saldo em aberto` de titulos `pendentes` e `parciais`;
- movimentos realizados em `cartao_credito` e `cartao_debito` tambem carregam os metadados de `financeiro_movimentos_cartao` para mostrar a taxa paga a operadora na propria linha da aba `Movimentos`;
- os filtros por categoria ficam dentro das abas `Movimentos` e `Titulos previstos`, refinando as tabelas internas sem trocar de rota nem criar endpoint extra;
- a aba `Dashboard` reaproveita os mesmos dados do payload da view para montar graficos e KPIs, sem nova rota dedicada.

## ERP - Orcamentos / Catalogo rapido

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/orcamentos/os-abertas/cliente` | Listar OS abertas do cliente para o card `Vinculo OS` do formulario | `orcamentos:visualizar` |
| GET | `/orcamentos/item/catalogo` | Buscar pecas e servicos ativos para o Select2 do item | `orcamentos:visualizar` |
| POST | `/estoque/salvar_ajax` | Cadastrar peca rapidamente a partir do formulario de Orcamentos | `estoque:criar` |
| POST | `/servicos/salvar_ajax` | Cadastrar servico rapidamente a partir do formulario de Orcamentos | `servicos:criar` |

Comportamento atual:

- `GET /orcamentos/os-abertas/cliente` aceita `cliente_id` obrigatorio e `q` opcional para filtrar por numero, status e resumo do equipamento;
- o endpoint retorna `results[]` com `os_id`, `numero_label`, `status_label` e dados resumidos do equipamento;
- o contrato desse lookup tolera marca/modelo ausentes sem devolver `500`;
- `GET /orcamentos/item/catalogo` continua sendo a fonte oficial do Select2 de itens do orcamento;
- `POST /estoque/salvar_ajax` e `POST /servicos/salvar_ajax` retornam o item no mesmo formato do catalogo, para que a linha atual do orcamento seja preenchida sem reload;
- o modal rapido do orcamento usa esses endpoints apenas para `Peca` e `Servico`;
- o frontend aplica imediatamente `descricao`, `referencia`, `valor` e, no caso de `peca`, tambem o bloco de `precificacao` com valor recomendado.

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
| GET | `/os/encerramento-meta/{id}` | Metadados do modal `Baixa da OS` na listagem | `os:visualizar` |
| POST | `/os/prazos-ajax/{id}` | Atualizar apenas a previsao via modal da listagem | `os:editar` |
| GET | `/os/orcamento-meta/{id}` | Metadados do modal de orcamento da listagem | `os:editar` |
| GET | `/os/whatsapp-meta/{id}` | Metadados do modal rapido de WhatsApp na listagem | `os:editar` |
| POST | `/os/orcamento-ajax/{id}` | Gerar PDF de orcamento e opcionalmente enviar ao cliente | `os:editar` |
| POST | `/os/status-ajax/{id}` | Alterar status por AJAX na listagem | `os:editar` |
| POST | `/os/encerrar-ajax/{id}` | Registrar a baixa tecnica da OS direto da listagem | `os:encerrar` |
| POST | `/os/status/{id}` | Alterar status | `os:editar` |
| POST | `/os/whatsapp/{id}` | Envio WhatsApp (texto/PDF/template) | `os:editar` |
| POST | `/os/email/{id}/enviar` | Envio de PDF da OS por e-mail | `os:editar` |
| POST | `/os/pdf/{id}/gerar` | Gerar documento PDF | `os:visualizar` |
| GET | `/os/imprimir/{id}` | Pre-visualizar ou imprimir consolidado da OS | `os:visualizar` |
| GET | `/osworkflow` | Tela administrativa do fluxo de trabalho da OS | `os:editar` |
| POST | `/osworkflow/salvar` | Persistir ordem e transicoes do fluxo da OS | `os:editar` |

Notas da interface de OS:
- `GET /os/nova` e `GET /os/visualizar/{id}` aceitam `?embed=1` para renderizacao em modal interno (sem sidebar/navbar).
- Em modo embed, formularios e acoes internas preservam o contexto para manter o fluxo dentro do modal.
- `POST /os/salvar` agora pode redirecionar para a visualizacao com a prompt reativa `os_post_create_pdf_prompt`, usada para preparar o envio do PDF de abertura logo apos a criacao.
- `POST /os/pdf/{id}/gerar` aceita os campos opcionais `incluir_fotos_abertura` e `grupos_fotos_abertura` quando `tipo_documento = abertura`, permitindo salvar a nova versao do comprovante com `fotos de perfil`, `fotos de entrada` ou ambos.
- `POST /os/pdf/{id}/gerar` passa a redirecionar de volta para `/os/visualizar/{id}#tab-documentos`, preservando o contexto da aba `Documentos` apos a geracao.
- `POST /os/whatsapp/{id}` e `POST /os/email/{id}/enviar` seguem a mesma ancora `#tab-documentos` nos fluxos HTML da aba `Documentos`, mantendo a visualizacao no mesmo contexto apos sucesso ou erro.
- `GET /os/visualizar/{id}` usa um card de status com acoes rapidas (`Proxima etapa` e `Cancelar`) que submetem em `POST /os/status/{id}`.
- `GET /os/visualizar/{id}` passou a concentrar geracao/listagem de PDFs e os envios por `WhatsApp` e `e-mail` dentro da aba `Documentos`.
- A listagem `/os` usa o mesmo workflow da visualizacao: clicar em `N OS` abre `/os/visualizar/{id}` e clicar em `Status` abre um modal enriquecido com historico, progresso e acoes rapidas.
- Quando chega uma notificacao `orcamento.public_status_changed`, a listagem `/os` recarrega automaticamente a grade para atualizar o badge comercial do orcamento sem `F5`.
- A listagem `/os` tambem aceita busca por `numero_os_legado` para localizar ordens migradas do sistema antigo.
- A listagem `/os` aceita `legado=1` para restringir o resultado apenas a ordens importadas do sistema anterior.
- A barra de busca global da navbar tambem passou a consultar `numero_os_legado`.
- Na listagem `/os`, clicar em `Cliente` abre `GET /clientes/visualizar/{id}?embed=1` dentro de modal interno.
- Na listagem `/os`, clicar em `Equipamento` abre `GET /equipamentos/visualizar/{id}?embed=1` dentro de modal interno.
- Na listagem `/os`, clicar em `Datas` chama `GET /os/prazos-meta/{id}` e salva por `POST /os/prazos-ajax/{id}`.
- Na listagem `/os`, clicar em `Baixa da OS` chama `GET /os/encerramento-meta/{id}` e conclui por `POST /os/encerrar-ajax/{id}` sem sair da fila operacional; se a baixa terminar em `Equipamento descartado`, o equipamento vinculado e encerrado automaticamente.
- Na listagem `/os`, clicar no telefone da coluna `Cliente` chama `GET /os/whatsapp-meta/{id}` e envia por `POST /os/whatsapp/{id}` sem sair da fila operacional.
- Na listagem `/os`, clicar no resumo da coluna `Valor` chama `GET /os/orcamento-meta/{id}` e gera/envia por `POST /os/orcamento-ajax/{id}`.

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

Resposta util em `GET /os/encerramento-meta/{id}`:
- `ok`
- `os`
  - `id`
  - `numero_os`
  - `status`
  - `estado_fluxo`
  - `cliente_nome`
  - `cliente_telefone`
  - `cliente_email`
  - `equipamento_nome`
  - `equip_tipo`
  - `equip_tipo_label`
  - `equip_serie`
  - `valor_mao_obra`
  - `valor_pecas`
  - `valor_total`
  - `desconto`
  - `valor_final`
  - `data_entrega`
  - `baixa_tecnica_em`
  - `status_final_pendente_pagamento`
  - badges renderizadas (`statusBadgeHtml`, `flowBadgeHtml`, `priorityBadgeHtml`)
- `encerramento`
  - `opcoes[]`
  - `retorno_padrao`
  - `retorno_disponivel`
  - `comunicacao_cliente_disponivel`
- `financeiro`
  - `titulo_id`
  - `status_titulo`
  - `status_titulo_label`
  - `valor_total`
  - `valor_recebido`
  - `valor_adiantamento`
  - `valor_recebido_total`
  - `valor_em_aberto`
  - `percentual_quitado`
  - `ultimo_recebimento_em`
  - `formas_pagamento_resumo`
- `custos`
  - `pecas`
  - `servicos`
  - `total`
- `cartao`
  - `disponivel`
  - `dataset.operadoras[]`
  - `dataset.bandeiras[]`
  - `dataset.taxas[]`
- `csrfHash`
- quando a OS estiver zerada financeiramente e houver `orcamento aprovado` ou `convertido`, os campos `os.valor_*` e `financeiro.valor_*` retornam o valor efetivo derivado do orcamento.

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
- quando a OS ainda nao refletir esses valores na propria tabela, o endpoint retorna o total efetivo usando o `orcamento aprovado/convertido` mais recente vinculado.
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

Payload aceito em `POST /os/encerrar-ajax/{id}`:
- `encerrar_como`
- `data_entrega`
- `observacao_encerramento` (opcional)
- `comunicar_cliente` (opcional)
- `agendar_retorno` (opcional)
- `retorno_data` (opcional, obrigatorio quando `agendar_retorno=1`)
- `recebimentos_json`
  - lista JSON com:
    - `classificacao_recebimento` (`baixa`, `adiantamento` ou `sinal`)
    - `forma_pagamento`
    - `valor`
    - `data_pagamento`
    - `operadora_id` (opcional)
    - `bandeira_id` (opcional)
    - `parcelas` (opcional)
    - `modalidade` (opcional)
    - `observacoes` (opcional)
- token CSRF

Comportamento de `POST /os/encerrar-ajax/{id}`:
- valida permissao `os:encerrar`, existencia da OS e o destino final da baixa;
- aceita `entregue_reparado`, `devolvido_sem_reparo` e `descartado` como destinos finais;
- cria ou reaproveita o titulo financeiro `A receber` da OS;
- registra os valores recebidos usando o fluxo de movimentos do financeiro, com impacto em `Fluxo de Caixa` e `DRE`;
- somente itens com `classificacao_recebimento=baixa` acionam a baixa operacional da OS;
- se a baixa operacional for parcial, salva a OS como `entregue_pagamento_pendente` e memoriza o status final desejado em `status_final_pendente_pagamento`;
- se a baixa operacional quitar o saldo, aplica o status final escolhido no campo `encerrar_como`;
- itens com `classificacao_recebimento=adiantamento` ou `classificacao_recebimento=sinal` registram pagamento antecipado e retornam `status_unchanged=true`, sem alterar status, datas de entrega/baixa ou cobrancas;
- preserva na observacao do movimento se o lancamento foi `recebimento da baixa`, `adiantamento` ou `sinal`, para a aba `Valores` da OS refletir a classificacao;
- quando houver recebimento em cartao, aplica a taxa configurada, grava metadados do movimento e cria a despesa automatica `Taxa de cartao`;
- agenda cobranca automatica em `1`, `3` e `5` dias quando a OS concluir com saldo pendente;
- pode criar follow-up automatico de retorno pos-servico;
- quando o destino final for `descartado`, encerra automaticamente o equipamento vinculado com o mesmo motivo;
- quando `comunicar_cliente=1`, o backend envia a mensagem de WhatsApp da baixa com o PDF consolidado da impressao A4 da OS anexado;
- quando a quitacao financeira chega a zero, sincroniza o status final definitivo da OS automaticamente;
- responde JSON com `status`, `status_label`, `lucro_estimado`, `taxa_financeira_estimada`, `warning` opcional e `csrfHash`.

Payload aceito em `POST /os/status/{id}`:
- `status`
- `observacao_status` (opcional)
- `controla_comunicacao_cliente` (opcional)
- `comunicar_cliente` (opcional)
- token CSRF

Comportamento de `POST /os/status/{id}`:
- atualiza o status conforme o workflow configurado
- aceita `cancelado` como destino direto a partir de qualquer etapa
- quando o novo status for `descartado`, encerra automaticamente o equipamento vinculado e preserva o historico do cliente
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

Query params uteis em `GET /os/imprimir/{id}`:
- `formato=a4|80mm`
- `incluir_fotos=0|1`
- `grupos_fotos=perfil,entrada,acessorios,estado_fisico,checklist`
- `auto_print=1` (fluxo termico)

Campos uteis em `POST /os/whatsapp/{id}` quando o envio partir do consolidado:
- `print_formato`
- `print_incluir_fotos`
- `print_grupos_fotos`
- `template_codigo`
- `mensagem_manual`

Comportamento atual do envio consolidado:
- quando `print_grupos_fotos` vier preenchido, o backend limita o PDF temporario aos grupos de fotos solicitados;
- essa combinacao e usada tanto no fluxo manual da aba `Documentos` quanto na prompt automatica exibida logo apos criar uma nova OS.

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
| POST | `/equipamentos/encerrar/{id}` | Encerrar a vida util do equipamento sem excluir o historico | `equipamentos:encerrar` |
| POST | `/equipamentos/reativar/{id}` | Reativar um equipamento encerrado para voltar a aceitar novas OS | `equipamentos:encerrar` |
| POST | `/equipamentos/vincular-existente-ajax` | Vincular o cliente atual a um equipamento ja encontrado por serie, MAC ou IMEI | `equipamentos:criar` |
| GET | `/equipamentos/por-cliente/{clienteId}` | Retornar somente equipamentos operacionais para selecao da OS, com suporte a `selected_id` historico | `equipamentos:visualizar` |

Regras do modal inline:
- `tipo` continua obrigatorio em todos os casos.
- `marca` e `modelo` continuam obrigatorios, exceto quando o tipo for `Desktop` em modo `montado`.
- `cor` e `ao menos uma foto` sao obrigatorios para concluir o cadastro/edicao no fluxo da OS.
- se `numero de serie`, `MAC` ou `IMEI` ja existirem no ERP, os endpoints retornam `409` com `status = duplicate_conflict`.
- quando o identificador ja pertencer a outro cliente, o frontend pode usar `POST /equipamentos/vincular-existente-ajax` para reaproveitar o mesmo equipamento na `OS`.
- `POST /equipamentos/encerrar/{id}` exige `motivo_encerramento`, aceita `observacao_encerramento` e retorna o equipamento atualizado com `csrfHash`.
- `POST /equipamentos/reativar/{id}` exige `motivo_reativacao`, aceita `observacao_reativacao` e devolve o equipamento para `ativo`.
- os dois endpoints acima tambem devolvem `lifecycle_history_html` e `lifecycle_history_count` para sincronizar a timeline do equipamento sem refresh manual;
- o encerramento e bloqueado se existir `OS` aberta/em andamento para o equipamento.
- equipamentos encerrados deixam de aceitar novos vinculos operacionais e nao aparecem mais por padrao nas listas de selecao da `OS` e do `Orcamento`.
- quando a OS e finalizada como `descartado`, o backend encerra automaticamente o equipamento vinculado com o mesmo motivo e preserva o historico do cliente.
- `GET /equipamentos/por-cliente/{clienteId}` aceita `selected_id` para manter um equipamento encerrado visivel apenas quando uma OS historica ja usa esse cadastro e esta sendo editada.
- o modal passa a aceitar os campos:
  - `desktop_modalidade`
  - `gabinete_tipo`
  - `gabinete_identificacao_status`
  - `gabinete_observacao`
  - `placa_mae`
  - `chipset`
  - `processador`
  - `memoria_ram`
  - `armazenamento`
  - `placa_video`
  - `fonte_alimentacao`
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

## ERP - Fornecedores

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/fornecedores` | Listagem de fornecedores | `fornecedores:visualizar` |
| GET | `/fornecedores/novo` | Formulario de novo fornecedor | `fornecedores:criar` |
| POST | `/fornecedores/salvar` | Criar fornecedor | `fornecedores:criar` |
| GET | `/fornecedores/consultar-cnpj` | Consultar dados publicos de CNPJ para autofill do formulario | `fornecedores:visualizar` |
| GET | `/fornecedores/editar/{id}` | Formulario de edicao | `fornecedores:editar` |
| POST | `/fornecedores/atualizar/{id}` | Atualizar fornecedor | `fornecedores:editar` |
| GET | `/fornecedores/excluir/{id}` | Excluir fornecedor | `fornecedores:excluir` |

Query params uteis:
- `cnpj`: CNPJ com ou sem mascara.

Comportamento atual:
- `GET /fornecedores/consultar-cnpj` reutiliza o `CnpjLookupService` do ERP;
- o endpoint devolve `razao_social`, `nome_fantasia`, `inscricao estadual`, `email`, `telefones` e `endereco` quando os provedores publicos tiverem esses dados;
- respostas de validacao de documento invalido retornam `422`;
- indisponibilidade temporaria de provedores ou ausencia de dados publicos continuam com `200`, mas com `success = false` e mensagem orientativa para o frontend manter o preenchimento manual.

## ERP - Migracao legada (CLI)

Esses comandos nao sao rotas HTTP. Eles rodam no shell da aplicacao e dependem da conexao `database.legacy.*`.

| Comando | Objetivo | Observacao |
|---|---|---|
| `php spark legacy:preflight` | Validar a origem legada antes da carga | retorna `exit code 1` quando houver bloqueios |
| `php spark legacy:prepare-target` | Inspecionar ou limpar os dados operacionais ficticios da base atual | com `--execute`, apaga dados operacionais e uploads mapeados |
| `php spark legacy:import --execute` | Importar clientes, equipamentos e OS do sistema antigo | roda um preflight antes da importacao |
| `php spark legacy:import --execute --wipe-target` | Limpar a base operacional atual e depois importar o legado | usado na virada real para dados do banco `erp` |
| `php spark legacy:report` | Consolidar o ultimo run salvo | aceita `--run_id=123` |
| `php spark os:cobrancas` | Processar a regua automatica de cobranca das OS com pagamento pendente | deve rodar periodicamente via cron ou agendador do servidor |

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

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/crm/clientes` | Lista de clientes acompanhados pelo CRM | `crm:visualizar` |
| GET | `/crm/timeline` | Timeline unificada de eventos por cliente/OS | `crm:visualizar` |
| GET | `/crm/interacoes` | Listagem de interacoes CRM | `crm:visualizar` |
| POST | `/crm/interacoes/salvar` | Registro manual de interacao | `crm:criar` |
| GET | `/crm/followups` | Lista de follow-ups | `crm:visualizar` |
| POST | `/crm/followups/salvar` | Criar follow-up | `crm:criar` |
| POST | `/crm/followups/{id}/status` | Atualizar status do follow-up | `crm:editar` |
| GET | `/crm/pipeline` | Kanban de pipeline operacional por OS | `crm:visualizar` |
| GET | `/crm/campanhas` | Painel de automacoes/templates/segmentacao CRM | `crm:visualizar` |
| GET | `/crm/metricas-marketing` | Dashboard de metricas para marketing/growth | `crm:visualizar` |
| POST | `/crm/metricas-marketing/engajamento` | Salvar periodos de engajamento temporal | `crm:editar` |
| GET | `/crm/clientes-inativos` | Lista de clientes sem OS recente | `crm:visualizar` |
| POST | `/crm/clientes-inativos/followup` | Criar follow-up de reativacao | `crm:criar` |

Query params suportados em `GET /crm/metricas-marketing`:
- `periodo`: `hoje`, `7d`, `30d`, `90d`, `mes_atual`, `mes_anterior`, `custom`
- `inicio`, `fim`: obrigatorios quando `periodo=custom` (formato `YYYY-MM-DD`)
- `canal`: filtro por canal/origem (ex.: `whatsapp`)
- `responsavel_id`: filtro por responsavel da conversa para recortes operacionais
- `status`: filtro por status da conversa (ex.: `aberta`, `aguardando`, `resolvida`, `arquivada`)
- `tag_id`: filtro por tag aplicada na conversa (`conversa_tags.tag_id`)

## Central de Mensagens

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/atendimento-whatsapp` | Inbox principal | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/chatbot` | Gestao de intencoes e regras do bot | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/faq` | Gestao de FAQ do atendimento | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/respostas-rapidas` | Catalogo de respostas rapidas | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/fluxos` | Fluxos de atendimento | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/filas` | Fila operacional e atribuicao de responsavel | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/metricas` | Dashboard de metricas da central | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/configuracoes` | Parametros operacionais da central | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/conversas` | Lista de conversas (filtros) | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/conversas/stream` | Stream SSE da fila lateral de conversas (cursor + snapshot) | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/conversa/{id}` | Thread + contexto cliente/OS (JSON para AJAX da central) | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/conversa/{id}/novas` | Atualizacao incremental da thread (after_id) | `atendimento_whatsapp:visualizar` |
| GET | `/atendimento-whatsapp/conversa/{id}/stream` | Stream SSE da thread em tempo quase real (mensagens e ping) | `atendimento_whatsapp:visualizar` |
| POST | `/atendimento-whatsapp/conversa/{id}/cadastrar-contato` | Cadastrar/atualizar contato e vincular na conversa | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/enviar` | Enviar texto/PDF/anexo para conversa | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/vincular-os` | Vincular conversa a OS | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/atualizar-meta` | Atualizar status/responsavel/tags da conversa | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/sync-inbound` | Sincronizar fila de inbound | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/chatbot/intencao/salvar` | Criar/atualizar intencao | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/chatbot/intencao/toggle/{id}` | Ativar/desativar intencao | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/chatbot/regra/salvar` | Criar/atualizar regra ERP | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/chatbot/regra/toggle/{id}` | Ativar/desativar regra ERP | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/faq/salvar` | Criar/atualizar FAQ | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/faq/toggle/{id}` | Ativar/desativar FAQ | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/respostas-rapidas/salvar` | Criar/atualizar resposta rapida | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/respostas-rapidas/toggle/{id}` | Ativar/desativar resposta rapida | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/fluxos/salvar` | Criar/atualizar fluxo | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/fluxos/toggle/{id}` | Ativar/desativar fluxo | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/filas/atualizar` | Atualizar fila da conversa | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/metricas/consolidar-diario` | Consolidar agregado diario | `atendimento_whatsapp:editar` |
| POST | `/atendimento-whatsapp/configuracoes/salvar` | Salvar parametros da central | `atendimento_whatsapp:editar` |

Observacao de dominio:
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato` continua exigindo tambem permissao de escrita em `clientes` no backend (`clientes:criar` ou `clientes:editar`), porque a acao pode criar ou atualizar cadastro oficial de contato/cliente.

## Precificacao

| Metodo | Rota | Objetivo | Permissao |
|---|---|---|---|
| GET | `/precificacao` | Redirecionar para a configuracao oficial do modulo | `precificacao:visualizar` |
| GET | `/precificacao/configuracao` | Parametros e overrides de precificacao | `precificacao:visualizar` |
| POST | `/precificacao/configuracao/salvar` | Salvar parametros detalhados e sincronizar engine | `precificacao:editar` |
| GET | `/precificacao/simulador` | Simulador de pecas e servicos | `precificacao:visualizar` |
| GET | `/precificacao/categoria-encargos/{id}` | Ler encargos ativos da categoria | `precificacao:visualizar` |
| POST | `/precificacao/categoria-encargos/{id}` | Atualizar encargos da categoria | `precificacao:editar` |
| GET | `/precificacao/categoria-override` | Resolver override de categoria/tipo | `precificacao:visualizar` |
| POST | `/precificacao/salvar` | Salvar configuracao operacional resumida | `precificacao:editar` |
| POST | `/precificacao/simular-peca` | Calcular quote de peca | `precificacao:visualizar` |
| POST | `/precificacao/simular-servico` | Calcular quote de servico | `precificacao:visualizar` |

Notas de resposta:
- `GET /atendimento-whatsapp/conversas` retorna metadados da ultima mensagem para UX estilo WhatsApp (`ultima_mensagem_texto`, `ultima_mensagem_tipo`, `ultima_mensagem_direcao`, `ultima_mensagem_bot`).
- `GET /atendimento-whatsapp/conversas` tambem pode retornar `contato_avatar_url` quando o contato ja estiver sincronizado com avatar WhatsApp.
- `GET /atendimento-whatsapp/conversa/{id}` retorna `unread_before` para permitir separador visual de nao lidas na thread antes de marcar como lida.
- `GET /atendimento-whatsapp/conversa/{id}` pode enriquecer a conversa com `contato_nome`, `contato_perfil_nome` e `contato_avatar_url`.
- quando `GET /atendimento-whatsapp/conversa/{id}` for acessado diretamente no navegador sem `AJAX`, o controller redireciona para `GET /atendimento-whatsapp?conversa_id={id}` para abrir a interface completa da central em vez de expor JSON cru.
- mensagens com anexo podem retornar metadados de disponibilidade de arquivo:
  - `arquivo_disponivel` (`1` disponivel, `0` ausente no disco)
  - `arquivo_original` e `anexo_path_original` quando a referencia legada existe, mas o arquivo fisico nao foi encontrado
  - quando `arquivo_disponivel=0`, os campos `arquivo` e `anexo_path` retornam `null` para evitar 404 no frontend
- `GET /atendimento-whatsapp/conversa/{id}/novas` retorna apenas mensagens com `id > after_id`, sem recarregar toda a thread.
- `GET /atendimento-whatsapp/conversas` e `GET /atendimento-whatsapp/conversa/{id}/novas` priorizam processamento rapido da fila local inbound (sem sync pesado de historico em toda chamada), para manter polling responsivo.
- `GET /atendimento-whatsapp/conversa/{id}` segue o mesmo principio de resposta rapida, sem execucao de sync pesado no caminho critico.
- `GET /atendimento-whatsapp/conversas/stream` suporta:
  - `probe=1` (pre-check em JSON)
  - `handshake=1` (validacao de `Content-Type: text/event-stream`)
  - `after_cursor` (stream incremental da fila a partir do ultimo snapshot conhecido)
- `GET /atendimento-whatsapp/conversa/{id}/stream` suporta:
  - `probe=1` (pre-check em JSON)
  - `handshake=1` (validacao de `Content-Type: text/event-stream`)
  - `after_id` (stream incremental a partir do ultimo id)
- Endpoints operacionais da Central (`conversas`, `conversas/stream`, `conversa`, `novas`, `stream`, `enviar`, `vincular-os`, `atualizar-meta`, `sync-inbound`, `conversa/{id}/cadastrar-contato`) seguem envelope padrao:
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
  - quando o envio humano for confirmado, a thread passa para `aguardando_humano=1` e `automacao_ativa=0`
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
- `whatsapp_direct_provider`: aceita `menuia`, `evolution`, `api_whats_local`, `api_whats_linux` e `webhook`
- `whatsapp_evolution_url`
- `whatsapp_evolution_apikey`
- `whatsapp_evolution_instance`
- `whatsapp_evolution_timeout`
- `whatsapp_evolution_sync_avatar`

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
- `key.remoteJid`
- `key.participant`
- `pushName`
- `message.conversation`
- `message.extendedTextMessage.text`
- `message.imageMessage.caption`
- `message.videoMessage.caption`
- `message.documentMessage.caption`
- `message.documentMessage.fileName`

Observacao:
- quando `self_check=true` (ou header `X-Webhook-Self-Check: 1`), o endpoint valida token/rota e retorna sucesso sem gravar mensagem inbound.
- um workflow n8n pode usar essa mesma rota para espelhar mensagens recebidas por canais externos antes de responder ao cliente, preservando a Central de Mensagens do ERP como trilha oficial.
- aliases de provider normalizados pela Central:
  - `evolution_api` -> `evolution`
  - `local_node` -> `api_whats_local`
- o projeto agora possui duas referencias n8n para esse espelhamento:
  - `documentacao/10-deploy/n8n/atendimento-clientes-whatsapp.json`
  - `documentacao/10-deploy/n8n/atendimento-clientes-whatsapp-ai-clientes.json`
  - `documentacao/10-deploy/n8n/evolution-whatsapp-atendimento-ia-erp.json`
- no fluxo recomendado com Evolution, o webhook da instancia deve apontar primeiro para o n8n, e o n8n usa `POST /webhooks/whatsapp` para manter o ERP como historico oficial.

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

Orquestracao n8n suportada:
- um workflow n8n externo pode consumir mensagens de outro provedor, enviar copia para `POST /webhooks/whatsapp` e reutilizar `POST /create-message` para responder pelo gateway oficial;
- referencia entregue no projeto: `documentacao/10-deploy/n8n/atendimento-clientes-whatsapp.json`.

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
