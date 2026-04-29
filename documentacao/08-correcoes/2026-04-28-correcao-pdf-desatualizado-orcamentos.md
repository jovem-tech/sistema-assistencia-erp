# Correcao - PDF Desatualizado no Envio de Orcamentos

Data: 28/04/2026

## Problema

O modulo podia reutilizar o ultimo PDF salvo mesmo depois de uma alteracao no orcamento.

Na pratica, isso permitia:

- anexar no WhatsApp um PDF antigo apos mudanca de aprovacao ou reaprovacao;
- abrir em download um arquivo que nao refletia mais a versao atual do orcamento;
- manter visivel o atalho para um arquivo historico ja desatualizado.

## Correcao aplicada

- adicionada validacao de frescor do PDF com base em `orcamentos.versao` e `orcamentos.updated_at`;
- quando o orcamento estiver mais novo que o ultimo PDF registrado, o sistema passa a regenerar o documento automaticamente;
- o link `Baixar ultimo arquivo` fica oculto quando o PDF salvo nao corresponde mais ao estado atual do orcamento.

## Resultado esperado

- o PDF anexado no WhatsApp reflete a autorizacao e os dados mais recentes do orcamento;
- download e visualizacao passam a abrir o documento atualizado sem depender de geracao manual previa;
- o usuario deixa de receber um anexo antigo durante rodadas de reaprovacao.
