# 🖨️ Nyxo Universal Printer para Laravel

<p align="center">
  <a href="https://printer.nyxo.ar">
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
  <a href="https://printer.nyxo.ar"><img src="https://img.shields.io/badge/Web_Oficial-printer.nyxo.ar-0284c7.svg?style=flat-square" alt="Web Oficial"></a>
</p>

<p align="center">
  <a href="#-guía-rápida-de-instalación">Instalación</a> •
  <a href="#-características-principales">Características</a> •
  <a href="#-descarga-del-agente-de-escritorio">Agente Desktop</a> •
  <a href="#-ejemplos-de-código">Ejemplos</a> •
  <a href="#-componente-livewire">Livewire</a> •
  <a href="README.md">English Version 🇬🇧</a>
</p>

<p align="center">
  <a href="https://printer.nyxo.ar/docs/MANUAL_INTEGRACION_100_ES.pdf">
    <img src="https://img.shields.io/badge/Manual_PDF_Oficial-Espa%C3%B1ol_(19_p%C3%A1gs)-6366f1?style=for-the-badge&logo=adobe-acrobat-reader&logoColor=white" alt="Manual PDF Oficial (Español)">
  </a>
  <a href="https://printer.nyxo.ar/docs/MANUAL_INTEGRATION_100_EN.pdf">
    <img src="https://img.shields.io/badge/Official_PDF_Manual-English_(18_pgs)-0284c7?style=for-the-badge&logo=adobe-acrobat-reader&logoColor=white" alt="Official PDF Manual (English)">
  </a>
  <a href="docs/MANUAL_INTEGRACION_100_ES.md">
    <img src="https://img.shields.io/badge/Manual_Completo-Markdown-10b981?style=for-the-badge&logo=markdown&logoColor=white" alt="Manual Completo Markdown">
  </a>
</p>


---

## ⚡ El Dolor Tradicional vs. La Solución Nyxo

Imprimir tickets fiscales, comandas de cocina o comprobantes A4 desde una aplicación web moderna (Laravel, Livewire, Vue, React, Inertia) siempre ha sido una pesadilla técnica:

| El Problema Habitual en la Web | La Solución Nyxo Universal Printer |
| :--- | :--- |
| ❌ **`window.print()`:** Fuerza la ventana de diálogo (`Ctrl+P`), frena a los cajeros y exige presionar Enter manualmente. | 🚀 **Impresión 100% Silenciosa:** Se despacha directamente a la impresora física en **0.2 segundos** sin intervención del usuario. |
| ❌ **Bloqueos de SSL / Contenido Mixto:** Los navegadores en HTTPS impiden conectar por HTTP plano a `localhost` o IPs de la red local. | 🛡️ **Cero Certificados SSL:** La app en la nube encola los trabajos vía API segura; el agente local los retira e inyecta sin fricciones. |
| ❌ **Dependencia de Java (QZ Tray):** Exige instalar pesadas máquinas virtuales de Java en cada terminal y lidiar con certificados autofirmados. | 🪶 **Agente Nativo Ultraliviano:** Conexión directa al Spooler de Windows (`winspool.drv`) y a SumatraPDF. Cero Java. |
| ❌ **Suscripciones Mensuales Eternas (PrintNode):** Costos recurrentes mensuales en dólares por cada máquina cliente conectada. | 🎁 **Amigable con el Desarrollador:** Puesto permanente a $0 USD vía Lemon Squeezy para desarrollo, pruebas y puesta en marcha. |
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
│              Descarga directa en: https://printer.nyxo.ar             │
│                                                                        │
│   - Retira los trabajos en segundo plano silenciosamente               │
│   - Puesto Gratuito Permanente para Desarrolladores ($0 Lemon Squeezy) │
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

## 🚀 Flujo de Implementación: Entorno de Desarrollo vs. Producción

Para que tu experiencia y la de tus clientes sea óptima, el ecosistema se divide en dos fases bien diferenciadas:

### 🛠️ FASE 1: Inicio para Desarrolladores (Tu Máquina de Trabajo / Testing)
*Objetivo: Programar, diseñar plantillas de tickets y probar todo en local sin gastar rollos de papel térmico.*

1. **Instala el paquete en tu proyecto Laravel:**
   ```bash
   composer require nyxo-app/nyxo-printer
   php artisan nyxo-printer:install
   php artisan migrate
   ```
