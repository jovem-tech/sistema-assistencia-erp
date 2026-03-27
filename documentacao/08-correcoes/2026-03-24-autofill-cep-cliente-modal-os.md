# Correcao: autofill de CEP no modal rapido de cliente da OS

Data: 2026-03-24

## Problema
No cadastro rapido de cliente dentro da `Nova OS`, o preenchimento por CEP podia falhar ou nao atingir corretamente todos os campos do mesmo formulario.

## Ajuste aplicado
- O lookup de CEP passou a resolver primeiro o `form` ou `modal` correto antes de preencher os campos.
- O preenchimento automatico agora escreve `endereco`, `bairro`, `cidade` e `uf` no contexto certo do formulario.
- O fluxo tambem cobre digitacao, colagem e finalizacao do CEP, sem depender apenas do blur.
- Chamadas duplicadas para o mesmo CEP foram evitadas durante a digitacao.
- Em CEP nao encontrado, o aviso usa `SweetAlert2` quando disponivel.

## Arquivo alterado
- `public/assets/js/scripts.js`

## Resultado esperado
- Ao completar um CEP valido no cadastro de cliente, os campos de endereco sao preenchidos automaticamente.
- O foco segue para `Numero` para acelerar a digitacao do endereco.
