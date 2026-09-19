<?php

declare(strict_types=1);

namespace Nyxo\Printer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Represents a physical workstation or POS computer running the Nyxo Desktop Agent.
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
 * @property-read string $pairing_string
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
     * Bootstrap model events.
     */
    protected static function booted(): void
    {
        static::creating(function (self $node): void {
            if (empty($node->print_token)) {
                $node->print_token = static::generateToken();
            }
            if (empty($node->pairing_code)) {
                $node->pairing_code = static::generatePairingCode();
            }
        });
    }

    /**
     * Define attribute casts.
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
     * Relationship with print jobs queued for this node.
     */
    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class, 'printer_node_id');
    }

    /**
     * Determines whether the printer node has reported activity in the last 2 minutes.
     */
    public function getIsOnlineAttribute(): bool
    {
        if (! $this->last_ping_at) {
            return false;
        }

        return $this->last_ping_at->greaterThanOrEqualTo(now()->subMinutes(2));
    }

    /**
     * Generates the 1-click Base64 pairing string ("url|token") for the Nyxo Desktop Agent.
     */
    public function getPairingStringAttribute(): string
    {
        $prefix = (string) config('nyxo-printer.route_prefix', 'api/v1/print');
        $endpoint = url(trim($prefix, '/'));

        return base64_encode($endpoint.'|'.$this->print_token);
    }

    /**
     * Backward-compatible alias for pairing_string.
     */
    public function getCodigoEnlaceAttribute(): string
    {
        return $this->getPairingStringAttribute();
    }

    /**
     * Scope to filter active nodes only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Generate a cryptographically secure 60-character random token.
     */
    public static function generateToken(): string
    {
        return Str::random(60);
    }

    /**
     * Generate a 6-digit numeric pairing code.
     */
    public static function generatePairingCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
