<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowProductTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_returns_product_by_id(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Produto Específico']);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}/{$product->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'stock_quantity',
                    'is_active',
                    'category' => ['id', 'name'],
                    'user' => ['id', 'name'],
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Produto encontrado com sucesso.',
                'data' => [
                    'id' => $product->id,
                    'name' => 'Produto Específico',
                ],
            ]);
    }

    public function test_fails_with_nonexistent_product(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}/99999");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Produto não encontrado.',
            ]);
    }

    public function test_fails_when_unauthenticated(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("{$this->endpoint}/{$product->id}");

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Não autenticado.',
            ]);
    }
}
