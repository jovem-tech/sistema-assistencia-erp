param(
    [string] $Channel = '8.0',
    [string] $RuntimeIdentifier = 'win-x64',
    [string] $Configuration = 'Release'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$projectPath = Join-Path $repoRoot 'tools\bench-collector\JovemTech.BenchCollector\JovemTech.BenchCollector.csproj'
$publishRoot = Join-Path $repoRoot "public\assets\agents\bench-collector\$RuntimeIdentifier"
$zipPath = Join-Path $repoRoot "public\assets\agents\JovemTechBenchCollector-$RuntimeIdentifier.zip"
$readmeSource = Join-Path $repoRoot 'tools\bench-collector\README.md'
$localDotnetDir = Join-Path $repoRoot '.tools\dotnet-sdk'
$localDotnet = Join-Path $localDotnetDir 'dotnet.exe'
$installScript = Join-Path $env:TEMP 'dotnet-install-jovemtech.ps1'

function Resolve-DotnetExecutable {
    $globalDotnet = Get-Command dotnet -ErrorAction SilentlyContinue
    if ($globalDotnet -and (dotnet --list-sdks | Measure-Object).Count -gt 0) {
        return $globalDotnet.Source
    }

    if (Test-Path $localDotnet) {
        return $localDotnet
    }

    Write-Host 'Instalando .NET SDK local para publicar o coletor...'
    Invoke-WebRequest -Uri 'https://dot.net/v1/dotnet-install.ps1' -OutFile $installScript
    & powershell -ExecutionPolicy Bypass -File $installScript -Channel $Channel -InstallDir $localDotnetDir -Quality GA | Out-Host

    if (!(Test-Path $localDotnet)) {
        throw 'Nao foi possivel instalar o .NET SDK local.'
    }

    return $localDotnet
}

$dotnetExe = Resolve-DotnetExecutable

if (Test-Path $publishRoot) {
    Remove-Item -LiteralPath $publishRoot -Recurse -Force
}

New-Item -ItemType Directory -Path $publishRoot -Force | Out-Null

& $dotnetExe publish $projectPath `
    -c $Configuration `
    -r $RuntimeIdentifier `
    --self-contained true `
    -p:PublishSingleFile=true `
    -p:PublishTrimmed=false `
    -p:IncludeNativeLibrariesForSelfExtract=true `
    -o $publishRoot

Copy-Item -LiteralPath $readmeSource -Destination (Join-Path $publishRoot 'README.md') -Force
Get-ChildItem -Path $publishRoot -Filter *.pdb -File -ErrorAction SilentlyContinue | Remove-Item -Force

if (Test-Path $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}

Compress-Archive -Path (Join-Path $publishRoot '*') -DestinationPath $zipPath -Force

Write-Host ''
Write-Host 'Coletor publicado com sucesso:'
Write-Host " - Pasta: $publishRoot"
Write-Host " - ZIP:   $zipPath"
