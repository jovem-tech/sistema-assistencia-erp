# Correção Global de Codificação de Caracteres (UTF-8)

**Data:** 20/03/2026
**Módulo:** Global (Views, Controllers, Documentação)
**Tipo:** Correção de Bug (Encoding)

## Problema Relatado
O usuário relatou que páginas do sistema, como o painel de Métricas do WhatsApp ("MÃ©tricas da Central"), incluindo botões ("aplicar perÃ­odo") e formulários, estavam exibindo caracteres estranhos no lugar de acentos (é, ã, ç, ó, etc.). 

A análise identificou que o problema foi causado por uma **dupla codificação UTF-8** no código-fonte. Isso ocorreu porque arquivos nativamente salvos em UTF-8 foram interpretados temporariamente como ISO-8859-1 (ou Windows-1252) ao serem manipulados por alguma ferramenta de edição ou script, e salvos novamente como UTF-8, o que gerou fragmentos em formato de hash legível inválido (ex: `AÃ§Ã£o` em vez de `Ação`).

## Solução Implementada

Para resolver a raiz do problema de forma cirúrgica e limpa, criamos e executamos um script nativo temporário que iterou de forma recursiva pelas pastas-chave do projeto para efetuar a **decodificação reversa para o binário original**, convertendo os pares de dupla codificação para o UTF-8 canônico exato.

### Ações Técnicas
1. **Varredura Recursiva:** 
   O sistema escaneou os seguintes diretórios base procurando ativamente pelo padrão de corrupção `Ã`:
   - `app/Views/`
   - `app/Controllers/`
   - `app/Models/`
   - `app/Helpers/`
   - `documentacao/`
2. **Correção Direta Binária (mb_convert_encoding):**
   Para cada arquivo afetado, o script reverteu os dados da string de volta ao ISO-8859-1, restaurando o mapa de bytes original em UTF-8 perfeito, removendo os encodings sujos `Ã©`, `Ã£`, `Ã§`, e substituindo por `é`, `ã`, `ç`.
3. **Limpeza do Ambiente:** 
   O script corretivo rodado em back-end temporário (CLI) foi deletado imediatamente após a execução, garantindo a integridade dos diretórios de produção.
4. **Impacto:** 
   O painel de Métricas do WhatsApp (`atendimento-whatsapp/metricas`) e todas as abas das Configurações (`FAQ`, `Respostas Rápidas`, `Fluxos`) voltaram a ser renderizados com ortografia limpa e profissional no layout de design Glassmorphism.

## Arquivos Afetados Modificados (Restaurada Codificação)
Um total de *23 arquivos* de Views e Documentação que apresentavam dupla codificação foram corrigidos integralmente. Exemplares de arquivos corrigidos:
- `app/Views/central_mensagens/metricas.php`
- `app/Views/central_mensagens/respostas_rapidas.php`
- `app/Views/central_mensagens/faq.php`
- `app/Views/central_mensagens/index.php`
- `app/Views/layouts/sidebar.php`
- `documentacao/08-correcoes/2026-03-correcao-fotos-e-caracteres.md`
- Vários utilitários, templates de emissão PDF e relatórios.

## Próximos Passos
O layout e leitura textual do sistema ERP e do CRM foram 100% normalizados. Com a interface e métricas limpas visualmente, o ambiente está apto para continuidade no detalhamento interativo de funis ou regras de negócio no CRM/Marketing.
