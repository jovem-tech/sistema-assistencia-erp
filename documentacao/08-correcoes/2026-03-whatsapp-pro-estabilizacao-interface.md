# Registro de Correcao: WhatsApp Pro (WhaTicket) - Estabilizacao de Interface

**Data:** 18/03/2026  
**Modulo:** WhatsApp Pro (`/whaticket`)

> Status em 20/03/2026: **historico arquivado**.  
> O ERP removeu o modulo `/whaticket` e adotou modulo unico interno em `/atendimento-whatsapp` (Central de Mensagens).

## Problema observado
- A tela carregava apenas um iframe com URL fixa e sem camada de estado.
- Quando o front-end WhaTicket ficava indisponivel, a interface virava uma area cinza sem diagnostico para o usuario.
- Nao havia validacao de host/porta antes do load do iframe.
- UX sem feedback claro de loading, erro ou reconexao.

## Causa raiz
- Implementacao minima no controller e na view:
  - URL hardcoded (`localhost:3000`)
  - sem endpoint de health-check dedicado para a tela
  - sem JS modular de orquestracao de estados

## Correcao aplicada

### Backend (PHP)
- Refatorado `app/Controllers/Whaticket.php`:
  - `resolveWhaticketContext()` para montar URL final com configuracao e SSO:
    - `whaticket_url` (default `http://127.0.0.1:3000`)
    - `whaticket_sso_secret`
    - `whaticket_sso_path` (default `/sso/{email}/{secret}`)
  - `socketProbe()` para teste rapido de conectividade TCP.
  - novo endpoint `status()` com resposta JSON (`ok`, `status`, `message`, `data`) usando:
    - probe socket
    - probe HTTP (`/api/health`, `/`, URL SSO)

### Rotas
- Incluida rota:
  - `GET /whaticket/status` -> `Whaticket::status` (filtro `auth`)

### Frontend (HTML/CSS)
- Reestruturada `app/Views/whaticket/index.php` mantendo Bootstrap e layout do sistema.
- Adicionados estados visuais profissionais:
  - loading com skeleton (3 colunas)
  - reconnecting
  - erro com CTA de tentativa
  - sucesso (iframe exibido)
- Incluido badge de status no topo do card.
- Mantidas acoes principais:
  - Atualizar
  - Reconectar
  - Nova aba
- Removido ajuste global agressivo de `body { overflow: hidden; }` nesta tela.

### Frontend (JavaScript)
- Criado `public/assets/js/whaticket.js` (modular, sem duplicar listeners):
  - maquina de estados da tela
  - polling de status do backend
  - timeout de carregamento do iframe
  - reconexao manual/automatica com feedback visual
  - fallback SweetAlert2 para mensagens de bloqueio

## Beneficio tecnico
- Interface deixa de falhar silenciosamente.
- Usuario passa a receber feedback claro de disponibilidade.
- Diagnostico ficou observavel sem abrir console.
- Base pronta para producao sem quebrar arquitetura atual do ERP.

## Arquivos alterados
- `app/Controllers/Whaticket.php`
- `app/Config/Routes.php`
- `app/Views/whaticket/index.php`
- `public/assets/js/whaticket.js`
- `documentacao/05-api/rotas.md`
- `documentacao/06-modulos-do-sistema/whatsapp.md`

---

## Complemento tecnico (19/03/2026) - Bloqueio de iframe por CSP/401

### Sintoma
- Console no ERP exibindo:
  - `Framing 'http://127.0.0.1:3001/' violates Content Security Policy: frame-ancestors 'self'`
  - `Failed to load resource: 401`
  - tentativas repetidas de recarregar o iframe.

### Causa
- O servidor remoto do WhaTicket estava acessivel, mas:
  - bloqueava embed externo via `CSP/X-Frame-Options`, ou
  - exigia autenticacao no iframe (`401/403`).
- O frontend continuava tentando abrir/reconectar o iframe mesmo sem condicoes de embed.

### Correcao aplicada

#### Backend
- `Whaticket::status` passou a retornar diagnostico de embed:
  - `status = online | auth_required | iframe_blocked`
  - `data.embeddable`
  - `data.auth_required`
  - `data.embed_block_reason`
  - `data.likely_api`
  - `data.configuration_hint`
  - headers relevantes (`content_security_policy`, `x_frame_options`).
