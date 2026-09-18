<?php

declare(strict_types=1);

namespace Nyxo\Printer\Commands;

use Illuminate\Console\Command;
use Nyxo\Printer\Models\PrintJob;

class CleanNyxoPrinterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nyxo-printer:clean {--days= : Purgar trabajos más antiguos que X días (por defecto según config)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina trabajos de impresión antiguos completados o fallidos para optimizar la base de datos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('nyxo-printer.prune_after_days', 7));
        $threshold = now()->subDays($days);

        $this->info("🧹 Purgando trabajos de impresión completados o fallidos anteriores a: {$threshold->toDateTimeString()} ({$days} días)...");

        $count = PrintJob::whereIn('status', ['printed', 'failed'])
            ->where('created_at', '<=', $threshold)
            ->delete();

        $this->info("✓ Se eliminaron {$count} trabajos de impresión antiguos.");

        return self::SUCCESS;
    }
}
