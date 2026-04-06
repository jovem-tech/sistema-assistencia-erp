# Politica Oficial de Versoes do App

## Padrao

O app mobile/PWA segue `SemVer` em linha propria:

- `MAJOR`: quebra relevante
- `MINOR`: nova funcionalidade compativel
- `PATCH`: correcao ou ajuste sem quebra

## Separacao obrigatoria

- ERP web possui versao propria
- app mobile possui versao propria
- toda versao do app declara ERP minimo compativel

## Estado oficial atual

- versao do app: `0.4.5`
- ERP minimo compativel: `2.11.5`

## Fontes oficiais da versao do app

- `mobile-app/package.json`
- arquivo runtime de versao do app
- este documento
- historico de versoes do app

## Regra de release

Nenhuma release do app pode ser concluida sem:

1. numero de versao definido;
2. compatibilidade minima com o ERP definida;
3. changelog atualizado;
4. versao explicita dentro do app.
