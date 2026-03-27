# Manual do Usuario - Ordens de Servico

## Visao geral
A Ordem de Servico (OS) e o registro principal do atendimento tecnico, da recepcao do equipamento ate a entrega.

### Identificador Unico (Numero da OS)
O sistema utiliza um padrao inteligente para numeracao: **`OSYYMMSSSS`**
- **YY**: Ano (Ex: 26)
- **MM**: Mes (Ex: 03)
- **SSSS**: Sequencia numerica (Ex: 0001)

Este numero e gerado automaticamente e a **sequencia reseta para 0001 no inicio de cada mes**, facilitando a organizacao e o controle de volume.

## Abertura de nova OS
Caminho: `Ordens de Servico > + Nova OS`

### Estrutura por abas
- `Cliente`
- `Equipamento`
- `Defeito`
- `Dados Operacionais`
- `Fotos`

Separacao atual do fluxo:
- Aba `Cliente`
  - Select2 de cliente com acoes `Novo` e `Editar`
  - card inteligente com nome, telefone e endereco do cliente selecionado
- Aba `Equipamento`
  - Select2 de equipamento (Cadastro: Cor e Foto de Perfil obrigatorios)
  - estado fisico do equipamento
  - acessorios e componentes na entrada
- Aba `Defeito`
  - relato do cliente
  - tecnico responsavel (atribuicao inicial)
  - defeitos comuns do tipo de equipamento na edicao
- Aba `Dados Operacionais`
  - prioridade
  - data de entrada
  - previsao de entrega
  - status
  - garantia (na edicao)

### Resumo lateral da OS
- O card `Resumo da OS` acompanha o preenchimento em tempo real.
- Cada linha exibe um indicador visual:
  - icone verde quando o item esta preenchido
  - icone vermelho quando o item ainda esta pendente
- Os topicos do resumo usam destaque tipografico mais forte, e a coluna de valor ganhou mais respiro visual para melhorar leitura de textos longos como o nome do equipamento.
- O resumo nao depende de atualizar a pagina para refletir o estado atual do formulario.

### Leitura visual das areas editaveis
- Cada bloco principal de preenchimento usa um fundo suave, borda discreta, cantos arredondados e sombra leve para sinalizar que se trata de uma superficie editavel.
- A paleta atual dessas superficies usa base branca com borda azul/cinza suave para reforcar visualmente a zona de preenchimento com leitura limpa e aspecto premium.
- Quando algum campo de uma secao entra em foco, o bloco inteiro recebe destaque visual mais forte, facilitando identificar qual area esta sendo editada.
- Os campos internos continuam claros e com foco azul suave para separar bem superficie e elemento de entrada.
- Sidebar, shell principal, painel de fotos e atalhos rapidos de relato seguem o mesmo padrao do design system da tela.

## Campos principais da abertura
| Campo | Obrigatorio | Uso |
|---|---|---|
| Cliente | Sim | Cliente dono da OS |
| Equipamento | Sim | Equipamento vinculado ao cliente (Cor e Foto obrigatorios) |
| Tecnico Responsavel | Nao | Tecnico que assume a OS |
| Prioridade | Sim | Baixa/Normal/Alta/Urgente |
| Data de Entrada | Sim | Data/hora de recebimento |
| Previsao de Entrega | Nao | Data prevista para retorno |
| Status | Sim | Estado inicial da OS |
| Relato do Cliente | Sim | Texto informado na recepcao |
| Estado fisico na entrada | Nao | Danos visuais observados |
| Acessorios na entrada | Nao | Itens recebidos com o equipamento |

## Registro de acessorios
O bloco `Acessorios e Componentes (na entrada)` usa botoes de insercao rapida.

Fluxo:
1. Clique em um botao rapido (`+ Chip`, `+ Capinha celular`, `+ Cabo`, etc.).
2. Preencha campos complementares quando existirem.
3. Salve o item.
4. Edite/remova quando necessario.
5. Adicione fotos por `Galeria` ou `Camera`.

