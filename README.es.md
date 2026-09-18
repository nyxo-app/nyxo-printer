# 🖨️ Nyxo Universal Printer para Laravel

<p align="center">
  <a href="https://printer.nyxo.app">
    <img src="https://raw.githubusercontent.com/nyxo-app/nyxo-printer/main/art/banner.png" alt="Nyxo Universal Printer Banner" width="100%" onerror="this.style.display='none'">
  </a>
</p>

<p align="center">
  <strong>Impresión Térmica (ESC/POS) y A4 PDF Silenciosa para Aplicaciones Modernas en Laravel.</strong><br>
  Sin ventanas emergentes (`Ctrl+P`), sin Java ni QZ Tray, sin problemas de certificados SSL y con emulador visual en pantalla para desarrollar sin papel.
</p>

<p align="center">
  <a href="https://packagist.org/packages/nyxo-app/nyxo-printer"><img src="https://img.shields.io/packagist/v/nyxo-app/nyxo-printer.svg?style=flat-square&color=6366f1" alt="Versión en Packagist"></a>
  <a href="https://packagist.org/packages/nyxo-app/nyxo-printer"><img src="https://img.shields.io/packagist/dt/nyxo-app/nyxo-printer.svg?style=flat-square&color=10b981" alt="Descargas"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/Licencia-MIT-blue.svg?style=flat-square" alt="Licencia: MIT"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2%2B-8b5cf6.svg?style=flat-square" alt="Versión de PHP"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-f43f5e.svg?style=flat-square" alt="Soporte Laravel"></a>
  <a href="https://printer.nyxo.app"><img src="https://img.shields.io/badge/Web_Oficial-printer.nyxo.app-0284c7.svg?style=flat-square" alt="Web Oficial"></a>
</p>

<p align="center">
  <a href="#-guía-rápida-de-instalación">Instalación</a> •
  <a href="#-características-principales">Características</a> •
  <a href="#-descarga-del-agente-de-escritorio">Agente Desktop</a> •
  <a href="#-ejemplos-de-código">Ejemplos</a> •
  <a href="#-componente-livewire">Livewire</a> •
  <a href="README.md">English Version 🇬🇧</a>
</p>

---

## ⚡ El Dolor Tradicional vs. La Solución Nyxo

Imprimir tickets fiscales, comandas de cocina o comprobantes A4 desde una aplicación web moderna (Laravel, Livewire, Vue, React, Inertia) siempre ha sido una pesadilla técnica:

| El Problema Habitual en la Web | La Solución Nyxo Universal Printer |
| :--- | :--- |
| ❌ **`window.print()`:** Fuerza la ventana de diálogo (`Ctrl+P`), frena a los cajeros y exige presionar Enter manualmente. | 🚀 **Impresión 100% Silenciosa:** Se despacha directamente a la impresora física en **0.2 segundos** sin intervención del usuario. |
| ❌ **Bloqueos de SSL / Contenido Mixto:** Los navegadores en HTTPS impiden conectar por HTTP plano a `localhost` o IPs de la red local. | 🛡️ **Cero Certificados SSL:** La app en la nube encola los trabajos vía API segura; el agente local los retira e inyecta sin fricciones. |
| ❌ **Dependencia de Java (QZ Tray):** Exige instalar pesadas máquinas virtuales de Java en cada terminal y lidiar con certificados autofirmados. | 🪶 **Agente Nativo Ultraliviano:** Conexión directa al Spooler de Windows (`winspool.drv`) y a SumatraPDF. Cero Java. |
| ❌ **Suscripciones Mensuales Eternas (PrintNode):** Costos recurrentes mensuales en dólares por cada máquina cliente conectada. | 🎁 **Amigable con el Desarrollador:** 100% Gratuito e ilimitado para desarrollo local (`localhost`) + 1 Puesto Gratuito permanente para producción. |
| ❌ **Desperdicio de Papel al Programar:** Necesitas una impresora física en el escritorio solo para calibrar la alineación del ticket. | 🖥️ **Emulador Visual en Pantalla:** Previsualiza y ajusta el diseño del ticket térmico en HTML sin gastar un solo centímetro de papel. |

---

## 🏗️ Arquitectura y Flujo de Datos

