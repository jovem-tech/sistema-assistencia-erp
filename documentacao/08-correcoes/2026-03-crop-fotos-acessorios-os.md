# Correção: Crop antes de anexar fotos de acessórios (OS)

## Contexto
Na tela `OS > Nova`, as fotos de acessórios eram anexadas diretamente após seleção do arquivo, sem etapa de corte.

## Correção aplicada
- Integrado o upload de fotos de acessórios ao modal de crop já existente no formulário.
- O sistema agora abre o corte antes da importação da imagem.
- Após confirmar o corte, a imagem é anexada ao acessório correspondente.
- Seleções múltiplas são processadas em fila, com corte individual para cada imagem.
- Adicionado botão de captura por câmera para acessórios, usando webcam/câmera do dispositivo quando disponível.

## Resultado esperado
- A foto do acessório só aparece na lista após confirmar `Finalizar Corte`.
- O usuário pode ajustar enquadramento e rotação antes de salvar.

## Arquivo impactado
- `app/Views/os/form.php`
