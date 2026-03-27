# Modulo: Ordens de Servico

## Objetivo
Controlar o ciclo completo de atendimento tecnico:
- recepcao
- diagnostico
- orcamento
- execucao
- qualidade
- encerramento

## Fluxo de status
Base:
- `os_status`
- `os_status_transicoes`
- `os_status_historico`

Campo operacional:
- `os.estado_fluxo` (`em_atendimento`, `em_execucao`, `pausado`, `pronto`, `encerrado`, `cancelado`)

Servico de regra:
- `app/Services/OsStatusFlowService.php`

Tela de administracao do fluxo:
- `GET /osworkflow`
- View: `app/Views/os_workflow/index.php`

## Comunicacao WhatsApp integrada

## Camada de mensageria
- `WhatsAppService` (fachada da OS)
- `MensageriaService` (resolucao de provider)
- contratos:
  - `WhatsAppProviderInterface` (direto)
  - `BulkMessageProviderInterface` (massa/futuro)

## Providers diretos
- `MenuiaProvider`
- `LocalGatewayProvider` (gateway local Node.js)
- `WebhookProvider` (customizado)

## Provider de massa (futuro CRM)
- `MetaOfficialProvider` (stub)

## Envio suportado na OS
- texto manual
- template
- PDF anexo
- texto + PDF

## Logs de envio
Tabela principal atual:
- `mensagens_whatsapp`

Compatibilidade:
- `whatsapp_envios`
- `whatsapp_mensagens`

## PDF da OS
Geracao por `OsPdfService`:
- abertura
- orcamento
- laudo
- entrega
- devolucao sem reparo

Persistencia:
- `os_documentos`
- `public/uploads/os_documentos/OS_<numero_os>/`

## Automacao por status
`Os::triggerAutomaticEventsOnStatus()` pode:
- gerar PDF automaticamente
- enviar template WhatsApp automaticamente

## Rotas-chave
- `POST /os/status/{id}`
- `GET /os/status-meta/{id}`
- `POST /os/status-ajax/{id}`
- `GET /os/fotos/{id}`
- `POST /os/whatsapp/{id}`
- `POST /os/pdf/{id}/gerar`
- `POST /os/datatable` (listagem server-side com filtros avancados)
- `GET /osworkflow`
- `POST /osworkflow/salvar`

## Filtros avancados da listagem (`/os`)

### Camada backend
- `Os::collectListFilters()` centraliza leitura/normalizacao de filtros (`GET` e `POST`).
- `Os::applyListFilters()` aplica criterios combinados no query builder sem duplicacao.
- `Os::applySituacaoFilter()` resolve regras operacionais de situacao:
  - `em_triagem`
  - `em_atendimento`
  - `finalizado`
  - `equipamento_entregue`
- `Os::datatable()` passou a separar:
  - `recordsTotal` com contagem direta em `os`
  - `recordsFiltered` com builder reduzido e joins apenas quando necessarios
  - pagina atual por IDs ordenados
  - carga final dos detalhes da grade apenas para os IDs da pagina atual
- A busca por numero de OS usa um caminho otimizado quando o termo tem perfil de codigo (`OS2026...` ou digitos equivalentes), evitando joins pesados desnecessarios.
- A busca global textual para alto volume deixou de usar joins amplos na consulta principal:
  - cliente via subconsulta indexada em `clientes.nome_razao`
  - equipamento via subconsultas indexadas em `equipamentos_marcas.nome`, `equipamentos_modelos.nome` e lookup em `equipamentos`
  - tecnico via subconsulta indexada em `funcionarios.nome`
  - `os.relato_cliente` como fallback textual com `FULLTEXT` quando disponivel; `LIKE` fica apenas como contingencia
- A busca por equipamento passou a montar apenas o ramo realmente compativel com o termo (`marca`, `modelo` ou ambos), evitando subconsulta mais ampla do que o necessario.
- Filtros de data usam faixa `datetime` (`>= inicio do dia` e `< proximo dia`) para preservar uso de indice.
- Filtros de valor evitam `COALESCE()` na coluna indexada.
- O filtro `tipo_servico` foi refatorado para subconsulta `IN (SELECT os_id ...)`, alinhada com indice composto em `os_itens`.
- O endpoint limita `length` a `100` linhas por requisicao para evitar payload abusivo.
- Filtros suportados no datatable:
- `q` (numero OS, cliente, marca/modelo, relato, tecnico)
  - `status` (multiplo)
  - `macrofase`
  - `estado_fluxo`
  - `data_inicio` / `data_fim`
  - `tecnico_id`
  - `tipo_servico` (subquery `IN` em `os_itens`)
  - `valor_min` / `valor_max`
  - `situacao`

