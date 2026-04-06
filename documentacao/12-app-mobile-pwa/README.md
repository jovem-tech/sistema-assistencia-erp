# App Mobile PWA - Documentacao Oficial

Atualizado em 05/04/2026.

## Objetivo

Este hub concentra a documentacao exclusiva do aplicativo mobile/PWA da Assistencia, separado da documentacao geral do ERP web, mas integrado ao mesmo backend, ao mesmo banco e ao mesmo padrao operacional.

## Estrutura

| Pasta | Conteudo |
|---|---|
| `00-visao-geral` | escopo, objetivo, stack e compatibilidade |
| `01-manual-do-usuario` | operacao mobile por fluxo |
| `02-manual-administrador` | configuracao, publicacao e suporte |
| `03-arquitetura-tecnica` | arquitetura, pastas, midias e versionamento |
| `04-banco-de-dados` | tabelas exclusivas mobile e tabelas compartilhadas |
| `05-api-mobile` | contratos e rotas da API v1 usadas pelo app |
| `06-design-system` | fundamentos visuais e componentes oficiais |
| `07-padroes-de-desenvolvimento` | regras obrigatorias para novas features |
| `08-skills-e-automacoes` | skills, prompts e automacoes futuras |
| `09-versionamento-e-releases` | politica, historico e changelog do app |
| `10-deploy-e-operacao` | local, PM2, HTTPS e operacao |
| `11-roadmap` | fases planejadas do app |

## Leitura recomendada

- Visao geral do app: `00-visao-geral/sobre-o-app.md`
- Escopo oficial: `00-visao-geral/objetivos-e-escopo.md`
- Fluxo de autenticacao: `03-arquitetura-tecnica/fluxo-de-autenticacao.md`
- Fluxo de conversas e tempo real: `03-arquitetura-tecnica/fluxo-de-conversas-e-tempo-real.md`
- Fluxo de notificacoes: `03-arquitetura-tecnica/fluxo-de-notificacoes.md`
- Manual de listagem de OS: `01-manual-do-usuario/os-listagem.md`
- Manual de abertura da OS: `01-manual-do-usuario/os-abertura-completa.md`
- Manual de detalhe e edicao da OS: `01-manual-do-usuario/os-detalhe-e-edicao.md`
- Design system do app: `06-design-system/fundamentos.md`
- Tokens visuais: `06-design-system/tokens-visuais.md`
- Padrao de fotos e crop: `06-design-system/padroes-de-fotos-e-crop.md`
- Estados e feedback: `06-design-system/estados-e-feedback.md`
- Acessibilidade: `06-design-system/acessibilidade.md`
- Convencoes tecnicas: `07-padroes-de-desenvolvimento/convencoes-react-next.md`
- Padrao de estado e API: `07-padroes-de-desenvolvimento/padroes-de-estado-e-api.md`
- Skills oficiais do app: `08-skills-e-automacoes/skills-oficiais-do-app.md`
- Politica de versoes: `09-versionamento-e-releases/politica-de-versoes.md`
- Historico do app: `09-versionamento-e-releases/historico-de-versoes.md`

## Relacao com o ERP

- O app usa o mesmo banco do ERP.
- O app usa o backend CodeIgniter 4 do ERP.
- O app nao duplica regras de negocio do ERP sem necessidade.
- O app possui documentacao, governanca e versionamento proprios.

## Estado oficial atual

- Linha oficial do app: `0.x`
- Versao atual do app: `0.4.8`
- Versao minima do ERP compativel: `2.11.5`
- Skills reais do app: `.agents/skills/mobile-pwa*`
- Versao visivel no app: login + navegacao autenticada
- Selecao de equipamento na OS: card rico com foto, tipo, marca, modelo, cor, numero de serie e IMEI
- Selecao de cliente na OS: card resumido clicavel para troca rapida via seletor inteligente
