param(
  [switch]$NoStart,
  [int]$Port = 3000,
  [int]$WaitSeconds = 30
)

$ErrorActionPreference = "Stop"

$workspace = Split-Path -Parent $PSScriptRoot
$nextDir = Join-Path $workspace ".next"
$outLog = Join-Path $workspace "dev.out.log"
$errLog = Join-Path $workspace "dev.err.log"

function Stop-NodeProcessSafe {
  param(
    [int]$ProcessId,
    [string]$Reason
  )

  if ($ProcessId -le 0) {
    return
  }

  try {
    Stop-Process -Id $ProcessId -Force -ErrorAction Stop
    Write-Host "[recover-dev] processo encerrado ($Reason): $ProcessId"
  } catch {
    Write-Warning "[recover-dev] falha ao encerrar processo $ProcessId ($Reason): $($_.Exception.Message)"
  }
}

Write-Host "[recover-dev] workspace: $workspace"

$nodeTargets = Get-CimInstance Win32_Process -Filter "name='node.exe'" | Where-Object {
  $_.CommandLine -match "sistema-assistencia\\mobile-app" -or
  $_.CommandLine -match "scripts/dev-server\.cjs"
}

$nodeTargetIds = @()
if ($nodeTargets) {
  $nodeTargetIds = $nodeTargets | Select-Object -ExpandProperty ProcessId -Unique
  foreach ($procId in $nodeTargetIds) {
    Stop-NodeProcessSafe -ProcessId $procId -Reason "workspace"
  }
} else {
  Write-Host "[recover-dev] nenhum processo node do mobile-app encontrado."
}

$portNodeOwners = @()
try {
  $listeners = Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue
  if ($listeners) {
    $portPids = $listeners | Select-Object -ExpandProperty OwningProcess -Unique
    foreach ($procId in $portPids) {
      if ($procId -in $nodeTargetIds) {
        continue
      }
      $process = Get-Process -Id $procId -ErrorAction SilentlyContinue
      if ($process -and $process.ProcessName -eq "node") {
        $portNodeOwners += $procId
      }
    }
  }
} catch {
  Write-Warning "[recover-dev] nao foi possivel validar listeners da porta ${Port}: $($_.Exception.Message)"
}

if ($portNodeOwners.Count -gt 0) {
  foreach ($procId in ($portNodeOwners | Select-Object -Unique)) {
    Stop-NodeProcessSafe -ProcessId $procId -Reason "porta-$Port"
  }
}

$removedNext = $false
for ($attempt = 1; $attempt -le 3; $attempt++) {
  try {
    if (Test-Path $nextDir) {
      Remove-Item -LiteralPath $nextDir -Recurse -Force
      Write-Host "[recover-dev] cache .next removido."
    } else {
      Write-Host "[recover-dev] pasta .next nao encontrada."
    }
    $removedNext = $true
    break
  } catch {
    Write-Warning "[recover-dev] tentativa $attempt falhou ao limpar .next: $($_.Exception.Message)"
    Start-Sleep -Milliseconds 700
  }
}

if (-not $removedNext) {
  throw "[recover-dev] nao foi possivel limpar .next apos 3 tentativas."
}

if ($NoStart) {
  Write-Host "[recover-dev] finalizado sem reiniciar (NoStart)."
  exit 0
}

Write-Host "[recover-dev] iniciando npm run dev..."
if (Test-Path $outLog) { Remove-Item $outLog -Force }
if (Test-Path $errLog) { Remove-Item $errLog -Force }

$child = Start-Process -FilePath "cmd.exe" -ArgumentList "/c", "npm run dev" -WorkingDirectory $workspace -RedirectStandardOutput $outLog -RedirectStandardError $errLog -PassThru
Write-Host "[recover-dev] processo iniciado: $($child.Id)"

$healthy = $false
for ($i = 0; $i -lt $WaitSeconds; $i++) {
  Start-Sleep -Seconds 1
  try {
    $response = Invoke-WebRequest -UseBasicParsing -Uri "http://localhost:$Port/login" -TimeoutSec 5
    if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 500) {
      $healthy = $true
      break
    }
  } catch {
    # aguardando subida completa
  }
}

if ($healthy) {
  Write-Host "[recover-dev] servidor OK em http://localhost:$Port/login"
} else {
  Write-Warning "[recover-dev] servidor nao confirmou readiness em ${WaitSeconds}s."
  if (Test-Path $outLog) {
    Write-Host "[recover-dev] ultimas linhas dev.out.log:"
    Get-Content -Path $outLog -Tail 30
  }
  if (Test-Path $errLog) {
    Write-Host "[recover-dev] ultimas linhas dev.err.log:"
    Get-Content -Path $errLog -Tail 30
  }
}

Write-Host "[recover-dev] concluido. Recarregue o navegador com Ctrl+Shift+R."
