<?php

namespace Tests\Feature;

use App\Orders\Models\Order;
use App\Orders\Models\OrderStatus;
use App\Payments\Models\Payment;
use App\Payments\Models\PaymentPlan;
use App\Products\Models\Product;
use App\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        OrderStatus::factory()->create(['id' => 1, 'status' => 'pending']);
        OrderStatus::factory()->create(['id' => 2, 'status' => 'paid']);
        OrderStatus::factory()->create(['id' => 3, 'status' => 'cancelled']);
        OrderStatus::factory()->create(['id' => 4, 'status' => 'failed']);
    }

    public function test_successful_payment_marks_installment_as_paid(): void
    {
        
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'total_amount' => 300,
            'installments' => 3,
            'order_status_id' => 1,
        ]);

        $plan = PaymentPlan::factory()->create([
            'order_id' => $order->id,
            'installment_number' => 1,
            'amount' => 100,
            'is_paid' => false,
        ]);

        
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'transaction_id' => 'TXN-123456',
                'message' => 'Payment approved'
            ], 200)
        ]);

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/payments/pay', [
            'order_id' => $order->id,
            'total_payment' => 100,
            'installment_number' => 1,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'payment_id',
            'order_status',
            'message',
            'transaction_id',
        ]);

        
        $this->assertDatabaseHas('payment_plan', [
            'id' => $plan->id,
            'is_paid' => true,
        ]);

        
        $this->assertDatabaseHas('payment', [
            'payment_plan_id' => $plan->id,
            'amount' => 100,
            'is_success' => true,
            'transaction_id' => 'TXN-123456',
        ]);
    }

    public function test_payment_fails_when_external_api_rejects(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'total_amount' => 100,
            'installments' => 1,
            'order_status_id' => 1,
        ]);

        $plan = PaymentPlan::factory()->create([
            'order_id' => $order->id,
            'installment_number' => 1,
            'amount' => 100,
            'is_paid' => false,
        ]);

        Http::fake([
            '*' => Http::response([
                'success' => false,
                'transaction_id' => 'TXN-FAILED-789',
                'message' => 'Insufficient funds'
            ], 200)
        ]);

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/payments/pay', [
            'order_id' => $order->id,
            'total_payment' => 100,
            'installment_number' => 1,
        ]);

        $response->assertStatus(502);

        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => 4,
        ]);

        $this->assertDatabaseHas('payment', [
            'payment_plan_id' => $plan->id,
            'is_success' => false,
        ]);

        $this->assertDatabaseHas('payment_plan', [
            'id' => $plan->id,
            'is_paid' => false,
        ]);
    }

    public function test_payment_fails_with_incorrect_amount(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'total_amount' => 100,
            'installments' => 1,
            'order_status_id' => 1,
        ]);

        $plan = PaymentPlan::factory()->create([
            'order_id' => $order->id,
            'installment_number' => 1,
            'amount' => 100,
            'is_paid' => false,
        ]);

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/payments/pay', [
            'order_id' => $order->id,
            'total_payment' => 50, 
            'installment_number' => 1,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => "Incorrect amount. Expected: 100.00, received: 50"
        ]);

        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => 4,
        ]);
    }

    public function test_payment_fails_with_incorrect_installment_number(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'total_amount' => 300,
            'installments' => 3,
            'order_status_id' => 1,
        ]);

        PaymentPlan::factory()->create([
            'order_id' => $order->id,
            'installment_number' => 1,
            'amount' => 100,
            'is_paid' => false,
        ]);

        PaymentPlan::factory()->create([
            'order_id' => $order->id,
            'installment_number' => 2,
            'amount' => 100,
            'is_paid' => false,
        ]);

        
        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/payments/pay', [
            'order_id' => $order->id,
            'total_payment' => 100,
            'installment_number' => 2, 
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'error' => "Must pay installment 1 first, but received 2"
        ]);
    }

    public function test_order_marked_as_paid_after_all_installments(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'total_amount' => 200,
            'installments' => 2,
            'order_status_id' => 1,
        ]);

        $plan1 = PaymentPlan::factory()->create([
            'order_id' => $order->id,
            'installment_number' => 1,
            'amount' => 100,
            'is_paid' => false,
        ]);

        $plan2 = PaymentPlan::factory()->create([
            'order_id' => $order->id,
            'installment_number' => 2,
            'amount' => 100,
            'is_paid' => false,
        ]);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'transaction_id' => 'TXN-MULTI',
                'message' => 'Payment approved'
            ], 200)
        ]);

        
        $this->withHeaders(['x-user-id' => $user->id])
            ->postJson('/api/payments/pay', [
                'order_id' => $order->id,
                'total_payment' => 100,
                'installment_number' => 1,
            ])
            ->assertStatus(201)
            ->assertJson([
                'order_status' => 'pending', 
            ]);

        
        $response = $this->withHeaders(['x-user-id' => $user->id])
            ->postJson('/api/payments/pay', [
                'order_id' => $order->id,
                'total_payment' => 100,
                'installment_number' => 2,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'order_status' => 'paid', 
            'message' => 'Payment successful. All installments paid.',
        ]);

        
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => 2, 
        ]);
    }

    public function test_failed_order_can_retry_payment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'total_amount' => 100,
            'installments' => 1,
            'order_status_id' => 4, 
        ]);

        $plan = PaymentPlan::factory()->create([
            'order_id' => $order->id,
            'installment_number' => 1,
            'amount' => 100,
            'is_paid' => false,
        ]);

        
        Payment::factory()->create([
            'payment_plan_id' => $plan->id,
            'amount' => 100,
            'is_success' => false,
        ]);

        
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'transaction_id' => 'TXN-RETRY-SUCCESS',
                'message' => 'Payment approved'
            ], 200)
        ]);

        
        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/payments/pay', [
            'order_id' => $order->id,
            'total_payment' => 100,
            'installment_number' => 1,
        ]);

        $response->assertStatus(201);

        
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => 2, 
        ]);

        
        $this->assertEquals(2, Payment::where('payment_plan_id', $plan->id)->count());
    }

    public function test_cannot_pay_already_paid_installment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'total_amount' => 100,
            'installments' => 1,
            'order_status_id' => 1,
        ]);

        $plan = PaymentPlan::factory()->create([
            'order_id' => $order->id,
            'installment_number' => 1,
            'amount' => 100,
            'is_paid' => true, 
        ]);

        $response = $this->withHeaders([
            'x-user-id' => $user->id,
        ])->postJson('/api/payments/pay', [
            'order_id' => $order->id,
            'total_payment' => 100,
            'installment_number' => 1,
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'error' => "Installment number 1 for order {$order->id} is already paid"
        ]);
    }
}
