<?php

declare(strict_types=1);

namespace Nyxo\Printer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a queued print job for a physical printer workstation.
 *
 * @property int $id
 * @property int $printer_node_id
 * @property string $format
 * @property string $content_type
 * @property string $content
 * @property string $status
 * @property int $attempts
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Nyxo\Printer\Models\PrinterNode $printerNode
 */
class PrintJob extends Model
{
    use HasFactory, Prunable;

    public function getTable(): string
    {
        return config('nyxo-printer.tables.jobs', 'print_jobs');
    }

    protected $fillable = [
        'printer_node_id',
        'format',
        'content_type',
        'content',
        'status',
        'attempts',
        'error_message',
    ];

    /**
     * Define attribute casts.
     */
    protected function casts(): array
    {
        return [
            'printer_node_id' => 'integer',
            'attempts' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relationship with the target Printer Node.
     */
    public function printerNode(): BelongsTo
    {
        return $this->belongsTo(PrinterNode::class, 'printer_node_id');
    }

    /**
     * Scope to filter pending jobs.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter by printer node.
     */
    public function scopeForNode(Builder $query, int $nodeId): Builder
    {
        return $query->where('printer_node_id', $nodeId);
    }

    /**
     * Scope to retrieve deliverable jobs for the desktop agent.
     * Includes 'pending' jobs and automatically rescues orphaned 'processing' jobs
     * that exceeded the timeout threshold without completion (e.g. power loss or crash).
     */
    public function scopeDeliverable(Builder $query, int $nodeId): Builder
    {
        $timeoutMinutes = (int) config('nyxo-printer.timeout_minutes', 3);
        $maxAttempts = (int) config('nyxo-printer.max_attempts', 3);
        $threshold = now()->subMinutes($timeoutMinutes);

        return $query->where('printer_node_id', $nodeId)
            ->where(function (Builder $q) use ($threshold, $maxAttempts) {
                $q->where('status', 'pending')
                    ->orWhere(function (Builder $stalled) use ($threshold, $maxAttempts) {
                        $stalled->where('status', 'processing')
                            ->where('updated_at', '<', $threshold)
                            ->where('attempts', '<', $maxAttempts);
                    });
            });
    }

    /**
     * Determine which records are eligible for automatic pruning.
     */
    public function prunable(): Builder
    {
        $days = (int) config('nyxo-printer.prune_after_days', 7);

        return static::whereIn('status', ['printed', 'failed'])
            ->where('created_at', '<=', now()->subDays($days));
    }
}