- A rotina de probe deixou de parar no primeiro retorno 401/403:
  - executa multiplos probes (`whaticket_url`, `/api/health`, `/`) e escolhe o melhor candidato para diagnostico.
  - reduz falso positivo de `auth_required` quando existe endpoint web acessivel em outra rota.
- Quando a URL configurada aponta para API/gateway (nao frontend embutivel), o backend devolve `configuration_hint` explicito para evitar diagnostico generico.

#### Frontend
- `public/assets/js/whaticket.js` agora:
  - valida `embeddable` antes de setar `iframe.src`;
  - interrompe reconexao automatica quando status e `auth_required`/`iframe_blocked`;
  - mostra mensagem guiada para abrir em `Nova aba` ou ajustar politica no servidor remoto.
- `app/Views/whaticket/index.php`:
  - remove `src` inicial do `iframe` para evitar request prematuro;
  - `iframe` passa a ser carregado somente apos status positivo.

### Resultado
- Tela `/whaticket` deixa de entrar em loop de erro por iframe bloqueado.
- Usuario recebe causa tecnica clara e acao operacional imediata.

---

## Complemento tecnico (19/03/2026) - Aba dedicada WhaTicket em Configuracoes

### Problema operacional
- Para configurar `/whaticket`, o usuario precisava mexer no fluxo da aba Integracoes, com risco de alterar `Canal Direto` e impactar o WhatsApp que ja estava em operacao.

### Correcao aplicada
- Criada aba separada `Configuracoes -> WhaTicket` com campos proprios:
  - `whatsapp_whaticket_url`
  - `whatsapp_whaticket_token`
  - `whatsapp_whaticket_origin`
  - `whatsapp_whaticket_timeout`
- Removida a secao `config-whaticket` do bloco condicionado ao `Canal Direto`.
- Mantido botao `Gerenciar` do gateway nessa aba dedicada.

### Resultado
- Configuracao do embed WhaTicket ficou isolada.
- O provider da Central de Mensagens nao precisa ser trocado para ajustar `/whaticket`.

---

## Complemento tecnico (19/03/2026) - Start local do WhaTicket sem impacto no gateway ativo

### Problema operacional
- Quando o frontend WhaTicket local (`127.0.0.1:3000`) estava offline, a tela `/whaticket` ficava indisponivel.
- O operador precisava iniciar manualmente por terminal, com risco de mexer no processo errado e impactar o canal WhatsApp em producao (`:3001`).

### Correcao aplicada

#### Backend
- Novo endpoint:
  - `POST /configuracoes/whatsapp/whaticket-local-start` (`Configuracoes::whatsappWhaticketLocalStart`)
- Fluxo do endpoint:
  - valida URL, caminho local e comando configurado
  - bloqueia caracteres perigosos no comando (`; & | > <` etc.)
  - verifica se o host ja esta no ar (socket probe)
  - inicia processo em background (Windows/Linux) sem encerrar gateway ativo
  - aguarda disponibilidade por alguns segundos e retorna `success` ou `starting`

#### Frontend
- `Configuracoes -> WhaTicket`:
  - campos `whatsapp_whaticket_local_path` e `whatsapp_whaticket_local_start_cmd`
  - botao `Iniciar local` com feedback via SweetAlert2
- `/whaticket`:
  - novo botao `Iniciar local` no header do painel
  - aciona o endpoint dedicado, mostra spinner e revalida status automaticamente

### Resultado
- O operador consegue subir o WhaTicket local diretamente no ERP.
- O processo de mensagens ja em operacao (`api_whats_local/api_whats_linux`) nao e reiniciado automaticamente.

---

## Complemento tecnico (19/03/2026) - Correcao do falso "inicializacao em andamento" (503 persistente)

### Sintoma
- `/whaticket/status` continuava retornando `503` apos clicar em `Iniciar local`.
- O painel mostrava "Inicializacao em andamento", mas o processo nao subia.

### Causa raiz
- O path configurado (`<erp>/whaticket`) nao tinha `package.json`.
- O comando `npm run dev` falhava com `ENOENT`, mas o fluxo ainda retornava estado generico.

### Correcao aplicada
- `Configuracoes::whatsappWhaticketLocalStart`:
  - auto-detecta pasta valida com `package.json` em caminhos comuns (`<erp>/whaticket`, `<erp>/whaticket/frontend`, `<htdocs>/whaticket`, `<htdocs>/whaticket/frontend`);
  - seleciona comando default coerente (`npm start` para frontend React, `npm run dev` para raiz backend);
  - quando socket nao sobe, le `whaticket.boot.err.log` e devolve trecho de erro no JSON.
