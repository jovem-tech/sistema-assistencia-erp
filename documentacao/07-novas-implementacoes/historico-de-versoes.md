# Historico de Versoes do Sistema

Atualizado em: 24/04/2026  
Versao atual oficial: `2.15.2`

## Observacao sobre o App Mobile/PWA

O app mobile/PWA passa a manter documentacao e politica de versionamento proprias, separadas da linha de versao do ERP.

Referencias oficiais do app:

- `documentacao/12-app-mobile-pwa/README.md`
- `documentacao/12-app-mobile-pwa/09-versionamento-e-releases/politica-de-versoes.md`
- `documentacao/12-app-mobile-pwa/09-versionamento-e-releases/historico-de-versoes.md`

Estado documental atual do app:

- versao do app: `0.4.2`
- ERP minimo compativel: `2.11.5`
- documentacao exclusiva aprofundada em 04/04/2026

## Release ERP + App

### 24/04/2026 - v2.15.2 / app 0.4.2
- formalizado o novo fluxo Git multiambiente com `develop-desktop` para desenvolvimento, `homolog-vm` para homologacao e `main` exclusiva para producao;
- a `VM Ubuntu 24` passou a ser a etapa oficial de validacao final antes de promover codigo para a `main`, alinhando homologacao e infraestrutura da `VPS`;
- documentado o checklist completo de deploy com backup Git da `VPS`, dump do banco com `--no-tablespaces` e compactacao dos arquivos antes de cada atualizacao;
- sincronizada a documentacao operacional do projeto com a nova governanca de quatro ambientes: `PC`, `notebook`, `VM` e `VPS`;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.2`.

### 24/04/2026 - v2.15.1 / app 0.4.2
- o formulario de Orcamentos passou a aceitar `Telefone de contato` como campo opcional em `/orcamentos/novo` e `/orcamentos/editar/{id}`;
- a validacao do modulo foi alinhada em `frontend + backend`, mantendo a checagem de celular WhatsApp com DDD apenas quando o numero for informado;
- o card `Dados do Cliente` passou a exibir `Contato adicional do cliente` com nome e telefone quando o cadastro possuir `clientes.nome_contato` e `clientes.telefone_contato`;
- o autocomplete de cliente/contato passou a levar esse resumo adicional para a selecao e para o estado inicial de edicao;
- publicada a documentacao oficial de Orcamentos nas secoes de manual do usuario, modulo tecnico, arquitetura e nota de release;
- sincronizada a versao exibida no ERP com `app/Config/SystemRelease.php` em `2.15.1`.

### 24/04/2026 - hotfix fotos fallback da OS (sem bump de versao)
- corrigido o helper `withFotoVersion()` da tela `app/Views/os/form.php` para nao anexar `?v=timestamp` em origens `data:` e `blob:`;
- a sidebar de foto do equipamento e o modal de fotos da OS voltaram a renderizar corretamente fallbacks inline quando o arquivo fisico nao existe;
- mantido o anti-cache apenas para URLs reais de upload, preservando a reatividade apos inserir, excluir ou definir foto principal.

### 23/04/2026 - v2.15.0 / app 0.4.2
- consolidada a nova leitura operacional da OS, com abas `Informacoes`, `Orcamento`, `Diagnostico`, `Fotos` e `Valores`;
- a aba `Informacoes` passou a exibir o status atual da OS e o status do orcamento vinculado apenas para leitura, sem alterar o fluxo por ali;
- a visualizacao da OS passou a abrir o orcamento ja vinculado em vez de criar duplicidade quando o atendimento ja possui proposta associada;
- a listagem `/os` passou a exibir o contexto combinado de `OS + orcamento`, incluindo status do orcamento, numero vinculado e fallback de valor pelo total do orcamento;
- a sincronizacao operacional ficou consolidada:
  - orcamento em andamento vinculado -> OS em `aguardando_autorizacao`;
  - orcamento `aprovado` ou `convertido` -> OS em `aguardando_reparo`;
- o modal `Nova OS` da listagem foi endurecido para nao fechar por clique fora ou `ESC`, exigindo confirmacao no `X` quando houver preenchimento em andamento;
- a documentacao principal do ERP foi revisada e normalizada em PT-BR UTF-8, incluindo `README`, manuais, modulos tecnicos, historico oficial, nota de release e registro de deploy.
- formalizado o fluxo Git multiambiente (`PC/notebook -> GitHub/develop -> VM Ubuntu 24 -> GitHub/main -> VPS`) e endurecido o `.gitignore` para evitar backups temporarios e dependencias geradas em commits futuros.

### 08/04/2026 - v2.12.1 / app 0.4.2 (hotfix navbar fixa)
- corrigida a navegacao web para manter a navbar superior fixa no topo da aplicacao durante o scroll;
- aplicado ajuste estrutural de compensacao de layout em `.main-content` para evitar sobreposicao de conteudo;
- sincronizado o offset lateral da navbar com o estado da sidebar (aberta, recolhida e mobile);
- deploy publicado sem sincronizacao de dados de teste (`public/uploads/`, `.env` e `writable/` preservados).

### 08/04/2026 - v2.12.0 / app 0.4.2
- release oficial do ERP para o novo modulo profissional de orcamentos, consolidando as fases 1, 2 e 3 do projeto;
- habilitado fluxo completo de orcamento avulso e orcamento vinculado a OS, incluindo painel dedicado no sidebar;
- envio operacional por WhatsApp, e-mail e PDF com trilha de envios completa e rastreavel;
- liberada conversao de orcamento aprovado para OS ou venda manual, com status dedicado para `pendente_abertura_os` quando for avulso;
- adicionada automacao de vencimento e follow-up comercial (`php spark orcamentos:lifecycle` e acao web `Executar automacao`);
- central de mensagens agora permite `Gerar e enviar orcamento` no contexto da conversa do cliente;
- app mobile/PWA permanece na versao `0.4.2` com ERP minimo compativel mantido em `2.11.5`.

### 08/04/2026 - fase 3 do modulo de orcamentos (compoe a release v2.12.0)
- entregue conversao de orcamento aprovado para `OS` ou `venda manual`, com fechamento em status `convertido`;
- aprovacoes publicas de orcamento sem OS agora entram em `pendente_abertura_os`;
- adicionada automacao de vencimento/follow-up de orcamentos no ERP (`Executar automacao`) e no CLI (`php spark orcamentos:lifecycle`);
- central de mensagens recebeu botao inline `Gerar e enviar orcamento` no contexto da conversa;
- contexto da conversa passou a exibir `Orcamentos relacionados` para acompanhamento rapido;
- sidebar comercial de orcamentos evoluiu para submenu de gestao (painel, aguardando resposta, pendentes de OS, novo orcamento);
- criado indice de performance para deduplicacao de follow-ups (`crm_followups.origem_evento`).

### 07/04/2026 - fase 2 do modulo de orcamentos (compoe a release v2.12.0)
- habilitado envio direto na tela de orcamento por `WhatsApp`, `e-mail` e `PDF`;
- criado service de PDF do modulo (`OrcamentoPdfService`) com versao de arquivo por orcamento;
- criado service de e-mail do modulo (`OrcamentoMailService`) com SMTP do ERP;
- adicionadas rotas de envio e arquivo: `POST /orcamentos/pdf/{id}/gerar`, `GET /orcamentos/pdf/{id}`, `POST /orcamentos/whatsapp/{id}/enviar`, `POST /orcamentos/email/{id}/enviar`;
- adicionada trilha completa de envio por tentativa em `orcamento_envios` com status, provedor, referencia externa e erro;
- visualizacao do orcamento ganhou painel de envio rapido com confirmacao via SweetAlert2 e rastreabilidade detalhada em tela.

### 07/04/2026 - fase 1 do modulo de orcamentos (compoe a release v2.12.0)
- criado modulo dedicado `Orcamentos` no sidebar com CRUD completo no ERP web;
- adicionadas tabelas dedicadas para cabecalho, itens, historico de status, envios e aprovacoes externas;
- habilitado fluxo de criacao com prefill por OS e por Central de Mensagens (conversa/cliente);
- adicionado link publico por token para aprovacao/rejeicao do cliente;
- adicionado provisionamento de permissao `orcamentos` no RBAC via migration;
- documentacao funcional/tecnica do modulo publicada nas secoes de usuario, administrador, arquitetura, banco, rotas e roadmap.

### 06/04/2026 - hotfix fotos de acessorios (sem bump de versao)
- concluido o fluxo de fotos no formulario rapido de `Acessorios e Componentes (na entrada)` na OS web.
- os botoes `Galeria` e `Camera` do card rapido agora abrem corretamente o fluxo de crop e preview antes do `Salvar item`.
- o rascunho do acessorio passou a manter `entryId` estavel para vincular as fotos ao item correto no envio do formulario.
- cancelamento de item rapido sem salvamento agora remove fotos temporarias do rascunho para evitar anexos orfaos.
- backend mobile (`OrdersController`) passou a preservar `id` em `acessorios_data` decodificado, garantindo mapeamento correto de `fotos_acessorios[entryId][]`.

### 06/04/2026 - hotfix de borda na aba Equipamento (sem bump de versao)
- ajustado o layout dos paineis `Checklist de entrada` e `Acessorios e Componentes` em `/os/nova` e `/os/editar/{id}` para eliminar vazamento visual de borda.
- criada classe estrutural dedicada (`os-equip-panels-row` + `os-equip-panel-card`) com trava de largura, controle de overflow e comportamento consistente de gutter.
- removida a variacao de sombra externa nesses dois paineis especificos para manter o contorno dentro do limite do card pai.
- reduzido o contraste da borda desses paineis para um visual mais discreto e harmonico no layout da aba `Equipamento`.
- reforcado o contorno arredondado dos paineis internos (raio dedicado e `background-clip`) para evitar efeito de quina reta/aparencia cortada.
- ajustado o respiro inferior do card interno para impedir que a borda de baixo fique colada visualmente ao limite do card externo.

### 06/04/2026 - hotfix UX OS web (sem bump de versao)
- adicionado botao inline `Editar` ao lado de `Novo` no campo `Equipamento *` da aba `Equipamento` na OS web.
- o novo botao segue o design system atual (`btn-outline-info`, tamanho pequeno, icone + texto).
- o botao `Editar` aparece apenas quando ha equipamento selecionado, reduzindo clique invalido.
- o fluxo reaproveita o mesmo modal/funcoes de edicao de equipamento ja existentes, sem duplicar logica.

### 06/04/2026 - hotfix stack de modais no checklist (sem bump de versao)
- corrigido o empilhamento visual entre `Checklist`, `Camera` e `Cropper` na OS web.
- padronizadas camadas de z-index para garantir abertura em cascata (`checklist < camera < crop`).
- sincronizado o z-index do `modal-backdrop` com a camada ativa para evitar modais "por tras" do overlay.
- removido alerta falso de "falha ao abrir checklist" que podia ocorrer por verificacao assincrona.
- ajustado `SweetAlert2` da tela para camada acima dos modais tecnicos, impedindo confirmacoes/alertas de ficarem atras do modal de checklist.

### 05/04/2026 - hotfix tecnico checklist (sem bump de versao)
- corrigido erro `500` em `GET /checklists/entrada` quando a infraestrutura de checklist ainda nao estava migrada no banco.
- adicionadas validacoes defensivas de infraestrutura no fluxo de checklist para evitar erro fatal e devolver estado seguro.
- adicionado fallback no `ChecklistTipoModel::findByCodigo()` para tratar indisponibilidade de tabela sem derrubar a tela.
- migration obrigatoria para habilitar o modulo: `php spark migrate`.

### 04/04/2026 - v2.11.5 / app 0.4.2
- a abertura de OS passou a exibir selecao rica de equipamento tanto no ERP web quanto no app mobile/PWA;
- cada opcao de equipamento agora mostra foto de perfil, `tipo - marca`, `modelo - cor` e `numero de serie/IMEI`, reduzindo erro em clientes com aparelhos semelhantes;
- a busca do equipamento foi ampliada para considerar esses metadados tecnicos nas duas interfaces;
- o backend passou a expor `foto_url`, `tipo_nome`, `marca_nome`, `modelo_nome`, `cor`, `numero_serie` e `imei` nos fluxos usados pela abertura de OS;
- ERP minimo compativel do app atualizado para `2.11.5`.

## Hotfix App Mobile/PWA

### 04/04/2026 - v0.4.1
- hotfix do `sw.js` para subdominio dedicado do app, removendo o pre-cache da raiz quando ela responder com redirect;
- cache do PWA restrito a assets estaveis e respostas `200 OK`, sem persistencia de respostas `503` e sem cache de chamadas `/api/`;
- fallback offline endurecido para usar rota segura em cache, reduzindo falsos `503 Service Unavailable` no app publicado.

## Politica de versionamento (SemVer)

Padrao adotado: `MAJOR.MINOR.PATCH`

- `MAJOR`: quebra de compatibilidade, mudanca estrutural relevante, migracao obrigatoria com impacto alto.
- `MINOR`: novas funcionalidades compativeis com versoes anteriores.
- `PATCH`: correcoes e ajustes sem quebra de compatibilidade.

## Regras obrigatorias para mudar versao

1. Definir o tipo de mudanca (MAJOR, MINOR ou PATCH) antes de publicar.
2. Atualizar `app/Config/SystemRelease.php`.
3. Atualizar este arquivo (`documentacao/07-novas-implementacoes/historico-de-versoes.md`).
4. Criar ou atualizar nota tecnica da release em `documentacao/07-novas-implementacoes/`.
5. Validar consistencia com override opcional em banco (`configuracoes.sistema_versao`), se utilizado.
6. Criar tag git no padrao `vMAJOR.MINOR.PATCH` quando autorizado.

## Linha do tempo oficial (consolidada)

> Observacao: releases antigas foram consolidadas retroativamente com base no historico tecnico e documental do projeto.

### v2.12.1 - Hotfix de layout: navbar superior fixa
- `.top-navbar` consolidada como elemento fixo (`position: fixed`) em desktop.
- Compensacao vertical da area principal via `padding-top: var(--navbar-height)` para evitar sobreposicao.
- Offset lateral sincronizado com sidebar aberta/recolhida e ajuste dedicado para mobile (`left: 0`).

### v2.12.0 - Orcamentos profissional (sidebar + envio multicanal + aprovacao publica + conversao + automacao)
- Novo modulo `Orcamentos` com painel dedicado no sidebar e operacao completa de cotacao avulsa e cotacao vinculada a OS.
- Envio operacional por `WhatsApp`, `e-mail` e `PDF` com trilha de tentativas/erros e reenvio auditavel.
- Fluxo de aprovacao/rejeicao por link publico com token e status `pendente_abertura_os` para aprovados avulsos.
- Conversao de aprovado para `OS` ou `venda manual` e automacao de vencimento/follow-up no CRM.

### v2.11.3 - PWA: hardening do Service Worker (fallback de Response)
- Corrigido o erro de runtime no Service Worker `Failed to convert value to 'Response'` em cenarios de falha de rede sem item correspondente no cache.
- O handler de `fetch` passou a garantir retorno de `Response` em todos os caminhos, incluindo fallback `503` offline quando nao houver cache.
- Adicionada protecao para requests nao HTTP no `fetch` do SW, evitando tratamento indevido de esquemas fora de rede.
- Melhorada a resiliencia de cache no fetch com tratamento de erro explicito no `cache.put`.
- Cache do SW versionado para `assistencia-mobile-v3` para forcar atualizacao limpa dos clientes.

### v2.11.4 - App mobile/PWA: consolidacao operacional, documentacao exclusiva e release oficial 0.4.0
- O app mobile/PWA passou da linha local `0.4.0-dev` para a release oficial `0.4.0`, com ERP minimo compativel `2.11.4`.
- Consolidado o fluxo mobile de OS com cliente, equipamento, fotos, crop, acessorios estruturados, busca inteligente e galerias de perfil.
- A documentacao exclusiva do app foi fechada em `documentacao/12-app-mobile-pwa/`, incluindo API detalhada, design system, padroes de desenvolvimento, skills e governanca de versionamento.
- O build de producao do app foi endurecido para publicacao, eliminando bloqueio de hook naming na tela de nova OS.
- O deploy da VPS foi executado em modo seguro, sem sincronizacao de dados de teste nem de `public/uploads/`.

### 04/04/2026 - App mobile/PWA: documentacao exclusiva aprofundada + skills reais
- O hub `documentacao/12-app-mobile-pwa/` foi expandido com manuais tela por tela, arquitetura complementar, API detalhada por modulo, banco campo a campo e design system aprofundado.
- O app passou a ter skills reais versionadas no proprio repositorio para guiar futuras alteracoes de mobile/PWA.
- A versao do app deixou de ficar apenas no login e passou a ficar explicita tambem na navegacao autenticada do mobile.

### v2.11.2 - PWA mobile com abertura completa de OS (paridade de cadastro)
- A API mobile recebeu `GET /api/v1/orders/meta` para abastecer o formulario de abertura com cliente, equipamento, tecnico, status, prioridade e defeitos por tipo.
- `POST /api/v1/orders` foi ampliado para abertura completa da OS, incluindo campos operacionais, financeiros, garantia, defeitos, acessorios, estado fisico e upload de fotos de entrada.
- O frontend mobile ganhou a tela `/os/nova` com formulario completo e envio `multipart/form-data`, reaproveitando o backend e banco ja existentes.
- A listagem mobile de OS ganhou acao direta `Nova OS`, conectando o fluxo de abertura sem sair do app PWA.
- O backend passou a aceitar upload de fotos de entrada com os nomes `fotos_entrada` e `fotos_entrada[]` para garantir compatibilidade entre clientes HTTP.

### v2.11.1 - Push mobile inbound em producao (Web Push real)
- Adicionado envio real de Web Push no backend via `minishlink/web-push` com assinatura VAPID.
- Criado `WebPushService` para disparo de notificacoes aos dispositivos registrados em `mobile_push_subscriptions`.
- `MobileNotificationService` passou a disparar push automaticamente apos criar notificacoes em `mobile_notifications`.
- `CentralMensagensService` passou a gerar notificacoes mobile ao receber mensagem inbound de cliente, com rota direta para `/conversas/{id}` no PWA.
- Subscriptions expiradas/invalidas passam a ser desativadas automaticamente (`ativo = 0`), reduzindo erro recorrente de entrega.
- Mantida compatibilidade com a base atual: sem duplicar tabelas de operacao e sem alterar o fluxo principal da Central web.

### v2.11.0 - Central Mobile PWA (MVP paralelo) integrada ao ERP
- Criada a fundacao do modulo mobile/PWA paralelo em `mobile-app/` (Next.js), sem alterar ou substituir a Central web existente.
- Implementada API interna versionada em `GET/POST/PUT/PATCH/DELETE /api/v1/*` no CodeIgniter 4 para `auth`, `users`, `clients`, `orders`, `conversations`, `messages`, `notifications`, `push subscriptions` e `realtime stream` (SSE).
- Adicionado filtro dedicado `apiToken` com autenticacao Bearer hashada, incluindo fallback `access_token` para conexoes SSE via EventSource.
- Criada a migration `2026-04-03-010000_CreateMobilePwaInfrastructure` com tabelas complementares:
  - `mobile_api_tokens`
  - `mobile_push_subscriptions`
  - `mobile_notifications`
  - `mobile_notification_targets`
  - `mobile_event_outbox`
- Reaproveitado o dominio existente do ERP: conversas/mensagens WhatsApp continuam via tabelas e servicos atuais (`conversas_whatsapp`, `mensagens_whatsapp`, `WhatsAppService::sendRaw`) e OS via `OsModel`.
- Publicado o ponto de entrada protegido `/atendimento-mobile`, com redirecionamento para `configuracoes.mobile_pwa_url` (fallback `/atendimento-mobile-app/login`) e modo de validacao desktop por `?preview=1`.
- App mobile publicado em PM2 e proxy Nginx na subrota `/atendimento-mobile-app`, mantendo o ERP web no mesmo host.
- Manifest PWA reforcado com icones PNG (`192`, `512`, `maskable`) para ampliar compatibilidade de instalacao no Android/Chrome.
- Login do PWA reforcado com card de instalacao assistida (`PwaInstallCard`) para expor botao de instalacao quando elegivel, instruir instalacao manual em iOS e sinalizar bloqueio quando o ambiente estiver sem HTTPS valido.
- Modulo `Notificacoes` reforcado com diagnostico tecnico de push (suporte do navegador, HTTPS, modo standalone no iOS, permissao atual e VAPID) e com bloqueio orientado do botao de ativacao quando os pre-requisitos nao estao atendidos.
- Diagnostico de push iOS ampliado com validacao de versao minima (`iOS 16.4+`), visibilidade de `Service Worker/PushManager/Notification API` e botao de teste local de notificacao.
- Ajustada a integracao entre subdominios (`app` -> `sistema`) no rewrite interno do Next para eliminar `404` em `/api/v1/*`.
- `sw.js` atualizado para install resiliente com cache incremental de assets, evitando quebra de ativacao por falha pontual de download.

### v2.10.17 - Central premium: bot/humano dentro do menu hamburguer
- Os controles de modo de atendimento (`Bot ativo/Bot desativado` e `Aguardando atendimento humano`) foram movidos para dentro do menu hamburguer da thread.
- O cabecalho principal manteve apenas informacao explicita de estado (`Status` e `Prioridade`) e o atalho `Ocultar/Mostrar contexto`.
- O comportamento binario de modo permaneceu inalterado no backend/frontend, mudando apenas o ponto de acesso visual para reduzir poluicao no topo do chat.

### v2.10.16 - Central premium: status/prioridade visiveis no header + modo binario bot/humano
- `Status` da conversa voltou a ficar visivel diretamente no cabecalho da thread (fora do menu) e permanece clicavel para abrir o modal de alteracao.
- `Prioridade` passou a ficar explicita no cabecalho da thread, com atualizacao visual imediata do nivel atual.
- O controle de bot foi refinado para exibir `Bot ativo` (verde) quando ligado e `Bot desativado` (vermelho) quando desligado.
- `Aguardando atendimento humano` passou a operar como estado oposto ao bot, evitando combinacoes conflitantes de modo de atendimento.
- O botao `Ocultar/Mostrar contexto` voltou ao cabecalho, ao lado do menu hamburguer.
- O bloco `Acoes avancadas` foi removido do dropdown de acoes por nao possuir funcionalidade efetiva.
- O cabecalho de acoes foi mantido em linha unica, com overflow horizontal controlado para preservar alinhamento lateral dos controles em qualquer largura.

### v2.10.15 - Central premium: menu hamburguer no cabecalho da thread
- A barra horizontal de acoes da conversa foi migrada para um menu hamburguer no topo da coluna central (`cm-thread-header-top`), eliminando sobreposicao de controles na area do chat.
- Todas as operacoes permaneceram no menu unico: status, modo de atendimento, assumir, atribuir, prioridade, encerrar, nova conversa, sync inbound, atualizar e acoes avancadas.
- O item de status ganhou renderizacao textual no proprio menu (`Status: ...`) com cores por estado, preservando leitura rapida sem ocupar espaco fixo da thread.

### v2.10.14 - Central premium: hotfix de tooltip/dropdown + timeout gateway ampliado
- Corrigido o conflito Bootstrap `one instance per element` na action bar da Central: botoes com `dropdown` deixaram de receber inicializacao de tooltip Bootstrap, eliminando spam de erro no console.
- Mantido tooltip em botoes compativeis e preservado `title` nativo nos toggles de dropdown para nao perder orientacao visual.
- Timeout padrao do provider local (`api_whats_local` e `api_whats_linux`) subiu de `20s` para `30s`, reduzindo falhas `503` por timeout em envio sob latencia.

### v2.10.13 - Central premium: modo unico de atendimento + acoes avancadas menos expostas
- Os chips `Bot ativo` e `Aguardando humano` foram unificados em um unico controle com dropdown de modo (`Bot ativo`, `Aguardando atendimento humano`, `Sem nenhum ativado`), evitando estados conflitantes no topo da thread.
- A acao `Ocultar contexto` permaneceu no menu `+`, mas foi movida para a secao `Acoes avancadas` com clique adicional, reduzindo acesso acidental.
- A action bar foi reforcada para permanecer em linha unica em todos os breakpoints, com overflow horizontal controlado quando necessario.
- Em mobile, a barra continua em uma unica linha com icones compactos, sem quebra vertical entre grupos.

### v2.10.12 - Central premium: action bar SaaS com 3 grupos e hierarquia operacional
- O topo da thread ganhou reformulacao completa da barra de acoes com estrutura em 3 grupos: `Status` (esquerda), `Acoes operacionais` (centro) e `Acoes criticas` (direita).
- O grupo de status passou a usar chips leves estilo produto SaaS (`Status`, `Bot ativo`, `Aguardando humano`), com feedback visual de estado sem aspecto de botao legado.
- O grupo central foi padronizado com `ActionButton` outline de altura uniforme, icone + texto e hover com elevacao leve para `Assumir`, `Atribuir` e `Prioridade`.
- O grupo critico passou a destacar `Encerrar` em vermelho e substituiu o antigo `+` por menu dropdown para acoes extras (`Nova conversa`, `Sincronizar inbound`, `Atualizar conversa`, `Contexto`).
- Em mobile, os labels da action bar passam a priorizar icones, o grupo central vira trilha com scroll horizontal e `Encerrar` permanece fixo e visivel.

### v2.10.11 - Central premium: timeout resiliente com lock livre entre polling e envio
- Endpoints de leitura da Central (`conversas`, `conversa/{id}` e `conversa/{id}/novas`) passaram a operar sem processamento de fila no caminho critico, priorizando resposta imediata do polling.
- O endpoint `enviar` passou a liberar lock de sessao antes do envio ao provider, reduzindo bloqueio concorrente entre envio e polling do mesmo operador.
- Timeout padrao de requests no frontend foi elevado para `30s` e o timeout de envio passou a ser dinamico com minimo de `25s`, evitando falso negativo em respostas mais lentas do provider.

### v2.10.10 - Central premium: polling incremental resiliente sem timeout em cascata
- Os endpoints de polling rapido (`conversas` e `conversa/{id}/novas`) deixaram de acionar sincronizacao pesada de historico do gateway a cada chamada.
- A leitura de fila local inbound foi separada em rotina dedicada (`processInboundQueueOnly`), preservando a atualizacao imediata sem bloquear o chat.
- O endpoint `sync-inbound` passou a liberar lock de sessao antes de processar sincronizacao pesada, evitando fila de requests concorrentes do mesmo operador.
- A coleta de historico no gateway foi recalibrada para lotes menores (menos chats/mensagens por ciclo), reduzindo tempo de resposta sob carga de midia.
- O frontend ganhou backoff progressivo em falhas de rede e menor frequencia de refresh da lista durante conversa ativa, reduzindo spam de timeout no console.

### v2.10.9 - Central premium: inbound multimidia com hidratacao de anexos
- O gateway WhatsApp passou a sincronizar historico com download de midias (audio, video, imagem e anexos), respeitando limite de tamanho configurado.
- O endpoint interno de historico (`/sync-chat-history`) agora devolve `media_base64`, `media_mime_type` e `media_filename` quando a midia estiver disponivel.
- O parser inbound da Central passou a mapear tipos de voz do WhatsApp (`ptt`/`voice`) como `audio`, evitando classificacao incorreta como `arquivo`.
- A rotina de deduplicacao por `provider_message_id` passou a hidratar midia faltante em mensagens ja existentes, preenchendo `arquivo`, `anexo_path`, `mime_type` e `tipo_conteudo` quando um payload posterior trouxer anexo.
- O frontend passou a exibir fallback de midia em sincronizacao no lugar de `[mensagem sem texto]`, reduzindo ambiguidade na leitura operacional.

### v2.10.8 - Central premium: controle operacional por modal + sync sem flicker
- O sync inbound automatico em background passou a evitar refresh desnecessario: a fila so recarrega quando houver mensagens novas (`count > 0`) e a thread ativa nao e mais reaberta a cada ciclo, eliminando efeito de bolhas sumirem/reaparecerem.
- O badge de status ao lado de `Contexto` virou acao clicavel e abre modal de troca de status da conversa.
- O botao `Atribuir` agora abre modal de atribuicao com lista de responsaveis, sem redirecionar foco para o painel lateral.
- O botao `Encerrar` agora oferece decisao operacional: `Concluir` (status `resolvida`) ou `Arquivar` (status `arquivada`).
- A barra de filtros rapidos ganhou o atalho `Arquivadas`.
- Foram adicionados botoes rapidos no cabecalho para:
  - definir `Prioridade` (padrao `normal`);
  - marcar `Bot ativo`;
  - marcar `Aguardando humano`.
- No backend, toda conversa em `resolvida` passa automaticamente para `aberta` quando chega nova mensagem inbound.

### v2.10.7 - Central premium: sincronizacao inbound silenciosa no chat
- A sincronizacao automatica em background deixou de exibir a faixa azul de `Sincronizando mensagens inbound...` dentro da thread.
- A barra de conexao do chat passa a ficar oculta no estado online normal e aparece apenas em sincronizacao manual ou em cenarios de alerta/offline.
- O feedback continuo da rotina automatica foi restringido ao badge de inbound no topo da tela, preservando a usabilidade do composer e da leitura da conversa.

### v2.10.6 - Central premium: composer com altura compacta forcada
- O `textarea` da thread passou a receber altura compacta com prioridade alta no CSS, garantindo alinhamento visual com o botao de envio mesmo em estados intermitentes.
- O auto-resize do composer agora limpa estados vazios com mais agressividade e reaplica a altura base apos abrir conversa e no bootstrap da tela.
- O breakpoint `<=360px` recebeu o mesmo tratamento compacto, mantendo coerencia visual tambem em telas pequenas.

### v2.10.5 - Central premium: composer compacto com altura base estavel
- O `textarea` da thread passou a respeitar uma altura base compacta alinhada ao botao de envio, evitando estados visuais "altos demais" quando o campo esta vazio.
- O auto-resize do composer agora so expande quando existe conteudo real multi-linha ou overflow efetivo, reduzindo casos intermitentes de desarmonia visual.
- O overflow vertical do campo fica oculto no estado compacto e so aparece quando o limite maximo de expansao for atingido.

### v2.10.1 - Central premium: conexao operacional, envio otimista e rascunho por conversa
- A conversa ativa passou a exibir barra de saude de conexao com estados claros (`Conectado`, `Sincronizando`, `Instavel`, `Offline`) e feedback de reconexao.
- O envio ganhou bolha otimista com status `Enviando`, aproximando a experiencia de mensageiro moderno e reduzindo sensacao de latencia.
- Falhas de envio agora permanecem marcadas na timeline como `Falha no envio`, sem esconder a tentativa do operador.
- O composer passou a salvar rascunho por conversa no navegador, restaurando automaticamente ao retornar para a thread.
- A lista de conversas passou a destacar threads com rascunho pendente para priorizacao operacional.

### v2.10.4 - Central premium: fila estatica por data real de movimentacao
- O endpoint de listagem da Central passou a calcular `ultima_movimentacao_em` diretamente da mensagem mais recente de cada conversa (`recebida_em`, `enviada_em` ou `created_at`).
- A ordenacao backend foi reforcada com `COALESCE` da data de movimentacao real + desempate por `ultima_mensagem_id` e `conversa_id`, eliminando alternancia aparente aleatoria entre conversas.
- O frontend da fila passou a usar `ultima_movimentacao_em` como fonte primaria para ordenar, assinar alteracoes e exibir o horario da ultima interacao.
- O servico de sincronismo deixou de atualizar `ultima_mensagem_em` com `now()` em reconciliacoes de mensagens duplicadas/historicas, evitando empate artificial de timestamps em lotes do sync.

### v2.10.3 - Central premium: filtros recolhidos, fila cronologica estavel e composer compacto
- A fila de atendimento passou a abrir com os filtros avancados recolhidos por padrao, exibindo por padrao apenas os filtros rapidos `Todas`, `Nao lidas`, `Abertas` e `Com OS`.
- Foi adicionada a acao dedicada `Filtros avancados` para abrir/fechar os filtros completos sem ocupar espaco constante da lista.
- A ordenacao da inbox foi reforcada para criterio cronologico estavel por ultima interacao (envio/recebimento), com desempate deterministico para evitar mudancas aparentes aleatorias na lista.
- O campo de digitacao da mensagem foi reduzido para altura inicial mais compacta e proporcional ao botao de envio, mantendo autoexpansao somente quando houver mais conteudo.

### v2.10.2 - Central premium: sidebar recolhida por padrao e rolagem explicita por coluna
- A tela `/atendimento-whatsapp` passou a abrir com `sidebar` recolhida automaticamente no desktop, priorizando area util para operacao de inbox.
- A lista de conversas, a thread ativa e o painel de contexto receberam rolagem dedicada com barra visivel e estilo consistente.
- O layout dos paines laterais foi endurecido com `height/min-height` e `flex` completos no `offcanvas-body`, removendo casos onde a barra sumia em telas intermediarias.

### v2.10.0 - Central premium: filtros rapidos e sincronizacao inbound assistida
- A coluna de conversas ganhou barra de filtros rapidos (`Todas`, `Nao lidas`, `Abertas`, `Com OS`, `Clientes novos`) para alternancia operacional com um clique.
- O header da Central passou a exibir badge dedicada de sincronizacao inbound (`Inbound ocioso`, `Sincronizando`, `Inbound ok`, `Falha inbound`) com feedback visual continuo.
- O sincronismo inbound manual foi reforcado para uso silencioso em background, com carregamento simultaneo da fila e da conversa ativa apos cada ciclo.
- Foi adicionado loop automatico de sincronizacao inbound em segundo plano, mantendo a inbox atualizada mesmo quando a mensagem entra por canal externo sem gatilho de stream imediato.
- A grade visual da esquerda recebeu ajustes responsivos para manter filtros rapidos utilizaveis em `<=575px`, `<=430px` e `<=360px`.

### v2.9.5 - Gateway WhatsApp da VPS alinhado com Linux e busca global sem caracteres quebrados
- A VPS passou a usar novamente o provider `api_whats_linux`, com URL local `127.0.0.1:3001`, token sincronizado com o Node e origem publica correta do ERP.
- O menu de contexto da busca global da navbar foi normalizado com labels seguras (`Servicos`, `Pecas` e `OS Legado (numero antigo)`), eliminando caracteres corrompidos na interface da VPS.
- O filtro de contexto da navbar ficou explicitamente preparado para localizar OS pelo numero legado sem depender apenas da busca geral.

### v2.9.6 - Menuia com URL canonica, validacao real e badge confiavel
- O provider `menuia` passou a normalizar automaticamente a URL para `https://chatbot.menuia.com/api`, evitando uso inconsistente do host antigo.
- O botao `Testar conexao` passou a enviar uma mensagem unica com timestamp para o telefone de teste, eliminando falso negativo por rejeicao de mensagem duplicada.
- A tela `Configuracoes -> Integracoes` agora distingue corretamente os estados `Menuia conectada`, `Erro Menuia` e `Menuia nao validada`.
- Sempre que `URL`, `Appkey` ou `Authkey` da Menuia forem alteradas, o status salvo anterior e invalidado para impedir indicacao verde com credencial antiga.

### v2.9.7 - Central de Mensagens sem duplicacao visual de outbound
- A thread do atendimento WhatsApp passou a deduplicar mensagens antes de renderizar, usando `id` como chave principal sempre que disponivel.
- Quando uma mesma mensagem volta pelo stream SSE e depois pelo polling incremental, o frontend agora atualiza a bolha existente em vez de criar uma segunda.
- A protecao tambem cobre a abertura inicial da conversa, evitando que uma thread ja carregada do backend entre na tela com registros repetidos por reconciliacao tardia.

### v2.9.8 - Central premium: skeletons, teclado e anti-duplo-envio fullstack
- A Central de Mensagens recebeu skeleton loading real na fila e na conversa ativa, melhorando a percepcao de performance em cargas e trocas de thread.
- Itens da inbox passaram a suportar foco e navegacao por teclado (`Enter`, `Space`, `ArrowUp`, `ArrowDown`), com atalho `Ctrl/Cmd + K` para busca e `/` para foco rapido.
- O badge de tempo real passou a mostrar estado operacional com horario da ultima atualizacao visual.
- O composer ganhou lock anti-envio concorrente para impedir duplicacao por clique/enter repetido enquanto a requisicao ainda esta em andamento.
- O backend passou a reconciliar outbound duplicado recente por fingerprint operacional antes de inserir nova linha em `mensagens_whatsapp`, reduzindo duplicacao em cenarios de eco de provedor/webhook.

### v2.9.9 - Central premium: modo foco + debounce de fila + idempotencia de envio
- O cabecalho da conversa ganhou o botao `Contexto`, permitindo recolher/mostrar o painel contextual no desktop para operar em modo foco.
- A preferencia de exibicao do painel contextual passa a ser persistida localmente no navegador, mantendo o layout escolhido entre recargas.
- A busca da fila de conversas recebeu debounce, filtrando em tempo real sem exigir Enter e reduzindo carga de requests.
- O `WhatsAppService` ganhou idempotencia de curta janela (3s) para bloquear envios duplicados por clique rapido, antes mesmo da chamada ao provider.
- A release complementa o lock do frontend e reduz duplicacao de mensagens em ambiente VPS sob uso intenso.

### v2.9.2 - Busca global com filtro explicito para OS legado e correcao de acentuacao
- O menu de filtros da busca global da navbar passou a exibir corretamente as labels `Servicos`, `Pecas`, `Usuario` e `Configuracoes`, eliminando caracteres corrompidos na interface.
- A busca global ganhou o filtro explicito `OS Legado`, permitindo restringir a pesquisa a ordens migradas e localizar rapidamente uma OS pelo numero antigo.
- O backend da busca global passou a responder com um grupo dedicado `OS Legado`, sem perder o comportamento atual da busca geral por numero legado.

### v2.9.4 - Confirmacao visual ao salvar cliente pelo modal da OS
- O modal de cadastro/edicao rapida de cliente dentro da OS passou a exibir `SweetAlert2` de sucesso apos salvar.
- Quando o fluxo for uma edicao, o operador recebe a confirmacao `Cliente atualizado`; quando for um novo cadastro, recebe `Cliente cadastrado`.
- O feedback acontece sem refresh da pagina e preserva o contexto atual do formulario da OS.

### v2.9.3 - Hardening do heartbeat de sessao para modais e salvamentos AJAX
- O `AuthFilter` passou a tratar `sessao/heartbeat` como fluxo especial, atualizando `last_activity` de forma controlada e liberando o lock da sessao logo apos a autenticacao.
- O endpoint `Sessao::heartbeat` agora fecha a sessao antes de responder o JSON, reduzindo a chance de filas e travamentos em ambiente com `FileHandler`.
- O `SessionMonitor` do frontend passou a segurar heartbeat enquanto houver `fetch` ou `$.ajax` same-origin em andamento e a abortar o request em 10 segundos quando a conexao nao responde.
- O objetivo dessa release e evitar `ERR_CONNECTION_TIMED_OUT` em modais operacionais durante concorrencia com heartbeat de sessao.

### v2.9.1 - Origem explicita dos valores consolidados das OS legadas
- A importacao legada passou a distinguir corretamente quando o valor veio de `os.mao_obra`, `os.total_servicos`, `os.total_produtos`, `os.subtotal` ou do total consolidado do cabecalho da OS antiga.
- O backfill sintetico foi endurecido para gerar itens rastreaveis mesmo quando o legado nao tinha discriminacao detalhada, mas mantinha apenas o total consolidado no cabecalho.
- A aba `Valores` da visualizacao da OS agora exibe um bloco `Origem do valor legado`, tornando explicito de onde saiu cada valor sintetico importado.

### v2.9.0 - Backfill completo dos detalhes das OS legadas
- A migracao legada deixou de importar apenas o cabecalho da OS e passou a preencher tambem laudos, solucoes aplicadas, observacoes internas, observacoes do cliente, aprovacao, forma de pagamento e composicao de itens.
- O pipeline passou a ler `orcamentos`, `orcamento_itens`, `servicos_orc`, `produtos_orc`, `historico_status_os`, `os_historico`, `os_defeitos` e `os_historicos`, sempre de forma idempotente e auditavel.
- Quando o legado nao possui itens discriminados, o importador cria itens sinteticos seguros para representar os totais de servicos e pecas, evitando OS com financeiro preenchido e composicao vazia.
- A visualizacao da OS passou a mostrar esses dados migrados nas abas `Itens / Servicos`, `Diagnostico` e `Valores`, incluindo notas legadas preservadas.

### v2.8.0 - Gestao visual de OS legado e busca global por numero antigo
- A listagem `/os` ganhou uma barra de origem com alternancia rapida entre `Todas as OS` e `Somente legado`, aplicando o filtro sem sair do contexto da operacao.
- O filtro legado passou a persistir em URL, localStorage, chips ativos e payload AJAX do DataTable.
- A busca global da navbar agora tambem consulta `numero_os_legado`, permitindo localizar ordens migradas diretamente pelo numero antigo.
- O catalogo de busca rapida do sistema ganhou a entrada `OS Legado`, apontando para `/os?legado=1`.

### v2.8.2 - Remocao dos cards redundantes na aba Informacoes
- A aba `Informacoes` da visualizacao da OS deixou de renderizar os cards `Cliente` e `Equipamento`, que estavam repetindo dados ja presentes no resumo superior.
- O conteudo principal da aba ficou concentrado apenas nos blocos complementares da ordem, reduzindo ruido visual e melhorando a leitura operacional.

### v2.8.1 - Limpeza da aba Informacoes e identificacao legada empilhada
- A aba `Informacoes` da visualizacao da OS deixou de repetir o nome do cliente e do equipamento dentro dos cards internos, mantendo esses nomes apenas no resumo superior da tela.
- A coluna `N OS` da listagem passou a empilhar o numero oficial, `Legado: ...` e `Origem: ...` em linhas distintas para ordens migradas, eliminando a leitura comprimida em linha unica.

### v2.7.4 - Consolidacao segura de clientes duplicados por CPF/CNPJ na migracao
- A migracao legada passou a consolidar clientes repetidos do banco `erp` quando compartilham o mesmo `CPF/CNPJ` valido.
- A consolidacao ganhou rastreabilidade em `legacy_import_aliases`, permitindo que varios `legacy_id` apontem para um cliente canonico sem perder auditoria.
- Isso impede que a carga falhe por chave unica de `cpf_cnpj` e evita duplicacao artificial de clientes no ERP novo.

### v2.7.5 - Hardening da consolidacao por documento ausente
- O normalizador da migracao deixou de considerar `CPF/CNPJ` ausente como identificador valido.
- Isso impede que clientes sem documento entrem por engano no fluxo de consolidacao segura por alias.
- A importacao continua consolidando apenas clientes com `CPF/CNPJ` valido e efetivamente presente no legado.

### v2.7.3 - Importacao resiliente de clientes legados sem telefone
- A migracao legada passou a tolerar clientes do banco `erp` sem telefone valido, sem interromper a carga.
- Quando o legado nao fornecer um `telefone1` utilizavel, o importador registra aviso operacional e grava string vazia no destino para respeitar a restricao `NOT NULL` da tabela `clientes`.
- O ajuste elimina a falha estrutural que abortava a carga real logo no inicio da etapa de clientes.

### v2.7.2 - Anti-duplicacao segura de equipamentos na migracao legada
- A migracao legada ganhou a tabela `legacy_import_aliases` para registrar aliases de equipamentos derivados do banco `erp` e apontar todos eles para um equipamento canonico quando houver identificador forte confiavel.
- O pipeline passou a deduplicar apenas por `numero_serie` ou `IMEI` validos, sem mesclagem heuristica por nome, marca, modelo ou semelhanca textual.
- Reprocessamentos continuam idempotentes, mas agora equipamentos repetidos em multiplas OS do legado podem convergir com seguranca para um unico cadastro no ERP novo.
- Quando um identificador forte colide com equipamento local sem rastreabilidade legada, a importacao nao mescla automaticamente e registra `skipped_conflict` para revisao.

### v2.7.1 - Adaptacao real para o banco `erp` e limpeza controlada da base atual
- A configuracao de migracao foi ajustada para o schema real do banco legado `erp`, usando `clientes` como origem de clientes e derivando snapshots de `equipamentos` a partir da tabela `os`.
- O preflight passou a concluir sem bloqueios no ambiente local, contabilizando `1295` clientes, `3560` equipamentos derivados e `3560` ordens de servico do legado.
- Telefones invalidos ou ausentes do legado passaram a ser tratados como aviso operacional, sem bloquear a carga.
- Foi adicionada a etapa de preparacao da base atual com os comandos:
  - `php spark legacy:prepare-target`
  - `php spark legacy:prepare-target --execute`
  - `php spark legacy:import --execute --wipe-target`
- A limpeza controlada apaga dados operacionais ficticios e uploads relacionados antes da migracao real, preservando usuarios, permissoes, configuracoes e catalogos estruturais.

### v2.7.0 - Migracao legada SQL com preflight, importacao e rastreabilidade
- Foi criada uma infraestrutura completa de migracao para importar `clientes`, `equipamentos` e `OS` diretamente de um banco SQL legado.
- O pipeline ganhou conexao secundaria dedicada, normalizacao de dados, resolucao de catalogos e comandos publicos:
  - `php spark legacy:preflight`
  - `php spark legacy:import --execute`
  - `php spark legacy:report`
- O modelo de dados passou a registrar `legacy_origem` e `legacy_id` nas entidades migradas, e `numero_os_legado` nas ordens de servico.
- A importacao ficou idempotente por `legacy_origem + legacy_id`, evitando duplicidade em reprocessamentos.
- A listagem `/os` e a visualizacao detalhada passaram a exibir e aceitar busca por `numero_os_legado`, preservando a transicao operacional entre sistemas.
- Foram adicionadas tabelas de auditoria (`legacy_import_runs` e `legacy_import_events`) para consolidar preflight, importacao, avisos e erros.

### v2.6.8 - Indicacao explicita de atraso no prazo da OS
- O badge de `Prazo` na listagem `/os` passou a mostrar ha quantos dias a ordem esta fora do prazo quando a previsao ja venceu e a OS ainda nao foi concluida.
- Quando a OS tiver sido entregue fora do prazo, o mesmo indicador passa a informar o atraso acumulado entre a previsao e a entrega.
- O objetivo e deixar o atraso operacional legivel sem exigir calculo manual pela equipe.

### v2.6.7 - Modal do cliente funcional na listagem e prazos sob regra operacional
- A consulta paginada da listagem `/os` voltou a carregar `cliente_id`, garantindo que clicar no nome do cliente abra corretamente a ficha completa com historico em modal embed.
- O modal `Atualizar prazos da OS` passou a operar somente sobre `previsao`, impedindo alteracoes indevidas em `data de entrada` e `data de entrega`.
- A data de entrega ficou explicitamente vinculada ao fluxo correto de status, e o modal agora exibe esses campos apenas para consulta operacional.
- A estrutura do modal de prazos foi reforcada com rolagem interna real, evitando que o rodape com `Salvar prazos` fique inacessivel em telas menores.

### v2.6.6 - Atalhos contextuais na listagem de OS para cliente, equipamento, datas e orcamento
- A listagem `/os` ganhou quatro pontos de entrada contextuais diretamente nas colunas operacionais da grade.
- Clicar em `Cliente` agora abre a ficha completa do cliente em modal embed, preservando a listagem no fundo.
- Clicar em `Equipamento` agora abre a ficha completa do equipamento em modal embed, com fotos e historico tecnico sem sair da tabela.
- Clicar em `Datas` abre um modal rapido para atualizar `data de entrada`, `previsao` e `entrega`.
- Clicar em `Valor Total` abre um modal de orcamento que gera nova versao do PDF e permite envio opcional ao cliente.
- As rotas AJAX e de embed foram alinhadas com permissao operacional de edicao para prazos/orcamento, e o controller de equipamento passou a suportar corretamente `?embed=1`.

### v2.6.5 - Modal de status da listagem com contexto de cliente e equipamento
- O modal de alteracao de status da listagem `/os` passou a exibir contexto operacional da OS no topo, sem depender de abrir a visualizacao completa.
- Foram adicionados dois blocos resumidos dentro do modal:
  - `Cliente`: nome, telefone e email
  - `Equipamento`: nome comercial, tipo, marca, modelo e numero de serie
- O endpoint `GET /os/status-meta/{id}` passou a devolver esses campos junto dos dados de status e workflow.

### v2.6.4 - Scroll restaurado no modal de status da listagem
- O modal de alteracao de status da listagem `/os` voltou a respeitar altura maxima de viewport e rolagem interna real.
- O formulario interno do modal passou a usar layout flexivel, permitindo que `modal-body` role sem esconder as acoes finais.
- O rodape com os botoes principais ficou ancorado visualmente, evitando perder o botao `Salvar status` em notebook e telas menores.

### v2.6.3 - Modal de status da listagem alinhado com a visualizacao da OS
- O numero da OS na listagem `/os` passou a ser clicavel e abre diretamente a tela de visualizacao da ordem.
- O modal de alteracao de status da listagem agora replica a mesma logica operacional da visualizacao da OS:
  - sugestao de `Proxima etapa`
  - atalho de `Cancelar`
  - seletor manual de destino permitido
  - observacoes da mudanca
  - opcao de notificar ou nao o cliente
- O modal tambem passou a exibir a timeline de `Historico e Progresso` e as ultimas movimentacoes da OS, sem exigir que o operador saia da listagem.
- `POST /os/status-ajax/{id}` ganhou controle explicito para evitar notificacao duplicada: o backend pode manter automacoes internas e enviar comunicacao manual ao cliente apenas quando solicitado.

### v2.6.2 - Acoes rapidas de status e retirada do Valor Final do topo da OS
- O card `Valor Final` foi removido do topo da visualizacao da OS, mantendo a consulta financeira concentrada na aba `Valores`.
- O card `Status` ganhou as acoes rapidas `Proxima etapa` e `Cancelar`, ambas usando um modal unico com campo de observacoes.
- A mudanca rapida de status passou a oferecer controle explicito sobre comunicar ou nao o cliente, sem duplicar automacoes de template.
- O status `cancelado` passou a ser permitido como destino direto em qualquer etapa do workflow.

### v2.6.1 - Historico e Progresso reposicionado para a lateral da OS
- O card `Historico e Progresso` da visualizacao `/os/visualizar/{id}` foi movido para a coluna lateral, imediatamente abaixo de `Fotos do Equipamento`.
- O bloco `Status` passou a ocupar sozinho a faixa principal logo abaixo das abas, reforcando a hierarquia operacional.
- O ajuste manteve a timeline vertical e o historico recente, mas com distribuicao visual mais coerente com a leitura da OS.

### v2.6.0 - Hierarquia da visualizacao da OS com progresso vertical
- A tela `/os/visualizar/{id}` foi reorganizada para seguir hierarquia operacional mais clara: abas da OS primeiro, depois `Status`, depois `Historico e Progresso`, e por fim `Documentos PDF` e `WhatsApp`.
- O antigo historico isolado foi consolidado em um card unico com timeline vertical de macrofases e historico recente da ordem.
- `Valor Final` permaneceu no topo direito e `Fotos do Equipamento` seguiram na coluna lateral, preservando leitura rapida da OS sem misturar operacao com navegacao principal.

### v2.5.9 - Versao visivel na tela de login
- A tela `/login` passou a exibir a versao atual do sistema no cabecalho do card de autenticacao.
- A exibicao usa `get_system_version()`, mantendo sincronia com `SystemRelease` e com eventual override em `configuracoes.sistema_versao`.
- O ajuste facilita suporte, diagnostico e confirmacao de release antes do usuario entrar no ERP.

### v2.5.8 - Fix do child row mobile para evitar texto empilhado na OS
- O modo mobile da listagem `/os` passou a tratar explicitamente a linha expandida com classe `os-responsive-child-row`.
- Regras de layout e ocultacao de pseudo-label foram aplicadas tanto para `tr.child` quanto para `tr.os-responsive-child-row`.
- Com isso, o bloco `Equipamento` no detalhe expandido volta a renderizar texto horizontal normal, sem empilhamento letra-a-letra.

### v2.5.7 - Correcao do detalhe de Equipamento no card mobile da OS
- O painel expandido (`+`) da listagem mobile deixou de comprimir o bloco `Equipamento` em largura insuficiente.
- O layout do detalhe mobile passou para empilhamento vertical de label/valor, evitando texto letra-a-letra.
- O pseudo-label residual (`Campo 1`) foi desativado na linha expandida mobile para manter leitura limpa.

### v2.5.6 - Cards mobile enxutos na listagem de OS
- A visualizacao mobile da listagem `/os` foi simplificada para mostrar apenas `Foto`, `N OS`, `Cliente` e `Modelo do equipamento` na face principal do card.
- O botao `+` passou a permanecer visivel no proprio card mobile para abrir os detalhes complementares.
- Informacoes secundarias (`Equipamento completo`, `Datas`, `Status`, `Relato`, `Valor Total` e `Acoes`) agora ficam no painel expandido, reduzindo ruido visual no celular.

### v2.5.5 - Hierarquia completa de recolhimento da grade de OS
- A protecao por overflow real da listagem `/os` foi estendida para as colunas `Datas` e `Equipamento`.
- A tabela agora segue uma hierarquia unica de recolhimento quando faltar largura util:
  - `Acoes`
  - `Relato`
  - `Status`
  - `Datas`
  - `Equipamento`
- Com isso, nenhuma dessas colunas permanece parcialmente visivel na borda direita; quando nao couberem, migram integralmente para o expansor `+`.

### v2.5.4 - Comportamento binario para Status e Relato na listagem de OS
- A protecao por overflow real da listagem `/os` foi expandida para as colunas `Relato` e `Status`.
- Quando a largura util do wrapper nao comportar a grade principal, a tabela passa a recolher `Acoes`, depois `Relato` e por ultimo `Status`, sempre movendo o conteudo integral para o expansor `+`.
- O ajuste elimina cortes parciais de badges e textos operacionais na borda direita da tabela.

### v2.5.3 - Comportamento binario da coluna Acoes na listagem de OS
- A listagem `/os` passou a validar a largura util real do wrapper da tabela depois de calcular as colunas visiveis.
- Se a coluna `Acoes` nao couber integralmente na grade principal, ela e ocultada por completo e migra para o painel expansivel `+`.
- O ajuste impede a exibicao parcial de botoes na borda direita da tabela e preserva a usabilidade sem depender de scroll lateral.

### v2.5.2 - Refino de largura das colunas N OS e Cliente
- A coluna `N OS` foi encurtada para usar apenas a largura minima segura do numero completo da ordem.
- A coluna `Cliente` tambem foi reduzida levemente para equilibrar melhor a distribuicao horizontal da grade.
- O ajuste preserva a quebra semantica do nome em duas linhas quando houver quatro palavras ou mais.

### v2.5.1 - Ajuste de largura das colunas e quebra semantica do cliente na OS
- As colunas `Status` e `Valor Total` da listagem `/os` foram compactadas para consumir apenas a largura necessaria.
- A coluna `Cliente` passou a quebrar o nome em duas linhas a partir da segunda palavra quando o cadastro possui quatro palavras ou mais.
- O ajuste devolve espaco util para leitura sem reintroduzir scroll horizontal como estrategia principal.

### v2.5.1 - Checklist de entrada com fallback automatico + correcoes visuais de acessorios
- Corrigido o fluxo do modal de checklist na OS para nao ficar sem verificacoes quando o tipo de equipamento ainda nao possuia modelo ativo.
- `ChecklistService` passou a criar automaticamente modelo e itens padrao do `Checklist de Entrada` para tipos novos, mantendo o preenchimento imediato.
- Ajustado o layout das cores rapidas dos acessorios para quebrar linha dentro do card, evitando estouro horizontal e cor fora das margens.

### v2.5.0 - Coluna de fotos e visualizador da listagem de OS
- A listagem `/os` ganhou a coluna `Foto` no inicio da grade operacional, exibindo a miniatura principal do equipamento.
- Clicar na miniatura abre um visualizador com duas abas: `Fotos do Equipamento` e `Fotos da Abertura`, sem sair da listagem.
- Foi criado o endpoint `GET /os/fotos/{id}` para carregar as galerias por AJAX e manter a tabela reativa.

### v2.4.2 - Sidebar recolhida por hover e tabela sem rolagem lateral na OS
- Na rota `/os`, a sidebar passa a iniciar recolhida automaticamente em desktop e notebook, expandindo por hover/foco sem consumir novamente a largura do conteudo principal.
- A tabela ficou ainda mais agressiva na redistribuicao de colunas, movendo `Valor Total`, `Relato` e `Acoes` para o expansor `+` mais cedo quando a largura util do card apertar.
- As larguras-base das colunas foram recalibradas para reduzir espacos excessivos e evitar dependencia de rolagem horizontal na listagem.

### v2.4.1 - Correcao do motor responsivo da listagem de OS
- A responsividade da tabela `/os` passou a calcular o breakpoint pela largura util real do card/listagem, em vez de se orientar apenas pela largura total da janela.
- Em notebook e tablet, a ocultacao de colunas ficou mais agressiva para evitar sobreposicao entre `N OS`, `Cliente`, `Equipamento`, `Datas` e `Status`.
- A tabela deixa de comprimir o conteudo ate sobrepor texto e passa a priorizar legibilidade, empurrando campos secundarios para o painel expansivel da linha.

### v2.4.0 - Responsividade agressiva da tela de Ordens de Servico
- A tela `/os` recebeu reestruturacao completa por breakpoint, com comportamento definido para desktop amplo, desktop, notebook, tablet e mobile.
- A listagem passou a manter tabela em desktop/tablet e converter as linhas em cards apenas abaixo de `768px`, preservando leitura e acoes confortaveis em celular.
- Sidebar, header, filtros, paginacao e tabela foram recalibrados para usar a largura util real da viewport sem gerar estouro horizontal em notebook a 100% de zoom.

### v2.3.0 - Workflow configuravel de status e nova listagem operacional de OS
- A listagem `/os` ganhou colunas operacionais ricas para `Equipamento`, `Relato`, `Datas` e `Status`, com leitura mais densa sem esconder contexto critico.
- O badge de status passou a abrir modal de alteracao por AJAX, carregando apenas os destinos permitidos para aquela OS.
- Foi criada a tela `Gestao de Conhecimento -> Fluxo de Trabalho OS`, permitindo configurar ordem, flags e transicoes entre status diretamente pelo ERP.

### v2.2.14 - Timeout configuravel e aviso explicito de sessao expirada
- Adicionada a configuracao `sessao_inatividade_minutos` em `Configuracoes -> Sessao e Seguranca`.
- O timeout de inatividade passou a usar uma unica fonte de verdade no backend, eliminando a divergencia entre filtro de autenticacao e sessao tecnica.
- O frontend protegido ganhou monitor global com heartbeat por atividade e alerta SweetAlert2 quando a sessao expira, inclusive em telas embed/modal.

### v1.0.0 - Base ERP operacional
- Fundacao do ERP com autenticacao, permissoes e layout administrativo.
- Modulos base operacionais (OS, clientes, equipamentos, servicos, estoque e financeiro).
- Estrutura inicial de banco e dashboards base.

### v1.1.0 - OS + PDF + WhatsApp base
- Evolucao do fluxo de Ordem de Servico.
- Estruturacao inicial de envio de comunicacoes e documentos PDF.
- Fundacao tecnica para integracao de atendimento por WhatsApp.

### v1.2.0 - Busca e produtividade
- Busca global e melhorias de navegacao para rotinas de atendimento.
- Otimizacoes de selecao e cadastro em fluxos criticos.
- Reducao de atrito operacional em telas de cadastro/consulta.

### v1.3.0 - Padronizacao visual e UX
- Evolucao do design system com padronizacao global de componentes.
- Melhorias de consistencia visual entre modulos.
- Base para evolucoes SaaS-like de UI.

### v1.4.0 - Central de Mensagens unificada
- Remocao do legado Whaticket e consolidacao do modulo nativo.
- Central de atendimento integrada ao ERP.
- Inicio da fase de estabilizacao operacional do novo modulo.

### v1.5.0 - CRM + Contatos integrados
- Integracao entre CRM e Central de Mensagens.
- Introducao da agenda de contatos separada de clientes ERP.
- Regras de lifecycle comercial (lead e conversao) no contexto de atendimento.

### v1.6.0 - Automacao e governanca de atendimento
- Expansao de chatbot, respostas rapidas e templates.
- Melhorias em filas, atribuicao e contexto de conversa.
- Avancos em fluxo bot/humano e regras operacionais.

### v1.7.0 - Midias e fluxo de fotos estabilizados
- Correcoes de upload, preview e sincronizacao de fotos.
- Melhorias em crop/camera/galerias em OS e equipamentos.
- Ajustes para reduzir regressao visual em modais e telas densas.

### v1.8.0 - Observabilidade e diagnostico rapido
- Padronizacao de codigos de erro no backend da Central.
- Melhorias de observabilidade por endpoint e diagnostico operacional.
- Estabilizacao de polling, filtros e comportamento de conversa.

### v2.0.0 - Maturidade de plataforma
- Consolidacao de deploy/documentacao operacional de VPS.
- Hardening inicial de processos de publicacao e recuperacao.
- Padrao global de responsividade ultra compatibilidade aplicado como diretriz de sistema.

### v2.2.13 - Obrigatoriedade de cor e foto no cadastro de equipamento
- Implementada a validacao obrigatoria de `Cor` e `Foto de Perfil` ao cadastrar novo equipamento via modal na OS.
- O sistema agora redireciona automaticamente para a aba e campo pendentes (Info, Cor ou Foto) antes de permitir salvar o registro.
- A cor inicial do cadastro foi resetada para "Nao selecionada" para forçar a identificacao visual correta pelo usuario.

### v2.2.15 - Hotfix de empilhamento de alertas no Checklist da OS
- O aviso `Checklist incompleto` e demais alertas de validacao passaram a calcular `z-index` dinamicamente com base na pilha ativa de `modals + backdrops`.
- O SweetAlert2 agora abre acima do modal de checklist, sem ficar oculto durante o salvamento.
- O ajuste foi aplicado no helper central de avisos da view de OS para manter consistencia em outros avisos tecnicos do mesmo fluxo.

### v2.2.16 - Clareza de status do Checklist de Entrada (DS)
- O bloco de checklist da aba `Equipamento` passou a mostrar um card de status com titulo e texto de apoio mais claros para o operador.
- Estados padronizados: `Aguardando equipamento`, `Checklist pendente de preenchimento`, `Checklist concluido: tudo OK`, `Checklist concluido com discrepancias` e `Checklist indisponivel`.
- O badge rapido foi simplificado para termos curtos (`Pendente`, `Tudo OK`, `N discrepancias`) mantendo consistencia visual com o design system.

### v2.2.17 - Ajuste de bordas dos cards internos da aba Equipamento
- Aplicado hardening de box-model nos wrappers internos (`border rounded-3/rounded`) para impedir extrapolacao de borda fora dos limites do card pai.
- Checklist de entrada, acessorios/componentes e blocos similares passaram a respeitar largura maxima do container com overflow controlado.
- O ajuste foi feito no CSS do design system da OS, mantendo o visual atual sem alterar regras de negocio.

### v2.2.12 - Reorganizacao de fluxo: Nova aba 'Defeito' na OS
- Criada a nova aba `Defeito` posicionada logo apos a aba `Equipamento`.
- O campo `Relato do cliente` e o seletor `Tecnico Responsavel` foram movidos da aba `Equipamento` para a nova aba `Defeito`.
- A aba `Equipamento` agora fica focada apenas no cadastro do item, acessorios e estado fisico na entrada.
- A aba `Execucao da OS` continua dedicada ao andamento operacional (prioridade, datas e status).

### v2.2.11 - Validacao final e fulltext do relato na listagem de OS
- A busca global `q` da listagem de OS passou a usar `FULLTEXT` em `os.relato_cliente` quando entra no fallback textual.
- A resolucao de busca por equipamento foi ajustada para nao manter ramos desnecessarios quando o termo bate apenas em marca ou apenas em modelo.
- A estrategia foi validada com massa sintetica de `50.000` OS, mantendo tempos medianos dentro da faixa alvo nos cenarios testados.

### v2.2.10 - Hardening da paginacao e busca global da listagem de OS
- A listagem `POST /os/datatable` passou a paginar por IDs ordenados e carregar os detalhes da grade apenas para a pagina atual.
- A busca global `q` deixou de depender de joins pesados na consulta principal e passou a priorizar numero de OS, cliente, equipamento e tecnico via subconsultas indexadas.
- Novos indices foram adicionados para catálogos de lookup e para os caminhos cronologicos por `cliente_id` e `equipamento_id`.

### v2.2.9 - Otimizacao da listagem de OS para alto volume
- A listagem `POST /os/datatable` passou a separar contagem total, contagem filtrada e consulta paginada com builders mais enxutos.
- Filtros de data, valor e tipo de servico foram refatorados para preservar melhor uso de indice no banco.
- Nova migration adicionou indices compostos para `data_abertura`, `status`, `estado_fluxo`, `tecnico_id`, `valor_final` e busca por tipo de servico em `os_itens`.

### v2.2.6 - Fundo branco nas superficies da Nova OS
- As superficies editaveis da `Nova Ordem de Servico` passaram a usar fundo branco como base visual.
- A borda suave `#e2e8f0` e o destaque de foco por secao foram preservados para manter hierarquia sem pesar na interface.
- A mudanca foi exclusivamente visual no CSS do modulo.

### v2.2.8 - Relatos rapidos agrupados por categoria
- Os relatos rapidos da entrada da OS voltaram a ser exibidos em dropdowns por categoria.
- A interacao continua a mesma: escolher o item e inserir o texto no `Relato do cliente`.
- O agrupamento reduz poluicao visual quando existem muitos defeitos/opcoes cadastradas.

### v2.2.7 - Alinhamento da Nova OS ao design system
- A pagina `Nova Ordem de Servico` teve a camada visual consolidada com classes do design system, reduzindo dependencia de estilos inline na area visivel.
- Sidebar, shell principal, painel de fotos, resumo lateral e titulos auxiliares passaram a seguir o mesmo vocabulário visual do DS.
- Os relatos rapidos visiveis passaram a seguir o padrao direto de botoes pequenos do sistema.

### v2.2.5 - Paleta azul/cinza suave na Nova OS
- As superficies editaveis da `Nova Ordem de Servico` foram ajustadas para a base `#f8fafc` com borda `#e2e8f0` e raio `16px`.
- O estado ativo da secao foi mantido com foco visual suave, preservando a leitura premium da interface.
- A mudanca foi exclusivamente visual no CSS do modulo.

### v2.2.10 - Fotos de acessorios com fluxo unificado na Nova OS
- O upload de fotos de acessorios da aba `Equipamento` passou a usar o fluxo padrao de `Galeria + Camera + corte` antes do anexo.
- A persistencia no backend foi reforcada para ler arquivos por `UploadedFile` (CI4), mantendo mapeamento por item de acessorio.
- O salvamento agora garante pasta por OS em `public/uploads/acessorios/<numero_os>/` e nome sequencial por tipo (`<tipo>_01`, `<tipo>_02`, ...).
- O bloco legado do formulario foi isolado para evitar conflito de campos/IDs com o fluxo principal.

### v2.2.4 - Paleta amarelo suave na Nova OS
- As superficies editaveis da tela `Nova Ordem de Servico` foram ajustadas da paleta clara neutra para amarelo suave.
- O destaque do bloco ativo foi preservado, mantendo foco visual por `:focus-within` e leitura premium dos campos.
- Nenhuma logica de formulario foi alterada; a mudanca e exclusivamente visual no CSS do modulo.

### v2.2.3 - Superficies editaveis destacadas na Nova OS
- Todas as areas de preenchimento da tela `Nova Ordem de Servico` passaram a usar fundo suave, borda discreta, cantos arredondados e contraste leve com o fundo externo.
- A secao em edicao agora recebe destaque visual automatico via `:focus-within`, deixando claro qual bloco esta ativo sem alterar a logica do formulario.
- Campos internos e Select2 foram harmonizados com fundo branco e foco azul suave para manter leitura profissional e padrao SaaS.

### v2.2.1 - Ajuste fino das abas do modal Nova OS
- Aba `Equipamento` passou a concentrar `Relato do cliente` no topo e `Tecnico Responsavel` ao final, sem alterar regras de negocio.
- Aba `Relato + Execucao` foi renomeada para `Execucao da OS`, ficando focada apenas em prioridade, datas, status e defeitos comuns da edicao.
- Mapa de validacao do formulario foi ajustado para abrir a aba correta quando faltarem relato ou tecnico.

### v2.2.0 - Refatoracao premium do modal Nova OS
- Modal `Nova Ordem de Servico` reorganizado em abas funcionais: `Cliente`, `Equipamento`, `Relato + Execucao`, `Fotos` e `Pecas e Orcamento` (quando edicao).
- Card inteligente do cliente com nome, telefone e endereco sincronizados em tempo real apos criacao/edicao via AJAX.
- Select2 single-line consolidado com truncamento responsivo e layout estavel para nomes longos em modais e formularios.
- Estilos da OS movidos para arquivo dedicado do design system (`os-form-layout.css`), reduzindo CSS inline na view.

### v2.1.0 - Dashboard responsivo + modais de OS + versao no rodape
- Refatoracao do dashboard com foco mobile/tablet/desktop.
- KPI atualizado para "Equipamento Entregue".
- Grafico principal alterado para "OS abertas por mes".
- Resumo financeiro convertido para barras horizontais.
- "Ultimas OS" com visualizacao e nova OS em modal (sem redirecionamento).
- Controle de versao exibido no rodape, sincronizado via `SystemRelease`.

## Como decidir o proximo numero de versao

- Exemplo 1: adicionou funcionalidade nova sem quebrar fluxo existente -> sobe `MINOR` (`2.1.0` -> `2.2.0`).
- Exemplo 2: corrigiu bug sem alterar contrato funcional -> sobe `PATCH` (`2.1.0` -> `2.1.1`).
- Exemplo 3: alterou contrato/estrutura com impacto de compatibilidade -> sobe `MAJOR` (`2.1.0` -> `3.0.0`).
