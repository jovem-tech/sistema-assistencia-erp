# Módulos do Sistema de Assistência Técnica

Abaixo estão as descrições detalhadas de todos os módulos lógicos construídos ao longo da vida do projeto:

## 1. Módulo de Autenticação e Perfis (RBAC) (`Auth.php`, `Usuarios.php`, `Grupos.php`)
O núcleo do controle de privilégios. As contas são atestadas e encriptadas de forma segura com `password_hash()` contra vazamentos. 
- Gestão interna das contas pelo painel de **Usuários**. 
- Monitoramento de inatividade (via `AuthFilter`). 
- Página local customizada com animações.
- **Grupos de Acesso (RBAC):** Os usuários são alocados em grupos de acesso limitados em granularidade. O sistema impede proativamente visualizações de módulos (`canModule`) ou botões específicos de ações em views (`can('os', 'excluir')`).

## 2. Dashboard e Relatórios
Trata os dados em tempo real da aplicação no formato gráfico (via Chart.js na View `Admin::index`). Contém atalhos para os dados macro:
- Volume do faturamento bruto e líquido;
- Total de OS no mês vigentes vs OS Resolvidas;
- Alertas de Estoque e clientes reincidentes.

## 3. Clientes e Equipamentos
O gerenciamento dos ativos principais. 
- Todo Cliente deve ser mantido centralizado (`app/Controllers/Clientes.php`).
- Todo **Equipamento** possui metadados customizáveis de marca e de status atrelados (`app/Controllers/Equipamentos.php`).
- **Múltiplos Clientes (Vínculos):** Os equipamentos suportam o conceito de múltiplos vínculos. Mesmo tendo um dono/proprietário oficial original, administradores podem associar o mesmo equipamento simultaneamente a outros clientes da assistência. Essa modelagem permite abrir livremente Ordens de Serviço a partir de requisições de parentes ou sócios de uma empresa sobre o mesmo aparelho.

## 4. Gestor de Ordem de Serviço (OS)
É o ponto de equilíbrio do Sistema (`app/Controllers/Os.php`).
**Os ciclos de status englobam:** `aguardando_analise` ➝ `aguardando_orcamento` ➝ `aguardando_aprovacao` ➝ `aprovado`/`reprovado` ➝ `em_reparo` ➝ `aguardando_peca` ➝ `pronto` ➝ `entregue` ou `cancelado`.
- Permite vincular produtos do estoque como "Peças de reposição" que irão ser orçadas na mesma fatura final.
- O layout final suporta Link Público de aprovação de orçamentos (Permite enviar Link em PDF para aprovação via WhatsApp).
- **Integração com Defeitos Comuns:** Permite vincular defeitos pré-cadastrados no momento da abertura da OS para agilizar o diagnóstico.

## 5. Módulo de Defeitos Comuns e Base de Conhecimento
Evolução do suporte técnico para um formato de inteligência operacional (`app/Controllers/EquipamentosDefeitos.php`).
- **Catálogo de Defeitos:** Organiza falhas recorrentes por Tipo de Equipamento e Classificação (Hardware/Software).
- **Base de Conhecimento (Procedimentos):** Cada defeito pode conter uma série de procedimentos operacionais padrão (POP). Isso permite que técnicos iniciantes ou experientes sigam um roteiro testado de diagnóstico e solução, padronizando a qualidade do serviço.
- **Interface Moderna:** Utiliza sistema de cards, busca em tempo real e gestão via modais AJAX para uma experiência fluida.

## 6. Módulo de Estoque e Compras
Gerência das Peças do lado técnico.
- Permite controle total dos produtos/peças base.
- Permite a edição dos valores e informações descritivas dos produtos. 
- Log de Estoque: Permite auditoria completa e *logs de movimentação* para as inserções ou retiradas diretas através de uma entrada manual ou edição administrativa nas quantidades do produto. 
- Abate Sincronizado: Cada item de serviço aplicado a uma Ordem de Serviço deduz automaticamente as unidades atreladas na prateleira local pelo Controller (`app/Controllers/Estoque.php`).

## 6. Módulo Financeiro
Controla as saídas, os desdobramentos faturados a partir das aprovações de orçamentos (Receitas) vs Custos brutos para cálculo métrico (`app/Controllers/Financeiro.php`).

## 7. Módulo de Configurações
Uma página coringa restrita apenas a `Admins` (`app/Controllers/Configuracoes.php`). Permite de forma simples ajustar:
- Logomarca da empresa, Telefone, WhatsApp Institucional e Endereço Físico (propagável e visível nos relatórios para envio externo).
- **Tema Customizável**: Controle entre o CSS de fundo `dark` e `light` via Helper (`get_theme()`), refletido instantaneamente em todo UI de base.

## 8. Central de Mensagens (WhatsApp OS)
O centro de comunicação inteligente do sistema (`app/Controllers/CentralMensagens.php`).
- **Inbox "WhatsApp OS"**: Interface profissional em 3 colunas (Conversas, Chatroom e Contexto) para gestão de atendimentos em tempo real.
- **Chatbot Inteligente**: Motor de automação baseado em intenções e regras do ERP para responder clientes 24h por dia.
- **Integração CRM**: Cada mensagem enviada ou recebida alimenta automaticamente a Timeline e as Interações do cliente no CRM.
- **Métricas e FAQ**: Painéis de produtividade e base de conhecimento dinâmica para suporte ao atendimento.
