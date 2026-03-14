# Registro de Evolução do Projeto - Março 2026

Este documento registra as implementações, melhorias de arquitetura e decisões de design tomadas recentemente para elevar a maturidade do sistema.

## 1. Implementação do Design System Interativo
Para garantir a consistência visual e agilizar o desenvolvimento de novas telas, foi criado um laboratório de design vivo.
- **Arquivo:** `public/design-system.html`
- **Conteúdo:** Catálogo completo de componentes (Botões Glow, Stat Cards, Glass Cards, Badges, Formulários e Alertas).
- **Objetivo:** Servir de referência rápida para classes CSS customizadas e padrões de UX.

## 2. Refatoração da Arquitetura do Menu Lateral (Sidebar)
O menu foi totalmente reorganizado com base em princípios de **UX/UI para Dashboards Administrativos**, focando em hierarquia lógica e redução de carga cognitiva.

### Novos Agrupamentos Funcionais (V2):
1.  **VISÃO GERAL:** Dashboard (Foco em KPIs).
2.  **OPERACIONAL:** 
    *   **Ordens de Serviço**: Core operacional.
    *   **Serviços**: Gestão de catálogo de serviços (Novo).
    *   **Estoque de Peças**: Gestão de insumos.
    *   **Aparelhos / Equip.**: Gestão técnica de dispositivos (Movido de Comercial).
    *   **Base de Defeitos**: Base de conhecimento técnico.
3.  **COMERCIAL:**
    *   **Submenu Pessoas**: Centraliza **Clientes**, **Equipe Técnico** e **Fornecedores**.
    *   **Vendas**: Módulo para faturamento e pedidos (Placeholder).
4.  **GESTÃO & RESULTADOS:** Financeiro e Relatórios.
5.  **CONFIGURAÇÕES:** Dados da Empresa, Usuários e Níveis de Acesso.

## 3. Padronização de Elementos Visuais
- **Iconografia:** Transição completa para ícones sólidos (`-fill`) do Bootstrap Icons para maior unidade visual.
- **Nomenclatura Profissional:** Atualização de labels para termos mais adequados ao mercado tecnológico ("Funcionários" -> "Equipe Técnico", "Sistema" -> "Dados da Empresa", "Defeitos Comuns" -> "Base de Defeitos").
- **UX Industrial:** Aplicação da regra de submenus para ocultar itens de configuração de baixa frequência (Auxiliares), mantendo a interface limpa.

## 4. Documentação de Inteligência (Skill Antigravity)
Foi criada uma "Skill" específica para agentes de IA que trabalham no projeto.
- **Local:** `.agents/skills/sistema_assistencia/SKILL.md`
- **Função:** Documentar padrões de código (CodeIgniter 4), convenções de backend (Flashdata, RBAC) e diretrizes de frontend (Glassmorphism, estilos de tabela). Isso garante que qualquer assistência futura siga rigorosamente os padrões já estabelecidos.

## 5. Manutenções Técnicas e Estabilização
- **Correção da Porta de Acesso:** Identificação e documentação do uso da porta `8080/8081` para o servidor Apache no ambiente XAMPP local.
- **Melhoria na Navegação Mobile:** Ajuste fino nos seletores e overlays da sidebar para garantir usabilidade em telas menores.

## 6. Implementação da Permissão "Encerrar" (Configuração Inicial)
Foi estruturada a base para a nova funcionalidade de encerramento de entidades, visando manter histórico auditável sem deletar registros.

### 🛠️ Mudanças na Estrutura
- **Banco de Dados**:
    - Nova permissão `encerrar` (slug) adicionada à tabela de permissões.
    - Colunas `status` (default 'ativo') e `encerrado_em` (datetime) adicionadas às tabelas `equipamentos` e `pecas`.
- **RBAC**:
    - Grupo **Administrador** recebeu automaticamente a permissão de encerramento em todos os módulos permitidos.
    - **Regra de Exclusão**: O módulo de **Clientes** foi bloqueado para o encerramento na matriz de acesso, sugerindo que o USER pode querer manter histórico.
