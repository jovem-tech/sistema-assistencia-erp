# Correcao - Sidebar recolhida e colunas secundarias na listagem de OS

Data: 27/03/2026

## Problema
Mesmo apos a reestruturacao responsiva da `v2.4.0` e do ajuste de breakpoint da `v2.4.1`, a listagem `/os` ainda podia ficar larga demais em notebook:
- a sidebar fixa continuava consumindo largura util da tabela;
- `Relato`, `Valor Total` e `Acoes` ainda permaneciam abertas por tempo demais;
- o usuario acabava voltando a depender de compressao de colunas e risco de rolagem lateral.

## Correcao aplicada
- A pagina `/os` passou a usar sidebar recolhida automaticamente em desktop/notebook.
- O menu lateral agora expande por hover/foco apenas enquanto esta sendo usado, sem devolver a largura ao conteudo principal.
- O DataTable passou a esconder `Acoes` mais cedo, junto de `Valor Total` e `Relato`, e a recalibrar melhor `Equipamento` e `Datas` conforme a largura util real do card.
- As larguras minimas das colunas foram reduzidas para remover excesso de espaco e priorizar a leitura das colunas principais.

## Impacto
- A tabela deixa de depender de scroll horizontal como solucao principal.
- O expansor `+` vira o ponto central para colunas secundarias quando a largura real da tela nao comporta tudo.
- A interacao com a sidebar continua fluida e funcional na propria rota `/os`.
