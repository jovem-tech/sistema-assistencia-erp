# Correcao - responsividade da listagem Financeiro e botoes de acao

Data: 10/06/2026
Versao: 2.23.29

## Problema

Na pagina `Financeiro`, os botoes de acao da listagem podiam ficar cortados na lateral em larguras intermediarias, o cabecalho podia parecer apertado demais para comportar todos os atalhos com conforto visual e a coluna `Descricao` ainda podia ser comprimida a ponto de deixar o card clicavel com o titulo quase vertical.

## Ajuste aplicado

- o cabecalho da pagina passou a flexionar melhor, mantendo `Ajuda`, `Cartoes e taxas`, `DRE`, `Fluxo de Caixa`, `Configuracoes` e o novo atalho `+ Novo lancamento` visiveis no mesmo bloco;
- o atalho de criacao foi compactado para preservar espaco em telas menores, sem perder a funcao principal;
- a coluna `Acoes` da tabela financeira passou a quebrar os botoes em mais de uma linha quando necessario, evitando que `Visualizar`, `Registrar baixa`, `Editar`, `Encerrar` e `Excluir` sejam ocultados;
- a tabela ganhou uma distribuicao fixa de larguras por coluna no desktop, reservando espaco real para `Descricao`, `Classificacao` e `Acoes` sem esmagar o restante do grid;
- o card interno da descricao deixou de usar um `min-width` agressivo e passou a aceitar quebra controlada no cabecalho, preservando o titulo e o chip `Ver detalhes` alinhados;
- a inicializacao da DataTable passou a receber `columns.adjust()` apos carga, fontes e resize, reduzindo regressao visual em notebooks e janelas redimensionadas.

## Resultado operacional

- a equipe consegue acessar as acoes da listagem sem precisar cacar botao cortado na borda da tela;
- a descricao volta a ficar horizontal e legivel mesmo quando o lancamento traz `cliente`, `fornecedor`, `equipamento` e resumo tecnico no mesmo card;
- o painel continua legivel em desktop, notebook e mobile;
- o atalho `+` traz uma leitura visual parecida com a usada na OS, sem quebrar o restante do layout.

## Arquivos impactados

- `app/Views/financeiro/index.php`
- `documentacao/01-manual-do-usuario/financeiro.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/08-correcoes/2026-06-10-correcao-financeiro-responsividade-botoes-acao.md`
