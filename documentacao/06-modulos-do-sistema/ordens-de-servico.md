# Modulo: Ordens de Servico

## Objetivo

O modulo de `Ordens de Servico` controla o ciclo completo do atendimento tecnico:

- recepcao;
- diagnostico;
- orcamento;
- execucao;
- qualidade;
- encerramento.

## Nucleo tecnico

### Camada principal

- controller: `app/Controllers/Os.php`
- model: `app/Models/OsModel.php`
- service de fluxo: `app/Services/OsStatusFlowService.php`
- service de PDF: `app/Services/OsPdfService.php`

### Estado operacional

Base conceitual:

- `os_status`
- `os_status_transicoes`
- `os_status_historico`

Campo operacional:

- `os.estado_fluxo`

Estados esperados:

- `em_atendimento`
- `em_execucao`
- `pausado`
- `pronto`
- `encerrado`
- `cancelado`

## Listagem `/os`

### Correcao de 2026-04-27

- `public/assets/js/os-list-filters.js` agora evita segunda inicializacao da mesma instancia de DataTables ao reutilizar `#osTable`;
- a grade server-side passou a inicializar com `retrieve: true`, absorvendo cenarios em que a tabela ja foi preparada antes do bootstrap completo da tela;
- o include da listagem ganhou querystring baseada em `filemtime` para invalidar cache antigo do navegador em `assets/js/os-list-filters.js`;
- o frontend intercepta especificamente o warning de reinicializacao da `osTable`, registrando no console sem abrir alerta modal para o usuario;
- o bootstrap jQuery/DataTables agora reutiliza defensivamente a instancia existente de `#osTable` mesmo quando outra rotina tocar a grade depois da carga inicial;
- o modal de status passou a normalizar textos renderizados dinamicamente apos hidratar timeline, historico e painel de orcamento;
- os textos fixos do modal `Alterar status da OS` e do embed de orcamento tambem foram normalizados em pt-BR, incluindo `Nº de serie`, abas, cards e mensagens de apoio;
- `app/Views/os/index.php` teve os principais labels e placeholders da listagem normalizados novamente em pt-BR;
- `public/assets/js/os-list-filters.js` passou a apontar para os IDs corretos de e-mail no contexto do modal, evitando hidratacao incompleta do resumo lateral;
- o ajuste preserva o fluxo AJAX server-side existente e atua apenas como hardening de bootstrap da grade.

### Restauro tecnico da release 2.16.5

Na release `2.16.5`, o modulo passou por uma recuperacao estrutural apos uma substituicao ampla de texto ter corrompido fallbacks `??`, nomes de metodos e trechos de bootstrap de dados:

- `app/Controllers/Os.php` voltou a compilar apos a restauracao de metodos de e-mail, validacoes auxiliares e fallbacks de arrays/inteiros;
- `app/Views/os/index.php`, `app/Views/os/form.php` e `app/Views/os/show.php` voltaram a compilar apos a correcao de ternarios quebrados e valores padrao mutilados;
- a validacao minima voltou a ser feita com `php -l` nesses arquivos antes de qualquer nova rodada de limpeza textual;
- depois do restauro sintatico, o modulo recebeu nova normalizacao de labels e mensagens em pt-BR.

### Responsabilidades

- busca global;
- filtros avancados;
- paginacao server-side;
- leitura combinada de OS, cliente, equipamento, prazo e orcamento.

### Ajuste rapido de prazos na listagem

Fluxo tecnico atual do modal `Atualizar prazos da OS`:

- payload de contexto: `GET /os/prazos-meta/{osId}`;
- submissao AJAX: `POST /os/prazos-ajax/{osId}`;
- view do modal: `app/Views/os/index.php`;
- JavaScript principal: `public/assets/js/os-list-filters.js`;
- controller de persistencia: `app/Controllers/Os.php::updateDatesAjax()`.

Regra consolidada na release `2.16.34`:

- o frontend passou a exibir o campo `motivo_alteracao` diretamente no modal;
- quando `requires_admin_approval = true` vier no payload, o modal passa a mostrar `admin_usuario` e `admin_senha`;
- o JavaScript envia `data_entrada`, `data_previsao`, `data_entrega`, `motivo_alteracao` e, quando necessario, as credenciais do administrador;
- o backend passou a usar `data_entrada` e `data_entrega` atuais da OS como fallback, reduzindo fragilidade caso algum campo nao viaje no POST.

