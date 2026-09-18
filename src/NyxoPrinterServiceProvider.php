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
     * Register package services and container bindings.
     */
    public function register(): void
    {
        // 1. Merge default configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/nyxo-printer.php',
            'nyxo-printer'
        );

        // 2. Bind printing service interface to implementation
        $this->app->bind(PrintServiceInterface::class, PrintService::class);

        // 3. Register NyxoPrinter singleton behind Facade
        $this->app->singleton('nyxo-printer', function ($app) {
            return new NyxoPrinterManager($app->make(PrintServiceInterface::class));
        });
    }

    /**
     * Bootstrap package services, routes, views, and components.
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

        // Desktop Agent Polling and Heartbeat REST API
        Route::prefix($prefix)
            ->middleware($middleware)
            ->group(__DIR__.'/../routes/api.php');

        // Windows Desktop Agent download route
        Route::middleware(['web'])
            ->group(__DIR__.'/../routes/web.php');
    }

    protected function registerResources(): void
    {
        // Load Blade views under 'nyxo-printer' namespace
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nyxo-printer');

        // Automatically load database migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function registerLivewireComponents(): void
    {
        // If Livewire is present in the host application, register the modal component
        if (class_exists(Livewire::class)) {
            Livewire::component('nyxo-printer-modal', NyxoPrinterModal::class);
        }
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish Configuration
            $this->publishes([
                __DIR__.'/../config/nyxo-printer.php' => config_path('nyxo-printer.php'),
            ], 'nyxo-printer-config');

            // Publish Migrations
            $this->publishes([
                __DIR__.'/../database/migrations/create_nyxo_printer_tables.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_nyxo_printer_tables.php'),
            ], 'nyxo-printer-migrations');

            // Publish Blade Views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/nyxo-printer'),
            ], 'nyxo-printer-views');

            // Publish Desktop Installer Asset
            $this->publishes([
                __DIR__.'/../resources/dist/Nyxo_Universal_Printer_Setup_Win.exe' => public_path('downloads/Nyxo_Universal_Printer_Setup_Win.exe'),
            ], 'nyxo-printer-assets');
        }
    }
}
