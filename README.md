# 🖨️ Nyxo Universal Printer for Laravel

<p align="center">
  <a href="https://printer.nyxo.ar">
    <img src="https://raw.githubusercontent.com/nyxo-app/nyxo-printer/main/art/banner.png" alt="Nyxo Universal Printer Banner" width="100%" onerror="this.style.display='none'">
  </a>
</p>

<p align="center">
  <strong>Silent Thermal Receipt (ESC/POS) & A4 PDF Printing for Modern Laravel Applications.</strong><br>
  Zero browser popups (`Ctrl+P`), zero Java dependencies, zero SSL mixed-content issues, and an in-screen visual emulator for rapid development.
</p>

<p align="center">
  <a href="https://packagist.org/packages/nyxo-app/nyxo-printer"><img src="https://img.shields.io/packagist/v/nyxo-app/nyxo-printer.svg?style=flat-square&color=6366f1" alt="Latest Version on Packagist"></a>
  <a href="https://packagist.org/packages/nyxo-app/nyxo-printer"><img src="https://img.shields.io/packagist/dt/nyxo-app/nyxo-printer.svg?style=flat-square&color=10b981" alt="Total Downloads"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square" alt="License: MIT"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2%2B-8b5cf6.svg?style=flat-square" alt="PHP Version"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-f43f5e.svg?style=flat-square" alt="Laravel Support"></a>
  <a href="https://printer.nyxo.ar"><img src="https://img.shields.io/badge/Website-printer.nyxo.ar-0284c7.svg?style=flat-square" alt="Official Website"></a>
</p>

<p align="center">
  <a href="#-quickstart-guide">Quickstart</a> •
  <a href="#-features">Features</a> •
  <a href="#-desktop-agent-download">Desktop Agent</a> •
  <a href="#-code-examples">Examples</a> •
  <a href="#-livewire-integration">Livewire</a> •
  <a href="README.es.md">Versión en Español 🇪🇸</a>
</p>

<p align="center">
  <a href="https://printer.nyxo.ar/docs/MANUAL_INTEGRATION_100_EN.pdf">
    <img src="https://img.shields.io/badge/Official_PDF_Manual-English_(18_pgs)-0284c7?style=for-the-badge&logo=adobe-acrobat-reader&logoColor=white" alt="Official PDF Manual (English)">
  </a>
  <a href="https://printer.nyxo.ar/docs/MANUAL_INTEGRACION_100_ES.pdf">
    <img src="https://img.shields.io/badge/Manual_PDF_Oficial-Espa%C3%B1ol_(19_p%C3%A1gs)-6366f1?style=for-the-badge&logo=adobe-acrobat-reader&logoColor=white" alt="Manual PDF Oficial (Español)">
  </a>
  <a href="docs/MANUAL_INTEGRATION_100_EN.md">
    <img src="https://img.shields.io/badge/Full_Manual-Markdown-10b981?style=for-the-badge&logo=markdown&logoColor=white" alt="Full Markdown Manual">
  </a>
</p>


---

## ⚡ The Problem vs. The Nyxo Solution

Printing physical receipts, kitchen orders, barcodes, or A4 invoices from modern cloud web applications (Laravel, Livewire, Vue, React, Inertia) is traditionally painful:

| The Traditional Web Printing Pain | The Nyxo Universal Printer Solution |
| :--- | :--- |
| ❌ **`window.print()`:** Forces browser dialogs (`Ctrl+P`), freezes cashiers, and requires manual Enter keystrokes. | 🚀 **100% Silent Instant Printing:** Dispatches directly to hardware in **0.2 seconds** without user intervention. |
| ❌ **Mixed Content / SSL Blocks:** Cloud HTTPS apps cannot connect to plain HTTP `localhost` or local LAN printer IPs. | 🛡️ **Zero SSL Certificates Required:** Cloud applications enqueue jobs safely via standard REST API; the local desktop agent pulls them seamlessly. |
| ❌ **Java & Certificate Hell (QZ Tray):** Demands heavy JRE installations on client terminals and self-signed certs. | 🪶 **Native Lightweight Desktop Agent:** Native Windows spooler (`winspool.drv`) & SumatraPDF integration. No Java required. |
| ❌ **Perpetual Monthly Subscriptions (PrintNode):** Expensive recurring monthly charges for every single terminal. | 🎁 **Developer Friendly:** Free permanent developer seat ($0 USD via Polar) for testing and development. |
| ❌ **Wasted Paper During Development:** You need a physical printer on your desk just to adjust ticket layout. | 🖥️ **In-Screen Visual Emulator:** Preview and debug thermal tickets in HTML or on a virtual POS screen without wasting paper. |