- `Configuracoes` view:
  - defaults atualizados para `whatsapp_whaticket_url = http://127.0.0.1:3000`;
  - path/cmd default agora seguem deteccao local de projeto.
- `Whaticket::resolveWhaticketContext`:
  - fallback padrao ajustado para `http://127.0.0.1:3000` (frontend), evitando default em porta de gateway.

---

## Complemento tecnico (19/03/2026) - Start local com backend dedicado (`:4003`)

### Sintoma
- O iframe do `/whaticket` carregava o frontend (`:3000`), mas o painel interno mostrava erros de rede:
  - `ERR_CONNECTION_REFUSED` para `:4003`
  - falhas em `/whatsapp/?session=0`, `/settings/viewregister`
- Resultado: tela de login parcial e modulo sem operacao real.

### Causa raiz
- O backend do WhaTicket nao estava rodando em paralelo ao frontend.
- O fluxo de start local retornava sucesso sem sinalizar de forma clara quando somente o frontend subia.

### Correcao aplicada
- `Configuracoes::whatsappWhaticketLocalStart` agora:
  - verifica e sobe backend antes/depois do frontend quando `whatsapp_whaticket_api_url` estiver offline;
  - usa auto-detect de backend em `<erp>/whaticket/backend` e `<htdocs>/whaticket/backend`;
  - retorna status parcial `partial_backend_offline` quando frontend online e backend ainda indisponivel;
  - inclui diagnostico detalhado em `data.backend` (mensagem, path, comando, `stderr_tail`).
- `public/assets/js/whaticket.js`:
  - interpreta `partial_backend_offline`;
  - mostra SweetAlert de aviso com contexto do backend.
- `Configuracoes -> WhaTicket`:
  - adicionados campos `whatsapp_whaticket_api_url`, `whatsapp_whaticket_backend_local_path`, `whatsapp_whaticket_backend_local_start_cmd`.

### Resultado
- O operador inicia WhaTicket local (frontend + backend) pelo ERP sem impactar o canal WhatsApp principal.
- Quando backend nao sobe, o erro deixa de ser silencioso e vira diagnostico acionavel no proprio painel.

---

## Complemento tecnico (19/03/2026) - Reducao de warnings no frontend React do WhaTicket

### Sintoma
- Console do iframe exibia warnings recorrentes:
  - `Grid prop "justify" deprecated`
  - `Can't perform a React state update on an unmounted component`
  - erro de registro de service worker em dev (`unsupported MIME type 'text/html'`).

### Correcao aplicada no frontend WhaTicket local
- `frontend/src/pages/Login/index.js`
  - `justify` substituido por `justifyContent`.
- `frontend/src/components/UserModal/index.js`
  - adicionado guard de montagem em `useEffect` para evitar `setState` apos unmount.
- `frontend/src/pages/Chat/ChatPopover.js`
  - adicionado `mountedRef` + cleanup para bloquear updates apos desmontagem e evitar warning de memory leak.
- `frontend/src/serviceWorker.js`
  - registro de service worker limitado ao ambiente `production`.

### Resultado
- Console mais limpo durante operacao local.
- Menos ruido de debug para o operador.

---

## Complemento tecnico (20/03/2026) - Estabilizacao CORS/login entre frontend `:3000` e backend `:4003`

### Sintoma
- No iframe do `/whaticket`, o login exibia erros recorrentes:
  - `Access to XMLHttpRequest ... has been blocked by CORS policy`
  - `ERR_FAILED` para rotas como `/auth/login`, `/version`, `/settings/viewregister`, `/whatsapp/?session=0`
- Em alguns cenarios, respostas `500` mascaravam o problema de integracao.

### Causa raiz
- Ambiente local com hosts mistos (`127.0.0.1` x `localhost`) gerava bloqueios intermitentes em chamadas cross-origin.
- O backend podia cair em erro de logger durante tratamento de excecao, degradando respostas de API.
- Configuracao local do frontend apontava para backend em host diferente do host principal do iframe.

### Correcao aplicada

---

## Complemento tecnico (20/03/2026) - Fim do loop de `503` no `/whaticket/status`

### Sintoma
- Console do navegador acumulava erros repetidos:
  - `GET /whaticket/status 503 (Service Unavailable)`
- Mesmo com tela aberta por longos periodos, o endpoint era consultado continuamente e gerava ruido operacional.

