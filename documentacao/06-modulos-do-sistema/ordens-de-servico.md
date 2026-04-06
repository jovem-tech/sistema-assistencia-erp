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

## Migracao legada SQL

Campos de rastreabilidade:
- `clientes.legacy_origem`
- `clientes.legacy_id`
- `equipamentos.legacy_origem`
- `equipamentos.legacy_id`
- `os.legacy_origem`
- `os.legacy_id`
- `os.numero_os_legado`

Regras:
- o ERP novo continua gerando `numero_os`
- a referencia antiga permanece em `numero_os_legado`
- a listagem `/os` aceita busca tanto pelo numero oficial quanto pelo legado
- a listagem `/os` ganhou o seletor visual `Somente legado`, que aplica `legado=1` sem sair do contexto
- a busca global da navbar passou a expor o filtro dedicado `OS Legado`, permitindo pesquisar somente ordens migradas pelo numero antigo
- a visualizacao da OS exibe `Numero legado` e `Origem` quando o registro foi importado
- a coluna `N OS` da listagem empilha `numero_os`, `Legado: <numero_os_legado>` e `Origem: <legacy_origem>` em linhas separadas quando a ordem vier de migracao
- a visualizacao da OS tambem passou a consumir o contexto legado importado em `Itens / Servicos`, `Diagnostico` e `Valores`
- o modal rapido de cliente na abertura/edicao da OS passou a emitir feedback de sucesso com `SweetAlert2` apos `clientes/salvar_ajax`, sem exigir refresh da tela

Pipeline:
- `php spark legacy:preflight`
- `php spark legacy:prepare-target`
- `php spark legacy:import --execute`
- `php spark legacy:import --execute --wipe-target`
- `php spark legacy:report`

Adaptacao atual do legado `erp`:
- clientes importados diretamente da tabela `clientes`
- equipamentos derivados da tabela `os` por snapshot deterministico
- clientes sem telefone valido entram como aviso e nao bloqueiam a carga
- quando o legado nao trouxer telefone valido, `clientes.telefone1` passa a ser salvo como string vazia no ERP novo para evitar falha estrutural durante a importacao
- clientes repetidos com o mesmo `CPF/CNPJ` valido passam a convergir com seguranca para um cliente canonico, sem duplicacao artificial
- quando houver `numero_serie` ou `IMEI` valido, snapshots compativeis podem convergir para um equipamento canonico ja migrado
- a consolidacao segura desses aliases fica registrada em `legacy_import_aliases`
- sem identificador forte confiavel, o snapshot continua individual por OS para evitar falsa mesclagem
- `orcamentos` complementa a OS com laudo, solucao aplicada, aprovacao e forma de pagamento
- `orcamento_itens`, `servicos_orc` e `produtos_orc` alimentam a composicao de itens da OS
- `historico_status_os` e `os_historico` alimentam o historico estruturado de status quando houver mapeamento
- `os_historicos` preserva anotacoes livres em `os_notas_legadas`
- quando a OS antiga nao tiver detalhamento por item, o importador gera itens sinteticos de totalizacao para servicos, pecas ou valor consolidado sem classificacao
- esses itens sinteticos registram em `observacao` o campo exato de origem do valor no legado (`os.mao_obra`, `os.total_servicos`, `os.total_produtos`, `os.subtotal` ou `os.valor + desconto`)

## Automacao por status
`Os::triggerAutomaticEventsOnStatus()` pode:
- gerar PDF automaticamente
- enviar template WhatsApp automaticamente
- quando a troca acontece pelo modal rapido da visualizacao da OS, o operador pode suprimir apenas a comunicacao automatica com o cliente sem perder os efeitos internos do ERP/CRM

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

## Selecao rica de equipamento na OS (ERP web e app mobile)

Objetivo:

- evitar selecao equivocada quando o cliente possui equipamentos iguais ou muito parecidos

Implementacao no ERP web:

- `app/Views/os/form.php`
- Select2 com `templateResult`, `templateSelection` e `matcher` customizados
- cada opcao exibe:
  - foto de perfil do equipamento
  - `tipo - marca`
  - `modelo - cor`
  - `numero de serie` ou `IMEI`
- no header inline do campo `Equipamento *`, o ERP agora exibe:
  - botao `Novo` para cadastro rapido
  - botao `Editar` seguindo design system (`btn-outline-info`) e visivel apenas quando existe equipamento selecionado
- ambos reutilizam o mesmo modal padrao de equipamento da OS, sem fluxo paralelo

Implementacao no app mobile/PWA:

- `mobile-app/src/app/os/nova/page.tsx`
- combobox rico com miniatura, linhas semanticas e busca pelos mesmos metadados

Fontes de dados:

