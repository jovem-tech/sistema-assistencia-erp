# Correcao - status reenviar preservado apos disparo

Data: 29/04/2026

## Problema

Mesmo depois de o orcamento revisado ser enviado por WhatsApp ou e-mail, o status podia continuar exibindo `Reenviar orcamento`.

Isso confundia a operacao porque o estado nao refletia mais a fase real do fluxo:

- o documento ja tinha sido enviado;
- o cliente ja estava com o link/PDF em maos;
- mas a tela ainda parecia indicar que o envio nao tinha acontecido.

## Ajuste aplicado

- o pos-envio agora inclui `STATUS_REENVIAR` na migracao automatica para `STATUS_AGUARDANDO`;
- o fluxo de transicao do modulo passou a aceitar esse mesmo avancar manualmente quando necessario;
- a label visual desse estado foi alinhada para `Aguardando aprovacao`.

## Resultado

Depois do reenvio real, o orcamento passa a mostrar corretamente que aguarda aprovacao do cliente, em vez de continuar preso na etapa preparatoria de reenvio.
