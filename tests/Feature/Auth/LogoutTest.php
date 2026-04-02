<?php

namespace Tests\Feature\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/auth/logout';

    private function loginAndGetTokens(User $user): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        return $response->json('data.tokens');
    }

    public function test_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $tokens = $this->loginAndGetTokens($user);

        $response = $this->withHeader('Authorization', "Bearer {$tokens['access_token']}")
            ->postJson($this->endpoint);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logout realizado com sucesso.',
            ]);

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$tokens['access_token']}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_revokes_associated_refresh_token(): void
    {
        $user = User::factory()->create();
        $tokens = $this->loginAndGetTokens($user);

        $this->withHeader('Authorization', "Bearer {$tokens['access_token']}")
            ->postJson($this->endpoint)
            ->assertOk();

        $dbToken = RefreshToken::where('token', hash('sha256', $tokens['refresh_token']))->first();
        $this->assertNotNull($dbToken->revoked_at);
    }

    public function test_does_not_revoke_other_device_tokens(): void
    {
        $user = User::factory()->create();
        $firstTokens = $this->loginAndGetTokens($user);
        $secondTokens = $this->loginAndGetTokens($user);

        $this->withHeader('Authorization', "Bearer {$firstTokens['access_token']}")
            ->postJson($this->endpoint)
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$secondTokens['access_token']}")
            ->getJson('/api/auth/me')
            ->assertOk();
    }

    public function test_fails_when_unauthenticated(): void
    {
        $response = $this->postJson($this->endpoint);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Não autenticado.',
            ]);
    }
}
