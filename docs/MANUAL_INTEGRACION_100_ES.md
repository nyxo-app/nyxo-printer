# 🖨️ Nyxo Universal Printer para Laravel
## Manual Definitivo de Arquitectura, Integración y Operación al 100%
**Guía Oficial de Ingeniería y Despliegue en Producción**  
*Documento de Referencia Técnica v1.0 — Compatible con PHP 8.2+, Laravel 10/11/12/13, Livewire 3/4 y Windows Spooler*

---

## 📑 Tabla de Contenidos

1. [Ficha Técnica y Propuesta de Valor](#1-ficha-técnica-y-propuesta-de-valor)
2. [Arquitectura del Sistema y Ciclo de Vida de los Datos](#2-arquitectura-del-sistema-y-ciclo-de-vida-de-los-datos)
3. [Instalación y Configuración Paso a Paso](#3-instalación-y-configuración-paso-a-paso)
4. [Gestión de Nodos de Impresión y Vinculación en 1 Clic](#4-gestión-de-nodos-de-impresión-y-vinculación-en-1-clic)
5. [Especificación Completa de la API REST Interna](#5-especificación-completa-de-la-api-rest-interna)
6. [Fluent Builder y Métodos de Encolado al 100% (`NyxoPrinter`)](#6-fluent-builder-y-métodos-de-encolado-al-100-nyxoprinter)
7. [Desarrollo y Pruebas Sin Gastar Papel (Paperless Dev)](#7-desarrollo-y-pruebas-sin-gastar-papel-paperless-dev)
8. [Sistema de Plantillas Reutilizables (Nativas y Personalizadas)](#8-sistema-de-plantillas-reutilizables-nativas-y-personalizadas)
9. [Integración Frontend con Livewire 3 & 4 (El Ciclo Completo)](#9-integración-frontend-con-livewire-3--4-el-ciclo-completo)
10. [Guía Oficial de Operación, Configuración y Despliegue del Agente de Escritorio](#10-guía-oficial-de-operación-configuración-y-despliegue-del-agente-de-escritorio)
11. [Control de Concurrencia, Resiliencia y Eventos de Ciclo de Vida](#11-control-de-concurrencia-resiliencia-y-eventos-de-ciclo-de-vida)
12. [Guía de Solución de Problemas y Preguntas Frecuentes (FAQ)](#12-guía-de-solución-de-problemas-y-preguntas-frecuentes-faq)

---

## 1. Ficha Técnica y Propuesta de Valor

La impresión desatendida en entornos web modernos se enfrenta a múltiples barreras de seguridad y arquitectura impuestas por los navegadores:
- `window.print()` congela el hilo de ejecución de la interfaz, fuerza la ventana modal (`Ctrl+P`) y exige que el operador presione "Aceptar" manualmente.
- Las restricciones de contenido mixto (HTTPS $\to$ HTTP) bloquean cualquier intento de una aplicación en la nube de conectarse a `localhost` o a direcciones IP privadas de la red local.
- Soluciones heredadas como QZ Tray imponen la instalación pesada de Java Runtime Environment (JRE) y la administración de certificados criptográficos autofirmados en cada estación de trabajo.
- Servicios en la nube como PrintNode cobran tarifas recurrentes por cada puesto físico conectado.

**Nyxo Universal Printer** resuelve estos desafíos mediante una arquitectura híbrida desacoplada:

| Indicador | Solución Web Tradicional | Nyxo Universal Printer |
| :--- | :--- | :--- |
| **Tiempo de Despacho** | 3 a 8 segundos (con confirmación de usuario) | **0.2 segundos** (desatendido y silencioso) |
| **Ventanas Emergentes** | Requiere `Ctrl+P` e interacción física | **Cero ventanas emergentes** |
| **Certificados SSL Locales** | Exigidos por el navegador en la máquina cliente | **No requeridos** (comunicación saliente HTTPS) |
| **Dependencia de Java** | Obligatoria en QZ Tray y similares | **Cero dependencias de Java** (C / Win32 Spooler) |
| **Apertura de Puertos** | Requiere Port Forwarding o IP fija en el router | **Cero puertos abiertos** (Outbound Polling) |
| **Consumo de Papel en Dev**| Obliga a tener la impresora física conectada | **Emulador en pantalla** y visor HTML interactivo |

---

## 2. Arquitectura del Sistema y Ciclo de Vida de los Datos

El subsistema opera bajo un modelo de **cola atómica con sondeo saliente seguro**:

```text
┌────────────────────────────────────────────────────────────────────────┐
│                   APLICACIÓN WEB LARAVEL (Cloud / VPS)                 │
│                                                                        │
│   NyxoPrinter::to($nodeId)                                             │
│       ->title('MI COMERCIO')                                           │
│       ->table($items)                                                  │
│       ->total($monto)                                                  │
│       ->cut()                                                          │
│       ->send();                                                        │
│                                                                        │
│   1. Genera bytes binarios (ESC/POS) o Base64 (PDF / RAW / JSON)       │
│   2. Persiste el registro en la tabla `print_jobs` en estado 'pending' │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ Polling HTTPS Saliente (Puerto 443)
                                    │ Header: Authorization: Bearer <token>
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│            AGENTE DE ESCRITORIO NYXO (PC Cliente / Punto de Venta)     │
│                                                                        │
│   - Envía pulso cada 5 segundos (GET /api/v1/print/ping)               │
│   - Retira trabajos pendientes con bloqueo atómico (GET /jobs)         │
│   - Envía confirmación de éxito o reporte de fallo (POST /status)      │
└───────────────────┬───────────────────────────────┬────────────────────┘
                    │ winspool.drv (RAW)            │ SumatraPDF (Silent CLI)
                    ▼                               ▼
    ┌───────────────────────────────┐   ┌───────────────────────────────┐
    │  IMPRESORA TÉRMICA DE TICKETS │   │  IMPRESORA CONVENCIONAL A4    │
    │  (ESC/POS 80mm / 58mm)        │   │  (Láser / Tinta de Oficina)   │
    │  • Comandas, Tickets, Facturas│   │  • Facturas A4, Remitos       │
    │  • Corte físico y apertura RJ1│   │  • Hojas membretadas, PDF     │
    └───────────────────────────────┘   └───────────────────────────────┘
```

### Ciclo de Estados de un Trabajo de Impresión (`PrintJob`):
1. **`pending`**: El trabajo es encolado en Laravel mediante `NyxoPrinter::to($id)->send()`.
2. **`processing`**: El agente local consulta `GET /jobs`. La base de datos aplica `lockForUpdate()` dentro de una transacción y transiciona el registro atómicamente incrementando el contador de intentos (`attempts`).
3. **`printed`**: La impresora física confirma la recepción del flujo binario en el spooler. El agente envía `POST /jobs/{id}/status` con `status: 'printed'`.
4. **`failed`**: Si se produce un corte de papel, atasco o desconexión física, el agente reporta `status: 'failed'` junto con el mensaje de error capturado (`error_message`).

---

## 3. Instalación y Configuración Paso a Paso

### 3.1 Requisitos de Entorno
- **PHP:** Versión 8.2 o superior (probado exhaustivamente en PHP 8.2, 8.3 y 8.4).
- **Extensiones PHP Requeridas:** `ext-iconv` (para transliteración a CP850), `ext-json`, `ext-mbstring`.
- **Framework:** Laravel 10.x, 11.x, 12.x o 13.x.
- **Sistema Operativo del Cliente POS:** Windows 10 u 11 (64-bit) con el Agente Nyxo instalado.

### 3.2 Instalación vía Composer
Ejecutar en la raíz de la aplicación Laravel:
```bash
composer require nyxo-app/nyxo-printer
```

### 3.3 Instalador Automatizado
Ejecutar el comando de instalación interactiva:
```bash
php artisan nyxo-printer:install
```
Este comando ejecuta de forma desatendida:
1. Publicación del archivo `config/nyxo-printer.php`.
2. Publicación de la migración timestamped en `database/migrations/`.
3. Publicación de las vistas Blade y componentes Livewire en `resources/views/vendor/nyxo-printer/`.
4. Copia del instalador del agente de escritorio (`Nyxo_Universal_Printer_Setup_Win.exe`) en la carpeta pública `public/downloads/` para auto-hospedaje inmediato.
5. Pregunta interactiva para ejecutar `php artisan migrate`.

### 3.4 Migraciones y Estructura de Tablas
Si no se ejecutaron en el paso anterior:
```bash
php artisan migrate
```

#### Tabla `printer_nodes` (Nodos de Impresión):
| Columna | Tipo | Descripción |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Identificador único del nodo de caja / puesto. |
| `name` | `VARCHAR(100)` | Nombre descriptivo (ej: "Caja 1 - Mostrador Principal"). |
| `print_token` | `VARCHAR(80) UNIQUE` | Token criptográfico de autenticación de 60 caracteres. |
| `pairing_code` | `VARCHAR(10) NULL INDEX` | Código numérico alternativo de 6 dígitos. |
| `empresa_id` | `BIGINT UNSIGNED NULL INDEX` | Clave foránea opcional para SaaS multi-empresa. |
| `last_ping_at` | `TIMESTAMP NULL INDEX` | Marca de tiempo del último latido recibido por el agente. |
| `is_active` | `TINYINT(1) DEFAULT 1` | Indicador de habilitación operativa del nodo. |
| `timestamps` | `TIMESTAMP` | Marcas `created_at` y `updated_at`. |

#### Tabla `print_jobs` (Cola Atómica de Trabajos):
| Columna | Tipo | Descripción |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Identificador único del trabajo de impresión. |
| `printer_node_id` | `BIGINT UNSIGNED FK` | Relación estricta con `printer_nodes` con eliminación en cascada. |
| `format` | `VARCHAR(50) DEFAULT 'a4'` | Formato objetivo: `ticket_80mm`, `ticket_58mm`, `a4`, `raw`. |
| `content_type` | `VARCHAR(50)` | Tipo de carga: `escpos_base64`, `pdf_base64`, `raw_text`, `json_structured`. |
| `content` | `LONGTEXT` | Carga binaria en Base64 o texto crudo. |
| `status` | `VARCHAR(30) DEFAULT 'pending'` | Estado: `pending`, `processing`, `printed`, `failed`. |
| `attempts` | `SMALLINT UNSIGNED DEFAULT 0` | Número de veces que se ha intentado despachar. |
| `error_message` | `TEXT NULL` | Diagnóstico de error devuelto por el hardware. |
| `timestamps` | `TIMESTAMP` | Marcas temporales con índice compuesto `['printer_node_id', 'status', 'created_at']`. |

### 3.5 Archivo de Configuración (`config/nyxo-printer.php`)
```php
return [
    // Prefijo de la API REST para el agente de escritorio
    'route_prefix' => env('NYXO_PRINTER_PREFIX', 'api/v1/print'),

    // Middleware aplicado a los endpoints de la impresora
    'middleware' => ['api'],

    // Ruta de descarga pública para el instalador Windows
    'download_route' => 'downloads/Nyxo_Universal_Printer_Setup_Win.exe',

    // Portal oficial de soporte y licencias
    'portal_url' => env('NYXO_PRINTER_PORTAL_URL', 'https://printer.nyxo.ar'),

    // Nombres de tablas en base de datos
    'tables' => [
        'nodes' => 'printer_nodes',
        'jobs' => 'print_jobs',
    ],

    // Columna multi-tenant (null si es monolito simple)
    'tenant_column' => env('NYXO_PRINTER_TENANT_COLUMN', 'empresa_id'),

    // Minutos para considerar un trabajo en 'processing' como huérfano y reencolarlo
    'timeout_minutes' => (int) env('NYXO_PRINTER_TIMEOUT_MINUTES', 3),

    // Intentos máximos de reintento ante caídas
    'max_attempts' => (int) env('NYXO_PRINTER_MAX_ATTEMPTS', 3),

    // Días de retención para purga automática
    'prune_after_days' => (int) env('NYXO_PRINTER_PRUNE_DAYS', 7),

    // Ancho térmico predeterminado (80 o 58)
    'default_width' => (int) env('NYXO_PRINTER_DEFAULT_WIDTH', 80),

    // Tabla de caracteres para transliteración térmica
    'codepage' => env('NYXO_PRINTER_CODEPAGE', 'CP850'),
];
```

---

## 4. Gestión de Nodos de Impresión y Vinculación en 1 Clic

Cada equipo físico conectado a una o más impresoras se registra como un registro `PrinterNode`:

```php
use Nyxo\Printer\Models\PrinterNode;

// 1. Creación del nodo (en Seeder, Controlador o Filament/Nova):
$nodo = PrinterNode::create([
    'name' => 'Caja Mostrador Central',
    'is_active' => true,
    // 'empresa_id' => $empresa->id, // Si opera en modo SaaS
]);

// 2. Token de 60 caracteres (autogenerado criptográficamente si no se especifica):
$token = $nodo->print_token;
// Ejemplo: "8fK9xT2yR8qW1yT5mN4zB6cA0dF3gH7jK2lM5nP8rS1tV4wY7bC0eG3hJ6kL"

// 3. Código de Enlace Rápido en 1 Clic (Base64 que agrupa URL y Token):
$codigoEnlace = $nodo->codigo_enlace; // O alias $nodo->pairing_string
// Formato interno decodificado: "https://mi-sistema.com/api/v1/print|8fK9xT2yR8..."
```

### Configuración en la PC del Cliente Windows

#### 🔒 Estado Inicial Limpio de Fábrica (Seguridad y Privacidad)
Por estricto diseño de seguridad comercial y privacidad multi-inquilino, el agente de escritorio de Nyxo se distribuye en **estado de fábrica 100% limpio y desvinculado**:
- **Sin URLs ni Tokens Precargados:** Ni la dirección del servidor (`saasUrl`) ni las credenciales secretas del puesto (`tenantToken`) vienen predefinidas. La aplicación inicia vacía esperando la vinculación manual o mediante el código rápido.
- **Sin Parámetros de Emulador por Defecto (Prioridad Limpia):** Los campos de destino del emulador C# o impresora de red (`emulatorUrl`) inician vacíos. En cajas y puntos de venta reales con ticketeras físicas conectadas por USB/Red, este campo no se utiliza ni interfiere en la operativa. Si durante desarrollo se ingresa una dirección de emulador (ej: `127.0.0.1:9100`), el agente entra automáticamente en **Modo Emulador Exclusivo**, deshabilitando los selectores de impresoras físicas térmicas para mantener la interfaz limpia y redirigiendo todo el tráfico térmico a la ventana del emulador. Al vaciar el campo, las impresoras físicas se rehabilitan de inmediato.
- **Bloqueo Estricto por Falta de Licencia:** La terminal inicia sin licencia activa. Mientras la terminal no cuente con una licencia válida y activa de Lemon Squeezy, **todas las funciones del agente quedan estrictamente bloqueadas** (pestañas de Estado, Conexión, Impresoras y Configuración inhabilitadas con candados 🔒). La única acción permitida en la aplicación es ingresar y activar la clave de licencia en la pestaña correspondiente. Una vez activada con éxito, la interfaz se desbloquea en su totalidad. Todas las licencias (tanto la modalidad gratuita para desarrolladores como los planes comerciales) se emiten exclusivamente a través de **Lemon Squeezy** (`https://printer.nyxo.ar`).

#### 🛡️ Primer Inicio en Windows 10/11 (Aviso de SmartScreen)
Al ejecutar el instalador o la versión portable por primera vez, es normal que Microsoft Defender SmartScreen muestre una ventana azul informativa (*"Windows protegió su PC"*), al tratarse de un ejecutable recién compilado sin un certificado de firma EV corporativo:
- Para continuar: Hacer clic en el enlace **"Más información"** y presionar el botón **"Ejecutar de todas formas"**.

#### 🔄 Demonio en Segundo Plano y Bandeja del Sistema (System Tray)
El agente de Nyxo opera como un **servicio desatendido en segundo plano**:
- **Al presionar la `X` de la ventana:** El programa **no se cierra**; se minimiza a la Bandeja del Sistema (*System Tray*) junto al reloj de Windows. Esto previene que un cajero cierre la aplicación por error e interrumpa la impresión de tickets.
- **Para restaurar la ventana:** Hacer doble clic en el ícono de la impresora junto al reloj de Windows, o simplemente volver a ejecutar el archivo `.exe` (el mecanismo *Single Instance Lock* detectará la instancia en segundo plano y traerá la ventana al frente de inmediato).
- **Para cerrar definitivamente la aplicación:** Hacer clic derecho sobre el ícono de la bandeja del sistema y seleccionar **"Salir"**.

#### ⚙️ Proceso de Vinculación Paso a Paso:
1. Abrir `Nyxo_Universal_Printer.exe`.
2. Ir a la pestaña **🔑 Conexión**.
3. **Método Rápido en 1 Clic (Recomendado):** Pegar el `$nodo->codigo_enlace` (o `$nodo->pairing_string`) generado en el panel web de Laravel dentro del campo **"⚡ Código de Enlace Rápido"** y presionar **Pegar** y **Guardar**. El agente decodifica automáticamente la URL base del servidor y el token secreto.
4. **Método Manual:** Introducir la URL del endpoint (ej. `https://mi-sistema.com/api/v1/print`) y el `$nodo->print_token`.
5. En la pestaña **🖨️ Impresoras**, seleccionar la impresora física asignada para cada formato (*Ticket 80mm*, *Ticket 58mm* o *A4*).
6. En la pestaña **🛡️ Licencia**, ingresar la clave oficial emitida por **Lemon Squeezy** (variante Developer Free de $0 USD o variantes comerciales) y presionar **Activar Licencia**.

### Monitoreo en Tiempo Real (Heartbeat)
El agente Windows emite un latido cada 5 segundos hacia `GET /api/v1/print/ping`. Laravel actualiza `last_ping_at` silenciosamente sin disparar observadores (`updateQuietly()`).

```php
// Comprobar si el puesto está encendido y el agente conectado:
if ($nodo->is_online) {
    // 🟢 Online: El agente emitió un latido en los últimos 2 minutos
} else {
    // 🔴 Offline: Máquina apagada o sin conexión
}
```

#### Renderizado en Vistas Blade / Tailwind:
```blade
<div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium {{ $nodo->is_online ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
    <span class="w-2 h-2 rounded-full {{ $nodo->is_online ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
    <span>{{ $nodo->name }}: {{ $nodo->is_online ? 'En Línea' : 'Desconectado' }}</span>
</div>
```

---

## 5. Especificación Completa de la API REST Interna

Toda la comunicación de los endpoints está protegida por el middleware [`CheckPrintToken`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Http/Middleware/CheckPrintToken.php).

### Fuentes de Extracción del Token:
El middleware inspecciona sucesivamente los siguientes canales (resolviendo bloqueos de proxies y configuraciones de Apache/FastCGI):
1. Cabecera `X-Tenant-Token`
2. Cabecera `X-Print-Token`
3. Cabecera `Print-Token`
4. Cabecera `Token`
5. Cabecera estándar `Authorization: Bearer <token>`
6. Parámetro Query o Body `?token=` o `?print_token=`
7. Variables de servidor Apache `HTTP_AUTHORIZATION` y `REDIRECT_HTTP_AUTHORIZATION`

> 💡 **Auto-decodificación:** Si el usuario pega accidentalmente el Código de Enlace en Base64 en un campo previsto para el token crudo, el middleware detecta el carácter `|`, decodifica la cadena y extrae el token correspondiente sin arrojar error.

### Endpoints Oficiales:

#### 1. Verificación de Latido (`GET /api/v1/print/ping`)
- **Headers:** `Authorization: Bearer <print_token>`
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Pong",
  "node": {
    "id": 1,
    "name": "Caja Principal",
    "tenant_id": 1
  },
  "tenant": {
    "id": 1,
    "nombre": "Caja Principal"
  },
  "punto_venta": {
    "id": 1,
    "nombre": "Caja Principal",
    "numero": 1
  },
  "timestamp": "2026-09-21T10:30:00-03:00"
}
```

#### 2. Extracción Atómica de Trabajos (`GET /api/v1/print/jobs`)
- **Headers:** `Authorization: Bearer <print_token>`
- **Mecanismo:** Envuelto en `DB::transaction()` con `lockForUpdate()`. Toma los registros `pending` (o trabajos huérfanos rescatados), los transiciona inmediatamente a `processing` e incrementa `attempts = attempts + 1`.
- **Respuesta Exitosa (200 OK):**
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

#### 3. Actualización de Estado (`POST /api/v1/print/jobs/{id}/status`)
- **Headers:** `Authorization: Bearer <print_token>`
- **Body JSON:**
```json
{
  "status": "printed",
  "error_message": null
}
```
- **Respuesta Exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Print job status updated successfully.",
  "job_id": 104,
  "new_status": "printed"
}
```

---

## 6. Fluent Builder y Métodos de Encolado al 100% (`NyxoPrinter`)

El Facade [`NyxoPrinter`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Facades/NyxoPrinter.php) provee una API encadenable limpia, robusta y completamente tipada.

### 6.1 Tickets Térmicos ESC/POS (`ThermalBuilder`)
Construcción de tickets con soporte nativo de caracteres en español (`ñ`, acentos) y símbolos monetarios (`$`):

```php
use Nyxo\Printer\Facades\NyxoPrinter;

NyxoPrinter::to($nodoId)
    ->width(80) // 80mm (42 cols) o 58mm (32 cols). Ajusta el format automáticamente.
    ->copies(1) // Cantidad de copias físicas a encolar
    
    // Encabezado y Títulos
    ->title('CAFE & RESTO NYXO', doubleWidth: true, doubleHeight: true)
    ->center('CUIT: 30-71829384-9 | IVA Responsable Inscripto')
    ->center('Av. Corrientes 1234, CABA')
    ->line('-') // Línea separadora continua adaptada al ancho (42 o 32 guiones)
    
    // Datos del Comprobante
    ->text('Ticket Factura B: 0001-00004921', bold: true)
    ->text('Fecha: ' . now()->format('d/m/Y H:i') . ' | Cajero: Juan')
    ->doubleLine('=') // Línea doble (====================)
    
    // Tabla de Artículos (alineación automática de descripción y precio)
    ->table([
        ['nombre' => 'Café Espresso Doble', 'cantidad' => 2, 'precio' => 7000],
        ['nombre' => 'Tostado Jamón y Queso Especial', 'cantidad' => 1, 'precio' => 4500],
        ['nombre' => 'Agua Mineral 500ml', 'cantidad' => 1, 'precio' => 2000],
    ], headerLeft: 'DESCRIPCION', headerRight: 'TOTAL')
    ->line('-')
    
    // Totales
    ->text('Subtotal: $13,500.00', align: 'right')
    ->text('Descuento Socio (10%): -$1,350.00', align: 'right')
    ->total(12150.00, label: 'TOTAL A PAGAR:', currency: '$')
    ->feed(1) // Avance de líneas
    
    // Códigos 1D y 2D
    ->qr('https://www.afip.gob.ar/fe/qr/?p=eyJ2ZXIiOjF9', size: 6) // QR Fiscal
    ->barcode('000100004921', type: 'CODE39') // Código de barras para lector
    
    // Pie de página y agradecimiento
    ->center('¡Gracias por su compra!')
    ->center('Conserve su comprobante para cambios')
    
    // Control Electromecánico de Hardware
    ->openDrawer(pin: 0) // Pulso eléctrico para abrir el cajón monedero RJ11/RJ12
    ->beep(times: 2)     // Alarma acústica en comanderas de cocina o barras
    ->cut(full: false)   // Corte de guillotina parcial o total (true = full)
    
    // Despacho a la cola
    ->send();
```

### 6.2 Modificadores Avanzados de Texto en `ThermalBuilder`
```php
// Texto con formato enriquecido directo
$ticket->text(
    text: 'TEXTO ESPECIAL SUBRAYADO',
    align: 'center',       // 'left' | 'center' | 'right'
    bold: true,            // Negrita / Emphasized
    underline: true,       // Subrayado físico por hardware
    doubleHeight: false,   // Doble altura
    doubleWidth: false     // Doble anchura
);
```

### 6.3 Inyección Directa ESC/POS con `custom(callable)`
Si necesitas enviar una secuencia de escape específica no prevista por los helpers (por ejemplo, definir caracteres personalizados o gráficos especiales):
```php
use Mike42\Escpos\Printer;

NyxoPrinter::to($nodoId)
    ->title('PRUEBA NATIVA')
    ->custom(function (Printer $printer) {
        // Acceso directo a la instancia de Mike42\Escpos\Printer
        $printer->setLineSpacing(30);
        $printer->getPrintConnector()->write("\x1B\x21\x30"); // Modo RAW directo
    })
    ->cut()
    ->send();
```

> [!CAUTION]
> **Regla Crítica de PHP 8.x en Mike42:**  
> Al invocar `$printer->close()`, el conector en memoria se finaliza destruyendo su buffer a `null`. Para evitar la excepción fatal `implode(): Argument #1 must be of type array|string, null given`, la extracción de datos debe ejecutarse siempre antes del cierre:
> ```php
> $data = $connector->getData();
> $printer->close();
> return base64_encode($data);
> ```
> *Esta regla ya se encuentra blindada internamente en `ThermalBuilder::toBase64()`.*

---

### 6.4 Impresión Silenciosa de Facturas y Remitos en A4 PDF

#### Opción A: Desde un archivo PDF físico existente en almacenamiento:
```php
NyxoPrinter::to($nodoId)
    ->copies(2)
    ->pdfFile(storage_path('app/facturas/factura_0001_0004921.pdf'))
    ->send();
```

#### Opción B: Desde una cadena Base64 en memoria (DomPDF, Snappy o Browsershot):
```php
use Barryvdh\DomPDF\Facade\Pdf;

// Generar PDF en memoria sin escribir en disco:
$pdf = Pdf::loadView('comprobantes.factura_a4', ['orden' => $orden]);
$base64 = base64_encode($pdf->output());

// Encolar directamente:
NyxoPrinter::to($nodoId)
    ->copies(1)
    ->pdf($base64, format: 'a4')
    ->send();
```

---

### 6.5 Impresión Matricial / Impacto (`raw`) y Cargas JSON
```php
// 1. Impresoras de impacto o matriciales (cabezales de aguja, Epson LX-300):
NyxoPrinter::to($nodoId)
    ->raw("REMITO MATRICIAL\nCLIENTE: EMPRESA S.A.\nFECHA: 21/09/2026\n\n\x0C")
    ->send();

// 2. Payloads estructurados en JSON (para agentes personalizados o integraciones IoT):
NyxoPrinter::to($nodoId)
    ->json([
        'accion' => 'etiqueta_bulto',
        'tracking' => 'TRK-98213-AR',
        'bultos' => 3,
    ])
    ->send();
```

---

### 6.6 Atajos Directos del Facade `NyxoPrinter`
Para operaciones concisas en una sola instrucción:
```php
// Encolar PDF directamente
NyxoPrinter::pdf($nodoId, $pdfBase64, format: 'a4', copies: 1);

// Encolar binario térmico directamente
NyxoPrinter::thermal($nodoId, $escposBase64, format: 'ticket_80mm', copies: 1);

// Encolar texto plano directo
NyxoPrinter::raw($nodoId, "TEXTO DIRECTO", format: 'raw', copies: 1);

// Encolar plantilla
NyxoPrinter::template($nodoId, $instanciaPlantilla, copies: 1);

// Obtener modelo o código de enlace
$nodo = NyxoPrinter::node($nodoId);
$enlace = NyxoPrinter::getPairingString($nodo);
```

---

## 7. Desarrollo y Pruebas Sin Gastar Papel (Paperless Dev)

Una de las mayores fricciones al programar tickets de punto de venta es el desperdicio continuo de rollos térmicos para calibrar tipografías, anchos y márgenes. Nyxo Universal Printer incorpora dos soluciones nativas:

### 7.1 Previsualizador Gráfico en Pantalla (HTML Interactivo)
Puedes crear una ruta de prueba en tu archivo `routes/web.php` que renderice el ticket en el navegador tal como saldría físicamente:

```php
use Nyxo\Printer\Facades\NyxoPrinter;

Route::get('/dev/ticket-preview', function () {
    return NyxoPrinter::preview(function ($ticket) {
        $ticket->width(80)
            ->title('VISTA PREVIA EN PANTALLA')
            ->center('Comercio de Demostración')
            ->line('-')
            ->table([
                ['nombre' => 'Hamburguesa Clásica con Queso', 'cantidad' => 2, 'precio' => 12000],
                ['nombre' => 'Papas Fritas Rústicas Grandes', 'cantidad' => 1, 'precio' => 4500],
                ['nombre' => 'Gaseosa 500ml', 'cantidad' => 2, 'precio' => 3000],
            ])
            ->line('-')
            ->total(19500.00)
            ->qr('https://printer.nyxo.ar')
            ->cut();
    }, width: 80);
});
```
**Resultado en el navegador:** Un ticket fotorrealista con fondo continuo de papel térmico, tipografía monoespaciada de ancho fijo, líneas de corte punteadas, simulador de cajón monedero y códigos QR vectoriales.

---

### 7.2 Emulador de Hardware C# (ESSI Thermal Emulator)
El **ESSI Thermal Emulator** es una herramienta auxiliar de simulación visual autónoma para desarrolladores, diseñada para diseñar plantillas y probar sin gastar rollos de papel térmico.
- **Herramienta Opcional de Pruebas:** En cajas de cobro y puntos de venta reales con impresoras físicas USB, el emulador **NO se instala ni se utiliza**. El agente viene de fábrica con los campos de emulador completamente vacíos.
- **Regla de Prioridad Exclusiva del Emulador (Interfaz Limpia):**
  Para prevenir conflictos operativos y evitar que el usuario deba reconfigurar o desvincular impresoras físicas cada vez que desea hacer pruebas, el Agente Nyxo incorpora una regla de prioridad automática:
  - **Si el campo `emulatorUrl` está cargado (ej: `127.0.0.1:9100` en Ajustes):** El agente entra en **Modo Emulador Activo**. Los selectores de impresoras físicas térmicas (80mm y 58mm) se **deshabilitan automáticamente** en la pestaña *Impresoras* y se muestra un banner azul informativo: *"Modo Emulador Activo (Desarrollo) - Todas las impresiones térmicas se redirigen automáticamente a la emulación"*. El 100% de los tickets ESC/POS se despachan directamente al emulador en pantalla.
  - **Si el campo `emulatorUrl` está vacío:** El banner desaparece y los selectores de impresoras físicas térmicas vuelven a habilitarse, despachando los trabajos a las ticketeras reales USB de Windows o por IP de red.
  - *(Nota: La asignación de la impresora A4 permanece siempre activa e independiente, permitiendo imprimir facturas PDF físicas incluso con el emulador térmico encendido).*
- **Flujo de Puesta en Marcha del Emulador:**
  1. Ejecutar el **ESSI Thermal Emulator** (`ESSIThermalEmulator.exe`), el cual abre un socket de escucha en el puerto TCP/HTTP `9100`.
  2. En el Agente Nyxo, ir a la pestaña **⚙️ Ajustes** e ingresar en el campo **Emulador C# / Impresora de Red TCP** la dirección `127.0.0.1:9100` y presionar **Guardar Configuración**.
  3. Al enviar trabajos desde Laravel, el agente redirigirá automáticamente el flujo ESC/POS al emulador, dibujando el ticket en pantalla con animación de salida de papel, conmutador de ancho 80mm/58mm y opción de exportación nativa a PDF.

---

## 8. Sistema de Plantillas Reutilizables (Nativas y Personalizadas)

Para cumplir con el principio de responsabilidad única (Clean Architecture), la lógica de diseño de los comprobantes debe aislarse de los controladores o modelos de base de datos.

### 8.1 Plantilla Nativa 1: `ReceiptTemplate` (Tickets de Venta)
Ubicada en `Nyxo\Printer\Templates\ReceiptTemplate`:
```php
use Nyxo\Printer\Facades\NyxoPrinter;
use Nyxo\Printer\Templates\ReceiptTemplate;

$datosTicket = [
    'company' => 'SUPERMERCADO CENTRAL',
    'title' => 'TICKET DE COMPRA',
    'metadata' => [
        'Caja' => '01',
        'Cajero' => 'M. Pérez',
        'Fecha' => now()->format('d/m/Y H:i'),
    ],
    'items' => [
        ['name' => 'Leche Entera 1L', 'qty' => 2, 'price' => 2400],
        ['name' => 'Pan Lactal 500g', 'qty' => 1, 'price' => 3100],
    ],
    'total' => 5500.00,
    'qr' => 'https://comprobante.afip.gob.ar/5500',
    'barcode' => '779123456789',
    'footer' => '¡Gracias por su visita!',
    'open_drawer' => true,
    'cut' => true,
];

NyxoPrinter::to($nodoId)
    ->template(new ReceiptTemplate($datosTicket))
    ->send();
```

---

### 8.2 Plantilla Nativa 2: `OrderSummaryTemplate` (Taller / Servicios)
Ubicada en `Nyxo\Printer\Templates\OrderSummaryTemplate`:
```php
use Nyxo\Printer\Facades\NyxoPrinter;
use Nyxo\Printer\Templates\OrderSummaryTemplate;

$datosServicio = [
    'empresa' => 'ELECTRÓNICA & TALLER PRO',
    'order_number' => '4092',
    'fecha' => now()->format('d/m/Y H:i'),
    'cliente' => 'Carlos Gutiérrez',
    'telefono' => '+54 9 11 5555-4321',
    'equipo' => 'Notebook Lenovo ThinkPad T480',
    'problema' => 'No enciende tras corte de energía',
    'reparaciones' => [
        ['nombre' => 'Reemplazo Integrado PWM Fuente', 'precio' => 35000],
        ['nombre' => 'Limpieza y Cambio Pasta Térmica', 'precio' => 12000],
    ],
    'total' => 47000.00,
    'barcode' => 'ORD-4092',
    'terminos' => 'Garantía de 90 días sobre mano de obra.',
];

NyxoPrinter::to($nodoId)
    ->template(new OrderSummaryTemplate($datosServicio))
    ->send();
```

---

### 8.3 Creación de Plantillas de Negocio Propias
Cualquier clase que implemente [`PrintTemplateInterface`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Contracts/PrintTemplateInterface.php) es compatible:

```php
namespace App\PrintTemplates;

use Nyxo\Printer\Contracts\PrintTemplateInterface;
use Nyxo\Printer\Builders\ThermalBuilder;

class ComandaCocinaTemplate implements PrintTemplateInterface
{
    public function __construct(
        protected array $comanda
    ) {}

    public function build(ThermalBuilder $ticket): void
    {
        $ticket->center('*** COMANDA DE COCINA ***', bold: true, doubleHeight: true)
            ->text('MESA #' . $this->comanda['mesa'] . ' | Mozo: ' . $this->comanda['mozo'])
            ->text('Hora: ' . now()->format('H:i:s'))
            ->doubleLine('=');

        foreach ($this->comanda['platos'] as $plato) {
            $ticket->text(
                text: $plato['cantidad'] . 'x ' . $plato['nombre'],
                bold: true,
                doubleWidth: true
            );
            if (! empty($plato['observaciones'])) {
                $ticket->text('   > NOTA: ' . $plato['observaciones']);
            }
        }

        $ticket->line('-')
            ->beep(times: 3) // Alerta sonora al cocinero
            ->feed(2)
            ->cut();
    }
}
```

---

## 9. Integración Frontend con Livewire 3 & 4 (El Ciclo Completo)

El paquete incluye el componente Livewire [`NyxoPrinterModal`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Livewire/NyxoPrinterModal.php) estilizado con Tailwind CSS.

### 9.1 Paso 1: Inclusión en el Layout Blade
Agregar el componente global en `resources/views/layouts/app.blade.php`:
```blade
<!DOCTYPE html>
<html lang="es">
<head> ... </head>
<body>
    {{ $slot }}

    {{-- Modal Universal de Impresión Nyxo --}}
    <livewire:nyxo-printer-modal />
</body>
</html>
```

---

### 9.2 Paso 2: Botón de Disparo
En cualquier vista Blade, componente Livewire o tabla Filament:
```blade
<button 
    type="button"
    wire:click="$dispatch('open-print-modal', {
        documentId: {{ $orden->id }},
        documentType: 'orden_servicio',
        format: 'ticket_80mm'
    })"
    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow-sm transition">
    🖨️ Imprimir Comprobante
</button>
```

#### Preselección Inteligente de Nodo:
Al abrirse, el modal busca automáticamente el puesto asignado bajo esta prioridad:
1. `printerNodeId` pasado explícitamente en el evento.
2. Último nodo utilizado en la sesión del navegador (`session('nyxo_last_printer_node_id')`).
3. Nodo vinculado al usuario autenticado (`auth()->user()->printer_node_id`).
4. Primer nodo activo disponible en la base de datos.

---

### 9.3 Paso 3: El Listener en el Componente Anfitrión (Resolución de la Omisión de GitHub)
Al presionar el botón "Imprimir" dentro del modal, este dispara el evento `nyxo-print-requested`. **El componente anfitrión debe escuchar este evento para armar el documento y encolarlo:**

```php
namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Orden;
use Nyxo\Printer\Facades\NyxoPrinter;
use Nyxo\Printer\Templates\OrderSummaryTemplate;

class GestionOrdenes extends Component
{
    // ...

    /**
     * Escucha la solicitud de impresión disparada desde el modal de Nyxo
     */
    #[On('nyxo-print-requested')]
    public function procesarImpresion(array $payload): void
    {
        $documentId = $payload['documentId'];
        $documentType = $payload['documentType'];
        $format = $payload['format']; // 'ticket_80mm', 'ticket_58mm', 'a4'
        $printerNodeId = $payload['printerNodeId'];
        $copies = $payload['copies'] ?? 1;

        $orden = Orden::with(['cliente', 'detalles'])->findOrFail($documentId);

        // Caso A: Formato Térmico (Ticket)
        if ($format === 'ticket_80mm' || $format === 'ticket_58mm') {
            $width = $format === 'ticket_58mm' ? 58 : 80;

            NyxoPrinter::to($printerNodeId)
                ->width($width)
                ->copies($copies)
                ->template(new OrderSummaryTemplate([
                    'empresa' => config('app.name'),
                    'order_number' => $orden->numero,
                    'fecha' => $orden->created_at->format('d/m/Y H:i'),
                    'cliente' => $orden->cliente->nombre,
                    'telefono' => $orden->cliente->telefono,
                    'equipo' => $orden->equipo_modelo,
                    'problema' => $orden->falla_declarada,
                    'reparaciones' => $orden->detalles->map(fn($d) => [
                        'nombre' => $d->descripcion,
                        'precio' => $d->subtotal,
                    ])->toArray(),
                    'total' => $orden->total,
                ]))
                ->send();
        } 
        // Caso B: Formato Convencional A4 (PDF)
        else {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.orden_a4', ['orden' => $orden]);
            $base64 = base64_encode($pdf->output());

            NyxoPrinter::to($printerNodeId)
                ->copies($copies)
                ->pdf($base64, format: 'a4')
                ->send();
        }

        // Notificación opcional en la UI
        $this->dispatch('notificar', [
            'tipo' => 'success',
            'mensaje' => "Orden #{$orden->numero} encolada correctamente en la terminal.",
        ]);
    }
}
```

---

## 10. Guía Oficial de Operación, Configuración y Despliegue del Agente de Escritorio

El agente de escritorio **Nyxo Universal Printer** es la pieza que corre localmente en cada estación de cobro o punto de venta bajo Windows 10/11 (64-bit). Se comunica de forma saliente e ininterrumpida con el backend Laravel y despacha los trabajos físicos al hardware sin necesidad de abrir puertos en el router, sin certificados SSL locales y sin máquinas virtuales de Java.

---

### 10.1 Modalidades de Distribución: Instalador Windows vs. Versión Portable

Para adaptarse a las políticas de seguridad de cada comercio o infraestructura corporativa, el agente se distribuye en dos compilaciones oficiales:

| Característica | Instalador Windows (`Setup_Win.exe`) | Versión Portable (`Portable.exe`) |
| :--- | :--- | :--- |
| **Nombre de archivo** | `Nyxo_Universal_Printer_Setup_Win.exe` | `Nyxo_Universal_Printer_Portable.exe` |
| **Ruta de ejecución** | `%LOCALAPPDATA%\Programs\Nyxo Universal Printer` | Cualquier carpeta, escritorio o memoria USB |
| **Permisos de Administrador** | No requeridos (instalación en espacio de usuario) | No requeridos (cero instalación) |
| **Accesos Directos** | Creados automáticamente en Escritorio y Menú Inicio | Manuales (según la carpeta elegida) |
| **Arranque con Windows** | Integrado de forma nativa en System Tray | Soportado (guarda la ruta absoluta del archivo EXE) |
| **Registro de Windows** | Entrada en "Agregar o quitar programas" para desinstalación | Cero modificaciones en el registro |
| **Caso de uso recomendado** | **Terminales fijas de venta**, puestos de cobro y servidores de impresión de sucursal | **Demostraciones**, soporte técnico, equipos corporativos restringidos y pendrives |

> [!TIP]
> **Recomendación para la Versión Portable:** Si vas a activar la opción "Arrancar con Windows" en la versión Portable, coloca primero el ejecutable en una carpeta fija y definitiva (por ejemplo, `C:\NyxoPrinter\Nyxo_Universal_Printer_Portable.exe`) para evitar que el acceso directo de arranque se rompa si mueves el archivo más adelante.

---

### 10.2 Auto-Hospedaje y Descarga Directa desde Laravel

El paquete `nyxo-app/nyxo-printer` permite hospedar y servir ambos ejecutables directamente desde tu propio servidor web para garantizar autonomía total sin depender de repositorios externos:

1. **Rutas Web Registradas en el Service Provider:**
   - Instalador Windows: `GET /downloads/Nyxo_Universal_Printer_Setup_Win.exe` (`route('nyxo-printer.download')`)
   - Versión Portable: `GET /downloads/Nyxo_Universal_Printer_Portable.exe` (`route('nyxo-printer.download-portable')`)

2. **Ubicación Física en tu Proyecto Laravel:**
   Deposita los ejecutables compilados en la carpeta pública:
   - `public/downloads/Nyxo_Universal_Printer_Setup_Win.exe`
   - `public/downloads/Nyxo_Universal_Printer_Portable.exe`

3. **Fallback Automático:**
   Si los archivos no están presentes en `public/downloads/`, las rutas redirigen automáticamente al portal oficial de distribución configurado en `config/nyxo-printer.php` (`https://printer.nyxo.ar`).

4. **Botones de Descarga en tu Panel de Administración (Blade):**
```blade
<div class="flex items-center gap-3">
    <a href="{{ route('nyxo-printer.download') }}" 
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">
        💻 Descargar Instalador Windows (.exe)
    </a>
    <a href="{{ route('nyxo-printer.download-portable') }}" 
       class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg shadow-sm">
        📦 Descargar Versión Portable (.exe)
    </a>
</div>
```

---

### 10.3 Pestaña 1: Estado del Sistema y Monitoreo en Tiempo Real (`status-view`)

Al abrir la aplicación, la pantalla de inicio presenta la telemetría operativa del agente:

- **Indicador Visual de Estado:**
  - 🟢 **Online / Agente Iniciado:** El agente está activo y comunicándose exitosamente con el backend Laravel cada 5 segundos.
  - 🟡 **Verificando / Reconectando:** Intentando enlazar con el servidor tras un corte momentáneo de red.
  - 🔴 **Desconectado / Error:** El servidor API no responde o el Token es inválido.
- **Historial de Actividad en Vivo (Live Console):**
  - Muestra la traza cronológica de eventos con marca de tiempo: pings de sondeo saliente, recepción de trabajos (`PrintJob #ID`), inyección binaria a la impresora y confirmación de impresión física (`status = 'printed'`).
  - Botón **"Limpiar"**: Permite vaciar la vista de la consola en pantalla para comenzar una nueva sesión de depuración visual.

---

### 10.4 Pestaña 2: Conexión con el Servidor API (`connection-view`)

Esta sección administra las credenciales seguras de enlace entre el puesto físico y la nube:

1. **Método Recomendado: Código de Enlace Rápido (1 Clic):**
   - En el panel web de administración de Laravel, al dar de alta un nodo de impresión, el sistema genera un botón que copia al portapapeles un código unificado en Base64:
     ```text
     aHR0cHM6Ly9taS1jb21lcmNpby5jb20vYXBpL3YxL3ByaW50fG55eG9fdG9rZW5fY2FqYV8wMQ==
     ```
   - En la aplicación de escritorio, el operador simplemente hace clic en el botón **"Pegar"** junto al campo *Código de Enlace Rápido*.
   - El aplicativo decodifica automáticamente la cadena, extrayendo la **URL del Servidor API** y el **Token de Autenticación**.

2. **Método Manual:**
   - **URL del Servidor API:** Endpoint de la API REST (ejemplo: `https://mi-sistema.com/api/v1/print` o IP local `http://192.168.1.100:8000/api/v1/print`).
   - **Token de Autenticación / Punto de Venta:** Clave de 64 caracteres alfanuméricos asignada al nodo. Incluye botón con ícono de ojo (`👁️`) para visualizar u ocultar el secreto durante la carga.

3. **Herramienta de Diagnóstico Integrada:**
   - Botón **"🔍 Probar Conexión"**: Realiza un `GET /api/v1/print/ping` inmediato. Despliega un panel informativo con el tiempo de latencia en milisegundos, la versión del servidor Laravel y el estado de validación. Si las credenciales fallan, expone el motivo exacto (por ejemplo: `401 Unauthorized / Token inválido`).
   - Botón **"💾 Guardar Conexión"**: Encripta y persiste las credenciales localmente, reiniciando el bucle de sondeo automático.

---

### 10.5 Pestaña 3: Asignación de Impresoras (Dualidad Spooler Windows y Socket TCP RAW 9100)

Nyxo Universal Printer ofrece un modelo de ruteo independiente según el ancho y tipo de papel del documento:

#### A. Independencia Total de los 2 Anchos Térmicos y Documentos A4
- **Ticket Térmico 80 mm (48 columnas):**
  - Ancho estándar de punto de venta (3 pulgadas).
  - Admite tablas completas con columnas alineadas (`Cant`, `Descripción`, `P.Unit`, `Total`).
  - Utilizado para facturas fiscales, tickets de cobro en mostrador y comprobantes con código QR / barras.
- **Ticket Térmico 58 mm (32 columnas):**
  - Formato estrecho (2 pulgadas).
  - Utiliza maquetación vertical optimizada (`Cant` x `Producto` con total en línea inferior) para evitar truncamiento de texto.
  - Ideal para comandas de cocina rápida, cafeterías, tickets de comanda en barras o terminales móviles.
- **Documento A4 (Láser / Inyección de Tinta):**
  - Formato estándar de hoja suelta (210 x 297 mm).
  - El agente lo despacha de forma desatendida mediante su binario integrado de **SumatraPDF**, permitiendo imprimir facturas electrónicas completas en PDF vectorial sin diálogos de confirmación.

#### B. Arquitectura Dual: Drivers de Windows vs. Red TCP Socket RAW (Sin Drivers)
Para cada formato, el agente permite elegir entre dos tecnologías de comunicación física:

1. **Canal 1: Impresoras del Spooler de Windows:**
   - Detecta automáticamente todos los controladores instalados en el sistema (`Termica`, `EPSON TM-T20`, `Bixolon SRP-350`, `POS-80`, `HP LaserJet`, etc.).
   - Emplea `winspool.drv` inyectando bytes binarios en modo RAW directamente al búfer del hardware.
2. **Canal 2: Socket TCP RAW por Red (Puerto 9100 - Driverless):**
   - **¡Cero instalación de drivers en Windows!**
   - Permite ingresar directamente una dirección `IP:Puerto` (ej: `192.168.1.100:9100` o `127.0.0.1:9100`).
   - El agente abre una conexión de socket TCP directa con el hardware físico o el emulador, enviando el flujo binario ESC/POS en milisegundos.
   - Elimina de raíz problemas de bloqueos en la cola del spooler de Windows y evita la instalación de controladores obsoletos de 32 bits.

#### C. Matriz de Configuraciones Mixtas
Cada formato es autónomo y puede combinarse libremente:

| Formato | Configuración Típica A (Híbrida) | Configuración Típica B (100% Sin Drivers) | Configuración Típica C (Local USB) |
| :--- | :--- | :--- | :--- |
| **80 mm (Caja)** | Windows USB: `Termica` | TCP RAW: `192.168.1.100:9100` | Windows USB: `EPSON TM-T20` |
| **58 mm (Cocina)** | TCP RAW: `192.168.1.150:9100` | TCP RAW: `192.168.1.105:9100` | Windows USB: `POS-58` |
| **A4 (Oficina)** | Windows Láser: `HP LaserJet Pro` | Windows Láser: `Brother HL-L2360D` | Windows Láser: `Canon G3010` |

#### D. Conmutación Inteligente: Modo Emulador Exclusivo
Para evitar confusiones entre impresoras físicas reales y el entorno de desarrollo, el agente implementa una regla de exclusividad limpia:
- **Activación:** Al completar el campo de emulador en la pestaña *⚙️ Ajustes* (ej: `127.0.0.1:9100`), la pestaña *🖨️ Impresoras* despliega un banner destacado y **bloquea automáticamente los selectores de 80mm y 58mm**.
- **Enrutamiento 100% Garantizado:** Todo ticket térmico saliente se envía al emulador virtual, sin importar qué impresora USB física hubiese quedado seleccionada previamente.
- **Retorno a Producción:** Basta con borrar el texto del campo de emulador en Ajustes y guardar: los selectores de impresoras físicas térmicas se desbloquean inmediatamente y el agente vuelve a imprimir por el Spooler de Windows o socket TCP directo.

> [!NOTE]
> Botón **"🔄 Recargar Lista"**: Si conectas una nueva impresora USB mientras la aplicación está en marcha, simplemente pulsa este botón para refrescar la lista de dispositivos sin necesidad de reiniciar el programa.

---

### 10.6 Pestaña 4: Configuración General y Destino de Red Opcional (`settings-view`)

- **Idioma / Language:** Permite alternar la interfaz gráfica y las notificaciones del sistema de forma dinámica entre **Español (ES)** e **Inglés (EN)**.
- **Emulador C# / Impresora de Red TCP (Opcional - Prioridad Absoluta):**
  - Campo opcional que por defecto inicia **completamente vacío**. Si se utiliza el emulador de desarrollo `ESSIThermalEmulator` o una impresora Ethernet/TCP directa sin drivers, se ingresa aquí la dirección (ej: `127.0.0.1:9100` o `192.168.1.100:9100`).
  - **Efecto de Prioridad:** Al contener un valor válido, activa el *Modo Emulador*, bloqueando las opciones térmicas físicas de la pestaña de Impresoras y redirigiendo todo el tráfico de tickets al destino configurado. Si se deja vacío, se restablece el funcionamiento estándar con impresoras físicas.
- **Comportamiento sin Impresora Asignada:**
  - Si una terminal recibe un trabajo pero el formato no tiene ninguna impresora seleccionada ni destino de red o emulador configurado, el agente rechaza el trabajo e informa claramente al operador que debe asignar una impresora en el panel, evitando fallbacks silenciosos a puertos locales no deseados.
- **Arrancar con Windows:**
  - Casilla de verificación para registrar el inicio desatendido al encender la PC. El agente se inicia silenciosamente minimizado en la bandeja del sistema (System Tray junto al reloj).

---

### 10.7 Pestaña 5: Control de Licencia Comercial y Período de Gracia (`license-view`)

- **Gestión Comercial Exclusiva con Lemon Squeezy:**
  - Todas las licencias (tanto la licencia gratuita de desarrollador como las suscripciones y compras vitalicias) son administradas y validadas contra la API oficial de **Lemon Squeezy**.
  - Validación en línea de cupos de terminales activas (`Terminales utilizadas / Cupo total contratado`).
  - Tarjeta de información en vivo: Identificador de Terminal (Hardware UID), Nombre del Equipo, Cupo de Puestos, Email del Titular y Estado de Activación con firma criptográfica HMAC enlazada al hardware del equipo.
  - Botón para desvincular la licencia de un puesto físico y transferirla a otro equipo en caso de reemplazo de hardware.
- **Modalidad Desarrollador (Developer Free - $0 USD):**
  - Disponible en la landing page (`https://printer.nyxo.ar`), otorga 1 licencia gratuita vitalicia por correo electrónico para desarrolladores, emitida formalmente por Lemon Squeezy sin solicitar tarjeta de crédito.
- **Mecanismo de Bloqueo Estricto por Falta de Licencia:**
  - El agente implementa una política de cumplimiento estricto: **sin una licencia activa y verificada, el usuario no puede realizar ninguna acción en la aplicación excepto cargar y activar su clave de licencia**.
  - Todas las demás pestañas de la interfaz (`Estado`, `Conexión`, `Impresoras`, `Configuración`) se bloquean visual y funcionalmente (atenuadas al 35%, con cursor de no permitido, indicador 🔒 y tooltip de advertencia). Si el usuario intenta hacer clic sobre cualquier sección bloqueada, el sistema emite un aviso interactivo y redirige a la pestaña de Licencia.
  - Los formularios y controles de conexión e impresión quedan deshabilitados en el DOM (`disabled`), y el servicio de fondo (`server.js`) suspende de forma inmediata el sondeo de trabajos de impresión (`polling`) hasta que una licencia legítima sea activada.
  - Al activar una licencia (incluso la variante Developer Free de $0 USD), el agente desbloquea automáticamente toda la navegación y habilita el procesamiento continuo de comprobantes.
- **Período de Gracia Offline de 7 Días:**
  - Si un local comercial pierde el acceso a internet o sufre una caída de telecomunicaciones, el agente activa su **período de gracia offline de 7 días continuos**.
  - Durante este período, valida la firma de activación en la caché local y continúa imprimiendo todos los comprobantes físicos con total normalidad, revalidándose silenciosamente al regresar la conexión.

---

## 11. Control de Concurrencia, Resiliencia y Eventos de Ciclo de Vida

### 11.1 Prevención de Duplicados (Race Conditions)
En locales comerciales de alto tráfico con múltiples cajeros despachando simultáneamente, o ante fluctuaciones de microcortes de red, el agente podría consultar la cola varias veces en la misma fracción de segundo.

En [`PrintJobApiController::index()`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Http/Controllers/PrintJobApiController.php#L64):
```php
$jobs = DB::transaction(function () use ($node) {
    $pendingJobs = PrintJob::deliverable($node->id)
        ->lockForUpdate() // Bloqueo exclusivo a nivel de fila
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

### 11.2 Rescate Automático de Trabajos Huérfanos
Si una terminal de caja sufre un corte de energía o cuelgue del sistema operativo mientras imprimía, el trabajo quedaría congelado en `status = 'processing'`.

El scope [`PrintJob::scopeDeliverable()`](file:///d:/Productividad/Trabajo/WebsLaravel/Nyxo_Universal_Printer/composer_build/src/Models/PrintJob.php#L89) detecta los trabajos estancados:
- Si el trabajo permanece en `processing` por más de `config('nyxo-printer.timeout_minutes')` (por defecto 3 minutos) y no ha superado `max_attempts` (por defecto 3 intentos), se libera y se entrega nuevamente al reconectarse la PC.

---

### 11.3 Tarea Automática de Mantenimiento (Pruning)
Para mantener la base de datos limpia y con máxima velocidad de lectura en tablas con millones de registros:
```bash
php artisan nyxo-printer:clean --days=7
```
Programar en `routes/console.php` (Laravel 11/12/13):
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('nyxo-printer:clean --days=7')->daily();
```

---

### 11.4 Eventos del Ciclo de Vida de Impresión
El paquete emite eventos de dominio estándar de Laravel para auditoría o telemetría:

| Evento | Cuándo se dispara | Propiedades |
| :--- | :--- | :--- |
| `Nyxo\Printer\Events\PrintJobCreated` | Al crear un nuevo trabajo en la cola. | `public PrintJob $job` |
| `Nyxo\Printer\Events\PrintJobPrinted` | Cuando el agente confirma la impresión física. | `public PrintJob $job` |
| `Nyxo\Printer\Events\PrintJobFailed` | Ante atasco de papel, error de puerto o timeout. | `public PrintJob $job`, `public ?string $errorMessage` |

#### Ejemplo de Listener de Auditoría:
```php
namespace App\Listeners;

use Nyxo\Printer\Events\PrintJobFailed;
use Illuminate\Support\Facades\Log;

class LogFallaImpresion
{
    public function handle(PrintJobFailed $event): void
    {
        Log::channel('impresoras')->error("Fallo de impresión en Nodo #{$event->job->printer_node_id}", [
            'job_id' => $event->job->id,
            'formato' => $event->job->format,
            'error' => $event->errorMessage,
        ]);
    }
}
```

---

## 12. Guía de Solución de Problemas y Preguntas Frecuentes (FAQ)

### 🔴 ¿Por qué la impresora térmica imprime texto con `%PDF-1.7` en lugar de dibujar el comprobante?
**Causa:** Se intentó enviar un archivo PDF generado con DomPDF o similar a una impresora térmica utilizando `content_type = 'escpos_base64'`. Las comanderas térmicas son dispositivos de hardware RAW de caracteres y no disponen de intérprete de PDF vectorial.  
**Solución:**
- Para impresoras térmicas (tickets 80mm/58mm), utilizar **únicamente** la sintaxis fluida de `ThermalBuilder` o una plantilla `PrintTemplateInterface`.
- Si obligatoriamente se debe imprimir un PDF en una térmica, se debe configurar el trabajo con `format: 'ticket_80mm'` y `content_type: 'pdf_base64'`; el agente local de Windows utilizará SumatraPDF rasterizando el documento a escala del papel.

---

### 🔴 Error `401 Unauthorized / Print token not provided` en servidores Apache
**Causa:** Apache y FastCGI por defecto omiten la cabecera HTTP `Authorization: Bearer` por motivos de seguridad en hosting compartido o redes locales sin SSL.  
**Solución:** Agregar las siguientes directivas en el archivo `public/.htaccess` de Laravel:
```apache
RewriteEngine On
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```
*El middleware `CheckPrintToken` de Nyxo ya cuenta con soporte nativo para inspeccionar `REDIRECT_HTTP_AUTHORIZATION`.*

---

### 🔴 Falla al copiar el Código de Enlace en redes LAN (`navigator.clipboard is undefined`)
**Causa:** Los navegadores modernos bloquean el acceso al portapapeles nativo (`navigator.clipboard`) si la conexión se realiza sobre HTTP sin cifrado (por ejemplo: `http://192.168.1.50` o `http://caja.local`).  
**Solución:** Utilizar una función con elemento DOM oculto de respaldo:
```javascript
window.copiarCodigo = async function(texto) {
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(texto);
            return true;
        } catch (e) {}
    }
    const input = document.createElement('textarea');
    input.value = texto;
    input.style.position = 'fixed';
    input.style.opacity = '0';
    document.body.appendChild(input);
    input.select();
    const exito = document.execCommand('copy');
    document.body.removeChild(input);
    return exito;
};
```

---

### 🔴 Error en Laravel 13 `MissingAttributeException`
**Causa:** Laravel 13 habilita por defecto el modo estricto en Eloquent (`Model::shouldBeStrict(true)`). Si tu código intenta acceder a propiedades eliminadas en migraciones previas (como `pie_orden`, `pie_recibo` o `titulo_recibo`), Eloquent arroja una excepción fatal.  
**Solución:** Los modelos oficiales `PrinterNode` y `PrintJob` incluidos en la versión Composer `nyxo-app/nyxo-printer` ya fueron higienizados y únicamente declaran las columnas físicas persistidas en su esquema agnóstico.

---

### 🔴 Soporte Multi-Empresa / Multi-Tenant (SaaS)
Si tu aplicación alberga múltiples inquilinos o comercios independientes:
1. En `config/nyxo-printer.php`, definir `'tenant_column' => 'empresa_id'` (o `'tenant_id'`).
2. Al crear el nodo, registrar el identificador correspondiente:
   ```php
   PrinterNode::create([
       'name' => 'Caja Sucursal 2',
       'empresa_id' => $tenantActual->id,
       'is_active' => true,
   ]);
   ```
3. En tus consultas de panel de control, filtrar mediante el scope de tu inquilino:
   ```php
   $nodosDelInquilino = PrinterNode::where('empresa_id', auth()->user()->empresa_id)->get();
   ```

---

*Manual de ingeniería elaborado y verificado para la distribución comercial y despliegue de **Nyxo Universal Printer**.*
