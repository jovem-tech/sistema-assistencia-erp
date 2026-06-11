# Manual do UsuÃ¡rio - Ordens de ServiÃ§o

## VisÃ£o geral

A Ordem de ServiÃ§o (OS) Ã© o registro central do atendimento tÃ©cnico, desde a entrada do equipamento atÃ© a entrega, cancelamento ou devoluÃ§Ã£o.

O fluxo operacional atual cobre:

- recepÃ§Ã£o;
- diagnÃ³stico;
- orÃ§amento;
- execuÃ§Ã£o;
- qualidade;
- encerramento.

## Onde acessar

- listagem principal: `Ordens de ServiÃ§o`
- nova abertura pela listagem: botÃ£o `+ Nova OS`
- ediÃ§Ã£o: `/os/editar/{id}`
- visualizaÃ§Ã£o: `/os/visualizar/{id}`

## Identificador da OS

O nÃºmero segue o padrÃ£o `OSYYMMSSSS`:

- `YY`: ano;
- `MM`: mÃªs;
- `SSSS`: sequÃªncia do mÃªs.

Exemplo: `OS26040010`.

## Listagem de OS (`/os`)

### Responsividade multitelas de 09/06/2026

- a listagem `/os` foi reequilibrada para desktop widescreen, notebook `1366x768`, tablet e celulares pequenos;
- o cabecalho da pagina, o botao `Nova OS`, os filtros, a paginacao e a grade passam a se reorganizar sem depender de corte horizontal da pagina;
- em resolucoes intermediarias, a tabela prioriza leitura operacional e pode mover acoes secundarias para o detalhe expansivel da linha, preservando a usabilidade;
- no mobile, os cards da OS voltam a quebrar nome do cliente, telefone, equipamento, datas, badges e resumo financeiro dentro da largura real do aparelho, sem rolagem lateral indevida;
- o modal de baixa da OS tambem teve seus estilos migrados para o layout global da listagem, mantendo o mesmo comportamento responsivo entre telas.

### Hierarquia tipografica da listagem em 09/06/2026

- a grade recebeu uma nova escala tipografica para separar melhor dados principais e secundarios;
- `numero da OS`, `nome do cliente`, `tipo do equipamento`, `datas principais`, `status principal` e `total da OS` passaram a ter mais destaque visual;
- labels de apoio como `Tipo`, `Equip.`, `Entrada`, `Prazo`, `Recebido`, `Adiantamento` e `Saldo` ficaram mais discretas, sem competir com o conteudo operacional;
- o telefone principal do cliente passou a usar fonte um pouco menor que o nome, preservando o atalho do WhatsApp com menos competicao visual dentro da coluna `Cliente`;
- badges, datas tecnicas e resumo financeiro ganharam mais legibilidade em desktop, tablet e mobile, evitando leitura â€œmicroscopicaâ€ na fila.

### Correcao de 27/04/2026

- a busca global da listagem voltou a exibir o placeholder corretamente em pt-BR;
- o carregamento da grade foi protegido contra reinicializacao dupla do DataTables;
- o script da listagem passou a ser recarregado com controle de versao por alteracao real do arquivo, evitando persistencia de cache antigo no navegador;
- o modal de alteracao de status tambem recebeu nova normalizacao dos textos renderizados dinamicamente;
- as abas, labels e mensagens fixas do modal de alteracao de status e do painel de orcamento foram revisadas novamente em pt-BR para remover caracteres quebrados em equipamento, historico e comunicacao;
- a tela deixou de disparar o alerta `Cannot reinitialise DataTable` ao abrir `/os`.

### Estabilidade da tela

Na release `2.16.5`, a listagem, a edicao e a visualizacao da OS passaram por um restauro tecnico apos uma rodada de auditoria textual em pt-BR:

- a rota `/os` deixou de cair em erro `500`;
- a tela `/os/editar/{id}` voltou a abrir com o formulario completo;
- a tela `/os/visualizar/{id}` voltou a carregar os blocos de contexto, timeline e documentos sem `ParseError`;
- textos legados com caracteres quebrados foram normalizados em labels, botoes, avisos e mensagens operacionais do modulo.

### O que a listagem mostra

- foto do equipamento;
- nÃºmero da OS, agora exibido logo abaixo da foto do equipamento;
- cliente e telefone principal;
- equipamento;
- datas principais;
- status operacional;
- mini resumo financeiro;
- aÃ§Ãµes de visualizar e editar.

Na coluna `Equipamento`:

- quando o cadastro tecnico de `Desktop montado` gerar um nome muito longo, a grade passa a mostrar apenas `tipo de gabinete + chipset + processador`;
- a descricao exibida em `Equip.` e dividida em linhas controladas de ate `3 palavras`, preservando a leitura de termos tecnicos longos sem invadir as demais colunas;
- os demais detalhes continuam preservados no cadastro e podem ser vistos ao clicar no equipamento para abrir o modal/ficha completa.

### Busca global

Na barra de busca da listagem `/os`:

- a pesquisa continua aceitando cliente, equipamento, nÃºmero da OS e OS legado;
- qualquer sequencia numerica digitada que exista no telefone principal do cliente tambem passa a localizar a OS correspondente;
- quando o usuario digita apenas os numeros do telefone, a busca encontra a OS mesmo se o numero estiver mascarado na exibicao.

### Contato rapido na coluna `Cliente`

Na listagem `/os`:

- o nome do cliente continua abrindo a ficha completa em modal interno;
- nomes longos passam a ser exibidos em linhas controladas de ate `3 palavras`, evitando quebra irregular dentro da coluna;
- o telefone principal continua visivel logo abaixo do nome e aciona o modal rapido de WhatsApp da OS sem sair da fila;
- o numero deixa de ser cortado com `...` e passa a caber na propria celula.

No modal de WhatsApp, a equipe pode:

- escolher um `template pronto`;
- escrever uma `mensagem personalizada`;
- anexar um `documento salvo da OS`;
- ou enviar o `PDF consolidado da impressao (A4)` quando nenhum documento salvo for escolhido.