### Escopo operacional da listagem

- a abertura da tela aplica implicitamente o recorte de OS abertas definido em `OsStatusFlowService::getListOpenStatusCodes()`;
- o multiselect `Ordens abertas` mostra apenas esse subconjunto de etapas;
- o dropdown `Ordens fechadas` trabalha com `entregue_reparado`, `devolvido_sem_reparo` e `descartado`;
- o seletor avancado `Status geral` envia `status_scope=all`, removendo o recorte padrao e liberando a consulta conjunta de abertas + fechadas;
- o reset manual por `Limpar` ou `Limpar todos` volta ao estado inicial da fila aberta e limpa apenas os filtros aplicados;
- a UI dessa leitura operacional fica concentrada em `app/Views/os/index.php`, `public/assets/js/os-list-filters.js` e `public/assets/css/design-system/layouts/os-list-layout.css`.

### Comportamento atual da coluna de status

A listagem consolida o contexto da OS com o orcamento mais recente vinculado.

Hoje a celula pode mostrar:

- status principal da OS;
- estado de fluxo;
- status do orcamento;
- numero do orcamento.

### Sincronizacao com orcamento

Regra tecnica consolidada:

- orcamento em ciclo comercial ativo vinculado a OS:
  - OS sugerida/atualizada para `aguardando_autorizacao`;
- orcamento `reenviar_orcamento`:
  - a OS volta para `aguardando_autorizacao`, mesmo que o registro ja tenha sido aprovado antes, porque existe nova rodada de validacao com o cliente;
- orcamento `aprovado` ou `convertido`:
  - OS sugerida/atualizada para `aguardando_reparo`.
- orcamento `rejeitado` ou `cancelado`:
  - a OS e sincronizada para `cancelado`.

Regra complementar obrigatoria:

- essa sincronizacao automatica nao pode rebaixar uma OS que ja avancou manualmente para etapas posteriores do reparo, como `reparo_execucao`, `aguardando_peca`, `testes_operacionais`, `testes_finais`, `reparo_concluido` e similares;
- o orcamento continua definindo o ponto de entrada do fluxo tecnico, mas nao sobrescreve fases mais avancadas ja confirmadas pela equipe;
- na DataTable `/os`, o status principal renderizado deve sempre refletir `os.status` e `os.estado_fluxo` reais; o status do orcamento permanece apenas como badge auxiliar e nao pode substituir visualmente a OS.

Excecao intencional da release `2.16.18`:

- quando o orcamento entra em `reenviar_orcamento`, `rejeitado` ou `cancelado`, a sincronizacao pode forcar o retorno para `aguardando_autorizacao` ou `cancelado` mesmo que a OS ja tenha avancado antes, porque nesses casos o documento comercial foi reaberto ou encerrado e a OS precisa refletir esse novo bloqueio operacional.

Ajuste pratico consolidado na release `2.16.25`:

- quando a OS ja estiver `cancelado` e o orcamento for reenviado ao cliente, a sincronizacao tambem pode reabrir a OS ao detectar estados comerciais ativos como `pendente_envio`, `enviado`, `aguardando_resposta`, `reenviar_orcamento`, `aguardando_pacote` ou `pendente`;
- com isso, a OS deixa o estado `cancelado` e volta para `aguardando_autorizacao` assim que uma nova rodada comercial valida e ativa for colocada novamente em circulacao.
- na release `2.16.26`, o mesmo comportamento passou a cobrir a aprovacao final da rodada revisada: se o orcamento aprovado ainda encontrar a OS em `cancelado`, a sincronizacao remove esse bloqueio e leva a OS para `aguardando_reparo`.
- na release `2.16.28`, esse mesmo ciclo passou a valer tambem para orcamentos originalmente `convertidos` que sofrerem edicao: ao serem alterados, eles retornam para `reenviar_orcamento` e a OS volta a depender de autorizacao do cliente no mesmo fluxo comercial.
- na release `2.16.29`, depois do disparo real ao cliente, o orcamento revisado deixa `reenviar_orcamento` e passa para `aguardando_resposta` com label `Aguardando aprovacao`, preservando a leitura de que a OS segue esperando autorizacao.

