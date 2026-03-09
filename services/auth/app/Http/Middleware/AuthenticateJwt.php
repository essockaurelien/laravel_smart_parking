<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\JwtToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            return response()->json(['message' => 'Missing token'], 401);
        }

        try {
            $payload = JwtToken::decode($token);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if (!empty($payload->jti) && $this->isRevoked($payload->jti)) {
            return response()->json(['message' => 'Token revoked'], 401);
        }

        $userId = $payload->sub ?? null;
        $user = $userId ? User::find($userId) : null;
        if ($user === null) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $request->setUserResolver(static fn () => $user);
        $request->attributes->set('jwt_payload', $payload);

        return $next($request);
    }

    private function isRevoked(string $jti): bool
    {
        return DB::table('jwt_revoked_tokens')
            ->where('jti', $jti)
            ->where('expires_at', '>', now())
            ->exists();
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token !== '' ? $token : null;
    }
}