Observacao de envio pela Evolution API:

- se o cliente estiver com telefone salvo apenas como DDD + numero, o sistema adiciona o DDI `55` automaticamente no envio;
- se a Evolution rejeitar a mensagem, o operador ve um alerta com a falha operacional sem sair da OS;
- erros de validacao, como telefone vazio ou mensagem ausente, continuam sendo exibidos antes do envio.

### Leitura financeira na coluna `Valor`

Na mesma listagem:

- a coluna `Valor` passou a mostrar um mini resumo financeiro da OS;
- o destaque principal fica em `Total OS`, com o valor em negrito;
- abaixo, a grade mostra `Recebido`, `Adiantamento` quando existir e `Saldo`, com cores de alerta quando o saldo ainda estiver pendente;
- ao clicar nessa coluna, o operador continua abrindo o modal rapido de orcamento da ordem.

### Status exibido na listagem

A coluna de status agora concentra o contexto operacional completo da OS:

- status atual da OS;
- estado de fluxo da OS;
- status do orÃ§amento vinculado, quando existir;
- nÃºmero do orÃ§amento vinculado.

Regras prÃ¡ticas:

- o badge principal sempre mostra o status real salvo na OS;
- o badge de fluxo continua mostrando a etapa operacional real da OS;
- o status do orÃ§amento vinculado aparece apenas como contexto comercial secundÃ¡rio;
- orÃ§amento criado/vinculado e ainda em andamento pode sugerir `Aguardando AutorizaÃ§Ã£o`, sem substituir o status principal da OS;
- orÃ§amento `Aprovado` ou `Convertido` pode sugerir `Aguardando Reparo`, sem substituir o status principal da OS na listagem;
- depois que a equipe avanÃ§a a OS para etapas como `Em ExecuÃ§Ã£o do ServiÃ§o`, `Aguardando PeÃ§a`, `Testes` ou fases posteriores, a listagem nÃ£o deve mais voltar o status automaticamente para `Aguardando Reparo` sÃ³ porque o orÃ§amento continua aprovado;
- quando a OS nÃ£o tiver `valor_final` preenchido, a listagem pode usar o total do orÃ§amento vinculado como fallback visual.
- quando o status comercial do orÃ§amento for grande, o badge quebra em mais de uma linha dentro da prÃ³pria coluna `Status / OrÃ§amento`, sem invadir a coluna `Valor`.

Na prÃ¡tica:

- o orÃ§amento continua sugerindo o ponto de entrada do reparo;
- a conduÃ§Ã£o manual do reparo passa a prevalecer depois que a OS sai da fase inicial de execuÃ§Ã£o;
- a coluna `Status` nÃ£o deve mascarar o status real da OS com o status sugerido do orÃ§amento.

### Leitura do prazo na coluna `Datas`

O badge `Prazo` da listagem `/os` segue a seguinte regra operacional:

- enquanto a manutencao ainda estiver em andamento, o texto `Atrasado ha X dias` continua sendo calculado em relacao a data atual;
- quando a OS ja tiver sido entregue, o atraso deixa de ser acumulado e passa a considerar `data_entrega`;
- quando a manutencao ja tiver sido concluida, mas a OS ainda nao tiver sido entregue, o atraso deixa de correr na data atual e passa a considerar `data_conclusao`;
- quando a OS estiver em etapa conclusiva, a coluna passa a exibir tambem a linha `Conclusao`, separando visualmente a data em que a manutencao terminou da data de retirada/entrega;
- se a OS legada estiver em status de manutencao encerrada, mas ainda sem `data_conclusao` preenchida, a listagem usa `status_atualizado_em` apenas como fallback visual para impedir atraso infinito.

Na pratica:

- `Reparo Concluido`, `Reparado, Disponivel na Loja`, `Irreparavel`, `Irreparavel, Disponivel para Retirada`, `Reparo Recusado` e equivalentes deixam de parecer manutencoes ainda em execucao;
- a leitura fica no formato `Entrada`, `Prazo`, `Conclusao` e `Entrega`, quando houver conclusao operacional;
- o texto `Atrasado ha X dias` fica reservado para OS realmente em andamento;
- quando a manutencao terminou fora do prazo, o badge passa a mostrar `Atraso de X dias`, congelado na data de conclusao/entrega.
- `Conclusao` e `Entrega` agora aparecem com visual neutro, sem fundo colorido, deixando o destaque em estilo badge restrito ao `Prazo`.

### Modal `Atualizar prazos da OS`

Ao clicar sobre a coluna de datas na listagem `/os`, o sistema abre um modal rÃ¡pido para ajuste da previsÃ£o.

Campos e regras atuais:

- `Data de entrada`: exibida como referÃªncia operacional e enviada pelo sistema como base de comparaÃ§Ã£o;
- `Atalho de prazo`: ajuda a recalcular rapidamente a `PrevisÃ£o`;
- `PrevisÃ£o`: campo principal da alteraÃ§Ã£o;
- `Entrega`: somente leitura, preservada para o fluxo correto de encerramento;
- `Motivo da alteraÃ§Ã£o`: obrigatÃ³rio e registrado no histÃ³rico da OS;
- `AutorizaÃ§Ã£o administrativa`: aparece somente para perfis que nÃ£o sÃ£o administradores.

Na prÃ¡tica:

- nÃ£o Ã© mais necessÃ¡rio adivinhar por que o backend recusou a alteraÃ§Ã£o;
- quando o usuÃ¡rio nÃ£o for administrador, o prÃ³prio modal passa a pedir `administrador` e `senha`;
- o histÃ³rico tÃ©cnico da OS registra o motivo informado junto com a alteraÃ§Ã£o de prazo.

### Modal `Baixa da OS`

Ao clicar no botÃ£o `Baixa da OS` na listagem `/os`, o sistema agora abre um modal operacional prÃ³prio para concluir a ordem sem sair da fila.

O que o modal reÃºne:

- contexto resumido da OS, com cliente, equipamento e badges atuais;
- escolha de `como encerrar` a ordem:
  - `Equipamento entregue reparado`
  - `Equipamento devolvido sem reparo`
  - `Equipamento descartado`
