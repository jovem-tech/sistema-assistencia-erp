# Correcao - 500 no Checklist de Entrada por tabela ausente

Data: 05/04/2026

## Sintoma

Erro ao abrir:

- `GET /checklists/entrada`
- resposta `500 Internal Server Error`

Trecho observado em log:

- `Table 'assistencia_tecnica.checklist_tipos' doesn't exist`

## Diagnostico

O ambiente estava com codigo do modulo checklist ativo, mas sem a migration de infraestrutura aplicada.  
Com isso, a busca do tipo `entrada` em `checklist_tipos` disparava excecao de banco.

## Correcao aplicada

- executada migration:
  - `php spark migrate`
- adicionada protecao no model:
  - `ChecklistTipoModel::findByCodigo()` agora trata excecao e retorna `null` com log tecnico, evitando erro fatal.
- mantido hardening no controller:
  - `Checklists::entrada()` valida infraestrutura antes de carregar o checklist.

## Arquivos alterados

- `app/Models/ChecklistTipoModel.php`

## Validacao recomendada

1. Executar `php spark migrate:status` e confirmar `2026-04-05-010000_CreateChecklistInfrastructure` como migrada.
2. Abrir `/checklists/entrada`.
3. Confirmar carregamento normal da tela (ou aviso controlado, sem erro 500).
