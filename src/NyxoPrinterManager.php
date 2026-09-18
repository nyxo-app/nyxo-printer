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
     * Start a fluent job builder for the specified printer node.
     */
    public function to(int $nodeId): PrintJobBuilder
    {
        return new PrintJobBuilder($nodeId, $this->printService);
    }

    /**
     * Directly enqueue a Base64-encoded PDF document.
     */
    public function pdf(int $nodeId, string $pdfBase64, string $format = 'a4', int $copies = 1): PrintJob
    {
        return $this->to($nodeId)->copies($copies)->pdf($pdfBase64, $format)->send();
    }

    /**
     * Directly enqueue a Base64-encoded ESC/POS binary payload.
     */
    public function thermal(int $nodeId, string $escposBase64, string $format = 'ticket_80mm', int $copies = 1): PrintJob
    {
        return $this->printService->enqueueThermal($nodeId, $escposBase64, $format, $copies);
    }

    /**
     * Directly enqueue raw ASCII text.
     */
    public function raw(int $nodeId, string $rawText, string $format = 'raw', int $copies = 1): PrintJob
    {
        return $this->printService->enqueueRaw($nodeId, $rawText, $format, $copies);
    }

    /**
     * Enqueue a print job applying a reusable template instance.
     */
    public function template(int $nodeId, PrintTemplateInterface $template, int $copies = 1): PrintJob
    {
        return $this->to($nodeId)->copies($copies)->template($template)->send();
    }

    /**
     * Generate an in-browser photorealistic HTML thermal preview without dispatching to hardware.
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
     * Find a printer node by its primary key.
     */
    public function node(int $nodeId): ?PrinterNode
    {
        return PrinterNode::find($nodeId);
    }

    /**
     * Returns the 1-click Base64 pairing string ("url|token") for the desktop agent.
     */
    public function getPairingString(PrinterNode $node): string
    {
        return $node->pairing_string;
    }

    /**
     * Backward-compatible alias for getPairingString.
     */
    public function getPairingCode(PrinterNode $node): string
    {
        return $this->getPairingString($node);
    }

    /**
     * Retrieve the underlying print service instance.
     */
    public function getPrintService(): PrintServiceInterface
    {
        return $this->printService;
    }
}