- `app/Models/EquipamentoModel.php`
- `app/Controllers/Equipamentos.php::byClient()`
- `app/Controllers/Api/V1/OrdersController.php::meta()`

Campos adicionais expostos para esse fluxo:

- `foto_principal_arquivo` / `foto_url`
- `tipo_nome`
- `marca_nome`
- `modelo_nome`
- `cor`
- `numero_serie`
- `imei`

## Checklist de entrada e acessorios (robustez visual e funcional)

Checklist de entrada:
- Servico `ChecklistService` agora resolve modelo com fallback automatico para `entrada`.
- Quando nao existe modelo ativo para o tipo de equipamento da OS, o sistema cria um modelo inicial e seus itens padrao automaticamente.
- Abertura de checklist deixa de depender 100% de cadastro manual previo para tipos novos.

Acessorios (formulario rapido de cor):
- A grade de cores rapidas foi ajustada para `flex-wrap`.
- Os chips de cor agora respeitam largura do container e quebram linha sem vazamento visual.
- O card rapido de acessorio passou a aceitar fotos no proprio formulario (`Galeria` e `Camera`) antes do `Salvar item`.
- O frontend passou a manter um `entryId` de rascunho para vincular fotos temporarias ao item ainda nao salvo e reutilizar o mesmo vinculo no `POST`.
- Cancelamento do formulario rapido remove automaticamente uploads temporarios sem item salvo, evitando lixo visual e envio acidental.
- A linha de layout `os-equip-panels-row` recebeu hardening de gutter e caixa para impedir que as bordas internas de `Checklist` e `Acessorios` avancem alem do limite visual do painel.
- O contraste das bordas desses paines foi reduzido para manter leitura limpa no design system, com menor destaque de contorno.
- O raio dos cards internos foi reforcado (`border-radius` dedicado + `background-clip`) para preservar o contorno arredondado sem recorte lateral.
- O card interno dos dois paines passou a ter margem inferior dedicada para preservar separacao visual da borda base do container principal.

## Visualizacao operacional (`/os/visualizar/{id}`)

### Hierarquia atual
- coluna lateral:
  - `Fotos do Equipamento`
  - `Historico e Progresso`
- coluna principal:
  - resumo superior (`Cliente`, `Equipamento`, `Tecnico`)
  - abas da OS
  - card `Status`
  - `Documentos PDF`
  - `WhatsApp`
- o antigo card superior de `Valor Final` foi removido; a informacao permanece na aba `Valores`
- na aba `Informacoes`, os cards internos de `Cliente` e `Equipamento` foram removidos para evitar redundancia com o resumo superior
- a aba `Informacoes` fica concentrada nos blocos complementares, como `Relato do Cliente` e `Checklist de Entrada`

### Card `Status`
- o card concentra agora dois atalhos operacionais:
  - `Proxima etapa`
  - `Cancelar`
- ambos usam um modal unico com:
  - `status` de destino oculto
  - `observacao_status`
  - `controla_comunicacao_cliente = 1`
  - `comunicar_cliente` (checkbox)
- o destino de `Proxima etapa` e resolvido por `Os::resolvePrimaryNextStatus()`
- o cancelamento direto ficou permitido globalmente por `OsStatusFlowService::isTransitionAllowed()`

### Regras de comunicacao
- o backend agora separa duas camadas:
  - automacao interna do ERP/CRM
  - comunicacao externa com o cliente
- quando o modal rapido controla a comunicacao:
  - os efeitos internos continuam rodando
  - templates automaticos podem ser suprimidos para evitar duplicidade
  - a notificacao ao cliente, se marcada, e enviada manualmente por `WhatsAppService::sendRaw()`

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
  - `legado` (`1` = somente ordens migradas)
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
  - barra de origem com alternancia rapida entre `Todas as OS` e `Somente legado`
- tabela responsiva com estrategia mista:
  - `>= 768px`: mantem formato tabular
  - `< 768px`: converte as OS em cards verticais
- tabela em desktop/tablet/notebook:
  - coluna `Foto` posicionada no inicio da grade operacional, exibindo a miniatura principal do equipamento
  - coluna `N OS` passou a ser link de navegacao direta para `GET /os/visualizar/{id}`
  - coluna `Cliente` virou atalho contextual para abrir a ficha completa do cliente em modal embed
  - coluna `Equipamento` virou atalho contextual para abrir a ficha completa do equipamento em modal embed
  - coluna `Datas` virou atalho para o modal rapido de prazos, com atualizacao exclusiva de `previsao`
  - coluna `Valor Total` virou atalho para o modal de orcamento, com geracao de PDF e envio opcional ao cliente
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
  - quando a OS permanece aberta apos a data prevista, o badge vermelho passa a informar explicitamente ha quantos dias o prazo foi estourado
  - a coluna `Status` usa trigger clicavel por linha, com modal AJAX enriquecido e alinhado com a visualizacao da OS
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
- o filtro superior da busca global teve normalizacao de labels para eliminar mojibake nas opcoes `Servicos`, `Pecas`, `Usuario` e `Configuracoes`
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
- novos atalhos modais da listagem:
  - `GET /clientes/visualizar/{id}?embed=1`
  - `GET /equipamentos/visualizar/{id}?embed=1`
  - `GET /os/prazos-meta/{id}` + `POST /os/prazos-ajax/{id}`
  - `GET /os/orcamento-meta/{id}` + `POST /os/orcamento-ajax/{id}`