### Camada frontend
- View da listagem refatorada com barra horizontal premium e drawer mobile.
- Arquivo dedicado: `public/assets/js/os-list-filters.js`.
- CSS escopado: `public/assets/css/design-system/layouts/os-list-layout.css`.
- Comportamentos:
  - filtros por AJAX sem reload
  - debounce de busca (300ms)
  - persistencia em URL + localStorage
  - chips de filtros ativos com remocao individual
  - contador de resultados e overlay de carregamento
- tabela responsiva com estrategia mista:
  - `>= 768px`: mantem formato tabular
  - `< 768px`: converte as OS em cards verticais
- tabela em desktop/tablet/notebook:
  - coluna `Foto` posicionada no inicio da grade operacional, exibindo a miniatura principal do equipamento
  - a miniatura abre modal visualizador com duas galerias segregadas:
    - fotos de perfil do equipamento
    - fotos de abertura da OS (`recepcao`)
  - coluna inicial de controle (`+` / `-`) aparece apenas quando houver colunas ocultas
  - detalhes ocultos sao renderizados em child row no proprio DataTable
  - badges e botoes da coluna `Acoes` continuam funcionais dentro da expansao
  - coluna `N OS` usa largura minima fixa com `white-space: nowrap`
  - coluna `Cliente` recebe largura minima maior e quebra em duas linhas a partir da segunda palavra quando o nome tiver quatro palavras ou mais
  - as larguras-base de `N OS` e `Cliente` foram refinadas novamente para reduzir desperdicio horizontal na grade
  - coluna `Equipamento` foi convertida para layout em tres linhas semanticas:
    - `Tipo`
    - `Marca`
    - `Modelo`
  - coluna `Relato` usa quebra de linha natural e ajuste progressivo de fonte para preservar ate 4 linhas uteis
  - coluna `Datas` substitui `Data Abertura` e renderiza:
    - `Entrada`
    - `Prazo`
    - `Entrega`
  - o status de prazo e calculado com base em `data_entrada`, `data_previsao` e `data_entrega`, recebendo badge contextual (`success`, `warning`, `orange`, `danger`)
  - a coluna `Status` usa trigger clicavel por linha, com modal AJAX de alteracao e observacao opcional
  - as colunas `Status` e `Valor Total` foram estreitadas para devolver largura operacional a `Cliente` e `Equipamento`
- comportamento por faixa:
  - `>= 1400px`: tabela ampla, mantendo `Foto` visivel ao lado de `N OS`
  - `1200px a 1399px`: oculta `Valor Total` e antecipa `Relato`/`Acoes` quando a largura util real apertar
  - `992px a 1199px`: oculta `Valor Total`, `Relato` e `Acoes`, mantendo `Foto` visivel enquanto houver largura util suficiente
  - `768px a 991px`: pode ocultar `Foto`, `Equipamento`, `Relato` e `Valor Total` antes de qualquer scroll horizontal
  - `<= 767px`: cards mobile enxutos com apenas `Foto`, `N OS`, `Cliente`, `Modelo` e botao `+` na face principal
- o motor responsivo da tabela agora usa a largura util real do wrapper (`table-responsive` / `card-body`) como fonte principal para decidir o layout, evitando que a view continue em modo desktop quando a area livre ja esta em faixa de notebook
- a estrategia principal deixa de ser `scroll horizontal`: o child row do DataTable passa a concentrar colunas menos prioritarias antes de qualquer estouro lateral
- as colunas `Acoes`, `Relato`, `Status`, `Datas` e `Equipamento` passaram a usar validacao defensiva por overflow real do wrapper
- a ordem de recolhimento residual ficou:
  - `Acoes`
  - `Relato`
  - `Status`
  - `Datas`
  - `Equipamento`
- cada uma delas, quando nao couber integralmente, sai por completo da grade e vai para o child row
- no card mobile, o botao `+` fica sempre visivel para abrir:
  - equipamento completo
  - datas
  - status
  - relato
  - valor total
  - acoes
- no detalhe mobile expandido, `Equipamento` foi ajustado para layout vertical de label/valor, evitando compressao letra-a-letra.
- o pseudo-label tecnico (`Campo 1`) foi removido do child row mobile.
- as regras mobile passaram a cobrir os dois formatos de classe da linha expandida (`child` e `os-responsive-child-row`), evitando regressao de layout quando o DataTables renderiza o child row com classe personalizada.
- o hint textual `Alterar status` e escondido nos perfis comprimidos para preservar largura funcional da coluna `Status`
- filtros:
  - `>= 1200px`: barra desktop em linha com quebra das acoes quando necessario
  - `992px a 1199px`: duas colunas + linha dedicada para as acoes
  - `768px a 991px`: duas colunas + grade compacta de acoes
  - `<= 767px`: drawer/offcanvas de filtros
