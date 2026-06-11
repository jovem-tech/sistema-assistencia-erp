namespace JovemTech.BenchCollector;

internal static class ConsolePrompts
{
    public static void PromptForMissingValues(BenchCollectorOptions options)
    {
        Console.WriteLine();
        Console.WriteLine("=== Coletor de Bancada Jovem Tech ===");
        Console.WriteLine("Preencha os dados abaixo. Enter reutiliza o valor sugerido quando existir.");
        Console.WriteLine();

        options.ErpBaseUrl = Prompt("URL base do ERP", options.ErpBaseUrl);
        options.ErpLoginEmail = Prompt("Email do usuario do ERP", options.ErpLoginEmail);
        options.WarrantyOsNumber = Prompt("Numero da OS", options.WarrantyOsNumber);

        if (string.IsNullOrWhiteSpace(options.WarrantyOsNumber))
        {
            options.WarrantyPublicUrl = Prompt("URL publica do selo (opcional)", options.WarrantyPublicUrl);
        }

        options.InstallationId = Prompt("InstallationId (opcional)", options.InstallationId);

        var mode = Prompt("Modo continuo? (s/N)", options.Continuous ? "s" : "n");
        options.Continuous = mode.Equals("s", StringComparison.OrdinalIgnoreCase);

        if (options.Continuous)
        {
            var currentInterval = options.IntervalMinutes > 0 ? options.IntervalMinutes.ToString() : "15";
            var intervalText = Prompt("Intervalo em minutos", currentInterval);
            if (int.TryParse(intervalText, out var interval) && interval > 0)
            {
                options.IntervalMinutes = interval;
            }
        }
    }

    private static string Prompt(string label, string currentValue)
    {
        var suffix = string.IsNullOrWhiteSpace(currentValue) ? string.Empty : $" [{currentValue}]";
        Console.Write($"{label}{suffix}: ");
        var typed = (Console.ReadLine() ?? string.Empty).Trim();
        return typed.Length == 0 ? currentValue : typed;
    }
}
