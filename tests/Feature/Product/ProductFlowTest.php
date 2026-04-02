<?php

namespace Tests\Feature\Product;

use App\Events\Product\ProductCreated;
use App\Events\Product\ProductDeleted;
use App\Events\Product\ProductUpdated;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProductFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/products';

    public function test_full_product_crud_lifecycle(): void
    {
        Event::fake([ProductCreated::class, ProductUpdated::class, ProductDeleted::class]);

        $user = User::factory()->create();
        $category = Category::factory()->create();
        $newCategory = Category::factory()->create();

        // CREATE
        $createResponse = $this->actingAs($user)->postJson($this->baseUrl, [
            'name' => 'Produto Lifecycle',
            'description' => 'Teste de ciclo completo.',
            'price' => 49.90,
            'category_id' => $category->id,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        $createResponse->assertStatus(201);
        $productId = $createResponse->json('data.id');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Produto Lifecycle',
            'user_id' => $user->id,
        ]);

        Event::assertDispatched(ProductCreated::class);

        // READ (show)
        $this->actingAs($user)
            ->getJson("{$this->baseUrl}/{$productId}")
            ->assertOk()
            ->assertJsonPath('data.id', $productId)
            ->assertJsonPath('data.name', 'Produto Lifecycle');

        // READ (index)
        $this->actingAs($user)
            ->getJson($this->baseUrl)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // UPDATE
        $updateResponse = $this->actingAs($user)
            ->putJson("{$this->baseUrl}/{$productId}", [
                'name' => 'Produto Atualizado',
                'price' => 79.90,
                'category_id' => $newCategory->id,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.name', 'Produto Atualizado')
            ->assertJsonPath('data.price', '79.90');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Produto Atualizado',
            'price' => 79.90,
        ]);

        Event::assertDispatched(ProductUpdated::class);

        // DELETE
        $this->actingAs($user)
            ->deleteJson("{$this->baseUrl}/{$productId}")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Produto removido com sucesso.',
            ]);

        $this->assertSoftDeleted('products', ['id' => $productId]);

        Event::assertDispatched(ProductDeleted::class);

        // VERIFY deleted product is not found
        $this->actingAs($user)
            ->getJson("{$this->baseUrl}/{$productId}")
            ->assertStatus(404);
    }
}
