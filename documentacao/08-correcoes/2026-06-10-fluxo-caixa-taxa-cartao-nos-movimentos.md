# Correcao - Fluxo de Caixa exibe taxa de cartao nos movimentos realizados

Data: 10/06/2026
Migracao: nao houve

## Problema

Na aba `Movimentos` do `Fluxo de Caixa`, as entradas baixadas com `cartao_credito` e `cartao_debito` mostravam apenas o identificador bruto da forma de pagamento. A taxa da operadora ja era gravada em `financeiro_movimentos_cartao`, mas nao era carregada nem exibida na listagem.

## Ajuste aplicado

- a consulta de `Fluxo de Caixa` passou a fazer `left join` com `financeiro_movimentos_cartao`;
- quando disponivel, o relatorio tambem resolve o nome da operadora e preserva o valor da taxa no payload da view;
- a coluna `Referencia` da aba `Movimentos` agora humaniza a forma de pagamento e adiciona a linha `Taxa operadora R$ ...` para baixas em cartao;
- o mesmo detalhe passou a aparecer no modal de detalhamento da `Grade diaria`, mantendo consistencia entre as visoes.

## Resultado operacional

- o operador consegue enxergar no mesmo movimento quanto foi pago de taxa para a maquinha;
- a leitura do liquido recebido em cartao fica mais auditavel sem abrir outras telas;
- o relatorio reaproveita o metadado ja salvo no financeiro, sem criar nova rota nem nova tabela.

## Arquivos impactados

- `app/Models/FinanceiroModel.php`
- `app/Views/relatorios/view_fluxo_caixa.php`
- `documentacao/01-manual-do-usuario/relatorios.md`
- `documentacao/01-manual-do-usuario/financeiro.md`
- `documentacao/04-banco-de-dados/tabelas-principais.md`
- `documentacao/05-api/rotas.md`
- `documentacao/08-correcoes/2026-06-10-fluxo-caixa-taxa-cartao-nos-movimentos.md`

## Observacoes

- nenhuma migration adicional foi criada ou executada;
- nao houve mudanca em `openDocPage`, porque a pagina e a ajuda continuam as mesmas.
