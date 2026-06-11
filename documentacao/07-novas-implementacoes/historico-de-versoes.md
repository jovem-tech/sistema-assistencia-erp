# Historico de Versoes do Sistema

Atualizado em: 10/06/2026
Versao atual oficial: `2.23.29`

### 10/06/2026 - v2.23.29 / app 0.4.2
- o modal `Baixa da OS` passou a perguntar, antes da confirmacao final, se o operador deseja enviar a mensagem pelo WhatsApp;
- quando o envio e aceito, o backend anexa o PDF consolidado da impressao da OS em formato A4, reaproveitando o documento mais completo do fluxo operacional;
- o comportamento de envio continua opcional: a baixa pode ser registrada com ou sem WhatsApp, sem sair da fila `/os`;
- a grade principal do `Financeiro` passou a usar larguras proporcionais fixas por coluna no desktop, impedindo que `Descricao` colapse o card clicavel e quebre o titulo letra a letra;
- `Classificacao` e `Acoes` continuam visiveis no mesmo grid sem disputar largura de forma agressiva, enquanto o card da descricao preserva o chip `Ver detalhes` alinhado e legivel;
- a aba `Dashboard` de `Relatorios -> Fluxo de Caixa` passou a usar alturas controladas para os canvases e um recalculo dos graficos apos a aba ficar visivel;
- com isso, a tela deixou de esticar verticalmente em excesso ao abrir o dashboard e manteve leitura responsiva tambem em `<= 430px`, `<= 390px`, `<= 360px` e `<= 320px`, sem overflow horizontal;
- a listagem `/os` passou a reduzir o peso tipografico do telefone dentro da coluna `Cliente`, preservando o nome como referencia principal da linha;
- as linhas `Conclusao` e `Entrega` na coluna `Datas` deixaram de reutilizar o fundo colorido do pill de `Prazo`, mantendo o destaque visual apenas para o atraso operacional;
- a documentacao de usuario, a documentacao tecnica do modulo e a referencia de API foram atualizadas para refletir o novo fluxo;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.29`, renovando o cache dos assets CSS/JS.

## Observacao sobre o App Mobile/PWA

O app mobile/PWA passa a manter documentacao e politica de versionamento proprias, separadas da linha de versao do ERP.

Referencias oficiais do app:

- `documentacao/12-app-mobile-pwa/README.md`
- `documentacao/12-app-mobile-pwa/09-versionamento-e-releases/politica-de-versoes.md`
- `documentacao/12-app-mobile-pwa/09-versionamento-e-releases/historico-de-versoes.md`

Estado documental atual do app:

- versao do app: `0.4.2`
- ERP minimo compativel: `2.11.5`
- documentacao exclusiva consolidada em 04/04/2026, com hub oficial em `documentacao/12-app-mobile-pwa/`

## Release ERP + App

### 10/06/2026 - v2.23.28 / app 0.4.2
- a pagina `Financeiro` passou a manter os botoes de acao visiveis em telas menores, com a coluna `Acoes` quebrando os icones em linhas quando necessario;
- o cabeçalho do modulo ganhou um atalho `+ Novo lancamento` mais compacto, preservando a acao principal sem esconder os demais botoes da tela;
- o comportamento responsivo foi ajustado para reduzir o corte lateral da lista e melhorar a leitura operacional em desktop estreito, notebook e mobile;
- a documentacao de usuario do modulo financeiro foi atualizada para refletir a nova leitura de responsividade;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.28`, renovando o cache dos assets CSS/JS.

### 09/06/2026 - v2.23.27 / app 0.4.2
- a tela de abertura, edicao e visualizacao da OS passou a seguir a mesma regra para `Data de Entrega`, exibindo o campo logo abaixo do status quando o operador seleciona `entregue_reparado`, `devolvido_sem_reparo`, `descartado`, `entregue_pagamento_pendente` ou o alias `entregue`;
- o campo tambem reaparece automaticamente quando a OS volta a um status final, mantendo o valor no rascunho e permitindo ajuste manual antes de salvar;
- a regra de negocio compartilhada passou a considerar `descartado` como status que exige data de entrega, alinhando formulario, edicao e baixa automatica;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.27`, renovando o cache dos assets CSS/JS.

### 09/06/2026 - v2.23.26 / app 0.4.2
- a listagem `Taxas cadastradas` dentro da aba `Taxa por parcela` passou a exibir botões de filtro por operadora, agilizando a consulta das taxas registradas por marca;
- os filtros funcionam sem recarregar a tela, ocultando e exibindo as linhas da tabela imediatamente no navegador;
- a documentação do modulo financeiro foi atualizada para refletir a nova filtragem operacional;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.26`, renovando o cache dos assets CSS/JS.

### 09/06/2026 - v2.23.25 / app 0.4.2
- a tela `Financas -> Cartoes e taxas` passou a exibir a listagem de `Taxas cadastradas` dentro da propria aba `Taxa por parcela`, eliminando a aba separada;
- a interface passou a manter apenas quatro abas visiveis, com `Taxa por parcela` concentrando cadastro e consulta no mesmo painel;
- a aba `Taxa por parcela` distribui formulario e tabela lado a lado em desktop e em pilha em telas menores;
- a documentacao do modulo financeiro foi atualizada para refletir a nova organizacao visual;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.25`, renovando o cache dos assets CSS/JS.

### 09/06/2026 - v2.23.24 / app 0.4.2
- a tela `Financas -> Cartoes e taxas` foi reorganizada em cinco abas para separar melhor operadoras, bandeiras, faixa de taxas, listagem consolidada e simulador;
- a navegação horizontal das abas foi preparada para caber em telas pequenas sem quebrar o layout da pagina;
- a documentacao do modulo financeiro foi atualizada para refletir a nova organizacao visual;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.24`, renovando o cache dos assets CSS/JS.

### 09/06/2026 - v2.23.23 / app 0.4.2
- o painel `Resumo financeiro e lucro` do modal `Baixa da OS` passou a exibir tambem `Adiantamento ja recebido`, `Lancado nesta acao` e `Saldo projetado apos salvar`;
- os valores desse resumo agora reagem imediatamente durante a digitacao dos pagamentos, sem depender de sair do campo para atualizar;
- a leitura operacional da baixa ficou consistente com os cards de recebimentos, evitando que o resumo lateral aparente `R$ 0,00` enquanto o operador ainda esta preenchendo a baixa;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.23`, renovando o cache dos assets CSS/JS.

### 09/06/2026 - v2.23.22 / app 0.4.2
- a `Grade diaria operacional` do `Fluxo de Caixa` deixou de listar lancamentos diretamente na celula do dia, reduzindo a poluicao visual em datas com muitos registros;
- a grade ganhou a coluna `Acoes`, com botao `Visualizar` para abrir um modal por dia;
- o novo modal concentra `movimentos realizados` e `titulos previstos` do dia, mantendo a grade principal mais dinamica e operacional;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.22`, renovando o cache dos assets CSS/JS.

### 09/06/2026 - v2.23.21 / app 0.4.2
- o modal `Baixa da OS` passou a separar pagamentos antecipados da baixa operacional: `Adiantamento` e `Sinal` registram valor no Financeiro, Fluxo de Caixa e DRE sem alterar o status da OS;
- somente lançamentos classificados como `Recebimento da baixa` alteram o status da OS;
- baixa operacional parcial leva a OS para `entregue_pagamento_pendente` e mantém a ordem aberta para cobrança;
- baixa operacional integral aplica o status final selecionado pelo operador, como `entregue_reparado`, `devolvido_sem_reparo` ou `descartado`;
- a interface do modal passou a exibir `Sem alteração de status` quando a ação contém apenas pagamento antecipado;
- a versão oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.21`, renovando o cache dos assets CSS/JS.

### 09/06/2026 - v2.23.20 / app 0.4.2
- envios de PDF por WhatsApp pela tela `/os/visualizar/{id}` passaram a tratar falhas operacionais do provider com HTTP `200` e `ok:false`, evitando `Failed to load resource 422` no console quando a Evolution rejeita a mensagem;
- o provider `EvolutionApiProvider` agora adiciona automaticamente o DDI `55` em telefones brasileiros sem codigo de pais, alinhando o comportamento ao gateway local;
- respostas da Evolution priorizam detalhes internos como `response.message`; quando vier apenas `Bad Request`, passam a exibir uma mensagem orientativa sobre DDI, instancia conectada e aceite do arquivo;
- o wrapper global `DSFeedback.fire` desfoca o elemento ativo antes de abrir SweetAlert2 e restaura foco seguro depois, reduzindo avisos de acessibilidade por `aria-hidden`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.20`, renovando o cache dos assets CSS/JS.

### 09/06/2026 - v2.23.19 / app 0.4.2
- os diagnosticos de WhatsApp em `Configuracoes` passaram a retornar HTTP `200` com `ok:false` para falhas operacionais esperadas do provider/gateway, evitando `Failed to load resource 422` no console;
- `422` continua reservado para validacoes reais de formulario, como telefone de teste vazio;
- o modal `Gerenciar Gateway` remove o foco do botao ativo antes de abrir SweetAlert2 e restaura foco seguro apos o alerta, evitando aviso de acessibilidade por `aria-hidden` com descendente focado;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.19`, renovando o cache dos assets CSS/JS.

### 09/06/2026 - v2.23.18 / app 0.4.2
- a coluna `Status / Orcamento` da listagem `/os` passou a quebrar badges comerciais longos dentro da propria celula;
- status como `Pendente de envio para aprovacao do cliente` deixam de invadir a coluna `Valor`;
- o CSS do badge de orcamento recebeu `max-width: 100%`, `white-space: normal`, `overflow-wrap: anywhere` e `word-break: break-word`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.18`, renovando o cache dos assets CSS/JS.

### 08/06/2026 - v2.23.17 / app 0.4.2
- a aba `Envio do orcamento`, inclusive quando aberta em modal pela OS, passou a exibir o bloco `Status do envio` logo abaixo do link publico;
- o resumo mostra o ultimo resultado comercial registrado em `orcamento_envios`, com canal, data, destino, provedor e erro tecnico quando houver;
- a tela tambem separa o ultimo status por `WhatsApp` e por `E-mail`, deixando evidente se o envio foi concluido, duplicado, ainda nao tentado ou falhou;
- a responsividade do novo bloco cobre os breakpoints `<= 430px`, `<= 390px`, `<= 360px` e `<= 320px`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.17`, renovando o cache dos assets CSS/JS.

### 08/06/2026 - v2.23.16 / app 0.4.2
- o historico do ciclo de vida do equipamento passou a receber um backfill automatico dos eventos antigos ja existentes em `logs`, preenchendo a timeline mesmo para equipamentos que foram encerrados antes da nova tabela existir;
- a migration `2026-06-08-130000_BackfillEquipamentoLifecycleHistoricoFromLogs` consolida encerramentos, reativacoes e encerramentos automaticos sem quebrar a integridade referencial;
- registros de equipamentos que nao existem mais na tabela `equipamentos` sao ignorados no backfill para preservar as chaves estrangeiras da timeline;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.16`, renovando o cache dos assets CSS/JS.

### 08/06/2026 - v2.23.15 / app 0.4.2
- a ficha do equipamento passou a exibir um historico proprio do ciclo de vida, com encerramentos manuais, reativacoes e encerramentos automaticos por OS descartada;
- cada encerramento e cada volta a operacao ficam persistidos em uma timeline dedicada, sem depender apenas da tabela de logs tecnicos;
- o bloco `Ordens de Servico Vinculadas` continua exibindo o historico completo de OS, inclusive quando o equipamento estiver encerrado;
- as respostas AJAX de `encerrar` e `reativar` passaram a devolver o HTML atualizado do historico para manter a tela sincronizada sem refresh manual;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.15`, renovando o cache dos assets CSS/JS.

### 08/06/2026 - v2.23.14 / app 0.4.2
- quando uma OS e finalizada com status `descartado`, o ERP encerra automaticamente o equipamento vinculado usando o mesmo ciclo de vida operacional do cadastro;
- o equipamento encerrado por descarte continua no historico do cliente, mas fica indisponivel para novas OS e novos vinculos operacionais;
- a ficha do equipamento ganhou o botao `Reativar`, permitindo voltar o cadastro para `ativo` quando o bem for recuperado, recondicionado ou reparado;
- o backend e o servico de quitacao financeira passaram a registrar essa sincronizacao automaticamente, sem depender de acao manual do usuario na ficha do equipamento;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.14`, renovando o cache dos assets CSS/JS.

