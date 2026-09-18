<?php

declare(strict_types=1);

namespace Nyxo\Printer\Builders;

use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\Printer;

class ThermalBuilder
{
    protected int $width;

    protected string $codepage;

    /**
     * @var array<int, callable(Printer): void>
     */
    protected array $operations = [];

    /**
     * @var array<int, array{type: string, data: mixed}>
     */
    protected array $logEntries = [];

    public function __construct(?int $width = null, ?string $codepage = null)
    {
        $this->width = $width ?? (int) config('nyxo-printer.default_width', 80);
        $this->codepage = $codepage ?? (string) config('nyxo-printer.codepage', 'CP850');
    }

    /**
     * Define el ancho del papel térmico en milímetros (80 o 58).
     */
    public function width(int $width): self
    {
        $this->width = in_array($width, [58, 80], true) ? $width : 80;

        return $this;
    }

    /**
     * Obtiene la cantidad de caracteres por línea según el ancho del papel.
     */
    public function getColumns(): int
    {
        return $this->width === 58 ? 32 : 42;
    }

    /**
     * Agrega un título destacado al ticket.
     */
    public function title(
        string $text,
        bool $doubleWidth = true,
        bool $doubleHeight = true,
        string $align = 'center'
    ): self {
        $this->logEntries[] = ['type' => 'title', 'data' => ['text' => $text, 'align' => $align]];

        $this->operations[] = function (Printer $printer) use ($text, $doubleWidth, $doubleHeight, $align) {
            $this->applyAlignment($printer, $align);

            $mode = Printer::MODE_EMPHASIZED;
            if ($doubleWidth) {
                $mode |= Printer::MODE_DOUBLE_WIDTH;
            }
            if ($doubleHeight) {
                $mode |= Printer::MODE_DOUBLE_HEIGHT;
            }

            $printer->selectPrintMode($mode);
            $printer->text($this->sanitizeText($text)."\n");
            $printer->selectPrintMode();
        };

        return $this;
    }

    /**
     * Imprime una línea de texto estándar.
     */
    public function text(
        string $text,
        string $align = 'left',
        bool $bold = false,
        bool $underline = false,
        bool $doubleHeight = false,
        bool $doubleWidth = false
    ): self {
        $this->logEntries[] = ['type' => 'text', 'data' => ['text' => $text, 'align' => $align, 'bold' => $bold]];

        $this->operations[] = function (Printer $printer) use ($text, $align, $bold, $underline, $doubleHeight, $doubleWidth) {
            $this->applyAlignment($printer, $align);

            $mode = Printer::MODE_FONT_A;
            if ($bold) {
                $mode |= Printer::MODE_EMPHASIZED;
            }
            if ($doubleHeight) {
                $mode |= Printer::MODE_DOUBLE_HEIGHT;
            }
            if ($doubleWidth) {
                $mode |= Printer::MODE_DOUBLE_WIDTH;
            }
            if ($underline) {
                $printer->setUnderline(Printer::UNDERLINE_SINGLE);
            }

            $printer->selectPrintMode($mode);
            $printer->text($this->sanitizeText($text)."\n");
            $printer->selectPrintMode();

            if ($underline) {
                $printer->setUnderline(Printer::UNDERLINE_NONE);
            }
        };

        return $this;
    }

    /**
     * Imprime texto centrado con opciones de estilo.
     */
    public function center(string $text, bool $bold = false, bool $doubleHeight = false, bool $doubleWidth = false): self
    {
        return $this->text($text, align: 'center', bold: $bold, doubleHeight: $doubleHeight, doubleWidth: $doubleWidth);
    }

    /**
     * Imprime texto alineado a la derecha.
     */
    public function right(string $text, bool $bold = false): self
    {
        return $this->text($text, align: 'right', bold: $bold);
    }

    /**
     * Imprime una línea horizontal divisoria ajustada al ancho del papel.
     */
    public function line(string $char = '-'): self
    {
        $cols = $this->getColumns();
        $lineText = str_repeat(mb_substr($char, 0, 1), $cols);

        $this->logEntries[] = ['type' => 'line', 'data' => $lineText];

        $this->operations[] = function (Printer $printer) use ($lineText) {
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode();
            $printer->text($lineText."\n");
        };

        return $this;
    }

    /**
     * Imprime una línea doble horizontal divisoria (ej: '======').
     */
    public function doubleLine(string $char = '='): self
    {
        return $this->line($char);
    }