- o modal do cliente na listagem depende de `cliente_id` na carga paginada e agora volta a abrir corretamente a ficha completa com historico em contexto embed
- o modal de prazos foi restringido para operacao segura:
  - `data de entrada` somente leitura
  - `entrega` somente leitura
  - `previsao` como unico campo editavel
- a data de entrega continua vinculada ao fluxo correto de status da OS
- a estrutura visual do modal de prazos recebeu reforco de altura e rolagem interna para manter o rodape acessivel
- responsividade agressiva validada em `1366px`, `1024px`, `820px`, `700px`, `390px`, `360px` e `320px`
- wrappers `os-table-wrap`, `card-body` e `table-responsive` usam `min-width: 0` para impedir estouro estrutural
- Validacao de escala executada com massa sintetica de `50.000` OS, incluindo pagina inicial, pagina profunda, filtro por status, tecnico, busca por numero, cliente, equipamento, relato e `tipo_servico`.

## Modal de alteracao de status na listagem

Fluxo atual:
- o badge da coluna `Status` chama `GET /os/status-meta/{id}`
- o backend devolve os status permitidos, o destino principal sugerido, a timeline de progresso e o historico recente da OS selecionada
- o frontend abre `#osStatusModal` e preenche:
  - resumo da OS
  - bloco de contexto do cliente
  - bloco de contexto do equipamento
  - badges do estado atual
  - acoes rapidas `Proxima etapa` e `Cancelar`
  - grupos de destino disponiveis
  - timeline `Historico e Progresso`
  - ultimas movimentacoes
  - switch para comunicar ou nao o cliente
- ao confirmar, `POST /os/status-ajax/{id}` persiste a troca e recarrega apenas o DataTable

Comportamento operacional:
- `Proxima etapa` usa `Os::resolvePrimaryNextStatus()` para selecionar o caminho normal sugerido do fluxo
- `Cancelar` usa o destino `cancelado` quando disponivel no conjunto de transicoes permitido
- o operador ainda pode trocar o destino manualmente pelo select se precisar retornar ou seguir outra opcao valida
- o modal usa a mesma base de timeline da visualizacao `/os/visualizar/{id}`, evitando divergencia entre listagem e detalhe
- a notificacao ao cliente e opt-in; se marcada, o backend envia mensagem manual via `WhatsAppService::sendRaw()`
- se o cliente nao tiver telefone, o switch e bloqueado e o modal informa esse estado claramente
- o modal passou a usar rolagem interna controlada, com `modal-body` rolavel e rodape acessivel mesmo quando o conteudo vertical excede a altura util da viewport

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

## Visualizacao da OS (`/os/visualizar/{id}`)

Hierarquia atual da tela:
- coluna lateral fixa com `Fotos do Equipamento`
- logo abaixo das fotos, na mesma coluna sticky: `Historico e Progresso`
- linha principal superior com:
  - resumo operacional (`Cliente`, `Equipamento`, `Tecnico`)
  - abas do formulario/atendimento
  - card `Valor Final`
- linha seguinte com:
  - card `Status`
- linha operacional seguinte com:
  - `Documentos PDF`
  - `WhatsApp`

Abas principais:
- `Informacoes`
- `Itens / Servicos`
- `Diagnostico`
- `Fotos de Entrada`
- `Valores`

Leitura especial da aba `Valores` em OS legadas:
- quando o financeiro veio apenas do cabecalho do ERP antigo, a tela mostra o bloco `Origem do valor legado`
- esse bloco aponta qual item sintetico sustentou o total e de qual campo legado ele foi derivado

Implementacao:
- Controller: `Os::show($id)`
- View: `app/Views/os/show.php`
- CSS responsivo: `public/assets/css/design-system/layouts/responsive-layout.css`

Dados adicionais entregues pela controller para a view:
- `statusGrouped`
- `statusOptions`
- `statusHistorico`
- `workflowTimeline`
- `workflowRecentHistory`

### Timeline vertical de progresso
- A timeline e montada a partir de `OsStatusFlowService::getStatusGrouped()` + historico real em `os_status_historico`.
- Cada macrofase recebe estado visual:
  - `completed`
  - `current`
  - `probable`
  - `upcoming`
