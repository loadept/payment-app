<?php

namespace Tests\Feature;

use App\Products\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_get_existing_products(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'brand',
                    'price',
                ],
            ],
        ]);
    }

    public function test_get_products_pagination(): void
    {
        Product::factory()->count(15)->create();

        $response = $this->getJson('/api/products?page=2');

        $response->assertStatus(200);
        $response->assertJsonPath('current_page', 2);
        $response->assertJsonCount(5, 'data');
    }

    public function test_get_products_empty(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
