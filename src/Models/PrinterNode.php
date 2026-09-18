<?php

declare(strict_types=1);

namespace Nyxo\Printer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Representa un puesto físico o computadora con el Agente de Impresión Nyxo instalado.
 *
 * @property int $id
 * @property string $name
 * @property string $print_token
 * @property string|null $pairing_code
 * @property int|null $empresa_id
 * @property \Illuminate\Support\Carbon|null $last_ping_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $is_online
 * @property-read string $codigo_enlace
 */
class PrinterNode extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('nyxo-printer.tables.nodes', 'printer_nodes');
    }

    protected $fillable = [
        'name',
        'print_token',
        'pairing_code',
        'empresa_id',
        'last_ping_at',
        'is_active',
    ];

    /**
     * Define los casts de atributos.
     */
    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'last_ping_at' => 'datetime',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relación con los trabajos de impresión encolados para este nodo.
     */
    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class, 'printer_node_id');
    }

    /**
     * Determina si el nodo ha reportado actividad reciente (últimos 2 minutos).
     */
    public function getIsOnlineAttribute(): bool
    {
        if (! $this->last_ping_at) {
            return false;
        }

        return $this->last_ping_at->greaterThanOrEqualTo(now()->subMinutes(2));
    }

    /**
     * Genera el Código de Enlace Rápido en formato Base64 ("url|token") para vincular el Agente Nyxo en 1 clic.
     */
    public function getCodigoEnlaceAttribute(): string
    {
        $prefix = (string) config('nyxo-printer.route_prefix', 'api/v1/print');
        $endpoint = url(trim($prefix, '/'));

        return base64_encode($endpoint.'|'.$this->print_token);
    }

    /**
     * Scope para filtrar únicamente nodos activos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Genera un token aleatorio seguro de 60 caracteres.
     */
    public static function generateToken(): string
    {
        return Str::random(60);
    }

    /**
     * Genera un código de emparejamiento numérico de 6 dígitos.
     */
    public static function generatePairingCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
