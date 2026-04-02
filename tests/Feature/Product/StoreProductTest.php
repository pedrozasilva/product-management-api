<?php

namespace Tests\Feature\Product;

use App\Events\Product\ProductCreated;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StoreProductTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    private function validPayload(array $overrides = []): array
    {
        $category = Category::factory()->create();

        return array_merge([
            'name' => 'Produto Teste',
            'description' => 'Descrição do produto teste.',
            'price' => 99.90,
            'category_id' => $category->id,
            'stock_quantity' => 10,
            'is_active' => true,
        ], $overrides);
    }

    public function test_creates_product_and_returns_data(): void
    {
        Event::fake([ProductCreated::class]);

        $user = User::factory()->create();
        $payload = $this->validPayload();

        $response = $this->actingAs($user)->postJson($this->endpoint, $payload);

        $response->assertStatus(201)
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
                'message' => 'Produto criado com sucesso.',
                'data' => [
                    'name' => 'Produto Teste',
                    'description' => 'Descrição do produto teste.',
                    'price' => '99.90',
                    'stock_quantity' => 10,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Produto Teste',
            'user_id' => $user->id,
        ]);
    }

    public function test_dispatches_product_created_event(): void
    {
        Event::fake([ProductCreated::class]);

        $user = User::factory()->create();
        $payload = $this->validPayload();

        $this->actingAs($user)->postJson($this->endpoint, $payload)->assertStatus(201);

        Event::assertDispatched(ProductCreated::class);
    }

    public function test_assigns_authenticated_user_as_owner(): void
    {
        Event::fake([ProductCreated::class]);

        $user = User::factory()->create();
        $payload = $this->validPayload();

        $response = $this->actingAs($user)->postJson($this->endpoint, $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_fails_without_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['name', 'price', 'category_id', 'stock_quantity']);
    }

    public function test_fails_with_invalid_category_id(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload(['category_id' => 99999]);

        $response = $this->actingAs($user)->postJson($this->endpoint, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_fails_with_negative_price(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload(['price' => -10]);

        $response = $this->actingAs($user)->postJson($this->endpoint, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_fails_with_negative_stock_quantity(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload(['stock_quantity' => -5]);

        $response = $this->actingAs($user)->postJson($this->endpoint, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stock_quantity']);
    }

    public function test_fails_when_unauthenticated(): void
    {
        $payload = $this->validPayload();

        $response = $this->postJson($this->endpoint, $payload);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Não autenticado.',
            ]);
    }
}