```text
┌────────────────────────────────────────────────────────────────────────┐
│                     TU APLICACIÓN EN LARAVEL                           │
│                                                                        │
│   NyxoPrinter::to($puestoCaja)                                         │
│       ->title('MI COMERCIO')                                           │
│       ->table($articulos)                                              │
│       ->total($monto)                                                  │
│       ->qr('https://afip.gob.ar/factura/123')                          │
│       ->openDrawer()                                                   │
│       ->cut()                                                          │
│       ->send();                                                        │
│                                                                        │
│   Encola el payload binario en DB con bloqueo atómico lockForUpdate()  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ Polling seguro vía token (X-Tenant-Token)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│            AGENTE DE ESCRITORIO NYXO UNIVERSAL PRINTER                 │
│              Descarga directa en: https://printer.nyxo.app             │
│                                                                        │
│   - Retira los trabajos en segundo plano silenciosamente               │
│   - Uso gratuito e ilimitado en localhost y *.test                     │
│   - Inyección directa RAW ESC/POS al Spooler de Windows                │
│   - Inyección de PDFs en A4 silenciosos vía SumatraPDF                 │
└───────────────────┬───────────────────────────────┬────────────────────┘
                    │                               │
                    ▼                               ▼
    ┌───────────────────────────────┐   ┌───────────────────────────────┐
    │  IMPRESORA TÉRMICA DE TICKETS │   │  IMPRESORA CONVENCIONAL A4    │
    │  (EPSON, XPrinter, POS-80)    │   │  (Láser, Chorro de Tinta)     │
    │  • Ancho de 80mm y 58mm       │   │  • Facturas y Remitos A4      │
    │  • Corte automático de papel  │   │  • Contratos y Planillas      │
    │  • Apertura de cajón RJ11     │   │  • Calidad gráfica vectorial  │
    └───────────────────────────────┘   └───────────────────────────────┘
```

---

## 🖥️ Descarga del Agente de Escritorio

Para imprimir físicamente en impresoras USB, de Red (Ethernet/WiFi) o Bluetooth sin diálogos emergentes, la computadora cliente (con Windows) corre el **Agente de Escritorio Nyxo Universal Printer**.

> ### ⬇️ [Descargar el Instalador de Windows desde printer.nyxo.app](https://printer.nyxo.app)
> 
> * **Localhost Grace:** 100% libre e ilimitado para pruebas en entornos locales (`localhost`, `127.0.0.1`, `*.test` o emulador C#).
> * **Tier Gratuito para Desarrolladores:** Obtén **1 Puesto de Producción Gratuito** de por vida desde [printer.nyxo.app](https://printer.nyxo.app) (sin solicitar tarjeta de crédito).

---

## 📦 Guía Rápida de Instalación

### 1. Requerir el Paquete vía Composer

```bash
composer require nyxo-app/nyxo-printer
```

### 2. Ejecutar el Instalador Asistido

```bash
php artisan nyxo-printer:install
```

Este comando publicará automáticamente:
* `config/nyxo-printer.php` (prefijo de rutas, nombres de tablas, timeouts, ancho por defecto).
* Migraciones de base de datos (`printer_nodes` y `print_jobs`).
* Vistas Blade y componentes Livewire.

### 3. Ejecutar las Migraciones

```bash
php artisan migrate
```

---

## 💻 Puesta en Marcha en 1 Minuto

### 1. Crear un Nodo / Puesto de Impresión

Un **Nodo de Impresión** representa una terminal física o caja registradora:

```php
use Nyxo\Printer\Models\PrinterNode;

$nodo = PrinterNode::create([
    'name' => 'Caja Principal Mostrador',
    'driver' => 'thermal_80mm', // 'thermal_80mm' | 'thermal_58mm' | 'a4'
    'status' => 'online',
    'is_active' => true,
]);

// Obtén el código de enlace en 1 clic para configurar el agente sin escribir a mano:
$codigoEnlace = $nodo->codigo_enlace;
```

---

## 🧾 Ejemplos de Código

### A) Ticket Térmico con Sintaxis Fluida (ESC/POS)

Diseña comprobantes con una interfaz encadenable semántica. Los acentos, caracteres en español (`ñ`, `á`) y símbolos monetarios (`$`) se transliteran automáticamente a `CP850` / `WPC1252`:

```php
use Nyxo\Printer\Facades\NyxoPrinter;

NyxoPrinter::to($nodoId)
    ->width(80) // 80mm o 58mm
    ->title('NYXO CAFÉ & RESTÓ', doubleWidth: true, doubleHeight: true)
    ->center('CUIT: 30-71829384-9')
    ->text('Fecha: ' . now()->format('d/m/Y H:i') . ' - Comprobante #1042')
    ->line()
    ->table([
        ['nombre' => 'Café Espresso Doble', 'cantidad' => 2, 'precio' => 7000],
        ['nombre' => 'Tostado Especial JyQ', 'cantidad' => 1, 'precio' => 4500],
        ['nombre' => 'Medialuna de Manteca', 'cantidad' => 3, 'precio' => 6000],
    ])
    ->line()
    ->total(17500, label: 'TOTAL A PAGAR:')
    ->feed(1)
    ->qr('https://mi-factura.afip.gob.ar/1042', size: 6) // QR Fiscal o de Cobro
    ->barcode('00010429', type: 'CODE39')
    ->center('¡Muchas gracias por su visita!')
    ->openDrawer() // Envía pulso eléctrico al cajón monedero RJ11
    ->cut()        // Corte automático de guillotina
    ->send();
```

---

### B) Impresión Silenciosa de Facturas A4 en PDF

Despacha comprobantes A4 completos a impresoras convencionales de oficina en una sola línea:

```php
use Nyxo\Printer\Facades\NyxoPrinter;

// 1. Desde un archivo existente en disco:
NyxoPrinter::to($nodoId)
    ->copies(2)
    ->pdfFile(storage_path('app/facturas/factura_4059.pdf'))
    ->send();

// 2. Desde una cadena Base64 (DomPDF, Snappy, Spatie Browsershot):
$pdfBase64 = base64_encode($dompdf->output());

NyxoPrinter::to($nodoId)
    ->pdf($pdfBase64)
    ->send();
```

---

### C) Previsualización en Pantalla (Diseña sin Gastar Papel)

Prueba y calibra la tipografía y el diseño térmico directamente en el navegador:

```php
use Nyxo\Printer\Facades\NyxoPrinter;

Route::get('/preview-ticket', function () {
    return NyxoPrinter::preview(function ($ticket) {
        $ticket->width(80)
               ->title('VISTA PREVIA EN PANTALLA')
               ->text('Ajustando el diseño sin gastar rollos')
               ->table([
                   ['nombre' => 'Producto de Prueba A', 'precio' => 1200],
                   ['nombre' => 'Producto de Prueba B', 'precio' => 3400],
               ])
               ->total(4600)
               ->qr('https://printer.nyxo.app')
               ->cut();
    }, width: 80);
});
```

*Genera un bloque HTML interactivo que simula el papel térmico continuo con tipografía monoespaciada, cortes y códigos QR.*

---

### D) Plantillas Reutilizables (Print Templates)

Encapsula la lógica de impresión de tus comprobantes en clases limpias y reutilizables:

```php
use Nyxo\Printer\Contracts\PrintTemplateInterface;
use Nyxo\Printer\Builders\ThermalBuilder;

class ComandaCocinaTemplate implements PrintTemplateInterface
{
    public function __construct(protected array $comanda) {}

    public function build(ThermalBuilder $ticket): void
    {
        $ticket->center('*** COMANDA COCINA ***', bold: true, doubleHeight: true)
               ->text('MESA #' . $this->comanda['mesa'] . ' | Mozo: ' . $this->comanda['mozo'])
               ->doubleLine()
               ->table($this->comanda['platos'])
               ->feed(1)
               ->beep(times: 2) // ¡Dispara la alarma sonora de la comandera!
               ->cut();
    }
}

// Despacho en una línea:
NyxoPrinter::to($nodoCocinaId)
    ->template(new ComandaCocinaTemplate($datosPedido))
    ->send();
```

---

## 🎨 Componente Livewire Frontend

Incluye un modal listo para usar en **Livewire 3 y 4** estilizado con Tailwind CSS, con selector de puestos y estado de conexión en vivo:

### 1. Incluir el modal en tu layout Blade:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<livewire:nyxo-printer-modal />
```

### 2. Abrir el modal desde cualquier botón o componente:

```blade
<button wire:click="$dispatch('open-print-modal', {
    documentId: {{ $orden->id }},
    documentType: 'orden',
    format: 'ticket_80mm'
})">
    🖨️ Imprimir Comprobante
