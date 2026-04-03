<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductNameFilterTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_filters_products_by_name(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Notebook Dell']);
        Product::factory()->create(['name' => 'Mouse Logitech']);
        Product::factory()->create(['name' => 'Notebook Lenovo']);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?name=Notebook");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'pagination' => ['total' => 2],
            ]);
    }

    public function test_filters_products_by_partial_name(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Teclado Mecânico RGB']);
        Product::factory()->create(['name' => 'Monitor Ultrawide']);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?name=Mecânico");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_name_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Notebook Dell']);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?name=notebook");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_name_returns_empty_when_no_match(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?name=ProdutoInexistente999");

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJson([
                'pagination' => ['total' => 0],
            ]);
    }

    public function test_filter_by_name_trims_whitespace(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Cadeira Gamer']);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?name=+Cadeira+");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_combines_name_filter_with_pagination(): void
    {
        $user = User::factory()->create();

        Product::factory()->count(5)->create(['name' => 'Mouse Gamer']);
        Product::factory()->count(3)->create(['name' => 'Teclado Mecânico']);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?name=Mouse&per_page=2&page=1");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'pagination' => [
                    'total' => 5,
                    'per_page' => 2,
                    'current_page' => 1,
                    'last_page' => 3,
                ],
            ]);
    }
}