- **UI (Matriz de Acesso)**:
    - Nova coluna disponível na gestão de **Níveis de Acesso**.
    - Ícone de bloqueio (`bi-dash-circle`) exibido na linha de Clientes para a coluna Encerrar.

### 🚀 Implementação Visual e UX (Concluído)
- **Botão de Ação**: O ícone de arquivo (`bi-archive`) foi adicionado em todas as listagens operacionais (OS, Equipamentos, Estoque, Financeiro, Equipe Técnico e Fornecedores) na cor `warning` (amarelo), diferenciando-o visualmente da exclusão definitiva.
- **Ajuda ao Usuário**: Implementada função Javascript `confirmarEncerramento()` em `public/assets/js/scripts.js` para garantir que ações críticas exijam confirmação deliberada.
- **Sincronização de Vocabulário**: O sistema agora utiliza o termo **"Níveis de Acesso"** de ponta a ponta (Menu, Banco de Dados e Títulos de Página), eliminando ambiguidades com o termo genérico "Permissões".

### 📝 Guia de Implementação para Desenvolvedores
Para as entidades que suportam encerramento (OS, Equipamentos, Peças):
1. **Model**: Deve-se filtrar por `status != 'encerrado'` em listagens operacionais, permitindo visualização em relatórios históricos.
2. **Controller**: Utilizar `can('modulo', 'encerrar')` para exibir botões de ação na interface.
3. **Lógica Técnica**: Ao encerrar, setar `status = 'encerrado'` (ou valor customizado como 'condenado') e `encerrado_em = now()`.

## 7. Refinement of Visual Hierarchy and Sidebar UX
Para suportar o crescimento da árvore de navegação, o menu lateral foi redesenhado para fornecer uma visão clara de profundidade e estados de expansão.

### 🌳 Árvore de Navegação Premium
- **Linhas de Guia Verticais**: Submenus agora possuem linhas de conexão verticais sutis que facilitam o rastreamento visual da hierarquia (ex: Operacional -> Aparelhos -> Modelos).
- **Indicadores de Submenu (Chevrons)**:
    - Todos os menus expansíveis ganharam ícones de seta (`bi-chevron-down`).
    - **Animação de Rotação**: Usando CSS transform, a seta gira 180° suavemente ao abrir/fechar a seção, fornecendo feedback imediato.
- **Hierarquia de 3 Níveis**:
    - **Nível 1**: Ícones sólidos e coloridos (Dashboard, OS, Pessoas).
    - **Nível 2**: Indentação de 28px com linha conectora.
    - **Nível 3 (Atributos)**: Indentação adicional, tipografia menor (`small`) e ícones minimalistas para evitar poluição visual.

### 💡 Feedback de Estado e Interatividade
- **Active Glow**: Itens ativos agora possuem uma barra luminescente lateral (`box-shadow` e `glow`) que destaca a página atual sem "gritar" visualmente.
- **Transições Suaves**: Toda a movimentação de abertura e fechamento de menus utiliza transições Bezier para uma sensação de sistema fluido e moderno.

## 8. Expansão de Módulos: Serviços e Vendas
Para suportar a evolução para um ERP completo, a estrutura de dados e permissões foi expandida.

### ⚙️ Módulo de Serviços
- **Funcionalidade**: Cadastro de serviços padronizados (ex: Troca de Tela, Reparo de Placa) com valores padrão e descrições técnicas.
- **Integração**: Preparado para ser selecionado diretamente dentro do fluxo de itens de uma OS.
- **Segurança**: Integrado ao RBAC com todas as 7 permissões (Visualizar até Encerrar).

### 💰 Módulo de Vendas (Infraestrutura)
- **Status**: Módulo em desenvolvimento (Placeholder).
- **Preparação**: Criado controlador, rotas protegidas e entrada no banco de dados de módulos. Isso permite que administradores já configurem permissões de acesso para grupos antes mesmo da funcionalidade core ser liberada, garantindo uma transição suave.

### 🗄️ Infraestrutura de Banco de Dados
- **Tabela `servicos`**: Implementada com suporte a Soft Deletes lógico (`status`) e auditoria.
- **Otimização de Módulos**: Tabela `modulos` atualizada com campos `icone` e `ordem_menu` para permitir que o sidebar seja renderizado de forma mais dinâmica e organizada.

