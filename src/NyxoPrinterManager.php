<?php

declare(strict_types=1);

namespace Nyxo\Printer;

use Nyxo\Printer\Builders\PrintJobBuilder;
use Nyxo\Printer\Builders\ThermalBuilder;
use Nyxo\Printer\Contracts\PrintServiceInterface;
use Nyxo\Printer\Contracts\PrintTemplateInterface;
use Nyxo\Printer\Models\PrinterNode;
use Nyxo\Printer\Models\PrintJob;

class NyxoPrinterManager
{
    public function __construct(
        protected readonly PrintServiceInterface $printService
    ) {}

    /**
     * Inicia la construcción fluida de un trabajo para el nodo indicado.
     */
    public function to(int $nodeId): PrintJobBuilder
    {
        return new PrintJobBuilder($nodeId, $this->printService);
    }

    /**
     * Encola directamente un documento PDF en Base64.
     */
    public function pdf(int $nodeId, string $pdfBase64, string $format = 'a4', int $copies = 1): PrintJob
    {
        return $this->to($nodeId)->copies($copies)->pdf($pdfBase64, $format)->send();
    }

    /**
     * Encola directamente un payload binario ESC/POS en Base64.
     */
    public function thermal(int $nodeId, string $escposBase64, string $format = 'ticket_80mm', int $copies = 1): PrintJob
    {
        return $this->printService->enqueueThermal($nodeId, $escposBase64, $format, $copies);
    }

    /**
     * Encola directamente texto plano ASCII.
     */
    public function raw(int $nodeId, string $rawText, string $format = 'raw', int $copies = 1): PrintJob
    {
        return $this->printService->enqueueRaw($nodeId, $rawText, $format, $copies);
    }

    /**
     * Encola un trabajo aplicando directamente una plantilla.
     */
    public function template(int $nodeId, PrintTemplateInterface $template, int $copies = 1): PrintJob
    {
        return $this->to($nodeId)->copies($copies)->template($template)->send();
    }

    /**
     * Genera una previsualización HTML en tiempo real sin enviar a imprimir.
     *
     * @param PrintTemplateInterface|callable(ThermalBuilder): void $builderOrTemplate
     */
    public function preview(PrintTemplateInterface|callable $builderOrTemplate, int $width = 80): string
    {
        $thermal = new ThermalBuilder($width);

        if ($builderOrTemplate instanceof PrintTemplateInterface) {
            $builderOrTemplate->build($thermal);
        } else {
            $builderOrTemplate($thermal);
        }

        return $thermal->preview();
    }

    /**
     * Obtiene la instancia de un nodo de impresión por su ID.
     */
    public function node(int $nodeId): ?PrinterNode
    {
        return PrinterNode::find($nodeId);
    }

    /**
     * Devuelve el Código de Enlace Rápido en formato Base64 ("url|token").
     */
    public function getPairingCode(PrinterNode $node): string
    {
        return $node->codigo_enlace;
    }

    /**
     * Obtiene el servicio de bajo nivel de encolado.
     */
    public function getPrintService(): PrintServiceInterface
    {
        return $this->printService;
    }
}
