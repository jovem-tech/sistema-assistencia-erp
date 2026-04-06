# Tabelas Compartilhadas com o ERP

## Premissa

O app opera sobre as mesmas entidades centrais do ERP.

## Tabelas reutilizadas pelo app

- `usuarios`
- `clientes`
- `equipamentos`
- `equipamentos_fotos`
- `conversas_whatsapp`
- `mensagens_whatsapp`
- `ordens_de_servico` e tabelas relacionadas

## Regra de uso

- ler e escrever pelas mesmas regras de negocio do ERP;
- evitar criar atalhos tecnicos que desalinhem o app do sistema principal;
- documentar todo campo novo usado pelo app.

## Impacto de mudanca

Qualquer alteracao em tabela compartilhada precisa avaliar:

1. impacto no ERP web;
2. impacto no app mobile;
3. impacto em migracoes, deploy e rollback.

