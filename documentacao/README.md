# Documentacao - Sistema de Assistencia Tecnica

> Jovem Tech  
> ERP atual: `2.23.29`
> App mobile/PWA: `0.4.2`  
> Atualizado em `09/06/2026`

## Objetivo

Este diretorio concentra a documentacao funcional, tecnica e operacional do ERP da assistencia tecnica.

O indice abaixo foi revisado para refletir a linha atual `2.23.23`, incluindo a implementacao do financeiro gerencial (`DRE` + `Fluxo de Caixa`), o detalhamento em modal da grade do `Financeiro`, a opcao de `despesa fixa mensal na DRE`, o catalogo configuravel de `categorias`, `grupos DRE` e `subgrupos DRE`, `baixa parcial` com `multiplos movimentos por titulo`, o vinculo de `fornecedor` nas contas `A pagar`, a correcao da persistencia do status `ativo/inativo` no modulo `Fornecedores`, o preenchimento automatico de dados publicos por `CNPJ` no cadastro de fornecedores, a correcao do fluxo de exclusao com confirmacao padronizada para botoes baseados em `data-url`, a origem automatica com ajuda reforcada de `data de competencia` no formulario financeiro, a UX de `Mes/ano de competencia` com persistencia automatica em `01/mm/aaaa` para lancamentos manuais, a correcao do `PDF de abertura` da OS com checklist de entrada, observacoes livres do estado, a prompt automatica de envio apos a criacao da ordem, o refinamento dessa prompt com as opcoes `Enviar agora`, `Gerar sem abrir WhatsApp` e `Enviar depois`, a selecao de `fotos de perfil` e `fotos de entrada` na geracao manual do `Comprovante de abertura` pela aba `Documentos`, a preservacao da aba `Documentos` apos geracao e envios, o cadastro tecnico de `Desktop montado` com agente de inventario, o `Coletor de Bancada` portatil distribuido em `.zip` para `Desktop` e `Notebook`, a importacao direta do snapshot local salvo em `C:\JovemTechBenchCollector`, a nomeacao final do JSON por `OS` no padrao `inf_<numero_os>.json`, a limpeza automatica do `JovemTechBenchCollector.exe` e do `README.md` apos a coleta local, o suporte completo a `Notebook` no botao `Buscar do agente (C:\)`, o fallback `BIOS -> MAC` para numero de serie, a correcao definitiva do `dry-run` local para evitar falha de `Invalid URI`, o enriquecimento desse snapshot como `OS digital`, com dados essenciais da ordem, do cliente e da Jovem Tech, a padronizacao do campo catalogado `Modelo` para priorizar o `chipset` na importacao local do agente, o comparativo mensal de `OS abertas` x `OS entregues reparadas` no grafico principal do `Dashboard`, a deduplicacao de equipamentos por `numero de serie`, `MAC` e `IMEI`, com vinculacao segura de clientes ao mesmo bem fisico, o resumo compacto de `Desktop montado` na grade da `OS`, priorizando `gabinete`, `chipset` e `processador`, a correcao da coluna `Descricao` no `Financeiro`, com quebra por palavra e largura minima operacional, o endurecimento do modal detalhado do `Financeiro` para funcionar tambem em titulos sem `OS` vinculada, o ajuste de layout do formulario financeiro para alinhar `vencimento`, `competencia` e `pagamento` lado a lado, o filtro dedicado de `Despesas fixas` na listagem operacional, a evolucao do RBAC com modulos independentes para `CRM`, `Atendimento WhatsApp` e `Precificacao`, a `grade diaria operacional` no `Fluxo de Caixa`, com revisao de acentuacao em `pt_BR` na propria tela, a normalizacao do valor efetivo da `OS` entre listagem, aba `Valores` e modal `Baixa da OS`, a classificacao operacional de `recebimento da baixa`, `adiantamento` e `sinal` dentro do mesmo modal, a regra de que `Adiantamento` e `Sinal` nao alteram status da OS, a reorganizacao da grade diaria do caixa com detalhamento por modal, e agora a sincronizacao imediata dos valores digitados no `Resumo financeiro e lucro` da `Baixa da OS`.

Nesta rodada `2.23.2`, o `Fluxo de Caixa` passou a exibir a composicao visual das `entradas previstas` e `saidas previstas` no proprio resumo do periodo e tambem na `grade diaria operacional`.

Atualizacao complementar `2.23.3`: a aba `Valores` da `OS` agora destaca `adiantamento recebido`, `total recebido`, `saldo pendente` e o `historico de recebimentos`, enquanto o modal `Baixa da OS` passou a deixar explicito que os valores anteriores sao tratados como adiantamento.

