# Manual do Usuário - Ordens de Serviço

## Visão geral

A Ordem de Serviço (OS) é o registro central do atendimento técnico, desde a entrada do equipamento até a entrega, cancelamento ou devolução.

O fluxo operacional atual cobre:

- recepção;
- diagnóstico;
- orçamento;
- execução;
- qualidade;
- encerramento.

## Onde acessar

- listagem principal: `Ordens de Serviço`
- nova abertura pela listagem: botão `+ Nova OS`
- edição: `/os/editar/{id}`
- visualização: `/os/visualizar/{id}`

## Identificador da OS

O número segue o padrão `OSYYMMSSSS`:

- `YY`: ano;
- `MM`: mês;
- `SSSS`: sequência do mês.

Exemplo: `OS26040010`.

## Listagem de OS (`/os`)

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
- número da OS;
- cliente;
- equipamento;
- datas principais;
- status operacional;
- valor total;
- ações de visualizar e editar.

### Status exibido na listagem

A coluna de status agora concentra o contexto operacional completo da OS:

- status atual da OS;
- estado de fluxo da OS;
- status do orçamento vinculado, quando existir;
- número do orçamento vinculado.

Regras práticas:

- o badge principal sempre mostra o status real salvo na OS;
- o badge de fluxo continua mostrando a etapa operacional real da OS;
- o status do orçamento vinculado aparece apenas como contexto comercial secundário;
- orçamento criado/vinculado e ainda em andamento pode sugerir `Aguardando Autorização`, sem substituir o status principal da OS;
- orçamento `Aprovado` ou `Convertido` pode sugerir `Aguardando Reparo`, sem substituir o status principal da OS na listagem;
- depois que a equipe avança a OS para etapas como `Em Execução do Serviço`, `Aguardando Peça`, `Testes` ou fases posteriores, a listagem não deve mais voltar o status automaticamente para `Aguardando Reparo` só porque o orçamento continua aprovado;
- quando a OS não tiver `valor_final` preenchido, a listagem pode usar o total do orçamento vinculado como fallback visual.

Na prática:

- o orçamento continua sugerindo o ponto de entrada do reparo;
- a condução manual do reparo passa a prevalecer depois que a OS sai da fase inicial de execução;
- a coluna `Status` não deve mascarar o status real da OS com o status sugerido do orçamento.

### Atualizacao automatica do status do orcamento

Quando a resposta do cliente acontece pelo link publico do orcamento:

- a equipe nao precisa atualizar a pagina manualmente;
- a listagem `/os` detecta a resposta em tempo real e recarrega a grade automaticamente;
- o badge comercial de orcamento na coluna `Status` volta sincronizado sem `F5`;
- se o modal `Alterar status da OS` estiver aberto para a mesma ordem, o contexto do modal tambem e reidratado;
- a navbar mostra uma notificacao nova no sino ao lado da foto do perfil;
- ao abrir o dropdown, o operador ve qual orcamento foi respondido e qual foi o novo status comercial;
- ao clicar na notificacao, o ERP abre a rota correta da tela de destino, sem sair para um caminho invalido fora do contexto do sistema.

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
- os labels visiveis do modal foram padronizados em pt-BR, incluindo `Ações rápidas`, `Solução e diagnóstico`, `Gerenciamento do Orçamento`, `Histórico e progresso` e `Últimas movimentações`;
- ao abrir `Editar orcamento` ou `Visualizar`, a janela do orcamento sobe na frente do modal de status para evitar sobreposicao invertida;
- quando o orcamento e salvo em modo embed, o resumo dentro do modal de status e atualizado automaticamente;
- quando o cliente responde o orcamento pelo link publico, o contexto comercial da ordem volta sincronizado assim que a notificacao em tempo real chega ao ERP.

### Largura e leitura da tabela

Na tabela principal `/os`:

- `Foto` agora ocupa apenas a largura visual da thumbnail;
- `N OS` foi reduzida para acompanhar a sequencia do numero da ordem;
- `Cliente` agora quebra o nome em ate `3 palavras por linha`, com no maximo `3 linhas`, acompanha a maior linha visivel na pagina atual, mantem a borda direita mais proxima do nome e deixa o texto centralizado na celula;
- `Equipamento` passa a se ajustar pela maior palavra visivel entre `Tipo`, `Marca` e `Modelo`;
- `Valor Total` continua se ajustando pela maior celula exibida na pagina atual;
- `Relato` mostra preview com ate `3 palavras por linha`, em no maximo `3 linhas`;
- ao passar o mouse sobre `Relato`, o navegador exibe o texto completo da observacao.

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

