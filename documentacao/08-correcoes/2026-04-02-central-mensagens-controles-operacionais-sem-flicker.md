# Correcao - Central de Mensagens: controles operacionais sem flicker

Data: 02/04/2026  
Release associada: `v2.10.8`

## Problema observado

- o sync automatico ainda causava desconforto visual na thread, com percepcao de mensagens/balhoes sumindo e reaparecendo;
- o status nao era acionavel no cabecalho;
- `Atribuir` direcionava para o contexto lateral em vez de abrir opcoes dedicadas;
- `Encerrar` nao permitia escolher entre concluir e arquivar;
- faltavam atalhos rapidos para `Arquivadas`, prioridade e alternancia bot/humano.

## Ajuste aplicado

- sync inbound em background passou a:
  - evitar reabrir a conversa ativa;
  - atualizar a fila apenas quando houver novas mensagens processadas;
- status da conversa no header virou acao clicavel com modal de selecao;
- `Atribuir` passou a abrir modal com responsaveis ativos;
- `Encerrar` ganhou decisao operacional (`Concluir` ou `Arquivar`);
- filtro rapido `Arquivadas` foi adicionado na fila;
- novos botoes no cabecalho:
  - `Prioridade`;
  - `Bot ativo`;
  - `Aguard. humano`;
- inbound em conversa `resolvida` agora reabre automaticamente para `aberta` no backend.

## Arquivos alterados

- `app/Views/central_mensagens/index.php`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `public/assets/js/central-mensagens.js`
- `app/Services/CentralMensagensService.php`
- `app/Config/SystemRelease.php`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/README.md`

## Resultado esperado

- conversa visualmente estavel durante sincronizacao automatica;
- gestao de status/atribuicao/prioridade mais rapida no cabecalho;
- encerramento com escolha explicita entre concluir e arquivar;
- fila arquivada acessivel por atalho rapido.
