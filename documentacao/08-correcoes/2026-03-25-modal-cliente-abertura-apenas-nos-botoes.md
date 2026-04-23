# Correcao: modal rapido de cliente abre apenas pelos botoes de acao

Data: 2026-03-25

## Problema

Na tela `Nova Ordem de Servico`, o modal de cadastro/edicao rapida de cliente podia ser disparado ao clicar na area do campo `Cliente`, em vez de abrir somente pelas acoes dedicadas.

## Causa

Os botoes `+ Novo` e `Editar` estavam aninhados na mesma estrutura de `label` do campo, ampliando a area de ativacao da acao e gerando abertura indevida do modal.

## Correcao aplicada

- o cabecalho do campo `Cliente` foi reorganizado para manter a `label` ligada apenas ao `select`
- os botoes `+ Novo` e `Editar` foram mantidos como acoes paralelas, fora da `label`
- o comportamento de abertura do modal ficou restrito aos botoes dedicados

## Resultado esperado

- clicar no `Select2` de `Cliente` apenas abre a busca/selecao do campo
- clicar na area do campo nao abre mais o modal rapido
- o modal de cliente abre somente ao clicar em `+ Novo` ou `Editar`