- `data da entrega`;
- `observaÃ§Ãµes da baixa`;
- opÃ§Ã£o para `enviar WhatsApp com o PDF consolidado da OS`;
- opÃ§Ã£o para `agendar retorno pÃ³s-serviÃ§o`;
- bloco de `recebimentos e adiantamentos`;
- resumo financeiro com `custos`, `taxas de cartÃ£o`, `recebimento lÃ­quido` e `lucro estimado`.

Comportamento prÃ¡tico:

- o operador pode concluir a baixa sem registrar recebimento imediato;
- o modal permite registrar um ou vÃ¡rios recebimentos na mesma baixa;
- o botÃ£o `Adicionar adiantamento` abre uma escolha rÃ¡pida entre `Adiantamento total` e `Sinal`;
- cada lanÃ§amento da baixa pode ser classificado como `Recebimento da baixa`, `Adiantamento` ou `Sinal`;
- somente a classificaÃ§Ã£o `Recebimento da baixa` altera o status operacional da OS;
- `Adiantamento` e `Sinal` reduzem o saldo financeiro e entram no `Fluxo de Caixa`/`DRE`, mas nÃ£o alteram status, data de entrega, baixa tÃ©cnica ou cobranÃ§a automÃ¡tica;
- se a baixa operacional ficar parcial, a OS passa para `entregue_pagamento_pendente` e continua aberta para cobranÃ§a;
- se a baixa operacional quitar o saldo, a OS muda para o status final selecionado, como `entregue_reparado`, `devolvido_sem_reparo` ou `descartado`;
- antes de confirmar a baixa, o sistema pergunta se o operador deseja enviar a mensagem no WhatsApp; quando a resposta for positiva, a mensagem segue com o PDF consolidado da impressao A4 da OS;
- antes de confirmar, a tela mostra o `status projetado` ou `Sem alteraÃ§Ã£o de status`, o `saldo remanescente` e o `lucro estimado da OS`.
- se a OS estiver com campos financeiros zerados, mas ja houver `orcamento aprovado` ou `convertido`, o modal usa automaticamente esse total aprovado para preencher `valor da OS`, `saldo` e `lucro estimado`.
- em telas menores ou janelas com pouca altura, o modal libera rolagem interna para manter `Recebimentos`, `Resumo financeiro` e `RodapÃ© de aÃ§Ã£o` acessÃ­veis sem cortar conteÃºdo.
- quando a baixa for concluida como `Equipamento descartado`, o equipamento vinculado passa a ser encerrado automaticamente e sai de operacao para novas OS;
- se esse equipamento voltar a funcionar depois, a equipe pode reativa-lo pela propria ficha do cadastro.
- o encerramento automatico e a eventual volta a operacao tambem ficam registrados na timeline do proprio equipamento.

### Data de entrega na abertura, edicao e visualizacao

- quando o status da OS for `Equipamento entregue reparado`, `Devolvido sem reparo`, `Descartado` ou `Entregue pendencia financeira`, o campo `Data de entrega` passa a aparecer logo abaixo do status na tela de abertura e edicao;
- se a OS ja estiver em um desses status e o campo vier vazio, o sistema sugere a data atual para evitar salvar uma entrega sem referencia;
- na tela de visualizacao, a mesma data continua aparecendo no card de datas, junto de entrada, previsao e conclusao;
- quando a OS voltar para um status em andamento, o campo deixa de ser exibido na edicao, mas o historico da OS continua preservado.

### Pagamento pendente apÃ³s a baixa

Regra operacional atual:

- `baixa tÃ©cnica concluÃ­da` nÃ£o significa `encerramento financeiro definitivo`;
- quando a OS for entregue com saldo pendente, ela fica em `entregue_pagamento_pendente`;
- essa OS deve ser tratada como `concluÃ­da`, mas ainda `nÃ£o encerrada`;
- a equipe pode continuar acompanhando a pendÃªncia financeira atÃ© a quitaÃ§Ã£o;
- quando o saldo chega a zero, o sistema sincroniza automaticamente o status final da OS.

### CobranÃ§a automÃ¡tica da OS pendente

Quando a baixa termina com saldo financeiro em aberto:

- o sistema agenda cobranÃ§as automÃ¡ticas em `1`, `3` e `5` dias;
- a rÃ©gua usa o telefone do cliente cadastrado na OS;
- se o cliente nÃ£o tiver telefone vÃ¡lido, a baixa Ã© concluÃ­da mesmo assim, mas o sistema avisa que a cobranÃ§a automÃ¡tica nÃ£o poderÃ¡ funcionar corretamente.

### Atualizacao automatica do status do orcamento

Quando a resposta do cliente acontece pelo link publico do orcamento:

- a equipe nao precisa atualizar a pagina manualmente;
- a listagem `/os` detecta a resposta em tempo real e recarrega a grade automaticamente;
- o badge comercial de orcamento na coluna `Status` volta sincronizado sem `F5`;
- se o modal `Alterar status da OS` estiver aberto para a mesma ordem, o contexto do modal tambem e reidratado;
- a navbar mostra uma notificacao nova no sino ao lado da foto do perfil;
- ao abrir o dropdown, o operador ve qual orcamento foi respondido e qual foi o novo status comercial;
- ao clicar na notificacao, o ERP abre primeiro um modal com o teor da atualizacao;
- se a notificacao possuir conversa ou tela vinculada, o modal oferece a acao `Abrir conversa`, levando o operador para a rota correta do ERP sem cair em caminho invalido;
- o dropdown tambem oferece `Marcar todas` e `Limpar lidas` para organizar o inbox do sino sem recarregar a pagina.

### Modal `Alterar status da OS`

Ao clicar em `Alterar status` na coluna de status da listagem `/os`, a janela operacional agora concentra:

- cabecalho com `Alterar status da OS #OS...`;
- resumo de cliente e equipamento da ordem;
- badges atuais da OS;
- uma area de trabalho com `3 abas internas`:
  - `Acoes rapidas`;
  - `Solucao e diagnostico`;
  - `Gerenciamento do Orcamento`;
