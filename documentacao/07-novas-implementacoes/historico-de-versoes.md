# Historico de Versoes do Sistema

Atualizado em: 27/03/2026  
Versao atual oficial: `2.5.9`

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
