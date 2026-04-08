# Modulo: Central de Mensagens (Central de Atendimento Inteligente 24h)

Atualizado em 08/04/2026 (integracao Orcamentos fase 3).

## Objetivo
Transformar o WhatsApp operacional em uma central unica de atendimento integrada ao ERP + CRM, com:
- inbox WhatsApp OS
- contexto de cliente/equipamento/OS
- automacao de resposta por intencao (chatbot)
- fila de atendimento humano
- metricas operacionais de atendimento

## Integracao com Orcamentos (08/04/2026 - fase 3)

A Central passou a suportar orcamento rapido dentro da conversa ativa, sem sair da tela:

- novo botao `Gerar e enviar orcamento` no contexto da conversa;
- fluxo em modal (descricao, valor, validade, mensagem e opcao de anexar PDF);
- criacao do orcamento vinculada a `conversa_id` e tentativa de `cliente_id/os_id` quando disponiveis;
- envio imediato por WhatsApp usando a trilha do modulo de orcamentos;
- secao `Orcamentos relacionados` no painel de contexto para acompanhamento rapido.

Arquivos tecnicos impactados:

- `app/Controllers/CentralMensagens.php`
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`

## Extensao mobile paralela (03/04/2026 - v2.11.0)

A Central web continua sendo o nucleo operacional principal. A release `2.11.0` adiciona um modulo paralelo mobile/PWA que reutiliza o mesmo backend e banco:

- API interna versionada em `/api/v1` para auth, conversas, mensagens, OS e notificacoes;
- frontend separado em `mobile-app/` (Next.js), sem substituir a tela web atual;
- persistencia complementar de notificacoes/push em tabelas `mobile_*`, sem duplicar `conversas_whatsapp` e `mensagens_whatsapp`.

Documento tecnico dedicado:

- `documentacao/06-modulos-do-sistema/central-mobile-pwa.md`

## Modo Bot/Humano movido para o menu hamburguer (02/04/2026 - v2.10.17)

Ajuste de layout operacional no cabecalho da thread para reduzir ruido visual mantendo a acao de modo de atendimento no menu de contexto da conversa.

O que mudou:

- os botoes `Bot ativo/Bot desativado` e `Aguardando atendimento humano` sairam do cabecalho visivel;
- os dois controles passaram a ficar dentro do menu hamburguer (`Mais acoes da conversa`);
- `Status` e `Prioridade` continuam explicitos no cabecalho da coluna central;
- o botao `Ocultar/Mostrar contexto` permanece ao lado do menu hamburguer.

Arquivos tecnicos impactados:

- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`
- `public/assets/css/design-system/layouts/central-mensagens.css`

## Header operacional explicito + modo binario Bot/Humano (02/04/2026 - v2.10.16)

Ajuste de usabilidade no cabecalho da thread para recuperar leitura imediata dos estados criticos e remover acoes sem efeito.

O que mudou:

- `Status` voltou a ficar visivel no cabecalho (fora do menu) e continua clicavel para abrir modal de alteracao;
- `Prioridade` voltou a ficar explicita no cabecalho (fora do menu), com leitura direta do estado atual;
- modo de atendimento ficou binario e inverso no topo:
  - `Bot ativo` (chip verde) quando automacao esta ligada;
  - `Bot desativado` (chip vermelho) quando automacao esta desligada;
  - `Aguardando atendimento humano` passa a refletir o estado oposto do bot;
- o botao `Ocultar/Mostrar contexto` retornou ao cabecalho, posicionado ao lado do menu hamburguer;
- o item `Acoes avancadas` foi removido do dropdown por nao possuir funcionalidade efetiva;
- o menu hamburguer ficou focado em acoes operacionais e de fila (`Assumir`, `Atribuir`, `Encerrar`, `Nova conversa`, `Sincronizar inbound`, `Atualizar conversa`);
- os controles do cabecalho foram reforcados para permanecer lado a lado em linha unica, com overflow horizontal quando necessario.

Arquivos tecnicos impactados:

- `app/Views/central_mensagens/index.php`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`

## Menu hamburguer de acoes no cabecalho da thread (02/04/2026 - v2.10.15)

Refino estrutural aplicado para impedir que os controles da conversa fiquem ocultos ou avancem sobre a area de mensagens.

O que mudou:

- a barra de acoes horizontal foi removida da area abaixo do cabecalho da thread;
- as acoes foram consolidadas em menu hamburguer no `cm-thread-header-top` da coluna central;
- o menu passou a conter:
  - status da conversa;
  - modo de atendimento (`Sem nenhum ativado`, `Bot ativo`, `Aguardando atendimento humano`);
  - assumir, atribuir, prioridade e encerrar;
  - nova conversa, sincronizar inbound, atualizar conversa;
  - acoes avancadas (incluindo `Ocultar contexto`).

Impacto de UX:

- elimina sobreposicao de botoes sobre o chat;
- libera espaco vertical da thread;
- preserva todas as operacoes em um ponto unico de acesso no cabecalho.

Arquivos tecnicos impactados:

- `app/Views/central_mensagens/index.php`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`

## Hotfix tooltip/dropdown + resiliencia de envio (02/04/2026 - v2.10.14)

Correcao aplicada para eliminar warnings repetidos do Bootstrap na action bar e reduzir falha de envio por timeout de provider.

O que mudou:

- inicializacao de tooltip na Central agora ignora elementos com `data-bs-toggle` diferente de `tooltip` (ex.: `dropdown`);
- botoes de dropdown da action bar (`modo de atendimento` e `mais acoes`) nao recebem instancia Bootstrap Tooltip;
- timeout padrao do gateway local em `MensageriaService` foi ampliado:
  - `whatsapp_local_node_timeout`: `30s` (default);
  - `whatsapp_linux_node_timeout`: `30s` (default).

Impacto esperado:

- elimina erro de console `Bootstrap doesn't allow more than one instance per element`;
- reduz incidencia de `503 Service Unavailable` quando o envio falha apenas por latencia/timing do gateway.

Arquivos tecnicos impactados:

- `public/assets/js/central-mensagens.js`
- `app/Services/MensageriaService.php`
- `app/Services/WhatsApp/LocalGatewayProvider.php`

## Modo unico de atendimento + acesso avancado de contexto (02/04/2026 - v2.10.13)

Refino da action bar para reduzir ambiguidade operacional e prevenir cliques acidentais em acoes sensiveis.

O que mudou:

