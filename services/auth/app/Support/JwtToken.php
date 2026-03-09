<?php

namespace App\Support;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class JwtToken
{
    public static function issue(User $user, ?string $jti = null): string
    {
        $now = time();
        $ttlSeconds = static::ttlSeconds();
        $tokenId = $jti ?: (string) Str::uuid();

        $payload = [
            'iss' => static::issuer(),
            'aud' => static::audience(),
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
            'jti' => $tokenId,
            'sub' => (string) $user->id,
            'email' => $user->email,
            'role' => $user->role ?? 'base',
            'name' => $user->name,
        ];

        return JWT::encode($payload, static::privateKey(), 'RS256', static::keyId());
    }

    public static function decode(string $token): object
    {
        return JWT::decode($token, new Key(static::publicKey(), 'RS256'));
    }

    public static function ttlSeconds(): int
    {
        $minutes = (int) env('JWT_TTL_MINUTES', 60);

        return max(5, $minutes) * 60;
    }

    public static function refreshTtlDays(): int
    {
        $days = (int) env('JWT_REFRESH_TTL_DAYS', 30);

        return max(1, $days);
    }

    public static function issuer(): string
    {
        return (string) env('JWT_ISSUER', 'spark-auth');
    }

    public static function audience(): string
    {
        return (string) env('JWT_AUDIENCE', 'spark-api');
    }

    public static function keyId(): string
    {
        return (string) env('JWT_KID', 'spark-dev');
    }

    private static function privateKey(): string
    {
        $path = env('JWT_PRIVATE_KEY_PATH');
        if (is_string($path) && $path !== '' && is_file($path)) {
            return (string) file_get_contents($path);
        }

        return (string) env('JWT_PRIVATE_KEY', '');
    }

    private static function publicKey(): string
    {
        $path = env('JWT_PUBLIC_KEY_PATH');
        if (is_string($path) && $path !== '' && is_file($path)) {
            return (string) file_get_contents($path);
        }

        return (string) env('JWT_PUBLIC_KEY', '');
    }
}
