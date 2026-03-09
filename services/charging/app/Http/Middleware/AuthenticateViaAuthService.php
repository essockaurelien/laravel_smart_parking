<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthenticateViaAuthService
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Missing bearer token'], 401);
        }

        $baseUrl = rtrim(config('auth-service.base_url'), '/');
        $response = Http::withToken($token)->get("{$baseUrl}/api/me");
        if (!$response->ok()) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $request->attributes->set('auth_user', $response->json());

        return $next($request);
    }
}