</button>
```

---

## 🛡️ Control de Concurrencia y Resiliencia

* **Bloqueos Atómicos:** La lectura de la cola utiliza `lockForUpdate()` dentro de transacciones de base de datos, evitando impresiones duplicadas ante ráfagas de pedidos simultáneos.
* **Rescate de Trabajos Huérfanos:** Si una terminal de cobro sufre un corte de luz mientras imprimía, Nyxo reencola el trabajo automáticamente tras vencer el tiempo de expiración (`config('nyxo-printer.timeout_minutes')`).
* **Mantenimiento Automatizado de Base de Datos:**

```bash
php artisan nyxo-printer:clean --days=7
```

Puedes programarlo en tu `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('nyxo-printer:clean --days=7')->daily();
```

---

## ⚙️ Archivo de Configuración

```php
// config/nyxo-printer.php
return [
    'route_prefix' => env('NYXO_PRINTER_PREFIX', 'api/v1/print'),
    'tables' => [
        'nodes' => 'printer_nodes',
        'jobs' => 'print_jobs',
    ],
    'tenant_column' => env('NYXO_PRINTER_TENANT_COLUMN', 'empresa_id'),
    'timeout_minutes' => (int) env('NYXO_PRINTER_TIMEOUT_MINUTES', 3),
    'max_attempts' => (int) env('NYXO_PRINTER_MAX_ATTEMPTS', 3),
    'prune_after_days' => (int) env('NYXO_PRINTER_PRUNE_DAYS', 7),
    'default_width' => 80, // 80mm o 58mm
    'codepage' => 'CP850', // Transliteración de caracteres en español
];
```

---

## 🤝 Comunidad y Soporte Comercial

* **Reportes de Errores y Sugerencias:** [GitHub Issues](https://github.com/nyxo-app/nyxo-printer/issues)
* **Descarga del Agente y Licencias Comerciales:** [printer.nyxo.app](https://printer.nyxo.app)
* **Lead Magnet:** Solicita tu clave gratuita de 1 puesto de producción sin tarjeta de crédito en [printer.nyxo.app](https://printer.nyxo.app).

---

## 📄 Licencia

El paquete de Laravel de Nyxo Universal Printer es software de código abierto bajo la [Licencia MIT](LICENSE.md).  
El Agente de Escritorio de Windows es software propietario comercial licenciado a través de [Lemon Squeezy](https://printer.nyxo.app).
