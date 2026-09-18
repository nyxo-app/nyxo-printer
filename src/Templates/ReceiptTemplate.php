<?php

declare(strict_types=1);

namespace Nyxo\Printer\Templates;

use Nyxo\Printer\Builders\ThermalBuilder;
use Nyxo\Printer\Contracts\PrintTemplateInterface;

/**
 * Plantilla estándar de Recibo / Ticket de Venta para comanderas térmicas de 80mm o 58mm.
 */
class ReceiptTemplate implements PrintTemplateInterface
{
    /**
     * @param array{
     *     empresa?: string,
     *     title?: string,
     *     datos?: array<string, string>,
     *     items?: array<int, array{nombre: string, cantidad?: int|float, precio: float|int}>,
     *     total: float|int,
     *     qr?: string,
     *     barcode?: string,
     *     footer?: string,
     *     open_drawer?: bool,
     *     cut?: bool
     * } $data
     */
    public function __construct(
        protected array $data
    ) {}

    public function build(ThermalBuilder $ticket): void
    {
        // 1. Encabezado / Empresa y Título
        $empresa = $this->data['empresa'] ?? null;
        $title = $this->data['title'] ?? 'RECIBO DE VENTA';

        if ($empresa) {
            $ticket->title($empresa, doubleWidth: true, doubleHeight: true);
        }

        $ticket->center($title, bold: true);
        $ticket->doubleLine('=');

        // 2. Metadatos / Datos Clave-Valor
        if (! empty($this->data['datos'])) {
            foreach ($this->data['datos'] as $key => $val) {
                $ticket->text("{$key}: {$val}");
            }
            $ticket->line('-');
        }

        // 3. Tabla de Artículos
        if (! empty($this->data['items'])) {
            $ticket->table($this->data['items']);
            $ticket->line('-');
        }

        // 4. Monto Total
        $total = (float) ($this->data['total'] ?? 0);
        $ticket->total($total);

        // 5. Código QR (ej. Facturación AFIP o Pago)
        if (! empty($this->data['qr'])) {
            $ticket->qr($this->data['qr']);
        }

        // 6. Código de Barras
        if (! empty($this->data['barcode'])) {
            $ticket->barcode($this->data['barcode']);
        }

        // 7. Pie de Página / Términos
        if (! empty($this->data['footer'])) {
            $ticket->center($this->data['footer']);
        }

        // 8. Opciones de Hardware
        if (! empty($this->data['open_drawer'])) {
            $ticket->openDrawer();
        }

        if (($this->data['cut'] ?? true) !== false) {
            $ticket->cut();
        }
    }
}
