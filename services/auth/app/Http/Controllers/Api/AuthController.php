<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\JwtToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['sometimes', 'in:base,premium,admin'],
            'premium_until' => ['sometimes', 'date'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'base',
            'premium_until' => $data['premium_until'] ?? null,
        ]);

        $tokens = $this->issueTokens($user);
        unset($tokens['refresh_token_id']);

        return response()->json(array_merge([
            'user' => $user,
        ], $tokens), 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $tokens = $this->issueTokens($user);
        unset($tokens['refresh_token_id']);

        return response()->json(array_merge([
            'user' => $user,
        ], $tokens));
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function refresh(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $record = $this->findRefreshToken($data['refresh_token']);
        if (!$record || $record->revoked_at || $record->expires_at < now()) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $user = User::find($record->user_id);
        if (!$user) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        DB::table('jwt_refresh_tokens')
            ->where('id', $record->id)
            ->update(['revoked_at' => now()]);

        $tokens = $this->issueTokens($user);

        if (isset($tokens['refresh_token_id'])) {
            DB::table('jwt_refresh_tokens')
                ->where('id', $record->id)
                ->update(['replaced_by_id' => $tokens['refresh_token_id']]);
        }

        unset($tokens['refresh_token_id']);

        return response()->json($tokens);
    }

    public function logout(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => ['sometimes', 'string'],
        ]);

        if (!empty($data['refresh_token'])) {
            $record = $this->findRefreshToken($data['refresh_token']);
            if ($record && !$record->revoked_at) {
                DB::table('jwt_refresh_tokens')
                    ->where('id', $record->id)
                    ->update(['revoked_at' => now()]);
            }
        }

        $token = $request->bearerToken();
        if ($token) {
            $this->revokeAccessToken($token);
        }

        return response()->json(['status' => 'ok']);
    }

    public function authorizeToken(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Missing token'], 403);
        }

        try {
            $payload = JwtToken::decode($token);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid token'], 403);
        }

        if (!empty($payload->jti) && $this->isRevoked($payload->jti)) {
            return response()->json(['message' => 'Token revoked'], 403);
        }

        return response()->json(['status' => 'ok']);
    }

    private function issueTokens(User $user): array
    {
        $jti = (string) Str::uuid();
        $access = JwtToken::issue($user, $jti);
        $refresh = Str::random(64);

        $refreshId = $this->storeRefreshToken($user->id, $refresh);

        return [
            'access_token' => $access,
            'refresh_token' => $refresh,
            'token_type' => 'Bearer',
            'expires_in' => JwtToken::ttlSeconds(),
            'refresh_expires_in' => JwtToken::refreshTtlDays() * 86400,
            'refresh_token_id' => $refreshId,
        ];
    }

    private function storeRefreshToken(int $userId, string $refreshToken): int
    {
        $hash = hash('sha256', $refreshToken);
        $expiresAt = now()->addDays(JwtToken::refreshTtlDays());

        return (int) DB::table('jwt_refresh_tokens')->insertGetId([
            'user_id' => $userId,
            'token_hash' => $hash,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function findRefreshToken(string $refreshToken): ?object
    {
        $hash = hash('sha256', $refreshToken);

        return DB::table('jwt_refresh_tokens')->where('token_hash', $hash)->first();
    }

    private function revokeAccessToken(string $token): void
    {
        try {
            $payload = JwtToken::decode($token);
        } catch (\Throwable $e) {
            return;
        }

        $jti = $payload->jti ?? null;
        $exp = $payload->exp ?? null;
        $sub = $payload->sub ?? null;
        if (!$jti || !$exp || !$sub) {
            return;
        }

        DB::table('jwt_revoked_tokens')->updateOrInsert(
            ['jti' => $jti],
            [
                'user_id' => (int) $sub,
                'expires_at' => date('Y-m-d H:i:s', (int) $exp),
                'revoked_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function isRevoked(string $jti): bool
    {
        return DB::table('jwt_revoked_tokens')
            ->where('jti', $jti)
            ->where('expires_at', '>', now())
            ->exists();
    }
}
