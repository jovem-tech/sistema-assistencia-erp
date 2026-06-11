using System.Text.Json;

namespace JovemTech.BenchCollector;

internal sealed class SnapshotFileStore
{
    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase,
        WriteIndented = true,
    };

    private static readonly string RootDirectory = @"C:\JovemTechBenchCollector";
    private static readonly string RootSnapshotPath = Path.Combine(RootDirectory, "last-snapshot.json");

    public async Task<string> SaveAsync(
        InventorySnapshot snapshot,
        BenchCollectorOptions options,
        BootstrapResponse? bootstrap,
        CancellationToken cancellationToken)
    {
        var collectedAt = DateTimeOffset.TryParse(snapshot.CollectedAtUtc, out var collectedAtValue)
            ? collectedAtValue
            : DateTimeOffset.UtcNow;
        var savedAt = DateTimeOffset.UtcNow;

        var payload = new
        {
            source = "JovemTechBenchCollector",
            documentType = "inventory_snapshot",
            erpBaseUrl = options.ErpBaseUrl,
            warrantyOsNumber = options.WarrantyOsNumber,
            warrantyPublicUrl = options.WarrantyPublicUrl,
            erpLoginEmail = options.ErpLoginEmail,
            installationId = snapshot.InstallationId,
            agentId = bootstrap?.AgentId ?? string.Empty,
            agentLabel = bootstrap?.AgentLabel ?? string.Empty,
            collectedAtUtc = collectedAt.ToUniversalTime().ToString("O"),
            collectedAtLocal = collectedAt.ToLocalTime().ToString("dd/MM/yyyy HH:mm:ss"),
            savedAtUtc = savedAt.ToString("O"),
            savedAtLocal = savedAt.ToLocalTime().ToString("dd/MM/yyyy HH:mm:ss"),
            snapshot = snapshot,
        };

        var snapshotPath = ResolveSnapshotPath(options);
        Directory.CreateDirectory(RootDirectory);
        await using var stream = File.Create(snapshotPath);
        await JsonSerializer.SerializeAsync(stream, payload, JsonOptions, cancellationToken);
        return snapshotPath;
    }

    public string GetDefaultPath(BenchCollectorOptions? options = null)
    {
        return ResolveSnapshotPath(options);
    }

    private static string ResolveSnapshotPath(BenchCollectorOptions? options)
    {
        var numeroOs = NormalizeToken(options?.WarrantyOsNumber ?? string.Empty);
        if (numeroOs == string.Empty)
        {
            return RootSnapshotPath;
        }

        return Path.Combine(RootDirectory, $"inf_{numeroOs}.json");
    }

    private static string NormalizeToken(string value)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return string.Empty;
        }

        var buffer = new List<char>(value.Length);
        foreach (var ch in value.Trim().ToLowerInvariant())
        {
            if ((ch >= 'a' && ch <= 'z') || (ch >= '0' && ch <= '9'))
            {
                buffer.Add(ch);
            }
        }

        return buffer.Count > 0 ? new string(buffer.ToArray()) : string.Empty;
    }
}
