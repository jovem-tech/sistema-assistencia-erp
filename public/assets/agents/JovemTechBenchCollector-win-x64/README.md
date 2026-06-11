# Coletor de Bancada Jovem Tech

Projeto fonte do coletor portatil para `Desktop` e `Notebook`.

## Objetivo

- rodar sem instalacao no computador do cliente;
- coletar inventario tecnico visivel quando a maquina ligar;
- provisionar o agente pela `OS`;
- enviar o `check-in` para o ERP;
- complementar automaticamente `placa-mae`, `chipset`, `processador`, `memoria`, `GPU`, `armazenamento` e dados do Windows.

## Publicacao

Use o script:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\agents\publish-bench-collector.ps1
```

Saidas geradas:

- `public/assets/agents/bench-collector/win-x64/JovemTechBenchCollector.exe`
- `public/assets/agents/JovemTechBenchCollector-win-x64.zip`

## Uso rapido do executavel

Modo interativo:

```powershell
.\JovemTechBenchCollector.exe
```

Modo direto:

```powershell
.\JovemTechBenchCollector.exe --erp-base-url "https://erp.exemplo.com.br" --warranty-os-number "OS12345" --erp-login-email "tecnico@empresa.com"
```

Teste sem enviar ao ERP:

```powershell
.\JovemTechBenchCollector.exe --dry-run
```

## Observacoes

- o coletor foi pensado para `Windows`;
- por padrao ele executa uma coleta unica, ideal para bancada;
- use `--continuous` apenas quando quiser repetir `check-ins`;
- o script `public/assets/agents/jovemtec-monitor-agent.ps1` continua disponivel como fallback tecnico.
