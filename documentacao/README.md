# Documentacao - Sistema de Assistencia Tecnica

> Jovem Tech  
> ERP atual: `2.15.18`  
> App mobile/PWA: `0.4.2`  
> Atualizado em `26/04/2026`

## Objetivo

Este diretorio concentra a documentacao funcional, tecnica e operacional do ERP da assistencia tecnica.

O indice abaixo foi revisado para refletir a release `2.15.18`, com destaque para a edicao da OS com exclusao reativa das fotos persistidas de entrada, para o resumo embutido do orcamento na aba `Pecas e Orcamento`, para o modal de criacao/edicao de orcamento sem sair da tela da OS, para o ajuste do select de prazo na aba `Dados Operacionais`, para o refinamento manual das larguras da tabela `/os`, para o ajuste fino das colunas `Cliente` e `Equipamento`, para o aperto adicional da borda direita de `Cliente`, para a centralizacao visual do nome do cliente na celula, para o modal de status com abas internas, para a notificacao web em tempo real quando o cliente responde o orcamento pelo link publico e para o hotfix de navegação correta ao clicar nessa notificacao.

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
- Arquitetura do modulo de orcamentos: `03-arquitetura-tecnica/modulo-orcamentos.md`
- Estrutura de pastas: `03-arquitetura-tecnica/estrutura-de-pastas.md`
- Fluxo Git multiambiente: `10-deploy/fluxo-git-multiambiente.md`
- Guia rapido do fluxo 4 ambientes: `10-deploy/guia-rapido-fluxo-4-ambientes.md`
- PDF do guia rapido 4 ambientes: `10-deploy/guia-rapido-fluxo-4-ambientes.pdf`

### Versao e release atual

- Historico oficial de versoes do ERP: `07-novas-implementacoes/historico-de-versoes.md`
- Release atual: `07-novas-implementacoes/2026-04-26-release-v2.15.18-fix-rota-notificacao-orcamento.md`
- Registro da release anterior na VPS: `10-deploy/2026-04-23-atualizacao-vps-release-v2.15.0.md`

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
- a listagem `/os` passou a ajustar `Cliente` e `Valor Total` pela maior celula da pagina atual, reduziu `Foto` e `N OS` para o tamanho estritamente necessario e transformou `Relato` em preview de ate 3 linhas com leitura completa no hover;
- a coluna `Cliente` agora quebra o nome em ate `3 linhas` de `3 palavras` e usa a largura da maior linha visivel da pagina;
- a coluna `Equipamento` deixou a largura fixa e agora se ajusta pela maior palavra operacional visivel entre `Tipo`, `Marca` e `Modelo`;
- a coluna `Cliente` recebeu um aperto adicional na borda direita, reduzindo a folga entre o nome e a coluna `Equipamento`;
- o nome do cliente passou a ficar centralizado visualmente dentro da propria celula na listagem `/os`;
- o modal `Alterar status da OS` da listagem `/os` passou a trazer o numero da ordem no cabecalho e uma area de trabalho com abas internas para `Acoes rapidas`, `Solucao e diagnostico` e `Gerenciamento do Orcamento`;
- as acoes rapidas do modal de status agora mostram `Status atual da OS`, `Fluxo normal sugerido` e `Fluxo selecionado` de forma agrupada, mantendo cliente, equipamento, timeline e historico no mesmo contexto;
- o card de orcamento dentro do modal de status passou a abrir `Criar`, `Editar` e `Visualizar orcamento` em iframe, com sincronizacao reativa do resumo apos salvar;
- a abertura de `Editar` ou `Visualizar orcamento` a partir do modal de status agora sobe na frente da troca de status, com camada de modal e backdrop promovidas corretamente;
- quando o cliente aprova ou rejeita o orcamento pelo link publico, o ERP passa a criar notificacao interna para usuarios com permissao de visualizar `OS` ou `Orcamentos`;
- a navbar ganhou um sino ao lado do perfil com feed autenticado, stream SSE e fallback por polling para exibir essas notificacoes sem recarregar a pagina;
- a listagem `/os` agora escuta o evento `orcamento.public_status_changed`, recarrega a grade automaticamente e atualiza o contexto do modal de status quando ele estiver aberto;
- o clique na notificacao da navbar passou a abrir a rota correta do ERP mesmo em ambientes com `index.php` e subdiretorio, sem cair em `404 Not Found`;
- indice principal sincronizado com a release `2.15.18` e com a nova nota tecnica do hotfix de rota das notificacoes.

## Regra editorial

Sempre que houver nova release do ERP:

1. atualizar `app/Config/SystemRelease.php`;
2. revisar este indice;
3. atualizar o historico oficial em `07-novas-implementacoes/historico-de-versoes.md`;
4. publicar a nota tecnica da release;
5. registrar a atualizacao de VPS em `10-deploy/`, quando aplicavel.
