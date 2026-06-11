using System.Text.Json;

namespace JovemTech.BenchCollector;

internal sealed class LocalConfigStore
{
    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase,
        WriteIndented = true,
    };

    private readonly string _filePath;

    public LocalConfigStore()
    {
        var baseDir = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "JovemTech",
            "BenchCollector");

        Directory.CreateDirectory(baseDir);
        _filePath = Path.Combine(baseDir, "config.json");
    }

    public async Task<CollectorPreferences?> LoadAsync(CancellationToken cancellationToken)
    {
        if (!File.Exists(_filePath))
        {
            return null;
        }

        await using var stream = File.OpenRead(_filePath);
        return await JsonSerializer.DeserializeAsync<CollectorPreferences>(stream, JsonOptions, cancellationToken);
    }

    public async Task SaveAsync(CollectorPreferences preferences, CancellationToken cancellationToken)
    {
        await using var stream = File.Create(_filePath);
        await JsonSerializer.SerializeAsync(stream, preferences, JsonOptions, cancellationToken);
    }
}