---

## 🏗️ Architecture & How It Works

```text
┌────────────────────────────────────────────────────────────────────────┐
│                   YOUR LARAVEL APPLICATION (Cloud SaaS)                │
│                                                                        │
│   NyxoPrinter::to($cashierNode)                                        │
│       ->title('COFFEE SHOP')                                           │
│       ->table($items)                                                  │
│       ->total($amount)                                                 │
│       ->qr('https://invoice.gov/123')                                  │
│       ->openDrawer()                                                   │
│       ->cut()                                                          │
│       ->send();                                                        │
│                                                                        │
│   Saves atomic binary payload to DB with lockForUpdate() concurrency   │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ Polling via Secure Token (X-Tenant-Token)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│             NYXO UNIVERSAL PRINTER AGENT (Client PC / POS)             │
│                 Download from: https://printer.nyxo.ar                │
│                                                                        │
│   - Pulls print jobs silently in background                            │
│   - Permanent Free Developer Seat ($0 USD via Polar)                   │
│   - Injects RAW ESC/POS commands directly to Windows Spooler           │
│   - Injects A4 PDFs silently via background SumatraPDF                 │
└───────────────────┬───────────────────────────────┬────────────────────┘
                    │                               │
                    ▼                               ▼
    ┌───────────────────────────────┐   ┌───────────────────────────────┐
    │  THERMAL RECEIPT PRINTER      │   │  CONVENTIONAL A4 PRINTER      │
    │  (EPSON, XPrinter, POS-80)    │   │  (Laser, Inkjet, Office)      │
    │  • 80mm / 58mm Paper          │   │  • Invoices & Packing Slips   │
    │  • Automatic Guillotine Cut   │   │  • Contracts & Delivery Notes │
    │  • RJ11 Cash Drawer Kick      │   │  • High quality PDF output    │
    └───────────────────────────────┘   └───────────────────────────────┘
```

## 🚀 Implementation Workflow: Developer Setup vs. Production Deployment

To ensure a seamless experience for both software developers and retail clients, the ecosystem is divided into two distinct phases:

### 🛠️ PHASE 1: Developer Onboarding (Your Development Machine / Testing)
*Goal: Code, design ticket templates, and test silent printing without wasting thermal paper rolls.*

1. **Install the package in your Laravel project:**
   ```bash
   composer require nyxo-app/nyxo-printer
   php artisan nyxo-printer:install
   php artisan migrate
   ```
