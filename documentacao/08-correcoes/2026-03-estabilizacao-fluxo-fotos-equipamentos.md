# Correção: Estabilização do Fluxo de Fotos de Equipamentos (2026-03)

## Problema

O fluxo antigo de fotos da tela de equipamentos ainda usava uma implementação isolada, sem a mesma proteção aplicada na OS.

Isso causava falhas como:

- travamento da tela com backdrop preto;
- dependência rígida do `Cropper` para inserir fotos;
- processamento incompleto de múltiplas imagens da galeria;
- exclusão com mensagens nativas e pouca rastreabilidade no console.

## O que foi corrigido

- O fluxo de fotos em `app/Views/equipamentos/form.php` passou a seguir o padrão do sistema:
  - galeria;
  - câmera;
  - preview;
  - remoção;
  - corte quando disponível;
  - fallback seguro quando o editor não estiver carregado.
- Foram adicionadas rotinas de limpeza de modais órfãos e `backdrop` preso.
- O sistema agora processa múltiplas imagens da galeria em fila, respeitando o limite de 4 fotos.
- Erros do fluxo passaram a registrar mensagens mais explícitas no console com o prefixo:
  - `[Equipamentos Fotos]`
- Exclusão de foto existente foi ajustada para usar `Swal.fire` quando disponível, com fallback técnico apenas se necessário.

## Impacto funcional

- Cadastro de equipamento;
- edição de equipamento;
- fluxo de foto usado antes de vincular o equipamento à OS;
- consistência com as regras do `AGENTS.md`.

## Observações técnicas

- O sistema continua preferindo o editor de corte visual.
- Se `Cropper` não estiver carregado, a imagem ainda pode ser inserida sem bloquear a operação.
- O frontend mantém sincronização do `input[file]` real com a fila interna de fotos antes do salvamento.
