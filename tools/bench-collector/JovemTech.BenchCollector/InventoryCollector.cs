using System.Globalization;
using System.Management;
using System.Net.NetworkInformation;
using System.Text.RegularExpressions;

namespace JovemTech.BenchCollector;

internal sealed class InventoryCollector
{
    public InventorySnapshot Collect()
    {
        var computerSystem = QueryFirst("SELECT Manufacturer, Model, TotalPhysicalMemory FROM Win32_ComputerSystem");
        var bios = QueryFirst("SELECT SerialNumber, SMBIOSBIOSVersion FROM Win32_BIOS");
        var baseBoard = QueryFirst("SELECT Manufacturer, Product FROM Win32_BaseBoard");
        var processor = QueryFirst("SELECT Name FROM Win32_Processor");
        var operatingSystem = QueryFirst("SELECT Caption, Version, BuildNumber FROM Win32_OperatingSystem");
        var enclosure = QueryFirst("SELECT ChassisTypes FROM Win32_SystemEnclosure");
        var videoControllers = QueryMany("SELECT Name FROM Win32_VideoController");
        var storageDevices = GetStorageInventory();

        var chassisLabel = GetChassisLabel(ReadIntArray(enclosure, "ChassisTypes"));
        var motherboard = BuildMotherboardLabel(baseBoard);
        var model = ReadString(computerSystem, "Model");
        var deviceType = GetDeviceType(model, chassisLabel);
        var serialResolution = ResolveInventorySerialNumber(bios);
        var ramGb = RoundGigabytes(ReadDouble(computerSystem, "TotalPhysicalMemory"));
        var gpu = string.Join(" | ", videoControllers
            .Select(item => ReadString(item, "Name"))
            .Where(value => !string.IsNullOrWhiteSpace(value))
            .Distinct(StringComparer.OrdinalIgnoreCase));
        var storageSummary = string.Join(" | ", storageDevices
            .Select(device => string.Join(" ", new[] { device.Type, device.Model, device.SizeLabel }.Where(value => !string.IsNullOrWhiteSpace(value))))
            .Where(value => !string.IsNullOrWhiteSpace(value))
            .Distinct(StringComparer.OrdinalIgnoreCase));

        return new InventorySnapshot
        {
            Hostname = Environment.MachineName.Trim(),
            SerialNumber = serialResolution.Value,
            SerialSource = serialResolution.Source,
            Manufacturer = ReadString(computerSystem, "Manufacturer"),
            Model = model,
            DeviceType = deviceType,
            ChassisType = chassisLabel,
            Motherboard = motherboard,
            Chipset = GetChipsetCandidate($"{motherboard} {model}"),
            BiosVersion = ReadString(bios, "SMBIOSBIOSVersion"),
            Cpu = ReadString(processor, "Name"),
            Gpu = gpu,
            RamGb = ramGb,
            MemorySummary = FormatGbLabel(ramGb),
            StorageSummary = storageSummary,
            StorageDevices = storageDevices,
            WindowsCaption = ReadString(operatingSystem, "Caption"),
            WindowsVersion = ReadString(operatingSystem, "Version"),
            WindowsBuild = ReadString(operatingSystem, "BuildNumber"),
            CollectedAtUtc = DateTimeOffset.UtcNow.ToString("O"),
        };
    }

    private static ManagementBaseObject? QueryFirst(string query)
    {
        try
        {
            using var searcher = new ManagementObjectSearcher(query);
            using var results = searcher.Get();
            return results.Cast<ManagementBaseObject>().FirstOrDefault();
        }
        catch
        {
            return null;
        }
    }

    private static List<ManagementBaseObject> QueryMany(string query)
    {
        try
        {
            using var searcher = new ManagementObjectSearcher(query);
            using var results = searcher.Get();
            return results.Cast<ManagementBaseObject>().ToList();
        }
        catch
        {
            return [];
        }
    }

    private static List<StorageDevice> GetStorageInventory()
    {
        var devices = new List<StorageDevice>();
        var seen = new HashSet<string>(StringComparer.OrdinalIgnoreCase);

        foreach (var drive in QueryMany("SELECT Model, MediaType, Size FROM Win32_DiskDrive"))
        {
            var size = ReadUnsignedLong(drive, "Size");
            if (size == 0)
            {
                continue;
            }

            var device = new StorageDevice
            {
                Type = ReadString(drive, "MediaType"),
                Model = ReadString(drive, "Model"),
                SizeLabel = FormatSizeLabel(size),
            };

            var fingerprint = $"{device.Type}|{device.Model}|{device.SizeLabel}".Trim('|');
            if (fingerprint.Length == 0 || !seen.Add(fingerprint))
            {
                continue;
            }

            devices.Add(device);
        }

        return devices;
    }