2. **Claim your Free Developer License ($0 USD):**
   Request your official permanent key at [printer.nyxo.ar](https://printer.nyxo.ar) to activate your test terminal.
3. **Download Nyxo Universal Printer:**
   The silent Windows background agent that communicates with physical printer hardware: [Download Installer](https://printer.nyxo.ar).
4. **Download ESSI Thermal Emulator (Virtual On-Screen POS Printer):**
   No physical thermal printer plugged in today? Run the cross-platform emulator (Win/Mac/Linux) listening on TCP port `9100` to preview 80mm/58mm tickets and export PDFs on the fly.

---

### 🏢 PHASE 2: Production Deployment (At Client Store / Retail POS)
*Goal: Zero friction, zero command-line tools, and zero post-sale support liability.*

At your client's physical retail terminals (cashier counters, kitchens, dispatch desks), **the client NEVER needs Composer, terminal commands, or the emulator**:

> ⚠️ **IMPORTANT:** On the client's PC, **ONLY the `Nyxo Universal Printer` executable is installed**:
> 1. The store owner or technician downloads and runs the official Windows installer (`.exe`).
> 2. Enters your Laravel application URL and the Commercial License Key purchased from Polar ($99, $199, or $399 USD).
> 3. Done! The physical terminal prints instantly and silently without any browser print popups (`Ctrl+P`).

---

## 📦 Installation

### 1. Require via Composer

```bash
composer require nyxo-app/nyxo-printer
```

### 2. Run the Interactive Installer

```bash
php artisan nyxo-printer:install
```

This publishes:
* `config/nyxo-printer.php` (Custom route prefix, table names, timeouts, default paper width).
* Database migrations (`printer_nodes` and `print_jobs`).
* Livewire / Blade components.

### 3. Run Migrations

```bash
php artisan migrate
```

---

## 💻 Printer Nodes, Tokens & Windows Agent Setup (Step-by-Step)

To dispatch print jobs to physical printers (thermal receipts or standard A4) without opening ports or configuring static IPs/NAT on the client's router, Nyxo uses a **Token-Authenticated Printer Node Architecture**.

```
┌────────────────────────────────────────────────────────┐
│             Laravel Server (Cloud / VPS)               │
│  • PrinterNode model with unique per-terminal Token    │
│  • Enqueues atomic print jobs in `print_jobs` table    │
└───────────────────────────┬────────────────────────────┘
                            ▲
                            │  Outbound HTTPS (Long-Polling / Heartbeat)
                            │  Header: Authorization: Bearer <print_token>
                            ▼
┌────────────────────────────────────────────────────────┐
│             Cashier PC / Point of Sale                 │
│  • NyxoUniversalPrinter.exe (Desktop Print Agent)      │
│  • Connected to physical printer via USB or Local LAN  │
│  • Thermal ESC/POS (80mm/58mm) or Standard A4 Printer  │
└────────────────────────────────────────────────────────┘
```

---

### Step 1: Create the Node & Generate the Token in Laravel

Each physical workstation (Cashier 1, Kitchen, Dispatch) is registered as a `PrinterNode`. Upon creation, Laravel automatically generates a cryptographically secure 60-character token and the 1-click pairing string:

```php
use Nyxo\Printer\Models\PrinterNode;

// Inside a Seeder, Controller or via Tinker:
$node = PrinterNode::create([
    'name' => 'Cashier 1 - Main Front Desk',
    // 'empresa_id' => 1, // Optional: if operating in a multi-tenant environment
    'is_active' => true,
]);

// 1. Raw cryptographic token (60 random chars for manual setup or external APIs):
$token = $node->print_token;
// Example: "7kL9vP2xR8qW1yT5mN4zB6cA0dF3gH7jK2lM5nP8rS1tV4wY7bC0eG3hJ6kL9mN"

// 2. 1-Click Pairing String (Recommended: URL + Token bundled in Base64):
$pairingString = $node->pairing_string; // or $node->codigo_enlace
// Example: "aHR0cHM6Ly9teS1wb3MuY29tL2FwaS92MS9wcmludHw3a0w5dlAyeFI4..."
```

> 💡 **Tip:** In your admin dashboard (Filament, Nova or Blade), add a **"Copy Pairing Code"** button or QR code so technicians can link cashier PCs in seconds without typing URLs or tokens by hand.

---

### Step 2: Where & How to Configure the Token on the Windows PC

On the Windows computer physically connected to the thermal or A4 printer:

1. **Launch Nyxo Universal Printer** (`NyxoUniversalPrinter.exe`).
2. Navigate to the **⚙️ Connection / Settings** tab.
3. **Link the Workstation (2 available methods):**
   - **Method A (Recommended - 1 Click):** Paste the string obtained from `$node->pairing_string` into the **"Quick Connect Code"** field and click **"Connect"**. The desktop agent automatically parses the backend URL and the authentication token.
   - **Method B (Manual):** Enter your Laravel API endpoint URL (e.g. `https://my-pos.com/api/v1/print`) and paste `$node->print_token` in the **Token** field.
4. From the **Windows Printer** dropdown, select the local device (e.g. *POS-80*, *Epson TM-T20*, *Generic Text Only*, or the *ESSI Thermal Emulator* during development).
5. Click **"Save & Connect"**.

---

### Step 3: Real-Time Heartbeat & Online Status

As soon as the Windows agent connects, it sends an automatic ping every 5 seconds to your Laravel server (`GET /api/v1/print/ping`). Laravel updates `last_ping_at` quietly without triggering heavy model observers.

You can check whether the workstation is currently online from anywhere in your Laravel code:

```php
// Checks if the physical terminal reported a heartbeat within the last 2 minutes:
if ($node->is_online) {
    // 🟢 Terminal connected, desktop agent running, ready to print
} else {
    // 🔴 PC powered off, agent closed, or no internet connection
}
```

In your Blade templates or Livewire views:

```blade
<div class="flex items-center gap-2">
    <span class="w-3 h-3 rounded-full {{ $node->is_online ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
    <span>{{ $node->name }} ({{ $node->is_online ? '🟢 Online' : '🔴 Offline' }})</span>
</div>
```

---

### Step 4: Security, Multi-Terminal Isolation & Token Rotation

- **Strict Isolation:** Each workstation only receives print jobs (`PrintJob`) queued specifically for its own `printer_node_id`. Register 2 will never intercept receipts sent to Register 1.
- **Zero Port Forwarding:** All network communication originates from the client PC to Laravel over outbound HTTPS (standard port 443). Works out-of-the-box behind home routers, NAT/CGNAT, mobile 4G/5G hotspots, and strict corporate firewalls.
- **Instant Token Revocation:** If a computer is decommissioned, stolen, or replaced, you can revoke its credentials instantly without touching any other registers:

```php
// Rotate the token (the previous desktop client is immediately disconnected):
$node->update([
    'print_token' => PrinterNode::generateToken(),
]);

// Or temporarily deactivate the node:
$node->update(['is_active' => false]);
```

---

## 🧾 Code Examples

### A) Fluent Thermal Receipt (ESC/POS)

Design clean, professional receipts with a fluent, chainable API. All Spanish and Latin characters (`ñ`, accents, `$`) are automatically converted to `CP850` / `WPC1252`:

```php
use Nyxo\Printer\Facades\NyxoPrinter;

NyxoPrinter::to($nodeId)
    ->width(80) // 80mm or 58mm
    ->title('NYXO BISTRO & COFFEE', doubleWidth: true, doubleHeight: true)
    ->center('Tax ID: 30-71829384-9')
    ->text('Date: ' . now()->format('d/m/Y H:i') . ' - Order #1042')
    ->line()
    ->table([
        ['nombre' => 'Double Espresso', 'cantidad' => 2, 'precio' => 7000],
        ['nombre' => 'Toasted Ham & Cheese', 'cantidad' => 1, 'precio' => 4500],
        ['nombre' => 'Artisan Croissant', 'cantidad' => 3, 'precio' => 6000],
    ])
    ->line()
    ->total(17500, label: 'TOTAL DUE:')
    ->feed(1)
    ->qr('https://nyxo.ar/verify/1042', size: 6) // Fiscal / Payment QR
    ->barcode('00010429', type: 'CODE39')
    ->center('Thank you for your visit!')
    ->openDrawer() // Send electrical pulse to RJ11 cash drawer
    ->cut()        // Automatic guillotine cut
    ->send();
```

---

### B) Silent A4 PDF Printing

Print customer invoices, warranty certificates, or packing slips directly to standard office printers:

```php
use Nyxo\Printer\Facades\NyxoPrinter;

// 1. From an existing file on disk:
NyxoPrinter::to($nodeId)
    ->copies(2)
    ->pdfFile(storage_path('app/invoices/inv_4059.pdf'))
    ->send();

// 2. From Base64 string (DomPDF, Snappy, Spatie Browsershot, etc.):
$pdfBase64 = base64_encode($dompdf->output());

NyxoPrinter::to($nodeId)
    ->pdf($pdfBase64)
    ->send();
```

---

### C) In-Browser Preview (Develop Without Paper!)

Test your layout and formatting directly in the browser without printing on physical rolls:

```php
use Nyxo\Printer\Facades\NyxoPrinter;

Route::get('/preview-ticket', function () {
    return NyxoPrinter::preview(function ($ticket) {
        $ticket->width(80)
               ->title('DEVELOPMENT PREVIEW')
               ->text('Adjusting layout on screen!')
               ->table([
                   ['nombre' => 'Test Item 1', 'precio' => 1200],
                   ['nombre' => 'Test Item 2', 'precio' => 3400],
               ])
               ->total(4600)
               ->qr('https://printer.nyxo.ar')
               ->cut();
    }, width: 80);
});
```

*Renders an interactive, photorealistic thermal paper preview directly in your browser with monospace typography, dividers, and QR codes.*

---

### D) Reusable Print Templates

Organize your business logic using clean, testable template classes:

```php
use Nyxo\Printer\Contracts\PrintTemplateInterface;
use Nyxo\Printer\Builders\ThermalBuilder;

class KitchenOrderTemplate implements PrintTemplateInterface
{
    public function __construct(protected array $order) {}

    public function build(ThermalBuilder $ticket): void
    {
        $ticket->center('*** KITCHEN TICKET ***', bold: true, doubleHeight: true)
               ->text('TABLE #' . $this->order['table'] . ' | Waiter: ' . $this->order['waiter'])
               ->doubleLine()
               ->table($this->order['dishes'])
               ->feed(1)
               ->beep(times: 2) // Sound acoustic buzzer in kitchen!
               ->cut();
    }
}

// Dispatch anywhere:
NyxoPrinter::to($kitchenNodeId)
    ->template(new KitchenOrderTemplate($orderData))
    ->send();
```

---

## 🎨 Livewire Integration

Nyxo Universal Printer includes a ready-to-use **Livewire 3 & 4 Modal Component** styled with Tailwind CSS, supporting node selection, format switching, and real-time online status indicators.

### 1. Include the modal in your layout:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<livewire:nyxo-printer-modal />
```

### 2. Trigger from any button or Alpine component:

```blade
<button wire:click="$dispatch('open-print-modal', {
    documentId: {{ $order->id }},
    documentType: 'order',
    format: 'ticket_80mm'
})">
    🖨️ Print Receipt
