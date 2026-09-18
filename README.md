# 🖨️ Nyxo Universal Printer for Laravel

[![Latest Version on Packagist](https://img.shields.io/badge/package-nyxo--app%2Fnyxo--printer-blue.svg)](https://packagist.org/packages/nyxo-app/nyxo-printer)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-indigo.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-red.svg)](https://laravel.com)

**Nyxo Universal Printer** es un subsistema completo y desacoplado para Laravel que permite la **impresión directa y silenciosa** hacia impresoras locales (hojas A4 convencionales y comanderas térmicas de 80mm/58mm ESC/POS) a través de un agente de escritorio para Windows, **sin ventanas emergentes de navegador (`Ctrl+P`), sin problemas de certificados SSL y con emulador visual incluido para desarrollo**.

---

## 🌟 Características Principales

* 🚀 **Impresión Silenciosa Instantánea:** Sin cuadros de diálogo ni intervención del usuario.
* 🧾 **Motor Fluido ESC/POS:** Construye tickets con sintaxis semántica encadenable (`title`, `text`, `table`, `total`, `qr`, `barcode`, `openDrawer`, `beep`, `cut`).
* 📄 **Soporte Nativo A4 PDF:** Envía documentos PDF completos a impresoras convencionales con una sola línea de código.
* 📱 **Doble Compatibilidad QR:** Soporte nativo por hardware ESC/POS y fallback gráfico para comanderas económicas.
* 🖥️ **Emulador / Previsualización Visual:** Prueba y diseña tickets en pantalla (`preview()`) sin tener una comandera física conectada.
* ⚡ **Control de Concurrencia y Resiliencia:** Bloqueo de transacciones atómicas (`lockForUpdate`) y rescate automático de trabajos huérfanos por corte de luz.
* 🗄️ **Mantenimiento Automatizado (Prunable):** Purgado automático de trabajos antiguos para mantener la base de datos veloz.
* 🎨 **Componente Livewire 3/4 Incluido:** Modal con diseño Tailwind CSS y selector de puestos con indicador online/offline en vivo.

---

## 📦 Instalación

### 1. Requerir el paquete vía Composer

```bash
composer require nyxo-app/nyxo-printer
```

*(Si estás probando el paquete de forma local antes de publicarlo en Packagist, puedes agregarlo a tu `composer.json` como repositorio de tipo `"path"`)*:
```json
"repositories": [
    {
        "type": "path",
        "url": "../Nyxo_Universal_Printer/composer_build"
    }
]
```

### 2. Ejecutar el Asistente de Instalación

```bash
php artisan nyxo-printer:install
```

Este comando publicará automáticamente:
* `config/nyxo-printer.php` (Configuración de rutas, tablas y timeouts).
* Migraciones de base de datos (`printer_nodes` y `print_jobs`).
* Vistas Blade / Livewire.
* El instalador de Windows `.exe` en `public/downloads/`.
* Y te consultará si deseas ejecutar `php artisan migrate` de inmediato.

---

## 💻 Guía de Uso Rápido

### A) Impresión Térmica ESC/POS (Diseño Fluido)

```php
use Nyxo\Printer\Facades\NyxoPrinter;

NyxoPrinter::to($nodeId)
    ->width(80) // 80mm o 58mm
    ->title('MI COMERCIO', doubleWidth: true, doubleHeight: true)
    ->text('Fecha: 17/08/2026 - Año de Garantía') // Acentos automáticos
    ->line()
    ->table([
        ['nombre' => 'Cambio de Módulo OLED', 'precio' => 45000],
        ['nombre' => 'Templado 9D', 'precio' => 5000],
    ])
    ->total(50000)
    ->qr('https://mi-factura.afip.gob.ar/123') // QR de Facturación o Pago
    ->barcode('00045892')
    ->openDrawer() // Pulso a cajón de dinero (opcional)
    ->cut()
    ->send();
```

---

### B) Impresión de Documentos PDF en A4

```php
use Nyxo\Printer\Facades\NyxoPrinter;

// 1. Desde Base64 (DomPDF, Spatie PDF, Snappy)
NyxoPrinter::to($nodeId)
    ->copies(2)
    ->pdf($pdfBase64)
    ->send();

// 2. Desde un archivo en disco
NyxoPrinter::to($nodeId)
    ->pdfFile(storage_path('app/ordenes/orden_458.pdf'))
    ->send();
```

---

### C) Uso de Plantillas Reutilizables (Presets)

```php
use Nyxo\Printer\Facades\NyxoPrinter;
use Nyxo\Printer\Templates\ReceiptTemplate;

NyxoPrinter::to($nodeId)
    ->template(new ReceiptTemplate([
        'empresa' => 'eRepair Taller',
        'title' => 'COMPROBANTE DE PAGO',
        'items' => [
            ['nombre' => 'Servicio Técnico Especializado', 'precio' => 25000],
        ],
        'total' => 25000,
        'qr' => 'https://...',
        'footer' => '¡Gracias por su confianza!',
    ]))
    ->send();
```

#### Creación de Plantillas Propias
Cualquier clase puede convertirse en plantilla implementando `PrintTemplateInterface`:

```php
use Nyxo\Printer\Contracts\PrintTemplateInterface;
use Nyxo\Printer\Builders\ThermalBuilder;

class ComandaCocinaTemplate implements PrintTemplateInterface
{
    public function __construct(protected array $pedido) {}

    public function build(ThermalBuilder $ticket): void
    {
        $ticket->center('MESA #'.$this->pedido['mesa'], bold: true, doubleHeight: true)
               ->text('Mozo: '.$this->pedido['mozo'])
               ->doubleLine()
               ->table($this->pedido['items'])
               ->beep(times: 2) // Alarma sonora en cocina
               ->cut();
    }
}
```

---

### D) Previsualización en Pantalla (Modo Emulador)

Para probar diseños y estilos sin imprimir en papel real ni tener una comandera conectada:

```php
$htmlTicket = NyxoPrinter::preview(function ($ticket) {
    $ticket->title('PRUEBA EN PANTALLA')
           ->text('Diseñando ticket sin gastar rollos')
           ->total(1500)
           ->cut();
}, width: 80);

// Devuelve un bloque HTML/CSS simulando el papel continuo y tipografía térmica.
```

---

### E) Componente Livewire Frontend

Incluye el modal en tu layout principal (`resources/views/layouts/app.blade.php`):
```blade
<livewire:nyxo-printer-modal />
```

Abre el modal desde cualquier botón o pantalla mediante eventos de Livewire/Alpine:
```blade
<button wire:click="$dispatch('open-print-modal', { documentId: {{ $orden->id }}, documentType: 'orden', format: 'ticket_80mm' })">
    Imprimir Ticket
</button>
```

---

## 📡 Protocolo y Vinculación del Agente de Escritorio

El paquete expone automáticamente los siguientes endpoints bajo el prefijo configurado (`/api/v1/print`):

| Método | Endpoint | Descripción |
| :--- | :--- | :--- |
| `GET` | `/ping` | Heartbeat para reportar estado activo y obtener datos del nodo. |
| `GET` | `/jobs` | Obtiene trabajos pendientes con bloqueo atómico (`lockForUpdate`). |
| `POST` | `/jobs/{id}/status` | Actualiza estado a `printed` o `failed` y dispara eventos. |
| `GET` | `/downloads/Nyxo_Universal_Printer_Setup_Win.exe` | Descarga del instalador de Windows. |

### 🔗 Código de Enlace Rápido (1 Clic)
Para vincular el agente de escritorio sin escribir la URL y el Token a mano, puedes obtener el código unificado:
```php
$codigoEnlace = NyxoPrinter::getPairingCode($nodo); // Devuelve: Base64(url|token)
```

---

## 🧹 Mantenimiento de Base de Datos

Para purgar trabajos completados o fallidos antiguos y mantener la base de datos optimizada:

```bash
php artisan nyxo-printer:clean --days=7
```

Puedes programarlo en tu `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('nyxo-printer:clean --days=7')->daily();
```

---

## 🛡️ Eventos del Ciclo de Vida

El paquete dispara eventos nativos de Laravel para que tu aplicación pueda reaccionar:

* `Nyxo\Printer\Events\PrintJobCreated`: Al encolar un trabajo.
* `Nyxo\Printer\Events\PrintJobPrinted`: Cuando el hardware físico termina de imprimir con éxito.
* `Nyxo\Printer\Events\PrintJobFailed`: Si ocurre algún error en la impresora.

---

## 📄 Licencia

Este paquete está licenciado bajo la licencia [MIT](LICENSE.md).