Regras:
- Opcao `Equipamento recebido sem acessorios` marca a entrada sem itens.
- Fotos por acessorio usam crop/preview antes de salvar.
- Se o editor visual nao abrir corretamente, o sistema usa fallback automatico e adiciona a foto sem corte para nao travar a tela.
- Imagens ficam em `uploads/acessorios/OS_<numero_os>/`.

## Registro de estado fisico
O bloco `Estado fisico do equipamento` usa a mesma logica de item dinamico dos acessorios.

Fluxo:
1. Clique em um botao rapido (`+ Tela trincada`, `+ Arranhoes`, `+ Carcaca quebrada`, `+ Outro dano`).
2. Salve o item.
3. Edite/remova o item quando necessario.
4. Adicione fotos por item com `Galeria` ou `Camera`.

Regra especial:
- `Sem avarias aparentes na entrada` substitui os itens cadastrados (com confirmacao).
- Se o editor visual nao abrir corretamente, o sistema usa fallback automatico e adiciona a foto sem corte para nao travar a tela.

Armazenamento de fotos:
- `uploads/estado_fisico/OS_<numero_os>/estado_<slug_os>_<sequencia>.<ext>`

## Relato do cliente
Na abertura da OS, o relato pode ser montado com selecao rapida por categoria.

Fonte dos itens:
- Modulo `Gestao de Conhecimento > Defeitos Relatados`

Comportamento:
- Os itens ficam agrupados por categoria em dropdowns.
- Clicar em uma opcao adiciona a frase no textarea.
- O tecnico pode editar manualmente o texto livremente.

## Aba Fotos (abertura)
O upload de fotos de entrada usa o padrao unico do sistema:
- Galeria
- Camera
- Crop antes de salvar
- Preview com remocao
- Fallback automatico sem corte quando o modal/editor visual falhar

Observacao tecnica de UX:
- A abertura do editor de corte na OS segue o mesmo comportamento do cadastro de equipamentos (`/equipamentos/novo`), para manter consistencia entre os fluxos de foto do sistema.
- A abertura da camera na OS reutiliza o mesmo padrao de modal controlado e, se o navegador bloquear a interface da camera, o sistema informa o motivo por alerta e pelo console.

## Visualizacao da OS
Caminho: `/os/visualizar/{id}`

Agora a OS exibe o estado fisico em dois pontos:
- `Informacoes > Estado fisico na entrada` (descricao + fotos por item)
- `Fotos de Entrada > Fotos do Estado fisico`

Tambem exibe:
- Fotos da entrada geral
- Fotos de acessorios

## Listagem de OS responsiva
Caminho: `/os`

Melhorias aplicadas:
- Barra de filtros horizontal premium com busca global, status detalhado e acoes rapidas de aplicar, limpar e abrir filtros avancados.
- Aplicacao em tempo real sem reload completo da pagina (DataTables server-side + AJAX).
- Persistencia dos filtros na URL e no navegador (localStorage), mantendo contexto ao atualizar ou compartilhar o link.
- Chips de filtros ativos com remocao individual e acao `Limpar todos`.
- Contador de resultados atualizado dinamicamente acima da tabela.
- Overlay de carregamento suave durante aplicacao dos filtros.

### Comportamento por faixa de tela

- `>= 1400px`
  - tabela completa, com leitura densa e distribuicao ampla de colunas;
  - filtros em linha, com boa area de respiro.
- `1200px a 1399px`
  - tabela em modo desktop compacto;
  - `Valor Total` sai da grade principal para priorizar leitura operacional;
  - quando a largura util apertar, `Relato` e `Acoes` tambem podem migrar para o painel expansivel da linha;
  - acoes dos filtros quebram para a linha seguinte quando necessario.
