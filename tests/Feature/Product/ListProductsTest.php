<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListProductsTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_returns_all_products(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson($this->endpoint);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Produtos listados com sucesso.',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_returns_empty_list_when_no_products(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson($this->endpoint);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Produtos listados com sucesso.',
            ])
            ->assertJsonCount(0, 'data');
    }

    public function test_returns_products_with_category_and_user(): void
    {
        $user = User::factory()->create();
        Product::factory()->create();

        $response = $this->actingAs($user)->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
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
                ],
            ]);
    }

    public function test_fails_when_unauthenticated(): void
    {
        $response = $this->getJson($this->endpoint);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Não autenticado.',
            ]);
    }

    public function test_returns_products_ordered_by_latest(): void
    {
        $user = User::factory()->create();

        $oldest = Product::factory()->create([
            'name' => 'Produto Antigo',
            'created_at' => now()->subDays(2),
        ]);
        $newest = Product::factory()->create([
            'name' => 'Produto Recente',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson($this->endpoint);

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals($newest->id, $data[0]['id']);
        $this->assertEquals($oldest->id, $data[1]['id']);
    }
}
