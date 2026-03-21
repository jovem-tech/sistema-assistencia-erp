# Correção: Organização de Fotos de Perfil por Equipamento (2026-03)

## Problema

As fotos de perfil de equipamentos eram acumuladas diretamente em:

`public/uploads/equipamentos_perfil/`

Com muitos cadastros, a pasta ficava desorganizada e difícil de manter.

## O que foi corrigido

- Implementada pasta dedicada por equipamento.
- Novo padrão de pasta:
  - `{modelo-slug}-{cliente_slug}`
  - Exemplo: `iphone-11-davi_araujo_de_oliveira_rosa`
- Cada foto passa a usar nome incremental:
  - `perfil_1.jpg`, `perfil_2.png`, `perfil_3.webp`, etc.
- Limite mantido: máximo de 4 fotos por equipamento.
- Compatibilidade preservada para fluxo legado (`foto_perfil`).

## Cobertura dos fluxos

- Cadastro de equipamento (tela completa).
- Edição de equipamento (tela completa).
- Cadastro/edição inline no modal da OS.
- Exclusão de foto (inclui limpeza de pasta vazia).
- Exclusão de equipamento (remove arquivos físicos antes do delete).

## Migração de registros antigos

- Ao editar ou carregar fotos de um equipamento, o sistema normaliza automaticamente:
  - caminhos antigos para o novo padrão,
  - arquivos legados em `uploads/equipamentos/` para `uploads/equipamentos_perfil/{pasta_do_equipamento}/`.

## Observações técnicas

- `equipamentos_fotos.arquivo` agora deve ser tratado como **caminho relativo** (não apenas nome de arquivo).
- `GET /equipamentos/fotos/{id}` continua retornando `url` pronta para renderização.
