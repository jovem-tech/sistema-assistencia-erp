# AGENTS.md - Sistema de Assistencia Tecnica

## Regra Permanente: Documentacao Sempre Atualizada

Sempre que o usuario solicitar alteracoes, atualizacoes, correcoes ou melhorias no codigo do sistema, a IA deve obrigatoriamente:

1. Implementar a mudanca no codigo.
2. Atualizar toda a documentacao relevante em `documentacao/` para refletir exatamente o que mudou:
   - Manuais de usuario e administrador
   - Arquitetura tecnica
   - API
   - Banco de dados
   - Roadmap (se a mudanca impactar planejamento)
   - Historico de correcoes e novas implementacoes, quando aplicavel
3. Ajustar links de ajuda e mapeamentos de `openDocPage` caso novas paginas sejam criadas ou renomeadas.
4. Informar explicitamente quais arquivos de documentacao foram atualizados.

Esta regra se aplica a todas as futuras solicitacoes relacionadas ao codigo.

## Padroes Obrigatorios de UX/UI

Sempre que houver implementacao ou ajuste de interface no sistema:

1. Mensagens de aviso, alerta, confirmacao e erro devem usar SweetAlert2 (`Swal.fire`).
   - Evitar `alert()`, `confirm()` e `prompt()` nativos, exceto fallback tecnico temporario quando o SweetAlert2 estiver indisponivel.
2. Qualquer funcionalidade de upload/captura de fotos deve seguir o padrao ja implementado no projeto.
   - Reutilizar o fluxo atual de fotos (galeria + camera quando aplicavel, preview, remocao e corte/edicao antes de salvar).
   - Evitar criar fluxos paralelos de upload que quebrem a consistencia visual e funcional.
3. Padrao de fotos de equipamento deve ser reativo e sincronizado.
   - Atualizar miniaturas e foto principal imediatamente apos inserir, excluir ou definir principal.
   - Evitar cache visual: adicionar `?v=timestamp` nas URLs renderizadas quando houver mudanca.
   - Preferir endpoints que retornem `fotos` atualizadas para re-render imediato (ex.: salvar/atualizar/excluir).
   - Quando houver acao de definir principal, usar endpoint dedicado e refletir no card e no modal.

## Padrao Global de Responsividade (Ultra Compatibilidade)

Sempre que houver implementacao de interface nova ou manutencao de telas existentes:

1. Aplicar responsividade agressiva por padrao em **todas** as telas e componentes.
2. Cobrir, no minimo, os breakpoints:
   - `<= 430px` (smartphones gerais)
   - `<= 390px` (iPhones compactos)
   - `<= 360px` (androids pequenos)
   - `<= 320px` (ultra compacto)
3. Garantir obrigatoriamente:
   - nenhum corte horizontal da pagina;
   - cards, titulos e botoes sem truncamento visual;
   - tabelas legiveis em mobile (stack/card ou scroll controlado);
   - formularios sem estouro lateral;
   - modais usaveis em telas pequenas;
   - graficos com reflow/recalc ao trocar orientacao/dispositivo.
4. Novas tabelas devem aceitar `data-label` por coluna para stack mobile.
5. Toda entrega de UI deve incluir validacao em viewport mobile real no DevTools (320px e 360px, no minimo).
6. Esse padrao e obrigatorio para implementacoes futuras, nao opcional.

## Regra Operacional para Fotos e Modais

Sempre que a IA criar ou alterar qualquer fluxo de:
- upload de fotos
- captura por camera
- preview/lightbox
- recorte/edicao com Cropper
- galerias com miniaturas

ela deve obrigatoriamente seguir este checklist:

1. Comparar com um fluxo ja estavel antes de implementar.
   - Se existir uma tela do sistema onde o fluxo equivalente ja funcione corretamente, ela deve ser usada como referencia obrigatoria.
   - Evitar recriar a logica do zero se ja houver implementacao funcional em outro modulo.
2. Nao criar variacoes paralelas do sistema de fotos.
   - Reaproveitar os mesmos helpers, padroes de modal, fila de arquivos, preview e remocao.
   - Se precisar adaptar para outro contexto (OS, acessorios, estado fisico, equipamento), adaptar a mesma base.
