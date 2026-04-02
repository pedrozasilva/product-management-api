<?php

namespace Tests\Feature\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutAllTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/auth/logout-all';

    private function loginAndGetTokens(User $user): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        return $response->json('data.tokens');
    }

    public function test_revokes_every_token(): void
    {
        $user = User::factory()->create();
        $firstTokens = $this->loginAndGetTokens($user);
        $secondTokens = $this->loginAndGetTokens($user);

        $this->withHeader('Authorization', "Bearer {$firstTokens['access_token']}")
            ->postJson($this->endpoint)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logout realizado em todos os dispositivos.',
            ]);

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$secondTokens['access_token']}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);

        $this->assertEquals(0, $user->tokens()->count());

        $activeRefreshTokens = RefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->count();

        $this->assertEquals(0, $activeRefreshTokens);
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
