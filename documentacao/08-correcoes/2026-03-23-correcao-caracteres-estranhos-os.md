# Correcao - caracteres estranhos na tela de OS

Data: 23/03/2026

## Problema reportado

Na tela de abertura da Ordem de Servico havia texto com encoding corrompido no cabecalho do bloco de dados, exibindo caracteres estranhos ao usuario.

## Correcao aplicada

1. Ajustado o texto do titulo `Cliente, Equipamento e Tecnico Responsavel` na view de formulario da OS.
2. O label voltou a ser renderizado corretamente como `Cliente, Equipamento e Tecnico Responsavel` na interface.

## Arquivos atualizados

- `app/Views/os/form.php`
- `documentacao/08-correcoes/2026-03-23-correcao-caracteres-estranhos-os.md`

## Resultado esperado

- A tela `/os/nova` e o modo embed da `Nova OS` passam a exibir o titulo sem mojibake.
- O usuario nao ve mais caracteres estranhos nesse bloco do formulario.