Atualizacao complementar `2.23.4`: quando a `OS` ainda estiver com os campos financeiros zerados, mas ja tiver `orcamento aprovado` ou `convertido`, o sistema passa a usar esse valor efetivo no modal `Baixa da OS`, na aba `Valores` e nos metadados auxiliares da listagem.

Atualizacao complementar `2.23.5`: o modal `Baixa da OS` passou a oferecer o botao `Adicionar adiantamento`, com escolha rapida entre `Adiantamento total` e `Sinal`, e a aba `Valores` agora diferencia esses lancamentos no historico financeiro da OS.

Atualizacao complementar `2.23.10`: o nome do cliente no card mobile da listagem `/os` passou a usar toda a largura util do card, sem faixa horizontal curta, e a navbar mobile passou a alinhar menu hamburger, titulo, notificacoes e perfil de forma global no sistema.

Atualizacao complementar `2.23.11`: a busca global saiu da navbar mobile e passou para o topo do menu hamburger, mantendo a barra superior compacta em todo o sistema.

Atualizacao complementar `2.23.21`: `Adiantamento` e `Sinal` na baixa da OS passaram a ser tratados apenas como antecipacao financeira, com registro em `Fluxo de Caixa` e `DRE`, enquanto somente `Recebimento da baixa` altera o status operacional.

Atualizacao complementar `2.23.22`: a `Grade diaria operacional` do `Fluxo de Caixa` ficou mais limpa e passou a abrir os detalhes do dia em modal pela coluna `Acoes`.

Atualizacao complementar `2.23.23`: o painel `Resumo financeiro e lucro` da `Baixa da OS` passou a manter em tempo real os valores de `Adiantamento ja recebido`, `Lancado nesta acao` e `Saldo projetado apos salvar` enquanto a equipe digita os recebimentos.

Para referencia operacional atual, considere a linha vigente do ERP como `2.23.23`.

## Estrutura

| Pasta | Conteudo principal |
|---|---|
| `00-visao-geral` | contexto do produto e stack |
| `01-manual-do-usuario` | operacao diaria por modulo |
| `02-manual-administrador` | configuracao, permissoes e governanca |
| `03-arquitetura-tecnica` | estrutura do codigo e fluxos internos |
| `04-banco-de-dados` | tabelas, relacionamentos e regras de persistencia |
| `05-api` | rotas HTTP internas e publicas |
| `06-modulos-do-sistema` | visao tecnica por modulo |
| `07-novas-implementacoes` | releases e entregas funcionais |
| `08-correcoes` | historico de bugfixes e hotfixes |
| `09-roadmap` | planejamento futuro |
| `10-deploy` | instalacao, atualizacao e operacao em VPS |
| `11-padroes` | convencoes de projeto |
| `12-app-mobile-pwa` | documentacao dedicada do app mobile/PWA |

## Leitura rapida recomendada

### Operacao

- OS - manual do usuario: `01-manual-do-usuario/ordens-de-servico.md`
- Orcamentos - manual do usuario: `01-manual-do-usuario/orcamentos.md`
- Fluxo administrativo de OS: `02-manual-administrador/fluxo-de-trabalho-os.md`

### Tecnica

- Modulo OS: `06-modulos-do-sistema/ordens-de-servico.md`
- Modulo Orcamentos: `06-modulos-do-sistema/orcamentos.md`
- Modulo WhatsApp e automacoes conversacionais: `06-modulos-do-sistema/whatsapp.md`
- Modulo Checklists: `06-modulos-do-sistema/checklists.md`
- Modulo Pacotes de Servicos: `06-modulos-do-sistema/pacotes-servicos.md`
- Modulo Precificacao: `06-modulos-do-sistema/precificacao.md`
- Manual do financeiro: `01-manual-do-usuario/financeiro.md`
- Planejamento e evolucao do financeiro gerencial: `09-roadmap/estudo-dre-fluxo-de-caixa.md`
- Arquitetura do modulo de orcamentos: `03-arquitetura-tecnica/modulo-orcamentos.md`
- Estrutura de pastas: `03-arquitetura-tecnica/estrutura-de-pastas.md`
- Fluxo Git multiambiente: `10-deploy/fluxo-git-multiambiente.md`
- Guia rapido do fluxo 4 ambientes: `10-deploy/guia-rapido-fluxo-4-ambientes.md`
- PDF do guia rapido 4 ambientes: `10-deploy/guia-rapido-fluxo-4-ambientes.pdf`
- Deploy Docker Swarm no Contabo: `10-deploy/docker-swarm-contabo.md`
- Integracao com o Setup Vem Fazer: `10-deploy/integracao-setup-vemfazer.md`
- Workflow n8n por etapas da Jovem Tech: `07-novas-implementacoes/2026-05-07-workflow-n8n-jovem-tech-etapas-atendimento.md`