- timeline do fluxo e historico recente.

Comportamento pratico:

- a aba inicial padrao e `Acoes rapidas`;
- a aba `Acoes rapidas` concentra `Proxima etapa`, `Cancelar`, `Status atual da OS`, `Fluxo normal sugerido`, `Fluxo selecionado` e a escolha manual do destino;
- a aba `Solucao e diagnostico` concentra `Procedimentos executados`, `Solucao aplicada` e `Diagnostico`;
- a aba `Gerenciamento do Orcamento` concentra o resumo e as acoes de criar, editar ou visualizar o orcamento;
- o modal continua respeitando o fluxo permitido para troca de status;
- os procedimentos inseridos passam a registrar automaticamente data/hora e tecnico atual da OS;
- os labels visiveis do modal foram padronizados em pt-BR, incluindo `AÃ§Ãµes rÃ¡pidas`, `SoluÃ§Ã£o e diagnÃ³stico`, `Gerenciamento do OrÃ§amento`, `HistÃ³rico e progresso` e `Ãšltimas movimentaÃ§Ãµes`;
- ao abrir `Editar orcamento` ou `Visualizar`, a janela do orcamento sobe na frente do modal de status para evitar sobreposicao invertida;
- quando o orcamento e salvo em modo embed, o resumo dentro do modal de status e atualizado automaticamente;
- quando o cliente responde o orcamento pelo link publico, o contexto comercial da ordem volta sincronizado assim que a notificacao em tempo real chega ao ERP.
- quando o orcamento vinculado estiver `Convertido`, a acao `Editar orcamento` continua disponivel para ajustes diretos no mesmo registro, sem obrigar a abrir apenas em modo de consulta.
- se esse orcamento `Convertido` for alterado, o sistema abre nova rodada comercial no mesmo registro e volta o status do orcamento para `Reenviar orcamento`, refletindo novamente a dependencia de autorizacao do cliente.
- depois que esse reenvio e efetivamente disparado ao cliente, o status comercial deixa `Reenviar orcamento` e passa para `Aguardando aprovacao`, mantendo a OS na leitura de espera por autorizacao.

### Largura e leitura da tabela

Na tabela principal `/os`:

- `Foto / OS` concentra a thumbnail e o numero principal da ordem no mesmo bloco visual;
- o numero operacional abaixo da foto deixou de exibir `#`, recebeu reforco de fonte e permanece com leitura destacada na mesma coluna;
- a coluna separada `N OS` continua existindo apenas como apoio tecnico da grade, mas fica recolhida na leitura principal;
- a ordem visual da tabela passou a priorizar `Cliente`, `Equipamento`, `Datas`, `Status / Orcamento` e `Valor` antes dos campos secundarios;
- em desktop/notebook, a grade usa larguras fixas previsiveis e linhas verticais de separacao entre as colunas;
- quando faltar largura util fora do mobile, a coluna `Acoes` recolhe primeiro para o painel expansivel `+` e `Relato` permanece somente no detalhe expansivel, com o texto completo da observacao;
- `Cliente` ficou com largura dedicada, fonte reduzida, nome dividido em linhas de ate `3 palavras` e o telefone passou a caber sem truncamento visivel;
- a coluna `Equipamento` agora prioriza `Tipo` e `Equip.` na face principal da grade;
- a descricao de `Equip.` foi compactada em linhas controladas de ate `3 palavras`, incluindo separadores tecnicos como `|`, para manter a tabela estavel sem depender de scroll lateral;
- a coluna `Status / Orcamento` agora prioriza um badge principal de status e uma linha menor de orcamento, escondendo o estado secundario quando ele nao acrescenta leitura;
- a coluna `Valor` passou a destacar `Total OS` em negrito e a exibir `Recebido`, `Adiantamento` e `Saldo` em linhas menores, com largura propria para evitar sobreposicao com `Status / Orcamento` ou corte do conteudo;
- o texto completo da observacao continua acessivel no painel `+`, sem depender de quebra de linha na grade principal.

### Leitura mobile da listagem

No celular, a face principal do card mostra somente:

- `Foto / OS`;
- `Cliente`;
- `Tipo` + `Equip.` do equipamento;
- botao `+` para expandir os detalhes.

Cada bloco do card agora reserva o proprio espaco no mobile, entao os rÃ³tulos nao ficam sobre o nome do cliente ou sobre a descricao do equipamento.

O nome do cliente agora usa toda a largura util do card no celular e pode quebrar em linhas normais quando for grande, evitando ficar preso em uma faixa curta com rolagem horizontal.
O telefone principal continua em linha unica abaixo do nome, mantendo o atalho do WhatsApp, e o equipamento segue compacto no card.
O cabecalho mobile do sistema foi alinhado globalmente, mantendo notificacoes e perfil na mesma linha visual do menu hamburger.

No painel `+`, o sistema move:

- `Datas`;
- `Status / Orcamento`;
- `Valor`;
- `Relato`;
- `Acoes`.

Como `Relato` ficou fixo no painel `+`, o expansor passa a mostrar o texto completo da observacao, e nao apenas o preview reduzido da linha.

No celular, a busca global fica dentro do menu hamburger. Abra o menu lateral e use o campo de busca no topo para localizar OS, clientes, equipamentos e demais registros.
Quando houver resultados, esse painel agora abre logo abaixo do campo e usa toda a largura disponivel do bloco de busca no menu lateral, evitando sobreposicao estreita em aparelhos compactos como `390px`, `360px` e `320px`.

### Filtros

A tela agora abre por padrao na fila de OS abertas.

Filtros principais:

- busca global;
- `Ordens abertas`: refina apenas as etapas operacionais ainda em andamento;
- `Ordens fechadas`: permite consultar `Equipamento Entregue`, `Devolvido Sem Reparo` e `Equipamento Descartado`;
- filtros avancados por contexto operacional.

Nos filtros avancados:

- `Status geral` oferece a consulta ampla `Todos os status`;
- esse modo junta abertas + fechadas na mesma listagem sem depender do atalho rapido da tela principal.

