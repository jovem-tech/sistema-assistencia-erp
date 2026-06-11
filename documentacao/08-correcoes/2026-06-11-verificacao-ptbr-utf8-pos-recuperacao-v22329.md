# Verificacao global de pt-BR e UTF-8 apos recuperacao da v2.23.29

**Data:** 11/06/2026  
**Escopo:** sistema ERP, scripts frontend e documentacao  
**Tipo:** manutencao corretiva de linguagem/encoding

## Contexto

Depois da recuperacao completa da versao `2.23.29`, foram encontrados dois padroes diferentes de corrupcao textual:

1. arquivos salvos em `Windows-1252`/ANSI, lidos como se fossem `UTF-8`;
2. arquivos em `UTF-8` com trechos que ja estavam gravados como mojibake por dupla codificacao.

Isso afetava labels, mensagens do sistema, comentarios tecnicos, paginas de documentacao e scripts de normalizacao do frontend.

## Acao aplicada

- foi criado o utilitario `scripts/maintenance/fix-ptbr-utf8.js`;
- a rotina passou a auditar arquivos rastreados pelo Git e escolher, por linha, a melhor reconstrucao entre leitura `UTF-8`, leitura `Windows-1252` e passes adicionais de reparo;
- os arquivos com texto ja perdido em forma de `?` receberam pos-ajustes manuais controlados;
- os normalizadores de frontend em `public/assets/js/central-mensagens.js` e `public/assets/js/os-list-filters.js` foram simplificados para um reparo mais robusto de mojibake em tempo de execucao.

## Areas revisadas

- `app/Controllers/`
- `app/Views/`
- `app/Helpers/`
- `app/Services/`
- `app/Database/Migrations/`
- `public/assets/js/`
- `documentacao/`
- `database.sql`

## Resultado

- textos operacionais voltaram a exibir acentos pt-BR corretamente;
- labels e mensagens de OS, configuracoes e listagens ficaram alinhados com UTF-8 canônico;
- a documentacao tecnica e de usuario voltou a refletir os termos corretos em portugues;
- o projeto agora possui uma rotina reaproveitavel para novas auditorias de encoding.