### 08/06/2026 - v2.23.13 / app 0.4.2
- o modulo de `Equipamentos` ganhou o botao `Encerrar`, voltado para retirada de pecas, problemas irreparaveis, descarte e demais cenarios de fim de vida util;
- o encerramento agora exige `motivo`, aceita `observacao interna`, grava `data/hora` do fechamento e preserva o cadastro apenas como historico do cliente;
- equipamentos encerrados deixaram de aparecer nas listas operacionais de abertura de `OS` e `Orcamentos`, sem quebrar a edicao de registros historicos que ja apontavam para esse equipamento;
- a ficha do equipamento passou a bloquear `Nova OS` e novos vinculos operacionais, exibindo badges de historico e o motivo do encerramento em tempo real;
- a listagem e a ficha detalhada passaram a sinalizar `OS em andamento` e desabilitar o botao `Encerrar` quando ainda houver ordens abertas para o equipamento, evitando o 422 antes do envio;
- foi criada a migration `2026-06-08-101500_AddLifecycleFieldsToEquipamentos`, adicionando as colunas `status_operacional`, `motivo_encerramento`, `observacao_encerramento` e `encerrado_em`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.13`, renovando o cache dos assets CSS/JS.

### 08/06/2026 - v2.23.12 / app 0.4.2
- o modal de cadastro/edicao de equipamento dentro da tela `/os/editar/{id}` passou a exibir o campo `Observacoes do equipamento` na aba `Info`;
- o tecnico agora consegue destacar peculiaridades, avarias visiveis e alertas operacionais do aparelho sem sair do contexto da OS;
- a hidratacao do modal e do seletor de equipamento passou a sincronizar esse campo tambem no cache/frontend da OS, preservando a observacao ao reabrir o modal;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.12`, renovando o cache dos assets CSS/JS.

### 07/06/2026 - v2.23.11 / app 0.4.2
- a busca global deixou de ser exibida na navbar mobile para manter a barra superior compacta e alinhada ao menu hamburger;
- o mesmo componente de busca passou a ser renderizado no topo do sidebar mobile, acessivel ao abrir o menu hamburger;
- `public/assets/js/global-search.js` agora inicializa multiplas instancias independentes da busca global, evitando conflito entre a busca desktop e a busca do menu mobile;
- o espacamento extra da listagem `/os` abaixo da navbar foi reduzido, ja que o campo de busca nao ocupa mais o topo da pagina;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.11`, renovando o cache dos assets CSS/JS.

### 07/06/2026 - v2.23.10 / app 0.4.2
- o card mobile da listagem `/os` passou a exibir nomes longos de cliente usando toda a largura util do card, com quebra normal de linha em vez de uma faixa curta com rolagem horizontal;
- o telefone principal do cliente permanece em linha unica abaixo do nome, preservando o atalho do modal rapido de WhatsApp;
- o alinhamento da navbar mobile foi movido para a responsividade global, mantendo menu hamburger, titulo, notificacoes e perfil na mesma linha visual em todo o sistema;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.10`, renovando o cache dos assets CSS.

### 07/06/2026 - v2.23.9 / app 0.4.2
- a listagem `/os` passou a calcular os `data-labels` do card mobile com base no indice real da coluna na DataTable, evitando o deslocamento de `Cliente` para `Equipamento` quando `Nº OS` fica oculto;
- o card mobile da grade `/os` deixou de usar a antiga malha interna e passou a renderizar cada `td` como linha flexivel, eliminando a sobreposicao visual entre rótulo, nome do cliente e bloco de equipamento;
- a coluna `Cliente`, o telefone principal e o bloco de `Equipamento` passaram a permanecer em uma unica linha no mobile, com rolagem horizontal interna apenas quando o texto excede a largura do card;
- o bloco de `Cliente` passou a ocupar a largura inteira do card no mobile, evitando uma faixa estreita ao lado do nome e do telefone e concentrando a rolagem horizontal na linha longa do nome;
- a barra superior da listagem `/os` foi compactada no mobile para trazer notificacoes, perfil e busca mais para o topo da tela, com alinhamento visual do hamburger e dos icones da direita;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.9`.

### 07/06/2026 - v2.23.8 / app 0.4.2
- a coluna `Cliente` da listagem `/os` voltou a exibir o telefone principal como linha visivel abaixo do nome, sem truncar o numero com `...`, mantendo o atalho para o modal rapido de WhatsApp;
- a coluna `Status / Orcamento` foi reorganizada em um status principal, uma linha menor de orcamento e um estado secundario oculto quando redundante com a leitura principal;
- a coluna `Valor` passou a destacar `Total OS` em negrito e simplificou as linhas secundarias para `Recebido`, `Adiantamento` e `Saldo`, reduzindo o corte dos valores monetarios;
- a largura das colunas da grade `/os` foi reajustada para preservar a leitura das colunas novas sem mexer no restante do layout;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.8`.

### 07/06/2026 - v2.23.7 / app 0.4.2
- a grade `/os` recebeu nova distribuicao de larguras para `Foto / OS`, `Cliente`, `Equipamento`, `Datas`, `Status / Orcamento` e `Valor`, evitando que o resumo financeiro seja comprimido ou sobreposto;
- a coluna `Cliente` passou a renderizar nomes longos em linhas de ate `3 palavras`, preservando o telefone clicavel logo abaixo do nome;
- a linha `Equip.` da coluna `Equipamento` passou a renderizar descricoes tecnicas em blocos de ate `3 palavras`, mantendo separadores `|` junto ao termo anterior para leituras como `Mini Tower | H610 |`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.7`.

### 07/06/2026 - v2.23.6 / app 0.4.2
- a coluna `Cliente` da listagem `/os` passou a exibir tambem o telefone principal do cadastro, com atalho visual direto para contato;
- clicar no telefone agora abre um modal rapido de WhatsApp na propria fila de OS, com suporte a `template pronto`, `mensagem personalizada` e envio de `documento salvo da OS` ou `PDF consolidado da impressao`;
- a listagem ganhou a rota `GET /os/whatsapp-meta/{id}` para hidratar esse modal sem sair da tela;
- a coluna `Valor` deixou de mostrar apenas o total final e passou a exibir um mini resumo financeiro da OS, destacando `recebido`, `adiantamento`, `saldo` e `total`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.6`.

### 07/06/2026 - ajuste visual interno da listagem de OS (sem mudanca de versao oficial)
- a grade `/os` passou a exibir o numero principal da ordem logo abaixo da foto do equipamento, concentrando a identificacao no mesmo bloco visual;
- a coluna `N OS` deixou de disputar largura na tabela principal e segue apenas no payload tecnico da DataTable;
- a linha `Equip.` da coluna `Equipamento` foi reduzida em `40%` para liberar espaco horizontal sem remover o resumo tecnico.

### 07/06/2026 - v2.23.5 / app 0.4.2
- o modal `Baixa da OS` ganhou o botao `Adicionar adiantamento`, ao lado de `Adicionar recebimento`, para registrar pagamentos antecipados sem sair da fila operacional;
- esse novo fluxo abre uma escolha rapida entre `Adiantamento total` e `Sinal`, preenchendo o saldo restante quando fizer sentido e preservando a classificacao no proprio lancamento;
- cada linha financeira da baixa agora pode ser classificada como `Recebimento da baixa`, `Adiantamento` ou `Sinal`, e essa informacao passa a viajar em `recebimentos_json`;
- nota de compatibilidade `2.23.21`: `Adiantamento` e `Sinal` sao apenas lancamentos financeiros e nao alteram o status operacional da OS;
- a aba `Valores` da visualizacao da OS agora diferencia os badges do historico financeiro entre `Adiantamento` e `Sinal`, em vez de tratar tudo apenas como adiantamento generico;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.5`.

### 07/06/2026 - v2.23.4 / app 0.4.2
- o modal `Baixa da OS` passou a usar o `valor efetivo` da ordem quando os campos financeiros da tabela `os` ainda estiverem zerados;
- esse valor efetivo agora cai automaticamente para o `orcamento aprovado` ou `convertido` mais recente vinculado, mantendo coerencia com a listagem operacional;
- a aba `Valores` da visualizacao da OS e o modal auxiliar de orcamento tambem passaram a usar a mesma base financeira;
- a criacao automatica do titulo `A receber` no fechamento passou a respeitar esse fallback, evitando OS concluida com valor exibido mas titulo zerado;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.4`.

### 07/06/2026 - v2.23.3 / app 0.4.2
- a aba `Valores` da visualizacao da `OS` passou a exibir `adiantamento recebido`, `total recebido`, `saldo financeiro pendente` e o `historico de recebimentos`;
- o backend da OS agora consolida um resumo financeiro proprio do titulo `A receber`, com status resolvido, percentual quitado, ultimo recebimento e classificacao dos lancamentos tratados como adiantamento;
- o modal `Baixa da OS` passou a rotular explicitamente `Adiantamento ja recebido`, alinhando a linguagem do financeiro com a operacao de balcao;
- a rota AJAX de encerramento da OS passou a expor `valor_adiantamento`, `valor_recebido_total`, `percentual_quitado`, `ultimo_recebimento_em` e `formas_pagamento_resumo`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.3`.

### 07/06/2026 - v2.23.2 / app 0.4.2
- o card `Resumo do período` do relatório `Fluxo de Caixa` passou a exibir a composicao visual de `entradas previstas` e `saidas previstas`;
- cada composicao agora mostra `descricao`, `vencimento`, `OS` quando houver vinculo, valor `ja recebido/pago` e saldo `em aberto`, eliminando a leitura implicita do numero resumido;
- a `Grade diaria operacional` tambem passou a listar, em cada dia com previsao, quais titulos compoem o valor previsto daquela data;
- a view `app/Views/relatorios/view_fluxo_caixa.php` foi saneada em UTF-8 para reforcar a acentuacao correta em `pt_BR`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.2`.

### 06/06/2026 - v2.23.1 / app 0.4.2
- o relatório `Fluxo de Caixa` ganhou uma `grade diária operacional`, destacando por dia `entradas`, `saídas`, `saldo do dia` e `acumulado do mês`;
- a curva diária anterior foi reorganizada para uma leitura mais operacional, mantendo o suporte interno a `saldo realizado`, `saldo projetado` e previsões por vencimento;
- o backend passou a calcular `saldo_do_dia` e `acumulado_mes` dentro de `FinanceiroModel::buildCashFlowDailyRows()`, reaproveitando os mesmos movimentos e títulos já usados no relatório;
- a tela do fluxo também recebeu revisão textual em `pt_BR`, corrigindo acentuação de labels e mensagens como `Mês`, `Saídas`, `Descrição`, `Referência`, `Classificação` e `Títulos`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.23.1`.

### 06/06/2026 - landing comercial publica alinhada ao estado atual do ERP
- foi criada uma landing publica nas rotas `/site` e `/apresentacao`, sem alterar a rota raiz atual de login do sistema;
- a nova pagina passou a usar dados institucionais configurados no ERP (`empresa_nome`, `empresa_telefone`, `empresa_email`, `empresa_endereco`, logo e favicon), evitando CTA e rodape ficticios;
- a copy e os blocos comerciais foram reescritos para refletir os modulos reais do produto hoje: `OS`, `Orcamentos` com `link publico`, `WhatsApp OS`, `CRM`, `Financeiro gerencial`, `PWA` e `Coletor de Bancada`;
- na rodada seguinte, a pagina tambem passou por normalizacao textual para pt-BR, com acentuacao correta em titulos, CTA, FAQ, prova comercial e mensagem pre-preenchida do WhatsApp.

### 06/06/2026 - reorganizacao do link publico na visualizacao de Orcamentos
- a tela `Visualizar Orcamento` passou a exibir o bloco `Link publico e referencia comercial` dentro da aba `Envio do orcamento`, junto de PDF, WhatsApp e e-mail;
- a aba `Financeiro do orcamento` ficou focada apenas no resumo monetario (`subtotal`, `desconto`, `acrescimo` e `total final`);
- o ajuste foi aplicado em `app/Views/orcamentos/show.php`, sem alterar IDs de copiar/abrir o link publico nem os fluxos existentes de envio.

### 06/06/2026 - correcao do lookup de OS abertas no formulario de Orcamentos
- o endpoint `GET /orcamentos/os-abertas/cliente` deixou de falhar com `500` ao serializar OS abertas de clientes cujo equipamento possuia `marca` e/ou `modelo` ausentes no formatter;
- `App\Controllers\Orcamentos::formatOsAbertaLookupResult()` voltou a preencher `equip_marca` e `equip_modelo` explicitamente antes de montar `search_text` e o payload do card `Vinculo OS`;
- foi adicionada cobertura de regressao em `tests/unit/OrcamentosLookupTest.php` para garantir que o lookup continue funcionando mesmo com cadastro parcial do equipamento.

### 05/06/2026 - evolucao do RBAC para modulos independentes
- o sistema de permissoes passou a tratar `crm`, `atendimento_whatsapp` e `precificacao` como modulos proprios, em vez de depender apenas de `clientes` e `orcamentos`;
- foi criada a migration `2026-06-05-160000_SyncEvolvedRbacModules`, responsavel por criar os slugs novos em `modulos` e replicar permissao inicial equivalente para os grupos existentes;
- as rotas de `CRM`, `Central de Mensagens` e `Precificacao` foram atualizadas para usar filtros dedicados (`permission:crm:*`, `permission:atendimento_whatsapp:*` e `permission:precificacao:*`);
- a sidebar, a busca global e a ficha do cliente passaram a respeitar a visibilidade desses modulos separadamente, evitando liberar ou esconder funcionalidades pelo dominio errado.

### 05/06/2026 - v2.22.13 / app 0.4.2
- a tela principal do `Financeiro` ganhou o filtro `Despesas fixas`, voltado para contas `A pagar` marcadas como `Despesa fixa mensal na DRE`;
- o novo filtro foi integrado a mesma barra de `tipo` e `status`, permitindo combinar `despesas fixas` com `Pendentes`, `Parciais`, `Pagos` e `Cancelados`;
- os links de filtro da grade passaram a preservar melhor o contexto atual entre `tipo`, `status` e recorrencia fixa, evitando perder a combinacao a cada clique;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.13`.

