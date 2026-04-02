<?php

namespace Tests\Feature\Auth;

use App\Events\Auth\UserRegistered;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/auth/register';

    public function test_creates_user_and_returns_tokens(): void
    {
        Event::fake([UserRegistered::class]);

        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ];

        $response = $this->postJson($this->endpoint, $payload);

        $response->assertStatus(201)
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
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                    ],
                    'tokens' => [
                        'token_type' => 'Bearer',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseCount('refresh_tokens', 1);

        Event::assertDispatched(UserRegistered::class);
    }

    public function test_fails_without_required_fields(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $payload = [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ];

        $response = $this->postJson($this->endpoint, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_fails_with_short_password(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];

        $response = $this->postJson($this->endpoint, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_fails_when_password_confirmation_does_not_match(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'different',
        ];

        $response = $this->postJson($this->endpoint, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_fails_with_invalid_email(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ];

        $response = $this->postJson($this->endpoint, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