- os controles `Bot ativo` e `Aguardando humano` foram unificados em um dropdown unico de modo:
  - `Sem nenhum ativado`
  - `Bot ativo`
  - `Aguardando atendimento humano`
- o menu `+` passou a agrupar `Ocultar contexto` dentro de `Acoes avancadas` (com clique adicional), diminuindo acesso imediato;
- a action bar foi fixada em linha unica em todos os breakpoints, com overflow horizontal quando necessario.

Arquivos tecnicos impactados:

- `app/Views/central_mensagens/index.php`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`

## Action bar SaaS premium em 3 grupos (02/04/2026 - v2.10.12)

Reformulacao completa da barra de acoes no topo da conversa para padrao SaaS moderno (hierarquia visual, clareza operacional e responsividade agressiva).

Estrutura aplicada:

- esquerda (`chat-actions-left`): `Status`, `Bot ativo`, `Aguardando humano`;
- centro (`chat-actions-center`): `Assumir`, `Atribuir`, `Prioridade`;
- direita (`chat-actions-right`): `Encerrar` + menu `+` para acoes extras.

Padrao visual:

- componentes padronizados:
  - `StatusBadge`
  - `ToggleChip`
  - `ActionButton`
- barra com altura operacional estavel (`56px` a `64px`);
- fundo claro (`#f9fafb`) e borda suave (`#e5e7eb`);
- hover com elevacao leve, estados `active/disabled` e tooltips em toda a action bar.

Mobile (`<= 767.98px`):

- texto ocultado nos controles da action bar (somente icones);
- grupo central com scroll horizontal;
- grupo direito permanece fixo, mantendo `Encerrar` sempre visivel.

Arquivos tecnicos impactados:

- `app/Views/central_mensagens/index.php`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`

## Timeout resiliente entre polling e envio (02/04/2026 - v2.10.11)

Correcao aplicada para eliminar contencao entre envio de mensagem e polling incremental da mesma conversa, reduzindo erro recorrente de timeout em cascata.

O que mudou:

- `conversas`, `conversa/{id}` e `conversa/{id}/novas` deixaram de executar processamento auxiliar de fila no caminho critico;
- `enviar` passou a liberar lock de sessao antes de chamar provider, evitando serializacao indesejada entre requests concorrentes do mesmo operador;
- timeout base de request no frontend subiu para `30s`;
- timeout do envio passou a ser dinamico (`max(25s, timeout global)`), reduzindo falso negativo em rede lenta/gateway sob carga.

Arquivos tecnicos impactados:

- `app/Controllers/CentralMensagens.php`
- `public/assets/js/central-mensagens.js`

## Polling incremental resiliente sem timeout em cascata (02/04/2026 - v2.10.10)

Correcao de performance aplicada para eliminar repeticao de erro `Tempo limite excedido (20s)` durante polling da thread.

O que mudou:

- endpoints de polling rapido deixaram de executar sync pesado de historico do gateway em toda requisicao;
- foi criada separacao entre:
  - processamento local da fila inbound (rapido),
  - sincronizacao de historico no gateway (pesada, sob demanda/rotina dedicada);
- os endpoints AJAX principais da Central passaram a liberar lock de sessao antes do processamento, evitando bloqueio entre requests simultaneas do mesmo operador;
- o frontend passou a:
  - reduzir frequencia de refresh da lista enquanto uma conversa esta aberta;
  - aplicar backoff progressivo quando houver falhas repetidas de rede;
  - reduzir spam de logs de erro no console.

Arquivos tecnicos impactados:

- `app/Controllers/CentralMensagens.php`
- `app/Services/CentralMensagensService.php`
- `public/assets/js/central-mensagens.js`

## Inbound multimidia com hidratacao de anexos (02/04/2026 - v2.10.9)

Correcao fullstack para mensagens inbound de audio/video/imagem que estavam chegando como `[mensagem sem texto]`.

O que mudou:

- o gateway Node passou a baixar e incluir midia tambem na sincronizacao de historico (`/sync-chat-history`), nao apenas no evento realtime;
- o payload de historico agora pode carregar `media_base64`, `media_mime_type` e `media_filename`;
- o parser da Central passou a classificar `ptt`/`voice` como `audio`;
- quando uma mensagem ja existe por `provider_message_id`, o backend agora atualiza o registro com o anexo (arquivo + mime + tipo) assim que um payload posterior trouxer a midia;
- no frontend, mensagens de midia sem arquivo local imediato exibem estado de sincronizacao de anexo, evitando ambiguidade visual.

Arquivos tecnicos impactados:

- `whatsapp-api/server.js`
- `app/Services/CentralMensagensService.php`
- `public/assets/js/central-mensagens.js`

## Controles operacionais por modal + sync sem flicker (02/04/2026 - v2.10.8)

Refino funcional aplicado no cabecalho da thread para reduzir atrito operacional e eliminar efeito visual de "sumir/reaparecer" durante sincronizacao automatica.

O que mudou:

- badge de `Status` no topo da conversa agora e clicavel e abre modal com opcoes de status;
- botao `Atribuir` agora abre modal de atribuicao de responsavel (sem deslocar para o painel lateral);
- botao `Encerrar` passou a decidir entre:
  - `Concluir` (status `resolvida`);
  - `Arquivar` (status `arquivada`);
- filtro rapido `Arquivadas` foi adicionado na fila de atendimento;
- novos atalhos no cabecalho:
  - `Prioridade` (baixa/normal/alta/urgente);
  - `Bot ativo`;
  - `Aguard. humano`;
- sync inbound em background agora:
  - nao reabre a thread ativa automaticamente;
  - nao força refresh da fila quando `count = 0`, reduzindo flicker visual;
- backend passa conversa de `resolvida` para `aberta` automaticamente quando chega novo inbound.

Arquivos tecnicos impactados:

- `app/Views/central_mensagens/index.php`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`
- `app/Services/CentralMensagensService.php`

## Composer com altura compacta forcada e alinhada ao envio (02/04/2026 - v2.10.6)

Correcao de ergonomia reforcada para impedir que o `textarea` do composer continue "alto demais" mesmo apos restauracao de rascunho, troca de conversa ou recarga parcial da tela.

O que mudou:

- a altura compacta do `textarea` passou a ser aplicada com prioridade alta no CSS, mantendo o campo visualmente alinhado ao botao de envio;
- o estado vazio agora regride de forma agressiva para a altura base, limpando sobras de `height` inline deixadas por ciclos anteriores;
- a tela reaplica o resize do composer apos abrir conversa e apos o bootstrap inicial, reduzindo risco de o campo permanecer "preso" em altura antiga;
- a autoexpansao segue ativa, mas so cresce quando o conteudo realmente exige multiplas linhas.

