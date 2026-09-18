<?php

declare(strict_types=1);

namespace Nyxo\Printer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para compatibilidad con versiones previas.
 * Se recomienda utilizar directamente CheckPrintToken.
 */
class EnsureTenantByPrintToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $middleware = new CheckPrintToken;

        return $middleware->handle($request, $next);
    }
}