### 05/06/2026 - v2.22.12 / app 0.4.2
- o formulario de `Novo lancamento` e `Editar lancamento` do `Financeiro` foi reorganizado para exibir `Data vencimento`, `Mes/ano de competencia` e `Data de pagamento` lado a lado no desktop;
- `Origem automatica` foi reposicionada para a linha superior, preservando o mesmo comportamento de leitura automatica sem alterar a regra de negocio do modulo;
- a alteracao foi apenas de layout, sem impacto em persistencia, calculo de `DRE` ou `fluxo de caixa`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.12`.

### 05/06/2026 - v2.22.11 / app 0.4.2
- corrigido o modal de detalhamento do `Financeiro` para titulos sem `OS` vinculada, que antes podiam disparar erro ao tentar montar o bloco de equipamento a partir de valor nulo;
- a partial `detail_modal_content` passou a tratar defensivamente a ausencia de `osDetalhes`, exibindo apenas o contexto financeiro da conta quando nao houver ordem relacionada;
- o endpoint `GET /financeiro/detalhes/{id}` passou a responder JSON estruturado mesmo em falha interna de renderizacao, preservando o tratamento amigavel no frontend;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.11`.

### 05/06/2026 - v2.22.10 / app 0.4.2
- corrigida a coluna `Descricao` da grade do `Financeiro`, que em alguns cenarios do desktop estava quebrando o texto letra a letra e comprimindo excessivamente o card clicavel;
- a view do modulo passou a sobrescrever a regra global agressiva de `word-break` apenas dentro da tabela financeira e ganhou largura minima operacional para a celula de `Descricao`;
- o card interno da descricao agora respeita melhor quebra por palavra, mantendo `cliente`, `fornecedor`, `equipamento` e resumo contextual legiveis sem desconfigurar a linha;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.10`.

### 05/06/2026 - v2.22.9 / app 0.4.2
- a coluna `Equipamento` da listagem `/os` passou a encurtar automaticamente nomes tecnicos muito longos de `Desktop montado`;
- quando o resumo completo extrapola a leitura ideal da grade, a celula agora prioriza `tipo de gabinete`, `chipset` e `processador`;
- o modal/ficha do equipamento continua exibindo o nome completo e os demais detalhes tecnicos sem perda de informacao;
- foi adicionada a funcao auxiliar `equipamento_resumo_tecnico_essencial()` para apoiar esse resumo compacto;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.9`.

### 05/06/2026 - v2.22.8 / app 0.4.2
- o cadastro completo de `Equipamentos`, o modal rapido da `OS` e a API interna de equipamentos passaram a bloquear duplicidade por `numero de serie`, `MAC` e `IMEI`;
- quando o identificador ja pertence ao mesmo cliente, o sistema orienta a reutilizar o equipamento existente em vez de criar outro cadastro;
- quando o identificador ja pertence a outro cliente, o ERP oferece vincular o cliente atual ao mesmo equipamento, preservando um historico unico do bem fisico;
- foi criado o `EquipamentoIdentidadeService`, centralizando normalizacao dos identificadores, comparacao e vinculacao segura de clientes ao cadastro existente;
- a migration `2026-06-05-120000_AddEquipamentoIdentityIndexes` adicionou indices de apoio em `equipamentos.numero_serie` e `equipamentos.imei`, removeu pares duplicados antigos em `equipamento_clientes` e aplicou unicidade operacional em `(equipamento_id, cliente_id)`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.8`.

### 05/06/2026 - v2.22.7 / app 0.4.2
- o grafico principal do `Dashboard` passou a comparar duas series mensais no mesmo eixo: `OS abertas` e `OS entregues reparadas`;
- o endpoint `GET /admin/stats` agora devolve `os_abertas_ano[].entregues_reparadas`, permitindo que o frontend plote a segunda linha sem recarregar a pagina inteira;
- a linha de `OS entregues reparadas` foi padronizada em verde para leitura comparativa rapida no painel operacional;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.7`.

### 05/06/2026 - v2.22.6 / app 0.4.2
- a importacao local do `Coletor de Bancada` passou a priorizar o valor de `chipset` no campo catalogado `Modelo` tanto no cadastro completo de `Equipamentos` quanto no modal rapido de equipamento da `OS`;
- quando o snapshot nao trouxer `chipset`, o sistema usa o `model` detectado no inventario apenas como fallback, evitando manter `System Product Name` ou rotulos genericos como identificacao principal quando houver chipset disponivel;
- o `model` original do inventario continua preservado dentro do snapshot/`OS digital`, sem perder o dado bruto vindo da BIOS/fabricante;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.6`.

### 04/06/2026 - v2.22.5 / app 0.4.2
- o snapshot local do `Coletor de Bancada` passou a usar o numero da `OS` no nome do arquivo sempre que esse contexto estiver disponivel, no padrao `C:\JovemTechBenchCollector\inf_<numero_os>.json`;
- sem `OS`, o fallback continua sendo `C:\JovemTechBenchCollector\last-snapshot.json`, preservando o uso tecnico fora do fluxo da ordem;
- o botao `Buscar do agente (C:\)` agora envia esse contexto de `OS` para o executavel local antes da coleta, permitindo que o proprio coletor ja grave no nome final esperado;
- depois que a coleta automatica local termina com sucesso e o JSON e enriquecido com a `OS digital`, o ERP remove `JovemTechBenchCollector.exe` e `README.md` da pasta `C:\JovemTechBenchCollector`, deixando apenas o arquivo final util;
- as mensagens de sucesso do formulario de `Equipamentos` e do modal de equipamento da `OS` passaram a informar essa limpeza dos arquivos temporarios;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.5`.

### 04/06/2026 - v2.22.4 / app 0.4.2
- o `last-snapshot.json` do `Coletor de Bancada` passou a registrar no topo `collectedAtUtc`, `collectedAtLocal`, `savedAtUtc` e `savedAtLocal`, deixando a data da coleta visivel mesmo em leitura local pura;
- quando a coleta local e disparada pelos formularios do ERP, o backend agora enriquece esse arquivo como uma `OS digital`, adicionando os blocos `serviceOrder`, `customer` e `company`;
- no contexto da `OS`, o snapshot local pode guardar `numero da OS`, `status`, `prioridade`, `tecnico`, `relato do cliente`, `datas principais`, `equipamento` e `link publico do selo`, alem do nome/telefone/email do cliente;
- os dados configurados da Jovem Tech (`empresa_nome`, `empresa_telefone`, `empresa_email`, `empresa_endereco`) passam a acompanhar o snapshot local enriquecido, facilitando o uso do arquivo como ficha digital salva no PC do cliente;
- os botoes `Buscar do agente (C:\)` no cadastro de `Equipamentos` e no modal de equipamento da `OS` passaram a enviar automaticamente o contexto disponivel para essa montagem do documento local;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.4`.

### 04/06/2026 - v2.22.3 / app 0.4.2
- corrigido o fluxo do `Coletor de Bancada` em `--dry-run`, que ainda tentava criar o cliente HTTP antes de verificar o modo local e podia falhar com `Invalid URI` quando o botao `Buscar do agente (C:\)` executava a coleta sem contexto de `ERP`;
- o executavel agora so instancia `ErpAgentClient` quando realmente vai fazer `bootstrap/check-in`, preservando a coleta local pura para o botao do formulario;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.3`.

### 04/06/2026 - v2.22.2 / app 0.4.2
- o botao `Buscar do agente (C:\)` passou a atender `Desktop` e `Notebook` no cadastro completo e no modal de equipamento da `OS`;
- quando o coletor ainda nao existe em `C:\JovemTechBenchCollector`, o ERP agora copia automaticamente `JovemTechBenchCollector.exe`, executa uma coleta local nova e reaproveita o snapshot gerado logo em seguida;
- a importacao local passou a preencher tambem `marca` e `modelo`, especialmente util para `Notebook`, onde esses dados costumam vir da BIOS/fabricante;
- o numero de serie do inventario agora prioriza a `BIOS` e usa o `MAC` da placa de rede como fallback quando a BIOS nao trouxer uma serie confiavel, tanto no coletor `C#` quanto no script PowerShell de fallback;
- o `JovemTechBenchCollector.exe` passou a aceitar `--dry-run` sem exigir contexto de `ERP`, `OS` e `email`, permitindo leitura local pura na bancada;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.2`.

### 04/06/2026 - v2.22.1 / app 0.4.2
- o `Coletor de Bancada` passou a salvar o ultimo snapshot local em `C:\JovemTechBenchCollector\last-snapshot.json`, para reaproveitamento imediato na bancada;
- foi criada a rota protegida `GET /equipamentos/bench-collector/snapshot-local`, que le esse arquivo local e devolve os campos tecnicos ja normalizados para o formulario do equipamento;
- o cadastro completo de `Equipamentos` e o modal de equipamento dentro da `OS` ganharam o botao `Buscar do agente (C:\)`, que preenche automaticamente `serie`, `placa-mae`, `chipset`, `processador`, `memoria`, `armazenamento`, `placa de video` e sugestao de `gabinete`;
- o coletor foi endurecido para nao abortar a execucao inteira caso a gravacao local do snapshot falhe por permissao no `C:\`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.1`.

### 04/06/2026 - v2.22.0 / app 0.4.2
- o fluxo de inventario tecnico de `Desktop` e `Notebook` ganhou um `Coletor de Bancada` portatil em `C#`, publicado como `public/assets/agents/JovemTechBenchCollector-win-x64.zip`;
- o executavel `JovemTechBenchCollector.exe` roda sem instalacao no computador do cliente, faz `bootstrap` pela `OS`, coleta hardware local via `WMI` e envia um `check-in` unico por padrao;
- o coletor passou a suportar uso interativo no console, `--dry-run` para teste local sem envio ao ERP e modo `--continuous` para repeticao de check-ins quando necessario;
- foi criado o script `scripts/agents/publish-bench-collector.ps1`, que instala um SDK .NET local quando preciso e publica o artefato self-contained dentro de `public/assets/agents/bench-collector/win-x64/`;
- a ficha do equipamento agora prioriza o download do pacote `.zip` do coletor e deixa o comando em `PowerShell` apenas como fallback tecnico;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.22.0`.

### 04/06/2026 - v2.21.0 / app 0.4.2
- o cadastro de `Equipamentos` passou a tratar `Desktop` em dois modos: `Desktop de marca/OEM` e `Desktop montado`;
- no modo `Desktop montado`, `Marca` e `Modelo` deixam de ser a identificacao principal e o ERP passa a montar um `resumo tecnico` com `gabinete`, `chipset`, `processador`, `memoria`, `armazenamento`, `GPU` e `fonte`;
- a tela de `Equipamentos` e o modal inline da `Nova OS` ganharam o painel tecnico de desktop, incluindo ajuda para `Como identificar?` o tipo de gabinete;
- o backend recebeu `EquipamentoProfileService`, responsavel por validar o fluxo de desktop montado, aplicar o catalogo padrao `Montado > Desktop montado`, gerar `display_name/display_label` e sincronizar a configuracao tecnica;
- a API interna de equipamentos (`/api/v1/equipments*`) passou a aceitar e devolver os novos campos tecnicos;
- foi publicado o agente `public/assets/agents/jovemtec-monitor-agent.ps1`, capaz de provisionar e fazer `check-in` de inventario para `Desktop` e `Notebook`;
- o endpoint `POST /api/v1/agents/check-in` agora grava snapshots do inventario e sincroniza automaticamente `placa_mae`, `chipset`, `processador`, `memoria_ram`, `armazenamento` e `placa_video` no equipamento vinculado;
- a exibicao do equipamento foi alinhada em OS, PDFs, impressao, dashboard, relatorios, busca global, financeiro e ficha do equipamento para respeitar o novo `resumo_tecnico`;
- a migration `2026-06-04-090000_AddDesktopProfilesAndAgentInventoryToEquipamentos` adicionou os campos tecnicos em `equipamentos` e ampliou `monitor_agents`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.21.0`.

