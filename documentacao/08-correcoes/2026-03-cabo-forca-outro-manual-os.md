# Correção: Tipos de Cabo na Abertura de OS

## Contexto
No cadastro rápido de acessórios da `OS > Nova`, o tipo de cabo não contemplava `Cabo de força` e a opção `Outro` não permitia detalhamento manual do tipo.

## Correção aplicada
- Adicionada a opção `Cabo de força` na lista de tipos do acessório `Cabo`.
- Quando o usuário seleciona `Outro`, o formulário exibe automaticamente um campo editável para informar manualmente o tipo de cabo.
- No salvamento, o texto manual informado em `Outro` passa a compor a descrição final do acessório.

## Resultado esperado
- Seleção direta: `Cabo Cabo de força`
- Seleção `Outro` com detalhe manual: `Cabo VGA para monitor`
- Sem tipo informado: `Cabo`

## Arquivo impactado
- `app/Views/os/form.php`