Ao clicar em `+ Nova OS`, a abertura é feita em modal.

Comportamento atual:

- o modal não fecha clicando fora;
- o modal não fecha pela tecla `ESC`;
- o fechamento manual fica restrito ao botão `X`;
- ao clicar no `X`, o sistema alerta que existe um registro de ordem de serviço em andamento e que o preenchimento não salvo será perdido.
- esse alerta de confirmação agora sobe acima do modal iframe e do backdrop, evitando ficar escondido atrás da janela `Nova OS`.

## Abertura de nova OS

### Estrutura do formulário

O cadastro é organizado por abas:

- `Cliente`
- `Equipamento`
- `Defeito`
- `Dados Operacionais`
- `Fotos`

Na edição, o fluxo inclui também a etapa `Solução`.

### Campos centrais

| Campo | Obrigatório | Observação |
|---|---|---|
| Cliente | Sim | pode ser selecionado e editado pelo fluxo rápido |
| Equipamento | Sim | seleção rica com foto e identificação técnica |
| Técnico responsável | Não | pode ser definido na abertura ou depois |
| Prioridade | Sim | baixa, normal, alta ou urgente |
| Data de entrada | Sim | data/hora da recepção |
| Previsão | Não | usada para acompanhamento do prazo |
| Status | Sim | estado inicial da OS |
| Relato do cliente | Sim | problema informado na recepção |

### Seleção de equipamento

O seletor de equipamento foi enriquecido para reduzir erro de escolha quando o cliente possui aparelhos parecidos.

Cada opção pode exibir:

- foto de perfil;
- tipo e marca;
- modelo e cor;
- número de série ou IMEI.

Também existem ações inline:

- `Novo`
- `Editar`

### Sidebar de fotos na edição

Na edição da OS, a lateral `Foto do Equipamento` continua exibindo a imagem principal e as miniaturas de forma imediata.

Comportamento atual:

- fotos reais do equipamento recebem atualização anti-cache automática quando há troca de principal, inclusão ou exclusão;
- quando o equipamento não possui arquivo físico disponível, o sistema usa fallback inline sem quebrar a visualização;
- o preview principal e as miniaturas permanecem sincronizados sem exigir recarga manual da página.

### Aba `Dados Operacionais` na edição

Na edição, os campos `Status` e `Previsão de Entrega` seguem o fluxo de salvamento direto da OS.

Regras práticas:

- o select `Status` da edicao exibe todos os status operacionais cadastrados, permitindo ajustes fora da trilha curta do fluxo quando a equipe precisar corrigir a etapa manualmente;
- a `Previsão de Entrega` não pode ficar anterior à `Data de Entrada`;
- o dropdown `Prazo (dias)` passa a refletir novamente o prazo salvo ao reabrir a OS, calculando a diferenca entre `Data de Entrada` e `Previsão de Entrega`;
- os labels, dicas, placeholders e mensagens auxiliares da tela /os/editar/{id} passaram por uma varredura complementar de pt-BR/UTF-8, cobrindo cliente, tecnico, acessorios, checklist, camera, diagnostico e resumo lateral;
- pendências opcionais da recepção não bloqueiam mais o salvamento da edição.

### Aba `Fotos`

As `Fotos de Entrada do Equipamento` agora trabalham com inclusão e remoção sem recarregar a tela.

Comportamento atual:

- fotos novas continuam podendo ser capturadas pela câmera ou escolhidas na galeria;
- fotos já persistidas aparecem com botão de exclusão;
- ao excluir uma foto persistida, ela sai da visualização imediatamente;
- a mesma exclusão remove o arquivo físico correspondente de `public/uploads/os_anormalidades`.

### Aba `Pecas e Orcamento`

A aba `Pecas e Orcamento` passou a mostrar o conteúdo real do orçamento vinculado à OS.

Comportamento atual:

- lista todos os itens lançados no orçamento, incluindo peças, serviços, pacotes e outros tipos;
- mostra resumo por grupo e tabela completa de itens;
- quando não houver itens, a aba exibe o botão para criar ou lançar itens no orçamento;
- quando já houver orçamento vinculado, a aba pode mostrar `Visualizar orçamento` e também `Editar orçamento`;
- a abertura dessas ações acontece em modal, no mesmo padrão visual da `Nova OS` da listagem;
- depois do salvamento do orçamento no modal, o bloco da aba é atualizado automaticamente dentro da tela da OS.

## Visualização da OS (`/os/visualizar/{id}`)

### Estrutura atual da tela

A tela foi reorganizada em duas áreas:

- coluna lateral com `Fotos do Equipamento` e `Histórico e Progresso`;
- coluna principal com resumo superior e abas centrais.

Resumo superior:

- cliente;
- equipamento;
- técnico.

### Abas da visualização

As abas principais são:

- `Informações`
- `Orçamento`
- `Diagnóstico`
- `Fotos`
- `Documentos`
- `Valores`

### Aba `Informações`

A aba `Informações` agora é somente de leitura para contexto operacional.

Ela mostra:

- relato do cliente;
- checklist de entrada;
- status atual da OS;
- status do orçamento vinculado, quando existir.

Tambem foi aplicada revisao de labels em pt-BR/UTF-8 na lateral e na timeline, cobrindo `Histórico e Progresso`, `Recepção`, `Diagnóstico`, `Orçamento`, `Execução`, `Interrupção`, `Concluído`, `Últimas movimentações`, `Previsão` e `Conclusão`.

Nesta mesma rodada, a navegacao por abas, o resumo de contexto, os blocos do orcamento vinculado e os textos auxiliares da visualizacao tambem receberam normalizacao complementar em pt-BR/UTF-8.

Importante:

- essa aba exibe o status atual;
- ela não é usada para alterar o status da OS.

### Aba `Orçamento`

Quando existir orçamento vinculado, a aba apresenta o resumo comercial da OS:

- número do orçamento;
- status do orçamento;
- tipo/origem;
- validade;
- itens inseridos;
- total do orçamento.

Se a OS ainda não tiver orçamento, a aba informa o estado vazio de forma explícita.

### Aba `Diagnóstico`

Concentra o conteúdo técnico da ordem:

- procedimentos executados;
- diagnóstico técnico;
- solução aplicada;
- técnico responsável;
- garantia.

### Aba `Fotos`

O agrupamento de imagens foi consolidado em uma única aba organizada por cards.

Ela pode reunir:

- foto de perfil do equipamento;
- demais fotos do equipamento;
- fotos da entrada;
- fotos de acessórios;
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
- quando nenhum PDF salvo da OS e selecionado no envio por `WhatsApp`, o sistema gera automaticamente um PDF consolidado no mesmo padrao visual da impressao `A4`;
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

Reúne os detalhes financeiros e temporais da OS:

- mão de obra;
- peças;
- subtotal;
- desconto;
- total;
- datas principais;
- situação do orçamento;
- dados complementares de financeiro da OS e do orçamento vinculado.

## Botão de orçamento no topo da OS

O comportamento foi endurecido para evitar duplicidade.

### Regras

- se a OS não possui orçamento vinculado: o botão permanece `Gerar orçamento`;
- se a OS já possui orçamento vinculado, em qualquer status:
  - não cria novo orçamento;
  - passa a abrir o orçamento existente;
  - o rótulo muda para `Editar orçamento` ou visualização equivalente conforme o contexto e a permissão.

## Relação entre OS e orçamento

### Quando um orçamento é gerado para a OS

- a listagem da OS é recarregada com o contexto do orçamento;
- a OS é movida para `Aguardando Autorização` enquanto o orçamento estiver em andamento.

### Quando o orçamento muda para `Aprovado` ou `Convertido`

- a OS vinculada passa para `Aguardando Reparo`.

Essa regra vale tanto para a experiência visual da listagem quanto para a sincronização operacional da OS.

## Checklist, acessórios e fotos de entrada

O fluxo de entrada continua permitindo:

- checklist por tipo de equipamento;
- registro de acessórios na entrada;
- fotos por galeria ou câmera;
- preview e organização dos anexos.

## Observações finais

- use a visualização da OS para leitura e acompanhamento;
- use os fluxos operacionais específicos para alterar status, gerar documentos e enviar orçamento;
- quando houver orçamento vinculado, considere sempre o estado combinado `OS + orçamento` antes de avançar a execução.