Arquivos tecnicos impactados:

- `app/Views/central_mensagens/index.php`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`

## Sincronizacao inbound silenciosa e nao intrusiva (02/04/2026 - v2.10.7)

Refino de usabilidade aplicado para impedir que a sincronizacao automatica interrompa visualmente a leitura e a digitacao no chat.

O que mudou:

- o `auto sync` em background deixou de acionar a faixa visual azul dentro da thread;
- a barra `cm-connection-strip` passa a ficar oculta no estado online normal e aparece apenas em sincronizacao manual ou quando houver alerta/offline real;
- o feedback da sincronizacao automatica fica restrito ao badge de inbound no topo, sem ocupar espaco na area principal da conversa;
- a sincronizacao manual continua com feedback explicito, preservando transparencia operacional quando o operador aciona a rotina por conta propria.

Arquivos tecnicos impactados:

- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`

## Ordenacao cronologica por movimentacao real (02/04/2026 - v2.10.4)

Correcao estrutural aplicada para impedir reordenacao aleatoria na fila quando o sincronismo inbound reencontra mensagens historicas.

O que mudou:

- endpoint `GET /atendimento-whatsapp/conversas` passou a calcular `ultima_movimentacao_em` pela mensagem mais recente da conversa (`mensagens_whatsapp`), usando:
  - `recebida_em`
  - `enviada_em`
  - `created_at`
- ordenacao backend agora usa `COALESCE(ultima_movimentacao_em, conversas_whatsapp.ultima_mensagem_em, updated_at, created_at) DESC`, com desempate deterministico por `ultima_mensagem_id` e `conversa_id`;
- a resposta do endpoint normaliza `ultima_mensagem_em` para refletir a movimentacao real quando `ultima_movimentacao_em` estiver disponivel;
- frontend passou a priorizar `ultima_movimentacao_em` para assinatura da lista, exibicao de horario e ordenacao local;
- `CentralMensagensService` deixou de atualizar `conversas_whatsapp.ultima_mensagem_em` com `now()` em reconciliacoes de mensagens duplicadas/historicas, evitando "empate artificial" de recencia.

Arquivos tecnicos impactados:

- `app/Controllers/CentralMensagens.php`
- `app/Services/CentralMensagensService.php`
- `public/assets/js/central-mensagens.js`

## Filtros recolhidos + fila cronologica estavel + composer compacto (01/04/2026 - v2.10.3)

Ajustes de produtividade aplicados na coluna de fila e no composer da thread:

- filtros avancados da fila agora iniciam recolhidos por padrao e ficam acessiveis por um botao dedicado (`Filtros avancados`);
- permanecem visiveis continuamente apenas os filtros rapidos:
  - `Todas`
  - `Nao lidas`
  - `Abertas`
  - `Com OS`
- ordenacao da fila reforcada para comportamento cronologico estavel pela ultima interacao (envio/recebimento), removendo efeitos de "reembaralhar" entre ciclos de sincronizacao quando nao houve mudanca real de recencia;
- campo de digitacao do composer reduzido para altura mais compacta e proporcional ao botao de envio, mantendo autoexpansao apenas quando necessario.

Arquivos tecnicos impactados:
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`
- `public/assets/css/design-system/layouts/central-mensagens.css`

## Ajuste de ergonomia e rolagem (01/04/2026 - v2.10.2)

Melhorias aplicadas para operacao continua da tela `/atendimento-whatsapp`:

- sidebar global agora entra recolhida automaticamente ao abrir a Central (modo foco de atendimento), seguindo o mesmo comportamento de auto-expandir ao hover no desktop;
- colunas de Conversas, Thread e Contexto passam a manter barras de rolagem dedicadas e visiveis, com trilho/polegar customizados para facilitar navegacao em listas longas;
- estrutura dos paines laterais (`offcanvas-body`) foi reforcada com altura/flex completos para evitar perda de scroll em breakpoints intermediarios.

Arquivos tecnicos impactados:
- `app/Views/central_mensagens/index.php`
- `public/assets/css/design-system/layouts/central-mensagens.css`

Rota principal: `/atendimento-whatsapp`
Alias de compatibilidade: `/central-mensagens`

## Refatoracao visual premium (20/03/2026)

A tela principal da Central foi elevada para padrao SaaS de operacao (WhatsApp Web + Intercom style), mantendo o mesmo backend e rotas.

Principais evolucoes aplicadas em `/atendimento-whatsapp`:
- header da tela com indicador de estado de tempo real (`Tempo real`, `Polling`, `Instavel`)
- cards de conversa com hierarquia forte (avatar, nome, preview, badges operacionais, nao lidas, responsavel)
- ordenacao da inbox com prioridade para nao lidas e, dentro disso, recencia
- barra de filtros com feedback explicito de filtros ativos e botao `Limpar`
- cabecalho do chat com acoes de operador:
  - atualizar conversa
  - assumir conversa (atribui para usuario logado)
  - atribuir (modal de opcoes de responsavel)
  - encerrar (escolha entre `resolvida` e `arquivada`)
- bolhas de mensagem com:
  - separador por data (`Hoje`, `Ontem`, data completa)
  - status outbound (`Enviada`, `Entregue`, `Lida`, `Falha`)
  - animacao suave para mensagens novas
- composer modernizado:
  - menu de anexo
  - menu rapido de emojis
  - foco em envio rapido por teclado (`Enter` envia, `Shift+Enter` quebra linha)
- contexto lateral reorganizado por secoes:
  - contato (agenda)
  - cliente ERP
  - gestao da conversa
  - vinculo de OS
  - follow-ups
- estados visuais elegantes para:
  - carregando
  - sem dados
  - erro

Arquivos tecnicos impactados:
- `app/Controllers/CentralMensagens.php`
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`

## Estabilizacao de runtime (fase atual)

Padrao aplicado para reduzir erros de sessao/rede e evitar loops:
- runtime unico no frontend: a pagina renderiza somente `window.CM_CFG` + `public/assets/js/central-mensagens.js`
- bloco legado inline mantido apenas como referencia interna e fora da resposta HTML (`if (false)`)
- wrappers de request (`getJson` e `postForm`) com tratamento explicito para:
  - 401/403: encerra polling/stream, informa expiracao de sessao e redireciona para `/login`
  - 502/503/504: mensagem operacional de indisponibilidade de backend/gateway
  - resposta HTML inesperada: bloqueio com mensagem tecnica amigavel
  - falha de rede/CORS e timeout: erro padronizado para diagnostico rapido
