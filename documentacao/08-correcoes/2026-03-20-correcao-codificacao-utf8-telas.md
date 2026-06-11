# Correção Global de Codificação de Caracteres (UTF-8)

**Data:** 20/03/2026
**Módulo:** Global (Views, Controllers, Documentação)
**Tipo:** Correção de Bug (Encoding)

## Problema Relatado
O usuário relatou que páginas do sistema, como o painel de Métricas do WhatsApp ("Métricas da Central"), incluindo botões ("aplicar período") e formulários, estavam exibindo caracteres estranhos no lugar de acentos.

A análise identificou que o problema foi causado por uma **dupla codificação UTF-8** no código-fonte. Isso ocorreu porque arquivos nativamente salvos em UTF-8 foram interpretados temporariamente como ISO-8859-1 (ou Windows-1252) ao serem manipulados por alguma ferramenta de edição ou script, e salvos novamente como UTF-8, o que gerou fragmentos de mojibake em vez do texto canônico.

## Solução Implementada
Para resolver a raiz do problema de forma cirúrgica e limpa, foi criado e executado um utilitário temporário que percorreu as pastas-chave do projeto e reverteu os trechos corrompidos para o UTF-8 canônico.

### Ações Técnicas
1. **Varredura recursiva:**
   O sistema escaneou os seguintes diretórios base procurando ativamente por padrões clássicos de corrupção de encoding:
   - `app/Views/`
   - `app/Controllers/`
   - `app/Models/`
   - `app/Helpers/`
   - `documentacao/`
2. **Correção direta binária:**
   Para cada arquivo afetado, o utilitário reverteu os dados da string para o mapa de bytes original antes de reconstruir o texto em UTF-8 limpo.
3. **Limpeza do ambiente:**
   O script corretivo executado em back-end temporário (CLI) foi removido após o uso, preservando a integridade dos diretórios de produção.
4. **Impacto:**
   O painel de Métricas do WhatsApp (`atendimento-whatsapp/metricas`) e as abas das Configurações (`FAQ`, `Respostas Rápidas`, `Fluxos`) voltaram a ser renderizados com ortografia limpa e profissional no layout do sistema.

## Arquivos Afetados Modificados
Um conjunto de views, controllers e documentos afetados por dupla codificação foi corrigido integralmente. Exemplos representativos:
- `app/Views/central_mensagens/metricas.php`
- `app/Views/central_mensagens/respostas_rapidas.php`
- `app/Views/central_mensagens/faq.php`
- `app/Views/central_mensagens/index.php`
- `app/Views/layouts/sidebar.php`
- `documentacao/08-correcoes/2026-03-correcao-fotos-e-caracteres.md`
- templates de emissão PDF e relatórios correlatos.

## Próximos Passos
O layout e a leitura textual do sistema ERP e do CRM foram normalizados, deixando o ambiente apto para evolução funcional sem ruído visual causado por encoding.
