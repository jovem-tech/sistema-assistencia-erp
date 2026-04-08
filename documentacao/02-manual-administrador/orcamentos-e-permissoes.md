# Orcamentos - Administracao e Permissoes

## Modulo e permissoes
O modulo `orcamentos` foi adicionado ao RBAC via migration.

Permissoes aplicadas:
- `visualizar`
- `criar`
- `editar`
- `excluir`

## Provisionamento inicial (fase 1)
Durante a migration:
- modulo `orcamentos` e criado em `modulos`,
- grupos com acesso de visualizacao a `clientes` ou `os` recebem permissao base de orcamentos (`visualizar`, `criar`, `editar`),
- grupo `Administrador` recebe adicionalmente `excluir`.

## Ajuste fino por grupo
Para ajustar acesso:
1. Ir em `Configuracoes > Niveis de Acesso`.
2. Abrir o grupo desejado.
3. Marcar/desmarcar a matriz do modulo `Orcamentos`.

## Rotas protegidas
As rotas do modulo usam filtro `permission:orcamentos:*`:
- `GET /orcamentos`
- `GET /orcamentos/novo`
- `POST /orcamentos/salvar`
- `GET /orcamentos/visualizar/{id}`
- `GET /orcamentos/editar/{id}`
- `POST /orcamentos/atualizar/{id}`
- `POST /orcamentos/status/{id}`
- `POST /orcamentos/converter/{id}`
- `POST /orcamentos/automacao/executar`
- `POST /orcamentos/pdf/{id}/gerar`
- `GET /orcamentos/pdf/{id}`
- `POST /orcamentos/whatsapp/{id}/enviar`
- `POST /orcamentos/email/{id}/enviar`
- `POST /orcamentos/central-mensagens/gerar-enviar`
- `GET /orcamentos/excluir/{id}`

## Operacao de envio (fase 2)
- Envio por WhatsApp e e-mail exige permissao `orcamentos:editar`.
- Geracao/visualizacao de PDF exige permissao `orcamentos:visualizar`.
- Todas as tentativas (sucesso, duplicado e erro) ficam auditadas em `orcamento_envios`.
- Em caso de sucesso no envio para cliente, o orcamento entra em `aguardando_resposta` (quando aplicavel).

## Operacao de conversao e automacao (fase 3)
- Conversao para OS/venda exige permissao `orcamentos:editar`.
- Geracao + envio rapido pela Central exige `orcamentos:criar` e `orcamentos:editar`.
- Automacao de vencimento/follow-up pode ser executada:
  - via botao `Executar automacao` no painel de orcamentos;
  - via comando CLI `php spark orcamentos:lifecycle` (recomendado em cron no servidor).
- Recomendacao de rotina em producao (Linux/cron):
  - `*/30 * * * * /usr/bin/php /var/www/sistema-assistencia/spark orcamentos:lifecycle`

## Observacao operacional
Os links publicos de aprovacao/rejeicao (`/orcamento/{token}`) nao exigem autenticacao porque sao endpoints externos para o cliente final.
