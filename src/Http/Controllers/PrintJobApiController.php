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
     * Endpoint de comprobación de salud / heartbeat (GET /ping).
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
     * Obtiene los trabajos pendientes para el nodo y los bloquea como 'processing' (GET /jobs).
     * Incluye recuperación de trabajos huérfanos mediante scopeDeliverable().
     */
    public function index(Request $request): JsonResponse
    {
        /** @var PrinterNode $node */
        $node = $request->attributes->get('printerNode');

        // Transacción atómica con lockForUpdate() para evitar duplicación ante peticiones simultáneas
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
     * Actualiza el estado del trabajo reportado por la utilidad local (POST /jobs/{id}/status).
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
                'message' => 'Trabajo no encontrado o no pertenece a este nodo de impresión.',
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

        // Disparamos eventos del ciclo de vida para que el resto de la aplicación reaccione
        if ($newStatus === 'printed') {
            event(new PrintJobPrinted($job));
        } elseif ($newStatus === 'failed') {
            event(new PrintJobFailed($job, $errorMessage));
        }

        return response()->json([
            'success' => true,
            'message' => 'Estado del trabajo actualizado correctamente.',
            'job_id' => $job->id,
            'new_status' => $job->status,
        ]);
    }
}
