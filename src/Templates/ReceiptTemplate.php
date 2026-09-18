<?php

declare(strict_types=1);

namespace Nyxo\Printer\Templates;

use Nyxo\Printer\Builders\ThermalBuilder;
use Nyxo\Printer\Contracts\PrintTemplateInterface;

/**
 * Standard Sales Receipt / Ticket template for 80mm or 58mm thermal printers.
 */
class ReceiptTemplate implements PrintTemplateInterface
{
    /**
     * @param array{
     *     company?: string,
     *     empresa?: string,
     *     title?: string,
     *     metadata?: array<string, string>,
     *     datos?: array<string, string>,
     *     items?: array<int, array{name?: string, nombre?: string, qty?: int|float, cantidad?: int|float, price?: float|int, precio?: float|int}>,
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
        // 1. Company Header & Title
        $company = $this->data['company'] ?? $this->data['empresa'] ?? null;
        $title = $this->data['title'] ?? 'SALES RECEIPT';

        if ($company) {
            $ticket->title($company, doubleWidth: true, doubleHeight: true);
        }

        $ticket->center($title, bold: true);
        $ticket->doubleLine('=');

        // 2. Metadata / Key-Value Details
        $metadata = $this->data['metadata'] ?? $this->data['datos'] ?? [];
        if (! empty($metadata)) {
            foreach ($metadata as $key => $val) {
                $ticket->text("{$key}: {$val}");
            }
            $ticket->line('-');
        }

        // 3. Item Table
        if (! empty($this->data['items'])) {
            $ticket->table($this->data['items']);
            $ticket->line('-');
        }

        // 4. Total Amount
        $total = (float) ($this->data['total'] ?? 0);
        $ticket->total($total);

        // 5. QR Code (e.g. Fiscal Verification or Payment Link)
        if (! empty($this->data['qr'])) {
            $ticket->qr($this->data['qr']);
        }

        // 6. Barcode
        if (! empty($this->data['barcode'])) {
            $ticket->barcode($this->data['barcode']);
        }

        // 7. Footer / Terms
        if (! empty($this->data['footer'])) {
            $ticket->center($this->data['footer']);
        }

        // 8. Hardware Control
        if (! empty($this->data['open_drawer'])) {
            $ticket->openDrawer();
        }

        if (($this->data['cut'] ?? true) !== false) {
            $ticket->cut();
        }
    }
}
