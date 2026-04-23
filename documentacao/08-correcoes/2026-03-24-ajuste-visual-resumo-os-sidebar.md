# Correcao: hierarquia visual no Resumo da OS

Data: 2026-03-24

## Problema
No card lateral `Resumo da OS`, os topicos estavam com pouco destaque visual e alguns valores longos, como o nome do equipamento, ficavam muito proximos do rótulo.

## Ajuste aplicado
- Os rótulos do resumo receberam peso tipografico mais forte.
- As linhas do resumo passaram a usar composicao em colunas, com area fixa para o topico e area flexivel para o valor.
- O bloco de valor ganhou espacamento adicional para melhorar a leitura quando o texto quebra em mais de uma linha.
- O ajuste foi mantido responsivo para a sidebar em larguras menores.

## Arquivo alterado
- `public/assets/css/design-system/layouts/os-form-layout.css`

## Resultado esperado
- `Cliente`, `Equipamento`, `Tecnico` e demais topicos aparecem com leitura mais clara.
- O nome do equipamento deixa de ficar colado ao topico no card lateral.
