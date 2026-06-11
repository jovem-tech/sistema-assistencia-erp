param(
    [Parameter(Mandatory = $true)]
    [string] $ErpBaseUrl,

    [string] $WarrantyOsNumber = '',

    [string] $WarrantyPublicUrl = '',

    [Parameter(Mandatory = $true)]
    [string] $ErpLoginEmail,

    [string] $InstallationId = '',

    [int] $IntervalMinutes = 15,

    [switch] $RunOnce
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-AgentInfo {
    param([string] $Message)
    Write-Host ("[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Message)
}

function Normalize-BaseUrl {
    param([string] $Value)
    return ($Value.TrimEnd('/'))
}

function Test-InvalidSerialNumber {
    param([string] $Value)

    $normalized = "$Value".Trim()
    if ($normalized -eq '') {
        return $true
    }

    $invalidTokens = @(
        'default string',
        'system serial number',
        'to be filled by o.e.m.',
        'to be filled by oem',
        'not applicable',
        'not available',
        'unknown',
        'none',
        'null',
        'oem'
    )

    foreach ($token in $invalidTokens) {
        if ($normalized.Equals($token, [System.StringComparison]::OrdinalIgnoreCase)) {
            return $true
        }
    }

    return $normalized -match '^0+$'
}

function Format-MacAddress {
    param([byte[]] $Bytes)

    if (-not $Bytes -or $Bytes.Count -lt 6) {
        return ''
    }

    return (($Bytes | ForEach-Object { $_.ToString('X2') }) -join '-')
}

function Get-PreferredMacAddress {
    try {
        $interfaces = [System.Net.NetworkInformation.NetworkInterface]::GetAllNetworkInterfaces() |
            Where-Object {
                $_.NetworkInterfaceType -ne [System.Net.NetworkInformation.NetworkInterfaceType]::Loopback -and
                $_.NetworkInterfaceType -ne [System.Net.NetworkInformation.NetworkInterfaceType]::Tunnel -and
                $_.OperationalStatus -ne [System.Net.NetworkInformation.OperationalStatus]::Unknown
            } |
            ForEach-Object {
                [PSCustomObject]@{
                    Interface = $_
                    Address = (Format-MacAddress -Bytes $_.GetPhysicalAddress().GetAddressBytes())
                    IsUp = $_.OperationalStatus -eq [System.Net.NetworkInformation.OperationalStatus]::Up
                    Priority = switch ($_.NetworkInterfaceType) {
                        ([System.Net.NetworkInformation.NetworkInterfaceType]::Ethernet) { 0; break }
                        ([System.Net.NetworkInformation.NetworkInterfaceType]::Wireless80211) { 1; break }
                        default { 2; break }
                    }
                }
            } |
            Where-Object { $_.Address -ne '' } |
            Sort-Object @{ Expression = 'IsUp'; Descending = $true }, @{ Expression = 'Priority'; Ascending = $true }

        return ($interfaces | Select-Object -ExpandProperty Address -First 1)
    } catch {
        Write-AgentInfo "Falha ao resolver MAC para fallback de serie: $($_.Exception.Message)"
        return ''
    }
}

function Resolve-InventorySerialNumber {
    param([string] $BiosSerialNumber)

    $serialNumber = "$BiosSerialNumber".Trim()
    if (-not (Test-InvalidSerialNumber -Value $serialNumber)) {
        return [ordered]@{
            value = $serialNumber
            source = 'bios'
        }
    }

    $macAddress = Get-PreferredMacAddress
    if ($macAddress -ne '') {
        return [ordered]@{
            value = $macAddress
            source = 'mac'
        }
    }

    return [ordered]@{
        value = $serialNumber
        source = ''
    }
}

function Get-ChassisLabel {
    param([int[]] $ChassisTypes)

    $desktopCodes = @{
        3  = 'Desktop'
        4  = 'Low Profile Desktop'
        5  = 'Pizza Box'
        6  = 'Mini Tower'
        7  = 'Tower'
        15 = 'Space Saving'
        16 = 'Lunch Box'
        23 = 'Rack Mount'
        24 = 'Sealed Case'
        34 = 'Mini PC'
        35 = 'Stick PC'
    }

    $notebookCodes = @{
        8  = 'Portable'
        9  = 'Laptop'
        10 = 'Notebook'
        14 = 'Sub Notebook'
        30 = 'Tablet'
        31 = 'Convertible'
        32 = 'Detachable'
    }

    foreach ($code in $ChassisTypes) {
        if ($desktopCodes.ContainsKey($code)) {
            return $desktopCodes[$code]
        }
    }

    foreach ($code in $ChassisTypes) {
        if ($notebookCodes.ContainsKey($code)) {
            return $notebookCodes[$code]
        }
    }

    return ''
}

function Get-DeviceType {
    param(
        [string] $Model,
        [string] $ChassisLabel
    )

    $candidate = ($Model + ' ' + $ChassisLabel).ToLowerInvariant()
    if ($candidate -match 'notebook|laptop|portable|sub notebook|tablet|convertible|detachable') {
        return 'notebook'
    }

    return 'desktop'
}

function Get-ChipsetCandidate {
    param([string] $BoardText)

    $patterns = @(
        'H[0-9]{3}',
        'B[0-9]{3}',
        'Z[0-9]{3}',
        'X[0-9]{3}',
        'A[0-9]{3}',
        'Q[0-9]{3}',
        'C[0-9]{3}',
        'TRX[0-9]{2}',
        'X[45]70',
        'B[45]50',
        'A[35]20'
    )

    foreach ($pattern in $patterns) {
        $match = [regex]::Match($BoardText.ToUpperInvariant(), $pattern)
        if ($match.Success) {
            return $match.Value
        }
    }

    return ''
}

function Format-SizeLabel {
    param([double] $Bytes)

    if ($Bytes -ge 1TB) {
        return ('{0:N0}TB' -f ($Bytes / 1TB)).Replace(',', '.')
    }
    if ($Bytes -ge 1GB) {
        return ('{0:N0}GB' -f ($Bytes / 1GB)).Replace(',', '.')
    }

    return ('{0:N0}MB' -f ($Bytes / 1MB)).Replace(',', '.')
}

function Get-StorageInventory {
    $items = @()
    $seen = @{}

    try {
        $drives = Get-CimInstance Win32_DiskDrive | Where-Object { $_.Size -gt 0 }
        foreach ($drive in $drives) {
            $label = @(
                ($drive.MediaType | ForEach-Object { "$_".Trim() }),
                ($drive.Model | ForEach-Object { "$_".Trim() }),
                (Format-SizeLabel -Bytes ([double] $drive.Size))
            ) | Where-Object { $_ -ne '' }

            $normalized = ($label -join ' ').Trim()
            if ($normalized -eq '' -or $seen.ContainsKey($normalized)) {
                continue
            }

            $seen[$normalized] = $true
            $items += [ordered]@{
                type = ("$($drive.MediaType)".Trim())
                model = ("$($drive.Model)".Trim())
                sizeLabel = (Format-SizeLabel -Bytes ([double] $drive.Size))
            }
        }
    } catch {
        Write-AgentInfo "Falha ao coletar armazenamento: $($_.Exception.Message)"
    }

    return ,$items
}

function Get-InventorySnapshot {
    $computerSystem = Get-CimInstance Win32_ComputerSystem
    $bios = Get-CimInstance Win32_BIOS
    $baseBoard = Get-CimInstance Win32_BaseBoard
    $processor = Get-CimInstance Win32_Processor | Select-Object -First 1
    $operatingSystem = Get-CimInstance Win32_OperatingSystem
    $enclosure = Get-CimInstance Win32_SystemEnclosure
    $videoControllers = Get-CimInstance Win32_VideoController | Where-Object { "$($_.Name)".Trim() -ne '' }

    $storageDevices = Get-StorageInventory
    $storageSummary = ($storageDevices | ForEach-Object {
        @($_.type, $_.model, $_.sizeLabel) -join ' '
    } | Where-Object { $_.Trim() -ne '' } | Select-Object -Unique) -join ' | '

    $gpuSummary = ($videoControllers | ForEach-Object { "$($_.Name)".Trim() } | Select-Object -Unique) -join ' | '
    $chassisTypes = @($enclosure.ChassisTypes | ForEach-Object { [int] $_ })
    $chassisLabel = Get-ChassisLabel -ChassisTypes $chassisTypes

    $boardParts = @(
        "$($baseBoard.Manufacturer)".Trim(),
        "$($baseBoard.Product)".Trim()
    ) | Where-Object { $_ -ne '' }
    $motherboard = ($boardParts -join ' ').Trim()

    $deviceModel = "$($computerSystem.Model)".Trim()
    $deviceType = Get-DeviceType -Model $deviceModel -ChassisLabel $chassisLabel
    $serialResolution = Resolve-InventorySerialNumber -BiosSerialNumber "$($bios.SerialNumber)"

    $ramGb = [math]::Round(([double] $computerSystem.TotalPhysicalMemory / 1GB), 2)
    $chipset = Get-ChipsetCandidate -BoardText ($motherboard + ' ' + $deviceModel)

    return [ordered]@{
        hostname = $env:COMPUTERNAME
        serialNumber = "$($serialResolution.value)".Trim()
        serialSource = "$($serialResolution.source)".Trim()
        manufacturer = "$($computerSystem.Manufacturer)".Trim()
        model = $deviceModel
        deviceType = $deviceType
        chassisType = $chassisLabel
        motherboard = $motherboard
        chipset = $chipset
        biosVersion = "$($bios.SMBIOSBIOSVersion)".Trim()
        cpu = "$($processor.Name)".Trim()
        gpu = $gpuSummary
        ramGb = $ramGb
        memorySummary = ("{0:N0} GB" -f $ramGb).Replace(',', '.')
        storageSummary = $storageSummary
        storageDevices = $storageDevices
        windowsCaption = "$($operatingSystem.Caption)".Trim()
        windowsVersion = "$($operatingSystem.Version)".Trim()
        windowsBuild = "$($operatingSystem.BuildNumber)".Trim()
        collectedAtUtc = (Get-Date).ToUniversalTime().ToString('o')
    }
}

function Resolve-InstallationId {
    param([hashtable] $Snapshot)

    if ($InstallationId.Trim() -ne '') {
        return $InstallationId.Trim()
    }

    $raw = @(
        $Snapshot.hostname,
        $Snapshot.serialNumber,
        $Snapshot.model
    ) -join '-'

    $normalized = ($raw -replace '[^a-zA-Z0-9\-]', '-').Trim('-')
    if ($normalized -eq '') {
        $normalized = 'jt-agent-' + [guid]::NewGuid().ToString('N')
    }

    return $normalized.ToLowerInvariant()
}

function Invoke-AgentBootstrap {
    param(
        [string] $BaseUrl,
        [hashtable] $Snapshot
    )

    $payload = [ordered]@{
        installationId = (Resolve-InstallationId -Snapshot $Snapshot)
        warrantyOsNumber = $WarrantyOsNumber.Trim()
        warrantyPublicUrl = $WarrantyPublicUrl.Trim()
        erpLoginEmail = $ErpLoginEmail.Trim().ToLowerInvariant()
        hostname = $Snapshot.hostname
        serialNumber = $Snapshot.serialNumber
        manufacturer = $Snapshot.manufacturer
        model = $Snapshot.model
        deviceType = $Snapshot.deviceType
        chassisType = $Snapshot.chassisType
        motherboard = $Snapshot.motherboard
        chipset = $Snapshot.chipset
        biosVersion = $Snapshot.biosVersion
        cpu = $Snapshot.cpu
        gpu = $Snapshot.gpu
        ramGb = $Snapshot.ramGb
        storageSummary = $Snapshot.storageSummary
        windowsCaption = $Snapshot.windowsCaption
        windowsVersion = $Snapshot.windowsVersion
        windowsBuild = $Snapshot.windowsBuild
    }

    return Invoke-RestMethod `
        -Method Post `
        -Uri ($BaseUrl + '/api/v1/agents/bootstrap-from-warranty') `
        -ContentType 'application/json' `
        -Body ($payload | ConvertTo-Json -Depth 6)
}

function Invoke-AgentCheckIn {
    param(
        [string] $BaseUrl,
        [string] $ApiToken,
        [string] $AgentId,
        [hashtable] $Snapshot
    )

    $payload = [ordered]@{
        agentId = $AgentId
        installationId = (Resolve-InstallationId -Snapshot $Snapshot)
        hostname = $Snapshot.hostname
        serialNumber = $Snapshot.serialNumber
        manufacturer = $Snapshot.manufacturer
        model = $Snapshot.model
        deviceType = $Snapshot.deviceType
        chassisType = $Snapshot.chassisType
        motherboard = $Snapshot.motherboard
        chipset = $Snapshot.chipset
        biosVersion = $Snapshot.biosVersion
        cpu = $Snapshot.cpu
        gpu = $Snapshot.gpu
        ramGb = $Snapshot.ramGb
        memorySummary = $Snapshot.memorySummary
        storageSummary = $Snapshot.storageSummary
        storageDevices = $Snapshot.storageDevices
        windowsCaption = $Snapshot.windowsCaption
        windowsVersion = $Snapshot.windowsVersion
        windowsBuild = $Snapshot.windowsBuild
        collectedAtUtc = $Snapshot.collectedAtUtc
    }

    return Invoke-RestMethod `
        -Method Post `
        -Uri ($BaseUrl + '/api/v1/agents/check-in') `
        -Headers @{ Authorization = ('Bearer ' + $ApiToken) } `
        -ContentType 'application/json' `
        -Body ($payload | ConvertTo-Json -Depth 8)
}

$baseUrl = Normalize-BaseUrl -Value $ErpBaseUrl
if ($WarrantyOsNumber.Trim() -eq '' -and $WarrantyPublicUrl.Trim() -eq '') {
    throw 'Informe WarrantyOsNumber ou WarrantyPublicUrl.'
}

Write-AgentInfo 'Coletando inventario inicial da maquina...'
$initialSnapshot = Get-InventorySnapshot

Write-AgentInfo 'Provisionando agente no ERP...'
$bootstrap = Invoke-AgentBootstrap -BaseUrl $baseUrl -Snapshot $initialSnapshot
$agentId = "$($bootstrap.AgentId)".Trim()
$apiToken = "$($bootstrap.ApiToken)".Trim()

if ($agentId -eq '' -or $apiToken -eq '') {
    throw 'O ERP nao retornou AgentId/ApiToken validos para o bootstrap.'
}

$effectiveInterval = [math]::Max(1, [int] ($bootstrap.InventoryIntervalMinutes | ForEach-Object { $_ } | Select-Object -First 1))
if ($IntervalMinutes -gt 0) {
    $effectiveInterval = $IntervalMinutes
}

Write-AgentInfo ("Agente provisionado com sucesso. AgentId: {0}" -f $agentId)

do {
    $snapshot = Get-InventorySnapshot
    Write-AgentInfo 'Enviando check-in de inventario...'
    $result = Invoke-AgentCheckIn -BaseUrl $baseUrl -ApiToken $apiToken -AgentId $agentId -Snapshot $snapshot
    Write-AgentInfo ("Check-in confirmado. Proximo envio em {0} minuto(s)." -f $effectiveInterval)

    if ($RunOnce.IsPresent) {
        break
    }

    Start-Sleep -Seconds ($effectiveInterval * 60)
} while ($true)