### 02/06/2026 - v2.20.5 / app 0.4.2
- o formulario de `Novo Orcamento` e `Editar Orcamento` ganhou cadastro rapido inline de `Peca` e `Servico` dentro da propria linha do item, evitando que a equipe precise sair para os modulos de `Estoque` e `Servicos`;
- quando a linha estiver como `Peca` ou `Servico`, a area de catalogo passa a mostrar o botao `Cadastrar`, que abre um modal reativo de cadastro rapido sem recarregar a pagina;
- o backend recebeu os endpoints `POST /estoque/salvar_ajax` e `POST /servicos/salvar_ajax`, ambos retornando o item salvo no mesmo formato usado pelo `GET /orcamentos/item/catalogo`;
- apos sucesso, o item e inserido imediatamente no Select2 da linha atual e o orcamento recebe descricao, referencia, valor e metadados de precificacao sem perder o contexto da tela;
- em `Peca`, o cadastro rapido ja respeita a logica de `peca instalada`, incluindo valor recomendado e piso minimo operacional;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.20.5`.

### 02/06/2026 - v2.20.4 / app 0.4.2
- corrigido o retorno das acoes `Documentos PDF -> Gerar`, `Enviar por WhatsApp` e `Enviar por E-mail` na visualizacao da OS, que antes podiam recarregar a pagina e deixar a interface cair novamente na aba inicial `Informacoes`;
- `POST /os/pdf/{id}/gerar`, `POST /os/whatsapp/{id}` e `POST /os/email/{id}/enviar` agora redirecionam explicitamente para `#tab-documentos` nos fluxos HTML da aba `Documentos`;
- a propria `app/Views/os/show.php` continua lendo o hash da URL e reativando automaticamente a aba correspondente ao carregar;
- com isso, apos gerar ou enviar um documento pela aba `Documentos`, o operador permanece no mesmo contexto visual do card de documentos;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.20.4`.

### 02/06/2026 - v2.20.2 / app 0.4.2
- a aba `Documentos` da visualizacao da OS passou a perguntar, via SweetAlert2, se o `Comprovante de abertura` salvo deve levar `fotos de perfil`, `fotos de entrada` ou nenhuma foto adicional;
- essa escolha agora alimenta o endpoint oficial `POST /os/pdf/{id}/gerar`, permitindo que a nova versao persistida em `os_documentos` saia com o anexo fotografico correto;
- o `OsPdfService` passou a aceitar `include_photos` e `photo_groups` para o documento `abertura`, reaproveitando o contexto consolidado do `OsPrintService`;
- o comprovante oficial de abertura passou a anexar os grupos selecionados tambem quando o HTML vier de template dinamico, nao apenas da view legada;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.20.2`.

### 02/06/2026 - v2.20.1 / app 0.4.2
- a prompt exibida logo apos abrir uma nova OS recebeu refinamento de UX para ficar mais operacional no balcao;
- o modal agora oferece tres caminhos claros: `Enviar agora`, `Gerar sem abrir WhatsApp` e `Enviar depois`;
- a escolha de `fotos de perfil` e `fotos de entrada` continua no mesmo passo e passa a alimentar tanto o envio imediato quanto a pre-visualizacao aberta sem WhatsApp;
- ao optar por `Gerar sem abrir WhatsApp`, a tela abre diretamente a pre-visualizacao `A4` com os grupos de foto selecionados, sem obrigar o operador a entrar no fluxo de mensageria;
- quando o operador seguir para `Enviar agora`, o modal de WhatsApp continua sendo preconfigurado com `template os_aberta`, `A4` e os grupos de foto escolhidos;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.20.1`.

### 01/06/2026 - v2.20.0 / app 0.4.2
- o `PDF de abertura` da OS voltou a refletir corretamente `relato do cliente`, `acessorios recebidos` e `estado fisico`, corrigindo o payload usado pelos modelos documentais;
- o fluxo de abertura e edicao da OS voltou a persistir `estado_fisico_data` no backend, evitando perda silenciosa dessas informacoes ao salvar;
- o `Checklist de entrada` ganhou o campo livre `Observacoes do estado na entrada`, persistido em `checklist_execucoes.observacoes_estado`;
- no documento de abertura e no consolidado `A4`, o bloco `Estado do aparelho` agora combina pendencias do checklist e essa observacao manual da recepcao;
- logo apos criar uma nova OS, a visualizacao pode abrir uma prompt SweetAlert2 para preparar o envio do `PDF de abertura`, incluindo opcionalmente `fotos de perfil` e `fotos de entrada` no arquivo temporario enviado por WhatsApp;
- o endpoint de impressao e o envio consolidado por WhatsApp passaram a aceitar `grupos_fotos`, permitindo restringir o PDF temporario aos grupos desejados;
- a tabela `checklist_execucoes` recebeu a coluna `observacoes_estado` por migration dedicada;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.20.0`.

### 01/06/2026 - v2.19.6 / app 0.4.2
- o formulario de `Novo lancamento` e `Editar lancamento` do modulo `Financeiro` passou a usar o campo `Mes/ano de competencia` para lancamentos manuais, em vez de pedir uma data cheia que confundia a leitura gerencial;
- quando a equipe informa apenas `mes/ano`, o backend converte automaticamente para `data_competencia = 01/mm/aaaa`, preservando compatibilidade com o campo `DATE` no banco;
- lancamentos automaticos de `OS` continuam mantendo a `data completa` de entrega na competencia, sem perder a rastreabilidade exata da receita operacional;
- a listagem do `Financeiro`, o modal detalhado e o relatorio operacional passaram a exibir `competencia` como `mes/ano` nos lancamentos manuais e `data cheia` apenas quando a origem automatica for `OS`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.19.6`.

### 01/06/2026 - v2.19.5 / app 0.4.2
- o formulario de `Novo lancamento` e `Editar lancamento` do modulo `Financeiro` deixou de expor o campo manual de `Origem`, que agora passa a ser sempre definida automaticamente pelo backend;
- a tela passou a mostrar uma previa somente leitura de `Origem automatica`, explicando para a equipe se o titulo sera tratado como `Lancamento manual`, `Ordem de servico`, `Compra para estoque`, `Compra para OS` ou `Despesa vinculada a OS`;
- a ajuda textual de `Data de competencia` foi reforcada com exemplo pratico de conta de junho paga em julho, deixando claro que o campo representa o mes economico da DRE;
- quando `data_competencia` vier vazia, o backend passa a usar primeiro `data_vencimento` e deixa `data_pagamento` apenas como fallback final; em receitas de `OS`, a prioridade continua sendo `data_entrega`;
- listagens e modal detalhado passaram a exibir a `origem` com rotulo humano em vez do codigo tecnico bruto;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.19.5`.

### 01/06/2026 - v2.19.4 / app 0.4.2
- corrigido o fluxo de exclusao acionado pelos botoes da listagem de `Fornecedores` e `Funcionarios`, que antes podia navegar para `/undefined` quando a acao era renderizada com `data-url`;
- o handler global `.btn-delete` passou a aceitar tanto botoes com `data-url` quanto links com `href`, mantendo compatibilidade com os outros modulos do ERP;
- a confirmacao de exclusao dessas acoes passou a usar `SweetAlert2` via `window.DSFeedback.confirm`, substituindo o `confirm()` nativo nesse fluxo central;
- quando um botao de exclusao vier sem rota configurada, o sistema agora registra erro tecnico no console e mostra feedback amigavel em tela, evitando o redirecionamento quebrado;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.19.4`.

### 01/06/2026 - v2.19.3 / app 0.4.2
- o cadastro de `Fornecedores` passou a consultar dados publicos por `CNPJ` e preencher automaticamente razao social, nome fantasia, contatos e endereco quando essas informacoes estiverem disponiveis;
- foi criada a rota protegida `GET /fornecedores/consultar-cnpj`, reaproveitando o `CnpjLookupService` ja usado no modulo de clientes;
- o formulario de fornecedor ganhou feedback contextual abaixo do campo `CNPJ`, spinner durante a consulta e alerta SweetAlert2 quando o provedor estiver indisponivel ou o documento for invalido;
- o `CnpjLookupService` passou a enriquecer o resultado com campos faltantes vindos de provedores secundarios e agora tambem tenta resolver `inscricao estadual` (`ie_rg`) quando esse dado estiver disponivel;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.19.3`.

### 01/06/2026 - v2.19.2 / app 0.4.2
- corrigida a persistencia do campo `ativo` no modulo `Fornecedores`, que antes nao gravava corretamente quando o switch era desligado na edicao;
- o formulario passou a enviar explicitamente `0` ou `1` no campo `ativo`, eliminando a dependencia do comportamento nativo do checkbox;
- o controller `Fornecedores` passou a normalizar o payload antes de salvar, garantindo consistencia mesmo em requests incompletos;
- com isso, alternar entre `Ativo` e `Inativo` volta a refletir corretamente na listagem e no cadastro do fornecedor;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.19.2`.

### 01/06/2026 - v2.19.1 / app 0.4.2
- o formulario de `Novo lancamento` e `Editar lancamento` do modulo `Financeiro` passou a exibir o campo `Fornecedor` apenas quando o `Tipo` for `A pagar`;
- o vinculo passou a usar o cadastro oficial do modulo `Fornecedores`, evitando texto livre e mantendo consistencia entre despesa e parceiro comercial;
- quando o titulo for `A receber`, o campo fica oculto e o backend limpa qualquer `fornecedor_id` para impedir associacao indevida de receita a fornecedor;
- a grade e o modal de detalhamento financeiro passaram a exibir o fornecedor vinculado nas despesas quando esse relacionamento existir;
- a tabela `financeiro` recebeu a coluna `fornecedor_id` por migration dedicada;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.19.1`.

### 01/06/2026 - v2.19.0 / app 0.4.2
- implementado suporte a `baixa parcial` e `multiplos movimentos por titulo` no modulo `Financeiro`;
- criada a tabela `financeiro_movimentos`, e titulos antigos `pagos` passaram por backfill para preservar o `fluxo de caixa realizado`;
- o status do titulo agora pode ficar `parcial`, com recalculo automatico de `quitado`, `saldo em aberto`, `ultima baixa` e `formas de pagamento`;
- a acao `Registrar baixa` da listagem passou a aceitar valor parcial, manter historico de movimentos e liquidar o titulo somente quando o saldo chegar a zero;
- o modal de detalhamento financeiro passou a mostrar `historico de baixas`, alem do resumo financeiro completo do titulo;
- o relatorio `Movimentacoes Financeiras` passou a exibir `valor total`, `quitado`, `aberto` e quantidade de baixas por titulo;
- o `Fluxo de Caixa` passou a usar cada `movimento realizado` no bloco de realizados e apenas o `saldo em aberto` nos titulos previstos;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.19.0`.

### 01/06/2026 - v2.18.1 / app 0.4.2
- o relatorio `Movimentacoes Financeiras` passou a consumir o catalogo configurado de `categorias`, `grupos DRE` e `subgrupos DRE`, inclusive para enriquecer registros legados;
- a tela operacional ganhou resumos de `entradas por categoria` e `saidas por categoria`, mantendo a mesma classificacao usada no modulo `Financeiro`;
- o `Fluxo de Caixa` passou a exibir `realizado por categoria` e `previsto por categoria`, alem de mostrar a classificacao gerencial nas listas de movimentos;
- a navegacao central de `Relatorios` foi ajustada para deixar explicita a leitura por categoria nos atalhos financeiro e fluxo;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.18.1`.

### 01/06/2026 - v2.18.0 / app 0.4.2
- o menu lateral deixou de tratar `Financeiro` como item isolado e passou a concentrar a navegacao em `Financas`, com atalhos dedicados para `Financeiro`, `DRE Gerencial`, `Fluxo de Caixa` e `Configuracoes`;
- foi criada a tela `Financas -> Configuracoes`, centralizando o cadastro de `categorias financeiras`, `grupos DRE` e `subgrupos DRE`;
- o formulario de lancamento financeiro passou a usar dropdowns estruturados para `Categoria`, `Grupo DRE` e `Subgrupo DRE`, com links rapidos para configuracao;
- as categorias financeiras agora podem sugerir defaults de `grupo`, `subgrupo`, impacto em `DRE`, impacto em `Caixa` e recorrencia de `despesa fixa mensal na DRE`;
- foram criadas as tabelas `financeiro_categorias`, `financeiro_dre_grupos` e `financeiro_dre_subgrupos`, com seed inicial e backfill a partir do que ja existia no financeiro;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.18.0`.

### 01/06/2026 - v2.17.2 / app 0.4.2
- o formulario de `Novo lancamento` e `Editar lancamento` passou a oferecer a chave `Despesa fixa mensal na DRE` para titulos do tipo `A pagar`;
- quando essa opcao e marcada, a despesa passa a entrar automaticamente na `DRE` de todos os meses seguintes a partir da `data_competencia`, sem novo cadastro manual mes a mes;
- a regra foi aplicada com a nova coluna `financeiro.dre_fixo_mensal` e leitura automatica no `FinanceiroModel::getDreReport()`;
- a listagem do `Financeiro`, o modal de detalhamento e o proprio relatorio `DRE Gerencial` passaram a sinalizar esse comportamento recorrente;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.17.2`.

