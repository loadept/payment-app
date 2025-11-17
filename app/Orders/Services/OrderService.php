<?php

namespace App\Orders\Services;

use App\Orders\Models\Order;
use App\Orders\Repositories\OrderRepository;
use App\Payments\Repositories\PaymentPlanRepository;
use App\Products\Repositories\ProductOrderRepository;
use App\Products\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private ProductOrderRepository $productOrderRepository,
        private PaymentPlanRepository $paymentPlanRepository
    ) {}

    public function getOrdersByUser(int $userId, ?int $orderId = null): array
    {
        $orders = $this->orderRepository
            ->findByCustomerId($userId, $orderId)
            ->map(fn($order) => [
                'id'            => $order->id,
                'customer_id'   => $order->customer->id,
                'customer'      => $order->customer->name,
                'status'        => $order->status->status,
                'total_amount'  => $order->total_amount,
                'installments'  => $order->installments,
                'products'      => $order->products->map(fn($product) => [
                    'id'       => $product->id,
                    'name'     => $product->name,
                    'price'    => $product->price,
                    'total'    => $product->price * $product->pivot->quantity,
                    'quantity' => $product->pivot->quantity,
                ]),
                'payment_plans' => $order->paymentPlans->map(fn($plan) => [
                    'installment_number' => $plan->installment_number,
                    'amount'             => $plan->amount,
                    'due_date'           => $plan->due_date,
                    'is_paid'            => $plan->is_paid,
                    'payments'           => $plan->payments->map(fn($payment) => [
                        'id'             => $payment->id,
                        'amount'         => $payment->amount,
                        'is_success'     => $payment->is_success,
                        'transaction_id' => $payment->transaction_id,
                        'created_at'     => $payment->created_at,
                    ]),
                ]),
            ]);

        return [
            'orders' => $orders,
            'count' => $orders->count()
        ];
    }

    public function getOrderByUser(int $userId, int $orderId): ?Order {
        return $this->orderRepository->findByUser($userId, $orderId);
    }

    public function createOrder(int $userId, array $productsData, int $installments): array
    {
        DB::beginTransaction();

        try {
            $productsDataById = collect($productsData)->keyBy('id')->toArray();

            $productIds = array_column($productsData, 'id');
            $products = $this->productRepository->findByIds($productIds);

            if ($products->count() !== count($productsData)) {
                throw ValidationException::withMessages([
                    'products' => 'One or more products are invalid'
                ]);
            }

            $totalAmount = $products->sum(
                fn($product) => $product->price * ($productsDataById[$product->id]['quantity'] ?? 0)
            );
            $order = $this->orderRepository->create([
                'customer_id'  => $userId,
                'total_amount' => $totalAmount,
                'installments' => $installments,
            ]);

            $productOrders = [];    
            foreach ($products as $product) {
                /** @var \App\Products\Models\Product $product */
                $productOrders[] = [
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $productsDataById[$product->id]['quantity'],
                ];
            }
            $this->productOrderRepository->insertBatch($productOrders);

            $installmentAmount = $totalAmount / $installments;
            $paymentPlans = [];
            for ($i = 0; $i < $installments; $i++) {
                $paymentPlans[] = [
                    'order_id'           => $order->id,
                    'installment_number' => $i + 1,
                    'amount'             => $installmentAmount,
                    'due_date'           => now()->addMonths($i),
                    'is_paid'            => false,
                ];
            }
            $this->paymentPlanRepository->insertBatch($paymentPlans);

            DB::commit();

            return [
                'order_id' => $order->id,
                'message' => 'Order created successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