- shell responsivo:
  - `main-content` passou a respeitar a largura restante apos a sidebar fixa
  - em tablet/mobile a sidebar vira overlay e nao reserva largura lateral
  - na rota `/os`, em desktop/notebook, a sidebar entra em modo auto-recolhido por padrao para liberar largura real da listagem
  - nesse mesmo modo, a sidebar expande por hover/foco sem devolver a largura ao `main-content`, funcionando como painel temporario sobreposto
- novo endpoint auxiliar da listagem:
  - `GET /os/fotos/{id}` devolve JSON com as fotos do equipamento e da abertura, usado pelo modal visualizador da grade
- responsividade agressiva validada em `1366px`, `1024px`, `820px`, `700px`, `390px`, `360px` e `320px`
- wrappers `os-table-wrap`, `card-body` e `table-responsive` usam `min-width: 0` para impedir estouro estrutural
- Validacao de escala executada com massa sintetica de `50.000` OS, incluindo pagina inicial, pagina profunda, filtro por status, tecnico, busca por numero, cliente, equipamento, relato e `tipo_servico`.

## Modal de alteracao de status na listagem

Fluxo atual:
- o badge da coluna `Status` chama `GET /os/status-meta/{id}`
- o backend devolve apenas os status permitidos para a OS selecionada
- o frontend abre `#osStatusModal` e preenche os grupos de destino disponiveis
- ao confirmar, `POST /os/status-ajax/{id}` persiste a troca e recarrega apenas o DataTable

Regras de transicao:
- se existirem linhas ativas em `os_status_transicoes`, elas passam a ser a fonte oficial do workflow
- se a tabela de transicoes estiver vazia, o sistema aplica fallback automatico por `ordem_fluxo`
- o fallback considera somente status ativos e permite mover para o status anterior ou proximo da sequencia

Servico responsavel:
- `app/Services/OsStatusFlowService.php`

Responsabilidades do servico:
- calcular destinos permitidos
- bloquear transicoes invalidas
- montar hints de navegacao do fluxo
- salvar configuracao administrativa do workflow

## Configuracao administrativa do workflow

Menu:
- `Gestao de Conhecimento > Fluxo de Trabalho OS`

Capacidades:
- reordenar `ordem_fluxo`
- ativar/desativar status
- marcar status final
- marcar status de pausa
- definir destinos permitidos por status em seletor multiplo

Persistencia:
- tabela `os_status`
- tabela `os_status_transicoes`

Ajuda contextual:
- `openDocPage('os-workflow')`

## Modo embed (dashboard e atalhos rapidos)
- `GET /os/nova?embed=1`
- `GET /os/visualizar/{id}?embed=1`
- Em embed, as telas usam `layouts/embed.php` para abrir em modal sem sidebar/navbar.
- Formularios de criacao/edicao preservam `embed=1` para manter o fluxo dentro do modal.
- Na pagina `/os`, o botao `+ Nova OS` usa esse mesmo modo embed em modal, sem botao de redirecionamento externo.
- Na mesma rota `/os`, a navbar principal oculta a acao global `Nova OS` para nao duplicar CTA no topo; a abertura passa a depender apenas do botao da propria listagem.
- O editor rapido de cliente (`+ Novo` / `Editar`) permanece como modal Bootstrap tambem em `embed=1`, aberto dentro do iframe para preservar contexto sem redirecionamento.
- O gatilho de abertura do modal rapido de cliente ficou restrito aos botoes `+ Novo` e `Editar`; interacoes com o `Select2` de cliente ou com a area do campo nao podem abrir o modal.
- Ao salvar cliente via AJAX dentro do iframe, o formulario reinicializa e sincroniza o `select2` local para atualizar instantaneamente o valor selecionado, os resultados exibidos e o card de contexto do cliente.
- O modal rapido de cliente compartilha o lookup automatico de CEP do sistema: ao completar um CEP valido, preenche `endereco`, `bairro`, `cidade` e `uf` no mesmo formulario e move o foco para `numero`.
- No mesmo fluxo, o iframe envia `postMessage` para a pagina pai recarregar o DataTable `/os` sem perder o contexto do modal.
- O cadastro rapido de equipamento da OS passou a validar `tipo`, `marca` e `modelo` antes do envio; em falta, o modal reposiciona o usuario na aba `Info`.
- Os campos `Marca` e `Modelo` do modal rapido agora usam botao verde `+ Adicionar` junto da label, removendo o input-group lateral anterior.
- O label `Nº de Série` do modal rapido foi normalizado.
- O gatilho de abertura do modal rapido de equipamento ficou restrito ao botao `+ Novo`; interacoes com o `Select2` de equipamento ou com a area do campo nao podem abrir o modal.
- A `Senha de Acesso` do modal rapido foi migrada para um componente compartilhado com dois modos:
  - `desenho`: grade Android 3x3, persistida como `desenho_1-4-7-8-9`
  - `texto`: senha livre