2. **Obtén tu Licencia Gratuita de Desarrollador ($0 USD):**
   Solicita tu clave oficial en [printer.nyxo.ar](https://printer.nyxo.ar) para activar tu terminal de pruebas.
3. **Descarga Nyxo Universal Printer:**
   El agente de Windows que gestiona la impresión silenciosa con el hardware real: [Descargar Instalador](https://printer.nyxo.ar).
4. **Descarga ESSI Thermal Emulator (Simulador de Tickets en Pantalla):**
   ¿No tienes la impresora térmica conectada en tu escritorio? Ejecuta el emulador bilingüe (Win/Mac/Linux) que escucha en el puerto TCP `9100` y renderiza el ticket en vivo de 80mm o 58mm con exportación a PDF.

---

### 🏢 FASE 2: Puesta en Producción (En el Comercio / Cliente Final)
*Objetivo: Cero fricción, cero comandos técnicos y cero carga de soporte.*

En las terminales físicas de punto de venta (cajas, mostradores, cocinas) de tus clientes **NO se necesita el emulador, ni Composer, ni terminales de comandos**:

> ⚠️ **IMPORTANTE:** En la computadora del cliente final **ÚNICAMENTE se instala el ejecutable `Nyxo Universal Printer`**:
> 1. El cliente o técnico descarga y ejecuta el instalador oficial de Windows (`.exe`).
> 2. Pega la URL de tu aplicación web Laravel y la Clave de Licencia Comercial adquirida ($99, $199 o $399 USD).
> 3. ¡Listo! La terminal física ya imprime automáticamente sin abrir cuadros de diálogo del navegador (`Ctrl+P`).

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

## 💻 Configuración de Nodos, Tokens y Agente Windows (Paso a Paso)

Para enviar trabajos de impresión a impresoras físicas (térmicas o convencionales) sin abrir puertos ni lidiar con IPs fijas o NAT en el router del cliente, Nyxo utiliza una arquitectura de **Nodos de Impresión autenticados por Token**.

```
┌────────────────────────────────────────────────────────┐
│             Servidor Laravel (Nube / VPS)              │
│  • Modelo PrinterNode con Token único por terminal     │
│  • Encola trabajos binarios en la tabla `print_jobs`   │
└───────────────────────────┬────────────────────────────┘
                            ▲
                            │  HTTPS saliente (Outbound Long-Polling / Heartbeat)
                            │  Header: Authorization: Bearer <print_token>
                            ▼
┌────────────────────────────────────────────────────────┐
│             PC de Caja / Punto de Venta                │
│  • NyxoUniversalPrinter.exe (Agente de Impresión)      │
│  • Conectada a la impresora física por USB o Red Local │
│  • Impresora Térmica 80mm / 58mm o Convencional A4     │
└────────────────────────────────────────────────────────┘
```

---

### Paso 1: Crear el Nodo y Obtener el Token en Laravel

Cada puesto físico (Caja 1, Cocina, Despacho) se registra como un `PrinterNode`. Al crearse, Laravel genera automáticamente un token criptográfico de 60 caracteres y el código de enlace:

```php
use Nyxo\Printer\Models\PrinterNode;

// En un Seeder, Controlador o mediante Tinker:
$nodo = PrinterNode::create([
    'name' => 'Caja 1 - Mostrador Principal',
    // 'empresa_id' => 1, // Opcional si operas en modo multi-tenant
    'is_active' => true,
]);

// 1. Token criptográfico puro (para configuración manual o APIs externas):
$token = $nodo->print_token;
// Resultado: "7kL9vP2xR8qW1yT5mN4zB6cA0dF3gH7jK2lM5nP8rS1tV4wY7bC0eG3hJ6kL9mN"

// 2. Código de Enlace Rápido (Recomendado: URL + Token empaquetados en Base64):
$codigoEnlace = $nodo->codigo_enlace; 
// Resultado: "aHR0cHM6Ly9taS1wb3MuY29tL2FwaS92MS9wcmludHw3a0w5dlAyeFI4..."
```

> 💡 **Tip:** En tu panel de administración (Filament, Nova o Blade), agrega un botón para **"Copiar Código de Enlace"** o un código QR para que el usuario o técnico configure la PC en 2 clics.

---

### Paso 2: Dónde y Cómo se Coloca el Token en la PC de la Impresora

En la computadora con Windows donde está enchufada la impresora térmica o A4:

1. **Abrir Nyxo Universal Printer** (`NyxoUniversalPrinter.exe`).
2. Ir a la pestaña **⚙️ Conexión / Configuración**.
3. **Vincular la Terminal (2 métodos disponibles):**
   - **Método A (Recomendado - 1 Clic):** Pegar el string copiado de `$nodo->codigo_enlace` en el campo **"Código de Enlace Rápido"** y pulsar **"Vincular"**. El agente decodifica automáticamente la URL de tu servidor Laravel y el token de autenticación.
   - **Método B (Manual):** Introducir la URL base del endpoint (ej. `https://mi-sistema.com/api/v1/print`) y en el campo **Token** pegar el `$nodo->print_token`.
4. En el selector de **Impresora de Windows**, elegir el dispositivo local asignado (ej. *POS-80*, *Epson TM-T20*, *Generic Text Only* o el *Emulador Térmico ESSI* durante desarrollo).
5. Hacer clic en **"Guardar y Conectar"**.

---

### Paso 3: Verificación de Estado en Tiempo Real (Heartbeat)

En cuanto el agente de Windows se vincula, comienza a emitir un pulso automático cada 5 segundos hacia tu servidor Laravel (`GET /api/v1/print/ping`). Laravel actualiza la columna `last_ping_at` silenciosamente.

Puedes consultar el estado de conexión del puesto en cualquier momento:

```php
// Comprueba si la terminal física emitió un pulso en los últimos 2 minutos:
if ($nodo->is_online) {
    // 🟢 Terminal conectada, agente activo y listo para imprimir
} else {
    // 🔴 Computadora apagada, agente cerrado o sin conexión a internet
}
```

En tus vistas Blade o componentes Livewire:

```blade
<div class="flex items-center gap-2">
    <span class="w-3 h-3 rounded-full {{ $nodo->is_online ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
    <span>{{ $nodo->name }} ({{ $nodo->is_online ? '🟢 Conectado' : '🔴 Desconectado' }})</span>
</div>
```

---

### Paso 4: Seguridad, Aislamiento y Rotación de Tokens

- **Aislamiento Estricto:** Cada caja solo recibe los trabajos de impresión (`PrintJob`) asignados a su respectivo `printer_node_id`. Caja 2 jamás interceptará un ticket enviado a Caja 1.
- **Sin Apertura de Puertos:** Toda la comunicación se origina desde la PC del cliente hacia Laravel vía HTTPS saliente (puerto 443 estándar). Es compatible con routers domésticos, CGNAT, hotspots 4G/5G y redes corporativas con firewall restrictivo.
- **Rotación Inmediata de Tokens:** Si una computadora es dada de baja o reemplazada, puedes invalidar su acceso al instante:

```php
// Regenerar un nuevo token para el puesto (el agente anterior queda revocado al instante):
$nodo->update([
    'print_token' => PrinterNode::generateToken(),
]);

// O desactivar temporalmente el nodo:
$nodo->update(['is_active' => false]);
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
               ->qr('https://printer.nyxo.ar')
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

### 3. Procesar la solicitud de impresión en tu componente Livewire anfitrión:

Cuando el usuario presiona "Imprimir" en el modal, este emite el evento `nyxo-print-requested` con los datos de caja y formato elegidos. Solo debes escuchar dicho evento para renderizar y encolar el trabajo:

```php
use Livewire\Attributes\On;
use Nyxo\Printer\Facades\NyxoPrinter;
use App\Models\Orden;

#[On('nyxo-print-requested')]
public function procesarImpresion(array $payload): void
{
    $orden = Orden::findOrFail($payload['documentId']);
    $nodoId = $payload['printerNodeId'];
    $formato = $payload['format']; // 'ticket_80mm', 'ticket_58mm', 'a4'

    NyxoPrinter::to($nodoId)
        ->width($formato === 'ticket_58mm' ? 58 : 80)
        ->title(config('app.name'))
        ->table($orden->detalles->map(fn($i) => ['nombre' => $i->descripcion, 'precio' => $i->subtotal])->toArray())
        ->total($orden->total)
        ->cut()
        ->send();
}
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
* **Descarga del Agente y Licencias Comerciales:** [printer.nyxo.ar](https://printer.nyxo.ar)
* **Lead Magnet:** Solicita tu clave gratuita de 1 puesto de producción sin tarjeta de crédito en [printer.nyxo.ar](https://printer.nyxo.ar).

---

## 📄 Licencia

El paquete de Laravel de Nyxo Universal Printer es software de código abierto bajo la [Licencia MIT](LICENSE.md).  
El Agente de Escritorio de Windows es software propietario comercial licenciado a través de [Lemon Squeezy](https://printer.nyxo.ar).