- encerramento defensivo de ciclo assinc:
  - `beforeunload` e `pagehide` finalizam polling + stream
  - quando a aba fica oculta, o stream SSE e fechado e reaberto no retorno
- throttle de log no polling incremental para evitar flood no console quando houver indisponibilidade prolongada

## Deduplicacao visual de mensagens outbound (31/03/2026)

Correcao aplicada na thread da Central para evitar bolhas duplicadas quando a mesma mensagem retorna por mais de um canal de atualizacao em tempo real.

Situacao tratada:
- uma mensagem outbound podia chegar primeiro pelo stream SSE e depois novamente pelo polling incremental;
- como o frontend apenas concatenava `novas` em `state.mensagens`, a mesma mensagem podia aparecer duas vezes na UI mesmo existindo uma unica linha em `mensagens_whatsapp`.

Protecao implementada em `public/assets/js/central-mensagens.js`:
- as mensagens agora sao mescladas por identidade estavel antes de renderizar;
- quando houver `id`, ele passa a ser a chave primaria da deduplicacao;
- quando ainda nao houver `id`, o frontend usa uma fingerprint defensiva com direcao, provider, `provider_message_id`, timestamps, tipo, anexo e texto;
- ao receber uma mensagem repetida, a UI atualiza o registro existente em vez de empilhar outro.

Pontos cobertos:
- abertura inicial da conversa;
- append incremental via polling (`/conversa/{id}/novas`);
- append incremental via SSE (`/conversa/{id}/stream`).

## Hardening premium de UX e operacao (01/04/2026)

Evolucoes aplicadas na interface principal para aproximar a experiencia de mensageria enterprise:

- carregamento com skeletons reais na lista de conversas e na thread ativa (sem blocos "vazios" durante fetch);
- navegacao por teclado na inbox:
  - `Enter`/`Space` abre a conversa focada;
  - `ArrowUp`/`ArrowDown` troca o foco entre conversas;
  - `Ctrl+K` (ou `Cmd+K`) foca a busca da fila;
  - `/` tambem foca busca quando o operador nao esta digitando em campo de formulario;
  - `Esc` fecha menus flutuantes de anexo/emoji;
- reforco de acessibilidade:
  - cada item da inbox agora tem `role="button"`, `tabindex="0"` e `aria-selected`;
  - estado de foco visivel dedicado para uso intensivo por teclado;
- indicador de tempo real com contexto temporal:
  - badge passa a exibir o estado (`Tempo real`, `Polling`, `Instavel`) + horario da ultima atualizacao visual;
- hardening anti-duplo-envio no composer:
  - envio concorrente bloqueado por lock de runtime (`state.sendingMessage`);
  - evita duplicacao por clique repetido ou enter sequencial durante request em andamento;
- resiliencia extra no backend para outbound:
  - antes de inserir outbound novo, o servico tenta reconciliar duplicata recente por fingerprint operacional (telefone, direcao, conversa, conteudo/tipo e janela curta de tempo);
  - se encontrar equivalente recente, atualiza payload/status e reutiliza a mesma mensagem ao inves de criar segunda linha.

Arquivos tecnicos impactados nesta rodada:
- `public/assets/js/central-mensagens.js`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `app/Services/CentralMensagensService.php`

## Foco operacional e anti-duplicacao reforcado (01/04/2026 - v2.9.9)

Nova rodada de refinamento premium para uso intensivo em atendimento:

- painel contextual com modo foco no desktop:
  - novo botao `Contexto` no cabecalho da conversa;
  - operador pode recolher/mostrar a coluna da direita sem perder thread ativa;
  - estado persistido em `localStorage` para manter preferencia entre recargas;
- busca da fila com debounce:
  - digitacao em `Buscar cliente, telefone, OS...` passa a filtrar sem precisar `Enter`;
  - reduz requisicoes em excesso e melhora sensacao de fluidez;
- hardening de envio outbound no backend:
  - `WhatsAppService` agora bloqueia envios duplicados recentes (janela curta de 3 segundos) por fingerprint de conversa/telefone/conteudo/anexo/tipo;
  - evita dupla postagem por clique repetido/reenvio acidental antes de novo feedback visual;
- refinamentos de codigo do composer:
  - limpeza de trechos residuais no fluxo de captura de audio/video;
  - organizacao de transicoes visuais da grade ao ocultar/mostrar contexto.

Arquivos tecnicos impactados nesta rodada:
- `app/Views/central_mensagens/index.php`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`
- `app/Services/WhatsAppService.php`

## Produtividade de fila e inbound assistido (01/04/2026 - v2.10.0)

Nova rodada de refinamento operacional para aproximar a experiencia da Central de um mensageiro enterprise em uso continuo:

- barra de filtros rapidos na coluna de conversas:
  - `Todas`
  - `Nao lidas`
  - `Abertas`
  - `Com OS`
  - `Clientes novos`
- os filtros rapidos sao sincronizados com os filtros estruturados (busca/status/checkboxes), mantendo feedback unico em `Filtros ativos`.
- badge dedicada de sincronizacao inbound no topo da tela:
  - `Inbound ocioso`
  - `Sincronizando`
  - `Inbound ok`
  - `Falha inbound`
- sincronizacao inbound agora suporta:
  - modo manual (com feedback completo para operador);
  - modo silencioso (background) para manter inbox e thread atualizadas sem interromper a operacao.
- loop automatico de sincronizacao inbound em segundo plano:
  - reduz janela de atraso para mensagens recebidas fora do fluxo de stream imediato;
  - ao voltar para a aba visivel, dispara sincronizacao silenciosa para convergencia rapida.
- responsividade adicional da barra de filtros rapidos para breakpoints pequenos (`<=575`, `<=430`, `<=360`), sem estourar layout.

Arquivos tecnicos impactados nesta rodada:
- `app/Views/central_mensagens/index.php`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`

## Padronizacao de erro + observabilidade operacional (fase 20/03/2026)

Backend da Central passou a usar contrato unico por status HTTP com `code` tecnico:
- sucesso: `{ ok: true, status, code, ... }`
- erro: `{ ok: false, status, code, message }`

