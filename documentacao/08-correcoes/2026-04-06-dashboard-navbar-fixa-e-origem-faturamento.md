# Correcao - Dashboard: navbar fixa e rastreabilidade do faturamento do mes

Data: 06/04/2026

Publicacao oficial no ERP: `v2.12.1` (08/04/2026)

## Problemas observados

1. A navbar superior nao permanecia fixa de forma consistente durante a navegacao.
2. Havia duvida operacional sobre a origem do valor exibido no card `Faturamento mes`.

## Ajustes aplicados

Arquivo alterado:

- `public/assets/css/estilo.css`

Mudancas:

- `.top-navbar` alterada para `position: fixed` com `top: 0`.
- Compensacao de layout adicionada em `.main-content` com `padding-top: var(--navbar-height)`.
- Offset lateral da navbar alinhado ao estado da sidebar:
  - aberta: `left: var(--sidebar-width)`
  - recolhida: `left: var(--sidebar-collapsed)`
  - mobile (`<= 991.98px`): `left: 0`
- Ajuste de impressao para remover compensacao (`padding-top: 0 !important` em `@media print`).

## Origem oficial do card "Faturamento mes"

Fluxo:

1. `app/Controllers/Admin.php` chama `OsModel::getDashboardStats()`.
2. `app/Models/OsModel.php` calcula `faturamento_mes` com `SUM(valor_final)`.
3. Filtro aplicado:
   - base atual: `status = 'entregue_reparado'`
   - referencia temporal: `MONTH(data_entrega)` e `YEAR(data_entrega)` do mes/ano correntes.

Consulta equivalente:

```sql
SELECT COALESCE(SUM(valor_final), 0) AS faturamento_mes
FROM os
WHERE status = 'entregue_reparado'
  AND MONTH(data_entrega) = MONTH(CURRENT_DATE())
  AND YEAR(data_entrega) = YEAR(CURRENT_DATE());
```

## Resultado esperado

- Navbar sempre fixa no topo durante o scroll.
- Card `Faturamento mes` com criterio de calculo documentado e auditavel.
