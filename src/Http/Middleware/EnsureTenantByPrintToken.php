<?php

declare(strict_types=1);

namespace Nyxo\Printer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backward compatibility middleware alias.
 * Using CheckPrintToken directly is recommended.
 */
class EnsureTenantByPrintToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $middleware = new CheckPrintToken;

        return $middleware->handle($request, $next);
    }
}
