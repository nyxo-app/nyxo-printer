# 🖨️ Nyxo Universal Printer for Laravel
## The Definitive Architecture, Integration & 100% Operation Manual
**Official Engineering & Production Deployment Guide**  
*Technical Reference Document v1.0 — Compatible with PHP 8.2+, Laravel 10/11/12/13, Livewire 3/4 & Windows Spooler*

---

## 📑 Table of Contents

1. [Technical Specifications & Value Proposition](#1-technical-specifications--value-proposition)
2. [System Architecture & Data Lifecycle](#2-system-architecture--data-lifecycle)
3. [Step-by-Step Installation & Configuration](#3-step-by-step-installation--configuration)
4. [Printer Node Management & 1-Click Terminal Pairing](#4-printer-node-management--1-click-terminal-pairing)
5. [Complete Internal REST API Specification](#5-complete-internal-rest-api-specification)
6. [Fluent Builder & 100% Enqueuing Methods (`NyxoPrinter`)](#6-fluent-builder--100-enqueuing-methods-nyxoprinter)
7. [Paperless Development & Testing](#7-paperless-development--testing)
8. [Reusable Template System (Built-in & Custom)](#8-reusable-template-system-built-in--custom)
9. [Frontend Livewire 3 & 4 Integration (The Complete Lifecycle)](#9-frontend-livewire-3--4-integration-the-complete-lifecycle)
10. [Official Desktop Agent Guide: Operation, Configuration & Deployment](#10-official-desktop-agent-guide-operation-configuration--deployment)
11. [Concurrency Control, Resilience & Lifecycle Events](#11-concurrency-control-resilience--lifecycle-events)
12. [Troubleshooting & Frequently Asked Questions (FAQ)](#12-troubleshooting--frequently-asked-questions-faq)

---

## 1. Technical Specifications & Value Proposition

Unattended silent physical printing in modern cloud web applications faces severe architectural and browser-level security barriers:
- `window.print()` freezes UI execution, triggers intrusive modal dialogs (`Ctrl+P`), and demands manual cashier keystrokes.
- Mixed Content browser security rules (HTTPS $\to$ HTTP) block cloud applications from reaching local network IPs or `localhost`.
- Legacy solutions like QZ Tray require heavy client-side Java Runtime Environments (JRE) and self-signed certificate management.
- Cloud SaaS services like PrintNode charge expensive monthly recurring per-terminal subscription fees.

**Nyxo Universal Printer** resolves these challenges with a decoupled hybrid architecture:

| Metric | Traditional Web Printing | Nyxo Universal Printer |
| :--- | :--- | :--- |
| **Dispatch Latency** | 3 to 8 seconds (user interaction required) | **0.2 seconds** (silent and unattended) |
| **Browser Popups** | Forces `Ctrl+P` dialog on every ticket | **Zero browser popups** |
| **Local SSL Certificates** | Demanded by browsers on client PCs | **None required** (outbound HTTPS polling) |
| **Java Dependency** | Mandatory in QZ Tray and similar bridges | **Zero Java dependencies** (Native C / WinSpool) |
| **Router Port Forwarding** | Requires open ports or static public IPs | **Zero inbound ports** (Outbound Polling) |
| **Paper Wasted in Dev** | Demands a physical printer on your desk | **In-browser preview** and virtual emulator |

---

## 2. System Architecture & Data Lifecycle

The subsystem operates under an **atomic queue model with secure outbound polling**:

```text
┌────────────────────────────────────────────────────────────────────────┐
│                   LARAVEL WEB APPLICATION (Cloud / VPS)                │
│                                                                        │
│   NyxoPrinter::to($nodeId)                                             │
│       ->title('COFFEE SHOP')                                           │
│       ->table($items)                                                  │
│       ->total($amount)                                                 │
│       ->cut()                                                          │
│       ->send();                                                        │
│                                                                        │
│   1. Compiles binary bytes (ESC/POS) or Base64 (PDF / RAW / JSON)      │
│   2. Persists atomic payload in `print_jobs` table as 'pending'        │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ Outbound HTTPS Polling (Port 443)
                                    │ Header: Authorization: Bearer <token>
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│             NYXO DESKTOP AGENT (Client POS PC / Cashier)               │
│                                                                        │
│   - Sends heartbeat ping every 5 seconds (GET /api/v1/print/ping)      │
│   - Fetches pending jobs with atomic locking (GET /jobs)               │
│   - Sends success confirmation or error diagnostic (POST /status)      │
└───────────────────┬───────────────────────────────┬────────────────────┘
                    │ winspool.drv (RAW)            │ SumatraPDF (Silent CLI)
                    ▼                               ▼
    ┌───────────────────────────────┐   ┌───────────────────────────────┐
    │  THERMAL RECEIPT PRINTER      │   │  CONVENTIONAL A4 PRINTER      │
    │  (ESC/POS 80mm / 58mm)        │   │  (Office Laser / Inkjet)      │
    │  • Receipts, Kitchen Orders   │   │  • Invoices, Delivery Slips   │
    │  • Guillotine Cut & RJ11 Kick │   │  • High quality PDF output    │
    └───────────────────────────────┘   └───────────────────────────────┘
```

### State Lifecycle of a Print Job (`PrintJob`):
1. **`pending`**: Job is queued in Laravel via `NyxoPrinter::to($id)->send()`.
2. **`processing`**: Local desktop agent polls `GET /jobs`. The database executes `lockForUpdate()` within an atomic transaction, transitioning the record to `processing` and incrementing `attempts`.
3. **`printed`**: Hardware printer confirms data injection into Windows Spooler. Desktop agent sends `POST /jobs/{id}/status` with `status: 'printed'`.
4. **`failed`**: In case of paper jams, power disconnection, or hardware faults, the agent reports `status: 'failed'` along with the captured diagnostic string (`error_message`).

---

## 3. Step-by-Step Installation & Configuration

### 3.1 Environment Requirements
- **PHP:** 8.2 or higher (tested on PHP 8.2, 8.3, and 8.4).
- **PHP Extensions:** `ext-iconv` (for CP850 transliteration), `ext-json`, `ext-mbstring`.
- **Framework:** Laravel 10.x, 11.x, 12.x, or 13.x.
- **Client POS OS:** Windows 10 or 11 (64-bit) with the Nyxo Desktop Agent installed.

### 3.2 Installation via Composer
Run in your Laravel project root:
```bash
composer require nyxo-app/nyxo-printer
```

### 3.3 Interactive Installer
Run the automated installation command:
```bash
php artisan nyxo-printer:install
```
This command automatically performs:
1. Publishing configuration file to `config/nyxo-printer.php`.
2. Publishing timestamped database migrations to `database/migrations/`.
3. Publishing Blade views and Livewire modal component to `resources/views/vendor/nyxo-printer/`.
4. Copying Windows desktop agent installer (`Nyxo_Universal_Printer_Setup_Win.exe`) into `public/downloads/` for immediate self-hosting.
5. Interactive prompt to execute `php artisan migrate`.

### 3.4 Database Migrations
If not executed during the previous step:
```bash
php artisan migrate
```

#### Table `printer_nodes` (Workstation Nodes):
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Unique primary key for physical workstation. |
| `name` | `VARCHAR(100)` | Human-readable name (e.g. "Register 1 - Front Counter"). |
| `print_token` | `VARCHAR(80) UNIQUE` | Cryptographic 60-character authentication token. |
| `pairing_code` | `VARCHAR(10) NULL INDEX` | 6-digit numeric fallback pairing code. |
| `empresa_id` | `BIGINT UNSIGNED NULL INDEX` | Foreign key for multi-tenant SaaS isolation. |
| `last_ping_at` | `TIMESTAMP NULL INDEX` | Heartbeat timestamp received from desktop agent. |
| `is_active` | `TINYINT(1) DEFAULT 1` | Node active status flag. |
| `timestamps` | `TIMESTAMP` | Timestamps `created_at` and `updated_at`. |

#### Table `print_jobs` (Atomic Job Queue):
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Unique primary key for the print job. |
| `printer_node_id` | `BIGINT UNSIGNED FK` | Constrained foreign key to `printer_nodes` with cascade deletion. |
| `format` | `VARCHAR(50) DEFAULT 'a4'` | Target format: `ticket_80mm`, `ticket_58mm`, `a4`, `raw`. |
| `content_type` | `VARCHAR(50)` | Payload type: `escpos_base64`, `pdf_base64`, `raw_text`, `json_structured`. |
| `content` | `LONGTEXT` | Binary payload in Base64 or plain ASCII text. |
| `status` | `VARCHAR(30) DEFAULT 'pending'` | State: `pending`, `processing`, `printed`, `failed`. |
| `attempts` | `SMALLINT UNSIGNED DEFAULT 0` | Retry attempts counter. |
| `error_message` | `TEXT NULL` | Hardware error message reported by desktop client. |
| `timestamps` | `TIMESTAMP` | Compound index: `['printer_node_id', 'status', 'created_at']`. |

### 3.5 Configuration File (`config/nyxo-printer.php`)
```php
return [
    // REST API route prefix for the desktop agent
    'route_prefix' => env('NYXO_PRINTER_PREFIX', 'api/v1/print'),

    // Middleware stack applied to printer routes
    'middleware' => ['api'],

    // Public download route for the desktop agent installer
    'download_route' => 'downloads/Nyxo_Universal_Printer_Setup_Win.exe',

    // Official support and licensing portal
    'portal_url' => env('NYXO_PRINTER_PORTAL_URL', 'https://printer.nyxo.ar'),

    // Database table names
    'tables' => [
        'nodes' => 'printer_nodes',
        'jobs' => 'print_jobs',
    ],

    // Multi-tenant column (null if single-tenant monolith)
    'tenant_column' => env('NYXO_PRINTER_TENANT_COLUMN', 'empresa_id'),

    // Minutes before a stalled 'processing' job is rescued and re-queued
    'timeout_minutes' => (int) env('NYXO_PRINTER_TIMEOUT_MINUTES', 3),

    // Maximum delivery retry attempts
    'max_attempts' => (int) env('NYXO_PRINTER_MAX_ATTEMPTS', 3),

    // Automatic retention days before database cleanup
    'prune_after_days' => (int) env('NYXO_PRINTER_PRUNE_DAYS', 7),

    // Default thermal paper width in mm (80 or 58)
    'default_width' => (int) env('NYXO_PRINTER_DEFAULT_WIDTH', 80),

    // Character set codepage transliteration table
    'codepage' => env('NYXO_PRINTER_CODEPAGE', 'CP850'),
];
```

---

## 4. Printer Node Management & 1-Click Terminal Pairing

Each physical computer running the desktop agent is registered as a `PrinterNode`:

```php
use Nyxo\Printer\Models\PrinterNode;

// 1. Create the node (in Seeder, Controller or Filament/Nova resource):
$node = PrinterNode::create([
    'name' => 'Cashier 1 - Main Front Desk',
    'is_active' => true,
    // 'empresa_id' => $tenant->id, // If running in SaaS mode
]);

// 2. Cryptographic token (60 characters, auto-generated):
$token = $node->print_token;
// Example: "8fK9xT2yR8qW1yT5mN4zB6cA0dF3gH7jK2lM5nP8rS1tV4wY7bC0eG3hJ6kL"

// 3. 1-Click Pairing String (URL + Token bundled in Base64):
$pairingString = $node->pairing_string; // Or alias $node->codigo_enlace
// Decoded format: "https://my-pos.com/api/v1/print|8fK9xT2yR8..."
```

### Configuration on the Windows Client PC

#### 🔒 Clean Factory State by Default (Security & Multi-Tenant Privacy)
By strict security and multi-tenant isolation design, the official client application is distributed **100% clean and unlinked by default**:
- **Zero Preloaded URLs or Tokens:** Neither the SaaS server endpoint (`saasUrl`) nor secret terminal tokens (`tenantToken`) come pre-configured. The desktop application starts completely empty, waiting for operator pairing via 1-click token or manual entry.
- **Zero Preloaded Emulator Parameters (Clean Priority):** The emulator and network printer address fields (`emulatorUrl`) start completely empty. On real production retail workstations with physical USB/Network thermal printers, this field is not used. If an emulator address is configured during development (e.g. `127.0.0.1:9100`), the agent automatically activates **Exclusive Emulator Mode**, cleanly disabling physical thermal printer selectors and routing 100% of thermal receipt jobs to the on-screen emulator. Clearing the field instantly restores physical printers.
- **Unlicensed Initial State & Zero Backdoors:** The application launches without any pre-loaded license key. All licenses (including the 100% free Developer license and commercial plans) are issued exclusively through **Lemon Squeezy** (`https://printer.nyxo.ar`).

#### 🛡️ First Launch on Windows 10/11 (SmartScreen Notice)
When running the installer or portable version for the first time, Windows Defender SmartScreen may display a blue dialog (*"Windows protected your PC"*). This is standard for newly distributed binaries that do not carry costly EV enterprise code-signing certificates:
- To proceed: Click **"More info"** and click the **"Run anyway"** button.

#### 🔄 Background Daemon & System Tray Integration
Nyxo Universal Printer operates as an **unattended background service**:
- **Clicking the `X` button:** The window **does not quit**; it hides to the Windows System Tray next to the clock. This prevents cashiers from accidentally closing the print daemon and halting point-of-sale receipts.
- **Restoring the window:** Double-click the printer tray icon next to the clock, or simply run the `.exe` again (the *Single Instance Lock* detects the existing background instance and brings the window to the foreground immediately).
- **Exiting the application:** Right-click the System Tray icon and select **"Exit"**.

#### ⚙️ Step-by-Step Pairing Workflow:
1. Launch `Nyxo_Universal_Printer.exe`.
2. Go to the **🔑 Connection** tab.
3. **1-Click Quick Pairing (Recommended):** Paste the `$node->pairing_string` (or `$node->codigo_enlace`) generated from the Laravel admin panel into the **"⚡ Quick Pairing Code"** field and click **Paste** and **Save**. The agent automatically unpacks the SaaS API URL and secret token.
4. **Manual Method:** Enter the API URL (e.g. `https://my-pos.com/api/v1/print`) and paste `$node->print_token`.
5. In the **🖨️ Printers** tab, select the physical device assigned for each format (*Ticket 80mm*, *Ticket 58mm*, or *Office A4*).
6. In the **🛡️ License** tab, enter your license key issued by **Lemon Squeezy** (Developer Free $0 USD or Commercial plans) and click **Activate License**.

### Real-Time Heartbeat Monitoring
The desktop agent sends an automated heartbeat every 5 seconds to `GET /api/v1/print/ping`. Laravel updates `last_ping_at` quietly without firing Eloquent model observers (`updateQuietly()`).

```php
// Check whether the physical terminal is powered on and online:
if ($node->is_online) {
    // 🟢 Online: Desktop agent reported heartbeat within last 2 minutes
} else {
    // 🔴 Offline: PC turned off, agent closed, or no internet connection
}
```

#### Blade / Tailwind Status Badge:
```blade
<div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium {{ $node->is_online ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
    <span class="w-2 h-2 rounded-full {{ $node->is_online ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
    <span>{{ $node->name }}: {{ $node->is_online ? 'Online' : 'Offline' }}</span>
</div>
```

---

## 5. Complete Internal REST API Specification

All endpoint communications are secured by [`CheckPrintToken`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Http/Middleware/CheckPrintToken.php) middleware.

### Multi-Source Token Resolution:
The middleware progressively inspects the following channels (preventing proxy or Apache FastCGI header loss):
1. `X-Tenant-Token` header
2. `X-Print-Token` header
3. `Print-Token` header
4. `Token` header
5. Standard `Authorization: Bearer <token>` header
6. Query or body parameters `?token=` or `?print_token=`
7. Apache server environment variables `HTTP_AUTHORIZATION` and `REDIRECT_HTTP_AUTHORIZATION`

> 💡 **Auto-Decoding:** If a user accidentally pastes the Base64 1-Click Pairing String into a token-only input field, the middleware detects the pipe `|` character, decodes the payload, and extracts the authentic token.

### Official API Endpoints:

#### 1. Heartbeat Ping (`GET /api/v1/print/ping`)
- **Headers:** `Authorization: Bearer <print_token>`
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Pong",
  "node": {
    "id": 1,
    "name": "Register 1",
    "tenant_id": 1
  },
  "tenant": {
    "id": 1,
    "nombre": "Register 1"
  },
  "punto_venta": {
    "id": 1,
    "nombre": "Register 1",
    "numero": 1
  },
  "timestamp": "2026-09-21T10:30:00-03:00"
}
```

#### 2. Atomic Job Fetching (`GET /api/v1/print/jobs`)
- **Headers:** `Authorization: Bearer <print_token>`
- **Mechanism:** Wrapped in `DB::transaction()` with `lockForUpdate()`. Fetches pending jobs, transitions them to `processing`, and increments `attempts = attempts + 1`.
- **Response (200 OK):**
```json
{
  "success": true,
  "count": 1,
  "jobs": [
    {
      "id": 104,
      "format": "ticket_80mm",
      "content_type": "escpos_base64",
      "content": "G0BAIBs...",
      "attempts": 1,
      "created_at": "2026-09-21T10:30:01-03:00"
    }
  ]
}
```

#### 3. Job Status Update (`POST /api/v1/print/jobs/{id}/status`)
- **Headers:** `Authorization: Bearer <print_token>`
- **Body JSON:**
```json
{
  "status": "printed",
  "error_message": null
}
```
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Print job status updated successfully.",
  "job_id": 104,
  "new_status": "printed"
}
```

---

## 6. Fluent Builder & 100% Enqueuing Methods (`NyxoPrinter`)

The [`NyxoPrinter`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Facades/NyxoPrinter.php) facade provides a clean, robust, chainable, fully typed API.

### 6.1 Thermal Receipts (ESC/POS) (`ThermalBuilder`)
Design clean thermal tickets with automatic transliteration to `CP850` / `WPC1252`:

```php
use Nyxo\Printer\Facades\NyxoPrinter;

NyxoPrinter::to($nodeId)
    ->width(80) // 80mm (42 cols) or 58mm (32 cols). Auto-adjusts format.
    ->copies(1) // Number of physical copies to queue
    
    // Header & Titles
    ->title('NYXO BISTRO & COFFEE', doubleWidth: true, doubleHeight: true)
    ->center('Tax ID: 30-71829384-9 | VAT Reg. Company')
    ->center('1234 Market Street, Suite 100')
    ->line('-') // Divider matching paper width (42 or 32 dashes)
    
    // Order Metadata
    ->text('Receipt #: 0001-00004921', bold: true)
    ->text('Date: ' . now()->format('Y-m-d H:i') . ' | Cashier: Sarah')
    ->doubleLine('=') // Double line divider (====================)
    
    // Aligned Table (automatic column padding)
    ->table([
        ['nombre' => 'Double Espresso', 'cantidad' => 2, 'precio' => 7000],
        ['nombre' => 'Ham & Cheese Croissant', 'cantidad' => 1, 'precio' => 4500],
        ['nombre' => 'Sparkling Water 500ml', 'cantidad' => 1, 'precio' => 2000],
    ], headerLeft: 'DESCRIPTION', headerRight: 'TOTAL')
    ->line('-')
    
    // Totales
    ->text('Subtotal: $13,500.00', align: 'right')
    ->text('Member Discount (10%): -$1,350.00', align: 'right')
    ->total(12150.00, label: 'TOTAL DUE:', currency: '$')
    ->feed(1) // Feed blank lines
    
    // 1D & 2D Codes
    ->qr('https://invoice.gov/verify/1042', size: 6) // Fiscal / Payment QR
    ->barcode('000100004921', type: 'CODE39') // 1D Barcode
    
    // Footer
    ->center('Thank you for your visit!')
    ->center('Please keep this receipt for returns')
    
    // Electromechanical Hardware Controls
    ->openDrawer(pin: 0) // Electrical kick pulse to RJ11 cash drawer
    ->beep(times: 2)     // Acoustic buzzer in kitchen or bar stations
    ->cut(full: false)   // Partial guillotine cut (true = full cut)
    
    // Dispatch to database queue
    ->send();
```

### 6.2 Advanced Text Modifiers in `ThermalBuilder`
```php
$ticket->text(
    text: 'UNDERLINED EMPHASIZED TEXT',
    align: 'center',       // 'left' | 'center' | 'right'
    bold: true,            // Emphasized font
    underline: true,       // Hardware physical underline
    doubleHeight: false,   // 2x height
    doubleWidth: false     // 2x width
);
```

### 6.3 Direct ESC/POS Command Injection (`custom(callable)`)
If you need direct hardware escape sequences not provided by fluent helpers:
```php
use Mike42\Escpos\Printer;

NyxoPrinter::to($nodeId)
    ->title('RAW TEST')
    ->custom(function (Printer $printer) {
        $printer->setLineSpacing(30);
        $printer->getPrintConnector()->write("\x1B\x21\x30");
    })
    ->cut()
    ->send();
```

> [!CAUTION]
> **PHP 8.x Buffer Extraction Rule:**  
> When `$printer->close()` is called, Mike42's connector destructively sets its internal buffer to `null`. To prevent `implode(): Argument #1 must be of type array|string, null given`, buffer data must always be extracted before closing:
> ```php
> $data = $connector->getData();
> $printer->close();
> return base64_encode($data);
> ```
> *This rule is already safely handled inside `ThermalBuilder::toBase64()`.*

---

### 6.4 Silent A4 PDF Invoices & Delivery Slips

#### Option A: From an existing file on disk:
```php
NyxoPrinter::to($nodeId)
    ->copies(2)
    ->pdfFile(storage_path('app/invoices/inv_4921.pdf'))
    ->send();
```

#### Option B: From in-memory Base64 (DomPDF, Snappy, Browsershot):
```php
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = Pdf::loadView('pdf.invoice_a4', ['order' => $order]);
$base64 = base64_encode($pdf->output());

NyxoPrinter::to($nodeId)
    ->copies(1)
    ->pdf($base64, format: 'a4')
    ->send();
```

---

### 6.5 Dot Matrix / Impact Printing (`raw`) & JSON Payloads
```php
// 1. Dot matrix / Impact printers (plain ASCII):
NyxoPrinter::to($nodeId)
    ->raw("PACKING SLIP\nCUSTOMER: ACME CORP\nDATE: 2026-09-21\n\n\x0C")
    ->send();

// 2. Structured JSON data:
NyxoPrinter::to($nodeId)
    ->json([
        'action' => 'box_label',
        'tracking' => 'TRK-98213-US',
        'boxes' => 3,
    ])
    ->send();
```

---

### 6.6 Direct Facade Convenience Shortcuts
```php
NyxoPrinter::pdf($nodeId, $pdfBase64, format: 'a4', copies: 1);
NyxoPrinter::thermal($nodeId, $escposBase64, format: 'ticket_80mm', copies: 1);
NyxoPrinter::raw($nodeId, "PLAIN TEXT", format: 'raw', copies: 1);
NyxoPrinter::template($nodeId, $templateInstance, copies: 1);

$node = NyxoPrinter::node($nodeId);
$pairingString = NyxoPrinter::getPairingString($node);
```

---

## 7. Paperless Development & Testing

Thermal paper roll waste during layout debugging is a major developer pain point. Nyxo Universal Printer provides two built-in solutions:

### 7.1 In-Browser Photorealistic HTML Preview
Register a test route in `routes/web.php` to preview your receipt in Chrome/Edge:

```php
use Nyxo\Printer\Facades\NyxoPrinter;

Route::get('/dev/ticket-preview', function () {
    return NyxoPrinter::preview(function ($ticket) {
        $ticket->width(80)
            ->title('SCREEN DEVELOPMENT PREVIEW')
            ->center('Demo Retail Store')
            ->line('-')
            ->table([
                ['nombre' => 'Classic Cheeseburger', 'cantidad' => 2, 'precio' => 12000],
                ['nombre' => 'Large French Fries', 'cantidad' => 1, 'precio' => 4500],
                ['nombre' => 'Cold Soda 500ml', 'cantidad' => 2, 'precio' => 3000],
            ])
            ->line('-')
            ->total(19500.00)
            ->qr('https://printer.nyxo.ar')
            ->cut();
    }, width: 80);
});
```
**Browser Output:** A realistic continuous thermal receipt with monospace typography, paper cut indicators, cash drawer pulse badges, and vector QR codes.

---

### 7.2 Hardware C# Emulator (ESSI Thermal Emulator)
The **ESSI Thermal Emulator** is an optional standalone visual simulator for developers, designed to craft receipt templates and test workflows without wasting physical thermal paper rolls.
- **Optional Testing Utility:** In real cash registers and production retail stores with physical USB/Network thermal printers, the emulator is **NOT installed or utilized**. The desktop agent ships from factory with emulator address fields completely empty.
- **Exclusive Emulator Priority Rule (Clean UI Lock):**
  To eliminate routing conflicts and remove the burden of unbinding physical printers during testing, the Nyxo Agent enforces an intelligent priority toggle:
  - **When `emulatorUrl` is populated (e.g. `127.0.0.1:9100` in Settings):** The agent enters **Active Emulator Mode**. Physical thermal printer dropdowns (80mm & 58mm) are **automatically disabled** in the *Printers* tab, and an informative blue notification banner is displayed: *"Emulator Mode Active (Dev) - All thermal print jobs are automatically redirected to the emulator"*. 100% of ESC/POS receipt jobs route straight to the on-screen emulator.
  - **When `emulatorUrl` is left empty:** The banner is hidden and physical thermal printer selectors are restored, routing jobs to physical Windows Spooler USB printers or TCP network printers.
  - *(Note: A4 document assignment remains independent and active, allowing physical laser/inkjet printing even when the thermal emulator is active).*
- **Local Testing Workflow:**
  1. Launch **ESSI Thermal Emulator** (`ESSIThermalEmulator.exe`), which starts a TCP/HTTP listener on port `9100`.
  2. In the Nyxo Agent, navigate to the **⚙️ Settings** tab, enter `127.0.0.1:9100` in the **C# Emulator / TCP Socket Destination** field, and click **Save Settings**.
  3. Jobs sent from Laravel will now render on-screen with virtual paper roll animations, 80mm/58mm width toggle, and native PDF export options.

---

## 8. Reusable Template System (Built-in & Custom)

To maintain Clean Architecture, formatting logic should never clutter controllers or Eloquent models.

### 8.1 Built-in Template 1: `ReceiptTemplate` (Sales Receipts)
Located at `Nyxo\Printer\Templates\ReceiptTemplate`:
```php
use Nyxo\Printer\Facades\NyxoPrinter;
use Nyxo\Printer\Templates\ReceiptTemplate;

$receiptData = [
    'company' => 'CENTRAL SUPERMARKET',
    'title' => 'SALES RECEIPT',
    'metadata' => [
        'Register' => '01',
        'Cashier' => 'John Doe',
        'Date' => now()->format('Y-m-d H:i'),
    ],
    'items' => [
        ['name' => 'Organic Whole Milk 1L', 'qty' => 2, 'price' => 2400],
        ['name' => 'Artisan Bread 500g', 'qty' => 1, 'price' => 3100],
    ],
    'total' => 5500.00,
    'qr' => 'https://verify.pos/5500',
    'barcode' => '779123456789',
    'footer' => 'Thank you for shopping with us!',
    'open_drawer' => true,
    'cut' => true,
];

NyxoPrinter::to($nodeId)
    ->template(new ReceiptTemplate($receiptData))
    ->send();
```

---

### 8.2 Built-in Template 2: `OrderSummaryTemplate` (Repair & Services)
Located at `Nyxo\Printer\Templates\OrderSummaryTemplate`:
```php
use Nyxo\Printer\Facades\NyxoPrinter;
use Nyxo\Printer\Templates\OrderSummaryTemplate;

$serviceData = [
    'empresa' => 'PRO TECH REPAIR LAB',
    'order_number' => '4092',
    'fecha' => now()->format('Y-m-d H:i'),
    'cliente' => 'Michael Scott',
    'telefono' => '+1 555-0199',
    'equipo' => 'Lenovo ThinkPad T480',
    'problema' => 'Will not boot after power surge',
    'reparaciones' => [
        ['nombre' => 'PWM Power Circuit IC Replacement', 'precio' => 35000],
        ['nombre' => 'Thermal Paste & Internal Cleaning', 'precio' => 12000],
    ],
    'total' => 47000.00,
    'barcode' => 'ORD-4092',
    'terminos' => '90-day warranty on labor.',
];

NyxoPrinter::to($nodeId)
    ->template(new OrderSummaryTemplate($serviceData))
    ->send();
```

---

### 8.3 Creating Custom Business Templates
Implement [`PrintTemplateInterface`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Contracts/PrintTemplateInterface.php):

```php
namespace App\PrintTemplates;

use Nyxo\Printer\Contracts\PrintTemplateInterface;
use Nyxo\Printer\Builders\ThermalBuilder;

class KitchenOrderTemplate implements PrintTemplateInterface
{
    public function __construct(
        protected array $order
    ) {}

    public function build(ThermalBuilder $ticket): void
    {
        $ticket->center('*** KITCHEN ORDER ***', bold: true, doubleHeight: true)
            ->text('TABLE #' . $this->order['table'] . ' | Waiter: ' . $this->order['waiter'])
            ->text('Time: ' . now()->format('H:i:s'))
            ->doubleLine('=');

        foreach ($this->order['dishes'] as $dish) {
            $ticket->text(
                text: $dish['qty'] . 'x ' . $dish['name'],
                bold: true,
                doubleWidth: true
            );
            if (! empty($dish['notes'])) {
                $ticket->text('   > NOTE: ' . $dish['notes']);
            }
        }

        $ticket->line('-')
            ->beep(times: 3) // Alert line cook
            ->feed(2)
            ->cut();
    }
}
```

---

## 9. Frontend Livewire 3 & 4 Integration (The Complete Lifecycle)

The package includes the Tailwind CSS modal component [`NyxoPrinterModal`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Livewire/NyxoPrinterModal.php).

### 9.1 Step 1: Include Modal in Blade Layout
Add the global modal to `resources/views/layouts/app.blade.php`:
```blade
<!DOCTYPE html>
<html lang="en">
<head> ... </head>
<body>
    {{ $slot }}

    {{-- Nyxo Universal Printer Modal --}}
    <livewire:nyxo-printer-modal />
</body>
</html>
```

---

### 9.2 Step 2: Trigger Button
In any Blade view or Livewire component:
```blade
<button 
    type="button"
    wire:click="$dispatch('open-print-modal', {
        documentId: {{ $order->id }},
        documentType: 'service_order',
        format: 'ticket_80mm'
    })"
    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow-sm transition">
    🖨️ Print Receipt
</button>
```

#### Smart Node Preselection:
1. `printerNodeId` passed explicitly in event payload.
2. Last node stored in session (`session('nyxo_last_printer_node_id')`).
3. Node associated with authenticated user (`auth()->user()->printer_node_id`).
4. First active node available in the database.

---

### 9.3 Step 3: Host Component Listener (Resolving GitHub's Omission)
When the user clicks "Print" inside the modal, it emits `nyxo-print-requested`. **Your host component must listen to this event to render and queue the document:**

```php
namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Order;
use Nyxo\Printer\Facades\NyxoPrinter;
use Nyxo\Printer\Templates\OrderSummaryTemplate;

class OrderManagement extends Component
{
    /**
     * Listen to print requests dispatched from the Nyxo modal
     */
    #[On('nyxo-print-requested')]
    public function handlePrintRequest(array $payload): void
    {
        $documentId = $payload['documentId'];
        $documentType = $payload['documentType'];
        $format = $payload['format']; // 'ticket_80mm', 'ticket_58mm', 'a4'
        $printerNodeId = $payload['printerNodeId'];
        $copies = $payload['copies'] ?? 1;

        $order = Order::with(['customer', 'items'])->findOrFail($documentId);

        // Case A: Thermal Receipt
        if ($format === 'ticket_80mm' || $format === 'ticket_58mm') {
            $width = $format === 'ticket_58mm' ? 58 : 80;

            NyxoPrinter::to($printerNodeId)
                ->width($width)
                ->copies($copies)
                ->template(new OrderSummaryTemplate([
                    'empresa' => config('app.name'),
                    'order_number' => $order->number,
                    'fecha' => $order->created_at->format('Y-m-d H:i'),
                    'cliente' => $order->customer->name,
                    'telefono' => $order->customer->phone,
                    'equipo' => $order->device_model,
                    'problema' => $order->issue_description,
                    'reparaciones' => $order->items->map(fn($item) => [
                        'nombre' => $item->description,
                        'precio' => $item->subtotal,
                    ])->toArray(),
                    'total' => $order->total,
                ]))
                ->send();
        } 
        // Case B: Standard Office A4 PDF
        else {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.order_a4', ['order' => $order]);
            $base64 = base64_encode($pdf->output());

            NyxoPrinter::to($printerNodeId)
                ->copies($copies)
                ->pdf($base64, format: 'a4')
                ->send();
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Order #{$order->number} queued successfully on workstation.",
        ]);
    }
}
```

---

## 10. Official Desktop Agent Guide: Operation, Configuration & Deployment

The **Nyxo Universal Printer** desktop agent is the native background client running locally on each cashier or Point-of-Sale (POS) terminal under Windows 10/11 (64-bit). It communicates outbound with the Laravel cloud backend and dispatches physical jobs to printers with zero open inbound router ports, zero client SSL certificates, and zero Java virtual machine dependencies.

---

### 10.1 Distribution Modes: Windows Installer vs. Portable Edition

To accommodate varying retail IT policies and enterprise security environments, the desktop agent is distributed in two official binaries:

| Feature | Windows Installer (`Setup_Win.exe`) | Portable Edition (`Portable.exe`) |
| :--- | :--- | :--- |
| **File Name** | `Nyxo_Universal_Printer_Setup_Win.exe` | `Nyxo_Universal_Printer_Portable.exe` |
| **Execution Path** | `%LOCALAPPDATA%\Programs\Nyxo Universal Printer` | Any folder, desktop, or USB flash drive |
| **Administrator Rights** | Not required (user-space install) | Not required (zero installation) |
| **Shortcuts** | Created automatically on Desktop & Start Menu | Manual (based on current directory) |
| **Windows Startup** | Integrated natively into System Tray | Supported (persists absolute path of EXE) |
| **Windows Registry** | Registered in "Add/Remove Programs" for clean uninstall | Zero registry footprint |
| **Recommended Use Case** | **Permanent POS checkouts**, cashier stations, and continuous production | **Demos**, IT support, lockdown enterprise PCs, and field testing |

> [!TIP]
> **Recommendation for the Portable Edition:** If you plan to enable the "Start with Windows" toggle in the Portable version, place the executable into a permanent folder (such as `C:\NyxoPrinter\Nyxo_Universal_Printer_Portable.exe`) beforehand so the startup registry entry remains valid if you move other files later.

---

### 10.2 Self-Hosting & Direct Downloads from Laravel

Package `nyxo-app/nyxo-printer` enables self-hosting both binaries directly from your own web server for complete infrastructure autonomy:

1. **Service Provider Web Routes:**
   - Windows Installer: `GET /downloads/Nyxo_Universal_Printer_Setup_Win.exe` (`route('nyxo-printer.download')`)
   - Portable Edition: `GET /downloads/Nyxo_Universal_Printer_Portable.exe` (`route('nyxo-printer.download-portable')`)

2. **File Storage Location:**
   Store your compiled binaries in your Laravel public directory:
   - `public/downloads/Nyxo_Universal_Printer_Setup_Win.exe`
   - `public/downloads/Nyxo_Universal_Printer_Portable.exe`

3. **Automatic Fallback:**
   If the physical files are absent in `public/downloads/`, routes seamlessly redirect to the official distribution portal configured in `config/nyxo-printer.php` (`https://printer.nyxo.ar`).

4. **Blade Download Buttons:**
```blade
<div class="flex items-center gap-3">
    <a href="{{ route('nyxo-printer.download') }}" 
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">
        💻 Download Windows Installer (.exe)
    </a>
    <a href="{{ route('nyxo-printer.download-portable') }}" 
       class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg shadow-sm">
        📦 Download Portable Edition (.exe)
    </a>
</div>
```

---

### 10.3 Tab 1: System Status & Real-Time Monitoring (`status-view`)

Upon launching the application, the home dashboard displays live operational telemetry:

- **Visual Connection Indicator:**
  - 🟢 **Online / Agent Started:** The agent is healthy and actively polling the Laravel backend every 5 seconds.
  - 🟡 **Checking / Reconnecting:** Attempting connection re-establishment following an intermittent network disruption.
  - 🔴 **Offline / Error:** The API endpoint is unreachable or the Token is invalid.
- **Live Activity Console (Log Stream):**
  - Displays real-time chronological event traces: heartbeat polling (`ping`), incoming jobs (`PrintJob #ID`), hardware spooler/socket injection, and physical output confirmation (`status = 'printed'`).
  - **"Clear" Button:** Wipes the on-screen console history for a clean visual debugging session.

---

### 10.4 Tab 2: Server Connection & 1-Click Pairing (`connection-view`)

Manage secure credentials connecting the workstation with your cloud server:

1. **Recommended Method: 1-Click Quick Pairing Code:**
   - In your Laravel administrative dashboard, each printer node features a "Copy Pairing Code" button that exports a unified Base64 string:
     ```text
     aHR0cHM6Ly9teS1zdG9yZS5jb20vYXBpL3YxL3ByaW50fG55eG9fdG9rZW5fY2FzaGllcl8wMQ==
     ```
   - In the desktop app, click **"Paste"** next to the *Quick Pairing Code* input.
   - The application instantly unpacks the payload, extracting and populating the **API Server URL** and **Authentication Token** automatically.

2. **Manual Configuration:**
   - **API Server URL:** REST API endpoint (e.g. `https://my-store.com/api/v1/print` or local IP `http://192.168.1.100:8000/api/v1/print`).
   - **Authentication / Terminal Token:** Unique 64-character alphanumeric secret key. Includes an eye toggle (`👁️`) to reveal or mask characters.

3. **Built-in Diagnostic Tool:**
   - **"🔍 Test Connection" Button:** Dispatches an immediate `GET /api/v1/print/ping`. Renders a live diagnostic badge with HTTP response latency (ms), Laravel server version, and authentication confirmation (or the exact failure reason if credentials fail).
   - **"💾 Save Connection" Button:** Encrypts and persists credentials locally, launching the automated background polling loop.

---

### 10.5 Tab 3: Printer Assignment (Dual Windows Spooler & RAW TCP Socket 9100)

Nyxo Universal Printer features independent format routing tailored to document width and media type:

#### A. Total Independence of the 2 Thermal Paper Widths and A4
- **Thermal Ticket 80 mm (48 columns):**
  - Standard point-of-sale receipt width (3 inches).
  - Accommodates full tabular layouts with aligned columns (`Qty`, `Description`, `Unit Price`, `Total`).
  - Perfect for fiscal receipts, cashier checkout tickets, and barcode/QR vouchers.
- **Thermal Ticket 58 mm (32 columns):**
  - Compact format (2 inches).
  - Employs optimized vertical formatting (`Qty` x `Item` with total on the subsequent line) to prevent text wrapping.
  - Ideal for fast-food kitchen orders, barista tickets, bar tabs, and portable terminals.
- **A4 Document (Laser / Inkjet):**
  - Full-size sheet documents (210 x 297 mm).
  - Handled silently via bundled **SumatraPDF**, enabling unattended vector PDF printing of official electronic invoices, delivery notes, and account statements without print dialogs.

#### B. Dual Architecture: Windows Spooler Drivers vs. RAW TCP Network Socket (Driverless)
For each format, select between two hardware communication channels:

1. **Channel 1: Windows Spooler Printers:**
   - Auto-discovers all drivers installed in Windows (`Termica`, `EPSON TM-T20`, `Bixolon`, `POS-80`, `HP LaserJet`, etc.).
   - Utilizes `winspool.drv` to bypass GDI rendering, injecting pure binary ESC/POS streams directly into hardware memory.
2. **Channel 2: RAW TCP Network Socket (Port 9100 - Driverless):**
   - **Zero Windows driver installation required!**
   - Directly input any network `IP:Port` (e.g. `192.168.1.100:9100` or `127.0.0.1:9100`).
   - Opens a direct TCP socket connection to hardware or emulator, transmitting binary ESC/POS in single-digit milliseconds.
   - Eliminates Windows Print Spooler freezes, hung print queues, and legacy 32-bit driver conflicts.

#### C. Mixed & Hybrid Setup Matrix
Every format operates independently and can be freely combined:

| Format | Typical Hybrid Setup | 100% Driverless Network | Local USB Workstation |
| :--- | :--- | :--- | :--- |
| **80 mm (Cashier)** | Windows USB: `Termica` | TCP RAW: `192.168.1.100:9100` | Windows USB: `EPSON TM-T20` |
| **58 mm (Kitchen)** | TCP RAW: `192.168.1.150:9100` | TCP RAW: `192.168.1.105:9100` | Windows USB: `POS-58` |
| **A4 (Back Office)** | Windows Laser: `HP LaserJet Pro` | Windows Laser: `Brother HL-L2360D` | Windows Laser: `Canon G3010` |

#### D. Intelligent Switching: Exclusive Emulator Mode
To prevent confusion between physical hardware and testing environments, the agent implements an exclusive priority toggle:
- **Activation:** Entering an address in the *⚙️ Settings* emulator field (e.g. `127.0.0.1:9100`) displays an alert banner on the *🖨️ Printers* tab and **automatically disables 80mm and 58mm dropdowns**.
- **100% Guaranteed Routing:** All outbound thermal receipt jobs route straight to the emulator socket, ignoring any physical USB printers that were previously assigned.
- **Return to Production:** Simply clear the emulator field in Settings and save: physical thermal printer dropdowns are instantly re-enabled for Windows Spooler or TCP network printing.

> [!NOTE]
> **"🔄 Refresh List" Button:** If you plug in a new USB thermal printer while the agent is running, simply click this button to refresh device discovery without restarting the application.

---

### 10.6 Tab 4: General Settings & Optional Network Target (`settings-view`)

- **Language Toggle:** Dynamically switch the user interface and system notifications between **English (EN)** and **Spanish (ES)**.
- **C# Emulator / TCP Socket Destination (Optional - Top Priority):**
  - Optional field that starts **completely empty by default**. If you are using the developer virtual emulator `ESSIThermalEmulator` or a direct driverless TCP/Ethernet printer, enter the IP:Port here (e.g. `127.0.0.1:9100` or `192.168.1.100:9100`).
  - **Priority Behavior:** When filled, it activates *Active Emulator Mode*, cleanly disabling physical thermal dropdowns in the Printers tab and routing all thermal traffic to this address. When cleared, regular physical printer routing resumes.
- **Unassigned Printer Handling:**
  - If a workstation receives a print job for a format without an assigned printer or network/emulator target, the agent rejects the job and explicitly notifies the operator that a printer must be configured in the panel, preventing unexpected silent fallbacks to local ports.
- **Start with Windows:**
  - Checkbox enabling silent background launch upon Windows user login, running minimized in the System Tray next to the clock.

---

### 10.7 Tab 5: Commercial License Control & Offline Grace Period (`license-view`)

- **Exclusive Lemon Squeezy Licensing:**
  - All licenses (including the free Developer tier and commercial subscriptions/lifetime plans) are managed and validated exclusively against the official **Lemon Squeezy** API.
  - Real-time online validation of concurrent terminal seats (`Assigned seats / Total purchased quota`).
  - Displays Hardware Terminal UID, Computer Hostname, Plan Type, and Registered Licensee Email, protected with a cryptographic HMAC hardware signature.
  - Includes a deactivation button to unbind seats when replacing workstation hardware.
- **Developer Mode (Developer Free - $0 USD):**
  - Claimable at `https://printer.nyxo.ar`, granting 1 free lifetime license per developer email, issued formally via Lemon Squeezy without requiring credit card details.
- **7-Day Offline Grace Period:**
  - If a store experiences an extended internet outage or ISP failure, the agent activates its **7-day continuous offline grace period**.
  - Validates cached cryptographic activation signatures locally, allowing retail checkouts and physical receipt printing to proceed uninterrupted until internet connectivity is restored.

---

## 11. Concurrency Control, Resilience & Lifecycle Events

### 11.1 Concurrency & Duplicate Prevention
In high-volume stores with multiple cashiers printing orders simultaneously:

In [`PrintJobApiController::index()`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Http/Controllers/PrintJobApiController.php#L64):
```php
$jobs = DB::transaction(function () use ($node) {
    $pendingJobs = PrintJob::deliverable($node->id)
        ->lockForUpdate() // Row-level pessimistic lock
        ->get();

    if ($pendingJobs->isEmpty()) {
        return collect([]);
    }

    PrintJob::whereIn('id', $pendingJobs->pluck('id'))
        ->update([
            'status' => 'processing',
            'attempts' => DB::raw('attempts + 1'),
        ]);

    return $pendingJobs;
});
```

---

### 11.2 Orphan Job Rescue
If a POS computer experiences a sudden power loss or OS crash while printing, the job remains stuck in `status = 'processing'`.

The scope [`PrintJob::scopeDeliverable()`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Models/PrintJob.php#L89) rescues stalled jobs:
- If a job has been in `processing` longer than `config('nyxo-printer.timeout_minutes')` (default 3 minutes) and has not exceeded `max_attempts` (default 3 attempts), it is freed and re-queued automatically upon reconnection.

---

### 11.3 Automated Database Maintenance (Pruning)
Keep your database lean and performant:
```bash
php artisan nyxo-printer:clean --days=7
```
Schedule daily in `routes/console.php` (Laravel 11/12/13):
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('nyxo-printer:clean --days=7')->daily();
```

---

### 11.4 Print Lifecycle Events
| Event | Dispatched When | Properties |
| :--- | :--- | :--- |
| `Nyxo\Printer\Events\PrintJobCreated` | Job is inserted into queue. | `public PrintJob $job` |
| `Nyxo\Printer\Events\PrintJobPrinted` | Hardware confirms paper output. | `public PrintJob $job` |
| `Nyxo\Printer\Events\PrintJobFailed` | Hardware fault, jam, or timeout. | `public PrintJob $job`, `public ?string $errorMessage` |

#### Audit Listener Example:
```php
namespace App\Listeners;

use Nyxo\Printer\Events\PrintJobFailed;
use Illuminate\Support\Facades\Log;

class LogPrintFailure
{
    public function handle(PrintJobFailed $event): void
    {
        Log::channel('printers')->error("Print failure on Node #{$event->job->printer_node_id}", [
            'job_id' => $event->job->id,
            'format' => $event->job->format,
            'error' => $event->errorMessage,
        ]);
    }
}
```

---

## 12. Troubleshooting & Frequently Asked Questions (FAQ)

### 🔴 Why does my thermal receipt printer output `%PDF-1.7` raw text?
**Cause:** A PDF document was queued with `content_type = 'escpos_base64'`. Thermal printers only understand ESC/POS character streams and lack PDF raster engines.  
**Solution:**
- For thermal printers (80mm/58mm), use `ThermalBuilder` or `PrintTemplateInterface`.
- If you must print a PDF on thermal paper, set `format: 'ticket_80mm'` and `content_type: 'pdf_base64'`; the Windows agent will invoke SumatraPDF to scale the document to paper width.

---

### 🔴 Error `401 Unauthorized / Print token not provided` behind Apache
**Cause:** Apache/FastCGI discards the `Authorization: Bearer` HTTP header by default in unencrypted local networks.  
**Solution:** Add these directives to `public/.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```
*Nyxo's `CheckPrintToken` middleware natively inspects `REDIRECT_HTTP_AUTHORIZATION`.*

---

### 🔴 Clipboard copy fails in local LAN (`navigator.clipboard is undefined`)
**Cause:** Modern browsers restrict `navigator.clipboard` to Secure Contexts (`https://` or `http://localhost`). On LAN HTTP (`http://192.168.1.50`), it is undefined.  
**Solution:** Use DOM textarea fallback:
```javascript
window.copyToClipboard = async function(text) {
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (e) {}
    }
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    const success = document.execCommand('copy');
    document.body.removeChild(textarea);
    return success;
};
```

---

### 🔴 Laravel 13 `MissingAttributeException`
**Cause:** Laravel 13 enables `Model::shouldBeStrict(true)` by default. If legacy code accesses removed columns (e.g. `pie_orden`), an exception is thrown.  
**Solution:** The official models `PrinterNode` and `PrintJob` in package `nyxo-app/nyxo-printer` only expose validated physical database columns.

---

### 🔴 Multi-Tenant SaaS Isolation
1. In `config/nyxo-printer.php`, set `'tenant_column' => 'company_id'`.
2. When creating nodes, associate the tenant ID:
   ```php
   PrinterNode::create([
       'name' => 'Store 2 Counter',
       'empresa_id' => $currentTenant->id,
       'is_active' => true,
   ]);
   ```
3. In your dashboard, filter by tenant:
   ```php
   $nodes = PrinterNode::where('empresa_id', auth()->user()->empresa_id)->get();
   ```

---

*Official Engineering Manual authored for the commercial distribution and enterprise deployment of **Nyxo Universal Printer**.*
