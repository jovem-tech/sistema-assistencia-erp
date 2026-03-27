# Correcao: padronizacao automatica do nome do cliente

Data: 2026-03-24
Modulo: Clientes / Ordem de Servico / Equipamentos
Arquivos principais:
- `app/Controllers/Clientes.php`
- `public/assets/js/scripts.js`
- `app/Views/clientes/form.php`
- `app/Views/os/form.php`
- `app/Views/equipamentos/form.php`

## Problema
- O campo de nome do cliente aceitava qualquer combinacao de caixa alta e caixa baixa.
- Com caps lock ou shift ativos, o cadastro acabava salvo com nomes fora do padrao visual do sistema.

## Correcao aplicada
- O frontend passou a aplicar normalizacao automatica em title case nos campos marcados com `data-auto-title-case="person-name"`.
- O backend do modulo de clientes reaplica a mesma regra ao salvar, atualizar, salvar via AJAX e importar CSV.

## Resultado esperado
- Exemplos como `paULO silVA sousa`, `PAULO SILVA SOUSA` e `paulo silva sousa` passam a ser persistidos como `Paulo Silva Sousa`.
- O comportamento fica consistente no cadastro completo de clientes, no cadastro rapido da OS e no cadastro rapido usado pelo modulo de equipamentos.
