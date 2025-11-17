<?php

namespace Tests\Feature;

use App\Orders\Models\OrderStatus;
use App\Products\Models\ProductOrder;
use App\Products\Models\Product;
use App\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_get_orders(): void
    {
        $po = ProductOrder::factory()->create();

        $response = $this->withHeaders([
            'x-user-id' => $po->order->customer_id,
        ])->getJson('/api/orders');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'orders' => [
                [
                    'id',
                    'customer_id',
                    'customer',
                    'status',
                    'total_amount',
                    'installments',
                    'products' => [
                        [
                            'id',
                            'name',
                            'price',
                            'total',
                            'quantity',
                        ],
                    ],
                ],
            ],
            'count',
        ]);

        $this->assertDatabaseHas('order', [
            'id' => $po->order->id,
        ]);
    }

    public function test_user_can_create_orders(): void
    {
        OrderStatus::factory()->create(['id' => 1, 'status' => 'pending']);

        $user = User::factory()->create();

        $prod1 = Product::factory()->create();
        $prod2 = Product::factory()->create();

        $payload = [
            'products' => [
                ['id' => $prod1->id, 'quantity' => 2],
                ['id' => $prod2->id, 'quantity' => 1],
            ],
            'installments' => 2,
        ];

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'order_id',
            'message',
        ]);

        $orderId = $response->json('order_id');

        $this->assertDatabaseHas('order', [
            'customer_id' => $user->id,
            'installments' => 2,
        ]);

        $this->assertDatabaseHas('product_order', [
            'order_id' => $orderId,
            'product_id' => $prod1->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('product_order', [
            'order_id' => $orderId,
            'product_id' => $prod2->id,
            'quantity' => 1,
        ]);
    }

    public function test_create_order_fails_without_user_header(): void
    {
        OrderStatus::factory()->create(['id' => 1, 'status' => 'pending']);
        $product = Product::factory()->create();

        $payload = [
            'products' => [['id' => $product->id, 'quantity' => 1]],
            'installments' => 1,
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['x-user-id']);
    }

    public function test_create_order_fails_with_invalid_user_id(): void
    {
        OrderStatus::factory()->create(['id' => 1, 'status' => 'pending']);
        $product = Product::factory()->create();

        $payload = [
            'products' => [['id' => $product->id, 'quantity' => 1]],
            'installments' => 1,
        ];

        $response = $this->withHeaders([
            'x-user-id' => 99999,
        ])->postJson('/api/orders', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['x-user-id']);
    }

    public function test_create_order_fails_without_products(): void
    {
        OrderStatus::factory()->create(['id' => 1, 'status' => 'pending']);
        $user = User::factory()->create();

        $payload = [
            'products' => [],
            'installments' => 1,
        ];

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/orders', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['products']);
    }

    public function test_create_order_fails_with_invalid_product_id(): void
    {
        OrderStatus::factory()->create(['id' => 1, 'status' => 'pending']);
        $user = User::factory()->create();

        $payload = [
            'products' => [['id' => 99999, 'quantity' => 1]],
            'installments' => 1,
        ];

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/orders', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['products.0.id']);
    }

    public function test_create_order_fails_with_invalid_quantity(): void
    {
        OrderStatus::factory()->create(['id' => 1, 'status' => 'pending']);
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $payload = [
            'products' => [['id' => $product->id, 'quantity' => 0]],
            'installments' => 1,
        ];

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/orders', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['products.0.quantity']);
    }

    public function test_create_order_fails_with_invalid_installments(): void
    {
        OrderStatus::factory()->create(['id' => 1, 'status' => 'pending']);
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $payload = [
            'products' => [['id' => $product->id, 'quantity' => 1]],
            'installments' => 0,
        ];

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/orders', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['installments']);
    }

    public function test_create_order_fails_with_missing_product_fields(): void
    {
        OrderStatus::factory()->create(['id' => 1, 'status' => 'pending']);
        $user = User::factory()->create();

        $payload = [
            'products' => [['id' => 1]],
            'installments' => 1,
        ];

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/orders', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['products.0.quantity']);
    }

    public function test_get_orders_fails_without_user_header(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['x-user-id']);
    }

    public function test_get_orders_returns_empty_for_user_without_orders(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->getJson('/api/orders');

        $response->assertStatus(200);
        $response->assertJson([
            'orders' => [],
            'count' => 0,
        ]);
    }

    public function test_user_can_get_specific_order(): void
    {
        $po = ProductOrder::factory()->create();

        $response = $this->withHeaders([
            'x-user-id' => $po->order->customer_id,
        ])->getJson('/api/orders/' . $po->order->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'orders' => [
                [
                    'id',
                    'customer_id',
                    'customer',
                    'status',
                    'total_amount',
                    'installments',
                    'products',
                ],
            ],
        ]);

        $response->assertJsonPath('orders.0.id', $po->order->id);
    }

    public function test_user_cannot_see_other_users_orders(): void
    {
        $order = ProductOrder::factory()->create()->order;
        $otherUser = User::factory()->create();

        $response = $this->withHeaders([
            'x-user-id' => $otherUser->id,
        ])->getJson('/api/orders/' . $order->id);

        $response->assertStatus(200);
        $response->assertJson([
            'orders' => [],
            'count' => 0,
        ]);
    }
}
