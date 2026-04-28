# 2026-04-27 - OS: aproximacao do PDF ao layout da impressao

## Contexto

O documento consolidado da OS estava visualmente consistente na impressao do navegador, mas o PDF gerado para envio por WhatsApp ainda apresentava mudancas marcantes na distribuicao das secoes e das paginas.

## O que foi corrigido

Arquivo principal:

- `app/Views/os/print.php`

Documentacao sincronizada:

- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-27-release-v2.16.9-fidelidade-pdf-os.md`

Ajustes aplicados:

- o modo `render-mode-pdf` passou a remover paddings e bordas extras do wrapper do documento;
- a folha do PDF passou a usar uma area util mais proxima da folha impressa pelo navegador;
- secoes grandes deixaram de forcar permanencia integral na mesma pagina do PDF;
- linhas de tabela, cards, blocos de checklist, galerias e agrupamentos internos continuam protegidos contra quebra visual brusca;
- o objetivo do ajuste e manter no anexo do WhatsApp a mesma hierarquia de secoes percebida na impressao.

## Resultado esperado

- o PDF anexado no WhatsApp deve se aproximar da mesma organizacao de paginas da impressao `A4`;
- o deslocamento de blocos entre paginas deve ser reduzido;
- a leitura do documento pelo cliente deve ficar mais coerente com o preview operacional validado pela equipe.

## Validacao local

- `php -l app/Views/os/print.php`
- `php -l app/Config/SystemRelease.php`