Aplicado em endpoints operacionais:
- `GET /atendimento-whatsapp/conversas`
- `GET /atendimento-whatsapp/conversa/{id}`
- `GET /atendimento-whatsapp/conversa/{id}/novas`
- `GET /atendimento-whatsapp/conversa/{id}/stream`
- `POST /atendimento-whatsapp/enviar`
- `POST /atendimento-whatsapp/vincular-os`
- `POST /atendimento-whatsapp/atualizar-meta`
- `POST /atendimento-whatsapp/sync-inbound`
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`

Observabilidade de falhas por endpoint:
- toda falha relevante gera `code` estavel (ex.: `CM_ENVIO_PROVIDER_FAILED`, `CM_META_UPDATE_FAILED`, `CM_SYNC_INBOUND_ERROR`)
- controller registra contexto tecnico (endpoint, ids, filtros, URI, metodo, IP) em `log_message`
- mesmo contexto e persistido na tabela `logs` para diagnostico operacional rapido sem depender apenas do console do navegador
- falhas de indisponibilidade do provider/gateway no envio agora retornam `CM_ENVIO_PROVIDER_UNAVAILABLE` com HTTP `503`, separando erro operacional de erro de validacao do usuario

## Compatibilidade com schema em migracao

Hotfix aplicado para evitar `500` no endpoint de lista quando o banco ainda nao recebeu a migration de contatos:
- `GET /atendimento-whatsapp/conversas` passou a funcionar mesmo sem tabela `contatos`
- join/filtros/campos de `contatos` sao habilitados apenas quando a tabela existe
- em banco ainda nao migrado, o filtro `clientes_novos` considera `conversas_whatsapp.cliente_id IS NULL`

Endpoint de cadastro rapido:
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`
- quando a tabela `contatos` nao existe, retorna `409` com `code = CM_CONTATOS_SCHEMA_MISSING` (ao inves de erro 500 generico)

## Inbox: ordenacao deterministica e filtro de clientes novos

A lista de conversas foi estabilizada para manter sempre as mais novas no topo, sem variacao visual entre recargas:
- ordenacao backend: `ultima_mensagem_em DESC`, `ultima_mensagem_id DESC`, `conversas_whatsapp.id DESC`
- ordenacao frontend (fallback deterministico): mesmo criterio antes do render

Novo filtro lateral:
- checkbox `Clientes novos` (ao lado de `Com OS aberta`)
- envia `clientes_novos=1`
- backend filtra:
  - `conversas_whatsapp.cliente_id IS NULL`
  - `contatos.cliente_id IS NULL`

## Cadastro rapido de contato novo pela conversa

Novo fluxo no card da conversa:
- quando `cliente_id` nao existe, o card recebe badge `Cliente novo`
- atendente pode usar botao `Salvar contato`
- modal (SweetAlert2) abre com:
  - telefone preenchido
  - nome sugerido pelo `contato_nome`/`contato_perfil_nome`/`nome_contato` (quando nao for telefone)

Endpoint novo:
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`
- cria/atualiza contato (`contatos`) e vincula em `conversas_whatsapp.contato_id`
- aplica etapa relacional no contato:
  - `lead_novo` (sem nome completo confiavel)
  - `lead_qualificado` (nome completo capturado)
  - `cliente_convertido` (quando existe vinculo com `clientes`)
- **nao** cria cliente automaticamente
- cliente so e vinculado quando existir contexto operacional (ex.: abertura de OS)

## Nova OS com contexto da conversa

Na coluna de contexto da conversa, o botao `Nova OS` abre `/os/nova` com query params de origem:
- `origem_conversa_id`
- `origem_contato_id`
- `cliente_id` (quando existir)
- `telefone`
- `nome_hint`

No formulario de OS:
- exibe alerta visual de origem da Central WhatsApp
- persiste ids de origem em hidden fields
- pre-seleciona cliente quando ja houver vinculo
- ao salvar, sincroniza conversa/contato com cliente e vinculo em `conversa_os`

## Automacao para contato novo sem nome de perfil

No `ChatbotService`, para conversa sem cliente e sem nome confiavel:
- bot envia:
  - `bom dia|boa tarde|boa noite, *atendimento automatico* ! tudo bem ?!`
  - linha em branco
  - `por favor diga APENAS seu nome e sobre nome para prosseguirmos o atendimento !`
- quando cliente responde com duas ou mais palavras validas:
  - conversa recebe `nome_contato` atualizado
  - bot confirma:
    - `blza {nome sobrenome} ! me digite ou mande um audio do que podemos lhe ajudar que logo encaminho para o atendimento de um jovem humano !`

## Arquitetura por camadas (produto)

Fluxo conceitual:

`WhatsApp OS -> Motor de Atendimento Automatico -> Integracao ERP -> Camada CRM (eventos/follow-up/timeline) -> Camada de Metricas/Gestao`

Fluxo tecnico:

`UI Central -> CentralMensagensController -> CentralMensagensService/ChatbotService/MetricasMensageriaService -> WhatsAppService -> MensageriaService -> Provider ativo`

Providers suportados:
- `api_whats_local`
- `api_whats_linux`
- `menuia`
- `webhook`

## Estrutura funcional por fases

### Fase 1 - WhatsApp OS (Inbox operacional)
- lista de conversas com filtros
- thread completa inbound/outbound
- envio manual de texto, PDF e anexos (`foto`, `video`, `audio`, `arquivo`)
- exibe anexos por mensagem com renderer especifico por tipo (imagem, audio, video, PDF e arquivo)
- vinculo por cliente/telefone
- vinculo e gestao de OS na conversa
- atualizacao incremental da thread via endpoint de novas mensagens

### Fase 2 - Contexto ERP + CRM
- painel lateral com cliente/OS/documentos/follow-ups
- atualizacao de status/responsavel/tags da conversa
- registro de eventos e interacoes CRM por mensagem
- badges operacionais por conversa (bot ativo/off, aguardando humano, OS vinculada, SLA estourado, prioridade)

### Fase 3 - Atendimento automatico (chatbot)
- deteccao de intencao por regras e palavras-chave
- FAQ configuravel
- resposta automatica para status OS, orcamento, previsao e horario
- fallback para humano com `aguardando_humano = 1`
- logs de decisao do bot
- threshold de confianca configuravel em `/atendimento-whatsapp/configuracoes`

### Fase 4 - Fluxos e automacoes
- regras ERP -> mensagem/follow-up
- fluxos de atendimento configuraveis
- respostas rapidas reutilizaveis
- regras dinamicas em `chatbot_regras_erp` executadas por `CrmService::applyStatusAutomation()`

### Fase 5 - Gestao e metricas
- dashboard de volume
- tempo medio de resposta
- taxa de automacao
- fila aguardando humano
- produtividade por atendente
- consolidacao diaria em `mensageria_metricas_diarias`

## Telas do modulo

<a id="whatsapp-os"></a>
### 1) WhatsApp OS
Rota: `/atendimento-whatsapp`

Tela em 3 colunas:
1. WhatsApp OS (filtros e nao lidas)
2. Thread (mensagens + envio)
3. Contexto (cliente/OS/follow-up/tags)

Layout estrutural aplicado (index):
- painel principal unico: `.central-mensagens-wrapper`
- altura desktop: `calc(100vh - 140px)`
- proporcao das colunas: `28% / 44% / 28%`
- rolagem individual por coluna
- remocao da barra superior de modulos (WhatsApp OS/Chatbot/Metricas/...) na tela principal para ampliar area util do atendimento
- cabecalho da pagina (`.cm-page-header`) com fundo transparente e sem bordas para maxima integracao visual
- barra de filtros horizontal e compacta no topo da listagem de conversas, com alinhamento flexível e responsivo

#### Responsividade profissional (inspirada em WhatsApp Web/Business)

Breakpoints aplicados:
- `>= 992px`: 3 colunas ativas (`lista + chat + contexto`) com distribuicao fixa `28% / 44% / 28%`.
- `768px a 991px`: colunas empilhadas verticalmente (`width: 100%`) para manter leitura e toque confortavel.
- `< 768px`: pilha vertical com cards em largura total e composer otimizado para toque.

Comportamentos de UX:
- rolagem independente por coluna (lista, thread e contexto)
- composer de mensagem fixo no rodape do chat
- botao flutuante "Ir para o fim" na thread para conversas extensas
- gatilhos `WhatsApp OS` e `Contexto` no header do chat aparecem somente em `< 992px` (offcanvas); em desktop as colunas permanecem fixas
- botoes de acao simplificados em telas menores
- prevencao de overflow horizontal e quebra de layout em anexos/midias
- suporte consistente para tema claro/escuro mantendo tokens do Design System
- altura principal central fixa em desktop/notebook: `calc(100vh - 140px)`, com rolagem independente em cada coluna

#### Atualizacao fluida da thread (SSE + fallback incremental)

Para reduzir refresh bruto e manter chat quase em tempo real:
- stream SSE por conversa: `GET /atendimento-whatsapp/conversa/{id}/stream`
- validacao previa por `probe` e `handshake` antes de abrir `EventSource`
- fallback automatico para polling incremental (`/novas?after_id=...`) quando SSE estiver indisponivel
- envio com UX desacoplada do refresh: confirmacao libera o botao rapidamente e a sincronizacao visual continua em background

Objetivo tecnico da validacao previa:
- evitar tentativas repetidas de SSE quando o endpoint retorna HTML (erro de MIME `text/html` no browser)
- manter atualizacao de mensagens funcional mesmo com indisponibilidade temporaria de stream

Comportamento implementado:
- polling automatico por configuracao (`central_mensagens_auto_sync_interval`)
- abertura de conversa marca inbound como lida e zera contador de nao lidas
- atualizacao de metadados (status, responsavel, prioridade, bot, aguardando humano, tags)
- mensagens recebidas (inbound) e enviadas (outbound) exibidas na mesma thread com classificacao de origem dinamica:
  - `sistema`: enviada pelo ERP por usuario interno
  - `externo`: enviada fora do ERP (app WhatsApp/gateway)
  - `chatbot`: enviada automaticamente pelo bot
- remetente dinamico no header da bolha outbound:
  - `sistema`: nome do usuario interno (`usuario_nome`)
  - `externo`: numero autenticado no gateway (`#gatewayAccountNumber`)
  - `chatbot`: `Chatbot`
