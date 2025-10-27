<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * This middleware bypasses CSRF verification for API routes.
 * Attach it only to the API group to avoid disabling CSRF on web forms.
 */
class DisableCsrfForApi
{
    public function handle(Request $request, Closure $next)
    {
        // Laravel's VerifyCsrfToken runs in the web middleware stack.
        // Our API routes are already under api middleware; if any web-based
        // CSRF check is enforced, we can simply continue without modifying the token.
        // This middleware exists as a no-op placeholder to signal intent.
        return $next($request);
    }
}