- `992px a 1199px`
  - modo notebook;
  - tabela continua tabular;
  - `Relato`, `Valor Total` e `Acoes` deixam a grade principal;
  - quando a largura util do card ficar mais apertada, `Equipamento` tambem migra para o painel expansivel da linha;
  - em notebook mais estreito, `Datas` tambem pode sair da grade antes de qualquer compressao horizontal;
  - filtros reorganizados em duas colunas, com botoes em linha propria.
- `768px a 991px`
  - modo tablet;
  - tabela continua tabular;
  - colunas principais ficam em `N OS`, `Cliente`, `Datas`, `Status` e `Acoes`;
  - `Equipamento`, `Relato` e `Valor Total` passam para a expansao da linha;
  - em tablet mais estreito, `Datas` tambem pode migrar para a expansao antes de a grade comprimir o texto.
- `576px a 767px`
  - a listagem muda para cards;
  - filtros deixam de usar a barra desktop e passam para o botao `Filtrar ordens`;
  - pagina passa a priorizar toque e leitura vertical.
- `<= 575px`
  - cards com paddings menores, botoes maiores e hierarquia tipografica reforcada;
  - ajustes extras continuam em `430px`, `390px`, `360px` e `320px` para evitar aperto visual.

### Filtros

- Em desktop e notebook, `Busca global` e `Status detalhado` se reorganizam sem sobreposicao.
- Em tablet, o card de filtros quebra em duas linhas com acoes distribuidas em grade.
- Em mobile, os filtros abrem em drawer lateral (`Filtrar ordens`), com campos em largura total e foco em toque.
- Filtros extras disponiveis:
  - Macrofase
  - Estado do fluxo
  - Situacao operacional (`Em triagem`, `Em atendimento`, `Finalizado`, `Equipamento entregue`)
  - Tecnico responsavel
  - Tipo de servico
  - Data de abertura (de/ate)
  - Faixa de valor (minimo/maximo)

### Listagem em tabela (desktop, notebook e tablet)

- A listagem ganhou coluna de controle responsivo com botao `+` / `-`.
- A primeira coluna operacional agora exibe `Foto`, usando a miniatura principal do equipamento.
- Clicar na miniatura abre um visualizador com duas abas:
  - `Fotos do Equipamento`: todas as fotos de perfil cadastradas para o equipamento, com destaque da principal
  - `Fotos da Abertura`: fotos tiradas durante a abertura da OS
- O botao aparece somente quando houver colunas escondidas para aquele breakpoint.
- Ao expandir a linha, a interface mostra um painel interno com os campos ocultos, sem perder badges e acoes.
- A coluna `N OS` permanece fixa em uma unica linha, sem quebrar o numero da ordem.
- A largura de `N OS` foi refinada para ocupar apenas o necessario ao codigo completo da ordem.
- A coluna `Cliente` ganhou prioridade de largura.
- Quando o nome do cliente tiver quatro palavras ou mais, a exibicao passa a quebrar em duas linhas a partir da segunda palavra para preservar leitura:
  - `Otavio Rosa`
  - `Dos Santos`
- A largura-base de `Cliente` tambem foi reduzida levemente para equilibrar melhor o conjunto da grade sem perder essa quebra semantica.
- A coluna `Equipamento` exibe:
  - `Tipo`
  - `Marca`
  - `Modelo`
- A coluna `Relato` quebra linha naturalmente dentro da celula:
  - tenta manter ate 4 linhas;
  - se ainda exceder esse espaco, a fonte e reduzida automaticamente para caber melhor.
- A coluna `Datas` substitui `Data Abertura` e mostra:
  - `Entrada`
  - `Prazo`
  - `Entrega`
- O indicador de `Prazo` usa destaque contextual:
  - verde: dentro do prazo
  - amarelo: faltam ate 2 dias
  - laranja: vence hoje
  - vermelho: prazo estourado
- O indicador de `Entrega` destaca se a conclusao ocorreu dentro ou fora do prazo:
  - verde: entregue no prazo
  - vermelho: entregue fora do prazo