- O card tambem agrega as ultimas movimentacoes para reduzir duplicidade entre historico puro e progresso visual.

### Responsividade da visualizacao
- `>= 1200px`: split lateral mantido com fotos a esquerda e conteudo hierarquico a direita.
- `>= 1200px`: o card `Historico e Progresso` fica empilhado logo abaixo de `Fotos do Equipamento`, formando uma lateral unica.
- `992px a 1199px`: resumo superior quebra de 3 para 2 colunas.
- `<= 991px`: resumo vira 1 coluna; formulario de status ocupa largura total.
- `<= 767px`: acoes do topo empilham, preview de foto reduz e listas internas perdem altura fixa.
- `<= 390px`: timeline, card de status e preview recebem compactacao adicional sem perder legibilidade.
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
- `Equipamento`: seletor de equipamento, botao `Checklist` (substitui `Estado fisico`) e acessorios/componentes da entrada.
- modal rapido de novo equipamento com abas `Info`, `Cor` e `Foto`, exigindo cor e pelo menos uma foto antes do salvamento.
- `Defeito`: relato do cliente, tecnico responsavel e defeitos comuns do tipo de equipamento (na edicao).
- `Dados Operacionais`: prioridade, entrada, previsao, status e garantia (na edicao).
- Rodape de envio: o submit final entra em estado `submitting`, com spinner no botao principal e overlay visual padrao sobre o formulario para evitar reenvio por clique duplo.
- O modal rapido de equipamento compartilha o mesmo componente de senha por desenho usado no cadastro completo de equipamentos.

## Checklist de Entrada (ERP + App)

- o campo textual `estado_fisico_data` foi substituido pelo fluxo de `Checklist de Entrada` estruturado.
- o formulario da OS exibe:
  - botao `Checklist`;
  - indicador de resumo em linguagem operacional:
    - `Aguardando equipamento`
    - `Pendente`
    - `Tudo OK`
    - `N discrepancias`
  - card de contexto com titulo e helper explicativos para cada estado.
- ao abrir o checklist:
  - o sistema resolve o modelo pelo tipo de equipamento;
  - permite marcar `ok`/`discrepancia`, observacao e fotos por item;
  - persiste execucao e respostas por OS.
- as fotos de discrepancia seguem o padrao:
  - `public/uploads/checklist/{numero_os}/{tipo_checklist}/`
  - nome: `checklist_entrada_{tipo_checklist}_{numero_os}_{ordem}.jpg`
- empilhamento de modais no fluxo de checklist/foto:
  - checklist abre na camada base do fluxo tecnico;
  - modal de camera sobe acima do checklist;
  - modal de crop sobe acima da camera;
  - backdrop acompanha a camada ativa para impedir sobreposicao invertida.
  - avisos/alertas via SweetAlert2 calculam o `z-index` dinamicamente a partir da pilha ativa (`modal + backdrop`) para nunca ficar atras do modal de checklist.
- resiliencia operacional:
  - se a infraestrutura de checklist nao estiver migrada, o sistema evita erro fatal e retorna estado indisponivel com orientacao de migracao.

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
- Os wrappers internos com `border rounded-3/rounded` na aba `Equipamento` passaram por hardening de box-model (`box-sizing`, largura maxima e overflow controlado) para eliminar extrapolacao visual de bordas.
- Os relatos rapidos do campo de entrada ficam agrupados em dropdowns por categoria, preservando a mesma acao de inserir texto no textarea.
- A camada de view/JS da OS tambem passou por normalizacao de acentuacao em labels, avisos, atalhos e textos auxiliares para eliminar mojibake na interface.

## Fotos de acessorios na OS
- O card `Acessorios e Componentes (na entrada)` utiliza o mesmo fluxo padrao de foto da OS:
  - selecao por `Galeria`;
  - captura por `Camera`;
  - corte/ajuste no modal de crop antes de anexar.
- As miniaturas aparecem em tempo real no item do acessorio, com opcao de remocao antes do salvamento da OS.
- No backend, os arquivos sao mapeados por item (`fotos_acessorios[entry_id][]`) e persistidos com `UploadedFile` em:
  - `public/uploads/acessorios/<numero_os>/`
  - nome sequencial por tipo (`<tipo>_01`, `<tipo>_02`, ...).
- A leitura das fotos existentes considera fallback de pasta legada (`OS_<numero_os>`) para compatibilidade com registros anteriores.

## Integracao com CRM e inbox
- Alteracao de status dispara `CrmService` para registrar eventos/follow-ups no CRM.
- Mensagens da OS passam a refletir em `mensagens_whatsapp` com `conversa_id` quando houver thread.
- Conversas da Central podem ser vinculadas a OS para atendimento contextual sem sair do inbox.
