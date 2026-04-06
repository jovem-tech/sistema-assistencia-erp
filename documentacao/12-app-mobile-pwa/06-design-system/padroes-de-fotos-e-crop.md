# Padroes de Fotos e Crop

Atualizado em 06/04/2026.

## Fluxo oficial

1. usuario escolhe camera ou galeria;
2. imagem abre no crop;
3. usuario confirma ou cancela;
4. miniatura aparece imediatamente;
5. toque na miniatura abre preview;
6. exclusao remove imediatamente da interface.

## Aplicacoes atuais

- foto de perfil do equipamento;
- fotos de acessorios;
- fotos de entrada da OS.

## Regras tecnicas

- mesmo comportamento visual em todos os fluxos;
- crop seguro em modal mobile;
- limite de quantidade documentado por contexto;
- preview em modal leve;
- usar o mesmo estilo de botoes do app.
- inicializacao do cropper deve ser sincrona no bundle principal (evitar dependencia de chunk dinamico para abrir o modal de corte);
- em falha de inicializacao, o estado do crop deve ser totalmente limpo (`arquivo`, `source`, `open`, `busy`) para impedir travamento da fila de fotos;
- validacao de imagem deve aceitar MIME `image/*` e fallback por extensao quando o dispositivo nao informar MIME.

## Armazenamento

- fotos devem seguir o padrao do ERP em `public/uploads`;
- acessorios devem usar pasta correta do fluxo de acessorios;
- alterar principal ou excluir deve refletir imediatamente.
