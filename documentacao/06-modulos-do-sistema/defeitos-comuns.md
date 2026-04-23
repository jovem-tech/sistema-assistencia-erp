# Módulo: Defeitos Comuns

## Finalidade
Catálogo de defeitos por tipo de equipamento e classificação (hardware/software), com base de procedimentos.

## Tabelas Utilizadas
- `equipamentos_defeitos`
- `equipamento_defeito_procedimentos`
- `os_defeitos`

## Rotas Principais
- `GET /equipamentosdefeitos`
- `POST /equipamentosdefeitos/salvar`
- `POST /equipamentosdefeitos/atualizar/{id}`
- `GET /equipamentosdefeitos/excluir/{id}`
- `POST /equipamentosdefeitos/por-tipo`
- `GET /equipamentosdefeitos/procedimentos/{id}`

## Permissões
`equipamentos:visualizar`, `equipamentos:criar`, `equipamentos:editar`, `equipamentos:excluir`, `equipamentos:importar`
