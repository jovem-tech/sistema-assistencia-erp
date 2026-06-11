namespace JovemTech.BenchCollector;

internal sealed class BenchCollectorOptions
{
    public string ErpBaseUrl { get; set; } = string.Empty;
    public string WarrantyOsNumber { get; set; } = string.Empty;
    public string WarrantyPublicUrl { get; set; } = string.Empty;
    public string ErpLoginEmail { get; set; } = string.Empty;
    public string InstallationId { get; set; } = string.Empty;
    public int IntervalMinutes { get; set; } = 15;
    public bool Interactive { get; set; } = true;
    public bool Continuous { get; set; }
    public bool SaveConfig { get; set; } = true;
    public bool ShowJson { get; set; }
    public bool DryRun { get; set; }
    public bool HelpRequested { get; set; }

    public static BenchCollectorOptions Parse(string[] args)
    {
        var options = new BenchCollectorOptions();

        for (var index = 0; index < args.Length; index++)
        {
            var arg = (args[index] ?? string.Empty).Trim();
            if (arg.Length == 0)
            {
                continue;
            }

            if (arg is "--help" or "-h" or "/?")
            {
                options.HelpRequested = true;
                continue;
            }

            if (arg is "--no-prompt" or "--non-interactive")
            {
                options.Interactive = false;
                continue;
            }

            if (arg == "--continuous")
            {
                options.Continuous = true;
                continue;
            }

            if (arg == "--no-save-config")
            {
                options.SaveConfig = false;
                continue;
            }

            if (arg == "--show-json")
            {
                options.ShowJson = true;
                continue;
            }

            if (arg == "--dry-run")
            {
                options.DryRun = true;
                continue;
            }

            if (!arg.StartsWith("--", StringComparison.Ordinal))
            {
                continue;
            }

            string value;
            var equalsIndex = arg.IndexOf('=');
            if (equalsIndex >= 0)
            {
                value = arg[(equalsIndex + 1)..].Trim();
                arg = arg[..equalsIndex];
            }
            else
            {
                value = index + 1 < args.Length ? (args[++index] ?? string.Empty).Trim() : string.Empty;
            }

            switch (arg)
            {
                case "--erp-base-url":
                    options.ErpBaseUrl = value;
                    break;
                case "--warranty-os-number":
                    options.WarrantyOsNumber = value;
                    break;
                case "--warranty-public-url":
                    options.WarrantyPublicUrl = value;
                    break;
                case "--erp-login-email":
                    options.ErpLoginEmail = value;
                    break;
                case "--installation-id":
                    options.InstallationId = value;
                    break;
                case "--interval-minutes":
                    if (int.TryParse(value, out var minutes) && minutes > 0)
                    {
                        options.IntervalMinutes = minutes;
                    }

                    break;
            }
        }

        return options;
    }

    public void ApplyDefaults(CollectorPreferences? preferences)
    {
        if (preferences is null)
        {
            return;
        }

        if (string.IsNullOrWhiteSpace(ErpBaseUrl))
        {
            ErpBaseUrl = preferences.ErpBaseUrl ?? string.Empty;
        }

        if (string.IsNullOrWhiteSpace(ErpLoginEmail))
        {
            ErpLoginEmail = preferences.ErpLoginEmail ?? string.Empty;
        }

        if (IntervalMinutes <= 0 && preferences.IntervalMinutes > 0)
        {
            IntervalMinutes = preferences.IntervalMinutes;
        }
    }

    public void Normalize()
    {
        ErpBaseUrl = (ErpBaseUrl ?? string.Empty).Trim().TrimEnd('/');
        WarrantyOsNumber = (WarrantyOsNumber ?? string.Empty).Trim();
        WarrantyPublicUrl = (WarrantyPublicUrl ?? string.Empty).Trim();
        ErpLoginEmail = (ErpLoginEmail ?? string.Empty).Trim().ToLowerInvariant();
        InstallationId = (InstallationId ?? string.Empty).Trim();
        IntervalMinutes = IntervalMinutes <= 0 ? 15 : IntervalMinutes;
    }

    public void Validate()
    {
        Normalize();

        if (DryRun)
        {
            return;
        }

        if (string.IsNullOrWhiteSpace(ErpBaseUrl))
        {
            throw new InvalidOperationException("Informe a URL base do ERP.");
        }

        if (string.IsNullOrWhiteSpace(ErpLoginEmail))
        {
            throw new InvalidOperationException("Informe o email do usuario do ERP.");
        }

        if (string.IsNullOrWhiteSpace(WarrantyOsNumber) && string.IsNullOrWhiteSpace(WarrantyPublicUrl))
        {
            throw new InvalidOperationException("Informe o numero da OS ou a URL publica do selo.");
        }
    }

    public CollectorPreferences ToPreferences()
    {
        return new CollectorPreferences
        {
            ErpBaseUrl = ErpBaseUrl,
            ErpLoginEmail = ErpLoginEmail,
            IntervalMinutes = IntervalMinutes,
        };
    }

    public static string HelpText()
    {
        return """
JovemTech Bench Collector

Uso interativo:
  JovemTechBenchCollector.exe

Uso com parametros:
  JovemTechBenchCollector.exe --erp-base-url "https://erp.exemplo.com.br" --warranty-os-number "OS12345" --erp-login-email "tecnico@empresa.com"

Parametros principais:
  --erp-base-url        URL base do ERP, sem / final
  --warranty-os-number  Numero da OS vinculada ao equipamento
  --warranty-public-url URL publica do selo de garantia (alternativa a OS)
  --erp-login-email     Email do usuario ativo do ERP
  --installation-id     Identificador tecnico opcional da instalacao
  --continuous          Mantem check-in continuo, usando o intervalo abaixo
  --interval-minutes    Intervalo do modo continuo (padrao: 15)
  --show-json           Exibe o snapshot em JSON antes do envio
  --dry-run             Coleta e mostra os dados sem provisionar/enviar ao ERP
  --no-prompt           Falha se faltar algum dado, sem perguntar no console
  --no-save-config      Nao salva ERP/e-mail usados nesta estacao
  --help                Mostra esta ajuda
""";
    }
}
