<?php

namespace Tests\Feature\Product;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_validation_fails_with_invalid_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?page=0");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Erro de validação.',
            ])
            ->assertJsonValidationErrors('page');
    }

    public function test_validation_fails_with_negative_per_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?per_page=-1");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Erro de validação.',
            ])
            ->assertJsonValidationErrors('per_page');
    }

    public function test_validation_fails_with_non_integer_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?page=abc");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('page');
    }

    public function test_validation_fails_with_name_exceeding_max_length(): void
    {
        $user = User::factory()->create();
        $longName = str_repeat('a', 256);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?name={$longName}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_validation_fails_with_page_exceeding_max(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?page=101");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('page');
    }

    public function test_validation_fails_with_nonexistent_category_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?category_id=99999");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    public function test_validation_fails_with_non_integer_category_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?category_id=abc");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    public function test_validation_fails_when_price_min_is_negative(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?price_min=-10");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('price_min');
    }

    public function test_validation_fails_when_price_max_is_less_than_price_min(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?price_min=200&price_max=100");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('price_max');
    }

    public function test_validation_fails_when_price_min_is_not_numeric(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?price_min=abc");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('price_min');
    }
}
