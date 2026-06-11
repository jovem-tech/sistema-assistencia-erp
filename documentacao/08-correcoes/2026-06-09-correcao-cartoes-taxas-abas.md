# Correcao - Cartoes e taxas organizados em abas

## Contexto

A tela `Financas -> Cartoes e taxas` concentrava quatro cadastros operacionais e o simulador em uma unica sequencia vertical, o que aumentava a rolagem e misturava contextos diferentes na mesma leitura.

## O que mudou

- a tela passou a usar quatro abas visiveis:
  - `Operadora de maquininha`
  - `Bandeiras`
  - `Taxa por parcela`
  - `Simulador de faturamento liquido`
- o bloco `Taxas cadastradas` foi incorporado dentro da aba `Taxa por parcela`, em um painel unico com o formulario acima e a listagem abaixo;
- a navegacao de abas ficou horizontalmente rolavel para preservar usabilidade em telas pequenas;
- a aba `Taxa por parcela` agora distribui formulario e tabela lado a lado no desktop e em pilha no mobile;
- a documentacao do modulo financeiro foi atualizada para refletir a nova organizacao visual.

## Impacto

- menos rolagem vertical na entrada da tela;
- separacao mais clara entre cadastro, consulta e simulacao;
- melhor leitura em desktop e mobile;
- menor chance de o operador editar o bloco errado por proximidade visual.

## Arquivos alterados

- `app/Views/financeiro/cartoes.php`
- `documentacao/01-manual-do-usuario/financeiro.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `app/Config/SystemRelease.php`
