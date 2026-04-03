<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexProductTest extends TestCase
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

    public function test_returns_pagination_structure(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(5)->create();

        $response = $this->actingAs($user)->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson([
                'pagination' => [
                    'current_page' => 1,
                    'total' => 5,
                ],
            ]);
    }

    public function test_paginates_with_default_per_page(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(25)->create();

        $response = $this->actingAs($user)->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJson([
                'pagination' => [
                    'per_page' => 20,
                    'total' => 25,
                    'last_page' => 2,
                ],
            ]);
    }

    public function test_paginates_with_custom_per_page(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(10)->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?per_page=5");

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJson([
                'pagination' => [
                    'per_page' => 5,
                    'total' => 10,
                    'last_page' => 2,
                ],
            ]);
    }

    public function test_caps_per_page_at_100(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?per_page=200");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Erro de validação.',
            ])
            ->assertJsonValidationErrors('per_page');
    }

    public function test_navigates_to_specific_page(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(10)->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?per_page=3&page=2");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJson([
                'pagination' => [
                    'current_page' => 2,
                    'per_page' => 3,
                    'total' => 10,
                ],
            ]);
    }

    public function test_returns_empty_data_when_page_exceeds_last_page(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?page=99");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

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

    public function test_validation_fails_with_invalid_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?page=0");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Erro de validação.',
            ])
            ->assertJsonValidationErrors('page');
    }

    public function test_validation_fails_with_negative_per_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?per_page=-1");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Erro de validação.',
            ])
            ->assertJsonValidationErrors('per_page');
    }

    public function test_validation_fails_with_non_integer_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?page=abc");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('page');
    }

    public function test_validation_fails_with_name_exceeding_max_length(): void
    {
        $user = User::factory()->create();
        $longName = str_repeat('a', 256);

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?name={$longName}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_validation_fails_with_page_exceeding_max(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?page=101");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('page');
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
