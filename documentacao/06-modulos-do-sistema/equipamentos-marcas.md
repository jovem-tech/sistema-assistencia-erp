# Módulo: Marcas de Equipamento

## Finalidade
Cadastrar e manter a lista de marcas (ex: Samsung, Apple, Dell).

## Tabela Utilizada
- `equipamentos_marcas`

## Rotas Principais
- `GET /equipamentosmarcas`
- `POST /equipamentosmarcas/salvar`
- `POST /equipamentosmarcas/importar`
- `GET /equipamentosmarcas/excluir/{id}`

## Permissões
`equipamentos:visualizar`, `equipamentos:criar`, `equipamentos:importar`, `equipamentos:excluir`