    private static string BuildMotherboardLabel(ManagementBaseObject? baseBoard)
    {
        return string.Join(" ", new[]
        {
            ReadString(baseBoard, "Manufacturer"),
            ReadString(baseBoard, "Product"),
        }.Where(value => !string.IsNullOrWhiteSpace(value)));
    }

    private static (string Value, string Source) ResolveInventorySerialNumber(ManagementBaseObject? bios)
    {
        var serialNumber = ReadString(bios, "SerialNumber");
        if (!LooksLikeInvalidSerialNumber(serialNumber))
        {
            return (serialNumber, "bios");
        }

        var macAddress = GetPreferredMacAddress();
        if (!string.IsNullOrWhiteSpace(macAddress))
        {
            return (macAddress, "mac");
        }

        return (serialNumber, string.Empty);
    }

    private static IReadOnlyList<int> ReadIntArray(ManagementBaseObject? source, string property)
    {
        if (source is null || source.Properties[property]?.Value is null)
        {
            return [];
        }

        var raw = source.Properties[property].Value;
        if (raw is Array array)
        {
            return array.Cast<object>()
                .Select(value =>
                {
                    try
                    {
                        return Convert.ToInt32(value, CultureInfo.InvariantCulture);
                    }
                    catch
                    {
                        return -1;
                    }
                })
                .Where(value => value >= 0)
                .ToList();
        }

        try
        {
            return [Convert.ToInt32(raw, CultureInfo.InvariantCulture)];
        }
        catch
        {
            return [];
        }
    }

    private static string ReadString(ManagementBaseObject? source, string property)
    {
        if (source is null || source.Properties[property]?.Value is null)
        {
            return string.Empty;
        }

        return Convert.ToString(source.Properties[property].Value, CultureInfo.InvariantCulture)?.Trim() ?? string.Empty;
    }

    private static ulong ReadUnsignedLong(ManagementBaseObject? source, string property)
    {
        if (source is null || source.Properties[property]?.Value is null)
        {
            return 0;
        }

        try
        {
            return Convert.ToUInt64(source.Properties[property].Value, CultureInfo.InvariantCulture);
        }
        catch
        {
            return 0;
        }
    }

    private static double ReadDouble(ManagementBaseObject? source, string property)
    {
        if (source is null || source.Properties[property]?.Value is null)
        {
            return 0;
        }

        try
        {
            return Convert.ToDouble(source.Properties[property].Value, CultureInfo.InvariantCulture);
        }
        catch
        {
            return 0;
        }
    }

    private static decimal RoundGigabytes(double bytes)
    {
        if (bytes <= 0)
        {
            return 0;
        }

        return Math.Round((decimal)(bytes / 1024d / 1024d / 1024d), 2, MidpointRounding.AwayFromZero);
    }

    private static bool LooksLikeInvalidSerialNumber(string value)
    {
        var normalized = (value ?? string.Empty).Trim();
        if (normalized.Length == 0)
        {
            return true;
        }

        var invalidTokens = new[]
        {
            "default string",
            "system serial number",
            "to be filled by o.e.m.",
            "to be filled by oem",
            "not applicable",
            "not available",
            "unknown",
            "none",
            "null",
            "oem",
        };

        if (invalidTokens.Any(token => normalized.Equals(token, StringComparison.OrdinalIgnoreCase)))
        {
            return true;
        }

        return Regex.IsMatch(normalized, "^0+$");
    }

    private static string GetPreferredMacAddress()
    {
        var candidates = NetworkInterface.GetAllNetworkInterfaces()
            .Where(network =>
                network.NetworkInterfaceType is not NetworkInterfaceType.Loopback
                and not NetworkInterfaceType.Tunnel
                && network.OperationalStatus != OperationalStatus.Unknown)
            .Select(network => new
            {
                Network = network,
                Address = FormatMacAddress(network.GetPhysicalAddress()),
            })
            .Where(item => !string.IsNullOrWhiteSpace(item.Address))
            .OrderByDescending(item => item.Network.OperationalStatus == OperationalStatus.Up)
            .ThenBy(item => item.Network.NetworkInterfaceType == NetworkInterfaceType.Ethernet ? 0 : 1)
            .ThenBy(item => item.Network.NetworkInterfaceType == NetworkInterfaceType.Wireless80211 ? 0 : 1)
            .ToList();

        return candidates.Select(item => item.Address).FirstOrDefault() ?? string.Empty;
    }

