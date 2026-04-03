<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/products';

    public function test_filters_active_products(): void
    {
        $user = User::factory()->create();

        Product::factory()->count(3)->active()->create();
        Product::factory()->count(2)->inactive()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?is_active=1");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJson(['pagination' => ['total' => 3]]);
    }

    public function test_filters_inactive_products(): void
    {
        $user = User::factory()->create();

        Product::factory()->count(3)->active()->create();
        Product::factory()->count(2)->inactive()->create();

        $response = $this->actingAs($user)->getJson("{$this->endpoint}?is_active=0");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['pagination' => ['total' => 2]]);
    }
}