Tambem foi aplicado fallback de valor:

- se `os.valor_final` estiver vazio, a listagem pode usar o total do orcamento vinculado mais recente.

### Atualizacao dinamica do status comercial

Na release `2.15.17`, o badge comercial do orcamento deixou de depender de refresh manual da pagina.

Fluxo tecnico atual:

- o cliente responde o orcamento pelo link publico;
- `app/Controllers/Orcamento.php` cria a notificacao interna `orcamento.public_status_changed`;
- `app/Views/layouts/navbar.php` exibe o sino de notificacoes ao lado do perfil;
- `public/assets/js/navbar-notifications.js` consome o feed inicial e o stream SSE autenticado;
- o mesmo script publica `window.dispatchEvent(new CustomEvent('erp:notification', ...))`;
- `public/assets/js/os-list-filters.js` escuta esse evento e chama `window.osListController.reload(true)`.

Resultado operacional:

- a listagem `/os` recarrega automaticamente;
- o badge comercial do orcamento na coluna `Status` volta sincronizado sem `F5`;
- se o modal `Alterar status da OS` estiver aberto para a mesma ordem, o frontend reidrata tambem esse contexto;
- ao clicar na notificacao, a navegacao agora respeita o contexto real do ERP mesmo quando a instalacao usa `index.php` ou subdiretorio.

## Modal `Nova OS` na listagem

### Estrutura

- view: `app/Views/os/index.php`
- abertura por `iframe` dentro do modal `#osCreateModal`

### Regra operacional atual

O modal foi protegido para evitar perda acidental de preenchimento:

- `data-bs-backdrop="static"`
- `data-bs-keyboard="false"`
- fechamento manual apenas pelo botao `X`
- confirmacao obrigatoria ao fechar

Mensagem exibida:

- existe um registro de ordem de servico em andamento;

## Edicao rapida do cliente dentro da OS

Nas releases `2.16.22` e `2.16.23`, a aba `Cliente` do formulario de OS passou a manter e estabilizar um botao dedicado `Editar` ao lado do seletor principal de cliente.

Comportamento operacional:

- o botao fica visivel para perfis com permissao `clientes:editar`;
- sem cliente selecionado, ele aparece desabilitado para manter a descoberta da acao sem causar erro;
- com cliente selecionado, o clique abre imediatamente o modal rapido de cliente dentro do proprio formulario da OS;
- a hidratacao detalhada do cliente usa `GET /clientes/json-edicao/{id}`, rota liberada para o mesmo perfil com permissao de editar;
- se a leitura detalhada falhar, o modal continua aberto com aviso contextual, em vez de bloquear silenciosamente a acao;
- na release `2.16.24`, o JavaScript da aba deixou de quebrar durante a sincronizacao do checklist de entrada, o que restaurou a inicializacao do botao `Editar` no fluxo real da tela;
- o fechamento do modal de cliente tambem passou a controlar o foco explicitamente, evitando o warning de acessibilidade ligado a `aria-hidden` no Bootstrap;
- ao salvar a alteracao, o nome do Select2, o card `Resumo do cliente selecionado` e o estado atual da OS sao sincronizados no mesmo contexto, sem reload da pagina.
- o preenchimento nao salvo sera perdido.
- o SweetAlert2 de confirmacao agora recebe promocao de camada e fica acima do modal iframe e do respectivo backdrop.

## Modal de status na listagem

### Estrutura atual

- view base: `app/Views/os/index.php`
- payload AJAX: `GET /os/status-meta/{osId}`
- submissao AJAX: `POST /os/status-ajax/{osId}`
- frontend principal: `public/assets/js/os-list-filters.js`
- layout responsivo: `public/assets/css/design-system/layouts/os-list-layout.css`

### Abas operacionais atuais

O modal `Alterar status da OS` passou a ser composto por:

- cabecalho com `Alterar status da OS #OS...`;
- resumo superior com cliente, equipamento e badges atuais;
- painel esquerdo com `3 abas internas`;
- aba `Acoes rapidas`, com `Status atual da OS`, `Fluxo normal sugerido` e `Fluxo selecionado`;
- aba `Solucao e diagnostico`, com `procedimentos_executados`, `solucao_aplicada` e `diagnostico_tecnico`;
- aba `Gerenciamento do Orcamento`, renderizada pelo mesmo resumo reutilizado da aba `Pecas e Orcamento`;
- coluna lateral de `Historico e progresso`.
- os labels visiveis desse modal foram normalizados em pt-BR no frontend, cobrindo tabs, hints, placeholders e blocos de historico.

### Persistencia do card tecnico

Ao salvar o modal de status:

- o backend continua aplicando a transicao via `OsStatusFlowService::applyStatus()`;
- no mesmo `POST /os/status-ajax/{id}`, o modal agora tambem envia `procedimentos_executados`, `solucao_aplicada` e `diagnostico_tecnico`;
- esses campos sao normalizados em `Os::normalizeNullableString()` e persistidos em `os.procedimentos_executados`, `os.solucao_aplicada` e `os.diagnostico_tecnico`.

### Orcamento embutido e sincronizacao

- o resumo de orcamento do modal usa `view('os/partials/orcamento_editor_panel', [..., 'orcamentoContext' => 'status_modal'])`;
- os botoes do card passaram a expor `data-os-frame-modal-url`, abrindo `Criar`, `Editar` ou `Visualizar orcamento` no iframe modal da listagem;
- na release `2.16.27`, orcamentos com status `convertido` deixaram de ser tratados como bloqueados no card embutido, mantendo a acao `Editar orcamento` disponivel no mesmo fluxo da OS;
- o helper `bindIframeModal()` da listagem agora promove esse iframe modal para uma camada acima do `#osStatusModal`, ajustando modal + backdrop para evitar que o orcamento fique atras da troca de status;
- quando o orcamento e salvo em modo embed, o `postMessage` `os:orcamento-updated` faz a listagem recarregar e hidrata novamente o contexto do modal de status, preservando o rascunho local quando possivel;
- quando o cliente responde esse mesmo orcamento pelo link publico, a reidratacao tambem pode acontecer via notificacao em tempo real da navbar.

## Formulario de OS

### View principal

- `app/Views/os/form.php`

### Validacao de salvamento

- o frontend do formulario nao deve bloquear a edicao da OS quando `tecnico_id` estiver vazio;
- `Tecnico Responsavel` permanece opcional na interface e no backend;
- a validacao obrigatoria de salvamento fica restrita aos campos realmente mandatorios do fluxo, como cliente, equipamento, data de entrada e relato do cliente;
- o backend de `Fotos de Entrada` na abertura e na edicao usa um persistidor centralizado em `Os::persistEntryPhotosFromRequest()` para aceitar `fotos_entrada` e `fotos_entrada[]`, criar `public/uploads/os_anormalidades` quando necessario e registrar warning sem perder o restante do salvamento se houver falha de caminho;
- na edicao, o select `Status` agora usa a arvore completa de status operacionais ativos cadastrados, permitindo ajuste administrativo fora da sequencia curta do fluxo;
- `data_entrada` e `data_previsao` passam por normalizacao e validacao no backend antes do `update`, impedindo previsao anterior a entrada.
- a normalizacao textual do formulario agora tambem cobre pp/Views/os/form.php, com revisao de labels, placeholders, mensagens de checklist, avisos de camera, resumo lateral e textos operacionais em pt-BR/UTF-8.

### Areas centrais

- `Cliente`
- `Equipamento`
- `Defeito`
- `Dados Operacionais`
- `Fotos`
- `Solucao` (na edicao)

### Selecao rica de equipamento

O seletor de equipamento passou a operar com contexto expandido:

- foto de perfil;
- tipo;
- marca;
- modelo;
- cor;
- numero de serie/IMEI.

### Regra de anti-cache para fotos da OS

Na view `app/Views/os/form.php`, o helper frontend `withFotoVersion()` e usado para anexar `?v=timestamp` somente em URLs reais de foto.

Regra tecnica consolidada:

