<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/auth/me';

    public function test_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson($this->endpoint);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Usuário autenticado.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    public function test_fails_when_unauthenticated(): void
    {
        $response = $this->getJson($this->endpoint);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Não autenticado.',
            ]);
    }
}
