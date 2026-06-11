using System.Text.RegularExpressions;

namespace JovemTech.BenchCollector;

internal static partial class Program
{
    private static readonly System.Text.Json.JsonSerializerOptions SnapshotJsonOptions = new()
    {
        PropertyNamingPolicy = System.Text.Json.JsonNamingPolicy.CamelCase,
        WriteIndented = true,
    };

    [GeneratedRegex("[^a-zA-Z0-9\\-]")]
    private static partial Regex InvalidInstallationIdChars();

    private static async Task<int> Main(string[] args)
    {
        if (!OperatingSystem.IsWindows())
        {
            Console.Error.WriteLine("Este coletor foi feito para Windows.");
            return 1;
        }

        var options = BenchCollectorOptions.Parse(args);
        if (options.HelpRequested)
        {
            Console.WriteLine(BenchCollectorOptions.HelpText());
            return 0;
        }

        using var cancellation = new CancellationTokenSource();
        Console.CancelKeyPress += (_, eventArgs) =>
        {
            eventArgs.Cancel = true;
            cancellation.Cancel();
        };

        try
        {
            var configStore = new LocalConfigStore();
            var snapshotStore = new SnapshotFileStore();
            options.ApplyDefaults(await configStore.LoadAsync(cancellation.Token));

            if (options.Interactive)
            {
                ConsolePrompts.PromptForMissingValues(options);
            }

            options.Validate();

            if (options.SaveConfig)
            {
                await configStore.SaveAsync(options.ToPreferences(), cancellation.Token);
            }

            var collector = new InventoryCollector();

            Console.WriteLine();
            Console.WriteLine("Coletando inventario inicial...");

            var initialSnapshot = collector.Collect();
            initialSnapshot.InstallationId = ResolveInstallationId(options.InstallationId, initialSnapshot);
            var snapshotPath = await TrySaveSnapshotAsync(snapshotStore, initialSnapshot, options, null, cancellation.Token);

            Console.WriteLine(initialSnapshot.ToPrettySummary());
            Console.WriteLine();
            if (!string.IsNullOrWhiteSpace(snapshotPath))
            {
                Console.WriteLine($"Snapshot local salvo em: {snapshotPath}");
                Console.WriteLine();
            }

            if (options.ShowJson)
            {
                Console.WriteLine("Snapshot JSON:");
                Console.WriteLine(System.Text.Json.JsonSerializer.Serialize(initialSnapshot, SnapshotJsonOptions));
                Console.WriteLine();
            }

            if (options.DryRun)
            {
                Console.WriteLine("Modo dry-run concluido sem envio ao ERP.");
                return 0;
            }

            var client = new ErpAgentClient(options.ErpBaseUrl);
            Console.WriteLine("Provisionando agente no ERP...");
            var bootstrap = await client.BootstrapAsync(options, initialSnapshot, cancellation.Token);
            await TrySaveSnapshotAsync(snapshotStore, initialSnapshot, options, bootstrap, cancellation.Token);
            var checkInEndpoint = string.IsNullOrWhiteSpace(bootstrap.CheckInEndpoint)
                ? "api/v1/agents/check-in"
                : bootstrap.CheckInEndpoint;
            var loopInterval = options.IntervalMinutes > 0 ? options.IntervalMinutes : Math.Max(1, bootstrap.InventoryIntervalMinutes);

            Console.WriteLine($"Agente provisionado com sucesso. AgentId: {bootstrap.AgentId}");
            Console.WriteLine($"Label: {bootstrap.AgentLabel}");
            Console.WriteLine();

            do
            {
                cancellation.Token.ThrowIfCancellationRequested();

                var snapshot = collector.Collect();
                snapshot.InstallationId = initialSnapshot.InstallationId;
                await TrySaveSnapshotAsync(snapshotStore, snapshot, options, bootstrap, cancellation.Token);

                Console.WriteLine("Enviando check-in...");
                var checkIn = await client.CheckInAsync(
                    checkInEndpoint,
                    bootstrap.ApiToken,
                    bootstrap.AgentId,
                    snapshot,
                    cancellation.Token);

                Console.WriteLine($"Check-in confirmado em {checkIn.ReceivedAt}.");
                await TrySaveSnapshotAsync(snapshotStore, snapshot, options, bootstrap, cancellation.Token);

                if (!options.Continuous)
                {
                    break;
                }

                Console.WriteLine($"Aguardando {loopInterval} minuto(s) para o proximo envio. Ctrl+C encerra.");
                await Task.Delay(TimeSpan.FromMinutes(loopInterval), cancellation.Token);
            } while (true);

            Console.WriteLine();
            Console.WriteLine("Coleta finalizada com sucesso.");
            return 0;
        }
        catch (OperationCanceledException)
        {
            Console.WriteLine();
            Console.WriteLine("Execucao encerrada pelo operador.");
            return 130;
        }
        catch (Exception ex)
        {
            Console.Error.WriteLine();
            Console.Error.WriteLine("Falha ao executar o coletor:");
            Console.Error.WriteLine(ex.Message);
            return 1;
        }
    }

    private static string ResolveInstallationId(string requestedId, InventorySnapshot snapshot)
    {
        if (!string.IsNullOrWhiteSpace(requestedId))
        {
            return requestedId.Trim();
        }

        var raw = string.Join('-', new[]
        {
            snapshot.Hostname,
            snapshot.SerialNumber,
            snapshot.Model,
        }.Where(value => !string.IsNullOrWhiteSpace(value)));

        raw = InvalidInstallationIdChars().Replace(raw, "-").Trim('-');
        if (string.IsNullOrWhiteSpace(raw))
        {
            raw = "jt-agent-" + Guid.NewGuid().ToString("N");
        }

        return raw.ToLowerInvariant();
    }

    private static async Task<string> TrySaveSnapshotAsync(
        SnapshotFileStore snapshotStore,
        InventorySnapshot snapshot,
        BenchCollectorOptions options,
        BootstrapResponse? bootstrap,
        CancellationToken cancellationToken)
    {
        try
        {
            return await snapshotStore.SaveAsync(snapshot, options, bootstrap, cancellationToken);
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Aviso: nao foi possivel gravar o snapshot local em {snapshotStore.GetDefaultPath(options)}.");
            Console.WriteLine($"Motivo: {ex.Message}");
            Console.WriteLine();
            return string.Empty;
        }
    }
}