- caminhos HTTP/relativos continuam recebendo versionamento para evitar cache visual;
- fallbacks inline em `data:` devem ser preservados sem alteracao;
- previews temporarios em `blob:` tambem devem ser preservados sem query string adicional.

### Fotos de entrada persistidas

Na aba `Fotos` da edicao:

- miniaturas persistidas agora recebem botao proprio de exclusao;
- a remocao chama `POST /os/fotos-entrada/excluir/{fotoId}`;
- o backend remove o registro de `os_fotos` e tambem o arquivo fisico em `public/uploads/os_anormalidades`;
- a grade de fotos e os contadores da tela sao atualizados no frontend sem exigir reload da OS.

### Tabela principal da listagem `/os`

O dimensionamento da grade operacional da listagem combina HTML simples em `app/Views/os/index.php` com duas camadas de comportamento:

- CSS de larguras e breakpoints em `public/assets/css/design-system/layouts/os-list-layout.css`;
- logica de visibilidade e autoajuste em `public/assets/js/os-list-filters.js`.

Na configuracao atual:

- `Foto` foi reduzida para acompanhar a thumbnail principal;
- `N OS` usa largura fixa por caracteres (`ch`) para seguir o numero operacional;
- `Cliente` usa preview backend com ate `3 linhas` de `3 palavras`, segue a maior linha efetivamente renderizada na pagina atual, recebeu reducao da folga direita por ajuste conjunto de `padding-right` e `paddingOffset` e agora centraliza o texto dentro da celula clicavel;
- `Equipamento` deixou de usar largura fixa e agora combina `Tipo`, `Marca` e `Modelo` com medicao no frontend baseada na maior palavra operacional visivel da pagina;
- `Valor Total` continua autoajustada pela maior celula da pagina atual via medicao no frontend;
- `Relato` deixou de renderizar o texto integral na grade e passou a exibir um preview de ate `9 palavras` distribuidas em `3 linhas` de `3 palavras`, com tooltip nativo no hover para leitura completa.

### Dados operacionais na edicao

Na aba `Dados Operacionais` da edicao:

- o select `Status` usa a arvore completa de status agrupados vinda de `OsStatusFlowService::getStatusGrouped()`, em vez de limitar o operador apenas as proximas transicoes;
- o campo persistido continua sendo `os.data_previsao`;
- o select auxiliar `Prazo (dias)` nao grava coluna propria: ele e recalculado a partir da diferenca entre `data_entrada` e `data_previsao`;
- quando a diferenca salva nao coincidir com os atalhos padrao (`1`, `3`, `7`, `30`), a interface cria uma opcao dinamica para refletir exatamente o prazo ja salvo ao reabrir a OS.

### Aba `Pecas e Orcamento` na edicao

O antigo card estatico foi substituido por um painel reativo baseado no orcamento vinculado:

- renderizacao principal em `app/Views/os/partials/orcamento_editor_panel.php`;
- refresh parcial por `GET /os/orcamento-resumo/{osId}`;
- listagem agrupada por tipo de item usando `Os::summarizeOrcamentoItems()`;
- botoes contextuais para `Criar orcamento`, `Lancar itens no orcamento`, `Visualizar orcamento` e `Editar orcamento`;
- abertura em iframe modal no mesmo padrao estrutural da `Nova OS` da listagem.

### Sincronizacao do modal de orcamento

Quando o orcamento e salvo em modo embed:

- `app/Views/orcamentos/show.php` envia `postMessage` com o evento `os:orcamento-updated`;
- `app/Views/os/form.php` escuta esse evento;
- a aba `Pecas e Orcamento` busca novamente o HTML do resumo e atualiza so o bloco afetado, preservando o restante do formulario da OS.

## Visualizacao da OS

### View principal

- `app/Views/os/show.php`

### Estrutura atual

- coluna lateral:
  - `Fotos do Equipamento`
  - `Historico e Progresso`
- coluna principal:
  - resumo operacional superior;
  - abas centrais;
  - demais blocos operacionais da ordem.

### Abas principais

- `Informacoes`
- `Orcamento`
- `Diagnostico`
- `Fotos`
- `Valores`

### Regras importantes

