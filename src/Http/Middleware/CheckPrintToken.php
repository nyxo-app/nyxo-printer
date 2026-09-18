<?php

declare(strict_types=1);

namespace Nyxo\Printer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nyxo\Printer\Models\PrinterNode;
use Symfony\Component\HttpFoundation\Response;

class CheckPrintToken
{
    /**
     * Validate the local printing agent token and bind the resolved node to the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Extract token from multiple possible sources (standard headers, query params, auth bearer)
        $token = $request->header('X-Tenant-Token')
            ?? $request->header('X-Print-Token')
            ?? $request->header('Print-Token')
            ?? $request->header('Token')
            ?? $request->bearerToken()
            ?? $request->header('Authorization')
            ?? $request->input('token')
            ?? $request->input('print_token')
            ?? $request->query('token')
            ?? $request->query('print_token')
            ?? $request->server('HTTP_AUTHORIZATION')
            ?? $request->server('REDIRECT_HTTP_AUTHORIZATION');

        if ($token && str_starts_with((string) $token, 'Bearer ')) {
            $token = substr((string) $token, 7);
        }

        $token = trim((string) $token);

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Print token not provided.',
            ], 401);
        }

        // 2. Query directly by token or auto-decode Base64 pairing string (format: "url|token")
        $node = PrinterNode::where('print_token', $token)->first();

        if (! $node) {
            $decoded = base64_decode($token, true);
            if ($decoded && str_contains($decoded, '|')) {
                $parts = explode('|', $decoded);
                $extractedToken = trim((string) end($parts));
                if ($extractedToken) {
                    $node = PrinterNode::where('print_token', $extractedToken)->first();
                }
            }
        }

        if (! $node || ! $node->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid print token or inactive printer node.',
            ], 401);
        }

        // 3. Bind resolved node to request attributes for controller access
        $request->attributes->set('printerNode', $node);
        $request->attributes->set('printer_node_id', $node->id);

        // 4. Touch heartbeat timestamp quietly
        $node->updateQuietly(['last_ping_at' => now()]);

        return $next($request);
    }
}