### Causa raiz
- O endpoint `Whaticket::status` retornava HTTP `503` para estados esperados de operacao local:
  - frontend offline
  - backend API (`:4003`) offline
- O frontend fazia polling fixo e constante, mantendo repeticao de erros no console durante indisponibilidade.

### Correcao aplicada

#### Backend (`app/Controllers/Whaticket.php`)
- `GET /whaticket/status` passou a responder HTTP `200` para estados operacionais degradados, mantendo:
  - `ok: false`
  - `status: offline|backend_offline|unhealthy`
  - `message` com diagnostico
- adicionados headers anti-cache (`Cache-Control: no-store`, `Pragma: no-cache`) para evitar reaproveitamento de estado antigo.
- Incluido em `data`:
  - `http_status` (codigo tecnico original: `503`/`502`)
  - `poll_recommended_ms` (sugestao de intervalo de nova checagem)

#### Frontend (`public/assets/js/whaticket.js`)
- Polling alterado de intervalo fixo para loop com backoff adaptativo:
  - usa `poll_recommended_ms` quando disponivel;
  - aumenta intervalo quando indisponivel (ate limite maximo);
  - volta para o intervalo base quando normaliza.
- Resultado: reduz carga e elimina flood de erros de rede no console.

### Resultado operacional
- Tela `/whaticket` continua informando indisponibilidade corretamente.
- Console deixa de ser poluido por centenas de erros `503`.
- Diagnostico tecnico permanece disponivel no payload JSON para suporte.

#### Whaticket backend (`C:\\xampp\\htdocs\\whaticket\\backend`)
- `src/app.ts`:
  - CORS em desenvolvimento (`NODE_ENV != production`) agora aceita origem dinamica para evitar bloqueio local por host misto.
  - Mantida validacao restritiva para producao (origens explicitamente permitidas).
  - adicionado `safeLog(...)` para evitar quebra do fluxo quando o logger principal falhar.
  - middleware de erro passou a usar log defensivo (sem derrubar retorno HTTP).
- `.env`:
  - `NODE_ENV=development`
  - `BACKEND_URL=http://127.0.0.1:4003`
  - `FRONTEND_URL=http://127.0.0.1:3000,http://localhost:3000`
  - `ERP_ORIGIN=http://localhost:8084,http://127.0.0.1:8084,http://localhost:8081,http://127.0.0.1:8081`

#### Whaticket frontend (`C:\\xampp\\htdocs\\whaticket\\frontend`)
- `.env`:
  - `REACT_APP_BACKEND_URL=http://127.0.0.1:4003`

### Validacao tecnica executada
- Preflight CORS (`OPTIONS`) validado com sucesso para:
  - `/auth/login`
  - `/version`
  - `/settings/viewregister`
  - `/companies/listPlan/undefined`
  - `/whatsapp/?session=0`
- Confirmado `Access-Control-Allow-Origin: http://127.0.0.1:3000` nas respostas.

### Resultado
- Login do WhaTicket deixa de falhar por CORS no ambiente local.
- Fluxo frontend (`:3000`) -> backend (`:4003`) fica estavel para operacao diaria.

---

## Complemento tecnico (20/03/2026) - Correcao de bloqueio indevido no login por `dueDate` nula/invalida

### Sintoma
- Ao logar no Whaticket, aparecia:
  - `Opss! Sua assinatura venceu Data invalida.`
- Mesmo com empresa ativa, o frontend bloqueava o acesso quando `company.dueDate` vinha nula.

### Causa raiz
- Regra antiga no frontend (`useAuth`) assumia que `dueDate` sempre existia e era valida.
- Com `dueDate = NULL`, o `moment(...).format()` gerava "Invalid date" e a condicao de vencimento bloqueava indevidamente o login.

### Correcao aplicada
- Arquivo ajustado no projeto Whaticket local:
  - `C:\\xampp\\htdocs\\whaticket\\frontend\\src\\hooks\\useAuth.js\\index.js`
- Nova regra:
  - se `dueDate` nao existir ou for invalida, **nao bloquear login**;
  - so aplicar bloqueio/aviso de assinatura quando `dueDate` for valida;
  - normalizado formato de data para `DD/MM/YYYY`.

### Resultado
- Login volta a funcionar para empresas com `dueDate` nula/invalida.
- Bloqueio de assinatura continua ativo quando houver vencimento real.

---

## Complemento tecnico (20/03/2026) - Correcao dos erros `500` no Whaticket (quick messages / kanban / dashboard)