    private static string FormatMacAddress(PhysicalAddress address)
    {
        var bytes = address.GetAddressBytes();
        if (bytes.Length < 6)
        {
            return string.Empty;
        }

        return string.Join("-", bytes.Select(value => value.ToString("X2", CultureInfo.InvariantCulture)));
    }

    private static string FormatGbLabel(decimal ramGb)
    {
        return ramGb <= 0
            ? string.Empty
            : $"{ramGb.ToString("0.##", CultureInfo.InvariantCulture)} GB";
    }

    private static string FormatSizeLabel(ulong bytes)
    {
        const decimal kb = 1024m;
        var mb = kb * 1024m;
        var gb = mb * 1024m;
        var tb = gb * 1024m;
        var value = bytes;

        if (value >= (ulong)tb)
        {
            return $"{Math.Round(value / tb, 0, MidpointRounding.AwayFromZero).ToString("0", CultureInfo.InvariantCulture)}TB";
        }

        if (value >= (ulong)gb)
        {
            return $"{Math.Round(value / gb, 0, MidpointRounding.AwayFromZero).ToString("0", CultureInfo.InvariantCulture)}GB";
        }

        return $"{Math.Round(value / mb, 0, MidpointRounding.AwayFromZero).ToString("0", CultureInfo.InvariantCulture)}MB";
    }

    private static string GetChassisLabel(IReadOnlyList<int> chassisTypes)
    {
        var desktopCodes = new Dictionary<int, string>
        {
            [3] = "Desktop",
            [4] = "Low Profile Desktop",
            [5] = "Pizza Box",
            [6] = "Mini Tower",
            [7] = "Tower",
            [15] = "Space Saving",
            [16] = "Lunch Box",
            [23] = "Rack Mount",
            [24] = "Sealed Case",
            [34] = "Mini PC",
            [35] = "Stick PC",
        };

        var notebookCodes = new Dictionary<int, string>
        {
            [8] = "Portable",
            [9] = "Laptop",
            [10] = "Notebook",
            [14] = "Sub Notebook",
            [30] = "Tablet",
            [31] = "Convertible",
            [32] = "Detachable",
        };

        foreach (var code in chassisTypes)
        {
            if (desktopCodes.TryGetValue(code, out var label))
            {
                return label;
            }
        }

        foreach (var code in chassisTypes)
        {
            if (notebookCodes.TryGetValue(code, out var label))
            {
                return label;
            }
        }

        return string.Empty;
    }

    private static string GetDeviceType(string model, string chassisLabel)
    {
        var candidate = $"{model} {chassisLabel}".ToLowerInvariant();
        return candidate.Contains("notebook", StringComparison.Ordinal)
               || candidate.Contains("laptop", StringComparison.Ordinal)
               || candidate.Contains("portable", StringComparison.Ordinal)
               || candidate.Contains("sub notebook", StringComparison.Ordinal)
               || candidate.Contains("tablet", StringComparison.Ordinal)
               || candidate.Contains("convertible", StringComparison.Ordinal)
               || candidate.Contains("detachable", StringComparison.Ordinal)
            ? "notebook"
            : "desktop";
    }

    private static string GetChipsetCandidate(string boardText)
    {
        if (string.IsNullOrWhiteSpace(boardText))
        {
            return string.Empty;
        }

        var patterns = new[]
        {
            "H[0-9]{3}",
            "B[0-9]{3}",
            "Z[0-9]{3}",
            "X[0-9]{3}",
            "A[0-9]{3}",
            "Q[0-9]{3}",
            "C[0-9]{3}",
            "TRX[0-9]{2}",
            "X[45]70",
            "B[45]50",
            "A[35]20",
        };

        foreach (var pattern in patterns)
        {
            var match = Regex.Match(boardText.ToUpperInvariant(), pattern);
            if (match.Success)
            {
                return match.Value;
            }
        }

        return string.Empty;
    }
}