- O breakpoint da tabela agora e decidido pela largura util real do card da listagem:
  - se a sidebar, paddings e filtros consumirem espaco em notebook, a tabela antecipa a ocultacao de colunas em vez de comprimir o texto.
- Em notebook mais estreito:
  - `Valor Total`, `Relato` e `Acoes` saem primeiro da grade;
  - `Equipamento` tambem sai da grade quando a largura util ficar critica;
  - `Datas` tambem pode migrar para o painel expansivel antes de a tabela entrar em colapso.
- Em tablet:
  - a coluna `Foto` pode migrar para o painel expansivel antes de forcar compressao horizontal da tabela.
- O texto auxiliar `Alterar status` fica oculto nos perfis comprimidos, preservando a area util da coluna `Status`.
- As colunas `Status` e `Valor Total` foram compactadas para ocupar apenas a largura necessaria ao badge e ao valor monetario, devolvendo mais espaco util para `Cliente`.
- As colunas `Acoes`, `Relato`, `Status`, `Datas` e `Equipamento` agora seguem regra binaria: ou aparecem inteiras na grade principal, ou sao movidas por completo para o expansor `+`, sem ficar cortadas na borda direita.
- Quando houver falta de largura residual na grade, a prioridade de recolhimento passa a ser:
  - `Acoes`
  - `Relato`
  - `Status`
  - `Datas`
  - `Equipamento`
- A tabela deixa de depender de rolagem horizontal como estrategia principal; o expansor `+` passa a concentrar colunas operacionais menos prioritarias quando a largura real nao comportar tudo.

### Listagem em cards (mobile)

- A tabela tradicional deixa de ser forçada no celular.
- Cada OS passa a ser exibida como card responsivo com:
  - numero da OS
  - foto principal do equipamento
  - status
  - cliente
  - equipamento
  - datas principais
  - relato
  - acoes
- Os cards preservam espacamento, area de toque e hierarquia visual para leitura rapida em telas pequenas.
- Nao ha dependencia de zoom ou fonte minuscula para manter usabilidade.

Atualizacao vigente do layout mobile:
- o card principal agora exibe apenas:
  - foto principal do equipamento
  - numero da OS
  - nome do cliente
  - modelo do equipamento
  - botao `+`
- ao tocar no `+`, o sistema abre os detalhes complementares:
  - equipamento completo
  - datas
  - status
  - relato
  - valor total
  - acoes
- essa organizacao substitui a leitura expandida anterior na face principal do card.
- no painel expandido mobile, `Equipamento` passou a usar bloco vertical de leitura (label acima do valor), evitando empilhamento de letras.
- o texto tecnico `Campo 1` nao e mais exibido no topo da linha expandida.
- o child row mobile agora e reconhecido tanto por `child` quanto por `os-responsive-child-row`, garantindo que as regras de largura e leitura sempre sejam aplicadas no detalhe expandido.

### Alteracao de status pela listagem

- A coluna `Status` permanece interativa para usuarios com permissao de edicao.
- Clicar no badge abre modal de alteracao de status.
- O modal lista apenas os destinos permitidos para aquele ponto do fluxo.
- A alteracao acontece por AJAX, sem recarregar a pagina inteira.
- As regras de transicao visiveis nesse modal seguem o fluxo configurado em `Gestao de Conhecimento > Fluxo de Trabalho OS`.

### Sidebar e shell da pagina

- Em tablet e mobile, a sidebar passa a operar como overlay:
  - recolhe automaticamente;
  - abre pelo botao hamburguer;
  - fecha ao clicar fora;
  - nao desperdiça largura do conteudo principal.
- Em notebook e desktop, o conteudo principal passou a usar corretamente a largura util restante da viewport, sem crescer alem do espaco disponivel quando a sidebar esta fixa ou recolhida.
- Na rota `/os`, em desktop e notebook, a sidebar inicia recolhida automaticamente para liberar mais largura util da listagem.
- Ainda na rota `/os`, ao passar o mouse ou navegar com foco pela sidebar, o menu expande temporariamente sobre o conteudo e volta a recolher ao sair do menu.

