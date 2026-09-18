<?php

declare(strict_types=1);

namespace Nyxo\Printer\Services;

use InvalidArgumentException;
use Nyxo\Printer\Contracts\PrintServiceInterface;
use Nyxo\Printer\Events\PrintJobCreated;
use Nyxo\Printer\Models\PrinterNode;
use Nyxo\Printer\Models\PrintJob;

class PrintService implements PrintServiceInterface
{
    /**
     * Enqueue a generic print job into the database.
     */
    public function enqueue(
        int $printerNodeId,
        string $contentType,
        string $content,
        string $format = 'a4',
        int $copies = 1
    ): PrintJob {
        $this->ensureNodeExists($printerNodeId);

        $lastJob = null;
        $copies = max(1, $copies);

        for ($i = 0; $i < $copies; $i++) {
            $lastJob = PrintJob::create([
                'printer_node_id' => $printerNodeId,
                'format' => $format,
                'content_type' => $contentType,
                'content' => $content,
                'status' => 'pending',
            ]);

            event(new PrintJobCreated($lastJob));
        }

        return $lastJob;
    }

    /**
     * Enqueue a Base64 PDF document for A4 or thermal printers.
     */
    public function enqueueA4(
        int $printerNodeId,
        string $pdfBase64,
        string $format = 'a4',
        int $copies = 1
    ): PrintJob {
        return $this->enqueue($printerNodeId, 'pdf_base64', $pdfBase64, $format, $copies);
    }

    /**
     * Enqueue binary ESC/POS commands for thermal receipt printers.
     */
    public function enqueueThermal(
        int $printerNodeId,
        string $escposBase64,
        string $format = 'ticket_80mm',
        int $copies = 1
    ): PrintJob {
        return $this->enqueue($printerNodeId, 'escpos_base64', $escposBase64, $format, $copies);
    }

    /**
     * Enqueue raw ASCII text for dot matrix or basic printers.
     */
    public function enqueueRaw(
        int $printerNodeId,
        string $rawText,
        string $format = 'raw',
        int $copies = 1
    ): PrintJob {
        return $this->enqueue($printerNodeId, 'raw_text', $rawText, $format, $copies);
    }

    /**
     * Enqueue structured JSON data.
     */
    public function enqueueJson(
        int $printerNodeId,
        array $data,
        string $format = 'json',
        int $copies = 1
    ): PrintJob {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);

        return $this->enqueue($printerNodeId, 'json_structured', (string) $content, $format, $copies);
    }

    protected function ensureNodeExists(int $printerNodeId): void
    {
        $exists = PrinterNode::where('id', $printerNodeId)->exists();

        if (! $exists) {
            throw new InvalidArgumentException("Printer node #{$printerNodeId} does not exist.");
        }
    }
}
