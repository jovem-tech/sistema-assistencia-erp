# Manual do Usuário - Ordens de Serviço

## Visão geral

A Ordem de Serviço (OS) é o registro central do atendimento técnico, desde a entrada do equipamento até a entrega, cancelamento ou devolução.

O fluxo operacional atual cobre:

- recepção;
- diagnóstico;
- orçamento;
- execução;
- qualidade;
- encerramento.

## Onde acessar

- listagem principal: `Ordens de Serviço`
- nova abertura pela listagem: botão `+ Nova OS`
- edição: `/os/editar/{id}`
- visualização: `/os/visualizar/{id}`

## Identificador da OS

O número segue o padrão `OSYYMMSSSS`:

- `YY`: ano;
- `MM`: mês;
- `SSSS`: sequência do mês.

Exemplo: `OS26040010`.

## Listagem de OS (`/os`)

### O que a listagem mostra

- foto do equipamento;
- número da OS;
- cliente;
- equipamento;
- datas principais;
- status operacional;
- valor total;
- ações de visualizar e editar.

### Status exibido na listagem

A coluna de status agora concentra o contexto operacional completo da OS:

- status atual da OS;
- estado de fluxo da OS;
- status do orçamento vinculado, quando existir;
- número do orçamento vinculado.

Regras práticas:

- orçamento criado/vinculado e ainda em andamento: a OS tende a aparecer em `Aguardando Autorização`;
- orçamento `Aprovado` ou `Convertido`: a OS passa para `Aguardando Reparo`;
- depois que a equipe avança a OS para etapas como `Em Execução do Serviço`, `Aguardando Peça`, `Testes` ou fases posteriores, a listagem não deve mais voltar o status automaticamente para `Aguardando Reparo` só porque o orçamento continua aprovado;
- quando a OS não tiver `valor_final` preenchido, a listagem pode usar o total do orçamento vinculado como fallback visual.

Na prática:

- o orçamento continua sugerindo o ponto de entrada do reparo;
- a condução manual do reparo passa a prevalecer depois que a OS sai da fase inicial de execução.

### Filtros

A tela suporta:

- busca global;
- filtro por status detalhado;
- filtros avançados por contexto operacional.

### Fechamento da nova OS pela listagem

Ao clicar em `+ Nova OS`, a abertura é feita em modal.

Comportamento atual:

- o modal não fecha clicando fora;
- o modal não fecha pela tecla `ESC`;
- o fechamento manual fica restrito ao botão `X`;
- ao clicar no `X`, o sistema alerta que existe um registro de ordem de serviço em andamento e que o preenchimento não salvo será perdido.

## Abertura de nova OS

### Estrutura do formulário

O cadastro é organizado por abas:

- `Cliente`
- `Equipamento`
- `Defeito`
- `Dados Operacionais`
- `Fotos`

Na edição, o fluxo inclui também a etapa `Solução`.

### Campos centrais

| Campo | Obrigatório | Observação |
|---|---|---|
| Cliente | Sim | pode ser selecionado e editado pelo fluxo rápido |
| Equipamento | Sim | seleção rica com foto e identificação técnica |
| Técnico responsável | Não | pode ser definido na abertura ou depois |
| Prioridade | Sim | baixa, normal, alta ou urgente |
| Data de entrada | Sim | data/hora da recepção |
| Previsão | Não | usada para acompanhamento do prazo |
| Status | Sim | estado inicial da OS |
| Relato do cliente | Sim | problema informado na recepção |

### Seleção de equipamento

O seletor de equipamento foi enriquecido para reduzir erro de escolha quando o cliente possui aparelhos parecidos.

Cada opção pode exibir:

- foto de perfil;
- tipo e marca;
- modelo e cor;
- número de série ou IMEI.

Também existem ações inline:

- `Novo`
- `Editar`

### Sidebar de fotos na edição

Na edição da OS, a lateral `Foto do Equipamento` continua exibindo a imagem principal e as miniaturas de forma imediata.

Comportamento atual:

- fotos reais do equipamento recebem atualização anti-cache automática quando há troca de principal, inclusão ou exclusão;
- quando o equipamento não possui arquivo físico disponível, o sistema usa fallback inline sem quebrar a visualização;
- o preview principal e as miniaturas permanecem sincronizados sem exigir recarga manual da página.

## Visualização da OS (`/os/visualizar/{id}`)

### Estrutura atual da tela

A tela foi reorganizada em duas áreas:

- coluna lateral com `Fotos do Equipamento` e `Histórico e Progresso`;
- coluna principal com resumo superior e abas centrais.

Resumo superior:

- cliente;
- equipamento;
- técnico.

### Abas da visualização

As abas principais são:

- `Informações`
- `Orçamento`
- `Diagnóstico`
- `Fotos`
- `Valores`

### Aba `Informações`

A aba `Informações` agora é somente de leitura para contexto operacional.

Ela mostra:

- relato do cliente;
- checklist de entrada;
- status atual da OS;
- status do orçamento vinculado, quando existir.

Importante:

- essa aba exibe o status atual;
- ela não é usada para alterar o status da OS.

### Aba `Orçamento`

Quando existir orçamento vinculado, a aba apresenta o resumo comercial da OS:

- número do orçamento;
- status do orçamento;
- tipo/origem;
- validade;
- itens inseridos;
- total do orçamento.

Se a OS ainda não tiver orçamento, a aba informa o estado vazio de forma explícita.

### Aba `Diagnóstico`

Concentra o conteúdo técnico da ordem:

- procedimentos executados;
- diagnóstico técnico;
- solução aplicada;
- técnico responsável;
- garantia.

### Aba `Fotos`

O agrupamento de imagens foi consolidado em uma única aba organizada por cards.

Ela pode reunir:

- foto de perfil do equipamento;
- demais fotos do equipamento;
- fotos da entrada;
- fotos de acessórios;
- fotos de checklist, quando houver.

### Aba `Valores`

Reúne os detalhes financeiros e temporais da OS:

- mão de obra;
- peças;
- subtotal;
- desconto;
- total;
- datas principais;
- situação do orçamento;
- dados complementares de financeiro da OS e do orçamento vinculado.

## Botão de orçamento no topo da OS

O comportamento foi endurecido para evitar duplicidade.

### Regras

- se a OS não possui orçamento vinculado: o botão permanece `Gerar orçamento`;
- se a OS já possui orçamento vinculado, em qualquer status:
  - não cria novo orçamento;
  - passa a abrir o orçamento existente;
  - o rótulo muda para `Editar orçamento` ou visualização equivalente conforme o contexto e a permissão.

## Relação entre OS e orçamento

### Quando um orçamento é gerado para a OS

- a listagem da OS é recarregada com o contexto do orçamento;
- a OS é movida para `Aguardando Autorização` enquanto o orçamento estiver em andamento.

### Quando o orçamento muda para `Aprovado` ou `Convertido`

- a OS vinculada passa para `Aguardando Reparo`.

Essa regra vale tanto para a experiência visual da listagem quanto para a sincronização operacional da OS.

## Checklist, acessórios e fotos de entrada

O fluxo de entrada continua permitindo:

- checklist por tipo de equipamento;
- registro de acessórios na entrada;
- fotos por galeria ou câmera;
- preview e organização dos anexos.

## Observações finais

- use a visualização da OS para leitura e acompanhamento;
- use os fluxos operacionais específicos para alterar status, gerar documentos e enviar orçamento;
- quando houver orçamento vinculado, considere sempre o estado combinado `OS + orçamento` antes de avançar a execução.
