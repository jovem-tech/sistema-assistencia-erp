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

### Responsabilidades

- busca global;
- filtros avancados;
- paginacao server-side;
- leitura combinada de OS, cliente, equipamento, prazo e orcamento.

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
- orcamento `aprovado` ou `convertido`:
  - OS sugerida/atualizada para `aguardando_reparo`.

Regra complementar obrigatoria:

- essa sincronizacao automatica nao pode rebaixar uma OS que ja avancou manualmente para etapas posteriores do reparo, como `reparo_execucao`, `aguardando_peca`, `testes_operacionais`, `testes_finais`, `reparo_concluido` e similares;
- o orcamento continua definindo o ponto de entrada do fluxo tecnico, mas nao sobrescreve fases mais avancadas ja confirmadas pela equipe;
- na DataTable `/os`, o status principal renderizado deve sempre refletir `os.status` e `os.estado_fluxo` reais; o status do orcamento permanece apenas como badge auxiliar e nao pode substituir visualmente a OS.

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
- o preenchimento nao salvo sera perdido.

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

### Persistencia do card tecnico

Ao salvar o modal de status:

- o backend continua aplicando a transicao via `OsStatusFlowService::applyStatus()`;
- no mesmo `POST /os/status-ajax/{id}`, o modal agora tambem envia `procedimentos_executados`, `solucao_aplicada` e `diagnostico_tecnico`;
- esses campos sao normalizados em `Os::normalizeNullableString()` e persistidos em `os.procedimentos_executados`, `os.solucao_aplicada` e `os.diagnostico_tecnico`.

### Orcamento embutido e sincronizacao

- o resumo de orcamento do modal usa `view('os/partials/orcamento_editor_panel', [..., 'orcamentoContext' => 'status_modal'])`;
- os botoes do card passaram a expor `data-os-frame-modal-url`, abrindo `Criar`, `Editar` ou `Visualizar orcamento` no iframe modal da listagem;
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

Mapeamento atual:

- `rascunho`, `pendente_envio`, `enviado`, `aguardando_resposta`, `aguardando_pacote`, `pacote_aprovado`, `pendente`
  - OS -> `aguardando_autorizacao`
- `aprovado`, `convertido`
  - OS -> `aguardando_reparo`

Protecao adicional da release `2.15.3`:

- `app/Controllers/Os.php`
- `app/Controllers/Orcamentos.php`
- `app/Controllers/Orcamento.php`
- `app/Services/OsStatusFlowService.php`

Esses pontos passaram a comparar a ordem do fluxo antes de sincronizar a OS com o status do orcamento. Se a OS ja estiver em uma etapa posterior ao alvo sugerido pelo orcamento, o sistema preserva o status manual da oficina.

## PDFs e mensageria

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

### WhatsApp

Camada principal:

- `WhatsAppService`
- `MensageriaService`

## Arquivos de referencia

- `app/Controllers/Os.php`
- `app/Models/OsModel.php`
- `app/Services/OsStatusFlowService.php`
- `app/Services/OsPdfService.php`
- `app/Views/os/index.php`
- `app/Views/os/form.php`
- `app/Views/os/show.php`
- `app/Views/layouts/navbar.php`
- `app/Controllers/Notificacoes.php`
- `public/assets/js/os-list-filters.js`
- `public/assets/js/navbar-notifications.js`