- badge "via" dinamica:
  - `sistema`: `via sistema`
  - `externo`: `via app externo`
  - `chatbot`: `chatbot`
- cores padronizadas por origem outbound:
  - `sistema`: azul
  - `externo`: verde
  - `chatbot`: roxo
- acao rapida `Responder` em mensagens recebidas para montar resposta no campo de envio sem sair da conversa
- envio rapido por teclado (`Enter` envia, `Shift+Enter` quebra linha)
- lista lateral em estilo WhatsApp com preview da ultima mensagem e prefixo de origem (`Cliente`, `Voce`, `Bot`)
- abertura automatica da primeira conversa disponivel para acelerar leitura de mensagens recebidas
- anexos enviados pelo atendente com preview antes do envio (chip de anexo com remover)
- visualizacao de imagem padronizada no mesmo modal de galeria do sistema, com navegacao entre imagens da thread
- audio reproduzido no proprio chat com player embutido (play/pause, progresso e duracao)
- refresh da thread sem recarregamento bruto (incremental por `after_id`)
- carregamento inicial da thread com **janela mais recente** de mensagens (evita abrir conversas longas no inicio historico e garante visibilidade das mensagens externas mais novas)
- área de chat com fundo personalizado estilo "WhatsApp Doodle" com suporte a temas claro e escuro para maior imersão
- abertura de conversa executa sincronizacao forçada do historico no gateway local/linux antes do render, para reduzir defasagem de mensagens externas em casos de atraso de webhook
- **Hub de Composição (Botão "+")**: Centralização de todas as ações de envio em um menu hub inspirado no WhatsApp Web:
  - **Enviar arquivo**: Seleção de mídias e documentos locais (imagem, vídeo, áudio, PDF, docs).
  - **Enviar PDF do sistema**: Painel flutuante para seleção de PDFs gerados pelo ERP (Laudos, Orçamentos).
  - **Tipo da mensagem**: Painel flutuante para classificar a mensagem (Manual, Orçamento, Laudo, Status OS).
  - **Tirar foto agora**: Acionamento direto da câmera do dispositivo (mobile/desktop).
  - **Gravar áudio agora**: Gravador de voz integrado com preview e confirmação (`MediaRecorder API`).
  - **Gravar vídeo agora**: Gravador de vídeo integrado com preview de câmera e confirmação.
- **Preview de Anexo Moderno**: Exibição de anexos selecionados via "Chips" compactos com ícone por tipo, nome do arquivo, tamanho e ação de remover.
- **Resiliência de Captura**: Fallback para seleção de arquivo caso o dispositivo não suporte capturas diretas de mídia.

## Midias e armazenamento (padrao oficial)

Base publica:

`public/uploads/central_mensagens/{telefone}/{tipo}/`

Tipos de subpasta:
- `foto`
- `video`
- `audio`
- `pdf`
- `arquivo`

