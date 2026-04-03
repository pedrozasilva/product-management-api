<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockFilterTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_filters_products_in_stock(): void
    {
        $user = User::factory()->create();

        Product::factory()->count(2)->create(['stock_quantity' => 10]);
        Product::factory()->create(['stock_quantity' => 0]);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?in_stock=1");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['pagination' => ['total' => 2]]);
    }

    public function test_filters_products_out_of_stock(): void
    {
        $user = User::factory()->create();

        Product::factory()->count(2)->create(['stock_quantity' => 10]);
        Product::factory()->count(3)->create(['stock_quantity' => 0]);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?in_stock=0");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJson(['pagination' => ['total' => 3]]);
    }
}
