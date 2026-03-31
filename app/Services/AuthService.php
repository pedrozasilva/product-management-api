<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function register(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
    }

    public function attemptLogin(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function createTokenPair(User $user, string $device = 'default'): array
    {
        $accessToken = $user->createToken($device);

        $refreshToken = $this->createRefreshToken(
            $user,
            $accessToken->accessToken->id,
        );

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') * 60,
        ];
    }

    public function refreshTokens(string $token): ?array
    {
        $refreshToken = RefreshToken::where('token', hash('sha256', $token))
            ->first();

        if (! $refreshToken || ! $refreshToken->isValid()) {
            return null;
        }

        $user = $refreshToken->user;

        $refreshToken->revoke();

        $user->tokens()
            ->where('id', $refreshToken->access_token_id)
            ->delete();

        return $this->createTokenPair($user, 'refreshed');
    }

    public function logout(User $user): void
    {
        RefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $user->tokens()->delete();
    }

    public function logoutCurrentDevice(User $user): void
    {
        $currentTokenId = $user->currentAccessToken()->id;

        RefreshToken::where('user_id', $user->id)
            ->where('access_token_id', $currentTokenId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $user->currentAccessToken()->delete();
    }

    private function createRefreshToken(User $user, int $accessTokenId): string
    {
        $plainToken = Str::random(64);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'access_token_id' => $accessTokenId,
            'expires_at' => now()->addDays((int) config('auth.refresh_token.expiration_days', 7)),
        ]);

        return $plainToken;
    }
}