## 9. Ferramentas de Gestão Massiva (CSV)
Foram implementadas ferramentas de produtividade para manipulação de grandes volumes de dados nos módulos operacionais.

### 📊 Exportação Avançada
- **Módulos**: Serviços e Estoque de Peças.
- **Função**: Gera arquivos CSV estruturados com headers amigáveis ao Excel.
- **Auditoria**: Cada exportação é registrada nos logs do sistema com o ID do responsável.

### 📥 Importação em Lote via Modelos
- **Segurança e Padronização**: Disponibilizados arquivos modelo (Download Template) para garantir que o usuário envie os dados no formato esperado pelo banco de dados.
- **Normalização Automática**:
    - Conversores de moeda inteligentes (tratam vírgulas e pontos automaticamente).
    - Geradores de códigos sequenciais (SKU/PC) para novos itens de estoque caso omitidos no arquivo.
- **RBAC**: As funções de importação e exportação são granulares, permitindo que o gestor decida quais usuários podem apenas visualizar ou também manipular o inventário massivamente.

## 10. Gestão Avançada de Imagens e Equipamentos
Foi integrada uma camada de processamento de imagem profissional ao fluxo de cadastro de equipamentos para elevar a qualidade do banco de imagens técnico.

### 📸 Captura e Edição Pro (Cropper.js)
- **Editor de Imagem Integrado**: Ao tirar uma foto ou escolher da galeria, o sistema abre automaticamente um modal de edição (Cropper).
    - **Funcionalidades**: Corte livre, redimensionamento proporcional e rotação (90°).
    - **Otimização**: Imagens são processadas em canvas e exportadas em alta qualidade (1024x1024) com tamanho de arquivo otimizado.
- **Integração com Câmera**: Implementado suporte nativo para captura via webcam/câmera do celular diretamente no navegador, com prioridade para câmera traseira (*environment*) em dispositivos móveis.
- **Preview Dinâmico**: Gerenciamento de múltiplos arquivos via `DataTransfer`, permitindo remover fotos antes do upload final.

## 11. Refinamento de UX: Cadastros Rápidos
Para reduzir interrupções no fluxo de trabalho (context switching), o sistema de "Atalhos de Cadastro" foi padronizado.

### ⚡ Botões de Adição In-Label
- **Padrão Visual**: Botões de `+ Novo` foram movidos para dentro das labels dos campos de seleção (`<label>`), utilizando um design minimalista (amarelo sólido, tamanho extra pequeno).
- **Escopo**: Implementado para **Clientes**, **Marcas** e **Modelos** em:
    - Modal de Equipamento (dentro da Ordem de Serviço).
    - Página principal de Cadastro de Equipamento (`equipamentos/novo`).
- **Benefício**: Mais espaço horizontal para os campos de seleção (Select2) e uma interface mais limpa e focada.

## 12. Melhorias na Gestão de Clientes e Equipamentos
- **Cascata Inteligente**: Lógica de "Marca -> Modelo" refatorada para ser mais reativa, desabilitando seletores automaticamente até que o vínculo pai seja selecionado.
- **Persistência de Cores**: O seletor de cores agora traduz automaticamente o código HEX para o nome da cor mais próxima em português, facilitando a identificação técnica.

## 13. Interface Premium de Equipamentos (Abas e Cores)
O módulo de equipamentos recebeu uma atualização profunda de UX/UI para suportar volumes maiores de dados e oferecer uma experiência de nível industrial.

### 🗂️ Reestruturação em Abas (Tabs) e Sidebar
O módulo de equipamentos recebeu uma atualização profunda de UX/UI e foi movido para a seção **OPERACIONAL** do sidebar, logo após o Estoque de Peças, para melhor alinhamento com o fluxo de trabalho técnico.
O formulário de cadastro e edição foi dividido em 3 núcleos lógicos para reduzir o cansaço visual e organizar o fluxo de entrada do aparelho:
- **Informações**: Focada em identificação (Marca/Modelo/Série), Senha (com alternância PIN/Texto) e Acessórios.
- **Cor**: Espaço dedicado à identidade visual do produto.
- **Fotos**: Centraliza a documentação por imagem com suporte a até 4 arquivos.