</button>
```

### 3. Handle the print request in your host Livewire component:

When the cashier clicks "Print" in the modal, it dispatches the `nyxo-print-requested` event with the user's selected node and format. Simply listen to it to render and queue the job:

```php
use Livewire\Attributes\On;
use Nyxo\Printer\Facades\NyxoPrinter;
use App\Models\Order;

#[On('nyxo-print-requested')]
public function handlePrintRequest(array $payload): void
{
    $order = Order::findOrFail($payload['documentId']);
    $nodeId = $payload['printerNodeId'];
    $format = $payload['format']; // 'ticket_80mm', 'ticket_58mm', 'a4'

    NyxoPrinter::to($nodeId)
        ->width($format === 'ticket_58mm' ? 58 : 80)
        ->title(config('app.name'))
        ->table($order->items->map(fn($i) => ['name' => $i->name, 'price' => $i->price])->toArray())
        ->total($order->total)
        ->cut()
        ->send();
}
```

---


## 🛡️ Concurrency & High-Volume Resiliency

* **Atomic Database Locks:** Work retrieval uses `lockForUpdate()` within atomic database transactions, preventing duplicate prints even under high concurrency.
* **Orphan Job Rescue:** If a client POS workstation experiences a power outage or crash while printing, Nyxo automatically re-queues the job after `config('nyxo-printer.timeout_minutes')`.
* **Automatic Pruning:** Keep your database lean and performant with the built-in pruner:

```bash
php artisan nyxo-printer:clean --days=7
```

Add to your `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('nyxo-printer:clean --days=7')->daily();
```

---

## 📡 Lifecycle Events

Hook into your application workflows by listening to native Laravel events:

| Event | Dispatched When |
| :--- | :--- |
| `Nyxo\Printer\Events\PrintJobCreated` | A job is successfully added to the print queue. |
| `Nyxo\Printer\Events\PrintJobPrinted` | The physical desktop agent confirms the paper has been printed. |
| `Nyxo\Printer\Events\PrintJobFailed` | Hardware failure, paper jam, or timeout reported by the printer. |

---

## ⚙️ Configuration Reference

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
    'default_width' => 80, // 80mm or 58mm
    'codepage' => 'CP850', // UTF-8 to CP850 transliteration
];
```

---

## 🤝 Community & Commercial Support

* **Issues & Bugs:** [GitHub Issues](https://github.com/nyxo-app/nyxo-printer/issues)
* **Desktop Agent & Commercial Licenses:** [printer.nyxo.ar](https://printer.nyxo.ar)
* **Lead Magnet:** Claim your 1-seat free developer key with zero credit card at [printer.nyxo.ar](https://printer.nyxo.ar).

---

## 📄 License

The Nyxo Universal Printer Laravel Package is open-sourced software licensed under the [MIT License](LICENSE.md).  
The Nyxo Universal Printer Desktop Agent is proprietary commercial software licensed via [Polar](https://printer.nyxo.ar).
