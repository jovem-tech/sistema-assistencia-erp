# 2026-03 - Melhorias em OS Nova

## Objetivo
Padronizar a recepcao de OS com foco em:
- registro rapido
- evidencias visuais
- separacao clara entre dados cadastrais e dados de entrada tecnica

## Entregas implementadas
- Reorganizacao visual da aba `Dados` em blocos separados:
  - Cliente/Equipamento/Tecnico
  - Prioridade/Entrada/Previsao/Status
  - Estado fisico do equipamento
  - Acessorios e Componentes (na entrada)
- Novo bloco `Estado fisico do equipamento` com logica igual a acessorios:
  - botoes rapidos
  - criacao de item
  - editar/remover item
  - upload de fotos por item (galeria/camera + crop)
  - opcao `Sem avarias aparentes na entrada`
- Persistencia de estado fisico na OS:
  - tabela `estado_fisico_equipamento`
  - tabela `estado_fisico_fotos`
  - fotos em `public/uploads/estado_fisico/OS_<numero_os>/`
- Integracao na visualizacao da OS (`/os/visualizar/{id}`):
  - secao `Estado fisico na entrada` na aba de informacoes
  - secao `Fotos do Estado fisico` na aba de fotos
- Ajuste de limpeza em edicao:
  - itens removidos saem do banco
  - arquivos orfaos do estado fisico sao removidos da pasta da OS

## Arquivos de codigo
- `app/Controllers/Os.php`
- `app/Views/os/form.php`
- `app/Views/os/show.php`
- `app/Models/EstadoFisicoOsModel.php`
- `app/Models/FotoEstadoFisicoModel.php`
- `update_os_estado_fisico.php`

## Validacao recomendada
1. Abrir `/os/nova`.
2. Preencher dados obrigatorios.
3. Adicionar ao menos um item em `Estado fisico do equipamento`.
4. Anexar fotos por galeria e por camera.
5. Salvar OS e abrir `/os/visualizar/{id}`.
6. Confirmar texto e fotos do estado fisico nas abas de informacoes e fotos.
