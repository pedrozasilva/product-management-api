<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/auth';

    public function test_full_auth_lifecycle(): void
    {
        $registerResponse = $this->postJson("{$this->baseUrl}/register", [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $registerResponse->assertStatus(201);
        $accessToken = $registerResponse->json('data.tokens.access_token');
        $refreshToken = $registerResponse->json('data.tokens.refresh_token');

        $this->withHeader('Authorization', "Bearer {$accessToken}")
            ->getJson("{$this->baseUrl}/me")
            ->assertOk()
            ->assertJsonPath('data.email', 'jane@example.com');

        $refreshResponse = $this->postJson("{$this->baseUrl}/refresh", [
            'refresh_token' => $refreshToken,
        ]);

        $refreshResponse->assertOk();
        $newAccessToken = $refreshResponse->json('data.tokens.access_token');

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$newAccessToken}")
            ->getJson("{$this->baseUrl}/me")
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$accessToken}")
            ->getJson("{$this->baseUrl}/me")
            ->assertStatus(401);

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$newAccessToken}")
            ->postJson("{$this->baseUrl}/logout")
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$newAccessToken}")
            ->getJson("{$this->baseUrl}/me")
            ->assertStatus(401);
    }
}
