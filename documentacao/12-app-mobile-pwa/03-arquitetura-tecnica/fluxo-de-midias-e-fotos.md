# Fluxo de Midias e Fotos

## Regra global

Todo fluxo de foto no app deve reaproveitar o mesmo padrao de:

- escolha por camera ou galeria;
- crop antes de salvar;
- preview por miniatura;
- remocao imediata;
- atualizacao reativa sem refresh.

## Contextos atuais

- foto de perfil do equipamento;
- fotos de acessorios;
- fotos de entrada da OS.
- miniatura do equipamento no seletor rico da abertura de OS, com abertura de galeria/carrossel das fotos de perfil.

## Armazenamento

- fotos de equipamento em `public/uploads` seguindo o mesmo padrao do ERP;
- fotos de acessorios em `public/uploads/acessorios/...`;
- fotos de entrada no fluxo oficial da OS.

## Regras tecnicas

- usar `multipart/form-data` quando houver upload;
- anexar timestamp em URLs quando houver risco de cache visual;
- manter miniaturas e principal sincronizadas imediatamente;
- miniaturas clicaveis do seletor rico de equipamento devem abrir a colecao completa de fotos de perfil do aparelho;
- modal de crop deve ser seguro em qualquer viewport mobile.
