<?php

declare(strict_types=1);

namespace Nyxo\Printer\Templates;

use Nyxo\Printer\Builders\ThermalBuilder;
use Nyxo\Printer\Contracts\PrintTemplateInterface;

/**
 * Plantilla de Resumen de Orden de Servicio / Taller / Reparaciones.
 */
class OrderSummaryTemplate implements PrintTemplateInterface
{
    /**
     * @param array{
     *     empresa?: string,
     *     order_number: string|int,
     *     fecha?: string,
     *     cliente?: string,
     *     telefono?: string,
     *     equipo?: string,
     *     problema?: string,
     *     reparaciones?: array<int, array{nombre: string, precio: float|int}>,
     *     total?: float|int,
     *     qr?: string,
     *     barcode?: string,
     *     terminos?: string
     * } $data
     */
    public function __construct(
        protected array $data
    ) {}

    public function build(ThermalBuilder $ticket): void
    {
        // 1. Encabezado
        if (! empty($this->data['empresa'])) {
            $ticket->title($this->data['empresa'], doubleWidth: true, doubleHeight: true);
        }

        $orderNum = (string) $this->data['order_number'];
        $ticket->center("ORDEN DE SERVICIO #{$orderNum}", bold: true);
        $ticket->doubleLine('=');

        // 2. Información General
        if (! empty($this->data['fecha'])) {
            $ticket->text('Fecha: '.$this->data['fecha']);
        }
        if (! empty($this->data['cliente'])) {
            $ticket->text('Cliente: '.$this->data['cliente']);
        }
        if (! empty($this->data['telefono'])) {
            $ticket->text('Tel: '.$this->data['telefono']);
        }
        if (! empty($this->data['equipo'])) {
            $ticket->text('Equipo: '.$this->data['equipo']);
        }
        if (! empty($this->data['problema'])) {
            $ticket->text('Falla: '.$this->data['problema']);
        }

        $ticket->line('-');

        // 3. Tareas / Reparaciones
        if (! empty($this->data['reparaciones'])) {
            $ticket->table($this->data['reparaciones'], headerLeft: 'TRABAJO REALIZADO', headerRight: 'COSTO');
            $ticket->line('-');
        }

        // 4. Total si aplica
        if (isset($this->data['total']) && $this->data['total'] > 0) {
            $ticket->total((float) $this->data['total']);
        }

        // 5. Código de barras del número de orden
        $barcode = $this->data['barcode'] ?? (string) $orderNum;
        $ticket->barcode($barcode);

        // 6. QR si aplica
        if (! empty($this->data['qr'])) {
            $ticket->qr($this->data['qr']);
        }

        // 7. Términos y Condiciones
        if (! empty($this->data['terminos'])) {
            $ticket->line('-');
            $ticket->center($this->data['terminos']);
        }

        $ticket->cut();
    }
}
