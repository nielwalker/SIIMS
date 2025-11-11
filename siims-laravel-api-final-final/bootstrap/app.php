<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add CORS middleware FIRST to handle preflight requests
        $middleware->api(prepend: [
            \App\Http\Middleware\HandleCors::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            // Disable CSRF for API routes to avoid 419 on same-origin cookies
            \App\Http\Middleware\DisableCsrfForApi::class,
        ]);

        $middleware->statefulApi();
        
        // Enable CORS for all API routes
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\CheckUserRole::class
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Add CORS headers to exception responses
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                $origin = $request->headers->get('Origin');
                $allowedOrigins = ['http://localhost:3000', 'http://127.0.0.1:3000', env('FRONTEND_URL', 'http://localhost:3000')];
                $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : $allowedOrigins[0];
                
                $response = response()->json([
                    'message' => $e->getMessage(),
                    'error' => config('app.debug') ? $e->getTraceAsString() : 'Server Error'
                ], 500);
                
                $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Max-Age', '86400');
                
                return $response;
            }
        });
    })->create();
