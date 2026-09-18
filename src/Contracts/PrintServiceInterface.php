<?php

declare(strict_types=1);

namespace Nyxo\Printer\Contracts;

use Nyxo\Printer\Models\PrintJob;

interface PrintServiceInterface
{
    /**
     * Encola un trabajo de impresión genérico en la base de datos.
     */
    public function enqueue(
        int $printerNodeId,
        string $contentType,
        string $content,
        string $format = 'a4',
        int $copies = 1
    ): PrintJob;

    /**
     * Encola un documento PDF en Base64 para impresoras A4 o térmicas.
     */
    public function enqueueA4(int $printerNodeId, string $pdfBase64, string $format = 'a4', int $copies = 1): PrintJob;

    /**
     * Encola comandos binarios ESC/POS para impresoras térmicas.
     */
    public function enqueueThermal(int $printerNodeId, string $escposBase64, string $format = 'ticket_80mm', int $copies = 1): PrintJob;

    /**
     * Encola texto plano ASCII para impresoras matriciales o notas simples.
     */
    public function enqueueRaw(int $printerNodeId, string $rawText, string $format = 'raw', int $copies = 1): PrintJob;

    /**
     * Encola un payload JSON estructurado (formato legacy).
     */
    public function enqueueJson(int $printerNodeId, array $data, string $format = 'json', int $copies = 1): PrintJob;
}
