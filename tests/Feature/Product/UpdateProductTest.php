<?php

namespace Tests\Feature\Product;

use App\Events\Product\ProductUpdated;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateProductTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_updates_product_and_returns_data(): void
    {
        Event::fake([ProductUpdated::class]);

        $user = User::factory()->create();
        $product = Product::factory()->create();
        $newCategory = Category::factory()->create();

        $payload = [
            'name' => 'Produto Atualizado',
            'price' => 199.90,
            'category_id' => $newCategory->id,
        ];

        $response = $this->actingAs($user)->putJson("{$this->endpoint}/{$product->id}", $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Produto atualizado com sucesso.',
                'data' => [
                    'id' => $product->id,
                    'name' => 'Produto Atualizado',
                    'price' => '199.90',
                    'category' => [
                        'id' => $newCategory->id,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto Atualizado',
            'price' => 199.90,
        ]);
    }

    public function test_dispatches_product_updated_event(): void
    {
        Event::fake([ProductUpdated::class]);

        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->putJson("{$this->endpoint}/{$product->id}", ['name' => 'Novo Nome'])
            ->assertOk();

        Event::assertDispatched(ProductUpdated::class);
    }

    public function test_allows_partial_update(): void
    {
        Event::fake([ProductUpdated::class]);

        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Original', 'stock_quantity' => 10]);

        $response = $this->actingAs($user)
            ->putJson("{$this->endpoint}/{$product->id}", ['stock_quantity' => 50]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Original')
            ->assertJsonPath('data.stock_quantity', 50);
    }

    public function test_fails_with_nonexistent_product(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("{$this->endpoint}/99999", ['name' => 'Teste']);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Produto não encontrado.',
            ]);
    }

    public function test_fails_with_invalid_category_id(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("{$this->endpoint}/{$product->id}", ['category_id' => 99999]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_fails_with_negative_price(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("{$this->endpoint}/{$product->id}", ['price' => -10]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_fails_when_unauthenticated(): void
    {
        $product = Product::factory()->create();

        $response = $this->putJson("{$this->endpoint}/{$product->id}", ['name' => 'Teste']);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Não autenticado.',
            ]);
    }
}
