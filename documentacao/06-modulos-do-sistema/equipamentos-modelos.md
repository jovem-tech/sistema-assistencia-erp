# Módulo: Modelos de Equipamento

## Finalidade
Cadastrar modelos vinculados a uma marca e suportar autocomplete inteligente.

## Tabela Utilizada
- `equipamentos_modelos`

## Rotas Principais
- `GET /equipamentosmodelos`
- `POST /equipamentosmodelos/salvar`
- `POST /equipamentosmodelos/importar`
- `POST /equipamentosmodelos/por-marca`

## Integração
Autocomplete via proxy:
`GET /api/modelos/buscar?q={termo}&marca={nome}&marca_id={id}&tipo={tipo}`

## Permissões
`equipamentos:visualizar`, `equipamentos:criar`, `equipamentos:importar`, `equipamentos:excluir`