### 🎨 Seletor de Cor Profissional (Accordion)
Evolução do sistema de cores para um catálogo visual de alto nível:
- **Organização por Famílias**: Cores agrupadas por tons (Neutras, Azuis, Verdes, etc.) utilizando um sistema de **Accordion (Sanfona)** com expansão única.
- **Swatches Ampliados**: Indicadores de cor ampliados para 26px com nomes comerciais reais (Midnight, Titanium, Rose Gold).
- **Detecção Inteligente**: O algoritmo agora destaca automaticamente a cor detectada por foto dentro do catálogo, garantindo padronização na base de dados.

### ⚡ Eficiência Técnica (Atalhos e Senhas)
- **Acessórios de Um Clique**: Adicionados botões de atalho (Carregador, Cabo, Capa, etc.) que inserem o item no campo de texto instantaneamente, eliminando digitação repetitiva.
- **Sistema de Senhas Dual**: Alternância rápida entre teclado numérico (PIN) e alfanumérico com placeholders contextuais, melhorando a precisão do técnico no balcão.
- **Estabilização Ajax**: Unificação dos IDs de formulário e exposição global de funções `window.updateColorUI`, resolvendo conflitos de carregamento dinâmico entre páginas e modais.

## 14. Expansão de Campos na Ordem de Serviço
Para aumentar a rastreabilidade na entrada de aparelhos e agilizar o faturamento, a tabela de Ordens de Serviço foi expandida.

### 🛠️ Novos Campos Técnicos
- **Acessórios (`acessorios`)**: Campo do tipo TEXT para registrar detalhadamente cabos, carregadores, capas e outros itens recebidos junto com o equipamento.
- **Forma de Pagamento (`forma_pagamento`)**: Registro da preferência de pagamento do cliente (Pix, Cartão, Dinheiro), agilizando a emissão de notas e recebimentos na finalização.

### 🚀 Implementação e Migração
- **Script de Update**: Criado `update_os_campos.php` para garantir que as novas colunas sejam adicionadas de forma segura em ambientes existentes.
- **Persistência**: Atualizado o `OsModel.php` para incluir os novos campos na lista de `allowedFields`.
- **UI**: Inseridos novos campos nas abas de "Relato e Detalhes" do formulário de OS, com suporte a rascunho automático.

## 15. Melhoria da Experiência de Abertura de OS
Foi refinado o fluxo de abertura de OS para reduzir retrabalho e aumentar a visibilidade do contexto durante o preenchimento.

### Resumo e Contexto Persistente
- **Resumo lateral da OS** com cliente, equipamento, técnico, prioridade, status, datas e contadores.
- **Foto do equipamento e miniaturas** exibidas na lateral após seleção do equipamento.

### Preenchimento Mais Rápido
- **Seleção inteligente de equipamento** quando existe apenas um item vinculado ao cliente.

### Proteção contra Perda de Dados
- **Rascunho automático local** durante a criação de OS.
- **Ações de restaurar/descartar** e botão de limpeza manual do rascunho.

## 16. Abas de Cadastro e Campos de Entrada na OS
Evolução do formulário de nova OS para reduzir erros e acelerar o preenchimento.

### Organização em Abas
- **Dados**, **Relato e Defeitos**, **Fotos** e **Peças e Orçamento**.

### Campos de Entrada Reforçados
- **Acessórios na OS** para registrar itens recebidos na entrada.
- **Prazo de entrega** com cálculo automático de data (1, 3, 7, 30 dias).
- **Forma de pagamento** disponível no cadastro.
- **Upload de fotos** agora usa o mesmo card do cadastro de equipamentos, com área clicável, drag/drop e miniaturas clicáveis.

### Feedback Visual
- **Indicadores de preenchimento** (✔️/❌) no resumo lateral.
- **Cor do equipamento** exibida mesmo quando não há foto.
- **Estabilização de Formulários**: Corrigido crash (Error 500) no cadastro/edição de funcionários devido a checkboxes não marcados.
