<?php

namespace Tests\Feature\Product;

use App\Events\Product\ProductDeleted;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteProductTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_deletes_product_with_soft_delete(): void
    {
        Event::fake([ProductDeleted::class]);

        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->deleteJson("{$this->endpoint}/{$product->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Produto removido com sucesso.',
            ]);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_dispatches_product_deleted_event(): void
    {
        Event::fake([ProductDeleted::class]);

        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->deleteJson("{$this->endpoint}/{$product->id}")
            ->assertOk();

        Event::assertDispatched(ProductDeleted::class);
    }

    public function test_fails_with_nonexistent_product(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson("{$this->endpoint}/99999");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Produto não encontrado.',
            ]);
    }

    public function test_fails_when_unauthenticated(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("{$this->endpoint}/{$product->id}");

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Não autenticado.',
            ]);
    }
}
