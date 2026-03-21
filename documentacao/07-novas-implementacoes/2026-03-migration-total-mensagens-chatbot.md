# Nova Implementação: Chatbot Totalmente Editável e com Templates (Março/2026)

## Descrição
Anteriormente, as respostas do chatbot para consultas de OS, orçamentos, previsões e outras ações operacionais estavam fixas no código PHP. Esta implementação migrou **todas** as mensagens para o banco de dados (`chatbot_intencoes`), utilizando um sistema de templates dinâmicos.

## Funcionamento
O sistema agora prioriza o campo **Resposta Padrão** de cada intenção cadastrada. Se o campo contiver variáveis entre chaves `{{var}}`, o chatbot as preenche automaticamente com os dados da conversa ou da Ordem de Serviço vinculada.

## Variáveis Disponíveis
As seguintes chaves podem ser usadas em qualquer resposta de intenção:
- `{{cliente_nome}}`: Nome do contato na conversa.
- `{{numero_os}}`: Número da OS vinculada (ex: #123).
- `{{status}}`: Status atual da OS (Formatado).
- `{{valor_final}}`: Valor total da OS em R$.
- `{{data_previsao}}`: Data de previsão de entrega.
- `{{garantia_dias}}`: Prazo de garantia em dias.
- `{{equipamento}}`: Marca e modelo do equipamento.
- `{{empresa_endereco}}`: Endereço da loja (configurações globais).

## Como Personalizar
1. Acesse **Operacional > Central de Mensagens > Chatbot**.
2. Escolha uma intenção (ex: `consultar_status_os`).
3. Edite o campo **Resposta Padrão**.
   - Exemplo: "Olá {{cliente_nome}}! Sua OS {{numero_os}} está em {{status}} e a previsão é {{data_previsao}}."
4. Salve a intenção. A mudança é imediata.

## Impacto Técnico
- Refatoração de `ChatbotService::resolverRespostaIntencao()` para aceitar o objeto completo da intenção.
- Implementação de `ChatbotService::renderResposta()` para processamento de templates.
- Remoção definitiva de strings user-facing fixas no código do chatbot.
