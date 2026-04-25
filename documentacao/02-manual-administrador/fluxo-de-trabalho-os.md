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

## Reflexo na listagem `/os`

A configuracao de status tambem impacta a filtragem principal da listagem:

- a tela inicia por padrao mostrando a fila de ordens abertas;
- o filtro `Ordens abertas` usa apenas etapas operacionais ainda em andamento;
- o filtro `Ordens fechadas` consulta os encerramentos `Equipamento Entregue`, `Devolvido Sem Reparo` e `Equipamento Descartado`;
- `Limpar` e `Limpar todos` limpam os filtros aplicados e restauram a fila padrao de ordens abertas;
- a consulta ampla de abertas + fechadas foi movida para `Status geral -> Todos os status` nos filtros avancados;
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