### 31/05/2026 - v2.17.1 / app 0.4.2
- a listagem do modulo `Financeiro` ganhou uma coluna `Descricao` mais rica, exibindo contexto de `categoria`, `OS`, `cliente`, `equipamento` e resumo do servico ou observacao principal;
- clicar em `Receber`, `Pagar`, na `Descricao` ou no novo icone de `visualizacao` agora abre um modal detalhado do lancamento sem tirar o operador da grade;
- quando o titulo estiver vinculado a uma `OS`, o modal passa a mostrar `cliente`, `equipamento`, `relato do cliente`, `diagnostico tecnico`, `solucao aplicada`, `procedimentos executados`, itens/servicos e defeitos registrados;
- para contas a pagar sem OS, o modal centraliza o cadastro financeiro do titulo para consulta rapida, incluindo datas, classificacao, impacto gerencial e observacoes;
- foi criada a rota protegida `GET /financeiro/detalhes/{id}` para abastecer o modal com carregamento assincrono;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.17.1`.

### 31/05/2026 - v2.17.0 / app 0.4.2
- implementado o baseline do financeiro gerencial no ERP, com novos campos na tabela `financeiro` para `data_competencia`, `origem`, `grupo/subgrupo DRE` e flags de impacto em `DRE` e `Caixa`;
- a camada de modelo passou a classificar automaticamente lancamentos de `receita operacional`, `outras receitas`, `despesas operacionais` e `custo direto (OS)`, com backfill inicial na migration;
- criado o relatorio `DRE Gerencial`, com receita liquida por `data_entrega`, custos diretos vindos de `os_itens` e consolidacao de outras receitas e despesas operacionais por `data_competencia`;
- criado o relatorio `Fluxo de Caixa`, com `saldo inicial`, realizados por `data_pagamento`, previstos por `data_vencimento`, curva diaria e `saldo projetado`;
- o painel `Financeiro`, o relatorio operacional e o `Dashboard` passaram a chamar o indicador de caixa de `Resultado de caixa`, evitando confundir caixa realizado com lucro contabil;
- a ajuda contextual da documentacao recebeu um mapa de aliases para abrir corretamente os manuais de `Financeiro`, `Relatorios`, `Dashboard` e modulos correlatos;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.17.0`.

### 27/05/2026 - v2.16.42 / app 0.4.2
- adicionada uma trilha oficial para integrar o ERP ao `Setup Vem Fazer`, reaproveitando Swarm, Traefik e a rede ja provisionados na VPS;
- criado `scripts/docker/install-vemfazer-stack.sh` para clonar/atualizar o repositorio do ERP, gerar o env do stack, buildar a imagem localmente e publicar a stack com `docker stack deploy`;
- criado `docker/swarm/setup-vemfazer-stack.yml`, com labels do Traefik parametrizadas por `STACK_SLUG`, e `docker/swarm/setup-vemfazer.env.example` para servir de baseline ao deploy;
- criada documentacao dedicada em `documentacao/10-deploy/integracao-setup-vemfazer.md`, incluindo o snippet de menu para encaixar o ERP como opcao do `setup-vemfazer.sh`;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.16.42`.

### 19/05/2026 - ajuste operacional da coluna `Datas` (em homologacao)
- a listagem `/os` passou a interromper a contagem de atraso na `data_conclusao` quando a manutencao ja estiver encerrada, evitando que status como `Reparo Concluido`, `Reparado, Disponivel na Loja`, `Irreparavel` e `Reparo Recusado` continuem parecendo OS em execucao;
- a mesma coluna passou a exibir a linha `Conclusao` para status conclusivos, separando explicitamente o fim tecnico da manutencao da `Entrega`;
- quando a OS ja tiver `data_entrega`, o badge continua fechando o atraso pela entrega; quando faltar `data_conclusao` em registros legados ja encerrados, a interface usa `status_atualizado_em` apenas como fallback visual;
- o backend de mudanca de status passou a preencher `data_conclusao` para estados de manutencao encerrada e a preservar a data original quando ela ja existir, evitando sobrescrever a conclusao real em transicoes posteriores;
- o fluxo de status tambem passou a preencher `data_entrega` em `Entregue - Pendencia Financeira`, alinhando a coluna `Datas` com o significado operacional do status.

### 03/05/2026 - v2.16.41 / app 0.4.2
- o sino de notificacoes da navbar passou a abrir um modal SweetAlert2 com o teor completo da notificacao antes de qualquer navegacao;
- o dropdown ganhou a acao `Limpar lidas`, removendo rapidamente do inbox web as notificacoes ja processadas pelo operador;
- a geracao e a normalizacao de `rota_destino` foram alinhadas para a URL canonica da Central (`/atendimento-whatsapp?conversa_id={id}`), eliminando `404` por rotas legadas `/conversas/{id}`;
- os endpoints web e mobile/PWA que devolvem notificacoes passaram a normalizar automaticamente essas rotas de conversa, mantendo compatibilidade com registros antigos;
- foi registrada a referencia oficial do workflow n8n `Evolution -> IA -> ERP -> Evolution`, com JSON importavel e nota de implantacao para o desenho recomendado do WhatsApp automatizado;
- a referencia do workflow Evolution + IA foi refinada para incluir historico recente de `mensagens_whatsapp` no prompt, reduzindo respostas sem memoria de contexto e saudacoes incoerentes;
- a versao V2 com memoria (`evolution-whatsapp-atendimento-ia-erp-memoria.json`) foi ajustada para consolidar o historico do ERP em um unico contexto e limitar a saida automatica a no maximo 3 blocos, evitando rajadas de mensagens por inbound unico;
- a mesma V2 com memoria passou a consultar diretamente as ordens de servico recentes da tabela `os` antes do agente responder, mantendo o fluxo simples e evitando depender de tool SQL acionada pela IA para buscar OS;
- a referencia V3 `evolution-whatsapp-atendimento-ia-erp-v3.json` foi revisada para usar um agente de triagem dedicado, gravacao de lead em horario local (`America/Fortaleza`), base institucional por tool real de Google Drive e sem automacao de busca PDF nesta etapa;
- o repositorio passou a incluir tambem o subworkflow `evolution-whatsapp-atendimento-ia-v3-tool-base-institucional-drive.json`, usado como tool do agente principal para consultar a base oficial da assistencia no Google Drive;
- o repositorio passou a incluir trilha oficial de deploy do ERP em Docker Swarm para Contabo, com `Dockerfile`, stack do Traefik, volumes persistentes e script de publicacao do stack;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.16.41`.

### 03/05/2026 - v2.16.40 / app 0.4.2
- a Central de Mensagens local passou a sincronizar ativamente a Evolution API quando o provider direto estiver em `evolution`, evitando depender apenas de webhook remoto inacessivel ao `localhost`;
- o backend agora consulta os chats recentes da instancia Evolution, reconcilia `lastMessage` com `provider_message_id` e injeta inbound/outbound externo na thread da Central sem duplicar mensagens ja processadas;
- a listagem `/atendimento-whatsapp/conversas` e o stream da fila passaram a disparar `syncInboundSafe()` antes do snapshot, tornando as novas mensagens perceptiveis no ambiente local durante polling, SSE e busca por filtros;
- a versao oficial do ERP foi atualizada em `app/Config/SystemRelease.php` para `2.16.40`, corrigindo o rodape local que ainda exibia `2.16.38`.

### 29/04/2026 - v2.16.38 / app 0.4.2
- reforcada a responsividade das abas da tela `Visualizar Orcamento` para manter todos os destinos visiveis dentro da largura da tela, sem quebra de linha e sem barra de rolagem aparente;
- `app/Views/orcamentos/show.php` passou a distribuir as abas com largura fluida e a trocar os rotulos completos por versoes curtas ou micro em breakpoints menores;
- o componente continua seguindo o design system com linha unica, mas agora evita que as ultimas abas fiquem cortadas em viewports compactos;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.38`.

### 29/04/2026 - v2.16.37 / app 0.4.2
- ocultada a barra de rolagem horizontal visivel das abas da tela `Visualizar Orcamento`, mantendo o componente em linha unica no padrao do design system;
- reforcado o override local em `app/Views/orcamentos/show.php` para superar a regra global `ds-tabs-scroll` sem remover a navegacao horizontal quando ela for necessaria;
- eliminado o espacamento inferior herdado do trilho de scroll, deixando a navegacao mais limpa visualmente;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.37`.

### 29/04/2026 - v2.16.36 / app 0.4.2
- realinhada a navegacao da tela `Visualizar Orcamento` ao design system do projeto, removendo a quebra em multiplas linhas das abas;
- `app/Views/orcamentos/show.php` passou a usar `nav-tabs nav-fill ds-tabs-scroll`, no mesmo padrao ja empregado em outras telas do ERP;
- as abas continuam responsivas, mas agora preservam linha unica com scroll horizontal do componente em vez de empilhar em grade;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.36`.

### 29/04/2026 - v2.16.35 / app 0.4.2
- melhorada a responsividade das abas da tela `Visualizar Orcamento` para evitar barra de rolagem horizontal;
- `app/Views/orcamentos/show.php` passou a distribuir as abas em grade responsiva, com duas colunas em telas compactas e uma coluna em `<= 360px`;
- os rotulos das abas agora aceitam quebra de linha controlada e largura total do botao, preservando leitura e toque em mobile;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.35`.

### 29/04/2026 - v2.16.34 / app 0.4.2
- corrigido o modal `Atualizar prazos da OS` na listagem `/os`, que estava falhando por exigir `motivo_alteracao` sem oferecer campo na interface;
- `app/Views/os/index.php` passou a exibir `Motivo da alteracao` e, quando necessario, a area de `Autorizacao administrativa`;
- `public/assets/js/os-list-filters.js` passou a enviar motivo, data de entrada, data de entrega e credenciais administrativas no fluxo AJAX de prazos;
- `app/Controllers/Os.php` ganhou fallback para reutilizar as datas atuais da OS quando o POST vier incompleto, deixando o endpoint mais resiliente;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.34`.

### 29/04/2026 - v2.16.33 / app 0.4.2
- complementada a tela `Visualizar Orcamento` com a aba dedicada `Envio do orcamento`, separando PDF, WhatsApp e e-mail da aba de itens;
- `app/Views/orcamentos/show.php` passou a exibir a navegacao `Envio do orcamento` entre `Pacotes de servico` e `Orcamento`;
- a aba `Orcamento` ficou focada em itens, historico de status e rastreabilidade, enquanto o disparo ao cliente ganhou contexto proprio;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.33`.

### 29/04/2026 - v2.16.32 / app 0.4.2
- refinado o formulario `Novo Orcamento` e `Editar Orcamento` para unificar `Orcamento` e `Financeiro do orcamento` em uma unica aba;
- `app/Views/orcamentos/form.php` passou a exibir a navegacao `Orcamento e financeiro`, mantendo os cards de itens e totalizacao no mesmo `tab-pane`;
- o ajuste reduz troca de abas no fechamento comercial sem alterar IDs, calculos ou o comportamento de salvar;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.32`.

### 29/04/2026 - v2.16.31 / app 0.4.2
- reorganizado o formulario `Novo Orcamento` e `Editar Orcamento` em abas para separar cliente, equipamento, dados operacionais, pacotes de servico, itens do orcamento e financeiro;
- `app/Views/orcamentos/form.php` passou a usar `nav-pills` horizontais com suporte a scroll em mobile, mantendo os mesmos IDs e hooks do JavaScript existente;
- a aba `Pacotes de servico` agora tambem exibe estado vazio quando o modulo de oferta dinamica nao estiver ativo naquele contexto;
- a organizacao visual reduz a rolagem longa do formulario sem alterar o fluxo de salvar, recalcular itens, vincular OS ou detectar oferta de pacote;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.31`.

### 29/04/2026 - v2.16.30 / app 0.4.2
- reorganizada a tela `Visualizar Orcamento` em abas para separar cliente, equipamento, dados operacionais, pacotes de servico, orcamento e financeiro;
- `app/Views/orcamentos/show.php` ganhou um card de visao geral no topo e uma navegacao por abas com suporte responsivo a telas compactas;
- o backend passou a fornecer `equipamentoView` consolidado em `app/Controllers/Orcamentos.php`, permitindo exibir tipo, marca, modelo, cor e foto principal em uma aba dedicada;
- os fluxos ja existentes de PDF, WhatsApp, e-mail, historico de status e rastreabilidade foram preservados dentro da nova organizacao visual;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.30`.

### 29/04/2026 - v2.16.29 / app 0.4.2
- ajustado o pos-envio do orcamento revisado para que `reenviar_orcamento` funcione apenas como estado preparatorio antes do disparo ao cliente;
- depois de enviar por WhatsApp ou e-mail, o backend agora move o registro para `aguardando_resposta`, exibido ao operador com a label `Aguardando aprovacao`;
- `app/Controllers/Orcamentos.php` passou a considerar `STATUS_REENVIAR` no `markAsDispatched()`, evitando que o status fique preso em `Reenviar orcamento` apos o envio real;
- a terminologia visivel do status de espera foi alinhada em `app/Models/OrcamentoModel.php` e nos textos auxiliares do lifecycle;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.29`.

