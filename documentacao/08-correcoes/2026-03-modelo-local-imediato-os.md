# 2026-03 - Modelo local imediato no Select2 da abertura da OS

## Problema
No modal de cadastro rápido de equipamento em `/os/nova`, o campo **Modelo** exigia digitar 3 caracteres para listar opções, mesmo quando já existiam modelos cadastrados no banco para a marca selecionada.

## Correção aplicada

- Ajustada a ponte de busca (`ModeloBridge`) para retornar modelos locais da marca selecionada mesmo com busca vazia.
- Mantida a busca na internet somente quando a consulta possui **3 ou mais caracteres**.
- Ajustado o Select2 do campo `#novoEquipModelo` para aceitar consulta inicial sem digitação (`minimumInputLength: 0`), exibindo os modelos locais imediatamente.

## Resultado esperado

- Ao selecionar uma marca, o usuário já visualiza os modelos cadastrados localmente no dropdown.
- A digitação de 1 ou 2 caracteres filtra apenas modelos locais.
- A partir de 3 caracteres, além do local, a busca externa pode complementar resultados.
