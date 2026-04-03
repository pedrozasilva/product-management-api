<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCombinedFiltersTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_combines_category_and_price_range_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Product::factory()->create(['category_id' => $category->id, 'price' => 50.00]);
        Product::factory()->create(['category_id' => $category->id, 'price' => 150.00]);
        Product::factory()->create(['category_id' => $category->id, 'price' => 500.00]);
        Product::factory()->create(['price' => 150.00]);

        $response = $this->actingAs($user)->getJson(
            "{$this->endpoint}?category_id={$category->id}&price_min=100&price_max=200"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['pagination' => ['total' => 1]]);
    }

    public function test_combines_all_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Product::factory()->create([
            'name' => 'Notebook Gamer',
            'category_id' => $category->id,
            'price' => 5000.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Notebook Básico',
            'category_id' => $category->id,
            'price' => 2000.00,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Notebook Empresarial',
            'category_id' => $category->id,
            'price' => 4000.00,
            'stock_quantity' => 5,
            'is_active' => false,
        ]);

        Product::factory()->create([
            'name' => 'Mouse Gamer',
            'price' => 200.00,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->getJson(
            "{$this->endpoint}?name=Notebook&category_id={$category->id}&price_min=3000&price_max=6000&in_stock=1&is_active=1"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['pagination' => ['total' => 1]]);

        $this->assertEquals('Notebook Gamer', $response->json('data.0.name'));
    }

    public function test_combines_in_stock_and_category_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Product::factory()->create(['category_id' => $category->id, 'stock_quantity' => 10]);
        Product::factory()->create(['category_id' => $category->id, 'stock_quantity' => 0]);
        Product::factory()->create(['stock_quantity' => 10]);

        $response = $this->actingAs($user)->getJson(
            "{$this->endpoint}?category_id={$category->id}&in_stock=1"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['pagination' => ['total' => 1]]);
    }

    public function test_combines_filters_with_pagination(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
        Product::factory()->count(3)->create(['price' => 100.00, 'is_active' => false]);

        $response = $this->actingAs($user)->getJson(
            "{$this->endpoint}?category_id={$category->id}&is_active=1&per_page=2&page=2"
        );

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'pagination' => [
                    'total' => 5,
                    'per_page' => 2,
                    'current_page' => 2,
                    'last_page' => 3,
                ],
            ]);
    }
}
