# Correcao - bloqueio indevido na edicao de orcamento convertido

Data: 29/04/2026

## Problema

Orcamentos com status `convertido` continuavam sendo tratados como bloqueados para edicao direta.

Na pratica, isso causava dois efeitos visiveis:

- a tela `Visualizar Orcamento` escondia o botao `Editar`;
- o painel de orcamento embutido na OS passava a priorizar apenas visualizacao/consulta para esse mesmo registro.

## Ajuste aplicado

- a regra de bloqueio em `app/Models/OrcamentoModel.php` foi removida para o status `convertido`;
- a view `app/Views/orcamentos/show.php` deixou de tratar esse estado como impeditivo para abrir o formulario;
- o contexto da OS herdou a mesma liberacao via campo `is_locked`.

## Resultado

Orcamentos convertidos voltam a poder ser editados normalmente no proprio registro, inclusive a partir da OS, sem depender de criar uma revisao separada.
