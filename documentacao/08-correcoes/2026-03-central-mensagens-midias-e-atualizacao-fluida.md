# Registro de Correcao: Central de Mensagens - Midias e Atualizacao Fluida

**Data:** 18/03/2026  
**Modulo:** Central de Mensagens (`/central-mensagens`)

## Problema
- A tela dependia de `public/assets/js/central-mensagens.js`, mas o arquivo nao existia.
- Isso deixava a experiencia inconsistente para anexos, visualizacao de imagem e atualizacao do chat.

## O que foi implementado
- Criado `public/assets/js/central-mensagens.js` com fluxo completo da inbox:
  - carregamento de conversas com filtros
  - abertura de thread
  - envio de mensagem com suporte a `anexo` (multipart)
  - sincronizacao incremental de mensagens via `GET /central-mensagens/conversa/{id}/novas?after_id=...`
- Implementado preview de anexo antes do envio (com opcao de remover).
- Implementado renderer de midias por tipo:
  - imagem (thumb + abrir no modal padrao)
  - audio (player embutido no chat com play/pause, barra e duracao)
  - video (player com controls)
  - pdf/arquivo (card com acao de abrir)
- Implementada navegacao entre imagens da thread no modal (`anterior/proxima`).
- Mantida atualizacao suave da lista e da thread, sem recarregamento brusco da interface.

## Correcao adicional: erro de MIME no EventSource (SSE)

### Sintoma observado
No navegador, o chat podia exibir repetidamente:
- `EventSource's response has a MIME type ("text/html") that is not "text/event-stream".`

### Ajuste aplicado
- Backend (`CentralMensagens::conversaStream`) passou a suportar:
  - `?probe=1` (pre-check em JSON)
  - `?handshake=1` (resposta curta em `text/event-stream` para validar cabecalhos)
- Frontend (`central-mensagens.js`) passou a:
  - validar `probe` antes de abrir stream
  - validar `handshake` e `Content-Type` antes de criar `EventSource`
  - bloquear reconexao agressiva por janela curta quando stream falha
  - manter fallback para polling incremental (`/novas?after_id=...`)

### Resultado
- eliminacao do loop de erro de MIME no console em cenarios de indisponibilidade/retorno HTML.
- atualizacao de mensagens continua funcional sem quebra da thread.

## Ajuste de responsividade adicional (altura da coluna central)

### Sintoma
- Em alguns monitores/notebooks, a coluna central do chat estava excessivamente longa, prejudicando o equilibrio visual com lista e contexto.

### Correcao aplicada
- Padronizacao do shell principal em layout unificado:
  - wrapper unico: `.central-mensagens-wrapper`
  - altura fixa em desktop/notebook: `calc(100vh - 140px)`
  - distribuicao das colunas: `28% / 44% / 28%`
  - rolagem independente por coluna
  - em telas menores (`< 992px`): empilhamento vertical (`width: 100%`)

### Efeito pratico
- painel central mais previsivel no desktop
- melhor proporcao visual entre conversas, thread e contexto
- manutencao da rolagem interna por painel sem quebra da experiencia do chat

## Ajuste de usabilidade em conversas longas + envio mais fluido

### Sintoma
- Em conversas extensas, ficar rolando ate o composer era cansativo.
- No envio, o spinner do botao ficava ativo por tempo excessivo por aguardar etapas de refresh.

### Correcao aplicada
- Adicionado botao flutuante `Ir para o fim` na thread para salto imediato ao final da conversa e foco no campo de resposta.
- O composer foi estabilizado com comportamento sticky no rodape do painel de chat.
- Envio ajustado para UX mais rapido:
  - spinner desliga imediatamente apos confirmacao de envio (`POST /enviar`);
  - refresh da thread/lista roda em background sem bloquear o botao de envio;
  - scroll automatico para o final apos envio.

### Resultado
- Menos friccao em conversas longas.
- Sensacao de resposta imediata ao enviar mensagem.
- Fluxo de atendimento mais proximo de mensageiros modernos.

## Reorganizacao estrutural da tela (wrapper unico 3 colunas)

### Solicitacao aplicada
- Remocao do bloco superior de botoes de modulos na tela principal da Central.
- Conversao para um painel unico com 3 colunas internas:
  - conversas (28%)
  - chat (44%)
  - contexto (28%)
- Altura principal fixa em desktop: `calc(100vh - 140px)`.
- Scroll individual por coluna.

### Estrutura
- Wrapper principal: `.central-mensagens-wrapper`
- Colunas:
  - `.coluna-conversas`
  - `.coluna-chat`
  - `.coluna-contexto`

### Responsividade
- Em telas menores, as 3 colunas passam para pilha vertical (`width: 100%`) mantendo legibilidade e area de toque.
- Mantida compatibilidade dos IDs/eventos JS existentes (sem quebra de logica PHP).

## Correcao de gatilho "Conversas" (offcanvas mobile)

### Sintoma
- O botao `Conversas` no header do chat aparecia em desktop e passava a percepcao de "nao funciona".
- Em desktop, a coluna de conversas ja fica fixa (nao offcanvas), entao o clique nao deve ser usado.

### Correcao aplicada
- Ajustado CSS para comportamento correto por breakpoint:
  - estado padrao: `cm-mobile-list-trigger` e `cm-mobile-context-trigger` ocultos;
  - em `< 992px`: os botoes sao exibidos como gatilho de offcanvas.

### Resultado
- Em desktop/notebook, os gatilhos mobile nao aparecem (evita confusao).
- Em tablet/mobile, o botao `Conversas` abre corretamente `#cmConversasCanvas` e o botao de contexto abre `#cmContextoCanvas`.

## Persistencia de arquivos
- O backend da Central ja estava preparado e foi mantido como padrao oficial:
  - `public/uploads/central_mensagens/{telefone}/foto`
  - `public/uploads/central_mensagens/{telefone}/video`
  - `public/uploads/central_mensagens/{telefone}/audio`
  - `public/uploads/central_mensagens/{telefone}/pdf`
  - `public/uploads/central_mensagens/{telefone}/arquivo`

## Arquivos alterados
- `public/assets/js/central-mensagens.js` (novo)
- `app/Views/central_mensagens/index.php` (refatoracao responsiva + cache-bust por `filemtime`)
- `app/Controllers/CentralMensagens.php` (SSE `probe` + `handshake` + headers de stream)
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/05-api/rotas.md`
