<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Prefijo y Middleware de Rutas API
    |--------------------------------------------------------------------------
    |
    | Define la URI y los middlewares bajo los cuales responderá el Agente
    | de impresión de escritorio de Nyxo (GET /ping, GET /jobs, POST /status).
    |
    */
    'route_prefix' => env('NYXO_PRINTER_PREFIX', 'api/v1/print'),

    'middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Ruta de descarga del instalador de Windows (.exe)
    |--------------------------------------------------------------------------
    |
    | Ruta pública desde la cual los usuarios pueden descargar el agente
    | instalador preconfigurado para vincular sus PCs.
    |
    */
    'download_route' => 'downloads/Nyxo_Universal_Printer_Setup_Win.exe',

    /*
    |--------------------------------------------------------------------------
    | Nombres de Tablas de Base de Datos
    |--------------------------------------------------------------------------
    |
    | Permite personalizar los nombres de las tablas para evitar colisiones
    | en bases de datos compartidas o heredadas.
    |
    */
    'tables' => [
        'nodes' => 'printer_nodes',
        'jobs' => 'print_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Soporte Multi-Tenant / Multi-Empresa
    |--------------------------------------------------------------------------
    |
    | Si tu aplicación es un SaaS o multi-empresa, especifica el nombre de la
    | columna de relación (ej. 'empresa_id' o 'tenant_id'). Si es un monolito
    | de una sola empresa, puedes dejarlo en null.
    |
    */
    'tenant_column' => env('NYXO_PRINTER_TENANT_COLUMN', 'empresa_id'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Resiliencia y Concurrencia
    |--------------------------------------------------------------------------
    |
    | timeout_minutes: Tiempo máximo que un trabajo puede permanecer en estado
    | 'processing' antes de que el sistema lo considere huérfano (por corte
    | de luz o desconexión) y lo vuelva a entregar para su impresión.
    |
    | max_attempts: Cantidad máxima de reintentos antes de marcar como fallido.
    |
    */
    'timeout_minutes' => (int) env('NYXO_PRINTER_TIMEOUT_MINUTES', 3),

    'max_attempts' => (int) env('NYXO_PRINTER_MAX_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Mantenimiento y Purga Automática de la Cola (Prunable)
    |--------------------------------------------------------------------------
    |
    | Días de retención para trabajos finalizados ('printed' o 'failed')
    | antes de ser eliminados por el comando 'php artisan nyxo-printer:clean'.
    |
    */
    'prune_after_days' => (int) env('NYXO_PRINTER_PRUNE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Configuración Térmica por Defecto (ESC/POS)
    |--------------------------------------------------------------------------
    |
    | default_width: Ancho en mm del papel térmico por defecto (80 o 58).
    | codepage: Tabla de códigos para caracteres en español (CP850 / WPC1252).
    |
    */
    'default_width' => (int) env('NYXO_PRINTER_DEFAULT_WIDTH', 80),

    'codepage' => env('NYXO_PRINTER_CODEPAGE', 'CP850'),
];
