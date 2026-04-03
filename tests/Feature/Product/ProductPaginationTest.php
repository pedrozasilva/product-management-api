<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPaginationTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_returns_pagination_structure(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(5)->create();

        $response = $this->actingAs($user)->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson([
                'pagination' => [
                    'current_page' => 1,
                    'total' => 5,
                ],
            ]);
    }

    public function test_paginates_with_default_per_page(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(25)->create();

        $response = $this->actingAs($user)->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJson([
                'pagination' => [
                    'per_page' => 20,
                    'total' => 25,
                    'last_page' => 2,
                ],
            ]);
    }

    public function test_paginates_with_custom_per_page(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(10)->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?per_page=5");

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJson([
                'pagination' => [
                    'per_page' => 5,
                    'total' => 10,
                    'last_page' => 2,
                ],
            ]);
    }

    public function test_caps_per_page_at_100(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?per_page=200");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Erro de validação.',
            ])
            ->assertJsonValidationErrors('per_page');
    }

    public function test_navigates_to_specific_page(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(10)->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?per_page=3&page=2");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJson([
                'pagination' => [
                    'current_page' => 2,
                    'per_page' => 3,
                    'total' => 10,
                ],
            ]);
    }

    public function test_returns_empty_data_when_page_exceeds_last_page(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?page=99");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
