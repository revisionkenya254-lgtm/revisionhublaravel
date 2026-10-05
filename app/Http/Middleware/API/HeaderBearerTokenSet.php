<?php

namespace App\Http\Middleware\API;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HeaderBearerTokenSet {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        $token = $request->bearer_token ?? request()->bearerToken();
        if ($token) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
            return $next($request);
        }
        return response()->json(['status' => 'error', 'message' => 'UnAuthenticated'], 401);
    }
}