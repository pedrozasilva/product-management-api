<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_filters_products_by_category_id(): void
    {
        $user = User::factory()->create();
        $electronics = Category::factory()->create(['name' => 'Eletrônicos']);
        $furniture = Category::factory()->create(['name' => 'Móveis']);

        Product::factory()->count(3)->create(['category_id' => $electronics->id]);
        Product::factory()->count(2)->create(['category_id' => $furniture->id]);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?category_id={$electronics->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJson(['pagination' => ['total' => 3]]);
    }

    public function test_filter_by_category_returns_empty_when_no_products_in_category(): void
    {
        $user = User::factory()->create();
        $emptyCategory = Category::factory()->create();
        Product::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?category_id={$emptyCategory->id}");

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJson(['pagination' => ['total' => 0]]);
    }
}
