<?php

declare(strict_types=1);

namespace Nyxo\Printer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallNyxoPrinterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nyxo-printer:install {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and publish Nyxo Universal Printer configuration, migrations, and assets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayBanner();

        $force = (bool) $this->option('force');

        // 1. Publish Configuration
        $this->info('📁 Publishing configuration file [config/nyxo-printer.php]...');
        $this->call('vendor:publish', [
            '--tag' => 'nyxo-printer-config',
            '--force' => $force,
        ]);

        // 2. Publish Migrations
        $this->info('🗄️  Publishing database migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'nyxo-printer-migrations',
            '--force' => $force,
        ]);

        // 3. Publish Blade Views & Livewire Component
        $this->info('🎨 Publishing Livewire/Blade views...');
        $this->call('vendor:publish', [
            '--tag' => 'nyxo-printer-views',
            '--force' => $force,
        ]);

        // 4. Check Desktop Installer
        $this->info('📦 Verifying Windows desktop agent setup...');
        $downloadsPath = public_path('downloads');
        if (! File::exists($downloadsPath)) {
            File::makeDirectory($downloadsPath, 0755, true);
        }

        $sourceExe = __DIR__.'/../../resources/dist/Nyxo_Universal_Printer_Setup_Win.exe';
        $destExe = $downloadsPath.'/Nyxo_Universal_Printer_Setup_Win.exe';

        if (File::exists($sourceExe)) {
            if (! File::exists($destExe) || $force) {
                File::copy($sourceExe, $destExe);
                $this->line('   <fg=green>✓</> Desktop installer copied to: <fg=yellow>public/downloads/Nyxo_Universal_Printer_Setup_Win.exe</>');
            } else {
                $this->line('   <fg=blue>ℹ</> Installer already exists in public/downloads (use --force to overwrite).');
            }
        } else {
            $portalUrl = (string) config('nyxo-printer.portal_url', 'https://printer.nyxo.ar');
            $this->line("   <fg=blue>ℹ</> Download the desktop agent installer from the official portal: <fg=yellow>{$portalUrl}</>");
        }

        // 5. Ask to run migrations
        if ($this->confirm('Would you like to run database migrations now?', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('🎉 Nyxo Universal Printer has been installed successfully!');
        $this->newLine();
        $this->line('  <fg=bright-white;bg=blue;options=bold> QUICKSTART GUIDE </>');
        $this->line('  1. Include the modal component in your Blade layout:');
        $this->line('     <fg=yellow><livewire:nyxo-printer-modal /></>');
        $this->newLine();
        $this->line('  2. Dispatch print jobs directly from PHP:');
        $this->line('     <fg=yellow>NyxoPrinter::to($nodeId)->pdf($pdfBase64)->send();</>');
        $this->line('     <fg=yellow>NyxoPrinter::to($nodeId)->title("RECEIPT")->total(45.00)->cut()->send();</>');
        $this->newLine();

        return self::SUCCESS;
    }

    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line('<fg=cyan>  _   _                 ____       _       _            </>');
        $this->line('<fg=cyan> | \ | |_   ___  _____ |  _ \ _ __(_)_ __ | |_ ___ _ __ </>');
        $this->line('<fg=cyan> |  \| | | | \ \/ / _ \| |_) | \'__| | \'_ \| __/ _ \ \'__|</>');
        $this->line('<fg=cyan> | |\  | |_| |>  < (_) |  __/| |  | | | | | ||  __/ |   </>');
        $this->line('<fg=cyan> |_| \_|\__, /_/\_\___/|_|   |_|  |_|_| |_|\__\___|_|   </>');
        $this->line('<fg=cyan>        |___/                                           </>');
        $this->line('<fg=gray> Universal Silent Printing for Laravel (A4 & ESC/POS Thermal)</>');
        $this->newLine();
    }
}
