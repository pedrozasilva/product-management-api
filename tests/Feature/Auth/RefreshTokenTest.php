<?php

namespace Tests\Feature\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/auth/refresh';

    private function loginAndGetTokens(User $user): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        return $response->json('data.tokens');
    }

    public function test_returns_new_token_pair(): void
    {
        $user = User::factory()->create();
        $tokens = $this->loginAndGetTokens($user);

        $response = $this->postJson($this->endpoint, [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'tokens' => ['access_token', 'refresh_token', 'token_type', 'expires_in'],
                ],
            ])
            ->assertJson(['success' => true]);

        $newTokens = $response->json('data.tokens');

        $this->assertNotEquals($tokens['access_token'], $newTokens['access_token']);
        $this->assertNotEquals($tokens['refresh_token'], $newTokens['refresh_token']);
    }

    public function test_revokes_old_refresh_token(): void
    {
        $user = User::factory()->create();
        $tokens = $this->loginAndGetTokens($user);

        $this->postJson($this->endpoint, [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk();

        $oldToken = RefreshToken::where('token', hash('sha256', $tokens['refresh_token']))->first();
        $this->assertNotNull($oldToken->revoked_at);
    }

    public function test_fails_with_already_used_token(): void
    {
        $user = User::factory()->create();
        $tokens = $this->loginAndGetTokens($user);

        $this->postJson($this->endpoint, [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk();

        $response = $this->postJson($this->endpoint, [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Refresh token inválido ou expirado.',
            ]);
    }

    public function test_fails_with_invalid_token(): void
    {
        $response = $this->postJson($this->endpoint, [
            'refresh_token' => 'completely-invalid-token',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Refresh token inválido ou expirado.',
            ]);
    }

    public function test_fails_with_expired_token(): void
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('test');

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', 'expired-token'),
            'access_token_id' => $accessToken->accessToken->id,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson($this->endpoint, [
            'refresh_token' => 'expired-token',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Refresh token inválido ou expirado.',
            ]);
    }

    public function test_fails_without_required_field(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['refresh_token']);
    }
}