Comportamento do reset:

- `Limpar` e `Limpar todos` removem apenas os filtros selecionados e devolvem a tela ao estado inicial de OS abertas;
- para consultar abertas + fechadas juntas, use `Status geral -> Todos os status` nos filtros avancados;
- ao acessar novamente a tela sem filtro salvo/manual, a listagem volta a iniciar pela fila aberta.

### Fechamento da nova OS pela listagem

Ao clicar em `+ Nova OS`, a abertura Ã© feita em modal.

Comportamento atual:

- o modal nÃ£o fecha clicando fora;
- o modal nÃ£o fecha pela tecla `ESC`;
- o fechamento manual fica restrito ao botÃ£o `X`;
- ao clicar no `X`, o sistema alerta que existe um registro de ordem de serviÃ§o em andamento e que o preenchimento nÃ£o salvo serÃ¡ perdido.
- esse alerta de confirmaÃ§Ã£o agora sobe acima do modal iframe e do backdrop, evitando ficar escondido atrÃ¡s da janela `Nova OS`.

## Abertura de nova OS

### Estrutura do formulÃ¡rio

O cadastro Ã© organizado por abas:

- `Cliente`
- `Equipamento`
- `Defeito`
- `Dados Operacionais`
- `Fotos`

Na ediÃ§Ã£o, o fluxo inclui tambÃ©m a etapa `SoluÃ§Ã£o`.

### Campos centrais

### Checklist de entrada e comprovante de abertura

Na abertura da OS, o bloco `Checklist de entrada` passa a alimentar diretamente o documento de abertura e a leitura tecnica da recepcao.

Regras praticas:

- as `pendencias` marcadas no checklist entram no PDF de abertura dentro da leitura de `Estado do aparelho`;
- o campo livre `Observacoes do estado na entrada` complementa essas pendencias com texto manual da equipe;
- `Relato do cliente`, `Acessorios recebidos` e os registros de `Estado fisico` passam a ser refletidos no documento quando forem preenchidos;
- ao salvar uma nova OS, a tela pergunta como o operador deseja seguir com o `PDF de abertura`;
- a prompt agora oferece tres caminhos: `Enviar agora`, `Gerar sem abrir WhatsApp` e `Enviar depois`;
- nesse mesmo modal, a equipe pode escolher se o documento vai anexar `fotos de perfil do equipamento`, `fotos de entrada` ou ambos.
- dentro da aba `Documentos`, as acoes `Gerar`, `Enviar por WhatsApp` e `Enviar por E-mail` preservam a propria aba aberta mesmo quando o fluxo recarrega a visualizacao da OS.

| Campo | ObrigatÃ³rio | ObservaÃ§Ã£o |
|---|---|---|
| Cliente | Sim | pode ser selecionado e editado pelo fluxo rÃ¡pido |
| Equipamento | Sim | seleÃ§Ã£o rica com foto e identificaÃ§Ã£o tÃ©cnica |
| TÃ©cnico responsÃ¡vel | NÃ£o | pode ser definido na abertura ou depois |
| Prioridade | Sim | baixa, normal, alta ou urgente |
| Data de entrada | Sim | data/hora da recepÃ§Ã£o |
| PrevisÃ£o | NÃ£o | usada para acompanhamento do prazo |
| Status | Sim | estado inicial da OS |
| Relato do cliente | Sim | problema informado na recepÃ§Ã£o |

### SeleÃ§Ã£o de equipamento

O seletor de equipamento foi enriquecido para reduzir erro de escolha quando o cliente possui aparelhos parecidos.

### Cadastro rapido de equipamento dentro da OS

No modal rapido de equipamento:

- `Desktop montado` continua podendo salvar sem depender de `Marca` e `Modelo` catalogados;
- `Notebook` continua exigindo `Marca` e `Modelo`, mas agora pode receber esses dados automaticamente do coletor local;
- o campo `Observacoes do equipamento` fica disponivel na aba `Info` para destacar peculiaridades, avarias visiveis e alertas tecnicos importantes do aparelho;
- ao editar um equipamento ja vinculado na OS, esse campo volta preenchido com o historico salvo e pode ser ajustado sem sair do contexto da ordem;
- o painel tecnico local fica disponivel para `Desktop` e `Notebook`;
- o botao `Buscar do agente (C:\)` tenta copiar o coletor para `C:\JovemTechBenchCollector`, executa uma leitura local nova e preenche os campos tecnicos;
- quando esse botao e acionado a partir da tela da `OS`, o arquivo final passa a usar o nome `C:\JovemTechBenchCollector\inf_<numero_os>.json`;
- nesse mesmo fluxo, o JSON e enriquecido como uma `OS digital`, guardando tambem `numero da OS`, `cliente`, `status`, `prioridade`, `tecnico`, `relato do cliente`, datas principais e os dados da Jovem Tech configurados no ERP;
- depois da coleta bem-sucedida, o ERP remove o `JovemTechBenchCollector.exe` e o `README.md` da pasta local, mantendo apenas o JSON final;
- no campo catalogado `Modelo`, a importacao do agente passa a priorizar o `chipset`; quando o inventario nao trouxer `chipset`, o sistema usa o `modelo` detectado como fallback;
- se `numero de serie`, `MAC` ou `IMEI` ja existirem no ERP, o modal bloqueia o novo cadastro e alerta que o equipamento ja esta registrado;
- se esse mesmo identificador pertencer a outro cliente, o modal oferece vincular o cliente atual ao equipamento existente e usar o mesmo cadastro na `OS`;
- se o identificador ja pertencer ao mesmo cliente, o modal passa a usar diretamente o equipamento existente, evitando duplicidade;
- o `Numero de serie` prioriza o valor da `BIOS` e, se ele nao existir ou vier invalido, usa o `MAC` da placa de rede.

Cada opÃ§Ã£o pode exibir:

- foto de perfil;
- tipo e marca;
- modelo e cor;
- nÃºmero de sÃ©rie ou IMEI.

TambÃ©m existem aÃ§Ãµes inline:

- `Novo`
- `Editar`

### Sidebar de fotos na ediÃ§Ã£o

Na ediÃ§Ã£o da OS, a lateral `Foto do Equipamento` continua exibindo a imagem principal e as miniaturas de forma imediata.

Comportamento atual:

- fotos reais do equipamento recebem atualizaÃ§Ã£o anti-cache automÃ¡tica quando hÃ¡ troca de principal, inclusÃ£o ou exclusÃ£o;
- quando o equipamento nÃ£o possui arquivo fÃ­sico disponÃ­vel, o sistema usa fallback inline sem quebrar a visualizaÃ§Ã£o;
- o preview principal e as miniaturas permanecem sincronizados sem exigir recarga manual da pÃ¡gina.

### Aba `Dados Operacionais` na ediÃ§Ã£o

Na ediÃ§Ã£o, os campos `Status` e `PrevisÃ£o de Entrega` seguem o fluxo de salvamento direto da OS.

Regras prÃ¡ticas:

- o select `Status` da edicao exibe todos os status operacionais cadastrados, permitindo ajustes fora da trilha curta do fluxo quando a equipe precisar corrigir a etapa manualmente;
- a `PrevisÃ£o de Entrega` nÃ£o pode ficar anterior Ã  `Data de Entrada`;
- o dropdown `Prazo (dias)` passa a refletir novamente o prazo salvo ao reabrir a OS, calculando a diferenca entre `Data de Entrada` e `PrevisÃ£o de Entrega`;
- os labels, dicas, placeholders e mensagens auxiliares da tela /os/editar/{id} passaram por uma varredura complementar de pt-BR/UTF-8, cobrindo cliente, tecnico, acessorios, checklist, camera, diagnostico e resumo lateral;
- pendÃªncias opcionais da recepÃ§Ã£o nÃ£o bloqueiam mais o salvamento da ediÃ§Ã£o.

### Aba `Fotos`

As `Fotos de Entrada do Equipamento` agora trabalham com inclusÃ£o e remoÃ§Ã£o sem recarregar a tela.

Comportamento atual:

- fotos novas continuam podendo ser capturadas pela cÃ¢mera ou escolhidas na galeria;
- fotos jÃ¡ persistidas aparecem com botÃ£o de exclusÃ£o;
- ao excluir uma foto persistida, ela sai da visualizaÃ§Ã£o imediatamente;
- a mesma exclusÃ£o remove o arquivo fÃ­sico correspondente de `public/uploads/os_anormalidades`.

### Aba `Pecas e Orcamento`

A aba `Pecas e Orcamento` passou a mostrar o conteÃºdo real do orÃ§amento vinculado Ã  OS.

Comportamento atual:

- lista todos os itens lanÃ§ados no orÃ§amento, incluindo peÃ§as, serviÃ§os, pacotes e outros tipos;
- mostra resumo por grupo e tabela completa de itens;
- quando nÃ£o houver itens, a aba exibe o botÃ£o para criar ou lanÃ§ar itens no orÃ§amento;
- quando jÃ¡ houver orÃ§amento vinculado, a aba pode mostrar `Visualizar orÃ§amento` e tambÃ©m `Editar orÃ§amento`;
- a abertura dessas aÃ§Ãµes acontece em modal, no mesmo padrÃ£o visual da `Nova OS` da listagem;
- depois do salvamento do orÃ§amento no modal, o bloco da aba Ã© atualizado automaticamente dentro da tela da OS.

## VisualizaÃ§Ã£o da OS (`/os/visualizar/{id}`)

### Estrutura atual da tela

A tela foi reorganizada em duas Ã¡reas:

- coluna lateral com `Fotos do Equipamento` e `HistÃ³rico e Progresso`;
- coluna principal com resumo superior e abas centrais.

Resumo superior:

- cliente;
- equipamento;
- tÃ©cnico.

### Abas da visualizaÃ§Ã£o

As abas principais sÃ£o:

- `InformaÃ§Ãµes`
- `OrÃ§amento`
- `DiagnÃ³stico`
- `Fotos`
- `Documentos`
- `Valores`

### Aba `InformaÃ§Ãµes`

A aba `InformaÃ§Ãµes` agora Ã© somente de leitura para contexto operacional.

Ela mostra:

- relato do cliente;
- checklist de entrada;
- status atual da OS;
- status do orÃ§amento vinculado, quando existir.

Tambem foi aplicada revisao de labels em pt-BR/UTF-8 na lateral e na timeline, cobrindo `HistÃ³rico e Progresso`, `RecepÃ§Ã£o`, `DiagnÃ³stico`, `OrÃ§amento`, `ExecuÃ§Ã£o`, `InterrupÃ§Ã£o`, `ConcluÃ­do`, `Ãšltimas movimentaÃ§Ãµes`, `PrevisÃ£o` e `ConclusÃ£o`.

Nesta mesma rodada, a navegacao por abas, o resumo de contexto, os blocos do orcamento vinculado e os textos auxiliares da visualizacao tambem receberam normalizacao complementar em pt-BR/UTF-8.

Importante:

- essa aba exibe o status atual;
- ela nÃ£o Ã© usada para alterar o status da OS.

### Aba `OrÃ§amento`

Quando existir orÃ§amento vinculado, a aba apresenta o resumo comercial da OS:

- nÃºmero do orÃ§amento;
- status do orÃ§amento;
- tipo/origem;
- validade;
- itens inseridos;
- total do orÃ§amento.

Se a OS ainda nÃ£o tiver orÃ§amento, a aba informa o estado vazio de forma explÃ­cita.

### Aba `DiagnÃ³stico`

Concentra o conteÃºdo tÃ©cnico da ordem:

- procedimentos executados;
- diagnÃ³stico tÃ©cnico;
- soluÃ§Ã£o aplicada;
- tÃ©cnico responsÃ¡vel;
- garantia.

### Aba `Fotos`

O agrupamento de imagens foi consolidado em uma Ãºnica aba organizada por cards.

Ela pode reunir:

