<?php

declare(strict_types=1);

namespace Nyxo\Printer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Nyxo\Printer\Events\PrintJobFailed;
use Nyxo\Printer\Events\PrintJobPrinted;
use Nyxo\Printer\Models\PrinterNode;
use Nyxo\Printer\Models\PrintJob;

class PrintJobApiController extends Controller
{
    /**
     * Heartbeat / Health check endpoint for the desktop agent (GET /ping).
     */
    public function ping(Request $request): JsonResponse
    {
        /** @var PrinterNode $node */
        $node = $request->attributes->get('printerNode');

        $node->updateQuietly(['last_ping_at' => now()]);

        $tenantColumn = config('nyxo-printer.tenant_column', 'empresa_id');
        $tenantId = $tenantColumn ? ($node->getAttribute($tenantColumn) ?? 1) : 1;

        return response()->json([
            'success' => true,
            'message' => 'Pong',
            'node' => [
                'id' => $node->id,
                'name' => $node->name,
                'tenant_id' => $tenantId,
            ],
            // Backward-compatible keys for legacy desktop agents
            'tenant' => [
                'id' => $tenantId,
                'nombre' => $node->name,
            ],
            'punto_venta' => [
                'id' => $node->id,
                'nombre' => $node->name,
                'numero' => $node->id,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Retrieve pending jobs for the node and atomically lock them as 'processing' (GET /jobs).
     * Includes automated orphan job rescue via scopeDeliverable().
     */
    public function index(Request $request): JsonResponse
    {
        /** @var PrinterNode $node */
        $node = $request->attributes->get('printerNode');

        // Atomic transaction with lockForUpdate() to prevent duplicates under concurrent requests
        $jobs = DB::transaction(function () use ($node) {
            $pendingJobs = PrintJob::deliverable($node->id)
                ->lockForUpdate()
                ->get();

            if ($pendingJobs->isEmpty()) {
                return collect([]);
            }

            PrintJob::whereIn('id', $pendingJobs->pluck('id'))
                ->update([
                    'status' => 'processing',
                    'attempts' => DB::raw('attempts + 1'),
                ]);

            return $pendingJobs;
        });

        $formattedJobs = $jobs->map(function (PrintJob $job) {
            $content = $job->content;
            if ($job->content_type === 'json_structured') {
                $content = json_decode($content, true);
            }

            return [
                'id' => $job->id,
                'format' => $job->format,
                'content_type' => $job->content_type,
                'content' => $content,
                'attempts' => $job->attempts + 1,
                'created_at' => $job->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $formattedJobs->count(),
            'jobs' => $formattedJobs,
        ]);
    }

    /**
     * Update job status reported by the desktop agent (POST /jobs/{id}/status).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        /** @var PrinterNode $node */
        $node = $request->attributes->get('printerNode');

        $job = PrintJob::where('id', $id)
            ->where('printer_node_id', $node->id)
            ->first();

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Print job not found or does not belong to this printer node.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:printed,failed,pending',
            'error_message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $newStatus = (string) $request->input('status');
        $errorMessage = $newStatus === 'failed' ? (string) $request->input('error_message') : null;

        $job->update([
            'status' => $newStatus,
            'error_message' => $errorMessage,
        ]);

        // Dispatch domain lifecycle events
        if ($newStatus === 'printed') {
            event(new PrintJobPrinted($job));
        } elseif ($newStatus === 'failed') {
            event(new PrintJobFailed($job, $errorMessage));
        }

        return response()->json([
            'success' => true,
            'message' => 'Print job status updated successfully.',
            'job_id' => $job->id,
            'new_status' => $job->status,
        ]);
    }
}