### 29/04/2026 - v2.16.28 / app 0.4.2
- complementada a regra de reenvio para que orcamentos com status `convertido` tambem abram nova rodada de aprovacao quando sofrerem edicao com mudancas relevantes;
- `app/Models/OrcamentoModel.php` passou a incluir `convertido` em `requiresReapprovalAfterEdit()`;
- `app/Services/OrcamentoService.php` passou a aceitar a transicao `convertido -> reenviar_orcamento`, destravando o salvamento da nova rodada;
- com isso, um orcamento convertido editado volta para `reenviar_orcamento`, incrementa a `versao` e reutiliza o mesmo registro para nova autorizacao do cliente;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.28`.

### 29/04/2026 - v2.16.27 / app 0.4.2
- liberada a edicao direta de orcamentos com status `convertido`, tanto na tela `Visualizar Orcamento` quanto no painel de orcamento embutido da OS;
- `app/Models/OrcamentoModel.php` deixou de marcar `convertido` como status travado para o formulario;
- `app/Views/orcamentos/show.php` voltou a exibir o botao `Editar` nesse estado e manteve o aviso contextual de que os ajustes continuam no mesmo registro;
- a operacao passa a permitir correcao de conteudo em orcamentos ja convertidos sem criar revisao automatica nem abrir apenas modo de consulta;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.27`.

### 29/04/2026 - v2.16.26 / app 0.4.2
- complementada a sincronizacao entre Orcamentos e OS para o caso em que a OS ainda permanecia `cancelado` mesmo depois da nova aprovacao do cliente;
- alem da reabertura em `aguardando_autorizacao`, o backend agora tambem retira a OS de `cancelado` quando o orcamento revisado chega a `aprovado` ou `convertido`;
- com isso, uma OS cancelada vinculada a um orcamento em `2ª aprovacao`, `3ª aprovacao` ou rodada equivalente volta a seguir corretamente para `aguardando_reparo`;
- o ajuste foi aplicado em `app/Controllers/Orcamentos.php` e `app/Controllers/Orcamento.php`, cobrindo fluxo interno e resposta pelo link publico;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.26`.

### 29/04/2026 - v2.16.25 / app 0.4.2
- corrigida a sincronizacao entre Orcamentos e OS para o caso pratico em que uma OS ja estava `cancelado`, mas o orcamento foi alterado e reenviado ao cliente;
- a reabertura da OS agora tambem acontece quando o orcamento entra novamente em estados comerciais ativos de autorizacao, como `pendente_envio`, `enviado`, `aguardando_resposta`, `reenviar_orcamento`, `aguardando_pacote` e `pendente`;
- com isso, a OS vinculada volta de `cancelado` para `aguardando_autorizacao` assim que uma nova rodada comercial valida for recolocada em andamento;
- o ajuste foi aplicado em `app/Controllers/Orcamentos.php` e `app/Controllers/Orcamento.php`, cobrindo tanto envio interno quanto resposta por link publico;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.25`.

### 28/04/2026 - v2.16.24 / app 0.4.2
- corrigido o erro JavaScript `discrepancias is not defined` na sincronizacao legada do checklist de entrada da OS;
- esse erro interrompia a execucao do script da tela `/os/editar/{id}` antes do bind do botao `Editar` do cliente, fazendo a acao parecer inoperante;
- a funcao `buildLegacyEstadoFisicoFromChecklist()` passou a usar identificador ASCII consistente, restaurando a inicializacao completa da aba `Cliente`;
- o fechamento do modal rapido de cliente tambem passou a mover o foco para fora da janela antes do `dismiss`, reduzindo o warning de `aria-hidden` com elemento descendente focado;
- a documentacao funcional do fluxo de edicao rapida do cliente foi atualizada para registrar o hotfix de runtime;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.24`.

### 28/04/2026 - v2.16.23 / app 0.4.2
- corrigido o fluxo do botao `Editar` na aba `Cliente` da OS, que podia nao abrir modal algum mesmo com cliente selecionado;
- o modal de edicao agora abre imediatamente com os dados ja disponiveis no Select2 e depois complementa os campos por AJAX;
- foi criada a rota `GET /clientes/json-edicao/{id}` com permissao `clientes:editar`, evitando dependencia indevida da permissao de visualizacao para carregar o cliente no contexto da OS;
- em falhas de leitura detalhada, o modal permanece aberto e exibe aviso contextual, sem deixar o operador com a impressao de clique sem efeito;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.23`.

### 28/04/2026 - v2.16.22 / app 0.4.2
- a aba `Cliente` do formulario de OS passou a manter um botao `Editar` visivel ao lado do seletor principal, habilitando a edicao do cliente em modal no mesmo fluxo de atendimento;
- o botao permanece desabilitado quando nao existe cliente selecionado e passa a abrir o modal rapido assim que um cliente valido esta vinculado na OS;
- foi criada a rota `POST /clientes/atualizar_ajax/{id}` para separar a atualizacao rapida em modal do endpoint de criacao, respeitando a permissao `clientes:editar`;
- apos salvar o cliente pelo modal, o Select2 da OS e o resumo do cliente continuam sendo atualizados sem refresh manual;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.22`.

### 28/04/2026 - v2.16.21 / app 0.4.2
- corrigida a exibicao do card `Historico de status` na tela `Visualizar Orcamento`, que podia mostrar `Sem historico de status` mesmo com registros existentes em `orcamento_status_historico`;
- a view `app/Views/orcamentos/show.php` voltou a mapear corretamente o array de timeline carregado pelo controller;
- a documentacao funcional do modulo de Orcamentos foi sincronizada com a correcao do painel de historico;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.21`.

### 28/04/2026 - v2.16.20 / app 0.4.2
- o modulo de Orcamentos passou a validar se o ultimo PDF gerado ainda corresponde ao estado atual do orcamento antes de reutilizar o arquivo em download, envio por e-mail ou envio por WhatsApp;
- quando o orcamento estiver mais novo que o PDF registrado, o sistema agora regenera o documento automaticamente para refletir status, versao, itens, valores e demais alteracoes recentes;
- o atalho `Baixar ultimo arquivo` da tela de visualizacao deixa de aparecer quando o arquivo salvo estiver defasado em relacao ao orcamento atual;
- a documentacao funcional do modulo foi sincronizada com essa regra de regeneracao automatica;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.20`.

### 28/04/2026 - v2.16.19 / app 0.4.2
- corrigida a excecao `Undefined variable $currentItems` no fluxo `Orcamentos::update()`, que podia quebrar a edicao de orcamentos durante a nova logica de reaprovacao no mesmo registro;
- o snapshot dos itens atuais do orcamento voltou a ser carregado no ponto correto da atualizacao, permitindo comparar alteracoes e abrir a rodada `reenviar_orcamento` sem erro de execucao;
- a documentacao do modulo de Orcamentos foi sincronizada com a estabilizacao do fluxo;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.19`.

### 28/04/2026 - v2.16.18 / app 0.4.2
- o modulo de Orcamentos deixou de criar um novo registro de revisao quando um orcamento aprovado precisa ser alterado e reenviado ao cliente;
- a nova rodada comercial passou a acontecer no mesmo `orcamentos.id`, com historico salvo em `orcamento_status_historico` e incremento de `versao` apenas quando o documento sai de um estado aprovado para nova aprovacao;
- foi criado o status `reenviar_orcamento`, usado para orcamentos aprovados que sofreram alteracoes e precisam de nova validacao do cliente;
- quando a nova rodada e aprovada, o sistema passou a expor label dinamica no status, como `2ª aprovacao` e `3ª aprovacao`, mantendo o mesmo registro;
- a resposta publica do cliente agora aceita esse novo ciclo de reaprovacao sem exigir novo token ou duplicacao do orcamento;
- a OS vinculada passa a voltar para `aguardando_autorizacao` quando o orcamento entra em `reenviar_orcamento`, vai para `cancelado` quando o cliente rejeita e segue para `aguardando_reparo` quando o cliente aprova;
- as telas `Orcamentos`, `Orcamento publico` e os badges auxiliares de `/os` foram ajustados para refletir a nova nomenclatura e o novo fluxo;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.18`.

### 28/04/2026 - v2.16.17 / app 0.4.2
- o dropdown `Imprimir` da visualizacao da OS passou a tratar `Bobina 80mm` como fluxo direto, abrindo o documento termico ja com disparo automatico da caixa de dialogo de impressao do sistema operacional;
- o formato `80mm` deixou de reutilizar a modal de pre-visualizacao do `A4` e ganhou layout proprio em `app/Views/os/print.php`, com composicao linear e tipografia mono mais adequada para impressoras termicas;
- o endpoint de impressao da OS passou a aceitar `auto_print=1` no contexto termico para acionar `window.print()` automaticamente sem etapa extra de gerenciamento;
- a documentacao funcional e o historico oficial foram sincronizados com o novo fluxo de impressao termica direta;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.17`.

### 27/04/2026 - v2.16.16 / app 0.4.2
- foi criada uma secao propria `Relato do Cliente e Diagnostico Tecnico` logo apos `Equipamento` no documento consolidado da OS;
- essa nova secao passa a ocupar o espaco antes usado por `Tecnico Responsavel`, simplificando a hierarquia do documento;
- a secao `Equipamento` voltou a ficar restrita aos dados tecnicos do aparelho;
- a documentacao funcional e o historico oficial foram sincronizados com essa reorganizacao do layout;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.16`.

### 27/04/2026 - v2.16.14 / app 0.4.2
- o bloco `Equipamento` do documento consolidado voltou a exibir `Relato do cliente` e `Diagnostico tecnico`, mas sem os cards/retangulos internos usados na tentativa anterior;
- os textos passaram a ser renderizados de forma integrada ao fluxo da secao, mantendo o contexto tecnico sem pesar visualmente o layout;
- a documentacao funcional e o historico oficial foram sincronizados com essa nova composicao do documento;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.14`.

### 27/04/2026 - v2.16.13 / app 0.4.2
- os cards detalhados de `Relato do cliente` e `Diagnostico tecnico` foram removidos de dentro da secao `Equipamento` no documento consolidado da OS;
- o bloco `Equipamento` voltou a permanecer focado apenas nos dados tecnicos principais do aparelho;
- a documentacao funcional e o historico oficial foram sincronizados com essa limpeza do layout do documento;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.13`.

### 27/04/2026 - v2.16.12 / app 0.4.2
- a secao `Técnico Responsável` deixou de ser renderizada no PDF gerado para envio por WhatsApp, permanecendo apenas no fluxo de impressao/preview quando aplicavel;
- os badges visuais do topo do documento consolidado foram removidos tanto da impressao quanto do PDF final;
- o campo `Formato` foi removido dos quadros informativos do documento consolidado;
- o bloco `Equipamento` passou a incorporar `Relato do cliente`, `Diagnostico tecnico`, `Solucao aplicada`, `Procedimentos executados` e observacoes relacionadas;
- a documentacao funcional e o historico oficial foram sincronizados com a nova composicao do documento consolidado da OS;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.12`.

### 27/04/2026 - v2.16.11 / app 0.4.2
- a secao `Relato do Cliente & Diagnostico Tecnico` foi removida especificamente do `render-mode-pdf` do documento consolidado da OS;
- com isso, o PDF gerado para envio deixa de incluir esse bloco, enquanto o preview do navegador pode continuar exibindo a secao quando necessario;
- a documentacao funcional e o historico oficial foram sincronizados com a remocao da secao no PDF;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.11`.

### 27/04/2026 - v2.16.10 / app 0.4.2
- o PDF consolidado da OS deixou de reutilizar a casca paginada em tabela da pre-visualizacao e passou a quebrar suas macrosecoes com divisores dedicados no `render-mode-pdf`;
- a mudanca busca reduzir paginas vazias, blocos deslocados e reordenacao agressiva de layout no arquivo anexado pelo WhatsApp;
- o preview do navegador continua com a paginação visual rica, enquanto o PDF final usa uma estrutura mais simples e previsivel para o `Dompdf`;
- documentacao tecnica e funcional do modulo de OS foi sincronizada com a nova estrategia de geracao do PDF;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.10`.

