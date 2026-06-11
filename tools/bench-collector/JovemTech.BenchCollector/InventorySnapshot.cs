using System.Text.Json.Serialization;

namespace JovemTech.BenchCollector;

internal sealed class InventorySnapshot
{
    public string Hostname { get; set; } = string.Empty;
    public string SerialNumber { get; set; } = string.Empty;
    public string SerialSource { get; set; } = string.Empty;
    public string Manufacturer { get; set; } = string.Empty;
    public string Model { get; set; } = string.Empty;
    public string DeviceType { get; set; } = string.Empty;
    public string ChassisType { get; set; } = string.Empty;
    public string Motherboard { get; set; } = string.Empty;
    public string Chipset { get; set; } = string.Empty;
    public string BiosVersion { get; set; } = string.Empty;
    public string Cpu { get; set; } = string.Empty;
    public string Gpu { get; set; } = string.Empty;
    public decimal RamGb { get; set; }
    public string MemorySummary { get; set; } = string.Empty;
    public string StorageSummary { get; set; } = string.Empty;
    public List<StorageDevice> StorageDevices { get; set; } = [];
    public string WindowsCaption { get; set; } = string.Empty;
    public string WindowsVersion { get; set; } = string.Empty;
    public string WindowsBuild { get; set; } = string.Empty;
    public string CollectedAtUtc { get; set; } = string.Empty;

    [JsonIgnore]
    public string InstallationId { get; set; } = string.Empty;

    public string ToPrettySummary()
    {
        return string.Join(Environment.NewLine, new[]
        {
            $"Host: {Hostname}",
            $"Serie: {SerialNumber}" + (string.IsNullOrWhiteSpace(SerialSource) ? string.Empty : $" ({SerialSource})"),
            $"Fabricante: {Manufacturer}",
            $"Modelo: {Model}",
            $"Tipo: {DeviceType}",
            $"Chassi: {ChassisType}",
            $"Placa-mae: {Motherboard}",
            $"Chipset: {Chipset}",
            $"CPU: {Cpu}",
            $"RAM: {MemorySummary}",
            $"GPU: {Gpu}",
            $"Armazenamento: {StorageSummary}",
            $"Windows: {WindowsCaption} ({WindowsVersion} / build {WindowsBuild})",
        });
    }
}

internal sealed class StorageDevice
{
    public string Type { get; set; } = string.Empty;
    public string Model { get; set; } = string.Empty;
    public string SizeLabel { get; set; } = string.Empty;
}
