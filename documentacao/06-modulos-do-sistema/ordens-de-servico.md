# Módulo: Ordens de Serviço

## Objetivo

O módulo de `Ordens de Serviço` controla o ciclo completo do atendimento técnico:

- recepção;
- diagnóstico;
- orçamento;
- execução;
- qualidade;
- encerramento.

## Núcleo técnico

### Camada principal

- controller: `app/Controllers/Os.php`
- model: `app/Models/OsModel.php`
- service de fluxo: `app/Services/OsStatusFlowService.php`
- service de PDF: `app/Services/OsPdfService.php`

### Estado operacional

Base conceitual:

- `os_status`
- `os_status_transicoes`
- `os_status_historico`

Campo operacional:

- `os.estado_fluxo`

Estados esperados:

- `em_atendimento`
- `em_execucao`
- `pausado`
- `pronto`
- `encerrado`
- `cancelado`

## Listagem `/os`

### Responsabilidades

- busca global;
- filtros avançados;
- paginação server-side;
- leitura combinada de OS, cliente, equipamento, prazo e orçamento.

### Comportamento atual da coluna de status

A listagem passou a consolidar o contexto da OS com o orçamento mais recente vinculado.

Hoje a célula pode mostrar:

- status principal da OS;
- estado de fluxo;
- status do orçamento;
- número do orçamento.

### Sincronização com orçamento

Regra técnica consolidada:

- orçamento em ciclo comercial ativo vinculado à OS:
  - OS sugerida/atualizada para `aguardando_autorizacao`;
- orçamento `aprovado` ou `convertido`:
  - OS sugerida/atualizada para `aguardando_reparo`.

Regra complementar obrigatoria:

- essa sincronizacao automatica nao pode rebaixar uma OS que ja avancou manualmente para etapas posteriores do reparo, como `reparo_execucao`, `aguardando_peca`, `testes_operacionais`, `testes_finais`, `reparo_concluido` e similares;
- o orcamento continua definindo o ponto de entrada do fluxo tecnico, mas nao sobrescreve fases mais avancadas ja confirmadas pela equipe.
- na DataTable `/os`, o status principal renderizado deve sempre refletir `os.status` e `os.estado_fluxo` reais; o status do orcamento permanece apenas como badge auxiliar e nao pode substituir visualmente a OS.

Também foi aplicado fallback de valor:

- se `os.valor_final` estiver vazio, a listagem pode usar o total do orçamento vinculado mais recente.

## Modal `Nova OS` na listagem

### Estrutura

- view: `app/Views/os/index.php`
- abertura por `iframe` dentro do modal `#osCreateModal`

### Regra operacional atual

O modal foi protegido para evitar perda acidental de preenchimento:

- `data-bs-backdrop="static"`
- `data-bs-keyboard="false"`
- fechamento manual apenas pelo botão `X`
- confirmação obrigatória ao fechar

Mensagem exibida:

- existe um registro de ordem de serviço em andamento;
- o preenchimento não salvo será perdido.

## Formulário de OS

### View principal

- `app/Views/os/form.php`

### Áreas centrais

- `Cliente`
- `Equipamento`
- `Defeito`
- `Dados Operacionais`
- `Fotos`
- `Solução` (na edição)

### Seleção rica de equipamento

O seletor de equipamento passou a operar com contexto expandido:

- foto de perfil;
- tipo;
- marca;
- modelo;
- cor;
- número de série/IMEI.

### Regra de anti-cache para fotos da OS

Na view `app/Views/os/form.php`, o helper frontend `withFotoVersion()` é usado para anexar `?v=timestamp` somente em URLs reais de foto.

Regra técnica consolidada:

- caminhos HTTP/relativos continuam recebendo versionamento para evitar cache visual;
- fallbacks inline em `data:` devem ser preservados sem alteração;
- previews temporários em `blob:` também devem ser preservados sem query string adicional.

## Visualização da OS

### View principal

- `app/Views/os/show.php`

### Estrutura atual

- coluna lateral:
  - `Fotos do Equipamento`
  - `Histórico e Progresso`
- coluna principal:
  - resumo operacional superior;
  - abas centrais;
  - demais blocos operacionais da ordem.

### Abas principais

- `Informações`
- `Orçamento`
- `Diagnóstico`
- `Fotos`
- `Valores`

### Regras importantes

- a aba `Informações` exibe status atual da OS e do orçamento, mas não altera status;
- a aba `Orçamento` centraliza o vínculo comercial da OS;
- a aba `Fotos` consolida fotos de perfil, equipamento, entrada, acessórios e checklist;
- a aba `Valores` detalha financeiro da OS e do orçamento vinculado.

## Integração com o módulo de Orçamentos

### Regra do botão superior

Na visualização da OS:

- sem orçamento vinculado: exibe `Gerar orçamento`;
- com orçamento vinculado: deixa de criar novo orçamento e passa a abrir o orçamento existente.

### Regra de sincronização de status

A sincronização foi distribuída entre:

- `app/Controllers/Orcamentos.php`
- `app/Controllers/Orcamento.php`
- `app/Controllers/Os.php`

Mapeamento atual:

- `rascunho`, `pendente_envio`, `enviado`, `aguardando_resposta`, `aguardando_pacote`, `pacote_aprovado`, `pendente`
  - OS -> `aguardando_autorizacao`
- `aprovado`, `convertido`
  - OS -> `aguardando_reparo`

Protecao adicional da release `2.15.3`:

- `app/Controllers/Os.php`
- `app/Controllers/Orcamentos.php`
- `app/Controllers/Orcamento.php`
- `app/Services/OsStatusFlowService.php`

Esses pontos passaram a comparar a ordem do fluxo antes de sincronizar a OS com o status do orçamento. Se a OS ja estiver em uma etapa posterior ao alvo sugerido pelo orçamento, o sistema preserva o status manual da oficina.

## PDFs e mensageria

### PDF

Persistência:

- `os_documentos`
- `public/uploads/os_documentos/OS_<numero_os>/`

Tipos usuais:

- abertura;
- orçamento;
- laudo;
- entrega;
- devolução sem reparo.

### WhatsApp

Camada principal:

- `WhatsAppService`
- `MensageriaService`

## Arquivos de referência

- `app/Controllers/Os.php`
- `app/Models/OsModel.php`
- `app/Services/OsStatusFlowService.php`
- `app/Services/OsPdfService.php`
- `app/Views/os/index.php`
- `app/Views/os/form.php`
- `app/Views/os/show.php`
