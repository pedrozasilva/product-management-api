<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceFilterTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_filters_products_by_price_min(): void
    {
        $user = User::factory()->create();

        Product::factory()->create(['price' => 50.00]);
        Product::factory()->create(['price' => 150.00]);
        Product::factory()->create(['price' => 300.00]);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?price_min=100");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['pagination' => ['total' => 2]]);
    }

    public function test_filters_products_by_price_max(): void
    {
        $user = User::factory()->create();

        Product::factory()->create(['price' => 50.00]);
        Product::factory()->create(['price' => 150.00]);
        Product::factory()->create(['price' => 300.00]);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?price_max=150");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['pagination' => ['total' => 2]]);
    }

    public function test_filters_products_by_price_range(): void
    {
        $user = User::factory()->create();

        Product::factory()->create(['price' => 30.00]);
        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 200.00]);
        Product::factory()->create(['price' => 500.00]);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?price_min=50&price_max=250");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['pagination' => ['total' => 2]]);
    }

    public function test_price_range_includes_boundary_values(): void
    {
        $user = User::factory()->create();

        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 200.00]);
        Product::factory()->create(['price' => 300.00]);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?price_min=100&price_max=300");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }
}
