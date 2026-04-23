# Correcao: modal rapido de equipamento abre apenas no botao

Data: 2026-03-25

## Problema

Na tela `Nova Ordem de Servico`, o modal `Cadastrar Novo Equipamento` podia ser aberto ao clicar na area do campo `Equipamento`, criando um comportamento inconveniente e inesperado para o usuario.

## Causa

O botao `+ Novo` estava aninhado na mesma estrutura de `label` do campo, o que ampliava a area de ativacao visual/logica da acao de cadastro rapido.

## Correcao aplicada

- o cabecalho do campo `Equipamento` foi separado em estrutura propria, mantendo a `label` ligada apenas ao `select`
- o botao `+ Novo` passou a ficar fora da `label`, preservando o mesmo layout visual
- o handler JS do botao agora bloqueia `preventDefault()` e `stopPropagation()` antes de abrir o modal

## Resultado esperado

- clicar no `Select2` de `Equipamento` apenas abre a selecao/lista do campo
- clicar na area do campo nao abre mais o modal de cadastro rapido
- o modal `Cadastrar Novo Equipamento` abre somente pelo botao `+ Novo`
