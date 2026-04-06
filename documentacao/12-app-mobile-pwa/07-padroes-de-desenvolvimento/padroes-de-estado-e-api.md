# Padroes de Estado e API

Atualizado em 04/04/2026.

## Estado no frontend

- telas pequenas podem usar `useState` local
- polling e stream devem possuir fallback
- respostas incrementais devem fazer merge por ID
- formularios longos devem preservar o contexto atual

## API client

- usar `apiRequest` central
- respeitar `skipAuth` apenas em login e fluxos tecnicos
- sempre tratar erros como mensagem amigavel + detalhe tecnico no console quando necessario

## Mutacoes

- salvar e refletir na interface sem refresh
- preferir atualizar apenas o bloco afetado
- usar `loadData` ou merge incremental apos mutacao
