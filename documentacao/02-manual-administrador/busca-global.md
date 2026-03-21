# Manual do Administrador: Busca Global Inteligente

A **Busca Global** é uma ferramenta poderosa que centraliza o acesso a dados de diversos módulos. Como administrador, é importante entender como gerenciar e otimizar esta funcionalidade.

## ⚙️ Configurações e Gerenciamento

### 1. Permissões de Acesso (RBAC)
A busca global respeita integralmente o sistema de permissões atual.
- **Visualização**: Se um usuário não tem permissão de `visualizar` um módulo (ex: Financeiro), os resultados desse módulo **não aparecerão** para ele na busca global.
- **Acesso direto**: Ao clicar em um resultado, o usuário é levado para a tela correspondente. Se a permissão for removida após o carregamento da busca, o sistema negará o acesso ao tentar abrir a página.

### 2. Módulos Pesquisáveis
A busca global atualmente varre:
- Ordens de Serviço (OS)
- Clientes e Contatos
- Equipamentos e Aparelhos
- Teclado de Mensagens WhatsApp
- Estoque de Peças
- Serviços cadastrados
- Menus e Módulos do Sistema

## 💡 Boas Práticas para o Administrador

- **Padronização de Cadastros**: Incentive os usuários a cadastrarem CPFs, CNPJs e Números de Série corretamente. Isso aumenta drasticamente a precisão da busca global (que suporta pesquisas por estes campos).
- **Atalhos Rápidos**: Treine a equipe para usar a busca global para navegar entre módulos (ex: digitar "estoque" em vez de procurar no menu lateral). Isso reduz o tempo de operação no ERP.
- **Segurança**: Monitore os logs de sistema se notar comportamentos anômalos de pesquisa (embora a busca global tenha limites de resultados por grupo para evitar extração massiva de dados).

## 🛠️ Manutenção Técnica
Caso a busca global deixe de retornar resultados de um novo módulo criado futuramente, a classe `GlobalSearchService.php` deve ser estendida para incluir o novo model.
