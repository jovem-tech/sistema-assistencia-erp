# Ordens de Servico - Listagem

Atualizado em 04/04/2026.

## Objetivo

Descrever a tela `/os`, focada em consulta rapida e entrada para abertura/detalhe da OS.

## O que a tela mostra

- header de modulo
- acao `Atualizar OS`
- acao `Nova OS`
- busca inteligente de OS
- cards compactos de ordens
- miniatura da foto principal do equipamento
- status e prioridade em chips

## Busca inteligente

O campo principal da listagem pesquisa:

- numero da OS
- numero legado da OS
- cliente
- telefone
- e-mail
- tecnico
- status
- prioridade
- marca
- modelo
- tipo
- IMEI
- serie
- cor
- observacoes

## Comportamento do card

- clicar na miniatura abre a galeria de fotos de perfil do equipamento
- clicar em qualquer outra area do card abre `/os/{id}`
- quando nao houver foto, o card usa fallback visual discreto

## Objetivo de UX

- leitura rapida
- toque confortavel
- navegacao direta
- zero dependencia de tabela horizontal
