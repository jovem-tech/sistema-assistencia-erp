# Correcao: obrigatoriedade de cor e foto no modal de equipamento da OS

Data: 2026-03-24
Modulo: Ordens de Servico / Equipamentos
Arquivos principais:
- `app/Views/os/form.php`
- `app/Controllers/Equipamentos.php`

## Problema
- O modal `Cadastrar Novo Equipamento` dentro da Nova OS permitia salvar equipamento sem registrar cor e sem anexar foto.
- Isso abria margem para cadastros incompletos e dificultava a identificacao visual do aparelho na recepcao.

## Correcao aplicada
- A validacao do modal rapido agora exige:
  - cor preenchida
  - ao menos uma foto do equipamento
- O frontend bloqueia o envio antes do `fetch`, abre automaticamente a aba pendente (`Cor` ou `Foto`) e posiciona o foco no campo visivel correspondente.
- O backend AJAX em `Equipamentos::storeAjax()` e `Equipamentos::updateAjax()` passou a validar o mesmo requisito para impedir bypass por requisicao manual.
- Em erro de validacao, a resposta JSON retorna `focus_tab` para orientar a interface.

## Resultado esperado
- Nao e mais possivel concluir o cadastro rapido de equipamento na OS sem cor e sem foto.
- O modal nao fecha em caso de pendencia.
- O tecnico e guiado diretamente para a etapa que falta concluir.
