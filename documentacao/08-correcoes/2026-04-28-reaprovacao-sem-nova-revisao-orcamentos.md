# Correcao - Reaprovacao de Orcamento sem Criar Novo Registro

Data: 28/04/2026

## Problema

Quando um orcamento aprovado precisava ser alterado, o sistema criava uma nova revisao em outro registro.

Isso fragmentava:

- o historico comercial;
- a rastreabilidade das alteracoes;
- a leitura do time sobre qual proposta era a vigente;
- a sincronizacao da OS com a nova rodada de autorizacao.

## Correcao aplicada

- a revisao passou a acontecer no proprio orcamento;
- foi criado o status `reenviar_orcamento` para sinalizar nova rodada de autorizacao;
- o historico de alteracoes e status passou a ficar no mesmo timeline;
- a aprovacao revisada passou a usar label dinamica por versao;
- a OS vinculada passou a acompanhar obrigatoriamente a rodada revisada, a rejeicao e a aprovacao final.

## Resultado esperado

- o operador edita o mesmo orcamento;
- o cliente recebe a proposta revisada sem duplicacao de cadastro;
- a equipe consegue ver no mesmo registro o que mudou e quantas aprovacoes ocorreram;
- a OS reflete corretamente `aguardando_autorizacao`, `cancelado` ou `aguardando_reparo` conforme o retorno do cliente.
