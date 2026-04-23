# 2026-03 - Correcao de tela travada no upload de fotos do estado fisico

## Problema
Na tela `/os/nova`, ao adicionar foto no bloco **Estado fisico do equipamento**, em alguns cenarios o overlay de modal (`backdrop`) permanecia ativo e a interface ficava travada.

## Causa tecnica
Fluxos encadeados de camera/crop/preview podiam deixar artefatos de modal no DOM (backdrop orfao e `body.modal-open`), especialmente em transicoes rapidas ou cancelamentos.

## Correcao aplicada
Arquivo alterado:
- `app/Views/os/form.php`

Ajustes:
- Criada rotina defensiva `cleanupStuckModalArtifacts()` para remover backdrops orfaos e restaurar estado do `body` quando nao ha modal aberto.
- Criado helper `hideModalSafe()` para:
  - desfocar elemento ativo dentro do modal antes de fechar
  - fechar modal com limpeza agendada
- Aplicado `hideModalSafe()` nos fechamentos do modal de crop em todos os fluxos:
  - acessorios
  - estado fisico
  - fotos de entrada da OS
  - fotos do equipamento (modal interno da OS)
- Adicionada limpeza no `hidden.bs.modal` de camera/crop.
- Adicionado helper `closeImageModalIfOpen()` para fechar o lightbox (`#imageModal`) antes de abrir camera/crop ou seletor de fotos.
- Blindado o `show.bs.modal` do `#imageModal` para cancelar abertura sem `data-img-src` valido (evita modal preto vazio).
- Adicionada limpeza adicional no `hidden.bs.modal` do `#imageModal`.
- Reforcado `closeImageModalIfOpen()` para fechamento forcado do modal/backdrop (sem depender de transicao), removendo lock de `body.modal-open` quando necessario.
- Inicializacao do `#imageModal` tornou-se idempotente: se o elemento ja existir sem conteudo interno, o HTML completo do lightbox e reconstruido automaticamente antes de registrar eventos.
- Adicionado fallback para indisponibilidade do Cropper: se `window.Cropper` nao estiver carregado, a foto e adicionada sem corte (evitando travamento da tela).
- Novos `console.error` foram incluídos nos fluxos críticos (`imageModal`, fallback de corte, `closeImageModalIfOpen`) para facilitar diagnóstico via console do navegador.

## Resultado esperado
- Nao ocorre mais tela escurecida travada ao adicionar/cortar fotos no estado fisico.
- Fluxo de foto permanece consistente com o padrao do sistema (camera/galeria + crop + preview).

## Atualizacao complementar
- O fluxo de abertura do editor de corte na `/os/nova` foi alinhado ao fluxo estavel de `/equipamentos/novo`.
- O modal de corte agora abre imediatamente e o Cropper e inicializado apenas quando o modal estiver visivel e a imagem estiver pronta.
- O fallback sem corte continua existindo, mas passa a atuar somente como contingencia quando o modal realmente falhar.
- O modal da camera da `/os/nova` passou a reinicializar a instancia do Bootstrap antes de cada abertura, limpando estados presos que deixavam apenas o backdrop visivel.
- Foram adicionados logs especificos para falha de abertura da camera e para indisponibilidade de `navigator.mediaDevices.getUserMedia`.
- Os modais de camera e crop da `/os/nova` agora sao anexados diretamente ao `document.body`, evitando clipping e conflitos de `z-index` com cards, abas e wrappers internos da tela.
