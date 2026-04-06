# Componentes Oficiais

## Componentes base

- header mobile
- card de conteudo
- card de OS
- chips de status
- chips de prioridade
- campos de formulario
- botao primario
- botao secundario
- menu hamburguer
- bottom navigation

## Componentes de fluxo

- modal inline
- modal de revisao pre-envio da OS
- footer flutuante de modal
- seletor inteligente
- card selecionado de cliente
- seletor rico de equipamento
- card operacional (`collection-block`)
- galeria de miniaturas
- preview de imagem
- crop modal

## Card selecionado de cliente

Uso oficial:

- abertura de OS no app mobile/PWA

Estrutura visual obrigatoria:

- card clicavel com nome do cliente e telefone principal
- quando o card esta visivel, o seletor fica recolhido para reduzir poluicao visual
- ao tocar no card, o seletor abre novamente para permitir troca rapida de cliente com busca por nome/telefone

## Card operacional (`collection-block`)

Uso oficial:

- abertura de OS no app mobile/PWA

Padrao visual:

- container com borda suave e fundo claro para agrupar blocos operacionais
- cabecalho com titulo da secao no lado esquerdo e acao principal no lado direito
- botoes de acao com altura e hierarquia unificadas entre `Acessorios`, `Checklist` e `Fotos de entrada`
- secoes de `Fotos de entrada` e `Observacoes` devem ficar dentro do mesmo padrao de card para manter ritmo visual

## Seletor rico de equipamento

Uso oficial:

- abertura de OS no app mobile/PWA

Estrutura visual obrigatoria:

- miniatura quadrada com foto de perfil do equipamento
- linha primaria com `tipo - marca`
- linha secundaria com `modelo - cor`
- linha terciaria com `numero de serie` ou `IMEI`, mostrando apenas um identificador por vez
- fallback visual quando nao houver foto
- quando o equipamento estiver selecionado, o proprio campo passa a exibir um card rico com a mesma hierarquia visual

Objetivo:

- impedir selecao equivocada quando o cliente possui equipamentos iguais ou muito parecidos
- permitir busca pelo conjunto de identificadores tecnicos, e nao apenas pelo texto do label
- permitir abrir a galeria/carrossel das fotos de perfil ao tocar na miniatura, sem interferir no clique de selecao do equipamento

## Regra de implementacao

Antes de criar um novo componente, verificar se o comportamento ja existe em:

- fotos do equipamento;
- fotos de acessorio;
- fotos de entrada;
- card de OS;
- formulario de abertura de OS.

## Modal de revisao pre-envio da OS

Uso oficial:

- etapa final da tela `/os/nova` antes da criacao da OS

Padrao visual:

- lista de todos os campos (obrigatorios e opcionais) com estado visual `Preenchido/Pendente`
- chips de criticidade (obrigatorio/opcional) no mesmo row
- contadores de pendencias no topo do modal
- footer flutuante com acao de correcao e continuidade
- segunda etapa no mesmo modal para preferencia de notificacao do cliente
