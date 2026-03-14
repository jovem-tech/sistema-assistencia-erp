# Melhorias na Abertura de OS (Nova)

## Visão Geral
Atualização de UX da tela `OS > Nova` para reduzir retrabalho, deixar o contexto visível durante o preenchimento e registrar todos os acessórios entregues com fotos.

## Principais Mudanças
- **Resumo lateral completo** mostra cliente, equipamento, técnico, prioridade, status, datas, faults e contadores com indicadores ✓/✕.
- **Sidebar persistente do equipamento** exibe foto principal, miniaturas e a cor do aparelho, mesmo quando ainda não há imagem carregada.
- **Upload de fotos repaginado** usa o mesmo painel do cadastro de equipamentos (drag/drop, galeria/câmera, previews clicáveis).
- **Botões rápidos de acessórios** (chip, capinhas, cabo, carregador, mochilas, entre outros) padronizam o texto e capturam campos extras antes de salvar.
- **Cor em acessórios com UX padronizada**: o formulário rápido usa seletor de cor e salva apenas o nome da cor no texto final do item (sem “sem cor”, sem hífen e sem exibir HEX).
- **Tipos de cabo expandidos**: incluída opção `Cabo de força` e, ao selecionar `Outro`, o sistema abre campo manual para detalhar o tipo.
- **Fotos por acessório** armazenadas sob `uploads/acessorios/OS_<número>` e exibidas tanto na seção de acessórios quanto na aba “Fotos”.
- **Tabela de suporte para acessórios** (`acessorios_os` e `fotos_acessorios`) permite salvar cada item com metadados e várias imagens.
- **Campo `data_entrada` na tabela `os`** garante rastreabilidade da data em que o equipamento chegou.
- **Prazo em dias** agora coloca automaticamente a data de previsão (1, 3, 7 e 30 dias).
- **Rascunho automático local** com ações de restaurar, descartar e limpar o cache do navegador.
- **Abas organizadas na abertura** (Dados, Relato e Defeitos, Fotos) com fluxo claramente dividido.
- **Peças e Orçamento** ficam disponíveis apenas após a OS estar criada (visualização/edição).

## Impacto para Usuários
- Menos perda de dados em cadastros longos.
- Visibilidade contínua do contexto da OS enquanto preenche o formulário.
- Mais agilidade quando o cliente tem apenas um equipamento.
- Registro visual detalhado dos acessórios, reduzindo dúvidas sobre o que foi entregue.

## Arquivos Atualizados
- `app/Views/os/form.php`
- `app/Views/os/show.php`
- `app/Controllers/Os.php`
- `app/Models/OsModel.php`
- `app/Models/AcessorioOsModel.php`
- `app/Models/FotoAcessorioModel.php`
- `database.sql`
- `update_os_campos.php`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/04-banco-de-dados/tabelas-principais.md`
