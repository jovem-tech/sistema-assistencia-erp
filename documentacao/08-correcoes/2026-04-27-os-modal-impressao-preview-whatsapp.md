# 2026-04-27 - OS: refatoracao do modal de impressao consolidada

## Contexto

O modal de impressao consolidada da tela `/os/visualizar/{id}` estava dividindo a atencao do operador entre uma barra lateral extensa e a area do documento. Isso reduzia a leitura do preview, duplicava a selecao de formato dentro da propria modal e deixava o formulario de envio por WhatsApp competindo visualmente com a pre-visualizacao.

## O que foi corrigido

Arquivo principal:

- `app/Views/os/show.php`

Documentacao sincronizada:

- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`

Ajustes aplicados:

- remocao completa da barra lateral antiga do modal de impressao;
- remocao dos cards internos de `Folha A4` e `Bobina 80mm`, mantendo a escolha do formato exclusivamente no dropdown `Imprimir` da pagina;
- badge do formato atual e botao `Abrir em nova guia` movidos para o cabecalho da modal;
- switch `Incluir fotos no documento` e botoes de acao reposicionados no rodape da modal;
- criacao de um modal dedicado para `Enviar PDF por WhatsApp`, com o mesmo formulario ja usado no fluxo anterior;
- sincronizacao reativa do modal de WhatsApp com o formato selecionado (`A4` ou `80mm`) e com a opcao de incluir fotos;
- preservacao do preview carregado em iframe ao alternar entre a modal de impressao e a modal de WhatsApp, evitando perda de contexto do operador;
- reforco do papel da pre-visualizacao como espelho do documento final gerado por `app/Views/os/print.php`.

## Resultado esperado

- ao clicar em `Imprimir -> Folha A4` ou `Imprimir -> Bobina 80mm`, a modal deve abrir diretamente no formato escolhido;
- a area principal do modal deve mostrar apenas a pre-visualizacao do documento, sem painel lateral competindo por espaco;
- `Abrir em nova guia` deve aparecer no cabecalho;
- `Incluir fotos no documento`, `Enviar PDF por WhatsApp` e `Imprimir agora` devem aparecer no rodape;
- ao abrir o modal de WhatsApp, o PDF enviado deve respeitar exatamente o formato e a opcao de fotos que estavam ativos no preview;
- a organizacao visual exibida no iframe deve continuar alinhada com o documento realmente impresso.

## Validacao local

- `php -l app/Views/os/show.php`