3. Verificar contexto de modal e empilhamento visual.
   - Em qualquer modal de camera ou crop, verificar risco de `z-index`, `overflow`, `transform`, `stacking context` e `backdrop` preso.
   - Quando houver risco de clipping ou modal aberto invisivel, anexar o modal diretamente ao `document.body`.
   - Sempre limpar `modal-backdrop`, `modal-open` e estados presos do Bootstrap quando necessario.
4. Inicializacao defensiva.
   - Validar existencia de elementos DOM antes de abrir camera, cropper ou preview.
   - Se `navigator.mediaDevices.getUserMedia` nao existir ou falhar, avisar com `Swal.fire` e registrar erro tecnico no console.
   - Se `Cropper` nao carregar, usar fallback tecnico sem travar a tela.
5. Logs tecnicos obrigatorios.
   - Em falhas de camera, crop, preview, modal invisivel ou fallback, registrar `console.error` com prefixo claro do modulo.
   - Sempre incluir contexto suficiente para diagnostico: tipo do fluxo, estado do modal, display, classes e origem da imagem quando aplicavel.
6. Reatividade obrigatoria.
   - Inseriu foto: miniatura e preview principal atualizam na hora.
   - Excluiu foto: miniatura some na hora.
   - Alterou principal: card e modal refletem imediatamente.
   - Evitar refresh manual da pagina como dependencia para refletir mudancas.
7. Validacao minima antes de concluir.
   - Testar galeria
   - Testar camera
   - Testar crop
   - Testar exclusao
   - Testar atualizacao do preview principal
   - Testar se backdrop/modal nao deixam a tela travada

## Diretrizes para WhatsApp API e VPS

Sempre que houver intervenção no módulo de WhatsApp ou solicitações de Deploy:

1. **Preservar Bridge Node.js**: O gateway em `whatsapp-api/` é um serviço independente. Alterações no ERP PHP não devem quebrar a compatibilidade com os endpoints `/status` e `/create-message`.
2. **Segurança de Gateway**: Nunca remover ou fragilizar a validação de `X-Api-Token` e `X-ERP-Origin` no `server.js`.
3. **Consciência de Ambiente (OS)**:
   - **Windows (Desenvolvimento)**: Caminhos de arquivo podem usar `\`. O serviço é iniciado manualmente.
   - **Linux (Produção/VPS)**: Caminhos DEVEM usar `/`. O serviço DEVE ser gerenciado via **PM2**.
4. **Case Sensitivity**: No Linux, nomes de classes e arquivos devem ser idênticos. Sempre usar `CamelCase` para Controllers, Models e Services.
5. **Puppeteer Headless**: Em ambiente Linux VPS, o Puppeteer deve sempre rodar com as flags `--no-sandbox` e `--disable-setuid-sandbox`.
6. **Fallback de Provedor**: Manter a lógica de `MensageriaService` flexível para permitir alternância rápida entre Local Gateway, Menuia e Webhooks.
7. **Logs de Comunicação**: Falhas de envio de WhatsApp devem ser registradas tanto no log do PHP (`LogModel`) quanto no console/log do Node.js (`pm2 logs`).
8. **Consistência Visual WhatsApp**:
   - O status do gateway deve ser exibido via badge interativo (clicável) no topo da seção de configurações.
   - Operações assíncronas (start, restart, logout, test) devem obrigatoriamente mostrar spinners nos botões e desabilitá-los durante a execução.
   - O estado "Conectado" deve ser acompanhado da ilustração 3D de sucesso em `assets/img/sistema/whatsapp_connected_success.png`.
   - Sempre oferecer o botão de "Iniciar Servidor" (`/local-start`) quando o status for inacessível.

## Prompt recomendado para solicitacoes de modificacao

Para garantir a cobertura documental total, use sempre a seguinte introducao antes de detalhar o que deseja:

```
Documentacao obrigatoria: implemente a alteracao, execute as migracoes e atualize todos os manuais, guias de banco de dados e notas de implementacao afetados, incluindo os links de ajuda.
```

Esse texto sinaliza que a solicitacao exige uma mudanca no codigo acompanhada de atualizacao completa da pasta `documentacao/` e facilita nossa rotina de auditoria.
