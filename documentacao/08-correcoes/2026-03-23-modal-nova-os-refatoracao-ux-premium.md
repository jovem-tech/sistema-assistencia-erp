# Correcao - Refatoracao estrutural do modal Nova OS

Data: 23/03/2026

## Problema
O modal `Nova Ordem de Servico` concentrava cliente, equipamento, tecnico e execucao no mesmo bloco visual, gerando:
- baixa hierarquia
- leitura ruim
- excesso de densidade no primeiro contato com o formulario
- dificuldade extra em telas menores

Tambem havia necessidade de:
- truncar nomes longos no `Select2`
- atualizar imediatamente o contexto visual do cliente apos criacao/edicao

## Correcao aplicada
- Reorganizacao do modal em abas:
  - `Cliente`
  - `Equipamento`
  - `Defeito`
  - `Dados Operacionais`
  - `Fotos`
  - `Pecas e Orcamento` (quando edicao)
- Inclusao de card informativo do cliente com nome, telefone e endereco.
- Sincronizacao do card e do `Select2` apos salvar cliente via AJAX.
- Relato do cliente centralizado na aba `Defeito`.
- Tecnico responsavel centralizado na aba `Defeito`.
- Defeitos comuns do tipo de equipamento movidos da aba operacional para a aba `Defeito`.
- Aba operacional renomeada para `Dados Operacionais`, dedicada a prioridade, datas, garantia e status.
- Extracao do CSS especifico da OS para `public/assets/css/design-system/layouts/os-form-layout.css`.
- Atualizacao do mapa de validacao para focar a aba correta ao detectar campo pendente.

## Arquivos alterados
- `app/Views/os/form.php`
- `app/Controllers/Clientes.php`
- `public/assets/css/design-system/layouts/os-form-layout.css`
- `public/assets/css/design-system/index.css`

## Resultado
- Separacao logica entre cadastro do cliente, contexto do equipamento, diagnostico/defeito e dados operacionais.
- Melhor comportamento em modal desktop/tablet/mobile.
- `Select2` com nomes longos sem quebrar seta, largura ou alinhamento interno.
