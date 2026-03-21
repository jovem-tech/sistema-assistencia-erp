# Correcao: Edicao de Equipamento Inline na Abertura da OS

## Contexto
Na abertura da OS (`/os/nova`), quando um equipamento ja cadastrado precisava de ajuste (serie, cor, estado fisico, acessorios ou foto), o tecnico precisava sair da tela para editar em outro modulo.

## Correcao aplicada
- Adicionado botao `Editar` ao lado do campo `Equipamento` na aba Dados da OS.
- O mesmo modal usado para `+ Novo` agora suporta dois modos:
  - cadastro de novo equipamento
  - edicao do equipamento selecionado
- Na edicao, o modal abre com dados preenchidos do equipamento atual e permite salvar sem sair da abertura da OS.
- Na aba de fotos do modal de edicao, todas as fotos ja cadastradas do equipamento sao exibidas em miniaturas (com destaque da principal) para facilitar revisao.
- A adicao de fotos no modal passou para fluxo multiplo (`fotos[]`) com o mesmo padrao de crop:
  - botao Galeria e Camera
  - corte por imagem antes de anexar
  - fila sequencial quando varias imagens sao selecionadas
  - miniaturas novas exibidas imediatamente com opcao de remover antes de salvar
- Fotos ja cadastradas no equipamento passaram a ter acao de exclusao no proprio modal (com confirmacao).
- Regra de limite no modal: ate 4 fotos por equipamento (soma de fotos existentes + novas).
- Implementada rota AJAX de atualizacao:
  - `POST /equipamentos/atualizar-ajax/{id}`
- Apos salvar, o select de equipamentos e o card lateral (foto/cor/info) sao atualizados imediatamente.

## Ajuste tecnico no backend
- `storeAjax()` e `updateAjax()` passaram a aceitar uploads multiplos no campo `fotos[]` (com fallback legado para `foto_perfil`).
- `updateAjax()` valida a disponibilidade de vagas e impede novas insercoes quando o equipamento ja atingiu 4 fotos, retornando aviso no fluxo AJAX.
- `deleteFoto()` garante consistencia da foto principal apos exclusao (quando necessario, promove outra foto para principal).
- Criado helper interno para normalizar a leitura dos arquivos enviados no modal.

## Arquivos impactados
- `app/Views/os/form.php`
- `app/Controllers/Equipamentos.php`
- `app/Config/Routes.php`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/05-api/rotas.md`
