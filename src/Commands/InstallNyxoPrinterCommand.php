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
    protected $signature = 'nyxo-printer:install {--force : Sobrescribir archivos existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instala y publica la configuración, migraciones y recursos de Nyxo Universal Printer';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayBanner();

        $force = (bool) $this->option('force');

        // 1. Publicar Configuración
        $this->info('📁 Publicando archivo de configuración [config/nyxo-printer.php]...');
        $this->call('vendor:publish', [
            '--tag' => 'nyxo-printer-config',
            '--force' => $force,
        ]);

        // 2. Publicar Migraciones
        $this->info('🗄️  Publicando migraciones de base de datos...');
        $this->call('vendor:publish', [
            '--tag' => 'nyxo-printer-migrations',
            '--force' => $force,
        ]);

        // 3. Publicar Vistas Blade
        $this->info('🎨 Publicando vistas Livewire/Blade...');
        $this->call('vendor:publish', [
            '--tag' => 'nyxo-printer-views',
            '--force' => $force,
        ]);

        // 4. Publicar Instalador de Windows .exe en public/downloads
        $this->info('📦 Publicando instalador de Windows [.exe] en public/downloads...');
        $downloadsPath = public_path('downloads');
        if (! File::exists($downloadsPath)) {
            File::makeDirectory($downloadsPath, 0755, true);
        }

        $sourceExe = __DIR__.'/../../resources/dist/Nyxo_Universal_Printer_Setup_Win.exe';
        $destExe = $downloadsPath.'/Nyxo_Universal_Printer_Setup_Win.exe';

        if (File::exists($sourceExe)) {
            if (! File::exists($destExe) || $force) {
                File::copy($sourceExe, $destExe);
                $this->line('   <fg=green>✓</> Instalador copiado a: <fg=yellow>public/downloads/Nyxo_Universal_Printer_Setup_Win.exe</>');
            } else {
                $this->line('   <fg=blue>ℹ</> El instalador ya existe en public/downloads (usa --force para sobrescribir).');
            }
        } else {
            $this->line('   <fg=blue>ℹ</> El instalador de escritorio se descarga desde la web oficial: <fg=yellow>https://printer.nyxo.app</>');
        }

        // 5. Preguntar si desea migrar ahora
        if ($this->confirm('¿Deseas ejecutar las migraciones de base de datos ahora?', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('🎉 ¡Nyxo Universal Printer ha sido instalado con éxito!');
        $this->newLine();
        $this->line('  <fg=bright-white;bg=blue;options=bold> GUÍA RÁPIDA DE USO </>');
        $this->line('  1. Incluye el modal en tu layout Blade:');
        $this->line('     <fg=yellow><livewire:nyxo-printer-modal /></>');
        $this->newLine();
        $this->line('  2. Envía impresiones directamente desde PHP:');
        $this->line('     <fg=yellow>NyxoPrinter::to($nodeId)->pdf($pdfBase64)->send();</>');
        $this->line('     <fg=yellow>NyxoPrinter::to($nodeId)->title("TICKET")->total(45000)->cut()->send();</>');
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