- foto de perfil do equipamento;
- demais fotos do equipamento;
- fotos da entrada;
- fotos de acessÃ³rios;
- fotos de checklist, quando houver.

### Aba `Documentos`

Concentra o gerenciamento e o envio dos PDFs da ordem sem sair da visualizacao.

Ela foi organizada em tres cards:

- `Documentos PDF`, para gerar novas versoes e baixar os arquivos ja emitidos;
- `Enviar por WhatsApp`, para usar template, mensagem manual e anexar um PDF opcional da OS, com geracao automatica do consolidado de impressao quando nenhum PDF salvo for escolhido;
- `Enviar por E-mail`, para escolher um PDF gerado, definir destino, assunto e mensagem antes do envio.

Regras praticas:

- o envio por e-mail exige ao menos um PDF previamente gerado para a OS;
- o e-mail usa a configuracao SMTP cadastrada no ERP;
- o campo de destino ja tenta preencher automaticamente com o e-mail do cliente;
- ao lado do download, cada PDF gerado agora tambem oferece `Visualizar`, abrindo o arquivo inline em modal;
- quando o tipo escolhido for `Orcamento`, a OS reutiliza exatamente o PDF oficial emitido pelo modulo `Orcamentos`, sem gerar uma segunda versao paralela do documento;
- o PDF oficial de `Orcamento` inclui o link/botao de aprovacao publica do cliente no proprio arquivo;
- quando a OS ainda nao possui orcamento vinculado e o operador tenta gerar o PDF de `Orcamento`, a tela informa isso por SweetAlert2 e pode abrir imediatamente o modal de elaboracao do orcamento;
- os envios de `Orcamento` por `WhatsApp` e `E-mail` seguem as mesmas regras do modulo `Orcamentos`, incluindo bloqueio por status comercial quando necessario;
- ao gerar `Comprovante de abertura` diretamente no card `Documentos PDF`, a tela abre uma prompt para escolher se a nova versao deve levar `fotos de perfil`, `fotos de entrada` ou nenhuma foto adicional;
- essa selecao passa a valer para o PDF salvo da aba `Documentos`, e nao apenas para a pre-visualizacao ou para o envio por WhatsApp;
- depois de clicar em `Gerar`, `Enviar` no card de `WhatsApp` ou `Enviar E-mail`, a visualizacao volta mantendo a aba `Documentos` aberta, sem jogar o operador de volta para `Informacoes`;
- quando nenhum PDF salvo da OS e selecionado no envio por `WhatsApp`, o sistema gera automaticamente um PDF consolidado no mesmo padrao visual da impressao `A4`;
- quando a OS acabou de ser criada, a propria tela pode abrir um modal para preparar o envio do `PDF de abertura` sem obrigar o operador a procurar o fluxo manual na aba `Documentos`;
- nesse fluxo pos-abertura, o operador pode `enviar agora`, `gerar o PDF sem abrir o WhatsApp` ou `deixar para depois`;
- quando a equipe opta por gerar sem abrir o WhatsApp, o sistema abre diretamente a pre-visualizacao `A4` do documento com as fotos selecionadas;
- o PDF temporario pode ser preparado sem fotos, apenas com `fotos de perfil`, apenas com `fotos de entrada` ou com os dois grupos juntos;
- os PDFs continuam centralizados em `public/uploads/os_documentos/OS_<numero_os>/`.

### Impressao consolidada da OS

O botao `Imprimir`, no topo da visualizacao da OS, passou a abrir um dropdown com dois formatos:

- `Folha A4`
- `Bobina 80mm`

Ao escolher um formato, o sistema abre um modal de pre-visualizacao antes da impressao final.

Nesse modal, o operador pode:

- revisar o documento consolidado da OS antes de imprimir;
- alternar entre `A4` e `80mm` sem sair da tela;
- decidir se deseja incluir ou nao as fotos;
- abrir a pre-visualizacao em nova guia;
- enviar o mesmo PDF por WhatsApp com mensagem personalizada.

Conteudo do documento consolidado:

- dados do cliente;
- dados do equipamento;
- status, fluxo, datas e tecnico;
- relato, diagnostico, solucao e procedimentos;
- checklist de entrada;
- acessorios e estado fisico;
- itens e servicos lancados;
- resumo financeiro completo;
- orcamento vinculado, quando existir;
- notas complementares da OS.

Regras das fotos:

- no formato `A4`, quando a opcao `Incluir fotos` estiver ativa, a foto principal de perfil do equipamento aparece ao lado esquerdo do bloco de equipamento;
- as demais fotos entram ao final do documento agrupadas por tipo, como `entrada`, `acessorios`, `perfil`, alem de outros grupos tecnicos existentes na OS, quando houver;
- no formato `80mm`, a impressao prioriza a leitura em rolagem continua e mantem as fotos na galeria final.
- para evitar perda de imagem no PDF final, as fotos usadas na impressao consolidada passam a ser incorporadas diretamente ao documento no momento da geracao.

Organizacao visual do A4:

- o nome e os dados da empresa ocupam toda a faixa superior do documento;
- logo abaixo, o card principal da OS ocupa toda a largura disponivel e destaca numero, badges, datas e identificacao operacional da ordem;
- na sequencia, os dados do cliente aparecem em uma secao dedicada;
- logo abaixo, as informacoes do equipamento ocupam toda a linha;
- quando `Incluir fotos` estiver ativo, a foto principal do equipamento aparece ao lado esquerdo desse bloco tecnico;
- a secao `Tecnico responsavel` permanece em bloco proprio, separada das informacoes de cliente e equipamento;
- no `A4`, a primeira pagina prioriza esse bloco-resumo inicial com empresa, identificacao da OS, cliente, equipamento com foto e tecnico responsavel;
- a segunda pagina passa a abrir em `Relato do cliente & Diagnostico tecnico`;
- ainda na segunda pagina, o documento segue com `Checklist de Entrada`, `Itens e Servicos Lancados na OS`, `Resumo Financeiro` e `Orcamento Vinculado`, quando existirem;
- a terceira pagina passa a ser reservada para `Fotos Anexadas`, separando a galeria final do conteudo tecnico e financeiro;
- o rodape do `A4` passa a mostrar a paginacao da propria pre-visualizacao em `Pagina X de Y`, alinhada com a divisao explicita das folhas no navegador;
- no PDF efetivamente gerado e enviado, a contagem do rodape e recalculada apos o render final para refletir o total real de paginas do arquivo;
- paginas sem conteudo util deixam de ser geradas no PDF final; a folha `Fotos Anexadas` so entra quando existirem imagens validas para renderizacao;
- o `Resumo financeiro` fica imediatamente acima da secao `Orcamento vinculado`.
- a estrutura visual do documento foi simplificada em blocos e tabelas mais estaveis, para manter a mesma organizacao tanto na pre-visualizacao quanto no PDF efetivamente enviado.

