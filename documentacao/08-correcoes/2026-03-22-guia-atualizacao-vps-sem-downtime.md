# Entrega de Documentacao - Guia de atualizacao de VPS sem downtime

Data: 22/03/2026  
Tipo: documentacao operacional de infraestrutura

## Objetivo

Formalizar um procedimento oficial para atualizacao de VPS (incluindo mudanca de versao Ubuntu) com risco controlado e sem indisponibilidade relevante.

## Entregas realizadas

- Novo guia dedicado:
  - `10-deploy/atualizacao-vps-sem-downtime.md`
- Atualizacao de indice principal:
  - `README.md` (dentro de `documentacao/`)
- Atualizacao dos guias de deploy com referencia ao novo material:
  - `10-deploy/deploy-producao.md`
  - `10-deploy/linux-vps-deployment.md`
- Atualizacao do mapeamento de ajuda (`openDocPage`):
  - `public/assets/js/scripts.js`

## Escopo tecnico incluido no novo guia

- Estrategia Blue/Green para contingencia.
- Fases de preparacao, validacao, virada e rollback.
- Fluxo recomendado para migrar para Ubuntu novo sem atualizar producao ativa por cima.
- Checklists antes/durante/depois da janela.
- Pontos de consistencia dev x producao.

## Resultado esperado

- Reducao de risco em atualizacoes de SO.
- Procedimento padrao para equipe executar mudancas de infraestrutura com previsibilidade.
- Melhor tempo de recuperacao em caso de falha de virada.

