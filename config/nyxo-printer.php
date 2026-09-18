<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Route Prefix & Middleware
    |--------------------------------------------------------------------------
    |
    | Defines the URL prefix and middleware stack for the printing endpoints
    | used by the Nyxo Universal Printer desktop agent (GET /ping, GET /jobs,
    | POST /jobs/{id}/status).
    |
    */
    'route_prefix' => env('NYXO_PRINTER_PREFIX', 'api/v1/print'),

    'middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Windows Desktop Agent Download Route
    |--------------------------------------------------------------------------
    |
    | Public route where client workstations can request the desktop agent
    | setup file. If the file is not hosted locally, it redirects to the
    | official portal URL.
    |
    */
    'download_route' => 'downloads/Nyxo_Universal_Printer_Setup_Win.exe',

    /*
    |--------------------------------------------------------------------------
    | Official Web Portal / Download URL
    |--------------------------------------------------------------------------
    |
    | The official URL where users and developers can download the desktop
    | agent, claim licenses, or access documentation.
    |
    */
    'portal_url' => env('NYXO_PRINTER_PORTAL_URL', 'https://printer.nyxo.app'),

    /*
    |--------------------------------------------------------------------------
    | Database Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the database table names to prevent naming collisions in
    | shared, legacy, or multi-tenant database architectures.
    |
    */
    'tables' => [
        'nodes' => 'printer_nodes',
        'jobs' => 'print_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant / Multi-Company Support
    |--------------------------------------------------------------------------
    |
    | If your application is a SaaS or multi-tenant system, specify the foreign
    | key column name (e.g. 'company_id', 'tenant_id', or 'empresa_id'). Set
    | to null if your application is a single-tenant monolith.
    |
    */
    'tenant_column' => env('NYXO_PRINTER_TENANT_COLUMN', 'empresa_id'),

    /*
    |--------------------------------------------------------------------------
    | Resilience & Concurrency Configuration
    |--------------------------------------------------------------------------
    |
    | timeout_minutes: Maximum minutes a job may stay in 'processing' status
    | before being treated as orphaned (due to power outage or crash) and
    | automatically re-queued for delivery.
    |
    | max_attempts: Maximum retry attempts before marking the job as failed.
    |
    */
    'timeout_minutes' => (int) env('NYXO_PRINTER_TIMEOUT_MINUTES', 3),

    'max_attempts' => (int) env('NYXO_PRINTER_MAX_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Automated Database Pruning
    |--------------------------------------------------------------------------
    |
    | Number of retention days for completed ('printed') or failed jobs
    | before being automatically purged by `php artisan nyxo-printer:clean`.
    |
    */
    'prune_after_days' => (int) env('NYXO_PRINTER_PRUNE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Default Thermal Printing Settings (ESC/POS)
    |--------------------------------------------------------------------------
    |
    | default_width: Default paper width in millimeters (80 or 58).
    | codepage: Character encoding transliteration table (e.g. CP850, WPC1252).
    |
    */
    'default_width' => (int) env('NYXO_PRINTER_DEFAULT_WIDTH', 80),

    'codepage' => env('NYXO_PRINTER_CODEPAGE', 'CP850'),
];
