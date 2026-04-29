# Correcao da organizacao em abas no formulario de orcamento

Data: 29/04/2026
Modulo: Orcamentos

## Problema

A reorganizacao anterior em abas foi aplicada apenas na tela `Visualizar Orcamento`, enquanto a necessidade operacional tambem abrangia o formulario de criacao e edicao.

## Correcao aplicada

- a estrutura em abas foi levada para `app/Views/orcamentos/form.php`;
- os blocos `Dados do Cliente`, `Dados do Equipamento`, `Dados Operacionais`, `Pacotes de servico`, `Orcamento` e `Financeiro do orcamento` passaram a ficar em `tab-pane`;
- a navegacao usa `nav-pills` com scroll horizontal em telas menores;
- os componentes existentes do formulario foram preservados para manter o comportamento do JavaScript de calculo, vinculos e oferta de pacote.

## Resultado esperado

- a tela `/orcamentos/novo` passa a exibir abas logo acima dos cards do formulario;
- o mesmo comportamento se repete em `/orcamentos/editar/{id}`;
- o operador navega entre blocos sem depender de rolagem longa na pagina inteira.
