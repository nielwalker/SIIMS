<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the origin from the request
        $origin = $request->headers->get('Origin');
        
        // Define allowed origins
        $allowedOrigins = [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            env('FRONTEND_URL', 'http://localhost:3000'),
        ];
        
        // Determine the origin to allow (use request origin if allowed, otherwise use first allowed)
        $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : $allowedOrigins[0];
        
        // Handle preflight OPTIONS request immediately
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204, [
                'Access-Control-Allow-Origin' => $allowedOrigin,
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Origin',
                'Access-Control-Allow-Credentials' => 'true', // Must be 'true' when frontend sends credentials
                'Access-Control-Max-Age' => '86400',
            ]);
        }

        $response = $next($request);

        // Ensure response is a Response object
        if (!$response instanceof Response) {
            $response = response($response);
        }

        // Add CORS headers to all responses - use setHeaders method for better compatibility
        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true'); // Must be 'true' when frontend sends credentials
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}