### 27/04/2026 - v2.16.9 / app 0.4.2
- o modo PDF do documento consolidado da OS passou a reproduzir com mais fidelidade a mesma area util da impressao do navegador, removendo folgas extras de wrapper que alteravam a quebra das paginas no `Dompdf`;
- as secoes extensas do documento agora podem quebrar entre paginas no PDF gerado, enquanto linhas de tabela, cards e blocos internos continuam preservados para evitar fragmentacao visual;
- o ajuste busca aproximar o anexo enviado por WhatsApp da mesma organizacao de secoes e paginas vista no fluxo de impressao da OS;
- documentacao tecnica e funcional do modulo de OS foi sincronizada com a correcao de fidelidade entre impressao e PDF;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.9`.

### 27/04/2026 - v2.16.8 / app 0.4.2
- o modal de impressao consolidada da OS foi simplificado para usar o formato selecionado exclusivamente no dropdown `Imprimir`, eliminando a duplicidade de selecao dentro da propria janela;
- a barra lateral antiga do preview foi removida para priorizar a leitura do documento real que sera impresso;
- o botao `Abrir em nova guia` foi movido para o cabecalho da modal, ao lado do badge do formato atual;
- o rodape da modal passou a concentrar `Incluir fotos no documento`, `Enviar PDF por WhatsApp` e `Imprimir agora`;
- o envio do PDF por WhatsApp a partir da impressao foi separado em um modal proprio, mantendo sincronizados formato e opcao de fotos com a pre-visualizacao;
- a documentacao funcional e de correcao do modulo de OS foi sincronizada com o novo fluxo de impressao;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.8`.

### 27/04/2026 - v2.16.7 / app 0.4.2
- consolidada a release tecnica dos ajustes recentes da listagem `/os`, incluindo a estabilizacao do bootstrap da `osTable` e a eliminacao do warning `Cannot reinitialise DataTable`;
- o modal `Alterar status da OS` recebeu saneamento complementar de labels PT-BR, contexto visual e timeline operacional;
- a impressao consolidada em `Folha A4` foi reorganizada para deixar o cabecalho institucional da empresa em largura total, destacar a identificacao operacional da OS em card proprio, separar os dados do cliente em secao dedicada, criar uma faixa propria para o equipamento e posicionar a foto principal opcional na lateral esquerda desse bloco;
- o envio de PDF da OS por `WhatsApp` passou a reutilizar o mesmo layout consolidado da impressao `A4` quando houver geracao sob demanda, inclusive como fallback automatico sem depender de um PDF salvo previamente;
- a view de impressao consolidada recebeu hardening de compatibilidade para manter elementos graficos, cards e blocos coloridos tambem no PDF final gerado pelo `Dompdf`;
- a composicao do PDF consolidado da OS foi simplificada em tabelas e secoes mais estaveis, melhorando a fidelidade do documento entre pre-visualizacao e envio por WhatsApp;
- as fotos da impressao consolidada passaram a ser incorporadas em `data URI`, garantindo a presenca da foto principal e das galerias tambem no PDF final;
- a paginacao do A4 foi organizada em tres blocos: pagina 1 com resumo operacional, pagina 2 com relato/checklist/financeiro/orcamento e pagina 3 reservada para Fotos Anexadas;
- o rodape do A4 passou a usar contagem coerente na pre-visualizacao e total real no PDF final gerado para envio;
- paginas sem conteudo util passaram a ser suprimidas no PDF consolidado, evitando folha de Fotos Anexadas sem imagem valida;
- o backend da OS teve alinhamento do mapa `humanizeWorkflowMacro()`, corrigindo labels de macrofases como `InterrupÃƒÆ’Ã‚Â§ÃƒÆ’Ã‚Â£o` e `ConcluÃƒÆ’Ã‚Â­do`;
- a documentacao oficial da release foi sincronizada com a nova versao do ERP;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.7`.

### 27/04/2026 - v2.16.5 / app 0.4.2
- restaurada a rota `/os`, que havia passado a responder com erro `500` apos uma rodada de substituicoes textuais em pt-BR corromper fallbacks `??`, nomes de metodos e blocos de bootstrap de dados;
- `app/Controllers/Os.php`, `app/Views/os/index.php`, `app/Views/os/form.php` e `app/Views/os/show.php` foram saneados ate voltarem a passar em `php -l`;
- a pagina de edicao da OS e a visualizacao da OS receberam nova limpeza de labels, avisos e mensagens legadas em pt-BR/UTF-8;
- o menu lateral voltou a exibir corretamente labels como `Ordens de Servico`, `Servicos`, `Estoque de Pecas` e `Gestao de Conhecimento` em ambientes afetados por texto mojibake;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.5`.

### 26/04/2026 - v2.16.4 / app 0.4.2
- a visualizacao da OS (`/os/visualizar/{id}`) ganhou um novo fluxo de `Imprimir` com dropdown de formatos `Folha A4` e `Bobina 80mm`;
- antes da impressao final, a tela agora abre um modal de pre-visualizacao com troca imediata de formato e opcao de incluir fotos sem sair da OS;
- o documento consolidado da impressao passou a reunir cliente, equipamento, datas, status, diagnostico, procedimentos, checklist, acessorios, estado fisico, itens, valores, orcamento vinculado e notas complementares;
- no modelo `A4`, quando as fotos estao habilitadas, a foto principal de perfil do equipamento passa a ocupar o topo direito do documento;
- as demais fotos anexadas sao agrupadas ao final por tipo operacional, incluindo entrada, acessorios, perfil e demais grupos tecnicos disponiveis;
- o modal de pre-visualizacao agora tambem pode enviar o PDF da OS por WhatsApp com mensagem personalizada baseada nos `Templates WhatsApp` da `Gestao de Conhecimento`;
- o backend passou a gerar PDF temporario para esse envio quando necessario, sem criar nova versao persistida em `os_documentos`;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.4`.

### 26/04/2026 - v2.16.3 / app 0.4.2
- a tela de edicao da OS (`/os/editar/{id}`) recebeu uma auditoria ampliada de pt-BR/UTF-8, cobrindo labels, placeholders, mensagens de checklist, textos de camera, resumo lateral e blocos operacionais;
- a visualizacao da OS (`/os/visualizar/{id}`) teve normalizacao complementar nos cards de contexto, na timeline `Historico e Progresso`, nas abas e no resumo do orcamento vinculado;
- o frontend reativo da listagem `/os` (`public/assets/js/os-list-filters.js`) passou a exibir mensagens e labels auxiliares em pt-BR consistente nos fluxos de status, prazo, fotos e orcamento;
- as telas `Orcamentos` de formulario e visualizacao receberam revisao adicional de textos de apoio, avisos de vinculo com OS e blocos de resumo comercial;
- a `Central de Mensagens` teve limpeza complementar de avisos, notificacoes e textos de modal para reduzir exibicao de frases legadas sem acentuacao;
- os layouts base `app/Views/layouts/main.php` e `app/Views/layouts/embed.php` alinharam o fallback do nome do sistema, reduzindo variacao textual em ambientes sem override de configuracao;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.3`.

### 26/04/2026 - v2.16.2 / app 0.4.2
- o modulo `Estoque de Pecas` recebeu normalizacao complementar de labels em pt-BR nas telas `Estoque de Pecas`, `Nova Peca`, `Editar Peca` e `Movimentacoes`;
- foram revisados titulos, cabecalhos de tabela, botoes, textos do modal `Importar Estoque (CSV)` e mensagens operacionais ligadas ao cadastro e importacao de pecas;
- a simulacao de precificacao da peca instalada passou a exibir toda a interface auxiliar em pt-BR consistente, incluindo avisos e toasts do SweetAlert2;
- o controller `Estoque.php` teve mensagens de sucesso, erro, exportacao CSV e logs operacionais ajustados para pt-BR;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.2`.

### 26/04/2026 - v2.16.1 / app 0.4.2
- normalizados em pt-BR os labels mais expostos da listagem `/os`, do modal `Alterar status da OS` e da visualizacao `/os/visualizar/{id}`, incluindo fluxo, timeline, hints e textos auxiliares;
- a confirmacao de fechamento da `Nova OS` pela listagem ganhou promocao de camada no SweetAlert2 para permanecer acima do modal iframe e do backdrop;
- o modulo `Servicos` recebeu revisao de labels em pt-BR nas telas de listagem, importacao CSV, cadastro/edicao e nas mensagens globais reaproveitadas pelo frontend;
- textos operacionais de controllers e scripts auxiliares tambem foram normalizados em pt-BR para reduzir alertas e mensagens com grafia inconsistente;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.16.1`.

### 26/04/2026 - v2.15.19 / app 0.4.2
- a tela `/os/visualizar/{id}` ganhou a nova aba `Documentos`, reunindo no mesmo contexto os cards de `Documentos PDF`, `Enviar por WhatsApp` e `Enviar por E-mail`;
- os cards antigos de `Documentos PDF` e `WhatsApp` deixaram de ficar soltos abaixo da visualizacao principal e passaram a acompanhar a navegacao por abas da OS;
- foi adicionada a rota `POST /os/email/{id}/enviar`, permitindo anexar um PDF ja gerado da ordem e enviar pelo SMTP configurado no ERP;
- o backend de `Os.php` passou a montar assunto, mensagem padrao e anexo a partir do documento selecionado em `os_documentos`;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.19`.

### 26/04/2026 - v2.15.18 / app 0.4.2
- corrigido o clique das notificacoes de resposta publica de orcamento na navbar, que em alguns ambientes estava abrindo `/os` fora do contexto do ERP e caindo em `404 Not Found`;
- o backend passou a persistir `rota_destino` com `site_url(...)`, respeitando instalacoes com `index.php` e subdiretorio;
- o frontend da navbar ganhou normalizacao adicional para notificacoes antigas ainda gravadas com rota iniciando por `/`, preservando a navegacao correta mesmo antes da limpeza da inbox;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.18`.

### 26/04/2026 - v2.15.17 / app 0.4.2
- a resposta publica do cliente ao orcamento (`aprovar` ou `rejeitar`) agora cria notificacao interna para usuarios com permissao de visualizar `OS` ou `Orcamentos`;
- a navbar passou a exibir um sino ao lado da foto do perfil, com feed autenticado, contador de nao lidas, stream SSE e fallback de polling;
- a listagem `/os` passou a escutar o evento `orcamento.public_status_changed` e recarrega automaticamente a grade para atualizar o badge comercial do orcamento sem `F5`;
- quando o modal `Alterar status da OS` estiver aberto para a mesma ordem, o contexto comercial tambem pode ser reidratado assim que a notificacao chega;
- foram adicionadas as rotas web `GET /notificacoes/navbar-feed`, `GET /notificacoes/stream`, `POST /notificacoes/lida/{id}` e `POST /notificacoes/lidas`;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.17`.

### 26/04/2026 - v2.15.16 / app 0.4.2
- o painel esquerdo do modal `Alterar status da OS` deixou de usar blocos empilhados e passou a operar com `3 abas internas`: `Acoes rapidas`, `Solucao e diagnostico` e `Gerenciamento do Orcamento`;
- a aba `Acoes rapidas` passou a ser a aba inicial padrao do modal, enquanto o frontend preserva a aba atual quando o resumo do orcamento e reidratado apos um `os:orcamento-updated`;
- a conversao para abas reduziu a altura ocupada no modal sem mexer na coluna fixa de `Historico e progresso`;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.16`.

### 25/04/2026 - v2.15.15 / app 0.4.2
- o modal `Alterar status da OS` da listagem `/os` passou a mostrar o numero da ordem no cabecalho, mantendo o contexto principal visivel desde a abertura;
- o card de `Acoes rapidas` agora agrupa `Status atual da OS`, `Fluxo normal sugerido` e `Fluxo selecionado`, sem depender do hint antigo espalhado entre cards;
- o modal recebeu um novo card tecnico de `Solucao e diagnostico`, com persistencia de `procedimentos_executados`, `solucao_aplicada` e `diagnostico_tecnico` no mesmo `POST /os/status-ajax/{id}`;
- o resumo de `Gerenciamento do Orcamento` passou a ficar embutido no modal de status, com abertura de `Criar`, `Editar` e `Visualizar` em iframe e sincronizacao automatica apos salvar o orcamento;
- o iframe de detalhes/orcamento aberto a partir do modal de status agora recebe promocao de camada para ficar acima do `#osStatusModal`, inclusive com backdrop correto;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.15`.

### 25/04/2026 - v2.15.14 / app 0.4.2
- o nome do cliente na listagem `/os` passou a ficar centralizado visualmente dentro da propria celula;
- a centralizacao foi aplicada tanto ao bloco de texto quanto ao botao clicavel da coluna `Cliente`, mantendo a navegacao para a ficha do cliente;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.14`.

### 25/04/2026 - v2.15.13 / app 0.4.2
- a coluna `Cliente` da listagem `/os` recebeu um ajuste fino adicional para aproximar a borda direita do texto exibido;
- o autoajuste do frontend passou a somar menos folga extra na medicao da coluna `Cliente`;
- a propria celula `Cliente` passou a usar `padding-right` mais enxuto, reduzindo o espaco visual antes da coluna `Equipamento`;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.13`.

### 25/04/2026 - v2.15.12 / app 0.4.2
- a coluna `Cliente` da listagem `/os` passou a quebrar o nome em ate `3 palavras por linha`, com limite de `3 linhas`, preservando o nome completo no hover;
- a largura de `Cliente` agora segue a maior linha efetivamente exibida entre as OS da pagina atual, em vez de manter um bloco mais largo do que o necessario;
- a coluna `Equipamento` deixou a largura fixa e passou a se ajustar pela maior palavra operacional visivel entre `Tipo`, `Marca` e `Modelo`;
- a medicao responsiva dessas colunas foi centralizada no frontend da listagem, preservando `Foto`, `N OS`, `Relato` e o restante da tabela sem regressao estrutural;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.12`.

