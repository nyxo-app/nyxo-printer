<?php

declare(strict_types=1);

namespace Nyxo\Printer\Builders;

use InvalidArgumentException;
use Nyxo\Printer\Contracts\PrintServiceInterface;
use Nyxo\Printer\Contracts\PrintTemplateInterface;
use Nyxo\Printer\Models\PrintJob;

/**
 * Fluent Builder unificado para la configuración y despacho de trabajos de impresión.
 *
 * @method self title(string $text, bool $doubleWidth = true, bool $doubleHeight = true, string $align = 'center')
 * @method self text(string $text, string $align = 'left', bool $bold = false, bool $underline = false, bool $doubleHeight = false, bool $doubleWidth = false)
 * @method self center(string $text, bool $bold = false, bool $doubleHeight = false, bool $doubleWidth = false)
 * @method self right(string $text, bool $bold = false)
 * @method self line(string $char = '-')
 * @method self doubleLine(string $char = '=')
 * @method self table(array $items, string $headerLeft = 'DESCRIPCION', string $headerRight = 'PRECIO')
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
     * Define la cantidad de copias a imprimir.
     */
    public function copies(int $copies): self
    {
        $this->copies = max(1, $copies);

        return $this;
    }

    /**
     * Define el formato de salida ('a4', 'ticket_80mm', 'ticket_58mm', 'raw').
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Define el ancho del papel térmico en mm (80 o 58) y adapta el formato.
     */
    public function width(int $width): self
    {
        $this->getThermalBuilder()->width($width);
        $this->format = $width === 58 ? 'ticket_58mm' : 'ticket_80mm';

        return $this;
    }

    /**
     * Configura el trabajo para imprimir un documento PDF mediante su cadena Base64.
     */
    public function pdf(string $base64, string $format = 'a4'): self
    {
        $this->contentType = 'pdf_base64';
        $this->content = $base64;
        $this->format = $format;

        return $this;
    }

    /**
     * Carga un archivo PDF existente desde el disco y lo codifica en Base64 para imprimir.
     */
    public function pdfFile(string $filePath, string $format = 'a4'): self
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("El archivo PDF no existe en la ruta: {$filePath}");
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new InvalidArgumentException("No se pudo leer el archivo PDF: {$filePath}");
        }

        return $this->pdf(base64_encode($data), $format);
    }

    /**
     * Configura el trabajo para imprimir texto plano ASCII (impresoras de impacto/matriciales).
     */
    public function raw(string $text, string $format = 'raw'): self
    {
        $this->contentType = 'raw_text';
        $this->content = $text;
        $this->format = $format;

        return $this;
    }

    /**
     * Configura el trabajo para imprimir un payload JSON estructurado.
     */
    public function json(array $data, string $format = 'json'): self
    {
        $this->contentType = 'json_structured';
        $this->content = (string) json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->format = $format;

        return $this;
    }

    /**
     * Aplica una plantilla predefinida que implemente PrintTemplateInterface.
     */
    public function template(PrintTemplateInterface $template): self
    {
        $template->build($this->getThermalBuilder());

        return $this;
    }

    /**
     * Delega llamadas de métodos térmicos directamente al ThermalBuilder interno.
     */
    public function __call(string $name, array $arguments): mixed
    {
        $builder = $this->getThermalBuilder();

        if (method_exists($builder, $name)) {
            $builder->{$name}(...$arguments);

            return $this;
        }

        throw new InvalidArgumentException("El método [{$name}] no existe en ".static::class);
    }

    /**
     * Despacha y guarda el trabajo de impresión en la base de datos para el nodo asignado.
     */
    public function send(): PrintJob
    {
        $this->resolvePayload();

        if (! $this->contentType || ! $this->content) {
            throw new InvalidArgumentException('No se ha definido ningún contenido para imprimir (PDF, texto térmico o raw).');
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
     * Genera una previsualización en HTML del documento para depuración en desarrollo.
     */
    public function preview(): string
    {
        if ($this->thermalBuilder) {
            return $this->thermalBuilder->preview();
        }

        if ($this->contentType === 'pdf_base64') {
            return '<iframe src="data:application/pdf;base64,'.$this->content.'" style="width:100%; height:600px; border:none;"></iframe>';
        }

        return '<pre style="background:#f8fafc; padding:16px; border:1px solid #e2e8f0; font-family:monospace;">'.htmlspecialchars($this->content ?? 'Sin contenido').'</pre>';
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
