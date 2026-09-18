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
    protected $signature = 'nyxo-printer:clean {--days= : Purge print jobs older than X days (default from config)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge completed or failed print jobs to keep the database lean and performant';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('nyxo-printer.prune_after_days', 7));
        $threshold = now()->subDays($days);

        $this->info("🧹 Purging completed or failed print jobs older than {$threshold->toDateTimeString()} ({$days} days)...");

        $count = PrintJob::whereIn('status', ['printed', 'failed'])
            ->where('created_at', '<=', $threshold)
            ->delete();

        $this->info("✓ Successfully purged {$count} legacy print jobs.");

        return self::SUCCESS;
    }
}