- A grade do modo `desenho` foi compactada para melhorar usabilidade e nao dominar a area util do modal.
- A serializacao da senha por desenho e normalizada no backend por `Equipamentos::normalizeSenhaAcessoPayload()`, inclusive nos endpoints AJAX.
- O cadastro rapido de equipamento da OS passou a validar `cor` e `ao menos uma foto` como obrigatorios no frontend e no backend AJAX.
- Em falha de validacao, o modal nao fecha: a resposta retorna a aba pendente (`cor` ou `foto`) e o frontend reposiciona o usuario diretamente no ponto faltante.
- Os Select2 single-line do formulario passam a herdar truncamento responsivo com `ellipsis`, preservando `title` com o valor completo e sem invadir a area da seta.
- O envio final da OS usa trava de submissao no `formOs`: ao acionar `Abrir OS` ou `Atualizar`, a tela aplica overlay de carregamento, desabilita os botoes do rodape e bloqueia cliques repetidos ate a navegacao concluir.
- O modo embed e a tela completa passaram a receber monitor global de sessao por meta tags (`session-timeout-minutes`, `session-heartbeat-url`, `session-login-url`), com heartbeat discreto enquanto houver atividade no formulario.
- Se o backend responder `401` por sessao expirada em AJAX/fetch, o frontend intercepta a resposta, mostra SweetAlert2 e redireciona o contexto correto (janela principal ou iframe embed) para o login.

## Estrutura atual do modal Nova OS

Abas operacionais:
- `Cliente`
- `Equipamento`
- `Defeito`
- `Dados Operacionais`
- `Fotos`
- `Pecas e Orcamento` (somente edicao)

Distribuicao:
- `Cliente`: seletor de cliente, acoes CRUD rapido e card informativo do cliente.
- `Equipamento`: seletor de equipamento, estado fisico e acessorios/componentes da entrada.
- modal rapido de novo equipamento com abas `Info`, `Cor` e `Foto`, exigindo cor e pelo menos uma foto antes do salvamento.
- `Defeito`: relato do cliente, tecnico responsavel e defeitos comuns do tipo de equipamento (na edicao).
- `Dados Operacionais`: prioridade, entrada, previsao, status e garantia (na edicao).
- Rodape de envio: o submit final entra em estado `submitting`, com spinner no botao principal e overlay visual padrao sobre o formulario para evitar reenvio por clique duplo.
- O modal rapido de equipamento compartilha o mesmo componente de senha por desenho usado no cadastro completo de equipamentos.

Objetivo da refatoracao:
- reduzir poluicao visual
- separar cadastro, contexto tecnico e execucao
- melhorar responsividade em modal embed

## Resumo lateral reativo
- O card `Resumo da OS` usa atualizacao em tempo real durante o preenchimento do formulario.
- Os indicadores por campo usam icones Bootstrap:
  - `bi-check-circle-fill` para item valido/preenchido
  - `bi-x-circle-fill` para item pendente
- Os rotulos do resumo usam peso visual mais forte e a composicao trabalha com coluna fixa para o topico, deixando mais espacamento entre rótulo e valor quando o texto do equipamento quebra em duas linhas.
- A troca dos indicadores acontece via `updateResumo()` sem reload da pagina.

## Superficies editaveis da Nova OS
- O layout do formulario destaca o bloco inteiro de preenchimento (`card`, `os-data-section`, cards laterais e wrappers internos), e nao apenas `input`, `select` e `textarea`.
- A paleta dessas superficies foi ajustada para base branca com borda azul/cinza suave, mantendo contraste leve e aspecto premium.
- O estado ativo da secao e controlado por `:focus-within`, com fundo diferenciado e borda azul suave na area em edicao.
- Inputs, selects, textareas e Select2 ficam com fundo branco para preservar contraste e leitura dentro dessas superficies.
- O alinhamento com o design system cobre tambem os cards da sidebar, a shell principal do formulario, o painel de fotos e os atalhos de relato da tela.
- Os relatos rapidos do campo de entrada ficam agrupados em dropdowns por categoria, preservando a mesma acao de inserir texto no textarea.
- A camada de view/JS da OS tambem passou por normalizacao de acentuacao em labels, avisos, atalhos e textos auxiliares para eliminar mojibake na interface.

## Integracao com CRM e inbox
- Alteracao de status dispara `CrmService` para registrar eventos/follow-ups no CRM.
- Mensagens da OS passam a refletir em `mensagens_whatsapp` com `conversa_id` quando houver thread.
- Conversas da Central podem ser vinculadas a OS para atendimento contextual sem sair do inbox.