### 25/04/2026 - v2.15.9 / app 0.4.2
- a aba `Pecas e Orcamento` de `/os/editar/{id}` passou a listar o orcamento vinculado com resumo por grupo e tabela completa de itens;
- quando a OS nao possui itens no orcamento, a propria aba agora oferece o fluxo de criar/lancar itens em modal iframe no mesmo padrao da `Nova OS` da listagem;
- quando ja existe orcamento vinculado, a aba passou a exibir acoes contextuais de `Visualizar orcamento` e `Editar orcamento` sem tirar o operador da edicao da OS;
- o modal embed do orcamento passou a sincronizar a aba da OS por `postMessage`, atualizando apenas o bloco do orcamento apos salvar;
- as `Fotos de Entrada` persistidas na edicao da OS agora podem ser excluidas da visualizacao e de `public/uploads/os_anormalidades` em uma unica acao reativa;
- a validacao da edicao da OS foi reforcada para aceitar apenas transicoes de status permitidas no select e impedir `Previsao de Entrega` anterior a `Data de Entrada`;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.9`.

### 25/04/2026 - v2.15.8 / app 0.4.2
- endurecido o salvamento das `Fotos de Entrada` da OS em `app/Controllers/Os.php`, cobrindo abertura e edicao;
- o backend agora aceita `fotos_entrada` e `fotos_entrada[]`, cria automaticamente `public/uploads/os_anormalidades` e usa nomes unicos com sufixo aleatorio para evitar colisao;
- falhas de caminho ou de movimentacao do arquivo passaram a gerar log tecnico e `warning` ao usuario, sem abortar o restante do salvamento da OS;
- a correcao foi validada em navegacao local nas OS `OS26033567` e `OS26033569`, incluindo o fluxo de edicao com anexo em `Fotos de Entrada`;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.8`.

### 25/04/2026 - v2.15.7 / app 0.4.2
- corrigido o bloqueio de salvamento em `/os/editar/{id}` quando a OS estava sem `Tecnico Responsavel` preenchido;
- a validacao customizada do frontend tratava `tecnico_id` como obrigatorio, mas o backend e o manual do modulo continuam considerando o campo opcional;
- a tela de edicao voltou a enviar `POST /os/atualizar/{id}` normalmente mesmo sem tecnico atribuido;
- validado o fluxo completo com persistencia real e restauracao do valor original no ambiente local;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.7`.

### 25/04/2026 - v2.15.6 / app 0.4.2
- o botao `Limpar` da listagem `/os` voltou a restaurar o estado inicial da tela, exibindo novamente apenas as ordens abertas;
- `Limpar todos` agora segue a mesma regra e remove apenas os filtros escolhidos pela equipe, sem abrir automaticamente a fila completa;
- a consulta ampla de abertas + fechadas foi movida para os filtros avancados, no novo seletor `Status geral`;
- a opcao `Todos os status` passou a concentrar o envio de `status_scope=all`, mantendo a busca geral fora da barra principal;
- a listagem continua iniciando pela fila de abertas, mas agora separa melhor `reset de filtros` de `consulta geral`;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.6`.

### 25/04/2026 - v2.15.5 / app 0.4.2
- a listagem `/os` passou a iniciar por padrao com a fila de ordens abertas;
- o antigo filtro `Status detalhado` foi renomeado para `Ordens abertas` e agora exibe apenas etapas operacionais abertas;
- foi adicionado o dropdown `Ordens fechadas` para consultar `Equipamento Entregue`, `Devolvido Sem Reparo` e `Equipamento Descartado`;
- `Busca global`, `Ordens abertas` e `Ordens fechadas` receberam textos auxiliares para reduzir ambiguidade na filtragem;
- o contexto visual da tabela agora alterna entre `Ordens em aberto`, `Ordens fechadas` e `Todas as ordens de servico` conforme o modo de consulta;
- os botoes `Limpar` e `Limpar todos` agora removem todas as filtragens e exibem juntas as OS abertas e fechadas por meio de `status_scope=all`;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.5`.

### 25/04/2026 - v2.15.4 / app 0.4.2
- corrigida a listagem `/os` para exibir na coluna `Status` o status real salvo na OS, mesmo quando existe um orcamento vinculado com status sugerido para o fluxo;
- o badge principal e o badge de fluxo da DataTable deixaram de ser sobrescritos visualmente por `os_status_sugerido`, preservando a leitura real da oficina na homologacao e na producao;
- o orcamento vinculado continua aparecendo na mesma celula apenas como contexto comercial auxiliar, com badge proprio e numero da proposta;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.4`.

### 24/04/2026 - v2.15.3 / app 0.4.2
- corrigida a regressao em que a sincronizacao automatica com orcamentos `aprovados` ou `convertidos` voltava a OS para `aguardando_reparo` mesmo depois de a oficina avancar manualmente para fases posteriores;
- o historico de status da OS ja registrava a mudanca manual, mas a listagem e outros fluxos podiam regravar `os.status` por causa da regra de sincronizacao com o orcamento mais recente;
- `Os.php`, `Orcamentos.php` e `Orcamento.php` passaram a preservar o status manual quando a OS ja estiver em etapa mais avancada do que o alvo sugerido pelo orcamento;
- `OsStatusFlowService` ganhou comparacao centralizada de ordem do fluxo para evitar rebaixamento indevido entre status tecnicos;
- versao oficial do ERP atualizada em `app/Config/SystemRelease.php` para `2.15.3`.

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
- A documentacao exclusiva do app foi centralizada em `documentacao/12-app-mobile-pwa/`, com README, politica de versoes e historico de releases.
- O build de producao do app foi endurecido para publicacao, eliminando bloqueio de hook naming na tela de nova OS.
- O deploy da VPS foi executado em modo seguro, sem sincronizacao de dados de teste nem de `public/uploads/`.

### 04/04/2026 - App mobile/PWA: documentacao exclusiva aprofundada + skills reais
- O hub `documentacao/12-app-mobile-pwa/` passou a concentrar a entrada oficial do app e a governanca de versionamento, sem duplicar a documentacao do ERP.
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
- A cor inicial do cadastro foi resetada para "Nao selecionada" para forÃƒÆ’Ã‚Â§ar a identificacao visual correta pelo usuario.

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
- Novos indices foram adicionados para catÃƒÆ’Ã‚Â¡logos de lookup e para os caminhos cronologicos por `cliente_id` e `equipamento_id`.

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
- Sidebar, shell principal, painel de fotos, resumo lateral e titulos auxiliares passaram a seguir o mesmo vocabulÃƒÆ’Ã‚Â¡rio visual do DS.
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
### v2.15.10 - Prazo sincronizado na edicao da OS
- A aba `Dados Operacionais` da tela `/os/editar/{id}` voltou a refletir corretamente o prazo salvo no dropdown `Prazo (dias)`, calculando a diferenca entre `Data de Entrada` e `Previsao de Entrega`.
- Quando a previsao salva coincide com os atalhos padrao (`1`, `3`, `7`, `30`), o select reabre ja selecionado; para intervalos personalizados, a interface cria uma opcao dinamica com a quantidade exata de dias.
- A persistencia continuou centralizada em `os.data_previsao`, evitando duplicidade de campo no banco e removendo a falsa impressao de que o prazo nao havia sido salvo.
### v2.15.11 - Larguras refinadas na tabela de OS e preview controlado do relato
- A listagem `/os` teve ajuste fino manual nas colunas `Foto` e `N OS`, reduzindo o espaco dessas areas para o tamanho estritamente necessario ao thumb e ao numero operacional.
- As colunas `Cliente` e `Valor Total` passaram a se ajustar automaticamente pela maior celula visivel na pagina atual, em vez de depender de larguras fixas excessivas.
- A coluna `Relato` passou a mostrar preview de ate `3 palavras por linha` em no maximo `3 linhas`, mantendo o texto completo disponivel no hover da celula.

### v2.23.0 - Baixa tecnica da OS, cartoes e cobranca automatica
- A listagem `/os` ganhou o modal `Baixa da OS`, permitindo concluir a ordem sem sair da fila operacional.
- A baixa passou a mostrar `custos estimados`, `taxas de cartao`, `valor liquido`, `saldo projetado` e `lucro estimado` antes da confirmacao final.
- O fluxo agora aceita `recebimento parcial`: nesse caso, a OS fica em `entregue_pagamento_pendente`, ou seja, concluida tecnicamente, mas ainda aberta para cobranca.
- Foi criada a memoria de `status_final_pendente_pagamento`, usada para encerrar a OS automaticamente no destino correto quando o titulo financeiro for totalmente quitado.
- O modulo `Financas -> Cartoes e taxas` foi adicionado para cadastro de `operadoras`, `bandeiras`, `taxas`, `parcelas` e `prazos de recebimento`, com simulador de venda liquida.
- Recebimentos em `cartao de credito` e `cartao de debito` passaram a registrar metadados da operadora e a gerar despesa automatica de `Taxa de cartao`, reduzindo corretamente o resultado liquido.
- Foi criada a fila `os_cobranca_agendamentos` com regua automatica em `1`, `3` e `5` dias para OS entregues com pagamento pendente.
- O formulario `/os/editar/{id}` agora bloqueia alteracoes em `forma_pagamento`, `valor_mao_obra`, `valor_pecas` e `desconto` quando a OS ja teve baixa tecnica, exigindo perfil administrador ou autenticacao administrativa no ato do salvamento.

### v2.23.1 - Correcao de scroll e responsividade no modal de baixa da OS
- O modal `Baixa da OS` passou a herdar o mesmo regime de altura, overflow interno e rodape fixo aplicado aos demais modais operacionais da listagem `/os`.
- A area central agora libera rolagem interna quando a janela tiver pouca altura, evitando corte do bloco de `Recebimentos`, `Resumo financeiro` e botoes finais.
- O layout recebeu reforco responsivo adicional para `767px`, `575px`, `430px`, `390px`, `360px` e `320px`, com melhor empilhamento de cards, botoes e linhas-resumo.

### 07/06/2026 - refinamento tecnico da listagem de OS
- A listagem `/os` foi reorganizada para seguir a ordem tecnica `Foto / OS`, `Cliente`, `Equipamento`, `Datas`, `Status / Orcamento`, `Valor`, `Relato` e `Acoes`.
- O numero da OS passou a ficar consolidado abaixo da foto na leitura principal, deixando a antiga coluna `N OS` recolhida para liberar largura util da grade.
- O numero operacional abaixo da foto deixou de exibir `#`, recebeu reforco visual de fonte e ficou alinhado ao bloco tecnico da coluna `Foto / OS`.
- A responsividade da tabela foi simplificada para um comportamento deterministico: fora do mobile, a coluna `Acoes` recolhe primeiro para o painel `+` e `Relato` passou a ficar permanentemente no detalhe expandivel, com o texto integral preservado.
- `Cliente` ganhou mais largura util, fonte menor, truncamento em linha unica e padding vertical adicional; `Equipamento` passou a manter o nome tecnico sem quebra para reduzir variacao visual causada por zoom.
- A busca global da listagem passou a localizar tambem qualquer sequencia numerica presente no telefone principal do cliente, inclusive quando a busca e feita apenas pelos digitos.
- `Status / Orcamento` deixou de mostrar `Orcamento ORC-...` e o texto auxiliar `Alterar status`, passando a usar o fallback `Orcamento indisponivel` quando a OS nao tiver orcamento vinculado.
- `Valor` foi compactado novamente em largura interna e no cabeçalho, com alinhamento a direita e padding lateral minimo, para aproximar ao maximo a borda da coluna do numero exibido; o titulo da coluna tambem recebeu largura visual menor.
- No mobile, a face principal do card passou a exibir somente `Foto / OS`, `Cliente`, `Tipo + Equip.` e o botao `+`, levando `Datas`, `Status / Orcamento`, `Valor`, `Relato` e `Acoes` para o detalhe expandido.
- O detalhe expandido do `+` passou a usar sempre o texto completo do relato, evitando repetir apenas o preview resumido da grade principal.
- A navbar da tela `/os` passou a manter a busca global visivel tambem no celular, com espacamento extra na pagina para evitar sobreposicao com o conteudo.
- A view da listagem e a navbar receberam nova rodada de saneamento pontual de labels/placeholder em `pt-BR`, priorizando trechos visiveis e seguros para nao afetar identificadores ou regras de negocio.
- O `datatable` da listagem recebeu reforco de compatibilidade no resumo de `Status / Orcamento`, evitando regressao por chamada interna antiga do helper de fluxo e prevenindo `500` no carregamento da grade.
