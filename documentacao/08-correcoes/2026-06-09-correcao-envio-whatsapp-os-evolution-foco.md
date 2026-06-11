# Correcao - Envio WhatsApp da OS com Evolution API e foco de modal

Data: 09/06/2026
Versao: 2.23.20

## Contexto

Na tela `/os/visualizar/{id}`, o modal `Enviar PDF por WhatsApp` podia exibir `Falha no envio: Bad Request` quando o provider direto era a Evolution API. O console tambem registrava `Failed to load resource 422` e aviso de acessibilidade porque o SweetAlert2 abria enquanto um botao dentro de `.app-wrapper[aria-hidden="true"]` mantinha foco.

## Ajustes aplicados

- `EvolutionApiProvider` passou a adicionar `55` em telefones brasileiros sem DDI antes de chamar a Evolution API;
- erros da Evolution priorizam detalhes internos como `response.message`; quando vier apenas `Bad Request`, a mensagem e convertida em orientacao operacional sobre DDI, instancia e arquivo;
- `Os::sendWhatsApp()` passou a responder falhas esperadas do provider com HTTP `200` e `ok:false`, mantendo `422` para validacoes de formulario;
- `DSFeedback.fire` passou a desfocar o elemento ativo antes de abrir SweetAlert2 e restaurar foco seguro apos o fechamento;
- `app/Views/os/show.php` passou a usar o wrapper global de alerta no modal de envio de PDF por WhatsApp.

## Validacao esperada

- Telefones como `22999999999` devem ser enviados para a Evolution como `5522999999999`;
- falha operacional da Evolution deve aparecer no SweetAlert2 sem gerar `Failed to load resource 422`;
- o console nao deve registrar `Blocked aria-hidden` ao abrir o alerta sobre o modal da OS.