Regras:
- telefone sanitizado (somente digitos) como identificador do cliente da conversa
- criacao automatica da pasta do telefone e da subpasta de tipo
- normalizacao de nome de arquivo e controle de sobrescrita (`_1`, `_2`, ...)
- a mensagem salva em banco registra `arquivo`, `anexo_path`, `mime_type` e `tipo_conteudo`

Fallback para referencias orfas (VPS sem sincronismo de `uploads`):
- quando a referencia da midia existe no banco, mas o arquivo fisico nao existe em `public/uploads`, o backend marca a mensagem com:
  - `arquivo_disponivel = 0`
  - `arquivo_original` / `anexo_path_original` (rastreamento da referencia legada)
- nessas mensagens, `arquivo` e `anexo_path` sao retornados como `null` para evitar requests HTTP quebrados.
- o frontend renderiza aviso de "Arquivo indisponivel no servidor" em vez de tentar baixar/abrir o arquivo.

<a id="chatbot"></a>
### 2) Chatbot / Automacao
Rota: `/atendimento-whatsapp/chatbot`

Permite:
- cadastrar/ativar intencoes
- cadastrar/ativar regras ERP
- acompanhar logs de deteccao e escalonamento
- **Variaveis Dinamicas**: Suporte a templates nas respostas (`{{cliente_nome}}`, `{{numero_os}}`, `{{status}}`, `{{valor_final}}`, `{{data_previsao}}`, `{{garantia_dias}}`, `{{equipamento}}`, `{{empresa_endereco}}`).

<a id="metricas"></a>
### 3) Metricas
Rota: `/atendimento-whatsapp/metricas`

Exibe:
- recebidas/enviadas/automaticas/humanas
- SLA estourado
- top intencoes
- volume por dia
- produtividade por atendente

<a id="respostas-rapidas"></a>
### 4) Respostas Rápidas
Rota: `/atendimento-whatsapp/respostas-rapidas`

Interface refatorada para alta produtividade com:
- **Layout Bilateral**: Formulário de cadastro dinâmico à esquerda e catálogo de visualização à direita.
- **Edição Profissional em Modal**: Abertura de formulário dedicado em modal glassmorphism para uma experiência de edição focada, sem perder o contexto da listagem.
- **Variáveis Dinâmicas (Tags)**: Suporte a placeholders inteligentes como `{{cliente_nome}}`, `{{numero_os}}`, `{{equipamento}}`, `{{status}}`, `{{valor_final}}`, `{{data_previsao}}`, `{{garantia_dias}}` e `{{defeito}}`.
- **Seletor de Variáveis**: Interface visual para inserção rápida de tags no texto com um clique, garantindo o uso correto da sintaxe.
- **Preenchimento em Tempo Real**: No chat, ao selecionar uma resposta rápida, o sistema substitui automaticamente as tags pelos dados reais do cliente e da OS vinculada antes de carregar no campo de envio.

<a id="fluxos"></a>
### 5) Fluxos de Atendimento
Rota: `/atendimento-whatsapp/fluxos`

Fluxos padronizados por tipo (operacional, orcamento, pos-atendimento).

<a id="faq"></a>
### 6) FAQ / Base de Conhecimento
Rota: `/atendimento-whatsapp/faq`

Integração de inteligência e interface avançada:
- **Edição via Modal**: Gestão simplificada de perguntas e respostas em ambiente focado.
- **Base de Conhecimento Dinâmica**: FAQ agora suporta as mesmas tags dinâmicas (`{{cliente_nome}}`, `{{numero_os}}`, etc.) das respostas rápidas.
- **Detecção por Gatilhos**: Definição de palavras-chave que disparam a resposta automática via motor de intenções.
- **Preview de Conteúdo**: Listagem otimizada com truncamento inteligente para facilitar a navegação em grandes bases.

<a id="filas"></a>
### 7) Filas e Responsaveis
Rota: `/atendimento-whatsapp/filas`

Gestao de priorizacao, responsavel e status operacional da conversa.

<a id="configuracoes"></a>
### 8) Configuracoes
Rota: `/atendimento-whatsapp/configuracoes`

Parametros da central:
- intervalo de sync
- SLA de resposta
- provider padrao
- threshold de confianca do bot
- horario e dias de operacao
- mensagem de fallback (personalizavel quando o bot nao entende)

## Tabelas envolvidas
- `conversas_whatsapp`
- `contatos`
- `conversa_os`
- `conversa_tags`
- `mensagens_whatsapp`
- `respostas_rapidas_whatsapp`
- `chatbot_intencoes`
- `chatbot_faq`
- `chatbot_fluxos`
- `chatbot_logs`
- `chatbot_regras_erp`
- `mensageria_metricas_diarias`
- `crm_eventos`
- `crm_interacoes`
- `crm_followups`
- `crm_mensagens`
- `whatsapp_inbound` (fila de recebimento bruto)

