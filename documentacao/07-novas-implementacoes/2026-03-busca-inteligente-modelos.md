# Busca Inteligente de Modelos (Ponte Autocomplete Google)

**Data de Lançamento:** Março de 2026

## 🎯 Objetivo
Implementar uma ponte inteligente para busca de modelos de equipamentos no momento do preenchimento da OS e do Cadastro de Equipamentos. 
Ao invés do usuário digitar e acabar criando modelos incorretos, o sistema busca modelos reais do mercado e cadastra automaticamente na base de dados quando selecionado, usando o autocomplete da API do Google Suggest.

## 🚀 Como Funciona
A ferramenta atua nativamente em três locais:
1. Nova Ordem de Serviço (No Modal de '+ Novo Equipamento')
2. Aba de Operacional > Estoque / Aparelhos > Cadastrar Aparelho
3. Aba de Configurações > Aparelhos > Modelos > Novo Modelo

### Fluxo de Uso
1. **Seleção de Contexto:** O usuário seleciona obrigatoriamente um **Tipo** (ex: Smartphone) e uma **Marca** (ex: Motorola).
2. **Busca Ativa:** Ao digitar o nome do modelo (mínimo de 3 caracteres), ocorre o gatilho da busca.
3. **Backend em Proxy:** O CodeIgniter (via `ModeloBridge.php`) captura o texto, adiciona a marca e o tipo ("Smartphone Motorola G84") e realiza a requisição no backend, escondendo a API.
4. **Limpeza e Formatação (Remoção de Redundância):** O backend recebe o retorno da internet e **LIMPA** termos redundantes. Promocionais como ("Novo, Lacrado") e redundância da própria categoria como ("Motorola", "Smartphone") são apagados. 
5. **Apresentação:** O Select2 no Frontend agrupa o retorno num dropdown rico com título `Modelos Cadastrados` e `Sugestões da Internet`.
6. **Seleção e Edição Livre (Tags):** Ao clicar num modelo da internet, o usuário ainda pode editar livremente o nome (ex: adicionar as especificações de cor/memória) porque implementamos `tags: true`.
7. **Auto-cadastro:** Ao submeter o formulário (seja de Equipamento ou OS), o script `Equipamentos::processarMarcaModelo()` detecta o ID gerado como `EXT|GGL_...` e realiza a inserção do Modelo e/ou Marca antes de associar ao equipamento da OS.

## 👨‍💻 Arquitetura e Implementação
* **Controller Principal:** `ModeloBridge::buscarSugestoesGoogle()` - Onde o proxy REST ocorre e faz a limpeza contextual inteligente (inclusive entendendo variações ortográficas, como `smartfone` -> `smartphone`).
* **View (JS Select2):** Funções customizadas de `templateResult` e `templateSelection` enriquecem o visual apenas para o display.
* **Fallback e Segurança:** Totalmente resiliente, com cache (`cache:true`) e debounce de 400ms para poupar performance.

## 🌐 Mapeamento do Dicionário (Limpeza de Tipos)
Se novos tipos ortográficos exóticos de equipamentos forem adicionados no futuro, o dicionário da função `$mapa` dentro do `normalizarTipo()` deve ser mantido para garantir que não sujem o Select2 do cliente final.