### Versao e release atual

- Historico oficial de versoes do ERP: `07-novas-implementacoes/historico-de-versoes.md`
- Release atual: `07-novas-implementacoes/2026-06-07-release-v2.23.11-busca-mobile-menu-hamburger.md`
- Registro da release anterior na VPS: `10-deploy/2026-04-23-atualizacao-vps-release-v2.15.0.md`
- Nota tecnica de containerizacao Docker/Swarm: `07-novas-implementacoes/2026-05-04-deploy-docker-swarm-contabo.md`

### App mobile/PWA

- Hub oficial do app: `12-app-mobile-pwa/README.md`
- Politica de versoes do app: `12-app-mobile-pwa/09-versionamento-e-releases/politica-de-versoes.md`
- Historico do app: `12-app-mobile-pwa/09-versionamento-e-releases/historico-de-versoes.md`

## O que foi consolidado nesta atualizacao documental

- fluxo Git multiambiente atualizado para o modelo oficial `develop-desktop -> homolog-vm -> main -> VPS`;
- homologacao da `VM Ubuntu 24` formalizada como etapa obrigatoria antes da promocao para `main`;
- checklist de backup da `VPS` consolidado com codigo, banco e arquivos antes de cada deploy;
- a sincronizacao entre `Orcamento` e `OS` foi endurecida para nao rebaixar fases ja avancadas do reparo quando o orcamento permanece `aprovado` ou `convertido`;
- a listagem `/os` segue iniciando pela fila de ordens abertas, com multiselect dedicado para etapas abertas e dropdown especifico para ordens fechadas;
- o reset manual por `Limpar` e `Limpar todos` voltou a limpar apenas os filtros selecionados e restaurar o estado inicial da fila aberta;
- o seletor avancado `Status geral` passou a concentrar a consulta ampla de `Todos os status`, incluindo abertas + fechadas;
- a edicao da OS voltou a salvar normalmente mesmo quando `Tecnico Responsavel` estiver vazio, alinhando a validacao do frontend com a regra opcional do modulo;
- o upload de `Fotos de Entrada` na abertura e na edicao da OS continua endurecido no backend e agora a tela de edicao tambem permite excluir fotos persistidas, removendo banco + arquivo fisico em `public/uploads/os_anormalidades` sem recarregar a pagina;
- a aba `Pecas e Orcamento` da edicao da OS passou a listar todos os itens do orcamento vinculado, com resumo por grupo (`pecas`, `servicos`, `pacotes` e similares);
- quando a OS ainda nao possui itens no orcamento, a propria aba abre um modal iframe para criar/lancar itens no orcamento no mesmo padrao visual da `Nova OS` da listagem;
- quando ja existe orcamento com itens, a mesma aba passa a oferecer acao contextual para editar ou visualizar o orcamento sem sair da edicao da OS;
- a coluna `Status` da listagem `/os` continua usando o status real salvo na OS como badge principal, mantendo o orcamento apenas como contexto auxiliar;
- na aba `Dados Operacionais` da edicao, o prazo voltou a reaparecer no dropdown `Prazo (dias)` a partir da combinacao salva entre `Data de Entrada` e `Previsao de Entrega`, inclusive para intervalos personalizados;
- a listagem `/os` usa larguras fixas proporcionais para `Foto / OS`, `Cliente`, `Equipamento`, `Datas`, `Status / Orcamento` e `Valor`, preservando o mini resumo financeiro sem sobrepor badges ou datas;
- a coluna `Cliente` agora quebra o nome em ate `4 linhas` de `3 palavras`, mantendo o telefone clicavel logo abaixo;
- a linha `Equip.` da coluna `Equipamento` quebra descricoes tecnicas em blocos de ate `3 palavras`, mantendo separadores `|` junto ao termo anterior;
- a coluna `Valor` recebeu largura propria para exibir `Recebido`, `Adiantamento`, `Saldo` e `Total OS` sem esmagar o conteudo;
- o nome do cliente passou a ficar centralizado visualmente dentro da propria celula na listagem `/os`;
- o modal `Alterar status da OS` da listagem `/os` passou a trazer o numero da ordem no cabecalho e uma area de trabalho com abas internas para `Acoes rapidas`, `Solucao e diagnostico` e `Gerenciamento do Orcamento`;
- as acoes rapidas do modal de status agora mostram `Status atual da OS`, `Fluxo normal sugerido` e `Fluxo selecionado` de forma agrupada, mantendo cliente, equipamento, timeline e historico no mesmo contexto;
- o card de orcamento dentro do modal de status passou a abrir `Criar`, `Editar` e `Visualizar orcamento` em iframe, com sincronizacao reativa do resumo apos salvar;
- a abertura de `Editar` ou `Visualizar orcamento` a partir do modal de status agora sobe na frente da troca de status, com camada de modal e backdrop promovidas corretamente;
- quando o cliente aprova ou rejeita o orcamento pelo link publico, o ERP passa a criar notificacao interna para usuarios com permissao de visualizar `OS` ou `Orcamentos`;
- a navbar ganhou um sino ao lado do perfil com feed autenticado, stream SSE e fallback por polling para exibir essas notificacoes sem recarregar a pagina;
- a listagem `/os` agora escuta o evento `orcamento.public_status_changed`, recarrega a grade automaticamente e atualiza o contexto do modal de status quando ele estiver aberto;
- o clique na notificacao da navbar agora abre um modal com o teor completo da notificacao e so navega para a conversa/rota de destino quando o operador confirmar, evitando `404` por rotas legadas;
- o dropdown do sino ganhou a acao `Limpar lidas`, removendo rapidamente do feed as notificacoes ja processadas pelo operador;
- a visualizacao `/os/visualizar/{id}` passou a concentrar geracao/listagem de PDFs e envios por `WhatsApp` e `e-mail` dentro da nova aba `Documentos`;
- o backend da OS ganhou a rota `POST /os/email/{id}/enviar`, usando `ErpMailService` para anexar um PDF ja gerado da ordem;
- o tipo `Orcamento` dentro da aba `Documentos` passou a reutilizar exatamente o PDF oficial emitido por `Orcamentos`, incluindo o link/botao de aprovacao publica no documento;
- quando a OS ainda nao possui orcamento vinculado e o operador escolhe gerar o PDF de `Orcamento`, a tela exibe confirmacao SweetAlert2 e pode abrir imediatamente o modal embutido de criacao do orcamento;
- ao lado do download do PDF gerado, a OS agora oferece visualizacao inline em modal para leitura rapida do documento;
- `Gestao de Conhecimento` passou a expor os submenus `Modelos PDF` e `Templates WhatsApp`, permitindo criar, editar, ativar e desativar modelos documentais e mensagens padrao reutilizadas pela aba `Documentos`;
- os nomes canonicos dos status da OS e os labels centrais do modulo de orcamentos foram normalizados para pt-BR, e um arquivo legado da camada `app/` foi saneado para UTF-8 valido;
- a listagem `/os`, o modal `Alterar status da OS` e a visualizacao `/os/visualizar/{id}` receberam normalizacao complementar de labels em pt-BR, cobrindo termos como `Ações rápidas`, `Solução e diagnóstico`, `Histórico e progresso`, `Últimas movimentações`, `Técnico`, `Orçamento`, `Previsão` e `Conclusão`;
- o fechamento da `Nova OS` pela listagem continua exigindo confirmacao, mas agora o SweetAlert2 sobe corretamente acima dos modais iframe e backdrops empilhados;
- o modulo `Serviços` teve as telas de listagem, importacao CSV e cadastro normalizadas em pt-BR nos titulos, labels, botoes e mensagens operacionais;
- o modulo `Estoque de Pecas` recebeu a mesma normalizacao em pt-BR nas telas de listagem, importacao CSV, cadastro/edicao, movimentacoes e mensagens do controller;
- a tela de edicao da OS, a visualizacao da OS, o formulario de Orcamentos, a visualizacao de Orcamentos e as mensagens reativas da listagem `/os` passaram por uma auditoria complementar de labels e mensagens em pt-BR/UTF-8;
- a `Central de Mensagens` recebeu limpeza adicional de alertas, notificacoes e textos de modal para reduzir exibicao de frases legadas sem acentuacao;
- o RBAC passou a tratar `crm`, `atendimento_whatsapp` e `precificacao` como modulos independentes, com migracao de compatibilidade para nao quebrar grupos ja existentes;
- foi criada uma landing comercial publica nas rotas `/site` e `/apresentacao`, alinhada aos modulos reais do ERP (`OS`, `Orcamentos`, `WhatsApp OS`, `CRM`, `Financeiro`, `PWA` e `Coletor de Bancada`), usando os dados institucionais configurados no proprio sistema e com copy revisada em pt-BR;
- o fallback do nome do sistema nos layouts `main.php` e `embed.php` foi alinhado para reduzir variacao textual entre ambientes;
- a listagem `/os` deixou de retornar `500` depois da restauracao sintatica de `app/Controllers/Os.php`, `app/Views/os/index.php`, `app/Views/os/form.php` e `app/Views/os/show.php`;
- a pagina de edicao e a visualizacao da OS receberam nova limpeza de labels, avisos e mensagens em pt-BR, reduzindo exibicao de textos legados com `?` no lugar de acentos;
- o menu lateral voltou a exibir corretamente rotulos como `Ordens de Servico`, `Servicos`, `Estoque de Pecas` e `Gestao de Conhecimento` em ambientes afetados por texto mojibake;
- indice principal sincronizado com a release `2.20.5`, com a implementacao de `DRE`, `Fluxo de Caixa`, o detalhamento em modal da grade do `Financeiro`, a opcao de `despesa fixa mensal na DRE`, os catalogos financeiros configuraveis, a `baixa parcial` com `multiplos movimentos`, sua leitura consistente nos relatorios e no fluxo de caixa, o vinculo de `fornecedor` em despesas `A pagar`, a correcao do status `ativo/inativo` no cadastro de fornecedores, o preenchimento automatico por `CNPJ` no cadastro de fornecedores, a `origem` automatica com ajuda reforcada de `data de competencia` no formulario financeiro, o campo `Mes/ano de competencia` com persistencia automatica em `01/mm/aaaa` para lancamentos manuais, a correcao do `PDF de abertura` da OS com checklist de entrada, observacoes livres do estado, o refinamento da prompt com `Enviar agora`, `Gerar sem abrir WhatsApp` e `Enviar depois`, a selecao de fotos tambem na geracao manual do `Comprovante de abertura` pela aba `Documentos`, a preservacao da propria aba `Documentos` apos geracao e envios, e agora o cadastro rapido inline de `Peca` e `Servico` no formulario de `Orcamentos`;
- a grade do modulo `Financeiro` agora exibe uma coluna `Descricao` mais contextual e abre um modal de consulta detalhada para receitas e despesas, inclusive com dados completos de `OS` quando houver vinculo;
- o formulario de lancamento financeiro agora permite marcar uma despesa recorrente para repeticao automatica na `DRE` dos meses seguintes;
- o modulo financeiro agora conta com `Financas -> Configuracoes`, centralizando cadastro de categorias financeiras e da estrutura de grupos/subgrupos da DRE;
- o `PDF de abertura` da OS voltou a exibir corretamente `Relato do cliente`, `Acessorios recebidos` e `Estado fisico`, e agora tambem lista `pendencias do checklist` junto do novo campo livre `Observacoes do estado na entrada`;
- ao salvar uma nova OS, a visualizacao pode abrir uma prompt automatica para preparar o envio do `PDF de abertura`, incluindo opcionalmente `fotos de perfil` e `fotos de entrada` no documento temporario;
- essa prompt pos-abertura agora tambem oferece `Gerar sem abrir WhatsApp` e `Enviar depois`, deixando o fluxo de balcao mais direto para quem so quer revisar ou imprimir o documento antes do disparo;
- o repositorio passou a incluir uma trilha oficial de integracao do ERP com o `Setup Vem Fazer`, com stack propria, env de referencia e instalador autonomo para publicar o ERP no Swarm ja existente;
- incluido o estudo oficial de implementacao do financeiro gerencial com `DRE`, `fluxo de caixa realizado` e `fluxo de caixa projetado`.
- a linha oficial do ERP foi consolidada em `2.23.29`, com o modal `Baixa da OS` perguntando se o operador deseja anexar o PDF consolidado no WhatsApp e com os links de ajuda de `Checklists`, `Pacotes de Servicos`, `Precificacao` e `App Mobile/PWA` apontando para paginas validas.

## Regra editorial

Sempre que houver nova release do ERP:

1. atualizar `app/Config/SystemRelease.php`;
2. revisar este indice;
3. atualizar o historico oficial em `07-novas-implementacoes/historico-de-versoes.md`;
4. publicar a nota tecnica da release;
5. registrar a atualizacao de VPS em `10-deploy/`, quando aplicavel.

## Deploy em container

Para ambiente Contabo com Docker Swarm + Traefik, use:

- `10-deploy/docker-swarm-contabo.md`
- `10-deploy/integracao-setup-vemfazer.md`
- `docker/swarm/contabo-stack.yml`
- `docker/swarm/contabo.env.example`
- `docker/swarm/setup-vemfazer-stack.yml`
- `docker/swarm/setup-vemfazer.env.example`
- `scripts/docker/deploy-contabo-swarm.sh`
- `scripts/docker/install-vemfazer-stack.sh`
