<?php

declare(strict_types=1);

namespace Nyxo\Printer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Nyxo\Printer\Commands\CleanNyxoPrinterCommand;
use Nyxo\Printer\Commands\InstallNyxoPrinterCommand;
use Nyxo\Printer\Contracts\PrintServiceInterface;
use Nyxo\Printer\Livewire\NyxoPrinterModal;
use Nyxo\Printer\Services\PrintService;

class NyxoPrinterServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios y bindings del contenedor.
     */
    public function register(): void
    {
        // 1. Fusionar configuración por defecto
        $this->mergeConfigFrom(
            __DIR__.'/../config/nyxo-printer.php',
            'nyxo-printer'
        );

        // 2. Vincular interfaz de encolado con su implementación
        $this->app->bind(PrintServiceInterface::class, PrintService::class);

        // 3. Registrar el Manager singleton detrás de la Facade NyxoPrinter
        $this->app->singleton('nyxo-printer', function ($app) {
            return new NyxoPrinterManager($app->make(PrintServiceInterface::class));
        });
    }

    /**
     * Inicializa los servicios, rutas, vistas y componentes del paquete.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerRoutes();
        $this->registerResources();
        $this->registerLivewireComponents();
        $this->registerPublishing();
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallNyxoPrinterCommand::class,
                CleanNyxoPrinterCommand::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        $prefix = (string) config('nyxo-printer.route_prefix', 'api/v1/print');
        $middleware = (array) config('nyxo-printer.middleware', ['api']);

        // Rutas API de Polling y Heartbeat del Agente
        Route::prefix($prefix)
            ->middleware($middleware)
            ->group(__DIR__.'/../routes/api.php');

        // Ruta de descarga del instalador de Windows (.exe)
        Route::middleware(['web'])
            ->group(__DIR__.'/../routes/web.php');
    }

    protected function registerResources(): void
    {
        // Carga de vistas con el namespace 'nyxo-printer'
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nyxo-printer');

        // Carga automática de migraciones si no se han publicado
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function registerLivewireComponents(): void
    {
        // Si Livewire está instalado en la aplicación, registramos el componente del modal
        if (class_exists(Livewire::class)) {
            Livewire::component('nyxo-printer-modal', NyxoPrinterModal::class);
        }
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publicar Configuración
            $this->publishes([
                __DIR__.'/../config/nyxo-printer.php' => config_path('nyxo-printer.php'),
            ], 'nyxo-printer-config');

            // Publicar Migraciones
            $this->publishes([
                __DIR__.'/../database/migrations/create_nyxo_printer_tables.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_nyxo_printer_tables.php'),
            ], 'nyxo-printer-migrations');

            // Publicar Vistas Blade
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/nyxo-printer'),
            ], 'nyxo-printer-views');

            // Publicar Instalador .exe
            $this->publishes([
                __DIR__.'/../resources/dist/Nyxo_Universal_Printer_Setup_Win.exe' => public_path('downloads/Nyxo_Universal_Printer_Setup_Win.exe'),
            ], 'nyxo-printer-assets');
        }
    }
}
