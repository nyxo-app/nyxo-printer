<?php

declare(strict_types=1);

namespace Nyxo\Printer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade unificada para la gestión e impresión directa con Nyxo Universal Printer.
 *
 * @method static \Nyxo\Printer\Builders\PrintJobBuilder to(int $nodeId)
 * @method static \Nyxo\Printer\Models\PrintJob pdf(int $nodeId, string $pdfBase64, string $format = 'a4', int $copies = 1)
 * @method static \Nyxo\Printer\Models\PrintJob thermal(int $nodeId, string $escposBase64, string $format = 'ticket_80mm', int $copies = 1)
 * @method static \Nyxo\Printer\Models\PrintJob raw(int $nodeId, string $rawText, string $format = 'raw', int $copies = 1)
 * @method static \Nyxo\Printer\Models\PrintJob template(int $nodeId, \Nyxo\Printer\Contracts\PrintTemplateInterface $template, int $copies = 1)
 * @method static string preview(\Nyxo\Printer\Contracts\PrintTemplateInterface|callable $builderOrTemplate, int $width = 80)
 * @method static \Nyxo\Printer\Models\PrinterNode|null node(int $nodeId)
 * @method static string getPairingCode(\Nyxo\Printer\Models\PrinterNode $node)
 * @method static \Nyxo\Printer\Contracts\PrintServiceInterface getPrintService()
 *
 * @see \Nyxo\Printer\NyxoPrinterManager
 */
class NyxoPrinter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nyxo-printer';
    }
}
