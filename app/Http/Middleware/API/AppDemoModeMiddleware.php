<?php

namespace App\Http\Middleware\API;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppDemoModeMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        if (strtoupper(env('APP_MODE')) !== 'LIVE') {
            $allowedRoutes = [
                'api.register', 'api.login', 'api.verify-otp', 'api.resend-otp',
                'api.forget-password', 'api.reset-password', 'api.logout', 'api.logoutAllApp'
            ];
            if (request()->routeIs(...$allowedRoutes) || request()->method() == 'GET') {
                return $next($request);
            } else {
                return response()->json(['status' => 'error', 'message' => 'In Demo Mode You Can Not Perform This Action'], 403);
            }
        }
        return $next($request);
    }
}
