# Correcao - diagnostico WhatsApp sem ruido de console e foco preso

Data: 09/06/2026
Versao: 2.23.19

## Problemas

Na tela `Configuracoes -> Integracoes WhatsApp`, falhas esperadas de diagnostico do gateway podiam aparecer no console como:

- `configuracoes/whatsapp/testar-conexao` com HTTP `422`;
- aviso de acessibilidade `Blocked aria-hidden` quando um SweetAlert2 era aberto por cima do modal `Gerenciar Gateway` enquanto o foco ainda estava no botao interno do modal.

## Ajuste realizado

- `Configuracoes::testWhatsAppConnection()` passou a retornar HTTP `200` com `ok:false` para falhas operacionais do provider/gateway.
- `Configuracoes::sendWhatsAppTestMessage()` passou a usar o mesmo padrao para falhas de provider, preservando `422` apenas para telefone vazio.
- `Configuracoes::whatsappInboundSelfCheck()` passou a devolver o diagnostico detalhado com HTTP `200` quando o self-check encontra pendencias.
- O helper local `fireSwal()` da tela de configuracoes remove o foco do elemento ativo dentro do modal antes de abrir SweetAlert2 e restaura foco seguro no modal depois do alerta.

## Impacto operacional

O operador continua recebendo alertas claros de falha no WhatsApp, mas o console deixa de tratar falhas esperadas de diagnostico como erro HTTP. O modal do gateway tambem deixa de gerar alerta de foco oculto por `aria-hidden`.

## Arquivos alterados

- `app/Controllers/Configuracoes.php`
- `app/Views/configuracoes/index.php`
- `app/Config/SystemRelease.php`
- `documentacao/02-manual-administrador/configuracao-do-sistema.md`
- `documentacao/06-modulos-do-sistema/whatsapp.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
