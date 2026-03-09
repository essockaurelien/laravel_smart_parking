<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAuthUserFromHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->header('X-User-Id');
        $role = $request->header('X-User-Role');
        $email = $request->header('X-User-Email');
        $name = $request->header('X-User-Name');

        if ($userId || $role || $email || $name) {
            $request->attributes->set('auth_user', [
                'id' => $userId,
                'role' => $role,
                'email' => $email,
                'name' => $name,
            ]);
        }

        return $next($request);
    }
}
