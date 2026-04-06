# Ordem de Servico - Abertura Completa

Atualizado em 06/04/2026.

## Objetivo

Descrever a tela `/os/nova`, usada para abertura completa de OS no mobile.

## Estrutura geral

- cabecalho do modulo
- botao `Reiniciar` (icone vetorial circular, sem emoji) para limpar o cadastro e restaurar os padroes
- menu hamburger de acoes extras
- bloco de dados principais
- bloco de relato do cliente com atalho de defeitos relatados
- bloco de dados operacionais
- bloco de previsao
- bloco de acessorios
- bloco de checklist de entrada
- bloco de fotos de entrada (em card padronizado)
- bloco de observacoes (cliente e internas em card padronizado)

## Revisao antes de salvar (`Criar OS`)

- ao tocar em `Criar OS`, o app abre um modal de revisao completa (sem salvar imediatamente)
- o modal lista:
  - todos os campos obrigatorios e opcionais
  - situacao de cada item (`Preenchido` ou `Pendente`)
  - fotos de entrada ja anexadas
- quando existe pendencia obrigatoria:
  - o botao primario de continuidade fica bloqueado
  - o app redireciona para o primeiro campo obrigatorio pendente
- quando sobram apenas pendencias opcionais:
  - o operador pode voltar para preencher
  - ou prosseguir sem preencher opcionais
- segunda etapa do modal:
  - pergunta se deve notificar o cliente na abertura da OS
  - opcoes: sem notificacao, mensagem de abertura, mensagem + PDF
- ao confirmar criacao:
  - o backend tenta enviar imediatamente a notificacao WhatsApp quando uma das opcoes de envio foi escolhida
  - no modo `mensagem + PDF`, o PDF de abertura e gerado e anexado quando possivel
  - se o PDF falhar, a abertura da OS continua e o sistema faz fallback para mensagem sem PDF
- o resultado desse despacho vira aviso operacional em `/notificacoes` (sucesso ou falha)
- somente apos essa confirmacao final a OS e enviada para criacao

## Selecao de cliente

- feita por campo inteligente pesquisavel com busca por nome e telefone
- apos selecionar, o campo vira um card resumido no mesmo padrao do design system:
  - nome completo
  - telefone principal (quando disponivel)
- para trocar de cliente, basta tocar no card e o seletor pesquisavel abre novamente
- no item selecionado da lista, o nome e o telefone usam contraste alto para manter legibilidade em qualquer brilho de tela
- possui atalho `+` para novo cliente
- a edicao do cliente fica em acao secundaria no menu
- modal de novo cliente passou a incluir:
  - nome do contato
  - telefone do contato
  - CEP
- ao preencher CEP valido, o app consulta automaticamente e completa endereco, bairro, cidade e UF

## Selecao de equipamento

- depende do cliente selecionado
- cada equipamento do cliente e exibido em card resumido para evitar confusao entre aparelhos parecidos
- o card mostra:
  - foto de perfil do equipamento
  - `tipo - marca`
  - `modelo - cor`
  - `numero de serie` ou `IMEI`, quando disponivel, sem exibir os dois ao mesmo tempo
- a busca inteligente do campo considera exatamente esses metadados
- quando o cliente possui varios equipamentos semelhantes, a selecao deixa de depender apenas do nome do modelo
- se a miniatura estiver disponivel, tocar na foto abre um carrossel com todas as fotos de perfil do equipamento
- possui atalho `+` para novo equipamento
- depois de selecionar um equipamento, o campo deixa de mostrar uma linha truncada e passa a exibir um card com:
  - foto de perfil clicavel
  - `tipo - marca`
  - `modelo - cor`
  - `N/S` ou `IMEI`, quando houver
- cadastro de equipamento segue o padrao do ERP, incluindo:
  - tipo
  - marca
  - modelo
  - numero de serie
  - IMEI
  - cor
  - senha de acesso
  - estado fisico
  - acessorios
  - foto de perfil

## Relato do cliente

- o campo `Relato do cliente` agora possui botao `+` ao lado, no mesmo padrao visual do seletor de equipamento
- ao tocar no `+`, o app abre modal com os itens de `Gestao de Conhecimento > Defeitos Relatados` do ERP
- o modal organiza por categoria e insere o texto escolhido direto no relato, sem perder o que ja foi digitado
- o texto inserido segue o mesmo padrao do ERP: normalizacao do prefixo `Cliente relata:` e pontuacao final automatica

## Checklist de entrada

- o campo antigo de `estado fisico` foi substituido por `Checklist`
- o botao `Checklist` abre o modal em qualquer estado da tela
- se ainda nao houver equipamento selecionado, o modal abre com orientacao para selecionar um equipamento
- quando houver modelo de checklist para o tipo do equipamento, os itens sao exibidos para preenchimento
- ao salvar, o resumo de discrepancias fica visivel no card do checklist

## Fotos no fluxo de abertura

- foto do equipamento: ate 4 fotos com crop antes de adicionar
- fotos de entrada da OS: ate 4 fotos com crop antes de adicionar
- ao selecionar foto de entrada (`Tirar foto` ou `Galeria`), o modal de corte abre imediatamente de forma consistente
- arquivos sem MIME valido continuam aceitos quando possuem extensao de imagem suportada (JPG, JPEG, JFIF, PNG, WEBP, GIF, BMP, HEIC, HEIF, AVIF)
- miniaturas clicaveis para preview ampliado
- os botoes `Tirar foto` e `Galeria` seguem o mesmo padrao visual dos botoes de acao dos cards operacionais

## Dados operacionais

- os dados operacionais ficaram agrupados em card (`collection-block`) para manter consistencia com os blocos de `Acessorios`, `Checklist`, `Fotos de entrada` e `Observacoes`
- inclui no mesmo container:
  - tecnico atribuido
  - prioridade
  - status
  - data de entrada
  - previsao (dias corridos)
  - data de previsao

## Acessorios

- adicionados por modal
- cada item pode ter descricao e fotos proprias
- as fotos do acessorio seguem o mesmo fluxo de crop e preview do app
- o botao `+ Adicionar` usa o mesmo tamanho-base do botao `Checklist`, mantendo consistencia visual entre cards

## Objetivo de UX

- abrir OS sem sair do contexto
- preservar progresso do formulario
- evitar duplicacao de cliente, marca e modelo
- reduzir erro operacional na escolha de equipamentos repetidos do mesmo cliente
