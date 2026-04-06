# Ordem de Servico - Detalhe e Edicao

Atualizado em 04/04/2026.

## Objetivo

Descrever a tela `/os/{id}`, usada para leitura e atualizacao operacional da ordem.

## Blocos editaveis

- status
- prioridade
- tecnico
- data de entrada
- data de previsao
- relato do cliente
- defeitos
- diagnostico tecnico
- solucao aplicada
- valores
- pagamento
- garantia
- observacoes do cliente
- observacoes internas

## Fontes de dados

- detalhe principal: `GET /api/v1/orders/{id}`
- metadados operacionais: `GET /api/v1/orders/meta`
- atualizacao: `PUT /api/v1/orders/{id}`

## Regras

- status e prioridade usam listas controladas
- tecnico usa lista de funcionarios ativos
- defeitos aparecem por tipo de equipamento
- valores e garantia ficam liberados nesta etapa de edicao
- o app reaproveita o mesmo backend e a mesma OS do ERP

## Resultado de salvamento

- a OS atualiza na propria tela
- a resposta informa `updated_fields`
- alteracao de status gera evento para notificacao mobile
