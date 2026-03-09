<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InternalToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Internal-Token');
        $expected = config('app.internal_token');

        if (!$expected || $token !== $expected) {
            return response()->json(['message' => 'Invalid internal token'], 401);
        }

        return $next($request);
    }
}
