# Correcao do modal de prazos da OS sem campo de motivo

Data: 29/04/2026
Modulo: Ordens de Servico

## Problema

O modal `Atualizar prazos da OS` falhava ao salvar porque o backend exigia `motivo_alteracao`, mas a interface nao oferecia nenhum campo para preenchimento. Em usuarios nao administradores, o fluxo ainda podia falhar novamente por falta do bloco de autorizacao.

## Correcao aplicada

- adicionado o campo obrigatorio `Motivo da alteracao` no modal;
- adicionada a area condicional de `Autorizacao administrativa` com usuario e senha;
- ajustado o AJAX para enviar todos os campos esperados pelo endpoint;
- reforcado o controller para reaproveitar as datas atuais da OS quando necessario.

## Resultado esperado

- o operador consegue justificar a mudanca de prazo no proprio modal;
- perfis nao administradores veem claramente quando a alteracao precisa de aprovacao;
- a atualizacao deixa de falhar por ausencia de campos que nao estavam visiveis na tela.
