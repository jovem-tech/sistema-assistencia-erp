# Correcao - Filtros por operadora em Taxas cadastradas

## Contexto

A lista `Taxas cadastradas` dentro da aba `Taxa por parcela` precisava de uma forma rapida de localizar as taxas de cada operadora sem sair do painel atual.

## O que mudou

- foram adicionados botões de filtro por operadora acima da tabela `Taxas cadastradas`;
- o filtro alterna as linhas da tabela imediatamente no navegador, sem recarregar a pagina;
- a opcao `Todas` restaura a visualizacao completa da listagem;
- quando o filtro selecionado nao encontra registros, a tabela exibe uma mensagem de retorno amigavel;
- a documentacao do modulo financeiro foi atualizada para refletir o novo comportamento.

## Impacto

- leitura mais rapida das regras por operadora;
- menos rolagem e menos procura manual na listagem;
- manutencao do contexto atual da aba `Taxa por parcela`, sem abrir novas telas.

## Arquivos alterados

- `app/Views/financeiro/cartoes.php`
- `documentacao/01-manual-do-usuario/financeiro.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `app/Config/SystemRelease.php`
