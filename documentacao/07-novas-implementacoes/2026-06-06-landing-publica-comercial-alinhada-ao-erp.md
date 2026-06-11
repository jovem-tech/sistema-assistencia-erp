# 2026-06-06 - Landing publica comercial alinhada ao ERP

## Objetivo

Substituir a copy generica da landing comercial por uma apresentacao publica coerente com o estado real atual do ERP.

## O que mudou

- criada a view `app/Views/public/landing_page.php`;
- `Home::index()` passou a montar dados institucionais dinamicos para a pagina publica;
- `app/Config/Routes.php` passou a expor as rotas `/site` e `/apresentacao`;
- a copy visivel da pagina foi normalizada para pt-BR com acentuacao correta, incluindo `title`, `meta description`, CTA, FAQ e mensagem inicial de WhatsApp;
- a copy comercial passou a destacar os modulos efetivamente disponiveis hoje:
  - `OS` com fotos, checklist e workflow;
  - `Orcamentos` com `link publico`, `PDF`, `WhatsApp` e `e-mail`;
  - `WhatsApp OS` e `CRM`;
  - `Financeiro` com `DRE` e `Fluxo de Caixa`;
  - `App mobile/PWA`;
  - `Coletor de Bancada` para `Desktop` e `Notebook`.

## Decisoes de implementacao

- a rota raiz `/` continuou apontando para `Auth::login`, preservando o fluxo operacional atual do ERP;
- a landing usa `empresa_nome`, `empresa_telefone`, `empresa_email`, `empresa_endereco`, `sistema_logo` e `sistema_icone` quando esses dados estiverem configurados;
- quando existir numero comercial utilizavel, a pagina monta CTA direto para `WhatsApp`;
- quando esse numero nao estiver configurado, a pagina mantem CTA seguro para navegacao interna e contato institucional.

## Impacto funcional

- o projeto passa a ter uma pagina publica de apresentacao pronta para uso comercial sem prometer modulos inexistentes;
- o conteudo de venda fica mais aderente ao posicionamento atual do produto, com foco em assistencia tecnica operacional e bancada.
