namespace JovemTech.BenchCollector;

internal sealed class CollectorPreferences
{
    public string? ErpBaseUrl { get; set; }
    public string? ErpLoginEmail { get; set; }
    public int IntervalMinutes { get; set; } = 15;
}