    /**
     * Imprime una tabla alineada de artículos / servicios y precios.
     *
     * @param array<int, array{nombre: string, cantidad?: int|float, precio: float|int|string}> $items
     */
    public function table(array $items, string $headerLeft = 'DESCRIPCION', string $headerRight = 'PRECIO'): self
    {
        $cols = $this->getColumns();
        $this->logEntries[] = ['type' => 'table', 'data' => $items];

        $this->operations[] = function (Printer $printer) use ($items, $cols, $headerLeft, $headerRight) {
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_EMPHASIZED);

            // Cabecera
            $leftLen = $cols - 12;
            $header = str_pad(mb_substr($headerLeft, 0, $leftLen), $leftLen).' '.str_pad($headerRight, 11, ' ', STR_PAD_LEFT);
            $printer->text($this->sanitizeText($header)."\n");
            $printer->selectPrintMode();

            // Filas
            foreach ($items as $item) {
                $nombre = (string) ($item['nombre'] ?? 'Item');
                $cant = isset($item['cantidad']) ? ' x'.(string) $item['cantidad'] : '';
                $desc = $nombre.$cant;
                $precioNum = (float) ($item['precio'] ?? 0);
                $precioFormatted = '$'.number_format($precioNum, 2, ',', '.');

                $descCol = str_pad(mb_substr($desc, 0, $leftLen), $leftLen);
                $priceCol = str_pad($precioFormatted, 11, ' ', STR_PAD_LEFT);

                $row = $descCol.' '.$priceCol;
                $printer->text($this->sanitizeText($row)."\n");
            }
        };

