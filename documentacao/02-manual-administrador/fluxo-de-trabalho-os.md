# Manual do Administrador - Fluxo de Trabalho da OS

Atualizado em 26/03/2026.

## Objetivo

Permitir que o administrador configure o fluxo operacional dos status de Ordem de Servico sem depender de ajuste direto no banco.

Tela:
- `Gestao de Conhecimento -> Fluxo de Trabalho OS`

Rota:
- `GET /osworkflow`

## O que pode ser configurado

Para cada status da OS, a tela permite definir:
- `Ordem`
- `Ativo`
- `Final`
- `Pausa`
- `Pode ir para`

## Significado de cada coluna

### Ordem
- controla a posicao visual e o fallback automatico do workflow
- quando nao existem transicoes personalizadas, o ERP usa o status anterior e o proximo dessa ordem como caminhos permitidos

### Ativo
- status inativo deixa de aparecer como destino operacional
- manter status antigos inativos evita poluir o modal de troca de status na listagem

### Final
- identifica status de encerramento logico
- util para separar etapas concluidas de etapas intermediarias

### Pausa
- indica que a OS esta em espera operacional
- pode ser usado para leitura de contexto e regras futuras do fluxo

### Pode ir para
- define explicitamente os destinos permitidos a partir daquele status
- aceita mais de um destino
- permite configurar avancos e retornos controlados

## Regra de fallback

Se nenhuma transicao personalizada estiver salva:
- o sistema usa fallback automatico por `ordem_fluxo`
- cada status ativo pode ir para o anterior e para o proximo da sequencia

Se houver ao menos uma transicao personalizada salva:
- o ERP passa a respeitar exclusivamente as combinacoes registradas em `os_status_transicoes`

## Impacto na operacao

Essa configuracao afeta:
- modal de troca de status na listagem `/os`
- validacao de transicao no backend
- hints do fluxo operacional usados pela OS
- select `Status` da tela `/os/editar/{id}`, que agora lista todos os status operacionais ativos cadastrados para permitir ajustes administrativos fora da sequencia curta de transicoes

### Estrutura atual do modal de troca de status

Na listagem `/os`, o modal `Alterar status da OS` agora organiza a operacao em quatro frentes no mesmo contexto:

- cabecalho com o numero da OS;
- conjunto de `3 abas internas` no painel esquerdo:
  - `Acoes rapidas`, com `Status atual da OS`, `Fluxo normal sugerido` e `Fluxo selecionado`;
  - `Solucao e diagnostico`, com procedimentos executados, solucao aplicada e diagnostico;
  - `Gerenciamento do Orcamento`, com botoes contextuais de criar, editar e visualizar orcamento;
- painel lateral de `Historico e progresso`.

Consequencia administrativa:

- toda alteracao na arvore de transicoes impacta diretamente a aba `Acoes rapidas`, o select de destino e os hints exibidos nesse modal;
- quando um status operacional deixar de ter proximo passo principal, o card passa a informar que nao ha fluxo normal sugerido disponivel;
- quando o operador abrir `Editar` ou `Visualizar orcamento` a partir desse modal, o iframe de detalhes precisa abrir acima da troca de status e com backdrop proprio;
- se o orcamento alterar a OS por sincronizacao comercial, o resumo do card de orcamento e o contexto do modal precisam continuar coerentes com o workflow configurado.
- na visualizacao `/os/visualizar/{id}`, a aba `Documentos` agora usa o status atual da OS como contexto para montar envios de PDF por `WhatsApp` e `e-mail`, entao alteracoes no fluxo tambem impactam a comunicacao entregue ao cliente.

## Reflexo na listagem `/os`

A configuracao de status tambem impacta a filtragem principal da listagem:

- a tela inicia por padrao mostrando a fila de ordens abertas;
- o filtro `Ordens abertas` usa apenas etapas operacionais ainda em andamento;
- o filtro `Ordens fechadas` consulta os encerramentos `Equipamento Entregue`, `Devolvido Sem Reparo` e `Equipamento Descartado`;
- `Limpar` e `Limpar todos` limpam os filtros aplicados e restauram a fila padrao de ordens abertas;
- a consulta ampla de abertas + fechadas foi movida para `Status geral -> Todos os status` nos filtros avancados;
- quando o cliente responde um orcamento vinculado pelo link publico, a listagem `/os` recebe a mudanca em tempo real e re-renderiza o badge comercial da coluna `Status`;
- a mesma resposta gera notificacao interna no sino da navbar para usuarios com permissao de visualizar `OS` ou `Orcamentos`;
- se a equipe alterar, desativar ou reorganizar codigos centrais do workflow, convem revisar tambem a experiencia da listagem `/os`.

## Procedimento recomendado de alteracao

1. Abrir `Gestao de Conhecimento -> Fluxo de Trabalho OS`.
2. Revisar a ordem dos status.
3. Marcar quais status estao ativos.
4. Configurar os destinos permitidos por etapa.
5. Salvar.
6. Voltar para `/os`.
7. Testar a troca de status em uma OS real ou de homologacao.

## Cuidados

- nao deixar todas as transicoes vazias se a equipe precisar de retornos especificos diferentes do fallback
- revisar status finais antes de marcar destinos para frente
- sempre validar permissao operacional do modulo `os`

## Ajuda contextual

Botao de ajuda da tela:
- `openDocPage('os-workflow')`

Arquivo aberto:
- `documentacao/02-manual-administrador/fluxo-de-trabalho-os.md`