- a aba `Informacoes` exibe status atual da OS e do orcamento, mas nao altera status;
- a aba `Orcamento` centraliza o vinculo comercial da OS;
- a aba `Fotos` consolida fotos de perfil, equipamento, entrada, acessorios e checklist;
- a aba `Valores` detalha financeiro da OS e do orcamento vinculado.
- a lateral `Historico e Progresso`, a timeline e os labels auxiliares da visualizacao foram alinhados em pt-BR/UTF-8 no frontend e nos helpers do controller.
- a normalizacao complementar desta release tambem revisou os textos de contexto da visualizacao em pp/Views/os/show.php, incluindo resumo primario, blocos de status, cards do orcamento vinculado e labels das abas.

## Integracao com o modulo de Orcamentos

### Regra do botao superior

Na visualizacao da OS:

- sem orcamento vinculado: exibe `Gerar orcamento`;
- com orcamento vinculado: deixa de criar novo orcamento e passa a abrir o orcamento existente.

### Regra de sincronizacao de status

A sincronizacao foi distribuida entre:

- `app/Controllers/Orcamentos.php`
- `app/Controllers/Orcamento.php`
- `app/Controllers/Os.php`

## Frontend reativo e linguagem operacional

Arquivos revisados nesta rodada para normalizacao textual:

- pp/Views/os/form.php`r
- pp/Views/os/show.php`r
- public/assets/js/os-list-filters.js`r

Esse lote corrigiu textos visiveis de modais, timeline, filtros, status, camera e resumo operacional sem alterar a semantica dos fluxos existentes.

Mapeamento atual:

- `rascunho`, `pendente_envio`, `enviado`, `aguardando_resposta`, `aguardando_pacote`, `pacote_aprovado`, `pendente`
  - OS -> `aguardando_autorizacao`
- `aprovado`, `convertido`
  - OS -> `aguardando_reparo`

Protecao adicional da release `2.15.3`:

- `app/Controllers/Os.php`

## Frontend reativo e linguagem operacional

Arquivos revisados nesta rodada para normalizacao textual:

- pp/Views/os/form.php`r
- pp/Views/os/show.php`r
- public/assets/js/os-list-filters.js`r

Esse lote corrigiu textos visiveis de modais, timeline, filtros, status, camera e resumo operacional sem alterar a semantica dos fluxos existentes.
- `app/Controllers/Orcamentos.php`
- `app/Controllers/Orcamento.php`
- `app/Services/OsStatusFlowService.php`

Esses pontos passaram a comparar a ordem do fluxo antes de sincronizar a OS com o status do orcamento. Se a OS ja estiver em uma etapa posterior ao alvo sugerido pelo orcamento, o sistema preserva o status manual da oficina.

## PDFs e mensageria

Na tela `/os/visualizar/{id}`, o envio documental passou a ficar concentrado na aba `Documentos`, com tres frentes operacionais no mesmo contexto:

- `Documentos PDF`, para gerar e listar versoes;
- `Enviar por WhatsApp`, com anexo opcional de PDF e fallback automatico para o consolidado de impressao quando nenhum arquivo salvo e escolhido;
- `Enviar por E-mail`, anexando um PDF ja gerado da OS.

### PDF

Persistencia:

- `os_documentos`
- `public/uploads/os_documentos/OS_<numero_os>/`

Tipos usuais:

- abertura;
- orcamento;
- laudo;
- entrega;
- devolucao sem reparo.

### Impressao consolidada

O topo da visualizacao da OS agora expande o fluxo de impressao com dois caminhos distintos por formato:

- dropdown `Imprimir` com `Folha A4` e `Bobina 80mm`;
- modal reativo em `app/Views/os/show.php` para `Folha A4`;
- abertura direta do documento termico para a caixa de dialogo de impressao do sistema operacional em `Bobina 80mm`;
- documento consolidado renderizado por `app/Views/os/print.php`;
- montagem de contexto centralizada em `app/Services/OsPrintService.php`.

Fluxo visual atual do `A4`:

- a escolha do formato acontece exclusivamente no dropdown `Imprimir`, e o modal apenas reflete a selecao ativa em badge no cabecalho;
- a barra lateral antiga foi removida para que a area util fique concentrada na pre-visualizacao do documento;
- o botao `Abrir em nova guia` passou para o cabecalho do modal, ao lado do badge do formato atual;
- o rodape do modal concentra `Incluir fotos no documento`, `Enviar PDF por WhatsApp` e `Imprimir agora`;
- o envio de WhatsApp foi desacoplado da lateral e agora abre um modal proprio, mantendo sincronizados o formato e a opcao `incluir_fotos` do fluxo `A4`;
- a janela de pre-visualizacao passou a operar como espelho do HTML final de `app/Views/os/print.php`, para que a organizacao visual da OS corresponda ao documento realmente impresso.

Fluxo visual atual do `80mm`:

- ao clicar em `Bobina 80mm`, a OS nao abre modal intermediario;
- `app/Views/os/show.php` abre diretamente o endpoint de impressao termica com `auto_print=1`;
- a view `app/Views/os/print.php` dispara `window.print()` automaticamente quando o formato termico e aberto no navegador;
- o modelo termico foi refeito em bloco proprio, com largura util de bobina, tipografia mono, secoes lineares e leitura otimizada para impressoras termicas.

Endpoint reutilizado:

- `GET /os/imprimir/{id}?formato=a4|80mm&incluir_fotos=0|1`
- `GET /os/imprimir/{id}?formato=80mm&auto_print=1`

Responsabilidades de `OsPrintService`:

- consolidar dados da OS, cliente, equipamento, itens, defeitos e procedimentos;
- reunir checklist, acessorios, estado fisico, orcamento e notas complementares;
- montar grupos de fotos por categoria operacional;
- gerar PDF temporario em `writable/uploads/os_print/` quando o envio partir do modal de impressao.
- converter foto principal e galerias em `data URI` no contexto da impressao, evitando dependencia de URL publica durante a renderizacao do `Dompdf`.

Comportamento documental:

- `A4` pode destacar a foto principal de perfil do equipamento ao lado esquerdo do bloco de equipamento quando `incluir_fotos = 1`;
- no layout `A4`, o cabecalho institucional da empresa ocupa toda a largura superior do documento, sem selo lateral adicional;
- o card-resumo da OS fica logo abaixo em faixa horizontal principal para destacar numero, badges, datas e identificacao operacional;
- os badges visuais do topo do documento consolidado foram removidos tanto da impressao quanto do PDF final;
- os dados do cliente passam a ocupar uma secao dedicada, separada do resumo principal;
- as informacoes do equipamento passam a ocupar uma faixa propria em largura total;
- quando `incluir_fotos = 1`, a foto principal entra na lateral esquerda desse bloco de equipamento;
- o bloco `Equipamento` permanece focado apenas nos dados tecnicos do aparelho;
- o campo `Formato` foi removido dos quadros informativos do documento consolidado;
- uma secao propria `Relato do Cliente e Diagnostico Tecnico` passa a ser exibida logo apos `Equipamento`, substituindo o antigo espaco de `Tecnico responsavel`;
- no `A4`, a primeira pagina e fechada deliberadamente apos o bloco de contexto inicial, preservando resumo operacional, equipamento e relato tecnico antes de iniciar o conteudo analitico;
- a propria pagina 2 concentra a sequencia de `Checklist de Entrada`, `Itens e Servicos Lancados na OS`, `Resumo Financeiro` e `Orcamento Vinculado`, respeitando a ordem operacional do documento;
- `Fotos Anexadas` passam a abrir uma terceira pagina exclusiva no `A4`, deixando a galeria fora do corpo tecnico-financeiro;
- a pre-visualizacao `A4` passa a montar folhas explicitas no HTML, permitindo que o rodape do iframe mostre `Pagina X de Y` de forma coerente com a divisao visual apresentada ao operador;
- no fluxo de geracao do PDF final, `app/Services/OsPrintService.php` desenha a paginacao no canvas do `Dompdf` apos o `render()`, refletindo o total real do arquivo mesmo quando houver variacao de quebra;
- o template `app/Views/os/print.php` passou a filtrar grupos de fotos sem URL valida antes da montagem das folhas, evitando pagina dedicada de `Fotos Anexadas` sem conteudo no PDF enviado;
- o modo `render-mode-pdf` da view `app/Views/os/print.php` agora replica os resets estruturais da impressao do navegador, removendo bordas e paddings extras do wrapper para aproximar a area util do PDF ao documento efetivamente impresso;
- no PDF gerado pelo `Dompdf`, as secoes grandes passaram a aceitar quebra entre paginas enquanto linhas, cards e blocos internos continuam protegidos contra fragmentacao visual;
- o modo `render-mode-pdf` deixou de reutilizar a casca paginada em tabela usada na pre-visualizacao do navegador; no PDF final, a quebra entre pagina 1, pagina 2 e `Fotos Anexadas` passou a usar divisores dedicados, reduzindo paginas vazias e desalinhamento estrutural no `Dompdf`;
- o `Resumo financeiro` fica imediatamente antes da secao `Orcamento vinculado`;
- as demais fotos seguem para o fechamento do documento agrupadas por tipo;
- `80mm` passou a usar um template termico dedicado, separado da casca visual do `A4`, com secoes compactas para tecnico, cliente, equipamento, relato, diagnostico, checklist, financeiro e orcamento;
- na visualizacao da OS, `80mm` nao depende mais da modal de gerenciamento de impressao e segue direto para a caixa de dialogo nativa do sistema operacional;
- a composicao do arquivo passou a priorizar tabelas e blocos simples no HTML para aumentar a fidelidade entre navegador e `Dompdf`.