        return $this;
    }

    /**
     * Imprime el monto total destacado a la derecha.
     */
    public function total(float $amount, string $label = 'TOTAL:', string $currency = '$'): self
    {
        $formatted = $currency.number_format($amount, 2, ',', '.');
        $this->logEntries[] = ['type' => 'total', 'data' => ['label' => $label, 'amount' => $formatted]];

        $this->operations[] = function (Printer $printer) use ($label, $formatted) {
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_EMPHASIZED);
            $printer->text($this->sanitizeText("{$label} {$formatted}\n"));
            $printer->selectPrintMode();
        };

        return $this;
    }

    /**
     * Imprime un código de barras 1D (Code39, Code128, etc.).
     */
    public function barcode(string $code, string $type = 'CODE39'): self
    {
        $this->logEntries[] = ['type' => 'barcode', 'data' => $code];

        $this->operations[] = function (Printer $printer) use ($code) {
            try {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $padded = str_pad($code, 8, '0', STR_PAD_LEFT);
                $printer->barcode($padded, Printer::BARCODE_CODE39);
                $printer->feed(1);
            } catch (\Throwable) {
                // Si la impresora física no soporta el comando, continuar sin abortar
            }
        };

        return $this;
    }

    /**
     * Imprime un código QR (con soporte de comando nativo o fallback gráfico).
     */
    public function qr(string $content, int $size = 6, string $errorCorrection = 'M'): self
    {
        $this->logEntries[] = ['type' => 'qr', 'data' => $content];

        $this->operations[] = function (Printer $printer) use ($content, $size) {
            try {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->qrCode($content, Printer::QR_ECLEVEL_M, max(1, min(16, $size)));
                $printer->feed(1);
            } catch (\Throwable) {
                // Continuar silenciosamente ante hardware muy antiguo
            }
        };

        return $this;
    }

    /**
     * Envía un pulso eléctrico para abrir el cajón portamonedas (RJ11/RJ12).
     */
    public function openDrawer(int $pin = 0): self
    {
        $this->logEntries[] = ['type' => 'drawer', 'data' => $pin];

        $this->operations[] = function (Printer $printer) use ($pin) {
            $printer->pulse($pin);
        };

        return $this;
    }

    /**
     * Emite una señal sonora / alarma en la impresora (Buzzer de cocina/caja).
     */
    public function beep(int $times = 1): self
    {
        $this->logEntries[] = ['type' => 'beep', 'data' => $times];

        $this->operations[] = function (Printer $printer) use ($times) {
            for ($i = 0; $i < $times; $i++) {
                // ESC B (Buzzer) o Bell
                $printer->getPrintConnector()->write("\x1B\x42\x02\x02");
            }
        };

        return $this;
    }

    /**
     * Avanza el papel la cantidad de líneas especificada.
     */
    public function feed(int $lines = 1): self
    {
        $this->logEntries[] = ['type' => 'feed', 'data' => $lines];

        $this->operations[] = function (Printer $printer) use ($lines) {
            $printer->feed(max(1, $lines));
        };

        return $this;
    }

    /**
     * Corta el papel automáticamente.
     */
    public function cut(bool $full = false): self
    {
        $this->logEntries[] = ['type' => 'cut', 'data' => $full ? 'full' : 'partial'];

        $this->operations[] = function (Printer $printer) use ($full) {
            $printer->feed(2);
            $mode = $full ? Printer::CUT_FULL : Printer::CUT_PARTIAL;
            $printer->cut($mode);
        };

        return $this;
    }

    /**
     * Permite ejecutar comandos personalizados crudos directamente sobre la instancia de Printer.
     *
     * @param callable(Printer): void $callback
     */
    public function custom(callable $callback): self
    {
        $this->operations[] = $callback;

        return $this;
    }

    /**
     * Compila todas las operaciones en comandos binarios ESC/POS y devuelve el resultado en Base64.
     * Implementa la regla crítica de PHP 8.x para evitar errores de buffer nulo.
     */
    public function toBase64(): string
    {
        $connector = new DummyPrintConnector;
        $printer = new Printer($connector);

        $printer->initialize();

        foreach ($this->operations as $op) {
            $op($printer);
        }

        // ⚠️ REGLA CRÍTICA EN PHP 8.x:
        // Obtener el buffer del conector ANTES de ejecutar $printer->close()
        $data = $connector->getData();
        $printer->close();

        return base64_encode($data);
    }

    /**
     * Genera una previsualización visual interactiva en HTML simulando el papel térmico continuo.
     */
    public function preview(): string
    {
        $cols = $this->getColumns();
        $widthPx = $this->width === 58 ? '300px' : '380px';

        $html = '<div style="max-width: '.$widthPx.'; margin: 20px auto; background: #fffdf5; padding: 24px 18px; font-family: monospace; font-size: 12px; color: #1e293b; border: 1px solid #e2e8f0; border-top: 4px dashed #94a3b8; border-bottom: 4px dashed #94a3b8; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); line-height: 1.4;">';

        foreach ($this->logEntries as $entry) {
            $type = $entry['type'];
            $data = $entry['data'];

            switch ($type) {
                case 'title':
                    $align = $data['align'] ?? 'center';
                    $html .= '<div style="text-align: '.$align.'; font-size: 16px; font-weight: bold; margin: 8px 0; letter-spacing: 0.5px;">'.htmlspecialchars($data['text']).'</div>';
                    break;
                case 'text':
                    $align = $data['align'] ?? 'left';
                    $bold = ! empty($data['bold']) ? 'font-weight: bold;' : '';
                    $html .= '<div style="text-align: '.$align.'; '.$bold.' white-space: pre-wrap;">'.htmlspecialchars($data['text']).'</div>';
                    break;
                case 'line':
                    $html .= '<div style="color: #64748b; margin: 6px 0; overflow: hidden; white-space: nowrap;">'.htmlspecialchars((string) $data).'</div>';
                    break;
                case 'table':
                    $html .= '<div style="margin: 8px 0;">';
                    foreach ((array) $data as $row) {
                        $html .= '<div style="display: flex; justify-content: space-between; gap: 8px;"><span>'.htmlspecialchars((string) ($row['nombre'] ?? 'Item')).'</span><span>$'.number_format((float) ($row['precio'] ?? 0), 2, ',', '.').'</span></div>';
                    }
                    $html .= '</div>';
                    break;
                case 'total':
                    $html .= '<div style="text-align: right; font-size: 15px; font-weight: bold; margin: 10px 0; border-top: 1px solid #cbd5e1; padding-top: 6px;">'.htmlspecialchars($data['label']).' '.htmlspecialchars($data['amount']).'</div>';
                    break;
                case 'barcode':
                    $html .= '<div style="text-align: center; margin: 12px 0; padding: 6px; background: #f8fafc; border: 1px dashed #cbd5e1; font-size: 11px;">||| || |||| | ||||| ||| ||<br><strong>'.htmlspecialchars((string) $data).'</strong></div>';
                    break;
                case 'qr':
                    $html .= '<div style="text-align: center; margin: 12px 0; padding: 10px; background: #f8fafc; border: 1px dashed #cbd5e1; font-size: 10px; word-break: break-all;">[ QR CODE ]<br><span style="color:#64748b;">'.htmlspecialchars((string) $data).'</span></div>';
                    break;
                case 'drawer':
                    $html .= '<div style="text-align: center; font-size: 10px; color: #6366f1; margin: 4px 0;">[ ⚡ Pulso Apertura de Cajón ]</div>';
                    break;
                case 'beep':
                    $html .= '<div style="text-align: center; font-size: 10px; color: #eab308; margin: 4px 0;">[ 🔔 Alarma Sonora x'.$data.' ]</div>';
                    break;
                case 'cut':
                    $html .= '<div style="text-align: center; color: #94a3b8; margin-top: 14px; font-size: 10px; border-top: 1px dashed #cbd5e1; padding-top: 4px;">--- CORTE DE PAPEL ---</div>';
                    break;
                case 'feed':
                    $html .= str_repeat('<br>', max(1, (int) $data));
                    break;
            }
        }

        $html .= '</div>';

        return $html;
    }

    protected function applyAlignment(Printer $printer, string $align): void
    {
        switch (strtolower($align)) {
            case 'center':
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                break;
            case 'right':
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                break;
            default:
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                break;
        }
    }

    /**
     * Sanitiza caracteres en español convirtiendo UTF-8 a la codificación de la impresora.
     */
    protected function sanitizeText(string $text): string
    {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', $this->codepage.'//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $text;
    }
}