### Nova OS por modal (sem redirecionamento)
- O botao `+ Nova OS` da listagem abre um modal com a tela de abertura em modo embed.
- Na rota `/os`, o atalho global `Nova OS` da navbar fica oculto para evitar duplicidade visual; o usuario passa a ver apenas o botao `+ Nova OS` da propria pagina de listagem.
- O fluxo ocorre sem troca de pagina, preservando contexto de filtros e tabela.
- O modal nao exibe botao de redirecionamento para pagina externa.
- Fechar o modal retorna para a listagem exatamente no mesmo estado visual.
- No modo embed, os botoes `+ Novo` e `Editar` do cliente abrem o cadastro rapido em modal Bootstrap dentro do proprio iframe, sem redirecionar para o fim da pagina.
- No campo `Cliente`, o cadastro rapido abre somente ao clicar nos botoes `+ Novo` ou `Editar`; clicar no Select2 ou na area do campo nao deve disparar modal.
- No campo `Equipamento`, o modal de cadastro rapido abre somente ao clicar no botao `+ Novo`; clicar no Select2 ou na area do campo nao deve disparar o modal.
- Ao atualizar um cliente por esse atalho, o nome e refletido imediatamente no seletor da OS, no texto visivel do `Select2`, na lista de busca aberta e no card informativo da aba `Cliente`, sem precisar atualizar a pagina.
- A listagem `/os` ao fundo tambem e recarregada por AJAX, sem sair do modal.
- O campo `Nome / Razao Social` do cadastro rapido de cliente aplica capitalizacao automatica em title case, independente de caps lock ou shift.
- O campo `CEP` do cadastro rapido de cliente consulta automaticamente o endereco e preenche `Endereco`, `Bairro`, `Cidade` e `UF` no proprio modal, levando o foco para `Numero`.
- No cadastro rapido de equipamento, se faltarem `Tipo`, `Marca` ou `Modelo`, o sistema interrompe o salvamento, volta automaticamente para a aba `Info` e destaca o campo pendente.
- Os campos `Marca` e `Modelo` do cadastro rapido de equipamento exibem o botao verde `+ Adicionar` ao lado da label para abrir o cadastro contextual.
- O campo `Nº de Série` do modal rapido foi normalizado visualmente.
- A `Senha de Acesso` do cadastro rapido pode ser salva em:
  - `DESENHO`: grade 3x3 no padrao Android, persistida como `desenho_1-4-7-8-9`
  - `TEXTO`: senha livre digitada normalmente
  - a grade visual foi reduzida para um bloco compacto dentro do modal
- No cadastro rapido de equipamento vinculado ao cliente, as abas `Cor` e `Foto` sao obrigatorias antes do salvamento.
- Se faltar cor ou foto no modal `Cadastrar Novo Equipamento`, o sistema bloqueia o envio, abre automaticamente a aba pendente e posiciona o foco no campo correto para conclusao.
- O `Relato do cliente`, o `Tecnico Responsavel` e os `Defeitos comuns do tipo de equipamento` ficam na aba **Defeito**, enquanto a aba **Dados Operacionais** fica dedicada apenas ao andamento operacional da OS.
- Campos Select2 com nomes longos agora truncam o texto visualmente com `...`, mantendo o nome completo no tooltip nativo.
- Ao clicar em `Abrir OS` ou `Atualizar`, o formulario entra em modo de envio: mostra loading visual padrao, desabilita as acoes do rodape e ignora novos cliques ate o salvamento terminar.
- Enquanto a tela estiver em uso, o sistema faz sincronismo discreto da sessao para evitar que formularios longos expirem no meio do preenchimento.
- Se a sessao realmente expirar por inatividade, um aviso SweetAlert2 aparece imediatamente com orientacao clara para voltar ao login, antes de o usuario perder tempo tentando salvar.

