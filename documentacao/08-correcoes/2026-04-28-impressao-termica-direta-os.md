# 2026-04-28 - OS: impressao termica direta em Bobina 80mm

## Contexto

Foi solicitado que a opcao `Bobina 80mm` da visualizacao da OS deixasse de abrir a modal de pre-visualizacao e seguisse direto para a caixa de dialogo de impressao do sistema operacional.

## O que foi corrigido

Arquivos principais:

- `app/Controllers/Os.php`
- `app/Views/os/show.php`
- `app/Views/os/print.php`

Documentacao sincronizada:

- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-28-release-v2.16.17-impressao-termica-os.md`

Ajustes aplicados:

- o clique em `Bobina 80mm` passou a abrir diretamente o endpoint de impressao da OS;
- a controller de impressao passou a aceitar a flag `auto_print`;
- a view de impressao ganhou um modelo termico dedicado, com leitura linear e disparo automatico de `window.print()` nesse fluxo.

## Resultado esperado

- a impressao termica deixa de depender da modal de gerenciamento do `A4`;
- o operador cai direto na dialog nativa de impressao;
- o documento termico fica mais compativel com bobina `80mm`.

## Validacao local

- `php -l app/Views/os/print.php`
- `php -l app/Views/os/show.php`
- `php -l app/Controllers/Os.php`
- `php -l app/Config/SystemRelease.php`