### WhatsApp

Camada principal:

- `WhatsAppService`
- `MensageriaService`

Fluxo adicional coberto nesta release:

- o modal de impressao pode enviar um PDF sem criar uma nova versao persistida em `os_documentos`;
- quando necessario, `Os::sendWhatsApp($id)` gera um PDF temporario pelo `OsPrintService`, envia o anexo e faz limpeza do arquivo ao final;
- o formulario geral de `WhatsApp` da OS tambem passou a usar esse mesmo fallback, garantindo que o PDF gerado sob demanda siga o mesmo layout consolidado da impressao `A4`;
- os templates configurados em `Gestao de Conhecimento -> Templates WhatsApp` continuam sendo a base de mensagem, mas o operador pode editar o texto antes do envio;
- dentro do fluxo de impressao, o formulario de WhatsApp agora abre em modal dedicado, sem competir por espaco com a area de preview;
- as respostas AJAX desse modal retornam `csrfHash`, mensagem operacional e flag de duplicidade para manter a interface reativa sem refresh.
- a view `app/Views/os/print.php` recebeu hardening visual para compatibilidade simultanea com navegador e `Dompdf`, reduzindo dependencia de recursos CSS que degradavam blocos graficos no PDF final.

### E-mail

Camada principal:

- `ErpMailService`
- `Os::sendEmail($id)`

Comportamento:

- o envio valida o e-mail de destino usando o cadastro atual da OS como fallback;
- o operador precisa selecionar um registro existente de `os_documentos` para anexar;
- o corpo do e-mail e montado com contexto de cliente, equipamento, status atual e tipo do documento;
- falhas e sucessos ficam registrados em `LogModel` com eventos `os_email` e `os_email_erro`.

## Arquivos de referencia

- `app/Controllers/Os.php`
- `app/Services/OsPrintService.php`
- `app/Views/os/print.php`
- `app/Views/os/show.php`

## Frontend reativo e linguagem operacional

Arquivos revisados nesta rodada para normalizacao textual:

- pp/Views/os/form.php`r
- pp/Views/os/show.php`r
- public/assets/js/os-list-filters.js`r

Esse lote corrigiu textos visiveis de modais, timeline, filtros, status, camera e resumo operacional sem alterar a semantica dos fluxos existentes.
- `app/Models/OsModel.php`
- `app/Models/OsDocumentoModel.php`
- `app/Services/OsStatusFlowService.php`
- `app/Services/OsPdfService.php`
- `app/Services/ErpMailService.php`
- `app/Views/os/index.php`
- `app/Views/os/form.php`
- `app/Views/os/show.php`
- `app/Views/layouts/navbar.php`
- `app/Controllers/Notificacoes.php`
- `public/assets/js/os-list-filters.js`
- `public/assets/js/navbar-notifications.js`