## Responsividade da abertura de OS
Caminho: `/os/nova`

Melhorias aplicadas:
- Layout principal padronizado com coluna lateral + formulario (`ds-split-layout`).
- Em notebook: coluna lateral reduzida com formulario mais amplo.
- Em tablet/mobile: empilhamento automatico (lateral acima, formulario abaixo).
- Abas com rolagem horizontal (`ds-tabs-scroll`) para evitar quebra visual.
- Blocos de dados (`os-data-section`) com espacamento ajustado por breakpoint.
- Botoes de acao da OS (`Abrir`, `Cancelar`, `Limpar rascunho`) com comportamento empilhado no mobile.

## Responsividade da visualizacao de OS
Caminho: `/os/visualizar/{id}`

Melhorias aplicadas:
- Estrutura principal convertida para split responsivo (painel de fotos + conteudo).
- Cards de status/PDF/WhatsApp com reorganizacao 1-2-3 colunas por breakpoint.
- Formulario rapido de status com quebra inteligente (sem apertar select e botao).
- Tabs de secoes com navegacao horizontal em telas menores.

## Edicao da OS
Na edicao (`/os/editar/{id}`), os dados de abertura podem ser ajustados e os registros de estado fisico/acessorios sao persistidos novamente.

## Fluxo operacional por macrofases
O status da OS foi padronizado em macrofases:
- recepcao
- diagnostico
- orcamento
- execucao
- interrupcao
- qualidade
- concluido
- finalizado_sem_reparo
- encerrado
- cancelado

## Regras de transicao de status
- O sistema valida transicoes permitidas entre status.
- Mudancas invalidas sao bloqueadas.
- Cada alteracao gera registro no historico da OS com usuario e data/hora.
- Na listagem `/os`, o badge de status pode ser clicado para abrir o modal de troca de status, respeitando exatamente essas regras.

## Configuracao do fluxo de trabalho da OS
Caminho: `Gestao de Conhecimento > Fluxo de Trabalho OS`

O administrador pode definir:
- ordem visual dos status
- se o status esta ativo
- se o status e final
- se o status representa pausa
- para quais outros status cada etapa pode avancar ou retornar

Regras:
- se nenhum destino personalizado for salvo, o sistema usa fallback automatico pela `ordem_fluxo`
- nesse fallback, cada status pode ir para o anterior e para o proximo da sequencia
- quando houver destinos configurados manualmente, o modal de troca de status da listagem passa a usar somente essas transicoes

## Comunicacao WhatsApp na OS
Na tela de visualizacao (`/os/visualizar/{id}`):
- Bloco `WhatsApp` para envio manual por template ou texto livre.
- Opcao de selecionar um PDF ja gerado da OS para enviar junto como anexo.
- Botao rapido de envio WhatsApp em cada documento da lista `Documentos PDF`.
- Historico de envios da OS com status e tipo de conteudo (texto, pdf ou texto+pdf).
- Envio automatico em status-chave (quando configurado): abertura, aguardando autorizacao, aguardando peca, pronto para retirada e entrega.

## Documentos PDF da OS
Na tela de visualizacao (`/os/visualizar/{id}`):
- Bloco `Documentos PDF` para gerar e listar versoes.
- Tipos disponiveis:
  - abertura
  - orcamento
  - laudo
  - entrega
  - devolucao_sem_reparo

Os arquivos ficam em:
- `public/uploads/os_documentos/OS_<numero_os>/`

## Qualidade visual de textos
- A interface da Nova OS e da edicao da OS teve normalizacao de acentuacao em alertas, labels e textos auxiliares.
- Mensagens como `Atenção`, `Observações`, `Solução Aplicada`, `Salvar Alterações` e `Descrição` voltaram a ser exibidas corretamente em UTF-8.
- Os agrupamentos de sugestoes e atalhos do modal de equipamento tambem foram revisados para remover caracteres corrompidos.
