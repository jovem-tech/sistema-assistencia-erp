# Correcao/Implementacao - Listagem de OS com datas operacionais e workflow configuravel

Data: 26/03/2026

## Problema

A listagem de OS estava limitada para uso operacional intenso:
- equipamento exibido em texto unico
- relato escondido com `...`
- coluna de datas sem leitura de prazo/entrega
- status apenas visual, sem acao rapida
- fluxo de transicao dependente de base fixa, sem tela administrativa

## Solucao aplicada

- reestruturacao da coluna `Equipamento` com `Tipo`, `Marca` e `Modelo`
- quebra de linha controlada no `Relato`, com tentativa de ajuste de fonte para caber em ate 4 linhas
- substituicao da coluna `Data Abertura` por `Datas`
- adicao de indicadores de prazo e entrega
- modal de alteracao de status na propria listagem
- criacao da tela `Fluxo de Trabalho OS` para configuracao de destinos permitidos

## Impacto

- leitura mais rapida da grade de OS
- menor necessidade de abrir a OS apenas para entender contexto basico
- governanca do fluxo de status diretamente pelo ERP
- compatibilidade mantida com o fluxo de historico e automacoes ja existentes
