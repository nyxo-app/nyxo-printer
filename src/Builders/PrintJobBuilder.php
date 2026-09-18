<?php

declare(strict_types=1);

namespace Nyxo\Printer\Builders;

use InvalidArgumentException;
use Nyxo\Printer\Contracts\PrintServiceInterface;
use Nyxo\Printer\Contracts\PrintTemplateInterface;
use Nyxo\Printer\Models\PrintJob;

/**
 * Unified Fluent Builder for configuring and dispatching print jobs.
 *
 * @method self title(string $text, bool $doubleWidth = true, bool $doubleHeight = true, string $align = 'center')
 * @method self text(string $text, string $align = 'left', bool $bold = false, bool $underline = false, bool $doubleHeight = false, bool $doubleWidth = false)
 * @method self center(string $text, bool $bold = false, bool $doubleHeight = false, bool $doubleWidth = false)
 * @method self right(string $text, bool $bold = false)
 * @method self line(string $char = '-')
 * @method self doubleLine(string $char = '=')
 * @method self table(array $items, string $headerLeft = 'DESCRIPTION', string $headerRight = 'PRICE')
 * @method self total(float $amount, string $label = 'TOTAL:', string $currency = '$')
 * @method self barcode(string $code, string $type = 'CODE39')
 * @method self qr(string $content, int $size = 6, string $errorCorrection = 'M')
 * @method self openDrawer(int $pin = 0)
 * @method self beep(int $times = 1)
 * @method self feed(int $lines = 1)
 * @method self cut(bool $full = false)
 * @method self custom(callable $callback)
 */
class PrintJobBuilder
{
    protected int $printerNodeId;

    protected int $copies = 1;

    protected string $format = 'a4';

    protected ?string $contentType = null;

    protected ?string $content = null;

    protected ?ThermalBuilder $thermalBuilder = null;

    public function __construct(
        int $printerNodeId,
        protected readonly PrintServiceInterface $printService
    ) {
        $this->printerNodeId = $printerNodeId;
    }

    /**
     * Set the number of copies to print.
     */
    public function copies(int $copies): self
    {
        $this->copies = max(1, $copies);

        return $this;
    }

    /**
     * Define the output format ('a4', 'ticket_80mm', 'ticket_58mm', 'raw').
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set the thermal paper width in mm (80 or 58) and automatically adjust format.
     */
    public function width(int $width): self
    {
        $this->getThermalBuilder()->width($width);
        $this->format = $width === 58 ? 'ticket_58mm' : 'ticket_80mm';

        return $this;
    }

    /**
     * Configure the job to print a PDF document via Base64 string.
     */
    public function pdf(string $base64, string $format = 'a4'): self
    {
        $this->contentType = 'pdf_base64';
        $this->content = $base64;
        $this->format = $format;

        return $this;
    }

    /**
     * Load an existing PDF file from disk and encode it to Base64 for printing.
     */
    public function pdfFile(string $filePath, string $format = 'a4'): self
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("PDF file does not exist at path: {$filePath}");
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new InvalidArgumentException("Unable to read PDF file at path: {$filePath}");
        }

        return $this->pdf(base64_encode($data), $format);
    }

    /**
     * Configure the job to print raw ASCII text (e.g. impact / dot matrix printers).
     */
    public function raw(string $text, string $format = 'raw'): self
    {
        $this->contentType = 'raw_text';
        $this->content = $text;
        $this->format = $format;

        return $this;
    }

    /**
     * Configure the job to print structured JSON data.
     */
    public function json(array $data, string $format = 'json'): self
    {
        $this->contentType = 'json_structured';
        $this->content = (string) json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->format = $format;

        return $this;
    }

    /**
     * Apply a predefined template implementing PrintTemplateInterface.
     */
    public function template(PrintTemplateInterface $template): self
    {
        $template->build($this->getThermalBuilder());

        return $this;
    }

    /**
     * Delegate thermal formatting calls directly to the underlying ThermalBuilder.
     */
    public function __call(string $name, array $arguments): mixed
    {
        $builder = $this->getThermalBuilder();

        if (method_exists($builder, $name)) {
            $builder->{$name}(...$arguments);

            return $this;
        }

        throw new InvalidArgumentException("Method [{$name}] does not exist on ".static::class);
    }

    /**
     * Persist and enqueue the print job in the database for the designated printer node.
     */
    public function send(): PrintJob
    {
        $this->resolvePayload();

        if (! $this->contentType || ! $this->content) {
            throw new InvalidArgumentException('No printable content defined (PDF, thermal ESC/POS, or raw text).');
        }

        return $this->printService->enqueue(
            printerNodeId: $this->printerNodeId,
            contentType: $this->contentType,
            content: $this->content,
            format: $this->format,
            copies: $this->copies
        );
    }

    /**
     * Generate an HTML preview of the document for development and debugging.
     */
    public function preview(): string
    {
        if ($this->thermalBuilder) {
            return $this->thermalBuilder->preview();
        }

        if ($this->contentType === 'pdf_base64') {
            return '<iframe src="data:application/pdf;base64,'.$this->content.'" style="width:100%; height:600px; border:none;"></iframe>';
        }

        return '<pre style="background:#f8fafc; padding:16px; border:1px solid #e2e8f0; font-family:monospace;">'.htmlspecialchars($this->content ?? 'No content').'</pre>';
    }

    protected function getThermalBuilder(): ThermalBuilder
    {
        if (! $this->thermalBuilder) {
            $this->thermalBuilder = new ThermalBuilder;
            $this->contentType = 'escpos_base64';
            $this->format = 'ticket_80mm';
        }

        return $this->thermalBuilder;
    }

    protected function resolvePayload(): void
    {
        if ($this->thermalBuilder && (! $this->content || $this->contentType === 'escpos_base64')) {
            $this->contentType = 'escpos_base64';
            $this->content = $this->thermalBuilder->toBase64();
        }
    }
}
