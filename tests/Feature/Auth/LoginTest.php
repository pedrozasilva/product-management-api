<?php

namespace Tests\Feature\Auth;

use App\Events\Auth\UserLoggedIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/auth/login';

    public function test_returns_user_and_tokens_with_valid_credentials(): void
    {
        Event::fake([UserLoggedIn::class]);

        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('secret1234'),
        ]);

        $response = $this->postJson($this->endpoint, [
            'email' => 'john@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'tokens' => ['access_token', 'refresh_token', 'token_type', 'expires_in'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'john@example.com',
                    ],
                    'tokens' => [
                        'token_type' => 'Bearer',
                    ],
                ],
            ]);

        Event::assertDispatched(UserLoggedIn::class);
    }

    public function test_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('secret1234'),
        ]);

        $response = $this->postJson($this->endpoint, [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Credenciais inválidas.',
            ]);
    }

    public function test_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson($this->endpoint, [
            'email' => 'nobody@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Credenciais inválidas.',
            ]);
    }

    public function test_fails_without_required_fields(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson($this->endpoint, [
            'email' => 'invalid',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