### Sintoma
- No frontend Whaticket (`:3000`), chamadas autenticadas retornando `500`:
  - `GET /quick-messages/list?companyId=1&userId=1`
  - `GET /tags/kanban`
  - `GET /dashboard?...`
- Console exibindo `backendErrors.Internal server error`.

### Causa raiz
- Banco local estava com schema legado (sem colunas mais novas usadas por modelos/servicos):
  - `quickmessages.geral`
  - `tags.kanban`
  - `prompts.queueId` (e campos relacionados)
  - `queueintegrations.jsonContent` (e campos relacionados)
- O servico de dashboard usava `JSON_ARRAYAGG`, indisponivel no MySQL local.

### Correcao aplicada (backend Whaticket)
- Novo helper:
  - `C:\\xampp\\htdocs\\whaticket\\backend\\src\\helpers\\TableSchema.ts`
  - cache de metadata e detecao de colunas existentes por tabela.
- Servicos ajustados para **fallback de schema** com atributos dinamicos:
  - `QuickMessageService/FindService.ts`
  - `QuickMessageService/ListService.ts`
  - `QuickMessageService/ShowService.ts`
  - `QuickMessageService/CreateService.ts`
  - `QuickMessageService/UpdateService.ts`
  - `QuickMessageService/DeleteService.ts`
  - `TagServices/KanbanListService.ts`
  - `TagServices/CreateService.ts`
  - `TagServices/UpdateService.ts`
  - `TagServices/SimpleListService.ts`
  - `TagServices/ShowService.ts`
  - `PromptServices/ListPromptsService.ts`
  - `QueueIntegrationServices/ListQueueIntegrationService.ts`
  - `WhatsappService/ShowWhatsAppService.ts`
- Dashboard refeito para compatibilidade SQL:
  - `ReportService/DashbardDataService.ts`
  - removido uso de `JSON_OBJECT/JSON_ARRAYAGG`;
  - counters e attendants agora montados via queries separadas + agregacao em Node.js.

### Validacao tecnica executada
- Testes diretos por servico (via `ts-node`) concluindo sem erro:
  - `FindService` (quick messages) -> OK
  - `KanbanListService` -> OK
  - `DashboardDataService` -> OK
  - `ListPromptsService` -> OK
  - `ListQueueIntegrationService` -> OK
  - `ShowWhatsAppService` -> OK

### Resultado
- Erros `500` principais deixam de ocorrer mesmo com schema legado.
- Tela do Whaticket e modulos internos ficam mais resilientes para ambiente local misto.
- Base pronta para evolucao sem bloquear operacao atual do ERP.

---

## Complemento tecnico (20/03/2026) - Correcao de ruido `401`/`ERR_SESSION_EXPIRED` no frontend Whaticket

### Sintoma
- Console e UI exibindo repetidamente:
  - `GET /whatsapp/?session=0 401 (Unauthorized)`
  - `Sessao expirada. Por favor entre.`
- Warnings de React por update em componente desmontado (`UserModal`, `ChatPopover`).

### Causa raiz
- `useAuth` registrava interceptors fora de `useEffect`, acumulando handlers duplicados.
- `useWhatsApps` chamava `/whatsapp/?session=0` mesmo sem token valido e mostrava toast em 401.
- Hooks/containers encerravam o socket com `disconnect()` no unmount, afetando listeners compartilhados e gerando corrida de estado.

### Correcao aplicada (frontend Whaticket)
- `frontend/src/hooks/useAuth.js/index.js`
  - interceptors movidos para `useEffect` com `eject` no cleanup;
  - 401/403 no bootstrap de sessao tratados sem toast de erro ruidoso;
  - limpeza consistente de `token/companyId` quando sessao expira;
  - guard de montagem (`mountedRef`) para evitar `setState` apos unmount.
- `frontend/src/hooks/useWhatsApps/index.js`
  - nao consulta `/whatsapp/?session=0` sem token;
  - 401/403 tratados como estado de sessao (sem `toastError`);
  - listeners com `socket.off(...)` em vez de `socket.disconnect()`;
  - guard de montagem para evitar updates apos unmount.
- `frontend/src/pages/Chat/ChatPopover.js`
  - listeners com `socket.off(...)` no cleanup;
  - guard de montagem antes de `dispatch`.

### Resultado
- `401` passa a ser tratado como estado normal de login (nao erro operacional).
- Reducao do spam de toasts e warnings de memory leak no console.
- Fluxo de login no iframe fica mais previsivel e estavel.
