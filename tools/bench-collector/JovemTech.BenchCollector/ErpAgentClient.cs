using System.Net.Http.Json;
using System.Text.Json;

namespace JovemTech.BenchCollector;

internal sealed class ErpAgentClient
{
    private static readonly JsonSerializerOptions SerializerOptions = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase,
        WriteIndented = true,
    };

    private readonly HttpClient _httpClient;

    public ErpAgentClient(string baseUrl)
    {
        _httpClient = new HttpClient
        {
            BaseAddress = new Uri(baseUrl.TrimEnd('/') + "/", UriKind.Absolute),
            Timeout = TimeSpan.FromSeconds(45),
        };
    }

    public async Task<BootstrapResponse> BootstrapAsync(BenchCollectorOptions options, InventorySnapshot snapshot, CancellationToken cancellationToken)
    {
        var payload = new
        {
            installationId = snapshot.InstallationId,
            warrantyOsNumber = options.WarrantyOsNumber,
            warrantyPublicUrl = options.WarrantyPublicUrl,
            erpLoginEmail = options.ErpLoginEmail,
            hostname = snapshot.Hostname,
            serialNumber = snapshot.SerialNumber,
            manufacturer = snapshot.Manufacturer,
            model = snapshot.Model,
            deviceType = snapshot.DeviceType,
            chassisType = snapshot.ChassisType,
            motherboard = snapshot.Motherboard,
            chipset = snapshot.Chipset,
            biosVersion = snapshot.BiosVersion,
            cpu = snapshot.Cpu,
            gpu = snapshot.Gpu,
            ramGb = snapshot.RamGb,
            storageSummary = snapshot.StorageSummary,
            windowsCaption = snapshot.WindowsCaption,
            windowsVersion = snapshot.WindowsVersion,
            windowsBuild = snapshot.WindowsBuild,
        };

        using var response = await _httpClient.PostAsJsonAsync("api/v1/agents/bootstrap-from-warranty", payload, SerializerOptions, cancellationToken);
        return await ReadResponseAsync<BootstrapResponse>(response, cancellationToken, "bootstrap");
    }

    public async Task<CheckInResponse> CheckInAsync(
        string checkInEndpoint,
        string apiToken,
        string agentId,
        InventorySnapshot snapshot,
        CancellationToken cancellationToken)
    {
        using var request = new HttpRequestMessage(HttpMethod.Post, NormalizeEndpoint(checkInEndpoint))
        {
            Content = JsonContent.Create(new
            {
                agentId,
                installationId = snapshot.InstallationId,
                hostname = snapshot.Hostname,
                serialNumber = snapshot.SerialNumber,
                manufacturer = snapshot.Manufacturer,
                model = snapshot.Model,
                deviceType = snapshot.DeviceType,
                chassisType = snapshot.ChassisType,
                motherboard = snapshot.Motherboard,
                chipset = snapshot.Chipset,
                biosVersion = snapshot.BiosVersion,
                cpu = snapshot.Cpu,
                gpu = snapshot.Gpu,
                ramGb = snapshot.RamGb,
                memorySummary = snapshot.MemorySummary,
                storageSummary = snapshot.StorageSummary,
                storageDevices = snapshot.StorageDevices,
                windowsCaption = snapshot.WindowsCaption,
                windowsVersion = snapshot.WindowsVersion,
                windowsBuild = snapshot.WindowsBuild,
                collectedAtUtc = snapshot.CollectedAtUtc,
            }, options: SerializerOptions),
        };

        request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", apiToken);

        using var response = await _httpClient.SendAsync(request, cancellationToken);
        return await ReadResponseAsync<CheckInResponse>(response, cancellationToken, "check-in");
    }

    public string SerializeSnapshot(InventorySnapshot snapshot)
    {
        return JsonSerializer.Serialize(snapshot, SerializerOptions);
    }

    private static async Task<T> ReadResponseAsync<T>(HttpResponseMessage response, CancellationToken cancellationToken, string operation)
    {
        var content = await response.Content.ReadAsStringAsync(cancellationToken);

        if (!response.IsSuccessStatusCode)
        {
            throw new InvalidOperationException(
                $"Falha no {operation} ({(int)response.StatusCode} {response.ReasonPhrase}). Resposta: {content}");
        }

        var data = JsonSerializer.Deserialize<T>(content, SerializerOptions);
        if (data is null)
        {
            throw new InvalidOperationException($"O ERP retornou uma resposta vazia ou invalida no {operation}.");
        }

        return data;
    }

    private static string NormalizeEndpoint(string endpoint)
    {
        endpoint = (endpoint ?? string.Empty).Trim();
        return endpoint.StartsWith("/", StringComparison.Ordinal) ? endpoint[1..] : endpoint;
    }
}

internal sealed class BootstrapResponse
{
    public string AgentId { get; set; } = string.Empty;
    public string AgentLabel { get; set; } = string.Empty;
    public string ApiToken { get; set; } = string.Empty;
    public string CheckInEndpoint { get; set; } = "api/v1/agents/check-in";
    public int InventoryIntervalMinutes { get; set; }
}

internal sealed class CheckInResponse
{
    public string AgentId { get; set; } = string.Empty;
    public string ReceivedAt { get; set; } = string.Empty;
    public int NextCheckInMinutes { get; set; }
}