Envio por WhatsApp a partir da impressao:

- o modal usa os templates cadastrados em `Gestao de Conhecimento -> Templates WhatsApp` como base de mensagem;
- o texto pode ser editado antes do envio;
- o PDF enviado respeita exatamente o formato e a opcao de fotos selecionados na pre-visualizacao.
- a camada visual do documento consolidado foi reforcada para manter blocos, cores e cards tanto na pre-visualizacao quanto no PDF final enviado ao cliente.

### Modelos de PDF e templates de WhatsApp

Os tipos documentais da OS, exceto `Orcamento`, agora podem ser administrados pela equipe:

- menu: `Gestao de Conhecimento -> Modelos PDF`
- menu: `Gestao de Conhecimento -> Templates WhatsApp`

Na pratica, isso permite:

- criar novos tipos como contrato, garantia, laudo complementar e termos internos;
- editar o HTML base dos PDFs da OS com placeholders de cliente, equipamento, datas, status e valores;
- ativar ou desativar tipos documentais sem alterar codigo;
- criar e revisar templates padrao de mensagem para os envios por WhatsApp.

### Aba `Valores`

Reune os detalhes financeiros e temporais da OS.

Leitura financeira reforcada:

- `adiantamento recebido` mostra tudo o que entrou antes da entrega final;
- `total recebido` soma todos os recebimentos ja lancados na OS;
- `saldo financeiro pendente` mostra o que ainda falta receber;
- o `historico de recebimentos` marca quais lancamentos foram tratados como `Recebimento da baixa`, `Adiantamento` ou `Sinal`.
- se a OS ainda estiver zerada financeiramente, mas ja tiver `orcamento aprovado` ou `convertido`, a aba passa a exibir esse valor efetivo no lugar de `R$ 0,00`.

### Adiantamento na baixa da OS

Quando a equipe registra valores antes da entrega final do equipamento:

- esses recebimentos podem ser classificados como `adiantamento` ou `sinal`;
- o modal `Baixa da OS` mostra esse valor no card `Adiantamento ja recebido`;
- a aba `Valores` da propria OS exibe o acumulado do adiantamento, o total recebido e o saldo ainda pendente;
- o histÃ³rico de recebimentos passa a destacar cada movimento com o badge `Adiantamento` ou `Sinal`, conforme a classificaÃ§Ã£o escolhida na baixa;
- esses lanÃ§amentos sÃ£o financeiros, entram no `Fluxo de Caixa` e na `DRE`, mas nÃ£o mudam o status da OS;
- para entregar a ordem, use a classificaÃ§Ã£o `Recebimento da baixa`.

ReÃºne os detalhes financeiros e temporais da OS:

- mÃ£o de obra;
- peÃ§as;
- subtotal;
- desconto;
- total;
- datas principais;
- situaÃ§Ã£o do orÃ§amento;
- dados complementares de financeiro da OS e do orÃ§amento vinculado.

## BotÃ£o de orÃ§amento no topo da OS

O comportamento foi endurecido para evitar duplicidade.

### Regras

- se a OS nÃ£o possui orÃ§amento vinculado: o botÃ£o permanece `Gerar orÃ§amento`;
- se a OS jÃ¡ possui orÃ§amento vinculado, em qualquer status:
  - nÃ£o cria novo orÃ§amento;
  - passa a abrir o orÃ§amento existente;
  - o rÃ³tulo muda para `Editar orÃ§amento` ou visualizaÃ§Ã£o equivalente conforme o contexto e a permissÃ£o.

## RelaÃ§Ã£o entre OS e orÃ§amento

### Quando um orÃ§amento Ã© gerado para a OS

- a listagem da OS Ã© recarregada com o contexto do orÃ§amento;
- a OS Ã© movida para `Aguardando AutorizaÃ§Ã£o` enquanto o orÃ§amento estiver em andamento.

### Quando o orÃ§amento muda para `Aprovado` ou `Convertido`

- a OS vinculada passa para `Aguardando Reparo`.

Essa regra vale tanto para a experiÃªncia visual da listagem quanto para a sincronizaÃ§Ã£o operacional da OS.

## Checklist, acessÃ³rios e fotos de entrada

O fluxo de entrada continua permitindo:

- checklist por tipo de equipamento;
- registro de acessÃ³rios na entrada;
- fotos por galeria ou cÃ¢mera;
- preview e organizaÃ§Ã£o dos anexos.

## ObservaÃ§Ãµes finais

- use a visualizaÃ§Ã£o da OS para leitura e acompanhamento;
- use os fluxos operacionais especÃ­ficos para alterar status, gerar documentos e enviar orÃ§amento;
- quando houver orÃ§amento vinculado, considere sempre o estado combinado `OS + orÃ§amento` antes de avanÃ§ar a execuÃ§Ã£o.

## Atualizacao complementar 2.23.23 - Resumo financeiro e lucro

- o painel Resumo financeiro e lucro da Baixa da OS passou a mostrar tambem Adiantamento ja recebido, Lancado nesta acao e Saldo projetado apos salvar;
- esses valores sao recalculados enquanto a equipe digita cada valor recebido, sem precisar sair do campo para o resumo atualizar;
- a leitura lateral do modal permanece alinhada com os cards de Recebimentos e adiantamentos, evitando divergencia visual durante a baixa.
