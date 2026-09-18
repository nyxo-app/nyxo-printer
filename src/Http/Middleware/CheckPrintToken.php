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
     * Valida el token del Agente de Impresión Local y vincula el nodo resuelto al request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Extraer el token de múltiples fuentes posibles (cabeceras estándar, Apache/FastCGI, query params)
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
                'message' => 'Token de impresión no proporcionado.',
            ], 401);
        }

        // 2. Buscar directamente por token o auto-decodificar Código de Enlace Base64 (formato: "url|token")
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
                'message' => 'Token de impresión inválido o nodo inexistente / inactivo.',
            ], 401);
        }

        // 3. Vincular el nodo resuelto al request para acceso directo en los controladores
        $request->attributes->set('printerNode', $node);
        $request->attributes->set('printer_node_id', $node->id);

        // 4. Actualizar la marca de tiempo de conexión activa sin disparar observadores pesados
        $node->updateQuietly(['last_ping_at' => now()]);

        return $next($request);
    }
}
