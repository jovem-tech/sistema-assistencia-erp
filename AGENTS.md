# AGENTS.md — Sistema de Assistência Técnica

## Regra Permanente: Documentação Sempre Atualizada

Sempre que o usuário solicitar **alterações, atualizações, correções ou melhorias no código do sistema**, a IA deve obrigatoriamente:

1. **Implementar a mudança no código.**
2. **Atualizar toda a documentação relevante em `documentacao/`** para refletir exatamente o que mudou:
   - Manuais de usuário e administrador
   - Arquitetura técnica
   - API
   - Banco de dados
   - Roadmap (se a mudança impactar planejamento)
   - Histórico de correções e novas implementações, quando aplicável
3. **Ajustar links de ajuda e mapeamentos de `openDocPage`** caso novas páginas sejam criadas ou renomeadas.
4. **Informar explicitamente quais arquivos de documentação foram atualizados.**

Esta regra se aplica a todas as futuras solicitações relacionadas ao código.

## Prompt recomendado para solicitações de modificação

Para garantir a cobertura documental total, use sempre a seguinte introdução antes de detalhar o que deseja:

```
Documentação obrigatória: implemente a alteração, execute as migrações e atualize todos os manuais, guias de banco de dados e notas de implementação afetados, incluindo os links de ajuda.
```

Esse texto sinaliza que a solicitação exige uma mudança no código acompanhada de atualização completa da pasta `documentacao/` e facilita nossa rotina de auditoria.
