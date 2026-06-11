# Correcao - status de envio no modal de orcamento

Data: 08/06/2026
Versao: 2.23.17

## Problema

Na aba `Envio do orcamento`, dentro do modal de visualizacao aberto pela OS, o operador via o `Status comercial`, mas nao tinha uma leitura imediata do resultado do envio por WhatsApp ou e-mail.

Mesmo quando a tentativa era registrada em `orcamento_envios`, era necessario consultar a area de rastreabilidade em outra aba/trecho da tela para saber se o envio foi concluido, duplicado ou se falhou.

## Ajuste realizado

- Adicionado o bloco `Status do envio` em `app/Views/orcamentos/show.php`.
- O bloco exibe o ultimo envio comercial registrado, considerando os canais `whatsapp` e `email`.
- Para cada canal, a tela mostra status, data, destino e detalhe do erro quando existir.
- Estados exibidos: `Sem tentativa`, `Pendente`, `Enviado`, `Duplicado evitado` e `Erro no envio`.
- O layout recebeu responsividade dedicada para `<= 430px`, `<= 390px`, `<= 360px` e `<= 320px`.

## Impacto operacional

O atendente consegue confirmar no proprio modal se o orcamento saiu ao cliente ou se houve falha tecnica, sem trocar de contexto e sem depender de consulta manual ao banco.

## Arquivos alterados

- `app/Views/orcamentos/show.php`
- `app/Config/SystemRelease.php`
- `documentacao/01-manual-do-usuario/orcamentos.md`
- `documentacao/03-arquitetura-tecnica/modulo-orcamentos.md`
- `documentacao/06-modulos-do-sistema/orcamentos.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