## Endpoints internos
- `GET /atendimento-whatsapp` (WhatsApp OS)
- `GET /atendimento-whatsapp/conversa/{id}`
- `GET /atendimento-whatsapp/conversa/{id}/novas?after_id={id}`
- `GET /atendimento-whatsapp/conversa/{id}/stream?after_id={id}`
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`
- `POST /atendimento-whatsapp/enviar`
- `POST /atendimento-whatsapp/vincular-os`
- `POST /atendimento-whatsapp/atualizar-meta`
- `POST /atendimento-whatsapp/sync-inbound`
- `POST /atendimento-whatsapp/chatbot/intencao/salvar`
- `POST /atendimento-whatsapp/chatbot/regra/salvar`
- `POST /atendimento-whatsapp/faq/salvar`
- `POST /atendimento-whatsapp/respostas-rapidas/salvar`

Comportamento canonico de navegacao:
- a tela principal da Central e `GET /atendimento-whatsapp`
- para abrir uma thread especifica no navegador, a URL canonica passa a ser `GET /atendimento-whatsapp?conversa_id={id}`
- `GET /atendimento-whatsapp/conversa/{id}` permanece como endpoint JSON para AJAX da propria central
- se essa rota for aberta diretamente no navegador (sem AJAX), o controller redireciona para a tela principal com `?conversa_id={id}`, evitando exibir JSON cru ao usuario
- `POST /atendimento-whatsapp/fluxos/salvar`
- `POST /atendimento-whatsapp/filas/atualizar`
- `POST /atendimento-whatsapp/metricas/consolidar-diario`
- `POST /atendimento-whatsapp/configuracoes/salvar`

Webhook inbound:
- `POST /webhooks/whatsapp` (token em `X-Webhook-Token` ou `?token=`)

Parametros de stream SSE:
- `probe=1`: valida disponibilidade basica do endpoint em JSON (pre-check).
- `handshake=1`: valida cabecalhos de stream (`text/event-stream`) antes de abrir `EventSource`.
- `after_id`: envia apenas mensagens novas apos o ultimo id renderizado.

## Recebimento inbound com midia (novo)

### Origem
Gateway Node (`whatsapp-api/server.js`) captura mensagens inbound e encaminha para o ERP.

### Payload inbound suportado
- texto (`message`, `text`, `body`)
- metadados de midia (`has_media`, `media_mime_type`, `media_filename`)
- conteudo base64 (`media_base64`) quando disponivel e dentro do limite

### Persistencia no ERP
`CentralMensagensService::registerInboundFromPayload()`:

## Sincronizacao de respostas fora do ERP (novo)

Para garantir visao completa da conversa, a Central agora registra tambem mensagens enviadas pela propria conta fora da tela do ERP (ex.: tecnico respondeu no app WhatsApp do celular).

### Como funciona
- O gateway Node escuta:
  - `message` para mensagens recebidas do cliente (`fromMe = false`)
  - `message_create` para mensagens enviadas pela propria conta (`fromMe = true`)
- Ambos os eventos sao encaminhados para `POST /webhooks/whatsapp`.
- O ERP persiste:
  - `direcao = inbound` para cliente -> equipe
  - `direcao = outbound` para equipe -> cliente (inclusive resposta feita fora do ERP)

### Regra de deduplicacao
- Quando chega `provider_message_id` ja existente, o ERP atualiza o registro e nao duplica.
- Em corrida de envio (mensagem enviada pelo ERP e evento `message_create` quase simultaneo), o ERP tenta reconciliar com o outbound pendente recente para evitar mensagem duplicada na thread.

### Resultado operacional
- Se o tecnico responder no celular, a mensagem aparece na conversa do ERP.
- Lista de conversas e thread permanecem sincronizadas.
- CRM continua recebendo evento/interacao com origem WhatsApp.
- Mensagens enviadas fora do ERP recebem indicador visual na thread:
  - origem: `Equipe (externo)`
  - badge: `via app externo`
  - meta: `| externa`
- cria/resolve conversa por telefone
- registra mensagem em `mensagens_whatsapp` (`direcao=inbound`)
- salva anexo inbound em disco quando houver base64
- registra espelho CRM (`crm_mensagens`) + interacao + evento
- aciona `ChatbotService` para resposta automatica ou escalonamento humano

### Caminho de anexos inbound
- `public/uploads/central_mensagens/{telefone}/{tipo}/...`

## Atualizacao tecnica 19/03/2026 (estabilizacao de operacao)

### 1) Stream SSE com protecao anti-loop (MIME incorreto)
- Frontend passou a respeitar `CM_CFG.enableSse`.
- Se o endpoint de stream responder com tipo invalido (ex.: `text/html`), o modulo:
  - desativa SSE temporariamente na sessao (`sessionStorage`),
  - mantem polling incremental ativo,
  - evita spam de reconexao e erro repetido no console.

### 2) Reconciliacao incremental quando o webhook atrasa
- Em `GET /atendimento-whatsapp/conversa/{id}/novas`, quando nao ha mensagens novas e existe `after_id`, o controller executa uma sincronizacao forcada curta (`syncInboundQueue(..., true)`) e consulta novamente.
- Isso reduz cenarios de "mensagem enviada fora do ERP nao apareceu ainda" em conversas ativas.

### 3) Blindagem de render na thread
- Renderer da mensagem usa fallback defensivo para payload (`payloadFn`) com escopo local e global.
- Objetivo: eliminar quebra de abertura de conversa por erro `messagePayload is not defined` em cenarios de cache/versao antiga.

### 4) UX mobile/offcanvas
- Gatilhos mobile de WhatsApp OS/Contexto (`cm-mobile-list-trigger` e `cm-mobile-context-trigger`) ganharam fallback JS para `bootstrap.Offcanvas.getOrCreateInstance(...).show()`.
- Mantem funcionamento em viewport reduzida mesmo quando o `data-bs-toggle` nao dispara por re-render dinâmico.

### 5) Envio com timeout defensivo no frontend
- `POST /atendimento-whatsapp/enviar` no cliente passou a usar timeout de 16s.
- Evita spinner preso indefinidamente quando o gateway demora/instabiliza.
- Em timeout, o usuario recebe alerta SweetAlert2 com orientacao de reenvio.

### 6) Gateway indisponivel tratado como erro operacional
- Quando o provider de envio estiver fora do ar, sem URL valida ou inacessivel por rede, a Central nao devolve mais `422`.
- O endpoint `POST /atendimento-whatsapp/enviar` responde `503` com `code = CM_ENVIO_PROVIDER_UNAVAILABLE`.
- A UI mostra mensagem operacional clara:
  - gateway local offline: orientar inicio/reinicio do servico
  - provider externo indisponivel: orientar nova tentativa apos estabilizacao
- O detalhe tecnico bruto continua apenas em log/contexto, evitando expor erro de baixo nivel ao operador final.

## Evolucao premium de continuidade (01/04/2026 - v2.10.1)

### 1) Barra de conexao operacional no painel da conversa
- A conversa ativa passou a exibir uma barra de saude de conexao (`Conectado`, `Sincronizando`, `Instavel`, `Offline`) com atualizacao em tempo real.
- O indicador reage a falhas de rede/timeout no frontend e sinaliza reconexao automatica sem bloquear o operador.

### 2) Envio otimista no composer (sensacao de app moderno)
- Ao enviar, a bolha outbound aparece imediatamente com estado `Enviando`, antes do retorno final da API.
- Se o envio confirmar, a bolha temporaria e reconciliada com a mensagem definitiva.
- Se falhar, a bolha permanece marcada como `Falha no envio`, reduzindo perda de contexto operacional.

### 3) Rascunho automatico por conversa
- O texto digitado no composer passa a ser salvo automaticamente por conversa no navegador.
- Ao trocar de thread e voltar, o rascunho da conversa e restaurado.
- Conversas com rascunho exibem badge visual na lista, melhorando continuidade de atendimento.

### 4) Composer com disponibilidade inteligente
- O botao de enviar agora respeita estado real da conversa e do conteudo.
- Ele so habilita quando existe conversa ativa e algum conteudo valido (texto, PDF selecionado ou anexo).
- O estado tambem sincroniza durante envio, anexos e restauracao de rascunho.

